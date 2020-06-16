<?php
namespace Ourcompany\Servio;

use \Bitrix\Main\Page\Asset,
    \Bitrix\Main\Localization\Loc;


class Hotel
{
    private $eventInstance;
    private $settings;
    private $settingErrors;


    public function __construct()
    {
        $this->eventInstance = new \Ourcompany\Servio\Event;
        $this->eventInstance->getSettings();

        $this->settings = \Ourcompany\Servio\Event::$settings;
        $this->settingErrors = \Ourcompany\Servio\Event::$settingErrors;

//        echo json_encode(['HELLO, OOOOO!',$this->settings,$this->settingErrors]);
    }

    public function getDealReserveId($dealId)
    {
        $result = 0;
        $dealId = intval($dealId);
        if($dealId > 0)
        {
            \CModule::IncludeModule("crm"); //Какого хера??
            $dealRecord = \Bitrix\Crm\DealTable::getRow([
                'select' => ['UF_CRM_1592225883215'], //  получаем строку с ID резерва
                'filter' => ['ID' => $dealId],
            ]);
            if($dealRecord)
            {
                $result = (intval($dealRecord['UF_CRM_1592225883215']) > 0) ? intval($dealRecord['UF_CRM_1592225883215']) : 0;
            }
        }
        return $result;
    }


    // !!!НЕ ЗАБЫТЬ ПОЛУЧАТЬ ПОЛЯ С ID клиента из СЕРВИО для Компаний или контактов.
    public function getDealDataById($dealId)
    {
        $dealId = intval($dealId);
        if($dealId >  0)
        {
            \CModule::IncludeModule("crm"); //Какого хера??
            $dealRecord = \Bitrix\Crm\DealTable::getRow([
                'select' => [/*'*','UF_CRM_1592225883215',*/'ID','CONTACT_ID','COMPANY_ID','ASSIGNED_BY_ID'], //  + получаем строку с ID резерва
                'filter' => ['ID' => $dealId],
            ]);
            $dealRecord['COMPANY_DATA'] = [];
            $dealRecord['CONTACT_DATA'] = [];
            $dealRecord['PHONES_AND_EMAILS'] = [];

            if($dealRecord)
            {
                if($dealRecord['COMPANY_ID'] > 0)
                {
                    $companyData = \Bitrix\Crm\CompanyTable::getRow([
                        'select' => ['*','PHONE'/*,'ID','TITLE','COMPANY_ID','ASSIGNED_BY_ID'*/], //  + получаем строку с ID резерва
                        'filter' => ['ID' => $dealRecord['COMPANY_ID']]
                    ]);
                    if($companyData)
                    {
                        if($dealRecord['CONTACT_ID'] <= 0 && $companyData['CONTACT_ID'] > 0)
                        {
                            $contactData = \Bitrix\Crm\CompanyTable::getRow([
                                'select' => ['*'/*,'ID','TITLE','COMPANY_ID','ASSIGNED_BY_ID'*/], //  + получаем строку с ID резерва
                                'filter' => ['ID' => $companyData['CONTACT_ID']]
                            ]);
                            if($contactData)
                            {
                                $dealRecord['CONTACT_DATA'] = $contactData;

                                //почты и телефоны для контакта
                                $dealRecord['PHONES_AND_EMAILS'] = self::getPhonesAndEmails('CONTACT',$companyData['CONTACT_ID'],['EMAIL','PHONE']);

//                                $phonesAndEmailsFilter = array(
//                                    'ENTITY_ID'  => 'CONTACT',
//                                    'ELEMENT_ID' => $companyData['CONTACT_ID'],
//                                    'TYPE_ID'    => ['EMAIL','PHONE'],//сюда NAME?
//                                    'VALUE_TYPE' => [],
//                                );
//                                $phonesAndEmails = \CCrmFieldMulti::GetListEx(array(),$phonesAndEmailsFilter,false,array('nTopCount'=>1),array('VALUE'))->fetch();
//                                if($phonesAndEmails)
//                                {
//                                    $dealRecord['CONTACT_DATA']['PHONES_AND_EMEILS'] = $phonesAndEmails;
//                                }
                            }
                        }
                        else
                        {
                            //почты и телефоны для компании
                            $dealRecord['PHONES_AND_EMAILS'] = self::getPhonesAndEmails('COMPANY',$companyData['ID'],['EMAIL','PHONE']);

//                            $phonesAndEmailsFilter = array(
//                                'ENTITY_ID'  => 'COMPANY',
//                                'ELEMENT_ID' => $companyData['ID'],
//                                'TYPE_ID'    => ['EMAIL','PHONE'],//сюда NAME?
//                                'VALUE_TYPE' => [],
//                            );
//                            $phonesAndEmails = \CCrmFieldMulti::GetListEx(array(),$phonesAndEmailsFilter,false,array('nTopCount'=>1),array('VALUE'))->fetch();
//                            if($phonesAndEmails)
//                            {
//                                $dealRecord['COMPANY_DATA']['PHONES_AND_EMEILS'] = $phonesAndEmails;
//                            }

                        }

                        $dealRecord['COMPANY_DATA'] = $companyData;
                    }
                }
                if($dealRecord['CONTACT_ID'] > 0)
                {
                    $contactData = \Bitrix\Crm\ContactTable::getRow([
                        'select' => ['*'/*,'ID','TITLE','COMPANY_ID','ASSIGNED_BY_ID'*/], //  + получаем строку с ID резерва
                        'filter' => ['ID' => $dealRecord['CONTACT_ID']]
                    ]);
                    if($contactData)
                    {
                        $dealRecord['CONTACT_DATA'] = $contactData;

                        //почты и телефоны в одном массиве
                        $dealRecord['PHONES_AND_EMAILS'] = self::getPhonesAndEmails('CONTACT',$dealRecord['CONTACT_ID'],['EMAIL','PHONE']);

                    }
                }
            }
            return $dealRecord;
        }

        return false;
    }

