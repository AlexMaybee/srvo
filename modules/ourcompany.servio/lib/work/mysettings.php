<?php

namespace Ourcompany\Servio\Work;

use \Bitrix\Main\Localization\Loc;

class Mysettings
{
    //массивы полученных из cOption настроеки ошибок
    public $settings = [
        'success' => [],
        'errors' => [],
    ];
//    public $settingsErrors = [];

    //названия типопв оплат, пока не напишут api
    public $payTypes = [];

    //значения полей по умолчанию
    public $defaultValues = [
        'LOSE_STAGE_ID_NAME_PART' => 'LOSE',
    ];

    //нужен для получения cOption
    const MODULE_ID = 'ourcompany.servio';

    //названия Options ( в т.ч. в options.php)
    const SETTINGS_OPTIONS = [
        'SERVIO_URI_LINK',
        'SERVIO_REST_KEY',
        'SERVIO_COMPANY_CODE',
        'SERVIO_RESERVE_CONFIRM_FILE_FORMAT',
        'SERVIO_BILL_FILE_FORMAT',
        'SERVIO_EXCHANGE_LANG_ID', //язык в сервио, если en - выдает ошибку соединения
        'SERVIO_FIELD_RESERVE_ID',
        'SERVIO_FIELD_COMPANY_ID',
        'SERVIO_FIELD_COMPANY_ADDRESS',
        'SERVIO_FIELD_CONTACT_ID',
        'SERVIO_FIELD_CONTACT_ADDRESS',
        'SERVIO_FIELD_COMPANY_ADDRESS',
        'SERVIO_FIELD_RESERVE_CONFIRM_FILE_ID',
        'SERVIO_FIELD_RESERVE_CONFIRM_FILE',
        'SERVIO_FIELD_BILL_FILE_ID',
        'SERVIO_FIELD_BILL_FILE',
    ];

