<?php
/**
 * 支付宝回调
*/
define('IN_ECS', true);
require('../../includes/init.php');
require('../../includes/lib_payment.php');

$out_trade_no = $_REQUEST['out_trade_no']; //商户订单号
$trade_no = $_REQUEST['trade_no'];	//支付宝交易号

if ($_REQUEST['trade_status'] == 'TRADE_SUCCESS'){
    $money_paid = $_REQUEST['total_fee'];
    /* 检查支付的金额是否相符 */
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
}

?>