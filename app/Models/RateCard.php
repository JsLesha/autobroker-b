<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kind', 'title', 'active'])]
class RateCard extends Model
{
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(RateVersion::class);
    }
}
