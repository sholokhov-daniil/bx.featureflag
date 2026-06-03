<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Sholokhov\Featureflag\ServiceProvider;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

global $APPLICATION;
global $USER;

if (!$USER->isAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$APPLICATION->SetTitle(Loc::getMessage('SHOLOKHOV_FEATUREFLAG_PAGE_TITLE'));

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

$viewOptionsResult = ServiceProvider::getAdminFeatureFlagService()->getViewOptions();

$bootstrap = [
    'view' => 'flags',
    'langId' => LANGUAGE_ID,
    'viewOptions' => $viewOptionsResult->isSuccess() ? ($viewOptionsResult->getData()['viewOptions'] ?? []) : [],
    'actions' => [
        'list' => 'sholokhov:featureflag.FeatureFlag.list',
        'get' => 'sholokhov:featureflag.FeatureFlag.get',
        'create' => 'sholokhov:featureflag.FeatureFlag.create',
        'update' => 'sholokhov:featureflag.FeatureFlag.update',
        'delete' => 'sholokhov:featureflag.FeatureFlag.delete',
        'toggle' => 'sholokhov:featureflag.FeatureFlag.toggle',
        'tagList' => 'sholokhov:featureflag.FeatureFlag.tagList',
        'tagCreate' => 'sholokhov:featureflag.FeatureFlag.tagCreate',
        'tagUpdate' => 'sholokhov:featureflag.FeatureFlag.tagUpdate',
        'tagDelete' => 'sholokhov:featureflag.FeatureFlag.tagDelete',
        'strategyList' => 'sholokhov:featureflag.FeatureFlag.strategyList',
        'saveViewOptions' => 'sholokhov:featureflag.FeatureFlag.saveViewOptions',
    ],
];
?>
<div id="sholokhov-featureflag-admin-app"></div>
<script>
    window.SholokhovFeatureFlagAdmin = <?= Json::encode($bootstrap, JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
