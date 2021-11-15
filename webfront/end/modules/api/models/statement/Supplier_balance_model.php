<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-05-09
 * Time: 11:17
 */

class Supplier_balance_model extends Api_base_model {

    protected $_listUrl = "";

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 日报表
     */
    public function day_supplier_balance_list($params){
        $url = $this->_baseUrl . $this->_day_list. "?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 月报表
     */
    public function month_supplier_balance_list($params){
        $url = $this->_baseUrl . $this->_month_list;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 季度报表
     */
    public function quarter_supplier_balance_list($params){
        $url = $this->_baseUrl . $this->_quarter_list;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 年度报表
     */
    public function year_supplier_balance_list($params){
        $url = $this->_baseUrl . $this->_year_list;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 导出
     * @param $params
     * @return array|mixed|null
     */
    public function balance_export($params){
        set_time_limit(0);
        //调用服务层api
        $url = $this->_baseUrl . $this->_balance_export;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 汇总
     * @param $params
     * @return array|mixed|null
     */
    public function get_statistic_list($params){
        set_time_limit(0);
        //调用服务层api
        $url = $this->_baseUrl . $this->_getStatisticList;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 公司主体
     */
    public function get_purchase_agent_info($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_purchase_agent;
        return $this->request_http($params, $url, 'GET',false);
    }

}