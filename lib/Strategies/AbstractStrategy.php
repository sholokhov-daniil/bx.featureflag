<?php

namespace Sholokhov\Featureflag\Strategies;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Sholokhov\Featureflag\Strategy\FeatureStrategyInterface;

/**
 * Базовый класс для встроенных стратегий доступа.
 *
 * Содержит общие helpers для разбора списков значений и формирования Result.
 */
abstract class AbstractStrategy implements FeatureStrategyInterface
{
    /**
     * Разбивает строку или массив на уникальный список непустых значений.
     *
     * @param mixed $value
     * @return string[]
     */
    protected function splitValues(mixed $value): array
    {
        if (is_array($value)) {
            $rawValues = $value;
        } elseif (is_string($value)) {
            $rawValues = preg_split('/[\s,;]+/', $value) ?: [];
        } else {
            $rawValues = [];
        }

        $result = [];
        foreach ($rawValues as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $result[$item] = $item;
            }
        }

        return array_values($result);
    }

    /**
     * Нормализует список положительных идентификаторов.
     *
     * @param mixed $value
     * @return int[]
     */
    protected function normalizePositiveIds(mixed $value): array
    {
        $ids = [];

        foreach ($this->splitValues($value) as $item) {
            $id = (int)$item;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * Создаёт ошибочный Result с одним сообщением.
     *
     * @param string $message Текст ошибки
     * @return Result
     */
    protected function error(string $message): Result
    {
        return (new Result())->addError(new Error($message));
    }

    /**
     * Создаёт успешный Result с нормализованной конфигурацией.
     *
     * @param array<string, mixed> $config
     * @return Result
     */
    protected function success(array $config): Result
    {
        return (new Result())->setData([
            'config' => $config,
        ]);
    }
}
