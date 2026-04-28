<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;
use JuraSciix\DataMapper\Exceptions\DeserializeException;
use JuraSciix\DataMapper\Exceptions\SerializeException;
use JuraSciix\DataMapper\Utils\StringHelper;
use JuraSciix\DataMapper\Utils\TypeHelper;

/**
 * @template-implements AdapterInterface<array<?, ?>>
 *
 * @internal
 */
final class AssocArrayAdapter extends GenericAdapter {

    function getGenericTypeCount(): int {
        return 2;
    }

    private function validate($data): void {
        if (!TypeHelper::isArray($data)) {
            throw new DeserializeException(StringHelper::interpolate("Expected array (non-list), but received ??", $data));
        }
    }

    function deserializeWithGenerics(DataMapper $mapper, mixed $data, array $adapters): array {
        $this->validate($data);

        [$keyAdapter, $valueAdapter] = $adapters;

        $map = [];
        foreach ($data as $key => $value) {
            try {
                $map[$keyAdapter->deserialize($mapper, $key)] = $valueAdapter->deserialize($mapper, $value);
            } catch (DeserializeException $e) {
                $e->unshiftStack("[$key]");
                throw $e;
            }
        }

        return $map;
    }

    function serializeWithGenerics(DataMapper $mapper, mixed $data, array $adapters): array {
        $this->validate($data);

        [$keyAdapter, $valueAdapter] = $adapters;

        $map = [];
        foreach ($data as $key => $value) {
            try {
                $map[$keyAdapter->serialize($mapper, $key)] = $valueAdapter->serialize($mapper, $value);
            } catch (SerializeException $e) {
                $e->unshiftStack("[$key]");
                throw $e;
            }
        }

        return $map;
    }
}