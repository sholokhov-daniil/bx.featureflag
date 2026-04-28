<?php

namespace Sholokhov\Featureflag;

use Throwable;
use RuntimeException;

use Sholokhov\Featureflag\DTO\FlagInfo;

use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ObjectNotFoundException;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Фасад для работы с фича-флагами
 *
 * Предоставляет удобный API для проверки активности фич,
 * а также выполнения условной логики на основе состояния флагов.
 *
 * Все методы являются статическими и используются как точка входа
 * для взаимодействия с системой фича-флагов.
 */
class Feature
{
    /**
     * Проверяет, активна ли фича
     *
     * Если флаг не найден или произошла ошибка при его получении —
     * считается, что фича выключена.
     *
     * @param string $code Символьный код фичи
     * @return bool true, если фича активна
     */
    public static function isEnabled(string $code): bool
    {
        return self::getByCode($code)?->isEnabled() ?? false;
    }

    /**
     * Проверяет, отключена ли фича
     *
     * Является инверсией метода {@see self::isEnabled()}.
     *
     * @param string $code Символьный код фичи
     * @return bool true, если фича отключена
     */
    public static function isDisabled(string $code): bool
    {
        return !self::isEnabled($code);
    }

    /**
     * Выполняет один из колбэков в зависимости от состояния фичи
     *
     * Если фича активна — вызывается $enabled,
     * иначе — $disabled.
     *
     * @param string   $code     Символьный код фичи
     * @param callable $enabled  Колбэк при активной фиче
     * @param callable $disabled Колбэк при отключенной фиче
     *
     * @return void
     */
    public static function when(string $code, callable $enabled, callable $disabled): void
    {
        self::isEnabled($code) ? $enabled() : $disabled();
    }

    /**
     * Требует, чтобы фича была активна
     *
     * Если фича отключена — выбрасывается исключение.
     * Удобно использовать для fail-fast логики.
     *
     * @param string $code Символьный код фичи
     *
     * @throws RuntimeException Если фича отключена
     * @return void
     */
    public static function require(string $code): void
    {
        if (self::isDisabled($code)) {
            throw new RuntimeException("Feature '{$code}' is disabled");
        }
    }

    /**
     * Проверяет, активна ли хотя бы одна фича из списка
     *
     * Возвращает true при первой найденной активной фиче.
     *
     * @param string[] $codes Список кодов фич
     * @return bool true, если хотя бы одна фича активна
     */
    public static function any(array $codes): bool
    {
        foreach ($codes as $code) {
            if (self::isEnabled($code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет, что все фичи из списка активны
     *
     * Возвращает false при первой найденной отключенной фиче.
     *
     * @param string[] $codes Список кодов фич
     * @return bool true, если все фичи активны
     */
    public static function all(array $codes): bool
    {
        foreach ($codes as $code) {
            if (self::isDisabled($code)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Регистрация новой фичи
     *
     * @param FlagInfo $flag
     * @return AddResult
     * @throws ObjectNotFoundException
     * @throws NotFoundExceptionInterface
     */
    public static function register(FlagInfo $flag): AddResult
    {
        return ServiceProvider::getFlagRepository()->create($flag);
    }

    /**
     * Возвращает флаг по его символьному коду
     *
     * В случае ошибки (например, проблема с репозиторием)
     * возвращает null.
     *
     * @param string $code Символьный код фичи
     * @return FlagInterface|null Объект флага или null, если не найден или произошла ошибка
     */
    public static function getByCode(string $code): ?FlagInterface
    {
        try {
            $flag = ServiceProvider::getFlagRepository()->findByCode($code);
        } catch (Throwable) {
            $flag = null;
            // TODO: логирование ошибки
        }

        return $flag;
    }
}