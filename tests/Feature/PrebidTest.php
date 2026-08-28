<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\PrebidListing;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrebidTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
    }

    public function test_dealer_can_create_listing_and_bid(): void
    {
        $dealer = $this->user(RoleCode::Dealer);

        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/prebid/listings', ['vin' => 'JTTESTVIN00000001', 'start_price' => 1000])
            ->assertCreated()
            ->assertJsonPath('status', 'moderation');

        $listing = PrebidListing::query()->first();
        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/prebid/listings/'.$listing->id.'/bid', ['amount' => 1100])
            ->assertCreated();

        $this->assertDatabaseHas('prebid_listings', ['id' => $listing->id, 'current_price' => 1100]);
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
