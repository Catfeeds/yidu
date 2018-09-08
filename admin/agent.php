<?php

/**
 * ECSHOP 会员管理程序
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: users.php 17217 2011-01-19 06:29:08Z liubo $
 */
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
 * 业主申请列表
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
	admin_priv('agent_apply_list');

	$smarty->assign('ur_here', '加盟商申请列表');

	$agent_apply_list = agent_apply_list();
	$smarty->assign('agent_apply_list', $agent_apply_list['agent_apply_list']);
	$smarty->assign('filter', $user_list['filter']);
	$smarty->assign('record_count', $agent_apply_list['record_count']);
	$smarty->assign('page_count', $agent_apply_list['page_count']);
	$smarty->assign('full_page', 1);
	$smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');

	assign_query_info();
	$smarty->display('agent_apply_list.htm');
}

/**
 * ajax返回业主申请列表
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

	$agent_apply_list = agent_apply_list();

	$smarty->assign('agent_apply_list', $agent_apply_list['agent_apply_list']);
	$smarty->assign('filter', $agent_apply_list['filter']);
	$smarty->assign('record_count', $agent_apply_list['record_count']);
	$smarty->assign('page_count', $agent_apply_list['page_count']);

	$sort_flag = sort_flag($agent_apply_list['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);

	make_json_result($smarty->fetch('agent_apply_list.htm'), '', array(
		'filter' => $agent_apply_list['filter'],'page_count' => $agent_apply_list['page_count']
	));
}

/* ------------------------------------------------------ */
// -- 添加会员帐号
/* ------------------------------------------------------ */
function action_add ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	$user = array(
		'rank_points' => $_CFG['register_points'],'pay_points' => $_CFG['register_points'],'sex' => 0,'credit_line' => 0
	);
	/* 取出注册扩展字段 */
	$sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 AND id != 6 ORDER BY dis_order, id';
	$extend_info_list = $db->getAll($sql);
	$smarty->assign('extend_info_list', $extend_info_list);

	$smarty->assign('ur_here', '添加业主');
	/*$smarty->assign('action_link', array(
		'text' => $_LANG['03_users_list'],'href' => 'users.php?act=list'
	));*/
	$smarty->assign('form_action', 'insert');
	$smarty->assign('user', $user);
	$smarty->assign('special_ranks', get_rank_list(true));

	$smarty->assign('lang', $_LANG);
	$smarty->assign('country_list', get_regions());
	$province_list = get_regions(1, $row['country']);
	$city_list = get_regions(2, $row['province']);
	$district_list = get_regions(3, $row['city']);

	$smarty->assign('province_list', $province_list);
	$smarty->assign('city_list', $city_list);
	$smarty->assign('district_list', $district_list);

	// 业主列表
	$sql = "SELECT user_id, user_name, real_name, agent_code FROM " . $ecs->table('users') . " WHERE is_agent = 1";
	$agent_list = $db->getAll($sql);
	$smarty->assign('agent_list', $agent_list);

	// 会员类型
	$smarty->assign('agent_type_list', agent_type_list());

	assign_query_info();
	$smarty->display('agent_info.htm');
}

