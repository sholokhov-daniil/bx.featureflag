<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Sholokhov\Featureflag\DTO\FlagInfo;
use Sholokhov\Featureflag\Feature;
use Sholokhov\Featureflag\ORM\FeatureTable;

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

$request = Application::getInstance()->getContext()->getRequest();
$errors = [];
$normalizeEnabledToYN = static function (mixed $value): string {
    if ($value === true || $value === 1 || $value === '1' || $value === 'Y') {
        return 'Y';
    }

    return 'N';
};
$buildUserTitle = static function (array $user): string {
    $fio = trim(implode(' ', array_filter([
        (string)($user['LAST_NAME'] ?? ''),
        (string)($user['NAME'] ?? ''),
        (string)($user['SECOND_NAME'] ?? ''),
    ])));

    if ($fio !== '') {
        return $fio;
    }

    $login = trim((string)($user['LOGIN'] ?? ''));
    if ($login !== '') {
        return $login;
    }

    return (string)($user['ID'] ?? '');
};

$messageCodes = [
    'added' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MSG_ADDED'),
    'updated' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MSG_UPDATED'),
    'deleted' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MSG_DELETED'),
    'status_updated' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MSG_STATUS_UPDATED'),
];

$mode = (string)$request->getQuery('mode');
if ($mode !== 'add' && $mode !== 'edit') {
    $mode = '';
}

$currentCode = trim((string)$request->getQuery('code'));

$formData = [
    'CODE' => '',
    'NAME' => '',
    'DESCRIPTION' => '',
    'ENABLED' => 'N',
];
$detailMeta = [
    'createdById' => 0,
    'createdByName' => '',
    'createdByUrl' => '',
    'createdAt' => '',
    'updatedAt' => '',
];

if ($mode === 'edit') {
    if ($currentCode === '') {
        $errors[] = Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_EMPTY_CODE');
        $mode = '';
    } else {
        $feature = FeatureTable::getByPrimary($currentCode)->fetch();
        if (!$feature) {
            $errors[] = Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_NOT_FOUND');
            $mode = '';
        } else {
            $formData = [
                'CODE' => (string)$feature['CODE'],
                'NAME' => (string)$feature['NAME'],
                'DESCRIPTION' => (string)$feature['DESCRIPTION'],
                'ENABLED' => $normalizeEnabledToYN($feature['ENABLED']),
            ];

            $detailMeta['createdById'] = (int)($feature[FeatureTable::FIELD_CREATED_BY] ?? 0);
            $detailMeta['createdAt'] = (string)($feature[FeatureTable::FIELD_DATE_CREATE] ?? '');
            $detailMeta['updatedAt'] = (string)($feature[FeatureTable::FIELD_DATE_UPDATE] ?? '');

            if ($detailMeta['createdById'] > 0) {
                $creator = UserTable::getByPrimary(
                    $detailMeta['createdById'],
                    ['select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN']]
                )->fetch();

                if ($creator) {
                    $detailMeta['createdByName'] = $buildUserTitle($creator);
                }

                $detailMeta['createdByUrl'] = '/bitrix/admin/user_edit.php?lang='
                    . rawurlencode(LANGUAGE_ID)
                    . '&ID='
                    . $detailMeta['createdById'];
            }
        }
    }
}

