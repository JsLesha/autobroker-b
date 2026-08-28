<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Lot;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReindexLotsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        config(['services.meilisearch.host' => '']);
    }

    public function test_reindex_fails_without_meilisearch_host(): void
    {
        $dealer = $this->user(RoleCode::Dealer);
        $this->actingAs($dealer, 'sanctum')->postJson('/api/v1/lots', ['vin' => 'JT444444444444444'])->assertCreated();
        $this->assertSame(1, Lot::query()->count());

        $this->artisan('lots:reindex')->assertFailed();
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
