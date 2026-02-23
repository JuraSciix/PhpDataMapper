<?php

namespace JuraSciix\DataMapper\Utils;

use InvalidArgumentException;
use JuraSciix\DataMapper\Adapters\Resolver\CovariantRegistry;

/**
 * @template V
 * @deprecated Используйте {@link CovariantRegistry}
 */
final class CovariantMap {
    /** @var array<class-string, V> */
    private $classes = [];
    private $interfaces = [];

    /**
     * @param string $type
     * @param V $value
     */
    function put(string $type, mixed $value): void {
        if (!TypeHelper::isValidType($type)) {
            throw new InvalidArgumentException("Invalid type: $type");
        }

        if (class_exists($type)) {
            $this->classes[$type] = $value;
        }

        if (interface_exists($type)) {
            $this->interfaces[$type] = $value;
        }
    }

    /**
     * @return V|null
     */
    function get(string $type): mixed {
        if (!TypeHelper::isValidType($type)) {
            throw new InvalidArgumentException("Invalid type: $type");
        }

        if (class_exists($type)) {
            $current = $type;
            do {
                if (array_key_exists($current, $this->classes)) {
                    return $this->classes[$current];
                }
                $current = get_parent_class($current);
            } while ($current !== false);
        }

        if (interface_exists($type)) {
            if (array_key_exists($type, $this->interfaces)) {
                return $this->interfaces[$type];
            }
            foreach (class_implements($type) as $interface) {
                if (array_key_exists($interface, $this->interfaces)) {
                    return $this->interfaces[$type];
                }
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    function contains(string $type) {
        if (!TypeHelper::isValidType($type)) {
            return false;
        }

        if (class_exists($type)) {
            $current = $type;
            do {
                if (array_key_exists($current, $this->classes)) {
                    return true;
                }
                $current = get_parent_class($current);
            } while ($current !== false);
        }

        if (interface_exists($type)) {
            if (array_key_exists($type, $this->interfaces)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int
     */
    function size() {
        return sizeof($this->classes);
    }
}