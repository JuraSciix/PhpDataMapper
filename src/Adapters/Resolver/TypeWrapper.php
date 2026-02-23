<?php

namespace JuraSciix\DataMapper\Adapters\Resolver;

use JuraSciix\DataMapper\Utils\DocTypeHelper;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

final class TypeWrapper {
    readonly TypeNode $sourceNode;
    readonly TypeNode $node;
    readonly string $string;

    function __construct(TypeNode $typeNode) {
        $this->sourceNode = $typeNode;
        $tn = DocTypeHelper::canonize($typeNode);
        $this->node = $tn;
        $this->string = strval($tn);
    }
}