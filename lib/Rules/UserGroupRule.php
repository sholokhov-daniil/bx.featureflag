<?php

namespace Sholokhov\Featureflag\Rules;

use CUser;
use Sholokhov\Featureflag\RuleInterface;

/**
 * Правило доступа к фиче по группам текущего пользователя.
 *
 * Правило используется как runtime-ограничение и проверяет,
 * пересекаются ли группы текущего пользователя с заранее заданным списком.
 *
 * Конфигурация правила передаётся извне через конструктор:
 * - `$groupIds` — список разрешённых ID групп
 * - `$supportedCodes` — список кодов фич, к которым применяется правило
 *
 * Если список кодов фич не передан, правило применяется ко всем флагам.
 * Все ID групп нормализуются один раз в конструкторе.
 */
final readonly class UserGroupRule implements RuleInterface
{
    /**
     * Нормализованный список разрешённых ID групп.
     *
     * @var int[]
     */
    private array $groupIds;

    /**
     * Список кодов фич, к которым применяется правило.
     *
     * @var string[]
     */
    private array $supportedCodes;

    /**
     * @param array<int|string> $groupIds Список разрешённых ID групп
     * @param string[] $supportedCodes Список кодов фич, для которых действует правило
     */
    public function __construct(
        array $groupIds,
        array $supportedCodes = [],
    )
    {
        $this->groupIds = $this->normalizeIds($groupIds);
        $this->supportedCodes = $supportedCodes;
    }

    /**
     * Проверяет, должно ли правило участвовать в вычислении указанной фичи.
     *
     * Если `supportedCodes` пустой, правило считается глобальным
     * и применяется ко всем флагам.
     *
     * @param string $code Код проверяемой фичи
     * @return bool
     */
    public function isSupported(string $code): bool
    {
        if ($this->supportedCodes === []) {
            return true;
        }

        return in_array($code, $this->supportedCodes, true);
    }

    /**
     * Проверяет, разрешена ли фича для текущего пользователя по его группам.
     *
     * Правило возвращает `true`, если:
     * - в конфигурации есть хотя бы одна допустимая группа
     * - у текущего пользователя определены группы
     * - есть пересечение между группами пользователя и разрешёнными группами
     *
     * @param string $code Код фичи
     * @return bool
     */
    public function isEnabled(string $code): bool
    {
        if ($this->groupIds === []) {
            return false;
        }

        $currentGroupIds = $this->getCurrentUserGroupIds();

        if ($currentGroupIds === []) {
            return false;
        }

        return array_intersect($this->groupIds, $currentGroupIds) !== [];
    }

    /**
     * Нормализует список идентификаторов:
     * - приводит значения к `int`
     * - отбрасывает неположительные значения
     * - убирает дубликаты
     *
     * @param array<int|string> $ids
     * @return int[]
     */
    private function normalizeIds(array $ids): array
    {
        $result = [];

        foreach ($ids as $id) {
            $id = (int)$id;

            if ($id > 0) {
                $result[$id] = $id;
            }
        }

        return array_values($result);
    }

    /**
     * Возвращает список групп текущего пользователя.
     *
     * Сначала используется `GetUserGroupArray()`, если он доступен,
     * иначе выполняется fallback на `CUser::GetUserGroup()`.
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
            return $this->normalizeIds((array)$USER->GetUserGroupArray());
        }

        if (method_exists($USER, 'GetID')) {
            return $this->normalizeIds(CUser::GetUserGroup((int)$USER->GetID()));
        }

        return [];
    }
}
