<?php

namespace App\Http\Requests\Lot;

use Illuminate\Foundation\Http\FormRequest;

class StoreLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('lots.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'vin' => ['required', 'string', 'max:32'],
            'lot_number' => ['nullable', 'string', 'max:64'],
            'transport_name' => ['nullable', 'string', 'max:191'],
            'auction_id' => ['nullable', 'exists:auctions,id'],
            'brand_id' => ['nullable', 'exists:transport_brands,id'],
            'model_id' => ['nullable', 'exists:transport_models,id'],
            'year' => ['nullable', 'integer', 'min:1980', 'max:2100'],
            'date_buy' => ['nullable', 'date'],
            'hammer_price' => ['nullable', 'numeric', 'min:0'],
            'fees' => ['nullable', 'numeric', 'min:0'],
            'engine' => ['nullable', 'string'],
            'odometer' => ['nullable', 'string'],
            'complectation' => ['nullable', 'string'],
            'body_type' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'outside' => ['sometimes', 'boolean'],
            'is_auction_participant' => ['sometimes', 'boolean'],
            'is_unformat_vin' => ['sometimes', 'boolean'],
            'buyer_user_id' => ['nullable', 'exists:users,id'],
            'counterparty_id' => ['nullable', 'exists:counterparties,id'],
            'credential_id' => ['nullable', 'exists:credentials,id'],
            'doc_fee_id' => ['nullable', 'exists:doc_fees,id'],
            'delivery_type_id' => ['nullable', 'exists:delivery_types,id'],
            'transportation_agent_id' => ['nullable', 'exists:transportation_agents,id'],
            'client' => ['sometimes', 'array'],
            'client.full_name' => ['nullable', 'string'],
            'client.last_name' => ['nullable', 'string'],
            'client.first_middle_name' => ['nullable', 'string'],
            'client.phone' => ['nullable', 'string'],
            'client.email' => ['nullable', 'email'],
            'client.messenger' => ['nullable', 'string'],
            'client.date_of_birth' => ['nullable', 'date'],
            'route' => ['sometimes', 'array'],
            'route.city_from_id' => ['nullable', 'exists:cities,id'],
            'route.city_to_id' => ['nullable', 'exists:cities,id'],
            'route.port_to_id' => ['nullable', 'exists:ports,id'],
            'route.location_from_id' => ['nullable', 'exists:locations,id'],
            'route.location_to_id' => ['nullable', 'exists:locations,id'],
            'vehicle' => ['sometimes', 'array'],
            'vehicle.size_id' => ['nullable', 'exists:transport_sizes,id'],
            'vehicle.fuel_id' => ['nullable', 'exists:transport_fuels,id'],
            'vehicle.drive_id' => ['nullable', 'exists:transport_drives,id'],
            'vehicle.transmission_id' => ['nullable', 'exists:transport_transmissions,id'],
            'vehicle.highlight_id' => ['nullable', 'exists:transport_highlights,id'],
            'vehicle.keys_id' => ['nullable', 'exists:transport_keys,id'],
            'vehicle.odometer_unit_id' => ['nullable', 'exists:transport_odometer_units,id'],
            'vehicle.run_status_id' => ['nullable', 'exists:transport_run_statuses,id'],
            'vehicle.color_id' => ['nullable', 'exists:vehicle_colors,id'],
            'vehicle.engine' => ['nullable', 'string'],
            'vehicle.odometer' => ['nullable', 'string'],
            'vehicle.complectation' => ['nullable', 'string'],
            'vehicle.body_type' => ['nullable', 'string'],
            'vehicle.electric' => ['sometimes', 'boolean'],
        ];
    }
}
