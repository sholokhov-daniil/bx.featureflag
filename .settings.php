<?php

use Sholokhov\Featureflag\Factory\FeatureFactory;
use Sholokhov\Featureflag\Factory\FeatureFactoryInterface;
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
            FeatureRepositoryInterface::class => [
                'constructor' => static fn() => new FeatureRepository,
            ],
            AdminFeatureFlagServiceInterface::class => [
                'className' => AdminFeatureFlagService::class,
            ],
        ]
    ]
];
