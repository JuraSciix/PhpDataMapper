<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;

/**
 * @template TValue
 * @template-implements AdapterInterface<TValue|null>
 */
final class NullableAdapter implements AdapterInterface {

    /**
     * @param AdapterInterface<TValue> $adapter
     */
    function __construct(
        readonly AdapterInterface $adapter
    ) {}

    /**
     * @inheritDoc
     */
    function deserialize(DataMapper $mapper, mixed $data): mixed {
        if (is_null($data)) {
            return null;
        }
        return $this->adapter->deserialize($mapper, $data);
    }

    /**
     * @inheritDoc
     */
    function serialize(DataMapper $mapper, mixed $data): mixed {
        if (is_null($data)) {
            return null;
        }
        return $this->adapter->serialize($mapper, $data);
    }
}