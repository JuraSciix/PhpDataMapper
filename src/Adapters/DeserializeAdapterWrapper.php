<?php

namespace JuraSciix\DataMapper\Adapters;

use Closure;
use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;

/**
 * @template-covariant TValue
 * @template-implements AdapterInterface<TValue>
 *
 * @internal
 */
final class DeserializeAdapterWrapper implements AdapterInterface {
    private readonly Closure $adapter;

    /**
     * @param callable(mixed):TValue $adapter
     */
    function __construct(callable $adapter) {
        $this->adapter = $adapter(...);
    }

    /**
     * @inheritDoc
     */
    function deserialize(DataMapper $mapper, mixed $data): mixed {
        return ($this->adapter)($data);
    }

    /**
     * @inheritDoc
     */
    function serialize(DataMapper $mapper, mixed $data): mixed {
        return $data;
    }
}