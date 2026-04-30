<?php

namespace Sholokhov\Featureflag\Http\Request;

use Bitrix\Main\HttpRequest;
use Bitrix\Main\Validation\Rule\Length;
use Bitrix\Main\Validation\Rule\NotEmpty;

/**
 * DTO запроса создания тега.
 */
final class TagCreateRequest
{
    /**
     * @param string $name Название тега.
     */
    public function __construct(
        #[NotEmpty(errorMessage: 'Не заполнено название тега')]
        #[Length(max: 255, errorMessage: 'Название тега не должно быть длиннее 255 символов')]
        public readonly string $name,
    ) {
    }

    /**
     * @param HttpRequest $request
     * @return self
     */
    public static function fromRequest(HttpRequest $request): self
    {
        return new self(
            name: trim((string)$request->get('name')),
        );
    }
}
