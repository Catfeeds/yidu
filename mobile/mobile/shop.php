<?php



define('IN_ECS', true);



require(dirname(__FILE__) . '/includes/init.php');



/* 载入语言文件 */

require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/shop.php');



assign_template();



$action  = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';

$smarty->assign('action', $action);



$user_id = $_SESSION['user_id'];



/* 检查用户是否为店主 */

$shop_info = is_shopman($user_id);

$supplier_id = $shop_info['shop_id'];



if ($action == 'shop_xuyue') {

	if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {

		$smarty->assign('iswei', 1); // 判断是否为微信

	}elseif (substr($_SERVER['HTTP_USER_AGENT'],-3) == 'ios' || substr($_SERVER['HTTP_USER_AGENT'],-7) == 'android') {
        $smarty->assign('app', 1); // 判断是否APP
    }
    $sql = "SELECT shop_id, shop_code, chuzu_time, end_time FROM " . $GLOBALS['ecs']->table('agent_shop') . " WHERE user_id = " . $user_id;

    $shop_info = $GLOBALS['db']->getRow($sql);



	include_once (ROOT_PATH . 'includes/lib_clips.php');



	$smarty->assign('shop_renter_cost_12', floatval(4*$_CFG['shop_renter_cost']));

	$smarty->assign('shop_renter_cost_9', floatval(3*$_CFG['shop_renter_cost']));

	$smarty->assign('shop_renter_cost_6', floatval(2*$_CFG['shop_renter_cost']));

    $smarty->assign('shop_renter_cost_3', floatval(1*$_CFG['shop_renter_cost']));

	$smarty->assign('shop_info', $shop_info);

	$smarty->assign('payment', get_online_payment_list());

	$smarty->display('shop_xuyue.dwt');

}

/* 店铺续租支付 */

elseif ($action == 'xuyue_pay') {

    require_once (ROOT_PATH . 'includes/lib_clips.php');



	$payment_list = array('alipay', 'weixin', 'balance');

	$month_num_list = array(3,6,9,12);



    $shop_id = intval($_REQUEST['shop_id']);   //店铺ID

    $shop_code = trim($_REQUEST['shop_code']);   //店铺编号



	$pay_code = trim($_REQUEST['pay_code']);   //支付方式

    $month_num = intval($_REQUEST['month_num']);   //续费月数



    $shop_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('agent_shop') . " WHERE shop_id = " . $shop_id);

    if (empty($shop_info)) {

        show_message('店铺不存在');

    }



	if (!in_array($pay_code, $payment_list)) {

		show_message('请选择正确的支付方式');

	}



    $payment_info = $GLOBALS['db']->getRow("SELECT pay_id, pay_code, pay_name FROM " . $GLOBALS['ecs']->table('payment') . " WHERE pay_code = '" . $pay_code . "' AND enabled = 1");

    if (empty($payment_info)) {

        show_message('支付方式不存在');

    }



	if (!in_array($month_num, $month_num_list)) {

		show_message('请选择正确的续费月数');

	}



    $jidu = $month_num/3;   //续费季度数



    $cost = floatval($jidu*$_CFG['shop_renter_cost']); //续租费用



    // 更新出租时间为当前时间

    // 更新到期时间

    // 恢复店铺状态：

    // agent_shop.chuzu_status = 2

    // supplier.status = 1

    // supplier_street.is_show = 1

    // street.status = 1



    if ($cost > 0) {

        // 写入续约记录

        $log_id = insert_xuyue_pay_log($user_id, $shop_id, $cost, $month_num, $payment_info['pay_id'], $payment_info['pay_name'], $is_paid = 0);



        $char = getRandChar(5);

        $out_trade_no = $char.$log_id;    //支付订单号,防止重复



        // 余额支付

        if ($pay_code == 'balance') {

            // 检查余额是否足够

            $sur_amount = get_user_yue($user_id);

            if($cost > $sur_amount) {

                show_message('余额不足');

            }



            // 减少店主余额

            log_account_change($user_id, $cost * (-1), 0, 0, 0, '店铺'.$shop_code.'续费'.$month_num.'个月');



            // 代理商租金返利

            $sql = "SELECT b.agent_type FROM " . $GLOBALS['ecs']->table('agent_shop') . " a LEFT JOIN " . $GLOBALS['ecs']->table('users') . " b ON a.agent_id = b.user_id WHERE a.user_id = " . $user_id;

            $agent_type = $GLOBALS['db']->getOne($sql); //代理商类型 1小盘 2中盘 3大盘 4VIP

            $agent_shop_cost = get_agent_shop_cost($agent_type);    //代理商店铺管理费

            $agent_rebate = $jidu * ($_CFG['shop_renter_cost'] - $agent_shop_cost);   //店铺续租，代理商得到差价返利金额



            $info = '店铺'.$shop_code.'续费'.$month_num.'个月返利';

            // 写入返利记录

            $data['shop_id'] = $shop_id;

            $data['agent_id'] = $shop_info['agent_id'];

            $data['renew_money'] = $cost;

            $data['rebate_money'] = $agent_rebate;

            $data['pay_id'] = $payment_info['pay_id'];

            $data['pay_name'] = $payment_info['pay_name'];

            $data['add_time'] = gmtime();

            $data['change_desc'] = $info;

            $res = $db->autoExecute($ecs->table('agent_rebate_log'), $data, 'INSERT');



            // 增加代理商金额

            log_account_change($shop_info['agent_id'], $agent_rebate, 0, 0, 0, $info);



            // 更新出租时间

            $end_time = local_strtotime('+'.$month_num.'month', $shop_info['end_time']);    //到期时间



            $sql = "UPDATE " . $GLOBALS['ecs']->table('agent_shop') . " SET chuzu_time = '" . gmtime() . "', end_time = '" . $end_time . "', chuzu_status = 2 WHERE shop_id = " . $shop_id;

            $GLOBALS['db']->query($sql);



            // 更新店铺状态

            $sql = "UPDATE " . $GLOBALS['ecs']->table('supplier') . " SET status = 1 WHERE supplier_id = " . $shop_id;

            $GLOBALS['db']->query($sql);



            $sql = "UPDATE " . $GLOBALS['ecs']->table('supplier_street') . " SET is_show = 1, status = 1 WHERE supplier_id = " . $shop_id;

            $GLOBALS['db']->query($sql);



            // 更新记录

            $sql = "UPDATE " . $GLOBALS['ecs']->table('shop_xuyue_log') . " SET is_paid = 1, paid_time = '" . gmtime() . "' WHERE log_id = " . $log_id;

            $GLOBALS['db']->query($sql);



            show_message('续约成功', '返回店铺', 'user.php?act=shop_zuyue');

            /*ecs_header("Location: user.php?act=shop_zuyue\n");

            exit;*/

        }

        // 支付宝

        elseif ($pay_code == 'alipay') {

            if ($is_app == 1) {

                ecs_header("Location: ./apppay/pay.php?out_trade_no=$out_trade_no&pay_code=alipay&flag=2\n");

            } else {

                ecs_header("Location: ./pay/alipayapi.php?out_trade_no=$out_trade_no&total_fee=$cost&flag=2\n");

            }

            exit;

        }

        // 微信支付

        elseif ($pay_code == 'weixin') {
        //wenjun start 08-14
            
            if ($is_app == 1) {
                // print_r(123);exit;
                // ecs_header("Location: ./apppay/pay.php?out_trade_no=$out_trade_no&pay_code=weixin\n");
                ecs_header("Location: ./apppay/pay.php?out_trade_no=$out_trade_no&pay_code=weixin&flag=2\n");

            } else {
                $out_trade_no = 'flag'.$char.$log_id;    //支付订单号,防止重复
                // ecs_header("Location: ./weixinpay_xy.php?out_trade_no=$out_trade_no&flag=2\n");
                // ecs_header("Location: ./weixinpay.php?out_trade_no=$out_trade_no&flag=2\n");
                // ecs_header("Location: ./weixinpay.php?out_trade_no=$out_trade_no\n");
                ecs_header("Location: ./weixinpay_xy.php?out_trade_no=$out_trade_no\n");

            }
        //end
            exit;

        }

    }

}

/* 查看订单列表 */

elseif ($action == 'order_list') {

	check_shop($supplier_id);



	include_once (ROOT_PATH . 'includes/lib_transaction.php');

	include_once (ROOT_PATH . 'includes/lib_order.php');



	$composite_status = intval($_REQUEST['composite_status']);	//状态



	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;



	switch ($composite_status) {

		case 0:	//全部订单

			$where = " WHERE 1 AND o.supplier_id = $supplier_id ";

			break;

		case 1:	//待发货订单

			$where = " WHERE 1 AND o.supplier_id = " . $supplier_id . " AND o.pay_status = 2 AND o.shipping_status <> 1 AND o.order_status = 1 AND pm.pay_code <> 'cod' ";

			break;

		case 2:	//售后/退款

			$where = " WHERE 1 AND o.order_status = 1 AND o.supplier_id = " . $supplier_id . " AND bo.status_back = 5 ";

			break;

		default:

			break;

	}

    if ($composite_status == 2) {

        $order_list = get_shop_back_orders($supplier_id, $where, $page, $action);

    } else {

        $order_list = get_shop_orders($supplier_id, $where, $page, $action);

    }



	$smarty->assign('order_list', $order_list['order_list']);

	$smarty->assign('pager', $order_list['pager']);

	$smarty->display('shop_order_list_'.$composite_status.'.dwt');

}

/* 退款操作 */

elseif ($action == 'back_refund') {

    check_shop($supplier_id);



    $back_id = intval($_REQUEST['back_id']);

    $status_back = '5';

    $status_refund = '1';

    // 通过申请

    update_back($back_id, $status_back, $status_refund);



    $action_note = '店主确认退款';

    $order = back_order_info($back_id);

    $sql = "update ". $ecs->table('back_goods') ." set status_refund='$status_refund'  where back_id='$back_id' and (back_type='0' or back_type='4') ";

    $db->query($sql);

    $refund_money_2 = $order['refund_money_1'];

    $refund_desc = '';

    $refund_type = 1;   //1退回余额 2线下退款

    $sql2 = "update ". $ecs->table('back_order') ." set  status_refund='$status_refund',  refund_money_2='$refund_money_2', refund_type='$refund_type', refund_desc='$refund_desc' where back_id='$back_id' ";

    $db->query($sql2);



    /* 退回用户余额 */

    if ($refund_type == '1')

    {

        $desc_back = "订单". $order['order_id'] .'退款';

        log_account_change($order['user_id'], $refund_money_2,0,0,0, $desc_back );

        //是否开启余额变动给客户发短信-退款

        if($_CFG['sms_user_money_change'] == 1)

        {

            $sql = "SELECT user_money,mobile_phone FROM " . $ecs->table('users') . " WHERE user_id = '" . $order['user_id'] . "'";

            $users = $db->getRow($sql);

            $content = sprintf($_CFG['sms_return_goods_tpl'],$refund_money_2,$users['user_money'],$_CFG['sms_sign']);

            if($users['mobile_phone'])

            {

                include_once('../send.php');

                sendSMS($users['mobile_phone'],$content);

            }

        }

    }



    ecs_header("Location: shop.php?act=order_list&composite_status=2\n");

    exit;

}

/* 退款操作 */

