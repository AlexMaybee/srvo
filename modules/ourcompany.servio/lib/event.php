<?php
namespace Ourcompany\Servio;

use \Bitrix\Main\Page\Asset,
    \Bitrix\Main\Localization\Loc;


class Event
{
    private $numberPattern = '#[\d]+#';

    public function addButtonAndScripts()
    {
        global $APPLICATION;

        //если что-то не заполнено в настройках, не подключаем файлы с кнопками и скриптами

        if(
            preg_match('#^/crm/deal/details/[\\d]+/#',$APPLICATION->GetCurPage())
            ||
//            preg_match('#^/crm/deal/list/#',$APPLICATION->GetCurPage())
//            ||
            preg_match('#^/crm/deal/category/[\\d]+/#',$APPLICATION->GetCurPage())
        )
        {

            //css & js for popup
        \Bitrix\Main\Page\Asset::getInstance()->addCss("https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css",true);
        \Bitrix\Main\Page\Asset::getInstance()->addJs("https://code.jquery.com/jquery-3.5.1.min.js",true);
        \Bitrix\Main\Page\Asset::getInstance()->addJs("https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js",true);
        \Bitrix\Main\Page\Asset::getInstance()->addJs("https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js",true);

            //Подключение б24 библиотеки типа бутстрапа
            \Bitrix\Main\Ui\Extension::load('ui.buttons');
            \Bitrix\Main\Ui\Extension::load('ui.buttons.icons');
//            \Bitrix\Main\Ui\Extension::load('ui.bootstrap4');

            $dealId = 0;
            $categoryId = '';
            if(preg_match('#^/crm/deal/details/[0-9]+/#',$APPLICATION->GetCurPage(),$matches))
            {
                preg_match('#[\d]+#',$matches[0],$matchesSubDeal);
                $dealId = ($matchesSubDeal[0]) ? $matchesSubDeal[0] : 0;
            }

            if(preg_match('#^/crm/deal/category/[0-9]+/#',$APPLICATION->GetCurPage(),$matches))
            {
                if(preg_match('#[\d]+#',$matches[0],$matchesSubCategory))
                {
                    $categoryId = (isset($matchesSubCategory[0])) ? $matchesSubCategory[0] : '';
                }
            }

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
                //флаг для отображения кнопки в сделках и категориях
                $showButtonFlag = false;

                if(stripos($settingObj->settings['success']['SERVIO_EXCHANGE_DEAL_CATEGORIES'],','))
                {
                    $allowedCategories = explode(',',$settingObj->settings['success']['SERVIO_EXCHANGE_DEAL_CATEGORIES']);
                }
                else
                {
                    $allowedCategories = (array) $settingObj->settings['success']['SERVIO_EXCHANGE_DEAL_CATEGORIES'];
                }

                //отображение в списке сделко в категории (направлении)
                if($categoryId !== '' && in_array($categoryId,$allowedCategories))
                {
                    $showButtonFlag = true;
                }
                if($dealId)
                {
                    //проверяем по id сделки ее категорию (направление)
                    $dealArray = (new \Ourcompany\Servio\Deal)->getDealFields(['ID' => $dealId],['ID','TITLE','CATEGORY_ID'],[]);
                    if($dealArray && in_array($dealArray['CATEGORY_ID'],$allowedCategories))
                    {
                        $showButtonFlag = true;
                    }
                }

                if($showButtonFlag)
                {
                    $html = '<button class="ui-btn ui-btn-success ui-btn-icon-task mar-rl-1 ui-btn-clock servio-tmp-disable" id="servio">Бронирование</button>';
                    $APPLICATION->AddViewContent('inside_pagetitle', $html,50000);
                }

            }
            else
            {
                $html = '<button class="ui-btn ui-btn-danger mar-rl-1 "
                    title="'.implode("\n",array_values($settingObj->settings['errors'])).'">Бронирование Error</button>';
                $APPLICATION->AddViewContent('inside_pagetitle', $html,50000);
            }
        }

        return [$settingObj->settings];
    }

    public function createFiedsAndOptions()
    {
        //список полей для получения
        $existedUserFieldsArr = [];
        $existedUserFieldsObject = \Bitrix\Main\UserFieldTable::getList([
            'filter' => ['ENTITY_ID' => \Ourcompany\Servio\Work\Mysettings::USER_FIELD_ENTITIES_FILTER],
            'select' => ['*']
        ]);

        while($field = $existedUserFieldsObject->fetch())
        {
            if(!in_array($field['FIELD_NAME'],$existedUserFieldsArr))
            {
                $existedUserFieldsArr[] = $field['FIELD_NAME'];
            }
        }

        foreach(\Ourcompany\Servio\Work\Mysettings::TECH_FIELDS as $cOpt => $field)
        {
            //если поле уже существуе, то просто сохраняем в cOptions
            if($existedUserFieldsArr && in_array($field['FIELD_NAME'],$existedUserFieldsArr))
            {
                $this->setCoptionValue($cOpt, $field['FIELD_NAME']);

                $this->logData(['exists' => $field['FIELD_NAME']]);

            }
            else
            {
            //иначе создаем и сохраняем в cOptions
                $obj = new \CUserTypeEntity;
                $createRes = $obj->add($field);
                if ($createRes) {
                    $this->setCoptionValue($cOpt, $field['FIELD_NAME']);

                    $this->logData(['create' => $field['FIELD_NAME']]);

                }
            }
        }
    }

    public function deleteFieds()
    {
        $fieldNames = [];
        $fieldNames = array_column(\Ourcompany\Servio\Work\Mysettings::TECH_FIELDS,'FIELD_NAME');
//        foreach (\Ourcompany\Servio\Work\Mysettings::TECH_FIELDS as $cOpt => $val)
//        {
////            $fieldNames[] = $val['FIELD_NAME'];
//            if(!in_array($val['FIELD_NAME'],$fieldNames))
//            {
//                $fieldNames[] = $val['FIELD_NAME'];
//            }
//        }
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

        $this->logData(['delete_fields' => $fieldNames]);
    }

    private function setCoptionValue($name,$value){
        return \Bitrix\Main\Config\Option::set(\Ourcompany\Servio\Work\Mysettings::MODULE_ID,$name,$value);
    }

    public function logData($data){
        $file = $_SERVER["DOCUMENT_ROOT"].'/test_111.log';
        file_put_contents($file, print_r([date('d.m.Y H:i:s'),$data],true), FILE_APPEND | LOCK_EX);
    }

}