<?php

namespace Sholokhov\Featureflag\ORM;

use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\ORM\Data\DataManager;
use Sholokhov\Featureflag\Normalizer\StrategyDecoder;

/**
 * ORM таблица фича-флагов
 *
 * Хранит базовую информацию о фичах:
 * - код фичи
 * - состояние (включена/выключена)
 * - название и описание
 * - служебные поля (дата и пользователь создания/обновления)
 *
 * Поддерживает автоматическое заполнение:
 * - даты создания и обновления
 * - пользователя, создавшего/обновившего запись
 *
 * @package Sholokhov\Featureflag\ORM
 */
class FeatureTable extends DataManager
{
    /** Символьный код фичи (primary key) */
    public const string FIELD_CODE = 'CODE';

    /** Флаг активности */
    public const string FIELD_ENABLED = 'ENABLED';

    /** Название фичи */
    public const string FIELD_NAME = 'NAME';

    /** Описание фичи */
    public const string FIELD_DESCRIPTION = 'DESCRIPTION';

    /** Идентификатор тега */
    public const string FIELD_TAG_ID = 'TAG_ID';

    /** JSON-конфигурация стратегий доступа */
    public const string FIELD_STRATEGIES = 'STRATEGIES';

    /** Дата создания */
    public const string FIELD_DATE_CREATE = 'DATE_CREATE';

    /** Дата последнего обновления */
    public const string FIELD_DATE_UPDATE = 'DATE_UPDATE';

    /** Пользователь, создавший запись */
    public const string FIELD_CREATED_BY = 'CREATED_BY';

    /** Пользователь, обновивший запись */
    public const string FIELD_UPDATED_BY = 'UPDATED_BY';

    /** Плановая дата удаления флага  */
    public const string REMOVE_PLANNED_AT = 'REMOVE_PLANNED_AT';

    public static function getTableName(): string
    {
        return 'sholokhov_featureflag';
    }

    public static function getMap(): array
    {
        return [
            (new Fields\StringField(self::FIELD_CODE))
                ->configurePrimary(),

            (new Fields\BooleanField(self::FIELD_ENABLED))
                ->configureDefaultValue(false),

            (new Fields\StringField(self::FIELD_NAME))
                ->configureRequired()
                ->configureSize(255),

            (new Fields\TextField(self::FIELD_DESCRIPTION))
                ->configureDefaultValue(''),

            (new Fields\IntegerField(self::FIELD_TAG_ID))
                ->configureNullable(),

            (new Fields\TextField(self::FIELD_STRATEGIES))
                ->configureDefaultValue('')
                ->addSaveDataModifier(
                    static fn($value) => json_encode($value)
                )
                ->addFetchDataModifier(
                    static fn($value) =>  new StrategyDecoder()->decode($value)
                ),

            (new Fields\DatetimeField(self::FIELD_DATE_CREATE))
                ->configureRequired()
                ->configureDefaultValueNow(),

            (new Fields\DatetimeField(self::FIELD_DATE_UPDATE))
                ->configureRequired()
                ->configureDefaultValueNow(),

            (new Fields\DatetimeField(self::REMOVE_PLANNED_AT)),

            (new Fields\IntegerField(self::FIELD_CREATED_BY))
                ->configureRequired(),

            (new Fields\IntegerField(self::FIELD_UPDATED_BY))
                ->configureRequired(),
        ];
    }

    public static function onBeforeAdd(Event $event): EventResult
    {
        $result = new EventResult();

        $now = new DateTime();
        $userId = self::getCurrentUserId();

        $result->modifyFields([
            self::FIELD_DATE_CREATE => $now,
            self::FIELD_DATE_UPDATE => $now,
            self::FIELD_CREATED_BY => $userId,
            self::FIELD_UPDATED_BY => $userId,
        ]);

        return $result;
    }

    public static function onBeforeUpdate(Event $event): EventResult
    {
        $result = new EventResult();

        $result->modifyFields([
            self::FIELD_DATE_UPDATE => new DateTime(),
            self::FIELD_UPDATED_BY => self::getCurrentUserId(),
        ]);

        return $result;
    }

    private static function getCurrentUserId(): int
    {
        global $USER;
        return $USER ? (int)$USER->GetID() : 0;
    }
}
