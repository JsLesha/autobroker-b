<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'prebid_auction_id', 'lot_id', 'seller_id', 'status',
    'start_price', 'buy_now_price', 'current_price',
])]
class PrebidListing extends Model
{
    protected function casts(): array
    {
        return [
            'start_price' => 'decimal:2',
            'buy_now_price' => 'decimal:2',
            'current_price' => 'decimal:2',
        ];
    }

    public function bids(): HasMany
    {
        return $this->hasMany(PrebidBid::class, 'listing_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
