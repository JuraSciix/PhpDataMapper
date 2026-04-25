<?php

namespace JuraSciix\DataMapper\Exceptions;

use RuntimeException;

/**
 * Техническое исключение. Не должно выходить за пределы библиотеки
 */
class DeserializeException extends RuntimeException {

    /**
     * @var string
     */
    private $stack = [];

    function unshiftStack(string $frame): void {
        array_unshift($this->stack, $frame);
    }

    function getPath(): string {
        return '$.' . join('.', $this->stack);
    }
}