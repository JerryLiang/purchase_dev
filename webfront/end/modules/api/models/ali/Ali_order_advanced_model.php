<?php
/**
 * 1688操作方法
 * @author: Jolon
 * @Date: 2019/12/24 10:23
 */

class Ali_order_advanced_model extends Api_base_model {

    public function __construct(){
        parent::__construct();
        $this->init();
    }

    /**
     * 1688 一键拍单
     * @param $params
     * @return array|mixed|null
     */
    public function one_key_create_order($params)
    {
//        $url    = $this->_baseUrl.'_new'. $this->_oneKeyCreateOrder;
        $url    = $this->_baseUrl. $this->_oneKeyCreateOrder;
        $params['action'] = 2;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 1688 一键下单
     * @param $params
     * @return array|mixed|null
     */
    public function advanced_one_key_create_order($params)
    {
//        $url    = $this->_baseUrl.'_new'. $this->_advancedOneKeyCreateOrder;
        $url    = $this->_baseUrl. $this->_advancedOneKeyCreateOrder;
        $params['action'] = 1;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 1688 一键拍单
     * @param $params
     * @return array|mixed|null
     */
    public function one_key_create_order_new($params)
    {
        $url    = $this->_baseUrl.'_new'. $this->_oneKeyCreateOrder;
//        $url    = $this->_baseUrl. $this->_oneKeyCreateOrder;
        $params['action'] = 2;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 1688 一键下单
     * @param $params
     * @return array|mixed|null
     */
    public function advanced_one_key_create_order_new($params)
    {
        $url    = $this->_baseUrl.'_new'. $this->_advancedOneKeyCreateOrder;
//        $url    = $this->_baseUrl. $this->_advancedOneKeyCreateOrder;
        $params['action'] = 1;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    public function advanced_one_key_payout($params)
    {
        $url    = $this->_baseUrl . $this->_advancedOneKeyPayout;
        $result = $this->httpRequest($url, $params, 'POST',[CURLOPT_TIMEOUT => 300]);
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

}