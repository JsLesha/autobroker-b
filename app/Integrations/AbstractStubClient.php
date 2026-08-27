<?php

namespace App\Integrations;

use App\Models\IntegrationLog;

abstract class AbstractStubClient implements IntegrationClient
{
    public function ping(): bool
    {
        IntegrationLog::query()->create([
            'provider' => $this->name(),
            'direction' => 'out',
            'status' => 'stub',
            'payload' => ['ok' => true],
        ]);

        return true;
    }
}
