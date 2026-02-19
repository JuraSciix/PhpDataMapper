<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;
use LogicException;

/**
 * @template TValue
 * @template TGeneric
 * @template-implements AdapterInterface<TValue>
 */
abstract class SingleGenericAdapter implements AdapterInterface {

    /**
     * @inheritDoc
     */
    final function deserialize(DataMapper $mapper, mixed $data): mixed {
        throw new LogicException();
    }

    /**
     * @inheritDoc
     */
    final function serialize(DataMapper $mapper, mixed $data): mixed {
        throw new LogicException();
    }

    /**
     * @param DataMapper $mapper
     * @param mixed $data
     * @param AdapterInterface<TGeneric> $adapter
     * @return TValue
     */
    abstract function deserializeWithGeneric(DataMapper $mapper, mixed $data, AdapterInterface $adapter): mixed;

    /**
     * @param DataMapper $mapper
     * @param TValue $data
     * @param AdapterInterface<TGeneric> $adapter
     * @return mixed
     */
    abstract function serializeWithGeneric(DataMapper $mapper, mixed $data, AdapterInterface $adapter): mixed;
}