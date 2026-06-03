<?php

namespace Sholokhov\Featureflag;

use Sholokhov\Featureflag\Factory\FeatureFactoryInterface;
use Sholokhov\Featureflag\Permission\PermissionInterface;
use Sholokhov\Featureflag\Repository\FeatureRepositoryInterface;
use Sholokhov\Featureflag\Repository\RuleRegistryInterface;
use Sholokhov\Featureflag\Service\AdminFeatureFlagServiceInterface;
use Sholokhov\Featureflag\Strategy\StrategyRegistryInterface;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;

use Psr\Container\NotFoundExceptionInterface;

class ServiceProvider
{
    /**
     * Возвращает права доступа к модулю
     *
     * @return PermissionInterface
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     */
    public static function getModulePermission(): PermissionInterface
    {
        return ServiceLocator::getInstance()->get('sholokhov.featureflag.permission.module');
    }

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

    /**
     * Хранилище UI-стратегий доступа к флагам.
     *
     * @return StrategyRegistryInterface
     * @throws ObjectNotFoundException
     * @throws NotFoundExceptionInterface
     */
    public static function getStrategyRegistry(): StrategyRegistryInterface
    {
        return ServiceLocator::getInstance()->get(StrategyRegistryInterface::class);
    }

    /**
     * Сервис админ-управления фича-флагами.
     *
     * @return AdminFeatureFlagServiceInterface
     * @throws ObjectNotFoundException
     * @throws NotFoundExceptionInterface
     */
    public static function getAdminFeatureFlagService(): AdminFeatureFlagServiceInterface
    {
        return ServiceLocator::getInstance()->get(AdminFeatureFlagServiceInterface::class);
    }
}
