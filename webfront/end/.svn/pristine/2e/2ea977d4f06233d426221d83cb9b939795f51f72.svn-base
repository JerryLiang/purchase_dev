<?php
require APPPATH.'core/MY_ApiBaseController.php';

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Offline_receipt extends MY_ApiBaseController {

    private $_modelObj;

    public function __construct(){
        parent::__construct();
        $this->load->model('finance/Offline_receipt_model');
        $this->_modelObj = $this->Offline_receipt_model;
    }

    /**
     * 获取 线下收款列表 数据列表
     * @author Jolon
     * @date   2021-01-13
     */
    public function get_offline_receipt_list(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_offline_receipt_list($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 确认待收款 界面
     * @author Jolon
     * @date   2021-01-13
     */
    public function confirm_waiting_receipt(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->confirm_waiting_receipt($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 确认待收款 驳回/确认到账
     * @author Jolon
     * @date   2021-01-13
     */
    public function confirm_receipted(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->confirm_receipted($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 新增/编辑 退款原因
     * @author Jolon
     * @date   2021-01-13
     */
    public function get_receipt_details(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_receipt_details($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 更新 退款原因 禁用启用状态
     * @author Jolon
     * @date   2021-01-13
     */
    public function update_receipt_details(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->update_receipt_details($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 导出 收款列表
     * @author Jolon
     * @date   2021-01-13
     */
    public function export_list_csv(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->export_list_csv($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

}
