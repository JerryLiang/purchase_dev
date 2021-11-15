<?php

/**
 * Created by PhpStorm.
 * 采购需求控制器
 * User: Jackson
 * Date: 2018/12/27 0027 11:17
 */
class Supplier_product_line extends MY_Controller
{

    protected $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Supplier_product_line_model');
        $this->_modelObj = $this->Supplier_product_line_model;
    }

    /**
     * @desc 获取产品线
     * @author Jackson
     * @Date 2019-01-21 17:01:00
     * @return array()
     **/
    public function get_product_line()
    {
        $parames   = gp();
        $parent_id = (isset($parames['parent_id']) && $parames['parent_id']) ? $parames['parent_id'] : 0;

        if (!empty($parent_id)) {
            $this->load->model('product/Product_line_model', 'Product_line_model');
            $data = $this->Product_line_model->get_product_line_list($parent_id);//产品线级别
            $this->send_data(['list' => $data], '供应商级别列表', true);
        }

        $this->send_data(NULL, '供应商级别列表', true);
    }


}