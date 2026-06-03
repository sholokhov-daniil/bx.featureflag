<?php

use Bitrix\Main\Localization\Loc;
use Sholokhov\Featureflag\ServiceProvider;

global $APPLICATION;

$module_id = 'sholokhov.featureflag';
if (!ServiceProvider::getModulePermission()->canRead()) {
    ShowError(Loc::getMessage('SHOLOKHOV_FEATUREFLAG_ACCESS_DENIED'));
    return;
}

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/options.php');
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/admin/settings.php');
IncludeModuleLangFile(__FILE__);

$aTabs = [
    [
        "DIV" => "edit1",
        "TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"),
        "ICON" => "seo_settings",
        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS")
    ]
];

$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>
<form method="POST" action="<?= $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?= LANG?>" name="sholokhov.featureflag_settings">
<?=bitrix_sessid_post();?>
<?php
$tabControl->BeginNextTab();
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
$tabControl->Buttons();
?>
    <script>
        function confirmRestoreDefaults()
        {
            return confirm('<?= AddSlashes(Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>');
        }
    </script>
    <input type="submit" name="Update" value="<?= Loc::getMessage("MAIN_SAVE")?>">
    <input type="hidden" name="Update" value="Y">
    <input type="reset" name="reset" value="<?= Loc::getMessage("MAIN_RESET")?>">
    <input type="submit" name="RestoreDefaults" title="<?= Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="return confirmRestoreDefaults();" value="<?= Loc::getMessage("MAIN_RESTORE_DEFAULTS")?>">
<?php
$tabControl->End();
?>
</form>
