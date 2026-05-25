<?php

namespace Sholokhov\Featureflag\Field\Validator;

use Bitrix\Main\Result;
use Sholokhov\Featureflag\Field\FieldInterface;

/**
 * Проверяет список SITE_ID.
 */
final class SiteIdListValidator extends AbstractFieldValidator
{
    private const string SITE_ID_PATTERN = '/^[A-Za-z0-9_.-]{1,50}$/';

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
            return $this->error($field, 'Некорректный список ID сайтов.');
        }

        foreach ($value as $siteId) {
            $siteId = (string)$siteId;
            if (!preg_match(self::SITE_ID_PATTERN, $siteId)) {
                return $this->error($field, "Некорректный ID сайта: {$siteId}");
            }
        }

        return $this->success();
    }
}
