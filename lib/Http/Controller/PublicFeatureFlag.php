<?php

namespace Sholokhov\Featureflag\Http\Controller;

use Bitrix\Main\Engine\Controller;
use Sholokhov\Featureflag\Service\AdminFeatureFlagServiceInterface;

class PublicFeatureFlag extends Controller
{
    public function configureActions(): array
    {
        return [
            'get' => [
                '-prefilters' => [
                    \Bitrix\Main\Engine\ActionFilter\Authentication::class
                ]
            ]
        ];
    }

    public function getAutoWiredParameters(): array
    {
        return [

        ];
    }

    public function getAction(string $code): array
    {
        return [];
    }
}