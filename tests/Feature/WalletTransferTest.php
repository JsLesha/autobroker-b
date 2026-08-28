<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\LedgerAccount;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
    }

    public function test_finance_can_transfer_and_list_entries(): void
    {
        $user = $this->user(RoleCode::Finance);
        $from = LedgerAccount::query()->create(['type' => 'cash', 'title' => 'A', 'currency' => 'USD']);
        $to = LedgerAccount::query()->create(['type' => 'dealer', 'title' => 'B', 'currency' => 'USD']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/wallets/transfer', [
                'from_account_id' => $from->id,
                'to_account_id' => $to->id,
                'amount' => 10,
            ])
            ->assertCreated()
            ->assertJsonPath('checksum.balanced', true);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/wallets/entries')
            ->assertOk()
            ->assertJsonCount(2, 'data');
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
