using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Text;
using System.Windows.Forms;
using System.Security.Cryptography;
using System.Security.Policy;
namespace WindowsApplication3
{
    public partial class Form1 : Form
    {
        public Form1()
        {
            InitializeComponent();
        }
        private string MD5(string pwd)
        {
            MD5 md5 = new MD5CryptoServiceProvider();
            byte[] data = System.Text.Encoding.Default.GetBytes(pwd);
            byte[] md5data = md5.ComputeHash(data);
            md5.Clear();
            string str = "";
            for (int i = 0; i < md5data.Length; i++)
            {
                str += md5data[i].ToString("x").PadLeft(2, '0');

            }
            return str;
        }
        /// <summary>
        /// 发送短信
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
      private void button1_Click(object sender, EventArgs e)
        {
            string pwd = MD5("1977113");	//密码MD5
            string uid = "9999";			//用户名
            string str1 =textBox1.Text;
            string str = textBox2.Text;
            StringBuilder sbTemp = new StringBuilder();
            sbTemp.Append("uid="+uid+"&pwd="+pwd+"&mobile=" + str1 + "&content=" + str);
            byte[] bTemp = System.Text.Encoding.GetEncoding("GBK").GetBytes(sbTemp.ToString());
            string postReturn = SMS.doPostRequest("http://http.yunsms.cn/tx/", bTemp);
            label1.Text = postReturn;
            if (label1.Text == "100")
            {
               
                label1.Text = "发送成功";
            }
            else
            {
                label1.Text = "发送失败";
            }

        }
        /// <summary>
        /// 接收短信
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void button2_Click(object sender, EventArgs e)
        {
            string pwd = MD5("1977113");	//密码MD5
            string uid = "9999";			//用户名
            string s1 = SMS.doGetRequest("http://http.yunsms.cn/rx/?uid=" + uid + "&pwd=" + pwd);
            if (string.IsNullOrEmpty(s1))
            {
                MessageBox.Show("已经接收过一次信息,不能重复接收!", "提示");
                return;
            }
            else
            {
                //byte[] stuff = System.Text.Encoding.Default.GetBytes(s11);
                //stuff = Encoding.Convert(Encoding.GetEncoding("GBK"), Encoding.GetEncoding("utf-8"), stuff);
                //string s1 = Encoding.Default.GetString(stuff);
                //s1 = Encoding.Default.GetString(stuff);
                char[] yu ={'{','}','|'};
                string[] str2 = s1.Split(yu);
                label1.Text = str2[0].ToString();
                textBox1.Text = str2[2].ToString();
                string s11 = str2[4].ToString();
                //byte[] buttf = Encoding.GetEncoding("GBK").GetBytes(s11);
                //textBox2.Text = Encoding.Default.GetString(buttf);
                label2.Text = str2[6].ToString();
            }
            if (label1.Text == "100")
            {

                label1.Text = "接收成功";
            }
            else
            {
                label1.Text = "验收失败";
            }


        }

        /// <summary>
        /// 短信条数
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void button3_Click(object sender, EventArgs e)
        {
            string pwd = MD5("1977113");	//密码MD5
            string uid = "9999";			//用户名
            string s1 = SMS.doGetRequest("http://http.yunsms.cn/mm/?uid=" + uid + "&pwd=" + pwd);
            string[] s2=s1.Split('|');
            string s3 = s2[0].ToString();
            string s4 = s2[2].ToString();
            label2.Text = s4;
        }
    }
}