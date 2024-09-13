<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantInit extends Command
{
    protected $signature = 'tenants:init';
    protected $description = 'Create owner table where all domains for tenant app live';

    public function handle(): int
    {
        DB::setDefaultConnection('owner');
        $path = database_path('migrations/owner'); // Custom migration path for the owner DB
        $this->info('Running migrations from: ' . $path);

        try {
            $this->call('migrate', ['--path' => $path, '--force' => true]);
            $this->info('Migrations have been executed successfully.');
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return 1; // Return non-zero for failure
        }

        return 0; // Return zero for success
    }
}
