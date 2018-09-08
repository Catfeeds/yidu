<!--#include file="md5.asp" -->
<%
'------------------------------------------------
'功能:	云短信网HTTP接口ASP调用说明
'日期:	2009-02-08
'说明:	http://接口地址/tx/?uid=数字用户名&pwd=MD5位32密码&mobile=号码&content=内容
'状态:
'	100 发送成功
'	101 验证失败
'	102 短信不足
'	103 操作失败
'	104 非法字符
'	105 内容过多
'	106 号码过多
'	107 频率过快
'	108 号码内容空
'	109 账号冻结
'	110 禁止频繁单条发送
'	111 系统暂定发送
'	112 号码不正确
'	120 系统升级
'------------------------------------------------
%>
<html>
<head>
<title>云短信网二次开发接口HTTP方式ASP调用演示</title>
</head>
<body>
<%
If request("m")="send" then
	sendsms trim(replace(request("mobile"),"，",",")),trim(request("msg"))
Else
%>
<form name=form1 method=post action="?m=send" onSubmit="if(this.mobile.value==''){alert('输入接收手机号码');this.mobile.focus();return false}">
<table width="90%" border="0" align="center" cellpadding="1" cellspacing="1" bgcolor="#CCCCCC">
  <tr>
    <td width="80" height="30" align="center" bgcolor="#FFFFFF">手机号码：</td>
    <td bgcolor="#FFFFFF"><input name=mobile type=text value="13800138000"></td>
  </tr>
  <tr>
    <td width="80" height="30" align="center" bgcolor="#FFFFFF">发送内容：</td>
    <td bgcolor="#FFFFFF">&nbsp;<textarea name=msg rows=6 style="width:98%">你好，验证码：1019【云短信】</textarea></td>
  </tr>
  <tr>
    <td height="30" colspan="2" align="center" bgcolor="#FFFFFF"><input type=submit value="发送短信" id=submit1 name=submit1></td>
    </tr>
</table>
</form>
<%
End If
%>
</body>
</html>
<%
Sub sendsms(mobile,msg)
'多个手机号之间用“,”分隔
dim userid,password,status
dim xmlObj,httpsendurl
userid = "9999"		'企业ID，请联系我们索取免费测试帐号
password = "9999"	'ID密码，要使用MD5加密为32位密文并转换为小写
password = LCASE(MD5(password))

httpsendurl="http://http.yunsms.cn/tx/?uid="&userid&"&pwd="&password&"&mobile="&mobile&"&content="&server.URLEncode(msg)
Set xmlObj = server.CreateObject("Microsoft.XMLHTTP")
xmlObj.Open "GET",httpsendurl,false
xmlObj.send()
status = xmlObj.responseText
Set xmlObj = nothing
If status = "100" then '发送成功
	Response.Write "<br><br>返回状态码："&status&"&nbsp;&nbsp;&nbsp;发送状态：发送成功！&nbsp;&nbsp;&nbsp; <a href=""javascript:history.back();"">返回发送页面</a>"
Else '发送失败
	Response.Write "<br><br>返回状态码："&status&"&nbsp;&nbsp;&nbsp;发送状态：发送失败！&nbsp;&nbsp;&nbsp;<a href=""javascript:history.back();"">返回发送页面</a>"
End if
End sub
%>
