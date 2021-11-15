<?php
require APPPATH . 'core/MY_ApiBaseController.php';


class Purchase_return_goods extends MY_ApiBaseController
{

    /** @var Purchase_return_goods */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/Purchase_return_goods_model');
        $this->_modelObj = $this->Purchase_return_goods_model;
    }

    /**
     * 验证退货sku,并返回可退货的sku数据
     */
    public function verify_return_sku()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data=$this->_modelObj->verify_return_sku($params);
        $this->sendData($data);
    }

    /**
     * 提交入库退货申请
     */
    public function save_return_data_submit()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data=$this->_modelObj->save_return_data_submit($params);
        $this->sendData($data);
    }

}