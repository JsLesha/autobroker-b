<?php

namespace App\Etl\Sources;

use App\Etl\Contracts\LegacySource;

class DumpLegacySource implements LegacySource
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $tables = [];

    public function __construct(private readonly string $path)
    {
        if (is_readable($path)) {
            $this->parse(file_get_contents($path) ?: '');
        }
    }

    public function insertCount(): int
    {
        $total = 0;
        foreach ($this->tables as $rows) {
            $total += count($rows);
        }

        return $total;
    }

    public function tableExists(string $table): bool
    {
        return array_key_exists($table, $this->tables) || $this->schemaMentions($table);
    }

    public function count(string $table): int
    {
        return count($this->tables[$table] ?? []);
    }

    public function rows(string $table, int $chunk = 500): iterable
    {
        foreach ($this->tables[$table] ?? [] as $row) {
            yield $row;
        }
    }

    private function schemaMentions(string $table): bool
    {
        return false;
    }

    private function parse(string $sql): void
    {
        if (! preg_match_all('/INSERT INTO `([^`]+)` \(([^)]+)\) VALUES\s*(.+?);/is', $sql, $matches, PREG_SET_ORDER)) {
            return;
        }

        foreach ($matches as $match) {
            $table = $match[1];
            $columns = array_map(fn ($c) => trim($c, " `\n\r\t"), explode(',', $match[2]));
            foreach ($this->splitTuples($match[3]) as $tuple) {
                $values = $this->parseTuple($tuple);
                if (count($values) !== count($columns)) {
                    continue;
                }
                $this->tables[$table][] = array_combine($columns, $values);
            }
        }
    }

    /** @return list<string> */
    private function splitTuples(string $valuesSql): array
    {
        $tuples = [];
        $depth = 0;
        $current = '';
        $len = strlen($valuesSql);
        for ($i = 0; $i < $len; $i++) {
            $ch = $valuesSql[$i];
            if ($ch === '(') {
                $depth++;
                if ($depth === 1) {
                    $current = '';
                    continue;
                }
            }
            if ($ch === ')' && $depth > 0) {
                $depth--;
                if ($depth === 0) {
                    $tuples[] = $current;
                    continue;
                }
            }
            if ($depth >= 1) {
                $current .= $ch;
            }
        }

        return $tuples;
    }

    /** @return list<mixed> */
    private function parseTuple(string $tuple): array
    {
        $out = [];
        $buf = '';
        $inString = false;
        $len = strlen($tuple);
        for ($i = 0; $i < $len; $i++) {
            $ch = $tuple[$i];
            if ($ch === "'" && ! $inString) {
                $inString = true;
                $buf = '';
                continue;
            }
            if ($ch === "'" && $inString) {
                if (($tuple[$i + 1] ?? '') === "'") {
                    $buf .= "'";
                    $i++;
                    continue;
                }
                $inString = false;
                $out[] = $buf;
                $buf = '';
                continue;
            }
            if ($inString) {
                $buf .= $ch;
                continue;
            }
            if ($ch === ',') {
                $token = trim($buf);
                if ($token !== '') {
                    $out[] = $this->castToken($token);
                }
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }
        $token = trim($buf);
        if ($token !== '') {
            $out[] = $this->castToken($token);
        }

        return $out;
    }

    private function castToken(string $token): mixed
    {
        if (strcasecmp($token, 'NULL') === 0) {
            return null;
        }
        if (is_numeric($token)) {
            return str_contains($token, '.') ? (float) $token : (int) $token;
        }

        return $token;
    }
}
