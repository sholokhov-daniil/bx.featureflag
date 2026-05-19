<?php

namespace Sholokhov\Featureflag\Strategy;

/**
 * Реестр стратегий доступа.
 *
 * Хранит зарегистрированные UI-стратегии и позволяет получить их
 * по коду при сохранении настроек или runtime-проверке флага.
 */
final class StrategyRegistry implements StrategyRegistryInterface
{
    /**
     * @var array<string, FeatureStrategyInterface>
     */
    private array $strategies = [];

    /**
     * Регистрирует стратегию доступа.
     *
     * @param FeatureStrategyInterface $strategy
     * @return static
     */
    public function register(FeatureStrategyInterface $strategy): static
    {
        $this->strategies[$strategy->getCode()] = $strategy;
        return $this;
    }

    /**
     * Возвращает стратегию по её коду.
     *
     * @param string $code
     * @return FeatureStrategyInterface|null
     */
    public function get(string $code): ?FeatureStrategyInterface
    {
        return $this->strategies[$code] ?? null;
    }

    /**
     * Возвращает все зарегистрированные стратегии.
     *
     * @return FeatureStrategyInterface[]
     */
    public function getAll(): array
    {
        return array_values($this->strategies);
    }
}
