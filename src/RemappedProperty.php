<?php

namespace JuraSciix\DataMapper;

use Attribute;

/**
 * Нужен, чтобы указывать имена ключей, которые отличаются от того, что объявлено в коде.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class RemappedProperty {

    function __construct(
        readonly string $key
    ) {}
}