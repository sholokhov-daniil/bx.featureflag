<?php

use Sholokhov\Featureflag\Factory\FlagFactory;
use Sholokhov\Featureflag\Factory\FlagFactoryInterface;
use Sholokhov\Featureflag\Repository\RuleRegistry;
use Sholokhov\Featureflag\Repository\RuleRegistryInterface;
use Sholokhov\Featureflag\Repository\FlagRepository;
use Sholokhov\Featureflag\Repository\FlagRepositoryInterface;

return [
    'services' => [
        'value' => [
            FlagFactoryInterface::class => [
                'constructor' => static fn() => new FlagFactory,
            ],
            RuleRegistryInterface::class => [
                'className' => RuleRegistry::class,
            ],
            FlagRepositoryInterface::class => [
                'className' => static fn() => new FlagRepository,
            ]
        ]
    ]
];
