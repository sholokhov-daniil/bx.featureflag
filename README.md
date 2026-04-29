# Feature Flags (`sholokhov.featureflag`)

Модуль для управления фича-флагами в 1C-Bitrix.

Текущая версия ориентирована на backend API: флаги хранятся в собственной ORM-таблице, читаются через фасад `Feature`, а дополнительные условия доступа подключаются через пользовательские правила.

## Что есть сейчас

- установка модуля через стандартный механизм Bitrix
- хранение флагов в таблице `sholokhov_featureflag`
- регистрация флагов из PHP-кода
- проверка флагов через статический фасад
- поддержка пользовательских runtime-правил через `RuleInterface`
- DI-регистрация сервисов через `.settings.php`

## Чего пока нет

- rollout по проценту пользователей из коробки
- готовых правил по пользователям, группам, сайтам

Если эти возможности нужны, их нужно дописывать поверх текущего API.

## Системные требования

- PHP 8.4+
- установленный 1C-Bitrix с поддержкой локальных модулей

## Установка

1. Положите модуль в `/local/modules/sholokhov.featureflag`.
2. В административной части Bitrix откройте `Marketplace -> Установленные решения`.
3. Установите модуль `sholokhov.featureflag`.

Во время установки модуль:

- создаёт таблицу `sholokhov_featureflag`
- регистрирует сервисы из `.settings.php`
- добавляет страницу админки `Сервисы -> Фича-флаги -> Управление флагами`

## Быстрый старт

Сначала подключите модуль:

```php
use Bitrix\Main\Loader;

Loader::includeModule('sholokhov.featureflag');
```

Зарегистрируйте флаг:

```php
use Sholokhov\Featureflag\DTO\FlagInfo;
use Sholokhov\Featureflag\Feature;

Feature::register(new FlagInfo(
    code: 'crm.application.v2',
    name: 'Новая карточка заявки',
    description: 'Переключатель новой версии карточки заявки',
    enabled: true,
));
```

Проверьте его в коде:

```php
use Sholokhov\Featureflag\Feature;

if (Feature::isEnabled('crm.application.v2')) {
    $this->newLogic();
} else {
    $this->oldLogic();
}
```

## API фасада `Feature`

### `Feature::isEnabled(string $code): bool`

Возвращает `true`, если флаг включён и все применимые правила разрешают доступ.

```php
if (Feature::isEnabled('catalog.fast-filter')) {
    // новая логика
}
```

### `Feature::isDisabled(string $code): bool`

Инверсия `isEnabled()`.

```php
if (Feature::isDisabled('catalog.fast-filter')) {
    // fallback
}
```

### `Feature::when(string $code, callable $enabled, callable $disabled): void`

Позволяет разнести поведение по двум callback.

```php
Feature::when(
    'checkout.v2',
    enabled: fn() => $this->renderNewCheckout(),
    disabled: fn() => $this->renderOldCheckout(),
);
```

### `Feature::require(string $code): void`

Выбрасывает `RuntimeException`, если флаг выключен.

```php
Feature::require('api.partner-access');
```

### `Feature::any(array $codes): bool`

Возвращает `true`, если активен хотя бы один флаг.

```php
if (Feature::any(['search.v2', 'search.ab-test'])) {
    $this->useNewSearch();
}
```

### `Feature::all(array $codes): bool`

Возвращает `true`, только если активны все флаги.

```php
if (Feature::all(['checkout.v2', 'payment.new-form'])) {
    $this->enableScenario();
}
```

### `Feature::getByCode(string $code): ?FlagInterface`

Возвращает объект флага или `null`, если он не найден.

```php
$flag = Feature::getByCode('crm.application.v2');

if ($flag) {
    $name = $flag->getName();
    $description = $flag->getDescription();
}
```

### `Feature::register(FlagInfo $flag): AddResult`

Создаёт запись в таблице флагов.

```php
$result = Feature::register(new FlagInfo(
    code: 'personal.cabinet.v2',
    name: 'Новый личный кабинет',
    description: 'Переключение новой версии личного кабинета',
    enabled: false,
));

if (!$result->isSuccess()) {
    throw new \RuntimeException(implode(', ', $result->getErrorMessages()));
}
```

