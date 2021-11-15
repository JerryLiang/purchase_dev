<?php

/* 1688 账号管理
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Account_ail extends MY_Controller
{
   public function __construct(){
        parent::__construct();
        $this->load->model('Account_ail_model');
    }
    
    /**
     * 1688账号列表
     * @author harvin
     * @date 2019-06-24
     * @desc /system/Account_ail/account_list
     */
    public function account_list(){
        $params = gp();
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0)
            $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $reslut = $this->Account_ail_model->get_account_list($params, $offsets, $limit, $page);
        $this->success_json($reslut);
    }
    /**
    * 创建1688主账号
    * @author harvin
    * @date 2019-06-25
    */
     public function add_account(){
      $params     = gp();
      if(!isset($params['account']) || empty($params['account'])){
          $this->error_json('账号必填');
      }  
      if(!isset($params['app_key']) || empty($params['app_key'])){
          $this->error_json('app_key必填');
      } 
      if(!isset($params['secret_key']) || empty($params['secret_key'])){
          $this->error_json('签名密钥必填');
      }      
       if(!isset($params['status']) || empty($params['status'])){
          $this->error_json('请选择状态');
      }
      try {
         $res= $this->Account_ail_model->get_add_account($params);
         if($res){
             $this->success_json([],NULL,'创建成功');
         }
      } catch (Exception $exc) {
          $this->error_json($exc->getMessage());   
      }
    }
    /**
     * 1688账号授权
     * @author harvin
     * @date 2019-06-25
     */
    public function account_oauth(){
      $id=  $this->input->get_post('id');
      if(empty($id)){
           return $this->error_json('参数不对');
      }
      try {            
         $data= $this->Account_ail_model->get_account_oauth($id);
         $this->success_json($data,null,'请求成功');
      } catch (Exception $exc) {
           $this->error_json($exc->getMessage());
      } 
    }
    
    /**
     * 1688账号更新-显示
     * @author harvin
     * @date 2019-06-25
     */
    public function account_update() {
        $id = $this->input->get_post('id');
        if (empty($id)) {
            return $this->error_json('参数不对');
        }
        try {
            $reslut = $this->Account_ail_model->get_list($id);
            $this->success_json($reslut, null, '请求成功');
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }
    /**
    * 更新保存
    *@author harvin
    *@date 2019-06-26 
    */
    public function account_update_save() {
       $params = gp();
        if (!isset($params['account']) || empty($params['account'])) {
            $this->error_json('账号必填');
        }
        if (!isset($params['app_key']) || empty($params['app_key'])) {
            $this->error_json('app_key必填');
        }   
        if (!isset($params['secret_key']) || empty($params['secret_key'])) {
            $this->error_json('签名密钥必填');
        }
        if (!isset($params['status']) || empty($params['status'])) {
            $this->error_json('请选择状态');
        }
        if (!isset($params['id']) || empty($params['id'])) {
            $this->error_json('id参数不存在');
        }
        unset($params['uid']);
        try {
            $res = $this->Account_ail_model->get_update_code($params);
            if ($res) {
                $this->success_json([], NULL, '更新成功');
            }
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }
    /**
     * 1688主账号删除数据
     * @author harvin
     * @date 2019-06-26
     */
    public function account_del(){
        $id = $this->input->get_post('id');
        if (empty($id)) {
            return $this->error_json('参数不对');
        }
        try {
            $reslut = $this->Account_ail_model->get_account_del($id);
            $this->success_json([], null, '删除成功');
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }
    
    
    
    
}
