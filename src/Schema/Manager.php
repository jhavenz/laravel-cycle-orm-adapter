<?php

declare(strict_types=1);

namespace WayOfDev\Cycle\Schema;

use Cycle\Database\DatabaseManager;
use Cycle\ORM\Schema as ORMSchema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Registry;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use WayOfDev\Cycle\Contracts\Config\Repository as Config;
use WayOfDev\Cycle\Contracts\SchemaManager;

final class Manager implements SchemaManager
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private SchemaGeneratorsFactory $schemaGeneratorsFactory,
        private Config $config,
        private CacheRepository $cache
    ) {
    }

    public function create(): ORMSchema
    {
        $schema = (new Compiler())->compile(
            new Registry($this->databaseManager),
            $this->schemaGeneratorsFactory->get(),
            $this->config->schema()['defaults'] ?? []
        );

        return new ORMSchema($schema);
    }

    public function flush(): void
    {
        // TODO: Implement flush() method.
    }
}
