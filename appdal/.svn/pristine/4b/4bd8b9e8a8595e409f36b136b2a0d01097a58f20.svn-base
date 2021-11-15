<?php

/**
 * Created by PhpStorm.
 * 采购需求控制器
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */
class Supplier_buyer extends MY_Controller
{

    protected $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Supplier_buyer_model');
        $this->_modelObj = $this->Supplier_buyer_model;
    }

    /**
     * @desc 获取下拉采购员列表
     * @author Jackson
     * @Date 2019-01-21 17:01:00
     * @return array()
     **/
    public function get_buyer()
    {
        $params = gp();
        $data = $this->_modelObj->get_buyers($params);
        $this->send_data($data, '采购员列表', true);
    }

}