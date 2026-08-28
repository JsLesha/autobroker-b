<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lot_id', 'user_id', 'body', 'noted_on', 'credential_id', 'counterparty_id', 'lot_label'])]
class LotNote extends Model
{
    protected function casts(): array
    {
        return ['noted_on' => 'date'];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
