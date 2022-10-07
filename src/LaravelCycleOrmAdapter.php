<?php

namespace WayOfDev\Cycle;

use Cycle\Database\Config;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseProviderInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Manager;
use League\Flysystem\PathPrefixer;
use WayOfDev\Cycle\Contracts\Config\Repository as ConfigRepositoryContract;

class LaravelCycleOrmAdapter extends Manager implements DatabaseProviderInterface
{
    // misc
    public const PACKAGE_NAME = 'laravel-cycle-orm-adapter';

    // config targets
    public const CFG_KEY = 'cycle';
    public const CFG_KEY_DATABASE = 'cycle.database';
    public const CFG_KEY_TOKENIZER = 'cycle.tokenizer';
    public const CFG_KEY_MIGRATIONS = 'cycle.migrations';
    public const CFG_KEY_GENERATORS = 'cycle.schema.generators';
    public const CFG_KEY_COLLECTIONS = 'cycle.schema.collections';

    // path bindings
    private const BASE_PATH_BINDING = self::CFG_KEY.'.basePath';
    private const HOME_PATH_BINDING = self::CFG_KEY.'.homePath';
    private const ALL_PATHS_BINDING = self::CFG_KEY.'.all_paths';
    private const TESTS_PATH_BINDING = self::CFG_KEY.'.testsPath';
    private const VENDOR_PATH_BINDING = self::CFG_KEY.'.vendorPath';
    private const CONFIG_PATH_BINDING = self::CFG_KEY.'.configPath';
    private const ASSETS_PATH_BINDING = self::CFG_KEY.'.assetsPath';
    private const DATABASE_PATH_BINDING = self::CFG_KEY.'.databasePath';
    private const RESOURCES_PATH_BINDING = self::CFG_KEY.'.resourcesPath';

    public function basePath(string $path = DIRECTORY_SEPARATOR): string
    {
        return $this->prefixer(self::BASE_PATH_BINDING)->prefixPath($path);
    }

    public function configPath(string $path = DIRECTORY_SEPARATOR): string
    {
        return $this->prefixer(self::CONFIG_PATH_BINDING)->prefixPath($path);
    }

    public function createDefaultDriver(): Config\ConnectionConfig
    {
        return $this->createDriver($this->getDefaultDriver());
    }

    public function createMysqlDriver(): Config\SQLiteDriverConfig
    {
        $configPath = implode('.', [
            self::CFG_KEY_DATABASE,
            'connections',
            'sqlite'
        ]);

        //return new Config\MySQLDriverConfig(
        //    connection: new Config\MySQL\TcpConnectionConfig(
        //        database: env('DB_NAME', 'wod'),
        //        host: env('DB_HOST', '127.0.0.1'),
        //        port: (int) env('DB_PORT', 3306),
        //        user: env('DB_USER', 'wod'),
        //        password: env('DB_PASSWORD')
        //    ),
        //    queryCache: true,
        //)
    }

    public function createSqliteDriver(): Config\SQLiteDriverConfig
    {
        $database = $this->packageConfig()->connection('sqlite.database', ':memory:');
        $pdoOptions = $this->packageConfig()->database('shared.pdo_options');

        /** @var Config\SQLite\ConnectionConfig $connectionConfig */
        $connectionConfig = match(true) {
            blank($database) => new Config\SQLite\TempFileConnectionConfig($pdoOptions),
            filled($database) && file_exists($database) => new Config\SQLite\FileConnectionConfig($database, $pdoOptions),
            default => new Config\SQLite\MemoryConnectionConfig($pdoOptions),
        };

        return new Config\SQLiteDriverConfig(...[
            'connection' => $connectionConfig,
            ...$this->sharedDatabaseConfigurations()
        ]);
    }

    public function database(string $database = null): DatabaseInterface
    {
        return $this->driver($database);
    }

    public function getDatabaseManager(string $connection = null): DatabaseManager
    {
        return $this->container->make(DatabaseManager::class);
        //return $this->container->make(DatabaseManager::class, [
        //    'config' => $this->driver($connection),
        //]);
    }

    public function getDefaultDriver()
    {
        return $this->packageConfig()->database('databases.default.connection', 'sqlite');
    }

    public function paths(): Collection
    {
        return collect(app()->tagged(self::ALL_PATHS_BINDING));
    }

    public function registerBaseBindings(): void
    {
        if (app()->has(self::HOME_PATH_BINDING)) {
            return;
        }

        $prefixer = $this->prefixer(
            substr(__DIR__, 0, stripos(__DIR__, self::PACKAGE_NAME)).self::PACKAGE_NAME
        );

        $bindings = [
            self::HOME_PATH_BINDING => '/',
            self::BASE_PATH_BINDING => 'src',
            self::CONFIG_PATH_BINDING => 'config',
            self::ASSETS_PATH_BINDING => 'assets',
            self::DATABASE_PATH_BINDING => 'database',
            self::RESOURCES_PATH_BINDING => 'resources',
            self::TESTS_PATH_BINDING => 'tests',
            self::VENDOR_PATH_BINDING => 'vendor',
        ];

        foreach ($bindings as $binding => $dirName) {
            $this->container->instance($binding, $prefixer->prefixPath($dirName));
        }

        $this->container->tag(array_keys($bindings), self::ALL_PATHS_BINDING);
    }

    private function prefixer(string $prefix): PathPrefixer
    {
        return new PathPrefixer(
            $this->container[$prefix] ?? $prefix,
            DIRECTORY_SEPARATOR
        );
    }

    public function context(): string
    {
        return str(__DIR__)->is('*vendor*'.self::PACKAGE_NAME.'*') ? 'package_dev' : 'vendor_package';
    }

    private function sharedDatabaseConfigurations()
    {
        $defaults = [
            'driver' => 'sqlite',
            'reconnect' => true,
            'timezone' => \config('app.timezone', fn () => date_default_timezone_get()),
            'queryCache' => false,
            'readonlySchema' => true,
            'readonly' => false,
        ];

        return Arr::only(
            $this->packageConfig()->database('shared', $defaults),
            array_keys($defaults)
        );
    }

    public function __call($method, $parameters)
    {
        return $this->getDatabaseManager($this->driver())->$method(...$parameters);
    }

    private function packageConfig(): ConfigRepositoryContract
    {
        return app(ConfigRepositoryContract::class);
    }
}
