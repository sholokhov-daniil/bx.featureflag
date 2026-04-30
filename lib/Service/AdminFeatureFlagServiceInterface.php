<?php

namespace Sholokhov\Featureflag\Service;

use Bitrix\Main\Result;

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
     * @param string $code
     * @param string $name
     * @param string $description
     * @param mixed $enabled
     * @return Result{flag: array<string, mixed>}
     */
    public function create(string $code, string $name, string $description, mixed $enabled): Result;

    /**
     * Обновляет существующий фича-флаг.
     *
     * @param string $code
     * @param string $name
     * @param string $description
     * @param mixed $enabled
     * @return Result{flag: array<string, mixed>}
     */
    public function update(string $code, string $name, string $description, mixed $enabled): Result;

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
}
