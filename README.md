# Feature Flags (`sholokhov.featureflag`)

Модуль для управления фича-флагами в 1C-Bitrix.

Текущая версия ориентирована на backend API: флаги хранятся в собственной ORM-таблице, читаются через фасад `Feature`, а дополнительные условия доступа подключаются через пользовательские правила.

## Что есть сейчас

- установка модуля через стандартный механизм Bitrix
- хранение флагов в таблице `sholokhov_featureflag`
- регистрация флагов из PHP-кода
- проверка флагов через статический фасад
- настройка стратегий доступа из UI
- хранение стратегий доступа в БД
- поддержка пользовательских runtime-правил через `RuleInterface`
- поддержка пользовательских UI-стратегий через `FeatureStrategyInterface`
- DI-регистрация сервисов через `.settings.php`

## Чего пока нет

- rollout по проценту пользователей из коробки
- аудит изменений флагов

Процентный rollout можно добавить как пользовательскую стратегию.

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

Флаг считается включённым, если одновременно выполняются условия:

1. В таблице `sholokhov_featureflag` у него `ENABLED = true`.
2. Если у флага нет стратегий доступа, флаг доступен для всех.
3. Если стратегии есть, хотя бы одна сохранённая стратегия должна вернуть `true`.
4. Все дополнительные runtime-правила, зарегистрированные через `RuleInterface`, должны вернуть `true`.

Если флаг не найден или при чтении произошла ошибка, `Feature::isEnabled()` вернёт `false`.

## Стратегии доступа из UI

В административной части флага можно настроить стратегии доступа. Теги используются только как смысловые метки назначения, например `Релиз`, `Тестирование` или `Сбор статистики`, и не влияют на доступность флага. Из коробки доступны:

- `ip_list` — ограничение для определённых IP
- `ip_range` — ограничение по диапазону IP
- `user_ids` — ограничение для определённых пользователей
- `user_groups` — ограничение для определённых групп пользователей
- `site_ids` — ограничение по ID сайта

Стратегии одного флага работают по принципу OR: если совпала любая стратегия флага, сохранённые UI-ограничения пропускают пользователя. Это удобно для безопасного релиза: можно открыть фичу, например, либо офисному IP, либо группе тестировщиков.
Сейчас UI поддерживает поля стратегий типов `text` и `textarea`.

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

## Пользовательские стратегии для UI

Если стратегия должна отображаться в админке, сохраняться в БД и участвовать в runtime-проверке, регистрируйте её через `StrategyRegistryInterface`.

Создайте класс стратегии:

```php
<?php

namespace Local\FeatureFlag;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Sholokhov\Featureflag\Strategy\FeatureStrategyInterface;

final class HeaderStrategy implements FeatureStrategyInterface
{
    public function getCode(): string
    {
        return 'header_value';
    }

    public function getName(): string
    {
        return 'HTTP-заголовок';
    }

    public function getDescription(): string
    {
        return 'Включает флаг при совпадении значения HTTP-заголовка.';
    }

    public function getFields(): array
    {
        return [
            [
                'code' => 'name',
                'type' => 'text',
                'label' => 'Имя заголовка',
                'placeholder' => 'X-Feature-Token',
                'required' => true,
            ],
            [
                'code' => 'value',
                'type' => 'text',
                'label' => 'Значение',
                'placeholder' => 'beta',
                'required' => true,
            ],
        ];
    }

    public function normalizeConfig(array $config): Result
    {
        $name = trim((string)($config['name'] ?? ''));
        $value = trim((string)($config['value'] ?? ''));

        if ($name === '' || $value === '') {
            return (new Result())->addError(new Error('Заполните имя и значение заголовка'));
        }

        return (new Result())->setData([
            'config' => [
                'name' => $name,
                'value' => $value,
            ],
        ]);
    }

    public function isEnabled(string $featureCode, array $config): bool
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', (string)$config['name']));

        return (string)($_SERVER[$serverKey] ?? '') === (string)$config['value'];
    }
}
```

Зарегистрируйте стратегию после подключения модуля, например в `init.php` или bootstrap вашего модуля:

```php
use Bitrix\Main\Loader;
use Local\FeatureFlag\HeaderStrategy;
use Sholokhov\Featureflag\ServiceProvider;

Loader::includeModule('sholokhov.featureflag');

ServiceProvider::getStrategyRegistry()->register(new HeaderStrategy());
```

После регистрации стратегия появится в форме флага. При сохранении модуль вызовет `normalizeConfig()`, положит нормализованную конфигурацию в поле `STRATEGIES`, а при `Feature::isEnabled()` вызовет `isEnabled()`.

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
- `TAG_ID` — идентификатор тега
- `STRATEGIES` — JSON-конфигурация стратегий доступа
- `DATE_CREATE`, `DATE_UPDATE`
- `CREATED_BY`, `UPDATED_BY`

Служебные поля выставляются автоматически через ORM-события `onBeforeAdd()` и `onBeforeUpdate()`.

Таблица `sholokhov_featureflag_tags` содержит:

- `ID` — первичный ключ тега
- `NAME` — название тега
- `SORT` — сортировка

## Ограничения текущей реализации

- runtime-правила `RuleInterface` не хранятся в базе и живут только в runtime
- нет отдельного механизма миграций или seed-скриптов для флагов
- документацию и bootstrap правил нужно поддерживать на уровне проекта

## Рекомендации по использованию

- используйте стабильные символьные коды, например `crm.application.v2`
- всегда оставляйте fallback-ветку для отключённого флага
- регистрируйте стартовые флаги в миграциях, init-скриптах или install-логике своих модулей
- после полного rollout удаляйте устаревшие флаги и старую ветку кода