elseif ($action == 'back_detail') {

    check_shop($supplier_id);



    $back_id = intval($_REQUEST['back_id']);



    $sql = "SELECT * FROM " . $ecs->table('back_order') . " WHERE back_id = " . $back_id . " AND supplier_id = " . $supplier_id;

    $back_info = $db->getRow($sql);



    $smarty->assign('back_info', $back_info);

    $smarty->display('shop_back_detail.dwt');



}

/* 查看订单详情 */

elseif ($action == 'order_detail') {

	include_once (ROOT_PATH . 'includes/lib_transaction.php');

	include_once (ROOT_PATH . 'includes/lib_payment.php');

	include_once (ROOT_PATH . 'includes/lib_order.php');

	include_once (ROOT_PATH . 'includes/lib_clips.php');

	include_once (ROOT_PATH . 'kuaidi/kuaidi.php');



	$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

	/* 订单详情 */

	$order = get_shop_order_detail($order_id, $supplier_id);



	if($order === false)

	{

		$GLOBALS['err']->add('订单不存在');

		$GLOBALS['err']->show($_LANG['back_home_lnk'], './');

		exit();

	}



	/* 是否显示添加到购物车 */

	if($order['extension_code'] != 'group_buy' && $order['extension_code'] != 'exchange_goods')

	{

		$smarty->assign('allow_to_cart', 1);

	}



	/* 订单商品 */

	$goods_list = order_goods($order_id);

	foreach($goods_list as $key => $value)

	{

		$goods_list[$key]['market_price'] = price_format($value['market_price'], false);

		$goods_list[$key]['goods_price'] = price_format($value['goods_price'], false);

		$goods_list[$key]['subtotal'] = price_format($value['subtotal'], false);

	}



	/* 设置能否修改使用余额数 */

	if($order['order_amount'] > 0)

	{

		if($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_CONFIRMED)

		{

			$user = user_info($order['user_id']);

			if($user['user_money'] + $user['credit_line'] > 0)

			{

				$smarty->assign('allow_edit_surplus', 1);

                                $order_surplus = floatval($order['order_amount'])>floatval($user['user_money'])?$user['user_money']:$order['order_amount'];

				$smarty->assign('order_surplus', $order_surplus);

                                $smarty->assign('max_surplus', sprintf($_LANG['max_surplus'], $user['user_money']));

			}

		}

	}



	/* 未发货，未付款时允许更换支付方式 */

	if($order['order_amount'] > 0 && $order['pay_status'] == PS_UNPAYED && $order['shipping_status'] == SS_UNSHIPPED)

	{

		$payment_list = available_payment_list(false, 0, true);



		/* 过滤掉当前支付方式和余额支付方式 */

		if(is_array($payment_list))

		{

			foreach($payment_list as $key => $payment)

			{

				//if($payment['pay_id'] == $order['pay_id'] || $payment['pay_code'] == 'balance')

                if($payment['pay_code'] == 'balance')

				{

					unset($payment_list[$key]);

				}

			}

		}

		$smarty->assign('payment_list', $payment_list);

	}



    $handler = get_shop_order_handler($order);

	/* 订单 支付 配送 状态语言项 */

	$order['order_status'] = $_LANG['os'][$order['order_status']];

	$order['pay_status'] = $_LANG['ps'][$order['pay_status']];

	$order['shipping_status'] = $_LANG['ss'][$order['shipping_status']];

        $order['topay'] = in_array($order['pay_id'],payment_id_list(true));

	// 快递跟踪

	$kuaidi = new Express();

    $sql = "SELECT delivery_id,shipping_name,invoice_no  FROM ". $GLOBALS['ecs']->table('delivery_order'). " WHERE order_id = '$order_id'AND user_id = '$user_id'";

    $wuliu = $GLOBALS['db']->getAll($sql);

    $kuaidi_list = array();

    foreach($wuliu as $key=>$value){

        if($value['shipping_name'] == '同城快递'){

                $result = getkosorder($value['invoice_no']);

        }else{

                $result = $kuaidi->getorder($value['shipping_name'],$value['invoice_no']);

        }

        $kuaidi_list[$value['delivery_id']]['data'] = $result['data'][0];

        $kuaidi_list[$value['delivery_id']]['shipping_name'] = $value['shipping_name'];

        $kuaidi_list[$value['delivery_id']]['invoice_no'] = $value['invoice_no'];

    }



	$smarty->assign('kuaidi_list', $kuaidi_list);

	$smarty->assign('order', $order);

    $smarty->assign('handler',$handler);

	$smarty->assign('goods_list', $goods_list);

	$smarty->assign('lang', $_LANG);

	$smarty->display('shop_order_detail.dwt');

}

/* 取消订单 */

elseif ($action == 'cancel_order') {

	include_once (ROOT_PATH . 'includes/lib_transaction.php');

	include_once (ROOT_PATH . 'includes/lib_order.php');



	$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;



	if(cancel_shop_order($order_id, $supplier_id))

	{

		ecs_header("Location: shop.php?act=order_list\n");

		exit();

	}

	else

	{

		$GLOBALS['err']->add('取消订单失败');

		$GLOBALS['err']->show($_LANG['order_list_lnk'], 'shop.php?act=order_list');

	}

}

/* 输入快递单号 */

elseif ($action == 'shipping_index') {

	$order_id = intval($_REQUEST['order_id']);	//订单ID

	$smarty->assign('order_id', $order_id);

	$smarty->display('shipping_index.dwt');

}

/* 一键发货 */

