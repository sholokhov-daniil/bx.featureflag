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
                    ['msg', 'mode', 'code']
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

if ($mode !== ''):
?>
    <form method="post" action="<?=htmlspecialcharsbx($APPLICATION->GetCurPageParam('', ['msg']))?>">
        <?=bitrix_sessid_post()?>
        <input type="hidden" name="action" value="<?=$mode === 'add' ? 'add' : 'update'?>">
        <table class="adm-detail-content-table edit-table">
            <tr>
                <td width="40%" class="adm-detail-content-cell-l"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_CODE')?>:</td>
                <td class="adm-detail-content-cell-r">
                    <?php if ($mode === 'add'): ?>
                        <input
                            type="text"
                            name="CODE"
                            value="<?=htmlspecialcharsbx($formData['CODE'])?>"
                            size="40"
                            maxlength="255"
                            required
                        >
                    <?php else: ?>
                        <input
                            type="text"
                            value="<?=htmlspecialcharsbx($formData['CODE'])?>"
                            size="40"
                            disabled
                        >
                        <input
                            type="hidden"
                            name="CODE"
                            value="<?=htmlspecialcharsbx($formData['CODE'])?>"
                        >
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_NAME')?>:</td>
                <td class="adm-detail-content-cell-r">
                    <input
                        type="text"
                        name="NAME"
                        value="<?=htmlspecialcharsbx($formData['NAME'])?>"
                        size="60"
                        maxlength="255"
                        required
                    >
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_DESCRIPTION')?>:</td>
                <td class="adm-detail-content-cell-r">
                    <textarea name="DESCRIPTION" rows="6" cols="80"><?=htmlspecialcharsbx($formData['DESCRIPTION'])?></textarea>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_ENABLED')?>:</td>
                <td class="adm-detail-content-cell-r">
                    <input
                        type="checkbox"
                        name="ENABLED"
                        value="Y"
                        <?=$formData['ENABLED'] === 'Y' ? 'checked' : ''?>
                    >
                </td>
            </tr>
        </table>
        <input type="submit" class="adm-btn-save" value="<?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_SAVE')?>">
        <a href="<?=htmlspecialcharsbx($listPageUrl)?>" class="adm-btn">
            <?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_CANCEL')?>
        </a>
    </form>
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
    <table class="adm-list-table">
        <thead>
        <tr class="adm-list-table-header">
            <td class="adm-list-table-cell"><div class="adm-list-table-cell-inner"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_CODE')?></div></td>
            <td class="adm-list-table-cell"><div class="adm-list-table-cell-inner"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_NAME')?></div></td>
            <td class="adm-list-table-cell"><div class="adm-list-table-cell-inner"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_DESCRIPTION')?></div></td>
            <td class="adm-list-table-cell"><div class="adm-list-table-cell-inner"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_ENABLED')?></div></td>
            <td class="adm-list-table-cell"><div class="adm-list-table-cell-inner"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_UPDATED_AT')?></div></td>
            <td class="adm-list-table-cell"><div class="adm-list-table-cell-inner"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_CREATED_BY')?></div></td>
            <td class="adm-list-table-cell"><div class="adm-list-table-cell-inner"><?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_FIELD_ACTIONS')?></div></td>
        </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr>
                <td class="adm-list-table-cell" colspan="7">
                    <?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_EMPTY_LIST')?>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <?php $enabledYN = $normalizeEnabledToYN($row['ENABLED']); ?>
                <?php $creatorId = (int)($row[FeatureTable::FIELD_CREATED_BY] ?? 0); ?>
                <?php $creator = $creatorId > 0 ? ($usersById[$creatorId] ?? null) : null; ?>
                <?php
                $creatorUrl = '/bitrix/admin/user_edit.php?lang=' . rawurlencode(LANGUAGE_ID) . '&ID=' . $creatorId;
                $creatorTitle = $creator ? $buildUserTitle($creator) : (string)$creatorId;
                ?>
                <tr class="adm-list-table-row">
                    <td class="adm-list-table-cell"><?=htmlspecialcharsbx((string)$row['CODE'])?></td>
                    <td class="adm-list-table-cell"><?=htmlspecialcharsbx((string)$row['NAME'])?></td>
                    <td class="adm-list-table-cell"><?=htmlspecialcharsbx((string)$row['DESCRIPTION'])?></td>
                    <td class="adm-list-table-cell"><?=($enabledYN === 'Y' ? Loc::getMessage('MAIN_YES') : Loc::getMessage('MAIN_NO'))?></td>
                    <td class="adm-list-table-cell"><?=htmlspecialcharsbx((string)$row['DATE_UPDATE'])?></td>
                    <td class="adm-list-table-cell">
                        <?php if ($creatorId > 0): ?>
                            <a href="<?=htmlspecialcharsbx($creatorUrl)?>">[<?=htmlspecialcharsbx((string)$creatorId)?>]</a>
                            <?=htmlspecialcharsbx($creatorTitle)?>
                        <?php else: ?>
                            &nbsp;
                        <?php endif; ?>
                    </td>
                    <td class="adm-list-table-cell">
                        <a href="<?=htmlspecialcharsbx($APPLICATION->GetCurPageParam(
                            'lang=' . LANGUAGE_ID . '&mode=edit&code=' . rawurlencode((string)$row['CODE']),
                            ['mode', 'code', 'msg']
                        ))?>">
                            <?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_EDIT')?>
                        </a>
                        <form method="post" style="display:inline-block; margin-left:8px;">
                            <?=bitrix_sessid_post()?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="code" value="<?=htmlspecialcharsbx((string)$row['CODE'])?>">
                            <input type="hidden" name="enabled" value="<?=$enabledYN === 'Y' ? 'N' : 'Y'?>">
                            <input
                                type="submit"
                                class="adm-btn"
                                value="<?=$enabledYN === 'Y'
                                    ? Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_DISABLE')
                                    : Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_ENABLE')?>"
                            >
                        </form>
                        <form
                            method="post"
                            style="display:inline-block; margin-left:8px;"
                            onsubmit="return confirm('<?=CUtil::JSEscape(Loc::getMessage('SHOLOKHOV_FEATUREFLAG_CONFIRM_DELETE'))?>');"
                        >
                            <?=bitrix_sessid_post()?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="code" value="<?=htmlspecialcharsbx((string)$row['CODE'])?>">
                            <input type="submit" class="adm-btn" value="<?=Loc::getMessage('SHOLOKHOV_FEATUREFLAG_BTN_DELETE')?>">
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
<?php
endif;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
