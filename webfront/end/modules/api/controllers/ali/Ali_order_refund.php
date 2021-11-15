<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * 1688操作方法
 * @author: yefanli
 * @Date: 2020/12/09
 */

class Ali_order_refund extends MY_ApiBaseController
{
    public function __construct(){
        parent::__construct();
        $this->load->model('ali/Ali_order_refund_model');
        $this->_modelObj = $this->Ali_order_refund_model;
    }

    /**
     * 获取1688 退款退货信息
     */
    public function get_order_refund_data()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_order_refund_data($params);
        $this->sendData($data);
    }

    /**
     * 保存1688 退款退货信息
     */
    public function save_order_refund_data()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->save_order_refund_data($params);
        $this->sendData($data);
    }

    /**
     * 保存1688 退款退货原因
     */
    public function get_order_refund_reason()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_order_refund_reason($params);
        $this->sendData($data);
    }

    /**
     * 获取退款退货列表
     */
    public function get_order_refund_list()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_order_refund_list($params);
        $this->sendData($data);
    }
}