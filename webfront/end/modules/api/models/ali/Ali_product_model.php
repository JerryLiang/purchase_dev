<?php
/**
 * PDF操作类
 * User: Jaxton
 * Date: 2019/01/14 10:23
 */

class Ali_product_model extends Api_base_model {

    public function __construct(){
        parent::__construct();
        $this->init();
    }

    public function preview_product_info($params){
        $url = $this->_baseUrl . $this->_previewProductInfoUrl;
        $url   .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function get_product_info($params){

        $url = $this->_baseUrl . $this->_getProductInfoUrl;
        $url   .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    public function relate_ali_sku($params){
        $url    = $this->_baseUrl . $this->_relateAliSkuUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function remove_relate_ali_sku($params){
        $url    = $this->_baseUrl . $this->_removeRelateAliSkuUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    public function refresh_product_info($params){
        $url    = $this->_baseUrl . $this->_refreshProductInfoUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    public function get_ali_sample_product_info($params){
        $url    = $this->_baseUrl . $this->_getAliSampleInfo;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    public function get_pdt_tongkuan($params){
        $url    = $this->_baseUrl . $this->_getPdtTongkuan;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

}