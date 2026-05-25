<?php

namespace Sholokhov\Featureflag\Strategies;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\FieldInterface;
use Sholokhov\Featureflag\Field\Normalizer\ListNormalizer;
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
        return ListNormalizer::strings($value);
    }

    /**
     * Нормализует список положительных идентификаторов.
     *
     * @param mixed $value
     * @return int[]
     */
    protected function normalizePositiveIds(mixed $value): array
    {
        return array_values(array_filter(
            ListNormalizer::positiveIntegers($value),
            static fn(mixed $id): bool => is_int($id)
        ));
    }

    /**
     * Валидирует и нормализует конфигурацию через описание свойств стратегии.
     *
     * @param array<string, mixed> $config
     * @return Result
     */
    public function normalizeConfig(array $config): Result
    {
        $result = new Result();
        $normalizedConfig = [];

        foreach ($this->getFieldsByCode() as $code => $field) {
            $value = $field->normalizeValue($config[$code] ?? null);
            $fieldResult = $field->validateValue($value);

            if (!$fieldResult->isSuccess()) {
                $result->addErrors($fieldResult->getErrors());
            }

            $normalizedConfig[$code] = $value;
        }

        if (!$result->isSuccess()) {
            return $result;
        }

        $configResult = $this->validateNormalizedConfig($normalizedConfig);
        if (!$configResult->isSuccess()) {
            return $configResult;
        }

        return $this->success($normalizedConfig);
    }

    /**
     * Денормализует сохранённую конфигурацию через описание свойств стратегии.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function denormalizeConfig(array $config): array
    {
        $denormalizedConfig = $config;

        foreach ($this->getFieldsByCode() as $code => $field) {
            $denormalizedConfig[$code] = $field->denormalizeValue($config[$code] ?? null);
        }

        return $denormalizedConfig;
    }

    /**
     * Дополнительная проверка уже нормализованной конфигурации.
     *
     * @param array<string, mixed> $config
     * @return Result
     */
    protected function validateNormalizedConfig(array $config): Result
    {
        return new Result();
    }

    /**
     * Возвращает свойства стратегии, индексированные по коду.
     *
     * @return array<string, FieldInterface>
     */
    private function getFieldsByCode(): array
    {
        $fields = [];

        foreach ($this->getFields() as $field) {
            $fields[$field->getCode()] = $field;
        }

        return $fields;
    }

    /**
     * Создаёт ошибочный Result с одним сообщением.
     *
     * @param string $message Текст ошибки
     * @return Result
     */
    protected function error(string $message): Result
    {
        return (new Result())->addError(new Error($message, '', [
            'field' => 'strategies',
        ]));
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
