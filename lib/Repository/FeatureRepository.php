<?php

namespace Sholokhov\Featureflag\Repository;

use Throwable;

use Sholokhov\Featureflag\DTO\FlagInfo;
use Sholokhov\Featureflag\ServiceProvider;
use Sholokhov\Featureflag\FeatureInterface;
use Sholokhov\Featureflag\ORM\FeatureTable;
use Sholokhov\Featureflag\ORM\FeatureTagTable;

use Bitrix\Main\Error;
use Bitrix\Main\DB\DuplicateEntryException;
use Bitrix\Main\EventManager;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Data\AddResult;

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
     * Добавляет запись в ORM-таблицу фича-флагов на основе DTO {@see FlagInfo}.
     *
     * @param FlagInfo $flag DTO с данными создаваемого флага
     *
     * @return AddResult Результат добавления записи
     *
     */
    public function create(FlagInfo $flag): AddResult
    {
        try {
            return FeatureTable::add([
                FeatureTable::FIELD_CODE => $flag->code,
                FeatureTable::FIELD_NAME => $flag->name,
                FeatureTable::FIELD_DESCRIPTION => $flag->description,
                FeatureTable::FIELD_ENABLED => $flag->enabled,
            ]);
        } catch (DuplicateEntryException) {
            return (new AddResult())
                ->addError(new Error('Флаг с таким кодом уже существует', 'DUPLICATE_CODE', [
                    'field' => 'code',
                ]));
        } catch (Throwable $exception) {
            return (new AddResult())
                ->addError(new Error('Ошибка при создании фича-флага: ' . $exception->getMessage()));
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
            $this->ensureSchema();

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
        } catch (Throwable $exception) {
            AddMessage2Log('Ошибка загрузки флагов: ' . $exception->getMessage());
        }
    }

    /**
     * Доинициализирует новые поля при обновлении уже установленного модуля.
     */
    private function ensureSchema(): void
    {
        $connection = FeatureTable::getEntity()->getConnection();
        $tableName = FeatureTable::getTableName();

        if (!$connection->isTableExists($tableName)) {
            FeatureTable::getEntity()->createDbTable();
            return;
        }

        $fields = array_change_key_case($connection->getTableFields($tableName), CASE_UPPER);

        $sqlHelper = $connection->getSqlHelper();
        $tableSql = $sqlHelper->quote($tableName);

        if (!isset($fields[FeatureTable::FIELD_TAG_ID])) {
            $fieldSql = $sqlHelper->quote(FeatureTable::FIELD_TAG_ID);
            $connection->queryExecute("ALTER TABLE {$tableSql} ADD {$fieldSql} int(11) NULL");
        }

        if (!isset($fields[FeatureTable::FIELD_STRATEGIES])) {
            $fieldSql = $sqlHelper->quote(FeatureTable::FIELD_STRATEGIES);
            $connection->queryExecute("ALTER TABLE {$tableSql} ADD {$fieldSql} text NULL");
        }

        $tagTableName = FeatureTagTable::getTableName();
        if (!$connection->isTableExists($tagTableName)) {
            FeatureTagTable::getEntity()->createDbTable();
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
            function(Event $event) {
                /** @var EntityObject $entity */
                $entity = $event->getParameter('object');
                $this->cache[$entity->get(FeatureTable::FIELD_CODE)] = $entity;
            }

        );
    }
}
