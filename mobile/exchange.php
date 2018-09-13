<?php

/**
 * ECSHOP 积分商城
 * ============================================================================
 * * 版权所有 2008-2015 广州市互诺计算机科技有限公司，并保留所有权利。
 * 网站地址: http://www.hunuo.com;
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: derek $
 * $Id: exchange.php 17217 2011-01-19 06:29:08Z derek $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

/*------------------------------------------------------ */
//-- act 操作项的初始化
/*------------------------------------------------------ */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}


//每个用户进入抽奖中心的时候，都要去判断所有的抽奖订单是否有过期的订单
$sql = 'SELECT order_id,add_time,extension_id,extension_num FROM ' .$GLOBALS['ecs']->table('order_info'). ' WHERE order_status != 2 AND pay_time=0 AND extension_code = "exchange_goods" AND is_lucky = 0';
$res = $GLOBALS['db']->getAll($sql);
//print_r($res);exit;
foreach ($res as $k => $v) {
    if ($v['add_time']*1+60*10 < time()) {
        $sql = 'UPDATE ' . $ecs->table('order_info') . " SET `order_status`= 2  WHERE `order_id`='" . $v['order_id'] . "'";
        $GLOBALS['db']->query($sql);
//        //计算这个取消订单里面的抽奖码。
//        $arr = count(explode(',', $v['extension_num']));

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



$last = isset($_REQUEST['last'])?trim($_REQUEST['last']):'';
$amount = isset($_REQUEST['amount'])?trim($_REQUEST['amount']):'';
if($_REQUEST['act'] == 'ajax_list'){

    include('includes/cls_json.php');

    $limit = " limit $last,$amount";//每次加载的个数
    $json   = new JSON;

    $goodslist = get_exchange_goods_list( $limit );
// print_r($goodslist);exit;
    foreach($goodslist as $key=>$val){
        $GLOBALS['smarty']->assign('goods',$val);
        $GLOBALS['smarty']->assign('key',$key);
        $res[]['info']  = $GLOBALS['smarty']->fetch('library/exchange_list.lbi');
    }

    die($json->encode($res));
}
/*---
/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

/*------------------------------------------------------ */
//-- 积分兑换商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 初始化分页信息 */
    //$page         = isset($_REQUEST['page'])   && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
    //$size         = isset($_CFG['page_size'])  && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;
    $cat_id       = isset($_REQUEST['cat_id']) && intval($_REQUEST['cat_id']) > 0 ? intval($_REQUEST['cat_id']) : 0;
    $integral_max = isset($_REQUEST['integral_max']) && intval($_REQUEST['integral_max']) > 0 ? intval($_REQUEST['integral_max']) : 0;
    $integral_min = isset($_REQUEST['integral_min']) && intval($_REQUEST['integral_min']) > 0 ? intval($_REQUEST['integral_min']) : 0;

    /* 排序、显示方式以及类型 */
    $default_display_type      = $_CFG['show_order_type'] == '0' ? 'list' : ($_CFG['show_order_type'] == '1' ? 'grid' : 'text');
    $default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'DESC' : 'ASC';
    $default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'goods_id' : ($_CFG['sort_order_type'] == '1' ? 'exchange_integral' : 'last_update');

    $sort    = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'exchange_integral', 'last_update','click_count'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;
    $order   = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC')))                              ? trim($_REQUEST['order']) : $default_sort_order_method;
    $display = (isset($_REQUEST['display']) && in_array(trim(strtolower($_REQUEST['display'])), array('list', 'grid', 'text'))) ? trim($_REQUEST['display'])  : (isset($_COOKIE['ECS']['display']) ? $_COOKIE['ECS']['display'] : $default_display_type);
    $display  = in_array($display, array('list', 'grid', 'text')) ? $display : 'text';
    setcookie('ECS[display]', $display, gmtime() + 86400 * 7);

    /* 页面的缓存ID */
    $cache_id = sprintf('%X', crc32($cat_id . '-' . $display . '-' . $sort  .'-' . $order  .'-' . $page . '-' . $size . '-' . $_SESSION['user_rank'] . '-' .
        $_CFG['lang'] . '-' . $integral_max . '-' .$integral_min));

    if (!$smarty->is_cached('exchange.dwt', $cache_id))
    {
//        /* 如果页面没有被缓存则重新获取页面的内容 */
//
//        $children = get_children($cat_id);
//
//        $cat = get_cat_info($cat_id);   // 获得分类的相关信息
//
//        if (!empty($cat))
//        {
//            $smarty->assign('keywords',    htmlspecialchars($cat['keywords']));
//            $smarty->assign('description', htmlspecialchars($cat['cat_desc']));
//        }
//
//        assign_template();
//
//        $position = assign_ur_here('exchange');
//        $smarty->assign('page_title',       $position['title']);    // 页面标题
//        $smarty->assign('ur_here',          $position['ur_here']);  // 当前位置
//
//        $smarty->assign('categories',       get_categories_tree());        // 分类树
//        $smarty->assign('helps',            get_shop_help());              // 网店帮助
//        $smarty->assign('top_goods',        get_top10());                  // 销售排行
//        $smarty->assign('promotion_info',   get_promotion_info());         // 促销活动信息
//        $smarty->assign('wap_exchange_ad',  get_wap_advlist('wap积分商城幻灯广告', 5));  //wap首页幻灯广告位
//
//
//        /* 调查 */
//        $vote = get_vote();
//        if (!empty($vote))
//        {
//            $smarty->assign('vote_id',     $vote['id']);
//            $smarty->assign('vote',        $vote['content']);
//        }
//
//        $ext = ''; //商品查询条件扩展
//
//        //$smarty->assign('best_goods',      get_exchange_recommend_goods('best', $children, $integral_min, $integral_max));
//        //$smarty->assign('new_goods',       get_exchange_recommend_goods('new',  $children, $integral_min, $integral_max));
//        $smarty->assign('hot_goods',       get_exchange_recommend_goods('hot',  $children, $integral_min, $integral_max));
//
//
//        $count = get_exchange_goods_count($children, $integral_min, $integral_max);
//        $max_page = ($count> 0) ? ceil($count / $size) : 1;
//        if ($page > $max_page)
//        {
//            $page = $max_page;
//        }
//        $goodslist = exchange_get_goods($children, $integral_min, $integral_max, $ext, $size, $page, $sort, $order);
//        if($display == 'grid')
//        {
//            if(count($goodslist) % 2 != 0)
//            {
//                $goodslist[] = array();
//            }
//        }
//        $smarty->assign('goods_list',       $goodslist);
//        $smarty->assign('category',         $cat_id);
//        $smarty->assign('integral_max',     $integral_max);
//        $smarty->assign('integral_min',     $integral_min);


        assign_pager('exchange',            $cat_id, $count, $size, $sort, $order, $page, '', '', $integral_min, $integral_max, $display); // 分页
        assign_dynamic('exchange_list'); // 动态内容
    }
    $smarty->assign('sort',$sort);
    $smarty->assign('order',$order);
    $smarty->assign('display',$display);
    $smarty->assign('feed_url',         ($_CFG['rewrite'] == 1) ? "feed-typeexchange.xml" : 'feed.php?type=exchange'); // RSS URL
    $smarty->display('exchange_list.dwt', $cache_id);
}

/*------------------------------------------------------ */
//-- 积分兑换商品详情
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'view')
{
    //今天的开奖码
    $totay = local_date('Y-m-d',time());
    $tt = local_strtotime($totay);
    $tt1 = $tt*1 +86400;

    $exchange_lucky = $GLOBALS['db']->getOne('SELECT exchange_lucky FROM ' . $GLOBALS['ecs']->table('exchange_goods') ." WHERE open_time > $tt AND open_time < $tt1 order by goods_id desc LIMIT 1");
    $smarty->assign('exchange_lucky',            $exchange_lucky);


    /* 获得热门商品列表 */
    $sql = 'SELECT g.goods_id, g.goods_name,  eg.exchange_integral,eg.exchange_number, ' .
        ' g.goods_thumb , g.goods_img, eg.is_hot ' .
        'FROM ' . $GLOBALS['ecs']->table('exchange_goods') . ' AS eg, ' .$GLOBALS['ecs']->table('goods') . ' AS g ' .
        "WHERE eg.goods_id = g.goods_id AND eg.is_hot = 1  ORDER BY goods_id $order LIMIT 9";
    $hot = $GLOBALS['db']->getAll($sql);
    // print_r($hot);exit;
    $smarty->assign('hot',            $hot);

    //下期开奖时间

    $s = local_date('Y-m-d',time());
    $w = intval(date('w' , strtotime($s)));
    if($w === 5){
        $l = strtotime($s)*1+86400*3;
        $s1 = local_date('y',$l);
        $s2 = local_date('m',$l);
        $s3 = local_date('d',$l);
    }elseif ($w === 6) {
        $l = strtotime($s)*1+86400*2;
        $s1 = local_date('y',$l);
        $s2 = local_date('m',$l);
        $s3 = local_date('d',$l);
    }else{
        $l = strtotime($s)*1+86400;
        $s1 = local_date('y',$l);
        $s2 = local_date('m',$l);
        $s3 = local_date('d',$l);
    }

    $smarty->assign('s1',            $s1); //年
    $smarty->assign('s2',            $s2); //月
    $smarty->assign('s3',            $s3); //日

    //往期中奖名单 最近10期的
    $stage = $GLOBALS['db']->getAll('SELECT stage FROM ' . $GLOBALS['ecs']->table('exchange_goods') ." WHERE stage != '' GROUP BY stage order by stage desc LIMIT 10");
    
    foreach ($stage as $k => $v) {
        $user = $GLOBALS['db']->getAll('SELECT user_id FROM ' . $GLOBALS['ecs']->table('exchange_goods') ." WHERE stage = $v[stage]");
        foreach ($user as $k1 => $v1) {
            $user_info[$k]['stage'] = $v['stage'];
            $info = $GLOBALS['db']->getRow('SELECT user_name,mobile_phone FROM ' . $GLOBALS['ecs']->table('users') ." WHERE user_id = $v1[user_id]");
            //修正电话号码

            $info['mobile_phone'] = substr($info['mobile_phone'] , 0 , 3).'*****'.substr($info['mobile_phone'] ,  -3);

            $user_info[$k]['arr'][] = $info;

        }
    }
    $smarty->assign('user_info',            $user_info);
    // print_r($user_info);exit;
    
    //判断是否为微信浏览器
    if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger'))
    {
        $smarty->assign('is_weixin','1');
        
    }

    $smarty->assign('wx_imgUrl',$url_name.'/mobile/themesmobile/default/images/img6.png');//微信分享图片

    $smarty->display('exchange_goods.dwt',      $cache_id);
}

