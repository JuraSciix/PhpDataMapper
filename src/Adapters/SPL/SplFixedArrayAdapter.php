<?php

namespace JuraSciix\DataMapper\Adapters\SPL;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\Adapters\SingleGenericAdapter;
use JuraSciix\DataMapper\DataMapper;
use JuraSciix\DataMapper\Exception\DeserializeException;
use JuraSciix\DataMapper\Exception\SerializeException;
use JuraSciix\DataMapper\Utils\StringHelper;
use JuraSciix\DataMapper\Utils\TypeHelper;
use SplFixedArray;

/**
 * @template-extends SingleGenericAdapter<SplFixedArray<?>>
 */
final class SplFixedArrayAdapter extends SingleGenericAdapter {

    /**
     * @template TGeneric
     *
     * @param AdapterInterface<TGeneric> $adapter
     * @return SplFixedArray<TGeneric>
     */
    function deserializeWithGeneric(DataMapper $mapper, mixed $data, AdapterInterface $adapter): SplFixedArray {
        if (!TypeHelper::isList($data)) {
            throw new DeserializeException(
                StringHelper::interpolate("Expected an array (list), but received: ??", $data));
        }

        $array = new SplFixedArray(sizeof($data));
        foreach ($data as $i => $item) {
            try {
                $array[$i] = $adapter->deserialize($mapper, $item);
            } catch (DeserializeException $e) {
                $e->unshiftStack("[$i]");
                throw $e;
            }
        }
        return $array;
    }

    /**
     * @template TGeneric
     * @param SplFixedArray<TGeneric> $data
     * @param AdapterInterface<TGeneric> $adapter
     * @return TGeneric[]
     */
    function serializeWithGeneric(DataMapper $mapper, mixed $data, AdapterInterface $adapter): array {
        if (!($data instanceof SplFixedArray)) {
            throw new SerializeException(
                StringHelper::interpolate("Expected an instance of SplFixedArray, but received: ??", $data));
        }

        $array = [];
        foreach ($data as $i => $item) {
            try {
                $array[] = $adapter->serialize($mapper, $item);
            } catch (SerializeException$e) {
                $e->unshiftStack("[$i]");
                throw $e;
            }
        }
        return $array;
    }
}