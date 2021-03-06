<?php

/**
 * ECSHOP 前台公用文件
 * ============================================================================
 * * 版权所有 2008-2015 广州市互诺计算机科技有限公司，并保留所有权利。
 * 网站地址: http://www.hunuo.com;
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: derek $
 * $Id: init.php 17217 2011-01-19 06:29:08Z derek $
*/
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
error_reporting(0);
//error_reporting(E_ALL);

if (__FILE__ == '')
{
    die('Fatal error code: 0');
}

/* 取得当前ecshop所在的根目录 */
define('ROOT_PATH', str_replace('includes/init.php', '', str_replace('\\', '/', __FILE__)));
define('TOKEN', "leileiceshi");
define('ROOT_PATH_WAP', str_replace('/mobile','',ROOT_PATH));
if (!file_exists(ROOT_PATH . '../data/install.lock') && !file_exists(ROOT_PATH . '../includes/install.lock')
    && !defined('NO_CHECK_INSTALL'))
{
    header("Location: ./../install/index.php\n");

    exit;
}

/* 初始化设置 */
@ini_set('memory_limit',          '64M');
@ini_set('session.cache_expire',  180);
@ini_set('session.use_trans_sid', 0);
@ini_set('session.use_cookies',   1);
@ini_set('session.auto_start',    0);
@ini_set('display_errors',        0);//报错提示开启hao2018

if (DIRECTORY_SEPARATOR == '\\')
{
    @ini_set('include_path', '.;' . ROOT_PATH);
}
else
{
    @ini_set('include_path', '.:' . ROOT_PATH);
}

require(ROOT_PATH . '../data/config.php');

if (defined('DEBUG_MODE') == false)
{
    define('DEBUG_MODE', 0);
}

if (PHP_VERSION >= '5.1' && !empty($timezone))
{
    date_default_timezone_set($timezone);
}

$php_self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
if ('/' == substr($php_self, -1))
{
    $php_self .= 'index.php';
}
define('PHP_SELF', $php_self);

require(ROOT_PATH . 'includes/inc_constant.php');
require(ROOT_PATH . 'includes/cls_ecshop.php');
require(ROOT_PATH . 'includes/cls_error.php');
require(ROOT_PATH . 'includes/lib_time.php');
require(ROOT_PATH . 'includes/lib_base.php');
require(ROOT_PATH . 'includes/lib_common.php');
require(ROOT_PATH . 'includes/lib_main.php');
require(ROOT_PATH . 'includes/lib_insert.php');
require(ROOT_PATH . 'includes/lib_goods.php');
require(ROOT_PATH . 'includes/lib_article.php');

/* 对用户传入的变量进行转义操作。*/
if (!get_magic_quotes_gpc())
{
    if (!empty($_GET))
    {
        $_GET  = addslashes_deep($_GET);
    }
    if (!empty($_POST))
    {
        $_POST = addslashes_deep($_POST);
    }

    $_COOKIE   = addslashes_deep($_COOKIE);
    $_REQUEST  = addslashes_deep($_REQUEST);
}

/* 创建 ECSHOP 对象 */
$ecs = new ECS($db_name, $prefix);
define('DATA_DIR', $ecs->data_dir());
define('IMAGE_DIR', $ecs->image_dir());

/* 初始化数据库类 */
require(ROOT_PATH . 'includes/cls_mysql.php');
$db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);
$db->set_disable_cache_tables(array($ecs->table('sessions'), $ecs->table('sessions_data'), $ecs->table('cart')));
$db_host = $db_user = $db_pass = $db_name = NULL;



/* 创建错误处理对象 */
$err = new ecs_error('message.dwt');

/* 载入系统参数 */
$_CFG = load_config();

