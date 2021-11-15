<?php

require APPPATH . 'core/MY_ApiBaseController.php';

class Work_desk extends MY_ApiBaseController
{
    private $_modelObj;
    public function __construct()
    {
        parent::__construct();
        $this->load->model('work_desk/Work_desk_model');
        $this->_modelObj = $this->Work_desk_model;
    }

    /**
     * 工作台页面数据
     * @url    /api/work_desk/Work_desk/get_data_list
     * @method GET
     * @doc
     * @author Justin
     * @date   2020/07/06
     */
    public function get_data_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_data_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 获取小组类型
     * @url    /api/work_desk/Work_desk/get_category_list
     * @method POST
     * @doc
     * @author Justin
     * @date   2020/07/06
     */
    public function get_category_list()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_category_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 根据小组类型获取小组
     * @url    /api/work_desk/Work_desk/get_group_by_category
     * @method POST
     * @doc
     * @author Justin
     * @date   2020/07/06
     */
    public function get_group_by_category()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_group_by_category($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 根据小组id获取采购员
     * @url    /api/work_desk/Work_desk/get_buyer_by_group
     * @method POST
     * @doc
     * @author Justin
     * @date   2020/07/06
     */
    public function get_buyer_by_group()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_buyer_by_group($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 各个模块数据刷新接口
     * @url    /api/work_desk/Work_desk/refresh_data
     * @method POST
     * @doc
     * @author Justin
     * @date   2020/07/11
     */
    public function refresh_data()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->refresh_data($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
}