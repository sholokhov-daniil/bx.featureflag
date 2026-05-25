# Feature Flags (`sholokhov.featureflag`)

Модуль для управления feature flags в 1C-Bitrix.

Модуль предназначен для:
- безопасного rollout новой функциональности
- ограничения доступа к новым возможностям
- A/B тестирования
- постепенного внедрения изменений
- runtime-управления поведением системы
- контроля технического долга feature flags

---

# Возможности модуля

- хранение feature flags в ORM-таблице
- управление флагами через административный интерфейс
- runtime-проверка доступности флагов
- пользовательские runtime-правила
- пользовательские UI-стратегии
- планирование удаления feature flags
- подсветка просроченных feature flags
- подсветка feature flags, которые должны быть удалены сегодня
- DI-регистрация сервисов через `.settings.php`
- fallback-safe архитектура
- интеграция с любым Bitrix-проектом

---

# Архитектура

Модуль разделяет:

- хранение состояния флага
- UI-стратегии доступа
- runtime-правила
- runtime-проверку доступности
- lifecycle feature flags

Feature flag считается активным только если:

1. флаг включён глобально
2. пользователь прошёл UI-стратегии
3. пользователь прошёл runtime-правила

Это позволяет:
- безопасно выкатывать функционал
- ограничивать аудиторию
- тестировать новые возможности
- постепенно расширять rollout
- контролировать устаревшие feature flags

---

# Планирование удаления feature flags

Модуль поддерживает поле:

```text
Плановая дата удаления
```

Данное поле предназначено для:

- контроля технического долга
- отслеживания устаревших feature flags
- планирования cleanup после rollout
- предотвращения накопления «вечных» feature flags

## Принцип работы

Feature flag может содержать дату:

```text
REMOVE_PLANNED_AT
```

После наступления даты:

- feature flag НЕ удаляется автоматически
- функциональность продолжает работать
- флаг помечается как требующий удаления

Это позволяет безопасно контролировать lifecycle feature flags без риска неожиданного отключения функциональности.

---

## Подсветка feature flags

Административный интерфейс автоматически подсвечивает:

| Состояние | Описание |
|---|---|
| Жёлтый | Feature flag должен быть удалён сегодня |
| Красный | Плановая дата удаления уже просрочена |

Это позволяет быстро находить feature flags, требующие cleanup.

---

# Схема вычисления feature flag

```text
Feature::isEnabled()
        │
        ▼
Флаг существует?
        │
        ▼
ENABLED = true ?
        │
        ▼
Есть UI-стратегии?
        │
   ┌────┴────┐
   ▼         ▼
нет       хотя бы одна true
   │         │
   └────┬────┘
        ▼
Все runtime-правила true?
        │
        ▼
      true
```

---

# Жизненный цикл feature flag

Рекомендуемый lifecycle:

1. Создание feature flag
2. Ограниченный rollout
3. Тестирование
4. Постепенное расширение аудитории
5. Полный rollout
6. Назначение даты удаления
7. Удаление старой логики
8. Удаление feature flag

---

# Рекомендации по использованию

## Всегда назначайте дату удаления

После успешного rollout рекомендуется сразу указывать:

```text
Плановую дату удаления
```

Это помогает:

- не забывать cleanup
- контролировать технический долг
- избегать накопления legacy-кода

---

## Feature flags — временный механизм

Feature flags не должны существовать постоянно.

После полного rollout рекомендуется:

- удалить старую ветку логики
- удалить runtime-правила
- удалить UI-стратегии
- удалить сам feature flag

---

## Не используйте feature flags как permanent-конфиг

Feature flag — это механизм rollout.

Не рекомендуется:

- хранить бизнес-настройки
- заменять feature flags системные настройки
- использовать feature flags как постоянный toggle

---

# Структура хранения

## Таблица sholokhov_featureflag

| Поле | Назначение |
|---|---|
| CODE | Код флага |
| ENABLED | Активность |
| NAME | Название |
| DESCRIPTION | Описание |
| TAG_ID | ID тега |
| REMOVE_PLANNED_AT | Плановая дата удаления |
| STRATEGIES | JSON стратегий |
| DATE_CREATE | Дата создания |
| DATE_UPDATE | Дата обновления |
| CREATED_BY | Кто создал |
| UPDATED_BY | Кто обновил |

---

# Production-рекомендации

- выкатывайте новые функции через feature flags
- не удаляйте fallback до полного rollout
- всегда назначайте дату удаления
- регулярно удаляйте просроченные feature flags
- используйте отдельные теги:
    - Release
    - Testing
    - Internal
    - Experimental

---

# Системные требования

- PHP 8.4+
- установленный 1C-Bitrix
- поддержка локальных модулей

---

# Установка

## 1. Установка модуля

Разместите модуль:

