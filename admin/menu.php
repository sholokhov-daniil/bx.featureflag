<?php

use Bitrix\Main\Localization\Loc;
use Sholokhov\Featureflag\ServiceProvider;

Loc::loadMessages(__FILE__);

if (!ServiceProvider::getModulePermission()->canRead()) {
    return false;
}
?>
<style>
    .sholokhov-featureflag-menu-icon,
    .sholokhov-featureflag-page-icon
    {
        position: relative;
        background: url('/bitrix/images/sholokhov.featureflag/feature-flag.png') center center no-repeat !important;
    }
</style>
<?php
return [
    'parent_menu' => 'global_menu_services',
    'section' => 'sholokhov_featureflag',
    'sort' => 1200,
    'text' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MENU_TITLE'),
    'title' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MENU_TITLE'),
    'icon' => 'sholokhov-featureflag-menu-icon',
    'page_icon' => 'sholokhov-featureflag-page-icon',
    'items_id' => 'menu_sholokhov_featureflag',
    'items' => [
        [
            'text' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MENU_FLAGS'),
            'url' => 'sholokhov_featureflag_list.php?lang=' . LANGUAGE_ID,
            'more_url' => ['sholokhov_featureflag_list.php'],
            'title' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MENU_FLAGS'),
        ],
    ],
];
