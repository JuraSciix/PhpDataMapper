<?php

namespace JuraSciix\UnitTest\DataMapper;

use JuraSciix\DataMapper\CaseStyle;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CaseStyleTest extends TestCase {

    #[Test]
    function test(): void {
        foreach (["FooBar", "Foo_Bar", "foo_bar", "fooBar"] as $variant) {
            self::assertSame("fooBar", CaseStyle::toCamelCase($variant));
            self::assertSame("FooBar", CaseStyle::toPascalCase($variant));
            self::assertSame("foo_bar", CaseStyle::toSnakeCase($variant));
            self::assertSame("foo-bar", CaseStyle::toKebabCase($variant));
        }
    }
}