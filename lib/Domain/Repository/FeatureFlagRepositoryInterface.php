<?php

declare(strict_types=1);

namespace Sholokhov\Featureflag\Domain\Repository;

use Sholokhov\Featureflag\FeatureInterface;

use Bitrix\Main\Result;

/**
 * Контракт репозитория фича-флагов.
 *
 * Определяет операции получения, сохранения и удаления
 * доменных сущностей фича-флагов.
 *
 * Не зависит от конкретного механизма хранения данных.
 * Реализация контракта находится в Infrastructure.
 */
interface FeatureFlagRepositoryInterface
{
    /**
     * Возвращает фича-флаг по его уникальному символьному коду.
     *
     * @param string $code Символьный код флага.
     *
     * @return FeatureInterface|null Найденный флаг или null, если он не существует.
     */
    public function findByCode(string $code): ?FeatureInterface;

    /**
     * Сохраняет фича-флаг в хранилище.
     *
     * Создаёт новую запись или обновляет существующую
     * в зависимости от наличия флага в хранилище.
     *
     * @param FeatureInterface $flag Доменная сущность фича-флага.
     *
     * @return Result
     */
    public function save(FeatureInterface $flag): Result;

    /**
     * Удаляет фича-флаг по его уникальному символьному коду.
     *
     * @param string $code Символьный код удаляемого флага.
     *
     * @return Result
     */
    public function deleteByCode(string $code): Result;

    /**
     * Производит создание нового фича-флага
     *
     * @param FeatureInterface $flag
     *
     * @return Result
     */
    public function create(FeatureInterface $flag): Result;

    /**
     * Обновляет существующую информацию фичи-флага
     *
     * @param FeatureInterface $flag
     *
     * @return Result
     */
    public function update(FeatureInterface $flag): Result;
}