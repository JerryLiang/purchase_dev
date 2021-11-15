<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-04-21
 * Time: 17:23
 */

class Ufxfuiou_pay_model extends Api_base_model {

    public function __construct() {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 列表数据
     */
    public function ufxfuiou_list($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_ufxfuiou_list . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
    /**
     *  富友列表导出
     */
    public function ufxfuiou_export($get){
        $url = $this->_baseUrl . $this->_ufxfuiou_export . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
    /**
     * 获取富友审核数据
     */
    public function fuyou_batch_info($get){
        $url = $this->_baseUrl . $this->_batch_info . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
    /**
     * 富友支付
     *//**/
    public function ufxfuiou_pay($post){
        $url = $this->_baseUrl . $this->_ufxfuiou_pay;
        $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;
    }

    /**
     * 富友驳回
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function pay_reject($post){
        $url = $this->_baseUrl . $this->_pay_reject;
        $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;
    }


}