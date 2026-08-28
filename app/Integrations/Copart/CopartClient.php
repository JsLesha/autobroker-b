<?php

namespace App\Integrations\Copart;

use App\Integrations\AbstractStubClient;
use Illuminate\Support\Facades\Http;

class CopartClient extends AbstractStubClient
{
    public function name(): string
    {
        return 'copart';
    }

    protected function configuredUrl(): string
    {
        return rtrim((string) config('services.copart.url'), '/');
    }

    public function lookup(string $lotNumber): array
    {
        $base = $this->configuredUrl();
        if ($base !== '') {
            try {
                $response = Http::timeout(10)->acceptJson()->get($base.'/lots/'.$lotNumber);
                if ($response->successful()) {
                    $this->log('out', 'ok', ['lot' => $lotNumber, 'live' => true]);

                    return array_merge(['lot' => $lotNumber, 'source' => 'live'], $response->json() ?? []);
                }
                $this->log('out', 'error', ['lot' => $lotNumber], $response->body());
            } catch (\Throwable $e) {
                $this->log('out', 'error', ['lot' => $lotNumber], $e->getMessage());
            }
        }

        $info = ['lot' => $lotNumber, 'source' => 'stub', 'checked_at' => now()->toIso8601String()];
        $this->log('out', 'stub', $info);

        return $info;
    }
}
