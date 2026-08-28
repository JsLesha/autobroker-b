<?php

namespace App\Integrations\Bitrix;

use App\Integrations\AbstractStubClient;
use Illuminate\Support\Facades\Http;

class BitrixClient extends AbstractStubClient
{
    public function name(): string
    {
        return 'bitrix';
    }

    protected function configuredUrl(): string
    {
        return rtrim((string) config('services.bitrix.webhook'), '/');
    }

    public function pushLead(array $fields): array
    {
        $base = $this->configuredUrl();
        if ($base !== '') {
            try {
                $response = Http::timeout(10)->acceptJson()->post($base.'/crm.lead.add.json', [
                    'fields' => $fields,
                ]);
                if ($response->successful()) {
                    $this->log('out', 'ok', ['live' => true, 'fields' => $fields]);

                    return array_merge(['source' => 'live'], $response->json() ?? []);
                }
                $this->log('out', 'error', $fields, $response->body());
            } catch (\Throwable $e) {
                $this->log('out', 'error', $fields, $e->getMessage());
            }
        }

        $info = ['source' => 'stub', 'fields' => $fields, 'id' => 'stub-'.now()->timestamp];
        $this->log('out', 'stub', $info);

        return $info;
    }
}
