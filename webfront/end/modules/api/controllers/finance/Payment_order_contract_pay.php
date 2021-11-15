<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Payment_order_contract_pay extends  MY_ApiBaseController{
    
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('finance/Payment_order_contract_pay_model');
        $this->_modelObj = $this->Payment_order_contract_pay_model;
    }
    
    
    /**
      * 批量审核显示（合同）
      * @author harvin 
      * @date 2019-4-4
      * api/finance/payment_order_contract_pay/batch_audit_info
      */
     public function batch_audit_info(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_audit_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
     }
    /**
      * 批量审核（合同）
      * @author harvin 
      * @date 2019-4-4
      * /finance/payment_order_contract_pay/batch_audit
      */
      public function batch_audit(){
           try {
            $data = $this->_modelObj->get_contract_order_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }
     /**
      * 线下支付---确认页（合同）
      * @author harvin 
      * @date 2019-4-7
      * api/finance/payment_order_contract_pay/offline_payment_info
      */
     public function offline_payment_info(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_offline_payment_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
     }
     /**
      * 线下支付---保存（合同）
      * @author harvin 
      * @date 2019-4-7
      * api/finance/payment_order_contract_pay/offline_payment_info
      */
     public function offline_payment_save(){
           try {
            $data = $this->_modelObj->get_offline_payment_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
     }
     /**
      * 线下支付---驳回（合同）
      * @author harvin 
      * @date 2019-4-7
      * api/finance/payment_order_contract_pay/offline_payment_info
      */
     public function offline_payment_reject(){
            try {
            $data = $this->_modelObj->get_offline_payment_reject($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
         
     }
     /**
      * 富友线上支付（合同）
      * @author harvin 
      * @date 2019-4-7
      * api/finance/payment_order_contract_pay/offline_payment_info
      */
     public function online_payment(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_online_payment($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
     }
}
