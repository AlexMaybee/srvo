<?php

namespace Ourcompany\Servio\Ajax;

class Handler extends \Bitrix\Main\Engine\Controller
{

    private $settings = [];
    private $payTypes = [];


    function __construct()
    {
        parent::__construct();
        $settingsObj = new \Ourcompany\Servio\Work\Mysettings;
//        $this->settings = (new \Ourcompany\Servio\Work\Mysettings)->settings;
        $this->settings = $settingsObj->settings;
        $this->payTypes = $settingsObj->payTypes;
    }

    // 1 Данные по полям сделки
    public function dealFieldsAction($DEAL_ID)
    {
        return (new \Ourcompany\Servio\Deal)->getDealFields(
            ['ID' => $DEAL_ID],
            [$this->settings['success']['SERVIO_FIELD_RESERVE_ID'],$this->settings['success']['SERVIO_FIELD_RESERVE_CONFIRM_FILE_ID'],$this->settings['success']['SERVIO_FIELD_RESERVE_CONFIRM_FILE']]
        );
//        return ['deal' => $DEAL_ID, 'settings' => $this->settings];
    }

    // 2 Данные компании, от которой производится резерв
    public function companyDataAction()
    {
        $result = [
            'result' => false,
            'error' => false,
        ];

        $companyInfoRes = (new \Ourcompany\Servio\Work\Request)->postRequest('GetCompanyInfo', ['CompanyCode'  => $this->settings['success']['SERVIO_COMPANY_CODE'],], $this->settings);

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
//        return ['test' => 'company'];
    }

    // 3 Contract Conditions + Paid Type В селекты
    public function contractConditionsAction($fields)
    {
        $result = [
            'result' => false,
            'error' => false,
        ];

        $clearFields = (new \Ourcompany\Servio\Work\Request)->safeInputData($fields);
        if(!$clearFields)
        {
            $result['error'] = 'Что-то случилос при очистке полей!';
        }
        else
        {
            $fields = $clearFields;

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

            $requestResult = (new \Ourcompany\Servio\Work\Request)->postRequest('GetRooms', $data, $this->settings);
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
//                $requestResult['PayTypes'] = $this->payTypes;

                if($requestResult['ContractConditions'])
                {
                    foreach ($requestResult['ContractConditions'] as $cC)
                    {
                        foreach ($cC['PaidTypes'] as $payType)
                        {
                            $requestResult['PayTypes'][$cC['ContractConditionID']][] = ['ID' => $payType, 'NAME' => $this->payTypes[$payType]];
                        }
                    }
                }

                //

                $result['result'] = $requestResult;
            }

        }
        return $result;
    }


    // 4 Получение цен
    public function pricesByFilterAction($FIELDS,$DEAL_ID)
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

        $dealId = (new \Ourcompany\Servio\Work\Request)->safeInputData($DEAL_ID);
        $fields = (new \Ourcompany\Servio\Work\Request)->safeInputData($FIELDS);

        $result['test_INPUT'] = [$dealId,$fields];


        $dealResut = (new \Ourcompany\Servio\Deal)->getDealAndClientShort($dealId,$this->settings);

        if($dealResut['errors'])
        {
//            $result['errors'] = array_merge($result['errors'],$dealResut['errors']);
            $result['errors'] = $dealResut['errors'];
        }
        else
        {
            $dealData = $dealResut['result'];

            //берем коммент из сделки и адрес из компании/контакта
            $result['fields']['COMMENTS'] = $dealData['COMMENTS'];

            if($dealData['CONTACT_DATA'] && $dealData['CONTACT_DATA'][$this->settings['success']['SERVIO_FIELD_CONTACT_ADDRESS']])
            {
                $result['fields']['ADDRESS'] = $dealData['CONTACT_DATA'][$this->settings['success']['SERVIO_FIELD_CONTACT_ADDRESS']];
            }
            elseif($dealData['COMPANY_DATA'] && $dealData['COMPANY_DATA'][$this->settings['success']['SERVIO_FIELD_COMPANY_ADDRESS']])
            {
                $result['fields']['ADDRESS'] = $dealData['COMPANY_DATA'][$this->settings['success']['SERVIO_FIELD_COMPANY_ADDRESS']];
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
        $roomCatNames = (new \Ourcompany\Servio\Work\Request)->postRequest('GetRoomTypesList', ['HotelID' => $fields['hotelId']], $this->settings);
        if($roomCatNames['Error'])
        {
            $result['errors'][] = $roomCatNames['Error'];
        }

//        $result['test_ROOMS_NAME'] = $roomCatNames;


        //комнаты по фильтру
//        $roomsData = $this->getCategoriesByFilter($fields);
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
            , $this->settings);

        $result['test_ROOMS_DATA'] = $roomsData;

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

//            $result['test_cat_filter_for_price'] = $roomsCatFilter;


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
                , $this->settings);

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

                $result['test_ROOMS_PRICE'] = $roomsPrices;
            }

        }


//        return ['test' => [$fields,$dealId,$ROOM_TYPES]];
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

    public function addReserveAction($FIELDS)
    {
        $fields['DEAL_ID'] = (new \Ourcompany\Servio\Work\Request)->safeInputData($FIELDS['DEAL_ID']);
        $fields['FILTERS'] = (new \Ourcompany\Servio\Work\Request)->safeInputData($FIELDS['FILTERS']);
        $fields['ROOM_CATEGORY'] = (new \Ourcompany\Servio\Work\Request)->safeInputData($FIELDS['ROOM_CATEGORY']);
        $result = (new \Ourcompany\Servio\Servio)->addReserve($fields,$this->settings);

//        return ['test_reserve' => $FIELDS];
        return $result;
    }

}