## Как работает вычисление флага

Флаг считается включённым, если одновременно выполняются два условия:

1. В таблице `sholokhov_featureflag` у него `ENABLED = true`.
2. Все зарегистрированные правила, которые поддерживают этот код, возвращают `true`.

Если флаг не найден или при чтении произошла ошибка, `Feature::isEnabled()` вернёт `false`.

## Пользовательские правила

Модуль поддерживает runtime-правила через интерфейс `Sholokhov\Featureflag\RuleInterface`.

В модуле есть готовые правила:

- `Sholokhov\Featureflag\Rules\IsAdminRule` — доступ только для администраторов Bitrix
- `Sholokhov\Featureflag\Rules\UserIdRule` — доступ по списку ID пользователей
- `Sholokhov\Featureflag\Rules\UserGroupRule` — доступ по списку ID групп

Правила конфигурируются через конструктор:

- `IsAdminRule` принимает необязательный массив кодов фич `supportedCodes`
- `UserIdRule` принимает массив разрешённых ID пользователей и необязательный `supportedCodes`
- `UserGroupRule` принимает массив разрешённых ID групп и необязательный `supportedCodes`

Если `supportedCodes` пустой, правило применяется ко всем флагам.
Переданные ID в `UserIdRule` и `UserGroupRule` нормализуются в конструкторе: приводятся к `int`, очищаются от дубликатов и неположительных значений.

Примеры использования встроенных правил:

```php
use Bitrix\Main\Loader;
use Sholokhov\Featureflag\ServiceProvider;
use Sholokhov\Featureflag\Rules\IsAdminRule;
use Sholokhov\Featureflag\Rules\UserGroupRule;
use Sholokhov\Featureflag\Rules\UserIdRule;

Loader::includeModule('sholokhov.featureflag');

ServiceProvider::getRuleRegistry()
    ->register(new IsAdminRule(
        supportedCodes: ['admin.dashboard.v2'],
    ))
    ->register(new UserIdRule(
        userIds: [1, 15, 42],
        supportedCodes: ['crm.application.v2'],
    ))
    ->register(new UserGroupRule(
        groupIds: [1, 8],
        supportedCodes: ['catalog.fast-filter'],
    ));
```

Если `supportedCodes` не передавать, правило будет применяться ко всем флагам:

```php
ServiceProvider::getRuleRegistry()->register(
    new UserGroupRule(groupIds: [1])
);
```

Практически регистрацию правил имеет смысл делать в `init.php` проекта или в bootstrap вашего прикладного модуля.

## Изменение состояния флага

В текущей версии изменение состояния и удаление доступны через фасад `Feature`:

```php
use Sholokhov\Featureflag\Feature;

// Включение
Feature::enabled('crm.application.v2');

// Отключение
Feature::disabled('crm.application.v2');

// Удаление
Feature::unRegister('crm.application.v2');
```

Из публичного API сейчас доступны включение, отключение и удаление. Изменение `NAME` и `DESCRIPTION` по-прежнему нужно делать через ORM.

## Структура хранения

Таблица `sholokhov_featureflag` содержит:

- `CODE` — первичный ключ флага
- `ENABLED` — признак активности
- `NAME` — человекочитаемое имя
- `DESCRIPTION` — описание
- `DATE_CREATE`, `DATE_UPDATE`
- `CREATED_BY`, `UPDATED_BY`

Служебные поля выставляются автоматически через ORM-события `onBeforeAdd()` и `onBeforeUpdate()`.

## Ограничения текущей реализации

- правила не хранятся в базе и живут только в runtime
- нет отдельного механизма миграций или seed-скриптов для флагов
- документацию и bootstrap правил нужно поддерживать на уровне проекта

## Рекомендации по использованию

- используйте стабильные символьные коды, например `crm.application.v2`
- всегда оставляйте fallback-ветку для отключённого флага
- регистрируйте стартовые флаги в миграциях, init-скриптах или install-логике своих модулей
- после полного rollout удаляйте устаревшие флаги и старую ветку кода
