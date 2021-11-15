<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购控制器
 */


class Purchase_label extends MY_ApiBaseController {

    /** @var Purchase_order_model */
    private $_modelObj;

    public function __construct() {
        parent::__construct();
        $this->load->model('purchase/Purchase_label_model');
        $this->_modelObj = $this->Purchase_label_model;
        $this->config->load('url_img', FALSE, TRUE);
    }

    //标签列表
    public function get_label_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_label_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }


    }


    //条码列表
    public function get_barcode_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_barcode_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }


    }



    /**
     * function:供应商承诺是否贴码
     **/

    public function provider_promise_barcode()
    {
        try
        {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->provider_promise_barcode($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }



    /**
     * 推送备货单到仓库获取反馈信息
     **/

    public function send_wms_label()
    {
        try
        {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->send_wms_label($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }


    /*
      * 推送标签条码标签到门户系统
      * $type int 1为label,2为产品条码
      */

    public function send_provider_label()
    {
        try
        {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->send_provider_label($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    /**
     *
     *
     */
    public function export_label(){
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->export_label($params);
        $tax_list_tmp = $data['data_list'];
        $heads = ['订单状态','备货单状态','备货单号','sku','采购单号','合同单号','供应商名称','采购数量','是否退税','是否已生成标签','物流标签内容','获取失败原因','采购已下载','供应商是否已下载','仓库推送时间','下单时间','是否更新','目的仓',
            '新目的仓','目的仓是否更新','已启用门户系统','发运类型','是否计划系统推送'];
        csv_export($heads,$tax_list_tmp,'标签列表-'.date('YmdH_i_s'));
    }


    public function export_barcode(){
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->export_barcode($params);
        $tax_list_tmp = $data['data_list'];
        $heads = ['订单状态','备货单状态','备货单号','sku','采购单号','合同单号','供应商名称','采购数量','是否退税','是否已生成条码','产品条码内容','条码是否唯一','获取失败原因','采购已下载','供应商已下载','仓库推送时间','下单时间','是否更新','目的仓','新目的仓','目的仓是否更新','已启用门户系统','发运类型','是否承诺贴码','是否计划系统推送'];

        csv_export($heads,$tax_list_tmp,'条码列表-'.date('YmdH_i_s'));
    }



    //条码列表
    public function get_combine_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_combine_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }


    }

    public function export_combine(){
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->export_combine($params);
        $tax_list_tmp = $data['data_list'];
        $heads = ['订单状态','备货单状态','备货单号','sku','采购单号','合同单号','供应商名称','采购数量','是否退税','是否已生成条码','产品条码内容','条码是否唯一','获取失败原因','采购已下载','供应商已下载','仓库推送时间','下单时间','是否更新',
            '目的仓','新目的仓','仓库是否更新','已启用门户系统','发运类型','是否承诺贴码','是否计划系统推送'];

        csv_export($heads,$tax_list_tmp,'二合一标签列表-'.date('YmdH_i_s'));
    }


    /**
     * 推送二合一标签到仓库获取反馈信息
     **/

    public function send_wms_combine_label()
    {
        try
        {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->send_wms_combine_label($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }


























}
