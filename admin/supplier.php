<?php

/**
 * ECSHOP 管理中心供货商管理
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.hunuo.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/supplier.php');
$smarty->assign('lang', $_LANG);


/*------------------------------------------------------ */
//-- 供货商列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 检查权限 */
    admin_priv('supplier_manage');

    /* 查询 */
    $result = suppliers_list();

    /* 模板赋值 */
    $ur_here_lang = $_REQUEST['status'] =='1' ? $_LANG['supplier_list'] : $_LANG['supplier_reg_list'];
    $smarty->assign('ur_here', $ur_here_lang); // 当前导航

    $smarty->assign('full_page',        1); // 翻页参数

    $smarty->assign('status',    $_REQUEST['status']);
    $smarty->assign('supplier_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('sort_suppliers_id', '<img src="images/sort_desc.gif">');
    $sql="select rank_id,rank_name from ". $ecs->table('supplier_rank') ." order by sort_order";
    $supplier_rank=$db->getAll($sql);
    $smarty->assign('supplier_rank', $supplier_rank);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('supplier_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('supplier_manage');

    $result = suppliers_list();

    $smarty->assign('supplier_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($result['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('supplier_list.htm'), '',
        array('filter' => $result['filter'], 'page_count' => $result['page_count']));
}


/*------------------------------------------------------ */
//-- 查看、编辑供货商
/*------------------------------------------------------ */
elseif ($_REQUEST['act']== 'edit')
{
    /* 检查权限 */
    admin_priv('supplier_manage');
    $suppliers = array();

    /* 取得供货商信息 */
    $id = $_REQUEST['id'];
    // 	 $status = intval($_REQUEST['status']);
    $sql = "SELECT * FROM " . $ecs->table('supplier') . " WHERE supplier_id = '$id'";
    $supplier = $db->getRow($sql);
    if (count($supplier) <= 0)
    {
        sys_msg('该供应商不存在！');
    }

    /* 省市县 */
    $supplier_country = $supplier['country'] ?  $supplier['country'] : $_CFG['shop_country'];
    $smarty->assign('country_list',       get_regions());
    $smarty->assign('province_list', get_regions(1, $supplier_country));
    $smarty->assign('city_list', get_regions(2, $supplier['province']));
    $smarty->assign('district_list', get_regions(3, $supplier['city']));
    $smarty->assign('supplier_country', $supplier_country);
    /* 供货商等级 */
    $sql="select rank_name from ". $ecs->table('supplier_rank') ." where rank_id = ".$supplier['rank_id'];
    $rank_name=$db->getOne($sql);
    $supplier['rank_name'] = $rank_name;
    // $sql="select rank_id,rank_name from ". $ecs->table('supplier_rank') ." order by sort_order";
    //$supplier_rank=$db->getAll($sql);
    //$smarty->assign('supplier_rank', $supplier_rank);

    /* 店铺类型 */
    $sql="select str_name from ". $ecs->table('street_category') ." where str_id = ".$supplier['type_id'];
    $type_name=$db->getOne($sql);
    $supplier['type_name'] = $type_name;

    $smarty->assign('ur_here', $_LANG['edit_supplier']);
    // 	 $lang_supplier_list = $status=='1' ? $_LANG['supplier_list'] :  $_LANG['supplier_reg_list'];
    //      $smarty->assign('action_link', array('href' => 'supplier.php?act=list', 'text' =>$lang_supplier_list ));
    if ($_REQUEST['status'] == '1')
    {
        $lang_supplier_list = $_LANG['supplier_list'];
        $smarty->assign('action_link', array('href' => 'supplier.php?act=list&status=1', 'text' =>$lang_supplier_list ));
    }
    else
    {
        $lang_supplier_list = $_LANG['supplier_reg_list'];
        $smarty->assign('action_link', array('href' => 'supplier.php?act=list', 'text' =>$lang_supplier_list ));
    }

    $smarty->assign('form_action', 'update');
    $smarty->assign('supplier', $supplier);
    // 商品等级
    $smarty->assign('rank_id', $supplier['rank_id']);
    $smarty->assign('supplier_rank_list', get_supplier_rank_list());

    // 所有拥有店铺的代理商
    $sql = "SELECT a.shop_id, a.shop_code, a.agent_id, b.user_id, b.user_name FROM " . $ecs->table('agent_shop') . " a LEFT JOIN " . $ecs->table('users') . " b ON a.agent_id = b.user_id WHERE a.chuzu_status = 0 AND b.user_id > 0 ORDER BY add_time DESC";
    $agent_shop_list = $db->getAll($sql);

    $smarty->assign('agent_shop_list', $agent_shop_list);

    assign_query_info();

    $smarty->display('supplier_info.htm');


}

/*------------------------------------------------------ */
//-- 查看供货商佣金日志
/*------------------------------------------------------ */
elseif ($_REQUEST['act']== 'view')
{
    /* 检查权限 */
    admin_priv('supplier_manage');

    /* 查询 */
    $result = rebate_log_list();

    /* 模板赋值 */
    $smarty->assign('ur_here', '佣金日志记录'); // 当前导航

    $smarty->assign('full_page',        1); // 翻页参数

    $smarty->assign('log_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('sort_suppliers_id', '<img src="images/sort_desc.gif">');

    /* 显示模板 */
    assign_query_info();
    $smarty->display('supplier_log_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query_log')
{
    check_authz_json('supplier_manage');

    $result = rebate_log_list();

    $smarty->assign('log_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    ;

    make_json_result($smarty->fetch('supplier_log_list.htm'), '',
        array('filter' => $result['filter'], 'page_count' => $result['page_count']));
}

/*------------------------------------------------------ */
//-- 提交添加、编辑供货商
/*------------------------------------------------------ */
elseif ($_REQUEST['act']=='update')
{
    /* 检查权限 */
    admin_priv('supplier_manage');

    //审核通过，必须要填写的项目
    //    if(intval($_POST['status']) == 1){
    //        if(intval($_POST['supplier_rebate_paytime'])<=0){
    //            sys_msg('结算类型必须选择！');
    //        }
    //    }

    /* 提交值 */
    $supplier_id =  intval($_POST['id']);
    $shop_id =  intval($_POST['shop_id']);
    $password =  $_POST['password'] ? trim($_POST['password']) : '123456';    //后台登录密码
    $status_url = intval($_POST['status_url']);
    $supplier = array(
        //'rank_id'   => intval($_POST['rank_id']),
        'country'   => intval($_POST['country']),
        'province'   => intval($_POST['province']),
        'city'   => intval($_POST['city']),
        'district'   => intval($_POST['district']),
        'address'   => trim($_POST['address']),
        //'tel'   => trim($_POST['tel']),
        'email'   => trim($_POST['email']),
        'id_card_no'   => trim($_POST['id_card_no']),
        //'contact_back'   => trim($_POST['contact_back']),
        //'contact_shop'   => trim($_POST['contact_shop']),
        //'contact_yunying'   => trim($_POST['contact_yunying']),
        //'contact_shouhou'   => trim($_POST['contact_shouhou']),
        //'contact_caiwu'   => trim($_POST['contact_caiwu']),
        //'contact_jishu'   => trim($_POST['contact_jishu']),

        'bank_account_name'	=>	trim($_POST['bank_account_name']),
        'bank_account_number'	=>	trim($_POST['bank_account_number']),
        'bank_name'	=>	trim($_POST['bank_name']),
        'bank_code'	=>	trim($_POST['bank_code']),
        'settlement_bank_account_name'	=>	trim($_POST['settlement_bank_account_name']),
        'settlement_bank_account_number'	=>	trim($_POST['settlement_bank_account_number']),
        'settlement_bank_name'	=>	trim($_POST['settlement_bank_name']),
        'settlement_bank_code'	=>	trim($_POST['settlement_bank_code']),
        'rank_id' => $_POST['rank_id'],

        'system_fee'   => trim($_POST['system_fee']),
        'supplier_bond'   => trim($_POST['supplier_bond']),
        'supplier_rebate'   => trim($_POST['supplier_rebate']),
        'supplier_rebate_paytime'   => intval($_POST['supplier_rebate_paytime']),
        'supplier_remark'   => trim($_POST['supplier_remark']),
        'status'   => intval($_POST['status'])
    );
    /* 取得供货商信息 */
    //$sql = "SELECT * FROM " . $ecs->table('supplier') . " WHERE supplier_id = '" . $supplier_id ."' ";
    $sql = "select s.supplier_id,s.add_time,s.status as supplier_status,s.supplier_name,s.tel,u.* from " . $ecs->table('supplier') . " as s left join ". $ecs->table('users') .
        " as u on s.user_id=u.user_id where s.supplier_id=".$supplier_id;
    $supplier_old = $db->getRow($sql);
    if (empty($supplier_old['supplier_id']))
    {
        sys_msg('该供货商信息不存在！');
    }
    // 申请用户不是店主才执行下面操作
    if(empty($supplier_old['shop_id'])){
        if ($supplier['status'] == 1) {
            // 店铺信息
            $sql = "SELECT * FROM " . $ecs->table('agent_shop') . " WHERE shop_id = " . $shop_id;
            $shop_info = $db->getRow($sql);
            if (empty($shop_info['shop_id'])) {
                sys_msg('代理商店铺不存在！');
            }

            // 首次审核通过操作
            if ($supplier['status'] != $supplier_old['supplier_status']) {
                $supplier['add_time'] = time();
                $supplier['shop_id'] = $shop_info['shop_id'];   //店铺ID
                $supplier['shop_code'] = $shop_info['shop_code'];   //店铺编号
                $supplier['agent_id'] = $shop_info['agent_id'];   //代理商ID

                // 更新用户店铺ID，并设置该用户为审核通过
                $sql = "UPDATE " . $ecs->table('users') . " SET `status`='1', `shop_id`=" . $shop_id . ", `parent_id`=" . $shop_info['1417'] . " WHERE `user_id`='" . $supplier_old['user_id'] . "'";
                $db->query($sql);

                // 设置店铺为已转出已激活状态
                $end_time = local_strtotime('+3 month');    //到期时间
                $sql = "UPDATE " . $ecs->table('agent_shop') . " SET `user_id`= " . $supplier_old['user_id'] . ", `chuzu_status` = 2, `chuzu_time` = '" . gmtime() . "', `jh_time` = '" . gmtime() . "', `end_time` = '" . $end_time . "' WHERE `shop_id`= " . $shop_id;
                $db->query($sql);

                // 为每一个创建店铺的商家创建基本信息的保存记录，如果之前没有创建过
                create_shop_settiongs($shop_id);

                // 设置店铺名称
                $sql = "UPDATE " . $ecs->table('supplier_shop_config') . " SET `value` = '" . $supplier_old['supplier_name'] . "' WHERE code = 'shop_name' and `supplier_id` = " . $shop_id;
                $db->query($sql);

                // 添加到店铺街（前台显示店铺列表）
                $ssdata['supplier_id'] = $shop_id;
                $ssdata['supplier_name'] = $supplier_old['supplier_name'];
                $ssdata['supplier_title'] = $supplier_old['supplier_name'];
                $ssdata['supplier_desc'] = $supplier_old['supplier_name'];
                $ssdata['addtime'] = gmtime();
                $ssdata['supplier_type'] = 0;
                $ssdata['supplier_tags'] = '';
                $ssdata['is_show'] = 1; //默认已经激活显示状态
                $ssdata['status'] = 1;  //默认已经激活显示状态

                $sql = "INSERT INTO " .$ecs->table('supplier_street'). " (supplier_id,supplier_type,supplier_name,supplier_title,supplier_desc,supplier_tags,add_time,is_show,status)VALUES ".
                       "(".$ssdata['supplier_id'].",".$ssdata['supplier_type'].",'".$ssdata['supplier_name']."','".$ssdata['supplier_title']."','".$ssdata['supplier_desc']."','".$ssdata['supplier_tags']."','".$ssdata['addtime']."',".$ssdata['is_show'].",".$ssdata['is_show'].")";
                $db->query($sql);

                /* 保存供货商信息 */
                $db->autoExecute($ecs->table('supplier'), $supplier, 'UPDATE', "supplier_id = '" . $supplier_id . "'");

                // 修改supplier_id为shop_id
                $sql = "UPDATE " . $ecs->table('supplier') . " SET `supplier_id` = '" . $shop_id . "' WHERE `supplier_id` = " . $supplier_id;
                $db->query($sql);

                //更新相关店铺的管理员状态
                $sql = "select * from ". $ecs->table('supplier_admin_user') ." where supplier_id=".$supplier_old['supplier_id'];
                $info = $db->getAll($sql);
                if(count($info)>0){
                    $sql = "UPDATE ". $ecs->table('supplier_admin_user') ." SET user_name = '".$supplier_old['user_name']."',password = '".md5($password)."', pwd='".$password."', email='".$supplier_old['email']."', checked = ".intval($_POST['status'])." WHERE supplier_id=".$shop_id." and uid=".$supplier_old['user_id'];
                    $db->query($sql);
                }else{
                    $insql = "INSERT INTO " . $ecs->table('supplier_admin_user') . " (`uid`, `user_name`, `email`, `password`, `add_time`, `last_login`, `last_ip`, `action_list`, `nav_list`, `lang_type`, `agency_id`, `supplier_id`,`pwd`,`todolist`, `role_id`, `checked`) ".
                        "VALUES(".$supplier_old['user_id'].", '".$supplier_old['user_name']."', '".$supplier_old['email']."', '".md5($password)."', ".$supplier_old['last_login'].", ".$supplier_old['last_login'].", '".$supplier_old['last_ip']."', 'all', '', '', 0, ".$shop_id.", ".$password.", NULL, NULL, ".intval($_POST['status']).")";
                    $db->query($insql);
                }

                //发送短信提示
                include_once('../send.php');
                $send_sms = sendSMS($supplier_old['tel'],$_LANG['ok_sms_tishi']);
            }
        } else {
            /* 保存供货商信息 */
            $db->autoExecute($ecs->table('supplier'), $supplier, 'UPDATE', "supplier_id = '" . $supplier_id . "'");
        }
    } else {
        // sys_msg('该申请的用户已经是店主！');
        /* 保存供货商信息 */
        $db->autoExecute($ecs->table('supplier'), $supplier, 'UPDATE', "supplier_id = '" . $supplier_id . "'");
    }

    /* 清除缓存 */
    clear_cache_files();

    /* 提示信息 */
    $links[] = array('href' => ($status_url >0 ? 'supplier.php?act=list&status=1' : 'supplier.php?act=list'), 'text' => ($status_url >0 ? $_LANG['back_supplier_list'] : $_LANG['back_supplier_reg']));
    sys_msg($_LANG['edit_supplier_ok'], 0, $links);

}

//删除店铺信息
elseif ($_REQUEST['act'] == 'delete'){
    /* 检查权限 */
    admin_priv('supplier_manage');
    $supplier_id =  intval($_GET['id']);

    $sql = "SELECT * FROM " . $ecs->table('supplier') . " WHERE supplier_id = ".$supplier_id;
    $supplier = $db->getRow($sql);
    if (count($supplier) <= 0)
    {
        sys_msg('该供应商不存在！');
    }


    if($supplier_id > 0){
        $ret = array();
        //入驻商相关删除信息
        $supplier_info = array(
            'delete FROM '.$ecs->table('supplier_admin_user').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_article').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_category').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_cat_recommend').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_goods_cat').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_guanzhu').' WHERE supplierid = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_money_log').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_nav').' WHERE supplier_id = '.$supplier_id,
//            'delete FROM '.$ecs->table('supplier_rebate_log').' WHERE rebateid in (SELECT rebate_id FROM '.$ecs->table('supplier_rebate').' WHERE supplier_id ='.$supplier_id.')',
            'delete FROM '.$ecs->table('supplier_shop_config').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_street').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_tag_map').' WHERE supplier_id = '.$supplier_id
        );
//        if($db->getOne('SELECT COUNT(*) FROM ' . $ecs->table('supplier_rebate') . ' WHERE supplier_id = ' . $supplier_id))
//        {
//            array_push($supplier_info, 'delete FROM '.$ecs->table('supplier_rebate_log').' WHERE rebateid in (SELECT rebate_id FROM '.$ecs->table('supplier_rebate').' WHERE supplier_id ='.$supplier_id.')');
//        }
        foreach($supplier_info as $sk=>$sv){
            if($db->query($sv)){}else{
                $ret[] = $sv;
            }
        }
        delete_supplier_pic($supplier_id);
        //商品相关删除信息
        $goods_info = array(
            'delete FROM '.$ecs->table('goods_activity').' WHERE goods_id in (SELECT goods_id FROM '.$ecs->table('goods').' WHERE supplier_id ='.$supplier_id.')',
            'delete FROM '.$ecs->table('goods_attr').' WHERE goods_id in (SELECT goods_id FROM '.$ecs->table('goods').' WHERE supplier_id ='.$supplier_id.')',
            'delete FROM '.$ecs->table('goods_cat').' WHERE goods_id in (SELECT goods_id FROM '.$ecs->table('goods').' WHERE supplier_id ='.$supplier_id.')',
            'delete FROM '.$ecs->table('goods_gallery').' WHERE goods_id in (SELECT goods_id FROM '.$ecs->table('goods').' WHERE supplier_id ='.$supplier_id.')',
            'delete FROM '.$ecs->table('goods_tag').' WHERE goods_id in (SELECT goods_id FROM '.$ecs->table('goods').' WHERE supplier_id ='.$supplier_id.')',
            'delete FROM '.$ecs->table('products').' WHERE goods_id in (SELECT goods_id FROM '.$ecs->table('goods').' WHERE supplier_id ='.$supplier_id.')'
        );
        foreach($goods_info as $gk=>$gv){
            if($db->query($gv)){}else{
                $ret[] = $gv;
            }
        }

        // 更新相关
        $update_info = array(
            'update ' . $ecs->table('users') . ' set shop_id = 0 WHERE shop_id = ' . $supplier_id,
            'update ' . $ecs->table('agent_shop') . ' set user_id = 0, chuzu_status = 0, chuzu_time = 0, jh_time = 0 WHERE shop_id = ' . $supplier_id
        );
        foreach($update_info as $uk=>$uv){
            if($db->query($uv)){}else{
                $ret[] = $uv;
            }
        }

        //最后删除中间表信息
        $other_info = array(
            'delete FROM '.$ecs->table('goods').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier').' WHERE supplier_id = '.$supplier_id
//            'delete FROM '.$ecs->table('supplier_rebate').' WHERE supplier_id = '.$supplier_id
        );
        foreach($other_info as $ok=>$ov){
            if($db->query($ov)){}else{
                $ret[] = $ov;
            }
        }

    }
    if(count($ret)>0){
        echo "如下删除语句执行失败:";
        echo "<pre>";
        print_r($ret);
        sleep(10);
    }

    /* 提示信息 */
    $links[0] = array('href' => 'supplier.php?act=list&status='.$supplier['status'], 'text' =>'返回上一页');
    sys_msg('删除成功!',0,$links);
}

