<?php

namespace App\Etl\Contracts;

interface LegacySource
{
    public function tableExists(string $table): bool;

    public function count(string $table): int;

    /**
     * @return iterable<int, array<string, mixed>>
     */
    public function rows(string $table, int $chunk = 500): iterable;
}
