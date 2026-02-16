<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\Adapters\Model\GetterInterface;
use ReflectionProperty;

/**
 * @template-covariant TValue
 * @template-implements GetterInterface<TValue>
 */
final class ReflectionPropertyGetter implements GetterInterface {

    function __construct(
        readonly ReflectionProperty $property
    ) {}

    /**
     * @inheritDoc
     */
    function get(object $instance): mixed {
        return $this->property->getValue($instance);
    }
}