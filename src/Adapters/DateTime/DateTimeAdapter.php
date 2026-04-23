<?php

namespace JuraSciix\DataMapper\Adapters\DateTime;

use DateTime;
use DateTimeZone;
use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;
use JuraSciix\DataMapper\Exception\DeserializeException;
use JuraSciix\DataMapper\Exception\SerializeException;
use JuraSciix\DataMapper\Utils\StringHelper;

/**
 * Адаптер, который приводит строку к значению типа {@link DateTime}.
 *
 * @template-implements AdapterInterface<DateTime>
 *
 * @internal
 */
final class DateTimeAdapter implements AdapterInterface {

    function __construct(
        readonly string $format,
        readonly ?DateTimeZone $timeZone,
        readonly bool $allowIntegers
    ) {}

    function deserialize(DataMapper $mapper, mixed $data): DateTime {
        if (is_int($data) && $this->allowIntegers) {
            $data = strval($data);
        }
        if (!is_string($data)) {
            throw new DeserializeException(
                StringHelper::interpolate("Expected a string, but received: ??", $data));
        }
        $dateTime = DateTime::createFromFormat($this->format, $data, $this->timeZone);
        if ($dateTime === false) {
            throw new DeserializeException(
                StringHelper::interpolate("Value ?? does not match to format ??", $data, $this->format));
        }
        return $dateTime;
    }

    function serialize(DataMapper $mapper, mixed $data): string {
        if (!($data instanceof DateTime)) {
            throw new SerializeException(
                StringHelper::interpolate("Expected an instance of DateTime, but received: ??", $data));
        }

        return $data->format($this->format);
    }
}