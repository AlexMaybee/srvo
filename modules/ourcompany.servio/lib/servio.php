<?php
namespace Ourcompany\Servio;

use \Bitrix\Main\Page\Asset,
    \Bitrix\Main\Localization\Loc;

class Servio
{
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

                    $result['result'] = $reserveRes['Account'];
                    $dealFields[$settings['SERVIO_FIELD_RESERVE_ID']] = $reserveRes['Account'];

                    //Сохранение коммента в сделку
                    if($comment)
                    {
                        $dealFields['COMMENTS'] = $comment;
                    }

                    $dealUdRes = $this->updateDeal($fields['DEAL_ID'],$dealFields);
                    if($dealUdRes['errors'])
                    {
                        $result['errors'] = $dealUdRes['errors'];
                    }

                    //обновление адреса в  компании или контакте
                    if($address)
                    {
                        if($dealData['CONTACT_DATA'])
                        {

                            $contactAddr = $dealData['CONTACT_DATA'][$settings['SERVIO_FIELD_CONTACT_ADDRESS']];
                            if($contactAddr)
                            {
                                $contactAddr = "$contactAddr\n".date('d.m.Y H:i').": $address";
                            }
                            else
                            {
                                $contactAddr = $address;
                            }
                            $addressUpdate = \Bitrix\Crm\ContactTable::update($dealData['CONTACT_DATA']['ID'],
                                [$settings['SERVIO_FIELD_CONTACT_ADDRESS'] => trim($contactAddr)]);

                            if(!$addressUpdate->isSuccess())
                            {
                                $result['errors'] = array_merge($result['errors'],$addressUpdate->getErrorMessages());
                            }
                        }
                        else
                        {
                            if($dealData['COMPANY_DATA'])
                            {
                                $companyAddr = $dealData['COMPANY_DATA'][$settings['SERVIO_FIELD_COMPANY_ADDRESS']];
                                if($companyAddr)
                                {
                                    $companyAddr = "$companyAddr\n".date('d.m.Y H:i').": $address";
                                }
                                else
                                {
                                    $companyAddr = $address;
                                }
                                $addressUpdate = \Bitrix\Crm\CompanyTable::update($dealData['COMPANY_DATA']['ID'],
                                    [$settings['SERVIO_FIELD_COMPANY_ADDRESS'] => trim($companyAddr)]);

                                if(!$addressUpdate->isSuccess())
                                {
                                    $result['errors'] = array_merge($result['errors'],$addressUpdate->getErrorMessages());
                                }
                            }

                        }

                    }


                }


                return $reserveRes;
            }

        }


        return $result;
    }
}