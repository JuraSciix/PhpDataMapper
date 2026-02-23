<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\CaseStyle;
use JuraSciix\DataMapper\Exception\ResolveException;
use JuraSciix\DataMapper\Utils\DocParserWrapper;
use JuraSciix\DataMapper\Utils\DocTypeHelper;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ReflectionClass as RClass;
use ReflectionClassConstant as RClassConstant;
use ReflectionException;
use ReflectionFunctionAbstract as RFunctionAbstract;
use ReflectionMethod;
use ReflectionProperty as RProperty;

/**
 * @internal
 */
final class Reflector {
    /** @var array<string, TypeNode> */
    private readonly array $paramTypes;

    function __construct(
        readonly DocParserWrapper $phpDocParser,
        readonly RClass           $class
    ) {
        $this->paramTypes = $this->getConstructorParamTypes();
    }

    /**
     * @param RProperty|RClass|RClassConstant|RFunctionAbstract $reflection
     * @return PhpDocNode|null
     */
    function getDocNode(mixed $reflection): ?PhpDocNode {
        $docComment = $reflection->getDocComment();
        if ($docComment !== false) {
            return $this->phpDocParser->parse($docComment);
        }
        return null;
    }

    private function getConstructorParamTypes() {
        $constructor = $this->class->getConstructor();
        if (is_null($constructor)) {
            return [];
        }
        $docNode = $this->getDocNode($constructor);
        if (is_null($docNode)) {
            return [];
        }
        $paramTagValues = $docNode->getParamTagValues();
        return array_combine(
            keys: array_map(
                callback: fn($str) => substr($str, 1), // Удаляем знак $ из начала строки
                array: array_column($paramTagValues, 'parameterName')
            ),
            values: array_map(
                callback: fn($type) => DocTypeHelper::expand(DocTypeHelper::canonize($type), $this->class),
                array: array_column($paramTagValues, 'type')
            )
        );
    }

    /**
     * @return TypeNode
     */
    function resolvePropertyType(RProperty $property) {
        // PHP-Doc дополняет синтаксические типы.
        // Из соображений простоты, отдаём приоритет PHP-Doc.

        $name = $property->getName();

        if ($property->isPromoted()) {
            // Свойство определено в конструкторе,
            // и тип может быть указан рядом.
            if (array_key_exists($name, $this->paramTypes)) {
                // Тип параметра определен в phpdoc над конструктором.
                return $this->paramTypes[$name];
            }

            // Заметка: PHP _требует_, чтобы promoted-свойства имели тип.
            return DocTypeHelper::toPhpDocTypeNode($property->getType());
        }

        // Свойств определено отдельно, и phpdoc указывается вместе с ним.
        $docNode = $this->getDocNode($property);
        if (isset($docNode)) {
            $varTagValues = $docNode->getVarTagValues();
            if (!empty($varTagValues)) {
                if (count($varTagValues) > 1) {
                    throw new ResolveException("Found multiple @var applications over property");
                }
                return DocTypeHelper::expand($varTagValues[0]->type, $this->class);
            }
        }

        // Информации в phpdoc нет, обращаемся к нативному типу.
        if ($property->hasType()) {
            return DocTypeHelper::toPhpDocTypeNode($property->getType());
        }

        // Тип явно не указан, а значит возвращаем mixed.
        return DocTypeHelper::mixedType();
    }

    /**
     * @return bool
     */
    function isPropertyHasDefaultValue(RProperty $property) {
        if ($property->isPromoted()) {
            // Для promoted-свойств отдельная свистопляска...
            $constructor = $this->class->getConstructor();
            // promoted-свойства определяются в конструкторе,
            // следовательно, конструктор априори существует.
            assert(isset($constructor));

            // Вылавливаем нужный параметр...
            foreach ($constructor->getParameters() as $parameter) {
                if ($parameter->getName() === $property->getName()) {
                    // Смотрим, есть ли у параметра значение по умолчанию...
                    if ($parameter->isDefaultValueAvailable()) {
                        // Есть - оно перейдет и к свойству
                        return true;
                    }
                }
            }
            return false;
        }

        // Простой случай
        return $property->hasDefaultValue();
    }

    /**
     * @return ReflectionMethod|null
     */
    function tryResolveGetterOf(RProperty $property) {
        $class = $property->getDeclaringClass();

        // foo_bar => getFooBar
        $name = 'get' . CaseStyle::toPascalCase($property->getName());

        try {
            $method = $class->getMethod($name);
        } catch (ReflectionException) {
            // Такого метода нет
            return null;
        }

        if ($method->getNumberOfParameters() != 0 || !$method->isPublic()) {
            // Метод не подходит по числу аргументов
            // Или по области видимости
            return null;
        }

        // Прошел проверку
        return $method;
    }

    /**
     * @return ReflectionMethod|null
     */
    function tryResolveSetterOf(RProperty $property) {
        $class = $property->getDeclaringClass();

        // foo_bar => setFooBar
        $name = 'set' . CaseStyle::toPascalCase($property->getName());

        try {
            $method = $class->getMethod($name);
        } catch (ReflectionException) {
            // Такого метода нет
            return null;
        }

        if ($method->getNumberOfParameters() != 1 || !$method->isPublic()) {
            // Метод не подходит по числу аргументов
            // Или по области видимости
            return null;
        }

        // Прошел проверку
        return $method;
    }
}