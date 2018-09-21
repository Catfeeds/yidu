<?php 
/**
 * 站点信息发送
 * @param  string $title   [description]
 * @param  string $content [description]
 * @param  string $user_id [description]
 * @return [type]          [description]
 */
function mail_add($title='',$content='',$user_id=''){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$sql = "INSERT INTO " .$ecs->table('message'). " (admin_id,user_id, title, short, " .
	                "content, type,add_time) ".
	            "VALUES ('1','0','$title','','$content','0','".gmtime()."')";
	$db->query($sql);
	$insert_id = mysql_insert_id();

	$sql = "INSERT INTO " .$ecs->table('message_log') . " (msg_id,user_id,add_time) " . " VALUES ($insert_id,$user_id,".gmtime().") ";
    $db->query($sql);
}


 ?>