<?php

namespace JuraSciix\UnitTest\DataMapper;

use SplFixedArray;

class BarList2 {

    /**
     * @var SplFixedArray<Bar>
     */
    private SplFixedArray $barList;

    /**
     * @return SplFixedArray<Bar>
     */
    public function getBarList(): SplFixedArray {
        return $this->barList;
    }

    /**
     * @param SplFixedArray<Bar> $barList
     */
    public function setBarList(SplFixedArray $barList): void {
        $this->barList = $barList;
    }
}