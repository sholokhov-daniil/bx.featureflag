<?php

namespace Sholokhov\Featureflag\Factory;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Sholokhov\Featureflag\FeatureInterface;

interface FlagFactoryInterface
{
    public function createFromEntity(EntityObject $entity): FeatureInterface;
}