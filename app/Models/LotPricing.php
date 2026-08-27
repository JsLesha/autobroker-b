<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lot_id', 'hammer_price', 'fees', 'total', 'currency'])]
class LotPricing extends Model
{
    protected $table = 'lot_pricing';

    protected function casts(): array
    {
        return [
            'hammer_price' => 'decimal:2',
            'fees' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
