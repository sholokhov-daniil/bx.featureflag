<?php

declare(strict_types=1);

namespace Sholokhov\Featureflag\Infrastructure\Persistence;

use Sholokhov\Featureflag\Domain\Entity\FeatureFlag;
use Sholokhov\Featureflag\FeatureInterface;
use Sholokhov\Featureflag\ORM\FeatureTable;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;

/**
 * Маппер доменной сущности фича-флага.
 *
 * Обеспечивает двустороннее преобразование данных между
 * доменной сущностью {@see FeatureInterface} и Bitrix ORM.
 *
 * Отвечает исключительно за преобразование данных.
 * Не выполняет запросы к базе данных, валидацию бизнес-правил
 * и управление жизненным циклом фича-флагов.
 */
final class FeatureFlagMapper
{
    /**
     * Преобразует ORM-сущность Bitrix в доменную сущность фича-флага.
     *
     * Извлекает значения полей ORM и создаёт независимый от инфраструктуры экземпляр {@see FeatureInterface}.
     *
     * @param EntityObject $entity ORM-сущность фича-флага.
     *
     * @return FeatureInterface
     * @throws ArgumentException
     * @throws SystemException
     */
    public function toDomain(EntityObject $entity): FeatureInterface
    {
        return new FeatureFlag(
            code: (string)$entity->get(FeatureTable::FIELD_CODE),
            name: (string)$entity->get(FeatureTable::FIELD_NAME),
            description: (string)$entity->get(FeatureTable::FIELD_DESCRIPTION),
            enabled: (bool)$entity->get(FeatureTable::FIELD_ENABLED),
            tagId: (int)$entity->get(FeatureTable::FIELD_TAG_ID),
            removePlanAt: $entity->get(FeatureTable::REMOVE_PLANNED_AT),
            strategies: (array)$entity->get(FeatureTable::FIELD_STRATEGIES),
        );
    }

    /**
     * Преобразует доменную сущность фича-флага в данные для Bitrix ORM.
     *
     * Формирует ассоциативный массив полей, используемый
     * при создании или обновлении записи через {@see FeatureTable}.
     *
     * Первичный ключ (CODE) не включается в результат,
     * поскольку обрабатывается репозиторием отдельно.
     *
     * @param FeatureInterface $flag Доменная сущность фича-флага.
     *
     * @return array<string, mixed>
     */
    public function toPersistence(FeatureInterface $flag): array
    {
        return [
            FeatureTable::FIELD_CODE => $flag->getCode(),
            FeatureTable::FIELD_NAME => $flag->getName(),
            FeatureTable::FIELD_DESCRIPTION => $flag->getDescription(),
            FeatureTable::FIELD_ENABLED => $flag->isEnabled(),
            FeatureTable::FIELD_TAG_ID => $flag->getTagId(),
            FeatureTable::REMOVE_PLANNED_AT => $flag->getRemovePlanAt(),
            FeatureTable::FIELD_STRATEGIES => $flag->getStrategies(),
        ];
    }
}