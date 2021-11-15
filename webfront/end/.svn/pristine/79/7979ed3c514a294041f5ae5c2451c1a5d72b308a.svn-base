<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * Class Purchase_setting
 */
class Purchase_setting extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('system/Purchase_setting_model');
        $this->_modelObj = $this->Purchase_setting_model;
    }

    /**
     * 获取公共仓修改限制列表
     */
    public function pertain_set_list()
    {
        $this->_init_request_param("POST");
        $data = $this->_modelObj->pertain_set_list($this->_requestParams);
        $this->sendData($data);
    }

    /**
     * 编辑/新增公共仓修改限制
     */
    public function pertain_set_edit()
    {
        $this->_init_request_param("POST");
        $data = $this->_modelObj->pertain_set_edit($this->_requestParams);
        $this->sendData($data);
    }

    /**
     * 获取自动取消列表
     */
    public function cancel_auto_list()
    {
        $this->_init_request_param("POST");
        $data = $this->_modelObj->cancel_auto_list($this->_requestParams);
        $this->sendData($data);
    }

    /**
     * 编辑/新增自动取消
     */
    public function cancel_auto_edit()
    {
        $this->_init_request_param("POST");
        $data = $this->_modelObj->cancel_auto_edit($this->_requestParams);
        $this->sendData($data);
    }

    /**
     * 应付款时间配置列表
     */
    public function need_pay_time_list()
    {
        $this->_init_request_param("POST");
        $data = $this->_modelObj->need_pay_time_list($this->_requestParams);
        $this->sendData($data);
    }

    /**
     * 编辑/新增应付款时间配置
     */
    public function need_pay_time_edit()
    {
        $this->_init_request_param("POST");
        $data = $this->_modelObj->need_pay_time_edit($this->_requestParams);
        $this->sendData($data);
    }

    /**
     * 自动对账配置
     */
    public function record_auto_list()
    {
        $this->_init_request_param("POST");
        $data = $this->_modelObj->record_auto_list($this->_requestParams);
        $this->sendData($data);
    }

    /**
     * 编辑/新增自动对账配置
     */
    public function record_auto_edit()
    {
        $this->_init_request_param("POST");
        $data = $this->_modelObj->record_auto_edit($this->_requestParams);
        $this->sendData($data);
    }

}