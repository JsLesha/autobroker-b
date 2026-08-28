<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_schema_only_dump(): void
    {
        $this->artisan('legacy:import', [
            '--path' => base_path('tests/fixtures/legacy-schema.sql'),
            '--dry-run' => true,
        ])->assertSuccessful();
    }
}
