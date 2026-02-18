<?php

namespace JuraSciix\DataMapper\Adapters;

use Exception;
use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\Adapters\Model\FactoryInterface;
use JuraSciix\DataMapper\Adapters\Model\Property;
use JuraSciix\DataMapper\DataMapper;
use JuraSciix\DataMapper\Exception\DeserializeException;
use JuraSciix\DataMapper\Exception\SerializeException;
use JuraSciix\DataMapper\Utils\StringHelper;
use JuraSciix\DataMapper\Utils\TypeHelper;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @template T
 */
final class ModelAdapter implements AdapterInterface {

    /** @var array<string, Property<?>> */
    private readonly array $key2prop;

    /**
     * @param Property<?>[] $properties
     * @param FactoryInterface<T> $factory
     */
    function __construct(
        readonly TypeNode         $typeNode,
        readonly array            $properties,
        readonly FactoryInterface $factory,
        readonly bool             $raiseUnmatchedKeys,
        readonly bool             $caseInsensitive,
    ) {
        $key2prop = [];
        foreach ($properties as $property) {
            $key2prop[$property->key] = $property;
        }
        $this->key2prop = $caseInsensitive ? array_change_key_case($key2prop) : $key2prop;
    }

    /**
     * @inheritDoc
     */
    function deserialize(DataMapper $mapper, mixed $data): mixed {
        // Заметка: пустой массив считается списком, :D
        if (!TypeHelper::isArray($data)) {
            throw new DeserializeException(
                StringHelper::interpolate("Expected array (not list), but received ??", $data));
        }

        // План:
        // 1. Проверить наличие всех обязательных свойств.
        // 2. Проверить лишние ключи.

        $path = [];

        if ($this->caseInsensitive) {
            // Более медленный путь
            $unmatchedKeys = [];
            foreach ($data as $key => $value) {
                $unexists = false;
                $key2 = $key;
                if (!array_key_exists($key2, $this->key2prop)) { // Hot att
                    $key2 = strtolower($key2);
                    if (!array_key_exists($key2, $this->key2prop)) { // Slow att
                        $unexists = true;
                    }
                }
                if ($unexists) {
                    if ($this->raiseUnmatchedKeys) {
                        $unmatchedKeys[] = $key;
                    }
                    continue;
                }

                $property = $this->key2prop[$key2];
                $path[] = [$property, $key, $value];
            }
            if (!empty($unmatchedKeys) && $this->raiseUnmatchedKeys) {
                $unmatchedKeyList = implode(', ', $unmatchedKeys);
                throw new DeserializeException("Unmatched keys found: $unmatchedKeyList");
            }
        } else {
            // Более быстрый путь
            $countdown = count($data);
            foreach ($this->properties as $property) {
                $key = $property->key;
                if (!array_key_exists($key, $data)) {
                    if (!$property->required) {
                        continue;
                    }

                    throw new DeserializeException("No required '$key' found");
                }
                $value = $data[$key];
                $path[] = [$property, $key, $value];
                $countdown--;
            }
            if ($countdown > 0 && $this->raiseUnmatchedKeys) {
                // Ищем не сопоставленные ключи
                foreach ($this->properties as $property) {
                    unset($data[$property->key]);
                }
                $unmatchedKeyList = implode(', ', array_keys($data));
                throw new DeserializeException("Unmatched keys found: $unmatchedKeyList");
            }
        }

        /** @var array<string, mixed> $promoted */
        $promoted = [];
        /** @var array{Property<?>, mixed} $deserialized */
        $deserialized = [];

        // После начинаем десериализацию
        /** @var Property $property */
        foreach ($path as [$property, $key, $value]) {
            try {
                $readyValue = $property->adapter->deserialize($mapper, $value);
            } catch (DeserializeException $e) {
                $e->unshiftStack($key);
                throw $e;
            }
            if ($property->promoted) {
                $promoted[$property->name] = $readyValue;
            } else {
                $deserialized[] = [$property, $readyValue];
            }
        }

        try {
            $instance = $this->factory->create($promoted);
        } catch (Exception $e) {
            throw new DeserializeException("Unable to create $this->typeNode", previous: $e);
        }

        /** @var Property $property */
        foreach ($deserialized as [$property, $readyValue]) {
            try {
                $property->setter->set($instance, $readyValue);
            } catch (Exception $e) {
                throw new DeserializeException("Cannot deserialize $this->typeNode", previous: $e);
            }
        }

        return $instance;
    }

    /**
     * @inheritDoc
     */
    function serialize(DataMapper $mapper, mixed $data): array {
        $array = [];
        foreach ($this->properties as $property) {
            try {
                $value = $property->getter->get($data);
            } catch (Exception $e) {
                throw new SerializeException("Unable to serialize $this->typeNode", previous: $e);
            }

            try {
                $array[$property->key] = $property->adapter->serialize($mapper, $value);
            } catch (SerializeException $e) {
                $e->unshiftStack($property->name);
                throw $e;
            }
        }
        return $array;
    }
}