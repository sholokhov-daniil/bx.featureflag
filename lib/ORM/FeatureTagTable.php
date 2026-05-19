<?php

namespace Sholokhov\Featureflag\ORM;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;

/**
 * ORM таблица тегов фича-флагов.
 *
 * Тег может хранить общие стратегии доступа, которые наследуются
 * всеми фича-флагами с этим тегом.
 */
final class FeatureTagTable extends DataManager
{
    public const string FIELD_ID = 'ID';
    public const string FIELD_NAME = 'NAME';
    public const string FIELD_SORT = 'SORT';
    public const string FIELD_STRATEGIES = 'STRATEGIES';

    /**
     * Возвращает имя таблицы тегов фича-флагов.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'sholokhov_featureflag_tags';
    }

    /**
     * Возвращает ORM-карту таблицы тегов.
     *
     * @return array<int, Fields\Field>
     */
    public static function getMap(): array
    {
        return [
            (new Fields\IntegerField(self::FIELD_ID))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new Fields\StringField(self::FIELD_NAME))
                ->configureRequired()
                ->configureSize(255)
                ->configureUnique(),
            (new Fields\IntegerField(self::FIELD_SORT))
                ->configureDefaultValue(500),
            (new Fields\TextField(self::FIELD_STRATEGIES))
                ->configureDefaultValue(''),
        ];
    }
}
