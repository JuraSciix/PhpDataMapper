<?php

namespace JuraSciix\DataMapper\Adapters;

use InvalidArgumentException;
use JuraSciix\DataMapper\DataMapper;
use LogicException;

/**
 * @template-extends GenericAdapter<never-return>
 *
 * @internal
 */
final class ProxyArrayAdapter extends GenericAdapter {

    /**
     * @inheritDoc
     */
    function createLambda(array $genericAdapters, bool $soft): GenericAdapterLambda {
        // В зависимости от числа аргументов, выбираем адаптер...
        // Если аргументов нет совсем, то в зависимости от $soft:
        // - генерируем ошибку;
        // - используем два адаптера для типа mixed;
        if (!$soft && !(1 <= count($genericAdapters) && count($genericAdapters) <= 2)) {
            $received = count($genericAdapters);
            throw new InvalidArgumentException(
                "Required 1 or 2 generic adapters, but received $received");
        }
        if (empty($genericAdapters)) {
            $genericAdapters = [EmptyAdapter::instance(), EmptyAdapter::instance()];
        }

        $adapter = match (count($genericAdapters)) {
            1 => new ListArrayAdapter(),
            2 => new AssocArrayAdapter(),
            default => throw new InvalidArgumentException()
        };
        return $adapter->createLambda($genericAdapters, $soft);
    }

    function getGenericTypeCount(): int {
        return 2;
    }

    /**
     * @inheritDoc
     */
    function deserializeWithGenerics(DataMapper $mapper, mixed $data, array $adapters): mixed {
        throw new LogicException();
    }

    /**
     * @inheritDoc
     */
    function serializeWithGenerics(DataMapper $mapper, mixed $data, array $adapters): mixed {
        throw new LogicException();
    }
}