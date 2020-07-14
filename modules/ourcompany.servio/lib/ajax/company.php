<?php

namespace Ourcompany\Servio\Ajax;


class Company extends \Bitrix\Main\Engine\Controller
{
//    public function configureActions()
//    {
//        return [
//            'companyTest' => [
//                'prefilters' => []
//            ]
//        ];
//    }

    public function companyTestAction($paramAA)
    {
        return ['test' => 'Hello, Man!',$paramAA,'event' => \Ourcompany\Servio\Event::SETTINGS_OPTIONS];
    }

    public function getCompanyInfo()
    {

    }

}