<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['counterparty_id', 'bank_name', 'iban', 'swift'])]
class CounterpartyBank extends Model
{
    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class);
    }
}
