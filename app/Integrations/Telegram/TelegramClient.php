<?php

namespace App\Integrations\Telegram;

use App\Integrations\AbstractStubClient;

class TelegramClient extends AbstractStubClient
{
    public function name(): string
    {
        return 'telegram';
    }
}
