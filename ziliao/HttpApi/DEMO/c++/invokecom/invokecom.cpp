// invokecom.cpp : Defines the entry point for the console application.
//

#include "stdafx.h"

//������COM֮ǰ������ע��COM��ע�᷽�����£�
//�Ȱ�smshttp.dll���ص�����������ŵ�C�̸�Ŀ¼���棬Ȼ���cmd,��������regsvr32.exe c:\smshttp.dll

#import "c:\smshttp.dll" no_namespace //����COM;
int _tmain(int argc, _TCHAR* argv[])
{
	::CoInitialize( NULL );		// COM ��ʼ��

	ISendMsgPtr sendMsgPtr(__uuidof(SendMsg));    //���COM�ӿ�
	sendMsgPtr->m_BstrUrl="http://http.yunsms.cn/tx"; //��������ַ
	sendMsgPtr->m_BstrUser="12345";           //�û��˺�
	sendMsgPtr->m_BstrPwd="12345";            //�û�����
	sendMsgPtr->m_BstrTime="";                //��ʱ����ʱ��,��ѡ��,�������������Ϊ��ֵ;�趨ʱ�������ʽΪ:YYYY-MM-DD HH:MM��
	                                          //�磺sendMsgPtr->m_BstrTime="2010-05-27 12:01" (��-��-�� ʱ:��),����ʱ���Ա���ʱ��Ϊ׼
	                                          
	sendMsgPtr->m_BstrMid="";               //����չ��,��ѡ��,����չ�������Ϊ��ֵ;�����û��˺��Ƿ�֧����չ
	sendMsgPtr->m_BstrEncode="";            //�ַ�����,��ѡ��,�����GBK���룬���Ϊ��ֵ;Ĭ�Ͻ���������GBK����,���ύ����UTF-8�����ַ�,��Ҫ��Ӳ��� sendMsgPtr->m_BstrEncode="utf8"
	                                          
    sendMsgPtr->RemoveMobiles();              //�����ǰ��ӵ��ֻ���
	sendMsgPtr->AddMobile("13100000000");     //�����Ҫ���͵��ֻ���
	sendMsgPtr->AddMobile("13100000001");     //�����Ҫ���͵��ֻ���
	sendMsgPtr->SetMsgContent("��ã��������翪�᡾���š�");          //���÷�������
	
	HRESULT lR;                               //�ӿڵ��÷���ֵ                       
	LONG lStatus;                             //�����������÷���״̬��
	lR=sendMsgPtr->GetMsgNum(&lStatus);       //���ʣ���������,lR=S_OK ��ʾ�������ӳɹ�,lR=S_FALSE ��ʾ��������ʧ��
	                                          //lR=S_OK ʱ lStatus ��Ч��lStatus ��ʾʣ��Ķ�������,�����Ҫ���ʣ�������������øú���

	lR=sendMsgPtr->SendMsg(&lStatus);         //���Ͷ���,lR=S_OK ��ʾ�������ӳɹ�,lR=S_FALSE ��ʾ��������ʧ��
	                                          //lR=S_OK ʱ lStatus ��Ч��lStatus ��ʾ����״̬��,���������·���ֵ��
	                                      /*
										     100			���ͳɹ�
		                                     101			��֤ʧ��
		                                     102			���Ų���
		                                     103			����ʧ��
		                                     104			�Ƿ��ַ�
		                                     105			���ݹ���
		                                     106			�������
		                                     107			Ƶ�ʹ���
		                                     108			�������ݿ�
		                                     109			�˺Ŷ���
		                                     110			��ֹƵ����������
		                                     111			ϵͳ�ݶ�����
		                                     120			ϵͳ����
										 */

	
	BSTR replyMsg=0;                         //���������������յĻظ����� 
	lR=sendMsgPtr->GetReplyMsg(&replyMsg);   //���ջظ�����,lR=S_OK ��ʾ�������ӳɹ�,lR=S_FALSE ��ʾ��������ʧ��
	                                         //lR=S_OK ʱ replyMsg ��Ч��replyMsg ��ʾ��õĻظ�����,
	                                         //ÿ�οɽ��ն����ظ�����,���ַ�����ʾ����ʽ����:
	                                         //{&}�ظ�����||�ظ�����||�ظ�ʱ��||�ظ����غ�{&}�ظ�����||�ظ�����||�ظ�ʱ��||�ظ����غš���.
                                             //����replyMsg����������ֵ:
	                                         //{&}13912341234||�ƶ��������Իظ�||2008-05-27 12:10:11||1068112227282{&}15912343333||�ƶ��������Իظ�2||2008-05-27 13:11:11||106811222728200

	SysFreeString(replyMsg);                 //�ͷ�replyMsg���ڴ�ռ�
	sendMsgPtr.Release();                    //�ͷ�COM�ӿ�
	::CoUninitialize();
	return 0;
}

