<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-03-16
 * Time: 14:25
 */

require APPPATH . 'core/MY_ApiBaseController.php';

class Purchase_return_receivable extends MY_ApiBaseController{

    private $_modelObj;
    public function __construct(){
        parent::__construct();
        $this->load->model('purchase_return/Purchase_return_receivable_model');
        $this->_modelObj = $this->Purchase_return_receivable_model;
    }

    /**
     * list
     */
    public function get_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     * 详情
     */
    public function get_return_receivable_items(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_return_receivable_items($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /***
     * 财务收款
     */
    public function click_receivables(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->click_receivables($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 导出
     */
    public function export_receivables_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->export_receivables_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }

    }

    /**
     *  修改收款账号弹窗
     */
    public function modify_receiving_account(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->modify_receiving_account($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }

    }

}