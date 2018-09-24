<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/* 载入语言文件 */
require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/agent.php');

assign_template();

$action  = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
$smarty->assign('action', $action);

$user_id = $_SESSION['user_id'];

/* 检查用户是否为代理商 */
$user_info = is_agent($user_id);

// 店铺回收记录
if ($action == 'recover_shop') {
    $sql = "SELECT count(*) FROM " .$GLOBALS['ecs']->table('agent_shop'). " WHERE chuzu_status = 3 AND agent_id='". $user_id ."' ";
    $record_count = $GLOBALS['db']->getOne($sql);
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    // 分页函数
    $pager = get_pager('shop.php', array( 'act' => $action ), $record_count, $page);

    // 获取店铺回收记录
    $sql  = "SELECT shop_id, shop_code, end_time FROM " .$GLOBALS['ecs']->table('agent_shop'). " WHERE chuzu_status = 3 AND agent_id='". $user_id . "' ORDER BY end_time desc ". " limit " . $pager['start'] . ", " . $pager['size'];

    $recover_shop_list = $GLOBALS['db']->getAll($sql);

    foreach ($recover_shop_list as $key => $value) {
        $recover_shop_list[$key]['end_time'] = local_date('Y-m-d H:i', $value['end_time']);
    }
    $smarty->assign('recover_shop_list', $recover_shop_list);
    $smarty->assign('pager', $pager);
	$smarty->display('agent_recover_shop_list.dwt');
}
// 店铺回收详情
elseif ($action == 'recover_detail') {
    $shop_id = intval($_REQUEST['shop_id']);
    if (empty($shop_id)) {
        show_message('非法操作');
    }
    $sql = "SELECT a.shop_id, a.chuzu_time, a.jh_time, a.end_time, b.user_name, b.mobile_phone, b.email, c.value AS shop_name FROM " . $GLOBALS['ecs']->table('agent_shop') . " a LEFT JOIN " . $GLOBALS['ecs']->table('users') . " b ON a.user_id = b.user_id LEFT JOIN " . $GLOBALS['ecs']->table('supplier_shop_config') . " c ON a.shop_id = c.supplier_id AND c.code = 'shop_name' WHERE a.shop_id = " . $shop_id;
    $recover_detail = $GLOBALS['db']->getRow($sql);
    $recover_detail['chuzu_time'] = local_date('Y-m-d', $value['chuzu_time']);
    $recover_detail['jh_time'] = local_date('Y-m-d', $value['jh_time']);
    $recover_detail['end_time'] = local_date('Y-m-d', $value['end_time']);
    $smarty->assign('recover_detail', $recover_detail);
    $smarty->display('agent_recover_shop_detail.dwt');
}
// 租客列表（店铺会员）
if ($action == 'renter_list') {
    $where = " WHERE a.chuzu_status = 2 AND a.agent_id = ". $user_id;
    $shop_id = intval($_REQUEST['shop_id']);
    $user_name = trim($_REQUEST['user_name']);
    if ($shop_id) {
        $where .= " AND a.shop_id = " . $shop_id;
    }
    if ($user_name) {
        $where .= " AND b.user_name like '%".$user_name."%'";
    }

    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('agent_shop') . " a LEFT JOIN " . $GLOBALS['ecs']->table('users') . " b ON a.user_id = b.user_id " . $where;

    $record_count = $GLOBALS['db']->getOne($sql);
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    // 分页函数
    $pager = get_pager('shop.php', array( 'act' => $action ), $record_count, $page);

    // 获取租客列表
    $sql  = "SELECT a.shop_id, a.chuzu_time, b.user_name FROM " . $GLOBALS['ecs']->table('agent_shop') . " a LEFT JOIN " . $GLOBALS['ecs']->table('users') . " b ON a.user_id = b.user_id " . $where . " ORDER BY chuzu_time desc ". " limit " . $pager['start'] . ", " . $pager['size'];

    $renter_list = $GLOBALS['db']->getAll($sql);

    foreach ($renter_list as $key => $value) {
        $renter_list[$key]['chuzu_time'] = local_date('Y-m-d', $value['chuzu_time']);
    }
    $smarty->assign('renter_list', $renter_list);
    $smarty->assign('pager', $pager);
    $smarty->display('agent_renter_list.dwt');
}
// 租客详情
elseif ($action == 'renter_detail') {
    $shop_id = intval($_REQUEST['shop_id']);
    if (empty($shop_id)) {
        show_message('非法操作');
    }
    $sql = "SELECT a.shop_id, a.chuzu_time, a.jh_time, b.user_name, b.mobile_phone, b.email, c.value AS shop_name FROM " . $GLOBALS['ecs']->table('agent_shop') . " a LEFT JOIN " . $GLOBALS['ecs']->table('users') . " b ON a.user_id = b.user_id LEFT JOIN " . $GLOBALS['ecs']->table('supplier_shop_config') . " c ON a.shop_id = c.supplier_id AND c.code = 'shop_name' WHERE a.shop_id = " . $shop_id;
    $renter_detail = $GLOBALS['db']->getRow($sql);
    $renter_detail['chuzu_time'] = local_date('Y-m-d', $value['chuzu_time']);
    $renter_detail['jh_time'] = local_date('Y-m-d', $value['jh_time']);
    $smarty->assign('renter_detail', $renter_detail);
    $smarty->display('agent_renter_detail.dwt');
}
// 租客搜索
elseif ($action == 'renter_search') {
    $sql  = "SELECT shop_id, shop_code FROM " . $GLOBALS['ecs']->table('agent_shop') . " WHERE chuzu_status = 2 AND agent_id='". $user_id . "' ORDER BY chuzu_time desc";

    $renter_list = $GLOBALS['db']->getAll($sql);
    $smarty->assign('renter_list', $renter_list);
    $smarty->display('agent_renter_search.dwt');
}
// 厂家商品引荐
elseif ($action == 'factory_apply_list') {
    $pass_status = intval($_REQUEST['pass_status']);  //0未审核 1审核通过 2审核不通过

    // 待审核
    $wait_pass = $GLOBALS['db']->getOne("select count(*) from " . $GLOBALS['ecs']->table('factory') . " where pass_status = 0 and agent_id = $user_id");
    $smarty->assign('wait_pass', $wait_pass);

    // 审核通过
    $pass = $GLOBALS['db']->getOne("select count(*) from " . $GLOBALS['ecs']->table('factory') . " where pass_status = 1 and agent_id = $user_id");
    $smarty->assign('pass', $pass);

    // 审核不通过
    $unpass = $GLOBALS['db']->getOne("select count(*) from " . $GLOBALS['ecs']->table('factory') . " where pass_status = 2 and agent_id = $user_id");
    $smarty->assign('unpass', $unpass);

    $sql = "select count(*) from " . $GLOBALS['ecs']->table('factory') . " where agent_id = " . $user_id . " and pass_status = " . $pass_status;
    $record_count = $GLOBALS['db']->getOne($sql);
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    // 分页函数
    $pager = get_pager('agent.php', array( 'act' => $action ), $record_count, $page);

    $sql = "select * from " . $GLOBALS['ecs']->table('factory') . " where agent_id = " . $user_id . " and pass_status = " . $pass_status . " order by add_time desc limit " . $pager['start'] . ", " . $pager['size'];
    $factory_list = $GLOBALS['db']->getAll($sql);
    foreach ($factory_list as $key => $value) {
        $factory_list[$key]['add_time'] = local_date('Y-m-d H:i', $value['add_time']);
    }

    $smarty->assign('factory_list', $factory_list);
    $smarty->assign('pager', $pager);
    $smarty->display('agent_factory_apply_list_' . $pass_status . '.dwt');
}
// 添加厂家
elseif ($action == 'add_factory') {
    $smarty->display('agent_add_factory.dwt');
}
// 添加厂家提交
elseif ($action == 'insert_factory') {
    include_once (ROOT_PATH . 'includes/lib_transaction.php');
    include_once (ROOT_PATH . '/includes/cls_image.php');
    $image = new cls_image($_CFG['bgcolor']);

    $agent_id = $user_id;   //代理商ID
    $factory_name = trim($_REQUEST['factory_name']);
    $factory_type = trim($_REQUEST['factory_type']);
    $contacts_name = trim($_REQUEST['contacts_name']);
    $contacts_phone = trim($_REQUEST['contacts_phone']);
    $goods_desc = trim($_REQUEST['goods_desc']);
    $address = trim($_REQUEST['address']);
    $card = trim($_REQUEST['card']);
    $pass_status = 0;   //0未审核 1审核通过 2审核不通过
    $add_time = gmtime();
    if(isset($_FILES['business_card']) && $_FILES['business_card']['tmp_name'] != '') {
        $business_card = $image->upload_image($_FILES['business_card']);
        if($business_card === false) {
            show_message($image->error_msg());
        }
    }

    if(isset($_FILES['tax_card']) && $_FILES['tax_card']['tmp_name'] != '') {
        $tax_card = $image->upload_image($_FILES['tax_card']);
        if($tax_card === false) {
            show_message($image->error_msg());
        }
    }

    if(isset($_FILES['code_card']) && $_FILES['code_card']['tmp_name'] != '') {
        $code_card = $image->upload_image($_FILES['code_card']);
        if($code_card === false) {
            show_message($image->error_msg());
        }
    }

    if(empty($business_card)) {
        show_message('请上传营业执照');
    }

    if(empty($tax_card)) {
        show_message('请上传税务登记证');
    }

    if(empty($code_card)) {
        show_message('请上传机构代码证');
    }

    $sql = "insert into " . $GLOBALS['ecs']->table('factory') . "(`factory_name`,`factory_type`,`contacts_name`,`contacts_phone`,`goods_desc`,`business_card`,`tax_card`,`code_card`,`agent_id`,`pass_status`,`add_time`) values('$factory_name','$factory_type','$contacts_name','$contacts_phone','$goods_desc','$business_card','$tax_card','$code_card','$agent_id','$pass_status','$add_time')";
    $GLOBALS['db']->query($sql);

    // $smarty->display('agent_apply_4.dwt');
    ecs_header("Location: agent.php?act=factory_apply_list\n");
    exit;
}
// 厂家商品明细
elseif ($action == 'factory_goods_list') {
    $pass_status = intval($_REQUEST['pass_status']);  //0未审核 1审核通过 2审核不通过

    $sql = "select count(*) from " . $GLOBALS['ecs']->table('factory') . " a inner join " . $GLOBALS['ecs']->table('goods') . " b on a.factory_id = b.factory_id where a.agent_id = " . $user_id . " and pass_status = " . $pass_status;
    $record_count = $GLOBALS['db']->getOne($sql);
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    // 分页函数
    $pager = get_pager('agent.php', array( 'act' => $action ), $record_count, $page);

    $sql = "select a.factory_id, a.factory_name, a.agent_id, b.goods_id, b.goods_name, b.add_time from " . $GLOBALS['ecs']->table('factory') . " a inner join " . $GLOBALS['ecs']->table('goods') . " b on a.factory_id = b.factory_id where a.agent_id = " . $user_id . " order by add_time desc limit " . $pager['start'] . ", " . $pager['size'];
    $goods_list = $GLOBALS['db']->getAll($sql);
    foreach ($goods_list as $key => $value) {
        $goods_list[$key]['add_time'] = local_date('Y-m-d', $value['add_time']);
        $goods_list[$key]['sale_total'] = sales_goods_number($value['goods_id']);
    }
    $smarty->assign('goods_list', $goods_list);
    $smarty->assign('pager', $pager);
    $smarty->display('agent_factory_goods_list.dwt');
}
// 店铺未安置会员列表（过期店铺的店主推荐注册的会员）
if ($action == 'weianzhi_list') {
    $where = " WHERE b.chuzu_status = 3 AND b.agent_id = " . $user_id;
    $shop_id = intval($_REQUEST['shop_id']);
    $user_name = trim($_REQUEST['user_name']);
    if ($shop_id) {
        $where .= " AND b.shop_id = " . $shop_id;
    }
    if ($user_name) {
        $where .= " AND a.user_name like '%".$user_name."%'";
    }

    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('users') . " a LEFT JOIN " . $GLOBALS['ecs']->table('agent_shop') . " b ON a.parent_id = b.user_id " . $where;

    $record_count = $GLOBALS['db']->getOne($sql);
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    // 分页函数
    $pager = get_pager('shop.php', array( 'act' => $action ), $record_count, $page);

    // 获取未安置会员列表
    $sql  = "SELECT b.shop_id, b.shop_code, b.agent_id, b.user_id AS shop_user_id, b.chuzu_status, b.end_time, a.user_id, a.user_name, a.mobile_phone, a.parent_id, a.reg_time FROM " . $GLOBALS['ecs']->table('users') . " a LEFT JOIN " . $GLOBALS['ecs']->table('agent_shop') . " b ON a.parent_id = b.user_id " . $where . " ORDER BY a.reg_time desc ". " limit " . $pager['start'] . ", " . $pager['size'];
    $weianzhi_list = $GLOBALS['db']->getAll($sql);
    foreach ($weianzhi_list as $key => $value) {
        $weianzhi_list[$key]['end_time'] = local_date('Y-m-d', $value['end_time']);
        $weianzhi_list[$key]['reg_time'] = local_date('Y-m-d', $value['reg_time']);
    }
    $smarty->assign('weianzhi_list', $weianzhi_list);
    $smarty->assign('pager', $pager);
    $smarty->display('agent_weianzhi_list.dwt');
}
// 转出未安置会员
elseif ($action == 'rollout_user') {
    $zc_user_id = $_REQUEST['user_id']; //转出的用户ID parent_id所属店铺为过期，并且是我的店铺
    $user_info = $db->getRow("SELECT a.user_name, a.parent_id, a.user_id, b.shop_id FROM " . $GLOBALS['ecs']->table('users') . " a INNER JOIN " . $GLOBALS['ecs']->table('agent_shop') . " b ON a.parent_id = b.user_id WHERE a.user_id = " . $zc_user_id . " AND b.chuzu_status = 3 AND b.agent_id = " . $user_id);
    if (empty($user_info)) {
        show_message('会员不存在');
    }
    $sql  = "SELECT shop_id, shop_code FROM " . $GLOBALS['ecs']->table('agent_shop') . " WHERE chuzu_status = 2 AND shop_id <> " . $user_info['shop_id'] . " AND agent_id='". $user_id . "' ORDER BY end_time desc";
    $shop_list = $GLOBALS['db']->getAll($sql);
    $smarty->assign('shop_list', $shop_list);
    $smarty->assign('user_info', $user_info);
    $smarty->display('agent_rollout_user.dwt');
}
// 转出未安置会员
elseif ($action == 'rollout_submit') {
    $zc_user_id = intval($_POST['user_id']);    //被转出的用户ID
    $shop_id = intval($_POST['shop_id']);    //转入的店铺ID
    if (empty($user_id)) {
        show_message('会员不存在');
    }
    if (empty($shop_id)) {
        show_message('请选择转出店铺');
    }

    $sql = "SELECT user_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE shop_id = " . $shop_id;
    $shop_user_id = $GLOBALS['db']->getOne($sql);   //转入店铺的用户ID

    // 转出操作
    $sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET parent_id = " . $shop_user_id . " WHERE user_id = " . $zc_user_id;
    $GLOBALS['db']->query($sql);

    ecs_header("Location: agent.php?act=weianzhi_list\n");
    exit;
}
// 未安置会员删除
elseif ($action == 'weianzhi_del') {
    $user_ids = $_REQUEST['user_id'];
    if (empty($user_ids)) {
        show_message('请选择要删除的选项');
    }
    if (is_array($user_ids)) {
        foreach ($user_ids as $key => $value) {
            $sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET parent_id = 0 WHERE user_id = " . $value;
            $GLOBALS['db']->query($sql);
        }
        ecs_header("Location: agent.php?act=weianzhi_list\n");
        exit;
    } else {
        show_message('非法操作');
    }
}
// 未安置会员搜索
elseif ($action == 'weianzhi_search') {
    $sql  = "SELECT shop_id, shop_code FROM " . $GLOBALS['ecs']->table('agent_shop') . " WHERE chuzu_status = 3 AND agent_id='". $user_id . "' ORDER BY end_time desc";

    $shop_list = $GLOBALS['db']->getAll($sql);
    $smarty->assign('shop_list', $shop_list);
    $smarty->display('agent_weianzhi_search.dwt');
}
// 提现记录（未完成）
elseif ($action == 'tx_log') {
    include_once (ROOT_PATH . 'includes/lib_clips.php');

    $sql = "SELECT user_money, frozen_money FROM " . $GLOBALS['ecs']->table('users') . "WHERE user_id = '$user_id'";
    $money_info = $GLOBALS['db']->getRow($sql);
    $smarty->assign('user_money', $money_info['user_money']);
    $smarty->assign('frozen_money', $money_info['frozen_money']);
    $smarty->assign('money_total', $money_info['user_money']+$money_info['frozen_money']);

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    /* 获取记录条数 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('user_account') . " WHERE user_id = '$user_id'" . " AND process_type = 1";
    $record_count = $db->getOne($sql);

    // 分页函数
    $pager = get_pager('user.php', array(
        'act' => $action
    ), $record_count, $page);

    // 获取剩余余额
    $surplus_amount = get_user_surplus($user_id);
    if(empty($surplus_amount))
    {
        $surplus_amount = 0;
    }

    // 获取余额记录
    $account_log = get_agent_account_log($user_id, $pager['size'], $pager['start']);
    // 模板赋值
    $smarty->assign('surplus_amount', price_format($surplus_amount, false));
    $smarty->assign('account_log', $account_log);
    $smarty->assign('pager', $pager);
    $smarty->display('agent_tx_log.dwt');
}
// 会员购物返利记录（分成记录）
elseif ($action == 'separate_log') {
    $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);

    $page = ! empty($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
    $size = ! empty($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;

    empty($affiliate) && $affiliate = array();

    if(empty($affiliate['config']['separate_by']))
    {
        // 推荐注册分成
        $affdb = array();
        $num = count($affiliate['item']);
        $up_uid = "'$user_id'";
        $all_uid = "'$user_id'";
        for($i = 1; $i <= $num; $i ++)
        {
            $count = 0;
            if($up_uid)
            {
                $sql = "SELECT user_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE parent_id IN($up_uid)";
                $query = $GLOBALS['db']->query($sql);
                $up_uid = '';
                while($rt = $GLOBALS['db']->fetch_array($query))
                {
                    $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                    if($i < $num)
                    {
                        $all_uid .= ", '$rt[user_id]'";
                    }
                    $count ++;
                }
            }
            $affdb[$i]['num'] = $count;
            $affdb[$i]['point'] = $affiliate['item'][$i - 1]['level_point'];
            $affdb[$i]['money'] = $affiliate['item'][$i - 1]['level_money'];
        }
        $smarty->assign('affdb', $affdb);

        // 累计分成
        // $sqltotal = "SELECT SUM(a.money) FROM " . $GLOBALS['ecs']->table('order_info') . " o" . " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id" . " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND o.extension_code <> 'exchange_goods' AND (u.parent_id IN ($all_uid)  AND o.is_brokerage=1 AND o.is_separate = 1 OR a.user_id = '$user_id' AND o.is_separate = 1)";

        // $sqlcount = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('order_info') . " o" . " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id" . " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND o.extension_code <> 'exchange_goods'  AND o.is_brokerage=1 AND (u.parent_id IN ($all_uid) AND o.is_separate = 1 OR a.user_id = '$user_id' AND o.is_separate = 1)";

        // $sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type, a.time, u.user_name, u.parent_id FROM " . $GLOBALS['ecs']->table('order_info') . " o" . " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id" . " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND o.is_brokerage=1 AND o.extension_code <> 'exchange_goods' AND (u.parent_id IN ($all_uid) AND o.is_separate = 1 OR a.user_id = '$user_id' AND o.is_separate = 1)" . " ORDER BY order_id DESC";
        // 
        //记录修改hao2018--分成的才展示
        $sqltotal = "SELECT SUM(a.money) FROM " . $GLOBALS['ecs']->table('order_info') . " o" . " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id" . " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND o.extension_code <> 'exchange_goods' AND a.user_id=$user_id AND a.separate_type=0";
        $sqlcount = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('order_info') . " o" . " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id" . " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND o.extension_code <> 'exchange_goods' AND a.user_id=$user_id AND a.separate_type=0";
        $sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type, a.time, u.user_name, u.parent_id FROM " . $GLOBALS['ecs']->table('order_info') . " o" . " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id" . " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" . " WHERE o.user_id > 0  AND o.extension_code <> 'exchange_goods' AND a.user_id=$user_id AND a.separate_type=0 ORDER BY order_id DESC";
        /*
         * SQL解释：
         *
         * 订单、用户、分成记录关联
         * 一个订单可能有多个分成记录
         *
         * 1、订单有效 o.user_id > 0
         * 2、满足以下之一：
         * a.直接下线的未分成订单 u.parent_id IN ($all_uid) AND o.is_separate = 0
         * 其中$all_uid为该ID及其下线(不包含最后一层下线)
         * b.全部已分成订单 a.user_id = '$user_id' AND o.is_separate > 0
         *
         */

        $affiliate_intro = nl2br(sprintf($_LANG['affiliate_intro'][$affiliate['config']['separate_by']], $affiliate['config']['expire'], $_LANG['expire_unit'][$affiliate['config']['expire_unit']], $affiliate['config']['level_register_all'], $affiliate['config']['level_register_up'], $affiliate['config']['level_money_all'], $affiliate['config']['level_point_all']));
    }

    $count = $GLOBALS['db']->getOne($sqlcount);
    $separate_total = $GLOBALS['db']->getOne($sqltotal);    //累计分成
    $separate_total = $separate_total ? $separate_total : 0;

    $max_page = ($count > 0) ? ceil($count / $size) : 1;
    if($page > $max_page)
    {
        $page = $max_page;
    }

    $res = $GLOBALS['db']->SelectLimit($sql, $size, ($page - 1) * $size);
    $logdb = array();
    while($rt = $GLOBALS['db']->fetchRow($res))
    {
        if(! empty($rt['suid']))
        {
            // 在affiliate_log有记录
            if($rt['separate_type'] == - 1 || $rt['separate_type'] == - 2)
            {
                // 已被撤销
                $rt['is_separate'] = 3;
            }
        }
        $rt['order_sn'] = substr($rt['order_sn'], 0, strlen($rt['order_sn']) - 5) . "***" . substr($rt['order_sn'], - 2, 2);
        $rt['time'] = local_date('Y-m-d', $rt['time']);
        $rt['shop_code'] = '';
        $rt['shop_name'] = '';
        if($rt['parent_id']>0){
            $supplier_user = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id=".$rt['parent_id']);
            $shop_info = $GLOBALS['db']->getRow("SELECT a.shop_code, b.value AS shop_name FROM " . $GLOBALS['ecs']->table('agent_shop') . " a LEFT JOIN " . $GLOBALS['ecs']->table('supplier_shop_config') . " b ON a.shop_id = b.supplier_id AND b.code = 'shop_name' WHERE a.shop_id = " . $supplier_user['shop_id']);
            $rt['shop_code'] = $shop_info['shop_code'];
            $rt['shop_name'] = $shop_info['shop_name'];
        }
        
        // 
        // $shop_info = $GLOBALS['db']->getRow("SELECT a.shop_code, b.value AS shop_name FROM " . $GLOBALS['ecs']->table('agent_shop') . " a LEFT JOIN " . $GLOBALS['ecs']->table('supplier_shop_config') . " b ON a.shop_id = b.supplier_id AND b.code = 'shop_name' WHERE a.shop_id = " . $rt['supplier_id']);
        // $rt['shop_code'] = $shop_info['shop_code'];
        // $rt['shop_name'] = $shop_info['shop_name'];
        $rt['money'] = $rt['money'] ? $rt['money'] : '0.00';
        $logdb[] = $rt;
    }

    $url_format = "agent.php?act=separate_log&page=";

    $pager = array(
        'page' => $page,'size' => $size,'sort' => '','order' => '','record_count' => $count,'page_count' => $max_page,'page_first' => $url_format . '1','page_prev' => $page > 1 ? $url_format . ($page - 1) : "javascript:;",'page_next' => $page < $max_page ? $url_format . ($page + 1) : "javascript:;",'page_last' => $url_format . $max_page,'array' => array()
    );
    for($i = 1; $i <= $max_page; $i ++)
    {
        $pager['page_number'][$i] = $url_format.$i;
    }

    $smarty->assign('url_format', $url_format);
    $smarty->assign('pager', $pager);
    $smarty->assign('affiliate_intro', $affiliate_intro);
    $smarty->assign('affiliate_type', $affiliate['config']['separate_by']);
    $smarty->assign('separate_total', $separate_total);
    $smarty->assign('logdb', $logdb);
    $smarty->assign('lang', $_LANG);

    $smarty->display('agent_separate_log.dwt');
}
// 租金返利记录
if ($action == 'rebate_log') {
    // 累计返利
    $sql = "SELECT SUM(rebate_money) FROM " . $GLOBALS['ecs']->table('agent_rebate_log') . " WHERE agent_id = " . $user_id;
    $rebate_total = $GLOBALS['db']->getOne($sql);
    $smarty->assign('rebate_total', $rebate_total);

    $where = " WHERE b.agent_id = " . $user_id;

    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('agent_rebate_log') . " a LEFT JOIN " . $GLOBALS['ecs']->table('agent_shop') . " b ON a.shop_id = b.shop_id " . $where;

    $record_count = $GLOBALS['db']->getOne($sql);
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    // 分页函数
    $pager = get_pager('shop.php', array( 'act' => $action ), $record_count, $page);

    // 获取未安置会员列表
    $sql  = "SELECT a.*, b.shop_code FROM " . $GLOBALS['ecs']->table('agent_rebate_log') . " a LEFT JOIN " . $GLOBALS['ecs']->table('agent_shop') . " b ON a.shop_id = b.shop_id " . $where . " ORDER BY a.add_time desc ". " limit " . $pager['start'] . ", " . $pager['size'];
    $rebate_log = $GLOBALS['db']->getAll($sql);
    foreach ($rebate_log as $key => $value) {
        $rebate_log[$key]['add_time'] = local_date('Y-m-d', $value['add_time']);
        $rebate_log[$key]['shop_name'] = $GLOBALS['db']->getOne("SELECT value FROM " . $GLOBALS['ecs']->table('supplier_shop_config') . " WHERE supplier_id = " . $value['shop_id'] . " AND code = 'shop_name'");
    }
    $smarty->assign('rebate_log', $rebate_log);
    $smarty->assign('pager', $pager);
    $smarty->display('agent_rebate_log.dwt');
}
// 引荐厂家销售返利记录
if ($action == 'factory_reward_log') {
    // 累计返利
    $sql = "SELECT SUM(money) FROM " . $GLOBALS['ecs']->table('agent_reward_log') . " WHERE agent_id = " . $user_id . " AND reward_type = 2";
    $reward_total = $GLOBALS['db']->getOne($sql);
    $smarty->assign('reward_total', $reward_total);

    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('agent_reward_log') . " WHERE reward_type = 2 AND agent_id = " . $user_id;

    $record_count = $GLOBALS['db']->getOne($sql);
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    // 分页函数
    $pager = get_pager('shop.php', array( 'act' => $action ), $record_count, $page);

    // 获取未安置会员列表
    $sql  = "SELECT * FROM " . $GLOBALS['ecs']->table('agent_reward_log') . " WHERE reward_type = 2 AND agent_id = " . $user_id . " ORDER BY add_time desc ". " limit " . $pager['start'] . ", " . $pager['size'];
    $reward_log = $GLOBALS['db']->getAll($sql);
    foreach ($reward_log as $key => $value) {
        $reward_log[$key]['add_time'] = local_date('Y-m-d', $value['add_time']);
        $reward_log[$key]['order_sn'] = $GLOBALS['db']->getOne("SELECT order_sn FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = " . $value['order_id']);
        $reward_log[$key]['factory_name'] = $GLOBALS['db']->getOne("SELECT b.factory_name FROM " . $GLOBALS['ecs']->table('goods') . " a LEFT JOIN " . $GLOBALS['ecs']->table('factory') . " b ON a.factory_id = b.factory_id WHERE a.goods_id = " . $value['goods_id']);
    }
    $smarty->assign('reward_log', $reward_log);
    $smarty->assign('pager', $pager);
    $smarty->display('agent_factory_rebate_log.dwt');
}

/*获取有效订单信息*/
function sales_goods_order($goods_id){
    $sql = "select * from " . $GLOBALS['ecs']->table('order_goods') . " AS g ,".$GLOBALS['ecs']->table('order_info') . " AS o WHERE o.order_id=g.order_id and g.goods_id = " . $goods_id . " and o.order_status in(1,5) " ;//o.order_status=1 1表示确认订单，5已分单
    return $GLOBALS['db']->getAll($sql);
}
/*获取某个商品有效订单  销量统计*/
function sales_goods_number($goods_id){
    $arr = sales_goods_order($goods_id);
    //return array_sum($arr['goods_number']);
    foreach($arr as $k=>$v){
        $val[] = $v['goods_number'];
    }
    $count = array_sum($val);
    if(!empty($count)){
        return $count;
    }else{
        return '0';
    }
}

?>