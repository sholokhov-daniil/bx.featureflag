<?php

declare(strict_types=1);

namespace Sholokhov\Featureflag\Http\Controller;

use Override;
use Throwable;

use Sholokhov\Featureflag\DTO\FeatureFlagPayload;
use Sholokhov\Featureflag\Feature;
use Sholokhov\Featureflag\FeatureInterface;
use Sholokhov\Featureflag\Http\AutoWire\ValidationParameter;
use Sholokhov\Featureflag\Http\Middleware\WriteAccessMiddleware;
use Sholokhov\Featureflag\Http\Middleware\ReadAccessMiddleware;
use Sholokhov\Featureflag\Http\Request\FeatureToggleRequest;
use Sholokhov\Featureflag\Http\Request\TagCreateRequest;
use Sholokhov\Featureflag\Http\Request\TagIdRequest;
use Sholokhov\Featureflag\Http\Request\TagUpdateRequest;
use Sholokhov\Featureflag\Infrastructure\Logger\LegacyLogger;
use Sholokhov\FeatureFlag\Infrastructure\Result\FailureResultTrait;
use Sholokhov\Featureflag\Presentation\Http\Response\Feature\ToggleResponse;
use Sholokhov\Featureflag\ServiceProvider;
use Sholokhov\Featureflag\Service\AdminFeatureFlagServiceInterface;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;

use Psr\Container\NotFoundExceptionInterface;

/**
 * HTTP-контроллер админки фича-флагов.
 *
 * @todo Сделать так, чтобы ошибки не возвращались со статусом 200
 * Контроллер остаётся thin-layer:
 * - получает request DTO через AutoWire;
 * - делегирует операции сервису;
 * - преобразует Result сервиса в HTTP-ответ.
 */
final class FeatureFlag extends Controller
{
    use FailureResultTrait;

    /**
     * @return array<string, array<string, mixed>>
     * @throws ObjectNotFoundException
     * @throws NotFoundExceptionInterface
     */
    #[Override]
    public function configureActions(): array
    {
        $permission = ServiceProvider::getModulePermission();

        $writeConfig = [
            '+prefilters' => [
                new WriteAccessMiddleware($permission)
            ]
        ];

        $readConfig = [
            '+prefilters' => [
                new ReadAccessMiddleware($permission)
            ]
        ];

        return [
            'list' => $readConfig,
            'get' => $readConfig,
            'create' => $writeConfig,
            'update' => $writeConfig,
            'delete' => $writeConfig,
            'toggle' => $writeConfig,
            'tagList' => $readConfig,
            'tagCreate' => $writeConfig,
            'tagUpdate' => $writeConfig,
            'tagDelete' => $writeConfig,
            'strategyList' => $readConfig,
            'saveViewOptions' => $readConfig,
        ];
    }

    /**
     * @todo Добавить обработку отсутствия фичи на уровне контроллера
     * @return ExactParameter
     * @throws BinderArgumentException
     */
    #[Override]
    public function getPrimaryAutoWiredParameter(): ExactParameter
    {
        return new ExactParameter(
            FeatureInterface::class,
            'feature',
            fn(string $className, string $code) => Feature::getByCode($code)
        );
    }

    /**
     * Регистрирует зависимости для автоподстановки в action-методы.
     *
     * @return array<int, Parameter>
     */
    #[Override]
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
     * @todo Переработать
     * @param AdminFeatureFlagServiceInterface $service
     *
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
     * @todo Переработать
     * @param string                           $code
     * @param AdminFeatureFlagServiceInterface $service
     *
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
     * @todo Переработать
     * @param FeatureFlagPayload|null          $request
     * @param AdminFeatureFlagServiceInterface $service
     *
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
     * @todo Переработать
     * @param FeatureFlagPayload|null          $request
     * @param AdminFeatureFlagServiceInterface $service
     *
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
     * @todo Переработать
     * @param string                           $code
     * @param AdminFeatureFlagServiceInterface $service
     *
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
     * @param FeatureInterface                 $feature
     * @param bool                             $enabled
     * @param AdminFeatureFlagServiceInterface $service
     *
     * @return array|null
     */
    public function toggleAction(
        FeatureInterface $feature,
        bool $enabled,
        AdminFeatureFlagServiceInterface $service
    ): ?ToggleResponse
    {
        try {
            $result = $service->toggle($feature->getCode(), $enabled);

            if ($result->isSuccess()) {
                return new ToggleResponse(enabled: $enabled);
            }

            $this->addErrors($result->getErrors());
        } catch (Throwable $e) {
            LegacyLogger::write($e);
            $this->addError(new Error('Ошибка обновления активности фичи'));
        }

        return null;
    }

    /**
     * Возвращает список тегов фича-флагов.
     *
     * @todo Переработать
     * @param AdminFeatureFlagServiceInterface $service
     *
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
     * @todo Переработать
     * @param TagCreateRequest|null            $request
     * @param AdminFeatureFlagServiceInterface $service
     *
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
     * @todo Переработать
     * @param TagUpdateRequest|null            $request
     * @param AdminFeatureFlagServiceInterface $service
     *
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
     * @todo Переработать
     * @param TagIdRequest|null                $request
     * @param AdminFeatureFlagServiceInterface $service
     *
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
     * @todo Переработать
     * @param AdminFeatureFlagServiceInterface $service
     *
     * @return array<string, mixed>|null
     */
    public function strategyListAction(AdminFeatureFlagServiceInterface $service): ?array
    {
        return $this->resolveServiceResult(
            $service->strategyList(),
        );
    }

    /**
     * Сохраняет пользовательские настройки отображения списка фича-флагов.
     *
     * @todo Переработать
     * @param string                           $displayMode
     * @param AdminFeatureFlagServiceInterface $service
     *
     * @return array<string, mixed>|null
     */
    public function saveViewOptionsAction(string $displayMode, AdminFeatureFlagServiceInterface $service): ?array
    {
        return $this->resolveServiceResult(
            $service->saveViewOptions($displayMode),
        );
    }

    /**
     * Преобразует сервисный Result в формат ответа контроллера.
     *
     * @param Result $result
     *
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
