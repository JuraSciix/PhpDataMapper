<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\Adapters\Model\SetterInterface;
use ReflectionMethod;

/**
 * @template TValue
 * @template-implements SetterInterface<TValue>
 *
 * @internal
 */
final class ReflectionMethodSetter implements SetterInterface {

    function __construct(
        readonly ReflectionMethod $method
    ) {}

    /**
     * @inheritDoc
     */
    function set(object $instance, mixed $value): void {
        $this->method->invoke($instance, $value);
    }
}