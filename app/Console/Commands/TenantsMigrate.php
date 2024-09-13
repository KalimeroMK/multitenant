<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantsMigrate extends Command
{
    protected $signature = 'tenants:migrate {tenant?} {--fresh} {--seed}';

    protected $description = 'Create clean migration and seed for one tenant or for all tenants';

    public function handle(): int
    {
        if ($tenantId = $this->argument('tenant')) {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                $this->error("Tenant with ID {$tenantId} not found.");

                return 1;  // Return exit code 1 to indicate error
            }

            return $this->migrate($tenant);
        } else {
            Tenant::all()->each(fn ($tenant) => $this->migrate($tenant));
        }

        return 0; // Default success exit code
    }

    public function migrate(Tenant $tenant): int
    {
        DB::beginTransaction();
        try {
            $tenant->configure()->use();
            $this->line('');
            $this->line('-----------------------------------------');
            $this->info("Migrating Tenant #{$tenant->id} ({$tenant->name})");
            $this->line('-----------------------------------------');

            $options = ['--force' => true];
            if ($this->option('seed')) {
                $options['--seed'] = true;
            }

            $this->call(
                $this->option('fresh') ? 'migrate:fresh' : 'migrate',
                $options
            );

            DB::commit();

            return 0; // Return success exit code
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Migration failed for Tenant #{$tenant->id} ({$tenant->name}): ".$e->getMessage());

            return 1; // Return error exit code on exception
        }
    }
}
