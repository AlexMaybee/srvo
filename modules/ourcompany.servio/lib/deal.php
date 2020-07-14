<?php

namespace Ourcompany\Servio;

\CModule::IncludeModule("crm"); //Какого хера??

class Deal
{

    public function getDealFields($filter,$select)
    {
        return \Bitrix\Crm\DealTable::getRow([
            'select' => $select,
            'filter' => $filter,
        ]);
    }

//        return (new \Ourcompany\Servio\Work\Request)->postRequest('');
}