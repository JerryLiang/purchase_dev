<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购控制器
 */


class Purchase_shipping extends MY_ApiBaseController {

    private $_modelObj;

    public function __construct() {
        parent::__construct();
        $this->load->model('purchase_shipment/Purchase_shipment_model');
        $this->_modelObj = $this->Purchase_shipment_model;
    }

    public function getshippingment(){

        try{
            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $data = $this->_modelObj->getshippingment($params);
            $this->sendData($data);
        }catch ( Exception $exp ){


        }
    }

    /**
     * 二验交期确认
     * POST
     * @author:luxu
     * @time:2020/4/20
     **/
    public function updateShipmentTime(){

        try{

            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $data = $this->_modelObj->updateShipmentTime($params);
            $this->sendData($data);

        }catch ( Exception $exp ){

            $this->sendError(-1, $exp->getMessage());
        }
    }

    /**
     * 获取修改日志
     * GET
     * @author:luxu
     * @time:2020/4/20
     **/

    public function getUpdateLog(){

        try{

            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $data = $this->_modelObj->getUpdateLog($params);
            $this->sendData($data);

        }catch ( Exception $exp ){

            $this->sendError(-1, $exp->getMessage());
        }
    }

    /**
     *  获取变更日志信息
     *  @Mthod GET
     *  @author:luxu
     *  @time:2020/4/20
     **/

    public function getChangeLog(){

        try{

            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $data = $this->_modelObj->getChangeLog($params);
            $this->sendData($data);
        }catch ( Exception $exp ){

            $this->sendError(-1, $exp->getMessage());
        }
    }

    /**
     *  获取计划变更日志信息
     *  @Mthod GET
     *  @author:luxu
     *  @time:2020/4/20
     **/
    public function getPlangChangeLog(){

        try{

            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $data = $this->_modelObj->getPlangChangeLog($params);
            $this->sendData($data);
        }catch ( Exception $exp ){

            $this->sendError(-1, $exp->getMessage());
        }
    }

    /**
     *  审核
     *  @Mthod POST
     *  @author:luxu
     *  @time:2020/4/21
     **/
    public function toExamineShipping(){

        try{

            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $data = $this->_modelObj->toExamineShipping($params);
            $this->sendData($data);
        }catch ( Exception $exp ){

            $this->sendError(-1, $exp->getMessage());
        }
    }

    public function updateDelivery(){

        try{

            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $data = $this->_modelObj->updateDelivery($params);
            $this->sendData($data);
        }catch ( Exception $exp ){

            $this->sendError(-1, $exp->getMessage());
        }
    }

    /**
     * 下载CSV 格式
     * @MTHODS  GET
     **/
    public function getshippingcsv(){

        try{

            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $data = $this->_modelObj->getshippingcsv($params);
            $this->sendData($data);
        }catch ( Exception $exp ){

            $this->sendError(-1, $exp->getMessage());
        }
    }

    /**
     * 下载EXCEL 格式
     * @MTHODS  GET
     **/
    public function getshippingexcel(){

        try{

            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $data = $this->_modelObj->getshippingexcel($params);
            $this->sendData($data);
        }catch ( Exception $exp ){

            $this->sendError(-1, $exp->getMessage());
        }
    }

    /**
     * 发运类型审核列表
     * @MTHODS GET
     * @AUTHOR:LUXU
     * @time:2020/7/3
     **/
    public function showToExamineShipping(){

        try{

            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $data = $this->_modelObj->showToExamineShipping($params);
            $this->sendData($data);

        }catch ( Exception $exp ){

            $this->sendError(-1, $exp->getMessage());
        }
    }
}