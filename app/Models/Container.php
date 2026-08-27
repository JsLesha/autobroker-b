<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['number', 'status', 'created_by', 'confirmed_at', 'loaded_at', 'archived_at'])]
class Container extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'loaded_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function lots(): BelongsToMany
    {
        return $this->belongsToMany(Lot::class, 'container_lot');
    }
}
