<?php

namespace JuraSciix\UnitTest\DataMapper;

class AssocArrayContainer {

    /**
     * @param array<string, int> $array
     */
    function __construct(
        readonly array $array
    ) {}
}