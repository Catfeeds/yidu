<?php

/**
 * ECSHOP 管理中心积分兑换商品程序文件
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author $
 * $Id $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*初始化数据交换对象 */
$exc   = new exchange($ecs->table("exchange_goods"), $db, 'goods_id', 'exchange_integral');
//$image = new cls_image();

/*------------------------------------------------------ */
//-- 商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 权限判断 */
    admin_priv('exchange_goods');

    //每次进入的时候，都要去判断所有的抽奖订单是否有过期的订单
    $sql = 'SELECT order_id,add_time,extension_id,extension_num FROM ' .$GLOBALS['ecs']->table('order_info'). ' WHERE order_status != 2 AND pay_time=0 AND extension_code = "exchange_goods" AND is_lucky = 0';
    $res = $GLOBALS['db']->getAll($sql);
    foreach ($res as $k => $v) {
        if ($v['add_time']*1+60*10 < time()) {
            $sql = 'UPDATE ' . $ecs->table('order_info') . " SET `order_status`= 2  WHERE `order_id`='" . $v['order_id'] . "'";
            $GLOBALS['db']->query($sql);

            //计算这个取消订单里面的抽奖码。
//            $arr = count(explode(',', $v['extension_num']));

            //49 start
            // //查询这个抽奖订单的抽奖商品。更改参与人数
            $exchange_number = $GLOBALS['db']->getOne("SELECT exchange_number FROM " . $ecs->table('exchange_goods') . " WHERE goods_id=$v[extension_id] and closing_index = 0");

            $s = 'SELECT goods_number FROM ' .$GLOBALS['ecs']->table('order_goods'). ' WHERE order_id ='.$v['order_id'];
            $arr = $GLOBALS['db']->getOne($s);
            //49 end
            $all = $exchange_number *1 - $arr *1 ;

            $GLOBALS['db']->query('UPDATE ' . $ecs->table('exchange_goods') . "SET exchange_number = $all WHERE goods_id='$v[extension_id]'");

        }
    }



    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      $_LANG['15_exchange_goods_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['exchange_goods_add'], 'href' => 'exchange_goods.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $goods_list = get_exchange_goodslist();
    //判断如果满28人且没开过盘的话，再去判断是否都已付款
    foreach ($goods_list['arr'] as $k => $v) {
        if ($v['exchange_number'] == 28 && $v['open_time']== 0) {
            $kk = $GLOBALS['db']->getAll( 'SELECT pay_status FROM ' .$GLOBALS['ecs']->table('order_info'). ' WHERE extension_code = "exchange_goods" AND order_status != 2 and extension_id = '.$v['goods_id']);

            foreach ($kk as $k1 => $v1) {
                if ($v1['pay_status'] != 2) {
                    $smarty->assign('no',       1);
                }
            }

        }
    }

    // print_r($goods_list);exit;
    $smarty->assign('goods_list',    $goods_list['arr']);
    $smarty->assign('filter',        $goods_list['filter']);
    $smarty->assign('record_count',  $goods_list['record_count']);
    $smarty->assign('page_count',    $goods_list['page_count']);

    $sort_flag  = sort_flag($goods_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('exchange_goods_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('exchange_goods');
    //每次进入的时候，都要去判断所有的抽奖订单是否有过期的订单
    $sql = 'SELECT order_id,add_time,extension_id,extension_num FROM ' .$GLOBALS['ecs']->table('order_info'). ' WHERE order_status != 2 AND pay_time=0 AND extension_code = "exchange_goods" AND is_lucky = 0';
    $res = $GLOBALS['db']->getAll($sql);
    foreach ($res as $k => $v) {
        if ($v['add_time']*1+60*10 < time()) {
            $sql = 'UPDATE ' . $ecs->table('order_info') . " SET `order_status`= 2  WHERE `order_id`='" . $v['order_id'] . "'";
            $GLOBALS['db']->query($sql);

            //计算这个取消订单里面的抽奖码。
//            $arr = count(explode(',', $v['extension_num']));

            //49 start
            // //查询这个抽奖订单的抽奖商品。更改参与人数
            $exchange_number = $GLOBALS['db']->getOne("SELECT exchange_number FROM " . $ecs->table('exchange_goods') . " WHERE goods_id=$v[extension_id] and closing_index = 0");

            $s = 'SELECT goods_number FROM ' .$GLOBALS['ecs']->table('order_goods'). ' WHERE order_id ='.$v['order_id'];
            $arr = $GLOBALS['db']->getOne($s);
            //49 end
            $all = $exchange_number *1 - $arr *1 ;

            $GLOBALS['db']->query('UPDATE ' . $ecs->table('exchange_goods') . "SET exchange_number = $all WHERE goods_id='$v[extension_id]'");

        }
    }
    $goods_list = get_exchange_goodslist();

    //判断如果满28人且没开过盘的话，再去判断是否都已付款
    foreach ($goods_list['arr'] as $k => $v) {
        if ($v['exchange_number'] == 28 && $v['open_time']== 0) {
            $kk = $GLOBALS['db']->getAll( 'SELECT pay_status FROM ' .$GLOBALS['ecs']->table('order_info'). ' WHERE extension_code = "exchange_goods" AND order_status != 2 and extension_id = '.$v['goods_id']);

            foreach ($kk as $k1 => $v1) {
                if ($v1['pay_status'] != 2) {
                    $smarty->assign('no',       1);
                }
            }

        }
    }


    $smarty->assign('goods_list',    $goods_list['arr']);
    $smarty->assign('filter',        $goods_list['filter']);
    $smarty->assign('record_count',  $goods_list['record_count']);
    $smarty->assign('page_count',    $goods_list['page_count']);

    $sort_flag  = sort_flag($goods_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('exchange_goods_list.htm'), '',
        array('filter' => $goods_list['filter'], 'page_count' => $goods_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加商品
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('exchange_goods');
    //每次进入的时候，都要去判断所有的抽奖订单是否有过期的订单
    $sql = 'SELECT order_id,add_time,extension_id,extension_num FROM ' .$GLOBALS['ecs']->table('order_info'). ' WHERE order_status != 2 AND pay_time=0 AND extension_code = "exchange_goods" AND is_lucky = 0';
    $res = $GLOBALS['db']->getAll($sql);
    foreach ($res as $k => $v) {
        if ($v['add_time']*1+60*10 < time()) {
            $sql = 'UPDATE ' . $ecs->table('order_info') . " SET `order_status`= 2  WHERE `order_id`='" . $v['order_id'] . "'";
            $GLOBALS['db']->query($sql);

            //计算这个取消订单里面的抽奖码。
//            $arr = count(explode(',', $v['extension_num']));

            //49 start
            // //查询这个抽奖订单的抽奖商品。更改参与人数
            $exchange_number = $GLOBALS['db']->getOne("SELECT exchange_number FROM " . $ecs->table('exchange_goods') . " WHERE goods_id=$v[extension_id] and closing_index = 0");

            $s = 'SELECT goods_number FROM ' .$GLOBALS['ecs']->table('order_goods'). ' WHERE order_id ='.$v['order_id'];
            $arr = $GLOBALS['db']->getOne($s);
            //49 end
            $all = $exchange_number *1 - $arr *1 ;

            $GLOBALS['db']->query('UPDATE ' . $ecs->table('exchange_goods') . "SET exchange_number = $all WHERE goods_id='$v[extension_id]'");

        }
    }
    /*初始化*/
    $goods = array();
    $goods['is_exchange'] = 1;
    $goods['is_hot']      = 0;
    $goods['option']      = '<option value="0">'.$_LANG['make_option'].'</option>';

    $smarty->assign('goods',       $goods);
    $smarty->assign('ur_here',     $_LANG['exchange_goods_add']);
    $smarty->assign('action_link', array('text' => $_LANG['15_exchange_goods_list'], 'href' => 'exchange_goods.php?act=list'));
    $smarty->assign('form_action', 'insert');
    assign_query_info();


    $smarty->display('exchange_goods_info.htm');
}

/*------------------------------------------------------ */
//-- 添加商品
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('exchange_goods');

    /*检查是否重复*/
    $is_only = $exc->is_only('goods_id', $_POST['goods_id'],0, " goods_id ='$_POST[goods_id]'");

    if (!$is_only)
    {
        sys_msg($_LANG['goods_exist'], 1);
    }
    //2018 wenjun start 只能存在一个推荐

    if ($_POST['is_best'] == 1) {
        $o = 'UPDATE ' . $ecs->table('exchange_goods') . " SET `is_best`= 0 ";
        $db->query($o);
    }

    //2018 wenjun end
    /*插入数据*/
    $add_time = gmtime();
    if (empty($_POST['goods_id']))
    {
        $_POST['goods_id'] = 0;
    }
    $sql = "INSERT INTO ".$ecs->table('exchange_goods')."(goods_id, exchange_integral, is_exchange, is_hot,exchange_number,is_best) ".
        "VALUES ('$_POST[goods_id]', '$_POST[exchange_integral]', '$_POST[is_exchange]', '$_POST[is_hot]',0,'$_POST[is_best]')";
    // print_r($sql);exit;
    $db->query($sql);

    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'exchange_goods.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'exchange_goods.php?act=list';

    admin_log($_POST['goods_id'],'add','exchange_goods');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg($_LANG['articleadd_succeed'],0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('exchange_goods');
    //每次进入的时候，都要去判断所有的抽奖订单是否有过期的订单
    $sql = 'SELECT order_id,add_time,extension_id,extension_num FROM ' .$GLOBALS['ecs']->table('order_info'). ' WHERE order_status != 2 AND pay_time=0 AND extension_code = "exchange_goods" AND is_lucky = 0';
    $res = $GLOBALS['db']->getAll($sql);
    foreach ($res as $k => $v) {
        if ($v['add_time']*1+60*10 < time()) {
            $sql = 'UPDATE ' . $ecs->table('order_info') . " SET `order_status`= 2  WHERE `order_id`='" . $v['order_id'] . "'";
            $GLOBALS['db']->query($sql);

            //计算这个取消订单里面的抽奖码。
//            $arr = count(explode(',', $v['extension_num']));

            //查询这个抽奖订单的抽奖商品。更改参与人数
            //49 start
            // //查询这个抽奖订单的抽奖商品。更改参与人数
            $exchange_number = $GLOBALS['db']->getOne("SELECT exchange_number FROM " . $ecs->table('exchange_goods') . " WHERE goods_id=$v[extension_id] and closing_index = 0");

            $s = 'SELECT goods_number FROM ' .$GLOBALS['ecs']->table('order_goods'). ' WHERE order_id ='.$v['order_id'];
            $arr = $GLOBALS['db']->getOne($s);
            //49 end
            $all = $exchange_number *1 - $arr *1 ;

            $GLOBALS['db']->query('UPDATE ' . $ecs->table('exchange_goods') . "SET exchange_number = $all WHERE goods_id='$v[extension_id]'");

        }
    }
    /* 取商品数据 */
    $sql = "SELECT eg.*, g.goods_name ".
        " FROM " . $ecs->table('exchange_goods') . " AS eg ".
        "  LEFT JOIN " . $ecs->table('goods') . " AS g ON g.goods_id = eg.goods_id ".
        " WHERE eg.goods_id='$_REQUEST[id]'";
    $goods = $db->GetRow($sql);
    $goods['option']  = '<option value="'.$goods['goods_id'].'">'.$goods['goods_name'].'</option>';

    //避免没输入时候出现个 0 ;
    if (!$goods['closing_index']) {
        $goods['closing_index']='';
    }

    //判断如果满28人且没开过盘的话，再去判断是否都已付款
    if ($goods['exchange_number'] == 28 && $goods['open_time']== 0) {
        $kk = $GLOBALS['db']->getAll( 'SELECT pay_status FROM ' .$GLOBALS['ecs']->table('order_info'). ' WHERE extension_code = "exchange_goods" AND order_status != 2 and extension_id = '.$goods['goods_id']);
        foreach ($kk as $k1 => $v1) {
            if ($v1['pay_status'] != 2) {
                $smarty->assign('no',       1);
            }
        }
    }



    $smarty->assign('goods',       $goods);
    $smarty->assign('ur_here',     $_LANG['exchange_goods_add']);
    $smarty->assign('action_link', array('text' => $_LANG['15_exchange_goods_list'], 'href' => 'exchange_goods.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');
    $smarty->assign('is_edit', '1');//用于判断是否是修改
    assign_query_info();
    $smarty->display('exchange_goods_info.htm');
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] =='update')
{   
    // print_r($_POST);exit;
    /* 权限判断 */
    admin_priv('exchange_goods');

    if (empty($_POST['goods_id']))
    {
        $_POST['goods_id'] = 0;
    }
    //收盘指数
    $closing_index = $_POST['closing_index'];
    
    $is_exchange = $_POST['is_exchange'];
    if ($closing_index) {
        $is_exchange = 0;//开奖了，就要不能再抽奖了
        //是否有开奖时间
        $pd = 'SELECT open_time FROM ' .$GLOBALS['ecs']->table('exchange_goods'). ' WHERE goods_id = '.$_POST['goods_id'];
        $result = $GLOBALS['db']->getOne($pd);
        if (empty($result)) {

            //除去今天的开奖结果，去判断上一次开奖的是第几期
            $s = local_strtotime(date('Y-m-d',time()));

            $sta = 'SELECT stage FROM ' .$GLOBALS['ecs']->table('exchange_goods'). ' WHERE goods_id = '.$_POST['goods_id'].' AND open_time < '.$s.' order by open_time desc';
            $stage = $GLOBALS['db']->getOne($sta);
            if (empty($stage)) {
                $stage = 1 ;
            }else{
                $stage = $stage*1 + 1; //那么这一期+1
            }

            //开奖时间 + 第几期
            $time =time();
            $o = 'UPDATE ' . $ecs->table('exchange_goods') . " SET `open_time`= $time,`stage`=$stage  WHERE `goods_id`='" . $_POST['goods_id'] . "'";
            $db->query($o);
        }

        //得奖数
        $exchange_lucky = $closing_index % 28 + 1 ;
        //改变得奖的订单状态
        $sql = 'SELECT order_sn,user_id,order_id,extension_num,goods_amount,mobile FROM ' .$GLOBALS['ecs']->table('order_info'). ' WHERE order_status != 2 AND extension_code = "exchange_goods" and extension_id = '.$_POST['goods_id'];
        $res = $GLOBALS['db']->getAll($sql);

        if ($res) {
            //把抽奖序号改成数组格式
            foreach ($res as $k => $v) {
                $res[$k]['extension_num']=explode(',', $v['extension_num']);
                //数量
                $res[$k]['ex_count'] = count(explode(',', $v['extension_num']));
            }
             
            foreach ($res as $k => $v) {
                foreach ($v['extension_num'] as $k1 => $v1) {
                    if ($v1 == $exchange_lucky) {
                        //中奖订单加已标志
                        $sql = 'UPDATE ' . $ecs->table('order_info') . " SET `is_lucky`= 1  WHERE `order_id`='" . $v['order_id'] . "'";
                        $db->query($sql);

                        //把得奖的用户id号，记录下来
                        $sql = 'UPDATE ' . $ecs->table('exchange_goods') . " SET `user_id`= $v[user_id]  WHERE `goods_id`='"  .$_POST['goods_id'] . "'";
                        $db->query($sql);

                        //记录已经修改了的订单，避免再次被修改
                        $_SESSION['order_id'] = $v['order_id'];

                        //获取商品名称 wenjun 05-31
                        $goods_name = $GLOBALS['db']->getOne('SELECT goods_name FROM ' .$GLOBALS['ecs']->table('goods'). ' WHERE goods_id = '.$_POST['goods_id']);
                        $goods_name =  mb_substr($goods_name, 0, 20, 'utf-8');
                        //获取用户名 wenjun 05-31
                        $user_name = $GLOBALS['db']->getOne('SELECT user_name FROM ' .$GLOBALS['ecs']->table('users'). ' WHERE user_id = '.$v['user_id']);
                        $user_name =  mb_substr($user_name, 0, 20, 'utf-8');
                        //发送短信
                        $content = sprintf('尊敬的%s：恭喜你成为易度商城易乐透订单%s的幸运会员，订单商品:%s将会48小时内发货，请注意物流信息',$user_name,$v1['order_sn'],$goods_name);
                        require_once(ROOT_PATH . 'mobile/sms/sms.php');
                        $kk555 = sendSMS($v['mobile'], $content);
                        
                        //返回剩下的购物币
                        if ($v['ex_count']>1) {
                            
                            $zjfh_gwb = ($v['goods_amount']/$v['ex_count'])*($v['ex_count']*1-1);
                            
                            $db->query("INSERT INTO " . $ecs->table('account_log') . "(user_id, rank_points, pay_points, change_time, change_desc, change_type) " . "VALUES ('".$v['user_id']."', '".$zjfh_gwb."', '" . $zjfh_gwb . "', " . gmtime() . ", '抽奖订单 ".$v['order_sn']." 赠送的购物币', '99')");
                            $log = $db->getRow("SELECT SUM(rank_points) AS rank_points, SUM(pay_points) AS pay_points FROM " . $ecs->table("account_log") . " WHERE user_id = '$v[user_id]'");
                            $db->query("UPDATE " . $ecs->table('users') . " SET rank_points = '" . $log['rank_points'] . "', pay_points = '" . $log['pay_points'] . "' WHERE user_id = '$v[user_id]'");

                        }




                    }
                }
            }
            //未中奖的，改变状态，把现金转成购物币，添加在acount_log表和更新用户的积分（购物币就是积分）
            foreach ($res as $k => $v) {
                if ($v['order_id'] != $_SESSION['order_id']) {
                    $sql = 'UPDATE ' . $ecs->table('order_info') . " SET `order_status`= 3 , `is_lucky`= -1 WHERE `order_id` != $_SESSION[order_id] AND `order_id`='" . $v['order_id'] . "'";
                    $db->query($sql);
                    //wenjun start
                    $db->query("INSERT INTO " . $ecs->table('account_log') . "(user_id, rank_points, pay_points, change_time, change_desc, change_type) " . "VALUES ('".$v['user_id']."', '".$v['goods_amount']."', '" . $v['goods_amount'] . "', " . gmtime() . ", '抽奖订单 ".$v['order_sn']." 赠送的购物币', '99')");
                    //wenjun end
                    $log = $db->getRow("SELECT SUM(rank_points) AS rank_points, SUM(pay_points) AS pay_points FROM " . $ecs->table("account_log") . " WHERE user_id = '$v[user_id]'");
                    $db->query("UPDATE " . $ecs->table('users') . " SET rank_points = '" . $log['rank_points'] . "', pay_points = '" . $log['pay_points'] . "' WHERE user_id = '$v[user_id]'");
                }
            }

            // wenjun 2018.01.31 店主分成
            draw_reward($_POST['goods_id']);

        }

    }else{
        $pd = 'SELECT exchange_lucky,closing_index FROM ' .$GLOBALS['ecs']->table('exchange_goods'). ' WHERE goods_id = '.$_POST['goods_id'];
        $wenjun = $GLOBALS['db']->getRow($pd);
        $exchange_lucky = $wenjun['exchange_lucky'];
        $closing_index  = $wenjun['closing_index'];
        // $exchange_lucky = 0;
    }
    if ($_POST['is_best'] == 1) {
        $o = 'UPDATE ' . $ecs->table('exchange_goods') . " SET `is_best`= 0 ";
        $db->query($o);
    }
    if ($exc->edit("exchange_lucky='$exchange_lucky', is_exchange='$is_exchange',is_hot='$_POST[is_hot]',is_best='$_POST[is_best]', closing_index='$_POST[closing_index]' ", $_POST['goods_id']))
    {
        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'exchange_goods.php?act=list&' . list_link_postfix();

        admin_log($_POST['goods_id'], 'edit', 'exchange_goods');

        clear_cache_files();
        sys_msg($_LANG['articleedit_succeed'], 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 编辑使用积分值
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_exchange_integral')
{
    check_authz_json('exchange_goods');

    $id                = intval($_POST['id']);
    $exchange_integral = floatval($_POST['val']);

    /* 检查文章标题是否重复 */
    if ($exchange_integral < 0 || $exchange_integral == 0 && $_POST['val'] != "$goods_price")
    {
        make_json_error($_LANG['exchange_integral_invalid']);
    }
    else
    {
        if ($exc->edit("exchange_integral = '$exchange_integral'", $id))
        {
            clear_cache_files();
            admin_log($id, 'edit', 'exchange_goods');
            make_json_result(stripslashes($exchange_integral));
        }
        else
        {
            make_json_error($db->error());
        }
    }
}

/*------------------------------------------------------ */
//-- 切换是否兑换
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_exchange')
{
    check_authz_json('exchange_goods');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_exchange = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 切换是否兑换
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_hot')
{
    check_authz_json('exchange_goods');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_hot = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 切换是否推荐
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_best')
{
    check_authz_json('exchange_goods');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);
    //2018 wenjun start 只能存在一个推荐

    if ($val == 1) {
        $o = 'UPDATE ' . $ecs->table('exchange_goods') . " SET `is_best`= 0 ";
        $db->query($o);
    }

    //2018 wenjun end
    $exc->edit("is_best = '$val'", $id);
    clear_cache_files();

    make_json_result($val);

}

/*------------------------------------------------------ */
//-- 批量删除商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch_remove')
{
    admin_priv('exchange_goods');

    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
    {
        sys_msg($_LANG['no_select_goods'], 1);
    }

    $count = 0;
    foreach ($_POST['checkboxes'] AS $key => $id)
    {
        if ($exc->drop($id))
        {
            admin_log($id,'remove','exchange_goods');
            $count++;
        }
    }

    $lnk[] = array('text' => $_LANG['back_list'], 'href' => 'exchange_goods.php?act=list');
    sys_msg(sprintf($_LANG['batch_remove_succeed'], $count), 0, $lnk);
}

/*------------------------------------------------------ */
//-- 删除商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('exchange_goods');

    $id = intval($_GET['id']);
    if ($exc->drop($id))
    {
        admin_log($id,'remove','article');
        clear_cache_files();
    }

    $url = 'exchange_goods.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 搜索商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'search_goods')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    $filters = $json->decode($_GET['JSON']);

    $arr = get_goods_list($filters);

    make_json_result($arr);
}

//商品价格
elseif ($_REQUEST['act'] == 'exchange_goods_price')
{

    $id = $_POST['id'];
    $sql = 'SELECT shop_price FROM ' .$GLOBALS['ecs']->table('goods'). ' WHERE goods_id = '.$id;
    $res = $GLOBALS['db']->getOne($sql);

    if ($res !='0.00') {
        $exchange_goods_price = round($res * 0.05,1);
        echo $exchange_goods_price;exit;
    }else{
        echo "no";exit;
    }
}






/* 获得商品列表 */
function get_exchange_goodslist()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'eg.goods_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

//        $where = '';
        $where = ' AND g.is_delete = 0 AND g.is_on_sale = 1 ';

        if (!empty($filter['keyword']))
        {
            $where = " AND g.goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        /* 商品总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('exchange_goods'). ' AS eg '.
            'LEFT JOIN ' .$GLOBALS['ecs']->table('goods'). ' AS g ON g.goods_id = eg.goods_id '.
            'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取商品数据 */
        $sql = 'SELECT eg.* , g.goods_name '.
            'FROM ' .$GLOBALS['ecs']->table('exchange_goods'). ' AS eg '.
            'LEFT JOIN ' .$GLOBALS['ecs']->table('goods'). ' AS g ON g.goods_id = eg.goods_id '.
            'WHERE 1 ' .$where. ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $arr = array();
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
    // print_r($sql);exit;
    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}


?>