<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\Adapters\Model\SetterInterface;
use ReflectionProperty;

/**
 * @template TValue
 * @template-implements SetterInterface<TValue>
 *
 * @internal
 */
final class ReflectionPropertySetter implements SetterInterface {

    function __construct(
        readonly ReflectionProperty $property
    ) {}

    /**
     * @inheritDoc
     */
    function set(object $instance, mixed $value): void {
        $this->property->setValue($instance, $value);
    }
}