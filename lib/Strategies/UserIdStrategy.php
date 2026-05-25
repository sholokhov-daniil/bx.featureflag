<?php

namespace Sholokhov\Featureflag\Strategies;

use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\TextareaField;

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
     * @return array<int, array<string, mixed>>
     */
    public function getFields(): array
    {
        return [
            new TextareaField('userIds')
                ->setName('ID пользователей')
                ->setPlaceholder('1, 15, 42')
                ->setRequired()
        ];
    }

    /**
     * Валидирует и нормализует список ID пользователей.
     *
     * @param array<string, mixed> $config
     * @return Result
     */
    public function normalizeConfig(array $config): Result
    {
        $userIds = $this->normalizePositiveIds($config['userIds'] ?? []);
        if ($userIds === []) {
            return $this->error('Укажите хотя бы один ID пользователя.');
        }

        return $this->success([
            'userIds' => $userIds,
        ]);
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
