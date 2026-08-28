<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Lot;
use App\Models\LotDrop;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LotDropFileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        Storage::fake('local');
    }

    public function test_dealer_can_upload_and_download_drop_file(): void
    {
        $dealer = $this->user(RoleCode::Dealer);
        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/lots', ['vin' => 'JT555555555555555', 'lot_number' => 'DRP-1'])
            ->assertCreated();
        $lot = Lot::query()->firstOrFail();

        $this->actingAs($dealer, 'sanctum')
            ->post("/api/v1/lots/{$lot->id}/drops", [
                'title' => 'Bill of sale',
                'file' => UploadedFile::fake()->create('bill.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated()
            ->assertJsonPath('title', 'Bill of sale');

        $drop = LotDrop::query()->where('lot_id', $lot->id)->firstOrFail();

        $this->actingAs($dealer, 'sanctum')
            ->get("/api/v1/lots/{$lot->id}/drops/{$drop->id}/file")
            ->assertOk()
            ->assertHeader('content-disposition');
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
