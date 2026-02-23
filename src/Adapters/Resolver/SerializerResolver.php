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
class SerializerResolver extends AdapterResolver {

    function __construct(SharedConfig $config, Reflector $reflector) {
        parent::__construct($config, $reflector, $config->serializers);
    }

    protected function failure(TypeNode $typeNode): AdapterInterface {
        throw new ResolveException("No suitable serializer found for '$typeNode' type");
    }
}