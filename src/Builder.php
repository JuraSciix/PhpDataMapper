<?php

namespace JuraSciix\DataMapper;

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
     * Регистрирует собственную фабрику для данного типа.
     *
     * @param FactoryInterface<?> $factory Фабрика.
     * @return self
     */
    function registerFactory(string $type, FactoryInterface $factory) {
        $this->config->factories->put($type, $factory);
        return $this;
    }

    /**
     * Регистрирует собственный адаптер для данного типа.
     *
     * @param AdapterInterface<?> $adapter Адаптер.
     * @return self
     */
    function registerAdapter(string $type, AdapterInterface $adapter) {
        $this->config->adapters->put($type, $adapter);
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
     * @return DataMapper
     */
    function build() {
        $this->config->registerBuiltinAdapters();
        $this->config->registerStdAdapters();
        return $this->mapper;
    }
}