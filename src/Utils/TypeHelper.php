<?php

namespace JuraSciix\DataMapper\Utils;

use InvalidArgumentException;

class TypeHelper {

    /**
     * @return string[]
     */
    static function getSuperTypes(string $type) {
        $implements = class_implements($type);
        if ($implements === false) {
            throw new InvalidArgumentException($type);
        }
        $parents = class_parents($type);
        if ($parents === false) {
            throw new InvalidArgumentException($type);
        }
        return array_merge($parents, $implements);
    }
}