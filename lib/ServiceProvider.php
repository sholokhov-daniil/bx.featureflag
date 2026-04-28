<?php

namespace Sholokhov\Featureflag;

use Sholokhov\Featureflag\Factory\FlagFactoryInterface;
use Sholokhov\Featureflag\Repository\FlagRepositoryInterface;
use Sholokhov\Featureflag\Repository\RuleRegistryInterface;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;

use Psr\Container\NotFoundExceptionInterface;

class ServiceProvider
{
    /**
     * Возвращает сборщик флагов
     *
     * @return FlagFactoryInterface
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     */
    public static function getFlagFactory(): FlagFactoryInterface
    {
        return ServiceLocator::getInstance()->get(FlagFactoryInterface::class);
    }

    /**
     * Хранилище зарегистрированных флагов
     *
     * @return FlagRepositoryInterface
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     */
    public static function getFlagRepository(): FlagRepositoryInterface
    {
        return ServiceLocator::getInstance()->get(FlagRepositoryInterface::class);
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