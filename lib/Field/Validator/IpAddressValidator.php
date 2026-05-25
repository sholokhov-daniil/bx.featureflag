<?php

namespace Sholokhov\Featureflag\Field\Validator;

use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\FieldInterface;
use Sholokhov\Featureflag\Field\Normalizer\IpNormalizer;

/**
 * Проверяет одиночный IP-адрес.
 */
final class IpAddressValidator extends AbstractFieldValidator
{
    public function __construct(
        private readonly string $message,
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
        $ip = trim((string)$value);
        if ($ip === '' || IpNormalizer::canonical($ip) !== null) {
            return $this->success();
        }

        return $this->error($field, $this->message);
    }
}
