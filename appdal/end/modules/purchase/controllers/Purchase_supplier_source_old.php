<?php

/**
 * Created by PhpStorm.
 * 采购单供应商来源历史数据修改
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */
class Purchase_supplier_source_old extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_supplier_source_old_model');
    }

    public function set_supplier() {

        $params               = $this->input->get_post('code');
        $result = $this->purchase_supplier_source_old_model->set_supplier( $params );
    }
}