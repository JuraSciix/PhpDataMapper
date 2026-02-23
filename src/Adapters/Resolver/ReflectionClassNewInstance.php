<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\Adapters\Model\FactoryInterface;
use ReflectionClass;
use ReflectionException;

/**
 * @internal
 */
final class ReflectionClassNewInstance implements FactoryInterface {

    function __construct(
        readonly ReflectionClass $class
    ) {}

    /**
     * @inheritDoc
     *
     * @throws ReflectionException
     */
    function create(array $args): object {
        return $this->class->newInstanceArgs($args);
    }
}