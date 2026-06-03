<?php

namespace Sholokhov\Featureflag\Field;

use Sholokhov\Featureflag\Field\Validator\FieldValidatorInterface;
use Bitrix\Main\Result;

trait ValidatorAwareTrait
{
    /**
     * Валидаторы значения свойства
     *
     * @var FieldValidatorInterface[]
     */
    protected array $validators = [];

    /**
     * Добавление валидатора значения свойства
     *
     * @param FieldValidatorInterface $validator
     * @return self
     */
    public function addValidator(FieldValidatorInterface $validator): self
    {
        $this->validators[] = $validator;
        return $this;
    }

    /**
     * Валидация значения на основе конфигурации свойства
     *
     * @param mixed $value
     * @return Result
     */
    public function validateValue(mixed $value): Result
    {
        $result = new Result();

        if ($this->isRequired() && $this->isEmptyValue($value)) {
            $result->addError($this->createError(
                $this->requiredMessage ?: sprintf('Заполните поле "%s".', $this->getName())
            ));
        }

        foreach ($this->validators as $validator) {
            $validateResult = $validator->validate($value, $this);
            if (!$validateResult->isSuccess()) {
                $result->addErrors($validateResult->getErrors());
            }
        }

        return $result;
    }
}