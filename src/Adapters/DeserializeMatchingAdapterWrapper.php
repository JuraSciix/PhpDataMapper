<?php

namespace JuraSciix\DataMapper\Adapters;

use Closure;
use JuraSciix\DataMapper\AdapterInterface;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @template-covariant TValue
 * @template-implements AdapterInterface<TValue>
 */
final class DeserializeMatchingAdapterWrapper extends MatchingAdapter {
    private readonly Closure $matchForDeserialize;

    /**
     * @param AdapterInterface<TValue> $adapter
     * @param callable(TValue):bool $matchForDeserialize
     */
    function __construct(
        AdapterInterface $adapter,
        TypeNode         $typeNode,
        callable         $matchForDeserialize
    ) {
        parent::__construct($adapter, $typeNode);
        $this->matchForDeserialize = $matchForDeserialize(...);
    }

    /**
     * @inheritDoc
     */
    function matchForDeserialize(mixed $data): bool {
        return ($this->matchForDeserialize)($data);
    }

    /**
     * @inheritDoc
     */
    function matchForSerialize(mixed $data): bool {
        return true;
    }
}