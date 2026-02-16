<?php

namespace JuraSciix\DataMapper;

use JuraSciix\DataMapper\Adapters\DeserializeAdapterWrapper;
use JuraSciix\DataMapper\Adapters\DeserializeMatchingAdapterWrapper;
use JuraSciix\DataMapper\Adapters\StdClassAdapter;
use JuraSciix\DataMapper\Utils\ContravariantMap;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

/**
 * @internal
 */
class SharedConfig {

    /**
     * @var ContravariantMap<FactoryInterface<?>>
     */
    public readonly ContravariantMap $factories;

    /**
     * @var ContravariantMap<AdapterInterface<?>>
     */
    public readonly ContravariantMap $adapters;

    /**
     * @var array<string, AdapterInterface<?>>
     */
    public array $builtinAdapters = [];

    public bool $caseSensitive = true;

    public CaseStyle $caseStyle = CaseStyle::SNAKE_CASE;

    public bool $omitUnmatchedKeys = true;

    public bool $allowTypeConverting = false;

    function __construct() {
        $this->factories = new ContravariantMap();
        $this->adapters = new ContravariantMap();
    }

    function registerBuiltinAdapters(): void {
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

    function registerStdAdapters(): void {

    }
}