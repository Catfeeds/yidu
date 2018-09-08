import java.io.UnsupportedEncodingException;
import org.apache.commons.httpclient.Header;
import org.apache.commons.httpclient.HttpClient;
import org.apache.commons.httpclient.NameValuePair;
import org.apache.commons.httpclient.methods.PostMethod;

//全文模板发送
public class SendMsg{
	public static void main(String[] args)throws Exception
	{
		HttpClient client = new HttpClient();
		PostMethod post = new PostMethod("http://http.yunsms.cn/tx/"); 
		post.addRequestHeader("Content-Type","application/x-www-form-urlencoded;charset=gbk");//在头文件中设置转码
		NameValuePair[] data ={new NameValuePair("uid", "数字用户名"),new NameValuePair("pwd", "加密密码"),new NameValuePair("mobile","手机号码"),new NameValuePair("content","验证码：8888【快信】")};
		post.setRequestBody(data);

		client.executeMethod(post);
		Header[] headers = post.getResponseHeaders();
		int statusCode = post.getStatusCode();
		System.out.println("statusCode:"+statusCode);
		for(Header h : headers)
		{
			System.out.println(h.toString());
		}
		String result = new String(post.getResponseBodyAsString().getBytes("gbk")); 
		System.out.println(result); //打印返回消息状态
		post.releaseConnection();

	}
}
