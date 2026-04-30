<?php

namespace Sholokhov\Featureflag\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Sholokhov\Featureflag\DTO\FlagInfo;
use Sholokhov\Featureflag\Feature;
use Sholokhov\Featureflag\ORM\FeatureTable;

Loc::loadMessages(__FILE__);

/**
 * Сервис админ-операций с фича-флагами.
 *
 * Содержит бизнес-логику:
 * - валидацию входных данных;
 * - CRUD/toggle операции;
 * - подготовку данных под формат API админки.
 */
final class AdminFeatureFlagService implements AdminFeatureFlagServiceInterface
{
    private const FIELD_CODE = 'code';
    private const FIELD_NAME = 'name';
    private const FIELD_DESCRIPTION = 'description';
    private const FIELD_ENABLED = 'enabled';

    private const CODE_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._-]*$/';

    /**
     * @inheritDoc
     */
    public function list(): Result
    {
        $rows = FeatureTable::query()
            ->setSelect(['*'])
            ->setOrder([
                FeatureTable::FIELD_DATE_CREATE => 'DESC',
                FeatureTable::FIELD_NAME => 'ASC',
            ])
            ->fetchAll();

        return (new Result())->setData([
            'items' => $this->prepareFlags($rows),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function get(string $code): Result
    {
        $result = new Result();
        $code = trim($code);

        $row = $this->getFlagRow($code, $result);
        if ($row === null) {
            return $result;
        }

        return $result->setData([
            'flag' => $this->prepareFlags([$row])[0],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function create(string $code, string $name, string $description, mixed $enabled): Result
    {
        $result = new Result();

        $code = trim($code);
        $name = trim($name);
        $description = trim($description);
        $enabledValue = $this->parseBoolean($enabled);

        $this->validatePayload($result, $code, $name, $description, $enabledValue);
        if (!$result->isSuccess()) {
            return $result;
        }

        $createResult = Feature::register(new FlagInfo(
            code: $code,
            name: $name,
            description: $description,
            enabled: (bool)$enabledValue,
        ));

        if (!$createResult->isSuccess()) {
            $this->appendResultErrors($result, $createResult);
            return $result;
        }

        $row = $this->getFlagRow($code, $result);
        if ($row === null) {
            return $result;
        }

        return $result->setData([
            'flag' => $this->prepareFlags([$row])[0],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function update(string $code, string $name, string $description, mixed $enabled): Result
    {
        $result = new Result();

        $code = trim($code);
        $name = trim($name);
        $description = trim($description);
        $enabledValue = $this->parseBoolean($enabled);

        $this->validatePayload($result, $code, $name, $description, $enabledValue);
        if (!$result->isSuccess()) {
            return $result;
        }

        $row = $this->getFlagRow($code, $result);
        if ($row === null) {
            return $result;
        }

        $updateResult = FeatureTable::update($code, [
            FeatureTable::FIELD_NAME => $name,
            FeatureTable::FIELD_DESCRIPTION => $description,
            FeatureTable::FIELD_ENABLED => (bool)$enabledValue,
        ]);

        if (!$updateResult->isSuccess()) {
            $this->appendResultErrors($result, $updateResult);
            return $result;
        }

        $updatedRow = $this->getFlagRow($code, $result);
        if ($updatedRow === null) {
            return $result;
        }

        return $result->setData([
            'flag' => $this->prepareFlags([$updatedRow])[0],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $code): Result
    {
        $result = new Result();
        $code = trim($code);

        if ($code === '') {
            $this->addFieldError($result, self::FIELD_CODE, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_EMPTY_CODE'));
            return $result;
        }

        $deleteResult = Feature::unRegister($code);
        if (!$deleteResult->isSuccess()) {
            $this->appendResultErrors($result, $deleteResult);
            return $result;
        }

        return $result->setData([
            'code' => $code,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function toggle(string $code, mixed $enabled): Result
    {
        $result = new Result();
        $code = trim($code);
        $enabledValue = $this->parseBoolean($enabled);

        if ($code === '') {
            $this->addFieldError($result, self::FIELD_CODE, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_EMPTY_CODE'));
            return $result;
        }

        if ($enabledValue === null) {
            $this->addFieldError($result, self::FIELD_ENABLED, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_INVALID_ENABLED'));
            return $result;
        }

        $toggleResult = $enabledValue ? Feature::enabled($code) : Feature::disabled($code);
        if (!$toggleResult->isSuccess()) {
            $this->appendResultErrors($result, $toggleResult);
            return $result;
        }

        $row = $this->getFlagRow($code, $result);
        if ($row === null) {
            return $result;
        }

        return $result->setData([
            'flag' => $this->prepareFlags([$row])[0],
        ]);
    }

    /**
     * Валидирует данные формы фича-флага.
     *
     * @param Result $result
     * @param string $code
     * @param string $name
     * @param string $description
     * @param bool|null $enabled
     * @return void
     */
    private function validatePayload(Result $result, string $code, string $name, string $description, ?bool $enabled): void
    {
        if ($code === '') {
            $this->addFieldError($result, self::FIELD_CODE, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_EMPTY_CODE'));
        } elseif (!preg_match(self::CODE_PATTERN, $code)) {
            $this->addFieldError($result, self::FIELD_CODE, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_INVALID_CODE'));
        } elseif (mb_strlen($code) > 255) {
            $this->addFieldError($result, self::FIELD_CODE, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_CODE_TOO_LONG'));
        }

        if ($name === '') {
            $this->addFieldError($result, self::FIELD_NAME, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_EMPTY_NAME'));
        } elseif (mb_strlen($name) > 255) {
            $this->addFieldError($result, self::FIELD_NAME, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_NAME_TOO_LONG'));
        }

        if (mb_strlen($description) > 5000) {
            $this->addFieldError(
                $result,
                self::FIELD_DESCRIPTION,
                (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_DESCRIPTION_TOO_LONG'),
            );
        }

        if ($enabled === null) {
            $this->addFieldError($result, self::FIELD_ENABLED, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_INVALID_ENABLED'));
        }
    }

    /**
     * Ищет запись фича-флага по коду.
     *
     * @param string $code
     * @param Result $result
     * @return array<string, mixed>|null
     */
    private function getFlagRow(string $code, Result $result): ?array
    {
        if ($code === '') {
            $this->addFieldError($result, self::FIELD_CODE, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_EMPTY_CODE'));
            return null;
        }

        $row = FeatureTable::query()
            ->setSelect(['*'])
            ->where(FeatureTable::FIELD_CODE, $code)
            ->fetch();

        if ($row === false) {
            $this->addFieldError($result, self::FIELD_CODE, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_NOT_FOUND'));
            return null;
        }

        return $row;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function prepareFlags(array $rows): array
    {
        $userIds = [];
        foreach ($rows as $row) {
            $createdBy = (int)($row[FeatureTable::FIELD_CREATED_BY] ?? 0);
            if ($createdBy > 0) {
                $userIds[] = $createdBy;
            }
        }

        $users = $this->loadUsers($userIds);
        $items = [];

        foreach ($rows as $row) {
            $createdById = (int)($row[FeatureTable::FIELD_CREATED_BY] ?? 0);
            $creator = $users[$createdById] ?? null;

            $items[] = [
                'code' => (string)($row[FeatureTable::FIELD_CODE] ?? ''),
                'name' => (string)($row[FeatureTable::FIELD_NAME] ?? ''),
                'description' => (string)($row[FeatureTable::FIELD_DESCRIPTION] ?? ''),
                'enabled' => $this->normalizeEnabled($row[FeatureTable::FIELD_ENABLED] ?? false),
                'createdAt' => $this->formatDate($row[FeatureTable::FIELD_DATE_CREATE] ?? null),
                'updatedAt' => $this->formatDate($row[FeatureTable::FIELD_DATE_UPDATE] ?? null),
                'createdBy' => [
                    'id' => $createdById,
                    'title' => $creator !== null ? $this->formatUserTitle($creator) : '',
                    'url' => $createdById > 0
                        ? '/bitrix/admin/user_edit.php?lang=' . rawurlencode(LANGUAGE_ID) . '&ID=' . $createdById
                        : '',
                ],
            ];
        }

        return $items;
    }

    /**
     * @param int[] $userIds
     * @return array<int, array<string, mixed>>
     */
    private function loadUsers(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($userIds === []) {
            return [];
        }

        $map = [];
        $result = UserTable::getList([
            'filter' => ['@ID' => $userIds],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'],
        ]);

        while ($user = $result->fetch()) {
            $map[(int)$user['ID']] = $user;
        }

        return $map;
    }

    /**
     * Формирует отображаемое имя пользователя в формате `[{ID}] {FIO}`.
     *
     * @param array<string, mixed> $user
     * @return string
     */
    private function formatUserTitle(array $user): string
    {
        $id = (int)($user['ID'] ?? 0);
        $fio = trim(implode(' ', array_filter([
            (string)($user['LAST_NAME'] ?? ''),
            (string)($user['NAME'] ?? ''),
            (string)($user['SECOND_NAME'] ?? ''),
        ])));

        if ($fio === '') {
            $fio = trim((string)($user['LOGIN'] ?? ''));
        }

        if ($fio === '') {
            $fio = (string)$id;
        }

        return sprintf('[%d] %s', $id, $fio);
    }

    /**
     * Форматирует дату под API-ответ админки.
     *
     * @param mixed $value
     * @return string
     */
    private function formatDate(mixed $value): string
    {
        if ($value instanceof DateTime) {
            return $value->format('d.m.Y H:i:s');
        }

        if ($value instanceof Date) {
            return $value->format('d.m.Y');
        }

        return is_string($value) ? $value : '';
    }

    /**
     * Преобразует значение активности из ORM в bool.
     *
     * @param mixed $value
     * @return bool
     */
    private function normalizeEnabled(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'Y';
    }

    /**
     * Преобразует входной флаг активности в bool или null (если значение невалидно).
     *
     * @param mixed $value
     * @return bool|null
     */
    private function parseBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            if ($value === 1) {
                return true;
            }

            if ($value === 0) {
                return false;
            }

            return null;
        }

        if (is_float($value)) {
            if ($value === 1.0) {
                return true;
            }

            if ($value === 0.0) {
                return false;
            }

            return null;
        }

        if (is_string($value)) {
            $normalized = mb_strtolower(trim($value));
            if (in_array($normalized, ['1', 'y', 'yes', 'true', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'n', 'no', 'false', 'off', ''], true)) {
                return false;
            }

            return null;
        }

        if ($value === null) {
            return null;
        }

        return null;
    }

    /**
     * Копирует ошибки из внутреннего Result в результирующий Result API.
     *
     * @param Result $target
     * @param Result $source
     * @return void
     */
    private function appendResultErrors(Result $target, Result $source): void
    {
        foreach ($source->getErrors() as $error) {
            $customData = $error->getCustomData();
            if (!is_array($customData)) {
                $customData = [];
            }

            $field = $customData['field'] ?? null;
            if (!is_string($field) || $field === '') {
                $field = $this->guessFieldFromErrorMessage($error->getMessage());
                if ($field !== null) {
                    $customData['field'] = $field;
                }
            }

            $target->addError(new Error($error->getMessage(), (string)$error->getCode(), $customData));
        }
    }

    /**
     * Добавляет ошибку, привязанную к полю формы.
     *
     * @param Result $result
     * @param string $field
     * @param string $message
     * @param string|int $code
     * @return void
     */
    private function addFieldError(Result $result, string $field, string $message, string|int $code = ''): void
    {
        $result->addError(new Error($message, (string)$code, ['field' => $field]));
    }

    /**
     * Пытается определить поле формы по тексту системной ошибки.
     *
     * @param string $message
     * @return string|null
     */
    private function guessFieldFromErrorMessage(string $message): ?string
    {
        $normalized = mb_strtolower($message);

        if (str_contains($normalized, 'код') || str_contains($normalized, 'code')) {
            return self::FIELD_CODE;
        }

        if (str_contains($normalized, 'назван') || str_contains($normalized, 'name')) {
            return self::FIELD_NAME;
        }

        if (str_contains($normalized, 'описан') || str_contains($normalized, 'description')) {
            return self::FIELD_DESCRIPTION;
        }

        if (str_contains($normalized, 'enabled') || str_contains($normalized, 'статус')) {
            return self::FIELD_ENABLED;
        }

        return null;
    }
}
