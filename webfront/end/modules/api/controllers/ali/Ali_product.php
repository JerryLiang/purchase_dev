<?php
require APPPATH . 'core/MY_ApiBaseController.php';

class Ali_product extends MY_ApiBaseController {

    private $_modelObj;

    public function __construct(){
        parent::__construct();
        $this->load->model('ali/Ali_product_model');
        $this->_modelObj = $this->Ali_product_model;
    }

    public function preview_product_info(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        $data = $this->_modelObj->preview_product_info($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function get_product_info(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_product_info($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    public function relate_ali_sku(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->relate_ali_sku($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    public function remove_relate_ali_sku(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->remove_relate_ali_sku($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function refresh_product_info()
    {
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->refresh_product_info($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function get_ali_sample_product_info()
    {
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_ali_sample_product_info($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function get_pdt_tongkuan()
    {
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_pdt_tongkuan($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }
}