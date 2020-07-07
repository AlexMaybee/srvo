<?php
namespace Ourcompany\Servio;

use \Bitrix\Main\Page\Asset,
    \Bitrix\Main\Localization\Loc;


class Event
{
    const MODULE_ID = 'ourcompany.servio';

    const SETTINGS_OPTIONS = ['SERVIO_URI_LINK','SERVIO_REST_KEY','SERVIO_COMPANY_CODE','SERVIO_FIELD_RESERVE_ID','SERVIO_FIELD_COMPANY_ID','SERVIO_FIELD_CONTACT_ID'];

    private $techFields = [
        'SERVIO_FIELD_RESERVE_ID' =>
            [
                'ENTITY_ID' => 'CRM_DEAL',
                'FIELD_NAME' => 'UF_CRM_HMS_RESERVE_ID',
                'USER_TYPE_ID' => 'double',
                'XML_ID' => '',
                'SORT' => 100,
                'MULTIPLE' => 'N',  //множ
                'MANDATORY' => 'N', //обязательное
                'SHOW_FILTER' => 'I',   //показывать в фильтре
                'SHOW_IN_LIST' => '1', //показывать в списке
                'EDIT_IN_LIST' => '',  //редактировать в списке
                'IS_SEARCHABLE' => 'Y', //участвует в поиске
                'EDIT_FORM_LABEL' => [  //подпись в карточке
                    'ru' => 'ID резерва в Servio',
                    'en' => 'Reserve ID in Servio',
                ],
                'LIST_COLUMN_LABEL' => [ //название в списке
                    'ru' => 'ID резерва в Servio',
                    'en' => 'Reserve ID in Servio',
                ],
                'LIST_FILTER_LABEL' => [ //название в списке фильтра
                    'ru' => 'ID резерва в Servio',
                    'en' => 'Reserve ID in Servio',
                ],
                'ERROR_MESSAGE' => [ //название в списке фильтра
                    'ru' => 'Ошибка в поле "ID резерва в Servio"',
                    'en' => 'Error in field "Reserve ID in Servio"',
                ],
            ],
        'SERVIO_FIELD_CONTACT_ID' =>
            [
                'ENTITY_ID' => 'CRM_CONTACT',
                'FIELD_NAME' => 'UF_CRM_HMS_CONTACT_ID',
                'USER_TYPE_ID' => 'double',
                'XML_ID' => '',
                'SORT' => 100,
                'MULTIPLE' => 'N',  //множ
                'MANDATORY' => 'N', //обязательное
                'SHOW_FILTER' => 'I',   //показывать в фильтре
                'SHOW_IN_LIST' => '1', //показывать в списке
                'EDIT_IN_LIST' => '',  //редактировать в списке
                'IS_SEARCHABLE' => 'Y', //участвует в поиске
                'EDIT_FORM_LABEL' => [  //подпись в карточке
                    'ru' => 'ID контакта в Servio',
                    'en' => 'Contact ID in Servio',
                ],
                'LIST_COLUMN_LABEL' => [ //название в списке
                    'ru' => 'ID контакта в Servio',
                    'en' => 'Contact ID in Servio',
                ],
                'LIST_FILTER_LABEL' => [ //название в списке фильтра
                    'ru' => 'ID контакта в Servio',
                    'en' => 'Contact ID in Servio',
                ],
                'ERROR_MESSAGE' => [ //название в списке фильтра
                    'ru' => 'Ошибка в поле "ID контакта в Servio"',
                    'en' => 'Error in field "Contact ID in Servio"',
                ],
            ],
        'SERVIO_FIELD_COMPANY_ID' =>
            [
                'ENTITY_ID' => 'CRM_COMPANY',
                'FIELD_NAME' => 'UF_CRM_HMS_COMPANY_ID',
                'USER_TYPE_ID' => 'double',
                'XML_ID' => '',
                'SORT' => 100,
                'MULTIPLE' => 'N',  //множ
                'MANDATORY' => 'N', //обязательное
                'SHOW_FILTER' => 'I',   //показывать в фильтре
                'SHOW_IN_LIST' => '1', //показывать в списке
                'EDIT_IN_LIST' => '',  //редактировать в списке
                'IS_SEARCHABLE' => 'Y', //участвует в поиске
                'EDIT_FORM_LABEL' => [  //подпись в карточке
                    'ru' => 'ID компании в Servio',
                    'en' => 'Company ID in Servio',
                ],
                'LIST_COLUMN_LABEL' => [ //название в списке
                    'ru' => 'ID компании в Servio',
                    'en' => 'Company ID in Servio',
                ],
                'LIST_FILTER_LABEL' => [ //название в списке фильтра
                    'ru' => 'ID компании в Servio',
                    'en' => 'Company ID in Servio',
                ],
                'ERROR_MESSAGE' => [ //название в списке фильтра
                    'ru' => 'Ошибка в поле "ID компании в Servio"',
                    'en' => 'Error in field "Company ID in Servio"',
                ],
            ],
    ];

