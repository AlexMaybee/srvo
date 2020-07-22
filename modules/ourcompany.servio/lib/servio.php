<?php
namespace Ourcompany\Servio;

use \Bitrix\Main\Page\Asset,
    \Bitrix\Main\Localization\Loc;

class Servio
{

    //данные нашей компании при загрузке
    public function companyData($settings)
    {
        $result = [
            'result' => false,
            'error' => false,
        ];

        $companyInfoRes = (new \Ourcompany\Servio\Work\Request)->postRequest('GetCompanyInfo', ['CompanyCode' => $settings['success']['SERVIO_COMPANY_CODE'],], $settings);

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

    //условия договоров + типы оплаты в селекты
    public function contractConditions($fields,$settings,$payTypes)
    {
        $result = [
            'result' => false,
            'error' => false,
        ];

        $data = [
            'CompanyCodeID' => intval($fields['companyCodeId']),
            'HotelID' => intval($fields['hotelId']),
            'DateArrival' => $fields['dateFrom'],
            'DateDeparture' => $fields['dateTo'],
            'ChildAges' => $fields['childAges'],
            'Adults' => intval($fields['adults']),
            'Childs' => intval($fields['childs']),
            'IsExtraBedUsed' => boolval($fields['extraBed']),
            'IsoLanguage'  => 'ru',
            'TimeArrival' =>  '', //пока константы
            'TimeDeparture' =>  '',  //пока константы
        ];

        $requestResult = (new \Ourcompany\Servio\Work\Request)->postRequest('GetRooms', $data, $settings);
        if($requestResult['Error'])
        {
            $result['error'] = $requestResult['Error'];
        }
        else
        {
            unset($requestResult['Error']);
            unset($requestResult['ErrorCode']);
            unset($requestResult['Result']);

            //заносим текст типов оплат
            if($requestResult['ContractConditions'])
            {
                foreach ($requestResult['ContractConditions'] as $cC)
                {
                    foreach ($cC['PaidTypes'] as $payType)
                    {
                        $requestResult['PayTypes'][$cC['ContractConditionID']][] = ['ID' => $payType, 'NAME' => $payTypes[$payType]];
                    }
                }
            }
            $result['result'] = $requestResult;
        }

        return $result;
    }

    //цены
    public function pricesByFilter($fields,$dealId,$settings)
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
        ];

        $dealResut = (new \Ourcompany\Servio\Deal)->getDealAndClientShort($dealId,$settings);
        if($dealResut['errors'])
        {
            $result['errors'] = array_merge($result['errors'],$dealResut['errors']);
        }
        else
        {
            $dealData = $dealResut['result'];

            //берем коммент из сделки и адрес из компании/контакта
            $result['fields']['COMMENTS'] = $dealData['COMMENTS'];

            if($dealData['CONTACT_DATA'] && $dealData['CONTACT_DATA'][$settings['success']['SERVIO_FIELD_CONTACT_ADDRESS']])
            {
                $result['fields']['ADDRESS'] = $dealData['CONTACT_DATA'][$settings['success']['SERVIO_FIELD_CONTACT_ADDRESS']];
            }
            elseif($dealData['COMPANY_DATA'] && $dealData['COMPANY_DATA'][$settings['success']['SERVIO_FIELD_COMPANY_ADDRESS']])
            {
                $result['fields']['ADDRESS'] = $dealData['COMPANY_DATA'][$settings['success']['SERVIO_FIELD_COMPANY_ADDRESS']];
            }
        }

        $childAgerResArr = [];
        if($fields['childAges'])
        {
            foreach (explode(' ',$fields['childAges']) as $age)
            {
                $childAgerResArr[] = intval($age);
            }
        }
        $fields['childAges'] = $childAgerResArr;

