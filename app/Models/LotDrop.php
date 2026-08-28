<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lot_id', 'title', 'document_path'])]
class LotDrop extends Model
{
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
