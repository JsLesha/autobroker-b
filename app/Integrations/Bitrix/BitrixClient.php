<?php

namespace App\Integrations\Bitrix;

use App\Integrations\AbstractStubClient;

class BitrixClient extends AbstractStubClient
{
    public function name(): string
    {
        return 'bitrix';
    }
}
