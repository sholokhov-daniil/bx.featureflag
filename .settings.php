<?php

use Sholokhov\Featureflag\Application\UseCase;
use Sholokhov\Featureflag\Domain\Repository\FeatureFlagRepositoryInterface;
use Sholokhov\Featureflag\Factory\FeatureFactory;
use Sholokhov\Featureflag\Factory\FeatureFactoryInterface;
use Sholokhov\Featureflag\Infrastructure\DI\Factory\ActionFactory;
use Sholokhov\Featureflag\Infrastructure\Persistence\FeatureFlagMapper;
use Sholokhov\FeatureFlag\Infrastructure\Repository\FeatureFlagRepository;
use Sholokhov\Featureflag\Permission\ModulePermission;
use Sholokhov\Featureflag\Repository\RuleRegistry;
use Sholokhov\Featureflag\Repository\RuleRegistryInterface;
use Sholokhov\Featureflag\Repository\FeatureRepository;
use Sholokhov\Featureflag\Repository\FeatureRepositoryInterface;
use Sholokhov\Featureflag\Service\AdminFeatureFlagService;
use Sholokhov\Featureflag\Service\AdminFeatureFlagServiceInterface;
use Sholokhov\Featureflag\Strategies\IpListStrategy;
use Sholokhov\Featureflag\Strategies\IpRangeStrategy;
use Sholokhov\Featureflag\Strategies\SiteIdStrategy;
use Sholokhov\Featureflag\Strategies\UserGroupStrategy;
use Sholokhov\Featureflag\Strategies\UserIdStrategy;
use Sholokhov\Featureflag\Strategy\StrategyRegistry;
use Sholokhov\Featureflag\Strategy\StrategyRegistryInterface;

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Sholokhov\\Featureflag\\Http\\Controller',
        ],
        'readonly' => true,
    ],
    'ui.entity-selector' => [
        'value' => [
            'entities' => [
                [
                    'entityId' => 'sholokhov.featureflag.user',
                    'provider' => [
                        'moduleId' => 'sholokhov.featureflag',
                        'className' => \Sholokhov\Featureflag\Provider\EntitySelector\UserProvider::class,
                    ]
                ],
                [
                    'entityId' => 'sholokhov.featureflag.user.group',
                    'provider' => [
                        'moduleId' => 'sholokhov.featureflag',
                        'className' => \Sholokhov\Featureflag\Provider\EntitySelector\UserGroupProvider::class,
                    ]
                ],
            ]
        ]
    ],
    'services' => [
        'value' => [
            FeatureFactoryInterface::class => [
                'constructor' => static fn() => new FeatureFactory,
            ],
            RuleRegistryInterface::class => [
                'className' => RuleRegistry::class,
            ],
            StrategyRegistryInterface::class => [
                'constructor' => static fn() => (new StrategyRegistry())
                    ->register(new IpListStrategy())
                    ->register(new IpRangeStrategy())
                    ->register(new UserIdStrategy())
                    ->register(new UserGroupStrategy())
                    ->register(new SiteIdStrategy()),
            ],
            /** @deprecated  */
            FeatureRepositoryInterface::class => [
                'constructor' => static fn() => new FeatureRepository,
            ],
            AdminFeatureFlagServiceInterface::class => [
                'className' => AdminFeatureFlagService::class,
            ],
            FeatureFlagRepositoryInterface::class => [
                'constructor' => static fn() => new FeatureFlagRepository(new FeatureFlagMapper)
            ],
            ActionFactory::FEATURE_TOGGLE_SERVICE_ID => [
                'constructor' => static fn() => new UseCase\SetFeatureFlagEnabled(
                    repository: \Sholokhov\Featureflag\ServiceProvider::getFeatureRepository()
                ),
            ],
            'sholokhov.action.factory' => [
                'className' => ActionFactory::class,
            ],
            'sholokhov.featureflag.permission.module' => [
                'className' => ModulePermission::class,
            ]
        ]
    ]
];
