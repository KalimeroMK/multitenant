<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @property int $id
 * @property string $name
 * @property string $domain
 * @property string $database Name of the tenant's database.
 */
class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'database',
    ];

    protected $connection = 'owner'; // Default connection for the owner database

    /**
     * Configure the tenant's database connection dynamically.
     */
    public function configure(): self
    {
        // Update the configuration for the tenant connection dynamically
        config([
            'database.connections.tenant.database' => $this->database,
        ]);

        // Purge the 'tenant' connection to refresh its settings
        DB::purge('tenant');

        // Clear tenant-specific cache if cache table exists
        if (Schema::hasTable('cache')) {
            $this->clearTenantCache();
        }

        return $this;
    }

    /**
     * Activate the tenant context across the application.
     */
    public function use(): self
    {
        // Set the default database connection to 'tenant'
        DB::setDefaultConnection('tenant');

        return $this;
    }

    /**
     * Clear cache specific to the tenant.
     */
    public function clearTenantCache(): void
    {
        // Get tenant-specific cache keys
        $cacheKeys = Cache::get('tenant_'.$this->id.'_keys', []);

        // Forget each cache key
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Optionally remove the keys tracking itself
        Cache::forget('tenant_'.$this->id.'_keys');
    }

    /**
     * Add a tenant-specific cache key.
     */
    public function addCacheKey(string $key): void
    {
        // Get the current list of cache keys for the tenant
        $cacheKeys = Cache::get('tenant_'.$this->id.'_keys', []);

        // Add the new cache key
        $cacheKeys[] = $key;

        // Update the list in the cache
        Cache::put('tenant_'.$this->id.'_keys', $cacheKeys);
    }
}
