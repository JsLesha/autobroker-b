<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['actor_id', 'action', 'entity_type', 'entity_id', 'meta', 'ip_address'])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }
}
