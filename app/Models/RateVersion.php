<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['rate_card_id', 'version', 'layer', 'effective_from', 'confirmed_by'])]
class RateVersion extends Model
{
    protected function casts(): array
    {
        return ['effective_from' => 'datetime'];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(RateCard::class, 'rate_card_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RateItem::class);
    }
}
