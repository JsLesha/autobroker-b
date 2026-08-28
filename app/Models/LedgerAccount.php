<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'owner_type', 'owner_id', 'currency', 'title', 'active', 'legacy_balance'])]
class LedgerAccount extends Model
{
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'account_id');
    }

    public function balance(): string
    {
        $debit = (float) $this->entries()->sum('debit');
        $credit = (float) $this->entries()->sum('credit');

        return number_format($debit - $credit, 2, '.', '');
    }
}
