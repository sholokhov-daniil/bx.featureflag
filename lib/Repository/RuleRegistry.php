<?php

namespace Sholokhov\Featureflag\Repository;

use Sholokhov\Featureflag\RuleInterface;

/**
 * Реестр правил проверки активности фича-флагов
 *
 * Хранит и управляет пользовательскими правилами (RuleInterface),
 * которые могут влиять на доступность фич в runtime.
 *
 * Позволяет разработчикам расширять поведение фича-флагов,
 * добавляя собственные условия (например: IP, cookie, роль пользователя и т.д.)
 * без изменения структуры хранения флагов.
 */
class RuleRegistry implements RuleRegistryInterface
{
    /**
     * Список зарегистрированных правил
     *
     * @var RuleInterface[]
     */
    private array $rules = [];

    /**
     * Регистрирует пользовательское правило
     *
     * Правило будет участвовать в проверке активности фичи,
     * если оно поддерживает соответствующий код фичи.
     *
     * @param RuleInterface $rule Экземпляр правила
     *
     * @return static Текущий экземпляр (для chain-вызовов)
     */
    public function register(RuleInterface $rule): static
    {
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * Возвращает все зарегистрированные правила
     *
     * Используется, когда необходимо получить полный список
     * правил без фильтрации.
     *
     * @return RuleInterface[] Список всех правил
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Возвращает правила, применимые к указанной фиче
     *
     * Фильтрует правила по методу {@see RuleInterface::isSupported()},
     * оставляя только те, которые должны участвовать в проверке
     * конкретного фича-флага.
     *
     * @param string $code Символьный код фичи
     *
     * @return RuleInterface[] Список подходящих правил
     */
    public function getByCode(string $code): array
    {
        $result = [];

        foreach ($this->rules as $rule) {
            if ($rule->isSupported($code)) {
                $result[] = $rule;
            }
        }

        return $result;
    }
}