elseif ($action == 'to_shipping') {

	include_once (ROOT_PATH . 'includes/lib_order.php');

	$user_id = $_SESSION['user_id'];

	$invoice_no = empty($_REQUEST['invoice_no']) ? '' : trim($_REQUEST['invoice_no']);  //快递单号

	$order_id = intval($_REQUEST['order_id']);	//订单ID

	$action_note = trim($_REQUEST['action_note']);	//操作备注

	if (!empty($invoice_no)) {

		/* 查询：根据订单id查询订单信息 */

		if (!empty($order_id)) {

			$order = shipping_order_info($order_id);

		} else{

			$GLOBALS['err']->add('订单不存在');

			$GLOBALS['err']->show($_LANG['order_list_lnk'], 'shop.php?act=order_list');

		}



		/* 查询：如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */

		$sql = "SELECT agency_id FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE user_id = " . $user_id;

		$agency_id = $GLOBALS['db']->getOne($sql);

		if ($agency_id > 0)

		{

		    if ($order['agency_id'] != $agency_id)

		    {

		        $GLOBALS['err']->add('您没有执行此项操作的权限!');

		        $GLOBALS['err']->show($_LANG['order_list_lnk'], 'shop.php?act=order_list');

		    }

		}



		/* 查询：取得用户名 */

		if ($order['user_id'] > 0) {

			$user = shipping_user_info($order['user_id']);

			if (!empty($user)) {

				$order['user_name'] = $user['user_name'];

			}

		}



		/* 查询：取得区域名 */

		$order['region'] = $GLOBALS['db']->getOne($sql);



		/* 查询：其他处理 */

		$order['order_time'] = local_date('Y-m-d H:i:s', $order['add_time']);

	    $order['invoice_no'] = $order['shipping_status'] == SS_UNSHIPPED || $order['shipping_status'] == SS_PREPARING ? $_LANG['ss'][SS_UNSHIPPED] : $order['invoice_no'];



	    /* 查询：是否保价 */

	    $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;



	    /* 查询：是否存在实体商品 */

	    $exist_real_goods = shipping_exist_real_goods($order_id);



	    /* 查询：取得订单商品 */

	    $_goods = shipping_get_order_goods(array('order_id' => $order['order_id'], 'order_sn' =>$order['order_sn']));



	    $attr = $_goods['attr'];

	    $goods_list = $_goods['goods_list'];

	    unset($_goods);



	    /* 查询：商品已发货数量 此单可发货数量 */

        if ($goods_list) {

			foreach ($goods_list as $key=>$goods_value) {

                if (!$goods_value['goods_id']) {

                    continue;

                }



                /* 超级礼包 */

                if (($goods_value['extension_code'] == 'package_buy') && (count($goods_value['package_goods_list']) > 0)) {

                    $goods_list[$key]['package_goods_list'] = shipping_package_goods($goods_value['package_goods_list'], $goods_value['goods_number'], $goods_value['order_id'], $goods_value['extension_code'], $goods_value['goods_id']);



                    foreach ($goods_list[$key]['package_goods_list'] as $pg_key => $pg_value) {

                        $goods_list[$key]['package_goods_list'][$pg_key]['readonly'] = '';

                        /* 使用库存 是否缺货 */

                        if ($pg_value['storage'] <= 0 && $_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_SHIP) {

                            $goods_list[$key]['package_goods_list'][$pg_key]['send'] = $_LANG['act_good_vacancy'];

                            $goods_list[$key]['package_goods_list'][$pg_key]['readonly'] = 'readonly="readonly"';

                        }

                        /* 将已经全部发货的商品设置为只读 */

                        elseif ($pg_value['send'] <= 0) {

                            $goods_list[$key]['package_goods_list'][$pg_key]['send'] = $_LANG['act_good_delivery'];

                            $goods_list[$key]['package_goods_list'][$pg_key]['readonly'] = 'readonly="readonly"';

                        }

                    }

                } else {

                    $goods_list[$key]['sended'] = $goods_value['send_number'];

                    $goods_list[$key]['sended'] = $goods_value['goods_number'];

                    $goods_list[$key]['send'] = $goods_value['goods_number'] - $goods_value['send_number'];

					$goods_list[$key]['readonly'] = '';

                    /* 是否缺货 */

                    if ($goods_value['storage'] <= 0 && $_CFG['use_storage'] == '1'  && $_CFG['stock_dec_time'] == SDT_SHIP) {

                        $goods_list[$key]['send'] = $_LANG['act_good_vacancy'];

                        $goods_list[$key]['readonly'] = 'readonly="readonly"';

                    } elseif ($goods_list[$key]['send'] <= 0) {

                        $goods_list[$key]['send'] = $_LANG['act_good_delivery'];

                        $goods_list[$key]['readonly'] = 'readonly="readonly"';

                    }

                }

            }

        }



		$suppliers_id = 0;



		$delivery['order_sn'] = trim($order['order_sn']);

		$delivery['add_time'] = trim($order['order_time']);

		$delivery['user_id'] = intval(trim($order['user_id']));

		$delivery['how_oos'] = trim($order['how_oos']);

		$delivery['shipping_id'] = trim($order['shipping_id']);

		$delivery['shipping_fee'] = trim($order['shipping_fee']);

		$delivery['consignee'] = trim($order['consignee']);

		$delivery['address'] = trim($order['address']);

		$delivery['country'] = intval(trim($order['country']));

		$delivery['province'] = intval(trim($order['province']));

		$delivery['city'] = intval(trim($order['city']));

		$delivery['district'] = intval(trim($order['district']));

		$delivery['sign_building'] = trim($order['sign_building']);

		$delivery['email'] = trim($order['email']);

		$delivery['zipcode'] = trim($order['zipcode']);

		$delivery['tel'] = trim($order['tel']);

		$delivery['mobile'] = trim($order['mobile']);

		$delivery['best_time'] = trim($order['best_time']);

		$delivery['postscript'] = trim($order['postscript']);

		$delivery['how_oos'] = trim($order['how_oos']);

		$delivery['insure_fee'] = floatval(trim($order['insure_fee']));

		$delivery['shipping_fee'] = floatval(trim($order['shipping_fee']));

		$delivery['agency_id'] = intval(trim($order['agency_id']));

		$delivery['shipping_name'] = trim($order['shipping_name']);



    	/* 查询订单信息 */

    	$order = shipping_order_info($order_id);



    	/* 初始化提示信息 */

   		$msg = '';



        /* 取得订单商品 */

        $_goods = shipping_get_order_goods(array('order_id' => $order_id, 'order_sn' => $delivery['order_sn']));

        $goods_list = $_goods['goods_list'];



        /* 检查此单发货商品库存缺货情况 */

        /* $goods_list已经过处理 超值礼包中商品库存已取得 */

        $virtual_goods = array();

        $package_virtual_goods = array();

        /* 生成发货单 */

        /* 获取发货单号和流水号 */

        $delivery['delivery_sn'] = get_delivery_sn();

        $delivery_sn = $delivery['delivery_sn'];



        /* 获取当前操作员 */

        $action_user = shipping_user_info($user_id);

        $delivery['action_user'] = $action_user['user_name'];



        /* 获取发货单生成时间 */

 		define('GMTIME_UTC', gmtime());

        $delivery['update_time'] = GMTIME_UTC;

        $delivery_time = $delivery['update_time'];

        $sql ="select add_time from ". $GLOBALS['ecs']->table('order_info') ." WHERE order_sn = '" . $delivery['order_sn'] . "'";

        $delivery['add_time'] =  $GLOBALS['db']->GetOne($sql);

        /* 获取发货单所属供应商 */

        $delivery['suppliers_id'] = $suppliers_id;



        /* 设置默认值 */

        $delivery['status'] = 2; // 正常

        $delivery['order_id'] = $order_id;



        /* 过滤字段项 */

        $filter_fileds = array(

        	'order_sn', 'add_time', 'user_id', 'how_oos', 'shipping_id', 'shipping_fee',

        	'consignee', 'address', 'country', 'province', 'city', 'district', 'sign_building',

        	'email', 'zipcode', 'tel', 'mobile', 'best_time', 'postscript', 'insure_fee',

        	'agency_id', 'delivery_sn', 'action_user', 'update_time',

        	'suppliers_id', 'status', 'order_id', 'shipping_name'

        );

        $_delivery = array();

        foreach ($filter_fileds as $value) {

            $_delivery[$value] = $delivery[$value];

        }

        /* 发货单入库 */

        $query = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('delivery_order'), $_delivery, 'INSERT', '', 'SILENT');

        $delivery_id = $GLOBALS['db']->insert_id();

		$sql_back_old = $GLOBALS['db']->getOne("SELECT back_id FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE order_id = " . $order['order_id'] . " AND back_type = 4 AND status_back < 6 AND status_back != 3");

		if (!empty($sql_back_old)) {

			$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('back_order') . " SET status_back = 6 WHERE back_id = " . $sql_back_old);

			$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('back_goods') . " SET status_back = 6 WHERE back_id = " . $sql_back_old);

		}

        if ($delivery_id) {

            $delivery_goods = array();

            //发货单商品入库

            if (!empty($goods_list)) {

                foreach ($goods_list as $value) {

                    // 商品（实货）（虚货）

                    if (empty($value['extension_code']) || $value['extension_code'] == 'virtual_card') {

                        $delivery_goods = array(

                        	'delivery_id' => $delivery_id,

                        	'goods_id' => $value['goods_id'],

                        	'product_id' => $value['product_id'],

                        	'product_sn' => $value['product_sn'],

                        	'goods_id' => $value['goods_id'],

                        	'goods_name' => $value['goods_name'],

                        	'brand_name' => $value['brand_name'],

                        	'goods_sn' => $value['goods_sn'],

                        	'send_number' => $value['goods_number'],

                        	'parent_id' => 0,

                        	'is_real' => $value['is_real'],

                        	'goods_attr' => $value['goods_attr']

                        );

                        /* 如果是货品 */

                        if (!empty($value['product_id'])) {

                            $delivery_goods['product_id'] = $value['product_id'];

                        }

                        $query = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('delivery_goods'), $delivery_goods, 'INSERT', '', 'SILENT');

						$sql = "UPDATE ".$GLOBALS['ecs']->table('order_goods'). "

						SET send_number = " . $value['goods_number'] . "

						WHERE order_id = '" . $value['order_id'] . "'

						AND goods_id = '" . $value['goods_id'] . "' ";

						$GLOBALS['db']->query($sql, 'SILENT');

					}

                    // 商品（超值礼包）

                    elseif ($value['extension_code'] == 'package_buy') {

                        foreach ($value['package_goods_list'] as $pg_key => $pg_value) {

                            $delivery_pg_goods = array(

                            	'delivery_id' => $delivery_id,

                            	'goods_id' => $pg_value['goods_id'],

                            	'product_id' => $pg_value['product_id'],

                            	'product_sn' => $pg_value['product_sn'],

                            	'goods_name' => $pg_value['goods_name'],

                            	'brand_name' => '',

                            	'goods_sn' => $pg_value['goods_sn'],

                            	'send_number' => $value['goods_number'],

                            	'parent_id' => $value['goods_id'], // 礼包ID

                            	'extension_code' => $value['extension_code'], // 礼包

                            	'is_real' => $pg_value['is_real']

                            );

                            $query = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('delivery_goods'), $delivery_pg_goods, 'INSERT', '', 'SILENT');

                            $sql = "UPDATE ".$GLOBALS['ecs']->table('order_goods'). "

                            SET send_number = " . $value['goods_number'] . "

                            WHERE order_id = '" . $value['order_id'] . "'

                            AND goods_id = '" . $pg_value['goods_id'] . "' ";

                            $GLOBALS['db']->query($sql, 'SILENT');

                        }

                    }

                }

            }

        } else {

            /* 操作失败 */

            $GLOBALS['err']->add('操作失败');

            $GLOBALS['err']->show($_LANG['order_list_lnk'], 'shop.php?act=order_list');

        }

        unset($filter_fileds, $delivery, $_delivery, $order_finish);



        /* 定单信息更新处理 */

        if (true) {

            /* 标记订单为已确认 "发货中" */

            /* 更新发货时间 */

            $order_finish = shipping_get_order_finish($order_id);

            $shipping_status = SS_SHIPPED_ING;

            if ($order['order_status'] != OS_CONFIRMED && $order['order_status'] != OS_SPLITED && $order['order_status'] != OS_SPLITING_PART) {

                $arr['order_status']    = OS_CONFIRMED;

                $arr['confirm_time']    = GMTIME_UTC;

            }

            $arr['order_status'] = $order_finish ? OS_SPLITED : OS_SPLITING_PART; // 全部分单、部分分单

            $arr['shipping_status']     = $shipping_status;

            update_order($order_id, $arr);

		}



        /* 记录log */

        order_action($order['order_sn'], $arr['order_status'], $shipping_status, $order['pay_status'], $action_note);



		/* 根据发货单id查询发货单信息 */

	    if (!empty($delivery_id)) {

	        $delivery_order = shipping_delivery_order_info($delivery_id);

	    } elseif (!empty($order_sn)) {

	    	$delivery_id = $GLOBALS['db']->getOne("SELECT delivery_id FROM " . $GLOBALS['ecs']->table('delivery_order') . " WHERE order_sn = " . $order_sn );

	    	$delivery_order = shipping_delivery_order_info($delivery_id);

	    } else {

        	$GLOBALS['err']->add('订单不存在');

			$GLOBALS['err']->show($_LANG['order_list_lnk'], 'shop.php?act=order_list');

		}



		/* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */

		$sql = "SELECT agency_id FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE user_id = '" . $_SESSION['admin_id'] . "'";

		$agency_id = $GLOBALS['db']->getOne($sql);

		if ($agency_id > 0) {

			if ($delivery_order['agency_id'] != $agency_id) {

				$GLOBALS['err']->add('您没有执行此项操作的权限');

				$GLOBALS['err']->show($_LANG['order_list_lnk'], 'shop.php?act=order_list');

        	}

	        /* 取当前办事处信息 */

	        $sql = "SELECT agency_name FROM " . $GLOBALS['ecs']->table('agency') . " WHERE agency_id = '$agency_id' LIMIT 0, 1";

	        $agency_name = $GLOBALS['db']->getOne($sql);

	        $delivery_order['agency_name'] = $agency_name;

    	}



	    /* 取得用户名 */

	    if ($delivery_order['user_id'] > 0) {

	    	$user = shipping_user_info($delivery_order['user_id']);

	        if (!empty($user)) {

	            $delivery_order['user_name'] = $user['user_name'];

	        }

	    }



	    /* 取得区域名 */

	    $sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .

	                "'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .

	            "FROM " . $GLOBALS['ecs']->table('order_info') . " AS o " .

	                "LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS c ON o.country = c.region_id " .

	                "LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS p ON o.province = p.region_id " .

	                "LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS t ON o.city = t.region_id " .

	                "LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS d ON o.district = d.region_id " .

	            "WHERE o.order_id = '" . $delivery_order['order_id'] . "'";

	    $delivery_order['region'] = $GLOBALS['db']->getOne($sql);



	    /* 是否保价 */

	    $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;



	    /* 取得发货单商品 */

	    $goods_sql = "SELECT *

	                  FROM " . $GLOBALS['ecs']->table('delivery_goods') . "

	                  WHERE delivery_id = " . $delivery_order['delivery_id'];

	    $goods_list = $GLOBALS['db']->getAll($goods_sql);



	    /* 是否存在实体商品 */

	    $exist_real_goods = 0;

	    if ($goods_list) {

	        foreach ($goods_list as $value) {

	            if ($value['is_real']) {

	                $exist_real_goods++;

	            }

	        }

	    }



	    /* 取得订单操作记录 */

	    $act_list = array();

	    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('order_action') . " WHERE order_id = '" . $delivery_order['order_id'] . "' AND action_place = 1 ORDER BY log_time DESC,action_id DESC";

	    $res = $GLOBALS['db']->query($sql);

	    while ($row = $GLOBALS['db']->fetchRow($res)) {

	        $row['order_status']    = $_LANG['os'][$row['order_status']];

	        $row['pay_status']      = $_LANG['ps'][$row['pay_status']];

	        $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? $_LANG['ss_admin'][SS_SHIPPED_ING] : $_LANG['ss'][$row['shipping_status']];

	        $row['action_time']     = local_date($_CFG['time_format'], $row['log_time']);

	        $act_list[] = $row;

	    }



		/*同步发货*/

		/*判断支付方式是否支付宝*/

		$alipay    = false;

		$order     = shipping_order_info($delivery_order['order_id']);  //根据订单ID查询订单信息，返回数组$order

		$payment   = payment_info($order['pay_id']);           //取得支付方式信息



	    /* 定义当前时间 */

	    define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳



	    /* 根据发货单id查询发货单信息 */

	    if (!empty($delivery_id)) {

	        $delivery_order = shipping_delivery_order_info($delivery_id);

	    } else {

	    	$GLOBALS['err']->add('订单不存在');

			$GLOBALS['err']->show($_LANG['order_list_lnk'], 'shop.php?act=order_list');

	    }



	    /* 查询订单信息 */

	    $order = shipping_order_info($order_id);



	    /* 检查此单发货商品库存缺货情况 */

	    $virtual_goods = array();

	    $delivery_stock_sql = "SELECT DG.goods_id, DG.is_real, DG.product_id, SUM(DG.send_number) AS sums, IF(DG.product_id > 0, P.product_number, G.goods_number) AS storage, G.goods_name, DG.send_number

	        FROM " . $GLOBALS['ecs']->table('delivery_goods') . " AS DG, " . $GLOBALS['ecs']->table('goods') . " AS G, " . $GLOBALS['ecs']->table('products') . " AS P

	        WHERE DG.goods_id = G.goods_id

	        AND DG.delivery_id = '$delivery_id'

	        AND DG.product_id = P.product_id

	        GROUP BY DG.product_id ";



	    $delivery_stock_result = $GLOBALS['db']->getAll($delivery_stock_sql);



	    /* 如果商品存在规格就查询规格，如果不存在规格按商品库存查询 */

	    if(!empty($delivery_stock_result)) {

	        foreach ($delivery_stock_result as $value) {

	            if (($value['sums'] > $value['storage'] || $value['storage'] <= 0) && (($_CFG['use_storage'] == '1'  && $_CFG['stock_dec_time'] == SDT_SHIP) || ($_CFG['use_storage'] == '0' && $value['is_real'] == 0))) {

	                /* 操作失败 */

	                $GLOBALS['err']->add('操作失败');

	                $GLOBALS['err']->show($_LANG['order_list_lnk'], 'shop.php?act=order_list');

	                break;

	            }



	            /* 虚拟商品列表 virtual_card*/

	            if ($value['is_real'] == 0) {

	                $virtual_goods[] = array(

	                	'goods_id' => $value['goods_id'],

	                	'goods_name' => $value['goods_name'],

	                	'num' => $value['send_number']

	                );

	            }

	        }

	    } else {

	        $delivery_stock_sql = "SELECT DG.goods_id, DG.is_real, SUM(DG.send_number) AS sums, G.goods_number, G.goods_name, DG.send_number

	        FROM " . $GLOBALS['ecs']->table('delivery_goods') . " AS DG, " . $GLOBALS['ecs']->table('goods') . " AS G

	        WHERE DG.goods_id = G.goods_id

	        AND DG.delivery_id = '$delivery_id'

	        GROUP BY DG.goods_id ";

	        $delivery_stock_result = $GLOBALS['db']->getAll($delivery_stock_sql);

	        foreach ($delivery_stock_result as $value) {

	            if (($value['sums'] > $value['goods_number'] || $value['goods_number'] <= 0) && (($_CFG['use_storage'] == '1'  && $_CFG['stock_dec_time'] == SDT_SHIP) || ($_CFG['use_storage'] == '0' && $value['is_real'] == 0))) {

	                /* 操作失败 */

	                $GLOBALS['err']->add('操作失败');

	                $GLOBALS['err']->show($_LANG['order_list_lnk'], 'shop.php?act=order_list');

	                break;

	            }



	            /* 虚拟商品列表 virtual_card*/

	            if ($value['is_real'] == 0) {

	                $virtual_goods[] = array(

	                	'goods_id' => $value['goods_id'],

	                	'goods_name' => $value['goods_name'],

	                	'num' => $value['send_number'],

	                );

	            }

	        }

	    }



	    /* 发货 */

	    /* 处理虚拟卡 商品（虚货） */

	    if (is_array($virtual_goods) && count($virtual_goods) > 0) {

	        foreach ($virtual_goods as $virtual_value) {

	            virtual_card_shipping($virtual_value,$order['order_sn'], $msg, 'split');

	        }

	    }



	    /* 如果使用库存，且发货时减库存，则修改库存 */

	    if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_SHIP) {

	        foreach ($delivery_stock_result as $value) {

	            /* 商品（实货）、超级礼包（实货） */

	            if ($value['is_real'] != 0) {

	                //（货品）

	                if (!empty($value['product_id'])) {

	                    $minus_stock_sql = "UPDATE " . $GLOBALS['ecs']->table('products') . "

	                                        SET product_number = product_number - " . $value['sums'] . "

	                                        WHERE product_id = " . $value['product_id'];

	                    $GLOBALS['db']->query($minus_stock_sql, 'SILENT');

	                }



	                $minus_stock_sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . "

	                                    SET goods_number = goods_number - " . $value['sums'] . "

	                                    WHERE goods_id = " . $value['goods_id'];



	                $GLOBALS['db']->query($minus_stock_sql, 'SILENT');

	            }

	        }

	    }



	    /* 修改发货单信息 */

	    $invoice_no = trim($invoice_no);

	    $_delivery['invoice_no'] = $invoice_no;

	    $_delivery['status'] = 0; // 0，为已发货

	    $query = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('delivery_order'), $_delivery, 'UPDATE', "delivery_id = $delivery_id", 'SILENT');

	    if (!$query) {

	        /* 操作失败 */

	        $GLOBALS['err']->add('操作失败');

	        $GLOBALS['err']->show($_LANG['order_list_lnk'], 'shop.php?act=order_list');

	    }



	    /* 标记订单为已确认 "已发货" */

	    /* 更新发货时间 */

	    $order_finish = shipping_get_all_delivery_finish($order_id);

	    $shipping_status = ($order_finish == 1) ? SS_SHIPPED : SS_SHIPPED_PART;

	    $arr['shipping_status']     = $shipping_status;

	    $arr['shipping_time']       = GMTIME_UTC; // 发货时间

	    $arr['invoice_no']          = trim($order['invoice_no'] . '<br>' . $invoice_no, '<br>');

	    update_order($order_id, $arr);



	    /* 发货单发货记录log */

	    order_action($order['order_sn'], OS_CONFIRMED, $shipping_status, $order['pay_status'], $action_note, null, 1);

	    /* 如果当前订单已经全部发货 */

	    if ($order_finish) {

	        /* 如果订单用户不为空，计算积分，并发给用户；发红包 */

	        if ($order['user_id'] > 0) {

	            /* 取得用户信息 */

	            $user = shipping_user_info($order['user_id']);

	        }

	    }



	    /* 操作成功 */

	    ecs_header("Location: shop.php?act=order_list\n");

		exit();

    }

}

