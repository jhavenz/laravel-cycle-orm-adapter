<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace WayOfDev\Cycle\Bridge\Laravel;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseInterface as DatabaseContract;
use Cycle\Database\DatabaseProviderInterface as DatabaseProviderContract;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator as CycleMigrator;
use Cycle\Migrations\RepositoryInterface as MigrationRepositoryContract;
use Cycle\ORM\Factory;
use Cycle\ORM\FactoryInterface as OrmFactoryInterface;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\SchemaInterface;
use Illuminate\Contracts\Cache\Factory as CacheContract;
use Illuminate\Contracts\Config\Repository as IlluminateConfig;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Spiral\Tokenizer\ClassesInterface;
use Spiral\Tokenizer\ClassesInterface as TokenizerClassesContract;
use Spiral\Tokenizer\ClassLocator;
use Spiral\Tokenizer\Config\TokenizerConfig;
use Spiral\Tokenizer\Tokenizer;
use WayOfDev\Cycle\Collection\CollectionConfig;
use WayOfDev\Cycle\Config;
use WayOfDev\Cycle\Console\Commands;
use WayOfDev\Cycle\Contracts\Config\Repository as ConfigRepositoryContract;
use WayOfDev\Cycle\Contracts\EntityManager as EntityManagerContract;
use WayOfDev\Cycle\Contracts\SchemaManager as SchemaManagerContract;
use WayOfDev\Cycle\Entity\Manager as EntityManager;
use WayOfDev\Cycle\LaravelCycleOrmAdapter;
use WayOfDev\Cycle\Schema\Manager;
use WayOfDev\Cycle\Schema\SchemaGeneratorsFactory;

