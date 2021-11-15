<?php
require APPPATH . 'core/MY_ApiBaseController.php';

class Supplier_check extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier/Supplier_check_model');
        $this->_modelObj = $this->Supplier_check_model;
    }

    /**
     * @desc 获取列表
     */
    public function get_list()
    {

        $params = $this->_requestParams;
        $data = $this->_modelObj->get_list($params);
        $this->sendData($data);
    }

    /**
     * @desc 导出列表
     */
    public function export_list()
    {

        $params = $this->_requestParams;
        $data = $this->_modelObj->export_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * @desc 导出列表
     */
    public function import_list()
    {
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        if(!isset($params['file_path']) || empty($params['file_path'])){
            $this->sendError(1, "文件地址参数缺失");
        }
        $file_path = $params['file_path'];
        if(!file_exists($file_path)){
            $this->sendError(1, "文件不存在[{$file_path}]");
        }
        $data = $this->_modelObj->import_list($params);
        $this->sendData($data);
    }

    /**
     * @desc 编辑sku退款率
     */
    public function refund_rate_edit()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->refund_rate_edit($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * @desc 根据备货单获取相应的验货信息
     */
    public function get_order_by_suggest()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_order_by_suggest($params);
        $this->sendData($data);
    }

    /**
     * @desc 获取批量确认数据
     */
    public function get_check_confirm()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_check_confirm($params);
        $this->sendData($data);
    }

    /**
     * @desc 新增/保存批量确认数据
     */
    public function save_check_confirm()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->save_check_confirm($params);
        $this->sendData($data);
    }

    /**
     * @desc 新增验货申请
     */
    public function create_check_save()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->create_check_save($params);
        $this->sendData($data);
    }

}