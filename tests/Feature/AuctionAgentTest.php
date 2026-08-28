<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Credential;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuctionAgentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        config(['services.auction_agent.url' => '']);
    }

    public function test_dealer_opens_stub_auction_session_and_bids(): void
    {
        $dealer = $this->user(RoleCode::Dealer);
        $credential = Credential::query()->create([
            'user_id' => $dealer->id,
            'login' => 'copart@test',
            'secret' => encrypt('secret'),
        ]);

        $session = $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/integrations/auction/session', ['credential_id' => $credential->id])
            ->assertOk()
            ->assertJsonPath('source', 'stub')
            ->json();

        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/integrations/auction/bid', [
                'session_id' => $session['session_id'],
                'lot' => '12345678',
                'amount' => 5000,
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'queued');
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
