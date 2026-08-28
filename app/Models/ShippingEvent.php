<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lot_id', 'code', 'occurred_at'])]
class ShippingEvent extends Model
{
    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
