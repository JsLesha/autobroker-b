<?php

namespace App\Integrations;

interface IntegrationClient
{
    public function name(): string;

    public function ping(): bool;
}
