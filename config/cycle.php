<?php

declare(strict_types=1);

use Cycle\Annotated;
use Cycle\Database\Config;
use Cycle\ORM\Collection;
use Cycle\ORM\SchemaInterface;
use Cycle\Schema;

return [
    /*
     * Where ClassLocator should search for entities and embeddings.
     * Important: By default, Laravel's application skeleton has its Model classes in the app/Models folder.
     * With Cycle you'll need to create a dedicated folder for your Entities and point your config/cycle.php
     * paths array to it. If you don't, Cycle will scan your whole app/ folder for files,
     * which will have a huge impact on performance!
     */
    'tokenizer' => [
        'directories' => [
            app_path('Models'),
        ],

        // ...
        'exclude' => [],

        // ...
        'scopes' => [],
    ],

    'database' => [
        'default' => 'default',

        // ...
        'aliases' => [],

        'databases' => [
            'default' => [
                'connection' => 'sqlite',
            ],
        ],

        'shared' => [
            'driver' => env('DB_DRIVER', 'sqlite'),
            'reconnect' => env('DB_RECONNECTS', true),
            'timezone' => env('DB_TIMEZONE', \config('app.timezone')),
            'queryCache' => env('ENABLE_QUERY_CACHING', false),
            'readonlySchema' => env('DB_CONNECTION_READONLY_SCHEMA', true),
            'readonly' => env('DB_CONNECTION_READONLY', false),
            'pdo_options' => []
        ],

        /*
         * Configuring connections, see:
         * https://cycle-orm.dev/docs/database-connect/2.x/en
         */
        'connections' => [

            /**
             * Setup sqlite database in-memory for testing purposes
             * {@see \Cycle\Database\Config\DriverConfig::class}
             */
            'sqlite' => [
                /**
                 * options:
                 *     - :memory:
                 *     - <file_path>
                 *     - ''
                 */
                'database' => file_exists(database_path('database.sqlite')) ? database_path('database.sqlite') : ':memory:',
            ],

            'pgsql' => new Config\PostgresDriverConfig(
                connection: new Config\Postgres\TcpConnectionConfig(
                    database: env('DB_NAME', 'wod'),
                    host: env('DB_HOST', '127.0.0.1'),
                    port: (int) env('DB_PORT', 5432),
                    user: env('DB_USER', 'wod'),
                    password: env('DB_PASSWORD')
                ),
                schema: Config\PostgresDriverConfig::DEFAULT_SCHEMA,
                reconnect: true,
                timezone: 'UTC',
                queryCache: true
            ),

            'mysql' => [
                new Config\MySQLDriverConfig(
                    connection: new Config\MySQL\TcpConnectionConfig(
                        database: env('DB_NAME', 'wod'),
                        host: env('DB_HOST', '127.0.0.1'),
                        port: (int) env('DB_PORT', 3306),
                        user: env('DB_USER', 'wod'),
                        password: env('DB_PASSWORD')
                    ),
                    queryCache: true,
                )
            ],

            'sqlserver' => new Config\SQLServerDriverConfig(
                connection: new Config\SQLServer\TcpConnectionConfig(
                    database: env('DB_NAME', 'wod'),
                    host: env('DB_HOST', '127.0.0.1'),
                    port: (int) env('DB_PORT', 1433),
                    user: env('DB_USER', 'wod'),
                    password: env('DB_PASSWORD')
                ),
                queryCache: true,
            ),
        ],
    ],

    'schema' => [
        'cache' => [
            /*
             * enabled => true (Default) - Schema will be stored in a cache after compilation.
             * It won't be changed after entity modification. Use `php app.php cycle` to update schema.
             *
             * enabled => false - Schema won't be stored in a cache after compilation.
             * It will be automatically changed after entity modification. (Development mode)
             */
            'enabled' => (bool) env('DB_SCHEMA_CACHE', true),

            'storage' => env('DB_SCHEMA_CACHE_DRIVER', 'file'),
        ],

        /*
         * The CycleORM provides the ability to manage default settings for
         * every schema with not defined segments
         */
        'defaults' => [
            SchemaInterface::MAPPER => \Cycle\ORM\Mapper\Mapper::class,
            SchemaInterface::REPOSITORY => \Cycle\ORM\Select\Repository::class,
            SchemaInterface::SCOPE => null,
            SchemaInterface::TYPECAST_HANDLER => [
                //
            ],
        ],

        'collections' => [
            'default' => 'illuminate',
            'factories' => [
                'array' => Collection\ArrayCollectionFactory::class,
                'illuminate' => Collection\IlluminateCollectionFactory::class,
            ],
        ],

        /*
         * https://cycle-orm.dev/docs/intro-install/2.x/en
         * See section - Schema Generation
         */
        'generators' => [
            Annotated\Embeddings::class,                 // register embeddable entities
            Annotated\Entities::class,                   // register annotated entities
            Annotated\TableInheritance::class,           // register STI/JTI
            Annotated\MergeColumns::class,               // add @Table column declarations

            Schema\Generator\ResetTables::class,         // re-declared table schemas (remove columns)
            Schema\Generator\GenerateRelations::class,   // generate entity relations
            Schema\Generator\GenerateModifiers::class,   // generate changes from schema modifiers
            Schema\Generator\ValidateEntities::class,    // make sure all entity schemas are correct
            Schema\Generator\RenderTables::class,        // declare table schemas
            Schema\Generator\RenderRelations::class,     // declare relation keys and indexes
            Schema\Generator\RenderModifiers::class,     // render all schema modifiers
            Annotated\MergeIndexes::class,               // add @Table column declarations

            Schema\Generator\GenerateTypecast::class,    // typecast non string columns
        ],
    ],

    'migrations' => [
        'directory' => database_path('migrations'),

        'table' => env('DB_MIGRATIONS_TABLE', 'migrations'),

        'safe' => env('APP_ENV') !== 'production',
    ],

    /*
     * Custom relation types for entities
     */
    'relations' => [
        // ...
    ],
];
