<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lot_id', 'city_from_id', 'city_to_id', 'port_to_id', 'location_from_id',
    'location_to_id', 'delivery_type_id', 'route_label', 'package_service_id',
    'transportation_agent_id', 'carrier_id',
])]
class LotRoute extends Model
{
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
