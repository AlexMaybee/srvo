<?php
namespace Ourcompany\Servio;

use \Bitrix\Main\Page\Asset,
    \Bitrix\Main\Localization\Loc;


class Hotel
{
    private $eventInstance;
    private $settings;
    private $settingErrors;
//    private $payTypes = [100 => 'Наличные',200 => 'Кредитная Карта',300 => 'Безналичная Оплата'];
    private $payTypes = [];
    private $defaultValues = [
        'LOSE_STAGE_ID_NAME_PART' => 'LOSE',
    ];

    public function __construct()
    {
        $this->payTypes = [
            100 => Loc::getMessage("OUR_COMPANY_HOTEL_PAY_TYPE_CASH"),
            200 => Loc::getMessage("OUR_COMPANY_HOTEL_PAY_TYPE_CREDIT_CARD"),
            300 => Loc::getMessage("OUR_COMPANY_HOTEL_PAY_TYPE_CASHLESS")
        ];

        $this->eventInstance = new \Ourcompany\Servio\Event;
        $this->eventInstance->getSettings();

        $this->settings = \Ourcompany\Servio\Event::$settings;
        $this->settingErrors = \Ourcompany\Servio\Event::$settingErrors;

//        $this->logData('test_log123.log',[$this->settings,$this->settingErrors]);
    }

    public function getDealServioFields($dealId)
    {
        $result = [];
        $dealId = intval($dealId);
        if($dealId > 0)
        {
            \CModule::IncludeModule("crm"); //Какого хера??
            $dealRecord = \Bitrix\Crm\DealTable::getRow([
                'select' => [$this->settings['SERVIO_FIELD_RESERVE_ID'],$this->settings['SERVIO_FIELD_RESERVE_CONFIRM_FILE_ID'],$this->settings['SERVIO_FIELD_RESERVE_CONFIRM_FILE']], //  получаем строку с ID резерва
                'filter' => ['ID' => $dealId],
            ]);
            if($dealRecord)
            {
                $result = $dealRecord;
//                $result = (intval($dealRecord[$this->settings['SERVIO_FIELD_RESERVE_ID']]) > 0) ? intval($dealRecord[$this->settings['SERVIO_FIELD_RESERVE_ID']]) : 0;
            }
        }
        return $result;
    }


