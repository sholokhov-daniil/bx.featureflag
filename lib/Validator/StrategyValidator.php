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
            return (new Result)->addError(new Error('Не выбран тип стратегии доступа'));
        }

        if (!is_array($config)) {
            return (new Result)->addError(new Error('Некорректная конфигурация стратегии доступа'));
        }

        $registry = ServiceProvider::getStrategyRegistry();

        $strategy = $registry->get($type);
        if (!$strategy) {
            return (new Result)->addError(new Error("Стратегия `{$type}` не зарегистрирована"));
        }

        $strategyResult = $strategy->normalizeConfig($config);
        if (!$strategyResult->isSuccess()) {
            return $strategyResult;
        }

        $normalizedConfig = $strategyResult->getData()['config'] ?? [];
        if (!is_array($normalizedConfig)) {
            return (new Result)->addError(new Error("Стратегия `{$type}` вернула некорректную конфигурацию"));
        }

        return new Result;
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

        foreach ($strategies as $item) {
            $validateResult = $this->validate($item);
            if (!$validateResult->isSuccess()) {
                $result->addErrors($validateResult->getErrors());
            }
        }

        return $result;
    }
}