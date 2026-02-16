<?php

namespace JuraSciix\DataMapper;

/**
 * @template TValue
 *
 * Адаптер данных: сериализация, десериализация.
 */
interface AdapterInterface {

    /**
     * Десериализовывает данные и возвращает результат.
     *
     * @param mixed $data Входящие данные.
     *
     * @return TValue Результат
     */
    function deserialize(DataMapper $mapper, mixed $data): mixed;

    /**
     * Сериализовывает объект.
     *
     * @param TValue $data Объект.
     * @return mixed Сериализованный результат.
     */
    function serialize(DataMapper $mapper, mixed $data): mixed;
}