<?php

namespace JuraSciix\UnitTest\DataMapper;

class MixedContainer {

    /**
     * @param mixed $value
     */
    function __construct(
        public readonly mixed $value
    ) {}
}