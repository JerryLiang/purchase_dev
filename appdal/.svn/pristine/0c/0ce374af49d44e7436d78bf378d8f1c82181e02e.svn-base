<?php

/**
 * @function:采购单供应商来源历史数据修改
 * @author:luxu
 **/

class Purchase_supplier_source_old_model extends Purchase_model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/purchase_order_model'); // 采购单子表
    }

    public function set_supplier( $params ) {

        $supplier_code = $this->purchase_db->from("supplier")->where("search_status=1")->select("supplier_code,supplier_source")->get()->result_array();

        foreach( $supplier_code as $key=>$value ) {

            $this->purchase_order_model->update_relate_supplier_source( $value['supplier_code'],$value['supplier_source']);
        }
    }
}