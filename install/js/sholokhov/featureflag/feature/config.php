<?php

use Bitrix\Main\Loader;
use Sholokhov\Featureflag\FeatureInterface;
use Sholokhov\Featureflag\ServiceProvider;

$flags = [];

if (Loader::includeModule('sholokhov.featureflag')) {
    $flags = array_map(
        static fn(FeatureInterface $feature) => $feature->getCode(),
        ServiceProvider::getFeatureRepository()->findForJs()
    );
}

\Bitrix\Main\Diag\Debug::dump($flags);
die();

return [
    'js' => 'dist/index.bundle.js',
    'rel' => [
		'main.core',
	],
    'skip_core' => false,
    'settings' => [
        'flags' => $flags
    ]
];
