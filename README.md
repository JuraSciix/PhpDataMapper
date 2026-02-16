# Objeckson

## Сепарация слоев данных и логики

Мы порицаем смешивание слоев данных и логики. 
Библиотека полностью опирается на свойства классов в рефлексии, и игнорирует всё остальное.
Вы можете определить геттеры и сеттеры для свойств, но только при наличии этих самых свойств!
Это просто аналог виртуальных свойств **PHP 8.4**.

Библиотека не поможет модифицировать уже инстанциированные объекты. Каждый объект .

Это так же делает библиотеку проще.


## Проблема классов-перечислений

Значения класса-перечисления (значения-перечесления) констатированы в коде, 
поэтому если в данных встретится новое значение, у нас не останется иного выбора, 
кроме как выбросить исключение.

Пример:
```php
enum State: int {
    case OPEN = 0;
    case CLOSE = 1; 
}

$mapper = new DataMapper();
// Гарантированно выбросит исключение:
// State have no case for value 2
$mapper->map(2, State::class);
```

### Решение через слияние

> Под слиянием понимается взятие значения-перечисления по умолчанию в случае, 
> если встретилось неизвестное значение.

**Проблема**: важно отслеживать появление неизвестных значений, 
поэтому решение в виде слияния не подходит.

## Проблема обобщенных типов

**PHP** ни коим образом не поддерживает обобщенные типы. 
**PHP Doc** слишком плох, чтобы компенсировать это. Необходим статический анализатор.

## Readonly-поля

Библиотека элегантно обрабатывает модификатор `readonly` у полей.

```php
class Note {
    readonly int $id;
    
    public string $text;
}
```

## Promoted-поля

Библиотека поддерживает promoted-поля.

Для promoted-полей библиотека подготовит значения до создания экземпляра.
Для остальных полей - после создания.

```php
// Контакт в телефонной книге
class Contact {

    // Интересный факт: модификатор public указывать необязательно, :D
    function __construct(
        readonly string $name,
        readonly string $phoneNumber
    ) {
        // Здесь можно написать какую-то логику
    }
    
    readonly bool $favourite;
}
```

## Getter & Setter

Библиотека поддерживает геттеры и сеттеры:

```php
// Contact Plain Old PHP Object
class ContactPopo {
    private $name, $phoneNumber;
    
    /** @param string $name */
    function setName($name) { $this->name = $name; }
    
    /** @return string */
    function getName() { return $this->name; }
        
    /** @param string $name */
    function setPhoneNumber($phoneNumber) { $this->phoneNumber = $phoneNumber; }
    
    /** @return string */
    function getPhoneNumber() { return $this->phoneNumber; }
}

```

## Собственные адаптеры

```php
$mapper = DataMapper::builder()
    ->registerAdapter(T::class, new Factory())
    ->build();
// ...
```

## Собственные фабрики

Вы можете определить, как создавать объект:

```php
$mapper = DataMapper::builder()
    ->registerFactory(T::class, new Factory())
    ->build();
// ...
```

## Объединения типов

Библиотека 1

> Интересный факт: любой тип `?T` считается за _OneOf_: `null | T`