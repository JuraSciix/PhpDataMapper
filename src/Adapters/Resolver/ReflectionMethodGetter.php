<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\Adapters\Model\GetterInterface;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @template-covariant TValue
 * @template-implements GetterInterface<TValue>
 */
final class ReflectionMethodGetter implements GetterInterface {

    function __construct(
        readonly ReflectionMethod $method
    ) {}

    /**
     * @inheritDoc
     */
    function get(object $instance): mixed {
        return $this->method->invoke($instance);
    }
}