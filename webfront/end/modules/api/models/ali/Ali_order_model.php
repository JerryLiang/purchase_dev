<?php
/**
 * 1688操作方法
 * @author: Jolon
 * @Date: 2019/12/24 10:23
 */

class Ali_order_model extends Api_base_model {

    public function __construct(){
        parent::__construct();
        $this->init();
    }

    public function one_key_order_preview($params)
    {
        $url    = $this->_baseUrl . $this->_oneKeyOrderPreviewUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function do_one_key_order($params)
    {
        $url    = $this->_baseUrl . $this->_doOneKeyOrderUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function batch_one_key_order_preview($params)
    {
        $url    = $this->_baseUrl . $this->_batchOneKeyOrderPreview;
        $result = $this->httpRequest($url, $params, 'POST',[CURLOPT_TIMEOUT => 300]);
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function batch_do_one_key_order($params)
    {
        $url    = $this->_baseUrl . $this->_batchDoOneKeyOrder;
        $result = $this->httpRequest($url, $params, 'POST',[CURLOPT_TIMEOUT => 300]);
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function get_ali_order_newest_price($params)
    {
        $url    = $this->_baseUrl . $this->_getAliOrderNewestPriceUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function one_key_order_confirm($params)
    {
        $url    = $this->_baseUrl . $this->_oneKeyOrderConfirmUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function one_key_order_submit($params)
    {
        $url    = $this->_baseUrl . $this->_oneKeyOrderSubmitUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function update_ali_receiving_address($params){
        $url    = $this->_baseUrl . $this->_updateAliReceivingAddressUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function get_ali_receiving_address($params){
        $url    = $this->_baseUrl . $this->_getAliReceivingAddressUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    public function get_cancel_ali_order($params){
        $url    = $this->_baseUrl . $this->_getCancelAliOrderUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    public function refresh_order_price($params){
        $url    = $this->_baseUrl . $this->_refreshOrderPrice;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    public function auto_one_key_order_submit($params){
        $url    = $this->_baseUrl . $this->_autoOneKeyOrderSubmit;
        $result = $this->httpRequest($url, $params, 'POST',[CURLOPT_TIMEOUT => 300]);// 设置超时时间 300秒
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function ali_batch_edit_order($params){
        $url    = $this->_baseUrl . $this->_aliBatchEditOrder;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    public function ali_batch_submit_order($params){
        $url    = $this->_baseUrl . $this->_aliBatchSubmitOrder;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function get_order_sku_infos($params){
        $url    = $this->_baseUrl . $this->_getOrderSkuInfos;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function refresh_ali_order_data($params){
        $url = $this->_baseUrl . $this->_getRefreshAliOrderDataUrl;
        $url   .= '?' . http_build_query($params);
        return $this->httpRequest($url, '', 'GET');
    }

    public function get_ali_order_just_in_time($params)
    {
        $url = $this->_baseUrl . $this->_getAliOrderJustInTime;
        $url   .= '?' . http_build_query($params);
        return $this->httpRequest($url, '', 'GET');
    }

    public function verify_ali_product_effective($params)
    {
        $url = $this->_baseUrl . $this->_verifyAliProductEffective;
        $url   .= '?' . http_build_query($params);
        return $this->httpRequest($url, '', 'GET');
    }
}