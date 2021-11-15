<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 财务付款合同控制器
 */
class Payment_order_contract_pay extends MY_Controller
{
    
      public function __construct() {
        parent::__construct();
        $this->load->model('finance/Payment_order_contract_pay_model');
      }
      
       /**
      * 批量审核显示（合同）
      * @author harvin 
      * @date 2019-4-4
      * /finance/payment_order_contract_pay/batch_audit_info
      */
     public function batch_audit_info(){
          $ids=$this->input->get_post('ids'); //参数id
          $status = $this->input->get_post('status'); // 审核层级 60":"待财务主管审核","61":"待财务经理审核","62":"待财务总监审核","63":"待总经办审核 30:'财务审核'
          if(empty($ids)){
              $this->error_json('请勾选数据');
          }
          try {
             $data_list= $this->Payment_order_contract_pay_model->get_audit_info($ids,$status);
             $this->success_json($data_list);
          } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
          }   
     }
     /**
      * 批量审核（合同）
      * @author harvin 
      * @date 2019-4-4
      * /finance/payment_order_contract_pay/batch_audit
      */
      public function batch_audit(){
          $ids=$this->input->get_post('ids'); //参数id
          $review_notice = $this->input->get_post('review_notice'); //审核备注
          $type = $this->input->get_post('type'); // 1 通过 2驳回
          $status = $this->input->get_post('status'); // 审核层级 60":"待财务主管审核","61":"待财务经理审核","62":"待财务总监审核","63":"待总经办审核 30:'财务审核'
          if(empty($ids)){
              $this->error_json('请勾选数据');
          }
          if(empty($review_notice)){
               $this->error_json('请填写审核备注');
          }
          if(empty($type)){
              $this->error_json('参数type,不存在');
          }
        $data= $this->Payment_order_contract_pay_model->get_contract_order_save($ids, $review_notice, $type,$status);   
        if($data['bool']){
            $this->success_json([],NULL,$data['msg']);
        } else{
            $this->error_json($data['msg']);
        }
      }
    
     
      /**
       * 线下支付---显示（合同请款单）
       * @author harvin 2019-4-4
       * /finance/payment_order_contract_pay/offline_payment_info
       */
      public function offline_payment_info(){
          $ids=$this->input->get_post('ids'); //参数id
          if(empty($ids)){
              $this->error_json('请勾选数据');
          }
          $ids= explode(',', $ids);
          if(!empty($ids) && !is_array($ids)){
              $this->error_json('参数id格式不正确');
          }
//          if(count($ids)>1){
//               $this->error_json('只能选择一条数据');
//          }
          try {
           // 判断是否同一个供应商,结算方式，支付方式 结算比例 是否退税 运费支付 是否一样 
          $this->Payment_order_contract_pay_model->get_supplier_ststus($ids);
          //获取显示数据
          $data= $this->Payment_order_contract_pay_model->get_offline_payment($ids);
          $this->success_json($data);
          } catch (Exception $exc) {
             $this->error_json($exc->getMessage());
          }  
      }
      /**
       * 线下支付---保存（合同请款单）
       * @author harvin 2019-4-7
       * /finance/payment_order_contract_pay/offline_payment_save
       */
      public function offline_payment_save(){
        $ids = $this->input->get_post('ids'); //参数id
        $pay_price = $this->input->get_post('pay_price'); //申请金额
        $payer_time = $this->input->get_post('payer_time'); //付款时间
        $account_short = $this->input->get_post('account_short'); //付款简称
        $pay_type=$this->input->get_post('pay_type');//支付方式
        $supplier_code=$this->input->get_post('supplier_code');//供应商编码
        $images = $this->input->get_post('images'); //付款回执
        $remarks=$this->input->get_post('remarks'); //付款备注
        if(empty($ids)){
            $this->error_json('参数id格式不能为空');
        }
        if(empty($payer_time)){
            $this->error_json('请选择付款时间');
        }  
        if(empty($account_short)){
            $this->error_json('请选择付款账号简称');
        }
        if(empty($pay_type)){
            $this->error_json('请选择支付方式');
        }
        if(empty($supplier_code)){
             $this->error_json('供应商编码不能为空');
        }
        if(empty($images)){
             $this->error_json('请上传付款回执单');
        }
      $temp=  $this->Payment_order_contract_pay_model
                ->get_offline_payment_save($ids,$payer_time,$account_short,$pay_type,$supplier_code,$images,$pay_price,$remarks);
        if($temp['bool']){
            $this->success_json([],null,$temp['msg']);
        }else{
            $this->error_json($temp['msg']);
        }  
      }
      /**
       * 线下支付---驳回（合同请款单）
       * @author harvin 2019-4-7
       * /finance/payment_order_contract_pay/offline_payment_reject
       */
      public function offline_payment_reject(){
          $ids = $this->input->get_post('ids'); //参数id
          $reject_notice = $this->input->get_post('reject_notice'); //参数id
          if(empty($ids)){
            $this->error_json('参数id格式不能为空');
          }
          $ids= explode(',', $ids);
          if(!empty($ids) && !is_array($ids)){
              $this->error_json('参数id格式不正确');
          }
          $temp=  $this->Payment_order_contract_pay_model
                ->get_offline_payment_reject($ids,$reject_notice);
          if ($temp['bool']) {
              $this->success_json([], null, $temp['msg']);
          } else {
              $this->error_json($temp['msg']);
          }
      }

      /**
       * 富友支付---显示（合同请款单）
       * @author harvin 2019-4-4
       * /finance/payment_order_contract_pay/online_payment
       */
      public function online_payment(){
          $ids  = $this->input->get_post('ids'); //参数id
          $platform_type = $this->input->get_post('type'); //区分是富友还是宝付 ufxfuiou  baofopay lakala
          $show_pay_details = $this->input->get_post('show_pay_details'); //默认隐藏付款信息,1.返回【付款信息】字段，其他不返回该字段

          if(empty($platform_type) or !in_array($platform_type,['ufxfuiou','baofopay','lakala','cebbank'])){
              $this->error_json('支付平台操作错误');
          }
          if(empty($ids)){
              $this->error_json('请勾选数据');
          }
          $ids = explode(',', $ids);
          if(!empty($ids) && !is_array($ids)){
              $this->error_json('参数id格式不正确');
          }
          try{
              if($platform_type == 'cebbank'){
                  $check_type = false;
              }else{
                  $check_type = true;
              }
              //判断是否同一个供应商,结算方式，支付方式 结算比例 是否退税 运费支付 是否一样
              $this->Payment_order_contract_pay_model->get_supplier_ststus($ids, $check_type,SOURCE_COMPACT_ORDER,$platform_type);
              //获取显示数据
              $data = $this->Payment_order_contract_pay_model->get_online_payment($ids, $platform_type,$show_pay_details);

              sort($ids);
              $session_key = 'online_payment'.md5(implode(',',array_unique($ids)));
              ($show_pay_details !== 1) and $this->rediss->setData($session_key,$platform_type);// 缓存当前支付的类型

              $this->success_json($data);
          }catch(Exception $exc){
              $this->error_json($exc->getMessage());
          }
      }
}