final class CycleServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public array $singletons = [
        LaravelCycleOrmAdapter::class,
    ];

    public function provides(): array
    {
        return [
            ClassLocator::class,
            CollectionConfig::class,
            ConfigRepositoryContract::class,
            CycleMigrator::class,
            DatabaseConfig::class,
            DatabaseContract::class,
            DatabaseProviderContract::class,
            EntityManagerContract::class,
            MigrationConfig::class,
            MigrationRepositoryContract::class,
            OrmFactoryInterface::class,
            SchemaInterface::class,
            SchemaGeneratorsFactory::class,
            Tokenizer::class,
            TokenizerClassesContract::class,
            TokenizerConfig::class,
        ];
    }

    public function register(): void
    {
        $this->registerPackageManager();
        $this->registerConfigAdapter();
        $this->registerClassLocator();
        $this->registerDatabaseManager();
        $this->registerEntityManager();
        $this->registerORM();
        $this->registerMigrator();
        $this->registerSchemaManager();

        $this->mergeConfigFrom(
            $this->app[LaravelCycleOrmAdapter::class]->configPath('cycle.php'),
            LaravelCycleOrmAdapter::CFG_KEY
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->app[LaravelCycleOrmAdapter::class]->configPath('cycle.php') => config_path('cycle.php'),
            ]);

            $this->registerConsoleCommands();
        }
    }

    private function registerPackageManager(): void
    {
        $this->app->resolving(
            LaravelCycleOrmAdapter::class,
            static fn (LaravelCycleOrmAdapter $manager) => $manager->registerBaseBindings()
        );
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

    private function registerConfigAdapter(): void
    {
        $this->app->singleton(
            ConfigRepositoryContract::class,
            static fn (Application $app): ConfigRepositoryContract => Config::fromArray(
                config: $app[IlluminateConfig::class]->get(LaravelCycleOrmAdapter::CFG_KEY)
            )
        );
    }

    private function registerClassLocator(): void
    {
        $this->app->singleton(
            TokenizerConfig::class,
            static fn (Application $app): TokenizerConfig => new TokenizerConfig(
                config: $app[IlluminateConfig::class]->get(LaravelCycleOrmAdapter::CFG_KEY_TOKENIZER)
            )
        );

        $this->app->singleton(
            Tokenizer::class,
            static fn (Application $app): Tokenizer => new Tokenizer(
                config: $app[TokenizerConfig::class]
            )
        );

        $this->app->singleton(
            ClassLocator::class,
            static fn (Application $app): TokenizerClassesContract => $app[Tokenizer::class]
        );

        $this->app->bind(
            ClassesInterface::class,
            static fn (Application $app): ClassesInterface => $app->make(ClassLocator::class)
        );

        $this->app->alias(
            TokenizerClassesContract::class,
            ClassLocator::class
        );
    }

    private function registerDatabaseManager(): void
    {
        $this->app->singleton(
            DatabaseConfig::class,
            static fn (Application $app): DatabaseConfig => new DatabaseConfig(
                config: $app[IlluminateConfig::class]->get(LaravelCycleOrmAdapter::CFG_KEY_DATABASE)
            )
        );

        $this->app->singleton(
            DatabaseProviderContract::class,
            static fn (Application $app): DatabaseProviderContract => $app[LaravelCycleOrmAdapter::class]
        );

        $this->app->bind(
            DatabaseContract::class,
            static fn (Application $app): DatabaseContract => $app[DatabaseProviderContract::class]->database()
        );

        //$this->app->alias(
        //    DatabaseProviderContract::class,
        //    DatabaseManager::class
        //);
    }

    private function registerEntityManager(): void
    {
        $this->app->singleton(
            EntityManagerContract::class,
            static fn (Application $app): EntityManagerContract => $app[EntityManager::class]
        );
    }

    private function registerORM(): void
    {
        $this->app->singleton(
            CollectionConfig::class,
            static fn (Application $app): CollectionConfig => new CollectionConfig(
                config: $app[IlluminateConfig::class]->get(LaravelCycleOrmAdapter::CFG_KEY_COLLECTIONS)
            )
        );

        $this->app->singleton(
            OrmFactoryInterface::class,
            static fn (Application $app): OrmFactoryInterface => new Factory(
                dbal: $app[DatabaseProviderContract::class],
                defaultCollectionFactory: with(
                    $app[CollectionConfig::class]->getDefaultCollectionFactoryClass(),
                    fn ($collectionClass) => $app[$collectionClass]
                )
            )
        );

        $this->app->singleton(
            ORMInterface::class,
            static fn (Application $app): ORMInterface => new ORM(
                factory: $app[OrmFactoryInterface::class],
                schema: $app[SchemaInterface::class]
            )
        );
    }

    private function registerMigrator()
    {
        $this->app->singleton(
            MigrationConfig::class,
            static fn (Application $app): MigrationConfig => new MigrationConfig(
                config: $app[IlluminateConfig::class]->get(LaravelCycleOrmAdapter::CFG_KEY_MIGRATIONS)
            )
        );

        $this->app->singleton(
            MigrationRepositoryContract::class,
            static fn (Application $app): MigrationRepositoryContract => new FileRepository(
                config: $app[MigrationConfig::class]
            )
        );

        $this->app->singleton(
            CycleMigrator::class,
            static fn (Application $app): CycleMigrator => new CycleMigrator(
                config: $app[MigrationConfig::class],
                dbal: $app[DatabaseProviderContract::class],
                repository: $app[MigrationRepositoryContract::class]
            )
        );
    }

    private function registerSchemaManager()
    {
        $this->app->singleton(
            SchemaInterface::class,
            static fn (Application $app): SchemaInterface => $app[SchemaManagerContract::class]->create()
        );

        $this->app->singleton(
            SchemaGeneratorsFactory::class,
            static fn (Application $app): SchemaGeneratorsFactory => new SchemaGeneratorsFactory(
                app: $app,
                generatorClasses: $app[IlluminateConfig::class]->get(LaravelCycleOrmAdapter::CFG_KEY_GENERATORS)
            )
        );

        $this->app->bind(
            SchemaManagerContract::class,
            static fn (Application $app, array $params = []): SchemaManagerContract => new Manager(
                databaseManager: $app[LaravelCycleOrmAdapter::class]->getDatabaseManager($params['connection'] ?? null),
                schemaGeneratorsFactory: $app[SchemaGeneratorsFactory::class],
                config: $app[ConfigRepositoryContract::class],
                cache: $app[CacheContract::class]->store()
            )
        );
    }
}
