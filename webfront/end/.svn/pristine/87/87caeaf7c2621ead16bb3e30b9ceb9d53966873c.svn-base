<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * [function desc]
 * @author Jackson
 * @param
 * @DateTime 2019/1/21
 */
class Supplier_product_line extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier/Supplier_product_line_model');
        $this->_modelObj = $this->Supplier_product_line_model;
    }

    /**
     * @desc 获取产品线级别列表（one:一级，two:二级,third:三级）
     * @author jackson
     * @Date 2019-01-21 15:26:00
     * @return array()
     */
    public function get_product_line()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_product_line($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

}