/*------------------------------------------------------ */
//-- 批量导出供货商
/*------------------------------------------------------ */
elseif ($_REQUEST['act']=='export')
{
    $where = " WHERE s.applynum = 3 AND s.status = 1 ";

    // 入驻商名称
    if (isset($_REQUEST['supplier_name']) && !empty($_REQUEST['supplier_name']))
    {
        $where .= " AND s.supplier_name LIKE '%" . mysql_like_quote($_REQUEST['supplier_name']) . "%'";
    }
    // 入驻商等级
    if (isset($_REQUEST['rank_id']) && !empty($_REQUEST['rank_id']))
    {
        $where .= " AND s.rank_id = " . $_REQUEST['rank_id'];
    }

    /* 查询 */
    $sql = "SELECT "
        . "u.user_name, " // 会员名称
        . "s.supplier_name, " // 入驻商名称
        . "s.rank_id, " // 入驻商等级
        . "s.tel, " // 公司电话
        . "s.system_fee, " // 平台使用费
        . "s.supplier_bond, " // 商家保证金
        . "s.supplier_rebate, " // 分成利率
        . "s.supplier_remark, " // 入驻商备注
        . "s.status " // 状态
        . "FROM "
        . $GLOBALS['ecs']->table("supplier") . " AS s LEFT JOIN "
        . $GLOBALS['ecs']->table("users") . " AS u ON s.user_id = u.user_id "
        . $where;

    $res = $GLOBALS['db']->getAll($sql);

    // 引入phpexcel核心类文件
    require_once ROOT_PATH . '/includes/phpexcel/Classes/PHPExcel.php';
    // 实例化excel类
    $objPHPExcel = new PHPExcel();
    // 操作第一个工作表
    $objPHPExcel->setActiveSheetIndex(0);
    // 设置sheet名
    $objPHPExcel->getActiveSheet()->setTitle('入驻商列表');
    // 设置表格宽度
    $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
    $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
    $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
    // 列名表头文字加粗
    $objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getFont()->setBold(true);
    // 列表头文字居中
    $objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getAlignment()
        ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    // 列名赋值
    $objPHPExcel->getActiveSheet()->setCellValue('A1', '会员名称');
    $objPHPExcel->getActiveSheet()->setCellValue('B1', '入驻商名称');
    $objPHPExcel->getActiveSheet()->setCellValue('C1', '入驻商等级');
    $objPHPExcel->getActiveSheet()->setCellValue('D1', '公司电话');
    $objPHPExcel->getActiveSheet()->setCellValue('E1', '平台使用费');
    $objPHPExcel->getActiveSheet()->setCellValue('F1', '商家保证金');
    $objPHPExcel->getActiveSheet()->setCellValue('G1', '分成利率');
    $objPHPExcel->getActiveSheet()->setCellValue('H1', '入驻商备注');
    $objPHPExcel->getActiveSheet()->setCellValue('I1', '状态');

    // 数据起始行
    $row_num = 2;
    // 向每行单元格插入数据
    foreach($res as $value)
    {
        // 入驻商等级
        switch ($value['rank_id'])
        {
            case 1:
                $rank_name = '初级店铺';
                break;
            case 2:
                $rank_name = '中级店铺';
                break;
            case 3:
                $rank_name = '高级店铺';
                break;
            default:
                $rank_name = '';
        }

        // 设置所有垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A' . $row_num . ':' . 'I' . $row_num)->getAlignment()
            ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        // 设置平台使用费和商家保证金为数字格式
        $objPHPExcel->getActiveSheet()->getStyle('E' . $row_num . ':' . 'F' . $row_num)->getNumberFormat()
            ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        // 设置分成利率为数字格式
        $objPHPExcel->getActiveSheet()->getStyle('G' . $row_num)->getNumberFormat()
            ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);

        // 设置单元格数值
        $objPHPExcel->getActiveSheet()->setCellValueExplicit('A' . $row_num, $value['user_name'], PHPExcel_Cell_DataType::TYPE_STRING);
        $objPHPExcel->getActiveSheet()->setCellValue('B' . $row_num, $value['supplier_name']);
        $objPHPExcel->getActiveSheet()->setCellValue('C' . $row_num, $rank_name);
        $objPHPExcel->getActiveSheet()->setCellValueExplicit('D' . $row_num, $value['tel'], PHPExcel_Cell_DataType::TYPE_STRING);
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $row_num, $value['system_fee']);
        $objPHPExcel->getActiveSheet()->setCellValue('F' . $row_num, $value['supplier_bond']);
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $row_num, $value['supplier_rebate']);
        $objPHPExcel->getActiveSheet()->setCellValue('H' . $row_num, $value['supplier_remark']);
        $objPHPExcel->getActiveSheet()->setCellValue('I' . $row_num, $value['status'] ? '通过' : '');
        $row_num++;
    }
    $outputFileName = '入驻商_' . time() . '.xls';
    $xlsWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header('Content-Disposition:inline;filename="' . $outputFileName . '"');
    header("Content-Transfer-Encoding: binary");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: no-cache");
    $xlsWriter->save("php://output");
    echo file_get_contents($outputFileName);
}

