<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;

class sholokhov_featureflag extends CModule
{
    var $MODULE_ID = 'sholokhov.featureflag';

    private const PHP_VERSION = '8.5.0';

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
            $this->InstallDB();
        } catch (Throwable $exception) {
            $APPLICATION->ThrowException($exception->getMessage());
            return false;
        }

        return true;
    }

    public function DoUninstall(): void
    {
        $this->unRegistrationEvents();
        $this->Remove();
    }

    public function InstallDB(): void
    {
        $this->registrationEvents();
        $this->Add();
    }

    private function checkPhpVersion(): void
    {
        if (version_compare(phpversion(), self::PHP_VERSION) == -1) {
            throw new Exception(
                Loc::getMessage("SHOLOKHOV_FEATUREFLAG_INVALID_PHP", ['#VERSION#' => self::PHP_VERSION])
            );
        }
    }

    private function registrationEvents(): void
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandlerCompatible("main", "OnBeforeProlog", $this->MODULE_ID);
    }

    private function unRegistrationEvents(): void
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler("main", "OnBeforeProlog", $this->MODULE_ID);
    }
}