<?php

namespace Sholokhov\Featureflag;

use Bitrix\Main\Result;

/**
 * Доступные методы флага
 */
interface FeatureInterface
{
    /**
     * Активность флага
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Доступность флага в публичном JS API.
     *
     * @return bool
     */
    public function isAvailableInJs(): bool;

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

    /**
     * Отключение фичи
     *
     * @return Result
     */
    public function disabled(): Result;

    /**
     * Включение вичи
     *
     * @return Result
     */
    public function enabled(): Result;

    /**
     * Удаление флага
     *
     * @return Result
     */
    public function delete(): Result;
}