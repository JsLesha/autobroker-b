<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
    }

    public function test_logist_can_create_container(): void
    {
        $logist = $this->user(RoleCode::Logist);

        $this->actingAs($logist, 'sanctum')
            ->postJson('/api/v1/containers', ['number' => 'MSKU1234567'])
            ->assertCreated()
            ->assertJsonPath('number', 'MSKU1234567');

        $this->actingAs($logist, 'sanctum')
            ->getJson('/api/v1/containers?q=MSKU')
            ->assertOk()
            ->assertJsonCount(1, 'data');
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
