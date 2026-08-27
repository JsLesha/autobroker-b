<?php

namespace App\Integrations\Aec;

use App\Integrations\AbstractStubClient;

class AecClient extends AbstractStubClient
{
    public function name(): string
    {
        return 'aec';
    }
}
