<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * 采购单历史数据修改控制器
 * User: luxu
 * Date: 2019/7/29 0027 11:17
 */
class Purchase_supplier_source extends MY_ApiBaseController {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/Purchase_supplier_source_old_model');
    }

    public function get_supplier_source() {

        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $this->Purchase_supplier_source_old_model->get_supplier_source($params);
    }
}