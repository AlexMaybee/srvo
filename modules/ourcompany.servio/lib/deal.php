<?php

namespace Ourcompany\Servio;

\CModule::IncludeModule("crm"); //Какого хера??

class Deal
{

    public function getDealFields($filter,$select,$order = [])
    {
        return \Bitrix\Crm\DealTable::getRow([
            'select' => $select,
            'filter' => $filter,
            'order' => $order,
        ]);
    }

    public function getContactFields($filter,$select,$order = [])
    {
        return \Bitrix\Crm\ContactTable::getRow([
            'select' => $select,
            'filter' => $filter,
            'order' => $order,
        ]);
    }

    public function getCompanyFields($filter,$select,$order = [])
    {
        return \Bitrix\Crm\CompanyTable::getRow([
            'select' => $select,
            'filter' => $filter,
            'order' => $order,
        ]);
    }


    /*телефоны для контакта/компании*/
    public function getPhonesAndEmails($entityString,$elemId,$valTypeArr = [])
    {
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


    public function getDealAndClientShort($dealId,$settings)
    {
        $result = [
            'result' => [],
            'errors' => [],
        ];

        $dealId = intval($dealId);
        if($dealId > 0)
        {
            $dealRecord = $this->getDealFields(['ID' => $dealId],['ID','CONTACT_ID','COMPANY_ID','COMMENTS']);

            $dealRecord['COMPANY_DATA'] = [];
            $dealRecord['CONTACT_DATA'] = [];

            if(!$dealRecord)
            {
                $result['errors'][] = "Сделка #{$dealId} не найдена!";
            }
            else
            {
                if ($dealRecord['COMPANY_ID'] > 0)
                {
                    $companyData = $this->getCompanyFields(
                        ['ID' => $dealRecord['COMPANY_ID']],
                        ['ID',$settings['success']['SERVIO_FIELD_COMPANY_ADDRESS']]
                    );

                    if($companyData)
                    {
                        $dealRecord['COMPANY_DATA'] = $companyData;

                        //подумать насчет подтягивания конакта
                    }
                    else
                    {
                        $result['errors'][] = "Компания #{$dealRecord['COMPANY_ID']} не найдена!";
                    }

                }

                if($dealRecord['CONTACT_ID'] > 0) {

                    $contactData = $this->getContactFields(
                        ['ID' => $dealRecord['CONTACT_ID']],
                        ['ID',$settings['success']['SERVIO_FIELD_CONTACT_ADDRESS']]
                    );
                    if($contactData) {
                        $dealRecord['CONTACT_DATA'] = $contactData;
                    }
                    else
                    {
                        $result['errors'][] = "Контакт #{$dealRecord['CONTACT_ID']} не найден!";
                    }
                }

                $result['result'] = $dealRecord;
            }
        }
        else
        {
            $result['errors'][] = "Проблема с переданным значением ID сделки = {$dealId}!";
        }
        return $result;
    }


    //общая функция для получения всего (при запросе цен? || резерв )
    public function getDealAndClientData($dealId,$settings)
    {
        $result = [
            'result' => [],
            'errors' => [],
        ];

        $dealId = intval($dealId);
        if($dealId > 0)
        {
            $dealRecord = $this->getDealFields(['ID' => $dealId],['ID','CONTACT_ID','COMPANY_ID','ASSIGNED_BY_ID','COMMENTS']);

            $dealRecord['COMPANY_DATA'] = [];
            $dealRecord['CONTACT_DATA'] = [];
            $dealRecord['PHONES_AND_EMAILS'] = [];
            if($dealRecord)
            {
                if(!$dealRecord['CONTACT_ID'] && !$dealRecord['COMPANY_ID'])
                {
                    $result['errors'][] = "В сделке #{$dealRecord['ID']} не выбран контакт/компания!";
                }
                else
                {
                    if ($dealRecord['COMPANY_ID'] > 0)
                    {
                        $companyData = $this->getCompanyFields(
                            ['ID' => $dealRecord['COMPANY_ID']],
                            ['*',$settings['success']['SERVIO_FIELD_COMPANY_ADDRESS']]
                        );

                        if($companyData)
                        {
                            $dealRecord['COMPANY_DATA'] = $companyData;

                            //Если в сделке нет контакта, то берем из компании
                            if($dealRecord['CONTACT_ID'] <= 0 && $companyData['CONTACT_ID'] > 0)
                            {
                                $contactData = $this->getContactFields(
                                    ['ID' => $companyData['CONTACT_ID']],
                                    ['*',$settings['success']['SERVIO_FIELD_CONTACT_ADDRESS']]
                                );
                                if($contactData)
                                {
                                    $dealRecord['CONTACT_DATA'] = $contactData;

                                    //почты и телефоны для контакта
                                    $dealRecord['PHONES_AND_EMAILS'] = $this->getPhonesAndEmails('CONTACT',$companyData['CONTACT_ID'],['EMAIL','PHONE']);
                                }
                                else
                                {
                                    $result['errors'][] = "Контакт #{$dealRecord['CONTACT_ID']} для компании #{$dealRecord['COMPANY_ID']} не найден!";
                                }
                            }
                            else
                            {
                                //почты и телефоны для компании
                                $dealRecord['PHONES_AND_EMAILS'] = $this->getPhonesAndEmails('COMPANY',$companyData['ID'],['EMAIL','PHONE']);
                            }
                        }
                        else
                        {
                            $result['errors'][] = "Компания #{$dealRecord['COMPANY_ID']} не найдена!";
                        }

                        if($dealRecord['CONTACT_ID'] > 0) {

                            $contactData = $this->getContactFields(
                                ['ID' => $dealRecord['CONTACT_ID']],
                                ['*',$settings['success']['SERVIO_FIELD_CONTACT_ADDRESS']]
                            );
                            if($contactData) {
                                $dealRecord['CONTACT_DATA'] = $contactData;

                                //почты и телефоны в одном массиве
                                $dealRecord['PHONES_AND_EMAILS'] = $this->getPhonesAndEmails('CONTACT',$dealRecord['CONTACT_ID'],['EMAIL','PHONE']);
                            }
                            else
                            {
                                $result['errors'][] = "Контакт #{$dealRecord['CONTACT_ID']} не найден!";
                            }
                        }
                    }
                    $result['result'] = $dealRecord;
                }
            }
            else
            {
                $result['errors'][] = "Сделка #{$dealId} не найдена!";
            }
        }
        else
        {
            $result['errors'][] = "Проблема с переданным значением ID сделки = {$dealId}!";
        }
        return $result;
    }

}