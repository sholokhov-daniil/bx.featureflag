<?php

namespace Sholokhov\Featureflag\Validator;

use Sholokhov\Featureflag\ORM\FeatureTagTable;
use Sholokhov\Featureflag\DTO\FeatureFlagPayload;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\SystemException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Validation\ValidationService;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Проверяет корректность заполненности информации по флагу
 */
class FlagValidator
{
    /**
     * Произвести валидацию флага
     *
     * @param FeatureFlagPayload $flag
     * @return Result
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ObjectNotFoundException
     * @throws NotFoundExceptionInterface
     */
    public function validate(FeatureFlagPayload $flag): Result
    {
        /** @var ValidationService $validator */
        $validator = ServiceLocator::getInstance()->get('main.validation.service');
        $result = $validator->validate($flag);

        if ($flag->tagId && !$this->isTagExists($flag->tagId)) {
            $result->addError(
                new Error(
                    Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_TAG_NOT_FOUND')
                )
            );
        }

        $strategyResult = (new StrategyValidator)->validateBulk($flag->strategies);
        if (!$strategyResult->isSuccess()) {
            $result->addErrors($strategyResult->getErrors());
        } else {
            $normalizedStrategies = $strategyResult->getData()['strategies'] ?? [];
            if (is_array($normalizedStrategies)) {
                $flag->strategies = $normalizedStrategies;
            }
        }

        return $result;
    }

    /**
     * Тег существует
     *
     * @param string $tag
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @autdor Daniil S.
     */
    private function isTagExists(string $tag): bool
    {
        return FeatureTagTable::query()
                ->setSelect([FeatureTagTable::FIELD_ID])
                ->where(FeatureTagTable::FIELD_ID, $tag)
                ->setLimit(1)
                ->fetch() !== false;
    }
}
