<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lot_id', 'size_id', 'fuel_id', 'drive_id', 'transmission_id', 'highlight_id',
    'keys_id', 'odometer_unit_id', 'run_status_id', 'engine', 'engine_hp', 'cylinders',
    'odometer', 'equipment', 'body_type', 'complectation', 'electric', 'color_id',
])]
class LotVehicle extends Model
{
    protected function casts(): array
    {
        return ['electric' => 'boolean'];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