/*------------------------------------------------------ */
//--  兑换
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'buy')
{

    /* 查询：判断是否登录 */
    if (!isset($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
    {
        $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'exchange') ? $GLOBALS['_SERVER']['HTTP_REFERER'] : './index.php';
    }

    /* 查询：判断是否登录 */
    if ($_SESSION['user_id'] <= 0)
    {
        show_message('对不起，您没有登录，不能参加抽奖，请您先登录！', array($_LANG['back_up_page']), array($back_act), 'error');
    }

    /* 查询：取得参数：商品id */
    $goods_id = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
    if ($goods_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 查询：取得兑换商品信息 */
    $goods = get_exchange_goods_info($goods_id);
    // print_r($goods);exit;
    if (empty($goods))
    {
        ecs_header("Location: ./\n");
        exit;
    }
    // print_r($goods);exit;
    /* 查询：检查兑换商品是否有库存 */
    $kc = $goods['exchange_number']*1 - $_POST['number']*1;
    if($_CFG['use_storage'] == 1 &&  $kc< 0)
    {
        show_message('对不起，该商品库存不足，现在不能抽奖！', array($_LANG['back_up_page']), array($back_act), 'error');
    }

    /* 查询：检查兑换商品是否是取消 */
    if ($goods['is_exchange'] == 0)
    {
        show_message('对不起，该商品可能已下架，现在不能抽奖！', array($_LANG['back_up_page']), array($back_act), 'error');
    }



    /* 查询：取得规格 */
    $specs = '';
    foreach ($_POST as $key => $value)
    {
        if (strpos($key, 'spec_') !== false)
        {
            $specs .= ',' . intval($value);
        }
    }

    $specs = trim($specs, ',');
    // print_r($specs);exit;
    /* 查询：如果商品有规格则取规格商品信息 配件除外 */
    if (!empty($specs))
    {
        $_specs = explode(',', $specs);

        $product_info = get_products_info($goods_id, $_specs);
    }
    if (empty($product_info))
    {
        $product_info = array('product_number' => '', 'product_id' => 0);
    }

    /* 查询：查询规格名称和值，考虑价格 */
    $attr_list = array();
    $sql = "SELECT a.attr_name, g.attr_value ,g.attr_price " .
        "FROM " . $ecs->table('goods_attr') . " AS g, " .
        $ecs->table('attribute') . " AS a " .
        "WHERE g.attr_id = a.attr_id " .
        "AND g.goods_attr_id " . db_create_in($specs);
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        $price += $row['attr_price']*1 ; //属性价格
        $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
    }

    $goods_attr = join(chr(13) . chr(10), $attr_list);
    $attr_price = round($price *0.05,1)*1 +$goods['exchange_integral']*1 ;
    /* 更新：清空购物车中所有积分商品 */
    include_once(ROOT_PATH . 'includes/lib_order.php');
    clear_cart(CART_EXCHANGE_GOODS);

    /* 更新：加入购物车 */
    $number = $_POST['number'];
    $cart = array(
        'user_id'        => $_SESSION['user_id'],
        'session_id'     => SESS_ID,
        'goods_id'       => $goods['goods_id'],
        'product_id'     => $product_info['product_id'],
        'goods_sn'       => addslashes($goods['goods_sn']),
        'goods_name'     => addslashes($goods['goods_name']),
        'market_price'   => $goods['market_price'],
        // 'goods_price'    => 0,
        'goods_price'    => $attr_price,
        'goods_number'   => $number,
        'goods_attr'     => addslashes($goods_attr),
        'goods_attr_id'  => $specs,
        'is_real'        => $goods['is_real'],
        'extension_code' => addslashes($goods['extension_code']),
        'parent_id'      => 0,
        'rec_type'       => CART_EXCHANGE_GOODS,
        'is_gift'        => 0
    );
    $db->autoExecute($ecs->table('cart'), $cart, 'INSERT');
    $_SESSION['sel_cartgoods'] = $db->insert_id();
    /* 记录购物流程类型：团购 */
    // print_r(CART_EXCHANGE_GOODS);exit;
    $_SESSION['flow_type'] = CART_EXCHANGE_GOODS;
    $_SESSION['extension_code'] = 'exchange_goods';
    $_SESSION['extension_id'] = $goods_id;
    $_SESSION['supplier_id'] = $_POST['supplier_id'];

    //支付方式
    $_SESSION['payment'] = $_POST['payment'];

    //发放序列号
    //每次发放之前，先把已发放的取出来，unset掉
//    $num = $db->getAll("SELECT extension_num FROM " . $ecs->table('order_info') . " WHERE extension_code='exchange_goods' AND extension_id='$goods_id' AND order_status != 2 AND is_lucky = 0");
//    foreach ($num as $k => $v) {
//        $r .= $v['extension_num'].',';
//    }
//    $str = rtrim($r,',');
//    $ces = explode(',', $str);
//    for ($i=1; $i <29 ; $i++) {
//        if (!in_array($i,$ces)) {
//            $rt .= $i.',';
//        }
//    }
//    //wj start
//    //改成随机发放抽奖码
//
//    $reslut = explode(',',rtrim($rt,','));
//    shuffle($reslut);//打乱数组
//
//
//    foreach ($reslut as $k => $v) {
//        if ($k < $number*1) {
//            $kk .= $v.',';
//        }
//
//    }
//    $_SESSION['extension_num'] = rtrim($kk,',');
    // print_r($_SESSION['extension_num']);exit;

    //wj end
    //49 start
    $_SESSION['extension_num']='';
    //购买量
    $_SESSION['num'] = $number ;
    /* 进入收货人页面 */
    ecs_header("Location: ./flow.php?step=done1\n");
    exit;
}


/*已选属性*/
elseif ($_REQUEST['act'] == 'attr')
{
    $attr_id    = $_POST['attr'];
    foreach ($attr_id as $k => $v) {
        $k = $GLOBALS['db']->getOne('SELECT attr_value FROM ' . $GLOBALS['ecs']->table('goods_attr') .
            " WHERE goods_attr_id = '$v'");
        $s .= '<em>"'.$k.'"</em>' ;
    }
    $value = $s ;
    echo $value;exit;
}

/*属性价格*/
elseif ($_REQUEST['act'] == 'price')
{
    $attr_id    = $_POST['attr'];
    foreach ($attr_id as $k => $v) {
        $k = $GLOBALS['db']->getOne('SELECT attr_price FROM ' . $GLOBALS['ecs']->table('goods_attr') .
            " WHERE goods_attr_id = '$v'");
        $s += $k ;
    }

    $price = round($s *0.05,1) ;

    echo $price;exit;
}

//wj start
/*------------------------------------------------------ */
//-- 积分兑换商品详情
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'vv')
{
    $goods_id = isset($_REQUEST['id'])  ? intval($_REQUEST['id']) : 0;
    /* 获得商品的信息 */
    $goods = get_exchange_goods_info($goods_id);
    $properties = get_goods_properties($goods_id);  // 获得商品的规格和属性

    $str1 = '<div class="pic"><img src="'.$goods['goods_img'].'"></div>
          <div class="tit">
            <h2>￥<em id="price">'.$goods['exchange_integral'].'</em></h2>
            <p>剩余单位：<em>'.$goods['exchange_number'].'</em>件</p>';
    if ($properties['spe']) {
        $str1.='<span>已选：<span id="xuan"></span></span>';
    }
    $str1.=' </div>
          ';


    $arr["goods"]=$str1;




    if ($properties['spe']) {
        $i = 0 ;
        foreach ($properties['spe'] as $k => $spec) {
            $str2  .='<div class="pick_con ys">

        <h2>'.$spec['name'].'：</h2>
        <ul class="clearfix">';
            foreach ($spec['values'] as $k1 => $value) {
                $str2  .='<li  onclick="changeAtt(this,'.$value['id'].')" href="javascript:;" name="'.$value['id'].'">'.$value['label'].'</li>
             <input style="display:none" id="spec_value_'.$value['id'].'" type="radio" name="spec_'.$i.'" value="'.$value['id'].'"  />';
            }
            $str2  .= '</ul>
      </div>';
            $i++;
        }
    }
    $arr["guige1"]=$str2;

    $specification_length = count($properties['spe']);                // 商品规格长度
    $arr["specification_length"]=$specification_length;


    $str3 = '
   <div class="gou clearfix">
      <h2>购买数量 <span>（最多可购买'.$goods['exchange_number'].'个单位）</span></h2>
      <div class="asub clearfix">
        <span onclick="goods_cut();" class="low"></span>
        <input value="1" type="number" id="number" name="number" onblur="uu(this.value)">
        <span onclick="goods_add();" class="rise"></span>
      </div>
    </div>
    <div class="pick_b clearfix">
      <span>共<em id="num">1</em>个单位</span>
      <p>支付：<i>￥</i><em id="price1">'.$goods['exchange_integral'].'</em></p>';
    if ($goods['exchange_number']>0) {
        $str3 .=  '<a class="part" onclick="submit_btn()">参加抽奖</a>';
    }else{
        $str3 .= '<span class="part" style="background: #ccc;color: #fff">参加人数已满</span>';
    }

    $str3 .='</div>';
    $arr["str3"]=$str3;


    $str4 = $goods['exchange_integral'];                // 价格
    $arr["pp"]=$str4;

    $str5 = $goods['exchange_number'];                // 数量
    $arr["nn"]=$str5;

    $str6 = $goods['goods_id'];                // ID
    $arr["ii"]=$str6;

    $str=json_encode($arr);//把json格式变成字符串
    print_r($str);exit;




}

