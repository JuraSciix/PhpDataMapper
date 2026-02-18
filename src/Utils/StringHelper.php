<?php

namespace JuraSciix\DataMapper\Utils;

class StringHelper {

    /**
     * @return string
     */
    static function interpolate(string $str, mixed ...$args) {
        $offset = 0;
        $result = "";
        foreach ($args as $key => $value) {
            $i = strpos($str, "??", $offset);
            if ($i === false) {
                // Больше мест для интерполяции не осталось
                break;
            }

            $result .= substr($str, $offset, $i - $offset);
            $result .= TypeHelper::export($value);
            $offset = $i + 2; // + Длина "??"
        }

        $result .= substr($str, $offset);

        return $result;
    }
}