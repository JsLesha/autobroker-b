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
        $this->assertDatabaseCount('lot_vehicles', 1);
    }

    public function test_dealer_lists_only_own_lots(): void
    {
        $a = $this->user(RoleCode::Dealer);
        $b = $this->user(RoleCode::Dealer, 'dealer-b@test.local');

        $this->actingAs($a, 'sanctum')->postJson('/api/v1/lots', ['vin' => 'VINAAAAAAAAAAAAAA'])->assertCreated();
        $this->actingAs($b, 'sanctum')->postJson('/api/v1/lots', ['vin' => 'VINBBBBBBBBBBBBBB'])->assertCreated();

        $this->actingAs($a, 'sanctum')
            ->getJson('/api/v1/lots')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.vin', 'VINAAAAAAAAAAAAAA');
    }

    public function test_lot_filters_dictionaries_and_notes(): void
    {
        $dealer = $this->user(RoleCode::Dealer);
        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/lots', ['vin' => 'JT999999999999999', 'lot_number' => 'LOT-9'])
            ->assertCreated();

        $this->actingAs($dealer, 'sanctum')
            ->getJson('/api/v1/lots?vin=JT999')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.lot_number', 'LOT-9');

        $this->actingAs($dealer, 'sanctum')
            ->getJson('/api/v1/lots/dictionaries')
            ->assertOk()
            ->assertJsonStructure(['fuels', 'drives', 'sizes', 'colors']);

        $lotId = Lot::query()->value('id');
        $this->actingAs($dealer, 'sanctum')
            ->postJson("/api/v1/lots/{$lotId}/notes", ['body' => 'проверка'])
            ->assertCreated()
            ->assertJsonPath('body', 'проверка');

        $this->actingAs($dealer, 'sanctum')
            ->getJson("/api/v1/lots/{$lotId}/notes")
            ->assertOk()
            ->assertJsonCount(1);
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

    private function user(RoleCode $code, ?string $email = null): User
    {
        $role = Role::query()->where('code', $code)->first();

        return User::factory()->create([
            'email' => $email ?? fake()->unique()->safeEmail(),
            'role_id' => $role?->id,
            'active' => true,
            'password' => 'Password123!',
        ]);
    }
}
