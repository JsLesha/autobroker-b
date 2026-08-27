<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lot_id', 'path', 'type', 'is_cover', 'is_selected', 'position'])]
class LotImage extends Model
{
    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
            'is_selected' => 'boolean',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
