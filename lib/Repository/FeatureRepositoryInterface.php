<?php

namespace Sholokhov\Featureflag\Repository;

use Sholokhov\Featureflag\DTO\FeatureFlagPayload;
use Sholokhov\Featureflag\FeatureInterface;

use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;

/**
 * Хранилище зарегистрированных флагов в системе
 *
 */
interface FeatureRepositoryInterface
{
    /**
     * Возвращает флаг на основе символьного кода
     *
     * @param string $code Код флага
     * @return FeatureInterface|null
     */
    public function findByCode(string $code): ?FeatureInterface;

    /**
     * Создание нового флага
     *
     * @param FeatureFlagPayload $flag
     * @return AddResult
     */
    public function create(FeatureFlagPayload $flag): AddResult;

    /**
     * Обновление существующего флага
     *
     * @param FeatureFlagPayload $payload
     * @return UpdateResult
     */
    public function update(FeatureFlagPayload $payload): UpdateResult;

    /**
     * Сброс текущего кеша
     * @return void
     */
    public function clearCache(): void;
}