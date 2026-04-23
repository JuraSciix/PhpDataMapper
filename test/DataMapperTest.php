<?php

namespace JuraSciix\UnitTest\DataMapper;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JuraSciix\DataMapper\CaseStyle;
use JuraSciix\DataMapper\DataMapper;
use JuraSciix\DataMapper\Exception\DataMapperException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DataMapperTest extends TestCase {

    static function provideCaseStyle() {
        return array_map(
            callback: fn (CaseStyle $cs) => [$cs, $cs->format('theValue')],
            array: CaseStyle::cases()
        );
    }

    #[Test]
    #[DataProvider('provideCaseStyle')]
    function failRequiredKey(CaseStyle $caseStyle, string $key): void {
        $mapper = DataMapper::builder()
            ->caseSensitive(true)
            ->caseStyle($caseStyle)
            ->build();

        $class = TestObject::class;
        $this->expectExceptionObject(new DataMapperException(
            "Cannot deserialize $. for $class: No required '$key' found"));

        $data = [];
        $mapper->deserialize($data, TestObject::class);
    }

    #[Test]
    function failUnmatchedKeys(): void {
        $mapper = DataMapper::builder()
            ->omitUnmatchedKeys(false)
            ->build();

        $class = TestObject::class;
        $this->expectExceptionObject(new DataMapperException(
            "Cannot deserialize $. for $class: Unmatched keys found: excess_value"));

        $data = [
            'the_value' => 'bar',
            'excess_value' => 1,
        ];
        $mapper->deserialize($data, TestObject::class);
    }

    #[Test]
    function successCaseInsensitive(): void {
        $expected = new TestObject('hello');

        $mapper = DataMapper::builder()
            ->caseSensitive(false)
            ->build();

        $data = [
            'THE_VALUE' => 'hello'
        ];
        $object = $mapper->deserialize($data, TestObject::class);

        $this->assertEquals($expected, $object);
    }

    static function provideDataArray(): array {
        $values = [];
        foreach (CaseStyle::cases() as $caseStyle) {
            $data = [
                $caseStyle->format('objects') => [
                    [$caseStyle->format('the_value') => 'hello'],
                    [$caseStyle->format('the_value') => 'bye'],
                ]
            ];
            $object = new TestObjectPool([
                new TestObject('hello'),
                new TestObject('bye')
            ]);

            $values[] = [$caseStyle, $data, $object];
        }

        return $values;
    }

    #[Test]
    #[DataProvider('provideDataArray')]
    function successDeserializeArray(CaseStyle $caseStyle, mixed $data, TestObjectPool $object): void {
        $mapper = DataMapper::builder()
            ->caseStyle($caseStyle)
            ->build();

        $deserialized = $mapper->deserialize($data, TestObjectPool::class);

        $this->assertEquals($object, $deserialized);
    }

    #[Test]
    #[DataProvider('provideDataArray')]
    function successSerializeArray(CaseStyle $caseStyle, mixed $data, TestObjectPool $object): void {
        $mapper = DataMapper::builder()
            ->caseStyle($caseStyle)
            ->build();

        $serialized = $mapper->serialize($object);

        $this->assertEquals($data, $serialized);
    }

    static function provideDateTime() {
        $format = DateTimeInterface::ATOM;
        $timeZone = new DateTimeZone("Europe/Moscow");

        $datetime = "2026-02-23T07:50:38+00:00";

        $object = new TimestampObject(
            mutable: DateTime::createFromFormat($format, $datetime, $timeZone),
            immutable: DateTimeImmutable::createFromFormat($format, $datetime, $timeZone),
        );
        $data = [
            'mutable' => $datetime,
            'immutable' => $datetime
        ];

        return [
            [$format, $timeZone, $data, $object]
        ];
    }

    #[Test]
    #[DataProvider('provideDateTime')]
    function successDeserializeDateTime(string $dateTimeFormat, DateTimeZone $timeZone, mixed $data, TimestampObject $object): void {
        $mapper = DataMapper::builder()
            ->dateTimeFormat($dateTimeFormat)
            ->timeZone($timeZone)
            ->build();

        $deserialized = $mapper->deserialize($data, TimestampObject::class);

        $this->assertEquals($object, $deserialized);
    }

    #[Test]
    #[DataProvider('provideDateTime')]
    function successSerializeDateTime(string $dateTimeFormat, DateTimeZone $timeZone, mixed $data, TimestampObject $object): void {
        $mapper = DataMapper::builder()
            ->dateTimeFormat($dateTimeFormat)
            ->timeZone($timeZone)
            ->build();

        $serialized = $mapper->serialize($object);

        $this->assertEquals($data, $serialized);
    }

    static function provideFiniteRecursionData() {
        // Добавляем пустые ключи ради строгого соответствия после сериализации
        $data = [
            'child' => null,
            'children' => [
                [
                    'child' => [
                        'child' => null,
                        'children' => []
                    ],
                    'children' => []
                ]
            ]
        ];

        $object = new FiniteRecursionObject(
            children: [
                new FiniteRecursionObject(
                    child: new FiniteRecursionObject()
                )
            ]
        );

        return [
            [$data, $object]
        ];
    }

    #[Test]
    #[DataProvider('provideFiniteRecursionData')]
    function successDeserializeFiniteRecursion(mixed $data, FiniteRecursionObject $object): void {
        $mapper = new DataMapper();

        $deserialized = $mapper->deserialize($data, FiniteRecursionObject::class);

        $this->assertEquals($object, $deserialized);
    }

    #[Test]
    #[DataProvider('provideFiniteRecursionData')]
    function successSerializeFiniteRecursion(mixed $data, FiniteRecursionObject $object): void {
        $mapper = new DataMapper();

        $serialized = $mapper->serialize($object);

        $this->assertEquals($data, $serialized);
    }

    #[Test]
    #[DataProvider('provideFiniteRecursionData')]
    function failDeserializeInfiniteRecursion(): void {
        $mapper = new DataMapper();

        $class = InfiniteRecursionObject::class;
        $this->expectExceptionObject(new DataMapperException(
            "Unable to resolve $class: Recursion detected: $class refers to $class, which refers back to it"));

        $mapper->deserialize([], InfiniteRecursionObject::class);
    }
}