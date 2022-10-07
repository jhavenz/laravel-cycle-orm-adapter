<?php

namespace WayOfDev\Cycle;

use Illuminate\Support\Collection;
use League\Flysystem\PathPrefixer;
use WayOfDev\Cycle\Bridge\Laravel\CycleServiceProvider;

class LaravelCycleOrmAdapter
{
    public const PACKAGE_NAME = 'laravel-cycle-orm-adapter';

    private const BASE_PATH_BINDING = CycleServiceProvider::CFG_KEY.'.basePath';
    private const HOME_PATH_BINDING = CycleServiceProvider::CFG_KEY.'.homePath';
    private const ALL_PATHS_BINDING = CycleServiceProvider::CFG_KEY.'.all_paths';
    private const TESTS_PATH_BINDING = CycleServiceProvider::CFG_KEY.'.testsPath';
    private const VENDOR_PATH_BINDING = CycleServiceProvider::CFG_KEY.'.vendorPath';
    private const CONFIG_PATH_BINDING = CycleServiceProvider::CFG_KEY.'.configPath';
    private const ASSETS_PATH_BINDING = CycleServiceProvider::CFG_KEY.'.assetsPath';
    private const DATABASE_PATH_BINDING = CycleServiceProvider::CFG_KEY.'.databasePath';
    private const RESOURCES_PATH_BINDING = CycleServiceProvider::CFG_KEY.'.resourcesPath';

    public function basePath(string $path = DIRECTORY_SEPARATOR): string
    {
        return $this->createPrefixer(self::BASE_PATH_BINDING)->prefixPath($path);
    }

    public function configPath(string $path = DIRECTORY_SEPARATOR): string
    {
        return $this->createPrefixer(self::CONFIG_PATH_BINDING)->prefixPath($path);
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

        $prefixer = $this->createPrefixer(
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
            app()->instance($binding, $prefixer->prefixPath($dirName));
        }

        app()->tag(array_keys($bindings), self::ALL_PATHS_BINDING);
    }

    private function createPrefixer(string $prefix): PathPrefixer
    {
        return new PathPrefixer(
            app()[$prefix] ?? $prefix,
            DIRECTORY_SEPARATOR
        );
    }

    public function context(): string
    {
        return str(__DIR__)->is('*vendor*'.self::PACKAGE_NAME.'*') ? 'dev' : 'installed';
    }
}
