<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureTenant();
        $this->configureQueue();
    }

    /**
     * Configure tenant based on the domain.
     */
    protected function configureTenant(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $host = request()->getHost();
        $tenant = Tenant::whereDomain($host)->firstOrFail();
        $tenant->configure()->use();  // Set up and use tenant configuration
    }

    /**
     * Configure the queue system to be aware of tenants.
     */
    protected function configureQueue(): void
    {
        // Add tenant_id to the job payload
        Queue::createPayloadUsing(function () {
            if (app()->bound('tenant')) {
                $tenant = app()->make('tenant');

                return ['tenant_id' => $tenant->id];
            }

            return [];
        });

        // Restore tenant context when job is processing
        Event::listen(JobProcessing::class, function (JobProcessing $event) {
            $tenantId = $event->job->payload()['tenant_id'] ?? null;

            if ($tenantId) {
                $tenant = Tenant::find($tenantId);
                $tenant?->configure()->use();
            }
        });
    }
}
