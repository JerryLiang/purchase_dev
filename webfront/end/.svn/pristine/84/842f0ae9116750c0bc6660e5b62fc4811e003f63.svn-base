<?php
require APPPATH . 'core/MY_ApiBaseController.php';

class Ali_quota_payment extends MY_ApiBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ali/Ali_quota_payment_model');
        $this->_modelObj = $this->Ali_quota_payment_model;
    }

    /**
     * 1688 额度付款
     */
    public function ali_quota_payment()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->ali_quota_payment($params);
        $this->sendData($data);
    }
}