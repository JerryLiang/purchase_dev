<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Account_sub_model extends Api_base_model
{   
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }
    
    
    
    
     /**
     * 列表页
     * @author harvin
     * @date 2019/6/27 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_sub_list($get){
         //调用服务层api
        $url = $this->_baseUrl . $this->_receListApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET'); 
    }
    /**
    * 创建1688子账号
    * @author harvin
    * @param array $post
    * @date 2019-06-25
    */
     public function get_add_sub($post){
      //调用服务层api
        $url = $this->_baseUrl . $this->_receivableSaveApi;
        $resp = $this->_curlWriteHandleApi($url, $post, 'POST');
        $resp['status'] = 1;
        $resp['errorMess'] = $this->_errorMsg;
        return $resp;
    }

    public function get_reduced_optimizing_user($post = array())
    {
        $url = $this->_baseUrl . $this->_get_reduced_optimizing_user;
        $resp = $this->_curlWriteHandleApi($url, $post, 'POST');
        return $resp;
    }
 
    /**
     * 修改显示
     * @author harvin
     * @date 2019/6/27 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_sub_edit($get){
          //调用服务层api
        $url = $this->_baseUrl . $this->_receUpdateApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET'); 
        
    }
    
    /**
    * 保存修改1688子账号
    * @author harvin
    * @param array $post
    * @date 2019-06-25
    */
     public function get_sub_edit_save($post){
      //调用服务层api
        $url = $this->_baseUrl . $this->_receivupdatesaveApi;
        $resp = $this->_curlWriteHandleApi($url, $post, 'POST');
        return $resp;
    }
      /**
     * 删除
     * @author harvin
     * @date 2019/6/27 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_sub_del($get){
          //调用服务层api
        $url = $this->_baseUrl . $this->_receDelApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET'); 
        
    }

    /**
     * 获取1688子账号信息接口
     *
     **/
    public function get_company_person( $params ) {

        $url = $this->_baseUrl . $this->_get_company_person . "?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
    
    
}