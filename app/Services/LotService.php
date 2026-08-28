<?php

namespace App\Services;

use App\Jobs\IndexLotJob;
use App\Models\Chat;
use App\Models\Lot;
use App\Models\LotClient;
use App\Models\LotPricing;
use App\Models\LotRoute;
use App\Models\LotVehicle;
use App\Models\ShippingRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LotService
{
    public function create(User $actor, array $data): Lot
    {
        return DB::transaction(function () use ($actor, $data) {
            $lot = Lot::query()->create([
                'vin' => $data['vin'],
                'lot_number' => $data['lot_number'] ?? null,
                'transport_name' => $data['transport_name'] ?? null,
                'auction_id' => $data['auction_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'model_id' => $data['model_id'] ?? null,
                'year' => $data['year'] ?? null,
                'date_buy' => $data['date_buy'] ?? null,
                'notes' => $data['notes'] ?? null,
                'outside' => $data['outside'] ?? false,
                'is_auction_participant' => $data['is_auction_participant'] ?? false,
                'is_unformat_vin' => $data['is_unformat_vin'] ?? false,
                'created_by' => $actor->id,
                'buyer_user_id' => $data['buyer_user_id'] ?? $actor->id,
                'counterparty_id' => $data['counterparty_id'] ?? null,
                'credential_id' => $data['credential_id'] ?? null,
                'doc_fee_id' => $data['doc_fee_id'] ?? null,
                'transportation_agent_id' => $data['transportation_agent_id'] ?? null,
                'status_order' => $data['status_order'] ?? 'new',
            ]);

            LotPricing::query()->create([
                'lot_id' => $lot->id,
                'hammer_price' => $data['hammer_price'] ?? 0,
                'fees' => $data['fees'] ?? 0,
                'start_price' => $data['start_price'] ?? 0,
                'total' => ($data['hammer_price'] ?? 0) + ($data['fees'] ?? 0),
            ]);

            LotVehicle::query()->create([
                'lot_id' => $lot->id,
                'engine' => $data['engine'] ?? ($data['vehicle']['engine'] ?? null),
                'odometer' => $data['odometer'] ?? ($data['vehicle']['odometer'] ?? null),
                'complectation' => $data['complectation'] ?? ($data['vehicle']['complectation'] ?? null),
                'body_type' => $data['body_type'] ?? ($data['vehicle']['body_type'] ?? null),
                'size_id' => $data['vehicle']['size_id'] ?? null,
                'fuel_id' => $data['vehicle']['fuel_id'] ?? null,
                'drive_id' => $data['vehicle']['drive_id'] ?? null,
                'transmission_id' => $data['vehicle']['transmission_id'] ?? null,
                'highlight_id' => $data['vehicle']['highlight_id'] ?? null,
                'keys_id' => $data['vehicle']['keys_id'] ?? null,
                'odometer_unit_id' => $data['vehicle']['odometer_unit_id'] ?? null,
                'run_status_id' => $data['vehicle']['run_status_id'] ?? null,
                'color_id' => $data['vehicle']['color_id'] ?? null,
                'electric' => $data['vehicle']['electric'] ?? false,
            ]);

            LotClient::query()->create(array_merge(['lot_id' => $lot->id], $data['client'] ?? []));
            LotRoute::query()->create(array_merge(
                ['lot_id' => $lot->id, 'delivery_type_id' => $data['delivery_type_id'] ?? null],
                $data['route'] ?? [],
            ));
            ShippingRecord::query()->create(['lot_id' => $lot->id, 'status' => 'pending']);
            Chat::query()->create(['type' => 'lot', 'lot_id' => $lot->id, 'title' => 'Лот '.$lot->vin]);

            IndexLotJob::dispatch($lot->id);

            return $lot->load(['pricing', 'shipping', 'vehicle', 'client', 'route', 'brand', 'model', 'auction', 'orderStatus']);
        });
    }

    public function update(Lot $lot, array $data): Lot
    {
        return DB::transaction(function () use ($lot, $data) {
            $lot->update(collect($data)->only([
                'vin', 'lot_number', 'transport_name', 'auction_id', 'brand_id', 'model_id',
                'year', 'date_buy', 'notes', 'outside', 'is_auction_participant', 'archived',
                'status_order', 'status_shipping', 'status_finance', 'buyer_user_id',
                'counterparty_id', 'credential_id', 'doc_fee_id', 'transportation_agent_id',
                'status_order_id',
            ])->all());

            if (isset($data['pricing']) && is_array($data['pricing'])) {
                $lot->pricing()->updateOrCreate(['lot_id' => $lot->id], $data['pricing']);
            }
            if (isset($data['vehicle']) && is_array($data['vehicle'])) {
                $lot->vehicle()->updateOrCreate(['lot_id' => $lot->id], $data['vehicle']);
            }
            if (isset($data['client']) && is_array($data['client'])) {
                $lot->client()->updateOrCreate(['lot_id' => $lot->id], $data['client']);
            }
            if (isset($data['route']) && is_array($data['route'])) {
                $lot->route()->updateOrCreate(['lot_id' => $lot->id], $data['route']);
            }
            if (isset($data['shipping']) && is_array($data['shipping'])) {
                $lot->shipping()->updateOrCreate(['lot_id' => $lot->id], $data['shipping']);
            }

            IndexLotJob::dispatch($lot->id);

            return $lot->fresh([
                'pricing', 'shipping', 'vehicle', 'client', 'route', 'shippingEvents',
                'brand', 'model', 'auction', 'orderStatus', 'buyer', 'counterparty', 'lotNotes.user',
            ]);
        });
    }
}
