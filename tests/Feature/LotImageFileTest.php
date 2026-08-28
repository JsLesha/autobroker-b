<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Lot;
use App\Models\LotImage;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LotImageFileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        Storage::fake('local');
    }

    public function test_dealer_can_upload_and_download_lot_image(): void
    {
        $dealer = $this->user(RoleCode::Dealer);
        $this->actingAs($dealer, 'sanctum')
            ->postJson('/api/v1/lots', ['vin' => 'JT333333333333333', 'lot_number' => 'IMG-1'])
            ->assertCreated();
        $lot = Lot::query()->firstOrFail();

        $this->actingAs($dealer, 'sanctum')
            ->post("/api/v1/lots/{$lot->id}/images", [
                'file' => UploadedFile::fake()->image('lot.jpg'),
            ])
            ->assertCreated()
            ->assertJsonStructure(['id', 'path']);

        $image = LotImage::query()->where('lot_id', $lot->id)->firstOrFail();

        $this->actingAs($dealer, 'sanctum')
            ->get("/api/v1/lots/{$lot->id}/images/{$image->id}/file")
            ->assertOk();
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
