<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;

/**
 * @template TValue
 * @template-implements AdapterInterface<TValue>
 *
 * @internal
 */
final class GenericAdapterLambda implements AdapterInterface {

    /**
     * @param GenericAdapter<TValue> $adapter
     * @param AdapterInterface<?>[] $genericTypes
     */
    function __construct(
        readonly GenericAdapter $adapter,
        readonly array $genericTypes
    ) {}

    /**
     * @inheritDoc
     */
    function deserialize(DataMapper $mapper, mixed $data): mixed {
        return $this->adapter->deserializeWithGenerics($mapper, $data, $this->genericTypes);
    }

    /**
     * @inheritDoc
     */
    function serialize(DataMapper $mapper, mixed $data): mixed {
        return $this->adapter->serializeWithGenerics($mapper, $data, $this->genericTypes);
    }
}