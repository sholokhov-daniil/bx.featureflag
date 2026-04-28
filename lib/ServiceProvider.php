<?php

namespace Sholokhov\Featureflag;

use Sholokhov\Featureflag\Factory\FeatureFactoryInterface;
use Sholokhov\Featureflag\Repository\FeatureRepositoryInterface;
use Sholokhov\Featureflag\Repository\RuleRegistryInterface;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;

use Psr\Container\NotFoundExceptionInterface;

class ServiceProvider
{
    /**
     * Возвращает сборщик флагов
     *
     * @return FeatureFactoryInterface
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     */
    public static function getFeatureFactory(): FeatureFactoryInterface
    {
        return ServiceLocator::getInstance()->get(FeatureFactoryInterface::class);
    }

    /**
     * Хранилище зарегистрированных флагов
     *
     * @return FeatureRepositoryInterface
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     */
    public static function getFeatureRepository(): FeatureRepositoryInterface
    {
        return ServiceLocator::getInstance()->get(FeatureRepositoryInterface::class);
    }

    /**
     * Хранилище пользовательских правил проверки активности флага
     *
     * @return RuleRegistryInterface
     * @throws ObjectNotFoundException
     * @throws NotFoundExceptionInterface
     */
    public static function getRuleRegistry(): RuleRegistryInterface
    {
        return ServiceLocator::getInstance()->get(RuleRegistryInterface::class);
    }
}