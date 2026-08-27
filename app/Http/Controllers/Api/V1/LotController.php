<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lot\StoreLotRequest;
use App\Models\ChatMessage;
use App\Models\Lot;
use App\Models\LotImage;
use App\Services\LotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LotController extends Controller
{
    public function __construct(private readonly LotService $lots)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lot::class);

        $query = Lot::query()->with(['brand', 'model', 'auction', 'pricing'])->orderByDesc('id');

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('vin', 'like', "%{$search}%")
                    ->orWhere('lot_number', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('archived')) {
            $query->where('archived', true);
        } else {
            $query->where('archived', false);
        }

        return response()->json($query->paginate(40));
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
            'brand', 'model', 'auction', 'pricing', 'shipping', 'images',
            'financeLines', 'invoices', 'payments', 'chat.messages.user',
        ]));
    }

    public function update(Request $request, Lot $lot): JsonResponse
    {
        $this->authorize('update', $lot);

        $lot->update($request->validate([
            'status_order' => ['sometimes', 'string', 'max:64'],
            'status_shipping' => ['sometimes', 'string', 'max:64'],
            'status_finance' => ['sometimes', 'string', 'max:64'],
            'notes' => ['nullable', 'string'],
            'archived' => ['sometimes', 'boolean'],
        ]));

        return response()->json($lot->fresh(['pricing', 'shipping']));
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
}
