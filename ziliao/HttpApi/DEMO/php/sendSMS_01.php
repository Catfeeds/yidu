<?php
/*--------------------------------
功能:HTTP接口 发送短信
说明:		http://http.yunsms.cn/tx/?uid=数字用户名&pwd=MD5位32密码&mobile=号码&content=内容
状态:
	100 发送成功
	101 验证失败
	102 短信不足
	103 操作失败
	104 非法字符
	105 内容过多
	106 号码过多
	107 频率过快
	108 号码内容空
	109 账号冻结
	110 禁止频繁单条发送
	111 系统暂定发送
	112	有错误号码
	113	定时时间不对
	114	账号被锁，10分钟后登录
	115	连接失败
	116 禁止接口发送
	117	绑定IP不正确
	120 系统升级
--------------------------------*/
$uid = '88888';		//数字用户名
$pwd = '8fsd888';		//密码
$mobile	 = '13856977472';	//号码
$content = '你好，你的短信验证码为：6666，请在1分钟内验证。【云曼】';		//内容
//即时发送
$res = sendSMS($uid,$pwd,$mobile,$content);
echo $res;

//定时发送
/*
$time = '2010-05-27 12:11';
$res = sendSMS($uid,$pwd,$mobile,$content,$time);
echo $res;
*/
function sendSMS($uid,$pwd,$mobile,$content,$time='',$mid='')
{
	$http = 'http://http.yunsms.cn/tx/';
	$data = array
		(
		'uid'=>$uid,					//数字用户名
		'pwd'=>strtolower(md5($pwd)),	//MD5位32密码
		'mobile'=>$mobile,				//号码
		'content'=>$content,			//内容
		'content'=>iconv('utf-8','gbk',$content), //如果是utf-8编码，则需转码iconv('utf-8','gbk',$content); 如果是gbk则无需转码
		'time'=>$time,		//定时发送
		'mid'=>$mid						//子扩展号
		);
	$re= postSMS($http,$data);			//POST方式提交
	
	 
	if( trim($re) == '100' )
	{
		return "发送成功!";
	}
	else 
	{
		return "发送失败! 状态：".$re;
	}
}

function postSMS($url,$data='')
{
	$post='';
	$row = parse_url($url);
	$host = $row['host'];
	$port = $row['port'] ? $row['port']:80;
	$file = $row['path'];
	while (list($k,$v) = each($data)) 
	{
		$post .= rawurlencode($k)."=".rawurlencode($v)."&";	//转URL标准码
	}
	$post = substr( $post , 0 , -1 );
	$len = strlen($post);
	
	//$fp = @fsockopen( $host ,$port, $errno, $errstr, 10);
	 
	$fp = stream_socket_client("tcp://".$host.":".$port, $errno, $errstr, 10);
	
	
	
	
	if (!$fp) {
		return "$errstr ($errno)\n";
	} else {
		$receive = '';
		$out = "POST $file HTTP/1.0\r\n";
		$out .= "Host: $host\r\n";
		$out .= "Content-type: application/x-www-form-urlencoded\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "Content-Length: $len\r\n\r\n";
		$out .= $post;		
		fwrite($fp, $out);
		while (!feof($fp)) {
			$receive .= fgets($fp, 128);
		}
		fclose($fp);
		$receive = explode("\r\n\r\n",$receive);
		unset($receive[0]);
		return implode("",$receive);
	}
}
?>