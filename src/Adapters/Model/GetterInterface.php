<?php

namespace JuraSciix\DataMapper\Adapters\Model;

/**
 * @template-covariant TValue
 */
interface GetterInterface {

    /**
     * @return TValue
     */
    function get(object $instance): mixed;
}