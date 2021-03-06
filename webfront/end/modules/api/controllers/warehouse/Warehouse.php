<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 物流系统获取仓库信息
 */


class Warehouse extends MY_ApiBaseController
{

    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('warehouse/Warehouse_model');
        $this->_modelObj = $this->Warehouse_model;
        $this->config->load('url_img', FALSE, TRUE);
    }
    public function get_warehouse_data()
    {

        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $result = $this->_modelObj->get_warehouse_data($params);
        $this->sendData($result);
    }

    /**
     * 获取采购系统仓库信息
     **/
    public function get_warehouse_list()
    {

        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $result = $this->_modelObj->get_warehouse_list($params);
        $this->sendData($result);
    }

    /**
     * 设置仓库地址
     **/
    public function set_warehouse_address()
    {

        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $result = $this->_modelObj->set_warehouse_address($params);
        $this->sendData($result);
    }

    /**
     * 获取仓库信息修改信息日志
     **/

    public function get_warehouse_log()
    {
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $result = $this->_modelObj->get_warehouse_log($params);
        $this->sendData($result);
    }

    /**
     * 设置仓库地址
     **/
    public function get_fright_rule()
    {

        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $result = $this->_modelObj->get_fright_rule($params);
        $this->sendData($result);
    }

    /**
     * 设置仓库地址
     **/
    public function create_fright_rule()
    {

        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $result = $this->_modelObj->create_fright_rule($params);
        $this->sendData($result);
    }

    /**
     * 设置仓库地址
     **/
    public function set_fright_rule_batch()
    {

        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $result = $this->_modelObj->set_fright_rule_batch($params);
        $this->sendData($result);
    }
}