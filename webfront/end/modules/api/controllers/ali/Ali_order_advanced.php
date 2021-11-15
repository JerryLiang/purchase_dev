<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * 1688操作方法
 * @author: Jolon
 * @Date: 2019/12/24 10:23
 */

class Ali_order_advanced extends MY_ApiBaseController{

	public function __construct(){
        parent::__construct();
        $this->load->model('ali/Ali_order_advanced_model');
        $this->_modelObj = $this->Ali_order_advanced_model;
    }

    public function one_key_create_order(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->one_key_create_order($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    public function advanced_one_key_create_order(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->advanced_one_key_create_order($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function one_key_create_order_new(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->one_key_create_order_new($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    public function advanced_one_key_create_order_new(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->advanced_one_key_create_order_new($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function advanced_one_key_payout(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->advanced_one_key_payout($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

}