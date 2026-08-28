<?php

namespace App\Integrations;

use App\Models\IntegrationLog;
use Illuminate\Support\Facades\Http;

abstract class AbstractStubClient implements IntegrationClient
{
    abstract protected function configuredUrl(): string;

    public function mode(): string
    {
        return $this->configuredUrl() !== '' ? 'live' : 'stub';
    }

    public function ping(): bool
    {
        $url = $this->configuredUrl();
        if ($url === '') {
            $this->log('out', 'stub', ['ok' => true]);

            return true;
        }

        try {
            $ok = Http::timeout(5)->acceptJson()->get($url)->successful();
            $this->log('out', $ok ? 'ok' : 'error', ['url' => $url, 'live' => true]);

            return $ok;
        } catch (\Throwable $e) {
            $this->log('out', 'error', ['url' => $url], $e->getMessage());

            return false;
        }
    }

    protected function log(string $direction, string $status, array $payload, ?string $error = null): void
    {
        IntegrationLog::query()->create([
            'provider' => $this->name(),
            'direction' => $direction,
            'status' => $status,
            'payload' => $payload,
            'error' => $error,
        ]);
    }
}
