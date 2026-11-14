<?php

declare(strict_types=1);

namespace Sholokhov\Featureflag\Application\UseCase;

use Sholokhov\Featureflag\Domain\Repository\FeatureFlagRepositoryInterface;
use Sholokhov\FeatureFlag\Infrastructure\Result\FailureResultTrait;

use Bitrix\Main\Result;

/**
 * Сценарий изменения глобального состояния фича-флага.
 *
 * Загружает доменную сущность, изменяет её состояние
 * и сохраняет изменения через репозиторий.
 *
 * Не выполняет проверку runtime-правил и стратегий доступа.
 */
final readonly class SetFeatureFlagEnabled
{
    use FailureResultTrait;

    public function __construct(
        private FeatureFlagRepositoryInterface $repository,
    ) {
    }

    /**
     * Устанавливает глобальное состояние фича-флага
     *
     * @param string $code    Символьный код флага
     * @param bool   $enabled Требуемое состояние активности
     *
     * @return Result
     */
    public function execute(string $code, bool $enabled): Result
    {
        $flag = $this->repository->findByCode($code);

        if ($flag === null) {
            return $this->errorResult(
                sprintf('Feature flag "%s" not found', $code),
                'NOT_FOUND'
            );
        }

        $enabled ? $flag->enable() : $flag->disable();

        return $this->repository->update($flag);
    }
}