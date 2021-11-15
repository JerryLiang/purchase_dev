<?php
/**
 * 1688操作方法
 * @author: yefanli
 * @Date: 2020/11/11
 */

class Ali_order_advanced_new_model extends Api_base_model {

    public function __construct(){
        parent::__construct();
        $this->init();
    }


    public function get_handle_create_list($params)
    {
        $url = $this->_baseUrl . $this->_getHandleCreateList;
        return $this->httrequest($params, $url);
    }


    public function get_handle_create_order_list($params)
    {
        $url = $this->_baseUrl . $this->_getHandleCreateOrderList;
        return $this->httrequest($params, $url);
    }


    public function one_key_create_order($params)
    {
        $url = $this->_baseUrl . $this->_advancedOneKeyCreateOrder;
        $params['action'] = 2;
        return $this->httrequest($params, $url);
    }


    public function advanced_one_key_create_order($params)
    {
        $url = $this->_baseUrl . $this->_advancedOneKeyCreateOrder;
        $params['action'] = 1;
        return $this->httrequest($params, $url);
    }


    public function one_key_create_purchase($params)
    {
        $url = $this->_baseUrl.'/one_key_create_purchase';
        $params['action'] = 3;
        return $this->httrequest($params, $url);
    }

}