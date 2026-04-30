<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

global $APPLICATION;
global $USER;

if (!$USER->isAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$APPLICATION->SetTitle(Loc::getMessage('SHOLOKHOV_FEATUREFLAG_TAGS_PAGE_TITLE'));

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

if (!Loader::includeModule('sholokhov.featureflag')) {
    CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MODULE_NOT_INSTALLED'),
    ]);

    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
    return;
}

Extension::load('sholokhov.featureflag-admin');

$bootstrap = [
    'view' => 'tags',
    'langId' => LANGUAGE_ID,
    'urls' => [
        'flagsPage' => '/bitrix/admin/sholokhov_featureflag_list.php?lang=' . rawurlencode(LANGUAGE_ID),
    ],
    'actions' => [
        'tagList' => 'sholokhov:featureflag.FeatureFlag.tagList',
        'tagCreate' => 'sholokhov:featureflag.FeatureFlag.tagCreate',
        'tagUpdate' => 'sholokhov:featureflag.FeatureFlag.tagUpdate',
        'tagDelete' => 'sholokhov:featureflag.FeatureFlag.tagDelete',
    ],
];
?>
<div id="sholokhov-featureflag-admin-app"></div>
<script>
    window.SholokhovFeatureFlagAdmin = <?= Json::encode($bootstrap, JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
