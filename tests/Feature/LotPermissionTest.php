<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Lot;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
    }

    public function test_dealer_can_create_lot(): void
    {
        $dealer = $this->user(RoleCode::Dealer);

        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/lots', ['vin' => 'JT123456789012345'])
            ->assertCreated()
            ->assertJsonPath('vin', 'JT123456789012345');

        $this->assertDatabaseHas('lots', ['vin' => 'JT123456789012345']);
        $this->assertDatabaseCount('chats', 1);
    }

    public function test_buyer_without_permission_cannot_list_lots(): void
    {
        $buyer = $this->user(RoleCode::Buyer);

        $this->actingAs($buyer, 'sanctum')
            ->getJson('/api/v1/lots')
            ->assertForbidden();
    }

    public function test_calculator_is_public(): void
    {
        $this->postJson('/api/v1/calculator/quote', ['kind' => 'sea'])
            ->assertOk()
            ->assertJsonStructure(['amount', 'currency']);
    }

    private function user(RoleCode $code): User
    {
        $role = Role::query()->where('code', $code)->first();

        return User::factory()->create([
            'role_id' => $role?->id,
            'active' => true,
            'password' => 'Password123!',
        ]);
    }
}
