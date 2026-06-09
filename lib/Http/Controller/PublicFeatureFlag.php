<?php

namespace Sholokhov\Featureflag\Http\Controller;

use Sholokhov\Featureflag\Feature;
use Sholokhov\Featureflag\FeatureInterface;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;

class PublicFeatureFlag extends Controller
{
    /**
     * @return array[]
     */
    public function configureActions(): array
    {
        return [
            'get' => [
                '-prefilters' => [
                    Authentication::class
                ]
            ]
        ];
    }

    /**
     * @return ExactParameter
     * @throws BinderArgumentException
     */
    public function getPrimaryAutoWiredParameter(): ExactParameter
    {
        return new ExactParameter(
            FeatureInterface::class,
            'feature',
            static fn($className, $code): ?FeatureInterface => is_string($code) ? (Feature::getByCode($code) ?? null) : null,
        );
    }

    /**
     * Возвращает информацию по кешу
     *
     * @param FeatureInterface|null $feature
     * @return array
     */
    public function getAction(?FeatureInterface $feature = null): array
    {
        if ($feature) {
            return [
                'code' => $feature->getCode(),
                'enabled' => $feature->isEnabled(),
            ];
        }

        return [
            'code' => (string)$this->getRequest()->get('code'),
            'enabled' => false
        ];
    }
}