<?php

namespace JuraSciix\UnitTest\DataMapper;

class Foo {
    static bool $setterCalled = false;
    static bool $getterCalled = false;

    public Bar $bar;

    public function setBar(Bar $bar): void {
        self::$setterCalled = true;
        $this->bar = $bar;
    }

    public function getBar(): Bar {
        self::$getterCalled = true;
        return $this->bar;
    }
}