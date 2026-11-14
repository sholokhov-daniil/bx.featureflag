<?php

declare(strict_types=1);

namespace Sholokhov\Featureflag\Presentation\Http\Response\Feature;

use Bitrix\Main\Type\Contract\Arrayable;

/**
 * Структура ответа endpoint смены активности фичи-флага
 */
final readonly class ToggleResponse implements Arrayable
{
    public function __construct(
        public bool $enabled,
    )
    {
    }

    /**
     * Преобразование DTO в массив данных
     *
     * @return bool[]
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
        ];
    }
}