/* 载入语言文件 */
require(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/common.php');

if ($_CFG['shop_closed'] == 1)
{
    /* 商店关闭了，输出关闭的消息 */
    header('Content-type: text/html; charset='.EC_CHARSET);

    die('<div style="margin: 150px; text-align: center; font-size: 14px"><p>' . $_LANG['shop_closed'] . '</p><p>' . $_CFG['close_comment'] . '</p></div>');
}

/* 关闭过期的店铺 henson */
close_expire_shop();

if (is_spider())
{
    /* 如果是蜘蛛的访问，那么默认为访客方式，并且不记录到日志中 */
    if (!defined('INIT_NO_USERS'))
    {
        define('INIT_NO_USERS', true);
        /* 整合UC后，如果是蜘蛛访问，初始化UC需要的常量 */
        if($_CFG['integrate_code'] == 'ucenter')
        {
             $user = & init_users();
        }
    }
    $_SESSION = array();
    $_SESSION['user_id']     = 0;
    $_SESSION['user_name']   = '';
    $_SESSION['email']       = '';
    $_SESSION['user_rank']   = 0;
    $_SESSION['discount']    = 1.00;
}

if (!defined('INIT_NO_USERS'))
{
    /* 初始化session */
    include(ROOT_PATH . 'includes/cls_session.php');

    $sess = new cls_session($db, $ecs->table('sessions'), $ecs->table('sessions_data'));

    define('SESS_ID', $sess->get_session_id());
}
if(isset($_SERVER['PHP_SELF']))
{
    $_SERVER['PHP_SELF']=htmlspecialchars($_SERVER['PHP_SELF']);
}
if (!defined('INIT_NO_USERS'))
{
    /* 会员信息 */
    $user =& init_users();

    if (!isset($_SESSION['user_id']))
    {
        /* 获取投放站点的名称 */
        $site_name = isset($_GET['from'])   ? htmlspecialchars($_GET['from']) : addslashes($_LANG['self_site']);
        $from_ad   = !empty($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;

        $_SESSION['from_ad'] = $from_ad; // 用户点击的广告ID
        $_SESSION['referer'] = stripslashes($site_name); // 用户来源

        unset($site_name);

        if (!defined('INGORE_VISIT_STATS'))
        {
            visit_stats();
        }
    }

    if (empty($_SESSION['user_id']))
    {
        if ($user->get_cookie())
        {
            /* 如果会员已经登录并且还没有获得会员的帐户余额、积分以及优惠券 */
            if ($_SESSION['user_id'] > 0)
            {
                update_user_info();
            }
        }
        else
        {
            $_SESSION['user_id']     = 0;
            $_SESSION['user_name']   = '';
            $_SESSION['email']       = '';
            $_SESSION['user_rank']   = 0;
            $_SESSION['discount']    = 1.00;
            if (!isset($_SESSION['login_fail']))
            {
                $_SESSION['login_fail'] = 0;
            }
        }
    }

    /* 设置推荐会员 */
    if (isset($_GET['u']))
    {
        set_affiliate();
    }

    /* session 不存在，检查cookie */
    if (!empty($_COOKIE['ECS']['user_id']) && !empty($_COOKIE['ECS']['password']))
    {
        // 找到了cookie, 验证cookie信息
        $sql = 'SELECT user_id, user_name, password ' .
                ' FROM ' .$ecs->table('users') .
                " WHERE user_id = '" . intval($_COOKIE['ECS']['user_id']) . "' AND password = '" .$_COOKIE['ECS']['password']. "'";

        $row = $db->GetRow($sql);

        if (!$row)
        {
            // 没有找到这个记录
           $time = time() - 3600;
           setcookie("ECS[user_id]",  '', $time, '/');
           setcookie("ECS[password]", '', $time, '/');
        }
        else
        {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_name'] = $row['user_name'];
            update_user_info();
        }
    }

    if (isset($smarty))
    {
        $smarty->assign('ecs_session', $_SESSION);
    }
}

if ((DEBUG_MODE & 1) == 1)
{
    error_reporting(E_ALL);
}
else
{
    error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
}
if ((DEBUG_MODE & 4) == 4)
{
    include(ROOT_PATH . 'includes/lib.debug.php');
}

/* 判断是否支持 Gzip 模式 */
/*if (!defined('INIT_NO_SMARTY') && gzip_enabled())
{
    ob_start('ob_gzhandler');
}
else
{
    ob_start();
}*/

/**
 * 连接处理
 * @param [type] $content [description]
 * @param string $strUrl  [description]
 */
function PicUrl($content = null, $strUrl = ''){
    if(empty($strUrl)){
        $strUrl = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'];
    }
    if(is_array($content)){
        foreach($content as &$v){
            if(!strstr($v,'http')){
                $v = $strUrl."/".$v;
            }
        }
    }else{
        if(!strstr($content,'http')){
            $content = $strUrl."/".$content;
        }
    }
    return $content;
}
if (!defined('INIT_NO_SMARTY'))
{
    header('Cache-control: private');
    header('Content-type: text/html; charset='.EC_CHARSET);

    /* 创建 Smarty 对象。*/
    require(ROOT_PATH . 'includes/cls_template.php');
    $smarty = new cls_template;

    $h_uid = $_SESSION['user_id'];
    $up_uid = get_affiliate();
    if($up_uid>0&&$h_uid>0){
        $sql = "SELECT shop_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$up_uid'";
        $p_user = $GLOBALS['db']->getRow($sql);

        $sql = "SELECT parent_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$h_uid'";
        $h_user = $GLOBALS['db']->getRow($sql);
        if($p_user['shop_id']>0&&$h_user['parent_id']==0){
            // 设置推荐人
            $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET parent_id = ' . $up_uid . ' WHERE user_id = ' . $h_uid;
            $GLOBALS['db']->query($sql);
        }
    }
    

    /*分享*/
    require_once(ROOT_PATH.'includes/jssdk.php');
    $jssdk = new JSSDK();
    $signPackage = $jssdk->GetSignPackage();
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $smarty->assign('signPackage',$signPackage);//微信分享
    $smarty->assign('ecs_url_name',$protocol.$_SERVER['SERVER_NAME']);//域名链接
    $h_user_id = isset($_SESSION['user_id'])?$_SESSION['user_id']:0;
    if(strstr($_SERVER['REQUEST_URI'],'?')){
        $h_url = $_SERVER['REQUEST_URI'];
        $h_url = preg_replace('/u=(\d+)/','u='.$h_user_id, $h_url);
        $ecs_url_fenxian = $protocol.$_SERVER['SERVER_NAME'].$h_url;
    }else{
        $ecs_url_fenxian = $protocol.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'?u='.$h_user_id;
    }
    $smarty->assign('ecs_url_fenxian',$ecs_url_fenxian);//分享链接
    


    $smarty->cache_lifetime = $_CFG['cache_time'];
    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
    $uachar = "/(nokia|sony|ericsson|mot|samsung|sgh|lg|philips|panasonic|alcatel|lenovo|cldc|midp|mobile)/i";
    if(($ua == '' || preg_match($uachar, $ua))&& !strpos(strtolower($_SERVER['REQUEST_URI']),'wap'))
    {
        $smarty->template_dir   = ROOT_PATH . 'themesmobile/default';
        $smarty->cache_dir      = ROOT_PATH . 'temp/caches';
        $smarty->compile_dir    = ROOT_PATH . 'temp/compiled';
    }
    else
    {
        $smarty->template_dir   = ROOT_PATH . 'themesmobile/default';
        $smarty->cache_dir      = ROOT_PATH . 'temp/caches';
        $smarty->compile_dir    = ROOT_PATH . 'temp/compiled';
    }

        if ((DEBUG_MODE & 2) == 2)
        {
            $smarty->direct_output = true;
            $smarty->force_compile = true;
        }
        else
        {
            $smarty->direct_output = false;
            $smarty->force_compile = false;
        }

        $smarty->assign('lang', $_LANG);
        $smarty->assign('ecs_charset', EC_CHARSET);
    if(($ua == '' || preg_match($uachar, $ua))&& !strpos(strtolower($_SERVER['REQUEST_URI']),'wap'))
    {
        if (!empty($_CFG['stylename']))
        {
            $smarty->assign('ecs_css_path', 'themesmobile/' . $_CFG['template'] . '/style_' . $_CFG['stylename'] . '.css');
        }
        else
        {
            $smarty->assign('ecs_css_path', 'themesmobile/' . $_CFG['template'] . '/style.css');
        }
    }else
        {
        if (!empty($_CFG['stylename']))
        {
            $smarty->assign('ecs_css_path', 'themesmobile/' . $_CFG['template'] . '/style_' . $_CFG['stylename'] . '.css');
        }
        else
        {
            $smarty->assign('ecs_css_path', 'themesmobile/' . $_CFG['template'] . '/style.css');
        }
    }

    // 判断是否app打开网页
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($user_agent, 'yidushop') !== false) {
        $is_app = 1;
    } else {
        $is_app = 555;
    }
    // 判断安卓还是苹果
    if (strpos($user_agent, 'yidushopios') !== false) {
        $from_client = 'ios';
    } elseif (strpos($user_agent, 'yidushop_android') !== false) {
        $from_client = 'android';
    } else {
        $from_client = 'mobile';
    }
    $smarty->assign('is_app', $is_app);
    $smarty->assign('from_client', $from_client);
}
/*
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : "/moblie/";
$autoUrl = str_replace($_SERVER['REQUEST_URI'],"",$GLOBALS['ecs']->url());

@file_get_contents($autoUrl."/weixin/auto_do.php");
*/

/**
 * sql 跨站点脚本解决方案
 * */
foreach($_GET as $key => $vo){
    $key1 =  htmlentities($key, ENT_QUOTES | ENT_IGNORE, "UTF-8");
    $vo1 =  htmlentities($vo, ENT_QUOTES | ENT_IGNORE, "UTF-8");
    $_GET[$key1] = $vo1;
}
$ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
$ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
$ra = array_merge($ra1, $ra2);
$url_info = $_SERVER['REQUEST_URI'];
foreach($ra as $vo){
    if(stripos($url_info,$vo) != 0){
        exit('参数有敏感字符，已禁止访问');
    }
}
?>