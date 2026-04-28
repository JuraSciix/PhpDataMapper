<?php

namespace JuraSciix\DataMapper\Adapters;

use InvalidArgumentException;
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
     * @param AdapterInterface<?>[] $genericAdapters
     * @return GenericAdapterLambda<TValue>
     */
    function createLambda(array $genericAdapters, bool $soft): GenericAdapterLambda {
        $required = $this->getGenericTypeCount();
        if (!$soft && $required !== count($genericAdapters)) {
            $received = count($genericAdapters);
            throw new InvalidArgumentException(
                "Expected $required generic adapters, but received $received");
        }

        if (count($genericAdapters) < $required) {
            // Доопределяем тип T<unresolved...> до T<mixed...>
            array_push($genericAdapters, ...array_fill(0, $required, EmptyAdapter::instance()));
        }
        return new GenericAdapterLambda($this, array_slice($genericAdapters, 0, $required));
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