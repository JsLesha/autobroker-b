<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PrebidBid;
use App\Models\PrebidListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrebidController extends Controller
{
    public function listings(Request $request): JsonResponse
    {
        $query = PrebidListing::query()->with('seller')->orderByDesc('id');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(40));
    }

    public function show(PrebidListing $listing): JsonResponse
    {
        return response()->json($listing->load(['seller', 'bids.user']));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('prebid.create'), 403);

        $data = $request->validate([
            'vin' => ['nullable', 'string', 'max:32'],
            'year' => ['nullable', 'integer', 'min:1980', 'max:2100'],
            'start_price' => ['required', 'numeric', 'min:0'],
            'buy_now_price' => ['nullable', 'numeric', 'min:0'],
            'lot_id' => ['nullable', 'exists:lots,id'],
        ]);

        $listing = PrebidListing::query()->create([
            'seller_id' => $request->user()->id,
            'lot_id' => $data['lot_id'] ?? null,
            'vin' => $data['vin'] ?? null,
            'year' => $data['year'] ?? null,
            'start_price' => $data['start_price'],
            'current_price' => $data['start_price'],
            'buy_now_price' => $data['buy_now_price'] ?? null,
            'status' => 'moderation',
        ]);

        return response()->json($listing, 201);
    }

    public function bid(Request $request, PrebidListing $listing): JsonResponse
    {
        abort_unless($request->user(), 401);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'buy_now' => ['sometimes', 'boolean'],
        ]);

        $bid = DB::transaction(function () use ($listing, $request, $data) {
            $listing->refresh();
            $amount = (float) $data['amount'];
            $buyNow = (bool) ($data['buy_now'] ?? false);

            if ($buyNow && $listing->buy_now_price && $amount < (float) $listing->buy_now_price) {
                abort(422, 'Сумма buy now меньше цены.');
            }

            if (! $buyNow && $amount <= (float) $listing->current_price) {
                abort(422, 'Ставка должна быть выше текущей.');
            }

            $bid = PrebidBid::query()->create([
                'listing_id' => $listing->id,
                'user_id' => $request->user()->id,
                'amount' => $amount,
                'is_buy_now' => $buyNow,
            ]);

            $listing->update([
                'current_price' => $amount,
                'status' => $buyNow ? 'sold' : $listing->status,
            ]);

            return $bid;
        });

        return response()->json($bid, 201);
    }

    public function moderate(Request $request, PrebidListing $listing): JsonResponse
    {
        abort_unless($request->user()?->isAdminLike(), 403);

        $data = $request->validate(['status' => ['required', 'in:confirmed,rejected']]);
        $listing->update(['status' => $data['status']]);

        return response()->json($listing);
    }
}
