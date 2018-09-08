<?php

/**
 * ECSHOP 接口
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: category.php 17217 2011-01-19 06:29:08Z liubo $
*/
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}
$action=isset($_REQUEST['act'])?trim($_REQUEST['act']):'';
$function_name =  'action_' . $action;
if( empty($action) || !function_exists($function_name))
{
        $API->error('参数错误',400);
}

call_user_func($function_name);

/*
 * 返回支付信息给APP
 */
function action_pay() {
    $user = $GLOBALS['user'];
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $action = $GLOBALS['action'];
    include_once (ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;
    
    $out_trade_no = (string)$_GET['out_trade_no'];
    $flag = intval($_GET['flag']);
    $pay_code = trim($_GET['pay_code']);

    if(empty($out_trade_no)){
        $result['code']=500;
        $result['msg']='非法操作！';
        $result['data'] = new stdClass();
        die($json->encode($result));
    }

    if ($flag == 1) {
        // 抽奖订单支付

        //根据支付id获取订单id
        $order_id = $GLOBALS['db']->getOne("SELECT order_id FROM ".$GLOBALS['ecs']->table('pay_log')." WHERE log_id = '$out_trade_no'");
        //获取订单信息
        $order = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id' OR parent_order_id = '$order_id' limit 1");
        if ($pay_code == 'alipay') {
            $res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/alipay/ajax_url_ex.php";   //回调地址
        } elseif ($pay_code == 'weixin') {
            $res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/weixin/ajax_url_ex.php";   //回调地址
        }

        $res['subject'] = '抽奖支付';
        $res['total_fee'] = (string)$order['order_amount'];
    } elseif ($flag == 2) {
        // 店铺续约支付

        // 店铺续约订单
        $log_id = substr($out_trade_no, 5);
        $order = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('shop_xuyue_log') . " WHERE log_id = '$log_id'");
        if ($pay_code == 'alipay') {
            $res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/alipay/ajax_url_xy.php";   //回调地址
        } elseif ($pay_code == 'weixin') {
            $res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/weixin/ajax_url_xy.php";   //回调地址
        }
        $res['subject'] = '店铺续约';
        $res['total_fee'] = (string)$order['amount'];
    } elseif ($flag == 3) {
        // 余额充值

        $order = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('pay_log')." WHERE log_id = '$out_trade_no'");
        if ($pay_code == 'alipay') {
            $res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/alipay/ajax_url.php";  //回调地址
        } elseif ($pay_code == 'weixin') {
            $res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/weixin/ajax_url.php";  //回调地址
        }
        $res['subject'] = '余额充值';
        $res['total_fee'] = (string)$order['order_amount'];
    } else {
        // 普通订单支付

        $order_id = $GLOBALS['db']->getOne("SELECT order_id FROM ".$GLOBALS['ecs']->table('pay_log')." WHERE log_id = '$out_trade_no'");
        //获取订单信息
        $order = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id' OR parent_order_id = '$order_id' limit 1");
        if ($pay_code == 'alipay') {
            $res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/alipay/ajax_url.php";  //回调地址
        } elseif ($pay_code == 'weixin') {
            $res['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/weixin/ajax_url.php";  //回调地址
        }
        $res['subject'] = '订单支付';
        $res['total_fee'] = (string)$order['order_amount'];
    }

    $res['out_trade_no'] = $out_trade_no;
    $res['pay_code'] = $pay_code;
    $res['redirect_url'] = "http://".$_SERVER['HTTP_HOST']."/mobile/apppay/redirect_url.php";   //回调地址

    $result = array();
    $result['code'] = 200;
    $result['msg'] = '';
    $result['data'] = $res;
    $json = new JSON;
    die($json->encode($result));
}
?>