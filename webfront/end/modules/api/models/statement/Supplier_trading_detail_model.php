<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-05-09
 * Time: 11:17
 */

class Supplier_trading_detail_model extends Api_base_model {

    protected $_listUrl = "";

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 供应商余额汇总表 - 交易明细
     */
    public function get_trading_detail_list($params){
        $url = $this->_baseUrl . $this->_getTradingDetailList;
        return $this->_curlReadHandleApi($url, $params, 'POST');
    }

    /**
     * 供应商余额汇总表 - 交易明细 - 导出
     */
    public function export_trading_detail_list($params){
        $url = $this->_baseUrl . $this->_exportTradingDetailList;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }


    /**
     * 供应商余额汇总表 - 交易明细 - 页面汇总
     */
    public function get_statistic_list($params){
        $url = $this->_baseUrl . $this->_getStatisticList;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

}