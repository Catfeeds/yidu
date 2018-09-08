'说明:	http://http.yunsms.cn/tx/?uid=数字用户名&pwd=MD5位32密码&mobile=号码&content=内容
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
'	112	有错误号码
'	113	定时时间不对
'	114	账号被锁，10分钟后登录
'	115	连接失败
'	116 禁止接口发送
'	117	绑定IP不正确
'	120 系统升级
'------------------------------------------------
Module SendEsms

    Sub Main()
        Dim strContent As String = "你好，验证码：1019【云短信】"
		'数字用户名
		Dim uid As String = "11111"
		'密码 MD5 32位
		Dim pass As String = "fa246d0262c3925617b0c72bb20eeb1d"
		'发送号码
		Dim mobile As String = "13585513344,13900008888"
		'GET 方式
        Dim getReturn As String = doGetRequest("http://http.yunsms.cn/tx/?uid="+uid+"&pwd="+pass+"&mobile="+mobile+"&content=" + strContent)
        Console.WriteLine("Get response is: " & getReturn)

		'POST 方式
        Dim sbTemp As System.Text.StringBuilder = New System.Text.StringBuilder()
        sbTemp.Append("uid=9999&pwd=fa246d0262c3925617b0c72bb20eeb1d&mobile=13585513344,13900008888&content=" + strContent)
        Dim bTemp() As Byte = System.Text.Encoding.GetEncoding("GBK").GetBytes(sbTemp.ToString())
        Dim postReturn As String = doPostRequest("http://http.yunsms.cn/tx/", bTemp)
        Console.WriteLine("Post response is: " + postReturn)


    End Sub

    '发送HTTP GET请求得结果
    Private Function doGetRequest(ByVal url As String) As String
        Dim strReturn As String = ""
        Dim hwRequest As System.Net.HttpWebRequest
        Dim hwResponse As System.Net.HttpWebResponse
        Try
            hwRequest = System.Net.HttpWebRequest.Create(url)
            hwRequest.Timeout = 5000
            hwRequest.Method = "GET"
            hwRequest.ContentType = "application/x-www-form-urlencoded"

            hwResponse = hwRequest.GetResponse()
            Dim srReader As System.IO.StreamReader = New System.IO.StreamReader(hwResponse.GetResponseStream(), System.Text.Encoding.ASCII)
            strReturn = srReader.ReadToEnd()
            srReader.Close()
            hwResponse.Close()

        Catch
        End Try
        Return strReturn
    End Function

    '发送HTTP POST请求得结果
    Private Function doPostRequest(ByVal url As String, ByVal bData() As Byte) As String
        Dim strReturn As String = ""
        Dim hwRequest As System.Net.HttpWebRequest
        Dim hwResponse As System.Net.HttpWebResponse
        Try
            hwRequest = System.Net.HttpWebRequest.Create(url)
            hwRequest.Timeout = 5000
            hwRequest.Method = "POST"
            hwRequest.ContentType = "application/x-www-form-urlencoded"
            hwRequest.ContentLength = bData.Length

            Dim smWrite As System.IO.Stream = hwRequest.GetRequestStream()
            smWrite.Write(bData, 0, bData.Length)
            smWrite.Close()

            hwResponse = hwRequest.GetResponse()
            Dim srReader As System.IO.StreamReader = New System.IO.StreamReader(hwResponse.GetResponseStream(), System.Text.Encoding.ASCII)
            strReturn = srReader.ReadToEnd()
            srReader.Close()
            hwResponse.Close()
        Catch

        End Try
        Return strReturn
    End Function

End Module