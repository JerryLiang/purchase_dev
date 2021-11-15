<?php
/**
 * Created by PhpStorm.
 * 产品基础信息表
 * User: Jolon
 * Date: 2018/12/29 0029 11:50
 */
class Alternative_suppliers_model extends Api_base_model
{

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 添加备选供应商接口
     * @METHOD POST
     * @author:luxu
     * @time:2021年4月22号
     **/
    public function add_alternative_supplier($params){

        $url = $this->_baseUrl . $this->_add_alternative_supplier;
        $params['sku'] = json_encode($params['sku']);
        $result = $this->httpRequest($url, $params, 'POST');
        return $result;
    }

    public function get_alternative_boxdata($params){

        $url = $this->_baseUrl.$this->_get_alternative_boxdata."?".http_build_query($params);
        return $this->httpRequest($url,'','GET');
    }

    public function get_alternative_supplier($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_get_alternative_supplier. "?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    public function save_alternative_supplier($params){
        $url = $this->_baseUrl . $this->_save_alternative_supplier;
        $result = $this->httpRequest($url, $params, 'POST');
        return $result;
    }

    public function alternative_supplier_examine($params){

        $url = $this->_baseUrl . $this->_alternative_supplier_examine. "?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    public function audit_alternative_supplier($params){
        $url = $this->_baseUrl . $this->_audit_alternative_supplier;
        $result = $this->httpRequest($url, $params, 'POST');
        return $result;

    }

    public function get_alternative_log($params){
        $url = $this->_baseUrl . $this->_get_alternative_log. "?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');

    }

    public function alternative_import($params){

        $url = $this->_baseUrl . $this->_alternative_import. "?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
}
