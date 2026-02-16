<?php

namespace JuraSciix\DataMapper;

/**
 * Поддерживаемые стили написания ключей.
 */
enum CaseStyle {
    /**
     * Стиль написания: `camelCase`
     */
    case CAMEL_CASE;

    /**
     * Стиль написания: `PascalCase`
     */
    case PASCAL_CASE;

    /**
     * Стиль написания: `snake_case`
     */
    case SNAKE_CASE;

    /**
     * Стиль написания: `kebab-case`
     */
    case KEBAB_CASE;

    // Код предложен ИИ

    /**
     * @return string
     */
    function format(string $str) {
        return match ($this) {
            CaseStyle::CAMEL_CASE => CaseStyle::toCamelCase($str),
            CaseStyle::PASCAL_CASE => CaseStyle::toPascalCase($str),
            CaseStyle::SNAKE_CASE => CaseStyle::toSnakeCase($str),
            CaseStyle::KEBAB_CASE => CaseStyle::toKebabCase($str),
        };
    }

    /**
     * @return string
     */
    static function toCamelCase(string $input) {
        $words = array_map('strtolower', CaseStyle::tokenize($input));
        if (empty($words)) return '';
        $first = array_shift($words);
        return $first . implode('', array_map('ucfirst', $words));
    }

    /**
     * @return string
     */
    static function toPascalCase(string $input) {
        $words = array_map('strtolower', CaseStyle::tokenize($input));
        return implode('', array_map('ucfirst', $words));
    }

    /**
     * @return string
     */
    static function toSnakeCase(string $input) {
        $words = array_map('strtolower', CaseStyle::tokenize($input));
        return implode('_', $words);
    }

    /**
     * @return string
     */
    static function toKebabCase(string $input) {
        $words = array_map('strtolower', CaseStyle::tokenize($input));
        return implode('-', $words);
    }

    private static function tokenize($input) {
        // 1. Разделяем подчеркивания и дефисы
        $input = str_replace(['-', '_'], ' ', $input);

        // 2. Основное регулярное выражение для аббревиатур:
        // Находим переходы:
        // - между строчной и заглавной (a|A)
        // - между заглавной и заглавной, если за ней строчная (HTML|Parser)
        $spaced = preg_replace('/([a-z\d])([A-Z])|([A-Z]+)([A-Z][a-z])/', '$1$3 $2$4', $input);

        // 3. Убираем лишние пробелы и разбиваем на массив
        return array_filter(explode(' ', trim($spaced)));
    }
}