```text
/local/modules/sholokhov.featureflag
```

---

## 2. Установка через Bitrix

Откройте:

```text
Marketplace -> Установленные решения
```

Установите модуль:

```text
sholokhov.featureflag
```

---

# Быстрый старт

## Подключение модуля

```php
use Bitrix\Main\Loader;

Loader::includeModule('sholokhov.featureflag');
```

---

## Регистрация feature flag

```php
use Sholokhov\Featureflag\DTO\FeatureFlagPayload;
use Sholokhov\Featureflag\Feature;

Feature::register(new FeatureFlagPayload(
    code: 'crm.application.v2',
    name: 'Новая карточка заявки',
    description: 'Переключатель новой версии карточки заявки',
    enabled: true,
));
```

---

## Проверка feature flag

```php
use Sholokhov\Featureflag\Feature;

if (Feature::isEnabled('crm.application.v2')) {
    $this->newLogic();
} else {
    $this->oldLogic();
}
```

---

# API фасада Feature

| Метод | Назначение |
|---|---|
| isEnabled | Проверка доступности |
| isDisabled | Инверсия isEnabled |
| when | Callback-based переключение |
| require | Жёсткая проверка |
| any | Проверка хотя бы одного флага |
| all | Проверка всех флагов |
| getByCode | Получение объекта флага |
| register | Регистрация флага |
| enabled | Включение флага |
| disabled | Отключение флага |
| unRegister | Удаление флага |

---

## Feature::isEnabled()

```php
if (Feature::isEnabled('catalog.fast-filter')) {
    // новая логика
}
```

---

## Feature::isDisabled()

```php
if (Feature::isDisabled('catalog.fast-filter')) {
    // fallback логика
}
```

---

## Feature::when()

```php
Feature::when(
    'checkout.v2',
    enabled: fn() => $this->renderNewCheckout(),
    disabled: fn() => $this->renderOldCheckout(),
);
```

---

## Feature::require()

```php
Feature::require('api.partner-access');
```

---

## Feature::any()

```php
if (Feature::any([
    'search.v2',
    'search.ab-test',
])) {
    $this->useNewSearch();
}
```

---

## Feature::all()

```php
if (Feature::all([
    'checkout.v2',
    'payment.new-form',
])) {
    $this->enableScenario();
}
```

---

## Feature::register()

```php
use Sholokhov\Featureflag\DTO\FeatureFlagPayload;
use Sholokhov\Featureflag\Feature;

$result = Feature::register(new FeatureFlagPayload(
    code: 'personal.cabinet.v2',
    name: 'Новый личный кабинет',
    description: 'Переключение новой версии личного кабинета',
    enabled: false,
));

if (!$result->isSuccess()) {
    throw new RuntimeException(
        implode(', ', $result->getErrorMessages())
    );
}
```

---

# Как работает вычисление feature flag

Флаг считается включённым, если одновременно выполняются условия:

1. `ENABLED = true`
2. хотя бы одна UI-стратегия разрешает доступ
3. все runtime-правила разрешают доступ

---

# UI-стратегии

Стратегии используются для ограничения доступа через административный интерфейс.

## Встроенные стратегии

| Код | Назначение |
|---|---|
| ip_list | Список IP |
| ip_range | Диапазон IP |
| user_ids | ID пользователей |
| user_groups | Группы пользователей |
| site_ids | ID сайтов |

---

## Принцип работы стратегий

Стратегии работают по принципу:

```text
OR
```

Если хотя бы одна стратегия вернула `true`, пользователь получает доступ.

Это позволяет:
- открывать фичу тестировщикам
- ограничивать rollout по IP
- постепенно расширять аудиторию

---

# Runtime-правила

Runtime-правила работают через интерфейс:

```php
Sholokhov\Featureflag\RuleInterface
```

---

# Встроенные runtime-правила

| Класс | Назначение |
|---|---|
| IsAdminRule | Только администраторы |
| UserIdRule | Ограничение по ID пользователей |
| UserGroupRule | Ограничение по группам |

---

# Регистрация runtime-правил

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

---

# Пользовательские UI-стратегии

Если стратегия должна:
- отображаться в UI
- храниться в БД
- участвовать в runtime-проверке

используйте:

```php
FeatureStrategyInterface
```

---

# Пример пользовательской стратегии

