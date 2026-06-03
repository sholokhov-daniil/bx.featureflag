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
     * @var array<string, mixed>|string
     */
    protected array|string $mask = '';

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
     * @return array<string, mixed>|string
     */
    public function getMask(): array|string
    {
        return $this->mask;
    }

    /**
     * Указать маску значения
     *
     * @param array<string, mixed>|string $mask
     * @return $this
     */
    public function setMask(array|string $mask): self
    {
        $this->mask = $mask;
        return $this;
    }

    /**
     * Указать regex-маску значения для UI.
     *
     * Маска выполняется как replace(pattern, replacement), поэтому подходит
     * для фильтрации вводимых символов без привязки UI к конкретному типу поля.
     *
     * @param string $pattern Регулярное выражение без ограничителей
     * @param string $replacement Значение замены
     * @param string $flags Флаги регулярного выражения
     * @param string $inputMode Подсказка inputmode для браузера
     * @return $this
     */
    public function setRegexMask(
        string $pattern,
        string $replacement = '',
        string $flags = 'g',
        string $inputMode = '',
    ): self {
        $this->mask = [
            'type' => 'regex',
            'pattern' => $pattern,
            'flags' => $flags,
            'replacement' => $replacement,
        ];

        if ($inputMode !== '') {
            $this->mask['inputMode'] = $inputMode;
        }

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