//wj end


/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */

/**
 * 获得分类的信息
 *
 * @param   integer $cat_id
 *
 * @return  void
 */
function get_cat_info($cat_id)
{
    return $GLOBALS['db']->getRow('SELECT keywords, cat_desc, style, grade, filter_attr, parent_id FROM ' . $GLOBALS['ecs']->table('category') .
        " WHERE cat_id = '$cat_id'");
}

/**
 * 获得分类下的商品
 *
 * @access  public
 * @param   string  $children
 * @return  array
 */
function exchange_get_goods($children, $min, $max, $ext, $size, $page, $sort, $order)
{
    $display = $GLOBALS['display'];
    $where = "eg.is_exchange = 1 AND g.is_delete = 0 AND ".
        "($children OR " . get_extension_goods($children) . ')';

    if ($min > 0)
    {
        $where .= " AND eg.exchange_integral >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND eg.exchange_integral <= $max ";
    }

    /* 获得商品列表 */
    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, eg.exchange_integral, ' .
        'g.goods_type, g.goods_brief, g.goods_thumb , g.goods_img, eg.is_hot ' .
        'FROM ' . $GLOBALS['ecs']->table('exchange_goods') . ' AS eg, ' .$GLOBALS['ecs']->table('goods') . ' AS g ' .
        "WHERE eg.goods_id = g.goods_id AND $where $ext ORDER BY $sort $order";
    $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        /* 处理商品水印图片 */
        $watermark_img = '';

//        if ($row['is_new'] != 0)
//        {
//            $watermark_img = "watermark_new_small";
//        }
//        elseif ($row['is_best'] != 0)
//        {
//            $watermark_img = "watermark_best_small";
//        }
//        else
        if ($row['is_hot'] != 0)
        {
            $watermark_img = 'watermark_hot_small';
        }

        if ($watermark_img != '')
        {
            $arr[$row['goods_id']]['watermark_img'] =  $watermark_img;
        }

        $arr[$row['goods_id']]['goods_id']          = $row['goods_id'];
        if($display == 'grid')
        {
            $arr[$row['goods_id']]['goods_name']    = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        }
        else
        {
            $arr[$row['goods_id']]['goods_name']    = $row['goods_name'];
        }
        $arr[$row['goods_id']]['name']              = $row['goods_name'];
        $arr[$row['goods_id']]['goods_brief']       = $row['goods_brief'];
        $arr[$row['goods_id']]['goods_style_name']  = add_style($row['goods_name'],$row['goods_name_style']);
        $arr[$row['goods_id']]['exchange_integral'] = $row['exchange_integral'];
        $arr[$row['goods_id']]['type']              = $row['goods_type'];
        $arr[$row['goods_id']]['goods_thumb']       = get_pc_url().'/'. get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']         = get_pc_url().'/'. get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['url']               = build_uri('exchange_goods', array('gid'=>$row['goods_id']), $row['goods_name']);
    }

    return $arr;
}

