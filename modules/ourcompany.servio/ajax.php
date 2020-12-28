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
        //данные сделки будем получать при резерве
//        case('GET_DEAL_DATA'):
//            $result = (new Hotel)->getDealDataById($_POST['DEAL_ID']);
//            break;
        //Проверяем Id резерва в спец. поле
        case('GET_DEAL_SERVIO_FIELDS'):
            $result = (new Hotel)->getDealServioFields($_POST['DEAL_ID']);
            break;

        case('GET_DATA_FOR_FORM'):
            $result = (new Hotel)->getInfoForServioReserveForm($_POST['FIELDS']);
            break;


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

            //New By Button "Search"
        case('GET_PRICES_BY_FILTER'):
            $result = (new Hotel)->getPricesByFilter($_POST['FIELDS'],$_POST['DEAL_ID']);
            break;
//        case('GET_CATEGORY_PRICE'):
//            $result = (new Hotel)->getPriceByCategory($_POST['FIELDS']);
//            break;

//        confirm reserve
        case('CONFIRM_RESERVE'):
            $result = (new Hotel)->confirmReserve($_POST['FIELDS']);
            break;

//        get bill for reserve
        case('GET_BILL_FOR_RESERVE'):
            $result = (new Hotel)->getBillForReserve($_POST['FIELDS']);
            break;

        case('ABORT_RESERVE'):
            $result = (new Hotel)->abortReserve($_POST['FIELDS']);
            break;

            //test
//        case('TEST_GET_DOCUMENT'):
//            $result = (new Hotel)->getDocument($_POST['DOCUMENT_ID']);
//            break;


        default:
            $result = ['result' => false, 'error' => 'WRONG ACTION!'];
            break;
    }

    echo json_encode($result);

//    $result = new Hotel;

//    echo json_encode(['test' => $_POST]);

}