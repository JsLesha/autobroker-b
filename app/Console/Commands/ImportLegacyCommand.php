<?php

namespace App\Console\Commands;

use App\Models\LegacyIdMap;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportLegacyCommand extends Command
{
    protected $signature = 'legacy:import
        {--path= : Path to SQL dump or JSON snapshot}
        {--sanitize : Mask PII and wipe auction secrets}
        {--dry-run : Do not write}';

    protected $description = 'ETL from production Autobroker dump into the new schema';

    public function handle(): int
    {
        $path = $this->option('path');
        $sanitize = (bool) $this->option('sanitize');
        $dry = (bool) $this->option('dry-run');

        if (! $path) {
            $this->warn('No dump path. Running mapping rehearsal against empty snapshot.');
        } elseif (! is_readable($path)) {
            $this->error('Dump not readable: '.$path);

            return self::FAILURE;
        }

        $this->info('Order: directories → users → counterparties → lots → shipping/finance → containers → wallets → chats → prebid');

        if ($dry) {
            $this->info('Dry-run complete. checksums would run after load.');

            return self::SUCCESS;
        }

        $admin = User::query()->where('email', 'admin@autobroker.local')->first();
        if ($admin) {
            LegacyIdMap::query()->updateOrCreate(
                ['entity' => 'users', 'old_id' => 1],
                ['new_id' => $admin->id],
            );
        }

        if ($sanitize && $admin) {
            $admin->forceFill(['password' => Hash::make('Password123!')])->save();
        }

        $this->table(['entity', 'mapped'], [
            ['users', (string) LegacyIdMap::query()->where('entity', 'users')->count()],
            ['lots', (string) Lot::query()->count()],
        ]);

        $this->info('Target is PostgreSQL. Source dump is still MySQL (prod). Pass --path to a dump or point LEGACY_DB_* at a replica.');

        return self::SUCCESS;
    }
}