/**
 * 获得分类下的商品总数
 *
 * @access  public
 * @param   string     $cat_id
 * @return  integer
 */
function get_exchange_goods_count($children, $min = 0, $max = 0, $ext='')
{
    $where  = "eg.is_exchange = 1 AND g.is_delete = 0 AND ($children OR " . get_extension_goods($children) . ')';


    if ($min > 0)
    {
        $where .= " AND eg.exchange_integral >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND eg.exchange_integral <= $max ";
    }

    $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('exchange_goods') . ' AS eg, ' .
        $GLOBALS['ecs']->table('goods') . " AS g WHERE eg.goods_id = g.goods_id AND $where $ext";

    /* 返回商品总数 */
    return $GLOBALS['db']->getOne($sql);
}

/**
 * 获得指定分类下的推荐商品
 *
 * @access  public
 * @param   string      $type       推荐类型，可以是 best, new, hot, promote
 * @param   string      $cats       分类的ID
 * @param   integer     $min        商品积分下限
 * @param   integer     $max        商品积分上限
 * @param   string      $ext        商品扩展查询
 * @return  array
 */
function get_exchange_recommend_goods($type = '', $cats = '', $min =0,  $max = 0, $ext='')
{
    $price_where = ($min > 0) ? " AND g.shop_price >= $min " : '';
    $price_where .= ($max > 0) ? " AND g.shop_price <= $max " : '';

    $sql =  'SELECT g.goods_id, g.goods_name, g.goods_name_style, eg.exchange_integral, ' .
        'g.goods_brief, g.goods_thumb, goods_img, b.brand_name ' .
        'FROM ' . $GLOBALS['ecs']->table('exchange_goods') . ' AS eg ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = eg.goods_id ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
        'WHERE eg.is_exchange = 1 AND g.is_delete = 0 ' . $price_where . $ext;
    $num = 0;
    $type2lib = array('best'=>'exchange_best', 'new'=>'exchange_new', 'hot'=>'exchange_hot');
    $num = get_library_number($type2lib[$type], 'exchange_list');

    switch ($type)
    {
        case 'best':
            $sql .= ' AND eg.is_best = 1';
            break;
        case 'new':
            $sql .= ' AND eg.is_new = 1';
            break;
        case 'hot':
            $sql .= ' AND eg.is_hot = 1';
            break;
    }

    if (!empty($cats))
    {
        $sql .= " AND (" . $cats . " OR " . get_extension_goods($cats) .")";
    }
    $order_type = $GLOBALS['_CFG']['recommend_order'];
    $sql .= ($order_type == 0) ? ' ORDER BY g.sort_order, g.last_update DESC' : ' ORDER BY RAND()';
    $res = $GLOBALS['db']->selectLimit($sql, $num);

    $idx = 0;
    $goods = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $goods[$idx]['id']                = $row['goods_id'];
        $goods[$idx]['name']              = $row['goods_name'];
        $goods[$idx]['brief']             = $row['goods_brief'];
        $goods[$idx]['brand_name']        = $row['brand_name'];
        $goods[$idx]['short_name']        = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
            sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        $goods[$idx]['exchange_integral'] = $row['exchange_integral'];
        $goods[$idx]['thumb']             = get_pc_url().'/'. get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $goods[$idx]['goods_img']         = get_pc_url().'/'. get_image_path($row['goods_id'], $row['goods_img']);
        $goods[$idx]['url']               = build_uri('exchange_goods', array('gid' => $row['goods_id']), $row['goods_name']);

        $goods[$idx]['short_style_name']  = add_style($goods[$idx]['short_name'], $row['goods_name_style']);
        $idx++;
    }

    return $goods;
}

