<?php

namespace Sholokhov\Featureflag\Strategies;

use Sholokhov\Featureflag\Field\EntitySelector\UserField;
use Sholokhov\Featureflag\Field\FieldInterface;
use Sholokhov\Featureflag\Field\Normalizer\ListNormalizer;
use Sholokhov\Featureflag\Field\Validator\PositiveIntegerListValidator;
use Sholokhov\Featureflag\Strategy\StrategyAvailability;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

/**
 * Стратегия доступа по ID пользователей.
 */
final class UserIdStrategy extends AbstractStrategy
{
    /**
     * Возвращает код стратегии.
     *
     * @return string
     */
    public function getCode(): string
    {
        return 'user_ids';
    }

    /**
     * Возвращает название стратегии для UI.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Пользователи';
    }

    /**
     * Возвращает описание стратегии для UI.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Включает флаг только для выбранных ID пользователей.';
    }

    /**
     * Возвращает схему полей формы.
     *
     * @return FieldInterface[]
     */
    public function getFields(): array
    {
        return [
            (new UserField('userIds'))
                ->setName('ID пользователей')
                ->setRequired(true, 'Укажите хотя бы один ID пользователя.')
                ->setNormalizer(static fn(mixed $value): array => ListNormalizer::positiveIntegers($value))
                ->setDenormalizer(static fn(mixed $value): string => ListNormalizer::denormalize($value))
                ->addValidator(new PositiveIntegerListValidator('Некорректный ID пользователя: %s'))
        ];
    }

    /**
     * Проверяет, входит ли текущий пользователь в список.
     *
     * @param string $featureCode Код фича-флага
     * @param array<string, mixed> $config Конфигурация стратегии
     * @return bool
     */
    public function isEnabled(string $featureCode, array $config): bool
    {
        $userIds = $this->normalizePositiveIds($config['userIds'] ?? []);
        if ($userIds === []) {
            return false;
        }

        $currentUserId = $this->getCurrentUserId();
        return $currentUserId > 0 && in_array($currentUserId, $userIds, true);
    }

    /**
     * Проверяет, доступна ли стратегия в текущем окружении.
     *
     * @return StrategyAvailability
     * @throws LoaderException
     */
    public function getAvailability(): StrategyAvailability
    {
        return Loader::includeModule('ui')
            ? StrategyAvailability::available()
            : StrategyAvailability::unavailableModule('ui');
    }

    /**
     * Возвращает ID текущего пользователя.
     *
     * @return int
     */
    private function getCurrentUserId(): int
    {
        global $USER;
        return $USER ? $USER->GetID() : 0;
    }
}
