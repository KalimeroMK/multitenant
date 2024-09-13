<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TenantTest extends TestCase
{
    public function test_migrates_single_tenant_successfully(): void
    {
        $tenant = Tenant::factory()->create();
        $this->createDatabase($tenant->database);
        $this->artisan('tenants:migrate', ['tenant' => $tenant->id])
            ->expectsOutput("Migrating Tenant #{$tenant->id} ({$tenant->name})")
            ->assertExitCode(0);
    }

    public function test_returns_error_if_tenant_not_found(): void
    {
        $this->artisan('tenants:migrate', ['tenant' => 999])
            ->expectsOutput('Tenant with ID 999 not found.')
            ->assertExitCode(1);
    }

    protected function createDatabase(string $database): void
    {
        try {
            $ownerConnection = 'owner';
            DB::connection($ownerConnection)->statement("CREATE DATABASE `{$database}`");
        } catch (Exception $e) {
            Log::error("Error creating database {$database}: {$e->getMessage()}");
        }
    }
}
