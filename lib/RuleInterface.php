<?php

namespace Sholokhov\Featureflag;

/**
 * Пользовательское правило проверки активности флага
 */
interface RuleInterface
{
    /**
     * Проверка принадлежности прафила флагу
     *
     * @param string $code
     * @return bool
     */
    public function isSupported(string $code): bool;

    /**
     * Проверка активности флага
     *
     * @param string $code
     * @return bool
     */
    public function isEnabled(string $code): bool;
}