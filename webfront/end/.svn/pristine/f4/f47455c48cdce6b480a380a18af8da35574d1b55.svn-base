<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020/3/10
 * Time: 14:54
 */

class Purchase_return_tracking_model extends  Api_base_model{

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
        $this->load->helper('export_csv');
    }

    /**
     * @param $get
     * @return array|mixed
     * @throws Exception
     */
    public function get_storage_collection_list($get){
        set_time_limit(0);
        //调用服务层api
        $url = $this->_baseUrl . $this->_tracking_list . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 供应商签收
     */
    public function  check_receipt($get){
        $url = $this->_baseUrl . $this->_check_receipt . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 保存上传截图
     */
    public function  save_upload($get){
        $url = $this->_baseUrl . $this->_save_upload . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 获取物流商信息
     */
    public function  express_info($get){
        $url = $this->_baseUrl . $this->_express_info . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 录入快递单号
     */
    public function  save_express($get){
        $url = $this->_baseUrl . $this->_save_express . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 获取详情信息
     */
    public function item_list($get){
        $url = $this->_baseUrl . $this->_item_list . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 查询退货轨迹
     */
    public function get_logistics_trajectory($get){
        $url = $this->_baseUrl . $this->_logistics_list . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 导出
     * @param $get
     * @return array|mixed
     * @throws Exception
     */
    public function export_storage_data($get){
        $url = $this->_baseUrl . $this->_export . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

}