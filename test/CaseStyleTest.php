<?php

namespace JuraSciix\UnitTest\DataMapper;

use JuraSciix\DataMapper\CaseStyle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertSame;

class CaseStyleTest extends TestCase {

    static function provideData(): array {
        $inputs = ['FooBar', 'fooBar', 'Foo_bar', 'foo_bar', 'Foo_Bar'];
        $caseStyles = [
            [CaseStyle::SNAKE_CASE, 'foo_bar'],
            [CaseStyle::CAMEL_CASE, 'fooBar'],
            [CaseStyle::PASCAL_CASE, 'FooBar'],
            [CaseStyle::KEBAB_CASE, 'foo-bar'],
        ];
        $values = [];
        foreach ($inputs as $input) {
            foreach ($caseStyles as [$caseStyle, $expected]) {
                $values[] = [$input, $caseStyle, $expected];
            }
        }
        return $values;
    }

    #[Test]
    #[DataProvider('provideData')]
    function ok(string $input, CaseStyle $caseStyle, string $expected): void {
        assertSame($expected, $caseStyle->format($input));
    }
}