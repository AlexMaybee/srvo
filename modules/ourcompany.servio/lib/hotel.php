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

    public function getInfoOnPageLoad()
    {
        $result = [
            'result' => false,
            'error' => false,
        ];

        //1.Данные компании
        $companyData = $this->getCompanyInfo();
        if($companyData['error'])
        {
            $result['error'] .= $companyData['error']."\n ";
        }
        else
        {
            $result['result']['company'] = $companyData['result'];
        }

        //данные по комнатам по выбранным фильтрам


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
        if(!$categoryRes['Result'] === 0)
        {
            $result['error'] = $categoryRes['Error'];
        }
        else
        {
            $data = ['HotelID' => 0];
            $priceFilter = [];
            $categoryNameRes = $this->postRequest('GetRoomTypesList',$data);
            if(!$categoryNameRes['Result'] === 0)
            {
                $result['error'] = $categoryNameRes['Error'];
            }
            else
            {
                foreach ($categoryRes['RoomTypes'] as $categoryArr)
                {
                    if($categoryArr['FreeRoom'] > 0)
                    {
                        $index = array_search($categoryArr['ID'],$categoryNameRes['IDs']);
                        if($index !== false)
                        {
                            $priceFilter[] = $categoryNameRes['IDs'][$index];

                            $result['result']['rooms'][] = [
                                'Id' => $categoryNameRes['IDs'][$index],
                                'HotelId' => $categoryNameRes['HotelIDs'][$index],
                                'CategoryName' => $categoryNameRes['ClassNames'][$index],
                                'FreeRoom'  => $categoryArr['FreeRoom'],
                                'MainPlacesCount'  => $categoryArr['MainPlacesCount'],
                                'NearestDateToReservation'  => $categoryArr['NearestDateToReservation'],
                            ];
                        }
                    }
                }

                //получение цен по каждой категории
                $categoryPriceArr = $this->getPriceByCategories($post,$priceFilter);

//                $this->logData($categoryPriceArr);

                if(!$categoryPriceArr['Result'] === 0)
                {
                    $result['error'] .= "\n".$categoryPriceArr['Error'];
                }
                else
                {

//                    $result['result']['priceLists'] =
//                        [
//                            'ValuteShort' => $categoryPriceArr['ValuteShort'],
//                            'PriceLists' => $categoryPriceArr['PriceLists'],
//                        ];


                    //переформатирование массива
                    $newArr = [];
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

                                $newArr[] = [
                                    'priceId' => $priceList['PriceListID'],
                                    'IsNonReturnRate' => $priceList['IsNonReturnRate'],
                                    'IsSpecRate' => $priceList['IsSpecRate'],
                                    'roomTypeId' => $roomType['ID'],
                                    'roomTypeName' => '',
                                    'totalDays' => count($servise['PriceDates']),
                                    'servises' =>  $serviseArr,
                                    'totalPrice' => '',
                                    'dates' => "{$post['dateFrom']} - {$post['dateTo']}", //??? правильно???
                                    'currency' => $categoryPriceArr['ValuteShort'],
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
                            }

                            $result['result']['priceLists'][] = $roomTypeS;
                        }
                    }

                    $result['result']['test'] = $categoryPriceArr;
//                    $result['result']['priceLists'] = $newArr;
                }

            }
        }
        return $result;
    }

    private function getCategoriesByFilter($post)
    {
        $data = [
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


    public function logData($data){
        $file = $_SERVER["DOCUMENT_ROOT"].'/11111.log';
        file_put_contents($file, print_r([date('d.m.Y H:i:s'),$data],true), FILE_APPEND | LOCK_EX);
    }

}