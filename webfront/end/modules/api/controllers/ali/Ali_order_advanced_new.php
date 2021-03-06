<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * 1688操作方法
 * @author: yefanli
 * @Date: 2020/11/11
 */

class Ali_order_advanced_new extends MY_ApiBaseController{

	public function __construct(){
        parent::__construct();
        $this->load->model('ali/Ali_order_advanced_new_model');
        $this->_modelObj = $this->Ali_order_advanced_new_model;
    }

    public function get_handle_create_list(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_handle_create_list($params);
        $this->sendData($data);
    }

    public function get_handle_create_order_list(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_handle_create_order_list($params);
        $this->sendData($data);
    }

}