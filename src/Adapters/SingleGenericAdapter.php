<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;
use LogicException;

/**
 * @template-implements AdapterInterface<?>
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
     * @template TGeneric
     *
     * @param DataMapper $mapper
     * @param mixed $data
     * @param AdapterInterface<TGeneric> $adapter
     * @return mixed
     */
    abstract function deserializeWithGeneric(DataMapper $mapper, mixed $data, AdapterInterface $adapter): mixed;

    /**
     * @template TGeneric
     *
     * @param DataMapper $mapper
     * @param mixed $data
     * @param AdapterInterface<TGeneric> $adapter
     * @return mixed
     */
    abstract function serializeWithGeneric(DataMapper $mapper, mixed $data, AdapterInterface $adapter): mixed;
}