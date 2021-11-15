<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/01/14
 * Time: 10:25
 */

class Check_product_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 验货规则-列表
     * @author Justin
     * @date 2019/11/30
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function get_rule_list($params)
    {
        $url = $this->_baseUrl . $this->_ruleListUrl;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 验货规则-获取编辑数据
     * @author Justin
     * @date 2020/04/13
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function get_edit_data($params)
    {
        $url = $this->_baseUrl . $this->_ruleDataUrl;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 验货规则-批量编辑
     * @author Justin
     * @date 2019/11/30
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function rule_batch_edit($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_ruleEditUrl;
        return $this->request_appdal($params,$url,'POST');
    }

    /**
     * 验货管理-列表
     * @author Justin
     * @date 2019/11/30
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function get_data_list($params)
    {
        $url = $this->_baseUrl . $this->_listUrl;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 验货管理-创建验货申请
     * @param $params
     * @return array|mixed
     */
    public function create_inspection($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_createInspectionUrl;
        return $this->request_appdal($params,$url,'POST');
    }

    /**
     * 验货管理-根据验货ID获取数据（采购确认和编辑页面展示数据）
     * @param $params
     * @return array|mixed|null
     */
    public function get_order_detail($params)
    {
        $url = $this->_baseUrl . $this->_orderDetailUrl;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 验货管理-获取PO详情
     * @param $params
     * @return array|mixed|null
     */
    public function get_po_detail($params)
    {
        $url = $this->_baseUrl . $this->_poDetailUrl;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 验货管理-手工创建验货单-验证验货PO是否属于等待到货及之后的状态
     * @param $params
     * @return array|mixed
     */
    public function check_po_status($params)
    {
        $url = $this->_baseUrl . $this->_checkPoStatusUrl;
        return $this->request_appdal($params,$url,'POST');
    }

    /**
     * 验货管理-采购确认
     * @param $params
     * @return array|mixed
     */
    public function order_confirm($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_orderConfirmUrl;
        return $this->request_appdal($params,$url,'POST');
    }

    /**
     * 验货管理-导出
     * @param $params
     * @return array|mixed
     */
    public function data_list_export($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_exportUrl;
        return $this->request_appdal($params,$url,'POST');
    }

    /**
     * 验货管理-编辑
     * @param $params
     * @return array|mixed
     */
    public function order_edit($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_orderEditUrl;
        return $this->request_appdal($params,$url,'POST');
    }

    /**
     * 验货管理-转合格申请
     * @param $params
     * @return array|mixed
     */
    public function qualify_for_apply($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_qualifyForApplyUrl;
        return $this->request_appdal($params,$url,'POST');
    }

    /**
     * 验货管理-作废验货单
     * @param $params
     * @return array|mixed
     */
    public function make_order_invalid($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_makeOrderInvalidUrl;
        return $this->request_appdal($params,$url,'POST');
    }

    /**
     * 验货管理-操作日志
     * @param $params
     * @return array|mixed|null
     */
    public function get_log($params)
    {
        $url = $this->_baseUrl . $this->_getLogUrl;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 验货管理-获取验货报告
     * @param $params
     * @return array|mixed|null
     */
    public function get_report($params)
    {
        $url = $this->_baseUrl . $this->_getReportUrl;
        return $this->request_http($params, $url, 'GET', false);
    }
}