/* 评价处理列表 */

elseif ($action == 'comment_list') {

	check_shop($supplier_id);



    $sql = "SELECT count(*) FROM " .$GLOBALS['ecs']->table('comment'). " AS c left join ". $GLOBALS['ecs']->table('goods') .

				"  AS g on c.id_value=g.goods_id WHERE c.status = 0 AND c.parent_id = 0 AND c.comment_type = 0 AND g.supplier_id='". $supplier_id ."' ";

    $record_count = $GLOBALS['db']->getOne($sql);

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    // 分页函数

	$pager = get_pager('shop.php', array( 'act' => $action ), $record_count, $page);



    /* 获取评论数据 */

    $sql  = "SELECT c.*, g.goods_name AS title, g.goods_thumb FROM " .$GLOBALS['ecs']->table('comment'). " AS c left join ". $GLOBALS['ecs']->table('goods') . " AS g on c.id_value = g.goods_id WHERE c.status = 0 AND c.parent_id = 0 AND c.comment_type = 0 AND g.supplier_id='". $supplier_id . "' ORDER BY add_time desc ". " limit " . $pager['start'] . ", " . $pager['size'];



    $comment_list = $GLOBALS['db']->getAll($sql);



    foreach ($comment_list as $key => $value) {

    	$comment_list[$key]['add_time'] = local_date('Y-m-d H:i', $value['add_time']);

    }

    $smarty->assign('comment_list', $comment_list);

	$smarty->assign('pager', $pager);

	$smarty->display('shop_comment_list.dwt');

}

/* 评价回复页面 */

elseif ($action == 'comment_index') {

	$comment_id = intval($_REQUEST['comment_id']);

	$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('comment') . " WHERE comment_id = " . $comment_id;

	$comment_info = $GLOBALS['db']->getRow($sql);



    $smarty->assign('comment_id', $comment_id);

    $smarty->assign('id_value', $comment_info['id_value']);

    $smarty->assign('user_name', $comment_info['user_name']);

	$smarty->display('comment_index.dwt');

}



/* 评价回复提交 */

elseif ($action == 'comment') {

	/* 获取IP地址 */

	$ip = real_ip();



	/* 获得评论是否有回复 */

	$sql = "SELECT comment_id, content, parent_id FROM ".$GLOBALS['ecs']->table('comment').

	       " WHERE parent_id = '$_REQUEST[comment_id]'";

	$reply_info = $GLOBALS['db']->getRow($sql);



	if (!empty($reply_info['content']))

	{

	    /* 更新回复的内容 */

	    $sql = "UPDATE ".$GLOBALS['ecs']->table('comment')." SET ".

	           "email     = '', ".

	           "user_name = '$_POST[user_name]', ".

	           "content   = '$_POST[content]', ".

	           "add_time  =  '" . gmtime() . "', ".

	           "ip_address= '$ip', ".

	           "status    = 0".

	           " WHERE comment_id = '".$reply_info['comment_id']."'";

	}

	else

	{

	    /* 插入回复的评论内容 */

	    $sql = "INSERT INTO ".$GLOBALS['ecs']->table('comment')." (comment_type, id_value, email, user_name , ".

	                "content, add_time, ip_address, status, parent_id) ".

	           "VALUES('0', '$_POST[id_value]','', " .

	                "'$_SESSION[user_name]','$_POST[content]','" . gmtime() . "', '$ip', '0', '$_POST[comment_id]')";

	}

	$GLOBALS['db']->query($sql);



	/* 更新当前的评论状态为已回复并且可以显示此条评论 */

	$sql = "UPDATE " .$GLOBALS['ecs']->table('comment'). " SET status = 1 WHERE comment_id = '$_POST[comment_id]'";

	$GLOBALS['db']->query($sql);



	ecs_header("Location: shop.php?act=comment_list\n");

	exit;

}

/* 商品申请列表 */

elseif ($action == 'goods_apply_list') {

	check_shop($supplier_id);



	$sh_type = intval($_REQUEST['sh_type']);

	// 待审核数

	$wait_sh = $GLOBALS['db']->getOne("SELECT count(*) FROM " .$GLOBALS['ecs']->table('shop_goods_apply'). " WHERE is_pass = 0 AND shop_id='". $supplier_id ."' ");

	// 审核通过

	$sh_ok = $GLOBALS['db']->getOne("SELECT count(*) FROM " .$GLOBALS['ecs']->table('shop_goods_apply'). " WHERE is_pass = 1 AND shop_id='". $supplier_id ."' ");

	// 审核不通过

	$sh_fail = $GLOBALS['db']->getOne("SELECT count(*) FROM " .$GLOBALS['ecs']->table('shop_goods_apply'). " WHERE is_pass = 2 AND shop_id='". $supplier_id ."' ");



    $sql = "SELECT count(*) FROM " .$GLOBALS['ecs']->table('shop_goods_apply'). " WHERE is_pass = '" . $sh_type . "' AND shop_id='". $supplier_id ."' ";

    $record_count = $GLOBALS['db']->getOne($sql);

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    // 分页函数

	$pager = get_pager('shop.php', array( 'act' => $action ), $record_count, $page);



    // 获取评论数据

    $sql  = "SELECT * FROM " .$GLOBALS['ecs']->table('shop_goods_apply'). " WHERE is_pass = '" . $sh_type . "' AND shop_id='". $supplier_id . "' ORDER BY add_time desc ". " limit " . $pager['start'] . ", " . $pager['size'];



    $goods_apply_list = $GLOBALS['db']->getAll($sql);



    foreach ($goods_apply_list as $key => $value) {

    	$goods_apply_list[$key]['add_time'] = local_date('Y-m-d H:i', $value['add_time']);

    	$goods_apply_list[$key]['goods_price'] = price_format($value['goods_price']);

    }



    $smarty->assign('wait_sh', $wait_sh);

    $smarty->assign('sh_ok', $sh_ok);

    $smarty->assign('sh_fail', $sh_fail);

    $smarty->assign('goods_apply_list', $goods_apply_list);

	$smarty->assign('pager', $pager);

	$smarty->display('shop_goods_apply_list_'.$sh_type.'.dwt');

}

