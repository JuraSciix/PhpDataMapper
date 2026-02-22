<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;

/**
 * @template TGeneric
 * @template-implements AdapterInterface<mixed>
 *
 * @deprecated Будет удалено в следующей версии
 */
final class SingleGenericLambdaAdapter implements AdapterInterface {

    /**
     * @param SingleGenericAdapter $adapter
     * @param AdapterInterface<TGeneric> $genericAdapter
     */
    function __construct(
        readonly SingleGenericAdapter $adapter,
        readonly AdapterInterface     $genericAdapter
    ) {}

    /**
     * @inheritDoc
     */
    function deserialize(DataMapper $mapper, mixed $data): mixed {
        return $this->adapter->deserializeWithGeneric($mapper, $data, $this->genericAdapter);
    }

    /**
     * @inheritDoc
     */
    function serialize(DataMapper $mapper, mixed $data): mixed {
        return $this->adapter->serializeWithGeneric($mapper, $data, $this->genericAdapter);
    }
}