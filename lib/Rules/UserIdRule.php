<?php

namespace Sholokhov\Featureflag\Rules;

use Sholokhov\Featureflag\RuleInterface;

/**
 * Правило доступа к фиче по ID текущего пользователя.
 *
 * Правило используется как runtime-ограничение и проверяет,
 * входит ли ID текущего пользователя в заранее заданный список.
 *
 * Конфигурация правила передаётся извне через конструктор:
 * - `$userIds` — список разрешённых ID пользователей
 * - `$supportedCodes` — список кодов фич, к которым применяется правило
 *
 * Если список кодов фич не передан, правило применяется ко всем флагам.
 * Все ID нормализуются один раз в конструкторе.
 */
final readonly class UserIdRule implements RuleInterface
{
    /**
     * Нормализованный список разрешённых ID пользователей.
     *
     * @var int[]
     */
    private array $userIds;

    /**
     * Список кодов фич, к которым применяется правило.
     *
     * @var string[]
     */
    private array $supportedCodes;

    /**
     * @param array<int|string> $userIds Список разрешённых ID пользователей
     * @param string[] $supportedCodes Список кодов фич, для которых действует правило
     */
    public function __construct(
        array $userIds,
        array $supportedCodes = [],
    )
    {
        $this->userIds = $this->normalizeIds($userIds);
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
     * Проверяет, разрешена ли фича для текущего пользователя.
     *
     * Правило возвращает `true`, если:
     * - в конфигурации есть хотя бы один допустимый ID пользователя
     * - текущий пользователь определён
     * - ID текущего пользователя входит в разрешённый список
     *
     * @param string $code Код фичи
     * @return bool
     */
    public function isEnabled(string $code): bool
    {
        if ($this->userIds === []) {
            return false;
        }

        $currentUserId = $this->getCurrentUserId();

        if ($currentUserId <= 0) {
            return false;
        }

        return in_array($currentUserId, $this->userIds, true);
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
     * Возвращает ID текущего пользователя из глобального объекта Bitrix.
     *
     * Если пользователь не определён, возвращает `0`.
     *
     * @return int
     */
    private function getCurrentUserId(): int
    {
        global $USER;

        if (is_object($USER) && method_exists($USER, 'GetID')) {
            return (int)$USER->GetID();
        }

        return 0;
    }
}
