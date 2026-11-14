<?php

namespace Sholokhov\Featureflag;

use Sholokhov\Featureflag\Domain\Repository\FeatureFlagRepositoryInterface;
use Sholokhov\Featureflag\Factory\FeatureFactoryInterface;
use Sholokhov\Featureflag\Infrastructure\DI\Factory\ActionFactory;
use Sholokhov\Featureflag\Permission\PermissionInterface;
use Sholokhov\Featureflag\Repository\RuleRegistryInterface;
use Sholokhov\Featureflag\Service\AdminFeatureFlagServiceInterface;
use Sholokhov\Featureflag\Strategy\StrategyRegistryInterface;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\DI\Exception\ServiceNotFoundException;
use Bitrix\Main\DI\Exception\CircularDependencyException;

use Psr\Container\NotFoundExceptionInterface;

class ServiceProvider
{
    /**
     * Возвращает фабрику действий
     *
     * @return ActionFactory
     * @throws ObjectNotFoundException
     * @throws CircularDependencyException
     * @throws ServiceNotFoundException
     */
    public static function getActionFactory(): ActionFactory
    {
        return ServiceLocator::getInstance()->get('sholokhov.action.factory');
    }

    /**
     * Возвращает права доступа к модулю
     *
     * @return PermissionInterface
     * @throws CircularDependencyException
     * @throws ObjectNotFoundException
     * @throws ServiceNotFoundException
     */
    public static function getModulePermission(): PermissionInterface
    {
        return ServiceLocator::getInstance()->get('sholokhov.featureflag.permission.module');
    }

    /**
     * Возвращает сборщик флагов
     *
     * @return FeatureFactoryInterface
     * @throws CircularDependencyException
     * @throws ObjectNotFoundException
     * @throws ServiceNotFoundException
     */
    public static function getFeatureFactory(): FeatureFactoryInterface
    {
        return ServiceLocator::getInstance()->get(FeatureFactoryInterface::class);
    }

    /**
     * Хранилище зарегистрированных флагов
     *
     * @return FeatureFlagRepositoryInterface
     * @throws CircularDependencyException
     * @throws ObjectNotFoundException
     * @throws ServiceNotFoundException
     */
    public static function getFeatureRepository(): FeatureFlagRepositoryInterface
    {
        return ServiceLocator::getInstance()->get(FeatureFlagRepositoryInterface::class);
    }

    /**
     * Хранилище пользовательских правил проверки активности флага
     *
     * @return RuleRegistryInterface
     * @throws CircularDependencyException
     * @throws ObjectNotFoundException
     * @throws ServiceNotFoundException
     */
    public static function getRuleRegistry(): RuleRegistryInterface
    {
        return ServiceLocator::getInstance()->get(RuleRegistryInterface::class);
    }

    /**
     * Хранилище UI-стратегий доступа к флагам.
     *
     * @return StrategyRegistryInterface
     * @throws CircularDependencyException
     * @throws ObjectNotFoundException
     * @throws ServiceNotFoundException
     */
    public static function getStrategyRegistry(): StrategyRegistryInterface
    {
        return ServiceLocator::getInstance()->get(StrategyRegistryInterface::class);
    }

    /**
     * Сервис админ-управления фича-флагами.
     *
     * @return AdminFeatureFlagServiceInterface
     * @throws CircularDependencyException
     * @throws ObjectNotFoundException
     * @throws ServiceNotFoundException
     */
    public static function getAdminFeatureFlagService(): AdminFeatureFlagServiceInterface
    {
        return ServiceLocator::getInstance()->get(AdminFeatureFlagServiceInterface::class);
    }
}
