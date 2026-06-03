<?php

namespace Sholokhov\Featureflag\Permission;

interface PermissionInterface
{
    /**
     * Присутствует доступ на чтение данных
     *
     * @return bool
     */
    public function canRead(): bool;

    /**
     * У пользователя полный доступ
     *
     * @return bool
     */
    public function canWrite(): bool;

    /**
     * Доступ закрыт
     *
     * @return bool
     */
    public function isDenied(): bool;

    /**
     * Полный доступ
     *
     * @return bool
     */
    public function hasFullAccess(): bool;

    /**
     * Возвращает уровень доступа
     *
     * @return string
     */
    public function getAccess(): string;
}