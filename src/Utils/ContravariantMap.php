<?php

namespace JuraSciix\DataMapper\Utils;

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
        // Сначала запрашиваем супертипы, чтобы проверить $type на корректность
        foreach (TypeHelper::getSuperTypes($type) as $superType) {
            $this->map[$superType] = $value;
        }

        $this->map[$type] = $value;
    }

    /**
     * @return V|null
     */
    function get(string $value): mixed {
        return $this->map[$value] ?? null;
    }

    /**
     * @return bool
     */
    function contains(string $type) {
        return array_key_exists($type, $this->map);
    }

    /**
     * @return int
     */
    function size() {
        return count($this->map);
    }
}