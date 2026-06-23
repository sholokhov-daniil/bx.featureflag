<?php

namespace Sholokhov\Featureflag\Factory;

use Sholokhov\Featureflag\Feature;
use Sholokhov\Featureflag\FeatureInterface;
use Sholokhov\Featureflag\FeatureFlag;
use Sholokhov\Featureflag\ORM\FeatureTable;
use Sholokhov\Featureflag\RuleInterface;
use Sholokhov\Featureflag\ServiceProvider;
use Sholokhov\Featureflag\Strategy\StoredStrategyRule;

use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ORM\Objectify\EntityObject;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Фабрика создания объектов фича-флагов
 *
 * Преобразует ORM-сущность Bitrix (HL-блок / таблица) в доменный объект {@see FeatureInterface}.
 * Также обогащает флаг набором правил, зарегистрированных в системе.
 */
class FeatureFactory implements FeatureFactoryInterface
{
    /**
     * Создаёт объект фича-флага на основе ORM-сущности
     *
     * Извлекает данные из {@see EntityObject} и формирует доменную модель {@see Feature}.
     * Дополнительно подтягивает все зарегистрированные правила, применимые к фиче.
     *
     * @param EntityObject $entity ORM-сущность Bitrix
     *
     * @return FeatureInterface Экземпляр фича-флага
     *
     * @throws NotFoundExceptionInterface Если не удалось получить зависимости из контейнера
     * @throws ObjectNotFoundException    Если реестр правил не найден
     * @throws ArgumentException          При некорректных аргументах ORM
     * @throws SystemException            При ошибках ORM/ядра Bitrix
     *
     */
    public function createFromEntity(EntityObject $entity): FeatureInterface
    {
        $code = (string)$entity->get(FeatureTable::FIELD_CODE);

        return new FeatureFlag(
            entity: $entity,
            rules: $this->getRules($code, $entity),
        );
    }

    /**
     * Возвращает правила, применимые к указанному фича-флагу
     *
     * Получает список правил из {@see ServiceProvider} и фильтрует их
     * по коду фичи через {@see RuleInterface}.
     *
     * @param string       $code   Символьный код фичи
     * @param EntityObject $entity ORM-сущность фича-флага
     *
     * @return RuleInterface[] Список правил для фичи
     *
     * @throws ArgumentException
     * @throws NotFoundExceptionInterface Если не удалось получить сервис из контейнера
     * @throws ObjectNotFoundException Если реестр правил не найден
     * @throws SystemException
     */
    private function getRules(string $code, EntityObject $entity): array
    {
        $rules = ServiceProvider::getRuleRegistry()->getByCode($code);
        $strategies = (array)$entity->get(FeatureTable::FIELD_STRATEGIES);

        if ($strategies !== []) {
            $rules[] = new StoredStrategyRule(
                items: $strategies,
                registry: ServiceProvider::getStrategyRegistry(),
            );
        }

        return $rules;
    }
}
