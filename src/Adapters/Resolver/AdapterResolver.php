<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\AdapterInterface;
use JuraSciix\DataMapper\Adapters\ListArrayAdapter;
use JuraSciix\DataMapper\Adapters\DeferredAdapter;
use JuraSciix\DataMapper\Adapters\EmptyAdapter;
use JuraSciix\DataMapper\Adapters\GenericAdapter;
use JuraSciix\DataMapper\Adapters\GenericAdapterLambda;
use JuraSciix\DataMapper\Adapters\Model\ModelAdapter;
use JuraSciix\DataMapper\Adapters\Model\Property;
use JuraSciix\DataMapper\Adapters\NullableAdapter;
use JuraSciix\DataMapper\DataProperty;
use JuraSciix\DataMapper\Exceptions\ResolveException;
use JuraSciix\DataMapper\SharedConfig;
use JuraSciix\DataMapper\Utils\DocParserWrapper;
use JuraSciix\DataMapper\Utils\ReflectionHelper;
use LogicException;
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

    private bool $deferred = false;

    private readonly DocParserWrapper $docParser;

    /**
     * @param SharedConfig $config
     */
    function __construct(
        readonly SharedConfig $config
    ) {
        $this->docParser = new DocParserWrapper();
    }

    // Можно разрешить только конечную рекурсию.
    //
    // Пример бесконечной рекурсии:
    // class C {
    //   readonly C $c;
    // }
    //
    // Пример конечной рекурсии:
    // class C {
    //   readonly Array<C> $cs;
    // }
    //
    // Еще один пример конечной рекурсии:
    // class C {
    //   readonly Nullable<C> $c;
    // }

    /**
     * @template TValue
     * @param TypeNode $typeNode
     * @return AdapterInterface<TValue>
     */
    function resolve(TypeNode $typeNode): AdapterInterface {
        return $this->resolveWrapper(new TypeWrapper($typeNode));
    }

    /**
     * @param TypeWrapper $wrapper
     * @param TypeNode $node
     * @return AdapterInterface<?>
     */
    private function resolveWithRecursion($wrapper, $node) {
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
    private function resolveOptional($wrapper) {
        if (in_array($wrapper->string, $this->processing, true)) {
            // Невозможно получить адаптер для данного типа в данный момент, но это и не обязательно.
            // Обещаем получить его позже.

            // Сообщаем, что есть DeferredAdapter, из-за которого все дерево кешировать нельзя.
            $this->deferred = true;

            return new DeferredAdapter($this, $wrapper->node);
        }

        return $this->resolveWrapper($wrapper);
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

        // Не кешируем DeferredAdapter, чтобы он не возникал там, где не надо...
        $prevDeferred = $this->deferred;
        $this->deferred = false;

        try {
            $adapter = $this->doResolve($wrapper);
            if (!$this->deferred) {
                $this->cache[$wrapper->string] = $adapter;
            }
        } finally {
            $this->deferred = $prevDeferred;
        }

        return $adapter;
    }

    /**
     * @param TypeWrapper $wrapper
     * @return AdapterInterface<?>
     */
    private function doResolve($wrapper) {
        $typeNode = $wrapper->node;

        if ($typeNode instanceof IdentifierTypeNode) {
            $adapter = $this->resolveIdentifier($wrapper, $typeNode);
            if (isset($adapter)) {
                // Считаем, что обобщенный тип применим только к IdentifierTypeNode.
                // Обработчик GenericTypeNode не будет
                if ($adapter instanceof GenericAdapter) {
                    return $adapter->createLambda([], true);
                }
                return $adapter;
            }
        }

        if ($typeNode instanceof NullableTypeNode) {
            $adapter = $this->resolveOptional(new TypeWrapper($typeNode->type));
            return new NullableAdapter($adapter);
        }

        if ($typeNode instanceof ArrayTypeNode) {
            $listAdapter = new ListArrayAdapter();
            $componentAdapter = $this->resolveOptional(new TypeWrapper($typeNode->type));
            return new GenericAdapterLambda($listAdapter, [$componentAdapter]);
        }

        if ($typeNode instanceof GenericTypeNode) {
            return $this->resolveGeneric($typeNode);
        }

        return $this->failure($typeNode);
    }

    /**
     * @param TypeWrapper $wrapper
     * @param IdentifierTypeNode $typeNode
     * @return AdapterInterface<?>
     */
    private function resolveIdentifier($wrapper, $typeNode) {
        $name = $typeNode->name;
        // Заметка (от 28 апреля, 2026): тип может быть встроенным, например, array.
        // Алгоритм заходит сюда в случае обобщенного типа: array<...>
        if (array_key_exists($name, $this->config->builtinAdapters)) {
            return $this->config->builtinAdapters[$name];
        }
        // Заметка (от 22 февраля, 2026): $typeName должен быть существующим типом.
        //  Все примитивные типы проверяются ранее.
        $adapter = $this->config->adapters->find($name);
        if (isset($adapter)) {
            return $adapter;
        }
        if (class_exists($name)) {
            $class = ReflectionHelper::getReflectionClassSurely($name);
            if ($class->isInstantiable()) {
                AdapterResolver::validateClass($class);
                return $this->resolveClass($wrapper, $class);
            }
        }

        return null;
    }

    /**
     * @return AdapterInterface<?>|null
     */
    protected function tryResolve(string $type): ?AdapterInterface {
        return $this->config->adapters->find($type);
    }

    /**
     * @return AdapterInterface<?>
     */
    protected function failure(TypeNode $typeNode): AdapterInterface {
        throw new ResolveException("No suitable adapter found for '$typeNode' type");
    }
    
    /**
     * @template TValue
     * @param ReflectionClass<TValue> $class
     * @return ModelAdapter<TValue>
     */
    private function resolveClass($wrapper, $class) {
        $modelProperties = [];

        $reflector = new Reflector($this->docParser, $class);

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
            $propertyTypeNode = $reflector->resolvePropertyType($property);
            $required = !$reflector->isPropertyHasDefaultValue($property);

            $getterMethod = $reflector->tryResolveGetterOf($property);
            $setterMethod = $reflector->tryResolveSetterOf($property);

            $modelProperties[] = new Property(
                name: $property->getName(),
                key: $key,
                promoted: $property->isPromoted(),
                adapter: $this->resolveWithRecursion($wrapper, $propertyTypeNode),
                required: $required,
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
        $type = $typeNode->type;
        assert($type instanceof IdentifierTypeNode);

        $adapter = $this->resolveIdentifier(new TypeWrapper($type), $type);
        if (!($adapter instanceof GenericAdapter)) {
            throw new ResolveException("Type '$type' is not generic");
        }

        $genericAdapters = array_map(
            callback: $this->resolve(...),
            array: $typeNode->genericTypes
        );
        try {
            return $adapter->createLambda($genericAdapters, true);
        } catch (LogicException $e) {
            throw new ResolveException("No suitable adapter found for type '$type->name'", previous: $e);
        }
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