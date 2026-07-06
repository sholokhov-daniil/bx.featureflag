<?php

declare(strict_types=1);

namespace Sholokhov\Featureflag\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;

/**
 * Преобразует ошибки внутренних операций в ошибки админского API с привязкой к полям формы.
 *
 * Mapper сохраняет исходные сообщения и коды ошибок, но дополняет customData['field'],
 * чтобы UI мог подсветить конкретное поле.
 */
final class AdminFeatureFlagErrorMapper
{
    /**
     * Создаёт Result с ошибками исходного Result, обогащёнными данными поля формы.
     *
     * @param Result $source Исходный Result.
     * @return Result Result с обогащёнными ошибками.
     */
    public function enrichFailedResult(Result $source): Result
    {
        $result = new Result();
        $this->appendErrors($result, $source);

        return $result;
    }

    /**
     * Копирует ошибки из одного Result в другой и дополняет их полем формы, если возможно.
     *
     * @param Result $target Целевой Result.
     * @param Result $source Исходный Result.
     * @return void
     */
    public function appendErrors(Result $target, Result $source): void
    {
        foreach ($source->getErrors() as $error) {
            $target->addError($this->withFieldCustomData($error));
        }
    }

    /**
     * Добавляет ошибку, привязанную к полю формы.
     *
     * @param Result $result Результат операции.
     * @param string $field Имя поля формы.
     * @param string $message Текст ошибки.
     * @param string|int $code Код ошибки.
     * @return void
     */
    public function addFieldError(Result $result, string $field, string $message, string|int $code = ''): void
    {
        $result->addError(new Error($message, (string)$code, ['field' => $field]));
    }

    /**
     * Возвращает Error с заполненным customData['field'], если поле можно определить.
     *
     * @param Error $error Исходная ошибка.
     * @return Error Ошибка с сохранённым сообщением, кодом и customData.
     */
    private function withFieldCustomData(Error $error): Error
    {
        $customData = $error->getCustomData();
        if (!is_array($customData)) {
            /** @var array<string, mixed> $customData */
            $customData = [];
        }

        $field = $customData['field'] ?? null;
        if (!is_string($field) || $field === '') {
            $field = $this->guessFieldFromError($error);
            if ($field !== null) {
                $customData['field'] = $field;
            }
        }

        return new Error($error->getMessage(), (string)$error->getCode(), $customData);
    }

    /**
     * Пытается определить поле формы по коду или тексту системной ошибки.
     *
     * @param Error $error Исходная ошибка.
     * @return string|null Имя поля формы или null.
     */
    private function guessFieldFromError(Error $error): ?string
    {
        $code = $error->getCode();
        if (is_string($code) && $code !== '') {
            $field = $this->normalizeErrorField(explode('.', $code, 2)[0]);
            if ($field !== null) {
                return $field;
            }
        }

        return $this->guessFieldFromErrorMessage($error->getMessage());
    }

    /**
     * Нормализует имя поля из кода ошибки.
     *
     * @param string $field Кандидат имени поля.
     * @return string|null Каноническое имя поля или null.
     */
    private function normalizeErrorField(string $field): ?string
    {
        return match ($field) {
            AdminFeatureFlagField::CODE,
            AdminFeatureFlagField::NAME,
            AdminFeatureFlagField::DESCRIPTION,
            AdminFeatureFlagField::ENABLED,
            AdminFeatureFlagField::AVAILABLE_IN_JS,
            AdminFeatureFlagField::TAG_ID,
            AdminFeatureFlagField::STRATEGIES,
            AdminFeatureFlagField::ID => $field,
            'available_in_js', 'AVAILABLE_IN_JS', 'js' => AdminFeatureFlagField::AVAILABLE_IN_JS,
            'tag', 'tag_id' => AdminFeatureFlagField::TAG_ID,
            'strategy', 'strategies[]' => AdminFeatureFlagField::STRATEGIES,
            default => null,
        };
    }

    /**
     * Пытается определить поле формы по тексту системной ошибки.
     *
     * @param string $message Текст ошибки.
     * @return string|null Имя поля формы или null.
     */
    private function guessFieldFromErrorMessage(string $message): ?string
    {
        $normalized = mb_strtolower($message);

        if (
            str_contains($normalized, 'тег')
            || str_contains($normalized, 'tag')
            || str_contains($normalized, 'идентификатор')
            || str_contains($normalized, 'id')
        ) {
            return AdminFeatureFlagField::TAG_ID;
        }

        if (str_contains($normalized, 'код') || str_contains($normalized, 'code')) {
            return AdminFeatureFlagField::CODE;
        }

        if (str_contains($normalized, 'назван') || str_contains($normalized, 'name')) {
            return AdminFeatureFlagField::NAME;
        }

        if (str_contains($normalized, 'описан') || str_contains($normalized, 'description')) {
            return AdminFeatureFlagField::DESCRIPTION;
        }

        if (str_contains($normalized, 'availableinjs') || str_contains($normalized, 'available_in_js') || str_contains($normalized, 'js')) {
            return AdminFeatureFlagField::AVAILABLE_IN_JS;
        }

        if (str_contains($normalized, 'enabled') || str_contains($normalized, 'статус')) {
            return AdminFeatureFlagField::ENABLED;
        }

        if (str_contains($normalized, 'стратег') || str_contains($normalized, 'strategy')) {
            return AdminFeatureFlagField::STRATEGIES;
        }

        return null;
    }
}
