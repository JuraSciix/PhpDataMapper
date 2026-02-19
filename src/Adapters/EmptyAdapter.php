<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;

/**
 * Адаптер, который пропускает значения без преобразования.
 *
 * @template-implements AdapterInterface<mixed>
 */
final class EmptyAdapter implements AdapterInterface {

    /**
     * @return EmptyAdapter
     */
    static function instance() {
        static $instance;

        return $instance ??= new EmptyAdapter();
    }

    function deserialize(DataMapper $mapper, mixed $data): mixed {
        return $data;
    }

    function serialize(DataMapper $mapper, mixed $data): mixed {
        return $data;
    }
}