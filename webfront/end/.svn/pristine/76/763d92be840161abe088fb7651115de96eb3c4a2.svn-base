<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/5
 * Time: 10:58
 */
require APPPATH . 'core/MY_ApiBaseController.php';

class Purchase_financial_audit_list extends MY_ApiBaseController
{

    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/Purchase_financial_audit_list_model');
        $this->_modelObj = $this->Purchase_financial_audit_list_model;
    }

    /**
     * 列表
     * @author Manson
     */
    public function financial_audit_list()
    {
        $params = $this->_requestParams;
        $data   = $this->_modelObj->invoice_listing_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg  = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 单条详情
     * @author Manson
     */
    public function audit_invoice_detail()
    {
        $params = $this->_requestParams;
        $data   = $this->_modelObj->audit_invoice_detail($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg  = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 批量详情
     * @author Manson
     */
    public function batch_audit_invoice_detail()
    {
        $params = $this->_requestParams;
        $data   = $this->_modelObj->batch_audit_invoice_detail($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg  = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 导出
     * @author Manson
     */
    public function export_list()
    {
        try {
            $this->_init_request_param('GET');
            $params = $this->_requestParams;
            $this->_modelObj->export_list($params);
        }catch ( Exception $exp ) {

            $this->sendData(array('status'=>0,'errorMessage'=>$exp->getMessage()));
        }
    }

    /**
     * 审核
     */
    public function batch_audit()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->batch_audit($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * @function: 任务编号：09631 【报关开票】流程调整:增加订单作废功能。增加已作废/已推送退税系统的状态
     * 申请作废接口
     * @author:luxu
     * @time:2020/8/4
     **/

    public function toVoid(){

        $params = $this->_requestParams;
        $data = $this->_modelObj->toVoid($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * @function: 任务编号：09631 【报关开票】流程调整:增加订单作废功能。增加已作废/已推送退税系统的状态
     * 申请作废申请
     * @author:luxu
     * @time:2020/8/4
     **/

    public function ToVoidAudit(){

        $params = $this->_requestParams;
        $data = $this->_modelObj->ToVoidAudit($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);

    }

    /**
     * 开票状态=未开票的，用户可以点击【无法开票】，否则报错：备货单号**只有开票状态=未开票才可点击
     * @author:luxu
     * @time:2020/8/11
     **/

    public function unableToInvoice(){

        $params = $this->_requestParams;
        $data = $this->_modelObj->unableToInvoice($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);

    }

}