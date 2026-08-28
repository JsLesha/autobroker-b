<?php

namespace App\Etl;

use App\Models\LegacyIdMap;
use Illuminate\Support\Facades\DB;

class IdMapper
{
    public function remember(string $entity, int|string|null $oldId, ?int $newId): void
    {
        if ($oldId === null || $newId === null) {
            return;
        }

        LegacyIdMap::query()->updateOrCreate(
            ['entity' => $entity, 'old_id' => (int) $oldId],
            ['new_id' => $newId],
        );
    }

    public function get(string $entity, int|string|null $oldId): ?int
    {
        if ($oldId === null) {
            return null;
        }

        $id = LegacyIdMap::query()
            ->where('entity', $entity)
            ->where('old_id', (int) $oldId)
            ->value('new_id');

        return $id !== null ? (int) $id : null;
    }

    public function counts(): array
    {
        return LegacyIdMap::query()
            ->select('entity', DB::raw('count(*) as c'))
            ->groupBy('entity')
            ->pluck('c', 'entity')
            ->all();
    }
}
