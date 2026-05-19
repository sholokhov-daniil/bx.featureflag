<?php

namespace Sholokhov\Featureflag\Strategy;

/**
 * Реестр UI-стратегий доступа к фича-флагам.
 */
interface StrategyRegistryInterface
{
    /**
     * Регистрирует стратегию.
     *
     * @param FeatureStrategyInterface $strategy
     * @return static
     */
    public function register(FeatureStrategyInterface $strategy): static;

    /**
     * Возвращает стратегию по коду.
     *
     * @param string $code
     * @return FeatureStrategyInterface|null
     */
    public function get(string $code): ?FeatureStrategyInterface;

    /**
     * Возвращает все зарегистрированные стратегии.
     *
     * @return FeatureStrategyInterface[]
     */
    public function getAll(): array;
}
