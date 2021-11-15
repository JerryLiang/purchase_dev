<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Baofoo_fopay_model extends Api_base_model
{
    
    public function __construct() {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

     /**
     * 待宝付审核列表
     * @author harvin
     * @date 2019/8/10 
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_baofoo_list($get){   
        //调用服务层api
        $url = $this->_baseUrl . $this->_getBaofoapylistApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
     /**
     * 宝付导出
     * @author harvin
     * @date 2019/8/12 
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_boofoo_fopay_export($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_getBaofoapylistexportApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
    /**
     * 待宝付提交
     * @author harvin
     * @date 2019/8/10 
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
    public function get_baofoo_submission($post){
        $url = $this->_baseUrl . $this->_getBaosubApi;
        $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs; 
    }
     /**
     * 宝付支付批量（显示）
     * @author harvin
     * @date 2019/8/10 
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_baofoo_batch_info($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_batch_infoApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
    /**
     * 宝付在线付款
     * @author harvin
     * @date 2019/8/10 
     * @param array $post
     * @return mixed|array
     * @throws Exception
     * /Baofoo_fopay/baofoo_fopay
     */
    public function get_baofoo_fopay($post){
        $url = $this->_baseUrl . $this->_baofoo_fopayApi;
        $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;       
    }
    
    /**
     * 宝付驳回
     * @author harvin
     * @date 2019/8/10 
     * @param array $post
     * @return mixed|array
     * @throws Exception
     * /Baofoo_fopay/baofoo_fopay
     */
    public function get_baofoo_fopay_reject($post){
        $url = $this->_baseUrl . $this->_baofoo_rejectfopayApi;
        $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;      
    }

    /**
     * 手动刷新宝付支付状态
     * @param $get
     * @return array|mixed
     * @throws Exception
     */
    public function update_pay_baofoo_status($get){
        $url = $this->_baseUrl . $this->_update_pay . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
}