/**
 * 获得积分兑换商品的详细信息
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  void
 */
function get_exchange_goods_info($goods_id)
{
    $time = gmtime();
    $sql = 'SELECT g.*, c.measure_unit, b.brand_id, b.brand_name AS goods_brand, eg.exchange_integral,eg.exchange_number, eg.is_exchange,eg.exchange_lucky ' .
        'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('exchange_goods') . ' AS eg ON g.goods_id = eg.goods_id ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('category') . ' AS c ON g.cat_id = c.cat_id ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON g.brand_id = b.brand_id ' .
        "WHERE g.goods_id = '$goods_id' AND g.is_delete = 0 " .
        'GROUP BY g.goods_id';

    $row = $GLOBALS['db']->getRow($sql);

    if ($row !== false)
    {
        /* 处理商品水印图片 */
        $watermark_img = '';

        if ($row['is_new'] != 0)
        {
            $watermark_img = "watermark_new";
        }
        elseif ($row['is_best'] != 0)
        {
            $watermark_img = "watermark_best";
        }
        elseif ($row['is_hot'] != 0)
        {
            $watermark_img = 'watermark_hot';
        }

        if ($watermark_img != '')
        {
            $row['watermark_img'] =  $watermark_img;
        }

        /* 修正重量显示 */
        $row['goods_weight']  = (intval($row['goods_weight']) > 0) ?
            $row['goods_weight'] . $GLOBALS['_LANG']['kilogram'] :
            ($row['goods_weight'] * 1000) . $GLOBALS['_LANG']['gram'];

        /* 修正上架时间显示 */
        $row['add_time']      = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']);

        /* 修正商品图片 */
        $row['goods_img']   = get_pc_url().'/' . get_image_path($goods_id, $row['goods_img']);
        $row['goods_thumb'] = get_pc_url().'/' . get_image_path($goods_id, $row['goods_thumb'], true);

        /*修正剩下数量*/
        $row['exchange_number'] = 28*1 - $row['exchange_number'];

        return $row;
    }
    else
    {
        return false;
    }
}

