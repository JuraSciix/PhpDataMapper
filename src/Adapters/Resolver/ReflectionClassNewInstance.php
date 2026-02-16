<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\FactoryInterface;
use ReflectionClass;
use ReflectionException;

class ReflectionClassNewInstance implements FactoryInterface {

    function __construct(
        readonly ReflectionClass $class
    ) {}

    /**
     * @inheritDoc
     *
     * @throws ReflectionException
     */
    function create(...$args): object {
        return $this->class->newInstanceArgs($args);
    }
}