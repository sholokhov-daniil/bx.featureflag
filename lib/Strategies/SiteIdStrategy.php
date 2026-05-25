<?php

namespace Sholokhov\Featureflag\Strategies;

use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\TextareaField;

/**
 * Стратегия доступа по текущему SITE_ID.
 */
final class SiteIdStrategy extends AbstractStrategy
{
    /**
     * Возвращает код стратегии.
     *
     * @return string
     */
    public function getCode(): string
    {
        return 'site_ids';
    }

    /**
     * Возвращает название стратегии для UI.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'ID сайта';
    }

    /**
     * Возвращает описание стратегии для UI.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Включает флаг только на перечисленных сайтах Bitrix.';
    }

    /**
     * Возвращает схему полей формы.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFields(): array
    {
        return [
            new TextareaField('siteIds')
                ->setName('ID сайтов')
                ->setPlaceholder('s1, s2')
                ->setRequired()
        ];
    }

    /**
     * Валидирует и нормализует список ID сайтов.
     *
     * @param array<string, mixed> $config
     * @return Result
     */
    public function normalizeConfig(array $config): Result
    {
        $siteIds = $this->splitValues($config['siteIds'] ?? []);
        if ($siteIds === []) {
            return $this->error('Укажите хотя бы один ID сайта.');
        }

        foreach ($siteIds as $siteId) {
            if (!preg_match('/^[A-Za-z0-9_.-]{1,50}$/', $siteId)) {
                return $this->error("Некорректный ID сайта: {$siteId}");
            }
        }

        return $this->success([
            'siteIds' => $siteIds,
        ]);
    }

    /**
     * Проверяет, входит ли текущий SITE_ID в разрешённый список.
     *
     * @param string $featureCode Код фича-флага
     * @param array<string, mixed> $config Конфигурация стратегии
     * @return bool
     */
    public function isEnabled(string $featureCode, array $config): bool
    {
        if (!defined('SITE_ID')) {
            return false;
        }

        return in_array((string)SITE_ID, $this->splitValues($config['siteIds'] ?? []), true);
    }
}
