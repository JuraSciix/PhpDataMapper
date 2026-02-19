<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\Adapters\Resolver\AdapterResolver;
use JuraSciix\DataMapper\Exception\ResolveException;

/**
 * Интерфейс для адаптеров, которые нельзя вызывать напрямую, и пригодные только для промежуточных операций.
 *
 * {@link AdapterResolver} учитывает этот интерфейс.
 */
interface Unusable {

    /**
     * Описание причины, почему адаптер нельзя вызывать.
     * Значение будет передано в {@link ResolveException}.
     *
     */
    function errorMessage(): string;
}