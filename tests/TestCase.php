<?php

namespace Gajosu\EloquentPreferences\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Gajosu\EloquentPreferences\EloquentPreferencesServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            EloquentPreferencesServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-http-client_table.php.stub';
        $migration->up();
        */
    }
}
