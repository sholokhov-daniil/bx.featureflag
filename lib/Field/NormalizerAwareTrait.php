<?php

namespace Sholokhov\Featureflag\Field;

use Closure;

/**
 * Доступные нормализаторы свойства
 */
trait NormalizerAwareTrait
{
    /**
     * Нормализатор данных
     *
     * @var Closure|null
     */
    protected ?Closure $normalizer = null;

    /**
     * Нормализация значения перед валидацией и сохранением
     *
     * @param mixed $value
     * @return mixed
     */
    public function normalizeValue(mixed $value): mixed
    {
        if ($this->normalizer === null) {
            return $value;
        }

        return ($this->normalizer)($value, $this);
    }

    /**
     * Указание нормализатора данных - перед сохранением данных
     *
     * @param Closure $normalizer
     * @return self
     */
    public function setNormalizer(Closure $normalizer): self
    {
        $this->normalizer = $normalizer;
        return $this;
    }
}