        //названия комнат
        $roomCatNames = (new \Ourcompany\Servio\Work\Request)->postRequest('GetRoomTypesList', ['HotelID' => $fields['hotelId']], $settings);
        if($roomCatNames['Error'])
        {
            $result['errors'][] = $roomCatNames['Error'];
        }
        else
        {
            $roomsData = (new \Ourcompany\Servio\Work\Request)->postRequest('GetRooms',
                [
                    'CompanyCodeID' => intval($fields['companyCodeId']),
                    'HotelID' => intval($fields['hotelId']),
                    'DateArrival' => $fields['dateFrom'],
                    'DateDeparture' => $fields['dateTo'],
                    'ChildAges' => $fields['childAges'],
                    'Adults' => intval($fields['adults']),
                    'Childs' => intval($fields['childs']),
                    'IsExtraBedUsed' => boolval($fields['extraBed']),
                    'IsoLanguage'  => 'ru',
                    'TimeArrival' =>  '', //пока константы
                    'TimeDeparture' =>  '',  //пока константы
                ]
                , $settings);


            if($roomsData['Error'])
            {
                $result['errors'][] = $roomsData['Error'];
            }
            else
            {
                $roomsCatFilter = [];

                foreach ($roomsData['RoomTypes'] as $roomCategory)
                {
                    //НЕ БЕРЕМ категории, где комнаты должны только освободиться!!!
//                if($roomCategory['FreeRoom'] > 0)
//                {
                    $roomsCatFilter[] = $roomCategory['ID'];
//                }
                }

                //цены комнат
                $roomsPrices = (new \Ourcompany\Servio\Work\Request)->postRequest('GetPrices',
                    [
                        'HotelID' => intval($fields['hotelId']),
                        'CompanyID' => intval($fields['companyId']),
                        'DateArrival' => $fields['dateFrom'],
                        'DateDeparture' => $fields['dateTo'],
                        'TimeArrival' =>  '',
                        'TimeDeparture' =>  '',
                        'Adults' => intval($fields['adults']),
                        'Childs' => intval($fields['childs']),
                        'ChildAges' => $fields['childAges'],
                        'IsExtraBedUsed' => boolval($fields['extraBed']),
                        'IsoLanguage'  => 'ru',
                        'RoomTypeIDs' => $roomsCatFilter, // [1,2,3,5],
                        'СontractConditionID' => 0,
                        'PaidType' => $fields['paidType'],
                        'NeedTransport' => $fields['transport'],
                        'IsTouristTax' => $fields['touristTax'],
                        'LPAuthCode' => '',
                    ]
                    , $settings);

                if($roomsPrices['Error'])
                {
                    $result['errors'][] = $roomsPrices['Error'];
                }
                else
                {
                    $dateFrom = date('d.m.Y',strtotime($fields['dateFrom']));
                    $dateTo = date('d.m.Y',strtotime($fields['dateTo']));
                    $dates = "{$dateFrom} - {$dateTo}";

                    if(
                        date('n',strtotime($fields['dateFrom'])) === date('n',strtotime($fields['dateTo']))
                        &&
                        date('Y',strtotime($fields['dateFrom'])) === date('Y',strtotime($fields['dateTo']))
                    )
                    {
                        $dates = date('d',strtotime($fields['dateFrom'])).' - '.date('d.m.Y',strtotime($fields['dateTo']));
                    }
                    elseif(
                        date('n',strtotime($fields['dateFrom'])) !== date('n',strtotime($fields['dateTo']))
                        &&
                        date('Y',strtotime($fields['dateFrom'])) === date('Y',strtotime($fields['dateTo']))
                    )
                    {
                        $dates = date('d.m',strtotime($fields['dateFrom'])).' - '.date('d.m.Y',strtotime($fields['dateTo']));
                    }

                    $result['table'] = $this->makePriceTable($roomCatNames,$roomsData,$roomsPrices,$dates);
                }
            }
        }
        return $result;
    }

    //преобразование массива для вывода в таблицу
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


    //создание резерва с 16.07.2020
    public function addReserve($fields,$settings)
    {
        $result = [
            'result' => false,
            'errors' => [],
        ];

        if($settings['errors'])
        {
            $result['errors'] = $settings['errors'];
        }
        else
        {
            $dealResut = (new \Ourcompany\Servio\Deal)->getDealAndClientData($fields['DEAL_ID'],$settings);

            $result['test_deal_data'] = $dealResut;

            if($dealResut['errors'])
            {
//            $result['errors'] = array_merge($result['errors'],$dealResut['errors']);
                $result['errors'] = $dealResut['errors'];
            }
            else
            {
                $dealData = $dealResut['result'];

                $clientName = '';
                $clientLastName = '';

                $company = ($fields['FILTERS']['companyName']) ? $fields['FILTERS']['companyName'] : '';
                $address = ($fields['FILTERS']['address']) ? $fields['FILTERS']['address'] : '';
                $comment = ($fields['FILTERS']['comment']) ? $fields['FILTERS']['comment'] : '';

                $phone = '';
                $email = '';
                $contactName = '';

                if($dealData['COMPANY_DATA'])
                {
                    $clientName = $dealData['COMPANY_DATA']['TITLE'];
                    $clientLastName = $dealData['COMPANY_DATA']['TITLE'];
                }
                if($dealData['CONTACT_DATA']){
                    $clientName = $dealData['CONTACT_DATA']['NAME'];
                    $clientLastName = $dealData['CONTACT_DATA']['LAST_NAME'];
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


                $data = [
                    'Address' => trim($address),
                    'Adults' => $fields['FILTERS']['adults'],
                    'ChildAges' => [], //$fields['FILTERS'][''] //это будет в popup
                    'Childs' => $fields['FILTERS']['childs'],
                    'ClientInfo' => $GLOBALS['USER']->getFullName().', '.$_SERVER['REMOTE_ADDR'], //ЗАМЕНИТЬ НА ФИО ЗАПОЛНЯЮЩЕГО
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

                $result['test_reserve_data'] = $data;

//                $this->logData(['Company test',$data]);

//                return $data;
//                $reserveRes = $this->postRequest('AddRoomReservation', $data);
                $reserveRes = (new \Ourcompany\Servio\Work\Request)->postRequest('AddRoomReservation', $data, $settings);

                if($reserveRes['Result'] !== 0)
                {
                    $result['errors'][] = $reserveRes['Error'];
                }
                else
                {
                    //запись Id резерва в поле сделки
                    //закрытие формы и открытие окна с данными резерва
                    //очистка формы и отображение резерва с кнопками продолжения или отмены

//                    $this->logData(['reserve add result:',$reserveRes]);

                    $result['result'] = $reserveRes['Account'];
                    $dealFields[$settings['success']['SERVIO_FIELD_RESERVE_ID']] = $reserveRes['Account'];


                    //Сохранение коммента в сделку
                    if($comment)
                    {
                        $dealFields['COMMENTS'] = $comment;
                    }

                    $dealUdRes = (new \Ourcompany\Servio\Deal)->updateDeal($fields['DEAL_ID'],$dealFields);
                    if($dealUdRes['errors'])
                    {
                        $result['errors'] = $dealUdRes['errors'];
                    }

                    //обновление адреса в  компании или контакте
                    if($address)
                    {
                        if($dealData['CONTACT_DATA'])
                        {

                            $contactAddr = trim($dealData['CONTACT_DATA'][$settings['success']['SERVIO_FIELD_CONTACT_ADDRESS']]);
                            if($contactAddr)
                            {
                                if($address !== $contactAddr)
                                {
                                    $contactAddr = "$contactAddr\n".date('d.m.Y H:i').": $address";
                                }

                            }
                            else
                            {
                                $contactAddr = $address;
                            }
                            $addressUpdate = \Bitrix\Crm\ContactTable::update($dealData['CONTACT_DATA']['ID'],
                                [$settings['success']['SERVIO_FIELD_CONTACT_ADDRESS'] => trim($contactAddr)]);

                            if(!$addressUpdate->isSuccess())
                            {
                                $result['errors'] = array_merge($result['errors'],$addressUpdate->getErrorMessages());
                            }
                        }
                        else
                        {
                            if($dealData['COMPANY_DATA'])
                            {
                                $companyAddr = trim($dealData['COMPANY_DATA'][$settings['success']['SERVIO_FIELD_COMPANY_ADDRESS']]);
                                if($companyAddr)
                                {
//                                    $companyAddr = "$companyAddr\n".date('d.m.Y H:i').": $address";
                                    if($address !== $companyAddr)
                                    {
                                        $contactAddr = "$companyAddr\n".date('d.m.Y H:i').": $address";
                                    }
                                }
                                else
                                {
                                    $companyAddr = $address;
                                }
                                $addressUpdate = \Bitrix\Crm\CompanyTable::update($dealData['COMPANY_DATA']['ID'],
                                    [$settings['success']['SERVIO_FIELD_COMPANY_ADDRESS'] => trim($companyAddr)]);

                                if(!$addressUpdate->isSuccess())
                                {
                                    $result['errors'] = array_merge($result['errors'],$addressUpdate->getErrorMessages());
                                }
                            }

                        }

                    }


                }


//                return $reserveRes;
            }

        }


        return $result;
    }


    //получение резерва с 16.07.2020
    public function getReserveData($id,$settings,$payTypes)
    {
        $result = [
            'result' => [],
            'errors' => [],
            'language' => LANGUAGE_ID,
        ];


        $reserveData = (new \Ourcompany\Servio\Work\Request)->postRequest('GetReservationInfo', ['Account' => intval($id)], $settings);
        if($reserveData['Result'] !== 0)
        {
            $result['errors'][] = $reserveData['Error'];
        }
        else
        {
            unset($reserveData['Error']);
            unset($reserveData['ErrorCode']);
            unset($reserveData['Result']);
            $reserveData['PaidTypeText'] = $payTypes[$reserveData['PaidType']];
            $reserveData['DateArrival'] = date('d.m.Y H:i',strtotime($reserveData['DateArrival']));
            $reserveData['DateDeparture'] = date('d.m.Y H:i',strtotime($reserveData['DateDeparture']));

            $servicesResultArray = [];
            $servisesArr = []; //для формирования массива услуг в th
//            $servisesByDate = []; //разбивка сервисов по дням

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
//                        $servisesByDate[$rDate][] = $service;
                    }
                }
            }

            //th
            $reserveData['ThServises'] = $servisesArr;
            //Formated Servises Prices By Dates
            $reserveData['ResultServises'] = array_values($servicesResultArray);
//            $reserveData['ServicesByDates'] = $servisesByDate;

            $result['result'] = $reserveData;
        }


