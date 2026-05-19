<?php

namespace Sholokhov\Featureflag\Strategies;

use Bitrix\Main\Result;
use CUser;

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
     * @return array<int, array<string, mixed>>
     */
    public function getFields(): array
    {
        return [
            [
                'code' => 'groupIds',
                'type' => 'textarea',
                'label' => 'ID групп',
                'placeholder' => '1, 8',
                'required' => true,
            ],
        ];
    }

    /**
     * Валидирует и нормализует список ID групп.
     *
     * @param array<string, mixed> $config
     * @return Result
     */
    public function normalizeConfig(array $config): Result
    {
        $groupIds = $this->normalizePositiveIds($config['groupIds'] ?? []);
        if ($groupIds === []) {
            return $this->error('Укажите хотя бы один ID группы.');
        }

        return $this->success([
            'groupIds' => $groupIds,
        ]);
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
