<?php

namespace App\Jobs;

use App\Infrastructure\Messaging\IngestBus;
use App\Models\Lot;
use App\Services\LotSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexLotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $lotId)
    {
        $this->onQueue('search');
    }

    public function handle(IngestBus $bus, LotSearchService $search): void
    {
        $lot = Lot::query()->find($this->lotId);
        if (! $lot) {
            return;
        }

        $search->upsert($lot);
        $bus->publish('lots.indexed', [
            'id' => $lot->id,
            'vin' => $lot->vin,
            'lot_number' => $lot->lot_number,
            'status_order' => $lot->status_order,
        ]);
    }
}
