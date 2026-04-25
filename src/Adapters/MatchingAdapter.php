<?php

namespace JuraSciix\DataMapper\Adapters;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;
use JuraSciix\DataMapper\Exceptions\DeserializeException;
use JuraSciix\DataMapper\Exceptions\SerializeException;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @template TValue
 * @template-implements AdapterInterface<TValue>
 */
abstract class MatchingAdapter implements AdapterInterface {

    /**
     * @param AdapterInterface<TValue> $adapter
     */
    function __construct(
        readonly AdapterInterface $adapter,
        readonly TypeNode $typeNode
    ) {}

    /**
     * @inheritDoc
     */
    function deserialize(DataMapper $mapper, mixed $data): mixed {
        if ($this->matchForDeserialize($data)) {
            return $this->adapter->deserialize($mapper, $data);
        }

        throw new DeserializeException("Value '$data' does not match $this->typeNode");
    }

    /**
     * @inheritDoc
     */
    function serialize(DataMapper $mapper, mixed $data): mixed {
        if ($this->matchForSerialize($data)) {
            return $this->adapter->serialize($mapper, $data);
        }

        throw new SerializeException("Value '$data' does not match $this->typeNode");
    }

    /**
     *
     */
    abstract function matchForDeserialize(mixed $data): bool;

    /**
     * @param TValue $data
     */
    abstract function matchForSerialize(mixed $data): bool;
}