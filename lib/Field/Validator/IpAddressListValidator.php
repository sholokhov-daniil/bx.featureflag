<?php

namespace Sholokhov\Featureflag\Field\Validator;

use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\FieldInterface;
use Sholokhov\Featureflag\Field\Normalizer\IpNormalizer;

/**
 * Проверяет список IP-адресов.
 */
final class IpAddressListValidator extends AbstractFieldValidator
{
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
            return $this->error($field, 'Некорректный список IP-адресов.');
        }

        foreach ($value as $ip) {
            $ip = (string)$ip;
            if (IpNormalizer::canonical($ip) === null) {
                return $this->error($field, "Некорректный IP-адрес: {$ip}");
            }
        }

        return $this->success();
    }
}
