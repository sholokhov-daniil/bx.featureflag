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
     * @param FlagInterface $flag
     * @return bool
     */
    public function isSupported(FlagInterface $flag): bool;

    /**
     * Проверка активности флага
     *
     * @param object $context
     * @return bool
     */
    public function isEnabled(object $context): bool;
}