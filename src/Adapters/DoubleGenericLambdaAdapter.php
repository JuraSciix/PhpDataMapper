<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;

/**
 * @template TGeneric1
 * @template TGeneric2
 */
final class DoubleGenericLambdaAdapter implements AdapterInterface {

    /**
     * @param AdapterInterface<TGeneric1> $adapter1
     * @param AdapterInterface<TGeneric2> $adapter2
     */
    function __construct(
        readonly DoubleGenericAdapter $adapter,
        readonly AdapterInterface $adapter1,
        readonly AdapterInterface $adapter2,
    ) {}

    /**
     * @inheritDoc
     */
    function deserialize(DataMapper $mapper, mixed $data): mixed {
        return $this->adapter->deserializeWithGenerics($mapper, $data, $this->adapter1, $this->adapter2);
    }


    /**
     * @inheritDoc
     */
    function serialize(DataMapper $mapper, mixed $data): mixed {
        return $this->adapter->serializeWithGenerics($mapper, $data, $this->adapter1, $this->adapter2);
    }
}