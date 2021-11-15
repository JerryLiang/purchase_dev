<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * 37539 异常处理模块，新增页面：供应商质量改善列表 #4
 * User: luxu
 * Date: 2021/07/27 10:00
 */

class Abnormal_quality_list extends MY_ApiBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('abnormal/Abnormal_quality_model','quality_model');
        $this->_modelObj = $this->quality_model;
    }

    public function get_Abnormal_list_data(){
        $this->_init_request_param("POST");

        $params = $this->_requestParams;

        $data = $this->_modelObj->get_Abnormal_list_data($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    public function add_Abnoral_list_data(){

        $this->_init_request_param("POST");

        $params = $this->_requestParams;

        $data = $this->_modelObj->add_Abnoral_list_data($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function handler_Abnoral_list_data(){

        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->handler_Abnoral_list_data($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);

    }

    public function Abnoral_log(){


        $this->_init_request_param("GET");

        $params = $this->_requestParams;

        $data = $this->_modelObj->Abnoral_log($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function import_Abnormal_list_data(){

        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->import_Abnormal_list_data($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function push_import_Abnormal_list_data(){

        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        if(!isset($params['file_path']) or empty($params['file_path'])){
            $this->sendError(1, "文件地址参数缺失");
        }
        $file_path = $params['file_path'];
        if(!file_exists($file_path)){
            $this->sendError(1, "文件不存在[{$file_path}]");
        }
        $params['file_path'] = $file_path;

        try {
            $data = $this->_modelObj->push_import_Abnormal_list_data($params);
            if($data['code']){
                $this->_code = 0;
                $this->_msg = $data['message'];
                $this->sendData();
            }else{
                $this->_code = 500;
                $this->_msg = $data['message'];
                $this->sendData(['error_file_path' => $data['data']]);
            }
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
}