<?php

use Sholokhov\Featureflag\ORM\FeatureTable;
use Sholokhov\Featureflag\ORM\FeatureTagTable;

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;

class sholokhov_featureflag extends CModule
{
    var $MODULE_ID = 'sholokhov.featureflag';

    private const PHP_VERSION = '8.4.0';

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ .  DIRECTORY_SEPARATOR . "version.php");
        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        } else {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->PARTNER_NAME = Loc::getMessage('SHOLOKHOV_FEATUREFLAG_PARTNER_NAME');
        $this->PARTNER_URI = 'https://github.com/sholokhov-daniil';
        $this->MODULE_NAME = Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('SHOLOKHOV_FEATUREFLAG_MODULE_DESCRIPTION');
    }

    public function DoInstall(): bool
    {
        global $APPLICATION;

        try {
            $this->checkPhpVersion();
            $this->Add();
            self::IncludeModule($this->MODULE_ID);
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();
        } catch (Throwable $exception) {
            $APPLICATION->ThrowException($exception->getMessage());
            return false;
        }

        return true;
    }

    public function DoUninstall(): void
    {
        self::IncludeModule($this->MODULE_ID);
        $this->UnInstallEvents();
        $this->UnInstallDB();
        $this->UnInstallFiles();
        $this->Remove();
    }

    public function InstallEvents(): void
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandlerCompatible("main", "OnBeforeProlog", $this->MODULE_ID);
    }

    public function UnInstallEvents(): void
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler("main", "OnBeforeProlog", $this->MODULE_ID);
    }

    public function InstallDB(): void
    {
        FeatureTagTable::getEntity()->createDbTable();
        FeatureTable::getEntity()->createDbTable();
    }

    public function UnInstallDB(): void
    {
        $connection = FeatureTable::getEntity()->getConnection();

        $featureTable = FeatureTable::getTableName();
        if ($connection->isTableExists($featureTable)) {
            $connection->dropTable($featureTable);
        }

        $tagTable = FeatureTagTable::getTableName();
        if ($connection->isTableExists($tagTable)) {
            $connection->dropTable($tagTable);
        }
    }

    public function InstallFiles(): void
    {
        CopyDirFiles(
            $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $this->MODULE_ID . '/install/images/sholokhov.featureflag',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/images/sholokhov.featureflag',
            true,
            true
        );

        CopyDirFiles(
            $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $this->MODULE_ID . '/install/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin',
            true,
            true
        );

        CopyDirFiles(
            $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $this->MODULE_ID . '/install/js',
            $_SERVER['DOCUMENT_ROOT'] . '/local/js',
            true,
            true
        );
    }

    public function UnInstallFiles(): void
    {
        DeleteDirFiles(
            $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $this->MODULE_ID . '/install/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin'
        );

        DeleteDirFilesEx('/local/js/sholokhov/featureflag');
    }

    private function checkPhpVersion(): void
    {
        if (version_compare(phpversion(), self::PHP_VERSION) == -1) {
            throw new Exception(
                Loc::getMessage("SHOLOKHOV_FEATUREFLAG_INVALID_PHP", ['#VERSION#' => self::PHP_VERSION])
            );
        }
    }
}
