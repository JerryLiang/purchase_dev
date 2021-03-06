<?php
/**
 * Created by PhpStorm.
 * 删除相关表日志记录操作
 * User: Jolon
 * Date: 2020/03/06 11:17
 */

class System_error_log_model extends Purchase_model{

    protected $table_name = 'system_error_log';

    public function __construct(){
        parent::__construct();
    }

    /**
     * 添加 系统操作错误日志
     * @author Jolon
     * @param string $message  错误信息
     * @return mixed
     */
    public function insertLog($message) {
        $this->load->helper('url');
        $this->load->helper('user');


        $userName   = getActiveUserName();
        $operate_ip = getActiveUserIp();

        if(!is_string($message)) $message = json_encode($message);

        $insert_data = [
            'operator_user' => !empty($userName) ? $userName : 'system',
            'operate_ip'    => !empty($operate_ip) ? $operate_ip : '0.0.0.0',
            'operate_route' => uri_string(),
            'log_time'      => date('Y-m-d H:i:s'),
            'message'       => $message,
        ];

        $this->purchase_db->insert($this->table_name, $insert_data);
        return $this->purchase_db->insert_id();
    }






}