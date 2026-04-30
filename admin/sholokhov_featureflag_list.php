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

$bootstrap = [
    'langId' => LANGUAGE_ID,
    'actions' => [
        'list' => 'sholokhov:featureflag.FeatureFlag.list',
        'get' => 'sholokhov:featureflag.FeatureFlag.get',
        'create' => 'sholokhov:featureflag.FeatureFlag.create',
        'update' => 'sholokhov:featureflag.FeatureFlag.update',
        'delete' => 'sholokhov:featureflag.FeatureFlag.delete',
        'toggle' => 'sholokhov:featureflag.FeatureFlag.toggle',
    ],
    'messages' => [
        'subtitle' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_PAGE_SUBTITLE'),
        'add' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_ADD'),
        'createTitle' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_POPUP_CREATE_TITLE'),
        'editTitle' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_POPUP_EDIT_TITLE'),
        'save' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_SAVE'),
        'cancel' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_CANCEL'),
        'delete' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_DELETE'),
        'loading' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_LOADING'),
        'empty' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_EMPTY_LIST'),
        'name' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_NAME'),
        'code' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_CODE'),
        'description' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_DESCRIPTION'),
        'enabled' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_ENABLED'),
        'createdBy' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_BY'),
        'createdAt' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_AT'),
        'updatedAt' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_UPDATED_AT'),
        'statusOn' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_STATUS_ON'),
        'statusOff' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_STATUS_OFF'),
        'deleteConfirm' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_CONFIRM_DELETE'),
        'createdSuccess' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MSG_ADDED'),
        'updatedSuccess' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MSG_UPDATED'),
        'deletedSuccess' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MSG_DELETED'),
        'toggleSuccess' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MSG_STATUS_UPDATED'),
        'genericError' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MSG_ERROR'),
        'loadError' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_LOAD_ERROR'),
        'newFlagLabel' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_NEW_FLAG_LABEL'),
        'hintLabel' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_HINT_LABEL'),
        'closeLabel' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_CLOSE_LABEL'),
        'openDetail' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_OPEN_DETAIL'),
        'panelTitle' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_PANEL_TITLE'),
        'panelCaption' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_PANEL_CAPTION'),
        'totalLabel' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_TOTAL_LABEL'),
        'descriptionPlaceholder' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_DESCRIPTION_PLACEHOLDER'),
        'namePlaceholder' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_NAME_PLACEHOLDER'),
        'codePlaceholder' => (string)Loc::getMessage('SHOLOKHOV_FEATUREFLAG_CODE_PLACEHOLDER'),
    ],
];
?>
<div id="sholokhov-featureflag-admin-app"></div>
<script>
    window.SholokhovFeatureFlagAdmin = <?= Json::encode($bootstrap, JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