function get_wap_advlist( $position, $num )
{
    $arr = array( );
    $sql = "select ap.ad_width,ap.ad_height,ad.ad_id,ad.ad_name,ad.ad_code,ad.ad_link,ad.ad_id from ".$GLOBALS['ecs']->table( "ecsmart_ad_position" )." as ap left join ".$GLOBALS['ecs']->table( "ecsmart_ad" )." as ad on ad.position_id = ap.position_id where ap.position_name='".$position.( "' and UNIX_TIMESTAMP()>ad.start_time and UNIX_TIMESTAMP()<ad.end_time and ad.enabled=1 limit ".$num );
    $res = $GLOBALS['db']->getAll( $sql );
    foreach ( $res as $idx => $row )
    {
        $arr[$row['ad_id']]['name'] = $row['ad_name'];
        $arr[$row['ad_id']]['url'] = "affiche.php?ad_id=".$row['ad_id']."&uri=".$row['ad_link'];
        $arr[$row['ad_id']]['image'] = "data/afficheimg/".$row['ad_code'];
        $arr[$row['ad_id']]['content'] = "<a href='".$arr[$row['ad_id']]['url']."' target='_blank'><img src='data/afficheimg/".$row['ad_code']."' width='".$row['ad_width']."' height='".$row['ad_height']."' /></a>";
        $arr[$row['ad_id']]['ad_code'] = $row['ad_code'];
    }
    return $arr;
}

