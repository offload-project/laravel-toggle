<?php

declare(strict_types=1);

namespace OffloadProject\Toggle;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use OffloadProject\Toggle\Commands\CacheClearCommand;
use OffloadProject\Toggle\Commands\CreateCommand;
use OffloadProject\Toggle\Commands\ListCommand;
use OffloadProject\Toggle\Contracts\Driver;
use OffloadProject\Toggle\Drivers\ConfigDriver;
use OffloadProject\Toggle\Drivers\DatabaseDriver;

class ToggleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/toggle.php', 'toggle');

        $this->app->singleton(ToggleManager::class, function (Application $app): ToggleManager {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);

            $manager = new ToggleManager(
                $config->get('toggle', []),
                $app->make('cache'),
            );

            $manager->setDriver($this->createDriver($app, $config));

            return $manager;
        });

        $this->app->alias(ToggleManager::class, 'toggle');
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerBladeDirectives();
        $this->registerCommands();
    }

    protected function createDriver(Application $app, ConfigRepository $config): Driver
    {
        $driver = $config->get('toggle.driver', 'config');

        return match ($driver) {
            'database' => new DatabaseDriver(
                $app->make('db.connection'),
                $config,
            ),
            default => new ConfigDriver($config),
        };
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/toggle.php' => config_path('toggle.php'),
        ], 'toggle-config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'toggle-migrations');
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('toggle', function (string $expression): string {
            return "<?php if (app(\OffloadProject\Toggle\ToggleManager::class)->active({$expression})): ?>";
        });

        Blade::directive('endtoggle', function (): string {
            return '<?php endif; ?>';
        });

        Blade::directive('elsetoggle', function (): string {
            return '<?php else: ?>';
        });
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            CacheClearCommand::class,
            CreateCommand::class,
            ListCommand::class,
        ]);
    }
}
