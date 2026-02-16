<?php

namespace JuraSciix\DataMapper\Adapters\Model;

/**
 * @template TValue
 */
interface SetterInterface {

    /**
     * @param TValue $value
     */
    function set(object $instance, mixed $value): void;
}