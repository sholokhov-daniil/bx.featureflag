<?php

namespace Sholokhov\Featureflag\Strategies;

use CUser;
use Sholokhov\Featureflag\Field\EntitySelector\UserGroupField;
use Sholokhov\Featureflag\Field\FieldInterface;
use Sholokhov\Featureflag\Field\Normalizer\ListNormalizer;
use Sholokhov\Featureflag\Field\Validator\PositiveIntegerListValidator;

/**
 * Стратегия доступа по группам текущего пользователя.
 */
final class UserGroupStrategy extends AbstractStrategy
{
    /**
     * Возвращает код стратегии.
     *
     * @return string
     */
    public function getCode(): string
    {
        return 'user_groups';
    }

    /**
     * Возвращает название стратегии для UI.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Группы пользователей';
    }

    /**
     * Возвращает описание стратегии для UI.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Включает флаг для пользователей из выбранных групп Bitrix.';
    }

    /**
     * Возвращает схему полей формы.
     *
     * @return FieldInterface[]
     */
    public function getFields(): array
    {
        return [
            (new UserGroupField('groupIds'))
                ->setName('Группы пользователей')
                ->setRequired(true, 'Укажите хотя бы один ID группы.')
                ->setNormalizer(static fn(mixed $value): array => ListNormalizer::positiveIntegers($value))
                ->setDenormalizer(static fn(mixed $value): string => ListNormalizer::denormalize($value))
                ->addValidator(new PositiveIntegerListValidator('Некорректный ID группы: %s'))
        ];
    }

    /**
     * Проверяет пересечение групп текущего пользователя с разрешёнными группами.
     *
     * @param string $featureCode Код фича-флага
     * @param array<string, mixed> $config Конфигурация стратегии
     * @return bool
     */
    public function isEnabled(string $featureCode, array $config): bool
    {
        $groupIds = $this->normalizePositiveIds($config['groupIds'] ?? []);
        if ($groupIds === []) {
            return false;
        }

        return array_intersect($groupIds, $this->getCurrentUserGroupIds()) !== [];
    }

    /**
     * Возвращает список ID групп текущего пользователя.
     *
     * @return int[]
     */
    private function getCurrentUserGroupIds(): array
    {
        global $USER;

        if (!is_object($USER)) {
            return [];
        }

        if (method_exists($USER, 'GetUserGroupArray')) {
            return $this->normalizePositiveIds((array)$USER->GetUserGroupArray());
        }

        if (method_exists($USER, 'GetID')) {
            return $this->normalizePositiveIds(CUser::GetUserGroup((int)$USER->GetID()));
        }

        return [];
    }
}
