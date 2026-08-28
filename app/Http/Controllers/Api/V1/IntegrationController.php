<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Integrations\Aec\AecClient;
use App\Integrations\Bitrix\BitrixClient;
use App\Integrations\Copart\CopartClient;
use App\Integrations\Telegram\TelegramClient;
use App\Integrations\VinCheck\VinCheckClient;
use App\Jobs\IngestExternalEventJob;
use App\Models\VinCheckReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdminLike(), 403);

        $clients = [
            app(AecClient::class),
            app(CopartClient::class),
            app(VinCheckClient::class),
            app(BitrixClient::class),
            app(TelegramClient::class),
        ];

        return response()->json(array_map(fn ($c) => [
            'provider' => $c->name(),
            'ok' => $c->ping(),
            'mode' => 'stub',
        ], $clients));
    }

    public function vinCallback(Request $request): JsonResponse
    {
        $secret = (string) config('services.vin_check.webhook_secret');
        abort_if($secret === '', 503);
        abort_unless(hash_equals($secret, (string) $request->header('X-Webhook-Secret')), 403);

        $vin = (string) $request->string('vin');
        if ($vin !== '') {
            VinCheckReport::query()->create([
                'vin' => $vin,
                'info' => $request->all(),
            ]);
        }

        IngestExternalEventJob::dispatch('vin-check', $request->all());

        return response()->json(['ok' => true]);
    }

    public function telegramWebhook(Request $request): JsonResponse
    {
        $secret = (string) config('services.telegram.webhook_secret');
        abort_if($secret === '', 503);
        abort_unless(hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);

        return response()->json(['ok' => true]);
    }
}