/* 添加商品 */

elseif ($action == 'add_goods') {

	$smarty->assign('shop_id', $supplier_id);

	$smarty->display('shop_add_goods.dwt');

}

/* 添加商品提交 */

elseif ($action == 'add_goods_submit') {

	$shop_id = intval($_REQUEST['shop_id']);

	$goods_name = trim($_REQUEST['goods_name']);

	$goods_type = trim($_REQUEST['goods_type']);

	$goods_desc = trim($_REQUEST['goods_desc']);

	$goods_price = floatval($_REQUEST['goods_price']);

	if (empty($shop_id)) {

		show_message('非法操作');

	}

	if (empty($goods_name)) {

		show_message('请输入商品名称');

	}

	if (empty($goods_type)) {

		show_message('请输入商品类型');

	}

	if (empty($goods_desc)) {

		show_message('请输入商品描述');

	}



	// 图片上传

	$total = 0;

	$data = array();

	if ($_FILES['goods_img']) {

	    foreach($_FILES['goods_img'] as $key => $value) {

	        foreach($value as $k => $v) {

	            $data[$k][$key] = $v;

	            if($key == 'name' && $v) {

	                $total++;

	            }

	        }

	    }

	} else {

		show_message('请上传商品图片');

	}

	if($total > 0) {

	    $goods_img_arr = array();

	    foreach ($data as $key => $value) {

	        include_once (ROOT_PATH . '/includes/cls_image.php');

	        $image = new cls_image($_CFG['bgcolor']);

	        $img_original = $image->upload_image($value, 'goods/' . local_date('Ym'));

	        $goods_img_arr[] = $img_original;

	    }

	}



	$goods_img = $goods_img_arr ? implode(',', $goods_img_arr) : '';



	$sql = "insert into " . $GLOBALS['ecs']->table('shop_goods_apply') . "(`shop_id`,`goods_name`,`goods_type`,`goods_desc`,`goods_price`,`goods_img`,`is_pass`,`add_time`) values('$shop_id','$goods_name','$goods_type','$goods_desc','$goods_price','$goods_img','0','" . gmtime() . "')";

	$GLOBALS['db']->query($sql);



	ecs_header("Location: shop.php?act=goods_apply_list\n");

	exit();

}

/* 商品列表 */

elseif ($action == 'goods_list') {

	check_shop($supplier_id);



	$goods_type = intval($_REQUEST['goods_type']);



	if ($goods_type == 1) {

		$children = get_cattype_supplier(0, $supplier_id);

		if($children === false){

			ecs_header("Location: supplier.php?suppId=".$supplier_id);

			exit;

		}

		$record_count = get_cagtegory_goods_count($children, $supplier_id);

		$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

		$size = isset($_CFG['page_size'])  && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;

		// 分页函数

    	$pager = get_pager('shop.php', array( 'act' => $action ), $record_count, $page);



    	$goods_list = category_get_goods($children, $size, $page, $supplier_id);

	} elseif ($goods_type == 2) {

		# code...

	}



	$smarty->assign('pager',   $pager);

    $smarty->assign('goods_list', $goods_list);

	$smarty->assign('supplier_id', $supplier_id);

	$smarty->display('shop_goods_list_'.$goods_type.'.dwt');

}

/* 商品管理 */

elseif ($action == 'sale_control') {

	$goods_id = intval($_REQUEST['goods_id']);

	$is_on_sale = intval($_POST['is_on_sale']);

	if (empty($goods_id)) {

		echo json_encode(array('code' => 500, 'msg' => '非法操作'));

		exit;

	}



	$sql="select supplier_id,supplier_status from ". $GLOBALS['ecs']->table('goods') ." where goods_id='$goods_id' ";

	$supplier_row = $GLOBALS['db']->getRow($sql);

	if ($supplier_row['supplier_id'] > 0 && $supplier_row['supplier_status'] <= 0 )

	{

		echo json_encode(array('code' => 500, 'msg' => '该商品还未审核通过！不能上架！'));

		exit;

	}

	$sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET is_on_sale = " . $is_on_sale . ", last_update = '" . gmtime() . "' WHERE goods_id = " . $goods_id;

	$GLOBALS['db']->query($sql);

	if ($is_on_sale == 1) {

		$msg = '上架成功';

	} else {

		$msg = '下架成功';

	}

	echo json_encode(array('code' => 200, 'msg' => $msg));

	exit;

}

/* 店铺活动列表 */

elseif ($action == 'shop_activity') {

	$pass_status = intval($_REQUEST['pass_status']);  //0未审核 1审核通过 2审核不通过



	// 待审核

	$wait_pass = $GLOBALS['db']->getOne("select count(*) from " . $GLOBALS['ecs']->table('activity_apply') . " where pass_status = 0 and shop_id = $supplier_id");

	$smarty->assign('wait_pass', $wait_pass);



	// 审核通过

	$pass = $GLOBALS['db']->getOne("select count(*) from " . $GLOBALS['ecs']->table('activity_apply') . " where pass_status = 1 and shop_id = $supplier_id");

	$smarty->assign('pass', $pass);



	// 审核不通过

	$unpass = $GLOBALS['db']->getOne("select count(*) from " . $GLOBALS['ecs']->table('activity_apply') . " where pass_status = 2 and shop_id = $supplier_id");

	$smarty->assign('unpass', $unpass);



	$sql = "select count(*) from " . $GLOBALS['ecs']->table('activity_apply') . " where shop_id = " . $supplier_id . " and pass_status = " . $pass_status;

	$record_count = $GLOBALS['db']->getOne($sql);

	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

	// 分页函数

	$pager = get_pager('agent.php', array( 'act' => $action ), $record_count, $page);



	$sql = "select * from " . $GLOBALS['ecs']->table('activity_apply') . " where shop_id = " . $supplier_id . " and pass_status = " . $pass_status . " order by add_time desc limit " . $pager['start'] . ", " . $pager['size'];



	$activity_list = $GLOBALS['db']->getAll($sql);

	foreach ($activity_list as $key => $value) {

	    $activity_list[$key]['add_time']   = local_date('Y-m-d H:i', $value['add_time']);
        $activity_list[$key]['start_time'] = local_date('Y-m-d', $value['start_time']);
        $activity_list[$key]['end_time']   = local_date('Y-m-d', $value['end_time']);
	    switch ($value['act_type']) {

	    	case '0':

	    		$value['act_type'] = '赠品';

	    		break;

	    	case '1':

	    		$value['act_type'] = '现金减免';

	    		break;

	    	case '2':

	    		$value['act_type'] = '价格折扣';

	    		break;

	    	default:

	    		break;

	    }

	}

	$smarty->assign('activity_list', $activity_list);

	$smarty->assign('pager', $pager);

	$smarty->display('shop_activity_apply_list_' . $pass_status . '.dwt');

}

/* 添加活动 */

elseif ($action == 'add_activity') {

	$activity = array(

		'start_time'    => date('Y-m-d', time() + 86400),

	    'end_time'      => date('Y-m-d', time() + 4 * 86400)

	);

	$smarty->assign('activity', $activity);

	$smarty->display('shop_add_activity.dwt');

}

/* 添加活动提交 */

elseif ($action == 'insert_activity') {

	include_once (ROOT_PATH . 'includes/lib_transaction.php');



	$activity_name = trim($_REQUEST['activity_name']);

	$activity_desc = trim($_REQUEST['activity_desc']);

	$goods_list = trim($_REQUEST['goods_list']);

	$act_type = intval($_REQUEST['act_type']);

	// $start_time = trim($_REQUEST['start_time']);

	// $end_time = trim($_REQUEST['end_time']);

    $start_time = local_strtotime(trim($_REQUEST['start_time']));

    $end_time = local_strtotime(trim($_REQUEST['end_time']));

	$min_amount = intval($_REQUEST['min_amount']);

	$max_amount = intval($_REQUEST['max_amount']);

	$act_type_ext = intval($_REQUEST['act_type_ext']);

	$pass_status = 0;   //0未审核 1审核通过 2审核不通过

	$add_time = gmtime();



	$sql = "insert into " . $GLOBALS['ecs']->table('activity_apply') . "(`activity_name`,`activity_desc`,`goods_list`,`act_type`,`start_time`,`end_time`,`min_amount`,`max_amount`,`act_type_ext`,`shop_id`,`pass_status`,`add_time`) values('$activity_name','$activity_desc','$goods_list','$act_type','$start_time','$end_time','$min_amount','$max_amount','$act_type_ext','$supplier_id','$pass_status','$add_time')";

	$GLOBALS['db']->query($sql);



	ecs_header("Location: shop.php?act=shop_activity\n");

	exit;

}

/* 添加活动提交 */

elseif ($action == 'sales_log') {

    include_once (ROOT_PATH . 'includes/lib_order.php');



    $where = "WHERE o.supplier_id='".$supplier_id."' ";



    /* 普通订单不显示虚拟团购订单 添加_START*/

    $where .= " AND o.extension_code != 'virtual_good'";

    /* 普通订单不显示虚拟团购订单 添加_END*/



    // 已完成订单

    $where .= order_query_sql('finished');



    // 销售额

    $sql = "SELECT SUM(o.goods_amount + o.tax + o.shipping_fee + o.insure_fee + o.pay_fee + o.pack_fee + o.card_fee) FROM " . $GLOBALS['ecs']->table('order_info') . " AS o ". $where;

    $sales_total = $GLOBALS['db']->getOne($sql);

    $smarty->assign('sales_total', $sales_total);



    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_info') . " AS o ". $where;

    $record_count = $GLOBALS['db']->getOne($sql);

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    // 分页函数

    $pager = get_pager('agent.php', array( 'act' => $action ), $record_count, $page);



    /* 查询 */

    $sql = "SELECT o.order_id, o.order_sn, o.add_time, o.order_status, o.shipping_status, o.order_amount, o.money_paid," .

                "o.pay_status, o.consignee, o.address, o.email, o.tel, o.extension_code, o.extension_id, " .

                "(" . order_amount_field('o.') . ") AS total_fee, " .

                "IFNULL(u.user_name, '" .$GLOBALS['_LANG']['anonymous']. "') AS buyer ".

                ',o.mobile,o.inv_payee,o.inv_content,o.inv_type,o.vat_inv_company_name'.

                ',o.vat_inv_taxpayer_id,o.vat_inv_registration_address,o.vat_inv_registration_phone'.

                ',o.vat_inv_deposit_bank,o.vat_inv_bank_account'.

                ',o.inv_consignee_name,o.inv_consignee_phone,o.inv_consignee_country'.

                ',o.inv_consignee_province,o.inv_consignee_city,o.inv_consignee_district'.

                ',o.inv_consignee_address,o.inv_status,o.inv_payee_type,o.inv_money'.

            " FROM " . $GLOBALS['ecs']->table('order_info') . " AS o " .

            " LEFT JOIN " .$GLOBALS['ecs']->table('users'). " AS u ON u.user_id=o.user_id ". $where .

            " ORDER BY add_time desc ".

            " LIMIT " . $pager[start] . ", $pager[size]";



    $row = $GLOBALS['db']->getAll($sql);



    /* 格式话数据 */

    foreach ($row as $key => $value)

    {

        $row[$key]['formated_order_amount'] = price_format($value['order_amount']);

        $row[$key]['formated_money_paid'] = price_format($value['money_paid']);

        $row[$key]['formated_total_fee'] = price_format($value['total_fee']);

        $row[$key]['short_order_time'] = local_date('m-d H:i', $value['add_time']);

        $row[$key]['formatted_add_time'] = local_date('Y-m-d H:i',$value['add_time']);

        $row[$key]['formatted_inv_money'] = price_format($value['inv_money']);

    }

    $smarty->assign('sales_log', $row);

    $smarty->assign('pager', $pager);

    $smarty->display('shop_sales_log.dwt');

}

