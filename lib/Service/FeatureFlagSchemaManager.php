<?php

namespace Sholokhov\Featureflag\Service;

use Throwable;
use Sholokhov\Featureflag\ORM\FeatureTable;

/**
 * Поддерживает фактическую схему таблицы фича-флагов в актуальном состоянии.
 *
 * Нужен для уже установленных экземпляров модуля: после добавления поля в ORM map
 * старые БД ещё не содержат колонку, а запросы с setSelect(['*']) начинают
 * обращаться к ней сразу после обновления кода.
 */
final class FeatureFlagSchemaManager
{
    private static bool $actualSchemaEnsured = false;

    /**
     * Создаёт недостающие колонки таблицы фича-флагов.
     *
     * @return void
     */
    public static function ensureActualSchema(): void
    {
        if (self::$actualSchemaEnsured) {
            return;
        }

        try {
            $connection = FeatureTable::getEntity()->getConnection();
            $tableName = FeatureTable::getTableName();

            if (!$connection->isTableExists($tableName)) {
                self::$actualSchemaEnsured = true;
                return;
            }

            if ($connection->getTableField($tableName, FeatureTable::FIELD_AVAILABLE_IN_JS) === null) {
                $helper = $connection->getSqlHelper();
                $connection->queryExecute(
                    'ALTER TABLE ' . $helper->quote($tableName)
                    . ' ADD ' . $helper->quote(FeatureTable::FIELD_AVAILABLE_IN_JS)
                    . " char(1) NOT NULL DEFAULT ''"
                );
            }

            self::$actualSchemaEnsured = true;
        } catch (Throwable $exception) {
            AddMessage2Log('Ошибка актуализации схемы фича-флагов: ' . $exception->getMessage());
        }
    }

    /**
     * Закрывает создание utility-класса.
     *
     * @return void
     */
    private function __construct()
    {
    }
}
