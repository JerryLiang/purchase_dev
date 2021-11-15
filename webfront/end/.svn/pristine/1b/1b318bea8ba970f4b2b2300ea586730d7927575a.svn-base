<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * [function 省市、省份、区县]
 * @author Jackson
 * @param
 * @DateTime 2019/1/25
 */
class Supplier_address extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier/Supplier_address_model');
        $this->_modelObj = $this->Supplier_address_model;
    }

    /**
     * @desc 获取省份、城市、区县 根据 类型及父ID
     * @author jackson
     * @Date 2019-01-25 15:26:00
     * @return array()
     */
    public function get_address()
    {

        $params = $this->_requestParams;
        $data = $this->_modelObj->get_address($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     *根据 获取 对应省、市、区 名称
     */
    public function get_address_list(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_address_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 获取下拉列表
     */
    public function get_drop_down_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_drop_down_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
}