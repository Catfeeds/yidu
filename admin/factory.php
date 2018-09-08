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
 * 厂家申请列表
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
	admin_priv('factory_apply_list');

	$smarty->assign('ur_here', '厂家申请列表');

	$factory_apply_list = factory_apply_list();
	$smarty->assign('factory_apply_list', $factory_apply_list['factory_apply_list']);
	$smarty->assign('filter', $factory_apply_list['filter']);
	$smarty->assign('record_count', $factory_apply_list['record_count']);
	$smarty->assign('page_count', $factory_apply_list['page_count']);
	$smarty->assign('full_page', 1);

	assign_query_info();
	$smarty->display('factory_apply_list.htm');
}

/**
 * ajax返回厂家申请列表
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

	$factory_apply_list = factory_apply_list();

	$smarty->assign('factory_apply_list', $factory_apply_list['factory_apply_list']);
	$smarty->assign('filter', $factory_apply_list['filter']);
	$smarty->assign('record_count', $factory_apply_list['record_count']);
	$smarty->assign('page_count', $factory_apply_list['page_count']);

	$sort_flag = sort_flag($factory_apply_list['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);

	make_json_result($smarty->fetch('factory_apply_list.htm'), '', array(
		'filter' => $factory_apply_list['filter'],'page_count' => $factory_apply_list['page_count']
	));
}

/**
 * 删除厂家申请记录
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
	admin_priv('factory_apply_list');

	$sql = "DELETE FROM " . $ecs->table('factory') . " WHERE factory_id = " . $_GET['factory_id'];
	$db->query($sql);
	/* 提示信息 */
	$link[] = array(
	    'text' => $_LANG['go_back'], 'href' => 'factory.php?act=apply_list'
	);

	sys_msg('删除成功', 0, $link);
}

/**
 * 批量删除厂家申请记录
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
	admin_priv('factory_apply_list');

	if(isset($_POST['checkboxes'])) {
	    $sql = "SELECT factory_id FROM " . $ecs->table('factory') . " WHERE factory_id " . db_create_in($_POST['checkboxes']);
	    $col = $db->getCol($sql);
	    $remove_list = implode(',', addslashes_deep($col));
	    $count = count($col);
	    $sql = "DELETE FROM " . $ecs->table('factory') . " WHERE factory_id IN(" . $remove_list . ")";
	    $db->query($sql);
	    $lnk[] = array(
	        'text' => $_LANG['go_back'], 'href' => 'factory.php?act=apply_list'
	    );
	    sys_msg(sprintf('删除成功', $count), 0, $lnk);
	} else {
	    $lnk[] = array(
	        'text' => $_LANG['go_back'], 'href' => 'factory.php?act=apply_list'
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
	admin_priv('factory_apply_list');

	/* 提示信息 */
	$link[] = array(
	    'text' => $_LANG['go_back'], 'href' => 'factory.php?act=apply_list'
	);

	$factory_id = $GLOBALS['db']->getOne("SELECT factory_id FROM " . $GLOBALS['ecs']->table('factory') . " WHERE factory_id = " . $_GET['factory_id']);
	if ($factory_id) {
		// 更新状态
		$sql = "UPDATE " . $ecs->table('factory') . " SET pass_status = 1 WHERE factory_id = " . $_GET['factory_id'];
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
	admin_priv('factory_apply_list');

	/* 提示信息 */
	$link[] = array(
	    'text' => $_LANG['go_back'], 'href' => 'factory.php?act=apply_list'
	);

	$factory_id = $GLOBALS['db']->getOne("SELECT factory_id FROM " . $GLOBALS['ecs']->table('factory') . " WHERE factory_id = " . $_GET['factory_id']);
	if ($factory_id) {
		// 更新状态
		$sql = "UPDATE " . $ecs->table('factory') . " SET pass_status = 2 WHERE factory_id = " . $_GET['factory_id'];
		$db->query($sql);

		sys_msg('操作成功', 0, $link);
	} else {
		sys_msg('操作失败', 0, $link);
	}
}

/**
 * 返回厂家申请列表数据
 */
function factory_apply_list ()
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
			$ex_where .= " AND factory_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' or factory_type like  '%" . mysql_like_quote($filter['keywords']) . "%' or contacts_name like  '%" . mysql_like_quote($filter['keywords']) . "%' or contacts_phone like  '%" . mysql_like_quote($filter['keywords']) . "%' ";
		}

		$filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('factory') . $ex_where);

		/* 分页大小 */
		$filter = page_and_size($filter);

		$sql = "SELECT * ".
                " FROM " . $GLOBALS['ecs']->table('factory') . $ex_where . " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] . " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

		$filter['keywords'] = stripslashes($filter['keywords']);
		set_filter($filter, $sql);
	}
	else
	{
		$sql = $result['sql'];
		$filter = $result['filter'];
	}

	$factory_apply_list = $GLOBALS['db']->getAll($sql);

	$count = count($factory_apply_list);
	for($i = 0; $i < $count; $i ++)
	{
		$factory_apply_list[$i]['add_time'] = local_date('Y-m-d H:i:s', $factory_apply_list[$i]['add_time']);
		$factory_apply_list[$i]['user_name'] = $GLOBALS['db']->getOne("SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = " . $factory_apply_list[$i]['agent_id']);

	}

	$arr = array(
		'factory_apply_list' => $factory_apply_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']
	);

	return $arr;
}

?>
