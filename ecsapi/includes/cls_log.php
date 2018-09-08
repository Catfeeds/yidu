<?php
/**
 * Logger class
 * User: Jam.Cheng
 * Date: 14-4-22
 * Time: 上午9:39
 * 使用方法
 *      $log=new Logger(array('log_path'=> str_replace('','/',dirname(__FILE__)).'/'));
 *      $log->writeLog("rn    登陆帐号:fewjfewijfejfejrn    登陆帐号:fewjfewijfejfej",'INFO');
 *      $log->writeLog("登陆帐号:fewjfewijfejfejrn",'DEBUG');
 *      $logger->writeLog("rn         ".json_encode(array(
 *              '_GET'=>$_GET,
 *              '_POST'=>$_POST,
 *              '_REQUEST'=>$_REQUEST
 *          )),'INFO','WAP_ALIPAY');
 *
 */

class Logger {
    protected $_log_path;
    protected $_enabled = TRUE;
    protected $_levels  = array('ERROR' => '1', 'DEBUG' => '2',  'INFO' => '3');
    protected $_log_pre = 'log_';
    public function __construct($config=array())
    {


        $this->_log_path = isset($config['log_path']) ? $config['log_path'] : '';
        if ( ! is_dir($this->_log_path)) {@mkdir($this->_log_path, 0777); }
    }

    public function writeLog($msg, $level = 'error', $log_name='response')
    {
        if ($this->_enabled === FALSE){return FALSE; }
        $level = strtoupper($level);             
        if ( ! isset($this->_levels[$level])){return FALSE; }
        $data['msg'] = $msg;
        $data['level'] = $level;
        $data['date'] = time();
        $data['logpath'] = $this->_log_path;
        $data['filepath'] = $this->_log_path.$this->_log_pre.$log_name.'_'.date('Ymd').'_'.$level.'.log';
        $this->_writeLogFile($msg, $data['filepath'], $level);
    }

    public function readLog($num, $date, $level)
    {
        if ($this->_enabled === FALSE){return FALSE; }
        empty($date) && $date = date('Y-m-d');
        $this->_readLogFile($num, $date, $level);
    }

    private function _writeLogFile($msg, $filepath, $level='DEBUG')
    {
        if ( ! $fp = @fopen($filepath, 'a+')){return FALSE; }
        $message = $this->_logFormat($msg, $level);
        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);
        return TRUE;
    }

    private function _readLogFile($num, $date, $level)
    {
        $filepath = $this->_log_path.'sdk_log_'.$date.'_'.$level.'.log';
        $fp = @fopen($filepath, "r"); //以只读的方式打开online.txt文件
        $pos=-2;
        $eof="";
        $str="";
        while($num>0)
        {
            while($eof!="n")
            {
                if(!fseek($fp,$pos,SEEK_END)){
                    $eof=fgetc($fp);
                    $pos--;
                }else{
                    break;
                }
            }
            $str.=fgets($fp);
            $eof="";
            $num--;
        }
        fclose($fp);
        echo nl2br($str);
        return TRUE;
    }

    private function _logFormat($msg, $level)
    {
        $data['date'] = time();
        $record_time = date('Y-m-d H:i:s', $data['date']);
        $log_str ='['.$level.']['.$record_time.'] '.$msg."n";
        return $log_str;
    }

}
