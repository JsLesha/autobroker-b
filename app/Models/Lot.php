<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'vin', 'lot_number', 'auction_id', 'brand_id', 'model_id', 'created_by',
    'buyer_user_id', 'counterparty_id', 'credential_id', 'year',
    'status_order', 'status_shipping', 'status_finance',
    'is_auction_participant', 'archived', 'notes',
])]
class Lot extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_auction_participant' => 'boolean',
            'archived' => 'boolean',
        ];
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(TransportBrand::class, 'brand_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(TransportModel::class, 'model_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class);
    }

    public function pricing(): HasOne
    {
        return $this->hasOne(LotPricing::class);
    }

    public function shipping(): HasOne
    {
        return $this->hasOne(ShippingRecord::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(LotImage::class);
    }

    public function financeLines(): HasMany
    {
        return $this->hasMany(FinanceLine::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function chat(): HasOne
    {
        return $this->hasOne(Chat::class);
    }
}
