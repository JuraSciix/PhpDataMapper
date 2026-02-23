<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use InvalidArgumentException;
use JuraSciix\DataMapper\Utils\TypeHelper;

/**
 * @template TValue
 * @template-implements RegistryInterface<TValue
 */
final class ContravariantRegistry implements RegistryInterface {
    /** @var array<string, TValue> */
    private $map = [];

    /**
     * @inheritDoc
     */
    function insert(mixed $key, mixed $value): void {
        if (!is_string($key) || !TypeHelper::isValidType($key)) {
            throw new InvalidArgumentException("Invalid type: {$key}");
        }

        $this->map[$key] = $value;
        foreach (TypeHelper::getSuperTypes($key) as $superType) {
            $this->map[$superType] = $value;
        }
    }

    /**
     * @inheritDoc
     */
    function find(mixed $key): mixed {
        if (!is_string($key) || !TypeHelper::isValidType($key)) {
            return null;
        }
        return $this->map[$key] ?? null;
    }
}