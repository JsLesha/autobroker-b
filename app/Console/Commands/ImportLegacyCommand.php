<?php

namespace App\Console\Commands;

use App\Etl\IdMapper;
use App\Etl\ImportPipeline;
use App\Etl\Sources\DatabaseLegacySource;
use App\Etl\Sources\DumpLegacySource;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Console\Command;

class ImportLegacyCommand extends Command
{
    protected $signature = 'legacy:import
        {--path= : Path to SQL dump}
        {--sanitize : Mask PII and wipe auction secrets}
        {--dry-run : Count and map only, do not write}';

    protected $description = 'ETL from legacy MySQL (dump or LEGACY_DB_*) into PostgreSQL';

    public function handle(): int
    {
        $path = $this->option('path');
        $sanitize = (bool) $this->option('sanitize');
        $dry = (bool) $this->option('dry-run');

        $dbSource = new DatabaseLegacySource;
        if (is_string($path) && $path !== '') {
            if (! is_readable($path)) {
                $this->error('Dump not readable: '.$path);

                return self::FAILURE;
            }
            $source = new DumpLegacySource($path);
            $this->info('Source: SQL dump '.$path);
            if ($source->insertCount() === 0) {
                $this->warn('Dump has no INSERT rows (schema-only). Dry-run will report zeros; connect LEGACY_DB_* or pass a data dump to load rows.');
            }
        } elseif ($dbSource->available() && filled(config('database.connections.legacy.host'))) {
            $source = $dbSource;
            $this->info('Source: MySQL connection legacy');
        } else {
            $this->error('Provide --path=dump.sql or set LEGACY_DB_HOST.');

            return self::FAILURE;
        }

        $this->info('Order: directories → identity → counterparties → lots → notes → shipping/finance → containers → wallets → chats → prebid');

        $pipeline = new ImportPipeline($source, new IdMapper, $sanitize, $dry);
        $pipeline->run();

        $rows = [];
        foreach ($pipeline->counts as $step => $count) {
            $rows[] = [$step, (string) $count];
        }
        try {
            $rows[] = ['lots_now', (string) Lot::query()->count()];
            $rows[] = ['users_now', (string) User::query()->count()];
        } catch (\Throwable) {
            $rows[] = ['lots_now', 'n/a (no target DB in this process)'];
            $rows[] = ['users_now', 'n/a'];
        }
        $this->table(['step', 'rows'], $rows);

        $this->info($dry
            ? 'Dry-run complete. Target is PostgreSQL.'
            : 'ETL finished. Target is PostgreSQL.');

        return self::SUCCESS;
    }
}
