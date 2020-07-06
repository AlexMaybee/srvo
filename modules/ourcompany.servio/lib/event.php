<?php
namespace Ourcompany\Servio;

use \Bitrix\Main\Page\Asset,
    \Bitrix\Main\Localization\Loc;


class Event
{
    const MODULE_ID = 'ourcompany.servio';

    const SETTINGS_OPTIONS = ['SERVIO_URI_LINK','SERVIO_REST_KEY','SERVIO_COMPANY_CODE','SERVIO_FIELD_RESERVE_ID','SERVIO_FIELD_COMPANY_ID','SERVIO_FIELD_CONTACT_ID'];


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
                $html = ' <button class="ui-btn ui-btn-success ui-btn-icon-task ui-btn-round mar-rl-1" id="servio">Servio!</button>';
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


    public function logData($data){
        $file = $_SERVER["DOCUMENT_ROOT"].'/test.log';
        file_put_contents($file, print_r([date('d.m.Y H:i:s'),$data],true), FILE_APPEND | LOCK_EX);
    }

}