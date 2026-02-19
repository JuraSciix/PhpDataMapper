<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\Exception\ResolveException;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * Ищет адаптер для десериализации.
 *
 * __Важно__: данный адаптер НЕЛЬЗЯ использовать для сериализации.
 */
class SerializerResolver extends AdapterResolver {

    protected function tryResolve(string $type): ?AdapterInterface {
        if ($this->config->serializers->contains($type)) {
            return $this->config->serializers->get($type);
        }
        return null;
    }

    protected function failure(TypeNode $typeNode): AdapterInterface {
        throw new ResolveException("No suitable serializer found for '$typeNode' type");
    }
}