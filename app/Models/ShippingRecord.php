<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lot_id', 'status', 'origin_port_id', 'destination_port_id', 'sea_line_id', 'status_id',
    'container_number', 'documents_received', 'lot_accepted_by_client',
    'ready_to_load_at', 'loaded_at', 'sailed_at', 'arrived_at', 'delivered_at', 'timeline',
])]
class ShippingRecord extends Model
{
    protected function casts(): array
    {
        return [
            'timeline' => 'array',
            'ready_to_load_at' => 'datetime',
            'loaded_at' => 'datetime',
            'sailed_at' => 'datetime',
            'arrived_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
