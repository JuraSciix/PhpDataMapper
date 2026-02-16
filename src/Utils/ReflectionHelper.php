<?php

namespace JuraSciix\DataMapper\Utils;

use AssertionError;
use ReflectionClass as RClass;
use ReflectionClassConstant as RClassConstant;
use ReflectionException;
use ReflectionFunctionAbstract as RFunctionAbstract;
use ReflectionParameter as RParameter;
use ReflectionProperty as RProperty;

class ReflectionHelper {

    /**
     * @return RClass
     */
    static function getReflectionClassSurely(string $class) {
        try {
            return new RClass($class);
        } catch (ReflectionException $e) {
            // Предполагаем, что это невозможный исход
            throw new AssertionError(previous: $e);
        }
    }

    /**
     * @param RClass|RFunctionAbstract|RProperty|RClassConstant|RParameter $reflection
     * @param class-string<?> $attributeClass
     * @return bool
     */
    static function hasAttribute(mixed $reflection, string $attributeClass) {
        $attributes = $reflection->getAttributes($attributeClass);
        return !empty($attributes);
    }

    /**
     * @template TAttribute
     * @param RClass|RFunctionAbstract|RProperty|RClassConstant|RParameter $reflection
     * @param class-string<TAttribute> $attributeClass
     * @return TAttribute|null
     */
    static function getAttribute(mixed $reflection, string $attributeClass) {
        $attributes = $reflection->getAttributes($attributeClass);
        if (empty($attributes)) {
            return null;
        }
        return $attributes[0]->newInstance();
    }
}