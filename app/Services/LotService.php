<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\Lot;
use App\Models\LotPricing;
use App\Models\ShippingRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LotService
{
    public function create(User $actor, array $data): Lot
    {
        return DB::transaction(function () use ($actor, $data) {
            $lot = Lot::query()->create([
                ...$data,
                'created_by' => $actor->id,
                'status_order' => $data['status_order'] ?? 'new',
            ]);

            LotPricing::query()->create([
                'lot_id' => $lot->id,
                'hammer_price' => $data['hammer_price'] ?? 0,
                'fees' => $data['fees'] ?? 0,
                'total' => ($data['hammer_price'] ?? 0) + ($data['fees'] ?? 0),
            ]);

            ShippingRecord::query()->create([
                'lot_id' => $lot->id,
                'status' => 'pending',
            ]);

            Chat::query()->create([
                'type' => 'lot',
                'lot_id' => $lot->id,
                'title' => 'Лот '.$lot->vin,
            ]);

            return $lot->load(['pricing', 'shipping', 'images', 'brand', 'model', 'auction']);
        });
    }
}
