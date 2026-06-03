<?php

namespace Sholokhov\Featureflag\Field\EntitySelector\Entities;

use Sholokhov\Featureflag\Provider\EntitySelector\UserGroupProvider;

/**
 * Упрощенная сущность групп пользователей.
 */
class UserGroupEntity extends EntityItem
{
    public function __construct()
    {
        parent::__construct(UserGroupProvider::ENTITY_ID);
    }

    public function setItemOptions(array $options): self
    {
        $this['itemOptions'] = $options;
        return $this;
    }

    public function addOption(string $name, mixed $value): self
    {
        $this->container['options'][$name] = $value;
        return $this;
    }
}
