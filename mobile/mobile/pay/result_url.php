<?php
/* *
 * 功能：支付宝页面跳转同步通知页面
 * 版本：3.3
 * 日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

 *************************页面功能说明*************************
 * 该页面可在本机电脑测试
 * 可放入HTML等美化页面的代码、商户业务逻辑程序代码
 * 该页面可以使用PHP开发工具调试，也可以使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyReturn
 */
define('IN_ECS', true);
require('../includes/init.php');
require('../includes/lib_order.php');
require_once("alipay.config.php");
require_once("lib/alipay_notify.class.php");
?>
<!DOCTYPE HTML>
<html>
    <head>
	<meta charset="utf-8">
    <meta name="viewport" content="target-densitydpi=device-dpi, width=device-width, initial-scale=1, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">

	<style type="text/css">
#page{
	width: 98%;
	height: 10em;
	margin:1em auto;

	font-size:1em;
	line-height:1.5em;
}
#page2{
	width: 98%;
	height: 10em;
	margin:1em auto;
	;
	font-size:1em;
	line-height:1.5em;
}
</style>

        <title>支付宝即时到账交易接口</title>

	</head>
    <body>

<?php
//初始化配置
$alipay_config = array(
				"partner" => PARTNER,
				"key" => KEY,
				"private_key_path" => PRIVATE_KEY_PATH,
				"ali_public_key_path" => ALI_PUBLIC_KEY_PATH,
				"sign_type" => SIGN_TYPE,
				"input_charset" => INPUT_CHARSET,
				"cacert" => CACERT,
				"transport" => TRANSPORT
				);
//计算得出通知验证结果

$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $alipayNotify->verifyReturn();

if($verify_result) {//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代码

	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
    //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

	//商户订单号
	$out_trade_no = $_GET['out_trade_no'];

	//支付宝交易号
	$trade_no = $_GET['trade_no'];

	//交易状态
	$result = $_GET['result'];


	//判断该笔订单是否在商户网站中已经做过处理
		//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
		//如果有做过处理，不执行商户的业务程序


	if($result=="success"){

			$d_sql = "SELECT o.extension_id " .
				" FROM " . $GLOBALS['ecs']->table('order_info') . ' as o ' .
				" LEFT JOIN " . $GLOBALS['ecs']->table('pay_log') . 'as p ' .
				" ON o.order_id=p.order_id " .
				" WHERE p.log_id =$out_trade_no and p.is_paid=1 and o.extension_code = 'exchange_goods'";
			$extension_id = $GLOBALS['db']->getOne($d_sql);

			if ($extension_id) {
				//获取订单号
				$sql = "SELECT o.user_id,g.goods_number,o.order_id,o.order_sn " .
					" FROM " . $GLOBALS['ecs']->table('order_info') . ' as o ' .
					" LEFT JOIN " . $GLOBALS['ecs']->table('order_goods') . 'as g ' .
					" ON o.order_id=g.order_id " .
					" WHERE o.extension_id = $extension_id and o.is_lucky = 0 and o.pay_time>0 order by o.pay_time asc ";
				$nnn = $GLOBALS['db']->getAll($sql);
				$total = 0;
				foreach ($nnn as $k => $v) {
					$total += $v['goods_number'];
				}
				// print_r()
				if ($total * 1 == 28) {

					foreach ($nnn as $k => $v) {
						$u[$k]['mobile_phone'] = $GLOBALS['db']->getOne("SELECT mobile_phone FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '" . $v['user_id'] . "'");
						$u[$k]['nn'] = $GLOBALS['db']->getOne("SELECT goods_number FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = '" . $v['order_id'] . "'");
						$u[$k]['oid'] = $v['order_id'];
						$u[$k]['order_sn'] = $v['order_sn'];
					}
					$arr = range(1, 28);
					$mm = '';
					$ll = 0;
					foreach ($u as $k => $v) {
						for ($i = $ll; $i < $ll * 1 + $v['nn'] * 1; $i++) {
							$mm .= $arr[$i] . ',';
						}
						$ll += $v['nn'] * 1;
						$u[$k]['mm'] = rtrim($mm, ',');
						unset($mm);
					}
					
                    require_once(ROOT_PATH . 'sms/sms.php');
                    
					foreach ($u as $k => $v) {
						$sql0 = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . " SET `extension_num`='" . $v['mm'] . "'  WHERE `order_id`='" . $v['oid'] . "'";

						$GLOBALS['db']->query($sql0);
						$content = sprintf('您的订单号%s获得的抽奖码是%s如有疑问，请联系商城客服。',$v['order_sn'], $v['mm']);
						// print_r($content);exit;
						if ($v['mobile_phone']) {

							$kk = sendSMS($v['mobile_phone'], $content);
							
						}
					}
				}
			}

	?>

<div id='page'>
	<div style="text-align:center;color:#e54d30;font-size:14px;font-weight:normal; line-height:150%;">
	<br />
	<br />
	<br />
	<font style=" font-size:14px!important;">祝贺您!您的订单支付已经成功!!!3秒后自动跳转动商城首页</font>

	</div>
</div>

<?php
	}else{
	//支付失败

?>
<div id='page2'>
	<div style="text-align:center;color:#666;font-size:14px;font-weight:normal; line-height:20px;">
	<br />
	<br />
	<br />
	很抱歉,您的订单支付失败!3秒后自动跳转动商城首页
	</div>
</div>
<?php
	}
}
else {
    //验证失败
    //如要调试，请看alipay_notify.php页面的verifyReturn函数

	?>
<div id='page2'>
	<div style="text-align:center;font-weight: bold;font-size:2em;"><span style="color:red;">支付成功！</span><br />
	</div>
</div>
<?php
}
?>
	<script type="text/javascript">
		var url = window.location.host;
		window.setTimeout("window.location='http://'+url+'/mobile/'",3000);
	</script>
    </body>
</html>