<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager,
    Bitrix\Main\EventManager;

require_once $_SERVER['DOCUMENT_ROOT'].'/local/modules/ourcompany.servio/lib/event.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/local/modules/ourcompany.servio/lib/work/mysettings.php';

Loc::loadMessages(__FILE__);

class ourcompany_servio extends \cModule
{

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__."/version.php");
        $this->MODULE_ID = 'ourcompany.servio';
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("OUR_COMPANY_SERVIO_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("OUR_COMPANY_SERVIO_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage("OUR_COMPANY_SERVIO_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("OUR_COMPANY_SERVIO_PARTNER_URI");
    }

    public function InstallEvents()
    {
        EventManager::getInstance()->registerEventHandler('main','OnBeforeProlog',$this->MODULE_ID,'Ourcompany\Servio\Event','addButtonAndScripts');
    }

    public function UninstallEvents()
    {
        EventManager::getInstance()->unRegisterEventHandler('main','OnBeforeProlog',$this->MODULE_ID,'Ourcompany\Servio\Event','addButtonAndScripts');
    }

    public function InstallFiles()
    {
        $dir = implode('_',explode('.',$this->MODULE_ID));
        CopyDirFiles($this->GetPatch()."/install/assets/js/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$dir."/", true, true);
        CopyDirFiles($this->GetPatch()."/install/assets/css/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/css/".$dir."/", true, true);
    }

    public function UninstallFiles()
    {
        $dir = implode('_',explode('.',$this->MODULE_ID));
        DeleteDirFilesEx("/bitrix/js/".$dir);
        DeleteDirFilesEx("/bitrix/css/".$dir);
    }

    public function DoInstall(){
        global $APPLICATION;
        if(self::isVersionD7())
        {
            $this->InstallFiles();
            $this->InstallEvents();
            ModuleManager::registerModule($this->MODULE_ID);

            //создание полей и options
            (new \Ourcompany\Servio\Event)->createFiedsAndOptions();

        }
        else
        {
            $APPLICATION->ThrowException(Loc::getMessage("OUR_COMPANY_SERVIO_ERROR_VERSION"));
        }
    }

    public function DoUninstall(){

        //удаление полей
        (new \Ourcompany\Servio\Event)->deleteFieds();
        \Bitrix\Main\Config\Option::delete($this->MODULE_ID);

        $this->UnInstallEvents();
        $this->UninstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }


    public function GetPatch($notDocumentRoot=false)
    {
        if($notDocumentRoot)
            return str_ireplace($_SERVER["DOCUMENT_ROOT"],'',dirname(__DIR__));
        else
            return dirname(__DIR__);
    }

    public function isVersionD7()
    {
        return CheckVersion(SM_VERSION, '14.00.00');
    }


    public function logData($data){
        $file = $_SERVER["DOCUMENT_ROOT"].'/test.log';
        file_put_contents($file, print_r([date('d.m.Y H:i:s'),$data],true), FILE_APPEND | LOCK_EX);
    }

}