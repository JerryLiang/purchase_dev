<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * Created by PhpStorm.
 * 同款货源控制器
 * User: Jolon
 * Date: 2021/11/09 0027 11:17
 */
class Product_similar extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('product/Product_similar_model');
        $this->_modelObj = $this->Product_similar_model;
    }


    /**
     * 获取 配置项
     */
    public function get_similar_config()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        try {
            $data = $this->_modelObj->get_similar_config($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 修改 配置项
     */
    public function save_similar_config()
    {
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        try {
            $data = $this->_modelObj->save_similar_config($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取同款货源列表数据（四合一页面）
     */
    public function get_similar_list()
    {

        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        try {
            $data = $this->_modelObj->get_similar_list($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }

    }

    /**
     * 获取 同款货源数据详情
     */
    public function get_similar_detail()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        try {
            $data = $this->_modelObj->get_similar_detail($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 移除 同款货源记录
     */
    public function delete_similar()
    {
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        try {
            $data = $this->_modelObj->delete_similar($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 分配人员 同款货源记录
     */
    public function allot_user_similar()
    {
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        try {
            $data = $this->_modelObj->allot_user_similar($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 审核 同款货源记录
     */
    public function audit_similar()
    {
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        try {
            $data = $this->_modelObj->audit_similar($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取操作日志
     */
    public function get_similar_logs()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        try {
            $data = $this->_modelObj->get_similar_logs($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 获取 同款货源推荐详情
     */
    public function get_similar_history_detail()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        try {
            $data = $this->_modelObj->get_similar_history_detail($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }

    }

    /**
     * 导出数据
     */
    public function similar_export()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        try {
            $data = $this->_modelObj->similar_export($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

}