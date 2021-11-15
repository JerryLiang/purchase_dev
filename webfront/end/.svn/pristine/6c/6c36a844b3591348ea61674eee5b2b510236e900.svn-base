<?php require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * User: totoro
 * Date: 2020-04-21
 * Time: 17:25
 */

class Ufxfuiou_pay extends  MY_ApiBaseController{

    private $_modelObj;

    public function __construct(){
        parent::__construct();
        $this->load->model('finance/Ufxfuiou_pay_model');
        $this->_modelObj = $this->Ufxfuiou_pay_model;
    }

    /**
     * 列表数据
     */
    public function ufxfuiou_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->ufxfuiou_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     *  富友列表导出
     */
    public function ufxfuiou_export(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->ufxfuiou_export($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     * 获取富友审核数据
     */
    public function fuyou_batch_info(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->fuyou_batch_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     * 富友支付
     */
    public function ufxfuiou_pay(){
        try {
            $data = $this->_modelObj->ufxfuiou_pay($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 富友驳回
     */

    public function pay_reject(){
        try {
            $data = $this->_modelObj->pay_reject($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

}