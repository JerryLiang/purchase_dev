<?php

require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * Created by PhpStorm.
 * User: luxu
 * Date: 2020/2/22
 * Time: 14:21
 */
class Data_center extends MY_ApiBaseController
{
    /** @var Bank_card_model */
    private $_modelObj;
    private $_centerObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('system/Data_control_config_model');
        $this->load->model('system/Center_Data_model');
        $this->_modelObj = $this->Data_control_config_model;
        $this->_centerObj = $this->Center_Data_model;

    }

    public function getConfiguration()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->getConfiguration($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
      * 审核接口
     **/
    public function downExamine()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_centerObj->downExamine($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     *删除数据接口
     **/
    public function delete_center_data()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_centerObj->delete_center_data($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
}