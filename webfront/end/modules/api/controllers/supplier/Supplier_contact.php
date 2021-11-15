<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * [function 获取供应商-联系方式]
 * @author Jackson
 * @param
 * @DateTime 2019/1/21
 */
class Supplier_contact extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier/Supplier_contact_model');
        $this->_modelObj = $this->Supplier_contact_model;
    }

    /**
     * @desc 供应商-联系方式
     * @author jackson
     * @Date 2019-01-21 15:26:00
     * @return array()
     */
    public function get_contact()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_contact($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        if(isset($data[0]) and empty($data[0])){
            $this->_code = '404';
            $this->_msg = isset($data[1])?$data[1]:'操作失败';
        }

        $this->sendData($data);
    }


    /**
     * @desc 供应商-翻译
     * @author jackson
     * @Date 2019-01-21 15:26:00
     * @return array()
     */
    public function translate_supplier_info()
    {
        try
        {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->translate_supplier_info($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

}