<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Account_ail extends MY_ApiBaseController
{
    /** @var Payment_order_pay_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('system/Account_ail_model');
        $this->_modelObj = $this->Account_ail_model;
    }
    
     /**
     * 1688账号列表
     * @author harvin
     * @date 2019-06-24
     * @desc api/system/Account_ail/account_list
     */
    public function account_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_account_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    
    
    /**
    * 创建1688主账号
    * @author harvin
    * @date 2019-06-25
    */
     public function add_account(){
        try {
            $data = $this->_modelObj->add_account($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }   
    }
     /**
     * 1688账号授权
     * @author harvin
     * @date 2019-06-25
     */
    public function account_oauth(){
      try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_account_oauth($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    
     /**
     * 1688账号更新-显示
     * @author harvin
     * @date 2019-06-25
     */
    public function account_update() {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_account_update($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    
    
     /**
    * 更新保存
    *@author harvin
    *@date 2019-06-26 
    */
    public function account_update_save(){
          try {
            $data = $this->_modelObj->get_account_update_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }
    
      /**
     * 1688主账号删除数据
     * @author harvin
     * @date 2019-06-26
     */
    public function account_del(){
          try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_account_del($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

}
