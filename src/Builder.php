<?php

namespace JuraSciix\DataMapper;

use DateTimeZone;
use JuraSciix\DataMapper\Exception\DataMapperException;

final class Builder {

    /**
     * @internal
     */
    function __construct(
        private readonly DataMapper   $mapper,
        private readonly SharedConfig $config,
    ) {}

    /**
     * Регистрирует собственный адаптер для данного типа.
     *
     * @param AdapterInterface<?> $adapter Адаптер.
     * @return self
     */
    function registerAdapter(string $type, AdapterInterface $adapter) {
        $this->config->deserializers->put($type, $adapter);
        $this->config->serializers->put($type, $adapter);
        return $this;
    }

    /**
     * Чувствительность к регистру ключей.
     *
     * @return self
     */
    function caseSensitive(bool $caseSensitive) {
        $this->config->caseSensitive = $caseSensitive;
        return $this;
    }

    /**
     * Стиль написания ключей. Применяется к названиям свойств.
     *
     * @return self
     */
    function caseStyle(CaseStyle $caseStyle) {
        $this->config->caseStyle = $caseStyle;
        return $this;
    }

    /**
     * Режим, в котором неизвестные ключи будут игнорироваться.
     * В ином случае, будет выбрасываться исключение {@link DataMapperException}.
     *
     * @return self
     */
    function omitUnmatchedKeys(bool $omitUnmatchedKeys) {
        $this->config->omitUnmatchedKeys = $omitUnmatchedKeys;
        return $this;
    }

    /**
     * Позволяет приводить типы. Например, `int` к `string` и наоборот.
     *
     * @return self
     */
    function allowTypeConverting(bool $allowTypeConverting) {
        $this->config->allowTypeConverting = $allowTypeConverting;
        return $this;
    }

    /**
     * Устанавливает формат даты для всех экземпляров {@link \DateTimeInterface}.
     *
     * __Важно__: даты работают только со строковыми значениями!
     *
     * @return self
     */
    function dateTimeFormat(string $dateTimeFormat) {
        $this->config->dateTimeFormat = $dateTimeFormat;
        return $this;
    }

    /**
     * Устанавливает часовой пяс для всех экземпляров {@link \DateTimeInterface}.
     *
     * __Важно__: даты работают только со строковыми значениями!
     *
     * @return self
     */
    function timeZone(DateTimeZone $timeZone) {
        $this->config->timeZone = $timeZone;
        return $this;
    }

    /**
     * @return DataMapper
     */
    function build() {
        $this->config->registerBuiltinAdapters();
        $this->config->registerSplAdapters($this);
        return $this->mapper;
    }
}