/**
 *  获取供应商列表信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function suppliers_list()
{
    $result = get_filter();
    if ($result === false)
    {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        /* 过滤信息 */
        $filter['supplier_name'] = empty($_REQUEST['supplier_name']) ? '' : trim($_REQUEST['supplier_name']);
        $filter['rank_name'] = empty($_REQUEST['rank_name']) ? '' : trim($_REQUEST['rank_name']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'supplier_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);
        $filter['status'] = empty($_REQUEST['status']) ? '0' : intval($_REQUEST['status']);

        $where = 'WHERE applynum = 3 ';
        $where .= $filter['status'] ? " AND s.status = '". $filter['status']. "' " : " AND s.status in('0','-1') ";
        if ($filter['supplier_name'])
        {
            $where .= " AND supplier_name LIKE '%" . mysql_like_quote($filter['supplier_name']) . "%'";
        }
        if ($filter['rank_name'])
        {
            $where .= " AND rank_id = '$filter[rank_name]'";
        }

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
        {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        }
        elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
        {
            $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
        }
        else
        {
            $filter['page_size'] = 15;
        }

        /* 记录总数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('supplier') ." as s ". $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT s.supplier_id, u.user_name, sau.user_name as supplier_user_name, sau.pwd, s.rank_id, s.supplier_name, s.tel, s.system_fee, s.supplier_bond, s.supplier_rebate, s.supplier_remark, s.add_time,  ".
            "s.status ".
            "FROM " . $GLOBALS['ecs']->table("supplier") . " as s left join " . $GLOBALS['ecs']->table("users") . " as u on s.user_id = u.user_id left join " . $GLOBALS['ecs']->table("supplier_admin_user") . " as sau on s.supplier_id = sau.supplier_id
                $where
                ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order']. "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";

        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $rankname_list =array();
    $sql2 = "select * from ". $GLOBALS['ecs']->table("supplier_rank") ;
    $res2 = $GLOBALS['db']->query($sql2);
    while ($row2=$GLOBALS['db']->fetchRow($res2))
    {
        $rankname_list[$row2['rank_id']] = $row2['rank_name'];
    }

    $list=array();
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $row['add_time'] =  local_date("Y-m-d H:i:s",$row['add_time']);
        $row['rank_name'] = $rankname_list[$row['rank_id']];
        $row['status_name'] = $row['status']=='1' ? '通过' : ($row['status']=='0' ? "未审核" : "未通过");
        $open = $GLOBALS['db']->getRow("select value from ".$GLOBALS['ecs']->table("supplier_shop_config")." where supplier_id=".$row['supplier_id']." and code='shop_closed'");
        if($open && $open['value'] == 0){
            $row['open'] = 1;
        }else{
            $row['open'] = 0;
        }
        $list[]=$row;
    }

    $arr = array('result' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
/*
* 入驻商的佣金记录
*/
function rebate_log_list()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 过滤信息 */
        $filter['id'] = intval($_REQUEST['id']);
        $filter['addtime_start'] = !empty($_REQUEST['addtime_start']) ? local_strtotime($_REQUEST['addtime_start']) : 0;
        $filter['addtime_end'] = !empty($_REQUEST['addtime_end']) ? local_strtotime($_REQUEST['addtime_end']." 23:59:59") : 0;
        $filter['status'] = empty($_REQUEST['status']) ? '0' : intval($_REQUEST['status']);

        $where = ' WHERE supplier_id = '.$filter['id'];
        $where .= $filter['addtime_start'] ? " AND addtime >= '". $filter['addtime_start']. "' " :  " ";
        $where .= $filter['addtime_end'] ? " AND addtime_end <= '". $filter['addtime_end']. "' " :  " ";
        $where .= $filter['status']>0 ? " AND status = '". $filter['status']. "' " : "";

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
        {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        }
        elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
        {
            $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
        }
        else
        {
            $filter['page_size'] = 15;
        }

        /* 记录总数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('supplier_money_log') . $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT * ".
            "FROM " . $GLOBALS['ecs']->table("supplier_money_log") . $where ."
                ORDER BY addtime desc
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";

        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $list=array();
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $row['add_time'] = local_date("Y-m-d H:i:s",$row['addtime']);
        $list[]=$row;
    }

    $arr = array('result' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/*
删除店铺下所有商品的上传的图片
*/
function delete_supplier_pic($suppid){
    global $db,$ecs;

    $sql = "select goods_thumb,goods_img,original_img from ".$ecs->table('goods')." where supplier_id=".$suppid;

    $query = $db->query($sql);
    while($row = $db->fetchRow($query)){
        @unlink(ROOT_PATH.$row['goods_thumb']);
        @unlink(ROOT_PATH.$row['goods_img']);
        @unlink(ROOT_PATH.$row['original_img']);
    }

    $sql = "select gg.img_url,gg.thumb_url,gg.img_original from ".$ecs->table('goods_gallery')." as gg,".$ecs->table('goods')." as g where g.supplier_id=".$suppid." and g.goods_id=gg.goods_id";

    $query = $db->query($sql);
    while($row = $db->fetchRow($query)){
        @unlink(ROOT_PATH.$row['img_url']);
        @unlink(ROOT_PATH.$row['thumb_url']);
        @unlink(ROOT_PATH.$row['img_original']);
    }
}

/**
 * 取得店铺等级列表
 * @return array 店铺等级列表 id => name
 */
function get_supplier_rank_list()
{
    $sql = 'SELECT rank_id, rank_name FROM ' . $GLOBALS['ecs']->table('supplier_rank') . ' ORDER BY sort_order';
    $res = $GLOBALS['db']->getAll($sql);

    $rank_list = array();
    foreach ($res AS $row)
    {
        $rank_list[$row['rank_id']] = addslashes($row['rank_name']);
    }

    return $rank_list;
}

/**
* 为每一个创建店铺的商家创建基本信息的保存记录，如果之前没有创建过
*
*/
function create_shop_settiongs($supplier_id)
{
    global $db, $ecs, $_LANG;

    if(!isset($supplier_id) || intval($supplier_id)<=0){
        return;
    }

    $sql = "SELECT count(id) FROM " . $ecs->table('supplier_shop_config') ." WHERE supplier_id=".$supplier_id;
    $num = $db->getOne($sql);
    if($num>0){
        return;
    }else{
        $insql = "INSERT INTO ". $ecs->table('supplier_shop_config') ." (`id`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `supplier_id`) VALUES
                (1, 0, 'shop_info', 'group', '', '', '', 1, ".$supplier_id."),
                (2, 0, 'hidden', 'hidden', '', '', '', 1, ".$supplier_id."),
                (8, 0, 'sms', 'group', '', '', '', 1, ".$supplier_id."),
                (101, 1, 'shop_name', 'text', '', '', '商家店铺名称', 1, ".$supplier_id."),
                (102, 1, 'shop_title', 'text', '', '', '商家店铺标题', 1, ".$supplier_id."),
                (103, 1, 'shop_desc', 'hidden', '', '', '商家店铺描述', 1, ".$supplier_id."),
                (104, 1, 'shop_keywords', 'text', '', '', '商家店铺关键字', 1, ".$supplier_id."),
                (105, 1, 'shop_country', 'manual', '', '', '1', 1, ".$supplier_id."),
                (106, 1, 'shop_province', 'manual', '', '', '0', 1, ".$supplier_id."),
                (107, 1, 'shop_city', 'manual', '', '', '0', 1, ".$supplier_id."),
                (108, 1, 'shop_address', 'text', '', '', '', 1, ".$supplier_id."),
                (109, 1, 'qq', 'text', '', '', '', 1, ".$supplier_id."),
                (110, 1, 'ww', 'text', '', '', '', 1, ".$supplier_id."),
                (111, 1, 'skype', 'hidden', '', '', '', 1, ".$supplier_id."),
                (112, 1, 'ym', 'hidden', '', '', '', 1, ".$supplier_id."),
                (113, 1, 'msn', 'hidden', '', '', '', 1, ".$supplier_id."),
                (114, 1, 'service_email', 'text', '', '', '', 1, ".$supplier_id."),
                (115, 1, 'service_phone', 'text', '', '', '', 1, ".$supplier_id."),
                (116, 1, 'shop_closed', 'select', '0,1', '', '0', 1, ".$supplier_id."),
                (117, 1, 'close_comment', 'textarea', '', '', '该店铺正在装修', 1, ".$supplier_id."),
                (118, 1, 'shop_logo', 'file', '', '../themes/".'{$template}'."/images/', '', 1, ".$supplier_id."),
                (119, 1, 'licensed', 'hidden', '0,1', '', '1', 1, ".$supplier_id."),
                (120, 1, 'user_notice', 'hidden', '', '', '用户中心公告！', 1, ".$supplier_id."),
                (121, 1, 'shop_notice', 'textarea', '', '', '商家店铺介绍:欢迎光临手机网,我们的宗旨：诚信经营、服务客户！\r\n<MARQUEE onmouseover=this.stop() onmouseout=this.start() \r\nscrollAmount=3><U><FONT color=red>\r\n<P>咨询电话010-10124444  010-21252454 8465544</P></FONT></U></MARQUEE>', 1, ".$supplier_id."),
                (122, 1, 'shop_reg_closed', 'hidden', '1,0', '', '0', 1, ".$supplier_id."),
                (123, 1, 'shop_index_num', 'textarea', '', '', '8\r\n6\r\n4', 1, ".$supplier_id."),
                (124, 1, 'shop_search_price', 'textarea', '', '', '0-1000元\r\n1000-2000元\r\n2000-4000元', 1, ".$supplier_id."),
                (201, 2, 'shop_header_color', 'hidden', '', '', '#E4368F', 1, ".$supplier_id."),
                (202, 2, 'shop_header_text', 'hidden', '', '', '请上传logo和banner', 1, ".$supplier_id."),
                (203, 2, 'template', 'hidden', '', '', 'dianpu1', 1, ".$supplier_id."),
                (204, 2, 'stylename', 'hidden', '', '', '', 1, ".$supplier_id."),
                (205, 2, 'flash_theme', 'hidden', '', '', '".$_SESSION['supplier_name'].$supplier_id."', 1, ".$supplier_id."),
                (801, 8, 'sms_shop_mobile', 'text', '', '', '', 1, ".$supplier_id."),
                (802, 8, 'sms_order_placed', 'select', '1,0', '', '0', 0, ".$supplier_id."),
                (803, 8, 'sms_order_payed', 'select', '1,0', '', '0', 1, ".$supplier_id."),
                (804, 8, 'sms_order_shipped', 'select', '1,0', '', '0', 1, ".$supplier_id.");";
        if($db->query($insql) === false){
            return false;
        }
        return true;
    }
}
?>