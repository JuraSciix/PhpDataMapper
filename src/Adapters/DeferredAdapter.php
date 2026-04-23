<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\Adapters\Resolver\AdapterResolver;
use JuraSciix\DataMapper\DataMapper;
use JuraSciix\DataMapper\Exception\DataMapperException;
use LogicException;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Throwable;

/**
 * Адаптер, который ссылается на другой адаптер по данному типу.
 * Полезен, чтобы не требовать моментального создания адаптера. Пригождается в рекурсивных случаях
 *
 * @template TValue
 * @template-extends AdapterInterface<TValue>
 *
 * @internal
 */
final class DeferredAdapter implements AdapterInterface {

    function __construct(
        readonly AdapterResolver $resolver,
        readonly TypeNode $typeNode
    ) {}

    /**
     * @inheritDoc
     *
     * @throws Throwable
     */
    function deserialize(DataMapper $mapper, mixed $data): mixed {
        return $this->resolver->resolve($this->typeNode)->deserialize($mapper, $data);
    }

    /**
     * @inheritDoc
     */
    function serialize(DataMapper $mapper, mixed $data): mixed {
        return $this->resolver->resolve($this->typeNode)->serialize($mapper, $data);
    }
}