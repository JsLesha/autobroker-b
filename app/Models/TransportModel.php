<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['brand_id', 'name', 'aec_id'])]
class TransportModel extends Model
{
    public function brand(): BelongsTo
    {
        return $this->belongsTo(TransportBrand::class, 'brand_id');
    }
}
