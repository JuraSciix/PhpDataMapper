<?php

namespace JuraSciix\UnitTest\DataMapper;

use JuraSciix\DataMapper\DataMapper;
use JuraSciix\DataMapper\Exception\DataMapperException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DifferentTest extends TestCase {

    #[Test]
    function caseInsensitive(): void {
        $mapper = DataMapper::builder()
            ->caseSensitive(false)
            ->allowTypeConverting(false)
            ->omitUnmatchedKeys(false)
            ->build();

        $perfectFoo = new Foo();
        $perfectFoo->bar = new Bar(12345);

        $data = [
            'Bar' => [
                'FooBar' => 12345
            ]
        ];
        $foo = $mapper->deserialize($data, Foo::class);

        self::assertEquals($perfectFoo, $foo);
    }

    #[Test]
    function typeConverting(): void {
        $mapper = DataMapper::builder()
            ->caseSensitive(true)
            ->allowTypeConverting(true)
            ->omitUnmatchedKeys(false)
            ->build();

        $perfectFoo = new Foo();
        $perfectFoo->bar = new Bar(12345);

        $data = [
            'bar' => [
                'foobar' => '12345'
            ]
        ];
        $foo = $mapper->deserialize($data, Foo::class);

        self::assertEquals($perfectFoo, $foo);
    }

    #[Test]
    function unmatchedKeys(): void {
        $mapper = DataMapper::builder()
            ->caseSensitive(true)
            ->allowTypeConverting(false)
            ->omitUnmatchedKeys(false)
            ->build();

        $class = Foo::class;
        self::expectExceptionObject(new DataMapperException(
            "Cannot deserialize $.bar for $class: Unmatched keys found: excess"));

        $data = [
            'bar' => [
                'foobar' => null,
                'excess' => true
            ]
        ];
        $mapper->deserialize($data, Foo::class);
    }
}