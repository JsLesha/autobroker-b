<?php

namespace App\Integrations\AuctionAgent;

use App\Integrations\AbstractStubClient;
use Illuminate\Support\Facades\Http;

class AuctionAgentClient extends AbstractStubClient
{
    public function name(): string
    {
        return 'auction_agent';
    }

    protected function configuredUrl(): string
    {
        return rtrim((string) config('services.auction_agent.url'), '/');
    }

    public function session(int $credentialId): array
    {
        $base = $this->configuredUrl();
        if ($base !== '') {
            try {
                $response = Http::timeout(10)->acceptJson()->post($base.'/session', [
                    'credential_id' => $credentialId,
                ]);
                if ($response->successful()) {
                    $this->log('out', 'ok', ['credential_id' => $credentialId, 'live' => true]);

                    return array_merge(['source' => 'live'], $response->json() ?? []);
                }
                $this->log('out', 'error', ['credential_id' => $credentialId], $response->body());
            } catch (\Throwable $e) {
                $this->log('out', 'error', ['credential_id' => $credentialId], $e->getMessage());
            }
        }

        $info = [
            'source' => 'stub',
            'credential_id' => $credentialId,
            'session_id' => 'stub-'.now()->timestamp,
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
        ];
        $this->log('out', 'stub', $info);

        return $info;
    }

    public function submitBid(string $sessionId, string $lotNumber, float $amount): array
    {
        $base = $this->configuredUrl();
        if ($base !== '') {
            try {
                $response = Http::timeout(15)->acceptJson()->post($base.'/bid', [
                    'session_id' => $sessionId,
                    'lot' => $lotNumber,
                    'amount' => $amount,
                ]);
                if ($response->successful()) {
                    $this->log('out', 'ok', ['lot' => $lotNumber, 'live' => true]);

                    return array_merge(['source' => 'live'], $response->json() ?? []);
                }
                $this->log('out', 'error', ['lot' => $lotNumber], $response->body());
            } catch (\Throwable $e) {
                $this->log('out', 'error', ['lot' => $lotNumber], $e->getMessage());
            }
        }

        $info = [
            'source' => 'stub',
            'session_id' => $sessionId,
            'lot' => $lotNumber,
            'amount' => $amount,
            'status' => 'queued',
        ];
        $this->log('out', 'stub', $info);

        return $info;
    }
}
