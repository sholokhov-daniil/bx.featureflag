<?php

namespace Sholokhov\Featureflag\Strategies;

use Throwable;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\FieldInterface;
use Sholokhov\Featureflag\Field\Normalizer\ListNormalizer;
use Sholokhov\Featureflag\Strategy\FeatureStrategyInterface;
use Sholokhov\Featureflag\Strategy\StrategyAvailability;

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
     * Проверяет, доступна ли стратегия в текущем окружении.
     *
     * @return StrategyAvailability
     */
    public function getAvailability(): StrategyAvailability
    {
        return StrategyAvailability::available();
    }

    /**
     * Валидирует и нормализует конфигурацию через описание свойств стратегии.
     *
     * @param array<string, mixed> $config
     * @return Result
     */
    public function normalizeConfig(array $config): Result
    {
        $availability = $this->getAvailability();
        if (!$availability->isAvailable()) {
            return $this->error($this->getUnavailableMessage($availability));
        }

        $result = new Result();
        $normalizedConfig = [];

        try {
            $fields = $this->getFieldsByCode();
        } catch (Throwable $exception) {
            return $this->error($this->getUnavailableMessage(
                StrategyAvailability::unavailable($exception->getMessage())
            ));
        }

        foreach ($fields as $code => $field) {
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
        if (!$this->getAvailability()->isAvailable()) {
            return $config;
        }

        $denormalizedConfig = $config;

        try {
            $fields = $this->getFieldsByCode();
        } catch (Throwable) {
            return $config;
        }

        foreach ($fields as $code => $field) {
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
     * Формирует сообщение о недоступности стратегии.
     *
     * @param StrategyAvailability $availability Статус доступности.
     * @return string
     */
    private function getUnavailableMessage(StrategyAvailability $availability): string
    {
        $reason = $availability->getReason();

        return $reason !== ''
            ? $reason
            : sprintf('Стратегия `%s` недоступна', $this->getName());
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
