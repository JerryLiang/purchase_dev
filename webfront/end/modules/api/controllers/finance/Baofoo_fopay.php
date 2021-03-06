<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Baofoo_fopay extends  MY_ApiBaseController
{
     private $_modelObj;
    
     public function __construct()
    {
        parent::__construct();
        $this->load->model('finance/Baofoo_fopay_model');
        $this->_modelObj = $this->Baofoo_fopay_model;
    }
    
    
    /**
     * 待宝付审核列表
     * @author harvin
     * @date 2018/8/9
     * api/finance/Baofopay/baofoo_list
     */
     public function baofoo_list(){   
         try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_baofoo_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     * 宝付提交
     * @author harvin
     * @date 2019/8/8
     * api/finance/Baofoo_fopay/baofoo_submission
     */
    public function baofoo_submission(){
        try {
            $data = $this->_modelObj->get_baofoo_submission($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }  
    }
    
    /**
     * 宝付支付批量（显示）
     * @author harvin
     * @date 2019/8/9
     * api/finance/Baofoo_fopay/baofoo_batch_info
     */
    public function baofoo_batch_info(){
         try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_baofoo_batch_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        } 
    }
    
    /**
     * 宝付在线付款
     * @author harvin
     * @date 2019/8/6
     * /Baofoo_fopay/baofoo_fopay
     */
     public function baofoo_fopay(){
        try {
            $data = $this->_modelObj->get_baofoo_fopay($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }   
     }
    
     
     /**
     * 宝付驳回操作
     * @author harvin
     * @date 2019/8/10
     * /Baofoo_fopay/baofoo_fopay_reject
     */
    public function baofoo_fopay_reject(){
        try {
            $data = $this->_modelObj->get_baofoo_fopay_reject($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }  
     }
     
    /**
     * 宝付导出
     * @author harvin
     * @date 2019/8/12
     */ 
    public function boofoo_fopay_export(){
        try {
         $this->_init_request_param("GET");
         $data = $this->_modelObj->get_boofoo_fopay_export($this->_requestParams);
         $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }  
    }

    /**
     * 手动刷新
     */
    public function update_pay_baofoo_status(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->update_pay_baofoo_status($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
}