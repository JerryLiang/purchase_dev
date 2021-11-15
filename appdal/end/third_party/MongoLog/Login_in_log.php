<?php
class Login_in_log extends LogAbstract{
    private $login_id = 0; //登录ID
    private $login_time = ''; //登录时间
    private $login_ip = ''; //登录IP
    private $prev_login_time = ''; //上次登录时间
    private $login_nums = 0; //截止到目前的登录次数
    private $current_day_login_nums = 0; //当天登录次数

    public function addLog($collection_name = '',$data =[]){
        $data['login_nums'] = $this->getLoginNumsByLoginId($collection_name,$data['login_id']);
        $data['current_day_login_nums'] = $this->getLoginNumsByLoginId($collection_name,$data['login_id'],strtotime(date("Y-m-d")));
        $data['login_time'] = time();
        $data['prev_login_time'] = $this->getPrevLoginTime($collection_name,$data['login_id']);
        $data['login_ip'] = $this->getIp();
        return $this->addByMongo($collection_name,$data);
    }

    public function getPrevLoginTime($collection_name,$login_id){
        $info = $this->mongo_db->where(['login_id' => $login_id])->order_by(['login_time' => -1])->limit(1)->get($collection_name.$this->subfix);
        return $info ? $info[0]->login_time : 0;
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


    public function getLoginNumsByLoginId($collection ='',$login_id,$date = ''){
        $where = ['login_id' => $login_id];
        if($date){
            $where['login_time']['$gt'] = $date;
        }
        $total = $this->getTotal($collection,$where);
        return ($total+1);
    }

    public function listLog($collection = '', $where = [],$page = 1,$pageSize = 20)
    {
        return parent::listByPage($collection,$where,$page,$pageSize);
    }


}