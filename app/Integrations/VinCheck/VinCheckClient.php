<?php

namespace App\Integrations\VinCheck;

use App\Integrations\AbstractStubClient;
use Illuminate\Support\Facades\Http;

class VinCheckClient extends AbstractStubClient
{
    public function name(): string
    {
        return 'vin_check';
    }

    public function lookup(string $vin): array
    {
        $base = rtrim((string) config('services.vin_check.base_url'), '/');
        $key = (string) config('services.vin_check.api_key');

        if ($base !== '') {
            try {
                $response = Http::timeout(8)
                    ->acceptJson()
                    ->withToken($key)
                    ->get($base.'/decode', ['vin' => $vin]);
                if ($response->successful()) {
                    $payload = $response->json() ?? [];
                    $this->log('out', 'ok', ['vin' => $vin, 'live' => true]);

                    return array_merge(['vin' => $vin, 'source' => 'live'], $payload);
                }
                $this->log('out', 'error', ['vin' => $vin], $response->body());
            } catch (\Throwable $e) {
                $this->log('out', 'error', ['vin' => $vin], $e->getMessage());
            }
        }

        $info = [
            'vin' => $vin,
            'source' => 'stub',
            'checked_at' => now()->toIso8601String(),
        ];
        $this->log('out', 'stub', $info);

        return $info;
    }

    protected function configuredUrl(): string
    {
        return rtrim((string) config('services.vin_check.base_url'), '/');
    }
}
