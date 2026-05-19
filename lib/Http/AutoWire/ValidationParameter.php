<?php

namespace Sholokhov\Featureflag\Http\AutoWire;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Validation\ValidationService;
use ReflectionParameter;

/**
 * AutoWire-параметр с DTO-валидацией без выброса исключений.
 */
final class ValidationParameter extends Parameter
{
    /**
     * @var \Closure|null
     */
    private ?\Closure $onValidationFailed;

    /**
     * @param string $className
     * @param \Closure $constructor
     * @param \Closure|null $onValidationFailed
     */
    public function __construct(string $className, \Closure $constructor, ?\Closure $onValidationFailed = null)
    {
        parent::__construct($className, $constructor);
        $this->onValidationFailed = $onValidationFailed;
    }

    /**
     * @param ReflectionParameter $parameter
     * @param Result $captureResult
     * @param mixed $newThis
     * @return object|null
     */
    public function constructValue(ReflectionParameter $parameter, Result $captureResult, $newThis = null): ?object
    {
        $object = parent::constructValue($parameter, $captureResult, $newThis);

        /** @var ValidationService $service */
        $service = ServiceLocator::getInstance()->get('main.validation.service');
        $result = $service->validate($object);

        if ($result->isSuccess()) {
            return $object;
        }

        $errors = $this->enrichErrors($result->getErrors());
        $captureResult->addErrors($errors);

        if ($this->onValidationFailed !== null) {
            ($this->onValidationFailed)($errors);
        }

        return null;
    }

    /**
     * @param Error[] $errors
     * @return Error[]
     */
    private function enrichErrors(array $errors): array
    {
        $preparedErrors = [];
        foreach ($errors as $error) {
            $customData = $error->getCustomData();
            if (is_array($customData) && isset($customData['field'])) {
                $preparedErrors[] = $error;
                continue;
            }

            $field = $this->extractFieldFromCode($error->getCode());
            if ($field === null) {
                $preparedErrors[] = $error;
                continue;
            }

            $preparedErrors[] = new Error(
                $error->getMessage(),
                $error->getCode(),
                ['field' => $field],
            );
        }

        return $preparedErrors;
    }

    /**
     * Извлекает имя поля из кода ошибки в формате "field" или "field.subcode".
     *
     * @param int|string $code
     * @return string|null
     */
    private function extractFieldFromCode(int|string $code): ?string
    {
        if (!is_string($code) || $code === '') {
            return null;
        }

        $parts = explode('.', $code, 2);
        $field = trim((string)$parts[0]);
        if ($field === '') {
            return null;
        }

        return $field;
    }
}
