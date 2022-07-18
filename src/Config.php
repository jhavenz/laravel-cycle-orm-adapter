<?php

declare(strict_types=1);

namespace WayOfDev\Cycle;

use Illuminate\Support\Arr;
use WayOfDev\Cycle\Contracts\Config\Repository;
use WayOfDev\Cycle\Exceptions\MissingRequiredAttributes;

use function array_diff;
use function array_keys;
use function implode;

final class Config implements Repository
{
    private const REQUIRED_FIELDS = [
        'directories',
        'databases',
        'schema',
        'migrations',
        'relations',
    ];

    public static function fromArray(array $config): self
    {
        $missingAttributes = array_diff(array_keys($config), self::REQUIRED_FIELDS);

        if ([] !== $missingAttributes) {
            throw MissingRequiredAttributes::fromArray(
                implode(',', $missingAttributes)
            );
        }

        return new self(
            $config['directories'],
            $config['databases'],
            $config['schema'],
            $config['migrations'],
            $config['relations']
        );
    }

    public function directories(): array
    {
        return $this->directories;
    }

    public function databases(): array
    {
        return $this->databases;
    }

    public function schema(): array
    {
        return $this->schema;
    }

    public function migrationsDirectory(): string
    {
        return Arr::get($this->migrations, 'directory');
    }

    public function migrationsTable(): string
    {
        return Arr::get($this->migrations, 'table');
    }

    public function relations(): array
    {
        return $this->relations;
    }

    private function __construct(
        private readonly array $directories,
        private readonly array $databases,
        private readonly array $schema,
        private readonly array $migrations,
        private readonly array $relations
    ) {
    }
}
