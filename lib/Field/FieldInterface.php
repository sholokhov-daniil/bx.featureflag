<?php

namespace Sholokhov\Featureflag\Field;

use Closure;

/**
 * Описание свойства конфигурации стратегии
 */
interface FieldInterface
{
    /**
     * Уникальный идентификатор свойства в рамках стратегии
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Хранимый тип данных стратегии
     *
     * @return FieldType
     */
    public function getType(): FieldType;

    /**
     * Название свойства
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Поле является обязательным
     *
     * @return bool
     */
    public function isRequired(): bool;

    /**
     * Преобразование свойства в справочник
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Указание нормализатора данных - перед сохранением данных
     *
     * @param Closure $normalizer
     * @return self
     */
    public function setNormalizer(Closure $normalizer): self;

    /**
     * Указание денормализатор данных - как прочитать данные
     *
     * @param Closure $denormalizer
     * @return self
     */
    public function setDenormalizer(Closure $denormalizer): self;
}