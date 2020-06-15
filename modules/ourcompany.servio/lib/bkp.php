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

        echo json_encode(['HELLO, OOOOO!',$this->settings,$this->settingErrors]);
    }

    public function getCompanyInfo()
    {
        $result = [
            'result' => false,
            'error' => false,
        ];

        //Если ошибки с настройками или их нет, то выдаем ошибку
        if($this->settingErrors)
        {
            return $result['error'] = implode("\n",array_values($this->settingErrors));
        }


        $data['CompanyCode'] = $this->settings['SERVIO_COMPANY_CODE'];
        $companyInfoRes = $this->postRequest('GetCompanyInfo',$data);

        if(!$companyInfoRes['Result'] === 0)
        {
            $result['error'] = $companyInfoRes['Error'];
        }
        else
        {
            $result['result'] = ['companyID' => $companyInfoRes['CompanyID']];
        }

        return $result;
    }

    private function postRequest($operation,$data)
    {
        $data_string = json_encode ($data, JSON_UNESCAPED_UNICODE);
        $curl = curl_init($this->servioUrl.$operation);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        // Принимаем в виде массива. (false - в виде объекта)
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
                'AccessToken: '.$this->token
            )
        );
        $result = curl_exec($curl);
        curl_close($curl);

        return json_decode($result,true);
    }
}