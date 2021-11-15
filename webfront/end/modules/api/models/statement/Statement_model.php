<?php
/**
 * 对账单数据库模型类
 * User: Jaden
 * Date: 2019/01/08 10:23
 */

class Statement_model extends Api_base_model {


    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType();
    }


    /**
     * 创建对账单(第一步)
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function create_statement_preview($params)
    {
        $url = $this->_baseUrl . $this->_createStatementPreviewUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 入库批次 创建对账单（第二步）
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function create_statement($params)
    {
        $url = $this->_baseUrl . $this->_createStatementUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 对账单管理列表
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function get_statement_list($params)
    {
        $url = $this->_baseUrl . $this->_getStatementListUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 【作废】设置 对账单 是否有效
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function set_status_valid($params)
    {
        $url = $this->_baseUrl . $this->_setStatusValidUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 打印对账单 - 获取打印数据
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function get_print_statement_data($params)
    {
        $url = $this->_baseUrl . $this->_printStatementUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 打印对账单
     * @param $params
     */
    public function print_statement_tmp($params){
        $url = $this->_baseUrl . $this->_printStatementUrl ."?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 查看对账单详情
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function preview_statement_detail($params)
    {
        $url = $this->_baseUrl . $this->_previewStatementDetailUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 上传扫描件
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function upload_statement_pdf($params)
    {
        $url = $this->_baseUrl . $this->_uploadStatementPdfUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 上传扫描件
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function batch_upload_statement_pdf($params)
    {
        $url = $this->_baseUrl . $this->_batchUploadStatementPdfUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 下载对账单 PDF 文件
     * @param $params
     * @return array
     */
    public function download_statement_pdf($params){
        if(isset($params['appdal_cron']) && $params['appdal_cron']){
            //后端定时计划任务调api接口，不需要验证登陆
            $host = constant('CG_API_HOST_' . static::MODULE_NAME);
            $url=$host .$this->_downloadStatementHtmlCron;
        }else{
            $url=$this->_baseUrl . $this->_downloadStatementHtml;
        }
        return $this->request_http($params,$url,'GET',false);
    }

    /**
     * 下载对账单 EXCEL 文件
     * @param $params
     * @return array
     */
    public function download_statement_html($params){
        $url=$this->_baseUrl . $this->_downloadStatementHtml;
        return $this->request_http($params,$url,'GET',false);

    }

    /**
     * 下载对账单 EXCEL 文件
     * @param $params
     * @return array
     */
    public function download_statement_excel($params){
        $url=$this->_baseUrl . $this->_downloadStatementExcel;
        return $this->request_http($params,$url,'GET',false);
    }


    /**
     * 下载对账单运费明细 EXCEL 文件
     * @param $params
     * @return array
     */
    public function download_freight_details($params){
        $url=$this->_baseUrl . $this->_download_freight_details;
        return $this->request_http($params,$url,'GET',false);
    }

    /**
     * 采购确认扫描件
     * @param $params
     * @return mixed
     * @author Justin
     */
    public function confirm_statement_pdf($params)
    {
        $url = $this->_baseUrl . $this->_confirmStatementPdfUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 查看上传pdf操作日志
     * @param $params
     * @return mixed
     * @author Justin
     */
    public function get_operation_pdf_logs($params)
    {
        $url = $this->_baseUrl . $this->_getOperationPdfLogsUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    /**
     * 下载付款申请书
     * @param $params
     * @return mixed
     * @author Justin
     */
    public function get_statement_pay_requisition($params)
    {
        $url = $this->_baseUrl . $this->_getStatementPayRequisition;
        $url   .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET',false);
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    /**
     * 批量下载对账单
     * @param $get
     * @return array|mixed
     * @throws Exception
     */
    public function batch_download_statement($get){
        set_time_limit(0);
        //调用服务层api
        $url = $this->_baseUrl . $this->_batchDownloadStatement . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 下载对账单CSV
     * @param $get
     * @return array|mixed
     * @throws Exception
     */
    public function statement_export_csv($get){
        set_time_limit(0);
        //调用服务层api
        $url = $this->_baseUrl . $this->_statementExportCsv . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }


    /**
     * 甲方先盖章（甲方发起盖章）
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function initiator_start_flow($params)
    {
        $url = $this->_baseUrl . $this->_initiatorStartFlow;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    /**
     * 甲方对账人审核
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function statement_audit($params)
    {
        $url = $this->_baseUrl . $this->_statementAudit;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    /**
     * 甲方盖章
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function signfields_flow($params)
    {
        $url = $this->_baseUrl . $this->_signfieldsFlow;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    /**
     * 甲方催办
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function signflows_rushsign($params)
    {
        $url = $this->_baseUrl . $this->_signflowsRushSign;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    /**
     * 甲方撤销
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function signflows_revoke($params)
    {
        $url = $this->_baseUrl . $this->_signflowsRevoke;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    /**
     * 上传附属文件
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function upload_attachment_pdf($params)
    {
        $url = $this->_baseUrl . $this->_uploadAttachmentPdf;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }
}