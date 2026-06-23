<?php

namespace Sholokhov\Featureflag\Http\Request\Normalizer;

/**
 * Нормализатор входного значения активности фича-флага.
 */
final class EnabledValueNormalizer
{
    /**
     * Приводит входное значение к bool.
     *
     * @param mixed $value
     * @return bool
     */
    public static function normalize(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            if ($value === 1) {
                return true;
            }

            if ($value === 0) {
                return false;
            }
        }

        if (is_float($value)) {
            if ($value === 1.0) {
                return true;
            }

            if ($value === 0.0) {
                return false;
            }
        }

        if (is_string($value)) {
            $normalized = mb_strtolower(trim($value));
            if (in_array($normalized, ['1', 'y', 'yes', 'true', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'n', 'no', 'false', 'off'], true)) {
                return false;
            }
        }

        return false;
    }
}
