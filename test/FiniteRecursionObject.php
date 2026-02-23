<?php

namespace JuraSciix\UnitTest\DataMapper;

class FiniteRecursionObject {

    /**
     * @param FiniteRecursionObject|null $child
     * @param FiniteRecursionObject[] $children
     */
    function __construct(
        readonly ?FiniteRecursionObject $child = null,
        readonly array $children = []
    ) {}
}