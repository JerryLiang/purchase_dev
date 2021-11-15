<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/5
 * Time: 10:58
 */
require APPPATH . 'core/MY_ApiBaseController.php';

class Shipment_plan_cancel extends MY_ApiBaseController
{

    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_shipment/Shipment_plan_cancel_model');
        $this->_modelObj = $this->Shipment_plan_cancel_model;
    }

    /**
     * 申请明细-列表
     * /api/purchase_shipment/Shipment_plan_cancel/list
     * @author Manson
     */
    public function list()
    {

        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->list($params);
        http_response($data);
    }

    /**
     * 申请明细-列表
     * /api/purchase_shipment/Shipment_plan_cancel/export
     * @author Manson
     */
    public function export()
    {
        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->export($params);
        http_response($data);
    }

    /**
     * 点击采购同意,弹出详情信息
     * /api/purchase_shipment/Shipment_plan_cancel/cancel_detail
     * @author Manson
     */
    public function cancel_detail()
    {
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->cancel_detail($params);
        http_response($data);
    }

    /**
     * 采购同意
     * @author Manson
     */
    public function purchase_agree()
    {
        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->purchase_agree($params);
        http_response($data);
    }

    /**
     * 采购驳回
     * /api/purchase_shipment/Shipment_plan_cancel/purchase_reject
     * @author Manson
     */
    public function purchase_reject()
    {
        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->purchase_reject($params);
        http_response($data);
    }



}