    public function getInfoForServioReserveForm($post)
    {
        $result = [
            'company' => [],
            'categories' => [],
            'errors' => [],
        ];

        //добавляем код компании для получения цен
        $post['companyId'] = 0;

        //1.Данные компании
        $companyData = $this->getCompanyInfo();
        if($companyData['error'])
        {
            $result['errors'][] = $companyData['error'];
        }
        else
        {
            $result['company'] = $companyData['result'];

            //обновляем код компании для получения цен
            $post['companyId'] = $companyData['result']['CompanyID'];
        }

        //2. Категории+ названия
        $categoryData = $this->getCategoriesWithRooms($post);
        if($categoryData['error'])
        {
            $result['errors'][] = $categoryData['error'];
        }
        else
        {
            $result['categories'] = $categoryData['result'];
        }


        return $result;
    }

    public function getCompanyInfo()
    {
        $result = [
            'result' => false,
            'error' => false,
        ];


//        $data['CompanyCode'] = $this->settings['SERVIO_COMPANY_CODE'];
        $companyInfoRes = $this->postRequest('GetCompanyInfo',
            [
                'CompanyCode'  => $this->settings['SERVIO_COMPANY_CODE'],
//                'CompanyCode'  => '',
            ]);

        if($companyInfoRes['Result'] === 0)
        {
            unset($companyInfoRes['Error']);
            unset($companyInfoRes['ErrorCode']);
            unset($companyInfoRes['Result']);
            $result['result'] = $companyInfoRes;
        }
        else
        {
            $result['error'] = $companyInfoRes['Error'];
        }

        return $result;
    }

