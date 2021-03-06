<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Account_sub extends MY_ApiBaseController
{
    /** @var Payment_order_pay_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('system/Account_sub_model');
        $this->_modelObj = $this->Account_sub_model;
    }
    
      /**
     * 子账号列表
     * @author harvin
     * @date 2019-06-26
     */
    public function sub_list(){
         try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_sub_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
      * 新SKU 降本优化人
     **/
    public function get_reduced_optimizing_user()
    {
        try{
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_reduced_optimizing_user($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $e )
        {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
     /**
     * 添加子账号
     * @author harvin
     * @date 2019-06-26
     */
    public function add_sub(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_add_sub($this->_requestParams);
            $this->sendData();
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }     
    }
    
    
     /**
     * 修改显示
     * @author harvin、
     * @date 2019-06-26
     */
    public function sub_edit() {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_sub_edit($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     * 保存修改1688子账号信息
     * @author harvin
     * @date 2019-06-26
     */
    public function sub_edit_save(){
         try {
             $this->_init_request_param("POST");
            $data = $this->_modelObj->get_sub_edit_save($this->_requestParams);

            $this->sendData(['status'=>1]);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }         
    }
    
    /**
     * 删除子账号
     * @author harvin
     * @data 2019-06-27
     */
    public function sub_del(){
         try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_sub_del($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
        
        
    }

    /**
       * function:获取1688子账号使用者信息接口
     **/
    public function get_company_person()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_company_person($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }

    }
}
