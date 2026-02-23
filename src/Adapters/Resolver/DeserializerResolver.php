<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\Exception\ResolveException;
use JuraSciix\DataMapper\SharedConfig;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * Ищет адаптер для десериализации.
 *
 * __Важно__: данный адаптер НЕЛЬЗЯ использовать для сериализации.
 */
class DeserializerResolver extends AdapterResolver {

    public function __construct(SharedConfig $config, Reflector $reflector) {
        parent::__construct($config, $reflector, $config->deserializers);
    }

    protected function failure(TypeNode $typeNode): AdapterInterface {
        throw new ResolveException("No suitable deserializer found for '$typeNode' type");
    }
}