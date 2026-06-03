<?php

namespace Sholokhov\Featureflag\Field\EntitySelector;

use ArrayAccess;
use Sholokhov\Featureflag\Field\EntitySelector\Entities\EntityItem;

/**
 * Настройки диалога выбора сущности
 */
class DialogOptions implements ArrayAccess
{
    protected array $container = [
        'enableSearch' => true,
    ];


    /**
     * Установить контекст диалога.
     *
     * @param string $context
     * @return $this
     */
    public function setContext(string $context): self
    {
        $this['context'] = $context;
        return $this;
    }

    /**
     * Установить предустанвленные значения
     *
     * @param array $items
     * @return $this
     */
    public function setPreselectedItems(array $items): self
    {
        $this['preselectedItems'] = $items;
        return $this;
    }

    /**
     * Добавить предустановленное значение
     *
     * @param string $entity
     * @param string|int $value
     * @return $this
     */
    public function addPreselectedItems(string $entity, string|int $value): self
    {
        $this->container['preselectedItems'][] = [$entity, $value];
        return $this;
    }

    /**
     * Указать сущности с которыми работает виалог
     *
     * @param array $entities
     * @return $this
     */
    public function setEntities(array $entities): self
    {
        $this['entities'] = [];

        array_walk(
            $entities,
            $this->addEntity(...)
        );

        return $this;
    }

    /**
     * Добавление источников данных диалога
     *
     * @param EntityItem $entity
     * @return self
     */
    public function addEntity(EntityItem $entity): self
    {
        $this->container['entities'][] = $entity;
        return $this;
    }

    public function toArray(): array
    {
        $data = $this->container;

        if (!empty($data['entities']) && is_array($data['entities'])) {
            $data['entities'] = array_map(
                static fn (EntityItem $entity) => $entity->toArray(),
                $data['entities']
            );
        }

        return $data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->container[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->container[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->container[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->container[$offset]);
    }
}
