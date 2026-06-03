<?php

namespace Sholokhov\Featureflag\Field\Validator;

use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\FieldInterface;

interface FieldValidatorInterface
{
    /**
     * Валидация значения свойства
     *
     * @param mixed $value
     * @param FieldInterface $field
     * @return Result
     */
    public function validate(mixed $value, FieldInterface $field): Result;
}