/* ------------------------------------------------------ */
// -- 添加业主
/* ------------------------------------------------------ */
function action_insert ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	$username = empty($_POST['username']) ? '' : trim($_POST['username']);
	$password = empty($_POST['password']) ? '' : trim($_POST['password']);
	$email = empty($_POST['email']) ? '' : trim($_POST['email']);
	$mobile_phone = empty($_POST['mobile_phone']) ? '' : trim($_POST['mobile_phone']);
	$sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
	$sex = in_array($sex, array(
		0, 1, 2
	)) ? $sex : 0;
	$birthday = $_POST['birthdayYear'] . '-' . $_POST['birthdayMonth'] . '-' . $_POST['birthdayDay'];
	$rank = empty($_POST['user_rank']) ? 0 : intval($_POST['user_rank']);
	$credit_line = empty($_POST['credit_line']) ? 0 : floatval($_POST['credit_line']);
	$real_name = empty($_POST['real_name']) ? '' : trim($_POST['real_name']);
	$card = empty($_POST['card']) ? '' : trim($_POST['card']);
	$country = $_POST['country'];
	$province = $_POST['province'];
	$city = $_POST['city'];
	$district = $_POST['district'];
	$address = empty($_POST['address']) ? '' : trim($_POST['address']);
	$agent_code = empty($_POST['agent_code']) ? '' : trim($_POST['agent_code']);
	$agent_type = intval($_POST['agent_type']);
	$recommend_id = intval($_POST['recommend_id']);
	$is_agent = 1;
	$status = 1;
	$users = & init_users();

	// 业主
	if (empty($agent_code)) {
		sys_msg('业主编号不能为空!', 1, array(), false);
	}
	$agent_exist = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table('users') . " WHERE agent_code = '" . $agent_code . "'");
	if ($agent_exist > 0) {
		sys_msg('业主编号已存在!', 1, array(), false);
	}

	if(! $users->add_user($username, $password, $email))
	{
		/* 插入会员数据失败 */
		if($users->error == ERR_INVALID_USERNAME)
		{
			$msg = $_LANG['username_invalid'];
		}
		elseif($users->error == ERR_USERNAME_NOT_ALLOW)
		{
			$msg = $_LANG['username_not_allow'];
		}
		elseif($users->error == ERR_USERNAME_EXISTS)
		{
			$msg = $_LANG['username_exists'];
		}
		elseif($users->error == ERR_INVALID_EMAIL)
		{
			$msg = $_LANG['email_invalid'];
		}
		elseif($users->error == ERR_EMAIL_NOT_ALLOW)
		{
			$msg = $_LANG['email_not_allow'];
		}
		elseif($users->error == ERR_EMAIL_EXISTS)
		{
			$msg = $_LANG['email_exists'];
		}
		else
		{
			// die('Error:'.$users->error_msg());
		}
		sys_msg($msg, 1);
	}

	/* 注册送积分 */
	if(! empty($GLOBALS['_CFG']['register_points']))
	{
		log_account_change($_SESSION['user_id'], 0, 0, $GLOBALS['_CFG']['register_points'], $GLOBALS['_CFG']['register_points'], $_LANG['register_points']);
	}

	/* 把新注册用户的扩展信息插入数据库 */
	$sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id'; // 读出所有扩展字段的id
	$fields_arr = $db->getAll($sql);

	$extend_field_str = ''; // 生成扩展字段的内容字符串
	$user_id_arr = $users->get_profile_by_name($username);
	foreach($fields_arr as $val)
	{
		$extend_field_index = 'extend_field' . $val['id'];
		if(! empty($_POST[$extend_field_index]))
		{
			$temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
			$extend_field_str .= " ('" . $user_id_arr['user_id'] . "', '" . $val['id'] . "', '" . $temp_field_content . "'),";
		}
	}
	$extend_field_str = substr($extend_field_str, 0, - 1);

	if($extend_field_str) // 插入注册扩展数据
	{
		$sql = 'INSERT INTO ' . $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
		$db->query($sql);
	}

	/* 更新会员的其它信息 */
	$other = array();
	$other['credit_line'] = $credit_line;
	$other['user_rank'] = $rank;
	$other['sex'] = $sex;
	$other['birthday'] = $birthday;
	$other['reg_time'] = local_strtotime(local_date('Y-m-d H:i:s'));

	$other['msn'] = isset($_POST['extend_field1']) ? htmlspecialchars(trim($_POST['extend_field1'])) : '';
	$other['qq'] = isset($_POST['extend_field2']) ? htmlspecialchars(trim($_POST['extend_field2'])) : '';
	$other['office_phone'] = isset($_POST['extend_field3']) ? htmlspecialchars(trim($_POST['extend_field3'])) : '';
	$other['home_phone'] = isset($_POST['extend_field4']) ? htmlspecialchars(trim($_POST['extend_field4'])) : '';
	$other['mobile_phone'] = isset($_POST['extend_field5']) ? htmlspecialchars(trim($_POST['extend_field5'])) : '';

	$db->autoExecute($ecs->table('users'), $other, 'UPDATE', "user_name = '$username'");
	if(isset($_FILES['face_card']) && $_FILES['face_card']['tmp_name'] != '')
	{
		$face_card = $GLOBALS['image']->upload_image($_FILES['face_card']);
		if($face_card === false)
		{
			sys_msg($GLOBALS['image']->error_msg(), 1, array(), false);
		}
	}
	if(isset($_FILES['back_card']) && $_FILES['back_card']['tmp_name'] != '')
	{
		$back_card = $GLOBALS['image']->upload_image($_FILES['back_card']);
		if($back_card === false)
		{
			sys_msg($GLOBALS['image']->error_msg(), 1, array(), false);
		}
	}

	$sql = "update " . $ecs->table('users') . " set  mobile_phone='$mobile_phone' , `real_name`='$real_name',`card`='$card',`country`='$country',`province`='$province',`city`='$city',`district`='$district',`address`='$address',`status`='$status',`is_agent`='$is_agent',`agent_type`='$agent_type',`agent_code`='$agent_code' where user_name = '" . $username . "'";
	$db->query($sql);

	if($face_card != '')
	{
		$sql = "update " . $ecs->table('users') . " set `face_card` = '$face_card' where user_name = '" . $username . "'";
		$db->query($sql);
	}
	if($back_card != '')
	{
		$sql = "update " . $ecs->table('users') . " set `back_card` = '$back_card' where user_name = '" . $username . "'";
		$db->query($sql);
	}

	/* 生成店铺等操作start */
	if ($agent_type && $user_id_arr['user_id']) {
		// 根据业主类型生成店铺代码

		//生成店铺的数量
		switch ($agent_type) {
			case '1':
				$agent_shop_number = $_CFG['agent_shop_num_x'];
				break;
			case '2':
				$agent_shop_number = $_CFG['agent_shop_num_z'];
				break;
			case '3':
				$agent_shop_number = $_CFG['agent_shop_num_d'];
				break;
			case '4':
				$agent_shop_number = $_CFG['agent_shop_num_v'];
				break;
			case '5':
				$agent_shop_number = $_CFG['agent_shop_num_c'];
				break;
			default:
				break;
		}
		// $agent_code = generate_code();	//业主编号
		$str_len = strlen($agent_shop_number);	//数字长度
		for ($i=1; $i <= $agent_shop_number; $i++) {
			$data['shop_code'] = $agent_code . sprintf("%0" . $str_len . "d", $i);	//生成$str_len位数，不足前面补0
			$data['agent_id'] = $user_id_arr['user_id'];
			$data['user_id'] = 0;
			$data['chuzu_status'] = 0;
			$data['add_time'] = gmtime();
			$res = $db->autoExecute($ecs->table('agent_shop'), $data, 'INSERT');
		}

		// 推荐人
		$pid = $recommend_id ? $recommend_id : 0;
		$pids = get_parent_uid($pid);
		$pid3 = $pids['pid2'];
		$pid2 = $pids['pid1'];
		$pid1 = $pid;

		// 更新用户
		$sql = "UPDATE " . $ecs->table('users') . " SET pid1 = " . $pid1 . ", pid2 = " . $pid2 . ", pid3 = " . $pid3 . " WHERE user_id = " . $user_id_arr['user_id'];
		$db->query($sql);
	}
	/* 生成店铺等操作end */

	/* 记录管理员操作 */
	admin_log($_POST['username'], 'add', 'users');

	/* 提示信息 */
	$link[] = array(
		'text' => $_LANG['go_back'],'href' => 'users.php?act=list'
	);
	sys_msg(sprintf($_LANG['add_success'], htmlspecialchars(stripslashes($_POST['username']))), 0, $link);
}

