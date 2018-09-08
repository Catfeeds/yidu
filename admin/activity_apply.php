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

 * 优惠活动申请列表

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

	admin_priv('activity_apply_list');



	$smarty->assign('ur_here', '优惠活动申请列表');



	$activity_apply_list = activity_apply_list();

	$smarty->assign('activity_apply_list', $activity_apply_list['activity_apply_list']);

	$smarty->assign('filter', $activity_apply_list['filter']);

	$smarty->assign('record_count', $activity_apply_list['record_count']);

	$smarty->assign('page_count', $activity_apply_list['page_count']);

	$smarty->assign('full_page', 1);



	assign_query_info();

	$smarty->display('activity_apply_list.htm');

}



/**

 * ajax返回优惠活动申请列表

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



	$activity_apply_list = activity_apply_list();



	$smarty->assign('activity_apply_list', $activity_apply_list['activity_apply_list']);

	$smarty->assign('filter', $activity_apply_list['filter']);

	$smarty->assign('record_count', $activity_apply_list['record_count']);

	$smarty->assign('page_count', $activity_apply_list['page_count']);



	$sort_flag = sort_flag($activity_apply_list['filter']);

	$smarty->assign($sort_flag['tag'], $sort_flag['img']);



	make_json_result($smarty->fetch('activity_apply_list.htm'), '', array(

		'filter' => $activity_apply_list['filter'],'page_count' => $activity_apply_list['page_count']

	));

}



/**

 * 删除优惠活动申请记录

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

	admin_priv('activity_apply_list');



	$sql = "DELETE FROM " . $ecs->table('activity_apply') . " WHERE apply_id = " . $_GET['apply_id'];

	$db->query($sql);

	/* 提示信息 */

	$link[] = array(

	    'text' => $_LANG['go_back'], 'href' => 'activity_apply.php?act=apply_list'

	);



	sys_msg('删除成功', 0, $link);

}



/**

 * 批量删除优惠活动申请记录

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

	admin_priv('activity_apply_list');



	if(isset($_POST['checkboxes'])) {

	    $sql = "SELECT apply_id FROM " . $ecs->table('activity_apply') . " WHERE apply_id " . db_create_in($_POST['checkboxes']);

	    $col = $db->getCol($sql);

	    $remove_list = implode(',', addslashes_deep($col));

	    $count = count($col);

	    $sql = "DELETE FROM " . $ecs->table('activity_apply') . " WHERE apply_id IN(" . $remove_list . ")";

	    $db->query($sql);

	    $lnk[] = array(

	        'text' => $_LANG['go_back'], 'href' => 'activity_apply.php?act=apply_list'

	    );

	    sys_msg(sprintf('删除成功', $count), 0, $lnk);

	} else {

	    $lnk[] = array(

	        'text' => $_LANG['go_back'], 'href' => 'activity_apply.php?act=apply_list'

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

	admin_priv('activity_apply_list');



	/* 提示信息 */

	$link[] = array(

	    'text' => $_LANG['go_back'], 'href' => 'activity_apply.php?act=apply_list'

	);



	$apply_id = $GLOBALS['db']->getOne("SELECT apply_id FROM " . $GLOBALS['ecs']->table('activity_apply') . " WHERE apply_id = " . $_GET['apply_id']);

	if ($apply_id) {

		// 更新状态

		$sql = "UPDATE " . $ecs->table('activity_apply') . " SET pass_status = 1 WHERE apply_id = " . $_GET['apply_id'];

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



	/* 检查权限 */

	admin_priv('activity_apply_list');



	/* 提示信息 */

	$link[] = array(

	    'text' => $_LANG['go_back'], 'href' => 'activity_apply.php?act=apply_list'

	);



	$apply_id = $GLOBALS['db']->getOne("SELECT apply_id FROM " . $GLOBALS['ecs']->table('activity_apply') . " WHERE apply_id = " . $_GET['apply_id']);

	if ($apply_id) {

		// 更新状态

		$sql = "UPDATE " . $ecs->table('activity_apply') . " SET pass_status = 2 WHERE apply_id = " . $_GET['apply_id'];

		$db->query($sql);



		sys_msg('操作成功', 0, $link);

	} else {

		sys_msg('操作失败', 0, $link);

	}

}



/**

 * 返回优惠活动申请列表数据

 */

function activity_apply_list ()

{

	$result = get_filter();

	if($result === false)

	{

		/* 过滤条件 */

		$filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);

		if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)

		{

			$filter['keywords'] = json_str_iconv($filter['keywords']);

		}



		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);

		$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);



		$ex_where = ' WHERE 1 ';

		if($filter['keywords'])

		{

			$ex_where .= " AND activity_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' or activity_desc like  '%" . mysql_like_quote($filter['keywords']) . "%' or goods_list like  '%" . mysql_like_quote($filter['keywords']) . "%' ";

		}



		$filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('activity_apply') . $ex_where);



		/* 分页大小 */

		$filter = page_and_size($filter);



		$sql = "SELECT * ".

                " FROM " . $GLOBALS['ecs']->table('activity_apply') . $ex_where . " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] . " LIMIT " . $filter['start'] . ',' . $filter['page_size'];



		$filter['keywords'] = stripslashes($filter['keywords']);

		set_filter($filter, $sql);

	}

	else

	{

		$sql = $result['sql'];

		$filter = $result['filter'];

	}



	$activity_apply_list = $GLOBALS['db']->getAll($sql);

	$count = count($activity_apply_list);

	for($i = 0; $i < $count; $i ++)

	{

		$activity_apply_list[$i]['add_time']   = local_date('Y-m-d H:i:s', $activity_apply_list[$i]['add_time']);
		$activity_apply_list[$i]['start_time'] = local_date('Y-m-d', $activity_apply_list[$i]['start_time']);
		$activity_apply_list[$i]['end_time']   = local_date('Y-m-d', $activity_apply_list[$i]['end_time']);

		$activity_apply_list[$i]['supplier_name'] = $GLOBALS['db']->getOne("SELECT supplier_name FROM " . $GLOBALS['ecs']->table('supplier') . " WHERE shop_id = " . $activity_apply_list[$i]['shop_id']);

		switch ($activity_apply_list[$i]['act_type']) {

			case '0':

				$activity_apply_list[$i]['act_type'] = '赠品';

				break;

			case '1':

				$activity_apply_list[$i]['act_type'] = '现金减免';

				break;

			case '2':

				$activity_apply_list[$i]['act_type'] = '价格折扣';

				break;

			default:

				break;

		}

	}



	$arr = array(

		'activity_apply_list' => $activity_apply_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']

	);



	return $arr;

}



?>

