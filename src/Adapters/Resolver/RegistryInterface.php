<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

/**
 * @template TValue
 */
interface RegistryInterface {

    /**
     * @param TValue $value
     */
    function insert(mixed $key, mixed $value): void;

    /**
     * @return TValue|null
     */
    function find(mixed $key): mixed;
}