<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'lot_id', 'number', 'status', 'amount', 'currency', 'docx_path', 'pdf_path',
    'preview_token', 'preview_expires_at', 'accepted_at', 'rejected_at',
])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'preview_expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function issuePreviewToken(): void
    {
        $this->forceFill([
            'preview_token' => Str::random(64),
            'preview_expires_at' => now()->addDays(7),
        ])->save();
    }
}
