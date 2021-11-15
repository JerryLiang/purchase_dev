<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/5
 * Time: 11:01
 */
class Purchase_financial_audit_list_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    public function invoice_listing_list($params){
        $url=$this->_baseUrl . $this->_listUrl;
        return $this->request_http($params,$url);
    }

    public function audit_invoice_detail($params){
        $url=$this->_baseUrl . $this->_auditDetailUrl;
        return $this->request_http($params,$url);
    }

    public function batch_audit_invoice_detail($params){
        $url=$this->_baseUrl . $this->_batchAuditDetailUrl;
        return $this->request_http($params,$url);
    }

    public function batch_audit($params){
        $url=$this->_baseUrl . $this->_batchAuditUrl;
        return $this->request_http($params,$url);
    }

    public function export_list($params)
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');
        ini_set('pcre.backtrack_limit', -1);

        $url = $this->_baseUrl . $this->_exportListUrl;
        $result = $this->_curlWriteHandleApi($url, $params, "POST");
//        pr($result);exit;
        if( !empty($result) && isset($result['data_list']) ) {
            header('location:' . $result['data_list']);
        }else if( !empty($result) && !isset($result['status'])) {
            throw new Exception("导出错误");
        }
    }

    /**
     * @function: 任务编号：09631 【报关开票】流程调整:增加订单作废功能。增加已作废/已推送退税系统的状态
     * 申请作废接口
     * @author:luxu
     * @time:2020/8/4
     **/

    public function toVoid($params){

        $url=$this->_baseUrl . $this->_toVoid;
        return $this->request_http($params,$url);
    }

    /**
     * @function: 任务编号：09631 【报关开票】流程调整:增加订单作废功能。增加已作废/已推送退税系统的状态
     * 申请作废审核接口
     * @author:luxu
     * @time:2020/8/4
     **/
    public function ToVoidAudit($params){

        $url=$this->_baseUrl . $this->_ToVoidAudit;
        return $this->request_http($params,$url);
    }

    /**
     * 开票状态=未开票的，用户可以点击【无法开票】，否则报错：备货单号**只有开票状态=未开票才可点击
     * @author:luxu
     * @time:2020/8/11
     **/
    public function unableToInvoice($params){
        $url=$this->_baseUrl . $this->_unableToInvoice;
        return $this->request_http($params,$url);
    }
}