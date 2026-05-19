<?php

namespace Sholokhov\Featureflag\Repository;

use Sholokhov\Featureflag\DTO\FlagInfo;
use Sholokhov\Featureflag\FeatureInterface;

use Bitrix\Main\ORM\Data\AddResult;

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
     * @param FlagInfo $flag
     * @return AddResult
     */
    public function create(FlagInfo $flag): AddResult;

    /**
     * Сброс текущего кеша
     * @return void
     */
    public function clearCache(): void;
}