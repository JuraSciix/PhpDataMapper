<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;
use LogicException;

/**
 * @template-implements AdapterInterface<mixed>
 */
abstract class DoubleGenericAdapter implements AdapterInterface {

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
     * @template TGeneric1
     * @template TGeneric2
     *
     * @param DataMapper $mapper
     * @param mixed $data
     * @param AdapterInterface<TGeneric1> $adapter1
     * @param AdapterInterface<TGeneric2> $adapter2
     * @return mixed
     */
    abstract function deserializeWithGenerics(DataMapper       $mapper, mixed $data,
                                              AdapterInterface $adapter1,
                                              AdapterInterface $adapter2): mixed;

    /**
     * @template TGeneric1
     * @template TGeneric2
     *
     * @param DataMapper $mapper
     * @param mixed $data
     * @param AdapterInterface<TGeneric1> $adapter1
     * @param AdapterInterface<TGeneric2> $adapter2
     * @return mixed
     */
    abstract function serializeWithGenerics(DataMapper       $mapper, mixed $data,
                                            AdapterInterface $adapter1,
                                            AdapterInterface $adapter2): mixed;
}