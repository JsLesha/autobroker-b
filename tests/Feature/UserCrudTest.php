<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
    }

    public function test_office_can_create_and_list_dealers(): void
    {
        $office = $this->user(RoleCode::Office);
        $dealerRole = Role::query()->where('code', RoleCode::Dealer)->firstOrFail();

        $this->actingAs($office, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Дилер Тест',
                'email' => 'dealer-new@test.local',
                'password' => 'Password123!',
                'role_id' => $dealerRole->id,
            ])
            ->assertCreated()
            ->assertJsonPath('email', 'dealer-new@test.local');

        $this->actingAs($office, 'sanctum')
            ->getJson('/api/v1/users?role=dealer')
            ->assertOk()
            ->assertJsonPath('data.0.email', 'dealer-new@test.local');
    }

    public function test_office_can_archive_user(): void
    {
        $office = $this->user(RoleCode::Office);
        $target = $this->user(RoleCode::Dealer, 'archive-me@test.local');

        $this->actingAs($office, 'sanctum')
            ->patchJson("/api/v1/users/{$target->id}", ['active' => false])
            ->assertOk()
            ->assertJsonPath('active', false);

        $this->actingAs($office, 'sanctum')
            ->getJson('/api/v1/users?role=dealer&archived=1')
            ->assertOk()
            ->assertJsonPath('data.0.email', 'archive-me@test.local');
    }

    public function test_dealer_cannot_create_users(): void
    {
        $dealer = $this->user(RoleCode::Dealer);

        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'X',
                'email' => 'x@test.local',
                'password' => 'Password123!',
                'role_id' => $dealer->role_id,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_sync_role_permissions(): void
    {
        $admin = $this->user(RoleCode::Admin);
        $dealer = Role::query()->where('code', RoleCode::Dealer)->firstOrFail();
        $ids = Permission::query()->whereIn('code', ['lots.read', 'lots.create'])->pluck('id')->all();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/roles/{$dealer->id}/permissions", ['permission_ids' => $ids])
            ->assertOk()
            ->assertJsonCount(2, 'permissions');
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
