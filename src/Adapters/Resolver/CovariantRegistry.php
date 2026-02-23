<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use InvalidArgumentException;
use JuraSciix\DataMapper\Utils\TypeHelper;

/**
 * @template TValue
 * @template-implements RegistryInterface<TValue
 */
final class CovariantRegistry implements RegistryInterface {
    /** @var array<class-string, TValue> */
    private $classes = [];
    private $interfaces = [];

    /**
     * @inheritDoc
     */
    function insert(mixed $key, mixed $value): void {
        if (!is_string($key) || !TypeHelper::isValidType($key)) {
            throw new InvalidArgumentException("Invalid type: {$key}");
        }

        if (class_exists($key)) {
            $this->classes[$key] = $value;
        }

        if (interface_exists($key)) {
            $this->interfaces[$key] = $value;
        }
    }

    /**
     * @inheritDoc
     */
    function find(mixed $key): mixed {
        if (!is_string($key) || !TypeHelper::isValidType($key)) {
            return null;
        }

        if (class_exists($key)) {
            $current = $key;
            do {
                if (array_key_exists($current, $this->classes)) {
                    return $this->classes[$current];
                }
                $current = get_parent_class($current);
            } while ($current !== false);
        }

        if (interface_exists($key)) {
            if (array_key_exists($key, $this->interfaces)) {
                return $this->interfaces[$key];
            }
            foreach (class_implements($key) as $interface) {
                if (array_key_exists($interface, $this->interfaces)) {
                    return $this->interfaces[$key];
                }
            }
        }

        return null;
    }
}