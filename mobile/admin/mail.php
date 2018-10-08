<?php

/**
 * ECSHOP  管理中心管理员留言程序
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: message.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/admin/mail.php');
/* act操作项的初始化 */
$_REQUEST['act'] = trim($_REQUEST['act']);
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
/*------------------------------------------------------ */
//-- 留言列表页面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     $_LANG['send_list']);
    $smarty->assign('action_link', array('text' => $_LANG['send_mail'], 'href' => 'mail.php?act=send'));

    $list = get_message_list();
    $smarty->assign('mail_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('mail_list.htm');
}
/*------------------------------------------------------ */
//-- ajax会员查询页面
/*------------------------------------------------------ */
elseif (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
{
    /* 过滤条件 */
    $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
    if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
    {
        $filter['keywords'] = json_str_iconv($filter['keywords']);
    }
    $ex_where = ' WHERE 1 ';
    if($filter['keywords'])
    {
        $ex_where .= " AND user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' or email like  '%" . mysql_like_quote($filter['keywords']) . "%' or mobile_phone like  '%" . mysql_like_quote($filter['keywords']) . "%' ";
    }
    $filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('users') . $ex_where);

    $sql = "SELECT user_id, user_name FROM " . $GLOBALS['ecs']->table('users') . $ex_where ;
    $user_list = $GLOBALS['db']->getAll($sql);
    $user_option = '';
    foreach($user_list as $item){
        $user_option .= '<option value="'.$item['user_id'].'" selected>'.$item['user_name'].'</option>';
    }
    $arr = array(
        'user_option' => $user_option, 'record_count' => $filter['record_count'].'条记录'
    );
    echo json_encode($arr);
}
/*------------------------------------------------------ */
//-- 翻页、排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $list = get_message_list();

    $smarty->assign('mail_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('mail_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/*------------------------------------------------------ */
//-- 留言发送页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'send')
{
    /* 获取管理员列表 */
    $smarty->assign('ur_here',     $_LANG['send_msg']);
    $smarty->assign('action_link', array('href' => 'mail.php?act=list', 'text' => $_LANG['mail_list']));
    $smarty->assign('action',      'add');
    $smarty->assign('form_act',    'insert');

    assign_query_info();
    $smarty->display('mail_info.htm');
}

/*------------------------------------------------------ */
//-- 处理留言的发送
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert')
{

    $receiver_id = $_REQUEST['receiver_id'];
    $sql = "INSERT INTO " .$ecs->table('message'). " (admin_id,user_id, title, short, " .
                    "content, type,add_time) ".
                "VALUES ('".$_SESSION['admin_id']."', '0','$_POST[title]','$_POST[short]','$_POST[content]','$_POST[type]',  '" .gmtime(). "')";
    $db->query($sql);
    $insert_id =mysql_insert_id();
    if ($receiver_id[0] == 'all') {
        $sql ="SELECT user_id FROM " . $ecs->table('users');
        $user_list = $db->getAll($sql);

        foreach($user_list as $user){
            $sql = "INSERT INTO " .$ecs->table('message_log') . " (msg_id,user_id,add_time) " . " VALUES ($insert_id,$user[user_id],".gmtime().") ";
            $db->query($sql);
        }
    } else {
        foreach($receiver_id as $key=>$user_id){
            $sql = "INSERT INTO " .$ecs->table('message_log') . " (msg_id,user_id,add_time) " . " VALUES ($insert_id,$user_id,".gmtime().") ";
            $db->query($sql);
        }
    }


    /*添加链接*/
    $link[0]['text'] = $_LANG['back_list'];
    $link[0]['href'] = 'mail.php?act=list';

    $link[1]['text'] = $_LANG['continue_send_msg'];
    $link[1]['href'] = 'mail.php?act=send';

    sys_msg($_LANG['send_mail'] . "&nbsp;" . $_LANG['action_succeed'],0, $link);

    /* 记录管理员操作 */
    admin_log(admin_log($_LANG['send_mail']), 'add', 'admin_message');
}
/*------------------------------------------------------ */
//-- 留言编辑页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    $id = intval($_REQUEST['id']);


    /* 获得留言数据*/
    $sql = 'SELECT * '.
           'FROM ' .$ecs->table('message'). " WHERE msg_id='$id'";
    $msg_arr = $db->getRow($sql);

    $smarty->assign('ur_here',     $_LANG['edit_mail']);
    $smarty->assign('action_link', array('href' => 'mail.php?act=list', 'text' => $_LANG['mail_list']));
    $smarty->assign('form_act',    'update');
    $smarty->assign('mail_arr',     $mail_arr);

    assign_query_info();
    $smarty->display('mail_info.htm');
}
elseif ($_REQUEST['act'] == 'update')
{
    /* 获得留言数据*/
    $mail_arr = array();
    $mail_arr = $db->getRow('SELECT * FROM ' .$ecs->table('message')." WHERE msg_id='$_POST[id]'");

    $sql = "UPDATE " .$ecs->table('message'). " SET ".
           "title = '$_POST[title]',".
           "short = '$_POST[short]',".
           "content = '$_POST[content]'".
           "WHERE admin_id = '$mail_arr[admin_id]' AND sent_time='$mail_arr[send_time]'";
    $db->query($sql);

    $link[0]['text'] = $_LANG['back_list'];
    $link[0]['href'] = 'mail.php?act=list';

    sys_msg($_LANG['edit_mail'] . ' ' . $_LANG['action_succeed'],0, $link);

    /* 记录管理员操作 */
    admin_log(addslashes($_LANG['edit_mail']), 'edit', 'admin_message');
}

