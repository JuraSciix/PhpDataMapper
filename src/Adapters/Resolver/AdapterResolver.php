<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\Adapters\ArrayAdapter;
use JuraSciix\DataMapper\Adapters\Model\Property;
use JuraSciix\DataMapper\Adapters\ModelAdapter;
use JuraSciix\DataMapper\Adapters\NullableAdapter;
use JuraSciix\DataMapper\Adapters\SingleGenericAdapter;
use JuraSciix\DataMapper\Adapters\SingleGenericLambdaAdapter;
use JuraSciix\DataMapper\DataProperty;
use JuraSciix\DataMapper\Exception\ResolveException;
use JuraSciix\DataMapper\SharedConfig;
use JuraSciix\DataMapper\Utils\DocTypeHelper;
use JuraSciix\DataMapper\Utils\ReflectionHelper;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
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
     * @return AdapterInterface<TValue>
     */
    function resolve(TypeNode $typeNode): AdapterInterface {
        $adapter = $this->resolveWrapper(new TypeWrapper($typeNode));
        if ($adapter instanceof SingleGenericAdapter) {
            // Дополняем тип
//            return new SingleGenericLambdaAdapter($adapter, EmptyAdapter::instance());
            throw new ResolveException("$typeNode requires specifying a generic type");
        }
        return $adapter;
    }

    /**
     * @param TypeWrapper $wrapper
     * @param TypeNode $node
     * @return AdapterInterface<?>
     */
    private function resolveWithTrack($wrapper, $node) {
        // Заметка: проверка на рекурсию нужна только для адаптеров над классами,
        //  остальные типы формально не могут быть рекурсивными.
        if (in_array($wrapper->string, $this->processing, true)) {
            $last = $this->processing[sizeof($this->processing) - 1];
            throw new ResolveException(
                "Recursion detected: $wrapper->string refers to $last, which refers back to it");
        }

        $this->processing[] = $wrapper->string;

        try {
            return $this->resolveWrapper(new TypeWrapper($node));
        } finally {
            // Последний элемент должен быть $typeString.
            $last = array_pop($this->processing);
            assert($last === $wrapper->string);
        }
    }

    /**
     * @param TypeWrapper $wrapper
     * @return AdapterInterface<?>
     */
    private function resolveWrapper($wrapper) {
        // Оптимизация: адаптеры для встроенных типов уже кешированы, поэтому проверяем их первыми.
        if (array_key_exists($wrapper->string, $this->config->builtinAdapters)) {
            return $this->config->builtinAdapters[$wrapper->string];
        }

        // Бездумно кешируем. Заметка: Это вредно, кстати.
        if (array_key_exists($wrapper->string, $this->cache)) {
            return $this->cache[$wrapper->string];
        }

        $adapter = $this->doResolve($wrapper);
        $this->cache[$wrapper->string] = $adapter;
        return $adapter;
    }

    /**
     * @param TypeWrapper $wrapper
     * @return AdapterInterface<?>
     */
    private function doResolve($wrapper) {
        $typeNode = $wrapper->node;

        if ($typeNode instanceof IdentifierTypeNode) {
            $typeName = $typeNode->name;
            if ($this->config->adapters->contains($typeName)) {
                return $this->config->adapters->get($typeName);
            }
            if (class_exists($typeName)) {
                $class = ReflectionHelper::getReflectionClassSurely($typeName);
                if ($class->isInstantiable()) {
                    AdapterResolver::validateClass($class);
                    return $this->resolveClass($wrapper, $class);
                }
            }
        }

        if ($typeNode instanceof NullableTypeNode) {
            $adapter = $this->resolve($typeNode->type);
            return new NullableAdapter($adapter);
        }

        if ($typeNode instanceof ArrayTypeNode) {
            $componentAdapter = $this->resolve($typeNode->type);
            return new ArrayAdapter($componentAdapter);
        }

        if ($typeNode instanceof GenericTypeNode) {
            return $this->resolveGeneric($typeNode);
        }

        throw new ResolveException("No suitable adapter found for '$typeNode' type");
    }

    protected function resolveClass(TypeWrapper $wrapper, ReflectionClass $class): AdapterInterface {
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
            $propertyTypeNode = $this->reflector->resolvePropertyType($property) ?: DocTypeHelper::mixedType();

            $getterMethod = $this->reflector->tryResolveGetterOf($property);
            $setterMethod = $this->reflector->tryResolveSetterOf($property);

            $modelProperties[] = new Property(
                name: $property->getName(),
                key: $key,
                promoted: $property->isPromoted(),
                adapter: $this->resolveWithTrack($wrapper, $propertyTypeNode),
                required: !$property->hasDefaultValue(),
                setter: isset($getterMethod) ? new ReflectionMethodSetter($setterMethod) : new ReflectionPropertySetter($property),
                getter: isset($getterMethod) ? new ReflectionMethodGetter($getterMethod) : new ReflectionPropertyGetter($property)
            );
        }

        $factory = new ReflectionClassNewInstance($class);

        return new ModelAdapter($wrapper->node, $modelProperties, $factory,
            !$this->config->omitUnmatchedKeys,
            !$this->config->caseSensitive);
    }

    /**
     * @param GenericTypeNode $typeNode
     * @return AdapterInterface<?>
     */
    private function resolveGeneric($typeNode) {
        $adapter = $this->resolveWrapper(new TypeWrapper($typeNode->type));
        if (!($adapter instanceof SingleGenericAdapter)) {
            throw new ResolveException("Type $typeNode->type not supplying a generic type");
        }

        // Положим, T[G1] = ...
        // Тогда T<T> или невозможен, или идентичен T<T<mixed>>, что разрешимо.
        // Следовательно, рекурсия невозможна.
        if (count($typeNode->genericTypes) === 1) {
            $genericAdapter = $this->resolve($typeNode->genericTypes[0]);
            return new SingleGenericLambdaAdapter($adapter, $genericAdapter);
        }

        return $adapter;
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