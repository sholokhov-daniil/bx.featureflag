<?php

namespace Sholokhov\Featureflag\Field;

use Closure;

/**
 * Управляет доступностью денормализатора данных
 */
trait DenormalizerAwareTrait
{
    /**
     * Денормализатор данных
     *
     * @var Closure|null
     */
    protected ?Closure $denormalizer = null;

    /**
     * Денормализация значения перед отдачей в UI
     *
     * @param mixed $value
     * @return mixed
     */
    public function denormalizeValue(mixed $value): mixed
    {
        if ($this->denormalizer === null) {
            return $value;
        }

        return ($this->denormalizer)($value, $this);
    }

    /**
     * Указание денормализатор данных - как прочитать данные
     *
     * @param Closure $denormalizer
     * @return self
     */
    public function setDenormalizer(Closure $denormalizer): self
    {
        $this->denormalizer = $denormalizer;
        return $this;
    }
}