<?php

declare(strict_types=1);

namespace Sholokhov\FeatureFlag\Infrastructure\Repository;

use Exception;
use Throwable;

use Sholokhov\Featureflag\Domain\Repository\FeatureFlagRepositoryInterface;
use Sholokhov\Featureflag\FeatureInterface;
use Sholokhov\FeatureFlag\Infrastructure\Result\FailureResultTrait;
use Sholokhov\Featureflag\Infrastructure\Persistence\FeatureFlagMapper;
use Sholokhov\Featureflag\ORM\FeatureTable;

use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\DB\DuplicateEntryException;

/**
 * Определяет операции получения, сохранения и удаления доменных сущностей фича-флагов
 */
final readonly class FeatureFlagRepository implements FeatureFlagRepositoryInterface
{
    use FailureResultTrait;

    public function __construct(
        private FeatureFlagMapper $mapper
    )
    {
    }

    /**
     * Возвращает фича-флаг по символьному коду
     *
     * При первом вызове выполняет загрузку всех флагов в runtime-кеш.
     * Если флаг не найден или загрузка завершилась ошибкой, возвращает null.
     *
     * @param string $code Символьный код фичи
     *
     * @return FeatureInterface|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function findByCode(string $code): ?FeatureInterface
    {
        $entity = FeatureTable::getByPrimary($code)->fetchObject();
        return $entity ? $this->mapper->toDomain($entity) : null;
    }

    /**
     * Сохраняет фича-флаг.
     * Если отсутствует фича-флаг, то он создается - в ином случае обновляется
     *
     * @param FeatureInterface $flag
     *
     * @return Result
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function save(FeatureInterface $flag): Result
    {
        return $this->existsByCode($flag->getCode())
            ? $this->update($flag)
            : $this->create($flag);

    }

    /**
     * Удаляет фича-флаг по коду
     *
     * @param string $code
     *
     * @return Result
     * @throws Exception
     */
    public function deleteByCode(string $code): Result
    {
        return FeatureTable::delete($code);
    }

    /**
     * Производит создание нового фича-флага
     *
     * @param FeatureInterface $flag
     *
     * @return Result
     */
    public function create(FeatureInterface $flag): Result
    {
        try {
            $fields = $this->mapper->toPersistence($flag);
            return FeatureTable::add($fields);
        } catch (DuplicateEntryException) {
            return $this->errorResult(
                'Флаг с таким кодом уже существует',
                'DUPLICATE_CODE',
                ['field' => 'code']
            );
        } catch (Throwable $e) {
            return $this->errorResult('Ошибка при создании фича-флага: ' . $e->getMessage());
        }
    }

    /**
     * Обновляет существующую информацию фичи-флага
     *
     * @param FeatureInterface $flag
     *
     * @return Result
     */
    public function update(FeatureInterface $flag): Result
    {
        try {
            $fields = $this->mapper->toPersistence($flag);
            return FeatureTable::update($flag->getCode(), $fields);
        } catch (Throwable $e) {
            return $this->errorResult('Ошибка при обновлении фича-флага: ' . $e->getMessage());
        }
    }

    /**
     * Проверяет наличие фича-флага
     *
     * @param string $code
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function existsByCode(string $code): bool
    {
        return !empty(FeatureTable::getByPrimary($code)->fetch());
    }
}