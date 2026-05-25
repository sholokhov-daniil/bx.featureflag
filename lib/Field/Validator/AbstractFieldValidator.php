<?php

namespace Sholokhov\Featureflag\Field\Validator;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\FieldInterface;

/**
 * Общие helpers валидаторов свойств стратегии.
 */
abstract class AbstractFieldValidator implements FieldValidatorInterface
{
    /**
     * Создаёт Result с одной ошибкой свойства.
     *
     * @param FieldInterface $field Конфигурация свойства
     * @param string $message Текст ошибки
     * @param string $code Код ошибки
     * @return Result
     */
    protected function error(FieldInterface $field, string $message, string $code = 'invalid'): Result
    {
        return (new Result())->addError(new Error(
            $message,
            'strategies.' . $field->getCode() . '.' . $code,
            ['field' => 'strategies']
        ));
    }

    /**
     * Создаёт успешный Result.
     *
     * @return Result
     */
    protected function success(): Result
    {
        return new Result();
    }
}
