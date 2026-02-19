<?php

namespace JuraSciix\UnitTest\DataMapper;

use JuraSciix\DataMapper\DataMapper;
use JuraSciix\DataMapper\Exception\DataMapperException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SplFixedArray;

class BasicTest extends TestCase {
    private DataMapper $mapper;

    public function __construct(string $name) {
        parent::__construct($name);

        $this->mapper = DataMapper::builder()
            ->omitUnmatchedKeys(false)
            ->caseSensitive(true)
            ->allowTypeConverting(false)
            ->build();
    }

    protected function setUp(): void {
        Foo::$setterCalled = false;
        Foo::$getterCalled = false;
    }

    #[Test]
    function deserializeOk(): void {
        $perfect = new Foo();
        $perfect->bar = new Bar(null);

        $data = [
            'bar' => [
                'foobar' => null
            ]
        ];
        $foo = $this->mapper->deserialize($data, Foo::class);

        self::assertEquals($perfect, $foo);
        self::assertTrue(Foo::$setterCalled);
        self::assertFalse(Foo::$getterCalled);
    }

    #[Test]
    function serializeOk(): void {
        $perfect = new Foo();
        $perfect->bar = new Bar(null);

        $data = [
            'bar' => [
                'foobar' => null
            ]
        ];

        $array = $this->mapper->serialize($perfect);

        self::assertEquals($data, $array);
        self::assertFalse(Foo::$setterCalled);
        self::assertTrue(Foo::$getterCalled);
    }

    #[Test]
    function deserializeFail(): void {
        $class = Foo::class;
        self::expectExceptionObject(new DataMapperException(
            "Cannot deserialize $.bar for $class: No required 'foobar' found"));

        $data = [
            'bar' => []
        ];
        $this->mapper->deserialize($data, Foo::class);
    }

    #[Test]
    function popoOk(): void {
        $perfect = new Popo();
        $perfect->d = 0.3485;
        $perfect->i = 4432;
        $perfect->z = false;

        $data = [
            'd' => 0.3485,
            'i' => 4432,
            'z' => false
        ];
        $popo = $this->mapper->deserialize($data, Popo::class);

        self::assertEquals($perfect, $popo);
    }

    #[Test]
    function badGuy(): void {
        $class = BadGuy::class;
        self::expectExceptionObject(new DataMapperException(
            "Unable to resolve $class: No suitable adapter found for 'object' type"));

        $data = [
            'wtf' => 1
        ];
        $this->mapper->deserialize($data, BadGuy::class);
    }

    #[Test]
    function recursion(): void {
        $class1 = RecursionBugs::class;
        $class2 = RecursionDaffy::class;
        self::expectExceptionObject(new DataMapperException(
            "Recursion detected: $class1 refers to $class2, which refers back to it"));
        $data = [];
        $this->mapper->deserialize($data, RecursionBugs::class);
    }

    #[Test]
    function deserializeArrayOk(): void {
        $perfect = new BarList();
        $perfect->setBarList([
            new Bar(1),
            new Bar(2),
            new Bar(null),
            new Bar(4),
        ]);

        $data = [
            'bar_list' => [
                ['foobar' => 1],
                ['foobar' => 2],
                ['foobar' => null],
                ['foobar' => 4],
            ]
        ];
        $barList = $this->mapper->deserialize($data, BarList::class);

        self::assertEquals($perfect, $barList);
    }

    #[Test]
    function deserializeMixedOk(): void {
        $perfect = new MixedContainer();
        $perfect->anything = ['foo' => 'bar'];

        $data = [
            'anything' => [
                'foo' => 'bar'
            ]
        ];
        $object = $this->mapper->deserialize($data, MixedContainer::class);

        self::assertEquals($perfect, $object);
    }

    #[Test]
    function deserializeSplFixedArrayOk(): void {
        $barList = new SplFixedArray(1);
        $barList[0] = new Bar(123);
        $perfect = new BarList2();
        $perfect->setBarList($barList);

        $data = [
            'bar_list' => [
                ['foobar' => 123]
            ]
        ];
        $object = $this->mapper->deserialize($data, BarList2::class);

        self::assertEquals($perfect, $object);
    }

    #[Test]
    function deserializeSplFixedArrayFail1(): void {
        $class = BarList2::class;
        self::expectExceptionObject(new DataMapperException(
            "Cannot deserialize $.bar_list.[0] for $class: No required 'foobar' found"));
        $data = [
            'bar_list' => [
                [
                    'no foobar:D' => null
                ]
            ]
        ];
        $this->mapper->deserialize($data, BarList2::class);
    }

    #[Test]
    function deserializeSplFixedArrayFail(): void {
        self::expectExceptionObject(new DataMapperException(
                "Unable to resolve SplFixedArray: SplFixedArray requires specifying a generic type"));

        $data = [];
        $this->mapper->deserialize($data, SplFixedArray::class);
    }
}