    public function getCategoriesWithRooms($post)
    {
        $result = [
            'result' => false,
            'error' => false,
        ];

//        return $post;

        //Получаем по датам категория + кол-во свободных номеров
        $categoryRes = $this->getCategoriesByFilter($post);

//        $result['result']['test_all_exists_rooms'] = $categoryRes;

        if(!$categoryRes['Result'] === 0)
        {
            $result['error'] = $categoryRes['Error'];
        }
        else
        {
            $data = ['HotelID' => 0];
            $priceFilter = [];
            $categoryNameRes = $this->postRequest('GetRoomTypesList',$data);

//            $result['result']['test_all_categories'] = $categoryNameRes;

            if(!$categoryNameRes['Result'] === 0)
            {
                $result['error'] = $categoryNameRes['Error'];
            }
            else
            {
                $roomsData = [];//записываем массив категорий с данными по резевам, если есть свободные комнаты
                foreach ($categoryRes['RoomTypes'] as $categoryArr)
                {
                    if($categoryArr['FreeRoom'] > 0)
                    {
                        $index = array_search($categoryArr['ID'],$categoryNameRes['IDs']);
                        if($index !== false)
                        {
                            $priceFilter[] = $categoryNameRes['IDs'][$index];

                            $roomsData[$categoryArr['ID']] = [
                                'FreeRoom' => $categoryArr['FreeRoom'],
                                'MainPlacesCount' => $categoryArr['MainPlacesCount'],
                                'NearestDateToReservation' => $categoryArr['NearestDateToReservation'],
                            ];

//                            $result['result']['rooms_test'][] = [
//                                'Id' => $categoryNameRes['IDs'][$index],
//                                'HotelId' => $categoryNameRes['HotelIDs'][$index],
//                                'CategoryName' => $categoryNameRes['ClassNames'][$index],
//                                'FreeRoom'  => $categoryArr['FreeRoom'],
//                                'MainPlacesCount'  => $categoryArr['MainPlacesCount'],
//                                'NearestDateToReservation'  => $categoryArr['NearestDateToReservation'],
//                            ];
                        }
                    }
                }

                if(!$priceFilter)
                {
                    $result['error'] = 'Нет свободных комнат!';
                }
                else
                {

                    //получение цен по каждой категории
                    $categoryPriceArr = $this->getPriceByCategories($post,$priceFilter);

//                    $result['result']['test_all_prices'] = $categoryNameRes;

//                $this->logData($categoryPriceArr);

                    //переформатирование массива
                    $newArr = [];

                    if($categoryPriceArr['Result'] !== 0)
                    {
                        $result['error'] = $categoryPriceArr['Error'];
                    }
                    else
                    {

//                    $result['result']['priceLists'] =
//                        [
//                            'ValuteShort' => $categoryPriceArr['ValuteShort'],
//                            'PriceLists' => $categoryPriceArr['PriceLists'],
//                        ];



                        foreach ($categoryPriceArr['PriceLists'] as $priceList)
                        {
                            foreach ($priceList['RoomTypes'] as $roomType)
                            {
                                foreach ($roomType['Services'] as $servise)
                                {

                                    $serviseArr[$servise['ServiceID']] = [
                                        'serviseName' => $servise['ServiceName'],
                                        'serviseTypeName' => $servise['ServiceTypeName'],
                                        'serviseSumArr' => []
                                    ];

                                    foreach ($servise['PriceDates'] as $priceDate)
                                    {
                                        $serviseArr[$servise['ServiceID']]['serviseSumArr'][] = $priceDate['Price'];
                                    }

                                    $dateFrom = date('d.m.Y',strtotime($post['dateFrom']));
                                    $dateTo = date('d.m.Y',strtotime($post['dateTo']));
                                    $dates = "{$dateFrom} - {$dateTo}";
                                    if(
                                        date('n',strtotime($post['dateFrom'])) === date('n',strtotime($post['dateTo']))
                                        &&
                                        date('Y',strtotime($post['dateFrom'])) === date('Y',strtotime($post['dateTo']))
                                    )
                                    {
                                        $dates = date('d',strtotime($post['dateFrom'])).' - '.date('d.m.Y',strtotime($post['dateTo']));
                                    }
                                    elseif(
                                        date('n',strtotime($post['dateFrom'])) !== date('n',strtotime($post['dateTo']))
                                        &&
                                        date('Y',strtotime($post['dateFrom'])) === date('Y',strtotime($post['dateTo']))
                                    )
                                    {
                                        $dates = date('d.m',strtotime($post['dateFrom'])).' - '.date('d.m.Y',strtotime($post['dateTo']));
                                    }


                                    $newArr[] = [
                                        'priceId' => $priceList['PriceListID'],
                                        'IsNonReturnRate' => $priceList['IsNonReturnRate'],
                                        'IsSpecRate' => $priceList['IsSpecRate'],
                                        'totalDays' => count($servise['PriceDates']),
                                        'servises' =>  $serviseArr,
                                        'totalPrice' => '',
                                        'dates' => "{$dates}", //??? правильно???
                                        'currency' => $categoryPriceArr['ValuteShort'],
                                        'roomTypeId' => $roomType['ID'],
                                        'roomTypeName' => '',
                                        'HotelId' => 0,
                                        'FreeRoom'  => 0,
                                        'MainPlacesCount'  => 0,
                                        'NearestDateToReservation'  => '',
                                    ];

                                }
                            }
                        }

                        if($newArr)
                        {
                            foreach ($newArr as $roomTypeS)
                            {
                                foreach ($roomTypeS['servises'] as $id => $servise)
                                {
                                    $servise['sum'] = array_sum($servise['serviseSumArr']);
                                    $roomTypeS['servises'][$id] = $servise;
                                    $roomTypeS['totalPrice'] += $servise['sum'];
                                }

                                $indexN = array_search($roomTypeS['roomTypeId'],$categoryNameRes['IDs']);
                                if($indexN !== false)
                                {
                                    $roomTypeS['roomTypeName'] = $categoryNameRes['ClassNames'][$indexN];
                                    $roomTypeS['HotelId'] = $categoryNameRes['HotelIDs'][$indexN];

                                }

                                if(in_array($roomTypeS['roomTypeId'],array_keys($roomsData)))
                                {
                                    $roomTypeS['FreeRoom'] = $roomsData[$roomTypeS['roomTypeId']]['FreeRoom'];
                                    $roomTypeS['MainPlacesCount'] = $roomsData[$roomTypeS['roomTypeId']]['MainPlacesCount'];
                                    $roomTypeS['NearestDateToReservation'] = $roomsData[$roomTypeS['roomTypeId']]['NearestDateToReservation'];
                                }

//                                $result['result']['priceLists'][$roomTypeS['roomTypeId']] = $roomTypeS;
                                $result['result']['rooms'][$roomTypeS['roomTypeId']] = $roomTypeS;
                            }
                        }

//                        $result['result']['test'] = $categoryPriceArr;
                    }

                }



//                $result['result']['post'] = $post;
//                $result['result']['test123'] = $newArr;
            }
        }
        return $result;
    }

