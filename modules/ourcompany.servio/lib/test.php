<?php
namespace Ourcompany\Servio;

use Bitrix\Main\Engine\Controller;

class Test extends Controller
{
    /**
     * @return array
     */
    public function configureActions()
    {
        return [
            'example' => [
                'prefilters' => []
            ]
        ];
    }

    /**
     * @param string $param2
     * @param string $param1
     * @return array
     */
    public static function exampleAction($param2 = 'qwe', $param1 = '')
    {
        return [
            'asd' => $param1,
            'count' => 300
        ];
    }
}