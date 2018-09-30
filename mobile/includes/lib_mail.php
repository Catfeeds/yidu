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