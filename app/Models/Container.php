<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'number', 'status', 'created_by', 'confirmed_at', 'loaded_at', 'archived_at',
    'sea_line_id', 'port_id', 'port_from_id', 'shipper_id', 'consolidation', 'is_full', 'l_date', 'pod',
])]
class Container extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'consolidation' => 'boolean',
            'is_full' => 'boolean',
            'confirmed_at' => 'datetime',
            'loaded_at' => 'datetime',
            'archived_at' => 'datetime',
            'l_date' => 'datetime',
            'pod' => 'datetime',
        ];
    }

    public function lots(): BelongsToMany
    {
        return $this->belongsToMany(Lot::class, 'container_lot');
    }
}
