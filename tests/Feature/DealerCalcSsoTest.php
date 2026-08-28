<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealerCalcSsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        config([
            'services.dealer_calc.sso_secret' => 'test-secret',
            'services.dealer_calc.sso_callback_url' => 'https://calc.example/sso/callback',
        ]);
    }

    public function test_dealer_gets_sso_redirect(): void
    {
        $dealer = $this->user(RoleCode::Dealer);

        $this->actingAs($dealer, 'sanctum')
            ->getJson('/api/v1/dealer-calc/sso')
            ->assertOk()
            ->assertJsonStructure(['redirect_url'])
            ->assertJsonPath('redirect_url', fn ($url) => str_contains($url, 'https://calc.example/sso/callback?token='));
    }

    public function test_buyer_cannot_use_sso(): void
    {
        $buyer = $this->user(RoleCode::Buyer);

        $this->actingAs($buyer, 'sanctum')
            ->getJson('/api/v1/dealer-calc/sso')
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
