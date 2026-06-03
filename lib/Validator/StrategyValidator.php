<?php

namespace Sholokhov\Featureflag\Validator;

use Sholokhov\Featureflag\ServiceProvider;

use Bitrix\Main\Error;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Result;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Производит проверку корректности настройки стратегии фичи
 */
class StrategyValidator
{
    /**
     * Валидация стратегии
     *
     * @param array $strategy
     * @return Result
     * @throws ObjectNotFoundException
     * @throws NotFoundExceptionInterface
     */
    public function validate(array $strategy): Result
    {
        $type = trim((string)($strategy['type'] ?? ''));
        $config = $strategy['config'] ?? [];

        if ($type === '') {
            return $this->error('Не выбран тип стратегии доступа');
        }

        if (!is_array($config)) {
            return $this->error('Некорректная конфигурация стратегии доступа');
        }

        $registry = ServiceProvider::getStrategyRegistry();

        $strategy = $registry->get($type);
        if (!$strategy) {
            return $this->error("Стратегия `{$type}` не зарегистрирована");
        }

        $availability = $strategy->getAvailability();
        if (!$availability->isAvailable()) {
            $reason = $availability->getReason();

            return $this->error($reason !== '' ? $reason : "Стратегия `{$type}` недоступна");
        }

        $strategyResult = $strategy->normalizeConfig($config);
        if (!$strategyResult->isSuccess()) {
            return $strategyResult;
        }

        $normalizedConfig = $strategyResult->getData()['config'] ?? [];
        if (!is_array($normalizedConfig)) {
            return $this->error("Стратегия `{$type}` вернула некорректную конфигурацию");
        }

        return (new Result())->setData([
            'strategy' => [
                'type' => $type,
                'config' => $normalizedConfig,
            ],
            'config' => $normalizedConfig,
        ]);
    }

    /**
     * Проверка массива стратегий
     *
     * @param array $strategies
     * @return Result
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     */
    public function validateBulk(array $strategies): Result
    {
        $result = new Result;
        $normalizedStrategies = [];

        foreach ($strategies as $item) {
            if (!is_array($item)) {
                $result->addErrors($this->error('Некорректная стратегия доступа')->getErrors());
                continue;
            }

            $validateResult = $this->validate($item);
            if (!$validateResult->isSuccess()) {
                $result->addErrors($validateResult->getErrors());
                continue;
            }

            $normalizedStrategy = $validateResult->getData()['strategy'] ?? null;
            if (is_array($normalizedStrategy)) {
                $normalizedStrategies[] = $normalizedStrategy;
            }
        }

        return $result->setData([
            'strategies' => $normalizedStrategies,
        ]);
    }

    /**
     * Создаёт ошибочный Result с привязкой к полю стратегий.
     *
     * @param string $message Текст ошибки
     * @return Result
     */
    private function error(string $message): Result
    {
        return (new Result())->addError(new Error($message, '', [
            'field' => 'strategies',
        ]));
    }
}
