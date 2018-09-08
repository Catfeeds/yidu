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
	<title>祝贺您！支付已成功！</title>
</head>
<body>
<div id='page'>
	<div style="text-align:center;color:#e54d30;font-size:14px;font-weight:normal; line-height:150%;">
		<br />
		<br />
		<br />
		<font style=" font-size:14px!important;">祝贺您！支付已成功！3秒后自动跳转动商城首页</font>
	</div>
</div>
<script type="text/javascript">
	var url = window.location.host;
	window.setTimeout("window.location='http://'+url+'/mobile/'",3000);
</script>
</body>
</html>