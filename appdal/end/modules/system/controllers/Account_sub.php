<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Account_sub extends MY_Controller
{
   public function __construct(){
        parent::__construct();
        $this->load->model('Account_sub_model');
        $this->load->model('purchase/Reduced_edition_model');
    }
    /**
     * 子账号列表
     * @author harvin
     * @date 2019-06-26
     */
    public function sub_list(){
        $params     = gp();
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0)
            $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $reulst= $this->Account_sub_model->get_sub_list($params, $offsets, $limit, $page);
        $this->success_json($reulst);   
    }
    /**
     * 添加子账号
     * @author harvin
     * @date 2019-06-26
     */
    public function add_sub(){
        $account=$this->input->get_post('account');
        $user_id=$this->input->get_post('user_id');
        $pay_user_id=$this->input->get_post('pay_user_id');   
        $level=$this->input->get_post('level');
        $dep_id = $this->input->get_post('dep_id');
        $dep_name = $this->input->get_post('dep_name');
        $prev_dep_name = $this->input->get_post('prev_dep_name');
        if(empty($account)){
            $this->error_json('请添加账号');
        }
        if(empty($user_id)){
            $this->error_json('请选择使用人');
        }
        if(empty($pay_user_id)){
            $this->error_json('请选择付款人');
        }

        if( empty($dep_id) || empty($dep_name)) {

            $this->error_json('请选择部门');

        }
        try {
           $res= $this->Account_sub_model->save_sub($account,$user_id,$pay_user_id,$level,$dep_id,$dep_name,$prev_dep_name);
           if($res){
              $this->success_json([],null,'添加成功');
           } 
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
            
    }

    
    /**
     * 修改显示
     * @author harvin、
     * @date 2019-06-26
     */
    public function sub_edit(){
       $id=$this->input->get_post('id');
       if(empty($id) || !is_numeric($id)){
           $this->error_json('请求参数错误');
       } 
       try {
        $data= $this->Account_sub_model->get_sub_list_row($id);
           $this->success_json($data,null,'获取数据成功');
       } catch (Exception $exc) {
           $this->error_json($exc->getMessage());
       }
          
       
    }
    
    /**
     * 保存修改1688子账号信息
     * @author harvin
     * @date 2019-06-26
     */
    public function sub_edit_save(){
        $account=$this->input->get_post('account');
        $user_id=$this->input->get_post('user_id');
        $pay_user_id=$this->input->get_post('pay_user_id');
        $level=$this->input->get_post('level');
        $id=$this->input->get_post('id');
        $dep_id = $this->input->get_post('dep_id');
        $dep_name = $this->input->get_post('dep_name');
        $prev_dep_name = $this->input->get_post('prev_dep_name');
        if(empty($account)){
            $this->error_json('请添加账号');
        }
        if(empty($user_id)){
            $this->error_json('请选择使用人');
        }
        if(empty($pay_user_id)){
            $this->error_json('请选择付款人');
        }
        if(empty($id)){
             $this->error_json('参数id缺少');
        }

        if( empty($dep_id) || empty($dep_name)) {

            $this->error_json('请选择部门');
        }
        try {
           $res= $this->Account_sub_model->sub_edit_save($account,$user_id,$pay_user_id,$level,$id,$dep_id,$dep_name,$prev_dep_name);
           if($res){
               $this->load->model('Reject_note_model');
               $log = [
                   'record_number' => $id,
                   'record_type' => 'PUR_PURCHASE_ACCOUNT_SUD',
                   'content' => '保存或者修改账号',
                   'content_detail' => '账号:'.$account.'付款人:'.$pay_user_id.',使用人:'.$user_id,
               ];
               $this->Reject_note_model->get_insert_log($log);

              $this->success_json([],null,'修改成功');


           } 
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }
    
    /**
     * 删除子账号
     * @author harvin
     * @data 2019-06-27
     */
    public function sub_del(){
        $id=$this->input->get_post('id');
       if(empty($id) || !is_numeric($id)){
           $this->error_json('请求参数错误');
       } 
        try {
          $re= $this->Account_sub_model->get_sub_del_row($id);
          if($re){
              $this->success_json([],null,'删除成功');
          }
       } catch (Exception $exc) {
           $this->error_json($exc->getMessage());
       } 
    }

    /**
       * 1688使用者信息
     **/
    public function get_company_person(){

        $client_name = $this->input->get_post('search_name');
        try{

            $result = $this->Account_sub_model->get_company_person( $client_name );
            $this->success_json($result,null,'获取数据成功');
        }catch ( Exception $exp ) {

            $this->error_json($exp->getMessage());
        }
    }

    /**
      * 获取SKU 降本优化人
     **/
    public function get_reduced_optimizing_user()
    {
        $client_name = $this->input->get_post('search_name');
        try{

            $result = $this->Reduced_edition_model->new_get_drop_down_box();
            $this->success_json($result,null,'获取数据成功');
        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }
    }


    /**
     * 自用 手动导入1688子账号
     * @author Jolon
     */
    public function import_ali_sub_account(){
        $list = [
//            ['account' => 'yibaisuperbuyers:开发004','user_id' => '10775'],
//            ['account' => 'yibaisuperbuyers:开发005','user_id' => '11389'],
        ];

        $this->load->model('user/Purchase_user_model');
        foreach($list as $key => &$value){
            $staff_code = $value['user_id'];
            $result = $this->Purchase_user_model->get_user_info_by_staff_code( $staff_code );
            if(isset($result['user_id'])){
                $value['user_id'] = $result['user_id'];
            }

            $value['pay_user_id']   = '7860';
            $value['dep_id']        = '1079231';
            $value['dep_name']      = '开发部';
            $value['prev_dep_name'] = '深圳市易佰网络科技有限公司';
            $value['uid']           = '736';
            $value['level']         = '1';
        }

        $url = 'http://pms.yibainetwork.com:81/system/account_sub/add_sub';

        foreach($list as $value2){
            $result = getCurlData($url,$value2);
            $result = json_decode($result,true);
            echo $value2['user_id'].'-->>'.$result['status'].'-->>'.$result['errorMess'];echo '<br/>';

        }
        echo 'sss';exit;

    }
    
    
}    
