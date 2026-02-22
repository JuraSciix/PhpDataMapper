<?php

namespace JuraSciix\UnitTest\DataMapper;

class FiniteRecursion {

    /**
     * @param FiniteRecursion|null $recursion
     * @param FiniteRecursion[] $recursionList
     */
    public function __construct(
        public ?FiniteRecursion $recursion = null,
        public array $recursionList = []
    ) {}
}