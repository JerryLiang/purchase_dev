<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/25
 * Time: 10:36
 */
class System_model extends MY_Model{
    private $system_db;

    public function __construct()
    {
        parent::__construct();
        $this->system_db = $this->load->database('system',TRUE);
        $this->table_name = 'supr_visit_system';
    }
    
    /**
     * 获取访问系统对应的加密秘钥
     * @param $system_code
     * @return mixed
     */
    public function getSecretToken($system_code){
        $this->system_db->select('secret_token');
        $this->system_db->where(array(
            'system_code'=> $system_code,
        ));

         return $this->system_db->get($this->table_name)->row_array();
    }
}