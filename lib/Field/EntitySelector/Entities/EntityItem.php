<?php

namespace Sholokhov\Featureflag\Field\EntitySelector\Entities;

use ArrayAccess;

class EntityItem implements ArrayAccess
{
    protected array $container = [
        'dynamicLoad' => true,
        'dynamicSearch' => true,
    ];

    public function __construct(string $id)
    {
        $this['id'] = $id;
    }

    public function toArray(): array
    {
        return $this->container;
    }

    public function setDynamicLoad(bool $value = false): self
    {
        $this['dynamicLoad'] = $value;
        return $this;
    }

    public function setDynamicSearch(bool $value = false): self
    {
        $this['dynamicSearch'] = $value;
        return $this;
    }

    public function setOptions(array $options): self
    {
        $this['options'] = $options;
        return $this;
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