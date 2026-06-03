<?php

namespace Sholokhov\Featureflag\Strategy;

use Throwable;
use Sholokhov\Featureflag\RuleInterface;

/**
 * Runtime-правило, которое применяет сохранённые в БД стратегии флага.
 */
final readonly class StoredStrategyRule implements RuleInterface
{
    /**
     * @param array<int, array{type: string, config: array<string, mixed>}> $items
     * @param StrategyRegistryInterface $registry Реестр стратегий
     */
    public function __construct(
        private array $items,
        private StrategyRegistryInterface $registry,
    ) {
    }

    /**
     * Проверяет, есть ли у флага сохранённые стратегии.
     *
     * @param string $code Код фича-флага
     * @return bool
     */
    public function isSupported(string $code): bool
    {
        return $this->items !== [];
    }

    /**
     * Проверяет доступ по сохранённым стратегиям.
     *
     * Стратегии внутри одного флага работают по OR: достаточно,
     * чтобы доступ разрешила хотя бы одна зарегистрированная стратегия.
     *
     * @param string $code Код фича-флага
     * @return bool
     */
    public function isEnabled(string $code): bool
    {
        foreach ($this->items as $item) {
            $type = (string)($item['type'] ?? '');
            $config = $item['config'] ?? [];

            if (!is_array($config)) {
                continue;
            }

            try {
                $strategy = $this->registry->get($type);
                if ($strategy === null || !$strategy->getAvailability()->isAvailable()) {
                    continue;
                }

                if ($strategy->isEnabled($code, $config)) {
                    return true;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return false;
    }
}
