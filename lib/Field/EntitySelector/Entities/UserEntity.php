<?php

namespace Sholokhov\Featureflag\Field\EntitySelector\Entities;

use Sholokhov\Featureflag\Provider\EntitySelector\UserProvider;

/**
 * Упрощенная сущность пользователей.
 * Данная сущность подходит в те случаи, когда не нужна интеграция с b24.
 * Если у вас b24, то рекомендуется воспользоваться штатным провайдером данных {@see B24UserEntity}
 */
class UserEntity extends EntityItem
{
    public function __construct()
    {
        parent::__construct(UserProvider::ENTITY_ID);
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