<?php

namespace JuraSciix\DataMapper\Utils;

use InvalidArgumentException;

class TypeHelper {

    /**
     * Проверяет, что значение это списковый массив.
     *
     * @return bool
     */
    static function isList(mixed $value) {
        return is_array($value) && array_is_list($value);
    }

    /**
     * Проверяет, что значение является массивом, но не списковым.
     *
     * @return bool
     */
    static function isArray(mixed $value) {
        // Заметка: пустой массив считается списком, :D
        return is_array($value) && (empty($value) || !array_is_list($value));
    }

    /**
     * Приводит значение к строке, сохраняя информацию об исходном виде данных.
     *
     * Скаляры напрямую преобразуются в строку, объекты - в названия классов, остальное - в названия типов.
     *
     * @return string
     */
    static function export(mixed $value) {
        if (is_scalar($value)) {
            // Заметка: для типа resource, var_export возвращает NULL
            // var_export(null) = var_export(resource)
            return var_export($value, true);
        }

        if (is_array($value)) {
            return array_is_list($value) ? "list" : "array";
        }

        if (is_object($value)) {
            $class = get_class($value);
            return "object $class";
        }

        $type = gettype($value);
        return "value of $type";
    }

    /**
     * @return string[]
     */
    static function getSuperTypes(string $type) {
        if (!TypeHelper::isValidType($type)) {
            throw new InvalidArgumentException("Invalid type: $type");
        }
        return array_merge(class_parents($type), class_implements($type));
    }

    /**
     * Проверяет, является ли тип корректным
     * @return bool
     */
    static function isValidType(string $type) {
        return class_exists($type) || interface_exists($type);
    }
}