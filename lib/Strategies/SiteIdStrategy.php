<?php

namespace Sholokhov\Featureflag\Strategies;

use Sholokhov\Featureflag\Field\FieldInterface;
use Sholokhov\Featureflag\Field\Normalizer\ListNormalizer;
use Sholokhov\Featureflag\Field\TextareaField;
use Sholokhov\Featureflag\Field\Validator\SiteIdListValidator;

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
     * @return FieldInterface[]
     */
    public function getFields(): array
    {
        return [
            (new TextareaField('siteIds'))
                ->setName('ID сайтов')
                ->setPlaceholder('s1, s2')
                ->setRequired(true, 'Укажите хотя бы один ID сайта.')
                ->setNormalizer(static fn(mixed $value): array => ListNormalizer::strings($value))
                ->setDenormalizer(static fn(mixed $value): string => ListNormalizer::denormalize($value))
                ->addValidator(new SiteIdListValidator())
        ];
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
