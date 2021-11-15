<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Account_ail_model extends Api_base_model
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
    public function get_account_list($get){
         //调用服务层api
        $url = $this->_baseUrl . $this->_receListApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET'); 
    }
    
    /**
    * 创建1688主账号
    * @author harvin
    * @date 2019-06-25
    */
     public function add_account($post){
      //调用服务层api
        $url = $this->_baseUrl . $this->_receivableSaveApi;
        $resp = $this->_curlWriteHandleApi($url, $post, 'POST');
        $resp['status'] = 1;
        $resp['errorMess'] = $this->_errorMsg;
        return $resp;
    }
     /**
     * 授权
     * @author harvin
     * @date 2019/6/27 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_account_oauth($get){
         //调用服务层api
        $url = $this->_baseUrl . $this->_receOauthApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET'); 
    }
     /**
     * 更新显示
     * @author harvin
     * @date 2019/6/27 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_account_update($get){
         //调用服务层api
        $url = $this->_baseUrl . $this->_receUpdateApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET'); 
    }
     /**
    * 保存1688主账号
    * @author harvin
    * @date 2019-06-25
    */
    public function get_account_update_save($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_receivupdatesaveApi;
        $resp = $this->_curlWriteHandleApi($url, $post, 'POST');
        $resp['status'] = 1;
        $resp['errorMess'] = $this->_errorMsg;
        return $resp;
    }
      /**
     * 删除数据
     * @author harvin
     * @date 2019/6/27 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_account_del($get){
          //调用服务层api
        $url = $this->_baseUrl . $this->_receDelApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET'); 
        
    }
}