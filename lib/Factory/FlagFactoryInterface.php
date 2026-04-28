<?php

namespace Sholokhov\Featureflag\Factory;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Sholokhov\Featureflag\FlagInterface;

interface FlagFactoryInterface
{
    public function createFromEntity(EntityObject $entity): FlagInterface;
}