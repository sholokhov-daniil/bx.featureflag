<?php

namespace Sholokhov\Featureflag;

use Bitrix\Main\Diag\Debug;
use Sholokhov\Featureflag\ORM\FeatureTable;

use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Objectify\EntityObject;

/**
 * Доменная модель фича-флага
 *
 * Представляет фича-флаг как объект доменной логики.
 * Инкапсулирует:
 * - данные ORM-сущности {@see EntityObject}
 * - пользовательские правила активации {@see RuleInterface}
 *
 * Флаг считается активным, если:
 * 1. Поле ENABLED = true
 * 2. Все применимые правила возвращают true
 *
 * Класс является immutable (readonly), за исключением изменения состояния
 * через методы enabled()/disabled(), которые изменяют ORM-сущность.
 */
final readonly class FeatureFlag implements FeatureInterface
{
    /**
     * @param EntityObject    $entity ORM-сущность флага
     * @param RuleInterface[] $rules  Список правил проверки активности
     */
    public function __construct(
        private EntityObject $entity,
        private array $rules
    ) {
    }

    /**
     * Проверяет, активен ли фича-флаг
     *
     * Алгоритм:
     * 1. Проверяет базовое состояние (FIELD_ENABLED)
     * 2. Применяет все пользовательские правила
     *
     * При первом false — выполнение прерывается.
     *
     * @return bool true, если фича активна
     *
     * @throws ArgumentException При ошибке доступа к полям ORM
     * @throws SystemException   При ошибках ORM
     */
    public function isEnabled(): bool
    {
        if (!$this->entity->get(FeatureTable::FIELD_ENABLED)) {
            return false;
        }

        $code = $this->getCode();

        foreach ($this->rules as $rule) {
            if (!$rule->isEnabled($code)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Проверяет, можно ли раскрывать фича-флаг в публичном JS API.
     *
     * @return bool true, если фича доступна для JS API
     *
     * @throws ArgumentException
     * @throws SystemException
     */
    public function isAvailableInJs(): bool
    {
        return (bool)$this->entity->get(FeatureTable::FIELD_AVAILABLE_IN_JS);
    }

    /**
     * Возвращает символьный код фича-флага
     *
     * @return string
     *
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getCode(): string
    {
        return (string)$this->entity->get(FeatureTable::FIELD_CODE);
    }

    /**
     * Возвращает название фича-флага
     *
     * @return string
     *
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getName(): string
    {
        return (string)$this->entity->get(FeatureTable::FIELD_NAME);
    }

    /**
     * Возвращает описание фича-флага
     *
     * @return string
     *
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getDescription(): string
    {
        return (string)$this->entity->get(FeatureTable::FIELD_DESCRIPTION);
    }

    /**
     * Отключает фича-флаг
     *
     * Изменяет поле ENABLED на false и сохраняет сущность.
     *
     * @return Result Результат сохранения
     *
     * @throws ArgumentException
     * @throws SystemException
     */
    public function disabled(): Result
    {
        $this->entity->set(FeatureTable::FIELD_ENABLED, false);
        return $this->entity->save();
    }

    /**
     * Включает фича-флаг
     *
     * Изменяет поле ENABLED на true и сохраняет сущность.
     *
     * @return Result Результат сохранения
     *
     * @throws ArgumentException
     * @throws SystemException
     */
    public function enabled(): Result
    {
        $this->entity->set(FeatureTable::FIELD_ENABLED, true);
        return $this->entity->save();
    }

    /**
     * Удаление фичи флага
     *
     * @return Result
     * @throws ArgumentException
     * @throws SystemException
     */
    public function delete(): Result
    {
        return $this->entity->delete();
    }
}