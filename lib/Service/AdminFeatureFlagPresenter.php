<?php

declare(strict_types=1);

namespace Sholokhov\Featureflag\Service;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Sholokhov\Featureflag\ORM\FeatureTable;
use Sholokhov\Featureflag\ORM\FeatureTagTable;

/**
 * Преобразует ORM-строки фича-флагов и тегов в стабильный формат API админки.
 *
 * Класс инкапсулирует presentation layer: загрузку связанных пользователей/тегов,
 * нормализацию типов и форматирование дат для UI.
 *
 * @phpstan-type FeatureRow array<string, mixed>
 * @phpstan-type UserRow array<string, mixed>
 * @phpstan-type TagRow array<string, mixed>
 * @phpstan-type AdminTag array{id: int, name: string}
 * @phpstan-type AdminFlag array{
 *     code: string,
 *     name: string,
 *     description: string,
 *     enabled: bool,
 *     tagId: int|null,
 *     tag: array{id: int|null, name: string},
 *     strategies: array<int, mixed>,
 *     createdAt: string,
 *     updatedAt: string,
 *     removePlannedAt: string,
 *     createdBy: array{id: int, title: string, url: string}
 * }
 */
final class AdminFeatureFlagPresenter
{
    private const string DATE_FORMAT = 'd.m.Y';
    private const string DATETIME_FORMAT = 'd.m.Y H:i:s';

    /**
     * Возвращает список фича-флагов в формате ответа админского API.
     *
     * @param array<int, array<string, mixed>> $rows ORM-строки фича-флагов.
     * @return array<int, array<string, mixed>> Подготовленные элементы списка.
     * @throws ArgumentException При ошибке ORM-запроса связанных данных.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств связанных данных.
     * @throws SystemException При системной ошибке ORM.
     */
    public function presentFlags(array $rows): array
    {
        /** @var array<int, int> $userIds */
        $userIds = $this->collectPositiveIds($rows, FeatureTable::FIELD_CREATED_BY);

        /** @var array<int, int> $tagIds */
        $tagIds = $this->collectPositiveIds($rows, FeatureTable::FIELD_TAG_ID);

        /** @var array<int, array<string, mixed>> $users */
        $users = $this->loadUsers($userIds);

        /** @var array<int, array<string, mixed>> $tags */
        $tags = $this->loadTags($tagIds);

        /** @var array<int, array<string, mixed>> $items */
        $items = [];

        foreach ($rows as $row) {
            $items[] = $this->presentFlagRow($row, $users, $tags);
        }

        return $items;
    }

    /**
     * Возвращает один фича-флаг в формате ответа админского API.
     *
     * @param array<string, mixed> $row ORM-строка фича-флага.
     * @return array<string, mixed> Подготовленный фича-флаг.
     * @throws ArgumentException При ошибке ORM-запроса связанных данных.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств связанных данных.
     * @throws SystemException При системной ошибке ORM.
     */
    public function presentFlag(array $row): array
    {
        /** @var array<int, array<string, mixed>> $flags */
        $flags = $this->presentFlags([$row]);

        return $flags[0] ?? [];
    }

    /**
     * Возвращает список тегов в формате ответа админского API.
     *
     * @param array<int, array<string, mixed>> $rows ORM-строки тегов.
     * @return array<int, array<string, mixed>> Подготовленные теги.
     */
    public function presentTags(array $rows): array
    {
        /** @var array<int, array<string, mixed>> $items */
        $items = [];

        foreach ($rows as $row) {
            $items[] = $this->presentTag($row);
        }

        return $items;
    }

    /**
     * Возвращает один тег в формате ответа админского API.
     *
     * @param array<string, mixed> $row ORM-строка тега.
     * @return array<string, mixed> Подготовленный тег.
     */
    public function presentTag(array $row): array
    {
        return [
            'id' => (int)($row[FeatureTagTable::FIELD_ID] ?? 0),
            'name' => (string)($row[FeatureTagTable::FIELD_NAME] ?? ''),
        ];
    }

