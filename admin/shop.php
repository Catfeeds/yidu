<?php



define('IN_ECS', true);



require (dirname(__FILE__) . '/includes/init.php');

include_once (ROOT_PATH . '/includes/cls_image.php');

$image = new cls_image($_CFG['bgcolor']);



$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'list';



/* 路由 */



$function_name = 'action_' . $action;



if(! function_exists($function_name))

{

	$function_name = "action_list";

}



call_user_func($function_name);



/* 路由 */



/**

 * 商品申请列表

 */

function action_apply_list ()

{

	// 全局变量

	$user = $GLOBALS['user'];

	$_CFG = $GLOBALS['_CFG'];

	$_LANG = $GLOBALS['_LANG'];

	$smarty = $GLOBALS['smarty'];

	$db = $GLOBALS['db'];

	$ecs = $GLOBALS['ecs'];

	$user_id = $_SESSION['user_id'];



	/* 检查权限 */

	admin_priv('shop_goods_apply_list');



	$smarty->assign('ur_here', '商品申请列表');



	$shop_goods_apply_list = shop_goods_apply_list();

	$smarty->assign('shop_goods_apply_list', $shop_goods_apply_list['shop_goods_apply_list']);

	$smarty->assign('filter', $user_list['filter']);

	$smarty->assign('record_count', $shop_goods_apply_list['record_count']);

	$smarty->assign('page_count', $shop_goods_apply_list['page_count']);



	assign_query_info();

	$smarty->display('shop_goods_apply_list.htm');

}



/**

 * ajax返回商品申请列表

 */

function action_query ()

{

	// 全局变量

	$user = $GLOBALS['user'];

	$_CFG = $GLOBALS['_CFG'];

	$_LANG = $GLOBALS['_LANG'];

	$smarty = $GLOBALS['smarty'];

	$db = $GLOBALS['db'];

	$ecs = $GLOBALS['ecs'];

	$user_id = $_SESSION['user_id'];



	$shop_goods_apply_list = shop_goods_apply_list();



	$smarty->assign('shop_goods_apply_list', $shop_goods_apply_list['shop_goods_apply_list']);

	$smarty->assign('filter', $shop_goods_apply_list['filter']);

	$smarty->assign('record_count', $shop_goods_apply_list['record_count']);

	$smarty->assign('page_count', $shop_goods_apply_list['page_count']);



	$sort_flag = sort_flag($shop_goods_apply_list['filter']);

	$smarty->assign($sort_flag['tag'], $sort_flag['img']);



	make_json_result($smarty->fetch('shop_goods_apply_list.htm'), '', array(

		'filter' => $shop_goods_apply_list['filter'],'page_count' => $shop_goods_apply_list['page_count']

	));

}



/**

 * 删除商品申请记录

 */

function action_remove ()

{

	// 全局变量

	$user = $GLOBALS['user'];

	$_CFG = $GLOBALS['_CFG'];

	$_LANG = $GLOBALS['_LANG'];

	$smarty = $GLOBALS['smarty'];

	$db = $GLOBALS['db'];

	$ecs = $GLOBALS['ecs'];

	$user_id = $_SESSION['user_id'];



	/* 检查权限 */

	admin_priv('shop_goods_apply_list');



	$sql = "DELETE FROM " . $ecs->table('shop_goods_apply') . " WHERE apply_id = " . $_GET['apply_id'];

	$db->query($sql);

	/* 提示信息 */

	$link[] = array(

	    'text' => $_LANG['go_back'], 'href' => 'shop.php?act=apply_list'

	);



	sys_msg('删除成功', 0, $link);

}



/**

 * 批量删除山炮申请记录

 */

function action_batch_remove ()

{

	// 全局变量

	$user = $GLOBALS['user'];

	$_CFG = $GLOBALS['_CFG'];

	$_LANG = $GLOBALS['_LANG'];

	$smarty = $GLOBALS['smarty'];

	$db = $GLOBALS['db'];

	$ecs = $GLOBALS['ecs'];

	$user_id = $_SESSION['user_id'];



	/* 检查权限 */

	admin_priv('shop_goods_apply_list');



	if(isset($_POST['checkboxes'])) {

	    $sql = "SELECT apply_id FROM " . $ecs->table('shop_goods_apply') . " WHERE apply_id " . db_create_in($_POST['checkboxes']);

	    $col = $db->getCol($sql);

	    $remove_list = implode(',', addslashes_deep($col));

	    $count = count($col);

	    $sql = "DELETE FROM " . $ecs->table('shop_goods_apply') . " WHERE apply_id IN(" . $remove_list . ")";

	    $db->query($sql);

	    $lnk[] = array(

	        'text' => $_LANG['go_back'], 'href' => 'shop.php?act=apply_list'

	    );

	    sys_msg(sprintf('删除成功', $count), 0, $lnk);

	} else {

	    $lnk[] = array(

	        'text' => $_LANG['go_back'], 'href' => 'shop.php?act=apply_list'

	    );

	    sys_msg('请选择要删除的记录', 0, $lnk);

	}

}



/**

 * 申请审核通过

 */

function action_pass ()

