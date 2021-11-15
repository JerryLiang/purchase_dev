<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购控制器
 */

class Purchase_shipping_report_loss extends MY_ApiBaseController {

    private $_modelObj;

    public function __construct() {
        parent::__construct();
        $this->load->model('purchase_shipment/Purchase_shipping_report_loss_model');
        $this->_modelObj = $this->Purchase_shipping_report_loss_model;
    }

    /**
     * 获取报损数据列表
     * /abnormal/report_loss/get_report_loss_list
     * @author Jaxton 2019/01/17
     */
    public function get_report_loss_list(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_report_loss_list($params);

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
    public function export_report_loss(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        $data = $this->_modelObj->export_report_loss($params);

    }



}