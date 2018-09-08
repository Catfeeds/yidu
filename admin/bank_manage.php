<?php

/**
 * ECSHOP 程序说明
 * ===========================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: liubo $
 * $Id: affiliate.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
include_once (ROOT_PATH . '/includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);

// 银行列表
if ($_REQUEST['act'] == 'list')
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
    admin_priv('bank_manage');

    $smarty->assign('ur_here', '银行管理');
    $smarty->assign('action_link', array(
        'text' => '添加银行','href' => 'bank_manage.php?act=add'
    ));

    $bank_list = bank_list();

    $smarty->assign('bank_list', $bank_list['bank_list']);
    $smarty->assign('filter', $bank_list['filter']);
    $smarty->assign('record_count', $bank_list['record_count']);
    $smarty->assign('page_count', $bank_list['page_count']);
    $smarty->assign('full_page', 1);

    assign_query_info();
    $smarty->display('bank_list.htm');
}

/* ------------------------------------------------------ */
// -- ajax返回跑腿商品类型列表
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    // 全局变量
    $user = $GLOBALS['user'];
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $user_id = $_SESSION['user_id'];

    $bank_list = bank_list();

    $smarty->assign('bank_list', $bank_list['bank_list']);
    $smarty->assign('filter', $user_list['filter']);
    $smarty->assign('record_count', $bank_list['record_count']);
    $smarty->assign('page_count', $bank_list['page_count']);

    $sort_flag = sort_flag($bank_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('bank_list.htm'), '', array(
        'filter' => $bank_list['filter'],'page_count' => $bank_list['page_count']
    ));
}

/* ------------------------------------------------------ */
// -- 添加银行
/* ------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add')
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
    admin_priv('bank_manage');

    $smarty->assign('ur_here', '添加银行');
    $smarty->assign('action_link', array(
        'text' => '银行管理','href' => 'bank_manage.php?act=list'
    ));
    $smarty->assign('form_action', 'insert');

    $smarty->assign('lang', $_LANG);

    assign_query_info();
    $smarty->display('bank_info.htm');
}

/* ------------------------------------------------------ */
// -- 添加银行处理
/* ------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'insert')
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
    admin_priv('bank_manage');
    $bank_name = $_POST['bank_name'] ? trim($_POST['bank_name']) : '';
    $type = trim($_POST['type']);
    if (!$bank_name) {
        sys_msg('请填写银行名称！', 1, array(), false);
    }

    $data['bank_name'] = $bank_name;
    if(isset($_FILES['icon']) && $_FILES['icon']['tmp_name'] != '')
    {
        $icon_arr = filter_var_array($_FILES['icon'], FILTER_SANITIZE_SPECIAL_CHARS);
        $icon = $image->upload_image($icon_arr, 'bank_icon/' . date('Ym'));
        if($icon === false)
        {
            sys_msg($image->error_msg(), 1, array(), false);
        }
    }
    $data['icon'] = $icon ? $icon : '';
    $data['add_time'] = gmtime();
    $res = $db->autoExecute($ecs->table('bank_list'), $data, 'INSERT');
    $link[] = array(
        'text' => $_LANG['go_back'],'href' => 'bank_manage.php?act=list'
    );
    if ($res) {
        sys_msg('添加成功', 0, $link);
    } else {
        sys_msg('添加失败', 1, array(), false);
    }
}

/* ------------------------------------------------------ */
// -- 编辑银行
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
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
    admin_priv('bank_manage');

    $sql = "SELECT * FROM " . $ecs->table('bank_list') . " WHERE bank_id = " . $_GET['id'];

    $bank_info = $db->GetRow($sql);

    if(!$bank_info) {
        $link[] = array(
            'text' => $_LANG['go_back'],'href' => 'bank_manage.php?act=list'
        );
        sys_msg('非法操作', 0, $links);
    }
    $smarty->assign('bank_info', $bank_info);
    $smarty->assign('lang', $_LANG);
    assign_query_info();
    $smarty->assign('ur_here', '编辑银行卡');
    $smarty->assign('action_link', array(
        'text' => '银行卡管理','href' => 'bank_manage.php?act=list'
    ));
    $smarty->assign('form_action', 'update');
    $smarty->display('bank_info.htm');
}

