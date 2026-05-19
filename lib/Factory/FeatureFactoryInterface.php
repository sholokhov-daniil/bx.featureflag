<?php

namespace Sholokhov\Featureflag\Factory;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Sholokhov\Featureflag\FeatureInterface;

interface FeatureFactoryInterface
{
    public function createFromEntity(EntityObject $entity): FeatureInterface;
}