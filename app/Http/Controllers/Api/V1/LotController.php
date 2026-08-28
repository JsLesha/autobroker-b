<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lot\StoreLotRequest;
use App\Http\Requests\Lot\UpdateLotRequest;
use App\Models\ChatMessage;
use App\Models\Lot;
use App\Models\LotDrop;
use App\Models\LotImage;
use App\Models\LotNote;
use App\Models\UserNotification;
use App\Services\LotSearchService;
use App\Services\LotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LotController extends Controller
{
    public function __construct(
        private readonly LotService $lots,
        private readonly LotSearchService $search,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lot::class);

        $query = Lot::query()
            ->visibleTo($request->user())
            ->with(['brand', 'model', 'auction', 'pricing', 'buyer', 'counterparty', 'client', 'route', 'orderStatus'])
            ->orderByDesc('id');

        $q = $request->string('q')->toString();
        $fromSearch = false;
        if ($q !== '') {
            $ids = $this->search->searchIds($q);
            if (is_array($ids)) {
                $fromSearch = true;
                if ($ids === []) {
                    return response()->json($query->whereRaw('1 = 0')->paginate($request->integer('limit') ?: 40));
                }
                $query->whereIn('id', $ids);
            }
        }

        $this->applyFilters($query, $request, $fromSearch);

        if ($request->boolean('auction_participant')) {
            $query->where('is_auction_participant', true);
        }

        return response()->json($query->paginate($request->integer('limit') ?: 40));
    }

    public function dictionaries(): JsonResponse
    {
        $tables = [
            'fuels' => 'transport_fuels',
            'drives' => 'transport_drives',
            'transmissions' => 'transport_transmissions',
            'highlights' => 'transport_highlights',
            'keys' => 'transport_keys',
            'odometer_units' => 'transport_odometer_units',
            'run_statuses' => 'transport_run_statuses',
            'sizes' => 'transport_sizes',
            'colors' => 'vehicle_colors',
            'damages' => 'vehicle_damages',
        ];
        $out = [];
        foreach ($tables as $key => $table) {
            $out[$key] = Schema::hasTable($table) ? DB::table($table)->orderBy('id')->get() : [];
        }

        return response()->json($out);
    }

    public function store(StoreLotRequest $request): JsonResponse
    {
        $lot = $this->lots->create($request->user(), $request->validated());

        return response()->json($lot, 201);
    }

    public function show(Lot $lot): JsonResponse
    {
        $this->authorize('view', $lot);

        return response()->json($lot->load([
            'brand', 'model', 'auction', 'pricing', 'shipping', 'shippingEvents',
            'vehicle', 'client', 'route', 'images', 'lotNotes.user',
            'financeLines', 'invoices', 'payments', 'chat.messages.user',
            'buyer', 'counterparty', 'credential', 'orderStatus', 'drops', 'notifications',
        ]));
    }

    public function update(UpdateLotRequest $request, Lot $lot): JsonResponse
    {
        return response()->json($this->lots->update($lot, $request->validated()));
    }

    public function storeImage(Request $request, Lot $lot): JsonResponse
    {
        $this->authorize('update', $lot);

        $request->validate([
            'file' => ['required', 'file', 'image', 'max:8192'],
        ]);

        $path = $request->file('file')->store('lots/'.$lot->id, 'local');

        $image = LotImage::query()->create([
            'lot_id' => $lot->id,
            'path' => $path,
            'type' => 'upload',
            'is_cover' => $lot->images()->count() === 0,
        ]);

        return response()->json($image, 201);
    }

    public function notes(Lot $lot): JsonResponse
    {
        $this->authorize('view', $lot);

        return response()->json($lot->lotNotes()->with('user')->orderByDesc('id')->get());
    }

    public function storeNote(Request $request, Lot $lot): JsonResponse
    {
        $this->authorize('update', $lot);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'noted_on' => ['nullable', 'date'],
        ]);
        $note = LotNote::query()->create([
            'lot_id' => $lot->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'noted_on' => $data['noted_on'] ?? now()->toDateString(),
            'lot_label' => $lot->lot_number,
        ]);

        return response()->json($note->load('user'), 201);
    }

    public function storeMessage(Request $request, Lot $lot): JsonResponse
    {
        $this->authorize('view', $lot);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $chat = $lot->chat()->firstOrCreate(['type' => 'lot'], ['title' => 'Лот '.$lot->vin]);

        $message = ChatMessage::query()->create([
            'chat_id' => $chat->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return response()->json($message->load('user'), 201);
    }

    public function messages(Request $request, Lot $lot): JsonResponse
    {
        $this->authorize('view', $lot);
        $chat = $lot->chat()->first();
        if (! $chat) {
            return response()->json([]);
        }
        $query = $chat->messages()->with('user')->orderBy('id');
        if ($since = $request->integer('since_id')) {
            $query->where('id', '>', $since);
        }

        return response()->json($query->limit(100)->get());
    }

    public function storeDrop(Request $request, Lot $lot): JsonResponse
    {
        $this->authorize('update', $lot);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'file' => ['nullable', 'file', 'max:12288'],
        ]);
        $path = $request->file('file')?->store('lots/'.$lot->id.'/drops', 'local');
        $drop = LotDrop::query()->create([
            'lot_id' => $lot->id,
            'title' => $data['title'],
            'document_path' => $path,
        ]);

        return response()->json($drop, 201);
    }

    public function notifications(Lot $lot): JsonResponse
    {
        $this->authorize('view', $lot);

        return response()->json($lot->notifications()->orderByDesc('id')->get());
    }

    public function storeNotification(Request $request, Lot $lot): JsonResponse
    {
        $this->authorize('update', $lot);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'body' => ['nullable', 'string', 'max:5000'],
        ]);
        $targetId = $lot->buyer_user_id ?: $request->user()->id;
        $note = UserNotification::query()->create([
            'user_id' => $targetId,
            'lot_id' => $lot->id,
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
        ]);

        return response()->json($note, 201);
    }

    public function export(Lot $lot): JsonResponse
    {
        $this->authorize('view', $lot);
        $lot->load(['vehicle', 'client', 'pricing', 'route', 'buyer', 'counterparty', 'auction']);

        return response()->json([
            'vin' => $lot->vin,
            'lot_number' => $lot->lot_number,
            'status_order' => $lot->status_order,
            'auction' => $lot->auction?->name ?? $lot->auction?->code,
            'buyer' => $lot->buyer?->only(['id', 'name', 'email', 'nickname']),
            'counterparty' => $lot->counterparty?->only(['id', 'name', 'type']),
            'vehicle' => $lot->vehicle,
            'client' => $lot->client,
            'pricing' => $lot->pricing,
            'route' => $lot->route,
        ]);
    }

    private function applyFilters($query, Request $request, bool $skipQuery = false): void
    {
        if (! $skipQuery && ($search = $request->string('q')->toString())) {
            $query->where(function ($q) use ($search) {
                $q->where('vin', 'like', "%{$search}%")
                    ->orWhere('lot_number', 'like', "%{$search}%")
                    ->orWhere('transport_name', 'like', "%{$search}%");
            });
        }
        if ($vin = $request->string('vin')->toString()) {
            $query->where('vin', 'like', "%{$vin}%");
        }
        if ($lot = $request->string('lot')->toString()) {
            $query->where('lot_number', 'like', "%{$lot}%");
        }
        if ($status = $request->string('status_order')->toString()) {
            $query->where('status_order', $status);
        }
        if ($statusId = $request->integer('status_order_id') ?: $request->integer('status_id')) {
            $query->where('status_order_id', $statusId);
        }
        if ($buyer = $request->integer('buyer_user_id')) {
            $query->where('buyer_user_id', $buyer);
        }
        if ($buyerName = $request->string('buyer')->toString()) {
            $query->whereHas('buyer', function ($q) use ($buyerName) {
                $q->where(function ($inner) use ($buyerName) {
                    $inner->where('nickname', 'like', "%{$buyerName}%")
                        ->orWhere('name', 'like', "%{$buyerName}%")
                        ->orWhere('email', 'like', "%{$buyerName}%");
                });
            });
        }
        if ($mark = $request->string('markModel')->toString() ?: $request->string('model')->toString()) {
            $query->where(function ($q) use ($mark) {
                $q->where('transport_name', 'like', "%{$mark}%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$mark}%"))
                    ->orWhereHas('model', fn ($m) => $m->where('name', 'like', "%{$mark}%"));
            });
        }
        if ($cp = $request->integer('counterparty_id')) {
            $query->where('counterparty_id', $cp);
        }
        if ($city = $request->integer('city_id')) {
            $query->whereHas('route', function ($q) use ($city) {
                $q->where(function ($inner) use ($city) {
                    $inner->where('city_to_id', $city)->orWhere('city_from_id', $city);
                });
            });
        }
        if ($port = $request->integer('port_id')) {
            $query->whereHas('route', fn ($q) => $q->where('port_to_id', $port));
        }
        if ($from = $request->string('date_from')->toString()) {
            $query->whereDate('date_buy', '>=', $from);
        }
        if ($to = $request->string('date_to')->toString()) {
            $query->whereDate('date_buy', '<=', $to);
        }
        if ($email = $request->string('email')->toString()) {
            $query->whereHas('client', fn ($q) => $q->where('email', 'like', "%{$email}%"));
        }
        if ($surname = $request->string('second_name')->toString()) {
            $query->whereHas('client', function ($q) use ($surname) {
                $q->where(function ($inner) use ($surname) {
                    $inner->where('last_name', 'like', "%{$surname}%")
                        ->orWhere('full_name', 'like', "%{$surname}%");
                });
            });
        }
        if ($request->boolean('archived')) {
            $query->where('archived', true);
        } else {
            $query->where('archived', false);
        }
    }
}
