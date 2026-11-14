<?php

declare(strict_types=1);

namespace Sholokhov\Featureflag\Infrastructure\DI\Factory;

use Sholokhov\Featureflag\Application\UseCase\SetFeatureFlagEnabled;

use Bitrix\Main\DI\Exception\CircularDependencyException;
use Bitrix\Main\DI\Exception\ServiceNotFoundException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;

final class ActionFactory
{
    public const string FEATURE_TOGGLE_SERVICE_ID = 'sholokhov.featureflag.action.feature.toggle';

    /**
     * Создает действие управления активности фичи
     *
     * @return SetFeatureFlagEnabled
     * @throws CircularDependencyException
     * @throws ServiceNotFoundException
     * @throws ObjectNotFoundException
     */
    public function createFeatureToggle(): SetFeatureFlagEnabled
    {
        return ServiceLocator::getInstance()->get(self::FEATURE_TOGGLE_SERVICE_ID);
    }
}