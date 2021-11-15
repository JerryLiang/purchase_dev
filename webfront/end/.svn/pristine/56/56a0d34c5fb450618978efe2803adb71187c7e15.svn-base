<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/12/26
 * Time: 15:09
 */

require APPPATH . 'core/MY_ApiBaseController.php';

class Payment_order_report extends MY_ApiBaseController{
    /** @var Payment_order_pay_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('financial_statements/Payment_order_report_model');
        $this->_modelObj = $this->Payment_order_report_model;
    }

    /**
     * 获取列表信息
     */
    public function get_pay_order_report(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_pay_order_report($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @param $get
     * @return array|mixed
     * @throws Exception
     */
    public function export_report_list(){
        set_time_limit(0);
        try {
            $this->_init_request_param("GET");
            $data= $this->_modelObj->export_report_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage()."导出出现异常!");
        }
    }


    /**
     * 添加、修改 财务付款统计表备注
     */
    public function add_finance_report_remark(){
        try {
            $this->_init_request_param("POST");
            $data= $this->_modelObj->add_finance_report_remark($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage()."添加备注出现异常!");
        }
    }

}