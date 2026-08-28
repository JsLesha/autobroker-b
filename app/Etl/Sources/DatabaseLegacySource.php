<?php

namespace App\Etl\Sources;

use App\Etl\Contracts\LegacySource;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseLegacySource implements LegacySource
{
    public function __construct(private readonly string $connection = 'legacy')
    {
    }

    public function available(): bool
    {
        try {
            DB::connection($this->connection)->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function tableExists(string $table): bool
    {
        return DB::connection($this->connection)->getSchemaBuilder()->hasTable($table);
    }

    public function count(string $table): int
    {
        if (! $this->tableExists($table)) {
            return 0;
        }

        return (int) DB::connection($this->connection)->table($table)->count();
    }

    public function rows(string $table, int $chunk = 500): iterable
    {
        if (! $this->tableExists($table)) {
            return;
        }

        foreach (DB::connection($this->connection)->table($table)->orderBy('id')->lazy($chunk) as $row) {
            yield (array) $row;
        }
    }
}
