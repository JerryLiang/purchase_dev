<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/24
 * Time: 14:58
 */
require APPPATH . 'core/MY_ApiBaseController.php';

class Statement_order_pay extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('finance/Statement_order_pay_model');
        $this->_modelObj = $this->Statement_order_pay_model;
    }


    /**
     * 对账单-创建请款单-数据预览（第一步）
     * @author Jolon
     * @desc array $_POST['statement_number']         要请款对账单号
     */
    public function statement_pay_order_preview(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->statement_pay_order_preview($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 对账单-创建请款单-付款申请书预览（第二步）
     * @author Jolon
     */
    public function statement_pay_order_preview_requisition_payment(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->statement_pay_order_preview_requisition_payment($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 对账单-创建请款单（第三步）
     * @author Jolon
     */
    public function statement_pay_order_create(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->statement_pay_order_create($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 请款单、请款审核 查看对账单详情
     * @author Jolon
     */
    public function pay_statement_detail(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->pay_statement_detail($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

}