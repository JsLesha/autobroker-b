<?php

namespace App\Console\Commands;

use App\Models\Lot;
use App\Services\LotSearchService;
use Illuminate\Console\Command;

class ReindexLotsCommand extends Command
{
    protected $signature = 'lots:reindex {--chunk=200 : Rows per batch}';

    protected $description = 'Upsert all lots into Meilisearch index';

    public function handle(LotSearchService $search): int
    {
        if (rtrim((string) config('services.meilisearch.host'), '/') === '') {
            $this->error('MEILISEARCH_HOST is not configured.');

            return self::FAILURE;
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $total = Lot::query()->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $indexed = 0;
        Lot::query()->orderBy('id')->chunkById($chunk, function ($lots) use ($search, $bar, &$indexed) {
            foreach ($lots as $lot) {
                $search->upsert($lot);
                $indexed++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Reindexed {$indexed} lot(s).");

        return self::SUCCESS;
    }
}
