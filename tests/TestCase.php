<?php

declare(strict_types=1);

namespace OffloadProject\Toggle\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use OffloadProject\Toggle\ToggleServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            ToggleServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('toggle.driver', 'config');
        $app['config']->set('toggle.cache.enabled', false);
        $app['config']->set('toggle.flags', [
            'test-flag' => true,
            'disabled-flag' => false,
        ]);
    }
}
