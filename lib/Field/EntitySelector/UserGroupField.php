<?php

namespace Sholokhov\Featureflag\Field\EntitySelector;

use Sholokhov\Featureflag\Field\EntitySelector\Entities\UserGroupEntity;

/**
 * Свойство привязки к группам пользователей.
 */
class UserGroupField extends AbstractEntitySelectorField
{
    protected function configuration(): void
    {
        parent::configuration();
        $this->setPlaceholder('Введите название группы');
        $this->setAddButtonCaption('Добавить группу');
        $this->setAddButtonCaptionMore('Добавить еще');
        $this->setMultiple(true);
        $this->setDialogOptions(
            (new DialogOptions)
                ->setContext('sholokhov.featureflag.user.group')
                ->addEntity(new UserGroupEntity)
        );
    }
}
