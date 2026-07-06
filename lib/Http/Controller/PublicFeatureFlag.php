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
        $publicConfig = [
            '-prefilters' => [
                Authentication::class,
            ],
        ];

        return [
            'get' => $publicConfig,
            'getBulk' => $publicConfig,
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
     * @return array{enabled: bool}|null
     */
    public function getAction(?FeatureInterface $feature = null): ?array
    {
        return $this->presentPublicFeature($feature);
    }

    /**
     * Возвращает публичную информацию по списку фич без раскрытия скрытых кодов.
     *
     * @param array<int, mixed> $codes
     * @return array<string, array{enabled: bool}|null>
     */
    public function getBulkAction(array $codes = []): array
    {
        $result = [];

        foreach ($this->normalizeCodes($codes) as $code) {
            $result[$code] = $this->presentPublicFeature(Feature::getByCode($code));
        }

        return $result;
    }

    /**
     * Возвращает публичный payload фичи или null, если фича скрыта от JS.
     *
     * @param FeatureInterface|null $feature
     * @return array{enabled: bool}|null
     */
    private function presentPublicFeature(?FeatureInterface $feature): ?array
    {
        if ($feature === null || !$feature->isAvailableInJs()) {
            return null;
        }

        return [
            'enabled' => $feature->isEnabled(),
        ];
    }

    /**
     * Нормализует список кодов из публичного запроса.
     *
     * @param array<int, mixed> $codes
     * @return array<int, string>
     */
    private function normalizeCodes(array $codes): array
    {
        $normalizedCodes = [];

        foreach ($codes as $code) {
            if (!is_string($code)) {
                continue;
            }

            $code = trim($code);
            if ($code === '') {
                continue;
            }

            $normalizedCodes[$code] = $code;
        }

        return array_values($normalizedCodes);
    }
}