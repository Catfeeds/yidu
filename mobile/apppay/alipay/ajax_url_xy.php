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

	$log_id = substr($out_trade_no, 5);

	$money_paid = $_REQUEST['total_fee'];

	/* 检查支付的金额是否相符 */

	if (!check_money1($log_id, $money_paid)) {

		return false;

	}

	xy_paid($log_id);
    show_message('续约成功', '返回店铺', 'user.php?act=shop_index');
}



?>