/* 粉丝列表 */

elseif ($action == 'fans_list') {

    check_shop($supplier_id);



    $sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('users') . " WHERE parent_id = '$user_id'";

    $record_count = $GLOBALS['db']->getOne($sql);

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    // 分页函数

    $pager = get_pager('shop.php', array( 'act' => $action ), $record_count, $page);



    // 获取粉丝数据

    $sql  = "SELECT user_id, user_name, headimg FROM " .$GLOBALS['ecs']->table('users'). " WHERE parent_id = '$user_id' ORDER BY reg_time desc ". " limit " . $pager['start'] . ", " . $pager['size'];



    $fans_list = $GLOBALS['db']->getAll($sql);

    foreach ($fans_list as $key => $value) {

        $sql = "select headimgurl from " . $GLOBALS['ecs']->table('weixin_user') . " where ecuid = '$value[user_id]'";

        $headimgurl = $GLOBALS['db']->getOne($sql);

        if($headimgurl)

        {

            $smarty->assign('headimgurl', $headimgurl);

        }else{

            $headimg = $GLOBALS['db']->getOne("select headimg from " . $GLOBALS['ecs']->table('users') . " where user_id = '$value[user_id]'");

            if($headimg){

                $fans_list[$key]['headimg'] = $headimg;

            }else{

                $headimgurl = 'themesmobile/default/images/user/user68.jpg';

                $fans_list[$key]['headimg'] = $headimgurl;

            }

        }

    }



    $smarty->assign('fans_list', $fans_list);

    $smarty->assign('pager', $pager);

    $smarty->display('shop_fans_list.dwt');

}

/*elseif ($action == 'fans_list') {

    check_shop($supplier_id);



    $sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('shop_fans') . " WHERE shop_id = '$supplier_id'";

    $record_count = $GLOBALS['db']->getOne($sql);

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    // 分页函数

    $pager = get_pager('shop.php', array( 'act' => $action ), $record_count, $page);



    // 获取粉丝数据

    $sql  = "SELECT a.user_id, b.user_name, b.headimg FROM " . $GLOBALS['ecs']->table('shop_fans') . " a LEFT JOIN " . $GLOBALS['ecs']->table('users') . " b ON a.user_id = b.user_id WHERE a.shop_id = '$supplier_id' ORDER BY add_time desc ". " limit " . $pager['start'] . ", " . $pager['size'];



    $fans_list = $GLOBALS['db']->getAll($sql);

    foreach ($fans_list as $key => $value) {

        $sql = "select headimgurl from " . $GLOBALS['ecs']->table('weixin_user') . " where ecuid = '$value[user_id]'";

        $headimgurl = $GLOBALS['db']->getOne($sql);

        if($headimgurl)

        {

            $smarty->assign('headimgurl', $headimgurl);

        }else{

            $headimg = $GLOBALS['db']->getOne("select headimg from " . $GLOBALS['ecs']->table('users') . " where user_id = '$value[user_id]'");

            if($headimg){

                $fans_list[$key]['headimg'] = $headimg;

            }else{

                $headimgurl = 'themesmobile/default/images/user/user68.jpg';

                $fans_list[$key]['headimg'] = $headimgurl;

            }

        }

    }

    $smarty->assign('fans_list', $fans_list);

    $smarty->assign('pager', $pager);

    $smarty->display('shop_fans_list.dwt');

}*/



/**

 * 获得指定商品属性所属的分类的ID

 *

 * @access  public

 * @param   integer     $cat        (1=>'精品推荐',2=>'新品上市',3=>'热卖商品')

 * @param	string		$keywords    关键字

 * @return  string

 */

function get_cattype_supplier($cat = 0, $supplier_id = 0, $keywords = '')

{

	if(empty($keywords)){

		$where = "supplier_id=".$supplier_id;

		if($cat > 0){

			$where .= " AND recommend_type=".$cat;

		}

		$sql = "select cat_id  from ". $GLOBALS['ecs']->table('supplier_category') ." where ".$where;

		$res = $GLOBALS['db']->getAll($sql);

		$arr = array();

		if(count($res)>0){

			foreach($res as $k => $v){

				$arr[$v['cat_id']] = $v['cat_id'];

			}

		}

		if(empty($arr)){

			return false;

		}

	    return 'sgc.cat_id ' . db_create_in(array_keys($arr));

	}else{

		return "g.goods_name like '%".$keywords."%'";

	}

}



/**

 * 获得分类下的商品总数

 *

 * @access  public

 * @param   string     $children

 * @param   string		$keywords   商品名称查找

 * @return  integer

 */

function get_cagtegory_goods_count($children, $supplier_id = 0)

{

    /* 返回商品总数 */

    if(empty($keywords)){

    	 $where  = "sgc.supplier_id=".$supplier_id." AND $children";

    	$sql = 'SELECT count(DISTINCT g.goods_id) FROM ' . $GLOBALS['ecs']->table('supplier_goods_cat') . ' AS sgc LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .

    			'ON sgc.goods_id = g.goods_id AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.is_virtual=0 WHERE '.$where;

    }else{

    	$where  = "g.supplier_id=".$supplier_id." AND $children";

    	$sql = 'SELECT count(DISTINCT g.goods_id) FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .

    			'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.is_virtual=0 AND '.$where;

    }

    return $GLOBALS['db']->getOne($sql);

}



/**

 * 获得分类下的商品

 *

 * @access  public

 * @param   string  $children

 * @return  array

 */

function category_get_goods($children, $size, $page, $supplier_id, $keywords='')

{

	$sql = "SELECT status FROM " . $GLOBALS['ecs']->table('supplier') . " WHERE supplier_id = " . $supplier_id;

	$status = $GLOBALS['db']->getOne($sql);

	if (empty($status)) {

		return array();

	}



    $display = $GLOBALS['display'];

    $where = "g.is_alone_sale = 1 AND ".

            "g.is_delete = 0 AND g.is_virtual=0 AND ($children)";



    /* 获得商品列表 */

    if(empty($keywords)){

    	$where .= " AND sgc.supplier_id=".$supplier_id;

    	$sql = 'SELECT DISTINCT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_on_sale, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, exclusive, ' .

                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, " .

                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .

    		'FROM ' . $GLOBALS['ecs']->table('supplier_goods_cat') . ' AS sgc ' .

            'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .

    			"ON sgc.goods_id = g.goods_id " .

            'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .

                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .

            "WHERE $where ORDER BY g.goods_id desc";

    }else{

    	$where .= " AND g.supplier_id=".$supplier_id;

    	$sql = 'SELECT DISTINCT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_on_sale, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, exclusive, ' .

                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, " .

                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .

    		'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .

            'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .

                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .

            "WHERE $where ORDER BY g.goods_id desc";

    }

    $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);



    $arr = array();

    while ($row = $GLOBALS['db']->fetchRow($res))

    {

        if ($row['promote_price'] > 0)

        {

            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);

        }

        else

        {

            $promote_price = 0;

        }



        /* 处理商品水印图片 */

        $watermark_img = '';



        if ($promote_price != 0)

        {

            $watermark_img = "watermark_promote_small";

        }

        elseif ($row['is_new'] != 0)

        {

            $watermark_img = "watermark_new_small";

        }

        elseif ($row['is_best'] != 0)

        {

            $watermark_img = "watermark_best_small";

        }

        elseif ($row['is_hot'] != 0)

        {

            $watermark_img = 'watermark_hot_small';

        }



        if ($watermark_img != '')

        {

            $arr[$row['goods_id']]['watermark_img'] =  $watermark_img;

        }



        $arr[$row['goods_id']]['goods_id']         = $row['goods_id'];

        if($display == 'grid')

        {

            $arr[$row['goods_id']]['goods_name']       = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];

        }

        else

        {

            $arr[$row['goods_id']]['goods_name']       = $row['goods_name'];

        }

        $final_price = get_final_price($row['goods_id'], 1, true, array());

        $arr[$row['goods_id']]['final_price']     = price_format($final_price);

        $arr[$row['goods_id']]['is_exclusive']  = is_exclusive($row['exclusive'],$final_price);

        $arr[$row['goods_id']]['name']             = $row['goods_name'];

        $arr[$row['goods_id']]['goods_brief']      = $row['goods_brief'];

        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_name'],$row['goods_name_style']);

        $arr[$row['goods_id']]['market_price']     = price_format($row['market_price']);

        $arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);

        $arr[$row['goods_id']]['type']             = $row['goods_type'];

        $arr[$row['goods_id']]['is_on_sale']             = $row['is_on_sale'];

        $arr[$row['goods_id']]['promote_price']    = ($promote_price > 0) ? price_format($promote_price) : '';

        $arr[$row['goods_id']]['goods_thumb']      =  get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_thumb'], true);

        $arr[$row['goods_id']]['goods_img']        =  get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_img']);

        $arr[$row['goods_id']]['url']              = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);

    }



    return $arr;

}



/**

 * 一键发货取得订单信息

 * @param   int     $order_id   订单id（如果order_id > 0 就按id查，否则按sn查）

 * @param   string  $order_sn   订单号

 * @return  array   订单信息（金额都有相应格式化的字段，前缀是formated_）

 */

function shipping_order_info($order_id, $order_sn = '')

