<?php

namespace JuraSciix\DataMapper\Utils;

use InvalidArgumentException;

/**
 * @template V
 */
final class ContravariantMap {
    /** @var array<string, V> */
    private $map = [];

    /**
     * @param string $type
     * @param V $value
     */
    function put(string $type, mixed $value): void {
        if (!TypeHelper::isValidType($type)) {
            throw new InvalidArgumentException("Invalid type: $type");
        }

        $this->map[$type] = $value;
        foreach (TypeHelper::getSuperTypes($type) as $superType) {
            $this->map[$superType] = $value;
        }
    }

    /**
     * @return V|null
     */
    function get(string $key): mixed {
        if (!TypeHelper::isValidType($key)) {
            throw new InvalidArgumentException("Invalid type: $key");
        }
        return $this->map[$key] ?? null;
    }

    /**
     * @return bool
     */
    function contains(string $type) {
        if (!TypeHelper::isValidType($type)) {
            return false;
        }
        return array_key_exists($type, $this->map);
    }

    /**
     * @return int
     */
    function size() {
        return count($this->map);
    }
}