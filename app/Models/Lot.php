<?php

namespace App\Models;

use App\Enums\RoleCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'vin', 'lot_number', 'transport_name', 'auction_id', 'brand_id', 'model_id', 'created_by',
    'buyer_user_id', 'counterparty_id', 'credential_id', 'year', 'date_buy',
    'status_order', 'status_shipping', 'status_finance',
    'status_order_id', 'status_shipping_id', 'status_finance_id', 'buyer_role_id',
    'doc_fee_id', 'transportation_agent_id',
    'is_auction_participant', 'is_unformat_vin', 'outside', 'archived', 'notes',
])]
class Lot extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_auction_participant' => 'boolean',
            'is_unformat_vin' => 'boolean',
            'outside' => 'boolean',
            'archived' => 'boolean',
            'date_buy' => 'date',
        ];
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->roleCode() === RoleCode::Dealer || $user->roleCode() === RoleCode::SubUser) {
            return $query->where(function ($q) use ($user) {
                $q->where('buyer_user_id', $user->id)->orWhere('created_by', $user->id);
            });
        }

        return $query;
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

    public function vehicle(): HasOne
    {
        return $this->hasOne(LotVehicle::class);
    }

    public function client(): HasOne
    {
        return $this->hasOne(LotClient::class);
    }

    public function route(): HasOne
    {
        return $this->hasOne(LotRoute::class);
    }

    public function lotNotes(): HasMany
    {
        return $this->hasMany(LotNote::class);
    }

    public function shippingEvents(): HasMany
    {
        return $this->hasMany(ShippingEvent::class);
    }

    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(StatusOrder::class, 'status_order_id');
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }
}