```php
<?php

namespace Local\FeatureFlag;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\TextField;
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
            (new TextField('name'))
                ->setName('Имя заголовка')
                ->setRequired(true),
                
            (new TextField('value'))
                ->setName('Значение')
                ->setRequired(true),
        ];
    }

    public function normalizeConfig(array $config): Result
    {
        $name = trim((string)($config['name'] ?? ''));
        $value = trim((string)($config['value'] ?? ''));

        if ($name === '' || $value === '') {
            return (new Result())
                ->addError(
                    new Error('Заполните имя и значение заголовка')
                );
        }

        return (new Result())->setData([
            'config' => [
                'name' => $name,
                'value' => $value,
            ],
        ]);
    }

    public function isEnabled(
        string $featureCode,
        array $config
    ): bool {
        $serverKey = 'HTTP_' .
            strtoupper(
                str_replace('-', '_', (string)$config['name'])
            );

        return (string)($_SERVER[$serverKey] ?? '')
            === (string)$config['value'];
    }
}
```

---

# Регистрация пользовательской стратегии

```php
use Bitrix\Main\Loader;
use Local\FeatureFlag\HeaderStrategy;
use Sholokhov\Featureflag\ServiceProvider;

Loader::includeModule('sholokhov.featureflag');

ServiceProvider::getStrategyRegistry()
    ->register(new HeaderStrategy());
```

---

# Типовые сценарии использования

---

## Rollout новой CRM карточки

```php
if (Feature::isEnabled('crm.card.v2')) {
    $this->renderNewCard();
} else {
    $this->renderOldCard();
}
```

---

## Ограничение API

```php
Feature::require('api.partner-access');
```

---

## Временное отключение функционала

```php
if (Feature::isDisabled('search.v2')) {
    return;
}
```

---

## A/B тестирование

```php
if (Feature::any([
    'checkout.v2',
    'checkout.ab-test',
])) {
    $this->renderNewCheckout();
}
```

---

## Runtime-доступ для администраторов

```php
ServiceProvider::getRuleRegistry()
    ->register(new IsAdminRule());
```

---

## Rollout через HTTP-заголовок

```text
X-Feature-Token: beta
```

---

# Где что регистрировать

| Что | Где рекомендуется |
|---|---|
| Feature::register() | install.php, миграции |
| Runtime-правила | init.php |
| UI-стратегии | init.php |
| Bootstrap логика | bootstrap.php |

---

# Жизненный цикл feature flag

Рекомендуемый lifecycle:

1. Создание feature flag
2. Ограниченный rollout
3. Тестирование
4. Постепенное расширение аудитории
5. Полный rollout
6. Удаление старой логики
7. Удаление feature flag

---

# Рекомендации по использованию

## Используйте стабильные коды

Хорошо:

```text
crm.application.v2
catalog.fast-filter
checkout.new-payment
```

Плохо:

```text
new-feature
test
temp
```

---

## Всегда оставляйте fallback

```php
if (Feature::isEnabled('checkout.v2')) {
    $this->newCheckout();
} else {
    $this->oldCheckout();
}
```

---

## Не используйте feature flags как конфиг

Feature flag — это временный механизм rollout.

Не рекомендуется:
- хранить бизнес-настройки
- использовать как permanent configuration
- заменять ими настройки модуля

---

## Удаляйте устаревшие feature flags

После полного rollout:
- удаляйте старую ветку логики
- удаляйте флаг
- удаляйте runtime-правила

---

# Production-рекомендации

- выкатывайте новые функции через feature flags
- не удаляйте fallback до полного rollout
- используйте отдельные теги:
    - Release
    - Testing
    - Internal
    - Experimental

---

# Почему runtime-правила не хранятся в БД

Runtime-правила:
- могут зависеть от окружения
- могут использовать DI
- могут использовать сервисы
- могут содержать бизнес-логику

Поэтому они регистрируются в runtime.

# Ограничения текущей реализации

- runtime-правила не хранятся в БД
- нет встроенного rollout по процентам
- нет встроенного аудита изменений
- нет встроенных seed-механизмов

---

# Полный пример подключения

```php
use Bitrix\Main\Loader;
use Sholokhov\Featureflag\DTO\FeatureFlagPayload;
use Sholokhov\Featureflag\Feature;
use Sholokhov\Featureflag\ServiceProvider;
use Sholokhov\Featureflag\Rules\IsAdminRule;

Loader::includeModule('sholokhov.featureflag');

Feature::register(new FeatureFlagPayload(
    code: 'crm.application.v2',
    name: 'Новая CRM карточка',
    description: 'Новая версия CRM карточки',
    enabled: false,
));

ServiceProvider::getRuleRegistry()
    ->register(new IsAdminRule(
        supportedCodes: ['crm.application.v2']
    ));

if (Feature::isEnabled('crm.application.v2')) {
    $this->renderNewCard();
} else {
    $this->renderOldCard();
}
```

---

# Итог

`sholokhov.featureflag` — это production-ready механизм feature flags для 1C-Bitrix, позволяющий:

- безопасно выкатывать функционал
- ограничивать аудиторию
- управлять rollout
- подключать runtime-правила
- расширять систему собственными стратегиями
- минимизировать риски релиза
- контролировать lifecycle feature flags
- снижать технический долг
