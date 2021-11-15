<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-07-09
 * Time: 9:27
 */

class Offline_refund  extends  MY_ApiBaseController
{

    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('finance/Offline_refund_model');
        $this->_modelObj = $this->Offline_refund_model;
    }

    /**
     * 线下退款列表
     * @methods GET
     * @author:luxu
     * @time:2021年1月12号
     **/

    public function get_offline_refund(){

        try {
            $this->_init_request_param('POST');
            $data = $this->_modelObj->get_offline_refund($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    public function add_offline_refund(){

        $this->_init_request_param('POST');
        $params = $this->_requestParams;

        $data = $this->_modelObj->add_offline_refund($params);
        $this->sendData($data);
    }

    public function get_offline_refund_import(){

        $this->_init_request_param('POST');
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_offline_refund_import($params);
        $this->sendData($data);
    }

    /**
     * 作废申请
     * @author:luxu
     * @time:2021年1月14号
     **/
    public function cancel_refund(){

        $this->_init_request_param('POST');
        $params = $this->_requestParams;

        $data = $this->_modelObj->cancel_refund($params);
        $this->sendData($data);
    }

    /**
     * 获取退款日志接口
     * @params
     * @author:luxu
     * @time:2021年1月14号
     **/
    public function get_refund_logs(){

        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_refund_logs($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    public function get_offline_reason(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_offline_reason($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }

    }

    /**
     * 获取1688拍单号
     * 1688拍单号根据填写的采购单号抓取"采购单"页面的同字段内容;采购单号无拍单号的.
     * 显示为空,采购单号为多个时,抓取第一个拍单号;若第一个为空,顺位抓取下一个拍单号;若全部为空,则显示为空
     * @methods  GET
     * @author:luxu
     * @time:2021年1月15号
     **/
    public function get_pai_number(){

        $this->_init_request_param('POST');
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_pai_number($params);
        $this->sendData($data);
    }
}