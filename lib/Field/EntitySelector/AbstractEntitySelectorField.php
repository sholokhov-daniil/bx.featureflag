<?php

namespace Sholokhov\Featureflag\Field\EntitySelector;

use ArrayAccess;

use Sholokhov\Featureflag\Field\AbstractField;
use Sholokhov\Featureflag\Field\FieldType;

/**
 * Свойство привязки к сущности
 */
abstract class AbstractEntitySelectorField extends AbstractField implements ArrayAccess
{
    /**
     * Тип данных свойства
     *
     * @var FieldType
     */
    protected FieldType $type = FieldType::EntitySelector;

    public function setId(?string $id): self
    {
        $this['id'] = $id;
        return $this;
    }

    public function getPlaceholder(): string
    {
        return $this['placeholder'] ??= 'Введите название элемента';
    }

    public function setPlaceholder(string $placeholder): self
    {
        $this['placeholder'] = $placeholder;
        return $this;
    }

    public function getAddButtonCaption(): ?string
    {
        return $this['addButtonCaption'] ?? null;
    }

    public function setAddButtonCaption(string $addButtonCaption): self
    {
        $this['addButtonCaption'] = $addButtonCaption;
        return $this;
    }

    public function setAddButtonCaptionMore(string $addButtonCaptionMore): self
    {
        $this['addButtonCaptionMore'] = $addButtonCaptionMore;
        return $this;
    }

    public function setEvents(array $events): self
    {
        $this['events'] = $events;
        return $this;
    }

    public function setTagTextColor(?string $tagTextColor): self
    {
        $this['tagTextColor'] = $tagTextColor;
        return $this;
    }

    public function setTagBgColor(?string $tagBgColor): self
    {
        $this['tagBgColor'] = $tagBgColor;
        return $this;
    }

    public function setShowAddButton(bool $showAddButton): self
    {
        $this['showAddButton'] = $showAddButton;
        return $this;
    }

    public function setShowAddButtonMore(bool $showAddButtonMore): self
    {
        $this['addButtonCaptionMore'] = $showAddButtonMore;
        return $this;
    }

    public function setReadonly(bool $readonly): self
    {
        $this['readonly'] = $readonly;
        return $this;
    }

    public function getDialogOptions(): ?DialogOptions
    {
        return $this['dialogOptions'] ?? null;
    }

    public function setDialogOptions(?DialogOptions $dialogOptions): self
    {
        $this['dialogOptions'] = $dialogOptions;
        return $this;
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $options = is_array($data['options'] ?? null) ? $data['options'] : [];
        unset($data['options']);

        $data['options'] = [
            ...$options,
            'id' => $options['id'] ?? $this->getCode(),
            'multiple' => $this->isMultiple(),
        ];

        $dialogOptions = $this->getDialogOptions()?->toArray();
        if ($dialogOptions !== null) {
            $data['options']['dialogOptions'] = $dialogOptions;
        }

        return $data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->container['options'][$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->container['options'][$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->container['options'][$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->container['options'][$offset]);
    }
}
