<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\Utils\ContravariantMap;

/**
 * @template TValue
 * @template-implements RegistryInterface<TValue
 */
final class ContravariantRegistry implements RegistryInterface {
    private readonly ContravariantMap $map;

    function __construct() {
        $this->map = new ContravariantMap();
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