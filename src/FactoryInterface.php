<?php

namespace JuraSciix\DataMapper;

/**
 * @template-covariant TValue
 *
 * Фабрика для объектов данного типа.
 */
interface FactoryInterface {

    /**
     * @param mixed ...$args Аргументы. Обычно для promoted-свойств.
     * @return TValue Новый объект
     */
    function create(mixed ...$args): mixed;
}