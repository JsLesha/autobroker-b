<?php

namespace App\Services;

use App\Models\Lot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LotSearchService
{
    public function upsert(Lot $lot): void
    {
        $host = $this->host();
        if ($host === '') {
            return;
        }

        try {
            Http::timeout(5)
                ->withToken((string) config('services.meilisearch.key'))
                ->acceptJson()
                ->post($host.'/indexes/lots/documents', [[
                    'id' => $lot->id,
                    'vin' => $lot->vin,
                    'lot_number' => $lot->lot_number,
                    'transport_name' => $lot->transport_name,
                    'status_order' => $lot->status_order,
                    'buyer_user_id' => $lot->buyer_user_id,
                ]]);
        } catch (Throwable $e) {
            Log::debug('meilisearch upsert skipped: '.$e->getMessage());
        }
    }

    /**
     * @return list<int>|null null means search backend unavailable
     */
    public function searchIds(string $query): ?array
    {
        $host = $this->host();
        if ($host === '' || $query === '') {
            return null;
        }

        try {
            $response = Http::timeout(3)
                ->withToken((string) config('services.meilisearch.key'))
                ->acceptJson()
                ->post($host.'/indexes/lots/search', [
                    'q' => $query,
                    'limit' => 40,
                ]);
            if (! $response->successful()) {
                return null;
            }

            return collect($response->json('hits') ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
        } catch (Throwable $e) {
            Log::debug('meilisearch search skipped: '.$e->getMessage());

            return null;
        }
    }

    private function host(): string
    {
        return rtrim((string) config('services.meilisearch.host'), '/');
    }
}
