<?php

namespace Sholokhov\Featureflag\Repository;

use Throwable;

use Sholokhov\Featureflag\DTO\FeatureFlagPayload;
use Sholokhov\Featureflag\ServiceProvider;
use Sholokhov\Featureflag\FeatureInterface;
use Sholokhov\Featureflag\ORM\FeatureTable;
use Sholokhov\Featureflag\Service\FeatureFlagSchemaManager;

use Bitrix\Main\Error;
use Bitrix\Main\Type\Date;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\DB\DuplicateEntryException;

/**
 * Репозиторий фича-флагов
 *
 * Отвечает за получение и создание флагов.
 * Для чтения использует внутренний runtime-кеш, чтобы не выполнять запрос
 * к базе данных при каждой проверке активности фичи.
 *
 * При первом обращении загружает все флаги из ORM-таблицы {@see FeatureTable}
 * и преобразует их в доменные объекты {@see FeatureInterface}.
 */
class FeatureRepository implements FeatureRepositoryInterface
{
    /**
     * Runtime-кеш загруженных флагов
     *
     * Ключ массива — символьный код фичи.
     *
     * @var array<string, FeatureInterface>
     */
    private array $cache = [];

    /**
     * Флаг состояния загрузки runtime-кеша
     *
     * @var bool
     */
    private bool $loaded = false;

    public function __construct()
    {
        FeatureFlagSchemaManager::ensureActualSchema();
        $this->registerEvents();
    }

    /**
     * Возвращает фича-флаг по символьному коду
     *
     * При первом вызове выполняет загрузку всех флагов в runtime-кеш.
     * Если флаг не найден или загрузка завершилась ошибкой, возвращает null.
     *
     * @param string $code Символьный код фичи
     *
     * @return FeatureInterface|null Объект флага или null, если флаг не найден
     */
    public function findByCode(string $code): ?FeatureInterface
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->cache[$code] ?? null;
    }

    /**
     * Создаёт новый фича-флаг
     *
     * Добавляет запись в ORM-таблицу фича-флагов на основе DTO {@see FeatureFlagPayload}.
     *
     * @param FeatureFlagPayload $flag DTO с данными создаваемого флага
     *
     * @return AddResult Результат добавления записи
     *
     */
    public function create(FeatureFlagPayload $flag): AddResult
    {
        $flagInfo = clone $flag;
        $flagInfo->code = trim($flagInfo->code);
        $flagInfo->name = trim($flagInfo->name);
        $flagInfo->description = trim($flagInfo->description);

        try {
            $validate = $flagInfo->validate();
            if (!$validate->isSuccess()) {
                return (new AddResult())->addErrors($validate->getErrors());
            }

            return FeatureTable::add([
                FeatureTable::FIELD_CODE => $flagInfo->code,
                FeatureTable::FIELD_NAME => $flagInfo->name,
                FeatureTable::FIELD_DESCRIPTION => $flagInfo->description,
                FeatureTable::FIELD_ENABLED => $flagInfo->enabled,
                FeatureTable::FIELD_AVAILABLE_IN_JS => $flagInfo->availableInJs,
                FeatureTable::FIELD_TAG_ID => $flagInfo->tagId,
                FeatureTable::REMOVE_PLANNED_AT => $flagInfo->removePlannedAt ? new Date($flagInfo->removePlannedAt,
                    'd.m.Y') : null,
                FeatureTable::FIELD_STRATEGIES => $flagInfo->strategies,

            ]);
        } catch (DuplicateEntryException) {
            return (new AddResult)
                ->addError(new Error('Флаг с таким кодом уже существует', 'DUPLICATE_CODE', [
                    'field' => 'code',
                ]));
        } catch (Throwable $exception) {
            return (new AddResult())
                ->addError(new Error('Ошибка при создании фича-флага: ' . $exception->getMessage()));
        }
    }

    /**
     * Обновление существующего флага
     *
     * @param FeatureFlagPayload $payload
     *
     * @return UpdateResult
     */
    public function update(FeatureFlagPayload $payload): UpdateResult
    {
        $flagInfo = clone $payload;
        $flagInfo->code = trim($flagInfo->code);
        $flagInfo->name = trim($flagInfo->name);
        $flagInfo->description = trim($flagInfo->description);


        try {
            $validate = $flagInfo->validate();
            if (!$validate->isSuccess()) {
                return (new UpdateResult())->addErrors($validate->getErrors());
            }

            return FeatureTable::update($flagInfo->code, [
                FeatureTable::FIELD_NAME => $flagInfo->name,
                FeatureTable::FIELD_DESCRIPTION => $flagInfo->description,
                FeatureTable::FIELD_ENABLED => $flagInfo->enabled,
                FeatureTable::FIELD_AVAILABLE_IN_JS => $flagInfo->availableInJs,
                FeatureTable::FIELD_TAG_ID => $flagInfo->tagId,
                FeatureTable::REMOVE_PLANNED_AT => $flagInfo->removePlannedAt ? new Date($flagInfo->removePlannedAt, 'd.m.Y') : null,
                FeatureTable::FIELD_STRATEGIES => $flagInfo->strategies,

            ]);
        } catch (Throwable $exception) {
            return (new UpdateResult)
                ->addError(new Error('Ошибка при обновлении фича-флага: ' . $exception->getMessage()));
        }
    }

    /**
     * Очистка кеша
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->load();
    }

    /**
     * Загружает все фича-флаги в runtime-кеш
     *
     * Получает записи из ORM-таблицы {@see FeatureTable}, создаёт доменные объекты
     * через фабрику флагов и сохраняет их во внутренний кеш по символьному коду.
     *
     * Ошибки загрузки не пробрасываются наружу, а записываются в лог Bitrix.
     * При ошибке кеш останется пустым.
     *
     * @return void
     */
    private function load(): void
    {
        try {
            FeatureFlagSchemaManager::ensureActualSchema();
            $this->cache = [];
            $factory = ServiceProvider::getFeatureFactory();

            $iterator = FeatureTable::query()
                ->setSelect(['*'])
                ->setCacheTtl(3600000)
                ->exec();

            while ($entity = $iterator->fetchObject()) {
                $flag = $factory->createFromEntity($entity);
                $this->cache[$flag->getCode()] = $flag;
            }

            $this->loaded = true;
        } catch (Throwable $exception) {
            AddMessage2Log('Ошибка загрузки флагов: ' . $exception->getMessage());
        }
    }

    /**
     * Регистрация соыбтий обновления хранилища
     *
     * @return void
     */
    private function registerEvents(): void
    {
        EventManager::getInstance()->addEventHandler(
            '',
            '\Sholokhov\Featureflag\ORM\Feature::OnAfterDelete',
            function (Event $event) {
                /** @var EntityObject $entity */
                $entity = $event->getParameter('object');
                $this->cache[$entity->get(FeatureTable::FIELD_CODE)] = $entity;
            }
        );
    }
}
