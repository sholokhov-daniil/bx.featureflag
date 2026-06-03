<?php

namespace Sholokhov\Featureflag\Permission;

/**
 * Проверка прав доступа к модулю
 */
class ModulePermission implements PermissionInterface
{
    /**
     * Полный доступ
     *
     * @return bool
     */
    public function hasFullAccess(): bool
    {
        return $this->getAccess() === 'X';
    }

    /**
     * Присутствует доступ на чтение данных
     *
     * @return bool
     */
    public function canRead(): bool
    {
        return $this->getAccess() >= 'R';
    }

    /**
     * У пользователя полный доступ
     *
     * @return bool
     */
    public function canWrite(): bool
    {
        return $this->getAccess() >= 'W';
    }

    /**
     * Доступ закрыт
     *
     * @return bool
     */
    public function isDenied(): bool
    {
        return $this->getAccess() === 'D';
    }

    /**
     * Возвращает уровень доступа к модулю
     *
     * @return string
     */
    public function getAccess(): string
    {
        global $APPLICATION;
        return (string)$APPLICATION->GetGroupRight('sholokhov.featureflag');
    }
}