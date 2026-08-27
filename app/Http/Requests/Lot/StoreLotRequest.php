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
            'auction_id' => ['nullable', 'exists:auctions,id'],
            'brand_id' => ['nullable', 'exists:transport_brands,id'],
            'model_id' => ['nullable', 'exists:transport_models,id'],
            'year' => ['nullable', 'integer', 'min:1980', 'max:2100'],
            'hammer_price' => ['nullable', 'numeric', 'min:0'],
            'fees' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
