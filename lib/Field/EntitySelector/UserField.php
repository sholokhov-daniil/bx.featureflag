<?php

namespace Sholokhov\Featureflag\Field\EntitySelector;

use Sholokhov\Featureflag\Field\EntitySelector\Entities\UserEntity;

/**
 * Свойство привязки к сущности "пользователь"
 */
class UserField extends AbstractEntitySelectorField
{
    protected function configuration(): void
    {
        parent::configuration();
        $this->setPlaceholder('Введите ФИО пользователя');
        $this->setAddButtonCaption('Добавить пользователя');
        $this->setAddButtonCaptionMore('Добавить еще');
        $this->setMultiple(true);
        $this->setDialogOptions(
            (new DialogOptions)
                ->setContext('sholokhov.featureflag.user')
                ->addEntity(new UserEntity)
        );
    }
}
