<?php

namespace App\Integrations\VinCheck;

use App\Integrations\AbstractStubClient;

class VinCheckClient extends AbstractStubClient
{
    public function name(): string
    {
        return 'vin_check';
    }
}
