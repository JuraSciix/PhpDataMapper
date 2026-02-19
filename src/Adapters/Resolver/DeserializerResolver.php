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
class DeserializerResolver extends AdapterResolver {

    protected function tryResolve(string $type): ?AdapterInterface {
        if ($this->config->deserializers->contains($type)) {
            return $this->config->deserializers->get($type);
        }
        return null;
    }

    protected function failure(TypeNode $typeNode): AdapterInterface {
        throw new ResolveException("No suitable deserializer found for '$typeNode' type");
    }
}