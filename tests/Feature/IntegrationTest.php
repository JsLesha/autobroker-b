<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
    }

    public function test_dealer_can_run_vin_check_stub(): void
    {
        $dealer = $this->user(RoleCode::Dealer);

        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/integrations/vin-check', ['vin' => 'JT333333333333333'])
            ->assertCreated()
            ->assertJsonPath('vin', 'JT333333333333333')
            ->assertJsonPath('info.source', 'stub');

        $this->assertDatabaseHas('vin_check_reports', ['vin' => 'JT333333333333333']);

        $this->actingAs($dealer, 'sanctum')
            ->getJson('/api/v1/integrations/vin-reports?vin=JT333333333333333')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_admin_can_see_integration_status(): void
    {
        $admin = $this->user(RoleCode::Admin);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/integrations/status')
            ->assertOk()
            ->assertJsonFragment(['provider' => 'vin_check']);
    }

    public function test_dealer_can_run_aec_stub_lookup(): void
    {
        $dealer = $this->user(RoleCode::Dealer);

        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/integrations/aec/lookup', ['vin' => 'JT555555555555555'])
            ->assertOk()
            ->assertJsonPath('source', 'stub');
    }

    public function test_dealer_cannot_see_integration_status(): void
    {
        $dealer = $this->user(RoleCode::Dealer);

        $this->actingAs($dealer, 'sanctum')
            ->getJson('/api/v1/integrations/status')
            ->assertForbidden();
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
