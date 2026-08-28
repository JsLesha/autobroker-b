<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Country;
use App\Models\Role;
use App\Models\TransportBrand;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryWriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
    }

    public function test_office_can_create_country_auction_and_brand(): void
    {
        $office = $this->user(RoleCode::Office);

        $this->actingAs($office, 'sanctum')
            ->postJson('/api/v1/countries', ['code' => 'LT', 'name' => 'Литва'])
            ->assertCreated()
            ->assertJsonPath('code', 'LT');

        $countryId = Country::query()->value('id');
        $this->actingAs($office, 'sanctum')
            ->postJson('/api/v1/cities', ['country_id' => $countryId, 'name' => 'Клайпеда'])
            ->assertCreated();

        $this->actingAs($office, 'sanctum')
            ->postJson('/api/v1/auctions', ['code' => 'COPART', 'name' => 'Copart'])
            ->assertCreated();

        $this->actingAs($office, 'sanctum')
            ->postJson('/api/v1/brands', ['name' => 'Toyota'])
            ->assertCreated();
        $brandId = TransportBrand::query()->value('id');
        $this->actingAs($office, 'sanctum')
            ->postJson("/api/v1/brands/{$brandId}/models", ['name' => 'Camry'])
            ->assertCreated()
            ->assertJsonPath('name', 'Camry');
    }

    public function test_dealer_cannot_create_country(): void
    {
        $dealer = $this->user(RoleCode::Dealer);

        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/countries', ['code' => 'US', 'name' => 'USA'])
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
