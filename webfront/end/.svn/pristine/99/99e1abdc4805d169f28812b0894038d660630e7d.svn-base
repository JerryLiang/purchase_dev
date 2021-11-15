<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * Class System_purchase
 */
class System_purchase extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('system/System_purchase_model');
        $this->_modelObj = $this->System_purchase_model;
    }

    /**
     * 获取列表
     */
    public function get_list()
    {
        $this->_init_request_param("POST");
        $data = $this->_modelObj->get_list($this->_requestParams);
        $this->sendData($data);
    }

    /**
     * 编辑/新增
     */
    public function save_edit_setting()
    {
        $this->_init_request_param("POST");
        $data = $this->_modelObj->save_edit_setting($this->_requestParams);
        $this->sendData($data);
    }

    /**
     * 启用禁用
     */
    public function on_off_setting()
    {
        $this->_init_request_param("POST");
        $data = $this->_modelObj->on_off_setting($this->_requestParams);
        $this->sendData($data);
    }

}