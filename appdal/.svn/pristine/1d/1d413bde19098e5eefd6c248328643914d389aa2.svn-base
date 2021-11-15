<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Account_sub_model extends Purchase_model {
    
      protected $table_name = 'alibaba_sub';
    

    public function __construct() {
        parent::__construct();
        $this->load->model('user/Purchase_user_model');
        $this->load->helper('status_order');
    }
    /**
     * 子账号列表
     * @param array $params
     * @param int $offsets
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function get_sub_list($params, $offsets, $limit, $page){
        $query=$this->purchase_db;
        $query->select('id,p_account,account,user_name,pay_user_name,status,level,dep_name,prev_dep_name');
        $query->from($this->table_name);
        if(isset($params['account']) && trim($params['account'])){
            $query->where('account',trim($params['account']));
        }
       if(isset($params['p_account']) && trim($params['p_account'])){
            $query->where('p_account',trim($params['p_account']));
        }
        if(isset($params['level']) && trim($params['level'])){
            $query->where('level',trim($params['level']));
        }
        if(isset($params['user_id']) && trim($params['user_id'])){
            $query->where('user_id',trim($params['user_id']));
        }
        if(isset($params['pay_user_id']) && trim($params['pay_user_id'])){
            $query->where('pay_user_id',trim($params['pay_user_id']));
        }

        if( isset($params['dep_id']) && trim( $params['dep_id'])) {

            $query->where('dep_id',trim( $params['dep_id']));
        }
        $count_qb = clone $query;
        $result = $query->limit($limit, $offsets)->order_by('id','desc')->get()->result_array();
        if( !empty($result) ) {

            foreach( $result as $key=>&$value ) {

                $value['dep_name'] = $value['dep_name']."+".$value['prev_dep_name'];
                unset($value['prev_dep_name']);
            }
        }
        //统计总数要加上前面筛选的条件
        $count_row = $count_qb->select("count(id) as num")->get()->row_array();
        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        $this->load->model('user/Purchase_user_model');
//        $data =  $this->Purchase_user_model->getCompanyAllPerson();
        $finance = $this->Purchase_user_model->get_finance_list();
//        $data_list['buy_user']= is_array($data)?array_column($data, 'name','id'):[];;
        $data_list['buy_user']= [];
        $data_list['status_list']= getAccountstatus();
        $data_list['status_level']= getAccountsSublevel();
        $data_list['finance_list']= is_array($finance)?array_column($finance, 'name','id'):[];
        $data_list['company_dep_list']=$this->Purchase_user_model->getCompanyDep();
        $key_table = [
                '主账号',
                '子账号',
                '使用者',
                '付款人',
                '状态',
                '级别',
                '部门',
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
     * 获取1688子账号信息接口
     * @param: $client_name     string   客户端传姓名
     * @result:  array
     **/
    public function get_company_person( $client_name ) {
        $data =  $this->Purchase_user_model->getCompanyAllPerson();
        if( empty($client_name) && !empty($data)) {

            return array_slice($data,0,10);
        }

        if( !empty($client_name) && !empty($data)) {
            $result_data = [];
            foreach( $data as $key=>$value ) {
                if( strpos($value['name'],$client_name)!==False ) {

                    $result_data[] = $value;
                }
            }
            return $result_data;
        }

        return [];
    }
    /**
     * 添加子账号
     * @author harvin
     * @param srting $account
     * @param int $user_id
     * @param int $pay_user_id
     * @param int $status
     * @return boolean
     * @throws Exception
     */
    public function save_sub($account,$user_id,$pay_user_id,$level,$dep_id = NULL,$dep_name=NULL,$prev_dep_name=NULL){
           //调用采购接口 获取采购员名
        $this->load->model('user/Purchase_user_model');
        $resurs = $this->Purchase_user_model->get_user_info($user_id);
        if($resurs==FALSE){
           throw new Exception('使用者信息OA不存在'); 
        }
        $resur = $this->Purchase_user_model->get_user_info($pay_user_id);
        if($resur==FALSE){
           throw new Exception('付款人信息OA不存在'); 
        }
        $data=[
            'p_account'=>'yibaisuperbuyers',
            'account' =>$account,
            'user_id' =>$user_id,
            'user_name'=>$resurs['user_name'],
            'user_number'=>$resurs['staff_code'],
            'pay_user_id'=>$pay_user_id, 
            'level'      =>$level,
            'pay_user_name'=>$resur['user_name'],
            'add_time'=>date('Y-m-d H:i:s'),
            'dep_id' => $dep_id,
            'prev_dep_name' => $prev_dep_name,
            'dep_name' => $dep_name
        ];
      $res= $this->purchase_db->insert($this->table_name,$data);
      if($res){
          $id = $this->purchase_db->insert_id();
          $this->load->config('api_config', FALSE, TRUE);
          $erp_system = $this->config->item('java_system_alibabasub');
          $send_url = $erp_system['url']."?access_token=". getOASystemAccessToken();
          $subMessage = $this->purchase_db->from($this->table_name)->where("id",$id)->get()->row_array();

          $data = [

              "id" => $id,
              "pAccount" => 'yibaisuperbuyers',
              "account" =>$account,
              "userId" => $user_id,
              "userName" =>$resurs['user_name'],
              'userNumber'=>$resurs['staff_code'],
              'status' => $subMessage['status'],
              'groupId' => $subMessage['group_id'],
              'pid' => $subMessage['pid'],
              'payUserId'=>$pay_user_id,
              'level'      =>$level,
              'payUserName'=>$resur['user_name'],
              'addTime'=>date('Y-m-d H:i:s'),
              'depId' => $dep_id,
              'prevDepName' => $prev_dep_name,
              'depName' => $dep_name
          ];
          $result = send_http($send_url,json_encode($data));
          unset($data);
          unset($resurs);
          unset($resur);
         return true;
      }else{
          throw new Exception('添加失败'); 
      }  
        
    }
    
    
   /**
    * 获取修改的信息
    * @author harvin
    * @date 2019-06-27
    * @param int $id
    * @return type
    * @throws Exception
    */
    public function get_sub_list_row($id){
       $sub_row= $this->purchase_db
                ->select("*")->where('id',$id)
                ->get($this->table_name)
                ->row_array();
       if(empty($sub_row)){
           throw new Exception('参数id,不存在');
       } 
      return $sub_row;
    }
    
    /**
     * 保存修改信息
     * @author harvin
     * @param srting $account
     * @param int $user_id
     * @param int $pay_user_id
     * @param int $status
     * @param int $id
     * @return boolean
     * @throws Exception
     */
    public function sub_edit_save($account,$user_id,$pay_user_id,$level,$id,$dep_id = NULL,$dep_name=NULL,$prev_dep_name=NULL){
             //调用采购接口 获取采购员名
        $this->load->model('user/Purchase_user_model');
        $resurs = $this->Purchase_user_model->get_user_info($user_id);
        if($resurs==FALSE){
           throw new Exception('使用者信息OA不存在'); 
        }
         $resur = $this->Purchase_user_model->get_user_info($pay_user_id);
        if($resur==FALSE){
           throw new Exception('付款人信息OA不存在'); 
        }
        $data=[
            'p_account'=>'yibaisuperbuyers',
            'account' =>$account,
            'user_id' =>$user_id,
            'user_name'=>$resurs['user_name'],
            'user_number'=>$resurs['staff_code'],
            'pay_user_id'=>$pay_user_id,
            'level'      =>$level,
            'pay_user_name'=>$resur['user_name'],
            'add_time'=>date('Y-m-d H:i:s'),
            'dep_id' => $dep_id,
            'prev_dep_name' => $prev_dep_name,
            'dep_name' => $dep_name
        ];
      $res= $this->purchase_db->where('id',$id)->update($this->table_name,$data);
      if($res){

          $this->load->config('api_config', FALSE, TRUE);
          $erp_system = $this->config->item('java_system_alibabasub');
          $send_url = $erp_system['url']."?access_token=". getOASystemAccessToken();
          $subMessage = $this->purchase_db->from($this->table_name)->where("id",$id)->get()->row_array();
          $data = [

              "id" => $id,
              "pAccount" => 'yibaisuperbuyers',
              "account" =>$account,
              "userId" => $user_id,
              "userName" =>$resurs['user_name'],
              'userNumber'=>$resurs['staff_code'],
              'status' => $subMessage['status'],
              'groupId' => $subMessage['group_id'],
              'pid' => $subMessage['pid'],
              'payUserId'=>$pay_user_id,
              'level'      =>$level,
              'payUserName'=>$resur['user_name'],
              'addTime'=>date('Y-m-d H:i:s'),
              'depId' => $dep_id,
              'prevDepName' => $prev_dep_name,
              'depName' => $dep_name
          ];
          $result = send_http($send_url,json_encode($data));

          unset($data);
          unset($resurs);
          unset($resur);
          return true;
      }else{
          throw new Exception('修改失败'); 
      }  
    }
    
    /**
     * 删除数据
     * @author harvin
     * @param int $id
     * @return boolean
     * @throws Exception
     */
    public function get_sub_del_row($id){
        $res=$this->purchase_db->where('id',$id)->delete($this->table_name);
       if($res){

           $this->load->config('api_config', FALSE, TRUE);
           $erp_system = $this->config->item('java_system_alibabasub');
           $send_url = $erp_system['del']."?access_token=". getOASystemAccessToken();
           $result = send_http($send_url,json_encode(['id'=>$id]));
      
           return TRUE;
       }else{
           throw new Exception('删除失败');
       }    
    }

}