    /**
     * Собирает положительные идентификаторы из набора ORM-строк.
     *
     * @param array<int, array<string, mixed>> $rows ORM-строки.
     * @param string $field Имя поля с идентификатором.
     * @return array<int, int> Уникальные положительные идентификаторы.
     */
    private function collectPositiveIds(array $rows, string $field): array
    {
        /** @var array<int, int> $ids */
        $ids = [];

        foreach ($rows as $row) {
            $id = (int)($row[$field] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * Загружает пользователей, связанных с фича-флагами.
     *
     * @param array<int, int> $userIds Идентификаторы пользователей.
     * @return array<int, array<string, mixed>> Пользователи, индексированные по ID.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    private function loadUsers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $users */
        $users = [];

        $result = UserTable::getList([
            'filter' => ['@ID' => $userIds],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'],
        ]);

        while (($user = $result->fetch()) !== false) {
            $users[(int)$user['ID']] = $user;
        }

        return $users;
    }

    /**
     * Загружает теги, связанные с фича-флагами.
     *
     * @param array<int, int> $tagIds Идентификаторы тегов.
     * @return array<int, array<string, mixed>> Теги, индексированные по ID.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    private function loadTags(array $tagIds): array
    {
        if ($tagIds === []) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $tags */
        $tags = [];

        $result = FeatureTagTable::query()
            ->setSelect([
                FeatureTagTable::FIELD_ID,
                FeatureTagTable::FIELD_NAME,
            ])
            ->whereIn(FeatureTagTable::FIELD_ID, $tagIds)
            ->exec();

        while (($tag = $result->fetch()) !== false) {
            $tags[(int)$tag[FeatureTagTable::FIELD_ID]] = $tag;
        }

        return $tags;
    }

    /**
     * Преобразует одну ORM-строку фича-флага в формат API.
     *
     * @param array<string, mixed> $row ORM-строка фича-флага.
     * @param array<int, array<string, mixed>> $users Пользователи, индексированные по ID.
     * @param array<int, array<string, mixed>> $tags Теги, индексированные по ID.
     * @return array<string, mixed> Подготовленный фича-флаг.
     */
    private function presentFlagRow(array $row, array $users, array $tags): array
    {
        $createdById = (int)($row[FeatureTable::FIELD_CREATED_BY] ?? 0);
        $tagId = (int)($row[FeatureTable::FIELD_TAG_ID] ?? 0);

        /** @var array<string, mixed>|null $creator */
        $creator = $users[$createdById] ?? null;

        /** @var array<string, mixed>|null $tag */
        $tag = $tags[$tagId] ?? null;

        return [
            'code' => (string)($row[FeatureTable::FIELD_CODE] ?? ''),
            'name' => (string)($row[FeatureTable::FIELD_NAME] ?? ''),
            'description' => (string)($row[FeatureTable::FIELD_DESCRIPTION] ?? ''),
            'enabled' => $this->normalizeEnabled($row[FeatureTable::FIELD_ENABLED] ?? false),
            'tagId' => $tagId > 0 ? $tagId : null,
            'tag' => [
                'id' => $tagId > 0 ? $tagId : null,
                'name' => $tag !== null ? (string)($tag[FeatureTagTable::FIELD_NAME] ?? '') : '',
            ],
            'strategies' => $this->normalizeStrategies($row[FeatureTable::FIELD_STRATEGIES] ?? []),
            'createdAt' => $this->formatDate($row[FeatureTable::FIELD_DATE_CREATE] ?? null, self::DATETIME_FORMAT),
            'updatedAt' => $this->formatDate($row[FeatureTable::FIELD_DATE_UPDATE] ?? null, self::DATETIME_FORMAT),
            'removePlannedAt' => $this->formatDate($row[FeatureTable::REMOVE_PLANNED_AT] ?? null, self::DATE_FORMAT),
            'createdBy' => $this->presentCreator($createdById, $creator),
        ];
    }

    /**
     * Возвращает автора фича-флага в формате API.
     *
     * @param int $createdById Идентификатор автора.
     * @param array<string, mixed>|null $creator ORM-строка пользователя или null.
     * @return array{id: int, title: string, url: string} Данные автора для UI.
     */
    private function presentCreator(int $createdById, ?array $creator): array
    {
        return [
            'id' => $createdById,
            'title' => $creator !== null ? $this->formatUserTitle($creator) : '',
            'url' => $createdById > 0
                ? '/bitrix/admin/user_edit.php?lang=' . rawurlencode((string)LANGUAGE_ID) . '&ID=' . $createdById
                : '',
        ];
    }

    /**
     * Формирует отображаемое имя пользователя в формате `[{ID}] {ФИО}`.
     *
     * @param array<string, mixed> $user ORM-строка пользователя.
     * @return string Подготовленное отображаемое имя.
     */
    private function formatUserTitle(array $user): string
    {
        $id = (int)($user['ID'] ?? 0);
        $fullName = trim(implode(' ', array_filter([
            (string)($user['LAST_NAME'] ?? ''),
            (string)($user['NAME'] ?? ''),
            (string)($user['SECOND_NAME'] ?? ''),
        ])));

        if ($fullName === '') {
            $fullName = trim((string)($user['LOGIN'] ?? ''));
        }

        if ($fullName === '') {
            $fullName = (string)$id;
        }

        return sprintf('[%d] %s', $id, $fullName);
    }

    /**
     * Форматирует дату для ответа админского API.
     *
     * @param mixed $value Значение даты из ORM.
     * @param string $format Формат даты Bitrix/PHP.
     * @return string Отформатированная дата или пустая строка.
     */
    private function formatDate(mixed $value, string $format): string
    {
        if ($value instanceof DateTime || $value instanceof Date) {
            return $value->format($format);
        }

        return is_string($value) ? $value : '';
    }

    /**
     * Преобразует значение активности из ORM в boolean.
     *
     * @param mixed $value Значение активности из ORM.
     * @return bool Нормализованный признак активности.
     */
    private function normalizeEnabled(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'Y'], true);
    }

    /**
     * Нормализует стратегии доступа из ORM-значения.
     *
     * @param mixed $value Значение стратегий из ORM.
     * @return array<int, mixed> Массив стратегий.
     */
    private function normalizeStrategies(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values($value);
    }
}
