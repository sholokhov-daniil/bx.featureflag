<?php

namespace Sholokhov\Featureflag\Field;

class TextField extends AbstractField
{
    /**
     * Подсказка значения
     *
     * @var string
     */
    protected string $placeholder = '';

    /**
     * Маска значения
     *
     * @var string
     */
    protected string $mask = '';

    /**
     * Возвращает подсказку
     *
     * @return string
     */
    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    /**
     * Указание подсказки
     *
     * @param string $placeholder
     * @return $this
     */
    public function setPlaceholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * Возвращает маску значения
     *
     * @return string
     */
    public function getMask(): string
    {
        return $this->mask;
    }

    /**
     * Указать маску значения
     *
     * @param string $mask
     * @return $this
     */
    public function setMask(string $mask): self
    {
        $this->mask = $mask;
        return $this;
    }

    /**
     * Преобразование свойства в массив
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        $mask = $this->getMask();
        if ($mask) {
            $data['mask'] = $mask;
        }

        $placeholder = $this->getPlaceholder();
        if ($placeholder) {
            $data['placeholder'] = $placeholder;
        }

        return $data;
    }
}