<?php

namespace Sholokhov\Featureflag\Repository;

use Sholokhov\Featureflag\FlagInterface;

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
     * @return FlagInterface|null
     */
    public function getByCode(string $code): ?FlagInterface;
}