<?php

namespace Sholokhov\Featureflag\DTO;

use Sholokhov\Featureflag\Validator\FlagValidator;
use Sholokhov\Featureflag\Http\Request\Normalizer\EnabledValueNormalizer;

use Bitrix\Main\Result;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Validation\Rule\Min;
use Bitrix\Main\Validation\Rule\InArray;
use Bitrix\Main\Validation\Rule\Length;
use Bitrix\Main\Validation\Rule\NotEmpty;
use Bitrix\Main\Validation\Rule\RegExp;

class FeatureFlagPayload
{
    /**
     * @param string $code Символьный код фича-флага.
     * @param string $name Название фича-флага.
     * @param bool $enabled Признак активности.
     * @param string $description Описание фича-флага.
     * @param int|null $tagId Идентификатор тега (опционально).
     * @param string $removePlannedAt Плановая дата удаления флага
     * @param array $strategies Стратегии доступа.
     */
    public function __construct(
        #[NotEmpty(errorMessage: 'Не заполнен код флага')]
        #[RegExp('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', errorMessage: 'Код флага может содержать только буквы, цифры, ".", "_" и "-"')]
        #[Length(max: 255, errorMessage: 'Код флага не должен быть длиннее 255 символов')]
        public string $code,
        #[NotEmpty(errorMessage: 'Не заполнено название флага')]
        #[Length(max: 255, errorMessage: 'Название флага не должно быть длиннее 255 символов')]
        public string $name,
        #[InArray([true, false], strict: true, errorMessage: 'Неправильное значение поля активности')]
        public bool $enabled,
        #[Length(max: 5000, errorMessage: 'Описание флага не должно быть длиннее 5000 символов')]
        public string $description = '',
        #[Min(min: 0, errorMessage: 'Неправильное значение тега')]
        public ?int $tagId = null,
        #[RegExp('/^$|^\d{2}\.\d{2}\.\d{4}$/', errorMessage: 'Плановая дата удаления должна быть в формате DD-MM-YY')]
        public string $removePlannedAt = '',
        public array $strategies = [],
    )
    {
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
            name: (string)$request->get('name'),
            description: (string)$request->get('description'),
            enabled: EnabledValueNormalizer::normalize($request->get('enabled')),
            tagId: (int)$request->get('tagId'),
            removePlannedAt: trim((string)$request->get('removePlannedAt')),
            strategies: (array)$request->get('strategies'),
        );
    }

    /**
     * Валидация информации по тегу
     *
     * @return Result
     */
    public function validate(): Result
    {
        return (new FlagValidator)->validate($this);
    }
}