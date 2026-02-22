<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;
use LogicException;

/**
 * @template TValue
 * @template-extends GenericAdapter<TValue>
 */
abstract class SingleGenericAdapter extends GenericAdapter {

    final function getGenericTypeCount(): int {
        return 1;
    }

    /**
     * @inheritDoc
     */
    final function deserializeWithGenerics(DataMapper $mapper, mixed $data, array $adapters): mixed {
        return $this->deserializeWithGeneric($mapper, $data, $adapters[0]);
    }

    /**
     * @inheritDoc
     */
    final function serializeWithGenerics(DataMapper $mapper, mixed $data, array $adapters): mixed {
        return $this->serializeWithGeneric($mapper, $data, $adapters[0]);
    }

    /**
     * @template TGeneric
     *
     * @param DataMapper $mapper
     * @param mixed $data
     * @param AdapterInterface<TGeneric> $adapter
     * @return TValue
     */
    abstract function deserializeWithGeneric(DataMapper $mapper, mixed $data, AdapterInterface $adapter): mixed;

    /**
     * @template TGeneric
     *
     * @param DataMapper $mapper
     * @param TValue $data
     * @param AdapterInterface<TGeneric> $adapter
     * @return mixed
     */
    abstract function serializeWithGeneric(DataMapper $mapper, mixed $data, AdapterInterface $adapter): mixed;
}