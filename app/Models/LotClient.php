<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lot_id', 'full_name', 'last_name', 'first_middle_name', 'date_of_birth',
    'phone', 'messenger', 'email',
])]
class LotClient extends Model
{
    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
