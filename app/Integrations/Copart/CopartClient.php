<?php

namespace App\Integrations\Copart;

use App\Integrations\AbstractStubClient;

class CopartClient extends AbstractStubClient
{
    public function name(): string
    {
        return 'copart';
    }
}
