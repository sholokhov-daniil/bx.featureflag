<?php

namespace Sholokhov\Featureflag\Service;

use Bitrix\Main\Result;
use Sholokhov\Featureflag\DTO\FlagInfo;

/**
 * Контракт сервиса управления фича-флагами в админке.
 */
interface AdminFeatureFlagServiceInterface
{
    /**
     * Возвращает список фича-флагов.
     *
     * @return Result{items: array<int, array<string, mixed>>}
     */
    public function list(): Result;

    /**
     * Возвращает данные одного фича-флага.
     *
     * @param string $code
     * @return Result{flag: array<string, mixed>}
     */
    public function get(string $code): Result;

    /**
     * Создаёт новый фича-флаг.
     *
     * @param FlagInfo $flag
     * @return Result{flag: array<string, mixed>}
     */
    public function create(FlagInfo $flag): Result;

    /**
     * Обновляет существующий фича-флаг.
     *
     * @param FlagInfo $flag
     * @return Result{flag: array<string, mixed>}
     */
    public function update(FlagInfo $flag): Result;

    /**
     * Удаляет фича-флаг.
     *
     * @param string $code
     * @return Result{code: string}
     */
    public function delete(string $code): Result;

    /**
     * Переключает активность фича-флага.
     *
     * @param string $code
     * @param mixed $enabled
     * @return Result{flag: array<string, mixed>}
     */
    public function toggle(string $code, mixed $enabled): Result;

    /**
     * Возвращает список тегов фича-флагов.
     *
     * @return Result{items: array<int, array<string, mixed>>}
     */
    public function tagList(): Result;

    /**
     * Создаёт новый тег.
     *
     * @param string $name
     * @return Result{tag: array<string, mixed>}
     */
    public function tagCreate(string $name): Result;

    /**
     * Обновляет существующий тег.
     *
     * @param string $id
     * @param string $name
     * @return Result{tag: array<string, mixed>}
     */
    public function tagUpdate(string $id, string $name): Result;

    /**
     * Удаляет тег.
     *
     * @param string $id
     * @return Result{id: int}
     */
    public function tagDelete(string $id): Result;

    /**
     * Возвращает список зарегистрированных стратегий доступа.
     *
     * @return Result{items: array<int, array<string, mixed>>}
     */
    public function strategyList(): Result;
}
