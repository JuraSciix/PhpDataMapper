<?php

namespace JuraSciix\UnitTest\DataMapper;

use DateTime;
use DateTimeImmutable;

class TimestampObject {

    function __construct(
        readonly DateTime $mutable,
        readonly DateTimeImmutable $immutable
    ) {}
}