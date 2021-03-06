<?php
/**
 * Created by PhpStorm.
 * 仓库管理
 * User: Jaden
 * Date: 2018/12/29 0029 11:50
 */
class Warehouse_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
      * 刷新仓库数据
     **/

    public function get_warehouse_data( $params ) {

        $url = $this->_baseUrl . $this->_getwarehousedata;
        $result = $this->_curlWriteHandleApi($url, $params, "POST");
        return $result;
    }


    /**
      * 获取仓库信息接口
     **/
    public function get_warehouse_list($params) {

        $url = $this->_baseUrl.$this->_get_warehouse_list;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        return $result;
    }

    /**
      * 设置仓库地址
     **/

    public function set_warehouse_address( $params ) {

        $url = $this->_baseUrl.$this->_set_warehouse_address;
        $result =  $this->httpRequest($url, $params, "POST");
        return $result;
    }

    public function get_warehouse_log( $params ) {

        $url = $this->_baseUrl.$this->_get_warehouse_log;

        $result = $this->_curlWriteHandleApi($url, $params, "POST");
        return $result;


    }

    /**
     * 设置仓库地址
     **/

    public function get_fright_rule( $params ) {

        $url = $this->_baseUrl.$this->_get_fright_rule;
        $result = $this->httpRequest($url, $params, "POST");
        return $result;
    }

    /**
     * 设置仓库地址
     **/

    public function create_fright_rule( $params ) {

        $url = $this->_baseUrl.$this->create_fright_rule;
        $result = $this->httpRequest($url, $params, "POST");
        return $result;
    }

    /**
     * 设置仓库地址
     **/

    public function set_fright_rule_batch( $params ) {

        $url = $this->_baseUrl.$this->set_fright_rule_batch;
        $result = $this->httpRequest($url, $params, "POST");
        return $result;
    }

}