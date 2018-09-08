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
    $money_paid = $notify_info['total_fee']/100;
    //判断价格是否一致 todo
    if (!check_money($out_trade_no, $money_paid)) {
        return false;
    }
    
    //是否开启余额变动给客户发短信-用户消费（发送抽奖码）
    if($_CFG['sms_user_money_change'] == 1)
    {
        // 获取抽奖码
        $d_sql = "SELECT o.extension_num,o.user_id ".
       " FROM " .$GLOBALS['ecs']->table('order_info') . ' as o '.
       " LEFT JOIN " .$GLOBALS['ecs']->table('pay_log') . 'as p '.
       " ON o.order_id=p.order_id ".
       " WHERE log_id =$out_trade_no and is_paid=1 ";
        $order=$GLOBALS['db']->getRow($d_sql);

        //获取用户电话
        $sql = "SELECT mobile_phone FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '" . $order['user_id'] . "'";
        $users = $GLOBALS['db']->getRow($sql);
        $content = sprintf('您获得的抽奖码是【%s】。如有疑问，请联系商城客服。',$order['extension_num']);
        if($users['mobile_phone'])
        {
            require_once (ROOT_PATH . 'sms/sms.php');
            sendSMS($users['mobile_phone'],$content);
        }
    }

    order_paid($out_trade_no,2);
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