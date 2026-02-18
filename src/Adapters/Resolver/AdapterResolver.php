<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\Adapters\ArrayAdapter;
use JuraSciix\DataMapper\Adapters\Model\Property;
use JuraSciix\DataMapper\Adapters\ModelAdapter;
use JuraSciix\DataMapper\Adapters\NullAdapter;
use JuraSciix\DataMapper\DataProperty;
use JuraSciix\DataMapper\Exception\ResolveException;
use JuraSciix\DataMapper\SharedConfig;
use JuraSciix\DataMapper\Utils\DocTypeHelper;
use JuraSciix\DataMapper\Utils\ReflectionHelper;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ReflectionClass;

class AdapterResolver {

    /**
     * @var array<string, AdapterInterface<?>>
     */
    private $cache = [];

    /** @var class-string[] Типы, которые прямо сейчас строятся. Это защита от рекурсии. */
    private $processing = [];

    private readonly Reflector $reflector;

    function __construct(
        private readonly SharedConfig $config
    ) {
        $this->reflector = new Reflector();
    }

    /**
     * @template TValue
     * @param TypeNode $typeNode
     * @return AdapterInterface<TValue>
     */
    function resolve(TypeNode $typeNode): AdapterInterface {
        $tn = DocTypeHelper::canonize($typeNode);

        // Бездумно кешируем. Заметка: Это вредно, кстати.
        $typeString = strval($tn);

        if (in_array($typeString, $this->processing, true)) {
            $last = $this->processing[sizeof($this->processing) - 1];
            throw new ResolveException(
                "Recursion detected: $typeString refers to $last, which refers back to it");
        }

        if (array_key_exists($typeString, $this->cache)) {
            return $this->cache[$typeString];
        }

        $this->processing[] = $typeString;

        try {
            $adapter = $this->doResolve($tn);
            $this->cache[$typeString] = $adapter;
            return $adapter;
        } finally {
            // Последний элемент должен быть $typeString.
            array_pop($this->processing);
        }
    }

    /**
     * @param TypeNode $typeNode
     * @return AdapterInterface<?>
     */
    private function doResolve($typeNode) {
        if ($typeNode instanceof IdentifierTypeNode) {
            $typeName = $typeNode->name;
            if (array_key_exists($typeName, $this->config->builtinAdapters)) {
                return $this->config->builtinAdapters[$typeName];
            }
            if ($this->config->adapters->contains($typeName)) {
                return $this->config->adapters->get($typeName);
            }
            if (class_exists($typeName)) {
                $class = ReflectionHelper::getReflectionClassSurely($typeName);
                if ($class->isInstantiable()) {
                    AdapterResolver::validateClass($class);
                    return $this->resolveClass($typeNode, $class);
                }
            }
        }

        if ($typeNode instanceof NullableTypeNode) {
            $adapter = $this->resolve($typeNode->type);
            return new NullAdapter($adapter);
        }

        if ($typeNode instanceof ArrayTypeNode) {
            $componentAdapter = $this->resolve($typeNode->type);
            return new ArrayAdapter($componentAdapter);
        }

        throw new ResolveException("No suitable adapter found for '$typeNode' type");
    }

    protected function resolveClass(TypeNode $typeNode, ReflectionClass $class): AdapterInterface {
        $modelProperties = [];

        foreach ($class->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if (ReflectionHelper::hasAttribute($property, DataProperty::class)) {
                $dataProperty = ReflectionHelper::getAttribute($property, DataProperty::class);
                $key = $dataProperty->key;
            } else {
                // Значение по умолчанию приводим к заданному стилю.
                $key = $this->config->caseStyle->format($property->getName());
            }

            // Вместо сложной логики, просто смотрим на тип свойства...
            // Это, скажем так, real deal.
            $propertyTypeNode = $this->reflector->resolvePropertyType($property) ?: new IdentifierTypeNode('mixed');

            $getterMethod = $this->reflector->tryResolveGetterOf($property);
            $setterMethod = $this->reflector->tryResolveSetterOf($property);

            $modelProperties[] = new Property(
                name: $property->getName(),
                key: $key,
                promoted: $property->isPromoted(),
                adapter: $this->resolve($propertyTypeNode),
                required: !$property->hasDefaultValue(),
                setter: isset($getterMethod) ? new ReflectionMethodSetter($setterMethod) : new ReflectionPropertySetter($property),
                getter: isset($getterMethod) ? new ReflectionMethodGetter($getterMethod) : new ReflectionPropertyGetter($property)
            );
        }

        $factory = new ReflectionClassNewInstance($class);

        return new ModelAdapter($typeNode, $modelProperties, $factory,
            !$this->config->omitUnmatchedKeys,
            !$this->config->caseSensitive);
    }

    private static function validateClass(ReflectionClass $class) {
        foreach ($class->getProperties() as $property) {
            if (ReflectionHelper::hasAttribute($property, DataProperty::class)) {
                if ($property->isStatic()) {
                    throw new ResolveException();
                }
            }
        }
    }
}