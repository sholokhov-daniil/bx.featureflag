<?php

namespace Sholokhov\Featureflag\Field\Normalizer;

/**
 * Нормализация списочных значений из UI и сохранённой конфигурации.
 */
final class ListNormalizer
{
    /**
     * Разбивает строку или массив на уникальный список непустых строк.
     *
     * @param mixed $value
     * @return string[]
     */
    public static function strings(mixed $value): array
    {
        if (is_array($value)) {
            $rawValues = $value;
        } elseif (is_string($value)) {
            $rawValues = preg_split('/[\s,;]+/', $value) ?: [];
        } else {
            $rawValues = [];
        }

        $result = [];
        foreach ($rawValues as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $result[$item] = $item;
            }
        }

        return array_values($result);
    }

    /**
     * Нормализует список положительных идентификаторов.
     *
     * Невалидные элементы сохраняются как строки, чтобы валидатор мог
     * показать пользователю конкретное ошибочное значение.
     *
     * @param mixed $value
     * @return array<int, int|string>
     */
    public static function positiveIntegers(mixed $value): array
    {
        $result = [];

        foreach (self::strings($value) as $item) {
            if (self::isPositiveInteger($item)) {
                $id = (int)$item;
                $result[$id] = $id;
                continue;
            }

            $result[$item] = $item;
        }

        return array_values($result);
    }

    /**
     * Преобразует сохранённый список в многострочное значение для UI.
     *
     * @param mixed $value
     * @return string
     */
    public static function denormalize(mixed $value): string
    {
        if (is_array($value)) {
            return implode("\n", array_map(static fn(mixed $item): string => (string)$item, $value));
        }

        if ($value === null) {
            return '';
        }

        return (string)$value;
    }

    /**
     * Проверяет, что значение является положительным целым числом.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isPositiveInteger(mixed $value): bool
    {
        if (is_int($value)) {
            return $value > 0;
        }

        return is_string($value) && preg_match('/^[1-9]\d*$/', $value) === 1;
    }

    /**
     * Закрывает создание utility-класса.
     *
     * @return void
     */
    private function __construct()
    {
    }
}
