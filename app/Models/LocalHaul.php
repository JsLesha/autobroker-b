<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lot_id', 'status', 'from_place', 'to_place', 'transit_at', 'delivered_at'])]
class LocalHaul extends Model
{
    protected function casts(): array
    {
        return [
            'transit_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
