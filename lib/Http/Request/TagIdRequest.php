<?php

namespace Sholokhov\Featureflag\Http\Request;

use Bitrix\Main\HttpRequest;
use Bitrix\Main\Validation\Rule\Length;
use Bitrix\Main\Validation\Rule\NotEmpty;
use Bitrix\Main\Validation\Rule\RegExp;

/**
 * DTO запроса с идентификатором тега.
 */
final class TagIdRequest
{
    /**
     * @param string $id Идентификатор тега.
     */
    public function __construct(
        #[NotEmpty(errorMessage: 'Не заполнен идентификатор тега')]
        #[RegExp('/^[1-9][0-9]*$/', errorMessage: 'Неправильное значение идентификатора тега')]
        #[Length(max: 20, errorMessage: 'Неправильное значение идентификатора тега')]
        public readonly string $id,
    ) {
    }

    /**
     * @param HttpRequest $request
     * @return self
     */
    public static function fromRequest(HttpRequest $request): self
    {
        return new self(
            id: trim((string)$request->get('id')),
        );
    }
}
