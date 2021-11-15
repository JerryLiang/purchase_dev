<?php
/**
 * User: yefanli
 * Date: 2020-07-24
 */
class Handle_behavior_logs extends Purchase_model 
{
    private $table = 'developers_use_system_behavior_logs';

    public function __construct(){
        parent::__construct();
        $this->load->library('mongo_db');
    }

    /**
     * 生成行为日志
     */
    public function generateBehaviorLogs($data=false)
    {
        try{
            if($data)$data["datetime"] = $this->get_microtime();
            $this->mongo_db->insert($this->table, $data);
        }catch(Exception $e){}
    }

    /**
     * 删除某一天前的行为日志
     */
    public function deleteHistoryLogs()
    {
        try{
            $date = date('Ymd', strtotime('-5 day'));
            $this->mongo_db->where_lt('datetime', $date)->delete_all($this->table);
        }catch(Exception $e){}
    }

    /**
     * @return string
     */
    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd H:i:s")."-".($b[0] * 1000);
    }
}