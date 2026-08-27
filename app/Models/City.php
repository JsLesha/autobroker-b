<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['country_id', 'name', 'aec_id'])]
class City extends Model
{
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
