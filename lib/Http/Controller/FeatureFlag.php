<?php

namespace Sholokhov\Featureflag\Http\Controller;

use Sholokhov\Featureflag\Http\AutoWire\ValidationParameter;
use Sholokhov\Featureflag\Http\Middleware\AdminAccessMiddleware;
use Sholokhov\Featureflag\Http\Request\FeatureCodeRequest;
use Sholokhov\Featureflag\Http\Request\FeatureToggleRequest;
use Sholokhov\Featureflag\Http\Request\FeatureUpsertRequest;
use Sholokhov\Featureflag\Service\AdminFeatureFlagServiceInterface;
use Sholokhov\Featureflag\ServiceProvider;

use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Result;

/**
 * HTTP-контроллер админки фича-флагов.
 *
 * Контроллер остаётся thin-layer:
 * - получает request DTO через AutoWire;
 * - делегирует операции сервису;
 * - преобразует Result сервиса в HTTP-ответ.
 */
final class FeatureFlag extends Controller
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function configureActions(): array
    {
        $config = [
            '+prefilters' => [
                new AdminAccessMiddleware(),
            ],
        ];

        return [
            'list' => $config,
            'get' => $config,
            'create' => $config,
            'update' => $config,
            'delete' => $config,
            'toggle' => $config,
        ];
    }

    /**
     * Регистрирует зависимости для автоподстановки в action-методы.
     *
     * @return array<int, Parameter>
     */
    public function getAutoWiredParameters(): array
    {
        $request = $this->getRequest();
        $addValidationErrors = function (array $errors): void {
            $this->addErrors($errors);
        };

        return [
            new Parameter(
                AdminFeatureFlagServiceInterface::class,
                static fn() => ServiceProvider::getAdminFeatureFlagService(),
            ),
            new ValidationParameter(
                FeatureCodeRequest::class,
                static fn() => FeatureCodeRequest::fromRequest($request),
                $addValidationErrors,
            ),
            new ValidationParameter(
                FeatureUpsertRequest::class,
                static fn() => FeatureUpsertRequest::fromRequest($request),
                $addValidationErrors,
            ),
            new ValidationParameter(
                FeatureToggleRequest::class,
                static fn() => FeatureToggleRequest::fromRequest($request),
                $addValidationErrors,
            ),
        ];
    }

    /**
     * Возвращает список фича-флагов.
     *
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function listAction(AdminFeatureFlagServiceInterface $service): ?array
    {
        return $this->resolveServiceResult(
            $service->list(),
        );
    }

    /**
     * Возвращает детальные данные фича-флага.
     *
     * @param FeatureCodeRequest|null $request
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function getAction(?FeatureCodeRequest $request, AdminFeatureFlagServiceInterface $service): ?array
    {
        if ($request === null) {
            return null;
        }

        return $this->resolveServiceResult(
            $service->get($request->code),
        );
    }

    /**
     * Создаёт фича-флаг.
     *
     * @param FeatureUpsertRequest|null $request
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function createAction(?FeatureUpsertRequest $request, AdminFeatureFlagServiceInterface $service): ?array
    {
        if ($request === null) {
            return null;
        }

        return $this->resolveServiceResult(
            $service->create($request->code, $request->name, $request->description, (bool)$request->enabled),
        );
    }

    /**
     * Обновляет фича-флаг.
     *
     * @param FeatureUpsertRequest|null $request
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function updateAction(?FeatureUpsertRequest $request, AdminFeatureFlagServiceInterface $service): ?array
    {
        if ($request === null) {
            return null;
        }

        return $this->resolveServiceResult(
            $service->update($request->code, $request->name, $request->description, (bool)$request->enabled),
        );
    }

    /**
     * Удаляет фича-флаг.
     *
     * @param FeatureCodeRequest|null $request
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function deleteAction(?FeatureCodeRequest $request, AdminFeatureFlagServiceInterface $service): ?array
    {
        if ($request === null) {
            return null;
        }

        return $this->resolveServiceResult(
            $service->delete($request->code),
        );
    }

    /**
     * Переключает активность фича-флага.
     *
     * @param FeatureToggleRequest|null $request
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function toggleAction(?FeatureToggleRequest $request, AdminFeatureFlagServiceInterface $service): ?array
    {
        if ($request === null) {
            return null;
        }

        return $this->resolveServiceResult(
            $service->toggle($request->code, (bool)$request->enabled),
        );
    }

    /**
     * Преобразует сервисный Result в формат ответа контроллера.
     *
     * @param Result $result
     * @return array<string, mixed>|null
     */
    private function resolveServiceResult(Result $result): ?array
    {
        if (!$result->isSuccess()) {
            $this->addErrors($result->getErrors());
            return null;
        }

        return $result->getData();
    }
}
