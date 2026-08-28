<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'type', 'name', 'email', 'phone', 'active', 'code', 'counterparty_type_id',
    'country_id', 'address', 'messenger', 'commission', 'is_default',
    'hide_in_lot', 'hide_in_calculator', 'is_sea_carrier', 'payment_types',
])]
class Counterparty extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_default' => 'boolean',
            'hide_in_lot' => 'boolean',
            'hide_in_calculator' => 'boolean',
            'is_sea_carrier' => 'boolean',
            'payment_types' => 'array',
            'commission' => 'decimal:2',
        ];
    }

    public function banks(): HasMany
    {
        return $this->hasMany(CounterpartyBank::class);
    }
}
