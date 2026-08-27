<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['type', 'name', 'email', 'phone', 'active'])]
class Counterparty extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function banks(): HasMany
    {
        return $this->hasMany(CounterpartyBank::class);
    }
}