/*------------------------------------------------------ */
//-- 留言查看页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'view')
{
    $msg_id = intval($_REQUEST['id']);

    /* 获得管理员留言数据 */
    $mail_arr = array();
    $sql     = "SELECT a.*, b.user_name ".
               "FROM " .$ecs->table('message')." AS a ".
               "LEFT JOIN " .$ecs->table('admin_user')." AS b ON b.user_id = a.admin_id ".
               "WHERE a.msg_id = '$msg_id'";
    $mail_arr = $db->getRow($sql);
    $mail_arr['title']   = nl2br(htmlspecialchars($mail_arr['title']));
    $mail_arr['short'] = nl2br(htmlspecialchars($mail_arr['short']));
    $mail_arr['content'] = nl2br(htmlspecialchars($mail_arr['content']));
    $mail_arr['formated_add_time'] = local_date('Y-m-d H:i:s', $mail_arr['add_time']);



    //模板赋值，显示
    $smarty->assign('ur_here',     $_LANG['view_mail']);
    $smarty->assign('action_link', array('href' => 'mail.php?act=list', 'text' => $_LANG['mail_list']));
    $smarty->assign('admin_user',  $_SESSION['admin_name']);
    $smarty->assign('mail_arr',     $mail_arr);

    assign_query_info();
    $smarty->display('mail_view.htm');
}

/* ------------------------------------------------------ */
// -- 删除消息
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
    $id = intval($_GET['id']);

    $sql = "UPDATE " . $ecs->table('message') . " SET deleted = 1 " . " WHERE msg_id = $id AND (admin_id='$_SESSION[admin_id]')";

    $status = $db->query($sql);

    /* 提示信息 */
    $link[] = array(
        'text' => $_LANG['go_back'], 'href' => 'mail.php?act=list'
    );
    if ($status) {
        sys_msg('删除成功', 0, $link);
    } else {
        sys_msg('删除失败', 0, $link);
    }
}

/**
 *  获取管理员留言列表
 *
 * @return void
 */
function get_message_list()
{
    /* 查询条件 */
    $filter['sort_by']    = empty($_REQUEST['sort_by'])    ? 'add_time' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);


    $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('message')." AS a WHERE 1  ";
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    /* 分页大小 */
    $filter = page_and_size($filter);

    $sql = "SELECT a.*,b.user_name".
            " FROM ".$GLOBALS['ecs']->table('message')." AS a,".$GLOBALS['ecs']->table('admin_user')." AS b ".
            " WHERE a.admin_id = b.user_id AND deleted = 0 ".
            " ORDER BY ".$filter['sort_by']." ".$filter['sort_order'].
            " LIMIT ". $filter['start'] .", $filter[page_size]";
    $row = $GLOBALS['db']->getAll($sql);

    foreach ($row as $key=>$val)
    {
        $row[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['add_time']);
    }

    $arr = array('item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

?>
