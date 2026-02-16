<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;
use JuraSciix\DataMapper\Exception\DeserializeException;
use JuraSciix\DataMapper\Exception\SerializeException;

/**
 * @template TComponent
 * @template-implements AdapterInterface<TComponent[]>
 */
final class ArrayAdapter implements AdapterInterface {

    /**
     * @param AdapterInterface<TComponent> $componentAdapter
     */
    function __construct(
        readonly AdapterInterface $componentAdapter
    ) {}

    private function validate($data): void {
        if (!is_array($data) || !array_is_list($data)) {
            throw new DeserializeException("Expected array (list)");
        }
    }

    /**
     * @inheritDoc
     */
    function deserialize(DataMapper $mapper, mixed $data): array {
        $this->validate($data);

        $array = [];
        foreach ($data as $i => $value) {
            try {
                $array[] = $this->componentAdapter->deserialize($mapper, $value);
            } catch (DeserializeException $e) {
                $e->unshiftStack("[$i]");
                throw $e;
            }
        }

        return $array;
    }

    /**
     * @inheritDoc
     */
    function serialize(DataMapper $mapper, mixed $data): array {
        $this->validate($data);

        $array = [];
        foreach ($data as $i => $value) {
            try {
                $array[] = $this->componentAdapter->serialize($mapper, $value);
            } catch (SerializeException $e) {
                $e->unshiftStack("[$i]");
                throw $e;
            }
        }

        return $array;
    }
}