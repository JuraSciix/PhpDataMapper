<?php

namespace JuraSciix\UnitTest\DataMapper;

use JuraSciix\DataMapper\DataProperty;

class Bar {

    public function __construct(
        #[DataProperty('foobar')]
        public readonly ?int $x
    ) {}
}