<?php

/**
 * ECSHOP 公用函数库
 * ============================================================================
 * * 版权所有 2008-2015 广州市互诺计算机科技有限公司，并保留所有权利。
 * 网站地址: http://www.hunuo.com;
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: derek $
 * $Id: lib_common.php 17217 2011-01-19 06:29:08Z derek $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

function get_pc_url(){

   $db = $GLOBALS['db'];
   $ecs= $GLOBALS['ecs'];

   $sql = "select value from ".$ecs->table('ecsmart_shop_config')." where code = 'pc_url' ";
   $res = $db->getOne($sql);
   if(!$res){
       $res = './..';
   }
   return $res;
}

function selled_wap_count($goods_id)
{
     $sql= "select sum(goods_number) as count from ".$GLOBALS['ecs']->table('order_goods')."where goods_id ='".$goods_id."'";
     $res = $GLOBALS['db']->getOne($sql);
     if($res>0)
     {
     return $res;
     }
     else
     {
       return('0');
     }
}

function get_evaluation_sum($goods_id)
{
$sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('comment') . " WHERE status=1 and  comment_type =0 and id_value =".$goods_id ;//status=1表示通过了的评论才算  comment_type =0表示针对商品的评价
    return $GLOBALS['db']->getOne($sql);
}

/**
 * 是否为手机专享价
 * @param type $exclusive
 * @param type $finalPrice
 * @return boolean
 */
function is_exclusive($exclusive = 0,$finalPrice= 0){
    if($exclusive>0 && $exclusive <= $finalPrice){
        return true;
    }else{
        return false;
    }
}


