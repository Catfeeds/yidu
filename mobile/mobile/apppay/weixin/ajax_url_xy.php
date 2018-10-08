<?php

/**

 * 微信回调

*/

define('IN_ECS', true);

require('../../includes/init.php');

require('../../includes/lib_payment.php');



$notify_info = getXmlArray();

$out_trade_no = addslashes($notify_info['out_trade_no']);   //微信支付交易号



if ($notify_info['return_code']==='SUCCESS' || $notify_info['result_code']==='SUCCESS'){

    $log_id = substr($out_trade_no, 5);

    $money_paid = $notify_info['total_fee']/100;

    //判断价格是否一致 todo

    if (!check_money1($log_id, $money_paid)) {

        return false;

    }
    
    xy_paid($log_id);
    show_message('续约成功', '返回店铺', 'user.php?act=shop_index');
} else{

    echo 'FAIL';exit;

}



//xml转数组

function getXmlArray() {

    $xmlData = file_get_contents("php://input");

    if ($xmlData){

        $postObj = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (! is_object($postObj)){

            return false;

        }

        $array = json_decode(json_encode($postObj), true); // xml对象转数组

        return array_change_key_case($array, CASE_LOWER); // 所有键小写

    }else{

        return false;

    }

}



?>