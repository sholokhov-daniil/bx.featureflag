<?php

namespace Sholokhov\Featureflag\Repository;

use Bitrix\Main\ORM\Data\AddResult;
use Sholokhov\Featureflag\DTO\FlagInfo;
use Sholokhov\Featureflag\FeatureInterface;

/**
 * Хранилище зарегистрированных флагов в системе
 *
 */
interface FlagRepositoryInterface
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
}