<?php

namespace JuraSciix\UnitTest\DataMapper;

use JuraSciix\DataMapper\Utils\StringHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

class StringHelperTest extends TestCase {

    #[Test]
    public function ok(): void {
        self::assertSame(
            expected: "Hello 'world'!",
            actual: StringHelper::interpolate("Hello ??!", "world")
        );

        self::assertSame(
            expected: "Hello array!",
            actual: StringHelper::interpolate("Hello ??!", ['foo' => 'bar'])
        );

        self::assertSame(
            expected: "Hello list!",
            actual: StringHelper::interpolate("Hello ??!", [1, 2, 3])
        );

        self::assertSame(
            expected: "Hello value of resource!",
            actual: StringHelper::interpolate("Hello ??!", STDIN)
        );

        self::assertSame(
            expected: "Hello object stdClass!",
            actual: StringHelper::interpolate("Hello ??!", new stdClass())
        );

        self::assertSame(
            expected: "Hello world!",
            actual: StringHelper::interpolate("Hello world!", 1, 2, 3)
        );

        self::assertSame(
            expected: "Hello ??!",
            actual: StringHelper::interpolate("Hello ??!")
        );
    }
}