    private function getCategoriesByFilter($post)
    {
        $data = [
//            'CompanyCodeID' => $post['companyId'],
            'HotelID' => 1,
            'DateArrival' => $post['dateFrom'],
            'DateDeparture' => $post['dateTo'],
            'ChildAges' => [],
            'Adults' => $post['adults'],
            'Childs' => $post['childs'],
            'IsExtraBedUsed' => false,
            'IsoLanguage'  => 'ru',
            'TimeArrival' =>  '', //пока константы
            'TimeDeparture' =>  '',  //пока константы
        ];
        return $res = $this->postRequest('GetRooms',$data);
    }

    //get prices
    public function getPriceByCategories($post,$categoryIds)
    {
        $data = [
            'HotelID' => 1,
            'CompanyID' => $post['companyId'],
            'DateArrival' => $post['dateFrom'],
            'DateDeparture' => $post['dateTo'],
            'TimeArrival' =>  '',
            'TimeDeparture' =>  '',
            'Adults' => $post['adults'],
            'Childs' => $post['childs'],
            'ChildAges' => [],
            'IsExtraBedUsed' => false,
            'IsoLanguage'  => 'ru',
            'RoomTypeIDs' => $categoryIds, // [1,2,3,5],
            'СontractConditionID' => 0,
            'PaidType' => 100,
            'IsExtraBedUsed' => false,
            'NeedTransport' => 0,
//            'IsTouristTax' => 0,
            'LPAuthCode' => '',
        ];

        if($post['companyId'])
        {
            $data['CompanyID'] = $post['companyId'];
        }

        return $this->postRequest('GetPrices',$data);
    }

