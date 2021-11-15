<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Payment_order_contract_pay_model extends Api_base_model
{
     public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }
      /**
     * 合同单批量审核
     * @author harvin
     * @date 2019/4/4 
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_audit_info($get)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_audit_infoApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
     /**
     * 合同单批量审核
     * @author harvin
     * @date 2019/4/4 
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
     public function get_contract_order_save($post){
        $url = $this->_baseUrl . $this->_batch_auditApi; 
        return $this->_curlWriteHandleApi($url, $post, 'POST');
     }
     /**
      * 线下支付---确认页
      * @author harvin
      * @param type $get
      * @return mixed|array
      */
     public function get_offline_payment_info($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_payment_infoApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
     }
     /**
     * 合同单批量审核
     * @author harvin
     * @date 2019/4/7
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
     public function get_offline_payment_save($post){
         $url = $this->_baseUrl . $this->_offline_payment_saveApi; 
        return $this->_curlWriteHandleApi($url, $post, 'POST');
     }
      /**
     * 合同单批量审核
     * @author harvin
     * @date 2019/4/8
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
      public function get_offline_payment_reject($post){
        $url = $this->_baseUrl . $this->offline_payment_rejectApi; 
        return $this->_curlWriteHandleApi($url, $post, 'POST');
      }


      /**
      * 富友线上支付
      * @author harvin
      * @param type $get
      * @return mixed|array
      */
     public function get_online_payment($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_online_paymentApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
     }
    
}
