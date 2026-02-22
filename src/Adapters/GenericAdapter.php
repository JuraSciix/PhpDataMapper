<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;
use LogicException;

/**
 * @template TValue
 * @template-implements AdapterInterface<TValue>
 */
abstract class GenericAdapter implements AdapterInterface {

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
     * @return int Число параметров.
     */
    abstract function getGenericTypeCount(): int;

    /**
     * @param DataMapper $mapper
     * @param mixed $data
     * @param AdapterInterface<?>[] $adapters
     * @return TValue
     */
    abstract function deserializeWithGenerics(DataMapper $mapper, mixed $data, array $adapters): mixed;

    /**
     * @param DataMapper $mapper
     * @param TValue $data
     * @param AdapterInterface<?>[] $adapters
     * @return mixed
     */
    abstract function serializeWithGenerics(DataMapper $mapper, mixed $data, array $adapters): mixed;
}