<?php

use Sholokhov\Featureflag\Factory\FeatureFactory;
use Sholokhov\Featureflag\Factory\FeatureFactoryInterface;
use Sholokhov\Featureflag\Repository\RuleRegistry;
use Sholokhov\Featureflag\Repository\RuleRegistryInterface;
use Sholokhov\Featureflag\Repository\FeatureRepository;
use Sholokhov\Featureflag\Repository\FeatureRepositoryInterface;
use Sholokhov\Featureflag\Service\AdminFeatureFlagService;
use Sholokhov\Featureflag\Service\AdminFeatureFlagServiceInterface;

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Sholokhov\\Featureflag\\Http\\Controller',
        ],
        'readonly' => true,
    ],
    'services' => [
        'value' => [
            FeatureFactoryInterface::class => [
                'constructor' => static fn() => new FeatureFactory,
            ],
            RuleRegistryInterface::class => [
                'className' => RuleRegistry::class,
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