    public static $settings = [
        'SERVIO_URI_LINK' => false,
        'SERVIO_REST_KEY' => false,
        'SERVIO_COMPANY_CODE' => false,
        'SERVIO_FIELD_RESERVE_ID' => false,
        'SERVIO_FIELD_COMPANY_ID' => false,
        'SERVIO_FIELD_CONTACT_ID' => false,
    ];
    public static $settingErrors = [];


//    public function __construct()
//    {
//        self::getSettings();
//    }

    public function getSettings()
    {
        foreach (self::SETTINGS_OPTIONS as $option)
        {
            $optionValue = \Bitrix\Main\Config\Option::get(self::MODULE_ID, $option);
            if($optionValue)
            {
                self::$settings[$option] = trim($optionValue);
            }
            else
            {
                self::$settingErrors[] = Loc::getMessage("OUR_COMPANY_SETTINGS_{$option}_ERROR");
            }

        }
    }


    public function addButtonAndScripts()
    {
        self::getSettings();

        //Подключение б24 библиотеки типа бутстрапа
        \Bitrix\Main\Ui\Extension::load('ui.buttons');
        \Bitrix\Main\Ui\Extension::load('ui.buttons.icons');


        //css & js for popup
        \Bitrix\Main\Page\Asset::getInstance()->addCss("https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css",true);
        \Bitrix\Main\Page\Asset::getInstance()->addJs("https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js",true);
        \Bitrix\Main\Page\Asset::getInstance()->addJs("https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js",true);

        global $APPLICATION;


        //если что-то не заполнено в настройках, не подключаем файлы с кнопками и скриптами


        if(preg_match('#^/crm/deal/details/[0-9]+/#',$APPLICATION->GetCurPage()))
        {

            $dir = implode('_',explode('.',self::MODULE_ID));

            \CJSCore::RegisterExt($dir, array(
                "js" => "/bitrix/js/{$dir}/script.js",
                "css" => "/bitrix/css/{$dir}/style.css",
            ));
            \CJSCore::init($dir);

            //Штатная библиотека
            if(!\CJSCore::Init(["jquery2"]))
                \CJSCore::Init(["jquery2"]);


            if(!self::$settingErrors)
            {
                $html = '<button class="ui-btn ui-btn-success ui-btn-icon-task ui-btn-round mar-rl-1" id="servio">Servio!</button>';
                $APPLICATION->AddViewContent('inside_pagetitle', $html,50000);
            }
            else
            {
                $html = '<button class="ui-btn ui-btn-danger mar-rl-1 "
                    title="'.implode("\n",array_values(self::$settingErrors)).'">Servio Error</button>';
                $APPLICATION->AddViewContent('inside_pagetitle', $html,50000);
            }


        }



//        self::logData([self::$settings,self::$settingErrors]);

        return [self::$settings,self::$settingErrors];
    }

    public function createFiedsAndOptions()
    {
        foreach($this->techFields as $cOpt => $field)
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
        foreach ($this->techFields as $cOpt => $val)
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

    private function setCoptionValue($name,$value){
        return \Bitrix\Main\Config\Option::set(self::MODULE_ID,$name,$value);
    }

    public function logData($data){
        $file = $_SERVER["DOCUMENT_ROOT"].'/test.log';
        file_put_contents($file, print_r([date('d.m.Y H:i:s'),$data],true), FILE_APPEND | LOCK_EX);
    }

}