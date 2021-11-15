<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/10/31
 * Time: 17:52
 */
require APPPATH . 'core/MY_ApiBaseController.php';

class Purchase_order_tracking extends MY_ApiBaseController
{

    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/Purchase_order_tracking_model');
        $this->_modelObj = $this->Purchase_order_tracking_model;
    }

    public function logistics_trace_list()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_logistics_trace_list($params);
        if (empty($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    public function get_express_url()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_express_url($params);
        if (empty($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 根据快递单号获取轨迹详情
     */
    public function logistics_track_detail()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_logistics_track_detail($params);
        if (empty($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 刷新轨迹状态
     */
    public function refresh_logistics_state()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->refresh_logistics_state($params);
        if (empty($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }
}
