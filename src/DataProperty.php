<?php

namespace JuraSciix\DataMapper;

use Attribute;

/**
 * По умолчанию, за ключ берётся название свойства и форматируется согласно заданному {@link CaseStyle}.
 * Этот атрибут позволяет переопределить ключ. Переопределенный ключ __не будет__ форматироваться.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class DataProperty {

    function __construct(
        readonly string $key
    ) {}
}