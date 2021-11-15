<?php
class Login_out_log extends LogAbstract
{
    private $login_id = 0;
    private $login_out_time = '';
    private $login_out_ip = '';
    public function addLog($collection_name = '',$data =[]){
        $data['login_out_time'] = time();
        $data['login_out_ip'] = $this->getIp();
        return $this->addByMongo($collection_name,$data);
    }

    public function listLog($collection = '', $where = [],$page = 1,$pageSize = 20)
    {
        return parent::listByPage($collection,$where,$page,$pageSize);
    }

    public function getIp(){
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
            $ip = getenv("REMOTE_ADDR");
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = "unknown";
        return($ip);
    }
}