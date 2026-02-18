<?php

namespace JuraSciix\DataMapper\Adapters;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\DataMapper;
use JuraSciix\DataMapper\Exception\DeserializeException;
use JuraSciix\DataMapper\Exception\SerializeException;

/**
 * @template-implements AdapterInterface<DateTimeImmutable>
 */
class DateTimeAdapter implements AdapterInterface {

    function __construct(
        readonly string $format,
        readonly ?DateTimeZone $timeZone
    ) {}

    function deserialize(DataMapper $mapper, mixed $data): DateTime {
        $dateTime = DateTime::createFromFormat($this->format, $data, $this->timeZone);
        if ($dateTime === false) {
            throw new DeserializeException("Invalid value of format '$this->format'");
        }
        return $dateTime;
    }

    function serialize(DataMapper $mapper, mixed $data): string {
        if (!($data instanceof DateTime)) {
            throw new SerializeException("Not a DateTime");
        }

        return $data->format($this->format);
    }
}