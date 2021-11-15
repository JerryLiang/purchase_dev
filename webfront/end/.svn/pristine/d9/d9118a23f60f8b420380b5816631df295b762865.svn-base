<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/5
 * Time: 11:01
 */
class Shipment_plan_cancel_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }


    public function list($params){
        $url=$this->_baseUrl . $this->_listUrl;
        $result = $this->request_appdal($params,$url,'GET');
        $result = $this->rsp_package($result);
        return $result;
    }

    public function export($params)
    {
        $url=$this->_baseUrl . $this->_exportUrl;
        $result = $this->request_appdal($params,$url,'GET');
        return $result;
    }

    public function cancel_detail($params)
    {
        $url=$this->_baseUrl . $this->_cancelDetailUrl;
        $result = $this->request_appdal($params,$url,'GET');
        return $result;
    }

    public function purchase_agree($params)
    {
        $url=$this->_baseUrl . $this->_purchaseAgreeUrl;
        $result = $this->request_appdal($params,$url,'POST');
        return $result;
    }

    public function purchase_reject($params)
    {
        $url=$this->_baseUrl . $this->_purchaseRejectUrl;
        $result = $this->request_appdal($params,$url,'POST');
        return $result;
    }
}