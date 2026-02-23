<?php

namespace JuraSciix\DataMapper;

use DateTimeInterface;
use DateTimeZone;
use JuraSciix\DataMapper\Adapters\DeserializeAdapterWrapper;
use JuraSciix\DataMapper\Adapters\DeserializeMatchingAdapterWrapper;
use JuraSciix\DataMapper\Adapters\EmptyAdapter;
use JuraSciix\DataMapper\Adapters\Resolver\InvariantRegistry;
use JuraSciix\DataMapper\Adapters\Resolver\RegistryInterface;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

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
     * @var RegistryInterface<AdapterInterface<?>>
     */
    public readonly RegistryInterface $adapters;

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
        $this->adapters = new InvariantRegistry();
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
}