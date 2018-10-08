<?php
/**
 * APP支付接口
 */
define('IN_ECS', true);
require('../includes/init.php');
require('../includes/lib_order.php');
include_once('../includes/lib_payment.php');
error_reporting(E_ALL ^ E_NOTICE);

$out_trade_no = (string)$_GET['out_trade_no'];
$flag = intval($_GET['flag']);
$pay_code = trim($_GET['pay_code']);

if ($flag == 1) {
	// 抽奖订单支付

	//根据支付id获取订单id
	$order_id = $GLOBALS['db']->getOne("SELECT order_id FROM ".$GLOBALS['ecs']->table('pay_log')." WHERE log_id = '$out_trade_no'");
	//获取订单信息
	$order = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id' OR parent_order_id = '$order_id' limit 1");
	if ($pay_code == 'alipay') {
		$res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/alipay/ajax_url_ex.php";	//回调地址
	} elseif ($pay_code == 'weixin') {
		$res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/weixin/ajax_url_ex.php";	//回调地址
	}

	$res['subject'] = '抽奖支付';
	$res['total_fee'] = (string)$order['order_amount'];
} elseif ($flag == 2) {
	// 店铺续约支付

	// 店铺续约订单
	$log_id = substr($out_trade_no, 5);
	$order = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('shop_xuyue_log') . " WHERE log_id = '$log_id'");
	if ($pay_code == 'alipay') {
		$res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/alipay/ajax_url_xy.php";	//回调地址
	} elseif ($pay_code == 'weixin') {
		$res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/weixin/ajax_url_xy.php";	//回调地址
	}
	$res['subject'] = '店铺续约';
	$res['total_fee'] = (string)$order['amount'];
} elseif ($flag == 3) {
	// 余额充值

	$order = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('pay_log')." WHERE log_id = '$out_trade_no'");
	if ($pay_code == 'alipay') {
		$res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/alipay/ajax_url.php";	//回调地址
	} elseif ($pay_code == 'weixin') {
		$res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/weixin/ajax_url.php";	//回调地址
	}
	$res['subject'] = '余额充值';
	$res['total_fee'] = (string)$order['order_amount'];
} else {
	// 普通订单支付

	$order_id = $GLOBALS['db']->getOne("SELECT order_id FROM ".$GLOBALS['ecs']->table('pay_log')." WHERE log_id = '$out_trade_no'");
	//获取订单信息
	$order = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id' OR parent_order_id = '$order_id' limit 1");
	if ($pay_code == 'alipay') {
		$res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/alipay/ajax_url.php";	//回调地址
	} elseif ($pay_code == 'weixin') {
		$res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/weixin/ajax_url.php";	//回调地址
	}
	$res['subject'] = '订单支付';
	$res['total_fee'] = (string)$order['order_amount'];
}

$res['out_trade_no'] = $out_trade_no;
$res['pay_code'] = $pay_code;
$res['redirect_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/redirect_url.php";	//回调地址

$data['status'] = 200;
$data['message'] = 'ok';
$data['data'] = $res;
echo json_encode($data);
exit;