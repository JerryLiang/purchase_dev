<?php

/**
 * 供应商管理 -> 供应商审核列表
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/24
 * Time: 16:30
 */
class Supplier_audit extends MY_Controller
{

    /** @var Supplier_audit_model */
    protected $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Supplier_audit_model');
        $this->_modelObj = $this->Supplier_audit_model;
    }

    /**
     * 供应商审核列表数据，分页和导出
     * @author: liwuxue
     * @date: 2019/1/24 17:14
     * @param:
     */
    public function audit_list()
    {
        set_time_limit(0);
        $resp = [];
        try {
            $param = gp();
            $resp = $this->_modelObj->get_audit_list($param);
        } catch (Exception $e) {
            $resp = ['msg'=>$e->getMessage()];
        }
        $this->send_data($resp, "供应商管理-->供应商审核列表", true);
    }

}