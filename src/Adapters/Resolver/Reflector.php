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

final class Reflector {
    private readonly DocParserWrapper $phpDocParser;

    function __construct() {
        $this->phpDocParser = new DocParserWrapper();
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

    function getConstructorParamTypes(RClass $class) {
        $constructor = $class->getConstructor();
        if (is_null($constructor)) {
            return null;
        }
        $docNode = $this->getDocNode($constructor);
        if (is_null($docNode)) {
            return null;
        }
        $paramTagValues = $docNode->getParamTagValues();
        return array_combine(
            keys: array_map(
                callback: fn ($str) => substr($str, 1), // Удаляем знак $ из начала строки
                array: array_column($paramTagValues, 'parameterName')
            ),
            values: array_map(
                callback: fn ($type) => DocTypeHelper::expand(DocTypeHelper::canonize($type), $class),
                array: array_column($paramTagValues, 'type')
            )
        );
    }

    /**
     * @return TypeNode|null
     */
    function resolvePropertyType(RProperty $property) {
        // PHP-Doc дополняет синтаксические типы.
        // Из соображений простоты, отдаём приоритет PHP-Doc.

        $holder = $property->getDeclaringClass();

        $docNode = $this->getDocNode($property);
        if (isset($docNode)) {
            $varTagValues = $docNode->getVarTagValues();
            if (!empty($varTagValues)) {
                if (count($varTagValues) > 1) {
                    throw new ResolveException("Found multiple @var applications over property");
                }
                return DocTypeHelper::expand($varTagValues[0]->type, $holder);
            }
        }

        if ($property->hasType()) {
            return DocTypeHelper::toPhpDocTypeNode($property->getType());
        }

        return null;
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