<?php

namespace WayOfDev\Cycle;

use League\Flysystem\PathPrefixer;

class LaravelCycleOrmAdapter
{
    public const PACKAGE_NAME = 'laravel-cycle-orm-adapter';

    private PathPrefixer $homePrefixer;

    public function __construct()
    {
        $needle = DIRECTORY_SEPARATOR.self::PACKAGE_NAME.DIRECTORY_SEPARATOR;

        $this->homePrefixer = new PathPrefixer(
            substr(__DIR__, 0, stripos(__DIR__, $needle)).DIRECTORY_SEPARATOR.self::PACKAGE_NAME,
            DIRECTORY_SEPARATOR
        );
    }

    public function path(string $path = DIRECTORY_SEPARATOR): string
    {
        return $this->homePrefixer->prefixPath($path);
    }
}
