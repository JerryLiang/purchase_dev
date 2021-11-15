<?php

/**
 * Created by PhpStorm.
 * 获取供应商结算方式
 * User: Jackson
 * Date: 2018/12/27 0027 11:17
 */
class Supplier_settlement extends MY_Controller
{

    protected $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Supplier_settlement_model');
        $this->_modelObj = $this->Supplier_settlement_model;
    }

    /**
     * @desc 获取供应商结算方式
     * @author Jackson
     * @Date 2019-01-21 17:01:00
     * @return array()
     **/
    public function get_settlement()
    {
        $params = gp();
        $data = $this->_modelObj->get_settlement($params);
        $this->send_data($data, '供应商结算方式', true);
    }  
}