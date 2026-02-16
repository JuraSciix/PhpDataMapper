<?php

namespace JuraSciix\DataMapper\Exception;

use RuntimeException;

/**
 * Техническое исключение. Не должно выходить за пределы библиотеки
 */
class SerializeException extends RuntimeException {

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