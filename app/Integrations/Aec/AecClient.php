<?php

namespace App\Integrations\Aec;

use App\Integrations\AbstractStubClient;
use Illuminate\Support\Facades\Http;

class AecClient extends AbstractStubClient
{
    public function name(): string
    {
        return 'aec';
    }

    protected function configuredUrl(): string
    {
        return rtrim((string) config('services.aec.url'), '/');
    }

    public function lookup(string $vin): array
    {
        $base = $this->configuredUrl();
        if ($base !== '') {
            try {
                $response = Http::timeout(10)->acceptJson()->get($base.'/vehicles', ['vin' => $vin]);
                if ($response->successful()) {
                    $this->log('out', 'ok', ['vin' => $vin, 'live' => true]);

                    return array_merge(['vin' => $vin, 'source' => 'live'], $response->json() ?? []);
                }
                $this->log('out', 'error', ['vin' => $vin], $response->body());
            } catch (\Throwable $e) {
                $this->log('out', 'error', ['vin' => $vin], $e->getMessage());
            }
        }

        $info = ['vin' => $vin, 'source' => 'stub', 'checked_at' => now()->toIso8601String()];
        $this->log('out', 'stub', $info);

        return $info;
    }
}