if (
    $request->isPost()
    && check_bitrix_sessid()
) {
    $action = (string)$request->getPost('action');

    if ($action === 'add' || $action === 'update') {
        $postedCode = trim((string)$request->getPost('CODE'));
        $postedName = trim((string)$request->getPost('NAME'));
        $postedDescription = trim((string)$request->getPost('DESCRIPTION'));
        $postedEnabled = $request->getPost('ENABLED') === 'Y' ? 'Y' : 'N';

        $formData = [
            'CODE' => $postedCode,
            'NAME' => $postedName,
            'DESCRIPTION' => $postedDescription,
            'ENABLED' => $postedEnabled,
        ];

        if ($postedCode === '') {
            $errors[] = Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_EMPTY_CODE');
        } elseif (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $postedCode)) {
            $errors[] = Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_INVALID_CODE');
        }

        if ($postedName === '') {
            $errors[] = Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_EMPTY_NAME');
        }

        if (!$errors) {
            if ($action === 'add') {
                $result = Feature::register(new FlagInfo(
                    code: $postedCode,
                    name: $postedName,
                    description: $postedDescription,
                    enabled: $postedEnabled === 'Y',
                ));
            } else {
                $result = FeatureTable::update($postedCode, [
                    FeatureTable::FIELD_NAME => $postedName,
                    FeatureTable::FIELD_DESCRIPTION => $postedDescription,
                    FeatureTable::FIELD_ENABLED => $postedEnabled === 'Y',
                ]);
            }

            if ($result->isSuccess()) {
                LocalRedirect($APPLICATION->GetCurPageParam(
                    'lang=' . LANGUAGE_ID . '&msg=' . ($action === 'add' ? 'added' : 'updated'),
                    ['msg', 'mode', 'code']
                ));
            }

            $errors = array_merge($errors, $result->getErrorMessages());
        }
    }

    if ($action === 'delete') {
        $code = trim((string)$request->getPost('code'));

        if ($code === '') {
            $errors[] = Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_EMPTY_CODE');
        } else {
            $result = Feature::unRegister($code);

            if ($result->isSuccess()) {
                LocalRedirect($APPLICATION->GetCurPageParam(
                    'lang=' . LANGUAGE_ID . '&msg=deleted',
                    ['msg', 'mode', 'code']
                ));
            }

            $errors = array_merge($errors, $result->getErrorMessages());
        }
    }

    if ($action === 'toggle') {
        $code = trim((string)$request->getPost('code'));
        $enabled = $request->getPost('enabled') === 'Y';

        if ($code === '') {
            $errors[] = Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ERR_EMPTY_CODE');
        } else {
            $result = $enabled ? Feature::enabled($code) : Feature::disabled($code);

            if ($result->isSuccess()) {
                LocalRedirect($APPLICATION->GetCurPageParam(
                    'lang=' . LANGUAGE_ID . '&msg=status_updated',
                    ['msg']
                ));
            }

            $errors = array_merge($errors, $result->getErrorMessages());
        }
    }

}

if ($errors) {
    CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MSG_ERROR'),
        'DETAILS' => implode('<br>', array_map('htmlspecialcharsbx', $errors)),
        'HTML' => true,
    ]);
}

$messageCode = (string)$request->getQuery('msg');
if (isset($messageCodes[$messageCode])) {
    CAdminMessage::ShowMessage([
        'TYPE' => 'OK',
        'MESSAGE' => $messageCodes[$messageCode],
    ]);
}

$listPageUrl = $APPLICATION->GetCurPageParam(
    'lang=' . LANGUAGE_ID,
    ['mode', 'code', 'msg']
);

$context = [];
if ($mode === '') {
    $context[] = [
        'TEXT' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_ADD'),
        'LINK' => $APPLICATION->GetCurPageParam(
            'lang=' . LANGUAGE_ID . '&mode=add',
            ['mode', 'code', 'msg']
        ),
        'ICON' => 'btn_new',
    ];
} else {
    $context[] = [
        'TEXT' => Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_BACK'),
        'LINK' => $listPageUrl,
        'ICON' => 'btn_list',
    ];
}

$contextMenu = new CAdminContextMenu($context);
$contextMenu->Show();

