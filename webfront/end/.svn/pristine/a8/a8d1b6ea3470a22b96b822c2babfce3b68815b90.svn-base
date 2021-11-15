<?php

/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2018/12/27 0027 11:23
 */
class Purchase_order_tax_model extends Api_base_model {

    protected $table_name = 'purchase_order';

    /**
     * Purchase_order_model constructor.
     */
    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 获取 含税订单
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-8
     */
    public function tax_order_tacking_list($params) {
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['limit']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_listUrl;
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

    public function tax_order_tacking_sum($params){

        $url = $this->_baseUrl . $this->_tax_order_tacking_sum;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        return $result;
    }
    /**
     * 获取 含税订单导出
     * @author Jaden
     * @param $params
     * 2019-1-8
     */
    public function drawback_export_list($params){
        $url = $this->_baseUrl . $this->_exportUrl;
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
     * 获取 点击入库数量弹出的列表
     * @author Jaden
     * @param $params
     * 2019-1-8
     */
    public function web_warehousing_list($params){
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['limit']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_warehousingUrl;
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
     * 获取 点击已报关数量弹出的列表
     * @author Jaden
     * @param $params
     * 2019-1-8
     */
    public function web_declare_customs_list($params){
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['limit']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_declareUrl;
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
     * 获取 点击已开票弹出的列表
     * @author Jaden
     * @param $params
     * 2019-1-8
     */
    public function web_invoiced_list($params){
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['limit']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_invoicedlUrl;
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
     * 生成发票清单
     * @author Jaden
     * @param $params
     * 2019-1-8
     */
    public function web_generate_invoice_list($params){
        // 2.调用接口
        $url = $this->_baseUrl . $this->_createinvoicelistingUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['errorMess'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }




    /**
     * 获取 点击库龄弹出的列表
     * @author Jaden
     * @param $params
     * 2019-1-8
     */
    public function web_library_age_list($params){
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['limit']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_libraryUrl;
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

    public function export_list($params)
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');
        ini_set('pcre.backtrack_limit', -1);
        $url = $this->_baseUrl . $this->_exportListUrl;
        $result = $this->_curlWriteHandleApi($url, $params, "POST");

        if( !empty($result) && isset($result['data_list']) ) {
//            header('access-Control-Allow-Origin:*');
            header('location:' . $result['data_list']);
        }else if( !empty($result) && !isset($result['status'])) {
            throw new Exception("导出错误");
        }
    }

    /**
     * 获取待采购审核数据
     * @MTHOD GET
     * @author :luxu
     * @time:2020/5/18
     **/

    public function get_purchase_review($invoice_number){

        $url = $this->_baseUrl . $this->_get_purchase_review;
        $result = $this->_curlWriteHandleApi($url, $invoice_number, "POST");
        return $result;
    }

    /**
     * 驳回接口
     * @MTHOD POST
     * @author:luxu
     * @time:2020/5/19
     **/

    public function reject($params){

        $url = $this->_baseUrl . $this->_reject;
        $result = $this->_curlWriteHandleApi($url, $params, "POST");
        return $result;
    }

    /**
     *  上传发票图片
     *  @METHODS  POST
     *  @author:luxu
     *  @time: 2020/5/19
     **/
    public function uplodeImage($params){

        $url = $this->_baseUrl . $this->_uplodeImage;
        $result = $this->_curlWriteHandleApi($url, $params, "POST");
        return $result;
    }

    public function getImage($params){

        $url = $this->_baseUrl . $this->_getImage;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        return $result;
    }
}
