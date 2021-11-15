<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * 供应商交期报表
 * @author Jolon
 * @param
 * @DateTime 2021/06/11
 */
class Supplier_average_delivery extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier/Supplier_average_delivery_model');
        $this->_modelObj = $this->Supplier_average_delivery_model;
    }

    /**
     * @desc 获取供应商交期列表
     * @author Jolon
     */
    public function get_delivery_list()
    {
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_delivery_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 供应商交期列表 - 导出
     */
    public function export_delivery_list(){
        set_time_limit(0);
        $this->_init_request_param("POST");
        $data = $this->_modelObj->export_delivery_list($this->_requestParams);
        if(is_null($data)){
            $this->_code = $this->getServerErrorCode();
            $this->_msg  = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }
}