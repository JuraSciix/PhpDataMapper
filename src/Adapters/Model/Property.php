<?php

namespace JuraSciix\DataMapper\Adapters\Model;

use JuraSciix\DataMapper\AdapterInterface;

/**
 * @template TValue
 */
final class Property {

    /**
     * @param string $name Название свойства.
     * @param string $key Ключ.
     * @param bool $promoted Свойство внедрено в конструктор?
     * @param AdapterInterface<TValue> $adapter Адаптер.
     * @param bool $required Свойство не было инициализировано?
     * @param SetterInterface<TValue> $setter
     * @param GetterInterface<TValue> $getter
     */
    function __construct(
        readonly string $name,
        readonly string $key,
        readonly bool $promoted,
        readonly AdapterInterface $adapter,
        readonly bool $required,
        readonly SetterInterface $setter,
        readonly GetterInterface $getter
    ) {}
}