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
	order_paid($out_trade_no,2);
}

?>