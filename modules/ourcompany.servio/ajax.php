<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-type: application/json');

use Ourcompany\Servio\Hotel;

//$asyncObj = new Crmgenesis\Newworktimecontrol\Customevent;


//echo json_encode(['test' => 'hello!']);

if(isset($_POST['ACTION']))
{
    switch($_POST['ACTION'])
    {
        case('GET_COMPANY_INFO'):
            $result = (new Hotel)->getCompanyInfo();
            break;
        case('GET_CATEGORIES_WITH_ROOMS'):
            $result = (new Hotel)->getCategoriesWithRooms($_POST['FIELDS']);
            break;
        case('ADD_RESERVE'):
            $result = (new Hotel)->addReserve($_POST['FIELDS']);
            break;
        case('GET_RESERVE_BY_ID'):
            $result = (new Hotel)->getReserveData($_POST['RESERVE_ID']);
            break;
        case('GET_CATEGORY_PRICE'):
            $result = (new Hotel)->getPriceByCategory($_POST['FIELDS']);
            break;

        default:
            $result = ['result' => false, 'error' => 'WRONG ACTION!'];
            break;
    }

    echo json_encode($result);

//    $result = new Hotel;

//    echo json_encode(['test' => $_POST]);

}