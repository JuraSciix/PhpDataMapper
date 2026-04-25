<?php

use JuraSciix\DataMapper\Exceptions\DataMapperException;
use JuraSciix\DataMapper\Exceptions\DeserializeException;
use JuraSciix\DataMapper\Exceptions\ResolveException;
use JuraSciix\DataMapper\Exceptions\SerializeException;

// Для обратной совместимости на версиях ниже 1.1.3
class_alias(DataMapperException::class, 'JuraSciix\DataMapper\Exception\DataMapperException');
class_alias(DeserializeException::class, 'JuraSciix\DataMapper\Exception\DeserializeException');
class_alias(ResolveException::class, 'JuraSciix\DataMapper\Exception\ResolveException');
class_alias(SerializeException::class, 'JuraSciix\DataMapper\Exception\SerializeException');
