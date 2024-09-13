<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.owner' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],

            'database.connections.tenant' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        $this->artisan('migrate', ['--database' => 'owner', '--path' => 'database/migrations/owner']);
        $this->artisan('migrate', ['--database' => 'tenant']);
    }
}
