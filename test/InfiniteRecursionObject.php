<?php

namespace JuraSciix\UnitTest\DataMapper;

class InfiniteRecursionObject {

    public function __construct(
        readonly InfiniteRecursionObject $child
    ) {}
}