package mlink.esms.api.simpleclient;

import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.util.HashMap;

import org.apache.commons.codec.binary.Hex;
import org.apache.commons.httpclient.HttpClient;
import org.apache.commons.httpclient.HttpException;
import org.apache.commons.httpclient.HttpMethod;
import org.apache.commons.httpclient.HttpStatus;
import org.apache.commons.httpclient.MultiThreadedHttpConnectionManager;
import org.apache.commons.httpclient.methods.GetMethod;
import org.apache.commons.httpclient.methods.PostMethod;

/**
 * Mlink下行请求java示例 <br>
 * <Ul>
 * <Li>本示例定义几种下行请求消息的使用方法</Li>
 * <Li>本示例依赖于 commons-codec，commons-httpclient，commons-logging等几个jar包</Li>
 * </Ul>
 */
public class SimpleClientExample {

    private String mtUrl="http://http.yunsms.cn/tx/";

    /**
     * 启动测试
     * @param args
     */
    public static void main(String[] args) {
        SimpleClientExample test = new SimpleClientExample();
        test.sendSMS();
    }

    //请求消息的使用示例
    public void sendSMS() throws Exception {
        String uid = "10000";		//数字用户名
        String pwd = "e10adc3949ba59abbe56e057f20f883e";		//MD5 32位 密码
        String mobile = "15900493333,15900492311";	//发送号码，多个用","号隔开
        String ecodeform = "GBK";
		//发送的内容
        String content = new String("testing......".getBytes(ecodeform));

        //组成url字符串
        StringBuilder smsUrl = new StringBuilder();
        smsUrl.append(mtUrl);
        smsUrl.append("?uid=" + uid);
        smsUrl.append("&pwd=" + pwd);
        smsUrl.append("&mobile=" + mobile);
        smsUrl.append("&content=" + content);

        //发送http请求，并接收http响应
        String resStr = doGetRequest(smsUrl.toString());
        System.out.println(resStr);
        if (resStr == "100")
		{
			System.out.println("成功！"); //成功
		}
		else
		{
			System.out.println("失败！");//失败
		}

    }
    /**
     * 发送http GET请求，并返回http响应字符串
     * 
     * @param urlstr 完整的请求url字符串
     * @return
     */
    public static String doGetRequest(String urlstr) {
        String res = null;
        HttpClient client = new HttpClient(new MultiThreadedHttpConnectionManager());
        client.getParams().setIntParameter("http.socket.timeout", 10000);
        client.getParams().setIntParameter("http.connection.timeout", 5000);

        HttpMethod httpmethod = new GetMethod(urlstr);
        try {
            int statusCode = client.executeMethod(httpmethod);
            if (statusCode == HttpStatus.SC_OK) {
                res = httpmethod.getResponseBodyAsString();
            }
        } catch (HttpException e) {
            e.printStackTrace();
        } catch (IOException e) {
            e.printStackTrace();
        } finally {
            httpmethod.releaseConnection();
        }
        return res;
    }

    /**
     * 发送http POST请求，并返回http响应字符串
     * 
     * @param urlstr 完整的请求url字符串
     * @return
     */
    public static String doPostRequest(String urlstr) {
        String res = null;
        HttpClient client = new HttpClient(new MultiThreadedHttpConnectionManager());
        client.getParams().setIntParameter("http.socket.timeout", 10000);
        client.getParams().setIntParameter("http.connection.timeout", 5000);
        
        HttpMethod httpmethod =  new PostMethod(urlstr);
        try {
            int statusCode = client.executeMethod(httpmethod);
            if (statusCode == HttpStatus.SC_OK) {
                res = httpmethod.getResponseBodyAsString();
            }
        } catch (HttpException e) {
            e.printStackTrace();
        } catch (IOException e) {
            e.printStackTrace();
        } finally {
            httpmethod.releaseConnection();
        }
        return res;
    }

}
