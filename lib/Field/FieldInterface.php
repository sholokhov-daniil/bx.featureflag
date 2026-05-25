<?php

namespace Sholokhov\Featureflag\Field;

use Bitrix\Main\Result;
use Closure;
use Sholokhov\Featureflag\Field\Validator\FieldValidatorInterface;

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
     * Нормализация значения перед валидацией и сохранением
     *
     * @param mixed $value
     * @return mixed
     */
    public function normalizeValue(mixed $value): mixed;

    /**
     * Денормализация значения перед отдачей в UI
     *
     * @param mixed $value
     * @return mixed
     */
    public function denormalizeValue(mixed $value): mixed;

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

    /**
     * Добавление валидатора значения свойства
     *
     * @param FieldValidatorInterface $validator
     * @return self
     */
    public function addValidator(FieldValidatorInterface $validator): self;

    /**
     * Валидация значения на основе конфигурации свойства
     *
     * @param mixed $value
     * @return Result
     */
    public function validateValue(mixed $value): Result;
}
