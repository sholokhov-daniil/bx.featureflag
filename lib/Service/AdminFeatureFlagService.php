<?php

declare(strict_types=1);

namespace Sholokhov\Featureflag\Service;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Psr\Container\NotFoundExceptionInterface;
use Sholokhov\Featureflag\DTO\FeatureFlagPayload;
use Sholokhov\Featureflag\Feature;
use Sholokhov\Featureflag\Field\FieldInterface;
use Sholokhov\Featureflag\ORM\FeatureTable;
use Sholokhov\Featureflag\ORM\FeatureTagTable;
use Sholokhov\Featureflag\ServiceProvider;
use Throwable;

/**
 * Координирует админские сценарии управления фича-флагами.
 *
 * Сервис оставляет за собой orchestration layer:
 * - валидирует простые scalar-параметры, которые приходят не через DTO;
 * - делегирует запись флагов доменному репозиторию/фасаду;
 * - выполняет CRUD тегов;
 * - передаёт ORM-строки presenter-классу для формирования API-ответа.
 */
final class AdminFeatureFlagService implements AdminFeatureFlagServiceInterface
{
    private const string CODE_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._-]*$/';
    private const int MAX_CODE_LENGTH = 255;
    private const int MAX_TAG_NAME_LENGTH = 255;

    /**
     * @var AdminFeatureFlagPresenter Presenter API-ответов админки.
     */
    private readonly AdminFeatureFlagPresenter $presenter;

    /**
     * @var AdminFeatureFlagErrorMapper Mapper ошибок админского API.
     */
    private readonly AdminFeatureFlagErrorMapper $errorMapper;

    /**
     * Создаёт сервис админских операций фича-флагов.
     *
     * @param AdminFeatureFlagPresenter|null $presenter Presenter API-ответов; null создаёт presenter по умолчанию.
     * @param AdminFeatureFlagErrorMapper|null $errorMapper Mapper ошибок; null создаёт mapper по умолчанию.
     * @return void
     */
    public function __construct(
        ?AdminFeatureFlagPresenter $presenter = null,
        ?AdminFeatureFlagErrorMapper $errorMapper = null,
    ) {
        $this->presenter = $presenter ?? new AdminFeatureFlagPresenter();
        $this->errorMapper = $errorMapper ?? new AdminFeatureFlagErrorMapper();
    }

    /**
     * Возвращает список фича-флагов для админского интерфейса.
     *
     * @return Result{items: array<int, array<string, mixed>>} Результат со списком фича-флагов.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    public function list(): Result
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = FeatureTable::query()
            ->setSelect(['*'])
            ->setOrder([
                FeatureTable::FIELD_DATE_CREATE => 'DESC',
                FeatureTable::FIELD_NAME => 'ASC',
            ])
            ->fetchAll();

        return $this->success([
            'items' => $this->presenter->presentFlags($rows),
        ]);
    }

    /**
     * Возвращает один фича-флаг по символьному коду.
     *
     * @param string $code Символьный код фича-флага.
     * @return Result{flag: array<string, mixed>} Результат с данными фича-флага.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    public function get(string $code): Result
    {
        $result = new Result();
        $code = $this->normalizeCode($code);

        if (!$this->validateCode($result, $code)) {
            return $result;
        }

        $row = $this->findFlagRow($code);
        if ($row === null) {
            $this->errorMapper->addFieldError($result, AdminFeatureFlagField::CODE, $this->message('SHOLOKHOV_FEATUREFLAG_ERR_NOT_FOUND'));
            return $result;
        }

        return $this->withFlagData($result, $row);
    }

    /**
     * Создаёт новый фича-флаг.
     *
     * @param FeatureFlagPayload $payload DTO создаваемого фича-флага.
     * @return Result{flag: array<string, mixed>} Результат создания с подготовленным фича-флагом.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws NotFoundExceptionInterface При ошибке получения зависимости из контейнера.
     * @throws ObjectNotFoundException При ошибке получения зависимости из контейнера Bitrix.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    public function create(FeatureFlagPayload $payload): Result
    {
        $result = ServiceProvider::getFeatureRepository()->create($payload);
        if (!$result->isSuccess()) {
            return $this->errorMapper->enrichFailedResult($result);
        }

        return $this->withFreshFlagData($result, $payload->code);
    }

    /**
     * Обновляет существующий фича-флаг.
     *
     * @param FeatureFlagPayload $payload DTO обновляемого фича-флага.
     * @return Result{flag: array<string, mixed>} Результат обновления с подготовленным фича-флагом.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws NotFoundExceptionInterface При ошибке получения зависимости из контейнера.
     * @throws ObjectNotFoundException При ошибке получения зависимости из контейнера Bitrix.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    public function update(FeatureFlagPayload $payload): Result
    {
        $result = ServiceProvider::getFeatureRepository()->update($payload);
        if (!$result->isSuccess()) {
            return $this->errorMapper->enrichFailedResult($result);
        }

        return $this->withFreshFlagData($result, $payload->code);
    }

    /**
     * Удаляет фича-флаг по символьному коду.
     *
     * @param string $code Символьный код фича-флага.
     * @return Result{code: string} Результат удаления.
     */
    public function delete(string $code): Result
    {
        $result = new Result();
        $code = $this->normalizeCode($code);

        if (!$this->validateCode($result, $code)) {
            return $result;
        }

        $deleteResult = Feature::unRegister($code);
        if (!$deleteResult->isSuccess()) {
            $this->errorMapper->appendErrors($result, $deleteResult);
            return $result;
        }

        return $result->setData([
            'code' => $code,
        ]);
    }

    /**
     * Переключает активность фича-флага.
     *
     * @param string $code Символьный код фича-флага.
     * @param bool|int|float|string|null $enabled Целевое состояние активности.
     * @return Result{flag: array<string, mixed>} Результат переключения с подготовленным фича-флагом.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    public function toggle(string $code, bool|int|float|string|null $enabled): Result
    {
        $result = new Result();
        $code = $this->normalizeCode($code);
        $enabledValue = $this->parseBoolean($enabled);

        if (!$this->validateCode($result, $code)) {
            return $result;
        }

        if ($enabledValue === null) {
            $this->errorMapper->addFieldError($result, AdminFeatureFlagField::ENABLED, $this->message('SHOLOKHOV_FEATUREFLAG_ERR_INVALID_ENABLED'));
            return $result;
        }

        $toggleResult = $enabledValue ? Feature::enabled($code) : Feature::disabled($code);
        if (!$toggleResult->isSuccess()) {
            $this->errorMapper->appendErrors($result, $toggleResult);
            return $result;
        }

        return $this->withFreshFlagData($result, $code);
    }

    /**
     * Возвращает список тегов фича-флагов.
     *
     * @return Result{items: array<int, array<string, mixed>>} Результат со списком тегов.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    public function tagList(): Result
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = FeatureTagTable::query()
            ->setSelect([
                FeatureTagTable::FIELD_ID,
                FeatureTagTable::FIELD_NAME,
                FeatureTagTable::FIELD_SORT,
            ])
            ->setOrder([
                FeatureTagTable::FIELD_SORT => 'ASC',
                FeatureTagTable::FIELD_NAME => 'ASC',
                FeatureTagTable::FIELD_ID => 'ASC',
            ])
            ->fetchAll();

        return $this->success([
            'items' => $this->presenter->presentTags($rows),
        ]);
    }

    /**
     * Создаёт тег фича-флагов.
     *
     * @param string $name Название тега.
     * @return Result{tag: array<string, mixed>} Результат создания тега.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    public function tagCreate(string $name): Result
    {
        $result = new Result();
        $name = $this->normalizeTagName($name);

        if (!$this->validateTagName($result, $name)) {
            return $result;
        }

        if ($this->isTagNameBusy($name)) {
            $this->errorMapper->addFieldError($result, AdminFeatureFlagField::NAME, $this->message('SHOLOKHOV_FEATUREFLAG_ERR_TAG_DUPLICATE'));
            return $result;
        }

        $createResult = FeatureTagTable::add([
            FeatureTagTable::FIELD_NAME => $name,
        ]);

        if (!$createResult->isSuccess()) {
            $this->errorMapper->appendErrors($result, $createResult);
            return $result;
        }

        return $this->withTagData($result, (int)$createResult->getId());
    }

    /**
     * Обновляет тег фича-флагов.
     *
     * @param string $id Идентификатор тега.
     * @param string $name Новое название тега.
     * @return Result{tag: array<string, mixed>} Результат обновления тега.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    public function tagUpdate(string $id, string $name): Result
    {
        $result = new Result();
        $tagId = $this->parseTagId($result, $id);
        $name = $this->normalizeTagName($name);

        if ($tagId === null || !$this->validateTagName($result, $name)) {
            return $result;
        }

        if ($this->getTagRow($tagId, $result) === null) {
            return $result;
        }

        if ($this->isTagNameBusy($name, $tagId)) {
            $this->errorMapper->addFieldError($result, AdminFeatureFlagField::NAME, $this->message('SHOLOKHOV_FEATUREFLAG_ERR_TAG_DUPLICATE'));
            return $result;
        }

        $updateResult = FeatureTagTable::update($tagId, [
            FeatureTagTable::FIELD_NAME => $name,
        ]);

        if (!$updateResult->isSuccess()) {
            $this->errorMapper->appendErrors($result, $updateResult);
            return $result;
        }

        return $this->withTagData($result, $tagId);
    }

    /**
     * Удаляет тег фича-флагов.
     *
     * @param string $id Идентификатор тега.
     * @return Result{id: int} Результат удаления тега.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    public function tagDelete(string $id): Result
    {
        $result = new Result();
        $tagId = $this->parseTagId($result, $id);

        if ($tagId === null) {
            return $result;
        }

        if ($this->getTagRow($tagId, $result) === null) {
            return $result;
        }

        $this->detachTagFromFlags($tagId);

        $deleteResult = FeatureTagTable::delete($tagId);
        if (!$deleteResult->isSuccess()) {
            $this->errorMapper->appendErrors($result, $deleteResult);
            return $result;
        }

        return $result->setData([
            'id' => $tagId,
        ]);
    }

    /**
     * Возвращает список зарегистрированных стратегий доступа.
     *
     * @return Result{items: array<int, array<string, mixed>>} Результат со списком стратегий.
     */
    public function strategyList(): Result
    {
        $result = new Result();

        try {
            /** @var array<int, array<string, mixed>> $items */
            $items = [];

            foreach (ServiceProvider::getStrategyRegistry()->getAll() as $strategy) {
                $items[] = [
                    'code' => $strategy->getCode(),
                    'name' => $strategy->getName(),
                    'description' => $strategy->getDescription(),
                    'fields' => array_map(
                        static fn(FieldInterface $field) => $field->toArray(),
                        $strategy->getFields()
                    ),
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
     * Создаёт успешный Result с данными.
     *
     * @param array<string, mixed> $data Данные результата.
     * @return Result Результат с установленными данными.
     */
    private function success(array $data): Result
    {
        return (new Result())->setData($data);
    }

    /**
     * Нормализует символьный код фича-флага.
     *
     * @param string $code Исходный код.
     * @return string Код без внешних пробелов.
     */
    private function normalizeCode(string $code): string
    {
        return trim($code);
    }

    /**
     * Проверяет символьный код фича-флага и добавляет ошибку в Result при невалидном значении.
     *
     * @param Result $result Результат операции.
     * @param string $code Символьный код фича-флага.
     * @return bool true, если код валиден.
     */
    private function validateCode(Result $result, string $code): bool
    {
        if ($code === '') {
            $this->errorMapper->addFieldError($result, AdminFeatureFlagField::CODE, $this->message('SHOLOKHOV_FEATUREFLAG_ERR_EMPTY_CODE'));
            return false;
        }

        if (mb_strlen($code) > self::MAX_CODE_LENGTH) {
            $this->errorMapper->addFieldError($result, AdminFeatureFlagField::CODE, $this->message('SHOLOKHOV_FEATUREFLAG_ERR_CODE_TOO_LONG'));
            return false;
        }

        if (!preg_match(self::CODE_PATTERN, $code)) {
            $this->errorMapper->addFieldError($result, AdminFeatureFlagField::CODE, $this->message('SHOLOKHOV_FEATUREFLAG_ERR_INVALID_CODE'));
            return false;
        }

        return true;
    }

    /**
     * Ищет ORM-строку фича-флага по символьному коду.
     *
     * @param string $code Символьный код фича-флага.
     * @return array<string, mixed>|null ORM-строка или null, если флаг не найден.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    private function findFlagRow(string $code): ?array
    {
        if ($code === '') {
            return null;
        }

        $row = FeatureTable::query()
            ->setSelect(['*'])
            ->where(FeatureTable::FIELD_CODE, $code)
            ->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * Загружает свежую ORM-строку фича-флага и устанавливает её в Result.
     *
     * @param Result $result Результат операции с фича-флагом.
     * @param string $code Символьный код фича-флага.
     * @return Result Результат с данными флага, если флаг найден.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    private function withFreshFlagData(Result $result, string $code): Result
    {
        $row = $this->findFlagRow($this->normalizeCode($code));
        if ($row === null) {
            return $result;
        }

        return $this->withFlagData($result, $row);
    }

    /**
     * Устанавливает ORM-строку фича-флага в Result в формате API.
     *
     * @param Result $result Результат операции с фича-флагом.
     * @param array<string, mixed> $row ORM-строка фича-флага.
     * @return Result Результат с подготовленным флагом.
     * @throws ArgumentException При ошибке ORM-запроса связанных данных.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств связанных данных.
     * @throws SystemException При системной ошибке ORM.
     */
    private function withFlagData(Result $result, array $row): Result
    {
        return $result->setData([
            'flag' => $this->presenter->presentFlag($row),
        ]);
    }

    /**
     * Преобразует входное значение активности в bool или null.
     *
     * @param bool|int|float|string|null $value Значение активности из HTTP/API.
     * @return bool|null Нормализованное значение или null при невалидном входе.
     */
    private function parseBoolean(bool|int|float|string|null $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        if (is_float($value)) {
            return match ($value) {
                1.0 => true,
                0.0 => false,
                default => null,
            };
        }

        if (is_string($value)) {
            return match (mb_strtolower(trim($value))) {
                '1', 'y', 'yes', 'true', 'on' => true,
                '0', 'n', 'no', 'false', 'off', '' => false,
                default => null,
            };
        }

        return null;
    }

    /**
     * Нормализует название тега.
     *
     * @param string $name Исходное название тега.
     * @return string Название без внешних пробелов.
     */
    private function normalizeTagName(string $name): string
    {
        return trim($name);
    }

    /**
     * Проверяет название тега и добавляет ошибку в Result при невалидном значении.
     *
     * @param Result $result Результат операции.
     * @param string $name Название тега.
     * @return bool true, если название валидно.
     */
    private function validateTagName(Result $result, string $name): bool
    {
        if ($name === '') {
            $this->errorMapper->addFieldError($result, AdminFeatureFlagField::NAME, $this->message('SHOLOKHOV_FEATUREFLAG_ERR_EMPTY_TAG_NAME'));
            return false;
        }

        if (mb_strlen($name) > self::MAX_TAG_NAME_LENGTH) {
            $this->errorMapper->addFieldError($result, AdminFeatureFlagField::NAME, $this->message('SHOLOKHOV_FEATUREFLAG_ERR_TAG_NAME_TOO_LONG'));
            return false;
        }

        return true;
    }

    /**
     * Преобразует идентификатор тега из строки запроса.
     *
     * @param Result $result Результат операции.
     * @param string $id Идентификатор тега из API.
     * @return int|null Положительный идентификатор или null при ошибке.
     */
    private function parseTagId(Result $result, string $id): ?int
    {
        $tagId = (int)trim($id);
        if ($tagId <= 0) {
            $this->errorMapper->addFieldError($result, AdminFeatureFlagField::ID, $this->message('SHOLOKHOV_FEATUREFLAG_ERR_TAG_INVALID_ID'));
            return null;
        }

        return $tagId;
    }

    /**
     * Проверяет, занято ли название тега.
     *
     * @param string $name Название тега.
     * @param int|null $exceptTagId Идентификатор тега, который нужно исключить из проверки.
     * @return bool true, если название уже занято другим тегом.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    private function isTagNameBusy(string $name, ?int $exceptTagId = null): bool
    {
        $tag = $this->findTagByName($name);
        if ($tag === null) {
            return false;
        }

        return $exceptTagId === null || (int)$tag[FeatureTagTable::FIELD_ID] !== $exceptTagId;
    }

    /**
     * Загружает ORM-строку тега по идентификатору.
     *
     * @param int $tagId Идентификатор тега.
     * @param Result $result Результат операции для записи ошибки, если тег не найден.
     * @return array<string, mixed>|null ORM-строка тега или null.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    private function getTagRow(int $tagId, Result $result): ?array
    {
        $row = FeatureTagTable::query()
            ->setSelect([
                FeatureTagTable::FIELD_ID,
                FeatureTagTable::FIELD_NAME,
            ])
            ->where(FeatureTagTable::FIELD_ID, $tagId)
            ->fetch();

        if (!is_array($row)) {
            $this->errorMapper->addFieldError($result, AdminFeatureFlagField::ID, $this->message('SHOLOKHOV_FEATUREFLAG_ERR_TAG_NOT_FOUND'));
            return null;
        }

        return $row;
    }

    /**
     * Ищет ORM-строку тега по названию.
     *
     * @param string $name Название тега.
     * @return array<string, mixed>|null ORM-строка тега или null.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
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

        return is_array($row) ? $row : null;
    }

    /**
     * Устанавливает свежие данные тега в Result.
     *
     * @param Result $result Результат операции с тегом.
     * @param int $tagId Идентификатор тега.
     * @return Result Результат с подготовленным тегом, если тег найден.
     * @throws ArgumentException При ошибке ORM-запроса.
     * @throws ObjectPropertyException При ошибке чтения ORM-свойств.
     * @throws SystemException При системной ошибке ORM.
     */
    private function withTagData(Result $result, int $tagId): Result
    {
        $tag = $this->getTagRow($tagId, $result);
        if ($tag === null) {
            return $result;
        }

        return $result->setData([
            'tag' => $this->presenter->presentTag($tag),
        ]);
    }

    /**
     * Снимает удаляемый тег со всех фича-флагов.
     *
     * @param int $tagId Идентификатор удаляемого тега.
     * @return void
     * @throws ArgumentException При ошибке ORM-entity.
     * @throws SystemException При системной ошибке ORM.
     */
    private function detachTagFromFlags(int $tagId): void
    {
        $connection = FeatureTable::getEntity()->getConnection();
        $sqlHelper = $connection->getSqlHelper();
        $featureTable = $sqlHelper->quote(FeatureTable::getTableName());
        $tagField = $sqlHelper->quote(FeatureTable::FIELD_TAG_ID);

        $connection->queryExecute("UPDATE {$featureTable} SET {$tagField} = NULL WHERE {$tagField} = {$tagId}");
    }

    /**
     * Возвращает локализованное сообщение по коду.
     *
     * @param string $code Код сообщения Loc.
     * @return string Текст сообщения или код, если перевод не найден.
     */
    private function message(string $code): string
    {
        $message = Loc::getMessage($code);

        return is_string($message) ? $message : $code;
    }
}
