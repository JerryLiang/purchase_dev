<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-05-09
 * Time: 11:17
 */

require APPPATH . 'core/MY_ApiBaseController.php';

class Supplier_balance extends MY_ApiBaseController{
    private $_modelObj;

    public function __construct(){
        parent::__construct();
        $this->load->model('statement/Supplier_balance_model');
        $this->_modelObj = $this->Supplier_balance_model;
    }

    /**
     * 日报表
     */
    public function day_supplier_balance_list(){
        $this->_init_request_param("GET");
        $data = $this->_modelObj->day_supplier_balance_list($this->_requestParams);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 月报表
     */
    public function month_supplier_balance_list(){
        $this->_init_request_param("GET");
        $data = $this->_modelObj->month_supplier_balance_list($this->_requestParams);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 季度报表
     */
    public function quarter_supplier_balance_list(){
        $this->_init_request_param("GET");
        $data = $this->_modelObj->quarter_supplier_balance_list($this->_requestParams);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 年度报表
     */
    public function year_supplier_balance_list(){
        $this->_init_request_param("GET");
        $data = $this->_modelObj->year_supplier_balance_list($this->_requestParams);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 导出
     */
    public function balance_export(){
        set_time_limit(0);
        $this->_init_request_param("GET");
        $data = $this->_modelObj->balance_export($this->_requestParams);
        http_response($data);
    }

    /**
     * 汇总
     */
    public function get_statistic_list(){
        set_time_limit(0);
        $this->_init_request_param("GET");
        $data = $this->_modelObj->get_statistic_list($this->_requestParams);
        http_response($data);
    }

    /**
     * 公司主体
     */
    public function  get_purchase_agent_info(){
        $this->_init_request_param("GET");
        $data = $this->_modelObj->get_purchase_agent_info($this->_requestParams);
        http_response($data);
    }



}