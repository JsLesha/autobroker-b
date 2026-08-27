<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'aec_id'])]
class TransportBrand extends Model
{
    public function models(): HasMany
    {
        return $this->hasMany(TransportModel::class, 'brand_id');
    }
}