    // !!!НЕ ЗАБЫТЬ ПОЛУЧАТЬ ПОЛЯ С ID клиента из СЕРВИО для Компаний или контактов.
    public function getDealDataById($dealId)
    {
        $dealId = intval($dealId);
        if($dealId > 0)
        {
            \CModule::IncludeModule("crm"); //Какого хера??
            $dealRecord = \Bitrix\Crm\DealTable::getRow([
                'select' => [/*'*',$this->settings['SERVIO_FIELD_RESERVE_ID'],*/'ID','CONTACT_ID','COMPANY_ID','ASSIGNED_BY_ID','COMMENTS'], //  + получаем строку с ID резерва
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
                        'select' => ['*',$this->settings['SERVIO_FIELD_COMPANY_ADDRESS'],/*,'ID','TITLE','COMPANY_ID','ASSIGNED_BY_ID'*/], //  + получаем строку с ID резерва
                        'filter' => ['ID' => $dealRecord['COMPANY_ID']]
                    ]);
                    if($companyData)
                    {
                        if($dealRecord['CONTACT_ID'] <= 0 && $companyData['CONTACT_ID'] > 0)
                        {
                            $contactData = \Bitrix\Crm\CompanyTable::getRow([
                                'select' => ['*',$this->settings['SERVIO_FIELD_CONTACT_ADDRESS'],/*,'ID','TITLE','COMPANY_ID','ASSIGNED_BY_ID'*/], //  + получаем строку с ID резерва
                                'filter' => ['ID' => $companyData['CONTACT_ID']]
                            ]);
                            if($contactData)
                            {
                                $dealRecord['CONTACT_DATA'] = $contactData;

                                //почты и телефоны для контакта
                                $dealRecord['PHONES_AND_EMAILS'] = self::getPhonesAndEmails('CONTACT',$companyData['CONTACT_ID'],['EMAIL','PHONE']);
                            }
                        }
                        else
                        {
                            //почты и телефоны для компании
                            $dealRecord['PHONES_AND_EMAILS'] = self::getPhonesAndEmails('COMPANY',$companyData['ID'],['EMAIL','PHONE']);
                        }

                        $dealRecord['COMPANY_DATA'] = $companyData;
                    }
                }
                if($dealRecord['CONTACT_ID'] > 0)
                {
                    $contactData = \Bitrix\Crm\ContactTable::getRow([
                        'select' => ['*',$this->settings['SERVIO_FIELD_CONTACT_ADDRESS'],/*,'ID','TITLE','COMPANY_ID','ASSIGNED_BY_ID'*/], //  + получаем строку с ID резерва
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

//    public function getCategoriesWithRooms($post)
//    {
//        $result = [
//            'result' => false,
//            'error' => false,
//        ];
//
////        return $post;
//
//        //Получаем по датам категория + кол-во свободных номеров
//        $categoryRes = $this->getCategoriesByFilter($post);
//
////        $result['result']['test_all_exists_rooms'] = $categoryRes;
//
//        if(!$categoryRes['Result'] === 0)
//        {
//            $result['error'] = $categoryRes['Error'];
//        }
//        else
//        {
//            $data = ['HotelID' => 0];
//            $priceFilter = [];
//            $categoryNameRes = $this->postRequest('GetRoomTypesList',$data);
//
////            $result['result']['test_all_categories'] = $categoryNameRes;
//
//            if(!$categoryNameRes['Result'] === 0)
//            {
//                $result['error'] = $categoryNameRes['Error'];
//            }
//            else
//            {
//                $roomsData = [];//записываем массив категорий с данными по резевам, если есть свободные комнаты
//                foreach ($categoryRes['RoomTypes'] as $categoryArr)
//                {
//                    if($categoryArr['FreeRoom'] > 0)
//                    {
//                        $index = array_search($categoryArr['ID'],$categoryNameRes['IDs']);
//                        if($index !== false)
//                        {
//                            $priceFilter[] = $categoryNameRes['IDs'][$index];
//
//                            $roomsData[$categoryArr['ID']] = [
//                                'FreeRoom' => $categoryArr['FreeRoom'],
//                                'MainPlacesCount' => $categoryArr['MainPlacesCount'],
//                                'NearestDateToReservation' => $categoryArr['NearestDateToReservation'],
//                            ];
//
////                            $result['result']['rooms_test'][] = [
////                                'Id' => $categoryNameRes['IDs'][$index],
////                                'HotelId' => $categoryNameRes['HotelIDs'][$index],
////                                'CategoryName' => $categoryNameRes['ClassNames'][$index],
////                                'FreeRoom'  => $categoryArr['FreeRoom'],
////                                'MainPlacesCount'  => $categoryArr['MainPlacesCount'],
////                                'NearestDateToReservation'  => $categoryArr['NearestDateToReservation'],
////                            ];
//                        }
//                    }
//                }
//
//                if(!$priceFilter)
//                {
//                    $result['error'] = 'Нет свободных комнат!';
//                }
//                else
//                {
//
//                    //получение цен по каждой категории
//                    $categoryPriceArr = $this->getPriceByCategories($post,$priceFilter);
//
////                    $result['result']['test_all_prices'] = $categoryNameRes;
//
////                $this->logData($categoryPriceArr);
//
//                    //переформатирование массива
//                    $newArr = [];
//
//                    if($categoryPriceArr['Result'] !== 0)
//                    {
//                        $result['error'] = $categoryPriceArr['Error'];
//                    }
//                    else
//                    {
//
////                    $result['result']['priceLists'] =
////                        [
////                            'ValuteShort' => $categoryPriceArr['ValuteShort'],
////                            'PriceLists' => $categoryPriceArr['PriceLists'],
////                        ];
//
//
//
//                        foreach ($categoryPriceArr['PriceLists'] as $priceList)
//                        {
//                            foreach ($priceList['RoomTypes'] as $roomType)
//                            {
//                                foreach ($roomType['Services'] as $servise)
//                                {
//
//                                    $serviseArr[$servise['ServiceID']] = [
//                                        'serviseName' => $servise['ServiceName'],
//                                        'serviseTypeName' => $servise['ServiceTypeName'],
//                                        'serviseSumArr' => []
//                                    ];
//
//                                    foreach ($servise['PriceDates'] as $priceDate)
//                                    {
//                                        $serviseArr[$servise['ServiceID']]['serviseSumArr'][] = $priceDate['Price'];
//                                    }
//
//                                    $dateFrom = date('d.m.Y',strtotime($post['dateFrom']));
//                                    $dateTo = date('d.m.Y',strtotime($post['dateTo']));
//                                    $dates = "{$dateFrom} - {$dateTo}";
//                                    if(
//                                        date('n',strtotime($post['dateFrom'])) === date('n',strtotime($post['dateTo']))
//                                        &&
//                                        date('Y',strtotime($post['dateFrom'])) === date('Y',strtotime($post['dateTo']))
//                                    )
//                                    {
//                                        $dates = date('d',strtotime($post['dateFrom'])).' - '.date('d.m.Y',strtotime($post['dateTo']));
//                                    }
//                                    elseif(
//                                        date('n',strtotime($post['dateFrom'])) !== date('n',strtotime($post['dateTo']))
//                                        &&
//                                        date('Y',strtotime($post['dateFrom'])) === date('Y',strtotime($post['dateTo']))
//                                    )
//                                    {
//                                        $dates = date('d.m',strtotime($post['dateFrom'])).' - '.date('d.m.Y',strtotime($post['dateTo']));
//                                    }
//
//
//                                    $newArr[] = [
//                                        'priceId' => $priceList['PriceListID'],
//                                        'IsNonReturnRate' => $priceList['IsNonReturnRate'],
//                                        'IsSpecRate' => $priceList['IsSpecRate'],
//                                        'totalDays' => count($servise['PriceDates']),
//                                        'servises' =>  $serviseArr,
//                                        'totalPrice' => '',
//                                        'dates' => "{$dates}", //??? правильно???
//                                        'currency' => $categoryPriceArr['ValuteShort'],
//                                        'roomTypeId' => $roomType['ID'],
//                                        'roomTypeName' => '',
//                                        'HotelId' => 0,
//                                        'FreeRoom'  => 0,
//                                        'MainPlacesCount'  => 0,
//                                        'NearestDateToReservation'  => '',
//                                    ];
//
//                                }
//                            }
//                        }
//
//                        if($newArr)
//                        {
//                            foreach ($newArr as $roomTypeS)
//                            {
//                                foreach ($roomTypeS['servises'] as $id => $servise)
//                                {
//                                    $servise['sum'] = array_sum($servise['serviseSumArr']);
//                                    $roomTypeS['servises'][$id] = $servise;
//                                    $roomTypeS['totalPrice'] += $servise['sum'];
//                                }
//
//                                $indexN = array_search($roomTypeS['roomTypeId'],$categoryNameRes['IDs']);
//                                if($indexN !== false)
//                                {
//                                    $roomTypeS['roomTypeName'] = $categoryNameRes['ClassNames'][$indexN];
//                                    $roomTypeS['HotelId'] = $categoryNameRes['HotelIDs'][$indexN];
//
//                                }
//
//                                if(in_array($roomTypeS['roomTypeId'],array_keys($roomsData)))
//                                {
//                                    $roomTypeS['FreeRoom'] = $roomsData[$roomTypeS['roomTypeId']]['FreeRoom'];
//                                    $roomTypeS['MainPlacesCount'] = $roomsData[$roomTypeS['roomTypeId']]['MainPlacesCount'];
//                                    $roomTypeS['NearestDateToReservation'] = $roomsData[$roomTypeS['roomTypeId']]['NearestDateToReservation'];
//                                }
//
////                                $result['result']['priceLists'][$roomTypeS['roomTypeId']] = $roomTypeS;
//                                $result['result']['rooms'][$roomTypeS['roomTypeId']] = $roomTypeS;
//                            }
//                        }
//
////                        $result['result']['test'] = $categoryPriceArr;
//                    }
//
//                }
//
//
//
////                $result['result']['post'] = $post;
////                $result['result']['test123'] = $newArr;
//            }
//        }
//        return $result;
//    }





    /*********** 22.06.2020**********/

    //get Prices Last - 22/06/2020
    public function getPricesByFilter($post,$dealId)
    {
        $result = [
            'errors' => [],
            'rooms' => [],
            'prices' => [],
            'names' => [],
            'table' => [],
            'fields' => [
                'COMMENTS' => '',
                'ADDRESS' => '',
            ],
            'contractConditions' => [],
        ];


        $dealData = $this->getDealDataById($dealId);

//        return $dealData;

        if(!$dealData)
        {
            $result['errors'][] = "Не найдена сделка #{$dealId}";
        }
        else
        {
            //берем коммент из сделки и адрес из компании/контакта
            $result['fields']['COMMENTS'] = $dealData['COMMENTS'];

            if($dealData['CONTACT_DATA'] && $dealData['CONTACT_DATA']['UF_CRM_HMS_CONTACT_ADDRESS'])
            {
                $result['fields']['ADDRESS'] = $dealData['CONTACT_DATA']['UF_CRM_HMS_CONTACT_ADDRESS'];
            }
            elseif($dealData['COMPANY_DATA'] && $dealData['COMPANY_DATA']['UF_CRM_HMS_COMPANY_ADDRESS'])
            {
                $result['fields']['ADDRESS'] = $dealData['COMPANY_DATA']['UF_CRM_HMS_COMPANY_ADDRESS'];
            }




        }

//        return [$dealId,$post];
//        return $result;

       $childAgerResArr = [];
        if($post['childAges'])
        {
            foreach (explode(' ',$post['childAges']) as $age)
            {
                $childAgerResArr[] = intval($age);
            }
        }
        $post['childAges'] = $childAgerResArr;

        //названия комнат
        $roomCatNames = $this->getRoomsCategoriesNames($post);
        if($roomCatNames['Error'])
        {
            $result['errors'][] = $roomCatNames['Error'];
        }


        //комнаты по фильтру
        $roomsData = $this->getCategoriesByFilter($post);
        if($roomsData['Error'])
        {
            $result['errors'][] = $roomsData['Error'];
        }
        else
        {
            $result['contractConditions'] = $roomsData['ContractConditions'];

            $roomsCatFilter = [];
            foreach ($roomsData['RoomTypes'] as $roomCategory)
            {
                //НЕ БЕРЕМ категории, где комнаты должны только освободиться!!!
//                if($roomCategory['FreeRoom'] > 0)
//                {
                    $roomsCatFilter[] = $roomCategory['ID'];
//                }
            }

            $result['test_cat_filter_for_price'] = $roomsCatFilter;

            //цены комнат
            $roomsPrices = $this->getPrices($post,$roomsCatFilter);
            if($roomsPrices['Error'])
            {
                $result['errors'][] = $roomsPrices['Error'];
            }
            else
            {

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

//                $result['test_table'] = $this->makePriceTable($roomCatNames,$roomsData,$roomsPrices);
                $result['table'] = $this->makePriceTable($roomCatNames,$roomsData,$roomsPrices,$dates);
            }

            $result['test_Prices'] = $roomsPrices;

        }



        $result['test_rooms'] = $roomsData;
        $result['test_rooms_names'] = $roomCatNames;



        return $result;
    }

    //upd 22.06.2020
    private function getCategoriesByFilter($post)
    {
        $data = [
            'CompanyCodeID' => intval($post['companyCodeId']),
            'HotelID' => intval($post['hootelId']),
            'DateArrival' => $post['dateFrom'],
            'DateDeparture' => $post['dateTo'],
            'ChildAges' => $post['childAges'],
            'Adults' => intval($post['adults']),
            'Childs' => intval(post['childs']),
            'IsExtraBedUsed' => boolval($post['extraBed']),
            'IsoLanguage'  => 'ru',
            'TimeArrival' =>  '', //пока константы
            'TimeDeparture' =>  '',  //пока константы
        ];

//        return $data;
        return $res = $this->postRequest('GetRooms',$data);
    }

    //new Get Categories names 22.06.2020
    private function getRoomsCategoriesNames($post)
    {
        $data['HotelID'] = $post['hootelId'];
        return $res = $this->postRequest('GetRoomTypesList',$data);
    }

    //get prices 22.06.2020
    private function getPrices($post,$categoriesArr)
    {
        $data = [
            'HotelID' => $post['hootelId'],
            'CompanyID' => $post['companyId'],
            'DateArrival' => $post['dateFrom'],
            'DateDeparture' => $post['dateTo'],
            'TimeArrival' =>  '',
            'TimeDeparture' =>  '',
            'Adults' => $post['adults'],
            'Childs' => $post['childs'],
            'ChildAges' => $post['childAges'],
            'IsExtraBedUsed' => $post['extraBed'],
            'IsoLanguage'  => 'ru',
            'RoomTypeIDs' => $categoriesArr, // [1,2,3,5],
            'СontractConditionID' => 0,
            'PaidType' => $post['paidType'],
            'NeedTransport' => $post['transport'],
            'IsTouristTax' => $post['touristTax'],
            'LPAuthCode' => '',
        ];

//        return $data;
//        if($post['companyId'])
//        {
//            $data['CompanyID'] = $post['companyId'];
//        }

        return $this->postRequest('GetPrices',$data);
    }



    /*********** 22.06.2020**********/



    /**********  01.07.2020**********/
    private function makePriceTable($roomCatNames,$roomsData,$roomsPrices,$dates)
    {
        $result = [];
        $arr = [];

        if($roomsPrices)
        {
            foreach ($roomsData['RoomTypes'] as $roomCategory)
            {
//            if($roomCategory['FreeRoom'] > 0)
//            {
                $index = array_search($roomCategory['ID'],$roomCatNames['IDs']);
                if($index !== false)
                {
                    $roomCategory['CategoryName'] = $roomCatNames['ClassNames'][$index];
                    $roomCategory['HotelId'] = $roomCatNames['HotelIDs'][$index];
                    $roomCategory['Currency'] = $roomsPrices['ValuteShort'];

                    $arr[$roomCategory['ID']] = $roomCategory;
                }
//            }
            }


            //новый массив по дням
            foreach ($roomsPrices['PriceLists'] as $priceList)
            {
                foreach ($priceList['RoomTypes'] as $roomCategory)
                {
//                    $roomCategory['NearestDateToReservation'] = date('d.m.Y',strtotime($roomCategory['NearestDateToReservation']));

                    foreach ($roomCategory['Services'] as $service)
                    {
                        foreach ($service['PriceDates'] as $roomDate)
                        {
                            $arr[$roomCategory['ID']]['NearestDateToReservation'] = date('d.m.Y',strtotime($arr[$roomCategory['ID']]['NearestDateToReservation']));

                            $arr[$roomCategory['ID']]['PriceListId'] = $priceList['PriceListID'];
//                            $arr[$roomCategory['ID']]['Date'] = $roomDate['Date'];
                            $arr[$roomCategory['ID']]['Date'] = $dates;
                            $arr[$roomCategory['ID']]['Price'] += $roomDate['Price'];
                            $arr[$roomCategory['ID']]['MinPayDays'] += $roomCategory['SaleRestrictions']['MinPay']['Days'];
                            $arr[$roomCategory['ID']]['MinStayDays'] += $roomCategory['SaleRestrictions']['MinStay']['Days'];
                        }
                    }
                }
            }

            $result = $arr;
        }

        return $result;
    }

    /**********  01.07.2020**********/




    //get prices
//    public function getPriceByCategories($post,$categoryIds)
//    {
//        $data = [
//            'HotelID' => 1,
//            'CompanyID' => $post['companyId'],
//            'DateArrival' => $post['dateFrom'],
//            'DateDeparture' => $post['dateTo'],
//            'TimeArrival' =>  '',
//            'TimeDeparture' =>  '',
//            'Adults' => $post['adults'],
//            'Childs' => $post['childs'],
//            'ChildAges' => [],
//            'IsExtraBedUsed' => false,
//            'IsoLanguage'  => 'ru',
//            'RoomTypeIDs' => $categoryIds, // [1,2,3,5],
//            'СontractConditionID' => 0,
//            'PaidType' => 100,
//            'PaidType' => $post['paidType'],
//            'NeedTransport' => 0,
////            'IsTouristTax' => 0,
//            'LPAuthCode' => '',
//        ];
//
//        if($post['companyId'])
//        {
//            $data['CompanyID'] = $post['companyId'];
//        }
//
//        return $this->postRequest('GetPrices',$data);
//    }


    //создание резерва
    public function addReserve($fields)
    {

//        return $fields;

        $result = [
            'result' => false,
            'error' => false,
        ];

        $dealData = $this->getDealDataById($fields['DEAL_ID']);

//        return $dealData;

        if($dealData)
        {

            if(!$dealData['COMPANY_DATA']  &&  !$dealData['CONTACT_DATA'])
            {
                $result['error'] = 'В сделке не выбран контакт/компания';
            }
            else
            {

//                $company = 'STATIC TEST COMPANY';
                $clientName = '';
                $clientLastName = '';


                $company = ($fields['FILTERS']['companyName']) ? $fields['FILTERS']['companyName'] : 'STATIC TEST COMPANY';
                $address = ($fields['FILTERS']['address']) ? $fields['FILTERS']['address'] : '';
                $comment = ($fields['FILTERS']['comment']) ? $fields['FILTERS']['comment'] : '';


                $phone = '';
                $email = '';
                $contactName = '';

                if($dealData['COMPANY_DATA'])
                {
                    $clientName = $dealData['COMPANY_DATA']['TITLE'];
                    $clientLastName = $dealData['COMPANY_DATA']['TITLE'];
//                  $address  = $dealData['COMPANY_DATA']['ADDRESS'];
//                   $comment = $dealData['COMPANY_DATA']['TITLE'];
//                   $company = $dealData['COMPANY_DATA']['TITLE'];
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
                    'Address' => trim($address),
                    'Adults' => $fields['FILTERS']['adults'],
                    'ChildAges' => [], //$fields['FILTERS'][''] //это будет в popup
                    'Childs' => $fields['FILTERS']['childs'],
                    'ClientInfo' => $_SERVER['REMOTE_ADDR'],
                    'Comment' => $comment,
                    'Company' => $company,  //НАФИГА???!!!
                    'CompanyID' => $fields['FILTERS']['companyId'], //113
                    'ContactName' => $contactName,
                    'ContractConditionID' => 1, //это брать где-то...
                    'Country' => 'Ukraine',
                    'DateArrival' => $fields['FILTERS']['dateFrom'],
                    'DateDeparture' => $fields['FILTERS']['dateTo'],
                    'Fax' => '',
                    'GuestLastName' => $clientName,
                    'GuestFirstName' => $clientLastName,
                    'HotelID' => intval($fields['ROOM_CATEGORY']['HotelId']),
                    'IsExtraBedUsed' => (bool)$fields['FILTERS']['extraBed'],
                    'IsTouristTax' => intval($fields['FILTERS']['touristTax']), //это будет в popup
                    'Iso3Country' => 'UKR',
                    'NeedTransport' => intval($fields['FILTERS']['transport']), //это будет в popup
                    'PaidType' => intval($fields['FILTERS']['paidType']),  //это будет в popup
                    'Phone' => trim($phone),
                    'PriceListID' => intval($fields['ROOM_CATEGORY']['PriceListId']),
                    'RoomTypeID' => intval($fields['FILTERS']['roomCategory']),
                    'TimeArrival' => '', //пока константы
                    'TimeDeparture' => '',  //пока константы
                    'eMail' => $email,
                ];

//                return $data;

                $reserveRes = $this->postRequest('AddRoomReservation', $data);

//                return $reserveRes;

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
                    $dealFields[$this->settings['SERVIO_FIELD_RESERVE_ID']] = $reserveRes['Account'];

                    //Сохранение коммента в сделку
                    if($comment)
                    {
                        $dealFields['COMMENTS'] = $comment;
                    }

                    $dealUdRes = $this->updateDeal($fields['DEAL_ID'],$dealFields);
                    if($dealUdRes['errors']) $result['error'] = implode("\n ",$dealUdRes['errors']);

                    //обновление адреса в  компании или контакте
                    if($address)
                    {
                        if($dealData['CONTACT_DATA'])
                        {

                            $contactAddr = $dealData['CONTACT_DATA'][$this->settings['SERVIO_FIELD_CONTACT_ADDRESS']];
                            if($contactAddr)
                            {
                                $contactAddr = "$contactAddr\n".date('d.m.Y H:i').": $address";
                            }
                            else
                            {
                                $contactAddr = $address;
                            }
                            $addressUpdate = \Bitrix\Crm\ContactTable::update($dealData['CONTACT_DATA']['ID'],
                                [$this->settings['SERVIO_FIELD_CONTACT_ADDRESS'] => trim($contactAddr)]);

                        }
                        else
                        {
                           if($dealData['COMPANY_DATA'])
                           {
                               $companyAddr = $dealData['COMPANY_DATA'][$this->settings['SERVIO_FIELD_COMPANY_ADDRESS']];
                               if($companyAddr)
                               {
                                   $companyAddr = "$companyAddr\n".date('d.m.Y H:i').": $address";
                               }
                               else
                               {
                                   $companyAddr = $address;
                               }
                               $addressUpdate = \Bitrix\Crm\CompanyTable::update($dealData['COMPANY_DATA']['ID'],
                                   [$this->settings['SERVIO_FIELD_COMPANY_ADDRESS'] => trim($companyAddr)]);
                           }

                        }
                    }

                }

            }

//            $this->logData(['Address Upd',$addressUpdate]);
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
        $result = [
            'result' => [],
            'error' => '',
        ];
        $data['Account'] = intval($id);
        $reserveData = $this->postRequest('GetReservationInfo',$data);
        if($reserveData['Result'] !== 0)
        {
            $result['error'] = $reserveData['Error'];
        }
        else
        {
            unset($reserveData['Error']);
            unset($reserveData['ErrorCode']);
            unset($reserveData['Result']);
            $reserveData['PaidTypeText'] = $this->payTypes[$reserveData['PaidType']];
            $reserveData['DateArrival'] = date('d.m.Y H:i',strtotime($reserveData['DateArrival']));
            $reserveData['DateDeparture'] = date('d.m.Y H:i',strtotime($reserveData['DateDeparture']));


            $servicesResultArray = [];
            $servisesArr = []; //для формирования массива услуг в th

            if($reserveData['Services'])
            {
                foreach ($reserveData['Services'] as $service)
                {
                    $servisesArr[] = [
                        'ServiceID' => $service['ServiceID'],
                        'ServiceName' => $service['ServiceName'],
                        'ServiceTypeName' => $service['ServiceTypeName']
                    ];

                    foreach ($service['PriceDate'] as $oneDate) {

                        $rDate = date('d.m.Y',strtotime($oneDate['Date']));

                        $servicesResultArray[$rDate]['Price'] += $oneDate['Price'];
                        $servicesResultArray[$rDate]['CustomerAccount'] = $oneDate['CustomerAccount'];
                        $servicesResultArray[$rDate]['Date'] = date('d.m.Y',strtotime($oneDate['Date']));
                        $servicesResultArray[$rDate]['IsPaid'] = $oneDate['IsPaid'];
                        $servicesResultArray[$rDate]['IsPaidFromSite'] = $oneDate['IsPaidFromSite'];
                        $servicesResultArray[$rDate]['OrderID'] = $oneDate['OrderID'];
                        $servicesResultArray[$rDate]['PayAccount'] = $oneDate['PayAccount'];
                        $servicesResultArray[$rDate]['ServiceProviderID'] = $oneDate['ServiceProviderID'];
                        $servicesResultArray[$rDate]['ServiceProviderName'] = $oneDate['ServiceProviderName'];
                        $servicesResultArray[$rDate]['ServicePrices'][] = $oneDate['Price'];
                    }
                }
            }

            //th
            $reserveData['ThServises'] = $servisesArr;
            //Formated Servises Prices By Dates
            $reserveData['ResultServises'] = array_values($servicesResultArray);

            $result['result'] = $reserveData;
        }
        return $result;
    }

    public function logData($filename,$data){
        $file = $_SERVER["DOCUMENT_ROOT"].'/'.$filename;
        file_put_contents($file, print_r([date('d.m.Y H:i:s'),$data],true), FILE_APPEND | LOCK_EX);
    }

//    public function logData_Make($filename,$content)
//    {
//        $file = $_SERVER["DOCUMENT_ROOT"].'/'.$filename;
//        file_put_contents($file, print_r($content,true));
//    }

    //подтверждение брони
    public function confirmReserve($fields)
    {
        $result = [
            'result' => false,
            'error' => false,
        ];

        $data = [
            'Account' => $fields['reserveId'],
            'IsoLanguage'  => 'ru',
//            'Format' => 0,
            'Format' => $this->settings['SERVIO_RESERVE_CONFIRM_FILE_FORMAT'],
        ];

        $confirmResult = $this->postRequest('GetAccountConfirm',$data);
        if($confirmResult['Result'] !== 0)
        {
            $result['error'] = $confirmResult['Error'];
        }
        else
        {

            $updDealRes = $this->updateDeal($fields['id'],[$this->settings['SERVIO_FIELD_RESERVE_CONFIRM_FILE_ID'] => $confirmResult['DocumentID']]);
            if(!$updDealRes)
            {
                $result['error']  = Loc::getMessage('OUR_COMPANY_HOTEL_CONFIRM_FILE_ID_DEAL_ERROR');
            }
            else
            {
//                sleep(3);

                $fileResult = $this->getDocument($confirmResult['DocumentID'],'servio_confirm_archive');


//            return $fileResult;


                if(!$fileResult['result'])
                {
                    $result['error'] = $fileResult['error'];
                }
                else
                {
                    //получили файл в массиве по типу $_FILES
                    $fileArr = $fileResult['result'];

                    $updDealRes = $this->updateDeal($fields['id'],[$this->settings['SERVIO_FIELD_RESERVE_CONFIRM_FILE'] => $fileArr]);
                    if(!$updDealRes)
                    {
//                    $result['error']  = 'Ошибка при обновлении сделки!';
                        $result['error']  = Loc::getMessage('OUR_COMPANY_HOTEL_CONFIRM_FILE_DEAL_ERROR');
                    }
                    else
                    {
                        $result['result'] = $updDealRes;
                    }
                    unlink($_SERVER['DOCUMENT_ROOT'].'/'.$fileArr['tmp_name']);
                }
            }

//            $result['result'] = $confirmResult;
        }

        return $result;
    }

    //получение счета для резерва
    public function getBillForReserve($fields)
    {
        $result = [
            'result' => [],
            'error' => false,
        ];

        $data = [
            'Account' => $fields['reserveId'],
            'IsoLanguage'  => 'ru',
//            'Format' => 1,
            'Format' => $this->settings['SERVIO_RESERVE_CONFIRM_FILE_FORMAT'],
        ];

        $billDataResult = $this->postRequest('GetAccountBill',$data);

        if($billDataResult['Result'] !== 0)
        {
            $result['error'] = $billDataResult['Error'];
        }
        else
        {
            //записываем ID документа счета в сделку и получаем документ
            $result['result'] = $billDataResult;

            //получение документа
        }

        return $result;
    }


    //возврат массива файла по типу $_FILES[]
    public function getDocument($fileId,$myFileName)
    {
        $result = [
            'result' => [],
            'error' => false,
        ];

        $data = [
            'DocumentID' => $fileId
        ];

        $docRes = $this->postRequest('GetDocument',$data);

//        $file = base64_decode($docRes['DocumentCode']);

        $docRes['D_ID'] = $fileId;
//        return $docRes;

        if($docRes['Result'] !== 0)
        {
            $result['error'] = $docRes['Error'];
        }
        else
        {
            if(!$docRes['IsReady'])
            {
                $result['error'] = 'Файл еще не готов! Попробуйте позже';
            }
            else
            {
//                $fileName = $_SERVER['DOCUMENT_ROOT'].'/servio_archive_'.strtotime('now').'.zip';
                $fileName = $_SERVER['DOCUMENT_ROOT'].'/'.$myFileName.'_'.strtotime('now').'.zip';
//            $fileName = 'FFFFF_'.strtotime('now').'.zip';


                $fileCreate = file_put_contents($fileName,base64_decode($docRes['DocumentCode']));

//            return $fileName;
                if($fileCreate)
                {
                    $result['result'] = \CFile::MakeFileArray($fileName);

                }
                else
                {
                    $result['error'] = 'Ошибка при создании временного файла архива';
                }
            }
        }
        return $result;
    }

    public function abortReserve($fields)
    {
        $result = [
            'result' => false,
            'error' => false,
        ];

//        return $fields;

        $data['Account'] = $fields['reserveId'];

        $reserveAbortResult = $this->postRequest('CancelReservation',$data);
        if($reserveAbortResult['Result'] !== 0)
        {
            $result['error'] = $reserveAbortResult['Error'];
        }
        else
        {
            //получаем направление (CATEGORY_ID) на проигрыш
            //учистим поля: id резерва ...

            \CModule::IncludeModule("crm"); //Какого хера??
            $dealData = \Bitrix\Crm\DealTable::getRow([
                'select' => [/*'*',$this->settings['SERVIO_FIELD_RESERVE_ID'],*/'ID','CATEGORY_ID'],
                'filter' => ['ID' => intval($fields['id'])],
            ]);

            $result['test'] = $dealData;

            if(!$dealData)
            {
                $result['error'] = "Не найдена сделка #{$fields['id']}";
            }
            else
            {
                //определение стадии пригрыша - стандарт от Б24 = C{CATEGORY_ID}:LOSE или LOSE (CATEGORY_ID === 0)
                $loseStageId = (intval($dealData['CATEGORY_ID']) === 0)
                    ? $this->defaultValues['LOSE_STAGE_ID_NAME_PART']
                    : "C{$dealData['CATEGORY_ID']}:{$this->defaultValues['LOSE_STAGE_ID_NAME_PART']}";

                $updDealFields = [
                    'STAGE_ID' => $loseStageId,
                    $this->settings['SERVIO_FIELD_RESERVE_ID'] => ''
                ];

                $dealUpdRes = $this->updateDeal($fields['id'],$updDealFields);
                if($dealUpdRes['errors'])
                {
                    $result['error'] = implode("\n ",$dealUpdRes['errors']);
                }
                else
                {
                    $result['result'] = $dealUpdRes['result'];
                }


                //обновление полей сделки

//                $result['test_update_fields'] = $updDealFields;
//                $result['test_update_res'] = $dealUpdRes;

            }

        }

        return $result;
//        return $reserveAbortResult;
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
                'Content-Type: application/json; charset=utf-8',
//                'Content-Length: ' . strlen($data_string),
                'AccessToken: '.$this->settings['SERVIO_REST_KEY']
            )
        );
        $result = curl_exec($curl);
        curl_close($curl);

        return json_decode($result,true);
    }

}