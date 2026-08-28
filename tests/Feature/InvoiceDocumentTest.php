<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Lot;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
    }

    public function test_creating_invoice_renders_html_preview(): void
    {
        $dealer = $this->user(RoleCode::Dealer);
        $finance = $this->user(RoleCode::Finance);
        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/lots', ['vin' => 'JT444444444444444', 'lot_number' => 'INV-LOT'])
            ->assertCreated();
        $lotId = Lot::query()->value('id');

        $payload = $this->actingAs($finance, 'sanctum')
            ->postJson("/api/v1/lots/{$lotId}/invoices", ['amount' => 2100])
            ->assertCreated()
            ->json();

        $this->assertNotEmpty($payload['html_path']);
        $this->assertNotEmpty($payload['preview_token']);

        $this->get('/api/v1/public/invoice/'.$payload['preview_token'].'/file')
            ->assertOk()
            ->assertSee('JT444444444444444')
            ->assertSee('2100');
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