/**
 * 滚动加载数据
 * @param type $limit
 * @return array
 */
function get_exchange_goods_list($limit){
    /* 初始化分页信息 */
    $cat_id       = isset($_REQUEST['cat_id']) && intval($_REQUEST['cat_id']) > 0 ? intval($_REQUEST['cat_id']) : 0;
    $integral_max = isset($_REQUEST['integral_max']) && intval($_REQUEST['integral_max']) > 0 ? intval($_REQUEST['integral_max']) : 0;
    $integral_min = isset($_REQUEST['integral_min']) && intval($_REQUEST['integral_min']) > 0 ? intval($_REQUEST['integral_min']) : 0;

    /* 排序、显示方式以及类型 */
    $default_display_type      = $_CFG['show_order_type'] == '0' ? 'list' : ($_CFG['show_order_type'] == '1' ? 'grid' : 'text');
    $default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'DESC' : 'ASC';
    $default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'goods_id' : ($_CFG['sort_order_type'] == '1' ? 'exchange_integral' : 'last_update');

    $sort    = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'exchange_integral', 'last_update','click_count'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;
    $order   = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC')))                              ? trim($_REQUEST['order']) : $default_sort_order_method;
    $display = (isset($_REQUEST['display']) && in_array(trim(strtolower($_REQUEST['display'])), array('list', 'grid', 'text'))) ? trim($_REQUEST['display'])  : (isset($_COOKIE['ECS']['display']) ? $_COOKIE['ECS']['display'] : $default_display_type);
    $display  = in_array($display, array('list', 'grid', 'text')) ? $display : 'text';
    setcookie('ECS[display]', $display, gmtime() + 86400 * 7);

    //2017.12.12 抽奖中心除去当前产品再获取列表
    $goods_id       = isset($_REQUEST['goods_id']) && intval($_REQUEST['goods_id']) > 0 ? intval($_REQUEST['goods_id']) : '';
    // print_r($goods_id);exit;

    /* 如果页面没有被缓存则重新获取页面的内容 */
    $children = get_children($cat_id);

    $ext = ''; //商品查询条件扩展

    $goodslist = ajax_exchange_get_goods($children, $integral_min, $integral_max, $ext,$limit, $sort,$goods_id, $order);
    if($display == 'grid')
    {
        if(count($goodslist) % 2 != 0)
        {
            $goodslist[] = array();
        }
    }
    return $goodslist;
}

