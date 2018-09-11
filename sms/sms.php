<?php

//error_reporting(0);

//session_start();



header("Content-type:text/html; charset=UTF-8");



function random ($length = 6, $numeric = 0)

{

	PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);

	if($numeric)

	{

		$hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));

	}

	else

	{

		$hash = '';

		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';

		$max = strlen($chars) - 1;

		for($i = 0; $i < $length; $i ++)

		{

			$hash .= $chars[mt_rand(0, $max)];

		}

	}

	return $hash;

}



function read_file ($file_name)

{

	$content = '';

	$filename = date('Ymd') . '/' . $file_name . '.log';

	if(function_exists('file_get_contents'))

	{

		@$content = file_get_contents($filename);

	}

	else

	{

		if(@$fp = fopen($filename, 'r'))

		{

			@$content = fread($fp, filesize($filename));

			@fclose($fp);

		}

	}

	$content = explode("\r\n",$content);

	return end($content);

}



if($_GET['act'] == 'check')

{

	$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';

	$mobile_code = isset($_POST['mobile_code']) ? trim($_POST['mobile_code']) : '';



	if(time() - $_SESSION['time'] > 30 * 60)

	{

		unset($_SESSION['mobile_code']);

		exit(json_encode(array(

			'msg' => '验证码超过30分钟。'

		)));

	}

	else

	{

		if($mobile != $_SESSION['mobile'] or $mobile_code != $_SESSION['mobile_code'])

		{

			exit(json_encode(array(

				'msg' => '手机验证码输入错误。'

			)));

		}

		else

		{

			exit(json_encode(array(

				'code' => '2'

			)));

		}

	}



}



if($_GET['act'] == 'send')

{



	$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';

	$mobile_code = isset($_POST['mobile_code']) ? trim($_POST['mobile_code']) : '';



	//session_start();

	if(empty($mobile))

	{

		exit(json_encode(array(

			'msg' => '手机号码不能为空'

		)));

	}



	$preg = '/^1[0-9]{10}$/'; // 简单的方法

	if(! preg_match($preg, $mobile))

	{

		exit(json_encode(array(

			'msg' => '手机号码格式不正确'

		)));

	}



	$mobile_code = random(6, 1);



	$content = sprintf($GLOBALS['_CFG']['sms_register_tpl'],$mobile_code,$GLOBALS['_CFG']['sms_sign']);





	if($_SESSION['mobile'])

	{

		if(strtotime(read_file($mobile)) > (time() - 60))

		{

			exit(json_encode(array(

				'msg' => '获取验证码太过频繁，一分钟之内只能获取一次。'

			)));

		}

	}



	$num = sendSMS($mobile, $content);

	if($num == true)

	{

		$_SESSION['mobile'] = $mobile;

		$_SESSION['mobile_code'] = $mobile_code;

		$_SESSION['time'] = time();

		exit(json_encode(array(

			'code' => 2

		)));

	}

	else

	{

		exit(json_encode(array(

			'msg' => '手机验证码发送失败。'

		)));

	}

}



function sendSMS ($mobile, $content) {
	
	include_once (ROOT_PATH . 'includes/SendSmsHttp.class.php');

	$sendSms = new SendSmsHttp();

	$sendSms->SpCode = '244257';

	$sendSms->LoginName = 'admin2';

	$sendSms->Password = 'xgx260';

	$sendSms->MessageContent = $content;

	$sendSms->UserNumber = $mobile;

	$sendSms->SerialNumber = '';

	$sendSms->ScheduleTime = '';

	$sendSms->ExtendAccessNum = '';

	$sendSms->f = '';

	$res = $sendSms->send();

	if ($res) {

		return true;

	} else {

		return false;

	}

}





function GetTimeString()

{

	date_default_timezone_set('Asia/Shanghai');

	$timestamp=time();

	$hours = date('H',$timestamp);

	$minutes = date('i',$timestamp);

	$seconds =date('s',$timestamp);

	$month = date('m',$timestamp);

	$day =  date('d',$timestamp);

	$stamp= $month.$day.$hours.$minutes.$seconds;

	return $stamp;

}



function postSMS ($url, $data = '')

{

	$row = parse_url($url);

	$host = $row['host'];

	$port = $row['port'] ? $row['port'] : 80;

	$file = $row['path'];

	while(list($k, $v) = each($data))

	{

		$post .= rawurlencode($k) . "=" . rawurlencode($v) . "&"; // 转URL标准码

	}

	$post = substr($post, 0, - 1);

	$len = strlen($post);

	$fp = @fsockopen($host, $port, $errno, $errstr, 10);

	if(! $fp)

	{

		return "$errstr ($errno)\n";

	}

	else

	{

		$receive = '';

		$out = "POST $file HTTP/1.1\r\n";

		$out .= "Host: $host\r\n";

		$out .= "Content-type: application/x-www-form-urlencoded\r\n";

		$out .= "Connection: Close\r\n";

		$out .= "Content-Length: $len\r\n\r\n";

		$out .= $post;

		fwrite($fp, $out);

		while(! feof($fp))

		{

			$receive .= fgets($fp, 128);

		}

		fclose($fp);

		$receive = explode("\r\n\r\n", $receive);

		unset($receive[0]);

		return implode("", $receive);

	}

}



function checkSMS ($mobile, $mobile_code)

{

	$arr = array(

		'error' => 0,'msg' => ''

	);

	if(time() - $_SESSION['time'] > 30 * 60)

	{

		unset($_SESSION['mobile_code']);

		$arr['error'] = 1;

		$arr['msg'] = '验证码超过30分钟。';

	}

	else

	{

		if($mobile != $_SESSION['mobile'] or $mobile_code != $_SESSION['mobile_code'])

		{

			$arr['error'] = 1;

			$arr['msg'] = '手机验证码输入错误。';

		}

		else

		{

			$arr['error'] = 2;

		}

	}

	return $arr;

}

?>
