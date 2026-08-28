<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lot_id', 'type', 'amount', 'currency', 'method', 'document_path', 'created_by', 'status', 'comment'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
