<?php

namespace App\Integrations\Telegram;

use App\Integrations\AbstractStubClient;
use Illuminate\Support\Facades\Http;

class TelegramClient extends AbstractStubClient
{
    public function name(): string
    {
        return 'telegram';
    }

    protected function configuredUrl(): string
    {
        $token = (string) config('services.telegram.bot_token');

        return $token !== '' ? 'https://api.telegram.org/bot'.$token : '';
    }

    public function ping(): bool
    {
        $base = $this->configuredUrl();
        if ($base === '') {
            $this->log('out', 'stub', ['ok' => true]);

            return true;
        }

        try {
            $ok = Http::timeout(5)->acceptJson()->get($base.'/getMe')->successful();
            $this->log('out', $ok ? 'ok' : 'error', ['live' => true]);

            return $ok;
        } catch (\Throwable $e) {
            $this->log('out', 'error', [], $e->getMessage());

            return false;
        }
    }

    public function sendMessage(int|string $chatId, string $text): array
    {
        $base = $this->configuredUrl();
        if ($base !== '') {
            try {
                $response = Http::timeout(10)->acceptJson()->post($base.'/sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $text,
                ]);
                if ($response->successful()) {
                    $this->log('out', 'ok', ['chat_id' => $chatId, 'live' => true]);

                    return array_merge(['source' => 'live'], $response->json() ?? []);
                }
                $this->log('out', 'error', ['chat_id' => $chatId], $response->body());
            } catch (\Throwable $e) {
                $this->log('out', 'error', ['chat_id' => $chatId], $e->getMessage());
            }
        }

        $info = ['source' => 'stub', 'chat_id' => $chatId, 'text' => $text];
        $this->log('out', 'stub', $info);

        return $info;
    }
}
