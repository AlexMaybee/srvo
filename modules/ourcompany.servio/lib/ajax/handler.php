<?php

namespace Ourcompany\Servio\Ajax;

class Handler extends \Bitrix\Main\Engine\Controller
{
    private $settingsObj;
    private $settings = [];


    function __construct()
    {
        parent::__construct();
        $this->settingsObj = new \Ourcompany\Servio\Work\Mysettings;
        $this->settings = $this->settingsObj->settings;
    }

    // 1 Данные по полям сделки
    public function dealFieldsAction($DEAL_ID)
    {
        return (new \Ourcompany\Servio\Deal)->getDealFields(
            ['ID' => $DEAL_ID],
            [$this->settings['success']['SERVIO_FIELD_RESERVE_ID'],$this->settings['success']['SERVIO_FIELD_RESERVE_CONFIRM_FILE_ID'],$this->settings['success']['SERVIO_FIELD_RESERVE_CONFIRM_FILE']]
        );
    }

    // 2 Данные компании, от которой производится резерв
    public function companyDataAction()
    {
        return (new \Ourcompany\Servio\Servio)->companyData($this->settings);
    }

    // 3 Contract Conditions + Paid Type В селекты
    public function contractConditionsAction($fields)
    {
        $fields = (new \Ourcompany\Servio\Work\Request)->safeInputData($fields);
        return (new \Ourcompany\Servio\Servio)->contractConditions($fields,$this->settings,$this->settingsObj->payTypes);
    }

    // 4 Получение цен
    public function pricesByFilterAction($FIELDS,$DEAL_ID)
    {
        $dealId = (new \Ourcompany\Servio\Work\Request)->safeInputData($DEAL_ID);
        $fields = (new \Ourcompany\Servio\Work\Request)->safeInputData($FIELDS);
        return (new \Ourcompany\Servio\Servio)->pricesByFilter($fields,$dealId,$this->settings);
    }

    public function addReserveAction($FIELDS)
    {
        $fields['DEAL_ID'] = (new \Ourcompany\Servio\Work\Request)->safeInputData($FIELDS['DEAL_ID']);
        $fields['FILTERS'] = (new \Ourcompany\Servio\Work\Request)->safeInputData($FIELDS['FILTERS']);
        $fields['ROOM_CATEGORY'] = (new \Ourcompany\Servio\Work\Request)->safeInputData($FIELDS['ROOM_CATEGORY']);
        return (new \Ourcompany\Servio\Servio)->addReserve($fields,$this->settings);
    }

    public function reserveDataAction($RESERVE_ID)
    {
        $id = (new \Ourcompany\Servio\Work\Request)->safeInputData($RESERVE_ID);
        return (new \Ourcompany\Servio\Servio)->getReserveData($id,$this->settings,$this->settingsObj->payTypes);
    }

    public function abortReserveAction($FIELDS)
    {
        $fields = (new \Ourcompany\Servio\Work\Request)->safeInputData($FIELDS);
        return (new \Ourcompany\Servio\Servio)->abortReserve($fields,$this->settings,(new \Ourcompany\Servio\Work\Mysettings)->defaultValues);
    }

    public function confirmReserveAction($FIELDS)
    {
        $fields = (new \Ourcompany\Servio\Work\Request)->safeInputData($FIELDS);
        return (new \Ourcompany\Servio\Servio)->confirmReserve($fields,$this->settings);
    }

    public function createBillReserveAction($FIELDS,$SERVICES,$FIRST_PAY)
    {
        $fields = (new \Ourcompany\Servio\Work\Request)->safeInputData($FIELDS);
//        $servises = (new \Ourcompany\Servio\Work\Request)->safeInputData($SERVICES);

        return (new \Ourcompany\Servio\Servio)->createBill($fields,$SERVICES,$FIRST_PAY,$this->settings);
        return [$fields,$servises];
    }


}