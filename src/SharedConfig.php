<?php

namespace JuraSciix\DataMapper;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use JuraSciix\DataMapper\Adapters\DateTime\DateTimeAdapter;
use JuraSciix\DataMapper\Adapters\DateTime\DateTimeImmutableAdapter;
use JuraSciix\DataMapper\Adapters\DeserializeAdapterWrapper;
use JuraSciix\DataMapper\Adapters\DeserializeMatchingAdapterWrapper;
use JuraSciix\DataMapper\Adapters\EmptyAdapter;
use JuraSciix\DataMapper\Adapters\SPL\SplFixedArrayAdapter;
use JuraSciix\DataMapper\Utils\ContravariantMap;
use JuraSciix\DataMapper\Utils\CovariantMap;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use SplFixedArray;

/**
 * @internal
 */
class SharedConfig {

    // Заметка: Адаптеры выполняют сериализацию и десериализацию.
    // Десериализация может быть контравариантной,
    // а сериализация ковариантной.
    // Либо адаптеры инвариантны друг к другу,
    // либо должны храниться в двух структурах, как ниже.

    /**
     * @var ContravariantMap<AdapterInterface<?>>
     */
    public readonly ContravariantMap $deserializers;

    /**
     * @var CovariantMap<AdapterInterface<?>>
     */
    public readonly CovariantMap $serializers;

    /**
     * @var array<string, AdapterInterface<?>>
     */
    public array $builtinAdapters = [];

    public bool $caseSensitive = true;

    public CaseStyle $caseStyle = CaseStyle::SNAKE_CASE;

    public bool $omitUnmatchedKeys = true;

    public bool $allowTypeConverting = false;

    public string $dateTimeFormat = DateTimeInterface::ATOM;

    public ?DateTimeZone $timeZone = null;

    function __construct() {
        $this->deserializers = new ContravariantMap();
        $this->serializers = new CovariantMap();
    }

    function registerBuiltinAdapters(): void {
        $this->builtinAdapters['mixed'] = EmptyAdapter::instance();

        $this->registerBuiltin('int', 'is_int', 'intval');
        $this->registerBuiltin('float', 'is_float', 'floatval');
        $this->registerBuiltin('bool', 'is_bool', 'boolval');
        $this->registerBuiltin('string', 'is_string', 'strval');
        $this->registerBuiltin('null', 'is_null', fn() => null);
    }

    function registerBuiltin(string $type, callable $matcher, callable $converter) {
        $adapter = new DeserializeAdapterWrapper($converter);
        if (!$this->allowTypeConverting) {
            $typeNode = new IdentifierTypeNode($type);
            $adapter = new DeserializeMatchingAdapterWrapper($adapter, $typeNode, $matcher);
        }
        $this->builtinAdapters[$type] = $adapter;
    }

    function registerSplAdapters(Builder $builder): void {
        // Между двумя имплементациями DateTimeInterface,
        // приоритет получит та, которая была последней зарегистрирована.
        $builder->registerAdapter(DateTime::class,
            new DateTimeImmutableAdapter($this->dateTimeFormat, $this->timeZone, $this->allowTypeConverting));
        // Последним регистрируется DateTime.
        $builder->registerAdapter(DateTime::class,
            new DateTimeAdapter($this->dateTimeFormat, $this->timeZone, $this->allowTypeConverting));

        $builder->registerAdapter(SplFixedArray::class, new SplFixedArrayAdapter());
    }
}