?>
<style>
    .ff-admin {
        margin-top: 14px;
    }
    .ff-panel {
        background: #ffffff;
        border: 1px solid #dce3ed;
        border-radius: 10px;
        box-shadow: 0 10px 28px rgba(30, 41, 59, 0.07);
        overflow: hidden;
    }
    .ff-panel-detail {
        max-width: none;
        width: 100%;
        margin: 0;
    }
    .ff-panel-head {
        padding: 14px 18px;
        border-bottom: 1px solid #e8edf4;
        background: linear-gradient(180deg, #fbfdff 0%, #f6f9ff 100%);
    }
    .ff-panel-title {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: #172033;
    }
    .ff-panel-subtitle {
        margin: 4px 0 0;
        font-size: 12px;
        color: #5d6a80;
    }
    .ff-panel-body {
        padding: 14px 18px 18px;
    }
    .ff-detail-grid {
        display: grid;
        grid-template-columns: minmax(140px, 220px) 1fr;
        gap: 12px 18px;
        align-items: center;
    }
    .ff-field-label {
        font-size: 12px;
        color: #5d6a80;
    }
    .ff-field-control {
        min-width: 0;
    }
    .ff-detail-grid .ff-input,
    .ff-detail-grid .ff-textarea {
        width: 100%;
        max-width: 760px;
        border: 1px solid #c7d4e8;
        border-radius: 10px;
        background: #f8fbff;
        color: #1e2a41;
        font-size: 13px;
        line-height: 1.45;
        padding: 9px 12px;
        box-sizing: border-box;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
    }
    .ff-detail-grid input.ff-input[type="text"] {
        appearance: none;
        -webkit-appearance: none;
        height: 40px;
        min-height: 40px;
        font-weight: 500;
        letter-spacing: 0;
    }
    .ff-detail-grid textarea.ff-textarea {
        appearance: none;
        -webkit-appearance: none;
        font-family: inherit;
    }
    .ff-detail-grid .ff-input:hover,
    .ff-detail-grid .ff-textarea:hover {
        background: #ffffff;
        border-color: #b9c9e1;
    }
    .ff-detail-grid .ff-input:focus,
    .ff-detail-grid .ff-textarea:focus {
        outline: none;
        background: #ffffff;
        border-color: #4f83ff;
        box-shadow: 0 0 0 3px rgba(79, 131, 255, 0.16);
    }
    .ff-textarea {
        min-height: 120px;
        resize: vertical;
    }
    .ff-readonly {
        font-size: 13px;
        color: #1e2a41;
        min-height: 20px;
    }
    .ff-user-link {
        color: #2b5fd9;
        text-decoration: none;
        font-weight: 600;
    }
    .ff-user-link:hover {
        text-decoration: underline;
    }
    .ff-form-actions {
        margin-top: 14px;
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .ff-btn {
        display: inline-block;
        border: 1px solid #cfd8e6;
        background: #ffffff;
        color: #22314d;
        border-radius: 8px;
        font-size: 13px;
        line-height: 18px;
        padding: 7px 11px;
        text-decoration: none;
        cursor: pointer;
    }
    .ff-btn:hover {
        border-color: #b5c4d8;
        background: #f8fbff;
    }
    .ff-btn-primary {
        border-color: #2463eb;
        background: #2463eb;
        color: #ffffff;
    }
    .ff-btn-primary:hover {
        border-color: #1f58d1;
        background: #1f58d1;
    }
    .ff-btn-danger {
        border-color: #e4c4c4;
        background: #fff6f6;
        color: #9f2a2a;
    }
    .ff-btn-danger:hover {
        border-color: #deabab;
        background: #ffefef;
    }
    .ff-table-wrap {
        overflow-x: auto;
    }
    .ff-table {
        width: 100%;
        border-collapse: collapse;
    }
    .ff-table thead th {
        text-align: left;
        padding: 10px 12px;
        background: #f8faff;
        border-bottom: 1px solid #e3eaf4;
        font-size: 12px;
        color: #5b6780;
        font-weight: 600;
        white-space: nowrap;
    }
    .ff-table tbody td {
        padding: 11px 12px;
        border-bottom: 1px solid #edf2f8;
        vertical-align: middle;
        font-size: 13px;
        color: #1e2a41;
    }
    .ff-table tbody tr:hover td {
        background: #fbfcff;
    }
    .ff-code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 12px;
        color: #22314d;
    }
    .ff-badge {
        display: inline-block;
        padding: 2px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        line-height: 17px;
        border: 1px solid transparent;
        white-space: nowrap;
    }
    .ff-badge-on {
        background: #e9f9ef;
        border-color: #b5e7c5;
        color: #117342;
    }
    .ff-badge-off {
        background: #f4f6fb;
        border-color: #d8e0ec;
        color: #59667c;
    }
    .ff-switch-form {
        margin: 0;
    }
    .ff-switch {
        position: relative;
        display: inline-block;
        width: 38px;
        height: 22px;
        vertical-align: middle;
    }
    .ff-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .ff-switch-slider {
        position: absolute;
        inset: 0;
        cursor: pointer;
        background: #d8e0ec;
        transition: .2s;
        border-radius: 999px;
    }
    .ff-switch-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 3px;
        top: 3px;
        background: #ffffff;
        border-radius: 50%;
        transition: .2s;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
    }
    .ff-switch input:checked + .ff-switch-slider {
        background: #2f7cff;
    }
    .ff-switch input:checked + .ff-switch-slider:before {
        transform: translateX(16px);
    }
    .ff-state-label {
        display: inline-block;
        margin-left: 8px;
        font-size: 12px;
        color: #5d6a80;
        vertical-align: middle;
    }
    .ff-user a {
        color: #2b5fd9;
        text-decoration: none;
        font-weight: 600;
    }
    .ff-feature-link {
        color: #2b5fd9;
        text-decoration: none;
        font-weight: 600;
    }
    .ff-feature-link:hover {
        text-decoration: underline;
    }
    .ff-feature-code {
        margin-top: 2px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 11px;
        color: #6c7890;
    }
    .ff-feature-title {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .ff-help-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 1px solid #c9d4e4;
        color: #5f6f89;
        font-size: 11px;
        font-weight: 700;
        cursor: help;
        user-select: none;
        background: #f8fbff;
        position: relative;
    }
    .ff-help-icon::before {
        content: attr(data-tooltip);
        position: absolute;
        left: 50%;
        bottom: calc(100% + 10px);
        transform: translateX(-50%) translateY(4px);
        min-width: 180px;
        max-width: 340px;
        padding: 8px 10px;
        border-radius: 8px;
        background: #1f2738;
        color: #f2f6ff;
        font-size: 12px;
        font-weight: 400;
        line-height: 1.35;
        white-space: normal;
        box-shadow: 0 10px 24px rgba(16, 23, 35, 0.28);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        z-index: 20;
        transition: opacity .15s ease, transform .15s ease, visibility .15s ease;
    }
    .ff-help-icon::after {
        content: "";
        position: absolute;
        left: 50%;
        bottom: calc(100% + 4px);
        transform: translateX(-50%) translateY(4px);
        border-width: 6px;
        border-style: solid;
        border-color: #1f2738 transparent transparent transparent;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        z-index: 19;
        transition: opacity .15s ease, transform .15s ease, visibility .15s ease;
    }
    .ff-help-icon:hover::before,
    .ff-help-icon:focus-visible::before,
    .ff-help-icon:hover::after,
    .ff-help-icon:focus-visible::after {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }
    .ff-delete-inline {
        margin: 0;
    }
