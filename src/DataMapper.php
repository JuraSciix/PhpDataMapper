<?php

namespace JuraSciix\DataMapper;

use JuraSciix\DataMapper\Adapters\Resolver\AdapterResolver;
use JuraSciix\DataMapper\Exception\DataMapperException;
use JuraSciix\DataMapper\Exception\DeserializeException;
use JuraSciix\DataMapper\Exception\ResolveException;
use JuraSciix\DataMapper\Exception\SerializeException;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 *
 */
final class DataMapper {

    /**
     * Возвращает сборный объект для построения `DataMapper`.
     *
     * @return Builder Сборный объект.
     */
    static function builder() {
        $mapper = new DataMapper();
        return new Builder($mapper, $mapper->config);
    }

    private readonly SharedConfig $config;

    private readonly AdapterResolver $resolver;

    function __construct() {
        $this->config = new SharedConfig();
        $this->resolver = new AdapterResolver($this->config);
    }

    /**
     * @template TValue
     *
     * Десериализовывает данные и возвращает результат.
     *
     * @param mixed $data Входящие данные.
     * @param class-string<TValue>|string $type Тип, на который надо отобразить данные.
     * @return TValue Результат.
     *
     * @throws DataMapperException В случае той или иной ошибки.
     */
    function deserialize(mixed $data, string $type): mixed {
        $typeNode = new IdentifierTypeNode($type);
        return $this->doDeserialize($data, $typeNode);
    }

    /**
     * @template TComponent
     *
     * Десериализовывает массив данного типа и возвращает результат.
     *
     * @param array $data Массив.
     * @param class-string<TComponent>|string $componentType Тип компонента массива.
     * @return TComponent[] Результат.
     *
     * @throws DataMapperException В случае той или иной ошибки.
     */
    function deserializeArray(array $data, string $componentType): array {
        $typeNode = new ArrayTypeNode(new IdentifierTypeNode($componentType));
        return $this->doDeserialize($data, $typeNode);
    }

    /**
     * @template TValue
     *
     * @param mixed $data
     * @param TypeNode $typeNode
     * @return TValue
     */
    private function doDeserialize($data, $typeNode) {
        try {
            $adapter = $this->resolver->resolve($typeNode);
            return $adapter->deserialize($this, $data);
        } catch (ResolveException $e) {
            $message = $e->getMessage();
            throw new DataMapperException(
                message: "Unable to resolve $typeNode: $message",
                previous: $e->getPrevious()
            );
        } catch (DeserializeException $e) {
            $path = $e->getPath();
            $message = $e->getMessage();
            throw new DataMapperException(
                message: "Cannot deserialize $path for $typeNode: $message",
                previous: $e->getPrevious()
            );
        }
    }

    /**
     * Сериализует объекты.
     *
     * @param mixed $data Входящие данные для сериализации.
     * @return mixed Результат.
     *
     * @throws DataMapperException В случае той или иной ошибки.
     */
    function serialize(mixed $data): mixed {
        $typeNode = new IdentifierTypeNode(is_object($data) ? get_class($data) : gettype($data));
        $adapter = $this->resolver->resolve($typeNode);
        try {
            return $adapter->serialize($this, $data);
        } catch (ResolveException $e) {
            $message = $e->getMessage();
            throw new DataMapperException(
                message: "Unable to resolve $typeNode: $message",
                previous: $e->getPrevious()
            );
        } catch (SerializeException $e) {
            $path = $e->getPath();
            $message = $e->getMessage();
            throw new DataMapperException(
                message: "Cannot serialize $path from $typeNode: $message",
                previous: $e->getPrevious()
            );
        }
    }

    /**
     * @return bool Адаптеры чувствительны к регистру ключей?
     */
    function isCaseSensitive() {
        return $this->config->caseSensitive;
    }

    /**
     * @return bool Неизвестные ключи игнорируются?
     */
    function isOmittingUnmatchedKeys() {
        return $this->config->omitUnmatchedKeys;
    }

    /**
     * @return bool Можно ли конвертировать типы, например, как `int` к `string` и наоборот?
     */
    function isAllowTypeConverting() {
        return $this->config->allowTypeConverting;
    }

    /**
     * @return CaseStyle Стиль написания ключей.
     */
    function getCaseStyle() {
        return $this->config->caseStyle;
    }
}