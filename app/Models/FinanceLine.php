<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lot_id', 'code', 'title', 'amount', 'currency', 'locked'])]
class FinanceLine extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'locked' => 'boolean',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