</style>
<div class="ff-admin">
<?php

if ($mode !== ''):
?>
    <div class="ff-panel ff-panel-detail">
        <div class="ff-panel-head">
            <h2 class="ff-panel-title">
                <?=$mode === 'add'
                    ? Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_ADD')
                    : Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_EDIT')?>
            </h2>
            <p class="ff-panel-subtitle"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_PAGE_TITLE')?></p>
        </div>
        <div class="ff-panel-body">
            <form method="post" action="<?=htmlspecialcharsbx($APPLICATION->GetCurPageParam('', ['msg']))?>">
                <?=bitrix_sessid_post()?>
                <input type="hidden" name="action" value="<?=$mode === 'add' ? 'add' : 'update'?>">
                <div class="ff-detail-grid">
                    <div class="ff-field-label"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_CODE')?>:</div>
                    <div class="ff-field-control">
                        <?php if ($mode === 'add'): ?>
                            <input
                                type="text"
                                name="CODE"
                                value="<?=htmlspecialcharsbx($formData['CODE'])?>"
                                maxlength="255"
                                class="ff-input ff-code"
                                required
                            >
                        <?php else: ?>
                            <div class="ff-readonly ff-code"><?=htmlspecialcharsbx($formData['CODE'])?></div>
                            <input type="hidden" name="CODE" value="<?=htmlspecialcharsbx($formData['CODE'])?>">
                        <?php endif; ?>
                    </div>

                    <div class="ff-field-label"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_ENABLED')?>:</div>
                    <div class="ff-field-control">
                        <input type="hidden" name="ENABLED" value="<?=$formData['ENABLED']?>">
                        <label class="ff-switch">
                            <input
                                type="checkbox"
                                <?=$formData['ENABLED'] === 'Y' ? 'checked' : ''?>
                                onchange="this.form.elements.ENABLED.value = this.checked ? 'Y' : 'N';"
                            >
                            <span class="ff-switch-slider"></span>
                        </label>
                        <span class="ff-state-label">
                            <?=$formData['ENABLED'] === 'Y'
                                ? Loc::getMessage('SHOLOKHOV_FEATUREFLAG_STATUS_ON')
                                : Loc::getMessage('SHOLOKHOV_FEATUREFLAG_STATUS_OFF')?>
                        </span>
                    </div>

                    <div class="ff-field-label"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_NAME')?>:</div>
                    <div class="ff-field-control">
                        <input
                            type="text"
                            name="NAME"
                            value="<?=htmlspecialcharsbx($formData['NAME'])?>"
                            maxlength="255"
                            class="ff-input"
                            required
                        >
                    </div>

                    <div class="ff-field-label"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_DESCRIPTION')?>:</div>
                    <div class="ff-field-control">
                        <textarea name="DESCRIPTION" class="ff-textarea"><?=htmlspecialcharsbx($formData['DESCRIPTION'])?></textarea>
                    </div>

                    <?php if ($mode === 'edit'): ?>
                        <div class="ff-field-label"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_BY')?>:</div>
                        <div class="ff-field-control ff-readonly">
                            <?php if ($detailMeta['createdById'] > 0): ?>
                                <a class="ff-user-link" href="<?=htmlspecialcharsbx($detailMeta['createdByUrl'])?>">
                                    [<?=htmlspecialcharsbx((string)$detailMeta['createdById'])?>]
                                </a>
                                <?=htmlspecialcharsbx($detailMeta['createdByName'] ?: (string)$detailMeta['createdById'])?>
                            <?php else: ?>
                                &nbsp;
                            <?php endif; ?>
                        </div>

                        <div class="ff-field-label"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_AT')?>:</div>
                        <div class="ff-field-control ff-readonly"><?=htmlspecialcharsbx($detailMeta['createdAt'])?></div>

                        <div class="ff-field-label"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_UPDATED_AT')?>:</div>
                        <div class="ff-field-control ff-readonly"><?=htmlspecialcharsbx($detailMeta['updatedAt'])?></div>
                    <?php endif; ?>
                </div>
                <div class="ff-form-actions">
                    <input type="submit" class="ff-btn ff-btn-primary" value="<?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_SAVE')?>">
                    <a href="<?=htmlspecialcharsbx($listPageUrl)?>" class="ff-btn">
                        <?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_CANCEL')?>
                    </a>
                    <?php if ($mode === 'edit'): ?>
                        <button
                            type="submit"
                            name="action"
                            value="delete"
                            class="ff-btn ff-btn-danger"
                            onclick="return confirm('<?=CUtil::JSEscape(Loc::getMessage('SHOLOKHOV_FEATUREFLAG_CONFIRM_DELETE'))?>');"
                        >
                            <?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_DELETE')?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
