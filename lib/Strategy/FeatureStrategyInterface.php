<?php

namespace Sholokhov\Featureflag\Strategy;

use Bitrix\Main\Result;

/**
 * Контракт стратегии доступа, которую можно настроить из UI.
 */
interface FeatureStrategyInterface
{
    /**
     * Уникальный код стратегии.
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Название стратегии для административного UI.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Короткое описание стратегии для административного UI.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Описание полей настройки стратегии.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFields(): array;

    /**
     * Валидирует и нормализует конфигурацию перед сохранением.
     *
     * В Result нужно положить нормализованный массив по ключу `config`.
     *
     * @param array<string, mixed> $config
     * @return Result
     */
    public function normalizeConfig(array $config): Result;

    /**
     * Проверяет, разрешает ли стратегия доступ к фиче в текущем runtime-контексте.
     *
     * @param string $featureCode
     * @param array<string, mixed> $config
     * @return bool
     */
    public function isEnabled(string $featureCode, array $config): bool;
}