//        $result['test_reserv_info'] = $reserveData;

        return $result;
    }


    //отмена резерва + смена стадии на проигрышную
    public function abortReserve($fields,$settings,$defaultValues)
    {
        $result = [
            'result' => false,
            'errors' => [],
        ];

        if(!$defaultValues)
        {
            $result['errors'][] = 'Ошибка при передаче default параметров из настроек (не админки)!';
        }
        else
        {
            $reserveAbortResult = (new \Ourcompany\Servio\Work\Request)->postRequest('CancelReservation', ['Account' => $fields['reserveId']], $settings);
            if($reserveAbortResult['Result'] !== 0)
            {
                $result['errors'][] = $reserveAbortResult['Error'];
            }
            else
            {
                $dealData = (new \Ourcompany\Servio\Deal)->getDealFields(
                    ['ID' => intval($fields['id'])],
                    ['ID','CATEGORY_ID']
                );
                if(!$dealData)
                {
                    $result['errors'][] = "Не найдена сделка #{$fields['id']}";
                }
                else
                {
                    //определение стадии пригрыша - стандарт от Б24 = C{CATEGORY_ID}:LOSE или LOSE (CATEGORY_ID === 0)
                    $loseStageId = (intval($dealData['CATEGORY_ID']) === 0)
                        ? $defaultValues['LOSE_STAGE_ID_NAME_PART']
                        : "C{$dealData['CATEGORY_ID']}:{$defaultValues['LOSE_STAGE_ID_NAME_PART']}";

                    $dealUpdRes = (new \Ourcompany\Servio\Deal)->updateDeal($fields['id'],[
                        'STAGE_ID' => $loseStageId,
                        $settings['success']['SERVIO_FIELD_RESERVE_ID'] => ''
                    ]);
                    if($dealUpdRes['errors'])
                    {
                        $result['errors'] = array_merge($result['errors'],$dealUpdRes['errors']);
                    }
                    else
                    {
                        $result['result'] = $dealUpdRes['result'];
                    }
                }
            }
//            $result['result'] = [$fields,$settings];
//            $result['result'] = [$dealData,$loseStageId];
        }
        return $result;
    }

    public function confirmReserve($fields,$settings)
    {
        $result = [
            'result' => false,
            'errors' => [],
        ];

        $confirmResult = (new \Ourcompany\Servio\Work\Request)->postRequest('GetAccountConfirm',
            [
                'Account' => $fields['reserveId'],
                'IsoLanguage'  => 'ru',
                'Format' => $settings['success']['SERVIO_RESERVE_CONFIRM_FILE_FORMAT'],
            ],
            $settings);
        if($confirmResult['Result'] !== 0)
        {
            $result['errors'][] = $confirmResult['Error'];
        }
        else
        {
            $updDealRes = (new \Ourcompany\Servio\Deal)->updateDeal($fields['id'],[$settings['success']['SERVIO_FIELD_RESERVE_CONFIRM_FILE_ID'] => $confirmResult['DocumentID']]);

            if($updDealRes['errors'])
            {
                $result['errors'] = array_merge($result['errors'],$updDealRes['errors']);
            }
            else {
                sleep(2);
                $fileResult = $this->getDocument($confirmResult['DocumentID'],'servio_confirm_archive',$settings);

                if(!$fileResult['result'])
                {
                    $result['errors'] = array_merge($result['errors'],$fileResult['errors']);
                }
                else {
                    //получили файл в массиве по типу $_FILES
                    $fileArr = $fileResult['result'];

                    $updDealRes = (new \Ourcompany\Servio\Deal)->updateDeal($fields['id'],[$settings['success']['SERVIO_FIELD_RESERVE_CONFIRM_FILE'] => $fileArr]);

                    if($updDealRes['errors'])
                    {
                        $result['errors'] = array_merge($result['errors'],$updDealRes['errors']);
                    }
                    else
                    {
                        $result['result'] = $updDealRes['result'];
                    }

                    //удаление файлаиз корня после передачи его в сделку
                    unlink($_SERVER['DOCUMENT_ROOT'].'/'.$fileArr['name']);
                }
            }

//            $result['test_document_confirm'] = $fileResult;
//            $result['test_update_deal'] = $updDealRes;

        }
        return $result;
    }


    //возврат массива файла по типу $_FILES[]
    public function getDocument($fileId,$myFileName,$settings)
    {
        $result = [
            'result' => [],
            'errors' => [],
        ];

        $docRes = (new \Ourcompany\Servio\Work\Request)->postRequest('GetDocument', ['DocumentID' => $fileId], $settings);

        if($docRes['Result'] !== 0)
        {
            $result['errors'][] = $docRes['Error'];
        }
        else
        {
            if(!$docRes['IsReady'])
            {
                $result['errors'][] = 'Файл еще не готов! Попробуйте позже';
            }
            else
            {
                $fileName = $_SERVER['DOCUMENT_ROOT'].'/'.$myFileName.'_'.strtotime('now').'.zip';
                $fileCreate = file_put_contents($fileName,base64_decode($docRes['DocumentCode']));

//                $fileCreate = file_put_contents($_SERVER['DOCUMENT_ROOT'].'/'.$myFileName.'_'.strtotime('now').'.zip',base64_decode($docRes['DocumentCode']));

                if($fileCreate)
                {
                    $result['result'] = \CFile::MakeFileArray($fileName);

                }
                else
                {
                    $result['errors'][] = 'Ошибка при создании временного файла архива';
                }
            }
        }

        return $result;
    }

    public function createBill($servises,$dates,$settings)
    {
        $result = [
            'result' => [],
            'errors' => [],
        ];

        $account = '';
        $totalSum = 0;
        $realServises = [];
        foreach ($servises as $servise)
        {
            $priceDates = [];

            foreach ($servise['PriceDate'] as $priceDate)
            {
                if(in_array(date('d.m.Y',strtotime($priceDate['Date'])),$dates))
                {
                    $priceDates[] = [
                        'Date' => $priceDate['Date'],
                        'Price' => $priceDate['Price'],
                        'CustomerAccount' => $priceDate['CustomerAccount'],
                    ];

                    $account = $priceDate['CustomerAccount'];
                    $totalSum += $priceDate['Price'];
                }
            }

            $realServises[] = [
                'ServiceID' => $servise['ServiceID'],
                'ServiceName' => $servise['ServiceName'],
                'ServiceCode' => $servise['ServiceCode'],
                'ServiceSystemCode' => $servise['ServiceSystemCode'],
                'ServiceTypeName' => $servise['ServiceTypeName'],
                'PriceDates' => $priceDates
            ];
        }


        //потом удалить
        $data = [
            'Account' => $account,
            'Services' => $realServises,
//            'Services' => $servises,
//            'TTT_PRICE' => $totalSum,
            'Amount' => round($totalSum,2),

        ];

//        $this->logData($data);

        //попытка получить счет - пока жопа!
        $billCreateResult = (new \Ourcompany\Servio\Work\Request)->postRequest('SetReservationBill',
            [
                'Account' => $account,
                'Services' => $realServises,
                'Amount' => round($totalSum,2),
            ]
            , $settings);

        $result['test_create_bill'] = $billCreateResult;

        if($billCreateResult['Result'] !== 0)
        {
            $result['errors'][] = $billCreateResult['Error'];
        }
        else
        {
            sleep(3);

            $billDocumentResult = (new \Ourcompany\Servio\Work\Request)->postRequest('GetAccountBill',
                [
                    'Account' => $billCreateResult['Account'],
                    'IsoLanguage'  => 'ru',
                    'Format' => $settings['success']['SERVIO_RESERVE_CONFIRM_FILE_FORMAT'],
                ]
                , $settings);

            $result['test_get_bill_doc'] = $billDocumentResult;
            if($billDocumentResult['Result'] !== 0)
            {
                $result['errors'][] = $billDocumentResult['Error'];
            }
            else
            {
                $result['test_get_bill_doc'] = $billDocumentResult;
            }
        }

        return $result;
    }


    public function logData($data){
        $file = $_SERVER["DOCUMENT_ROOT"].'/test.log';
        file_put_contents($file, print_r([date('d.m.Y H:i:s'),$data],true), FILE_APPEND | LOCK_EX);
    }

}