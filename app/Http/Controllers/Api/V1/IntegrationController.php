<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Integrations\Aec\AecClient;
use App\Integrations\Bitrix\BitrixClient;
use App\Integrations\Copart\CopartClient;
use App\Integrations\Telegram\TelegramClient;
use App\Integrations\VinCheck\VinCheckClient;
use App\Jobs\IngestExternalEventJob;
use App\Models\IntegrationLog;
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
            'mode' => $c->name() === 'vin_check' && filled(config('services.vin_check.base_url')) ? 'live' : 'stub',
        ], $clients));
    }

    public function logs(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdminLike(), 403);

        return response()->json(
            IntegrationLog::query()->orderByDesc('id')->limit(100)->get()
        );
    }

    public function vinCheck(Request $request, VinCheckClient $client): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('lots.read'), 403);
        $data = $request->validate(['vin' => ['required', 'string', 'max:32']]);
        $info = $client->lookup($data['vin']);
        $report = VinCheckReport::query()->create([
            'vin' => $data['vin'],
            'user_id' => $request->user()->id,
            'info' => $info,
        ]);
        IngestExternalEventJob::dispatch('vin-check', $info);

        return response()->json($report, 201);
    }

    public function vinReports(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('lots.read'), 403);
        $query = VinCheckReport::query()->orderByDesc('id');
        if ($vin = $request->string('vin')->toString()) {
            $query->where('vin', $vin);
        }

        return response()->json($query->limit(50)->get());
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
