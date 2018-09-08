// invokecom.cpp : Defines the entry point for the console application.
//

#include "stdafx.h"

//在引用COM之前，请先注册COM；注册方法如下：
//先把smshttp.dll下载到本机，比如放到C盘根目录下面，然后打开cmd,运行命令regsvr32.exe c:\smshttp.dll

#import "c:\smshttp.dll" no_namespace //引用COM;
int _tmain(int argc, _TCHAR* argv[])
{
	::CoInitialize( NULL );		// COM 初始化

	ISendMsgPtr sendMsgPtr(__uuidof(SendMsg));    //获得COM接口
	sendMsgPtr->m_BstrUrl="http://http.yunsms.cn/tx"; //服务器地址
	sendMsgPtr->m_BstrUser="12345";           //用户账号
	sendMsgPtr->m_BstrPwd="12345";            //用户密码
	sendMsgPtr->m_BstrTime="";                //定时发送时间,可选项,立即发送则该项为空值;需定时发送则格式为:YYYY-MM-DD HH:MM　
	                                          //如：sendMsgPtr->m_BstrTime="2010-05-27 12:01" (年-月-日 时:分),发送时间以北京时间为准
	                                          
	sendMsgPtr->m_BstrMid="";               //子扩展号,可选项,无扩展号则该项为空值;根据用户账号是否支持扩展
	sendMsgPtr->m_BstrEncode="";            //字符编码,可选项,如果是GBK编码，则该为空值;默认接收数据是GBK编码,如提交的是UTF-8编码字符,需要添加参数 sendMsgPtr->m_BstrEncode="utf8"
	                                          
    sendMsgPtr->RemoveMobiles();              //清空以前添加的手机号
	sendMsgPtr->AddMobile("13100000000");     //添加需要发送的手机号
	sendMsgPtr->AddMobile("13100000001");     //添加需要发送的手机号
	sendMsgPtr->SetMsgContent("你好，今天下午开会【快信】");          //设置发送内容
	
	HRESULT lR;                               //接口调用返回值                       
	LONG lStatus;                             //传入参数，获得返回状态码
	lR=sendMsgPtr->GetMsgNum(&lStatus);       //获得剩余短信条数,lR=S_OK 表示网络连接成功,lR=S_FALSE 表示网络连接失败
	                                          //lR=S_OK 时 lStatus 有效，lStatus 表示剩余的短信条数,如果需要获得剩余短信条数则调用该函数

	lR=sendMsgPtr->SendMsg(&lStatus);         //发送短信,lR=S_OK 表示网络连接成功,lR=S_FALSE 表示网络连接失败
	                                          //lR=S_OK 时 lStatus 有效，lStatus 表示返回状态码,可以是以下返回值：
	                                      /*
										     100			发送成功
		                                     101			验证失败
		                                     102			短信不足
		                                     103			操作失败
		                                     104			非法字符
		                                     105			内容过多
		                                     106			号码过多
		                                     107			频率过快
		                                     108			号码内容空
		                                     109			账号冻结
		                                     110			禁止频繁单条发送
		                                     111			系统暂定发送
		                                     120			系统升级
										 */

	
	BSTR replyMsg=0;                         //传入参数，保存接收的回复短信 
	lR=sendMsgPtr->GetReplyMsg(&replyMsg);   //接收回复短信,lR=S_OK 表示网络连接成功,lR=S_FALSE 表示网络连接失败
	                                         //lR=S_OK 时 replyMsg 有效，replyMsg 表示获得的回复短信,
	                                         //每次可接收多条回复短信,以字符串表示，格式如下:
	                                         //{&}回复号码||回复内容||回复时间||回复网关号{&}回复号码||回复内容||回复时间||回复网关号…….
                                             //例如replyMsg可以是如下值:
	                                         //{&}13912341234||云短信网测试回复||2008-05-27 12:10:11||1068112227282{&}15912343333||云短信网测试回复2||2008-05-27 13:11:11||106811222728200

	SysFreeString(replyMsg);                 //释放replyMsg的内存空间
	sendMsgPtr.Release();                    //释放COM接口
	::CoUninitialize();
	return 0;
}

