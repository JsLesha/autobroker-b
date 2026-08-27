<?php

namespace Tests\Feature;

use App\Models\LedgerAccount;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerChecksumTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_keeps_ledger_balanced(): void
    {
        $from = LedgerAccount::query()->create([
            'type' => 'cash',
            'title' => 'A',
            'currency' => 'USD',
            'active' => true,
        ]);
        $to = LedgerAccount::query()->create([
            'type' => 'dealer',
            'title' => 'B',
            'currency' => 'USD',
            'active' => true,
        ]);

        $service = app(LedgerService::class);
        $service->transfer($from, $to, '100.50', 'test');

        $sum = $service->checksum();
        $this->assertTrue($sum['balanced']);
        $this->assertSame('-100.50', $from->fresh()->balance());
        $this->assertSame('100.50', $to->fresh()->balance());
    }
}
