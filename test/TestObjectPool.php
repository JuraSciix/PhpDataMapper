<?php

namespace JuraSciix\UnitTest\DataMapper;

class TestObjectPool {

    /**
     * @param TestObject[] $objects
     */
    function __construct(
        readonly array $objects
    ) {}
}