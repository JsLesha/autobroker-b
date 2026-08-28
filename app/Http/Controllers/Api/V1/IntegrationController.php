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
use App\Models\User;
use App\Models\UserNotification;
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
            'mode' => $c->mode(),
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

    public function aecLookup(Request $request, AecClient $client): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('lots.read'), 403);
        $data = $request->validate(['vin' => ['required', 'string', 'max:32']]);
        $info = $client->lookup($data['vin']);
        IngestExternalEventJob::dispatch('aec', $info);

        return response()->json($info);
    }

    public function copartLookup(Request $request, CopartClient $client): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('lots.read'), 403);
        $data = $request->validate(['lot' => ['required', 'string', 'max:64']]);
        $info = $client->lookup($data['lot']);
        IngestExternalEventJob::dispatch('copart', $info);

        return response()->json($info);
    }

    public function bitrixLead(Request $request, BitrixClient $client): JsonResponse
    {
        abort_unless($request->user()?->isAdminLike() || $request->user()?->hasPermission('lots.update'), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'name' => ['nullable', 'string', 'max:191'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email'],
        ]);
        $info = $client->pushLead($data);
        IngestExternalEventJob::dispatch('bitrix', $info);

        return response()->json($info, 201);
    }

    public function telegramSend(Request $request, TelegramClient $client): JsonResponse
    {
        abort_unless($request->user()?->isAdminLike(), 403);
        $data = $request->validate([
            'chat_id' => ['required'],
            'text' => ['required', 'string', 'max:4000'],
        ]);
        $info = $client->sendMessage($data['chat_id'], $data['text']);

        return response()->json($info);
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

        IngestExternalEventJob::dispatch('telegram', $request->all());
        $from = data_get($request->all(), 'message.from.id');
        $text = data_get($request->all(), 'message.text');
        if ($from && $text) {
            $user = User::query()->where('telegram_id', $from)->first();
            if ($user) {
                UserNotification::query()->create([
                    'user_id' => $user->id,
                    'title' => 'Telegram',
                    'body' => is_string($text) ? $text : json_encode($text),
                ]);
            }
        }

        return response()->json(['ok' => true]);
    }
}
