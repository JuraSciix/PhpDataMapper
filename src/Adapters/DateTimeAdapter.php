<?php

namespace JuraSciix\DataMapper\Adapters;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;
use JuraSciix\DataMapper\Exception\DeserializeException;
use JuraSciix\DataMapper\Exception\SerializeException;
use JuraSciix\DataMapper\Utils\StringHelper;

/**
 * @template-implements AdapterInterface<DateTimeImmutable>
 */
class DateTimeAdapter implements AdapterInterface {

    function __construct(
        readonly string $format,
        readonly ?DateTimeZone $timeZone
    ) {}

    function deserialize(DataMapper $mapper, mixed $data): DateTime {
        if (!is_string($data)) {
            throw new DeserializeException(StringHelper::interpolate("Expected a string, but received: ??", $data));
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