<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-07-09
 * Time: 9:27
 */

class Lakala_pay  extends  MY_ApiBaseController{

    private $_modelObj;

    public function __construct(){
        parent::__construct();
        $this->load->model('finance/Lakala_pay_model');
        $this->_modelObj = $this->Lakala_pay_model;
    }



    /**
     *  应付提交支付接口
     */
    public function lakala_submission(){
        //POST提交情况下
        try {
            $data = $this->_modelObj->lakala_submission($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 界面列表
     */
    public function LaKaLa_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->LaKaLa_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     *
     * 审核时显示的列表
     *
     */
    public function audit_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->audit_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 拉卡拉驳回接口
     */
    public function lakala_reject(){
        try {
            $data = $this->_modelObj->lakala_reject($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 操作审核后将数据注册到拉卡拉
     * Totoro
     * 2020-06-16
     * 拉卡拉:批量代付注册接口
     * 易佰：拉卡拉界面审核
     * batchPay/registry
     */
    public function batchPay_registry(){
        try {
            $data = $this->_modelObj->batchPay_registry($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     *  拉卡拉导出接口
     */
    public function lakala_export(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->lakala_export($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     *  拉卡拉导出接口
     */
    public function refresh_pay_status(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->refresh_pay_status($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
}