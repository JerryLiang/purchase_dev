<?php
require APPPATH . 'core/MY_ApiBaseController.php';

class Ali_order extends MY_ApiBaseController{

	public function __construct(){
        parent::__construct();
        $this->load->model('ali/Ali_order_model');
        $this->_modelObj = $this->Ali_order_model;
    }

    public function one_key_order_preview(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->one_key_order_preview($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    public function do_one_key_order(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->do_one_key_order($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function batch_one_key_order_preview(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->batch_one_key_order_preview($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    public function batch_do_one_key_order(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->batch_do_one_key_order($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    public function get_ali_order_newest_price(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_ali_order_newest_price($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function one_key_order_confirm(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->one_key_order_confirm($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function one_key_order_submit(){
        $this->_init_request_param("REQUEST");
        $params = $this->_requestParams;

        //是否推送蓝凌系统
        $this->config->load('url_img', FALSE, TRUE);
        if($this->config->item('purchasing_order_audit') === true){
            $params['purchasing_order_audit'] = 1;
        }else{
            $params['purchasing_order_audit'] = 2;
        }

        $data = $this->_modelObj->one_key_order_submit($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    public function update_ali_receiving_address(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->update_ali_receiving_address($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    public function get_ali_receiving_address(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_ali_receiving_address($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }


    public function get_cancel_ali_order(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_cancel_ali_order($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }


    public function refresh_order_price(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->refresh_order_price($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    public function auto_one_key_order_submit(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->auto_one_key_order_submit($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    public function ali_batch_edit_order(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->ali_batch_edit_order($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }


    public function ali_batch_submit_order(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        //是否推送蓝凌系统
        $this->config->load('url_img', FALSE, TRUE);
        if($this->config->item('purchasing_order_audit') === true){
            $params['purchasing_order_audit'] = 1;
        }else{
            $params['purchasing_order_audit'] = 2;
        }

        $data = $this->_modelObj->ali_batch_submit_order($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    public function get_order_sku_infos(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_order_sku_infos($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    public function refresh_ali_order_data(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->refresh_ali_order_data($params);
        $this->sendData($data);
    }

    public function get_ali_order_just_in_time()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_ali_order_just_in_time($params);
        $this->sendData($data);
    }

    public function verify_ali_product_effective()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->verify_ali_product_effective($params);
        $this->sendData($data);
    }
}