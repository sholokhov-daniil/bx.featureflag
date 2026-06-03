<?php

namespace Sholokhov\Featureflag\Field\EntitySelector\Entities;

/**
 * Штатная сущность пользователя, которая завязана на b24
 *
 * @link https://dev.1c-bitrix.ru/api_d7/bitrix/ui/entity_selector/providers/standard_providers/user_provider.php
 */
class B24UserEntity extends EntityItem
{
    public function __construct()
    {
        parent::__construct('user');
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