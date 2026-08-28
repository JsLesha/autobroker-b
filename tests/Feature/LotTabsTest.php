<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Lot;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotTabsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
    }

    public function test_dealer_can_add_drop_notify_and_export(): void
    {
        $dealer = $this->user(RoleCode::Dealer);
        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/lots', ['vin' => 'JT111111111111111', 'lot_number' => 'EXP-1'])
            ->assertCreated();
        $lotId = Lot::query()->value('id');

        $this->actingAs($dealer, 'sanctum')
            ->postJson("/api/v1/lots/{$lotId}/drops", ['title' => 'Bill of sale'])
            ->assertCreated()
            ->assertJsonPath('title', 'Bill of sale');

        $this->actingAs($dealer, 'sanctum')
            ->postJson("/api/v1/lots/{$lotId}/notifications", ['title' => 'Документы готовы', 'body' => 'проверьте'])
            ->assertCreated()
            ->assertJsonPath('title', 'Документы готовы');

        $this->actingAs($dealer, 'sanctum')
            ->getJson("/api/v1/lots/{$lotId}/export")
            ->assertOk()
            ->assertJsonPath('vin', 'JT111111111111111')
            ->assertJsonPath('lot_number', 'EXP-1');
    }

    public function test_finance_issues_invoice_preview_token(): void
    {
        $dealer = $this->user(RoleCode::Dealer);
        $finance = $this->user(RoleCode::Finance);
        $this->actingAs($dealer, 'sanctum')->postJson('/api/v1/lots', ['vin' => 'JT222222222222222'])->assertCreated();
        $lotId = Lot::query()->value('id');

        $invoiceId = $this->actingAs($finance, 'sanctum')
            ->postJson("/api/v1/lots/{$lotId}/invoices", ['amount' => 1500])
            ->assertCreated()
            ->json('id');

        $this->actingAs($finance, 'sanctum')
            ->postJson("/api/v1/lots/{$lotId}/invoices/{$invoiceId}/preview")
            ->assertOk()
            ->assertJsonStructure(['preview_token']);
    }

    private function user(RoleCode $code): User
    {
        $role = Role::query()->where('code', $code)->first();

        return User::factory()->create([
            'email' => fake()->unique()->safeEmail(),
            'role_id' => $role?->id,
            'active' => true,
            'password' => 'Password123!',
        ]);
    }
}
