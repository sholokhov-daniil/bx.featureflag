<?php

use Sholokhov\Featureflag\Factory\FeatureFactory;
use Sholokhov\Featureflag\Factory\FeatureFactoryInterface;
use Sholokhov\Featureflag\Repository\RuleRegistry;
use Sholokhov\Featureflag\Repository\RuleRegistryInterface;
use Sholokhov\Featureflag\Repository\FeatureRepository;
use Sholokhov\Featureflag\Repository\FeatureRepositoryInterface;

return [
    'services' => [
        'value' => [
            FeatureFactoryInterface::class => [
                'constructor' => static fn() => new FeatureFactory,
            ],
            RuleRegistryInterface::class => [
                'className' => RuleRegistry::class,
            ],
            FeatureRepositoryInterface::class => [
                'className' => static fn() => new FeatureRepository,
            ]
        ]
    ]
];
