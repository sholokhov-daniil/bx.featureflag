<?php

namespace Sholokhov\Featureflag\Http\Controller;

use Sholokhov\Featureflag\DTO\FeatureFlagPayload;
use Sholokhov\Featureflag\Http\AutoWire\ValidationParameter;
use Sholokhov\Featureflag\Http\Middleware\AdminAccessMiddleware;
use Sholokhov\Featureflag\Http\Request\FeatureToggleRequest;
use Sholokhov\Featureflag\Http\Request\TagCreateRequest;
use Sholokhov\Featureflag\Http\Request\TagIdRequest;
use Sholokhov\Featureflag\Http\Request\TagUpdateRequest;
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
            'tagList' => $config,
            'tagCreate' => $config,
            'tagUpdate' => $config,
            'tagDelete' => $config,
            'strategyList' => $config,
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
                FeatureFlagPayload::class,
                static fn() => FeatureFlagPayload::fromRequest($request),
                $addValidationErrors,
            ),
            new ValidationParameter(
                FeatureToggleRequest::class,
                static fn() => FeatureToggleRequest::fromRequest($request),
                $addValidationErrors,
            ),
            new ValidationParameter(
                TagCreateRequest::class,
                static fn() => TagCreateRequest::fromRequest($request),
                $addValidationErrors,
            ),
            new ValidationParameter(
                TagUpdateRequest::class,
                static fn() => TagUpdateRequest::fromRequest($request),
                $addValidationErrors,
            ),
            new ValidationParameter(
                TagIdRequest::class,
                static fn() => TagIdRequest::fromRequest($request),
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
     * @param string $code
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function getAction(string $code, AdminFeatureFlagServiceInterface $service): ?array
    {
        return $this->resolveServiceResult(
            $service->get($code),
        );
    }

    /**
     * Создаёт фича-флаг.
     *
     * @param FeatureFlagPayload|null $request
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function createAction(?FeatureFlagPayload $request, AdminFeatureFlagServiceInterface $service): ?array
    {
        if ($request === null) {
            return null;
        }

        return $this->resolveServiceResult(
            $service->create($request),
        );
    }

    /**
     * Обновляет фича-флаг.
     *
     * @param FeatureFlagPayload|null $request
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function updateAction(?FeatureFlagPayload $request, AdminFeatureFlagServiceInterface $service): ?array
    {
        if ($request === null) {
            return null;
        }

        return $this->resolveServiceResult(
            $service->update($request),
        );
    }

    /**
     * Удаляет фича-флаг.
     *
     * @param string $code
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function deleteAction(string $code, AdminFeatureFlagServiceInterface $service): ?array
    {
        return $this->resolveServiceResult(
            $service->delete($code),
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
     * Возвращает список тегов фича-флагов.
     *
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function tagListAction(AdminFeatureFlagServiceInterface $service): ?array
    {
        return $this->resolveServiceResult(
            $service->tagList(),
        );
    }

    /**
     * Создаёт тег.
     *
     * @param TagCreateRequest|null $request
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function tagCreateAction(?TagCreateRequest $request, AdminFeatureFlagServiceInterface $service): ?array
    {
        if ($request === null) {
            return null;
        }

        return $this->resolveServiceResult(
            $service->tagCreate($request->name),
        );
    }

    /**
     * Обновляет тег.
     *
     * @param TagUpdateRequest|null $request
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function tagUpdateAction(?TagUpdateRequest $request, AdminFeatureFlagServiceInterface $service): ?array
    {
        if ($request === null) {
            return null;
        }

        return $this->resolveServiceResult(
            $service->tagUpdate($request->id, $request->name),
        );
    }

    /**
     * Удаляет тег.
     *
     * @param TagIdRequest|null $request
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function tagDeleteAction(?TagIdRequest $request, AdminFeatureFlagServiceInterface $service): ?array
    {
        if ($request === null) {
            return null;
        }

        return $this->resolveServiceResult(
            $service->tagDelete($request->id),
        );
    }

    /**
     * Возвращает список доступных стратегий доступа.
     *
     * @param AdminFeatureFlagServiceInterface $service
     * @return array<string, mixed>|null
     */
    public function strategyListAction(AdminFeatureFlagServiceInterface $service): ?array
    {
        return $this->resolveServiceResult(
            $service->strategyList(),
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
