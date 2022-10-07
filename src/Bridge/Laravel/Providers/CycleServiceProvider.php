<?php

declare(strict_types=1);

namespace WayOfDev\Cycle\Bridge\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use WayOfDev\Cycle\Console\Commands;

final class CycleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../../../config/cycle.php' => config_path('cycle.php'),
            ]);

            $this->registerConsoleCommands();
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../../config/cycle.php',
            Registrator::CFG_KEY
        );

        $registrators = [
            Registrators\RegisterAdapterConfig::class,
            Registrators\RegisterDatabaseManager::class,
            Registrators\RegisterEntityManager::class,
            Registrators\RegisterORM::class,
            Registrators\RegisterMigrator::class,
            Registrators\RegisterClassLocator::class,
            Registrators\RegisterSchemaManager::class,
        ];

        foreach ($registrators as $registrator) {
            (new $registrator())($this->app);
        }
    }

    private function registerConsoleCommands(): void
    {
        $this->commands([
            Commands\Database\ListCommand::class,
            Commands\Database\TableCommand::class,
            Commands\ORM\SyncCommand::class,
            Commands\ORM\MigrateCommand::class,
            Commands\ORM\UpdateCommand::class,
            Commands\ORM\RenderCommand::class,
            Commands\Migrations\MigrateCommand::class,
            Commands\Migrations\InitCommand::class,
            Commands\Migrations\RollbackCommand::class,
            Commands\Migrations\ReplayCommand::class,
        ]);
    }
}
