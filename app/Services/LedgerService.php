<?php

namespace App\Services;

use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LedgerService
{
    public function transfer(LedgerAccount $from, LedgerAccount $to, string $amount, string $memo, ?int $userId = null, $reference = null): string
    {
        if ($from->currency !== $to->currency) {
            throw new RuntimeException('Валюты счетов не совпадают.');
        }

        $batch = (string) Str::uuid();

        DB::transaction(function () use ($from, $to, $amount, $memo, $userId, $reference, $batch) {
            LedgerEntry::query()->create([
                'batch_id' => $batch,
                'account_id' => $from->id,
                'debit' => 0,
                'credit' => $amount,
                'currency' => $from->currency,
                'memo' => $memo,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference->id ?? null,
                'created_by' => $userId,
            ]);

            LedgerEntry::query()->create([
                'batch_id' => $batch,
                'account_id' => $to->id,
                'debit' => $amount,
                'credit' => 0,
                'currency' => $to->currency,
                'memo' => $memo,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference->id ?? null,
                'created_by' => $userId,
            ]);
        });

        return $batch;
    }

    public function checksum(): array
    {
        $debits = (string) LedgerEntry::query()->sum('debit');
        $credits = (string) LedgerEntry::query()->sum('credit');

        return [
            'debit_sum' => $debits,
            'credit_sum' => $credits,
            'balanced' => $debits === $credits,
        ];
    }
}
