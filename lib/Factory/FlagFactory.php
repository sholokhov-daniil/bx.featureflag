<?php

namespace Sholokhov\Featureflag\Factory;

use Sholokhov\Featureflag\Flag;
use Sholokhov\Featureflag\FlagInterface;
use Sholokhov\Featureflag\ORM\FlagTable;
use Sholokhov\Featureflag\RuleInterface;
use Sholokhov\Featureflag\ServiceProvider;

use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ORM\Objectify\EntityObject;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Фабрика создания объектов фича-флагов
 *
 * Преобразует ORM-сущность Bitrix (HL-блок / таблица) в доменный объект {@see FlagInterface}.
 * Также обогащает флаг набором правил, зарегистрированных в системе.
 */
class FlagFactory implements FlagFactoryInterface
{
    /**
     * Создаёт объект фича-флага на основе ORM-сущности
     *
     * Извлекает данные из {@see EntityObject} и формирует доменную модель {@see Flag}.
     * Дополнительно подтягивает все зарегистрированные правила, применимые к фиче.
     *
     * @param EntityObject $entity ORM-сущность Bitrix
     *
     * @return FlagInterface Экземпляр фича-флага
     *
     * @throws NotFoundExceptionInterface Если не удалось получить зависимости из контейнера
     * @throws ObjectNotFoundException    Если реестр правил не найден
     * @throws ArgumentException          При некорректных аргументах ORM
     * @throws SystemException            При ошибках ORM/ядра Bitrix
     *
     */
    public function createFromEntity(EntityObject $entity): FlagInterface
    {
        $code = (string)$entity->get(FlagTable::FIELD_CODE);

        return new Flag(
            code: $code,
            name: (string)$entity->get(FlagTable::FIELD_NAME),
            description: (string)$entity->get(FlagTable::FIELD_DESCRIPTION),
            enabled: (string)$entity->get(FlagTable::FIELD_ENABLED),
            rules: $this->getRules($code),
        );
    }

    /**
     * Возвращает правила, применимые к указанному фича-флагу
     *
     * Получает список правил из {@see ServiceProvider} и фильтрует их
     * по коду фичи через {@see RuleInterface}.
     *
     * @param string $code Символьный код фичи
     *
     * @return RuleInterface[] Список правил для фичи
     *
     * @throws ObjectNotFoundException    Если реестр правил не найден
     * @throws NotFoundExceptionInterface Если не удалось получить сервис из контейнера
     */
    private function getRules(string $code): array
    {
        return ServiceProvider::getRuleRegistry()->getByCode($code);
    }
}