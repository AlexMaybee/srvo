<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager,
    Bitrix\Main\EventManager;

Loc::loadMessages(__FILE__);

class ourcompany_servio extends \cModule
{

    private $b24Fields = [
        'SERVIO_FIELD_RESERVE_ID' =>
            [
                'ENTITY_ID' => 'CRM_DEAL',
                'FIELD_NAME' => 'UF_CRM_HMS_RESERVE_ID',
                'USER_TYPE_ID' => 'double',
                'XML_ID' => '',
                'SORT' => 100,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'N',
                'IS_SEARCHABLE' => 'Y',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'ID резерва в Servio',
                    'en' => 'Reserve ID in Servio',
                ],
            ],
        'SERVIO_FIELD_CONTACT_ID' =>
            [
                'ENTITY_ID' => 'CRM_CONTACT',
                'FIELD_NAME' => 'UF_CRM_HMS_CONTACT_ID',
                'USER_TYPE_ID' => 'double',
                'XML_ID' => '',
                'SORT' => 100,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'N',
                'IS_SEARCHABLE' => 'Y',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'ID контакта в Servio',
                    'en' => 'Contact ID in Servio',
                ],
            ],
        'SERVIO_FIELD_COMPANY_ID' =>
            [
                'ENTITY_ID' => 'CRM_COMPANY',
                'FIELD_NAME' => 'UF_CRM_HMS_COMPANY_ID',
                'USER_TYPE_ID' => 'double',
                'XML_ID' => '',
                'SORT' => 100,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'N',
                'IS_SEARCHABLE' => 'Y',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'ID компании в Servio',
                    'en' => 'Company ID in Servio',
                ],
            ],
    ];

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
            $this->createFieldsAndOptions();
        }
        else
        {
            $APPLICATION->ThrowException(Loc::getMessage("OUR_COMPANY_SERVIO_ERROR_VERSION"));
        }
    }

    public function DoUninstall(){
        $this->deleteFieldsAndOptions();
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

    //создание полей и их options в админке
    private function createFieldsAndOptions()
    {
        foreach($this->b24Fields as $cOpt => $field)
        {
            $obj = new \CUserTypeEntity;
            $createRes = $obj->add($field);
            if ($createRes) {
                $this->setCoptionValue($cOpt, $field['FIELD_NAME']);
            }
        }
    }

    private function deleteFieldsAndOptions()
    {
        $fieldNames = [];
        foreach ($this->b24Fields as $cOpt => $val)
        {
            $fieldNames[] = $val['FIELD_NAME'];
//            delCoption($cOpt);

            if($fieldNames)
            {
                $obj = new \CUserTypeEntity;

                $fieldsObj = \Bitrix\Main\UserFieldTable::getList([
                    'filter' => ['FIELD_NAME' => $fieldNames],
                    'select' => ['ID']
                ]);
                while($ob = $fieldsObj->fetch())
                {
                    $obj->delete($ob['ID']);
                }
            }
        }
    }



    public function logData($data){
        $file = $_SERVER["DOCUMENT_ROOT"].'/test.log';
        file_put_contents($file, print_r([date('d.m.Y H:i:s'),$data],true), FILE_APPEND | LOCK_EX);
    }


    private function setCoptionValue($name,$value){
        return \Bitrix\Main\Config\Option::set($this->MODULE_ID,$name,$value);
    }

//    private function delCoption($name)
//    {
//        return \Bitrix\Main\Config\Option::delete($this->MODULE_ID,['name' => $name]);
//    }

}