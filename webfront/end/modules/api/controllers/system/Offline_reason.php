<?php
require APPPATH.'core/MY_ApiBaseController.php';

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Offline_reason extends MY_ApiBaseController {

    private $_modelObj;

    public function __construct(){
        parent::__construct();
        $this->load->model('system/Offline_reason_model');
        $this->_modelObj = $this->Offline_reason_model;
    }

    /**
     * 获取 线下退款原因配置 数据列表
     * @author Jolon
     * @date   2021-01-13
     */
    public function get_offline_reason(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_offline_reason($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 获取 退款类型 下拉框选择
     * @author Jolon
     * @date   2021-01-13
     */
    public function get_refund_type_list(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_refund_type_list($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 新增退款类型
     * @author Jolon
     * @date   2021-01-13
     */
    public function create_refund_type(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->create_refund_type($this->_requestParams);
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
    public function update_refund_reason(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->update_refund_reason($this->_requestParams);
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
    public function update_refund_reason_status(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->update_refund_reason_status($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

}
