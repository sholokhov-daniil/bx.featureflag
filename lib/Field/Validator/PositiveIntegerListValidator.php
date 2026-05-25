<?php

namespace Sholokhov\Featureflag\Field\Validator;

use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\FieldInterface;
use Sholokhov\Featureflag\Field\Normalizer\ListNormalizer;

/**
 * Проверяет, что список состоит из положительных целых чисел.
 */
final class PositiveIntegerListValidator extends AbstractFieldValidator
{
    /**
     * @param string $messageTemplate Шаблон сообщения, где %s заменяется ошибочным значением.
     */
    public function __construct(
        private readonly string $messageTemplate,
    ) {
    }

    /**
     * Валидация значения свойства
     *
     * @param mixed $value
     * @param FieldInterface $field
     * @return Result
     */
    public function validate(mixed $value, FieldInterface $field): Result
    {
        if (!is_array($value)) {
            return $this->error($field, 'Некорректный список идентификаторов.');
        }

        foreach ($value as $item) {
            if (!ListNormalizer::isPositiveInteger($item)) {
                return $this->error($field, sprintf($this->messageTemplate, (string)$item));
            }
        }

        return $this->success();
    }
}
