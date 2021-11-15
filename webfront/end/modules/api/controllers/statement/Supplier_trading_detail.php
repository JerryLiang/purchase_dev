<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-06-05
 * Time: 14:02
 */


require APPPATH.'core/MY_ApiBaseController.php';

class Supplier_trading_detail extends MY_ApiBaseController {

    private $_modelObj;

    public function __construct(){
        parent::__construct();
        $this->load->model('statement/Supplier_trading_detail_model');
        $this->_modelObj = $this->Supplier_trading_detail_model;
    }

    /**
     * 供应商余额汇总表 - 交易明细
     */
    public function get_trading_detail_list(){
        $this->_init_request_param("POST");
        $data = $this->_modelObj->get_trading_detail_list($this->_requestParams);
        if(is_null($data)){
            $this->_code = $this->getServerErrorCode();
            $this->_msg  = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }


    /**
     * 供应商余额汇总表 - 交易明细 - 导出
     */
    public function export_trading_detail_list(){
        set_time_limit(0);
        $this->_init_request_param("POST");
        $data = $this->_modelObj->export_trading_detail_list($this->_requestParams);
        if(is_null($data)){
            $this->_code = $this->getServerErrorCode();
            $this->_msg  = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }


    /**
     * 供应商余额汇总表 - 交易明细 - 页面汇总
     */
    public function get_statistic_list(){
        set_time_limit(0);
        $this->_init_request_param("POST");
        $data = $this->_modelObj->get_statistic_list($this->_requestParams);
        if(is_null($data)){
            $this->_code = $this->getServerErrorCode();
            $this->_msg  = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }


}