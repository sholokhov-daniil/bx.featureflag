<?php

namespace Sholokhov\Featureflag\Factory;

use Sholokhov\Featureflag\Feature;
use Sholokhov\Featureflag\FeatureInterface;
use Sholokhov\Featureflag\FeatureFlag;
use Sholokhov\Featureflag\ORM\FeatureTable;
use Sholokhov\Featureflag\ORM\FeatureTagTable;
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
     * Runtime-кеш стратегий тегов.
     *
     * @var array<int, array<int, array{type: string, config: array<string, mixed>}>>
     */
    private array $tagStrategiesCache = [];

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
     * @param string $code Символьный код фичи
     * @param EntityObject $entity ORM-сущность фича-флага
     *
     * @return RuleInterface[] Список правил для фичи
     *
     * @throws ObjectNotFoundException    Если реестр правил не найден
     * @throws NotFoundExceptionInterface Если не удалось получить сервис из контейнера
     */
    private function getRules(string $code, EntityObject $entity): array
    {
        $rules = ServiceProvider::getRuleRegistry()->getByCode($code);
        $strategies = [
            ...$this->getTagStrategies($entity),
            ...$this->decodeStrategies((string)$entity->get(FeatureTable::FIELD_STRATEGIES)),
        ];

        if ($strategies !== []) {
            $rules[] = new StoredStrategyRule(
                items: $strategies,
                registry: ServiceProvider::getStrategyRegistry(),
            );
        }

        return $rules;
    }

    /**
     * Возвращает стратегии доступа, настроенные на теге флага.
     *
     * @param EntityObject $entity ORM-сущность фича-флага
     * @return array<int, array{type: string, config: array<string, mixed>}>
     *
     * @throws ArgumentException
     * @throws SystemException
     */
    private function getTagStrategies(EntityObject $entity): array
    {
        $tagId = (int)$entity->get(FeatureTable::FIELD_TAG_ID);
        if ($tagId <= 0) {
            return [];
        }

        if (array_key_exists($tagId, $this->tagStrategiesCache)) {
            return $this->tagStrategiesCache[$tagId];
        }

        try {
            $row = FeatureTagTable::query()
                ->setSelect([
                    FeatureTagTable::FIELD_ID,
                    FeatureTagTable::FIELD_STRATEGIES,
                ])
                ->where(FeatureTagTable::FIELD_ID, $tagId)
                ->fetch();
        } catch (\Throwable) {
            $row = false;
        }

        if ($row === false) {
            $this->tagStrategiesCache[$tagId] = [];
            return [];
        }

        $this->tagStrategiesCache[$tagId] = $this->decodeStrategies((string)($row[FeatureTagTable::FIELD_STRATEGIES] ?? ''));
        return $this->tagStrategiesCache[$tagId];
    }

    /**
     * Декодирует JSON-конфигурацию стратегий.
     *
     * @param string $value JSON-строка из ORM-поля
     * @return array<int, array{type: string, config: array<string, mixed>}>
     */
    private function decodeStrategies(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        if (!is_array($decoded) || !array_is_list($decoded)) {
            return [];
        }

        $result = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = trim((string)($item['type'] ?? ''));
            $config = $item['config'] ?? [];

            if ($type === '' || !is_array($config)) {
                continue;
            }

            $result[] = [
                'type' => $type,
                'config' => $config,
            ];
        }

        return $result;
    }
}
