<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lot_id', 'code', 'title', 'amount', 'currency', 'locked', 'counterparty_id',
    'invoice_id', 'is_block', 'is_ag', 'logist_checked', 'logist_close',
    'finance_checked', 'finance_close', 'is_paid', 'paid_at',
])]
class FinanceLine extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_paid' => 'boolean',
            'is_ag' => 'boolean',
            'locked' => 'boolean',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
