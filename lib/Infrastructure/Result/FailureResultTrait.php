<?php

declare(strict_types=1);

namespace Sholokhov\FeatureFlag\Infrastructure\Result;

use Bitrix\Main\Error;
use Bitrix\Main\Result;

/**
 * Вспомогательный Trait для формирования результатов с ошибками
 *
 * Инкапсулирует создание Bitrix Result с сообщением и кодом ошибки
 */
trait FailureResultTrait
{
    /**
     * Формирует результат с ошибкой
     *
     * @param string     $message Текст ошибки
     * @param string|int $code    Код ошибки
     * @param array      $context Контекст/дополнительные данные ошибки
     *
     * @return Result
     */
    protected function errorResult(string $message, string|int $code = 0, array $context = []): Result
    {
        return new Result()
            ->addError(new Error($message, $code, $context));
    }
}