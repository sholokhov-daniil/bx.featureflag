<?php

namespace Sholokhov\Featureflag\Repository;

use Sholokhov\Featureflag\RuleInterface;

interface RuleRegistryInterface
{
    /**
     * Регистрация пользовательской проверки активности флага
     *
     * @param RuleInterface $rule
     * @return RuleRegistryInterface
     */
    public function register(RuleInterface $rule): RuleRegistryInterface;

    /**
     * Возвращает все зарегистрированные правила
     *
     * @return RuleInterface[]
     */
    public function getRules(): array;

    /**
     * Возвращает все правила доступные для фичи
     *
     * @param string $code
     * @return RuleInterface[]
     */
    public function getByCode(string $code): array;
}