<?php

namespace Sholokhov\Featureflag\Http\Request;

use Bitrix\Main\HttpRequest;
use Bitrix\Main\Validation\Rule\InArray;
use Bitrix\Main\Validation\Rule\Length;
use Bitrix\Main\Validation\Rule\NotEmpty;
use Bitrix\Main\Validation\Rule\RegExp;
use Sholokhov\Featureflag\Http\Request\Normalizer\EnabledValueNormalizer;

/**
 * DTO запроса переключения активности фича-флага.
 */
final class FeatureToggleRequest
{
    /**
     * @param string $code Символьный код фича-флага.
     * @param bool $enabled Целевое состояние активности.
     */
    public function __construct(
        #[NotEmpty(errorMessage: 'Не заполнен код флага')]
        #[RegExp('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', errorMessage: 'Код флага может содержать только буквы, цифры, ".", "_" и "-"')]
        #[Length(max: 255, errorMessage: 'Код флага не должен быть длиннее 255 символов')]
        public readonly string $code,
        #[InArray([true, false], strict: true, errorMessage: 'Неправильное значение поля активности')]
        public readonly ?bool $enabled,
    ) {
    }

    /**
     * Создаёт DTO из HTTP-запроса контроллера.
     *
     * @param HttpRequest $request
     * @return self
     */
    public static function fromRequest(HttpRequest $request): self
    {
        return new self(
            code: (string)$request->get('code'),
            enabled: EnabledValueNormalizer::normalize($request->get('enabled')),
        );
    }
}