    private function postRequest($operation,$data)
    {

        //Если ошибки с настройками или их нет, то выдаем ошибку
        if($this->settingErrors)
        {
            return $result['error'] = implode("\n ",array_values($this->settingErrors));
        }

        $data_string = json_encode ($data, JSON_UNESCAPED_UNICODE);
        $curl = curl_init($this->settings['SERVIO_URI_LINK'].$operation);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        // Принимаем в виде массива. (false - в виде объекта)
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
                'AccessToken: '.$this->settings['SERVIO_REST_KEY']
            )
        );
        $result = curl_exec($curl);
        curl_close($curl);

        return json_decode($result,true);
    }

    //создание резерва
    public function addReserve($fields)
    {
        $result = [
            'result' => false,
            'error' => false,
        ];

        $dealData = $this->getDealDataById($fields['DEAL_ID']);
        if($dealData)
        {
            $company = 'STATIC TEST COMPANY';
            $clientName = '';
            $clientLastName = '';
            $address = 'STATIC TEST ADDRESS';
            $comment = 'STATIC COMMENT';
            $phone = '';
            $email = '';
            $contactName = '';

            if($dealData['COMPANY_DATA'])
            {
//                $clientName = $dealData['COMPANY_DATA']['TITLE'];
//                $clientLastName = $dealData['COMPANY_DATA']['TITLE'];
//                $address  = $dealData['COMPANY_DATA']['ADDRESS'];
//                $comment = $dealData['COMPANY_DATA']['TITLE'];
//                $company = $dealData['COMPANY_DATA']['TITLE'];
            }
            if($dealData['CONTACT_DATA']){
                $clientName = $dealData['CONTACT_DATA']['NAME'];
                $clientLastName = $dealData['CONTACT_DATA']['LAST_NAME'];
//                $address = $dealData['CONTACT_DATA']['ADDRESS'];
                $contactName = $dealData['CONTACT_DATA']['FULL_NAME'];
            }
            if($dealData['PHONES_AND_EMAILS'])
            {
                if(isset($dealData['PHONES_AND_EMAILS']['PHONE']) && $dealData['PHONES_AND_EMAILS']['PHONE'])
                {
                    foreach ($dealData['PHONES_AND_EMAILS']['PHONE'] as $ph)
                    {
                        $phone .= $ph['VALUE'].' ';
                    }
                }
                if(isset($dealData['PHONES_AND_EMAILS']['EMAIL']) && $dealData['PHONES_AND_EMAILS']['EMAIL'])
                {
                    foreach ($dealData['PHONES_AND_EMAILS']['EMAIL'] as $em)
                    {
                        $email .= $em['VALUE'].' ';
                    }
                }
            }

//            return $dealData;


            $data = [
//                'HotelID' => intval($fields['ROOM_CATEGORY']['HotelId']),
//                'DateArrival' => $fields['FILTERS']['dateFrom'],
//                'DateDeparture' => $fields['FILTERS']['dateTo'],
//                'TimeArrival' => '', //пока константы
//                'TimeDeparture' => '',  //пока константы
//                'Adults' => $fields['FILTERS']['adults'],
//                'Childs' => $fields['FILTERS']['childs'],
//                'ChildAges' => [], //$fields['FILTERS'][''] //это будет в popup
//                'IsExtraBedUsed' => false,
//                'GuestLastName' => $clientName,
//                'GuestFirstName' => $clientLastName,
//                'RoomTypeID' => intval($fields['FILTERS']['roomCategory']),
//                'CompanyID' => $fields['FILTERS']['companyId'], //113
//                'Company' => $company,  //НАФИГА???!!!
//                'ContractConditionID' => 1, //это брать где-то...
//                'PaidType' => 100,  //это будет в popup
//                'Iso3Country' => 'RUS',
//                'Country' => 'Ukraine',
//                'Address' => trim($address),
//                'Phone' => trim($phone),
//                'Fax' => '',
//                'eMail' => $email,
//                'NeedTransport' => 0, //это будет в popup
//                'Comment' => $comment,
//                'ClientInfo' => $_SERVER['REMOTE_ADDR'],
//                'IsTouristTax' => 0, //это будет в popup
//                'PriceListID' => $fields['ROOM_CATEGORY']['priceId'],
//                'ContactName' => $contactName,


                'HotelID' => 1,
                'DateArrival' => $fields['FILTERS']['dateFrom'],
                'DateDeparture' => $fields['FILTERS']['dateTo'],
                'TimeArrival' =>  '', //пока константы
                'TimeDeparture' =>  '',  //пока константы
                'Adults' => $fields['FILTERS']['adults'],
                'Childs' => $fields['FILTERS']['childs'],
                'ChildAges' => [],
                'IsExtraBedUsed' => false,
                'GuestLastName' => 'LOL',
                'GuestFirstName' => 'LolOvich',
                'RoomTypeID' => $fields['FILTERS']['roomCategory'],
                'CompanyID' => $fields['FILTERS']['companyId'], //113
                'Company'  => 'Company Name',
                'ContractConditionID' => 1,
                'PaidType' => 100,
                'Iso3Country' => 'UKR',
                'Country' => 'Ukraine',
                'Address' => 'Addr Test 1234',
                'Phone' => '0671112233',
                'Fax' => '',
                'eMail' => 'email.test@test.ua',
                'NeedTransport' => 0,
                'Comment' => 'TEST COMMENT LALLAA',
                'ClientInfo' => 'CLIENT test Info',
                'IsTouristTax' => 0,
                'PriceListID' => 39,
                'ContactName' => 'CONTACT NAME TEST',
            ];


//            return $data;
            $reserveRes = $this->postRequest('AddRoomReservation', $data);

//            $result['test'] = $reserveRes;
//            $result['data_fields'] = $data;
//            $result['popup_fields'] = $fields;

            if($reserveRes['Result'] !== 0)
            {
                $result['error'] = $reserveRes['Error'];
            }
            else
            {
                //запись Id резерва в поле сделки
                //закрытие формы и открытие окна с данными резерва
                //очистка формы и отображение резерва с кнопками продолжения или отмены

                $result['result'] = $reserveRes['Account'];
                $dealFields['UF_CRM_1592225883215'] = $reserveRes['Account'];
                $dealUdRes = $this->updateDeal($fields['DEAL_ID'],$dealFields);
                if($dealUdRes['errors']) $result['error'] = implode("\n ",$dealUdRes['errors']);
            }


        }
        else
        {
            $result['error'] = 'Ошибка при получении данных клиента в Б24!';
        }



        return $result;
    }


    /*телефоны для контакта/компании*/
    public function getPhonesAndEmails($entityString,$elemId,$valTypeArr = [])
    {
        \CModule::IncludeModule("crm"); //Какого хера??
        $result = [];
        $phonesAndEmailsFilter = [
            'ENTITY_ID'  => $entityString,
            'ELEMENT_ID' => $elemId,
            'TYPE_ID'    => $valTypeArr,
        ];
        $res = \CCrmFieldMulti::GetListEx([],$phonesAndEmailsFilter,false,[],['*']);
        while($ob = $res->fetch())
        {
            $result[$ob['TYPE_ID']][] = $ob;
        }
        return $result;
    }

    //скрипт обновления сделки
    public function updateDeal($id,$fields){

        \CModule::IncludeModule("crm"); //Какого хера??

        $result = [
            'result' => false,
            'errors' => [],
        ];
        $updResult = \Bitrix\Crm\DealTable::update($id,$fields);
        (!$updResult->isSuccess())
            ? $result['errors'] = $updResult->getErrorMessages()
            : $result['result'] = $updResult->getId();

        return $result;
    }

    public function getReserveData($id)
    {
        $data['Account'] = $id;
        return $this->postRequest('GetReservationInfo',$data);
    }

    public function logData($data){
        $file = $_SERVER["DOCUMENT_ROOT"].'/11111.log';
        file_put_contents($file, print_r([date('d.m.Y H:i:s'),$data],true), FILE_APPEND | LOCK_EX);
    }

}