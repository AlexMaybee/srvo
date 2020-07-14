<?php

namespace Ourcompany\Servio\Work;

class Request
{
    public function postRequest($operation,$data,$settings)
    {

        //Если ошибки с настройками или их нет, то выдаем ошибку
        if($settings['errors'])
        {
            return $result['error'] = implode("\n ",array_values($settings['errors']));
        }

        $data_string = json_encode ($data, JSON_UNESCAPED_UNICODE);
        $curl = curl_init($settings['success']['SERVIO_URI_LINK'].$operation);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        // Принимаем в виде массива. (false - в виде объекта)
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
//                'Content-Length: ' . strlen($data_string),
                'AccessToken: '.$settings['success']['SERVIO_REST_KEY']
            )
        );
        $result = curl_exec($curl);
        curl_close($curl);

        return json_decode($result,true);
    }
}