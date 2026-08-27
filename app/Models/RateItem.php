<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['rate_version_id', 'dimensions', 'amount', 'currency'])]
class RateItem extends Model
{
    protected function casts(): array
    {
        return [
            'dimensions' => 'array',
            'amount' => 'decimal:2',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(RateVersion::class, 'rate_version_id');
    }
}
