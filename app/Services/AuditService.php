<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditService
{
    public function log(?User $actor, string $action, ?object $entity = null, array $meta = [], ?Request $request = null): void
    {
        AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity->id ?? null,
            'meta' => $meta ?: null,
            'ip_address' => $request?->ip(),
        ]);
    }
}
