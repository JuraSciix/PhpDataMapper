<?php

namespace JuraSciix\DataMapper\Utils;

use AssertionError;
use Nette\Utils\Reflection;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

class DocTypeHelper {

    /**
     * @return IdentifierTypeNode
     */
    static function mixedType() {
        return new IdentifierTypeNode('mixed');
    }

    /**
     * @return TypeNode
     */
    static function toPhpDocTypeNode(ReflectionType $type) {
        if ($type instanceof ReflectionNamedType) {
            $node = new IdentifierTypeNode($type->getName());
            // Заметка: PHP позволит задать тип null, но не позволит задать ?null.
            // Тип null формально "разрешает null-значения", поэтому нужно делать проверку.
            // Гениально?
            if ($type->allowsNull() && $type->getName() != 'null') {
                $node = new NullableTypeNode($node);
            }
            return $node;
        }

        if ($type instanceof ReflectionUnionType) {
            $types = [];
            foreach ($type->getTypes() as $t) {
                $types[] = DocTypeHelper::toPhpDocTypeNode($t);
            }
            return new IntersectionTypeNode($types);
        }

        if ($type instanceof ReflectionIntersectionType) {
            $types = [];
            foreach ($type->getTypes() as $t) {
                $types[] = DocTypeHelper::toPhpDocTypeNode($t);
            }
            return new IntersectionTypeNode($types);
        }

        throw new AssertionError("Type is neither named, nor union, nor intersection");
    }

    /**
     * @return bool
     */
    static function isNull($typeNode) {
        return $typeNode instanceof IdentifierTypeNode && $typeNode->name == 'null';
    }

    static function expand(TypeNode $typeNode, ReflectionClass $holder): TypeNode {
        if ($typeNode instanceof IdentifierTypeNode) {
            $name = DocTypeHelper::canonizeName($typeNode->name);
            if (!Reflection::isBuiltinType($name) && !class_exists($name, false)) {
                $typeNode->name = Reflection::expandClassName($name, $holder);
            }
        } else if ($typeNode instanceof NullableTypeNode) {
            DocTypeHelper::expand($typeNode->type, $holder);
        } else if ($typeNode instanceof ArrayTypeNode) {
            DocTypeHelper::expand($typeNode->type, $holder);
        } else if ($typeNode instanceof ArrayShapeNode) {
            foreach ($typeNode->items as $item) {
                DocTypeHelper::expand($item->valueType, $holder);
            }
        } else if ($typeNode instanceof IntersectionTypeNode) {
            foreach ($typeNode->types as $tn) {
                DocTypeHelper::expand($tn, $holder);
            }
        } else if ($typeNode instanceof UnionTypeNode) {
            foreach ($typeNode->types as $tn) {
                DocTypeHelper::expand($tn, $holder);
            }
        } else if ($typeNode instanceof GenericTypeNode) {
            DocTypeHelper::expand($typeNode->type, $holder);
            foreach ($typeNode->genericTypes as $gtn) {
                DocTypeHelper::expand($gtn, $holder);
            }
        }
        return $typeNode;
    }

    static function canonizeName(string $name) {
        return match ($name) {
            'boolean' => 'bool',
            'double' => 'float',
            'integer' => 'int',
            default => $name
        };
    }

    /**
     * @return TypeNode
     */
    static function canonize(TypeNode $type) {
        if ($type instanceof IdentifierTypeNode) {
            $type->name = DocTypeHelper::canonizeName($type->name);
            return $type;
        }
        if ($type instanceof UnionTypeNode) {
            foreach ($type->types as &$tr) {
                $tr = self::canonize($tr);
            }

            $nullable = false;
            foreach ($type->types as $i => $t) {
                if (self::isNull($t)) {
                    $nullable = true;
                    unset($type->types[$i]);
                }
            }

            if (count($type->types) === 1) {
                $type = $type->types[0];
            }

            if ($nullable) {
                $type = new NullableTypeNode($type);
            }
            return $type;
        }

        if ($type instanceof GenericTypeNode) {
            $t = self::canonize($type->type);
            if ($t instanceof IdentifierTypeNode && in_array(strtolower($t->name), ['array', 'list'], true)) {
                // array<T> to T[]
                if (count($type->genericTypes) == 1) {
                    return new ArrayTypeNode(self::canonize($type->genericTypes[0]));
                }
            }
            foreach ($type->genericTypes as &$gt) {
                $gt = self::canonize($gt);
            }
        }

        return $type;
    }
}