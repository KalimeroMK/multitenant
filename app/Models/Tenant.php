<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @property int    $id
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
            'database'
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

        // Consider scoping the cache flush if possible
        if (Schema::hasTable('cache')) {
            Cache::flush();
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
}

