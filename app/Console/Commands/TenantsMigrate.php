<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantsMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate {tenant?} {--fresh} {--seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create clean migration and seed for one tenant or for all tenants';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if ($tenantId = $this->argument('tenant')) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                $this->migrate($tenant);
            } else {
                $this->error("Tenant with ID {$tenantId} not found.");
            }
        } else {
            Tenant::all()->each(
                fn ($tenant) => $this->migrate($tenant)
            );
        }
    }

    /**
     * Migrate the given tenant.
     */
    public function migrate(Tenant $tenant): void
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
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Migration failed for Tenant #{$tenant->id} ({$tenant->name}): " . $e->getMessage());
        }
    }
}