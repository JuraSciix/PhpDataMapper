<?php

namespace JuraSciix\UnitTest\DataMapper\Maps;

use JuraSciix\DataMapper\Utils\CovariantMap;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;

class CovariantMapTest extends TestCase {

    #[Test]
    public function classes1(): void {
        $map = new CovariantMap();
        $map->put(Base::class, function (Base $base): string {
            return 'base!';
        });

        $value = $map->get(Der1::class)(new Der1());
        assertSame('base!', $value);

        $value = $map->get(Der2::class)(new Der2());
        assertSame('base!', $value);
    }

    #[Test]
    public function classes2(): void {
        $map = new CovariantMap();
        $map->put(Der1::class, function (Der1 $base): string {
            return 'der1';
        });

        $value = $map->get(Der1::class)(new Der1());
        assertSame('der1', $value);
        assertNull($map->get(Der2::class));
    }

    #[Test]
    public function interfaces1(): void {
        $map = new CovariantMap();
        $map->put(Zero::class, function (Zero $zero): string {
            return 'zero';
        });

        $value = $map->get(Zero::class)(new Base());
        assertSame('zero', $value);
        assertNull($map->get(IBase::class));
    }
}