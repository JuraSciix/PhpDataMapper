<?php

namespace JuraSciix\DataMapper;

use DateTime;
use JuraSciix\DataMapper\Adapters\DateTime\DateTimeAdapter;
use JuraSciix\DataMapper\Adapters\DateTime\DateTimeImmutableAdapter;
use JuraSciix\DataMapper\Adapters\Resolver\AdapterResolver;
use JuraSciix\DataMapper\Adapters\Resolver\Reflector;
use JuraSciix\DataMapper\Adapters\SPL\SplFixedArrayAdapter;
use JuraSciix\DataMapper\Exception\DataMapperException;
use JuraSciix\DataMapper\Exception\DeserializeException;
use JuraSciix\DataMapper\Exception\ResolveException;
use JuraSciix\DataMapper\Exception\SerializeException;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use SplFixedArray;

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
        return new Builder();
    }

    private readonly SharedConfig $config;

    private readonly AdapterResolver $serializerResolver;
    private readonly AdapterResolver $deserializerResolver;

    /**
     * Конструктор.
     */
    function __construct(?Builder $builder = null) {
        $builder ??= new Builder();

        $config = $builder->config;

        $config->registerBuiltinAdapters();

        // Между двумя имплементациями DateTimeInterface,
        // приоритет получит та, которая была последней зарегистрирована.
        $builder->registerAdapter(DateTime::class,
            new DateTimeImmutableAdapter($config->dateTimeFormat, $config->timeZone, $config->allowTypeConverting));
        // Последним регистрируется DateTime.
        $builder->registerAdapter(DateTime::class,
            new DateTimeAdapter($config->dateTimeFormat, $config->timeZone, $config->allowTypeConverting));
        $builder->registerAdapter(SplFixedArray::class, new SplFixedArrayAdapter());

        $this->config = $config;

        $reflector = new Reflector();
        $this->serializerResolver = new AdapterResolver($this->config, $reflector,
            $this->config->serializers);
        $this->deserializerResolver = new AdapterResolver($this->config, $reflector,
            $this->config->deserializers);
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
            $adapter = $this->deserializerResolver->resolve($typeNode);
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
        $adapter = $this->serializerResolver->resolve($typeNode);
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