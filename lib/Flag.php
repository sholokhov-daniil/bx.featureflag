<?php

namespace Sholokhov\Featureflag;

/**
 * Описание флага
 */
final readonly class Flag implements FlagInterface
{
    /**
     * @param string $code
     * @param string $name
     * @param string $description
     * @param bool $enabled
     * @param RuleInterface[] $rules
     */
    public function __construct(
        private string $code,
        private string $name,
        private string $description,
        private bool $enabled,
        private array $rules
    )
    {
    }

    /**
     * Активность флага
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        foreach ($this->rules as $rule) {
            if (!$rule->isEnabled($this->code)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Возвращает символьный код флага
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Название флага
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Подробное описание флага
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}