    //массив полей для /install/index.php
    const TECH_FIELDS = [
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
        'SERVIO_FIELD_CONTACT_ADDRESS' =>
            [
                'ENTITY_ID' => 'CRM_CONTACT',
                'FIELD_NAME' => 'UF_CRM_HMS_CONTACT_ADDRESS',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => '',
                'SORT' => 100,
                'MULTIPLE' => 'N',  //множ
                'MANDATORY' => 'N', //обязательное
                'SHOW_FILTER' => 'I',   //показывать в фильтре
                'SHOW_IN_LIST' => '1', //показывать в списке
                'EDIT_IN_LIST' => '',  //редактировать в списке
                'IS_SEARCHABLE' => 'Y', //участвует в поиске
                'EDIT_FORM_LABEL' => [  //подпись в карточке
                    'ru' => 'Адресс контакта в Servio',
                    'en' => 'Contact Address in Servio',
                ],
                'LIST_COLUMN_LABEL' => [ //название в списке
                    'ru' => 'Адресс контакта в Servio',
                    'en' => 'Contact Address in Servio',
                ],
                'LIST_FILTER_LABEL' => [ //название в списке фильтра
                    'ru' => 'Адресс контакта в Servio',
                    'en' => 'Contact Address in Servio',
                ],
                'ERROR_MESSAGE' => [ //название в списке фильтра
                    'ru' => 'Ошибка в поле "Адресс контакта в Servio"',
                    'en' => 'Error in field "Contact Address in Servio"',
                ],
                'SETTINGS' => [
                    'ROWS' => '3',
                    /* Минимальная длина строки (0 - не проверять) */
//                    'MIN_LENGTH'    => '0',
                    /* Максимальная длина строки (0 - не проверять) */
//                    'MAX_LENGTH'    => '0',
                    /* Регулярное выражение для проверки */
//                    'REGEXP'        => '',
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
        'SERVIO_FIELD_COMPANY_ADDRESS' =>
            [
                'ENTITY_ID' => 'CRM_COMPANY',
                'FIELD_NAME' => 'UF_CRM_HMS_COMPANY_ADDRESS',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => '',
                'SORT' => 100,
                'MULTIPLE' => 'N',  //множ
                'MANDATORY' => 'N', //обязательное
                'SHOW_FILTER' => 'I',   //показывать в фильтре
                'SHOW_IN_LIST' => '1', //показывать в списке
                'EDIT_IN_LIST' => '',  //редактировать в списке
                'IS_SEARCHABLE' => 'Y', //участвует в поиске
                'EDIT_FORM_LABEL' => [  //подпись в карточке
                    'ru' => 'Адресс компании в Servio',
                    'en' => 'Company Address in Servio',
                ],
                'LIST_COLUMN_LABEL' => [ //название в списке
                    'ru' => 'Адресс компании в Servio',
                    'en' => 'Company Address in Servio',
                ],
                'LIST_FILTER_LABEL' => [ //название в списке фильтра
                    'ru' => 'Адресс компании в Servio',
                    'en' => 'Company Address in Servio',
                ],
                'ERROR_MESSAGE' => [ //название в списке фильтра
                    'ru' => 'Ошибка в поле "Адресс компании в Servio"',
                    'en' => 'Error in field "Company Address in Servio"',
                ],
                'SETTINGS' => [
                    'ROWS' => '3',
                    /* Минимальная длина строки (0 - не проверять) */
//                    'MIN_LENGTH'    => '0',
                    /* Максимальная длина строки (0 - не проверять) */
//                    'MAX_LENGTH'    => '0',
                    /* Регулярное выражение для проверки */
//                    'REGEXP'        => '',
                ],
            ],

        'SERVIO_FIELD_RESERVE_CONFIRM_FILE_ID' =>
            [
                'ENTITY_ID' => 'CRM_DEAL',
                'FIELD_NAME' => 'UF_CRM_HMS_RESERVE_CONFIRM_FILE_ID',
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
                    'ru' => 'ID файла подтверждения резерва в Servio',
                    'en' => 'Reserve Confirm File ID in Servio',
                ],
                'LIST_COLUMN_LABEL' => [ //название в списке
                    'ru' => 'ID файла подтверждения резерва в Servio',
                    'en' => 'Reserve Confirm File ID in Servio',
                ],
                'LIST_FILTER_LABEL' => [ //название в списке фильтра
                    'ru' => 'ID файла подтверждения резерва в Servio',
                    'en' => 'Reserve Confirm File ID in Servio',
                ],
                'ERROR_MESSAGE' => [ //название в списке фильтра
                    'ru' => 'ID файла подтверждения резерва в Servio',
                    'en' => 'Reserve Confirm File ID in Servio',
                ],
            ],

        'SERVIO_FIELD_RESERVE_CONFIRM_FILE' =>
            [
                'ENTITY_ID' => 'CRM_DEAL',
                'FIELD_NAME' => 'UF_CRM_HMS_RESERVE_CONFIRM_FILE',
                'USER_TYPE_ID' => 'file',
                'XML_ID' => '',
                'SORT' => 100,
                'MULTIPLE' => 'N',  //множ
                'MANDATORY' => 'N', //обязательное
                'SHOW_FILTER' => 'I',   //показывать в фильтре
                'SHOW_IN_LIST' => '1', //показывать в списке
                'EDIT_IN_LIST' => '',  //редактировать в списке
                'IS_SEARCHABLE' => 'Y', //участвует в поиске
                'EDIT_FORM_LABEL' => [  //подпись в карточке
                    'ru' => 'Файл подтверждения резерва в Servio',
                    'en' => 'Reserve Confirm File in Servio',
                ],
                'LIST_COLUMN_LABEL' => [ //название в списке
                    'ru' => 'Файл подтверждения резерва в Servio',
                    'en' => 'Reserve Confirm File in Servio',
                ],
                'LIST_FILTER_LABEL' => [ //название в списке фильтра
                    'ru' => 'Файл подтверждения резерва в Servio',
                    'en' => 'Reserve Confirm File in Servio',
                ],
                'ERROR_MESSAGE' => [ //название в списке фильтра
                    'ru' => 'Файл подтверждения резерва в Servio',
                    'en' => 'Reserve Confirm File in Servio',
                ],
            ],

        //МНОЖ
        'SERVIO_FIELD_BILL_FILE_ID' =>
            [
                'ENTITY_ID' => 'CRM_DEAL',
                'FIELD_NAME' => 'UF_CRM_HMS_BILL_FILE_ID',
                'USER_TYPE_ID' => 'double',
                'XML_ID' => '',
                'SORT' => 100,
                'MULTIPLE' => 'Y',  //множ
                'MANDATORY' => 'N', //обязательное
                'SHOW_FILTER' => 'I',   //показывать в фильтре
                'SHOW_IN_LIST' => '1', //показывать в списке
                'EDIT_IN_LIST' => '',  //редактировать в списке
                'IS_SEARCHABLE' => 'Y', //участвует в поиске
                'EDIT_FORM_LABEL' => [  //подпись в карточке
                    'ru' => 'ID файла счета в Servio',
                    'en' => 'Bill File ID in Servio',
                ],
                'LIST_COLUMN_LABEL' => [ //название в списке
                    'ru' => 'ID файла счета в Servio',
                    'en' => 'Bill File ID in Servio',
                ],
                'LIST_FILTER_LABEL' => [ //название в списке фильтра
                    'ru' => 'ID файла счета в Servio',
                    'en' => 'Bill File ID in Servio',
                ],
                'ERROR_MESSAGE' => [ //название в списке фильтра
                    'ru' => 'ID файла счета в Servio',
                    'en' => 'Bill File ID in Servio',
                ],
            ],


        //МНОЖ
        'SERVIO_FIELD_BILL_FILE' =>
            [
                'ENTITY_ID' => 'CRM_DEAL',
                'FIELD_NAME' => 'UF_CRM_HMS_BILL_FILE',
                'USER_TYPE_ID' => 'file',
                'XML_ID' => '',
                'SORT' => 100,
                'MULTIPLE' => 'Y',  //множ
                'MANDATORY' => 'N', //обязательное
                'SHOW_FILTER' => 'I',   //показывать в фильтре
                'SHOW_IN_LIST' => '1', //показывать в списке
                'EDIT_IN_LIST' => '',  //редактировать в списке
                'IS_SEARCHABLE' => 'Y', //участвует в поиске
                'EDIT_FORM_LABEL' => [  //подпись в карточке
                    'ru' => 'Файл счета из Servio',
                    'en' => 'Bill File from Servio',
                ],
                'LIST_COLUMN_LABEL' => [ //название в списке
                    'ru' => 'Файл счета из Servio',
                    'en' => 'Bill File from Servio',
                ],
                'LIST_FILTER_LABEL' => [ //название в списке фильтра
                    'ru' => 'Файл счета из Servio',
                    'en' => 'Bill File from Servio',
                ],
                'ERROR_MESSAGE' => [ //название в списке фильтра
                    'ru' => 'Файл счета из Servio',
                    'en' => 'Bill File from Servio',
                ],
            ],
    ];


    function __construct()
    {
        foreach (self::SETTINGS_OPTIONS as $option)
        {
            $optionValue = \Bitrix\Main\Config\Option::get(self::MODULE_ID, $option);
            if(trim($optionValue) != '')
            {
                $this->settings['success'][$option] = trim($optionValue);
            }
            else
            {
                $this->settings['errors'][] = Loc::getMessage("OUR_COMPANY_MYSETTINGS_{$option}_ERROR");
            }

            $this->payTypes = [
                100 => Loc::getMessage("OUR_COMPANY_MYSETTINGS_PAY_TYPE_CASH"),
                200 => Loc::getMessage("OUR_COMPANY_MYSETTINGS_PAY_TYPE_CREDIT_CARD"),
                300 => Loc::getMessage("OUR_COMPANY_MYSETTINGS_PAY_TYPE_CASHLESS")
            ];
        }
    }

    public function getBitrixLanguagesList()
    {
        $result = [];
        $languagesObj = \Bitrix\Main\Localization\LanguageTable::getList(['select' => ['NAME','LID','CULTURE_ID'], 'filter' => ['ACTIVE' => 'Y']]);
        while($ob =  $languagesObj->fetch())
        {
//            $result[$ob['LID']] = ['CULTURE_ID' => $ob['CULTURE_ID'],'NAME' => $ob['NAME']];
            $result[$ob['LID']] = $ob['NAME'];
        }
        return $result;
    }

}