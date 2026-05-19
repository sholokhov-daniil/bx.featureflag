<?php

namespace Sholokhov\Featureflag\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Throwable;
use Sholokhov\Featureflag\DTO\FlagInfo;
use Sholokhov\Featureflag\Feature;
use Sholokhov\Featureflag\ORM\FeatureTable;
use Sholokhov\Featureflag\ORM\FeatureTagTable;
use Sholokhov\Featureflag\ServiceProvider;

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
    private const FIELD_TAG_ID = 'tagId';
    private const FIELD_STRATEGIES = 'strategies';
    private const FIELD_ID = 'id';

    private const CODE_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._-]*$/';
    private bool $isSchemaInitialized = false;

    /**
     * @inheritDoc
     */
    public function list(): Result
    {
        $result = new Result();
        if (!$this->ensureSchema($result)) {
            return $result;
        }

        $rows = FeatureTable::query()
            ->setSelect(['*'])
            ->setOrder([
                FeatureTable::FIELD_DATE_CREATE => 'DESC',
                FeatureTable::FIELD_NAME => 'ASC',
            ])
            ->fetchAll();

        return $result->setData([
            'items' => $this->prepareFlags($rows),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function get(string $code): Result
    {
        $result = new Result();
        if (!$this->ensureSchema($result)) {
            return $result;
        }

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
    public function create(string $code, string $name, string $description, mixed $enabled, string $tagId, mixed $strategies = []): Result
    {
        $result = new Result();
        if (!$this->ensureSchema($result)) {
            return $result;
        }

        $code = trim($code);
        $name = trim($name);
        $description = trim($description);
        $enabledValue = $this->parseBoolean($enabled);
        $tagIdValue = $this->parseTagId($tagId);
        $strategiesResult = $this->normalizeStrategies($strategies);

        $this->validatePayload($result, $code, $name, $description, $enabledValue, $tagIdValue);
        $this->appendResultErrors($result, $strategiesResult);
        if (!$result->isSuccess()) {
            return $result;
        }

        $strategyItems = $strategiesResult->getData()['strategies'] ?? [];

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

        $bindMetaResult = FeatureTable::update($code, [
            FeatureTable::FIELD_TAG_ID => $tagIdValue,
            FeatureTable::FIELD_STRATEGIES => $this->encodeStrategies($strategyItems),
        ]);

        if (!$bindMetaResult->isSuccess()) {
            $this->appendResultErrors($result, $bindMetaResult);
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
    public function update(string $code, string $name, string $description, mixed $enabled, string $tagId, mixed $strategies = []): Result
    {
        $result = new Result();
        if (!$this->ensureSchema($result)) {
            return $result;
        }

        $code = trim($code);
        $name = trim($name);
        $description = trim($description);
        $enabledValue = $this->parseBoolean($enabled);
        $tagIdValue = $this->parseTagId($tagId);
        $strategiesResult = $this->normalizeStrategies($strategies);

        $this->validatePayload($result, $code, $name, $description, $enabledValue, $tagIdValue);
        $this->appendResultErrors($result, $strategiesResult);
        if (!$result->isSuccess()) {
            return $result;
        }

        $strategyItems = $strategiesResult->getData()['strategies'] ?? [];

        $row = $this->getFlagRow($code, $result);
        if ($row === null) {
            return $result;
        }

        $updateResult = FeatureTable::update($code, [
            FeatureTable::FIELD_NAME => $name,
            FeatureTable::FIELD_DESCRIPTION => $description,
            FeatureTable::FIELD_ENABLED => (bool)$enabledValue,
            FeatureTable::FIELD_TAG_ID => $tagIdValue,
            FeatureTable::FIELD_STRATEGIES => $this->encodeStrategies($strategyItems),
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
        if (!$this->ensureSchema($result)) {
            return $result;
        }

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
        if (!$this->ensureSchema($result)) {
            return $result;
        }

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
     * @inheritDoc
     */
    public function tagList(): Result
    {
        $result = new Result();
        if (!$this->ensureSchema($result)) {
            return $result;
        }

        $rows = FeatureTagTable::query()
            ->setSelect([
                FeatureTagTable::FIELD_ID,
                FeatureTagTable::FIELD_NAME,
                FeatureTagTable::FIELD_SORT,
                FeatureTagTable::FIELD_STRATEGIES,
            ])
            ->setOrder([
                FeatureTagTable::FIELD_SORT => 'ASC',
                FeatureTagTable::FIELD_NAME => 'ASC',
                FeatureTagTable::FIELD_ID => 'ASC',
            ])
            ->fetchAll();

        return $result->setData([
            'items' => array_map(
                fn(array $row): array => [
                    'id' => (int)($row[FeatureTagTable::FIELD_ID] ?? 0),
                    'name' => (string)($row[FeatureTagTable::FIELD_NAME] ?? ''),
                    'strategies' => $this->decodeStrategies((string)($row[FeatureTagTable::FIELD_STRATEGIES] ?? '')),
                ],
                $rows,
            ),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function tagCreate(string $name, mixed $strategies = []): Result
    {
        $result = new Result();
        if (!$this->ensureSchema($result)) {
            return $result;
        }

        $name = trim($name);
        $strategiesResult = $this->normalizeStrategies($strategies);

        $this->validateTagName($result, $name);
        $this->appendResultErrors($result, $strategiesResult);
        if (!$result->isSuccess()) {
            return $result;
        }

        $strategyItems = $strategiesResult->getData()['strategies'] ?? [];

        $existingByName = $this->findTagByName($name);
        if ($existingByName !== null) {
            $this->addFieldError($result, self::FIELD_NAME, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_TAG_DUPLICATE'));
            return $result;
        }

        $createResult = FeatureTagTable::add([
            FeatureTagTable::FIELD_NAME => $name,
            FeatureTagTable::FIELD_STRATEGIES => $this->encodeStrategies($strategyItems),
        ]);

        if (!$createResult->isSuccess()) {
            $this->appendResultErrors($result, $createResult);
            return $result;
        }

        $tagId = (int)$createResult->getId();
        $tag = $this->getTagRow($tagId, $result);
        if ($tag === null) {
            return $result;
        }

        return $result->setData([
            'tag' => [
                'id' => (int)$tag[FeatureTagTable::FIELD_ID],
                'name' => (string)$tag[FeatureTagTable::FIELD_NAME],
                'strategies' => $this->decodeStrategies((string)($tag[FeatureTagTable::FIELD_STRATEGIES] ?? '')),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function tagUpdate(string $id, string $name, mixed $strategies = []): Result
    {
        $result = new Result();
        if (!$this->ensureSchema($result)) {
            return $result;
        }

        $name = trim($name);
        $tagId = (int)$id;
        $strategiesResult = $this->normalizeStrategies($strategies);

        if ($tagId <= 0) {
            $this->addFieldError($result, self::FIELD_ID, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_TAG_INVALID_ID'));
            return $result;
        }

        $this->validateTagName($result, $name);
        $this->appendResultErrors($result, $strategiesResult);
        if (!$result->isSuccess()) {
            return $result;
        }

        $strategyItems = $strategiesResult->getData()['strategies'] ?? [];

        $currentTag = $this->getTagRow($tagId, $result);
        if ($currentTag === null) {
            return $result;
        }

        $existingByName = $this->findTagByName($name);
        if ($existingByName !== null && (int)$existingByName[FeatureTagTable::FIELD_ID] !== $tagId) {
            $this->addFieldError($result, self::FIELD_NAME, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_TAG_DUPLICATE'));
            return $result;
        }

        $updateResult = FeatureTagTable::update($tagId, [
            FeatureTagTable::FIELD_NAME => $name,
            FeatureTagTable::FIELD_STRATEGIES => $this->encodeStrategies($strategyItems),
        ]);

        if (!$updateResult->isSuccess()) {
            $this->appendResultErrors($result, $updateResult);
            return $result;
        }

        $tag = $this->getTagRow($tagId, $result);
        if ($tag === null) {
            return $result;
        }

        return $result->setData([
            'tag' => [
                'id' => (int)$tag[FeatureTagTable::FIELD_ID],
                'name' => (string)$tag[FeatureTagTable::FIELD_NAME],
                'strategies' => $this->decodeStrategies((string)($tag[FeatureTagTable::FIELD_STRATEGIES] ?? '')),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function tagDelete(string $id): Result
    {
        $result = new Result();
        if (!$this->ensureSchema($result)) {
            return $result;
        }

        $tagId = (int)$id;

        if ($tagId <= 0) {
            $this->addFieldError($result, self::FIELD_ID, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_TAG_INVALID_ID'));
            return $result;
        }

        $tag = $this->getTagRow($tagId, $result);
        if ($tag === null) {
            return $result;
        }

        $connection = FeatureTable::getEntity()->getConnection();
        $sqlHelper = $connection->getSqlHelper();
        $featureTable = $sqlHelper->quote(FeatureTable::getTableName());
        $tagField = $sqlHelper->quote(FeatureTable::FIELD_TAG_ID);
        $connection->queryExecute("UPDATE {$featureTable} SET {$tagField} = NULL WHERE {$tagField} = {$tagId}");

        $deleteResult = FeatureTagTable::delete($tagId);
        if (!$deleteResult->isSuccess()) {
            $this->appendResultErrors($result, $deleteResult);
            return $result;
        }

        return $result->setData([
            'id' => $tagId,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function strategyList(): Result
    {
        $result = new Result();

        try {
            $items = [];

            foreach (ServiceProvider::getStrategyRegistry()->getAll() as $strategy) {
                $items[] = [
                    'code' => $strategy->getCode(),
                    'name' => $strategy->getName(),
                    'description' => $strategy->getDescription(),
                    'fields' => $strategy->getFields(),
                ];
            }

            return $result->setData([
                'items' => $items,
            ]);
        } catch (Throwable $exception) {
            return $result->addError(new Error($exception->getMessage()));
        }
    }

    /**
     * Валидирует данные формы фича-флага.
     *
     * @param Result $result
     * @param string $code
     * @param string $name
     * @param string $description
     * @param bool|null $enabled
     * @param int|null $tagId
     * @return void
     */
    private function validatePayload(
        Result $result,
        string $code,
        string $name,
        string $description,
        ?bool $enabled,
        ?int $tagId,
    ): void
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

        if ($tagId !== null && !$this->isTagExists($tagId)) {
            $this->addFieldError($result, self::FIELD_TAG_ID, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_TAG_NOT_FOUND'));
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
        $tagIds = [];
        foreach ($rows as $row) {
            $createdBy = (int)($row[FeatureTable::FIELD_CREATED_BY] ?? 0);
            if ($createdBy > 0) {
                $userIds[] = $createdBy;
            }

            $tagId = (int)($row[FeatureTable::FIELD_TAG_ID] ?? 0);
            if ($tagId > 0) {
                $tagIds[] = $tagId;
            }
        }

        $users = $this->loadUsers($userIds);
        $tags = $this->loadTags($tagIds);
        $items = [];

        foreach ($rows as $row) {
            $createdById = (int)($row[FeatureTable::FIELD_CREATED_BY] ?? 0);
            $creator = $users[$createdById] ?? null;
            $tagId = (int)($row[FeatureTable::FIELD_TAG_ID] ?? 0);
            $tagName = $tagId > 0 ? ($tags[$tagId]['NAME'] ?? '') : '';

            $items[] = [
                'code' => (string)($row[FeatureTable::FIELD_CODE] ?? ''),
                'name' => (string)($row[FeatureTable::FIELD_NAME] ?? ''),
                'description' => (string)($row[FeatureTable::FIELD_DESCRIPTION] ?? ''),
                'enabled' => $this->normalizeEnabled($row[FeatureTable::FIELD_ENABLED] ?? false),
                'tagId' => $tagId > 0 ? $tagId : null,
                'tag' => [
                    'id' => $tagId > 0 ? $tagId : null,
                    'name' => $tagName,
                ],
                'strategies' => $this->decodeStrategies((string)($row[FeatureTable::FIELD_STRATEGIES] ?? '')),
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
     * @param int[] $tagIds
     * @return array<int, array<string, mixed>>
     */
    private function loadTags(array $tagIds): array
    {
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));
        if ($tagIds === []) {
            return [];
        }

        $map = [];
        $result = FeatureTagTable::query()
            ->setSelect([
                FeatureTagTable::FIELD_ID,
                FeatureTagTable::FIELD_NAME,
            ])
            ->whereIn(FeatureTagTable::FIELD_ID, $tagIds)
            ->exec();

        while ($tag = $result->fetch()) {
            $map[(int)$tag[FeatureTagTable::FIELD_ID]] = $tag;
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
     * Проверяет и доинициализирует схему для тегов при обновлении модуля.
     *
     * @param Result $result
     * @return bool
     */
    private function ensureSchema(Result $result): bool
    {
        if ($this->isSchemaInitialized) {
            return true;
        }

        try {
            $connection = FeatureTable::getEntity()->getConnection();

            $tagTableName = FeatureTagTable::getTableName();
            if (!$connection->isTableExists($tagTableName)) {
                FeatureTagTable::getEntity()->createDbTable();
            } else {
                $tagFields = array_change_key_case($connection->getTableFields($tagTableName), CASE_UPPER);
                if (!isset($tagFields[FeatureTagTable::FIELD_STRATEGIES])) {
                    $sqlHelper = $connection->getSqlHelper();
                    $tableSql = $sqlHelper->quote($tagTableName);
                    $fieldSql = $sqlHelper->quote(FeatureTagTable::FIELD_STRATEGIES);
                    $connection->queryExecute("ALTER TABLE {$tableSql} ADD {$fieldSql} text NULL");
                }
            }

            $featureTableName = FeatureTable::getTableName();
            if (!$connection->isTableExists($featureTableName)) {
                FeatureTable::getEntity()->createDbTable();
            } else {
                $fields = array_change_key_case($connection->getTableFields($featureTableName), CASE_UPPER);
                if (!isset($fields[FeatureTable::FIELD_TAG_ID])) {
                    $sqlHelper = $connection->getSqlHelper();
                    $tableSql = $sqlHelper->quote($featureTableName);
                    $fieldSql = $sqlHelper->quote(FeatureTable::FIELD_TAG_ID);
                    $connection->queryExecute("ALTER TABLE {$tableSql} ADD {$fieldSql} int(11) NULL");
                }

                if (!isset($fields[FeatureTable::FIELD_STRATEGIES])) {
                    $sqlHelper = $connection->getSqlHelper();
                    $tableSql = $sqlHelper->quote($featureTableName);
                    $fieldSql = $sqlHelper->quote(FeatureTable::FIELD_STRATEGIES);
                    $connection->queryExecute("ALTER TABLE {$tableSql} ADD {$fieldSql} text NULL");
                }
            }
        } catch (Throwable $exception) {
            $result->addError(new Error(
                (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_SCHEMA_INIT') ?: $exception->getMessage(),
            ));
            return false;
        }

        $this->isSchemaInitialized = true;
        return true;
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
     * Преобразует входной идентификатор тега в int|null.
     *
     * @param string $value
     * @return int|null
     */
    private function parseTagId(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $tagId = (int)$value;
        return $tagId > 0 ? $tagId : null;
    }

    /**
     * Валидирует и нормализует стратегии доступа.
     *
     * @param mixed $value
     * @return Result{strategies: array<int, array<string, mixed>>}
     */
    private function normalizeStrategies(mixed $value): Result
    {
        $result = new Result();
        $items = $this->parseStrategyItems($value);

        if ($items === null) {
            $this->addFieldError($result, self::FIELD_STRATEGIES, 'Некорректный формат стратегий доступа');
            return $result;
        }

        if ($items === []) {
            return $result->setData([
                'strategies' => [],
            ]);
        }

        try {
            $registry = ServiceProvider::getStrategyRegistry();
        } catch (Throwable $exception) {
            return $result->addError(new Error($exception->getMessage()));
        }

        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                $this->addFieldError($result, self::FIELD_STRATEGIES, 'Некорректный формат стратегии доступа');
                continue;
            }

            $type = trim((string)($item['type'] ?? ''));
            $config = $item['config'] ?? [];

            if ($type === '') {
                $this->addFieldError($result, self::FIELD_STRATEGIES, 'Не выбран тип стратегии доступа');
                continue;
            }

            if (!is_array($config)) {
                $this->addFieldError($result, self::FIELD_STRATEGIES, 'Некорректная конфигурация стратегии доступа');
                continue;
            }

            $strategy = $registry->get($type);
            if ($strategy === null) {
                $this->addFieldError($result, self::FIELD_STRATEGIES, "Стратегия `{$type}` не зарегистрирована");
                continue;
            }

            $strategyResult = $strategy->normalizeConfig($config);
            if (!$strategyResult->isSuccess()) {
                foreach ($strategyResult->getErrors() as $error) {
                    $this->addFieldError(
                        $result,
                        self::FIELD_STRATEGIES,
                        $strategy->getName() . ': ' . $error->getMessage(),
                    );
                }

                continue;
            }

            $normalizedConfig = $strategyResult->getData()['config'] ?? [];
            if (!is_array($normalizedConfig)) {
                $this->addFieldError($result, self::FIELD_STRATEGIES, "Стратегия `{$type}` вернула некорректную конфигурацию");
                continue;
            }

            $normalized[] = [
                'type' => $type,
                'config' => $normalizedConfig,
            ];
        }

        if (!$result->isSuccess()) {
            return $result;
        }

        return $result->setData([
            'strategies' => $normalized,
        ]);
    }

    /**
     * Преобразует входное значение стратегий в список элементов.
     *
     * @param mixed $value
     * @return array<int, mixed>|null
     */
    private function parseStrategyItems(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            try {
                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                return null;
            }
        }

        if (!is_array($value) || !array_is_list($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param array<int, array<string, mixed>> $strategies
     * @return string
     */
    private function encodeStrategies(array $strategies): string
    {
        try {
            return json_encode($strategies, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return '[]';
        }
    }

    /**
     * Декодирует JSON-конфигурацию стратегий.
     *
     * @param string $value JSON-строка из БД
     * @return array<int, array{type: string, config: array<string, mixed>}>
     */
    private function decodeStrategies(string $value): array
    {
        $items = $this->parseStrategyItems(trim($value));
        if ($items === null) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = trim((string)($item['type'] ?? ''));
            $config = $item['config'] ?? [];

            if ($type === '' || !is_array($config)) {
                continue;
            }

            $result[] = [
                'type' => $type,
                'config' => $config,
            ];
        }

        return $result;
    }

    /**
     * @param Result $result
     * @param string $name
     * @return void
     */
    private function validateTagName(Result $result, string $name): void
    {
        if ($name === '') {
            $this->addFieldError($result, self::FIELD_NAME, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_EMPTY_TAG_NAME'));
        } elseif (mb_strlen($name) > 255) {
            $this->addFieldError($result, self::FIELD_NAME, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_TAG_NAME_TOO_LONG'));
        }
    }

    /**
     * @param int $tagId
     * @param Result $result
     * @return array<string, mixed>|null
     */
    private function getTagRow(int $tagId, Result $result): ?array
    {
        $row = FeatureTagTable::query()
            ->setSelect([
                FeatureTagTable::FIELD_ID,
                FeatureTagTable::FIELD_NAME,
                FeatureTagTable::FIELD_STRATEGIES,
            ])
            ->where(FeatureTagTable::FIELD_ID, $tagId)
            ->fetch();

        if ($row === false) {
            $this->addFieldError($result, self::FIELD_ID, (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_TAG_NOT_FOUND'));
            return null;
        }

        return $row;
    }

    /**
     * @param string $name
     * @return array<string, mixed>|null
     */
    private function findTagByName(string $name): ?array
    {
        $row = FeatureTagTable::query()
            ->setSelect([
                FeatureTagTable::FIELD_ID,
                FeatureTagTable::FIELD_NAME,
            ])
            ->where(FeatureTagTable::FIELD_NAME, $name)
            ->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param int $tagId
     * @return bool
     */
    private function isTagExists(int $tagId): bool
    {
        return FeatureTagTable::query()
            ->setSelect([FeatureTagTable::FIELD_ID])
            ->where(FeatureTagTable::FIELD_ID, $tagId)
            ->setLimit(1)
            ->fetch() !== false;
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

        if (
            str_contains($normalized, 'тег')
            || str_contains($normalized, 'tag')
            || str_contains($normalized, 'идентификатор')
            || str_contains($normalized, 'id')
        ) {
            return self::FIELD_TAG_ID;
        }

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

        if (str_contains($normalized, 'стратег') || str_contains($normalized, 'strategy')) {
            return self::FIELD_STRATEGIES;
        }

        return null;
    }
}