/**
 * 删除业主申请记录
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
	admin_priv('agent_apply_list');

	$sql = "DELETE FROM " . $ecs->table('agent_apply') . " WHERE apply_id = " . $_GET['apply_id'];
	$db->query($sql);
	/* 提示信息 */
	$link[] = array(
	    'text' => $_LANG['go_back'], 'href' => 'agent.php?act=apply_list'
	);

	sys_msg('删除成功', 0, $link);
}

/**
 * 批量删除业主申请记录
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
	admin_priv('agent_apply_list');

	if(isset($_POST['checkboxes'])) {
	    $sql = "SELECT apply_id FROM " . $ecs->table('agent_apply') . " WHERE apply_id " . db_create_in($_POST['checkboxes']);
	    $col = $db->getCol($sql);
	    $remove_list = implode(',', addslashes_deep($col));
	    $count = count($col);
	    $sql = "DELETE FROM " . $ecs->table('agent_apply') . " WHERE apply_id IN(" . $remove_list . ")";
	    $db->query($sql);
	    $lnk[] = array(
	        'text' => $_LANG['go_back'], 'href' => 'agent.php?act=apply_list'
	    );
	    sys_msg(sprintf('删除成功', $count), 0, $lnk);
	} else {
	    $lnk[] = array(
	        'text' => $_LANG['go_back'], 'href' => 'agent.php?act=apply_list'
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
	admin_priv('agent_apply_list');

	/* 提示信息 */
	$link[] = array(
	    'text' => $_LANG['go_back'], 'href' => 'agent.php?act=apply_list'
	);

	$agent_code = $_REQUEST['agent_code'];
	if (empty($agent_code)) {
		sys_msg('业主编号不能为空!', 0, $link);
	}
	$agent_exist = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table('users') . " WHERE agent_code = '" . $agent_code . "'");
	if ($agent_exist > 0) {
		sys_msg('业主编号已存在!', 0, $link);
	}

	$apply_info = $GLOBALS['db']->getRow("SELECT agent_type, user_id, parent_agent_code FROM " . $GLOBALS['ecs']->table('agent_apply') . " WHERE apply_id = " . $_REQUEST['apply_id']);
	if ($apply_info['agent_type'] && $apply_info['user_id']) {
		// 根据业主类型生成店铺代码

		//生成店铺的数量
		switch ($apply_info['agent_type']) {
			case '1':
				$agent_shop_number = $_CFG['agent_shop_num_x'];
				break;
			case '2':
				$agent_shop_number = $_CFG['agent_shop_num_z'];
				break;
			case '3':
				$agent_shop_number = $_CFG['agent_shop_num_d'];
				break;
			case '4':
				$agent_shop_number = $_CFG['agent_shop_num_v'];
				break;
			case '5':
				$agent_shop_number = $_CFG['agent_shop_num_c'];
				break;
			default:
				break;
		}
		// $agent_code = generate_code();	//业主编号
		$str_len = strlen($agent_shop_number);	//数字长度
		for ($i=1; $i <= $agent_shop_number; $i++) {
			$data['shop_code'] = $agent_code . sprintf("%0" . $str_len . "d", $i);	//生成$str_len位数，不足前面补0
			$data['agent_id'] = $apply_info['user_id'];
			$data['user_id'] = 0;
			$data['chuzu_status'] = 0;
			$data['add_time'] = gmtime();
			$res = $db->autoExecute($ecs->table('agent_shop'), $data, 'INSERT');
		}

		// 是否存在上级业主
		if ($apply_info['parent_agent_code']) {
			$sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE agent_code = '" . $apply_info['parent_agent_code'] . "'";
			$user_id = $db->getOne($sql);
		}

		$pid = $user_id ? $user_id : 0;
		$pids = get_parent_uid($pid);
		$pid3 = $pids['pid2'];
		$pid2 = $pids['pid1'];
		$pid1 = $pid;

		// 更新用户
		$sql = "UPDATE " . $ecs->table('users') . " SET is_agent = 1, agent_type = '" . $apply_info['agent_type'] . "', agent_code = '" . $agent_code . "', pid1 = " . $pid1 . ", pid2 = " . $pid2 . ", pid3 = " . $pid3 . " WHERE user_id = " . $apply_info['user_id'];
		$db->query($sql);

		// 更新状态
		$sql = "UPDATE " . $ecs->table('agent_apply') . " SET is_pass = 1 WHERE apply_id = " . $_REQUEST['apply_id'];
		$db->query($sql);

		sys_msg('操作成功', 0, $link);
	} else {
		sys_msg('操作失败', 0, $link);
	}
}

