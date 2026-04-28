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
    public function isEnabled(): bool;

    /**
     * Название флага
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Подробное описание флага
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Код флага
     *
     * @return string
     */
    public function getCode(): string;
}