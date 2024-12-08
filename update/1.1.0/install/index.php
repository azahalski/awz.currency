<?php
use \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\EventManager,
    \Bitrix\Main\ModuleManager,
    \Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

class awz_currency extends CModule {

    var $MODULE_ID = "awz.currency";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = "Y";

    var $errors = false;

    public function __construct()
    {
        $arModuleVersion = array();
        include(__DIR__.'/version.php');

        $dirs = explode('/',dirname(__DIR__ . '../'));
        $this->MODULE_ID = array_pop($dirs);
        unset($dirs);

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = Loc::getMessage("AWZ_CURRENCY_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("AWZ_CURRENCY_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage("AWZ_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("AWZ_PARTNER_URI");
    }

    function InstallDB()
    {
        global $DB, $DBType, $APPLICATION;
        $this->errors = false;
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/". $this->MODULE_ID ."/install/db/".mb_strtolower($DB->type)."/install.sql";
        if(!file_exists($filePath)) return true;
        $this->errors = $DB->RunSQLBatch($filePath);

        if (!$this->errors) {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/". $this->MODULE_ID ."/install/db/" . mb_strtolower($DB->type) . "/access.sql";
            if (file_exists($filePath)) {
                $this->errors = $DB->RunSQLBatch($filePath);
            }
        }

        if (!$this->errors) {
            return true;
        } else {
            $APPLICATION->ThrowException(implode("", $this->errors));
            return $this->errors;
        }
        return true;
    }


    function UnInstallDB()
    {
        global $DB, $DBType, $APPLICATION;

        $this->errors = false;
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/". $this->MODULE_ID ."/install/db/".mb_strtolower($DB->type)."/uninstall.sql";
        if(!file_exists($filePath)) return true;
        $this->errors = $DB->RunSQLBatch($filePath);

        if (!$this->errors) {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/". $this->MODULE_ID ."/install/db/" . mb_strtolower($DB->type) . "/unaccess.sql";
            if (file_exists($filePath)) {
                $this->errors = $DB->RunSQLBatch($filePath);
            }
        }

        if (!$this->errors) {
            return true;
        }
        else {
            $APPLICATION->ThrowException(implode("", $this->errors));
            return $this->errors;
        }
    }


    function InstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandlerCompatible(
            'main', 'OnAfterUserUpdate',
            $this->MODULE_ID, '\\Awz\\Currency\\Access\\Handlers', 'OnAfterUserUpdate'
        );
        $eventManager->registerEventHandlerCompatible(
            'main', 'OnAfterUserAdd',
            $this->MODULE_ID, '\\Awz\\Currency\\Access\\Handlers', 'OnAfterUserUpdate'
        );
        return true;
    }

    function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'sale', 'OnAfterUserUpdate',
            'awz.currency', '\\Awz\\Currency\\Access\\Handlers', 'OnAfterUserUpdate'
        );
        $eventManager->unRegisterEventHandler(
            'sale', 'OnAfterUserAdd',
            'awz.currency', '\\Awz\\Currency\\Access\\Handlers', 'OnAfterUserUpdate'
        );
        return true;
        return true;
    }

    function InstallFiles()
    {
        CopyDirFiles(
            $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/components/awz/currency.config.permissions/",
            $_SERVER['DOCUMENT_ROOT']."/bitrix/components/awz/admin.config.permissions",
            true, true
        );
        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/components/awz/currency.config.permissions");
        return true;
    }

    function DoInstall()
    {
        global $APPLICATION, $step;

        $this->InstallFiles();
        $this->InstallDB();
		$this->checkOldInstallTables();
        $this->InstallEvents();
        $this->createAgents();

        ModuleManager::RegisterModule($this->MODULE_ID);
        $filePath = dirname(__DIR__ . '/../../options.php');
        if(file_exists($filePath)){
            LocalRedirect('/bitrix/admin/settings.php?lang='.LANG.'&mid='.$this->MODULE_ID.'&mid_menu=1');
        }
        return true;
    }

    function DoUninstall()
    {
        global $APPLICATION, $step;

        $step = intval($step);
        if($step < 2) { //выводим предупреждение
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('AWZ_CURRENCY_INSTALL_TITLE'),
                $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'. $this->MODULE_ID .'/install/unstep.php'
            );
        }
        elseif($step == 2) {
            //проверяем условие
            if($_REQUEST['save'] != 'Y' && !isset($_REQUEST['save'])) {
                $this->UnInstallDB();
            }
            $this->UnInstallFiles();
            $this->UnInstallEvents();
            $this->deleteAgents();

            ModuleManager::UnRegisterModule($this->MODULE_ID);

            return true;
        }
    }

    function createAgents() {

        CAgent::AddAgent(
            "\\Awz\\Currency\\Agents::getDayRb();",
            $this->MODULE_ID,
            "N",
            21600);
        CAgent::AddAgent(
            "\\Awz\\Currency\\Agents::getDayRf();",
            $this->MODULE_ID,
            "N",
            21600);
        CAgent::AddAgent(
            "\\Awz\\Currency\\Agents::updateBxCurs();",
            $this->MODULE_ID,
            "N",
            3600);

        return true;
    }

    function deleteAgents() {

        CAgent::RemoveAgent(
            "\\Awz\\Currency\\Agents::getDayRb();",
            $this->MODULE_ID
        );
        CAgent::RemoveAgent(
            "\\Awz\\Currency\\Agents::getDayRf();",
            $this->MODULE_ID
        );
        CAgent::RemoveAgent(
            "\\Awz\\Currency\\Agents::updateBxCurs();",
            $this->MODULE_ID
        );

        return true;
    }

	function checkOldInstallTables(){
		return true;
	}
}