{

	// 全局变量

	$user = $GLOBALS['user'];

	$_CFG = $GLOBALS['_CFG'];

	$_LANG = $GLOBALS['_LANG'];

	$smarty = $GLOBALS['smarty'];

	$db = $GLOBALS['db'];

	$ecs = $GLOBALS['ecs'];

	$user_id = $_SESSION['user_id'];



	/* 检查权限 */

	admin_priv('shop_goods_apply_list');



	/* 提示信息 */

	$link[] = array(

	    'text' => $_LANG['go_back'], 'href' => 'shop.php?act=apply_list'

	);



	$shop_id = $GLOBALS['db']->getOne("SELECT shop_id FROM " . $GLOBALS['ecs']->table('shop_goods_apply') . " WHERE apply_id = " . $_GET['apply_id']);

	if ($shop_id) {

		// 更新状态

		$sql = "UPDATE " . $ecs->table('shop_goods_apply') . " SET is_pass = 1 WHERE apply_id = " . $_GET['apply_id'];

		$db->query($sql);



		sys_msg('操作成功', 0, $link);

	} else {

		sys_msg('操作失败', 0, $link);

	}

}



/**

 * 不通过通过审核

 */

function action_unpass ()

{

	// 全局变量

	$user = $GLOBALS['user'];

	$_CFG = $GLOBALS['_CFG'];

	$_LANG = $GLOBALS['_LANG'];

	$smarty = $GLOBALS['smarty'];

	$db = $GLOBALS['db'];

	$ecs = $GLOBALS['ecs'];

	$user_id = $_SESSION['user_id'];

	$unpass_reason = trim($_REQUEST['unpass_reason']);

	$apply_id = intval($_REQUEST['apply_id']);

	/* 检查权限 */

	admin_priv('shop_goods_apply_list');



	/* 提示信息 */

	$link[] = array(

	    'text' => $_LANG['go_back'], 'href' => 'shop.php?act=apply_list'

	);



	$shop_id = $GLOBALS['db']->getOne("SELECT shop_id FROM " . $GLOBALS['ecs']->table('shop_goods_apply') . " WHERE apply_id = " . $apply_id);

	if ($shop_id) {

		// 更新状态

		$sql = "UPDATE " . $ecs->table('shop_goods_apply') . " SET is_pass = 2, unpass_reason = '" . $unpass_reason . "' WHERE apply_id = " . $apply_id;

		$db->query($sql);



		sys_msg('操作成功', 0, $link);

	} else {

		sys_msg('操作失败', 0, $link);

	}

}



/**

 * 返回代理商申请列表数据

 */

function shop_goods_apply_list ()

{

	$result = get_filter();

	if($result === false)

	{

		/* 过滤条件 */

		$filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
		$filter['is_pass'] = isset($_REQUEST['is_pass'])?empty($_REQUEST['is_pass']) ? 0 : intval($_REQUEST['is_pass']):'-1';
		$filter['add_time'] = empty($_REQUEST['add_time']) ? '' : local_strtotime(trim($_REQUEST['add_time']));
		
		if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)

		{

			$filter['keywords'] = json_str_iconv($filter['keywords']);

		}



		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);

		$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);



		$ex_where = ' WHERE 1 ';

		if($filter['keywords'])

		{

			$ex_where .= " AND a.goods_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' or a.goods_type like  '%" . mysql_like_quote($filter['keywords']) . "%' or b.supplier_name like  '%" . mysql_like_quote($filter['keywords']) . "%' ";

		}

		if($filter['is_pass'] !='-1')

		{

			$ex_where .= " AND a.is_pass =".$filter['is_pass'];

		}

		if($filter['add_time'])

		{
			$end_time = $filter['add_time']*1 + 86400;
			$ex_where .= " AND a.add_time >=".$filter['add_time']." AND a.add_time <".$end_time;

		}


		$filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('shop_goods_apply') . " a LEFT JOIN " . $GLOBALS['ecs']->table('supplier') . " b ON a.shop_id = b.supplier_id" . $ex_where);



		/* 分页大小 */

		$filter = page_and_size($filter);



		$sql = "SELECT a.*, b.supplier_name ".

                " FROM " . $GLOBALS['ecs']->table('shop_goods_apply') . " a LEFT JOIN " . $GLOBALS['ecs']->table('supplier') . " b ON a.shop_id = b.supplier_id" . $ex_where . " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] . " LIMIT " . $filter['start'] . ',' . $filter['page_size'];



		$filter['keywords'] = stripslashes($filter['keywords']);

		set_filter($filter, $sql);

	}

	else

	{

		$sql = $result['sql'];

		$filter = $result['filter'];

	}



	$shop_goods_apply_list = $GLOBALS['db']->getAll($sql);



	$count = count($shop_goods_apply_list);

	for($i = 0; $i < $count; $i ++)

	{

		$shop_goods_apply_list[$i]['add_time'] = local_date('Y-m-d H:i:s', $shop_goods_apply_list[$i]['add_time']);

		$shop_goods_apply_list[$i]['goods_price'] = price_format($shop_goods_apply_list[$i]['goods_price']);



	}



	$arr = array(

		'shop_goods_apply_list' => $shop_goods_apply_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']

	);

	return $arr;

}



?>

