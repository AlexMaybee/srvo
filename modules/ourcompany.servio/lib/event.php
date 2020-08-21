<?php
namespace Ourcompany\Servio;

use \Bitrix\Main\Page\Asset,
    \Bitrix\Main\Localization\Loc;


class Event
{
    public function addButtonAndScripts()
    {

        //Подключение б24 библиотеки типа бутстрапа
        \Bitrix\Main\Ui\Extension::load('ui.buttons');
        \Bitrix\Main\Ui\Extension::load('ui.buttons.icons');


        //css & js for popup
        \Bitrix\Main\Page\Asset::getInstance()->addCss("https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css",true);
        \Bitrix\Main\Page\Asset::getInstance()->addJs("http://code.jquery.com/jquery-3.5.1.min.js",true);
        \Bitrix\Main\Page\Asset::getInstance()->addJs("https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js",true);
        \Bitrix\Main\Page\Asset::getInstance()->addJs("https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js",true);

        global $APPLICATION;


        //если что-то не заполнено в настройках, не подключаем файлы с кнопками и скриптами

        if(
            preg_match('#^/crm/deal/details/[0-9]+/#',$APPLICATION->GetCurPage())
            ||
            preg_match('#^/crm/deal/list/#',$APPLICATION->GetCurPage())
            ||
            preg_match('#^/crm/deal/category/[0-9]+/#',$APPLICATION->GetCurPage())
        )
        {

            $dir = implode('_',explode('.',\Ourcompany\Servio\Work\Mysettings::MODULE_ID));

            \CJSCore::RegisterExt($dir, array(
                "js" => "/bitrix/js/{$dir}/script.js",
                "css" => "/bitrix/css/{$dir}/style.css",
            ));
            \CJSCore::init($dir);

            //настройки
            $settingObj = new \Ourcompany\Servio\Work\Mysettings;

            if(!$settingObj->settings['errors'])
            {
                $html = '<button class="ui-btn ui-btn-success ui-btn-icon-task mar-rl-1 ui-btn-clock servio-tmp-disable" id="servio">Бронирование</button>';
                $APPLICATION->AddViewContent('inside_pagetitle', $html,50000);
            }
            else
            {
                $html = '<button class="ui-btn ui-btn-danger mar-rl-1 "
                    title="'.implode("\n",array_values($settingObj->settings['errors'])).'">Бронирование Error</button>';
                $APPLICATION->AddViewContent('inside_pagetitle', $html,50000);
            }
        }

//        self::logData($settingObj->settings);

        return [$settingObj->settings];
    }

    public function createFiedsAndOptions()
    {
        foreach(\Ourcompany\Servio\Work\Mysettings::TECH_FIELDS as $cOpt => $field)
        {
            $obj = new \CUserTypeEntity;
            $createRes = $obj->add($field);
            if ($createRes) {
                $this->setCoptionValue($cOpt, $field['FIELD_NAME']);
            }
        }
    }

    public function deleteFieds()
    {
        $fieldNames = [];
        foreach (\Ourcompany\Servio\Work\Mysettings::TECH_FIELDS as $cOpt => $val)
        {
            $fieldNames[] = $val['FIELD_NAME'];

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

    private function setCoptionValue($name,$value){
        return \Bitrix\Main\Config\Option::set(\Ourcompany\Servio\Work\Mysettings::MODULE_ID,$name,$value);
    }

    public function logData($data){
        $file = $_SERVER["DOCUMENT_ROOT"].'/test.log';
        file_put_contents($file, print_r([date('d.m.Y H:i:s'),$data],true), FILE_APPEND | LOCK_EX);
    }

}