function ajax_exchange_get_goods($children, $min, $max, $ext, $limit, $sort,$goods_id, $order)
{
    $display = $GLOBALS['display'];
    $where = "eg.is_exchange = 1 AND g.is_delete = 0 AND g.is_on_sale = 1 AND ".
        "($children OR " . get_extension_goods($children) . ')';

    if ($min > 0)
    {
        $where .= " AND eg.exchange_integral >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND eg.exchange_integral <= $max ";
    }

    if ($goods_id) {
        $where .= " AND eg.goods_id != $goods_id ";
    }

    /* 获得商品列表 */
    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, eg.exchange_integral,eg.exchange_number, ' .
        'g.goods_type, g.goods_brief, g.goods_thumb , g.goods_img, eg.is_hot ' .
        'FROM ' . $GLOBALS['ecs']->table('exchange_goods') . ' AS eg, ' .$GLOBALS['ecs']->table('goods') . ' AS g ' .
        "WHERE eg.goods_id = g.goods_id AND $where $ext ORDER BY $sort $order";
    $sql .= " $limit";
    // print_r($sql);exit;
    $res = $GLOBALS['db']->query($sql);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $watermark_img = '';
        if ($row['is_hot'] != 0)
        {
            $watermark_img = 'watermark_hot_small';
        }

        if ($watermark_img != '')
        {
            $arr[$row['goods_id']]['watermark_img'] =  $watermark_img;
        }

        $arr[$row['goods_id']]['goods_id']          = $row['goods_id'];
        if($display == 'grid')
        {
            $arr[$row['goods_id']]['goods_name']    = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        }
        else
        {
            $arr[$row['goods_id']]['goods_name']    = $row['goods_name'];
        }
        $arr[$row['goods_id']]['name']              = $row['goods_name'];
        $arr[$row['goods_id']]['exchange_number']   = $row['exchange_number'];
        $arr[$row['goods_id']]['goods_brief']       = $row['goods_brief'];
        $arr[$row['goods_id']]['goods_style_name']  = add_style($row['goods_name'],$row['goods_name_style']);
        $arr[$row['goods_id']]['exchange_integral'] = $row['exchange_integral'];
        $arr[$row['goods_id']]['type']              = $row['goods_type'];
        $arr[$row['goods_id']]['goods_thumb']       = get_pc_url().'/' . get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']         = get_pc_url().'/' . get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['url']               = build_uri('exchange_goods', array('gid'=>$row['goods_id']), $row['goods_name']);
    }

    return $arr;
}
?>
