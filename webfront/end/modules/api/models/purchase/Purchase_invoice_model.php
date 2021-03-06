<?php
/**
 * Created by PhpStorm.
 * 开票清单
 * User: Jaden
 * Date: 2018/12/27 0027 11:23
 */

class Purchase_invoice_model extends Api_base_model {

    protected $table_name   = 'purchase_invoice_list';// 数据表名称
	protected $declare_customs_table = 'declare_customs';

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }


    /**
     * 获取 发票清单列
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-8
     */
    public function get_invoice_listing_list($params){
        $url=$this->_baseUrl . $this->_listUrl;
        return $this->request_http($params,$url);
    }

    /**
     * 发票清单列表提交弹出列表
     * @author Jaden 2019-1-10
     */
    public function web_submit_detail($params){
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['limit']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_submitDetailUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }


    /**
     * 发票清单列表财务审核弹出列表
     * @author Jaden 2019-1-10
     */
    public function web_submit_financial_audit_invoice_list($params){
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['limit']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_financialUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }

    /**
     * 发票清单列表提交操作
     * @author Jaden 2019-1-10
     */
    public function web_submit_invoice($params){
        // 2.调用接口
        $url = $this->_baseUrl . $this->_submitUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;    
    }


    /**
     * 发票清单列表撤销操作
     * @author Jaden 2019-1-10
     */
    public function web_revoke_invoice($params){
        // 2.调用接口
        $url = $this->_baseUrl . $this->_revokeUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;    
    }

    
    /**
     * 下载发票明细(导出)
     * @author Jaden 2019-1-10
     */
    public function web_download_export($params){
        $url = $this->_baseUrl . $this->_downloadexportUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');

        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }

     
      /**
     * 下载开票合同页面
     * @author Jaden 2019-1-10
     */
    public function web_download_view($params){
        $url = $this->_baseUrl . $this->_downloadviewUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');

        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }


     /**
     * 批量上传开票信息
     * @author Jaden 2019-1-10
     */
    public function web_download_import($import_arr){
        $url = $this->_baseUrl . $this->_downloadimportUrl;
        $url .= '?' . http_build_query($import_arr);
        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];   
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }

    /**
     * 导入
     */
    public function import_invoice_info($import_arr){
        $url = $this->_baseUrl . $this->_importInvoiceInfoUrl;
        $url .= '?' . http_build_query($import_arr);
        $result = $this->httpRequest($url, '', 'GET');
        // End
        return $result;
    }


    /**
     * 批量上传开票信息(下载模板)
     * @author Jaden 2019-1-10
     */
    public function web_download_import_model($params){
        $url = $this->_baseUrl . $this->_downloadimportmodelUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }


    public function web_get_batch_invoice_detail($params)
    {
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            100 :
            min(intval($params['limit']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_invoiceDetailUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
//        pr($result);exit;
        // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }


    public function web_batch_invoice_submit($params){
        $url= $this->_baseUrl . $this->_batchInvoiceSubmitUrl;
        return $this->request_http($params,$url,'GET',false);
    }

    public function download_invoice_excel($params)
    {
        $url=$this->_baseUrl . $this->_downloadInvoiceExcelUrl;
        $result =  $this->request_http($params,$url,'GET',false);
//        pr($result);exit;
        return $result;
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

    public function batch_submit($params){
        $url=$this->_baseUrl . $this->_batchSubmitUrl;

        $result = $this->request_appdal($params,$url,'POST');
        return $result;
    }
}