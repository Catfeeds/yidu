<?php 
/**
 * 站点信息发送
 * @param  string $title   [description]
 * @param  string $content [description]
 * @param  string $user_id [description]
 * @return [type]          [description]
 */
function mail_add($title='',$content='',$user_id='',$url=''){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$sql = "INSERT INTO " .$ecs->table('message'). " (admin_id,user_id, title, short, " .
	                "content, type,add_time) ".
	            "VALUES ('1','0','$title','','$content','0','".gmtime()."')";
	$db->query($sql);
	$insert_id = mysql_insert_id();

	$sql = "INSERT INTO " .$ecs->table('message_log') . " (msg_id,user_id,add_time) " . " VALUES ($insert_id,$user_id,".gmtime().") ";
    $db->query($sql);
    // $data = array(
    // 	'first'=>$title,
    // 	'keyword1'=>$content,
    // 	'keyword2'=>local_date("Y-m-d"),
    // );
    // wxtongzhi($user_id,'t8RK3W-k1k51RLN07ai4bKmPMfkXxAovnhBUConJeM8',$url,$data);
}

// V7wlUdgWBnAqKnTc99LrcEYylwPg2XRpHsqoZ0xAox4
// 开发者调用模版消息接口时需提供模版ID
// 标题开奖结果通知
// 行业IT科技 - 互联网|电子商务
// 详细内容
// {{first.DATA}}
// 项目名称：{{keyword1.DATA}}
// 订单编号：{{keyword2.DATA}}
// 抽奖编号：{{keyword3.DATA}}
// {{remark.DATA}}
// 在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
// 内容示例
// 亲爱的众筹用户，您支持的众筹项目已经开奖，恭喜您中奖！
// 项目名称：欧西尼智能手表 X8
// 订单编号：12345678901234
// 抽奖编号：88888888
// 项目发起人正在兑现承诺中，请您耐心等待~
// 
// 
// 
// bygr_NwuALb7v5xF3Chieg0ijFE8DtebJLErQuUZJ0E
// 开发者调用模版消息接口时需提供模版ID
// 标题发货提醒
// 行业IT科技 - 互联网|电子商务
// 详细内容
// {{first.DATA}}
// 订单号：{{keyword1.DATA}}
// 收货人姓名：{{keyword2.DATA}}
// 收货人手机号：{{keyword3.DATA}}
// 收货人详情地址：{{keyword4.DATA}}
// {{remark.DATA}}
// 在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
// 内容示例
// 店主您好，您有待发货订单(*￣︶￣)
// 订单号：1234589564541
// 收货人姓名：王先森
// 收货人手机号：12344579532
// 收货人详情地址：北京市海尚名都
// 请尽快发货哦(*^▽^*)
// 
// 
// 
// 
// 
// 
// jjRfCsh5Y9OExtDuMB_6kfB5rbZ5hp7JDEvkSE_Zj0U
// 开发者调用模版消息接口时需提供模版ID
// 标题审核结果通知
// 行业IT科技 - 互联网|电子商务
// 详细内容
// {{first.DATA}}
// 姓名：{{keyword1.DATA}}
// 审核结果：{{keyword2.DATA}}
// 审核时间：{{keyword3.DATA}}
// {{remark.DATA}}
// 在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
// 内容示例
// 亲，您提交申请现已处理。
// 姓名：张三
// 审核结果：通过
// 审核时间：2018年4月3日
//  审核通过后可通过公众号进入系统
function wxtongzhi($user_id='',$template_id='',$url='',$data=array()){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$url = PicUrl($url);
	$user_info = $db->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user')." WHERE ecuid = '$user_id'");
	if(!empty($user_info['fake_id'])){
	    //有微信的情况发送微信信息
	    $post_data = array(
	        'touser' => $user_info['fake_id'],
	        'template_id' => $template_id,
	        'url' => $url,
	        'topcolor' => '#000',
	        'data' => $data,
	    );
      	require_once(ROOT_PATH.'mobile/includes/jssdk.php');
	    $jssdk = new JSSDK();
	    $access_token = $jssdk->getAccessToken();
	    if(isset($access_token)){
	        $send_url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
	        $ch_post = curl_init();
	        curl_setopt($ch_post,CURLOPT_URL,$send_url);
	        curl_setopt($ch_post,CURLOPT_HEADER,0);
	        curl_setopt($ch_post, CURLOPT_POST, 1 );
	        curl_setopt($ch_post, CURLOPT_POSTFIELDS, json_encode($post_data));
	        $res = curl_exec($ch_post);
	        curl_close($ch_post);
	        if($res['errcode']==0){
	            return '微信通知成功';
	        }else{
	            return '微信通知失败';
	        }
	    }
	}
}

 ?>