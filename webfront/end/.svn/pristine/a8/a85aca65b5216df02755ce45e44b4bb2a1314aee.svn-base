<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-03-16
 * Time: 14:25
 */

class Purchase_return_receivable_model extends  Api_base_model{

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
        $this->load->helper('export_csv');
    }

    /**
     * list
     */
    public function get_list($get){
        set_time_limit(0);
        //调用服务层api
        $url = $this->_baseUrl . $this->_list . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
    /**
     * 详情
     */
    public function get_return_receivable_items($get){
        $url = $this->_baseUrl . $this->_items . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /***
     * 财务收款
     */
    public function click_receivables($get){
        $url = $this->_baseUrl . $this->_click_receivables . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 导出
     */
    public function export_receivables_list($get){
        $url = $this->_baseUrl . $this->_export_receivables . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 修改账号弹窗
     */
    public function modify_receiving_account($get){
        $url = $this->_baseUrl . $this->_modify_receiving_account . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }



}