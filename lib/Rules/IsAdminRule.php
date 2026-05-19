<?php

namespace Sholokhov\Featureflag\Rules;

use Sholokhov\Featureflag\RuleInterface;

/**
 * Правило доступа к фиче только для администраторов Bitrix.
 *
 * Правило используется как runtime-ограничение и проверяет,
 * возвращает ли текущий пользователь `true` из `$USER->IsAdmin()`.
 *
 * Конфигурация правила передаётся извне через конструктор:
 * - `$supportedCodes` — список кодов фич, к которым применяется правило
 *
 * Если список кодов фич не передан, правило применяется ко всем флагам.
 */
final readonly class IsAdminRule implements RuleInterface
{
    /**
     * Список кодов фич, к которым применяется правило.
     *
     * @var string[]
     */
    private array $supportedCodes;

    /**
     * @param string[] $supportedCodes Список кодов фич, для которых действует правило
     */
    public function __construct(array $supportedCodes = [])
    {
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
     * Проверяет, является ли текущий пользователь администратором.
     *
     * @param string $code Код фичи
     * @return bool
     */
    public function isEnabled(string $code): bool
    {
        global $USER;

        return is_object($USER)
            && method_exists($USER, 'IsAdmin')
            && $USER->IsAdmin();
    }
}