/**
 * 返回业主申请列表数据
 */
function agent_apply_list ()
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
			$ex_where .= " AND name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' or phone like  '%" . mysql_like_quote($filter['keywords']) . "%' or address like  '%" . mysql_like_quote($filter['keywords']) . "%' ";
		}

		$filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('agent_apply') . $ex_where);

		/* 分页大小 */
		$filter = page_and_size($filter);

		$sql = "SELECT * ".
                " FROM " . $GLOBALS['ecs']->table('agent_apply') . $ex_where . " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] . " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

		$filter['keywords'] = stripslashes($filter['keywords']);
		set_filter($filter, $sql);
	}
	else
	{
		$sql = $result['sql'];
		$filter = $result['filter'];
	}

	$agent_apply_list = $GLOBALS['db']->getAll($sql);

	$count = count($agent_apply_list);
	for($i = 0; $i < $count; $i ++)
	{
		$agent_apply_list[$i]['add_time'] = local_date('Y-m-d H:i:s', $agent_apply_list[$i]['add_time']);
		$agent_apply_list[$i]['user_name'] = $GLOBALS['db']->getOne("SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = " . $agent_apply_list[$i]['user_id']);
		$agent_apply_list[$i]['parent_agent_name'] = $GLOBALS['db']->getOne("SELECT user_name AS parent_agent_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE agent_code = '" . $agent_apply_list[$i]['parent_agent_code'] . "'");

	}

	$arr = array(
		'agent_apply_list' => $agent_apply_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']
	);

	return $arr;
}

?>
