<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

/**
 * @template TValue
 * @template-implements RegistryInterface<TValue>
 *
 * @internal
 */
final class InvariantRegistry implements RegistryInterface {
    /** @var array<string, TValue> */
    private array $map;

    function insert(mixed $key, mixed $value): void {
        $this->map[$key] = $value;
    }

    function find(mixed $key): mixed {
        return $this->map[$key] ?? null;
    }
}