<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Reason_config extends MY_ApiBaseController
{
    /** @var Payment_order_pay_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('system/Reason_config_model');
        $this->_modelObj = $this->Reason_config_model;
    }
    
      /**
     * 原因配置列表
     * @author jeff
     * @date 2019-10-08
     */
    public function reason_type_list(){
         try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->reason_type_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     * 原因配置列表
     * @author jeff
     * @date 2019-10-08
     */
    public function cancel_reason_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_cancel_reason_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    
    
     /**
     * 修改原因
     * @author jeff、
     * @date 2019-06-26
     */
    public function reason_edit() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->reason_edit($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {

            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 修改原因启用状态
     * @author jeff、
     * @date 2019-06-26
     */
    public function reason_status_change() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->reason_status_change($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {

            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 添加原因
     * @author jeff、
     * @date 2019-06-26
     */
    public function reason_add() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->reason_add($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {

            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 原因排序
     * @author jeff、
     * @date 2019-06-26
     */
    public function reason_sort() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->reason_sort($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {

            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取作废原因下拉框
     * @author jeff
     * http://www.caigouapi.com/api/system/reason_config/get_cancel_reasons
     */
    public function get_cancel_reasons() {

        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $params['status'] = 1;//启用的
        $data = $this->_modelObj->get_cancel_reasons($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 编辑原因统一提交(包括新增)
     * @author jeff、
     * @date 2019-06-26
     */
    public function reason_edit_submit() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->reason_edit_submit($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {

            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
}
