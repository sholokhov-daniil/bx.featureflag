<?php

namespace Sholokhov\Featureflag;

/**
 * Доступные методы флага
 */
interface FlagInterface
{
    /**
     * Активность флага
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Код флага
     *
     * @return string
     */
    public function getCode(): string;
}