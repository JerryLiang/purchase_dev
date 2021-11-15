<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购控制器
 */

class Purchase_shipping_cancel_list extends MY_ApiBaseController {
    private $_modelObj;

    public function __construct() {
        parent::__construct();
        $this->load->model('purchase_shipment/Purchase_shipment_cancel_model');
        $this->_modelObj = $this->Purchase_shipment_cancel_model;
    }

    /**
     * 获取取消列表
     * @author Jaxton 2019/01/17
     */
    public function get_cancel_list()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_cancel_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }
    /**
     * 导出
     * /abnormal/report_loss/export_report_loss
     * @author Jaxton 2019/01/18
     */
    public function export_cancel_list(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->export_cancel_list($params);

    }



}