/* ------------------------------------------------------ */
// -- 编辑银行处理
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'update')
{
    // 全局变量
    $user = $GLOBALS['user'];
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];

    /* 检查权限 */
    admin_priv('bank_manage');

    $bank_id = $_POST['bank_id'] ? $_POST['bank_id'] : 0;
    $bank_name = $_POST['bank_name'] ? trim($_POST['bank_name']) : '';
    if (!$bank_name) {
        sys_msg('请填写银行名称！', 1, array(), false);
    }
    if(isset($_FILES['icon']) && $_FILES['icon']['tmp_name'] != '')
    {
        $icon_arr = filter_var_array($_FILES['icon'], FILTER_SANITIZE_SPECIAL_CHARS);
        $icon = $image->upload_image($icon_arr, 'bank_icon/' . date('Ym'));
        if($icon === false)
        {
            sys_msg($image-->error_msg(), 1, array(), false);
        }
    }

    if($icon != '')
    {
        $sql = "update " . $ecs->table('bank_list') . " set `icon` = '$icon' where bank_id = '" . $bank_id . "'";
        $db->query($sql);
    }

    $sql = "update " . $ecs->table('bank_list') . " set `bank_name`='$bank_name' where bank_id = '" . $bank_id . "'";
    $db->query($sql);

    /* 提示信息 */
    $links[0]['text'] = '返回银行管理';
    $links[0]['href'] = 'bank_manage.php?act=list';
    $links[1]['text'] = $_LANG['go_back'];
    $links[1]['href'] = 'javascript:history.back()';

    sys_msg('编辑成功', 0, $links);
}

/* ------------------------------------------------------ */
// -- 删除银行
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
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
    admin_priv('bank_manage');

    $sql = "DELETE FROM " . $ecs->table('bank_list') . " WHERE bank_id=" . $_GET['id'];
    $status = $db->query($sql);
    /* 提示信息 */
    $link[] = array(
        'text' => $_LANG['go_back'], 'href' => 'bank_manage.php?act=list'
    );
    if ($status) {
        sys_msg('删除成功', 0, $link);
    } else {
        sys_msg('删除失败', 0, $link);
    }
}

/* ------------------------------------------------------ */
// -- 批量删除银行
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch_remove')
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
    admin_priv('bank_manage');

    if(isset($_POST['checkboxes'])) {
        $sql = "SELECT bank_id FROM " . $ecs->table('bank_list') . " WHERE bank_id " . db_create_in($_POST['checkboxes']);
        $col = $db->getCol($sql);
        $bank_id = implode(',', addslashes_deep($col));
        $count = count($col);
        $sql = "DELETE FROM " . $ecs->table('bank_list') . " WHERE bank_id IN(" . $bank_id . ")";
        $status = $db->query($sql);
        $lnk[] = array(
            'text' => $_LANG['go_back'], 'href' => 'bank_manage.php?act=list'
        );
        if ($status) {
            sys_msg(sprintf('删除成功', $count), 0, $lnk);
        } else {
            sys_msg('删除失败', 0, $lnk);
        }
    } else {
        $lnk[] = array(
            'text' => $_LANG['go_back'], 'href' => 'bank_manage.php?act=list'
        );
        sys_msg('请选择要删除的英航名称', 0, $lnk);
    }
}

/**
 * 银行列表数据
 */
function bank_list ()
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
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        $ex_where = " WHERE 1 ";
        if($filter['keywords'])
        {
            $ex_where .= " AND bank_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%'";
        }

        $filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('bank_list') . $ex_where);

        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('bank_list') . $ex_where . " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] . " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

        $filter['keywords'] = stripslashes($filter['keywords']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    $bank_list = $GLOBALS['db']->getAll($sql);

    $count = count($bank_list);
    for($i = 0; $i < $count; $i ++)
    {
        $bank_list[$i]['add_time'] = local_date('Y-m-d H:i:s', $bank_list[$i]['add_time']);
    }
    $arr = array(
        'bank_list' => $bank_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']
    );
    return $arr;
}
?>