{

    /* 计算订单各种费用之和的语句 */

    $total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee ";

    $order_id = intval($order_id);

    if ($order_id > 0)

    {

       $sql = "SELECT o.*, p.shop_name, p.address as zt_address, p.phone, p.contact, " . $total_fee . " FROM " . $GLOBALS['ecs']->table('order_info') .

               " as o left join " . $GLOBALS['ecs']->table('pickup_point') . " as p on o.pickup_point = p.id WHERE o.order_id = '$order_id'";

    }

    else

    {

       $sql = "SELECT o.*, p.shop_name, p.address as zt_address, p.phone, p.contact, " . $total_fee . " FROM " . $GLOBALS['ecs']->table('order_info') .

               " as o left join " . $GLOBALS['ecs']->table('pickup_point')." as p on o.pickup_point = p.id WHERE o.order_sn = '$order_sn'";

    }

    $order = $GLOBALS['db']->getRow($sql);

    /* 格式化金额字段 */

    if ($order)

    {

        $order['formated_goods_amount']   = price_format($order['goods_amount'], false);

        $order['formated_discount']       = price_format($order['discount'], false);

        $order['formated_tax']            = price_format($order['tax'], false);

        $order['formated_shipping_fee']   = price_format($order['shipping_fee'], false);

        $order['formated_insure_fee']     = price_format($order['insure_fee'], false);

        $order['formated_pay_fee']        = price_format($order['pay_fee'], false);

        $order['formated_pack_fee']       = price_format($order['pack_fee'], false);

        $order['formated_card_fee']       = price_format($order['card_fee'], false);

        $order['formated_total_fee']      = price_format($order['total_fee'], false);

        $order['formated_money_paid']     = price_format($order['money_paid'], false);

        $order['formated_bonus']          = price_format($order['bonus'], false);

        $order['formated_integral_money'] = price_format($order['integral_money'], false);

        $order['formated_surplus']        = price_format($order['surplus'], false);

        $order['formated_order_amount']   = price_format(abs($order['order_amount']), false);

        $order['formated_add_time']       = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);



        $sql_invoices = "SELECT invoice_no,shipping_name FROM ".$GLOBALS['ecs']->table('delivery_order')." WHERE order_id = ".$order['order_id']." AND status = 0";

        $order['invoices'] = $GLOBALS['db']->getAll($sql_invoices);



        $sql = "select region_id, region_name from " . $GLOBALS['ecs']->table('region') . " where region_id in (" . $order['country'] . "," . $order['province'] . "," . $order['city'] . "," . $order['district'] . ")";



        $rows = $GLOBALS['db']->getAll($sql);



        foreach($rows as $row)

        {

            $region_id = $row['region_id'];

            $region_name = $row['region_name'];



            if($region_id == $order['country'])

            {

                $order['country_name'] = $region_name;

            }

            else if($region_id == $order['province'])

            {

                $order['province_name'] = $region_name;

            }

            else if($region_id == $order['city'])

            {

                $order['city_name'] = $region_name;

            }

            else if($region_id == $order['district'])

            {

                $order['district_name'] = $region_name;

            }

        }

    }



    return $order;

}



/**

 * 取得用户信息

 * @param   int     $user_id    用户id

 * @return  array   用户信息

 */

function shipping_user_info($user_id)

{

    $sql = "SELECT u.*,ifnull(s.supplier_name,'') as supplier_name FROM " . $GLOBALS['ecs']->table('users') .

            " as u left join ".$GLOBALS['ecs']->table('supplier')." as s on u.user_id=s.user_id WHERE u.user_id = '$user_id'";

    $user = $GLOBALS['db']->getRow($sql);



    unset($user['question']);

    unset($user['answer']);



    // 格式化帐户余额

    if ($user)

    {

        $user['formated_user_money'] = price_format($user['user_money'], false);

        $user['formated_frozen_money'] = price_format($user['frozen_money'], false);

    }



    return $user;

}



/**

 * 查询购物车（订单id为0）或订单中是否有实体商品

 * @param   int     $order_id   订单id

 * @param   int     $flow_type  购物流程类型

 * @return  bool

 */

function shipping_exist_real_goods($order_id = 0, $flow_type = CART_GENERAL_GOODS)

{

    if ($order_id <= 0)

    {

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('cart') .

                " WHERE session_id = '" . SESS_ID . "' AND is_real = 1 " .

                "AND rec_type = '$flow_type'";

    }

    else

    {

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_goods') .

                " WHERE order_id = '$order_id' AND is_real = 1";

    }



    return $GLOBALS['db']->getOne($sql) > 0;

}



/**

 * 取得订单商品

 * @param   array     $order  订单数组

 * @return array

 */

function shipping_get_order_goods($order)

{

    $goods_list = array();

    $goods_attr = array();

    $sql = "SELECT o.*, g.suppliers_id AS suppliers_id,IF(o.product_id > 0, p.product_number, g.goods_number) AS storage, o.goods_attr, IFNULL(b.brand_name, '') AS brand_name, p.product_sn " .

            "FROM " . $GLOBALS['ecs']->table('order_goods') . " AS o ".

            "LEFT JOIN " . $GLOBALS['ecs']->table('products') . " AS p ON o.product_id = p.product_id " .

            "LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g ON o.goods_id = g.goods_id " .

            "LEFT JOIN " . $GLOBALS['ecs']->table('brand') . " AS b ON g.brand_id = b.brand_id " .

            "WHERE o.order_id = '$order[order_id]' ";

    $res = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res))

    {

        // 虚拟商品支持

        if ($row['is_real'] == 0)

        {

            /* 取得语言项 */

            $filename = ROOT_PATH . 'plugins/' . $row['extension_code'] . '/languages/common_' . $GLOBALS['_CFG']['lang'] . '.php';

            if (file_exists($filename))

            {

                include_once($filename);

                if (!empty($GLOBALS['_LANG'][$row['extension_code'].'_link']))

                {

                    $row['goods_name'] = $row['goods_name'] . sprintf($GLOBALS['_LANG'][$row['extension_code'].'_link'], $row['goods_id'], $order['order_sn']);

                }

            }

        }



        $row['formated_subtotal']       = price_format($row['goods_price'] * $row['goods_number']);

        $row['formated_goods_price']    = price_format($row['goods_price']);



        $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组



        if ($row['extension_code'] == 'package_buy')

        {

            $row['storage'] = '';

            $row['brand_name'] = '';

            $row['package_goods_list'] = get_package_goods_list($row['goods_id']);

        }



        //处理货品id

        $row['product_id'] = empty($row['product_id']) ? 0 : $row['product_id'];



        $goods_list[] = $row;

    }



    $attr = array();

    $arr  = array();

    foreach ($goods_attr AS $index => $array_val)

    {

        foreach ($array_val AS $value)

        {

            $arr = explode(':', $value);//以 : 号将属性拆开

            $attr[$index][] =  @array('name' => $arr[0], 'value' => $arr[1]);

        }

    }



    return array('goods_list' => $goods_list, 'attr' => $attr);

}



/**

 * 取得礼包列表

 * @param   integer     $package_id  订单商品表礼包类商品id

 * @return array

 */

function get_package_goods_list($package_id)

{

    $sql = "SELECT pg.goods_id, g.goods_name, (CASE WHEN pg.product_id > 0 THEN p.product_number ELSE g.goods_number END) AS goods_number, p.goods_attr, p.product_id, pg.goods_number AS

            order_goods_number, g.goods_sn, g.is_real, p.product_sn

            FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg

                LEFT JOIN " .$GLOBALS['ecs']->table('goods') . " AS g ON pg.goods_id = g.goods_id

                LEFT JOIN " . $GLOBALS['ecs']->table('products') . " AS p ON pg.product_id = p.product_id

            WHERE pg.package_id = '$package_id'";

    $resource = $GLOBALS['db']->query($sql);

    if (!$resource)

    {

        return array();

    }



    $row = array();



    /* 生成结果数组 取存在货品的商品id 组合商品id与货品id */

    $good_product_str = '';

    while ($_row = $GLOBALS['db']->fetch_array($resource))

    {

        if ($_row['product_id'] > 0)

        {

            /* 取存商品id */

            $good_product_str .= ',' . $_row['goods_id'];



            /* 组合商品id与货品id */

            $_row['g_p'] = $_row['goods_id'] . '_' . $_row['product_id'];

        }

        else

        {

            /* 组合商品id与货品id */

            $_row['g_p'] = $_row['goods_id'];

        }



        //生成结果数组

        $row[] = $_row;

    }

    $good_product_str = trim($good_product_str, ',');



    /* 释放空间 */

    unset($resource, $_row, $sql);



    /* 取商品属性 */

    if ($good_product_str != '')

    {

        $sql = "SELECT ga.goods_attr_id, ga.attr_value, ga.attr_price, a.attr_name

                FROM " .$GLOBALS['ecs']->table('goods_attr'). " AS ga, " .$GLOBALS['ecs']->table('attribute'). " AS a

                WHERE a.attr_id = ga.attr_id

                AND a.attr_type = 1

                AND goods_id IN ($good_product_str)";

        $result_goods_attr = $GLOBALS['db']->getAll($sql);



        $_goods_attr = array();

        foreach ($result_goods_attr as $value)

        {

            $_goods_attr[$value['goods_attr_id']] = $value;

        }

    }



    /* 过滤货品 */

    $format[0] = "%s:%s[%d] <br>";

    $format[1] = "%s--[%d]";

    foreach ($row as $key => $value)

    {

        if ($value['goods_attr'] != '')

        {

            $goods_attr_array = explode('|', $value['goods_attr']);



            $goods_attr = array();

            foreach ($goods_attr_array as $_attr)

            {

                $goods_attr[] = sprintf($format[0], $_goods_attr[$_attr]['attr_name'], $_goods_attr[$_attr]['attr_value'], $_goods_attr[$_attr]['attr_price']);

            }



            $row[$key]['goods_attr_str'] = implode('', $goods_attr);

        }



        $row[$key]['goods_name'] = sprintf($format[1], $value['goods_name'], $value['order_goods_number']);

    }



    return $row;

}



/**

 * 超级礼包发货数处理

 * @param   array   超级礼包商品列表

 * @param   int     发货数量

 * @param   int     订单ID

 * @param   varchar 虚拟代码

 * @param   int     礼包ID

 * @return  array   格式化结果

 */

function shipping_package_goods(&$package_goods, $goods_number, $order_id, $extension_code, $package_id)

{

    $return_array = array();



    if (count($package_goods) == 0 || !is_numeric($goods_number))

    {

        return $return_array;

    }



    foreach ($package_goods as $key=>$value)

    {

        $return_array[$key] = $value;

        $return_array[$key]['order_send_number'] = $value['order_goods_number'] * $goods_number;

        $return_array[$key]['sended'] = package_sended($package_id, $value['goods_id'], $order_id, $extension_code, $value['product_id']);

        $return_array[$key]['send'] = ($value['order_goods_number'] * $goods_number) - $return_array[$key]['sended'];

        $return_array[$key]['storage'] = $value['goods_number'];





        if ($return_array[$key]['send'] <= 0)

        {

            $return_array[$key]['send'] = $GLOBALS['_LANG']['act_good_delivery'];

            $return_array[$key]['readonly'] = 'readonly="readonly"';

        }



        /* 是否缺货 */

        if ($return_array[$key]['storage'] <= 0 && $GLOBALS['_CFG']['use_storage'] == '1')

        {

            $return_array[$key]['send'] = $GLOBALS['_LANG']['act_good_vacancy'];

            $return_array[$key]['readonly'] = 'readonly="readonly"';

        }

    }



    return $return_array;

}



/**

 * 订单中的商品是否已经全部发货

 * @param   int     $order_id  订单 id

 * @return  int     1，全部发货；0，未全部发货

 */

function shipping_get_order_finish($order_id)

{

    $return_res = 0;



    if (empty($order_id))

    {

        return $return_res;

    }



    $sql = 'SELECT COUNT(rec_id)

            FROM ' . $GLOBALS['ecs']->table('order_goods') . '

            WHERE order_id = \'' . $order_id . '\'

            AND goods_number > send_number';



    $sum = $GLOBALS['db']->getOne($sql);

    if (empty($sum))

    {

        $return_res = 1;

    }



    return $return_res;

}



