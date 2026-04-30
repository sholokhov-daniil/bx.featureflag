<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!$USER->isAdmin()) {
    return false;
}

return [
    'parent_menu' => 'global_menu_services',
    'section' => 'sholokhov_featureflag',
    'sort' => 1200,
    'text' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MENU_TITLE'),
    'title' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MENU_TITLE'),
    'icon' => 'sys_menu_icon',
    'page_icon' => 'sys_menu_icon',
    'items_id' => 'menu_sholokhov_featureflag',
    'items' => [
        [
            'text' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MENU_FLAGS'),
            'url' => 'sholokhov_featureflag_list.php?lang=' . LANGUAGE_ID,
            'more_url' => ['sholokhov_featureflag_list.php'],
            'title' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MENU_FLAGS'),
        ],
        [
            'text' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MENU_TAGS'),
            'url' => 'sholokhov_featureflag_tags.php?lang=' . LANGUAGE_ID,
            'more_url' => ['sholokhov_featureflag_tags.php'],
            'title' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MENU_TAGS'),
        ],
    ],
];
