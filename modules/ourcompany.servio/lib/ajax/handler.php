<?php

namespace Ourcompany\Servio\Ajax;

class Handler extends \Bitrix\Main\Engine\Controller
{

    private $settings = [];

    function __construct()
    {
        parent::__construct();
        $this->settings = (new \Ourcompany\Servio\Work\Mysettings)->settings;
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
        return ['cConditions' => $fields];
    }
}