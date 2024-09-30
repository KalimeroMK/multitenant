
# Implementing Multi-Tenancy in Laravel: A Comprehensive Guide

Multi-tenancy in web applications refers to the architecture where a single instance of the application serves multiple customers or 'tenants.' Each tenant's data is isolated from others, making this setup essential for SaaS platforms where multiple businesses or organizations might use the same application. 

This guide provides a detailed approach to implementing a **database-per-tenant** strategy in Laravel without using any external packages. It includes code examples, explanations, and the necessary commands to dynamically handle tenants' databases.

## Features
- Dynamic Tenant Database Switching
- Tenant-Specific Cache Management
- Custom Console Commands for Tenant Initialization and Migrations
- Middleware for Tenant Resolution Based on Domain
- Queue System Support for Multi-Tenancy

## 1. Setting Up Database Connections

In `config/database.php`, define the connections for the **Owner** and **Tenant**. The Owner connection handles tenant management, while the Tenant connection dynamically switches based on the tenant currently being accessed.

```php
return [
    'default' => env('DB_CONNECTION', 'tenant'),

    'connections' => [
        'tenant' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => null, // Database will be set dynamically
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        'owner' => [
            'driver' => 'mysql',
            'host' => env('OWNER_DB_HOST', '127.0.0.1'),
            'port' => env('OWNER_DB_PORT', '3306'),
            'database' => env('OWNER_DB_DATABASE', 'landlord'),
            'username' => env('OWNER_DB_USERNAME', 'root'),
            'password' => env('OWNER_DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],
    ],
];
```

## 2. Creating the Tenant Model and Migrations

Create a `Tenant` model linked to the `owner` connection for managing tenant-related data (e.g., name, domain, and database).

**Tenant Migration:**

```php
Schema::create('tenants', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('domain')->unique();
    $table->string('database');
    $table->timestamps();
});
```

**Tenant Model:**

```php
class Tenant extends Model
{
    protected $fillable = ['name', 'domain', 'database'];
    protected $connection = 'owner'; // Default connection for the owner database

    public function configure(): self
    {
        config(['database.connections.tenant.database' => $this->database]);
        DB::purge('tenant');
        return $this;
    }

    public function use(): self
    {
        DB::setDefaultConnection('tenant');
        return $this;
    }
}
```

## 3. Custom Console Command for Tenant Initialization

This command initializes the owner database, where all tenant information is stored.

```php
class TenantInit extends Command
{
    protected $signature = 'tenants:init';
    protected $description = 'Create owner table where all domains for tenant app live';

    public function handle(): int
    {
        DB::setDefaultConnection('owner');
        $path = database_path('migrations/owner');
        $this->info('Running migrations from: ' . $path);

        try {
            $this->call('migrate', ['--path' => $path, '--force' => true]);
            $this->info('Migrations have been executed successfully.');
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
```

## 4. Custom Console Command for Tenant Migrations

This command loops through all tenants and runs migrations on each tenant's database.

```php
class TenantsMigrateCommand extends Command
{
    protected $signature = 'tenants:migrate {tenant?} {--fresh} {--seed}';

    public function handle(): void
    {
        if ($tenantId = $this->argument('tenant')) {
            $tenant = Tenant::find($tenantId);
            $this->migrate($tenant);
        } else {
            Tenant::all()->each(fn($tenant) => $this->migrate($tenant));
        }
    }

    public function migrate(Tenant $tenant): void
    {
        $tenant->configure()->use();
        $this->info("Migrating Tenant #{$tenant->id} ({$tenant->name})");
        $options = ['--force' => true];
        if ($this->option('seed')) $options['--seed'] = true;
        $this->call($this->option('fresh') ? 'migrate:fresh' : 'migrate', $options);
    }
}
```

## 5. Middleware for Tenant Resolution

Ensure the correct tenant is used for each request with middleware that identifies tenants by domain.

```php
class TenantSessionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('tenant_id')) {
            $request->session()->put('tenant_id', app('tenant')->id);
        }

        if ($request->session()->get('tenant_id') != app('tenant')->id) {
            abort(401);
        }

        return $next($request);
    }
}
```

## 6. Testing Multi-Tenant Applications

Configure tests to properly handle both owner and tenant databases.

```php
public function setUp(): void
{
    parent::setUp();
    $this->artisan('migrate', ['--database' => 'owner']);
    $this->seed(OwnerSeeder::class);

    Tenant::all()->each(function (Tenant $tenant) {
        $tenant->configure();
        $this->artisan('migrate', ['--database' => 'tenant']);
    });
}
```

## 7. Setting Up a Service Provider for Tenant Resolution

Use a service provider to resolve tenants and set the correct tenant context for each request.

```php
class TenancyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureTenant();
        $this->configureQueue();
    }

    protected function configureTenant(): void
    {
        if ($this->app->runningInConsole()) return;

        $host = request()->getHost();
        $tenant = Tenant::whereDomain($host)->firstOrFail();
        $tenant->configure()->use();
    }

    protected function configureQueue(): void
    {
        Queue::createPayloadUsing(function () {
            if (app()->bound('tenant')) return ['tenant_id' => app('tenant')->id];
            return [];
        });

        Event::listen(JobProcessing::class, function (JobProcessing $event) {
            if ($tenantId = $event->job->payload()['tenant_id'] ?? null) {
                Tenant::find($tenantId)?->configure()->use();
            }
        });
    }
}
```

## Conclusion

This guide outlines how to implement multi-tenancy in Laravel using a database-per-tenant approach. With strong data isolation, scalability, and the ability to dynamically switch databases, this method is suitable for SaaS platforms requiring tenant-specific configurations.

### Pros:
- Strong data isolation
- Scalability with additional tenants
- Security through separate databases
- Cache efficiency
- Queue robustness

### Cons:
- Complexity in setup and management
- Resource overhead for separate databases
- Backup complexity
- Migration overhead for multiple tenants

The code examples can be found in the repository: [https://github.com/KalimeroMK/multitenant](https://github.com/KalimeroMK/multitenant)
