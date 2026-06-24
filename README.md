# Feature Flag для разработчиков 1С-Битрикс и Bitrix24

Модуль позволяет безопасно включать и отключать новую функциональность без деплоя кода, ограничивать доступ к фичам по условиям и контролировать жизненный цикл временных feature flags.

---

## Зачем нужен

В Bitrix-проектах часто нужно выкатывать новую функциональность постепенно:

- включить новую страницу только для администраторов;
- открыть функционал только для конкретных пользователей;
- протестировать новую версию компонента на одном сайте;
- включить фичу только для определённых IP;
- быстро отключить проблемный функционал без отката релиза;
- не забыть удалить временный feature flag после завершения rollout.

Модуль решает эту задачу через административный интерфейс и простой runtime API.

---

## Возможности

- Управление feature flags через административный интерфейс Bitrix.
- Runtime-проверка активности фичи из PHP.
- Runtime-проверка активности фичи из JavaScript.
- Глобальное включение / отключение фичи.
- Стратегии доступа к фичам.
- Runtime-правила.
- Хранение флагов в ORM-таблице.
- Поддержка тегов для группировки фич.
- Плановая дата удаления feature flag.
- Подсветка просроченных feature flags.
- Подсветка флагов, которые должны быть удалены сегодня.
- Fallback-safe поведение: если флаг не найден или произошла ошибка, фича считается выключенной.

---

## Встроенные стратегии

Модуль поддерживает стратегии ограничения доступа:

- по пользователям;
- по группам пользователей;
- по IP-адресам;
- по диапазону IP;
- по SITE_ID.

Стратегии можно использовать для постепенного rollout новой функциональности.

Примеры сценариев:

- включить новую карточку CRM только для группы тестировщиков;
- включить новую страницу только на сайте `s1`;
- открыть функционал только из корпоративной сети;
- протестировать фичу только на одном пользователе.

---

[![Documentation](https://img.shields.io/badge/documentation-50514F?style=for-the-badge&logo=readthedocs&logoColor=white)](https://github.com/sholokhov-daniil/bx.featureflag/wiki)
[![Telegram](https://img.shields.io/badge/sholokhov22-50514F?style=for-the-badge&logo=telegram&logoColor=white)](https://t.me/sholokhov22)
[![Email](https://img.shields.io/badge/sholokhovdaniil%40yandex.ru-50514F?style=for-the-badge&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABwAAAAcCAMAAABF0y+mAAAAYFBMVEX4YEr4X0n4XEX4VTz4Uzn4WkH5hnj8v7j91tH94N34ZVD+5uP////+8vD/9/b9zsj7uLD+7+393tr//v75cl/4bFn4UDX7ppv5gHH8wrv3TTH8xsD3Rib92dX4WkP4Z1PMr9nAAAAAnklEQVR4AcTPNQLDQBAEwUNhW8z4/1caM2kv9qS1qP4bbax171jJfBQn6TuJuZnJcn55eH0xrR5QlFUM9Q19BY12NmsFdDl0RulewqEFr0P4AB1Cm8BoA2giaCcro3IzMC4yarsCjYxKbzusmYxHBIULjPUFRF5Gt8LqtIhHBA+jdc8ddVbAdKgPPo4Lqqw/s+NTdZ6n8InWr8HpgQQAHnwKoF6Sk9YAAAAASUVORK5CYII=)](mailto:sholokhovdaniil@yandex.ru) 
