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

## Примеры использования

```php
use Sholokhov\Featureflag\Feature;

// Фича активна
if (Feature::isEnabled('crm.application.v2')) {}

// Фича не активна
if (Feature::isDisabled('catalog.fast-filter')) {}

// Вызов метода в зависимости от статуса фичи
Feature::when(
    'checkout.v2',
    enabled: fn() => $this->renderNewCheckout(),
    disabled: fn() => $this->renderOldCheckout(),
);

// Хотя бы одна фича активна
if (Feature::any([
    'search.v2',
    'search.ab-test',
])) {}

// Все фичи активны
if (Feature::all([
    'checkout.v2',
    'payment.new-form',
])) {}
```

## Пример использование в js

```js
// Если модуль sholokhov.featureflag уже инициализирован, то js расширение уже подключено
await BX.loadExt('sholokhov.featureflag.feature');

// Фича активна
BX.Sholokhov.FeatureFlag.Feature.isEnabled('crm.application.v2'); // boolean

// Фича не активна
BX.Sholokhov.FeatureFlag.Feature.isDisabled('catalog.fast-filter'); // boolean

// Вызов метода в зависимости от статуса фичи
BX.Sholokhov.FeatureFlag.Feature.when(
    'checkout.v2',
    function() {
        // enabled
    },
    function() {
        // disabled
    }
); // void

// Хотя бы одна фича активна
BX.Sholokhov.FeatureFlag.Feature.any(["crm.application.v2", "catalog.fast-filter"]) // boolean

// Все фичи активны
BX.Sholokhov.FeatureFlag.Feature.all(["crm.application.v2", "catalog.fast-filter"]) // boolean
```
