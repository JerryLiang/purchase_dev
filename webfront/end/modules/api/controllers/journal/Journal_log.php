<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/31
 * Time: 18:07
 */
class Journal_log extends MY_ApiBaseController
{
    /** @var Journal_log_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('journal/Journal_log_model');
        $this->_modelObj = $this->Journal_log_model;
    }

    /**
     * 驳回信息表操作日志接口---袁学文---袁学文
     * @url /api/journal/journal_log/get_log_list
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method GET
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=718
     */
    public function get_log_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_log_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 系统操作日志接口----袁学文---袁学文
     * @url /api/journal/journal_log/get_operator_log
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method GET
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=720
     */
    public function get_operator_log()
    {
        try {
          
         //   $this->_init_request_param("GET");
            $data = $this->_modelObj->get_operator_log($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    //http://192.168.71.170:86/api/journal/journal_log/test
    public function test(){
        
         //登录成功，记录登录日志
                $this->load->model("api/system/Admin_log_model");
                $username = isset($result['data']['user_name']) ? $result['data']['user_name'] : "123456";
                $uid= isset($result['data']['uid'])? $result['data']['uid'] : "1580";
                $staff_code = isset($result['data']['staff_code']) ? $result['data']['staff_code'] : "2525";
		$user_role = isset($result['data']['role_data']) ? implode(',',array_column($result['data']['role_data'],'name')) : '63696';     
                $this->Admin_log_model->write_log([
                    "route" => "/" . trim($this->uri->uri_string, "/"),
                    "user_name" => $username,//操作者用户名
                    "user_role" => $user_role,//操作者角色
                    "ip" => '127.0.0.1',
                    "description" => "用户{$username}[工号：{$staff_code}]登录",
                     'uid'=>$uid       
                ]);
                //设置系统采购单所有状态
                $this->load->model("api/system/Status_set_model");
                $this->Status_set_model->get_set(['uid'=>$uid]);
        
        
    }
}