<?php

namespace JuraSciix\UnitTest\DataMapper;

class BarList {

    /**
     * @var Bar[]
     */
    private $barList;

    public function getBarList(): array {
        return $this->barList;
    }

    public function setBarList(array $barList): void {
        $this->barList = $barList;
    }
}