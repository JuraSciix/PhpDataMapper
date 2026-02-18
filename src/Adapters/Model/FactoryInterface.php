<?php

namespace JuraSciix\DataMapper\Adapters\Model;

/**
 * @template-covariant TValue
 *
 * Фабрика для объектов данного типа.
 */
interface FactoryInterface {

    /**
     * @param array $args Аргументы. Обычно для promoted-свойств.
     * @return TValue Новый объект
     */
    function create(array $args): mixed;
}