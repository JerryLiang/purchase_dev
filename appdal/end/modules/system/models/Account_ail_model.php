<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Account_ail_model extends Purchase_model {
    
      protected $table_name = 'alibaba_account';
    

    public function __construct() {
        parent::__construct();
        $this->load->model('supplier_buyer_model', '', false, 'supplier');
        $this->load->helper('status_order');
}
  /**
   * 1688主账号列表
   * @author harvin
   * @date 2019-06-24
   * @param type $params
   * @param type $offsets
   * @param type $limit
   * @param type $page
   * @return array
   */
    public function get_account_list($params,$offsets=0, $limit=20,$page=1){
        $query=$this->purchase_db;
        $query->select('id,account,access_token,refresh_token,status,app_key,secret_key');
        $query->from($this->table_name);
        if(isset($params['account']) && trim($params['account'])){
            $query->where('account',trim($params['account']));
        }
       if(isset($params['access_token']) && trim($params['access_token'])){
            $query->where('access_token',trim($params['access_token']));
        }
        if(isset($params['refresh_token']) && trim($params['refresh_token'])){
            $query->where('refresh_token',trim($params['refresh_token']));
        }
        if(isset($params['app_key']) && trim($params['app_key'])){
            $query->where('app_key',trim($params['app_key']));
        }
         if(isset($params['secret_key']) && trim($params['secret_key'])){
            $query->where('secret_key',trim($params['secret_key']));
        }
        if(isset($params['status']) && trim($params['status'])){
            $query->where('status',trim($params['status']));
        }
        $count_qb = clone $query;
        $result = $query->limit($limit, $offsets)->order_by('id','desc')->get()->result_array();
        //统计总数要加上前面筛选的条件
        $count_row = $count_qb->select("count(id) as num")->get()->row_array();
        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        $data_list['status_list']= getAccountstatus();
        $key_table = [
                '账号',
                '访问令牌',
                'refresh_token',
                '状态',
                'app_key',
                '签名密钥',
                '操作'];
         $return_data = [
                'drop_down_box' =>$data_list,
                'key' => $key_table,
                'values' => $result,
                'page_data' => [
                    'total' => $total_count,   
                    'offset' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total_count / $limit)
                ]
            ];
       return $return_data; 
    }
   /**
    * 创建1688主账号
    * @author harvin
    * @date 2019-06-25
    * @param type $params
    * @return boolean
    * @throws Exception
    */
    public function get_add_account($params){
       $data=[
           'account'=>$params['account'],
           'app_key'=>$params['app_key'],
           'secret_key'=>$params['secret_key'],
           'status'=>$params['status']
       ];   
     $resulst= $this->purchase_db->insert($this->table_name,$data); 
     if(!$resulst){
         throw new Exception('插入记录失败');
     }else{
        $id=$this->purchase_db->insert_id();
        $this->purchase_db->where('id',$id)->update($this->table_name,['redirect_uri'=>CG_SYSTEM_APP_DAL_IP.'Account_api/update?id='.$id]);   
        return TRUE;
     } 
    }
    /**
     * 1688授权
     * @param type $id
     */
    public function get_account_oauth($id){
      $account_list=$this->purchase_db
                ->select('redirect_uri')
                ->where('id',$id)
                ->get($this->table_name)
                ->row_array();
    if(empty($account_list)) {
        throw new Exception('参数id,不存在');
    }   
        
    $data=[
        'url'=>$account_list['redirect_uri'],
    ];    
    return $data;
  }
  /**
   * 获取1688 授权
   * @param array $params
   * @return boolean
   * @throws Exception
   */
  public function get_update_code($params){  
      $id=$params['id'];
      unset($params['id']);
    $res=  $this->purchase_db
              ->where('id',$id)
              ->update($this->table_name,$params);
    if($res){
        return TRUE;
     }else{
         throw new Exception('参数id,不存在');
     } 
      
  }
  /**
   * 获取数据
   * @author harvin
   * @date 2019-06-26
   * @param int $id
   * @return boolean
   * @throws Exception
   */
  public function get_list($id){
      $res=  $this->purchase_db
              ->where('id',$id)
              ->get($this->table_name)->result_array();
    if($res){
        return $res;
     }else{
         throw new Exception('参数id,不存在');
     }  
  }
  /**
   * 删除数据
   * @param type $id
   * @return boolean
   * @throws Exception
   */
  public function get_account_del($id){
     $inds= $this->purchase_db
             ->where('id',$id)
             ->delete($this->table_name);
     if($inds){
         return TRUE;
     }else{
         throw new Exception('删除失败');
     }   
  }
  
}
