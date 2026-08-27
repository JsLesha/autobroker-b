<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['listing_id', 'user_id', 'amount', 'is_buy_now'])]
class PrebidBid extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_buy_now' => 'boolean',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PrebidListing::class, 'listing_id');
    }
}
