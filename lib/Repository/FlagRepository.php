<?php

namespace Sholokhov\Featureflag\Repository;

use Exception;
use Throwable;

use Sholokhov\Featureflag\FlagInterface;
use Sholokhov\Featureflag\ServiceProvider;

use Bitrix\Main\ORM\Data\AddResult;
use Sholokhov\Featureflag\DTO\FlagInfo;
use Sholokhov\Featureflag\ORM\FlagTable;

/**
 * Репозиторий фича-флагов
 *
 * Отвечает за получение и создание флагов.
 * Для чтения использует внутренний runtime-кеш, чтобы не выполнять запрос
 * к базе данных при каждой проверке активности фичи.
 *
 * При первом обращении загружает все флаги из ORM-таблицы {@see FlagTable}
 * и преобразует их в доменные объекты {@see FlagInterface}.
 */
class FlagRepository implements FlagRepositoryInterface
{
    /**
     * Runtime-кеш загруженных флагов
     *
     * Ключ массива — символьный код фичи.
     *
     * @var array<string, FlagInterface>
     */
    private array $cache = [];

    /**
     * Флаг состояния загрузки runtime-кеша
     *
     * @var bool
     */
    private bool $loaded = false;

    /**
     * Возвращает фича-флаг по символьному коду
     *
     * При первом вызове выполняет загрузку всех флагов в runtime-кеш.
     * Если флаг не найден или загрузка завершилась ошибкой, возвращает null.
     *
     * @param string $code Символьный код фичи
     *
     * @return FlagInterface|null Объект флага или null, если флаг не найден
     */
    public function findByCode(string $code): ?FlagInterface
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
     * @throws Exception При ошибке добавления записи через ORM
     */
    public function create(FlagInfo $flag): AddResult
    {
        return FlagTable::add([
            FlagTable::FIELD_CODE => $flag->code,
            FlagTable::FIELD_NAME => $flag->name,
            FlagTable::FIELD_DESCRIPTION => $flag->description,
            FlagTable::FIELD_ENABLED => $flag->enabled,
        ]);
    }

    /**
     * Загружает все фича-флаги в runtime-кеш
     *
     * Получает записи из ORM-таблицы {@see FlagTable}, создаёт доменные объекты
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
            $this->cache = [];
            $factory = ServiceProvider::getFlagFactory();

            $iterator = FlagTable::query()
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
}