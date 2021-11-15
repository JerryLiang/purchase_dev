<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * [function 获取供应商结算方式]
 * @author Jackson
 * @param
 * @DateTime 2019/1/21
 */
class Supplier_settlement extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier/Supplier_settlement_model');
        $this->_modelObj = $this->Supplier_settlement_model;
    }

    /**
     * @desc 获取供应商结算方式
     * @author jackson
     * @Date 2019-01-22 15:26:00
     * @param1 $url 链接地址
     * @param2 $value 传入的参数
     * @return array()
     */
    public function get_settlement()
    {

        $data = $this->_modelObj->get_settlement();

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

}