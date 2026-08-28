<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Lot;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
    }

    public function test_user_reads_own_inbox_and_polls_chat(): void
    {
        $dealer = $this->user(RoleCode::Dealer);
        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/lots', ['vin' => 'JT666666666666666'])
            ->assertCreated();
        $lotId = Lot::query()->value('id');

        $this->actingAs($dealer, 'sanctum')
            ->postJson("/api/v1/lots/{$lotId}/notifications", ['title' => 'Готово'])
            ->assertCreated();

        $list = $this->actingAs($dealer, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Готово')
            ->json();
        $id = $list['data'][0]['id'];

        $this->actingAs($dealer, 'sanctum')
            ->patchJson("/api/v1/notifications/{$id}/read")
            ->assertOk();
        $this->assertNotNull(UserNotification::query()->find($id)?->read_at);

        $this->actingAs($dealer, 'sanctum')
            ->postJson("/api/v1/lots/{$lotId}/messages", ['body' => 'привет'])
            ->assertCreated();

        $this->actingAs($dealer, 'sanctum')
            ->getJson("/api/v1/lots/{$lotId}/messages")
            ->assertOk()
            ->assertJsonPath('0.body', 'привет');
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
