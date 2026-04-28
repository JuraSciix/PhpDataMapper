<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;
use JuraSciix\DataMapper\Exceptions\DeserializeException;
use JuraSciix\DataMapper\Exceptions\SerializeException;
use JuraSciix\DataMapper\Utils\StringHelper;
use JuraSciix\DataMapper\Utils\TypeHelper;

/**
 * @template-implements AdapterInterface<array<?>>
 *
 * @internal
 */
final class ListArrayAdapter extends SingleGenericAdapter {

    private function validate($data): void {
        if (!TypeHelper::isList($data)) {
            throw new DeserializeException(StringHelper::interpolate("Expected array (list), but received ??", $data));
        }
    }

    function deserializeWithGeneric(DataMapper $mapper, mixed $data, AdapterInterface $adapter): array {
        $this->validate($data);

        $array = [];
        foreach ($data as $i => $value) {
            try {
                $array[] = $adapter->deserialize($mapper, $value);
            } catch (DeserializeException $e) {
                $e->unshiftStack("[$i]");
                throw $e;
            }
        }

        return $array;
    }

    function serializeWithGeneric(DataMapper $mapper, mixed $data, AdapterInterface $adapter): array {
        $this->validate($data);

        $array = [];
        foreach ($data as $i => $value) {
            try {
                $array[] = $adapter->serialize($mapper, $value);
            } catch (SerializeException $e) {
                $e->unshiftStack("[$i]");
                throw $e;
            }
        }

        return $array;
    }
}