/**

 * 取得发货单信息

 * @param   int     $delivery_order   发货单id（如果delivery_order > 0 就按id查，否则按sn查）

 * @param   string  $delivery_sn      发货单号

 * @return  array   发货单信息（金额都有相应格式化的字段，前缀是formated_）

 */

function shipping_delivery_order_info($delivery_id, $delivery_sn = '')

{

    $return_order = array();

    if (empty($delivery_id) || !is_numeric($delivery_id))

    {

        return $return_order;

    }



    $where = '';

    /* 获取管理员信息 */

    $admin_info = shipping_admin_info();



    /* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */

    if ($admin_info['agency_id'] > 0)

    {

        $where .= " AND agency_id = '" . $admin_info['agency_id'] . "' ";

    }



    /* 如果管理员属于某个供货商，只列出这个供货商的发货单 */

    if ($admin_info['suppliers_id'] > 0)

    {

        $where .= " AND suppliers_id = '" . $admin_info['suppliers_id'] . "' ";

    }



    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('delivery_order');

    if ($delivery_id > 0)

    {

        $sql .= " WHERE delivery_id = '$delivery_id'";

    }

    else

    {

        $sql .= " WHERE delivery_sn = '$delivery_sn'";

    }



    $sql .= $where;

    $sql .= " LIMIT 0, 1";

    $delivery = $GLOBALS['db']->getRow($sql);

    if ($delivery)

    {

        /* 格式化金额字段 */

        $delivery['formated_insure_fee']     = price_format($delivery['insure_fee'], false);

        $delivery['formated_shipping_fee']   = price_format($delivery['shipping_fee'], false);



        /* 格式化时间字段 */

        $delivery['formated_add_time']       = local_date($GLOBALS['_CFG']['time_format'], $delivery['add_time']);

        $delivery['formated_update_time']    = local_date($GLOBALS['_CFG']['time_format'], $delivery['update_time']);



        $return_order = $delivery;

    }



    return $return_order;

}



/**

 * 判断订单的发货单是否全部发货

 * @param   int     $order_id  订单 id

 * @return  int     1，全部发货；0，未全部发货；-1，部分发货；-2，完全没发货；

 */

function shipping_get_all_delivery_finish($order_id)

{

    $return_res = 0;



    if (empty($order_id))

    {

        return $return_res;

    }



    /* 未全部分单 */

    if (!shipping_get_order_finish($order_id))

    {

        return $return_res;

    }

    /* 已全部分单 */

    else

    {

        // 是否全部发货

        $sql = "SELECT COUNT(delivery_id)

                FROM " . $GLOBALS['ecs']->table('delivery_order') . "

                WHERE order_id = '$order_id'

                AND status = 2 ";

        $sum = $GLOBALS['db']->getOne($sql);

        // 全部发货

        if (empty($sum))

        {

            $return_res = 1;

        }

        // 未全部发货

        else

        {

            /* 订单全部发货中时：当前发货单总数 */

            $sql = "SELECT COUNT(delivery_id)

            FROM " . $GLOBALS['ecs']->table('delivery_order') . "

            WHERE order_id = '$order_id'

            AND status <> 1 ";

            $_sum = $GLOBALS['db']->getOne($sql);

            if ($_sum == $sum)

            {

                $return_res = -2; // 完全没发货

            }

            else

            {

                $return_res = -1; // 部分发货

            }

        }

    }



    return $return_res;

}



/**

 * 获取管理员信息

 */

function shipping_admin_info()

{

    $sql = "SELECT * FROM ". $GLOBALS['ecs']->table('admin_user')."

            WHERE user_id = '$_SESSION[admin_id]'

            LIMIT 0, 1";

    $admin_info = $GLOBALS['db']->getRow($sql);



    if (empty($admin_info))

    {

        return $admin_info = array();

    }



    return $admin_info;

}



/**

 * 检查店铺是否过期

 */

function check_shop ($supplier_id) {

	if ($supplier_id) {

		$sql = "SELECT chuzu_status FROM " . $GLOBALS['ecs']->table('agent_shop') . " WHERE shop_id = " . $supplier_id;

		$chuzu_status = $GLOBALS['db']->getOne($sql);

		if ($chuzu_status == 3) {

			show_message('店铺已过期，请续费！');

		}

	}

}



/**

 * 余额

 */

function get_user_yue ($user_id)

{

	$sql = "SELECT user_money FROM " . $GLOBALS['ecs']->table('users') . "WHERE user_id = '$user_id'";

	$res = $GLOBALS['db']->getOne($sql);

	return $res;

}



/* 更新退换货订单状态 */

function update_back($back_id, $status_back, $status_refund )

{

    $setsql = "";

    if ($status_back)

    {

        $setsql .= $setsql ? "," : "";

        $setsql .= "status_back='$status_back'";

    }

    if ($status_refund)

    {

        $setsql .= $setsql ? "," : "";

        $setsql .= "status_refund='$status_refund'";

    }

    $sql = "update ". $GLOBALS['ecs']->table('back_order') ." set  $setsql where back_id='$back_id' ";

    $GLOBALS['db']->query($sql);



    if($status_back =='5') //通过申请

    {

       $status_b = $GLOBALS['db']->getOne("select back_type from " . $GLOBALS['ecs']->table('back_order') . " where back_id='$back_id'");

       $status_b = ($status_b == 4) ? 4 : 0;

       $status_bo = $GLOBALS['db']->getOne("select order_sn from " . $GLOBALS['ecs']->table('back_order') . " where back_id='$back_id'");

       $close_order = $GLOBALS['db']->getOne("select shipping_status from " . $GLOBALS['ecs']->table('order_info') . " where order_sn = '" . $status_bo . "'");

       if ($close_order < 1)

       {

           $sql3="update ". $GLOBALS['ecs']->table('order_info') ." set order_status='2', to_buyer='用户对订单内的部分或全部商品申请退款并取消订单' where order_sn = '" . $status_bo . "'";

           $GLOBALS['db']->query($sql3);

       }



       $sql="update ". $GLOBALS['ecs']->table('back_goods') ." set status_back='$status_b' where back_id='$back_id' ";

       $GLOBALS['db']->query($sql);

       $sql2="update ". $GLOBALS['ecs']->table('back_order') ." set status_back='$status_b' where back_id='$back_id' ";

       $GLOBALS['db']->query($sql2);

    }

    if($status_back =='6') //拒绝申请

    {

       $sql="update ". $GLOBALS['ecs']->table('back_goods') ." set status_back='$status_back' where back_id='$back_id' ";

       $GLOBALS['db']->query($sql);

       $sql2="update ". $GLOBALS['ecs']->table('back_order') ." set status_back='$status_back' where back_id='$back_id' ";

       $GLOBALS['db']->query($sql2);

    }



    if($status_back =='1' or $status_back =='3') //收到退换回的货物，完成退换货

    {

       $sql="update ". $GLOBALS['ecs']->table('back_goods') ." set status_back='$status_back' where back_id='$back_id' ";

       $GLOBALS['db']->query($sql);

       $sql2="UPDATE ". $GLOBALS['ecs']->table('back_order') ." SET status_back='$status_back' WHERE back_id='$back_id' ";

       $GLOBALS['db']->query($sql2);



       $get_order_id = $GLOBALS['db']->getOne("SELECT order_id FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE back_id = '" . $back_id . "'");

       $get_goods_id = $GLOBALS['db']->getCol("SELECT goods_id FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE order_id = '" . $get_order_id . "' AND status_back = '3' AND back_type <> '3'");

       if (count($get_goods_id) > 0)

       {

           $get_goods_id_c =  (count($get_goods_id) == 1 ? ("<> '" . implode(',', $get_goods_id) . "'") : ("NOT IN (" . implode(',', $get_goods_id) . ")"));

           $no_back = $GLOBALS['db']->getOne("SELECT COUNT(rec_id) FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = '" . $get_order_id . "' AND goods_id " . $get_goods_id_c);

           if ($no_back == 0)

           {

               $sql3="UPDATE ". $GLOBALS['ecs']->table('order_info') ." SET order_status='2' WHERE order_id='" . $get_order_id . "' ";

               $GLOBALS['db']->query($sql3);

           }

       }

       $get_goods_info = $GLOBALS['db']->getRow("SELECT goods_id, back_type FROM " . $GLOBALS['ecs']->table('back_goods') . " WHERE back_id = '" . $back_id . "'");

       if ($status_back == '3' && $get_goods_info['back_type'] != '3') // 退款退货完成时，改变订单中商品的is_back值

       {

           $sql4 = "UPDATE " .$GLOBALS['ecs']->table('order_goods') . " SET is_back = 1 WHERE goods_id = '" . $get_goods_info['goods_id'] . "' AND order_id = '" . $get_order_id . "'";

           $GLOBALS['db']->query($sql4);



           //退款完成后，进行返库

           $back_type = $GLOBALS['db']->getOne("SELECT back_type FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE back_id = '" . $back_id . "'");

           $stock_dec_time = $GLOBALS['db']->getOne("SELECT value FROM " . $GLOBALS['ecs']->table('shop_config') . " WHERE code =  'stock_dec_time'");

           if ($back_type == 4 && $stock_dec_time == 1)

           {

               $back_go = $GLOBALS['db']->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = " . $get_order_id);

               foreach($back_go as $back_g)

               {

                   if ($back_g['product_id'] > 0)

                   {

                       $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('products') . " SET product_number = product_number + " . $back_g['goods_number'] . " WHERE product_id = " . $back_g['product_id']);

                   }

                    $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('goods') . " SET goods_number = goods_number + " . $back_g['goods_number'] . " WHERE goods_id = " . $back_g['goods_id']);

               }

           }

       }

    }

    if($status_back =='2') //换出商品寄回

    {

       $sql="update ". $GLOBALS['ecs']->table('back_goods') ." set status_back='$status_back' where back_type in(1,2,3) and back_id='$back_id' ";

       $GLOBALS['db']->query($sql);

    }

    if($status_refund=='1') //退款

    {

       $sql="update ". $GLOBALS['ecs']->table('back_goods') ." set status_refund='$status_refund' where back_type ='0' and back_id='$back_id' ";

       $GLOBALS['db']->query($sql);

    }

}



/**

 * 取得退货单信息

 * @param   int     $back_id   退货单 id（如果 back_id > 0 就按 id 查，否则按 sn 查）

 * @return  array   退货单信息（金额都有相应格式化的字段，前缀是 formated_ ）

 */

function back_order_info($back_id)

{

    $return_order = array();

    if (empty($back_id) || !is_numeric($back_id))

    {

        return $return_order;

    }



    $where = '';



    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('back_order') . "

            WHERE back_id = '$back_id'

            $where

            LIMIT 0, 1";

    $back = $GLOBALS['db']->getRow($sql);

    if ($back)

    {

        /* 格式化金额字段 */

        $back['formated_insure_fee']     = price_format($back['insure_fee'], false);

        $back['formated_shipping_fee']   = price_format($back['shipping_fee'], false);



        /* 格式化时间字段 */

        $back['formated_add_time']       = local_date($GLOBALS['_CFG']['time_format'], $back['add_time']);



        if ($back['back_type'] == 4)

        {

            $back['money_paid'] = $GLOBALS['db']->getOne("SELECT money_paid FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = " . $back['order_id']);

        }



        /* 退换货状态   退款状态 */



        $return_order = $back;

    }

    return $return_order;

}



?>