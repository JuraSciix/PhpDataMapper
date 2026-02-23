<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\Utils\CovariantMap;

/**
 * @template TValue
 * @template-implements RegistryInterface<TValue
 */
final class CovariantRegistry implements RegistryInterface {
    private readonly CovariantMap $map;

    function __construct() {
        $this->map = new CovariantMap();
    }

    /**
     * @inheritDoc
     */
    function insert(mixed $key, mixed $value): void {
        $this->map->put($key, $value);
    }

    /**
     * @inheritDoc
     */
    function find(mixed $key): mixed {
        if (!$this->map->contains($key)) return null;
        return $this->map->get($key);
    }
}