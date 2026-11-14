<?php

namespace Sholokhov\Featureflag;

use Bitrix\Main\Type\DateTime;

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
     * @return void
     */
    public function disable(): void;

    /**
     * Включение фичи
     *
     * @return void
     */
    public function enable(): void;

    /**
     * Возвращает ID тега к которому принадлежит  фича-флаг
     *
     * @return int|null
     */
    public function getTagId(): ?int;

    /**
     * Плановая дата удаления фича-флага
     *
     * @return DateTime|null
     */
    public function getRemovePlanAt(): ?DateTime;

    /**
     * Все стратегии активации фичи-флага
     *
     * @return array
     */
    public function getStrategies(): array;
}