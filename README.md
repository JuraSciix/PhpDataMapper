# DataMapper

Библиотека для отображения данных на объекты PHP с возможностями валидации.

## Внедрение

Быстрый пример:
```php
$mapper = new DataMapper();

$ticket = $mapper->deserialize(json_decode($jsonString, true), Ticket::class);
// ...

class Ticket {
    /**
     * @param string $text Текст обращения. 
     * @param string[] $tags Темы. 
     * @param DateTime $created Дата создания. 
     */
    function __construct(
        readonly string $text
        readonly array $tags,
        readonly DateTime $created,
    ) {}
}
```

Пример с опциями:
```php
// Все опции снизу соответствуют значениям по умолчанию и перерчислены для наглядности.
$mapper = DataMapper::builder()
    ->caseSensitive(false) // Отключаем чувствительность к регистру
    ->caseStyle(CaseStyle::SNAKE_CASE) // Выбираем snake_case стиль написания для ключей
    ->omitUnmatchedKeys(true) // Игнорируем неизвестные ключи
    ->allowTypeConverting(true) // Разрешаем приводить типы. Напр., int ↔ string
    ->dateTimeFormat(DateTime::ATOM) // Выбираем формат для дат
    ->timeZone(new DateTimeZone('Europe/Moscow')) // Выбираем часовой пояс
    ->build();
// ...
```

## Возможности и Roadmap

- [x] Поддержка phpdoc для уточнения типов
- [x] Поддержка обобщенных типов с одним параметром
- [ ] Поддержка обобщенных типов с произвольным числом параметров
- [x] Переопределение геттеров и сеттеров для свойств
- [x] Поддержка классов **Date/Time** и **SPL**.
- [ ] Интеграция сторонних библиотек

К интеграции планируются следующие библиотеки:
- [ ] `moneyphp/money`
- [ ] `brick/math`
- [ ] `mjaschen/phpgeo`
- [ ] `darsyn/ip`
- [ ] `nesbot/carbon`
- [ ] `ramsey/uuid`
- [ ] `psr/clock`
- [ ] `ext-ds`

## Установка

Требования:
- PHP ≥ 8.1
- Composer

Установка с помощью **Composer**:
```
composer require jurasciix/datamapper:^1.0
```

## Представления библиотеки

> Библиотека не обрабатывает возможности PHP версии выше 8.1, таким, как
> [Property Hooks](https://www.php.net/manual/en/language.oop5.property-hooks.php) и
> [Lazy Objects](https://www.php.net/manual/en/language.oop5.lazy-objects.php).

### Сепарация слоев данных и логики

Библиотека порицает смешивание слоев данных и логики,
поэтому стремится работать с _чистыми_ объектами, создавая их самостоятельно.

> Как определить свою логику обработки данных, рассказывается в главе [Адаптеры](#адаптеры).

Процесс анализа классов опирается на свойства классов. Как следствие,
процесс наследует все [особенности рефлексии PHP](#особенности-рефлексии-php).

### Классы-перечислений (enum)

> В настоящий момент библиотека не поддерживает классы-перечисления,
чтобы придерживаться идеологической и архитектурной простоты.

**Проблемы классов-перечислений**. Значения класса-перечисления (значения-перечесления)
констатированы в коде, поэтому если в данных встретится новое значение,
у нас не останется иного выбора, кроме как выбросить исключение.

Пример:
```php
enum State: int {
    case OPEN = 0;
    case CLOSE = 1; 
}

$mapper = new DataMapper();
// Гарантированно привело бы к ошибке.
$mapper->map(2, State::class);
```

**Потенциальное решение через слияние**.

> Под **слиянием** понимается взятие специального значения-перечисления того же класса-перечисления в случае,
> если встретилось неизвестное значение.

**Проблема**: важно отслеживать появление неизвестных значений,
поэтому решение через слияние не подходит.


### Адаптеры

Адаптер это объект, занимающийся сериализацией и десериализацией определенного семейства типов.

> Про семейства типов рассказывается в главе [Связь между типами](#связь-между-типами)

Если данные имеют неудобный вид, с которым не может работать библиотека,
есть возможность определить собственный адаптер:

```php
// Добавляем собственный адаптер для типа GeoCoords
$mapper = DataMapper::builder()
    ->registerAdapter(GeoCoords::class, new class implements AdapterInterface {
        function deserialize(DataMapper $mapper, mixed $data) {
            return new GeoCoords($data[0], $data[1]);
        }
        function serialize(DataMapper $mapper, mixed $data) {
            return [$data->lat, $data->lon];
        }
    })
    ->build();

// Приводим неудобные данные к типу GeoCoords.
$coords = $mapper->deserialize([41.53898, -110.78358], GeoCoords::class);
assert($coords instanceof GeoCoords);

class GeoCoords {
    function __construct(
        readonly float $lat,
        readonly float $lon
    ) {}
}
```


### Обобщенные типы

> Обобщенные типы не поддерживаются на уровне анализа из-за быстрорастущей комплексности этого процесса.

Пример:
```php
/** 
 * @template T
 */
case TList {
    /** @var T[] */
    public $array;
}

/**
 * @template-extends TList<int>
 */
class IntList extends TList {}
```

Анализатору придётся учесть всю иерархию типов и мн-во разных тегов: `template`, `extends`, `implements`, включая разновидности для `psalm` и т. д.

Более того, возникают нежелательно сложные вопросы с ограничениями параметров.

Тем не менее библиотека умеет распознавать обобщенные типы у свойств. Пример:
```php
class Polygon {
    /** @var SplFixedArray<Vertex2D> */
    public SplFixedArray $points;
}
class Vertex2D {
    public int $x, $y;
}
```

Анализатор полностью учтёт параметр `Vertex2D` в типе и отобразит данные ожидаемым образом.

> В случаях, когда параметры опущены, библиотека автоматически доопределяет их до `mixed`.

### Связь между типами

> Связь между типами учитывается только вручную созданными адаптерами.

> Для адаптеров, которые сгенерированы автоматически библиотекой, все типы инвариантны. 

Для дасериализации каждого типа `T` библиотека ищет любой подходящий (контравариантный) десериализатор.

Для сериализации каждого типа `T` библиотека ищет любой подходящий (ковариантный) сериализатор.

Пример:
```php
interface NameAware {
    function getName(): string;
}
interface RatingAware {
    function getRating(): float;
}
class NameAndRating implements NameAware, RatingAware {
    function __construct(
        readonly string $name,
        readonly float $rating,
    ) {}
    
    function getName(): string { return $this->name; }
    function getRating(): float { return $this->rating; }
}

$mapper = DataMapper::builder()
    ->registerAdapter(NameAndRating::class, new class implements AdapterInterface {
        function deserialize(DataMapper $mapper, mixed $data) {
            return new NameAndRating($data['name'], $data['rating']);
        }
        function serialize(DataMapper $mapper, mixed $data) {
            return [
                'name' => $data->getName(), 
                'rating' => $data->getRating()
            ];            
        }
    })
    ->builder();

// Ошибка: отсутствует ключ `rating`
$nameAware = $map->deserialize(['name' => '...'], NameAware::class);
```


### Особенности рефлексии PHP

Рассмотрим следующий код:
```php
case Base {
    private $foo;
}

class Der extends Base {}
```

При работе с классом `Der`, поле `Base::$foo` не будет учтено анализатором и проигнорируется.
Чтобы исправить это, поле `Base::$foo` должно иметь область видимости, как минимум, `protected`.
Геттеры и сеттеры не оказывают влияние на поведение анализатора,
так как это условное ограничение рефлексии PHP, которое поощряется из принципа простоты.

 