<?php
else:
    $rows = FeatureTable::getList([
        'select' => [
            FeatureTable::FIELD_CODE,
            FeatureTable::FIELD_NAME,
            FeatureTable::FIELD_DESCRIPTION,
            FeatureTable::FIELD_ENABLED,
            FeatureTable::FIELD_DATE_UPDATE,
            FeatureTable::FIELD_CREATED_BY,
        ],
        'order' => [FeatureTable::FIELD_CODE => 'ASC'],
    ])->fetchAll();

    $creatorIds = array_values(array_unique(array_filter(
        array_map(
            static fn(array $row): int => (int)($row[FeatureTable::FIELD_CREATED_BY] ?? 0),
            $rows
        ),
        static fn(int $id): bool => $id > 0
    )));

    $usersById = [];
    if ($creatorIds) {
        $userResult = UserTable::getList([
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'],
            'filter' => ['@ID' => $creatorIds],
        ]);

        while ($user = $userResult->fetch()) {
            $userId = (int)$user['ID'];
            $usersById[$userId] = $user;
        }
    }
?>
    <div class="ff-panel">
        <div class="ff-panel-head">
            <h2 class="ff-panel-title"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_PAGE_TITLE')?></h2>
            <p class="ff-panel-subtitle"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_PAGE_SUBTITLE')?></p>
        </div>
        <div class="ff-panel-body ff-table-wrap">
            <table class="ff-table">
                <thead>
                <tr>
                    <th><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_NAME')?></th>
                    <th><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_ENABLED')?></th>
                    <th><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_UPDATED_AT')?></th>
                    <th><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_BY')?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="4"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_EMPTY_LIST')?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php $enabledYN = $normalizeEnabledToYN($row['ENABLED']); ?>
                        <?php $creatorId = (int)($row[FeatureTable::FIELD_CREATED_BY] ?? 0); ?>
                        <?php $creator = $creatorId > 0 ? ($usersById[$creatorId] ?? null) : null; ?>
                        <?php
                        $creatorUrl = '/bitrix/admin/user_edit.php?lang=' . rawurlencode(LANGUAGE_ID) . '&ID=' . $creatorId;
                        $creatorTitle = $creator ? $buildUserTitle($creator) : (string)$creatorId;
                        $detailsUrl = $APPLICATION->GetCurPageParam(
                            'lang=' . LANGUAGE_ID . '&mode=edit&code=' . rawurlencode((string)$row['CODE']),
                            ['mode', 'code', 'msg']
                        );
                        ?>
                        <tr>
                            <td>
                                <span class="ff-feature-title">
                                    <a class="ff-feature-link" href="<?=htmlspecialcharsbx($detailsUrl)?>">
                                        <?=htmlspecialcharsbx((string)$row['NAME'])?>
                                    </a>
                                    <?php if (trim((string)$row['DESCRIPTION']) !== ''): ?>
                                        <span class="ff-help-icon" data-tooltip="<?=htmlspecialcharsbx((string)$row['DESCRIPTION'])?>" tabindex="0">?</span>
                                    <?php endif; ?>
                                </span>
                                <div class="ff-feature-code"><?=htmlspecialcharsbx((string)$row['CODE'])?></div>
                            </td>
                            <td>
                                <form method="post" class="ff-switch-form">
                                    <?=bitrix_sessid_post()?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="code" value="<?=htmlspecialcharsbx((string)$row['CODE'])?>">
                                    <input type="hidden" name="enabled" value="<?=$enabledYN?>">
                                    <label class="ff-switch">
                                        <input
                                            type="checkbox"
                                            <?=$enabledYN === 'Y' ? 'checked' : ''?>
                                            onchange="this.form.elements.enabled.value = this.checked ? 'Y' : 'N'; this.form.submit();"
                                        >
                                        <span class="ff-switch-slider"></span>
                                    </label>
                                    <span class="ff-state-label">
                                        <?=$enabledYN === 'Y'
                                            ? Loc::getMessage('SHOLOKHOV_FEATUREFLAG_STATUS_ON')
                                            : Loc::getMessage('SHOLOKHOV_FEATUREFLAG_STATUS_OFF')?>
                                    </span>
                                </form>
                            </td>
                            <td><?=htmlspecialcharsbx((string)$row['DATE_UPDATE'])?></td>
                            <td class="ff-user">
                                <?php if ($creatorId > 0): ?>
                                    <a href="<?=htmlspecialcharsbx($creatorUrl)?>">[<?=htmlspecialcharsbx((string)$creatorId)?>]</a>
                                    <?=htmlspecialcharsbx($creatorTitle)?>
                                <?php else: ?>
                                    &nbsp;
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
endif;
?>
</div>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
