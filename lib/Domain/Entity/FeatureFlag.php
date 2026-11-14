<?php

declare(strict_types=1);

namespace Sholokhov\Featureflag\Domain\Entity;

use Sholokhov\Featureflag\FeatureInterface;

use Bitrix\Main\Type\DateTime;

/**
 * Доменная сущность фича-флага.
 *
 * Представляет функциональный переключатель с уникальным символьным
 * кодом, названием, описанием и глобальным состоянием активности.
 *
 * Инкапсулирует изменение состояния флага и предоставляет
 * доступ к его основным характеристикам.
 *
 * Сущность не зависит от способа хранения данных и не выполняет
 * операции с базой данных.
 */
final class FeatureFlag implements FeatureInterface
{
    /**
     * Создаёт экземпляр фича-флага.
     *
     * @param string    $code         Уникальный символьный код флага.
     * @param string    $name         Название флага.
     * @param string    $description  Описание назначения флага.
     * @param bool      $enabled      Глобальное состояние активности флага.
     * @param ?int      $tagId        Принадлежность фичи к тегу
     * @param ?DateTime $removePlanAt Дата планового удаления фичи
     * @param array     $strategies   Стратегии доступноси фичи
     */
    public function __construct(
        private readonly string $code,
        private readonly string $name,
        private readonly string $description,
        private bool $enabled,
        private readonly ?int $tagId = null,
        private readonly ?DateTime $removePlanAt = null,
        private readonly array $strategies = [],
    ) {
    }

    /**
     * Возвращает уникальный символьный код флага.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Возвращает название флага.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Возвращает описание флага.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Проверяет глобальное состояние активности флага.
     *
     * Не учитывает стратегии доступа и дополнительные runtime-правила.
     * Возвращает исключительно сохранённое состояние флага.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Включает флаг на глобальном уровне.
     *
     * Изменяет внутреннее состояние сущности на активное.
     * Не выполняет сохранение изменений в хранилище.
     *
     * @return void
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Отключает флаг на глобальном уровне.
     *
     * Изменяет внутреннее состояние сущности на неактивное.
     * Не выполняет сохранение изменений в хранилище.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Возвращает принадлежность к тегу
     *
     * @return int
     */
    public function getTagId(): int
    {
        return $this->tagId;
    }

    /**
     * Дата планового удаления флага
     *
     * @return DateTime|null
     */
    public function getRemovePlanAt(): ?DateTime
    {
        return $this->removePlanAt;
    }

    /**
     * Возвращает стратегии доступности фичи
     *
     * @return array
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }
}
