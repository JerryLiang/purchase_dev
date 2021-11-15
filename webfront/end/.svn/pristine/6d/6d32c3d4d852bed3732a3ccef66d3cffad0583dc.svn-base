<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * [function desc]
 * @author Jackson
 * @param
 * @DateTime 2019/1/21
 */
class Supplier_buyer extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier/Supplier_buyer_model');
        $this->_modelObj = $this->Supplier_buyer_model;
    }

    /**
     * @desc 获取采购员
     * @author jackson
     * @Date 2019-01-21 15:26:00
     * @param1 $url 链接地址
     * @param2 $value 传入的参数
     * @return array()
     */
    public function get_buyer()
    {

        $params = $this->_requestParams;
        $data = $this->_modelObj->get_buyer($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

}