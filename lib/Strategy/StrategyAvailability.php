<?php

namespace Sholokhov\Featureflag\Strategy;

/**
 * Описывает возможность использовать стратегию в текущем окружении.
 */
final readonly class StrategyAvailability
{
    /**
     * @param bool $available Доступна ли стратегия.
     * @param string $reason Причина недоступности.
     */
    private function __construct(
        private bool $available,
        private string $reason = '',
    ) {
    }

    /**
     * Создаёт статус доступной стратегии.
     *
     * @return self
     */
    public static function available(): self
    {
        return new self(true);
    }

    /**
     * Создаёт статус недоступной стратегии.
     *
     * @param string $reason Причина недоступности.
     * @return self
     */
    public static function unavailable(string $reason): self
    {
        return new self(false, trim($reason));
    }

    /**
     * Создаёт статус недоступной стратегии из-за отстутствия модуля
     *
     * @param string $code
     * @return self
     */
    public static function unavailableModule(string $code): self
    {
        return self::unavailable("Модуль $code недоступен.");
    }

    /**
     * Проверяет, доступна ли стратегия.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * Возвращает причину недоступности стратегии.
     *
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
