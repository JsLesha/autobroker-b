<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
    }

    public function test_login_rejects_bad_password(): void
    {
        $this->createUser();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'dealer@test.local',
            'password' => 'wrongpass',
        ])->assertStatus(422);
    }

    public function test_login_issues_token(): void
    {
        $this->createUser();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'dealer@test.local',
            'password' => 'Password123!',
        ])->assertOk()
            ->assertJsonPath('user.email', 'dealer@test.local')
            ->assertJsonStructure(['token', 'user' => ['rights', 'role']]);
    }

    public function test_me_requires_auth(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->createUser(active: false);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'dealer@test.local',
            'password' => 'Password123!',
        ])->assertStatus(422);
    }

    public function test_dealer_cannot_impersonate(): void
    {
        $dealer = $this->createUser();
        $target = $this->createUser(email: 'other@test.local');

        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/auth/impersonate/'.$target->id)
            ->assertForbidden();
    }

    private function createUser(bool $active = true, string $email = 'dealer@test.local'): User
    {
        $role = Role::query()->where('code', RoleCode::Dealer)->first();

        return User::factory()->create([
            'email' => $email,
            'role_id' => $role?->id,
            'active' => $active,
            'password' => 'Password123!',
        ]);
    }
}
