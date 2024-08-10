<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class TenantInit extends Command
{
    protected $signature = 'tenants:init';

    protected $description = 'Create owner table where all domains for tenant app lives';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        DB::setDefaultConnection('owner');

        $path = 'Database/Migrations/Owner';
        $this->info('Running migration from: '.$path);

        // Running the migrations
        Artisan::call('migrate', [
            '--path' => $path,
            '--force' => true, // Use --force to run migrations in production if necessary
        ]);

        $this->info('Migrations have been executed successfully.');

        return 0;
    }
}