<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Purchase_order_determine_model extends Purchase_model {

    public function __construct() {
        parent::__construct();
        $this->config->load('key_name', FALSE, TRUE);
        $this->load->model('Prefix_number_model');
        $this->load->model('purchase_order_model','',false,'purchase');
        $this->load->model('purchase_order_cancel_model','',false,'purchase');
        $this->load->model('Purchase_order_unarrived_model','',false,'purchase');
        $this->load->model('Purchase_financial_audit_model', 'm_financial_audit', false, 'purchase');
        $this->load->model('abnormal/product_scree_model');
        $this->load->model('Reject_note_model');

        $this->load->model('Message_model');
    }
   
    
    /**
     * 取消未到货列表
     * @param $params
     * @author harvin 2019-3-9
     * @return  array
     */
    public function get_cencel_list($params,$offsets,$limit,$page){
        $query_builder=$this->purchase_db;
        $query_builder->select('D.apply_amount,A.id,A.cancel_number,A.audit_status,A.cancel_ctq,A.total_price,A.create_user_name,
        A.create_time,A.audit_user_name,A.audit_time,A.is_edit,A.upload_note,A.real_refund_total,A.freight,A.discount,A.process_cost,
        group_concat(DISTINCT B.serial_number) as serial_number,E.real_refund_time as completed_time,GROUP_CONCAT(DISTINCT B.relative_superior_number) as relative_superior_number
        ,A.cancel_source,C.buyer_id,C.buyer_name
        ');
        $query_builder->from('purchase_order_cancel as A');
        $query_builder->join('purchase_order_cancel_detail as B','A.id=B.cancel_id','left');
        $query_builder->join('purchase_order as C','C.purchase_number=B.purchase_number','left');
        $query_builder->join('purchase_order_pay_type as D','D.purchase_number=B.purchase_number','left');
        $query_builder->join('purchase_order_receipt as E','E.cancel_id=A.id','left');

         if(isset($params['ids']) && $params['ids']){ //取消id
             if(is_string($params['ids'])){
                 $query_builder->where('A.id',$params['ids']);
             }else{
                 $query_builder->where_in('A.id',$params['ids']);
             }
         }

        if(SetAndNotEmpty($params, 'buyer_id', 'ar')){
            $this->purchase_db->where_in("C.buyer_id", $params['buyer_id']);
        }

       if(isset($params['group_ids']) && !empty($params['group_ids'])){

           $this->purchase_db->where_in('A.create_user_id',$params['groupdatas']);
       }
       if(isset($params['upload_files_time'][0]) && isset($params['upload_files_time'][1]) && !empty($params['upload_files_time'][0]) && !empty($params['upload_files_time'][1])){
           $upt1 = $params['upload_files_time'][0];
           $upt2 = $params['upload_files_time'][1];
           $this->purchase_db->where("B.screenshot_time between '{$upt1}' and '{$upt2}'");
       }
       if(isset($params['refund_price'][0]) && !empty($params['refund_price'][0])){
           $upt1 = $params['refund_price'][0];
           $this->purchase_db->where("A.total_price >=".$upt1);
       }
       if(isset($params['refund_price'][1]) && !empty($params['refund_price'][1])){
           $upt2 = $params['refund_price'][1];
           $this->purchase_db->where("A.total_price <=".$upt2);
       }

         if(isset($params['cancel_number']) && trim($params['cancel_number'])){ //申请编码
            $query_builder->where('A.cancel_number', trim($params['cancel_number']));
         }
          if(isset($params['serial_number']) && trim($params['serial_number'])){ //收款流水号
            $serial_number= explode(' ',trim($params['serial_number']));
            if (count($serial_number)>1){
                $query_builder->where('B.serial_number', trim($params['serial_number']));
            }else{
                $query_builder->like('B.serial_number', trim($params['serial_number']));
            }

         }
        if(SetAndNotEmpty($params, 'cancel_source', 'n')){
            $query_builder->where('A.cancel_source', $params['cancel_source']);
        }
         if(isset($params['sku']) && trim($params['sku'])){ //sku
              //支持批量查询
             $sku_list= explode(' ', $params['sku']);
             $sku= array_filter($sku_list);
             $query_builder->where_in('B.sku',$sku);
         }
         if(isset($params['purchase_number']) && trim($params['purchase_number'])){ //采购单号
             $purchase_number_list= explode(' ', trim($params['purchase_number']));
             $purchase_number= array_filter($purchase_number_list);
             $query_builder->where_in('B.purchase_number',$purchase_number);
         }
         if(isset($params['create_user_id']) && $params['create_user_id']){
             $query_builder->where_in('A.create_user_id',$params['create_user_id']);
         }
         if(isset($params['audit_status']) && $params['audit_status']){
             $query_builder->where_in('A.audit_status',$params['audit_status']);
         }
         if(isset($params['create_time_start'])&& $params['create_time_start']){ //申请时间开始
             $query_builder->where('A.create_time >=',$params['create_time_start']);
         }
         if(isset($params['create_time_end'])&& $params['create_time_end']){ //申请时间开始
             $query_builder->where('A.create_time <=',$params['create_time_end']);
         }
       if(isset($params['relative_superior_number'])&& $params['relative_superior_number']){ //申请时间开始
           $query_builder->where('B.relative_superior_number',$params['relative_superior_number']);
       }
         if(isset($params['purchase_type_id'])&& $params['purchase_type_id']){ //业务线
             $query_builder->where_in('C.purchase_type_id ',$params['purchase_type_id']);
         }
         if(isset($params['ali_refund'])&& $params['ali_refund']==1){ //1688刷新退款信息
             $query_builder->where('C.source =',SOURCE_NETWORK_ORDER);
             $query_builder->where('A.audit_status =',CANCEL_AUDIT_STATUS_SCJT);
         }

         if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
             $this->purchase_db->where('C.supplier_code',$params['supplier_code']);
         }

         if( isset($params['cancel_price_min']) && !empty($params['cancel_price_min'])&&
             isset($params['cancel_price_max']) && !empty($params['cancel_price_max']) ){
             $this->purchase_db->where('A.total_price >',$params['cancel_price_min']);
             $this->purchase_db->where('A.total_price <',$params['cancel_price_max']);
         }

         if(isset($params['completed_time_start'])&& $params['completed_time_start']){ //退款时间开始
             $query_builder->where('E.real_refund_time >=',$params['completed_time_start']);
         }
         if(isset($params['completed_time_end'])&& $params['completed_time_end']){ //退款时间结束
             $query_builder->where('E.real_refund_time <=',$params['completed_time_end']);
         }

       //拍单号(1-≠0)
       if (isset($params['pai_number']) && 1 == $params['pai_number']) {
           $query_builder->where('D.pai_number <>', '');
       }
       //1688退款金额（1-≠0）
       if (isset($params['apply_amount']) && 1 == $params['apply_amount']) {
           $query_builder->where('D.apply_amount <>', 0);
       }
       //采购来源（1-合同，2-网采）
       if (!empty($params['source'])) {
           $query_builder->where('C.source', $params['source']);
       }

         $count_qb = clone $query_builder;
         $results = $query_builder->limit($limit, $offsets)->group_by('A.id')->order_by('A.id', 'desc')->get()->result_array();
         $count_row = $count_qb->select("count(distinct(A.id)) as num")->get()->row_array();
         $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;

         $this->load->model('finance/Purchase_order_pay_type_model');

         //组装需要的数据
         foreach ($results as $key => &$row){
             $pay_price_list = $this->get_receivable_pay_price_v2($row['id'], true);
             $results[$key]['pay_price'] = sprintf("%.3f", isset($pay_price_list['pay_price']) ? array_sum($pay_price_list['pay_price']) : 0);
             $results[$key]['original_pay_price'] = sprintf("%.3f", isset($pay_price_list['original_pay_price']) ? array_sum($pay_price_list['original_pay_price']) : 0);
             $purchase_numbers = $this->get_purchase_numbers($row['id']);
             $purchase_numbers_unique = array_unique($purchase_numbers['purchase_numbers']);

             if(!empty($purchase_numbers_unique)){
                 $results[$key]['purchase_numbers'] = implode(',',$purchase_numbers_unique);//采购单号

                 $pai_numbers = $this->Purchase_order_pay_type_model->get_pai_number($purchase_numbers_unique);
                 $pai_number_unique = array_unique($pai_numbers);
                 $results[$key]['pai_numbers'] = implode(',',$pai_number_unique);//拍单号

                 if (!empty($results[$key]['pai_numbers'])){
                     //根据拍单号查询1688退款金额
                     $apply_amounts = $this->Purchase_order_pay_type_model->get_apply_amount($pai_number_unique);

                     $results[$key]['apply_amounts'] = array_sum($apply_amounts)==0?'':array_sum($apply_amounts);//1688退款金额

                 }else{
                     $results[$key]['apply_amounts'] = '';
                 }


             }else{
                 $results[$key]['purchase_numbers'] = '';
                 $results[$key]['pai_numbers'] = '';
             }

             $row['completed_time'] = $row['completed_time']=='0000-00-00 00:00:00'?"":$row['completed_time'];//退款完成时间
             $row['cancel_source'] = $row['cancel_source'] == 2 ? "系统自动取消" : "手动取消";

             unset($purchase_numbers);
             unset($purchase_numbers_unique);
             unset($pai_numbers);
             unset($pai_number_unique);
         }

        $data_list['cancel_source']= ["1" => "手动取消", "2"=>"系统自动取消"];
         $data_list['create_user_id']=get_buyer_name(); //申请人
         $data_list['audit_status']= get_cancel_status(); //取消未到货状态
         $data_list['purchase_type_id']= getPurchaseType(); //业务线
       $data_list['pai_number']= [1=>'≠0']; //拍单号
       $data_list['apply_amount']= [1=>'≠0']; //1688退款金额
       $data_list['source']= getPurchaseSource(); //采购来源
         $key_table=['申请编码','取消未到货状态','取消数量','取消金额','取消运费','取消优惠额','取消加工费','应退款金额','收款流水号','申请人/申请时间','审核人/审核时间','操作'];
          $return_data = [
           'drop_down_box'=>$data_list,
            'key'=>$key_table,
            'values'=>$results,
            'page_data'=>[
                'total'=>$total_count,
                'offset'=>$page,
                'limit'=>$limit,
                'pages'=> ceil($total_count/$limit),
            ]
        ];
        return $return_data;
   }
    /**
     * 判断采购单状态
     * @param array $ids 数组ids
     * @param array $status_array 状态数组
     * @author harvin 2019-1-8
     * return bool
      */
    public function order_status($ids, $status_array) {
        $query_builder = $this->purchase_db;
        //先获取采购单号
        $data = [];
        foreach ($ids as $v) {
            $order = $query_builder->select('purchase_number')->where('id', $v)->get('purchase_order_items')->row();
            !empty($order) && $data[] = $order->purchase_number;
        }
        $data = array_unique($data);

        $bool = false;
        //再判断采购单主表的状态 
        foreach ($data as $key => $vo) {
            $order_status = $query_builder->select('purchase_order_status')->where('purchase_number', $vo)->get('purchase_order')->row();
            if (empty($order_status)) {
                break;
            }
            if (!in_array($order_status->purchase_order_status, $status_array)) {
                $bool = false;
                break;
            } else {
                $bool = true;
            }
        }
        return $bool;
    }

    /**
     * 判断采购单状态
     * @param array $ids 数组ids
     * @param array $status_array 状态数组
     * @author harvin 2019-1-8
     * return bool
     */
    public function is_order_change_status($ids, $status_array) {
        $query_builder = $this->purchase_db;
        //先获取采购单号
        $data = [];
        foreach ($ids as $v) {
            $order = $query_builder->select('purchase_number')->where('id', $v)->get('purchase_order_items')->row();
            !empty($order) && $data[] = $order->purchase_number;
        }
        $data = array_unique($data);

        $bool = '';
        //再判断采购单主表的状态
        foreach ($data as $key => $vo) {
            $order_status = $query_builder->select('purchase_order_status')->where('purchase_number', $vo)->get('purchase_order')->row();
            if (empty($order_status)) {
                break;
            }
            if (in_array($order_status->purchase_order_status, $status_array)) {
                $bool = $vo;
                break;
            } else {
                $bool = '';
            }
        }
        return $bool;
    }
    /**
     * 判断菜单单是否是在请款中
     * @param array $ids 
     * @author harvin 2019-3-7
     * @return bool 
     */
    public function order_pay_get(array $ids){
         $query_builder = $this->purchase_db;
        //先获取采购单号
        $data = [];
        foreach ($ids as $v) {
            $order = $query_builder->select('purchase_number')
                    ->where('id', $v)
                    ->get('purchase_order_items')
                    ->row();
            !empty($order) && $data[] = $order->purchase_number;
        }
       
        //获取请款单号
        $orderpaydetail = $query_builder->select('requisition_number')
                ->where_in('purchase_number', $data)
                ->get('purchase_order_pay_detail')
                ->result_array();
        $requisition_number = array_column(!empty($orderpaydetail) ? $orderpaydetail : [], 'requisition_number');

        if (empty($requisition_number))
            return false;
        //判断这个请款单 是否是在请款中
        $pay_status = $query_builder->select('pay_status')
            ->where_in('requisition_number', $requisition_number)
            ->get('purchase_order_pay')
            ->result_array();
        //未申请付款 已付款
        $pay_arr = [PAY_UNPAID_STATUS,PAY_MANAGER_REJECT,PAY_FINANCE_REJECT,PAY_SOA_REJECT,PAY_PAID,PAY_WAITING_MANAGER_REJECT,PAY_REJECT_SUPERVISOR,PAY_REJECT_MANAGER,PAY_REJECT_SUPPLY,PAY_GENERAL_MANAGER_REJECT,PAY_UFXFUIOU_BAOFOPAY,PAY_UFXFUIOU_REVIEW];
        foreach ($pay_status as $row) {
            if (!in_array($row['pay_status'], $pay_arr)) {
                return TRUE;
                break;
            }
        }
        return FALSE;
    }
    
    /**
     * 验证入库数量及采购数量
     * @param array $ids
     * @author harvin 2019-3-7
     * @return array 
     */
    public function warehouse_results_get(array $ids){
       $data = [];
        foreach ($ids as $id) {
            //获取每个sku 入库数量
            $warehouse_results = $this->purchase_db->select('instock_qty,purchase_number,sku')
                    ->where('items_id', $id)
                    ->get('warehouse_results')
                    ->row_array();
            //获取采购数量
            $order_items = $this->purchase_db->
                    select('confirm_amount,sku,purchase_number')
                    ->where('id', $id)
                    ->get('purchase_order_items')
                    ->row_array();
           $instock_qty=!empty($warehouse_results) ? $warehouse_results['instock_qty'] : 0;
           $confirm_amount=!empty($order_items) ? $order_items['confirm_amount'] : 0;
            //入库数量>采购数量  记录采购单号及sku
            if ($instock_qty > $confirm_amount) {
                $data[] = $order_items['purchase_number'] . "__" . $order_items['sku'];
            }
        }

        return $data;
    }
    /**
    * 验证是否存在上次取消未到货未审核完结的订单
    * @param array $ids
    * @author harvin 2019-3-7
    * @return array
    */
    public function get_cancel_status(array $ids){
      //获取取消未到货的 明细表 采购订单号
        $data = [];
        foreach ($ids as $id) {
            //获取最近一次取消未到货 数据
            $cancel_detail = $this->purchase_db
                    ->select('cancel_id,purchase_number,sku')->where('items_id', $id)
                    ->order_by('id desc')
                    ->get('purchase_order_cancel_detail')
                    ->row_array();
            if (!empty($cancel_detail)) {
                //查询取消未到货主表 信息
                $order_cancel = $this->purchase_db->select('audit_status')
                        ->where('id', $cancel_detail['cancel_id'])
                        ->get('purchase_order_cancel')
                        ->row_array();
                if (!empty($order_cancel)) {
                    //判断是否有上次取消未到货 未审核完成的
                    //10.待采购经理审核,20.待财务收款  50 待上传截图 
                    $audit_status = [CANCEL_AUDIT_STATUS_CG, CANCEL_AUDIT_STATUS_CF,CANCEL_AUDIT_STATUS_SCJT];
                    if (in_array($order_cancel['audit_status'], $audit_status)) {
                        $data[] = $cancel_detail['purchase_number'] . "__" . $cancel_detail['sku'];
                    }
                    //如果存在驳回单 再申请时 走再编辑流程  
                    $audit_status_bo=[CANCEL_AUDIT_STATUS_CGBH,CANCEL_AUDIT_STATUS_CFBH];
                    if (in_array($order_cancel['audit_status'], $audit_status_bo)) {
                        $data[] = $cancel_detail['purchase_number'] . "__" . $cancel_detail['sku'];
                    }
                    
                }
            }
        }
        return $data;
    }
        /**
        * 验证是否存在上次取消未到货未审核完结的订单
        * @param array $purchase_order_list //采购单号
        * @author harvin 2019-3-7
        * @return array
        */
    public function get_cancel_order_status(array $purchase_order_list){
          //获取取消未到货的 明细表 采购订单号
            $data = [];
            foreach ($purchase_order_list as $order) {
                //获取最近一次取消未到货 数据
                $cancel_detail = $this->purchase_db
                        ->select('cancel_id,purchase_number,sku')->where('purchase_number', $order)
                        ->order_by('id desc')
                        ->get('purchase_order_cancel_detail')
                        ->row_array();
                if (!empty($cancel_detail)) {
                    //查询取消未到货主表 信息
                    $order_cancel = $this->purchase_db->select('audit_status')
                            ->where('id', $cancel_detail['cancel_id'])
                            ->get('purchase_order_cancel')
                            ->row_array();
                    if (!empty($order_cancel)) {
                        //判断是否有上次取消未到货 未审核完成的
                        //10.待采购经理审核,20.待财务收款  50 待上传截图 
                        $audit_status = [CANCEL_AUDIT_STATUS_CG, CANCEL_AUDIT_STATUS_CF,CANCEL_AUDIT_STATUS_SCJT];
                        if (in_array($order_cancel['audit_status'], $audit_status)) {
                            $data[] = $cancel_detail['purchase_number'] . "__" . $cancel_detail['sku'];
                        }
                    }
                }
            }
            return $data;
        }
    /**
     * 判断采购单的供应商是否一致
     * @param array $ids
     * @author harvin 2019-3-7
     * @return bool  
    */
    public function get_supplier_sy(array $ids){
        //获取采购单号
          $order_items = $this->purchase_db->
                    select('purchase_number')
                    ->where_in('id', $ids)
                    ->get('purchase_order_items')
                    ->result_array();
        $purchase_number= array_column(isset($order_items)?$order_items:[], 'purchase_number');
        if(empty($purchase_number)) return FALSE;
        $purchase_number= array_unique($purchase_number);
        //判断采购单号的供应商是否相同
        $order=$this->purchase_db->select('supplier_code')
                ->where_in('purchase_number',$purchase_number)
                ->get('purchase_order')
                ->result_array();
        $supplier_code= array_column(isset($order)?$order:[], 'supplier_code');  
        if(empty($supplier_code)) return FALSE;
        $supplier_code= array_unique($supplier_code);
        if(count($supplier_code)>1){
            return TRUE;
        }else{
            return FALSE;
        }
        
        
    }
    /**
     * 更新采购单的状态
     * @param  array $ids
     * @author harvin 2019-1-10
     * 
     * */
    public function order_status_save($ids) {
        try {
             $query_builder = $this->purchase_db;
            //先获取采购单号
            $data = [];
            foreach ($ids as $v) {
                $order = $query_builder->select('purchase_number')->where('id', $v)->get('purchase_order_items')->row();
                if(empty($order)){
                    throw new Exception('参数id不存在');
                }
                $data[] = $order->purchase_number;
            }
            $data = array_unique($data);
           if(empty($data)){
               throw new Exception('采购单不存在');
           }
            $this->load->model('Reject_note_model');
            //开始事物
            $this->purchase_db->trans_begin();
            foreach ($data as $val) {
                $data_ls = [
                    'pay_type' => '',
                    'plan_product_arrive_time' => '',
                    'modify_time' => date('Y-m-d H:i:s'),
                    'modify_user_name' => getActiveUserName(),
                ];
                //更新采购单主表
                $res1 = $this->purchase_order_model->change_status($val,PURCHASE_ORDER_STATUS_WAITING_QUOTE); // 统一入口修改采购单状态
                $res2 = $this->purchase_db->where('purchase_number', $val)->update('purchase_order', $data_ls);
                if(empty($res1) or empty($res2)){
                    throw new Exception('采购单更新失败');
                }
                //记录采购单信息确认-请款金额相关信息
                $res = [
                    'real_price' => '',
                    'is_freight' => '',
                    'freight_formula_mode' => '',
                    'settlement_ratio' => '',
                    'purchase_acccount' => '',
                    'pai_number' => '',
                    'note' => "采购单撤销确认提交信息"
                ];
                //更新请款单  
               $res= $this->purchase_db->where('purchase_number', $val)->update('purchase_order_pay_type', $res);
                if(empty($res)){
                    throw new Exception('请款单更新失败');
                }
                //记录操作日志
                $log = [
                    'record_number' => $val,
                    'record_type' => 'PURCHASE_ORDER',
                    'content' => '采购单撤销确认提交信息',
                    'content_detail' => '采购单号' . $val . '采购单撤销确认提交信息'
                ];
                $this->Reject_note_model->get_insert_log($log);
            }
            $freight_discount = ['freight' => 0, 'discount' => 0];
            foreach ($ids as $val) {
                $ress = $this->purchase_db->where('id', $val)->update('purchase_order_items', $freight_discount);
                if(empty($ress)){
                    throw new Exception('采购明细表更新失败');
                }
            }


            if ($this->purchase_db->trans_status() === FALSE) {
                $this->purchase_db->trans_rollback();
                return  ['msg'=>'更新失败','bool'=>FALSE];
              
            } else {
                $this->purchase_db->trans_commit();
                return  ['msg'=>'','bool'=>true];
               
            }
        } catch (Exception $exc) {
            $this->purchase_db->trans_rollback();
            return  ['msg'=>$exc->getMessage(),'bool'=>FALSE];
            
            
            
            
        }

       
    }

    /**
     * 打印采购单数据
     * @author harvin
     * @param type $ids
     * @return array
     */
    public function get_printing_purchase_order($ids) {
        $data = [];
        $ids = $this->get_all_ids($ids);
        $this->load->model('product/product_model');

        $pertain_wms = [];
        $wms_address = '';
        $pur_freight = [];
        $pur_discount = [];
        $p_type_id = [];
        $warehouse_code = [];

        $header        = array('Content-Type: application/json');
        $access_taken  = getOASystemAccessToken();
        $newurl =  getConfigItemByName('api_config','java_system_erp','new_yibaiProduct-getSkuInfoBySku');
        $newurl = $newurl."?access_token=".$access_taken;

        foreach ($ids as $key => $val) {
            $orderinfo = $this->get_purchase_order_item($val);//查询订单采购明细表
            if(empty($orderinfo))  continue;
            $packaging_string = '';//打包方式
            $skuInfo = $this->product_model->getproduct($orderinfo['sku']);
            if( !empty($skuInfo['purchase_packaging']) ) {
                $search_start_index = strpos($skuInfo['purchase_packaging'],"[");
                $packaging_string = substr_replace($skuInfo['purchase_packaging']," ",$search_start_index);

            }
            if(!in_array($orderinfo['pertain_wms'], $pertain_wms))$pertain_wms[] = $orderinfo['pertain_wms'];
            if(!in_array($orderinfo['purchase_type_id'], $p_type_id))$p_type_id[] = $orderinfo['purchase_type_id'];
            if(!in_array($orderinfo['warehouse_code'], $warehouse_code))$warehouse_code[] = $orderinfo['warehouse_code'];

            $pur_number = $orderinfo['purchase_number'];
            //查询入库的数量
            $warehouse = $this->purchase_db
                    ->select('sum(instock_qty) as instock_qty,sum(bad_qty) as bad_qty,instock_user_name,instock_date')
                    ->where('sku', $orderinfo['sku'])
                    ->where('purchase_number', $pur_number)
                    ->get('warehouse_results')
                    ->row_array();
            $instock_qty       = $bad_qty = 0;
            $instock_user_name = $instock_date = '';
            if(!empty($warehouse)){
                $instock_qty       = $warehouse['instock_qty'];
                $bad_qty           = $warehouse['bad_qty'];
                $instock_user_name = $warehouse['instock_user_name'];
                $instock_date      = $warehouse['instock_date'];
            }

            $confirm_amount = 0;
            $c_ctq = $this->get_po_cancel_ctq($pur_number, $orderinfo['sku']);
            if($c_ctq && isset($c_ctq['c_ctq']))$confirm_amount = $c_ctq['c_ctq'];
            if($orderinfo['confirm_amount'] > 0 && (int)$orderinfo['confirm_amount'] >= (int)$confirm_amount)$confirm_amount = (int)$orderinfo['confirm_amount'] - (int)$confirm_amount;

            /**
             * 获取sku特殊包材
             */
            $param = ['sku' => $orderinfo['sku']];
            $newresult = [];
            try{
//                echo $newurl;exit;
                $newresult = getCurlData($newurl, json_encode($param,JSON_UNESCAPED_UNICODE),'post',$header);
                $newresult = json_decode($newresult,true);
            }catch (Exception $e){
                $newresult = [];
            }catch (Error $e){
                $newresult = [];
            }

            $img = $orderinfo['product_img_url']?erp_sku_img_sku($orderinfo['product_img_url']):($skuInfo['product_thumb_url']? erp_sku_img_sku_thumbnail($skuInfo['product_thumb_url']):"");
            $row = [
                'id'                  => $orderinfo['id'],
                'purchase_number'     => $pur_number,
                'sku'                 => $orderinfo['sku'],
                'product_img_url'     => $img,
                'product_name'        => $orderinfo['product_name'],
                'purchase_unit_price' => $orderinfo['purchase_unit_price'],
                'purchase_amount'     => $confirm_amount,// $orderinfo['confirm_amount'], // 下单数量 = 采购数量 - 取消数量
                'instock_qty'         => $instock_qty,
                'bad_qty'             => $bad_qty,
                'instock_user_name'   => $instock_user_name,
                'instock_date'        => $instock_date,
                'freight'             => isset($orderinfo['freight'])?$orderinfo['freight']: 0,
                'discount'            => isset($orderinfo['discount'])?$orderinfo['discount']: 0,
                'label_pdf'           => str_replace('192.168.1.34','wms.yibainetwork.com',$orderinfo['label_pdf']),//标签
                'barcode_pdf'         => str_replace('192.168.1.34','wms.yibainetwork.com',$orderinfo['barcode_pdf']),//工厂直发码
                'purchase_packaging'  => $packaging_string,
                'special_packing'     => isset($newresult['data']['specialPack'])?$newresult['data']['specialPack']:'', // 特殊包装类型

            ];
            $pur_freight[$pur_number] = isset($orderinfo['t_freight'])?$orderinfo['t_freight']: 0;
            $pur_discount[$pur_number] = isset($orderinfo['t_discount'])?$orderinfo['t_discount']: 0;
            $data[] = $row;
            $purchase_number_list[]= $pur_number;
            unset($orderinfo);
            unset($warehouse);
        }

        if(count($pertain_wms) == 1 || in_array(PURCHASE_TYPE_FBA_BIG, $p_type_id)){
            $w_code = in_array(PURCHASE_TYPE_FBA_BIG, $p_type_id) && isset($warehouse_code[0])? $warehouse_code[0] : $pertain_wms[0];
            $p_w = $this->purchase_db
                ->from('warehouse_address')
                ->select('province_text, city_text, area_text, town_text, address')
                ->where('warehouse_code = ', $w_code)
                ->get()
                ->row_array();
            if($p_w && isset($p_w['address']))$wms_address = $p_w['province_text']." ".$p_w['city_text']." ".$p_w['area_text']." ".$p_w['town_text']." ".$p_w['address'];
        }
        if(count($pertain_wms) > 1 && !in_array(PURCHASE_TYPE_FBA_BIG, $p_type_id)){
            $wms_address = '';
        }
      
        //查询采购单主表
        $order = $this->purchase_db
                ->select('supplier_name,buyer_name,create_time,supplier_code')
                ->where_in('purchase_number', $purchase_number_list)
                ->get('purchase_order')
                ->result_array();
        if(empty($order)){
           return ['code'=>false,'data'=>'','msg'=>'采购主表不存在'];
        }
        $supplier_code_list= array_column($order, 'supplier_code');
       
        if(count(array_unique($supplier_code_list))>1){
            return ['code'=>false,'data'=>'','msg'=>'请选择相同的供应商'];
        }


        $product_number = count($data);
        $total          = 0;
        $total_price    = 0;
        $total_freight  = count($pur_freight) > 0 ? array_sum(array_values($pur_freight)): 0;
        $total_discount = count($pur_discount) > 0 ? array_sum(array_values($pur_discount)): 0;
        if(!empty($data)){
            foreach($data as $key => $rows){
                $total       += $rows['purchase_amount'];// SKU总数量
                $total_price += $rows['purchase_amount'] * $rows['purchase_unit_price'];// 总商品金额
            }
        }

        $data_list = [
            'product_number' => $product_number,
            'total'          => $total,
            'total_price'    => format_price($total_price),
            'supplier_name'  =>$order[0]['supplier_name'],
            'buyer_name'     => $order[0]['buyer_name'],
            'create_time'    => $order[0]['create_time'],
            'address'        => $wms_address, // 改为PO公共仓地址
            'data'           => $data,
            'total_freight'  => format_price($total_freight),
            'total_discount' => format_price($total_discount),
        ];

        return ['code'=>TRUE,'data'=>$data_list,'msg'=>'请求成功'];
    }

    /**
     * 以采购单维度获取取消数量
     */
    private function get_po_cancel_ctq($pur_number, $sku)
    {
        $data = $this->purchase_db->from('purchase_order_cancel as oc')
            ->select('sum(ocd.cancel_ctq) as c_ctq')
            ->join('pur_purchase_order_cancel_detail as ocd', 'ocd.cancel_id=oc.id', 'left')
            ->where(['ocd.purchase_number' =>$pur_number, 'ocd.sku' => $sku])
            ->where_in('oc.audit_status', [60, 70, 90])
            ->get()
            ->row_array();
        return $data && count($data) > 0?$data: false;
    }

    /**
     * 打印采购单返回数据
     */
    public function get_print_menu($data,$uid){
        $print_menu   = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_menu');
        $url          = $print_menu;
        $header = array('Content-Type: application/json');
        $html = getCurlData($url,json_encode($data, JSON_UNESCAPED_UNICODE),'post',$header);//翻译结果
        return $html;
    }

    /**
     * 打印采购单返回数据
     */
    public function get_order_html($data,$uid){

        $print_menu   = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_order');
        $url          = $print_menu;
        $header = array('Content-Type: application/json');
        $html = getCurlData($url,json_encode($data, JSON_UNESCAPED_UNICODE),'post',$header);//翻译结果
        return $html;
    }

    /**
     * 查询取消未到货的数据
     * @param array $ids 参数id
     * @author harvin 2019-1-8
     * @return mixed
     * * */
    public function get_cancel_unarrived_goods(array $ids,$is_plan_cancel = 0,$plan_cancel_ids = []) {
        //获取供应商名称及编码
        $this->load->model('finance/Purchase_order_pay_type_model');
        $data= $this->get_supplier_name_code($ids);
       $supplier= isset($data['supplier'])?$data['supplier']:[];
       $purchase_numbers= isset($data['purchase_number'])?$data['purchase_number']:[];
        $purchase_type_id= isset($data['purchase_type_id'])?$data['purchase_type_id']:0;
        $purchase_order_status_map = array_column($data['purchase_order_status'],'purchase_order_status','purchase_number');

       if(empty($purchase_numbers)) return [];
        //取消明细
        $order_list=[];
        foreach($purchase_numbers as $row_number){
           $total_freight_discount = $this->get_total_freight_discount_true($row_number);//查询总运费和总优惠
            $purchase_order_status = $purchase_order_status_map[$row_number]??'';

            $pai_number = $this->Purchase_order_pay_type_model->get_pai_number([$row_number]);

           $order_list[]=[
               'purchase_number'=>$row_number, //订单号
               'pai_number'     =>$pai_number[0]??'',
               'purchase_type_id'=>getPurchaseType($purchase_type_id), //业务线
               'purchase_order_status'=>getPurchaseStatus($purchase_order_status), //业务线
               'total_order_amount'=> isset($this->get_total_order($row_number)['total_order'])?$this->get_total_order($row_number)['total_order']:0,//订单金额
//               'total_freight'=> isset($this->get_total_freight_discount($row_number)['freight'])?$this->get_total_freight_discount($row_number)['freight']:0,//订单运费
//               'total_discount'=>isset($this->get_total_freight_discount($row_number)['discount'])?$this->get_total_freight_discount($row_number)['discount']:0,//订单优惠额
               'total_freight'=> isset($total_freight_discount['total_freight'])?$total_freight_discount['total_freight']:0,//订单运费
               'total_discount'=>isset($total_freight_discount['total_discount'])?$total_freight_discount['total_discount']:0,//订单优惠额
               'total_process_cost'=>isset($total_freight_discount['total_process_cost'])?$total_freight_discount['total_process_cost']:0,//订单加工费
               'total_confirm_amount'=>isset($this->get_total_order($row_number)['total_confirm_amount'])?$this->get_total_order($row_number)['total_confirm_amount']:0,//订单数量
               'order_info'=>$this->get_order_info($row_number,$is_plan_cancel,$plan_cancel_ids,$ids),
           ];
        }
        $data_list=[
            'supplier'=>$supplier,
            'order_list'=>$order_list,
            'cancel_reason'=>getOrderCancelReason(),
        ];
        return  $data_list;  
    }
    /**
     *获取订单总运费及优惠额 
     *@author harvin 2019-3-14
     *@param  $purchase_number 采购单号
     * @return  array
     */
    public function get_total_freight_discount($purchase_number){
       $pay_type= $this->purchase_db
               ->select('freight,discount')
               ->where('purchase_number',$purchase_number)
               ->get('purchase_order_pay_type')
               ->row_array();
       if(empty($pay_type)){
            return []; 
        }
        return $pay_type;
    }

    /**
     *获取订单总运费及优惠额(统计采购明细表的总运费和优惠)
     *@author harvin 2019-3-14
     *@param  $purchase_number 采购单号
     * @return  array
     */
    public function get_total_freight_discount_true($purchase_number){
        $pay_type= $this->purchase_db
            ->select('freight AS total_freight,discount AS total_discount,process_cost AS total_process_cost')
            ->where('purchase_number',$purchase_number)
            ->get('pur_purchase_order_pay_type')
            ->row_array();
        if(empty($pay_type)){
            return [];
        }
        return $pay_type;
    }

    /**
     * 获取供应商名称及编码
     * @author harvin 2019-3-8
     * @param array $ids
     */
    public function get_supplier_name_code(array $ids){
         //获取供应商名称及编码
        $order_items=$order=$this->purchase_db
                ->select('purchase_number')
                ->where_in('id',$ids)
                ->get('purchase_order_items')
                ->result_array();
        $purchase_number= array_column(isset($order_items)?$order_items:[], 'purchase_number');
        if(empty($purchase_number)) return [];
        //去除重复值
        $purchase_numbers= array_unique($purchase_number);
        $order=$this->purchase_db
                ->select('supplier_code,supplier_name,purchase_type_id')
                ->where('purchase_number',$purchase_numbers[0])
                ->get('purchase_order')
                ->row_array();
        if(empty($order)) return [];

        $order_status_info = $this->purchase_db
            ->select('purchase_number,purchase_order_status')
            ->where_in('purchase_number',$purchase_numbers)
            ->get('purchase_order')
            ->result_array();
        //供应商基本信息
        $supplier=[
            'supplier_code'=> isset($order['supplier_code'])?$order['supplier_code']:'',
            'supplier_name'=> isset($order['supplier_name'])?$order['supplier_name']:'',
        ];
        $data=[
            'supplier'=>$supplier,
            'purchase_number'=>$purchase_numbers,
            'purchase_type_id'=>$order['purchase_type_id'],
            'purchase_order_status'=>$order_status_info,
        ];
        return $data; 
    }
    /**
     * 采购总价
     * @param string $purchase_number 采购单号
     * @author harvin 2019-1-10
     * @return mixed
     */
    public function get_total_order($purchase_number) {
        if(empty($purchase_number)) return 0;
        $orderinfo = $this->purchase_db->select(
                'SUM(purchase_unit_price * confirm_amount) AS total_order,'
//                .'SUM(purchase_unit_price * confirm_amount + freight - discount + process_cost + commission) AS total_real_price,'
                .'SUM(confirm_amount) AS total_confirm_amount'
//                .'SUM(freight) AS total_freight,'
//                .'SUM(discount) AS total_discount,'
//                .'SUM(process_cost) AS total_process_cost,'
//                .'SUM(commission) AS total_commission'
            )
            ->where('purchase_number', $purchase_number)
            ->get('purchase_order_items')
            ->row_array();
        $have_pay_type = $this->purchase_db->where('purchase_number',$purchase_number)
            ->get('purchase_order_pay_type')
            ->row_array();

        if(empty($orderinfo) or empty($have_pay_type)) return [];

        return [
            'total_order'          => format_price($orderinfo['total_order']),
            'total_confirm_amount' => format_price($orderinfo['total_confirm_amount']),
            'total_real_price'     => format_price($orderinfo['total_order'] + $have_pay_type['freight'] - $have_pay_type['discount'] + $have_pay_type['process_cost'] + $have_pay_type['commission']),
            'total_freight'        => format_price($have_pay_type['freight']),
            'total_discount'       => format_price($have_pay_type['discount']),
            'total_process_cost'   => format_price($have_pay_type['process_cost']),
            'total_commission'     => format_price($have_pay_type['commission']),
        ];
    }
  
    
    /**
     * 获取已取消明细表
     * @param string $purchase_number
     * @author harvin 2019-3-7
     * @return array 
     */
    public function get_order_info($purchase_number,$is_plan_cancel = 0,$plan_cancel_ids = [],$filter_ids=[],$reason_items_list=[]){
        //明细表
       $orderinfo= $this->purchase_db
               ->select('id,sku,product_name,purchase_unit_price,product_img_url,confirm_amount,upselft_amount,receive_amount,freight,discount,process_cost')
               ->where('purchase_number',$purchase_number)
               ->get('purchase_order_items')
               ->result_array();
       $sku= array_column(isset($orderinfo)?$orderinfo:[], 'sku');  
       //获取入库数量集合
      $warehouse_list= $this->get_warehouse_list($purchase_number,$sku);
      //报损数量集合
      $purchase_order_reportloss=$this->get_purchase_order_reportloss_list($purchase_number,$sku);
      //获取历史取消数量集合
      $order_cancel_list=$this->get_order_cancel_list($purchase_number,$sku);
        //获取备货单号集合
        $demand_number_list_new =$this->get_demand_number_list_new($purchase_number);
        $demand_number_list = [];
        foreach ($demand_number_list_new as $k=>$v){
            $demand_number_list[$k] = $v['demand_number'];
        }
      //获取历史取消的数量 及入库数量
      //获取预计到货时间集合
      $plan_product_arrive_time_list=$this->plan_product_arrive_time_list($purchase_number);

      //该备货单关联的申请ID的计划申请数量之和
        if ($is_plan_cancel == 1){
            if (!empty($demand_number_list) && !empty($plan_cancel_ids)){
                $this->load->model('Shipment_cancel_list_model','',false,'purchase_shipment');
                $sum_apply_cancel_qty_map = $this->Shipment_cancel_list_model->get_sum_apply_cancel_qty($demand_number_list,$plan_cancel_ids);
            }
        }

        // 25448
      foreach($orderinfo as $key=>$v){
          $pur_sku = $purchase_number.'-'.$v['sku'];
          $de_number = isset($demand_number_list[$pur_sku])?$demand_number_list[$pur_sku]:'';//备货单号
          //备货单状态
          $suggest_order_status =  $demand_number_list_new[$pur_sku]['suggest_order_status']??'';
          $suggest_order_status = !empty($suggest_order_status)?getPurchaseStatus($suggest_order_status):'';

          $orderinfo[$key]['demand_number']= $de_number;
          $orderinfo[$key]['suggest_order_status']= $suggest_order_status;
          //单个备货单采购金额
          $orderinfo[$key]['purchase_price_per_demand']= sprintf("%.3f",$v['purchase_unit_price']*$v['confirm_amount']);;

          $orderinfo[$key]['plan_product_arrive_time']= isset($plan_product_arrive_time_list[$purchase_number])?$plan_product_arrive_time_list[$purchase_number]:'';//预计到货时间
          $orderinfo[$key]['instock_qty']= isset($warehouse_list[$pur_sku])?$warehouse_list[$pur_sku]:0; //入库数量
          $orderinfo[$key]['loss_amount']=isset($purchase_order_reportloss[$pur_sku])?$purchase_order_reportloss[$pur_sku]:0; //报损数量
          $orderinfo[$key]['cancel_ctq']=isset($order_cancel_list[$pur_sku])?$order_cancel_list[$pur_sku]:0; //已取消数量
          if ($is_plan_cancel == 1){
              if(isset($sum_apply_cancel_qty_map[$orderinfo[$key]['demand_number']])) {//属于计划系统申请取消的正常显示
                  $orderinfo[$key]['is_plan'] = true;
              }else{
                  //非计划系统申请的数据要进行置灰
                  $orderinfo[$key]['is_plan'] = false;
              }

              $orderinfo[$key]['cancel_qty'] = $sum_apply_cancel_qty_map[$orderinfo[$key]['demand_number']]??'0'; //取消数量
              $orderinfo[$key]['cancel_qty'] = (int)$orderinfo[$key]['cancel_qty'];
          }else{
              $orderinfo[$key]['cancel_qty'] = 0;
          }
/*
          $orderinfo[$key]['average_freight']=sprintf("%.3f",$v['freight']/$v['confirm_amount']); //每个sku平均运费
          $orderinfo[$key]['average_discount']=sprintf("%.3f",$v['discount']/$v['confirm_amount']); //每个sku平均优惠额
          $orderinfo[$key]['average_process_cost']=sprintf("%.3f",$v['process_cost']/$v['confirm_amount']); //每个sku平均加工费*/
          $orderinfo[$key]['must_del'] = false;
          if(in_array($pur_sku, array_keys($demand_number_list_new)) && isset($demand_number_list_new[$pur_sku]['suggest_order_status'])
              && in_array($demand_number_list_new[$pur_sku]['suggest_order_status'], [9,11,14]))$orderinfo[$key]['must_del'] = true;
          //只展示勾选的明细
          if (!empty($filter_ids)) {
              if (in_array($v['id'],$filter_ids)) {
                  $orderinfo[$key]['is_show'] = 1;

              } else {
                  $orderinfo[$key]['is_show'] = 0;


              }

          }
          $cancel_times  = $this->purchase_db->select('count(cancel_detail.id) as num')->from('purchase_order_cancel_detail cancel_detail')->join('purchase_order_cancel','purchase_order_cancel.id=cancel_detail.cancel_id')
              ->where(['cancel_detail.items_id'=>$v['id'],'purchase_order_cancel.is_edit'=>2])
              ->where('purchase_order_cancel.create_time<=',date('Y-m-d H:i:s'))
              ->where('purchase_order_cancel.create_time>=',date('Y-m-d H:i:s',strtotime('-30days')))
              ->get()->row_array();
          $orderinfo[$key]['cancel_times'] = $cancel_times['num']??0;
          //取消原因
          if (!empty($reason_items_list)) {
              $orderinfo[$key]['cancel_reason'] = !empty($reason_items_list[$v['id']])?getOrderCancelReason($reason_items_list[$v['id']]):'';


          }





      }
        unset($warehouse_list);
        unset($purchase_order_reportloss);
        return $orderinfo;
        
        
    }
    /**
     * 获取备货单号集合
     * @param string  $purchase_number
     * @return array 
     */
    public function get_demand_number_list($purchase_number){
        $data=[];
        $suggest_map = $this->purchase_db->select('purchase_number,sku,demand_number')
            ->where('purchase_number', $purchase_number)
            ->get('purchase_suggest_map')
            ->result_array();
        if(empty($suggest_map)){
            return [];
        }
        foreach ($suggest_map as $row) {
            $data[$row['purchase_number'].'-'.$row['sku']]=$row['demand_number'];
        }
        return $data;

    }


    /**
     * 获取备货单号集合
     * @param string  $purchase_number
     * @return array
     */
    public function get_demand_number_list_new($purchase_number){
        $data=[];
        $suggest_map = $this->purchase_db
            ->from('purchase_suggest_map as m')
            ->join('purchase_suggest as s', "m.demand_number=s.demand_number", "left")
            ->select('m.purchase_number,m.sku,m.demand_number,s.suggest_order_status')
            ->where('m.purchase_number', $purchase_number)
            ->get()
            ->result_array();
        if(empty($suggest_map)){
            return [];
        }
        foreach ($suggest_map as $row) {
            $data[$row['purchase_number'].'-'.$row['sku']]=$row;
        }
        return $data;
    }

    /**
     * 获取预计到货时间
     * @param string  $purchase_number
     * @return array 
     */
    public function plan_product_arrive_time_list($purchase_number){
     $order=  $this->purchase_db
             ->select('plan_product_arrive_time,purchase_number')
             ->where('purchase_number',$purchase_number)
             ->get('purchase_order')
             ->result_array();
   
     if(empty($order)) {
         return [];
     }          
      return array_column($order, 'plan_product_arrive_time','purchase_number');  
        
    }

    /**
     * 获取历史取消的数量
     * @param string  $purchase_number
     * @param array $sku
     * @param string $convert_key
     * @return array
     */
    public function get_order_cancel_list($purchase_number,$sku = null,$convert_key = 'cancel_ctq' ){
        $audit_status_list = [CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_YDC];

        // 采购单 已取消总金额
        $po_cancel_sql_sub = " AND A.audit_status IN(".implode(",",$audit_status_list).")";
        if(is_array($purchase_number)){
            $po_cancel_sql_sub .= " AND B.purchase_number IN('".implode("','",$purchase_number)."')";
        }else{
            $po_cancel_sql_sub .= " AND B.purchase_number='$purchase_number'";
        }
        if(isset($sku) and $sku){
            if(is_array($sku)){
                $po_cancel_sql_sub .= " AND B.sku IN('".implode("','",$sku)."')";
            }else{
                $po_cancel_sql_sub .= " AND B.sku='$sku'";
            }
        }

        // 获取采购单
        $po_cancel_sql = "
        SELECT 
            combine_key,
            audit_status,
            SUM(cancel_ctq) AS cancel_ctq,
            SUM(item_total_price) AS item_total_price,
            SUM(freight) AS freight,
            SUM(discount) AS discount,
            SUM(process_cost) AS process_cost
        FROM (
        
            SELECT 
                CONCAT(B.purchase_number, '-', B.sku) AS combine_key, 
                A.audit_status, 
                SUM(B.cancel_ctq) AS cancel_ctq,
                SUM(B.item_total_price) AS item_total_price,
                0 AS freight,
                0 AS discount,
                0 AS process_cost
            FROM pur_purchase_order_cancel AS A 
            INNER JOIN pur_purchase_order_cancel_detail AS B ON A.id=B.cancel_id
            WHERE 1=1 {$po_cancel_sql_sub}
            GROUP BY B.purchase_number, B.sku
        
            UNION ALL
        
        
            SELECT
                po_detail.combine_key,
                po_detail.audit_status,
                po_detail.cancel_ctq,
                po_detail.item_total_price,
                SUM(po_detail.freight) AS freight,
                SUM(po_detail.discount) AS discount,
                SUM(po_detail.process_cost) AS process_cost
            FROM (
        
                SELECT  
                CONCAT(B.purchase_number,'-',B.sku) AS combine_key,
                A.audit_status,
                0 AS cancel_ctq,
                0 AS item_total_price,
                B.freight,
                B.discount,
                B.process_cost
        
                FROM pur_purchase_order_cancel AS A
                INNER JOIN pur_purchase_order_cancel_detail AS B ON A.id=B.cancel_id
                WHERE 1=1 {$po_cancel_sql_sub}
                GROUP BY B.cancel_id,B.purchase_number
        
            ) AS po_detail
            GROUP BY po_detail.combine_key
        )
        AS combine
        GROUP BY combine_key";

        $cancel_detail = $this->purchase_db->query($po_cancel_sql)->result_array();
        $cancel_detail = arrayKeyToColumn($cancel_detail,'combine_key');

        if(isset($convert_key) and $convert_key){
            $cancel_detail = array_column($cancel_detail,$convert_key,'combine_key');
            return $cancel_detail;
        }else{
            return $cancel_detail;
        }
    }


    /**
     * 获取报损数量
     * @param string  $purchase_number
     * @param array $sku
     * @return array 
     */
    public function get_purchase_order_reportloss_list($purchase_number,$sku){
         $data = [];
        $purchase_order_reportloss = $this->purchase_db->select('SUM(loss_amount) AS loss_amount,sku,pur_number')
                ->where('pur_number', $purchase_number)
                ->where('status',REPORT_LOSS_STATUS_FINANCE_PASS)
                ->where_in('sku', $sku)
                ->group_by('pur_number,sku')
                ->get('purchase_order_reportloss')
                ->result_array();
        if (empty($purchase_order_reportloss))
            return [];
        foreach ($purchase_order_reportloss as $key => $value) {
            $data[$value['pur_number'] . "-" . $value['sku']] = $value['loss_amount'];
        }
        return $data;
    }

    /**
     * 获取入库数量集合
     * @param string  $purchase_number
     * @param array $sku
     * @return array 
     * **/
   public function get_warehouse_list($purchase_number,$sku){
        $data=[];

        $warehouse_results = $this->purchase_db->select('instock_qty,sku,purchase_number')
                ->where('purchase_number', $purchase_number)
                ->where_in('sku', $sku)
                ->get('warehouse_results')
                ->result_array();
        if (empty($warehouse_results))
            return [];
        foreach ($warehouse_results as $key => $value) {
            $data[$value['purchase_number']."-".$value['sku']][]=$value['instock_qty'];
            
        }
        foreach ($data as $k => $row) { //统计
            $data[$k]= array_sum($row);
        }
        return $data;
    }
   /**
     * 获取入库不良品数量集合
     * @param string  $purchase_number
     * @param array $sku
     * @return array 
     * **/
   public function get_warehouse_bad_qty($purchase_number,$sku){
        $data=[];

        $warehouse_results = $this->purchase_db->select('bad_qty,sku,purchase_number')
                ->where('purchase_number', $purchase_number)
                ->where_in('sku', $sku)
                ->get('warehouse_results')
                ->result_array();

        if (empty($warehouse_results))
            return [];
        foreach ($warehouse_results as $key => $value) {
            $data[$value['purchase_number']."-".$value['sku']][]=$value['bad_qty'];
            
        }
        foreach ($data as $k => $row) { //统计
            $data[$k]= array_sum($row);
        }
        return $data;
    }

    /**
     * 获取入库多货数量集合
     * @param string  $purchase_number
     * @param array $sku
     * @return array
     * **/
    public function get_warehouse_instock_more_qty($purchase_number,$sku){
        $data=[];

        $warehouse_results = $this->purchase_db->select('instock_qty_more,sku,purchase_number')
            ->where('purchase_number', $purchase_number)
            ->where_in('sku', $sku)
            ->get('warehouse_results')
            ->result_array();

        if (empty($warehouse_results))
            return [];
        foreach ($warehouse_results as $key => $value) {
            $data[$value['purchase_number']."-".$value['sku']][]=$value['instock_qty_more'];

        }
        foreach ($data as $k => $row) { //统计
            $data[$k]= array_sum($row);
        }
        return $data;
    }
    /**
     * 获取最新入库时间及入库人
     * @param type $purchase_number
     * @param type $sku
     * @return type
     */
    public function get_warehouse_user_date($purchase_number,$sku){
          $warehouse_results = $this->purchase_db->select('instock_user_name,instock_date')
                ->where('purchase_number', $purchase_number)
                ->where('sku', $sku)
                ->order_by('create_time desc ')  
                ->get('warehouse_results')
                ->row_array();
      return $warehouse_results;
    }
    /**
     * 获取入库日志
     * @param type $purchase_number
     * @param type $sku
     */
    public function get_warehouse_log($purchase_number,$sku){
       $warehouse_results= $this->purchase_db->select('sku,arrival_qty,defective_num,delivery_user,arrival_date')
               ->where('purchase_number',$purchase_number)->where('sku',$sku)
                ->get('warehouse_results')
                ->result_array();
       if(empty($warehouse_results)) return [];
        foreach ($warehouse_results as $key => $value) {
            $warehouse_results[$key]['product_name']=$this->sku_name($sku);
        }
        return $warehouse_results;
    }
    /**
     * 获取产品名称
     * @param type $sku
     * @return type
     */
    public function sku_name($sku){
       $order= $this->purchase_db->select('product_name')
                    ->where('sku',$sku)
                    ->get('purchase_order_items')->row_array();
       return !empty($order)?$order['product_name']:'';
    }

    /**
     * 已取消的总金额
     * @param string $purchase_numbers 采购单号
     * @param string $type 汇总的金额类型
     * @return int|bool|array
     * @author harvin 2019-1-10
     * */
    public function get_total_cancellation($purchase_numbers,$type = 'total_cancellation') {
        $audit_status_list = [CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_YDC];

        // 采购单 已取消总金额
        $po_cancel_sql_sub = " AND A.audit_status IN(".implode(",",$audit_status_list).")";
        if(is_array($purchase_numbers)){
            $po_cancel_sql_sub .= " AND B.purchase_number IN('".implode("','",$purchase_numbers)."')";
        }elseif(is_string($purchase_numbers)){
            $po_cancel_sql_sub .= " AND B.purchase_number='$purchase_numbers'";
        }else{
            return 0;
        }

        // 获取采购单
        $po_cancel_sql = "
        SELECT 
            combine_key,
            audit_status,
            SUM(cancel_ctq) AS cancel_ctq,
            SUM(item_total_price) AS item_total_price,
            SUM(freight) AS freight,
            SUM(discount) AS discount,
            SUM(process_cost) AS process_cost
        FROM (
        
            SELECT 
                B.purchase_number AS combine_key, 
                A.audit_status, 
                SUM(B.cancel_ctq) AS cancel_ctq,
                SUM(B.item_total_price) AS item_total_price,
                0 AS freight,
                0 AS discount,
                0 AS process_cost
            FROM pur_purchase_order_cancel AS A 
            INNER JOIN pur_purchase_order_cancel_detail AS B ON A.id=B.cancel_id
            WHERE 1=1 {$po_cancel_sql_sub}
            GROUP BY B.purchase_number
        
            UNION ALL
        
        
            SELECT
                po_detail.combine_key,
                po_detail.audit_status,
                po_detail.cancel_ctq,
                po_detail.item_total_price,
                SUM(po_detail.freight) AS freight,
                SUM(po_detail.discount) AS discount,
                SUM(po_detail.process_cost) AS process_cost
            FROM (
        
                SELECT  
                B.purchase_number AS combine_key,
                A.audit_status,
                0 AS cancel_ctq,
                0 AS item_total_price,
                B.freight,
                B.discount,
                B.process_cost
        
                FROM pur_purchase_order_cancel AS A
                INNER JOIN pur_purchase_order_cancel_detail AS B ON A.id=B.cancel_id
                WHERE 1=1 {$po_cancel_sql_sub}
                GROUP BY B.cancel_id,B.purchase_number
        
            ) AS po_detail
            GROUP BY po_detail.combine_key
        )
        AS combine
        GROUP BY combine_key";

        $cancel_detail = $this->purchase_db->query($po_cancel_sql)->result_array();
//        print_r($cancel_detail);exit;
        if($cancel_detail){
            foreach ($cancel_detail as $key => $value){
                $cancel_detail[$key]['total_cancellation'] = $value['item_total_price']
                    + $value['freight']
                    - $value['discount']
                    + $value['process_cost'];
            }
        }else{
            if(is_string($purchase_numbers)){
                if(!empty($type)) return 0;
                else return [];
            }else{
                return [];
            }
        }
//        print_r($cancel_detail);exit;

        if(is_string($purchase_numbers)){// 字符串则只返回 当前KEY的值
            if(!empty($type) and $cancel_detail){
//                print_r($cancel_detail[0][$type]);exit;
                if(isset($cancel_detail[0][$type])){
                    return $cancel_detail[0][$type];
                }else{
                    return 0;
                }
            }else{
//                print_r($cancel_detail);exit;
                return arrayKeyToColumn($cancel_detail,'combine_key');
            }
        }else{// 数组返回所以KEY的值

            if(!empty($type) and $cancel_detail){
//                print_r(array_column($cancel_detail,$type,'combine_key'));exit;
//                var_dump($cancel_detail[0][$type]);exit;
                if(isset($cancel_detail[0][$type])){
                    return array_column($cancel_detail,$type,'combine_key');
                }else{
                    return [];
                }
            }else{
//                print_r(1);exit;
                return arrayKeyToColumn($cancel_detail,'combine_key');
            }
        }
    }

    /**
     * 获取已取消的金额 汇总明细
     * @param $purchase_numbers
     * @return array
     * @author Jolon 2020-04-29
     */
    public function get_total_cancel_price_details($purchase_numbers){
        die("废弃");// 请使用 $this->>get_total_cancellation 方法
//        $cancel_price_list = $this->purchase_db
//            ->select(
//                'cd.purchase_number,'
//                .'SUM(cd.item_total_price) AS cancel_product_money,'
//                .'SUM(cd.freight) AS cancel_freight,'
//                .'SUM(cd.discount) AS cancel_discount,'
//                .'SUM(cd.process_cost) AS cancel_process_cost,'
//                .'SUM(cd.item_total_price + cd.freight + cd.process_cost - cd.discount) AS cancel_real_price'
//            )
//            ->from('purchase_order_cancel_detail as cd')
//            ->join('purchase_order_cancel as c', 'c.id=cd.cancel_id', 'INNER')
//            ->where_in('cd.purchase_number', $purchase_numbers)
//            ->where_in('c.audit_status', [CANCEL_AUDIT_STATUS_SYSTEM, CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_YDC])
//            ->group_by('cd.purchase_number')
//            ->get()
//            ->result_array();
//
//        return !empty($cancel_price_list)?$cancel_price_list:[];
    }

    /**
     * 保存数据
     * @author harvin 2019-1-11
     * @param array $params  采购单号
     * @author harvin 2019-1-11
     * @return mixed
     */
    public function get_cancel_unarrived_goods_save(array $params, $is_system=false) {
        $this->load->helper('status_finance');
        $this->load->model('purchase_order_cancel_model', '', false, 'purchase');
        $this->load->model('statement/Charge_against_records_model');
        //$this->load->library('mongo_db');
        $part_success = false;

        // 20210429 解决重复提交问题
        $is_has_lock = $this->lock_cancel_order($params['ids'], false);
        if($is_has_lock){
            return ['msg' => '已申请或提交已重复，请1分钟后再试！', 'bool' => false];
        }
        $this->lock_cancel_order($params['ids']);

        try {
            //开始事物
            //$this->purchase_db->trans_begin(); 关闭事务：Jolon,JAVA执行成功但是采购保存失败
            //需要验证填写的“取消数量”是否合理，如果“申请中/已生成的报损数量+已取消数量+申请的取消数量+入库数量>采购数量”
            //转化数组
            $ids = explode(',', $params['ids']);
            $ids = array_unique(array_filter($ids));
            if(empty($ids)){
                throw new Exception('参数id,不存在');
            }
            $data = [];
            $application_cancel_fright=[];//申请取消的运费,按采购单汇总
            $application_cancel_discount=[];//申请取消的优惠,按采购单汇总
            $application_cancel_process_cost=[];//申请取消的加工费,按采购单汇总
            $history_cancel_fright=[];//历史取消的运费,按采购单汇总
            $history_cancel_discount=[];//历史取消的优惠,按采购单汇总
            $history_cancel_process_cost=[];//历史取消的加工费,按采购单汇总
            $loss_cancel_fright=[];//报损取消的运费,按采购单汇总
            $loss_cancel_process_cost=[];//报损取消的加工费,按采购单汇总
            $purchase_number_fright=[];//采购单总运费
            $purchase_number_discount=[];//采购单总优惠
            $purchase_number_process_cost=[];//采购单总加工费
            $charge_against_process_cost=[];//采购单总退款冲销加工费
            $application_cancel_total_price = [];//采购单总取消金额，按采购单汇总
            $cancel_info = [];
            $ap_can_ctq = $params['application_cancel_ctq'];
            $send_wms = false;

            // 如果取消未处理完成
            if(false && isset($params['is_submit']) && $params['is_submit']){
                $p_n_list = $this->purchase_db->from('purchase_order_cancel as c')
                    ->join("pur_purchase_order_cancel_detail as d", "c.id=d.cancel_id", "left")
                    ->select('c.cancel_number,d.items_id,d.purchase_number')
                    ->where_not_in('c.audit_status', [CANCEL_AUDIT_STATUS_CFYSK, CANCEL_AUDIT_STATUS_SYSTEM, CANCEL_AUDIT_STATUS_YZBS, CANCEL_AUDIT_STATUS_YDC])
                    ->where_in('d.items_id', $ids)
                    ->group_by("c.cancel_number")
                    ->get()->result_array();
                if($p_n_list && !empty($p_n_list)){
                    $cancel_l = array_unique(array_column($p_n_list, 'cancel_number'));
                    if(!empty($cancel_l))throw new Exception("采购单对应的取消单：".implode(',', $cancel_l).' 未处理完，请处理后再申请取消！');
                }
            }

            // o的所有备货单全部取消的：即已取消数量+申请取消数量=采购数量时，验证po对应的拍单号状态是否=交易关闭，未关闭不能提交
            // po的所有备货单全部取消的——即每一个备货单：已取消数量+申请取消数量=采购数量时，且入库数量=0。验证po对应的拍单号状态是否=交易关闭，未关闭不能提交
            // 21197 2020-07-23   yefanli
            $can_cancel = $this->purchase_db->select("oi.id, oi.purchase_number, po.ali_order_status,oi.upselft_amount, po.refund_status")
                ->from('purchase_order_items as oi')
                ->join('purchase_order as po','oi.purchase_number = po.purchase_number','left')
                ->where('po.source =', 2)
                ->where("po.account_type =", 20)
                ->where_in("oi.id", $ids)->get()->result_array();
            $cancel_list = [];
            foreach($can_cancel as $val){
                $o_l = isset($params['confirm_amount'][$val['id']]) ? (int)$params['confirm_amount'][$val['id']] : 0;
                $c_d = isset($params['cancel_ctq'][$val['id']]) ? (int)$params['cancel_ctq'][$val['id']] : 0;
                $c_i = isset($ap_can_ctq[$val['id']]) ? (int)$ap_can_ctq[$val['id']] : 0;
                $u_a = !empty($val["upselft_amount"]) ? (int)$val["upselft_amount"]:0;
                if(!in_array($val['purchase_number'], array_keys($cancel_list))){
                    $cancel_list[$val['purchase_number']] = [
                        "order_all" => $o_l,     // 总单数
                        "canceled"  => $c_d,  // 已取消单数
                        "canceling" => $c_i,  // 计划取消单数
                        "upselft_amount" => $u_a,  // 已入库数量
                        "status"    => $val["ali_order_status"],
                        "refund_status"    => $val["refund_status"],
                    ];
                }else{
                    $cancel_list[$val['purchase_number']]["order_all"] += $o_l;
                    $cancel_list[$val['purchase_number']]["canceled"] += $c_d;
                    $cancel_list[$val['purchase_number']]["canceling"] += $c_i;
                    $cancel_list[$val['purchase_number']]["upselft_amount"] += $u_a;
                }
            }

            $temp_freight = $temp_discount = $temp_process_cost = 0;

            foreach ($ids as $id) {
                //获取每个sku 申请中取消数量 及已取消数量
                $application_cancel_ctq = isset($ap_can_ctq[$id]) ? $ap_can_ctq[$id] : 0; //申请中数量
                $cancel_ctq = isset($params['cancel_ctq'][$id]) ? $params['cancel_ctq'][$id] : 0; //已取消的数量
                $confirm_amount = isset($params['confirm_amount'][$id]) ? $params['confirm_amount'][$id] : 0; //采购数量
                $instock_qty = isset($params['instock_qty'][$id]) ? $params['instock_qty'][$id] : 0; //入库数量
                $loss_amount = isset($params['loss_amount'][$id]) ? $params['loss_amount'][$id] : 0; //报损数量

                if (isset($params['freight'][$id]) && !is_two_decimal($params['freight'][$id])){
                    throw new Exception('取消运费小数最多只能为两位');
                }

                if (isset($params['discount'][$id]) && !is_two_decimal($params['discount'][$id])){
                    throw new Exception('取消优惠小数最多只能为两位');
                }

                if (isset($params['process_cost'][$id]) && !is_two_decimal($params['process_cost'][$id])){
                    throw new Exception('取消加工费小数最多只能为两位');
                }

                //获取对应的采购单号及sku
                $order_items = $this->purchase_db
                    ->select('purchase_number,sku,freight,discount')
                    ->where('id', (int)$id)
                    ->get('purchase_order_items')
                    ->row_array();

                $it_pur = $order_items['purchase_number'];
                $total_freight_discount = $this->get_total_freight_discount_true($it_pur);//查询总运费和总优惠

                if (!isset($purchase_number_fright[$it_pur])){
                    $purchase_number_fright[$it_pur]=$total_freight_discount['total_freight'];
                    $purchase_number_discount[$it_pur]=$total_freight_discount['total_discount'];
                    $purchase_number_process_cost[$it_pur]=$total_freight_discount['total_process_cost'];
                }

                $purchase_order_reportloss=[];
                if ($loss_amount>0){
                    //查询报损有没有审核通过,审核通过,只加入库数量,审核没通过,加上报损数量
                    $purchase_order_reportloss = $this->purchase_db->select('loss_amount')
                        ->where('pur_number', $it_pur)
                        ->where('status',REPORT_LOSS_STATUS_FINANCE_PASS)
                        ->where('sku', $order_items['sku'])
                        ->get('purchase_order_reportloss')
                        ->result_array();

                    if (!empty($purchase_order_reportloss)){
                        if (($application_cancel_ctq + $cancel_ctq + $instock_qty ) > $confirm_amount && !empty($order_items)) {
                            $data['cancel'][] = $it_pur . "--" . $order_items['sku'];
                        }

                    }else{
                        if (($application_cancel_ctq + $cancel_ctq + $instock_qty + $loss_amount) > $confirm_amount && !empty($order_items)) {
                            $data['cancel'][] = $it_pur . "--" . $order_items['sku'];
                        }
                    }
                }else{
                    if (($application_cancel_ctq + $cancel_ctq + $instock_qty) > $confirm_amount && !empty($order_items)) {
                        $data['cancel'][] = $it_pur . "--" . $order_items['sku'];
                    }
                }

                $freight_discount= $this->get_freight_discount($it_pur);  //历史取消的运费及优惠额
                $freight_ben=isset($params['freight'][$id]) ? $params['freight'][$id] : 0; //本次取消的运费;
                $discount_ben=isset($params['discount'][$id]) ? $params['discount'][$id] : 0; //本次取消的优惠额;
                $process_cost_ben=isset($params['process_cost'][$id]) ? $params['process_cost'][$id] : 0; //本次取消的加工费
                $total_price_ben=(isset($ap_can_ctq[$id])&&isset($params['purchase_unit_price'][$id]) )?$ap_can_ctq[$id]*$params['purchase_unit_price'][$id]  : 0; //实际商品金额

                //将同一个采购单的申请的取消运费优惠相加
                if (!isset($application_cancel_fright[$it_pur])){
                    $application_cancel_fright[$it_pur]=$freight_ben;
                    $application_cancel_discount[$it_pur]=$discount_ben;
                    $application_cancel_process_cost[$it_pur]=$process_cost_ben;


                }

                if (!isset($application_cancel_total_price[$it_pur])) {
                    $application_cancel_total_price[$it_pur] = $total_price_ben;
                } else {
                    $application_cancel_total_price[$it_pur]+= $total_price_ben;
                }

                //将历史取消的运费优惠相加
                if (!isset($history_cancel_fright[$it_pur])){
                    $history_cancel_fright[$it_pur]=$freight_discount['history_freight'];
                    $history_cancel_discount[$it_pur]=$freight_discount['history_discount'];
                    $history_cancel_process_cost[$it_pur]=$freight_discount['history_process_cost'];
                }

                //查询是否有报损运费
                $purchase_order_reportloss_fright = $this->purchase_db->select('loss_freight ,loss_process_cost ')
                    ->where('pur_number', $it_pur)
                    ->where('status=', REPORT_LOSS_STATUS_FINANCE_PASS)
                    ->group_by('stamp_number')
                    ->get('purchase_order_reportloss')
                    ->result_array();

                if (!empty($purchase_order_reportloss_fright)){
                    $loss_history = 0 ;
                    $loss_process_history = 0;
                    //将报损的运费相加
                    foreach ($purchase_order_reportloss_fright as $order_report_loss_fright ) {
                        $loss_history+=$order_report_loss_fright['loss_freight'];
                        $loss_process_history+=$purchase_order_reportloss_fright['loss_process_cost'];
                    }

                    if (!isset($loss_cancel_fright[$it_pur])){
                        $loss_cancel_fright[$it_pur]=$loss_history;
                    }

                    if (!isset($loss_cancel_process_cost[$it_pur])){
                        $loss_cancel_process_cost[$it_pur]=$loss_process_history;
                    }
                }else{
                    $loss_cancel_fright[$it_pur]=0;
                    $loss_cancel_process_cost[$it_pur]=0;
                }

                $charge_against_record = $this->Charge_against_records_model->get_ca_audit_status($it_pur,2);
                //将历史取消的运费优惠相加
                $car = isset($charge_against_record['charge_against_process_cost']) ? $charge_against_record['charge_against_process_cost']: 0;
                if (!isset($charge_against_record[$it_pur])){
                    $charge_against_process_cost[$it_pur]=$car;
                }else{
                    $charge_against_process_cost[$it_pur]+=$car;
                }

                // 物料仓 物料仓_塘厦、物料仓_虎门、物料仓_慈溪、物料仓_慈溪中转
                $ware_code = isset($params['warehouse_code'][$id]) ? $params['warehouse_code'][$id] : false; //本次取消的优惠额;
                if($ware_code && in_array($ware_code, ["CXWLC", "wuliaocang", "HM_WL", "WLCCX-ZZ"]))$send_wms = true;
            }

            foreach ($application_cancel_discount as $purchase_number => $value){
                //判断申请取消优惠是否>0 或<=po的总优惠额-已取消的总优惠额
                if ($value<0 || bccomp($value, $purchase_number_discount[$purchase_number]-$history_cancel_discount[$purchase_number], 3) > 0){
                    $errormsg = '采购单:' . $purchase_number . '取消的优惠额总和已超过po的总优惠额-已取消的总优惠额，请检查后在操作取消未到货';
                    throw new Exception($errormsg);
                }

                //判断申请取消运费是否>0 或<=po的总优惠额-已取消的总优惠额
                if ($application_cancel_fright[$purchase_number]<0 ||
                    bccomp($application_cancel_fright[$purchase_number],($purchase_number_fright[$purchase_number]-$history_cancel_fright[$purchase_number]-$loss_cancel_fright[$purchase_number]),3) > 0  ){
                    $errormsg = '采购单:' . $purchase_number . '取消的运费额总和已超过po的总运费额-已取消的总运费额-已报损的运费，请检查后在操作取消未到货';
                    throw new Exception($errormsg);
                }

                //判断申请取消加工费是否>0 或<=po的总加工费-已取消的总加工费
                if ($application_cancel_fright[$purchase_number]<0 ||
                    bccomp($application_cancel_process_cost[$purchase_number],($purchase_number_process_cost[$purchase_number]-$history_cancel_process_cost[$purchase_number]-$loss_cancel_process_cost[$purchase_number] - $charge_against_process_cost[$purchase_number]),3) > 0  ){
                    $errormsg = '采购单:' . $purchase_number . '取消的加工费总和已超过po的总加工费-已取消的总加工费-已报损的加工费-已退款冲销加工费，请检查后在操作取消未到货';
                    throw new Exception($errormsg);
                }
            }

            if (!empty($data['cancel'])) {
                $errormsg = '采购单及sku:' . implode(',', $data['cancel']) . '取消数量,报损数量之和已超过采购数量，请重新输入取消数量';
                throw new Exception($errormsg);
            }

            //记录采购单-取消未到货数量-主表
            $cancel_number=$this->Prefix_number_model->get_prefix_new_number('QX', 1, 4);
            $cancel_number="QX".date('Ymd').str_replace('QX', '', $cancel_number);
            $freights=isset($params['total_freight']) ? $params['total_freight'] : 0; //取消的运费
            $discounts= isset($params['total_discount']) ? $params['total_discount'] : 0; //取消的实际总金额
            $process_costs= isset($params['total_process_cost']) ? $params['total_process_cost'] : 0; //取消的实际总加工费
            $total_prices= isset($params['total_price']) ? $params['total_price'] : 0; //取消的实际总金额

            //验证一下申请运费和申请总运费是否相等


            $cal_application_cancel_fright = round(array_sum($application_cancel_fright),2);
            $cal_application_cancel_discount = round(array_sum($application_cancel_discount),2);
            $cal_application_cancel_process_cost = round(array_sum($application_cancel_process_cost),2);

            $cal_application_cancel_total_price  =round(array_sum($application_cancel_total_price)+array_sum($application_cancel_fright)-array_sum($application_cancel_discount)+array_sum($application_cancel_process_cost),2);

            if (bccomp($freights,$cal_application_cancel_fright,2)!=0) {
                throw new Exception('总取消运费不等于采购单取消运费总和!');
            }
            if (bccomp($discounts,$cal_application_cancel_discount,2)!=0) {
                throw new Exception('总取消折扣不等于采购单取消折扣总和!');
            }

            if (bccomp($process_costs,$cal_application_cancel_process_cost,2)!=0) {
                throw new Exception('总取消加工费不等于采购单取消加工费总和!');
            }

            if (bccomp($total_prices,$cal_application_cancel_total_price,2)!=0) {
                throw new Exception('取消实际金额不等于采购单取消实际金额总和!');
            }

            $cuid = $is_system ? 0 : (!empty($params['create_user_id'])?$params['create_user_id']:getActiveUserId());
            $cuser = $is_system ? "system" : (!empty($params['create_user_name'])?$params['create_user_name']:getActiveUserName());
            if(empty($cuid) || empty($cuser)){
                $cuid = 0;
                $cuser = "system";
            }
            $order_cancel = [
                'cancel_number' => $cancel_number, //取消未到货编码
                'create_user_id' => $cuid, //审请人ID
                'create_user_name' => $cuser, //申请人名称
                //'create_note' => isset($params['create_note']) ? $params['create_note'] : '', //申请备注
                'create_time' => date('Y-m-d H:i:s'), //申请时间
                'cancel_ctq'=>   isset($params['total_cancelled']) ? $params['total_cancelled'] : 0,//取消数量
                'cancel_image_list'=>  isset($params['image_list']) && gettype($params['image_list']) == 'array' ? json_encode($params['image_list']) : '',
                'freight' => $freights, //取消的运费
                'discount' => $discounts, //取消的优惠额
                'process_cost' => $process_costs, //取消的加工费
                'total_price' =>$total_prices, //取消的实际总金额
                'product_money' => ($total_prices - $freights + $discounts - $process_costs), //取消的商品总金额
            ];

            if(SetAndNotEmpty($params, 'cancel_id') || SetAndNotEmpty($params, 'type') || $send_wms){
                if(SetAndNotEmpty($params, 'cancel_id')){
                    //更新原先的记录
                    $this->purchase_db
                        ->where('id',$params['cancel_id'])
                        ->update('purchase_order_cancel',[
                            'is_edit'=>1,
                            'cancel_image_list'=>  isset($params['image_list']) && gettype($params['image_list']) == 'array' ? json_encode($params['image_list']) : ''
                        ]);
                }

                $cancel_inset = $this->purchase_db->insert('purchase_order_cancel', $order_cancel);
                if (empty($cancel_inset)) {
                    throw new Exception('记录采购单-取消未到货数量-主表失败');
                }
                $cancel_id = $this->purchase_db->insert_id('purchase_order_cancel');
            }

            //是否计划系统推送
            if(isset($params['is_plan_cancel']) && $params['is_plan_cancel'] == 1){
                $cancelType = 2;//计划系统取消
            } else {
                $cancelType = 1;//采购取消
            }

            $purchase_list = $this->get_purchase_list($ids);
            //记录采购单-取消未到货数量-子表
            $purchase_number_list = $sku_list = $order_status = $suggest_list = [];
            foreach ($purchase_list as &$val_a){
                $purchase_number_list[$val_a['id']] = $val_a['purchase_number'];
                $sku_list[$val_a['id']] = $val_a['sku'];
                $order_status[$val_a['id']] = $val_a;
                $suggest_list[] = [
                    "demandNumber"  => $val_a['demand_number'],
                    "poNumber"      => $val_a['purchase_number'],
                    "sku"           => $val_a['sku'],
                ];
            }

            $cancel_reason = [];
            foreach ($ids as $val) {
                $application_cancel_ctq_s = isset($ap_can_ctq[$val]) ? $ap_can_ctq[$val] : 0;
                if (isset($ap_can_ctq[$val])) {
                    if ($application_cancel_ctq_s > 0) {
                        //记录之前的采购单状态 已防驳回，返回原状态
                        $order_number_y=isset($purchase_number_list[$val]) ? $purchase_number_list[$val] : '';
                        // 获取编辑前的对应的报损编码
                        $relative_superior_number = '';
                        if(SetAndNotEmpty($params, 'cancel_id')){
                            $relative_superior_number = $this->purchase_db->select('relative_superior_number')
                                ->where('cancel_id',$params['cancel_id'])
                                ->where('items_id',$val)
                                ->get('purchase_order_cancel_detail')
                                ->row_array();
                            $relative_superior_number = $relative_superior_number?$relative_superior_number['relative_superior_number']:'';
                        }
                        if(empty($order_status)){
                            throw new Exception('采购单,不存在');
                        }
                        $cancel_detail = [
//                            'cancel_id' => $cancel_id, //取消未到货主表的id
                            'items_id' => $val, //采购单明细表id
                            'purchase_number' => $order_number_y, //采购单号
                            'sku' => isset($sku_list[$val]) ? $sku_list[$val] : '', //sku
                            'cancel_ctq' => isset($ap_can_ctq[$val]) ? $ap_can_ctq[$val] : 0, //取消数量
                            'freight' => isset($params['freight'][$val]) ? $params['freight'][$val] : 0, //取消运费
                            'discount' => isset($params['discount'][$val]) ? $params['discount'][$val] : 0, //取消优惠额
                            'process_cost' => isset($params['process_cost'][$val]) ? $params['process_cost'][$val] : 0, //取消加工费
                            'item_total_price' => $ap_can_ctq[$val] * $params['purchase_unit_price'][$val], //取消金额(取消数量*单价)
                            'create_note' =>  isset($params['create_note'][$val])?$params['create_note'][$val]:'', //取消备注
                            'purchase_order_status'=> isset($order_status[$val]['purchase_order_status'])?$order_status[$val]['purchase_order_status']:0,
                            'relative_superior_number' => $relative_superior_number,
                        ];
                        $cancel_reason[] = [
                            'items_id' => $val, //采购单明细表id
                            'sku' => isset($sku_list[$val]) ? $sku_list[$val] : '', //sku
                            'cancel_reason' => isset($params['cancel_reason'][$val]) ? $params['cancel_reason'][$val] : 0, //取消原因
                            'images' => isset($params['cancel_reason_img'][$val]) ? $params['cancel_reason_img'][$val] : '', // 取消聊天截图
                            'estimate_time' => isset($params['estimate_time'][$val]) ? $params['estimate_time'][$val] : '', // 预计供货时间
                        ];

                        $cancel_detail_list[$val]=$cancel_detail;

                        if(isset($params['is_plan_cancel']) && $params['is_plan_cancel'] == 1){
                            //备货单维度的-取消
                            $cancel_info[] = [
                                'purchase_number' => $order_number_y,
                                'sku' => isset($sku_list[$val]) ? $sku_list[$val] : '',
                                'cancel_ctq' => isset($ap_can_ctq[$val]) ? $ap_can_ctq[$val] : 0, //取消数量
                            ];
                        }

                        if(SetAndNotEmpty($params, 'cancel_id') || SetAndNotEmpty($params, 'type') || $send_wms){//再编辑插入数据
                            $cancel_detail['cancel_id'] = $cancel_id;
                            $cancel_detail_insert = $this->purchase_db->insert('purchase_order_cancel_detail', $cancel_detail);
                            if (empty($cancel_detail_insert)) {
                                throw new Exception('采购单-取消未到货数量-子表记录失败');
                            }
                        }

                        $purchase_number_order[]=$order_number_y;
                    }
                }
            }
            if(empty($purchase_number_order)){
                throw new Exception('采购单单号不存在');
            }
            $purchase_number_order = array_unique($purchase_number_order);
            if(!isset($params['type']) && empty($params['type']) && !$send_wms){

                //调java取消接口
                $order_cancel_temp = $order_cancel;
                $order_cancel_temp['cancel_type'] =$cancelType;
                $post_data['purchase_number_list'] = $purchase_list;
                $post_data['order_cancel'] = $order_cancel_temp;
                $post_data['cancel_detail_list'] = $cancel_detail_list;

//                echo json_encode($post_data);die;

                //请求java接口报损
                $report_loss_service_url = getConfigItemByName('api_config', 'java_system_service', 'cancel'); //获取java取消未到货接口地址
                $access_taken = getOASystemAccessToken();//访问java token

                $url_api=$report_loss_service_url."?access_token=".$access_taken;
                $result_json = getCancelCurlData($url_api, json_encode($post_data), 'post', array('Content-Type: application/json'));

                // 记录请求响应日志
                $api_log=[
                    'record_number'=>$cancel_number,
                    'api_url'=>$report_loss_service_url,
                    'record_type'=>'取消未到货推送仓库',
                    'post_content'=>json_encode($post_data),
                    'response_content'=>$result_json,
                    'create_time'=>date('Y-m-d H:i:s')
                ];
                $this->purchase_db->insert('api_cancel_log',$api_log);

                /*// 记录日志
                $data = [
                    'record_number' => $cancel_number,
                    'api_url' => $report_loss_service_url,
                    'record_type' => '取消未到货推送仓库',
                    'post_content' => json_encode($post_data),
                    'response_content' => json_encode($result_json),
                    'create_time' => date('Y-m-d H:i:s')
                ];
                $this->mongo_db->insert('get_cancel_unarrived_goods_save', $data);*/

                if(is_json($result_json)){
                    $result = json_decode($result_json,true);
                    if (isset($result['code']) && $result['code'] != 200){
                        throw new Exception(isset($result['msg'])?$result['msg']:$result_json);
                    }elseif (isset($result['status'])){
                        throw new Exception(isset($result['message'])?$result['message']:$result_json);
                    }else{// 取消成功
                        /**********取消成功推入消息队列，等待判断在途是否异常处理-START**********/
                        foreach ($purchase_number_order as $purchase_number){
                        $this->load->library('Rabbitmq');
                        //创建消息队列对象
                        $mq = new Rabbitmq();
                        //设置参数
                        $mq->setExchangeName('PURCHASE_ORDER_INNER_ON_WAY_EX_NAME');
                        $mq->setRouteKey('PURCHASE_ORDER_INNER_ON_WAY_R_KEY');
                            $mq->setType(AMQP_EX_TYPE_FANOUT);//设置为多消费者模式 分发
                        //构造存入数据
                        $push_data = array(
                            'type' => 'purchase_order_cancel',
                                'purchase_number' => $purchase_number
                        );
                        //存入消息队列
                        $mq->sendMessage($push_data);
                            //延迟0.1秒
                            usleep(100000);
                        }
                        /**********取消成功推入消息队列，等待判断在途是否异常处理-END**********/
                    }
                }else{
                    throw new Exception($result_json);
                }

                unset($order_cancel);
                unset($cancel_detail_list);

                $cancelId = isset($result['data']['cancelId']) ? $result['data']['cancelId']: false;

                //记录取消未到货操作日志
                $this->Reject_note_model->cancel_log([
                    'cancel_id'=>$cancelId,
                    'operation_type'=>'申请取消未到货',
                    'operation_content'=>(is_array($params['create_note']) ? implode(',', array_values($params['create_note'])): $params['create_note']),
                ]);

                $error_flag = [];
                foreach ($result['data']['results'] as $value){
                    if(!$value['success']){
                        $error_flag[] = $value['purNumber'].'：'.$value['message'];
                    }
                }

                if (count($error_flag) > 0){
                    if(count($error_flag) == count($result['data']['results'])){
                        throw new Exception('全部取消失败，原因:'.implode(',', $error_flag));
                    }else{
                        $part_success = implode(',', $error_flag);
                    }
                }

                // 21197 2020-07-23   yefanli  图片处理
                if(isset($params['image_list']) && gettype($params['image_list']) == 'array' && count($params['image_list']) > 0
                    && $cancelId){
                    $this->purchase_db
                        ->where('id', $cancelId)
                        ->update('purchase_order_cancel',['cancel_image_list'=> json_encode($params['image_list'])]);
                }

                // 如果是系统自动申请
                if($is_system && $cancelId){
                    $this->purchase_db
                        ->where('id', $cancelId)
                        ->update('purchase_order_cancel',['cancel_source'=> 2]);
                    $this->cancel_unarrived_goods_examine_save($cancelId, '系统自动审核', 1, $is_system, $params);
                }

                // 取消原因和聊天截图
                if(!$is_system && $cancelId && count($cancel_reason) > 0){
                    $scree_add = [];
                    foreach ($cancel_reason as $cr_val){
                        $this->purchase_db->where(["cancel_id" => $cancelId, "items_id" => $cr_val['items_id']])
                            ->update("purchase_order_cancel_detail", [
                                "cancel_reason" => $cr_val['cancel_reason'],
                                "cancel_reason_img" => json_encode($cr_val['images']),
                            ]);

                        // 只有 缺货/停产 时，需要生成屏蔽记录
                        $cs_id = $cr_val['cancel_reason'];
                        if(!in_array($cs_id, [1, 2]))continue;
                        $scree_apply = $cs_id == 1 ? 4: 10; // 原因替换 3 缺货 10 停产找货中,4才是缺货
                        $scree_add[] = [
                            "sku"           => $cr_val['sku'],
                            "apply_remark"  => $scree_apply,
                            "apply"         => $scree_apply,
                            "estimate"      => $cr_val['estimate_time'],
                            "imageurl"      => !is_array($cr_val['images']) ? [$cr_val['images']]: $cr_val['images'],
                            "chat_evidence" => !is_array($cr_val['images']) ? [$cr_val['images']]: $cr_val['images'],
                            "remark"        => '取消未到货生成。',
                            "apply_content" => '取消未到货生成。',
                        ];
                    }
                    if(count($scree_add) > 0)$this->create_product_scree($scree_add);
                }

            }else{
                //记录取消未到货操作日志
                $this->Reject_note_model->cancel_log([
                    'cancel_id'=>$cancel_id,
                    'operation_type'=>'申请取消未到货再次编辑申请',
                    'operation_content'=>(is_array($params['create_note']) ? implode(',', array_values($params['create_note'])): $params['create_note']),
                ]);
            }

            if(SetAndNotEmpty($params, 'cancel_id') || SetAndNotEmpty($params, 'type') || $send_wms) {//再编辑插入数据
                foreach ($purchase_number_order as $order_numberr) {
                    //变更采购单状态  为 作废订单待审核
                    $order_update = $this->purchase_order_model->change_status($order_numberr, PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT);
                    if (empty($order_update)) {
                        throw new Exception('采购单更新失败');
                    }
                    //记录操作日志
                    $this->Reject_note_model->get_insert_log([
                        'record_number'  => $order_numberr,
                        'record_type'    => 'PURCHASE_ORDER',
                        'content'        => '取消未到货',
                        'content_detail' => '采购单号' . $order_numberr . (is_array($params['create_note']) ? implode(',', array_values($params['create_note'])): $params['create_note']) . "状态为【作废订单待审核】"
                    ]);
                }
            }

            // 通知 wms 取消
            if($send_wms && $cancelId && count($suggest_list) > 0){
                $notice_wms = $this->notice_wms_pur_end($suggest_list);
                if($notice_wms['code'] == 0){
                    throw new Exception('取消失败，通知wms完结失败，原因：'.$notice_wms['msg']);
                }
            }

//            if ( $this->purchase_db->trans_status() === FALSE) {
//                //$this->purchase_db->trans_rollback();
//                return ['msg' => '操作失败', 'bool' => false];
//            } else {
//                //$this->purchase_db->trans_commit();
                $msg = $part_success ? '部分取消失败，'.$part_success:'操作成功';
                return ['msg' => $msg, 'bool' => true];
//            }
        } catch (Exception $exc) {
            //$this->purchase_db->trans_rollback();
            return ['msg' => $exc->getMessage(), 'bool' => false];
        }
    }

    /**
     * 通知wms采购单完结
     */
    public function notice_wms_pur_end($data, $c_id=0)
    {
        $res = ["code" => 0, "msg" => ""];
        $send_data = ["items" => $data];
        $send_data = json_encode($send_data);

        //请求java接口报损
        $url = getConfigItemByName('api_config', 'wms_system', 'receivePurchaseOrder'); //获取java取消未到货接口地址
        $access_taken = getOASystemAccessToken();
        $url = $url."?access_token=".$access_taken;
        $hrader = [
            'Content-Type: application/json',
            'w:SZ_AA',
            'org:org_00001'
        ];
        $result = getCancelCurlData($url, $send_data, 'post', $hrader);
        operatorLogInsert([
            'id' => 'notice_wms_pur_end',
            'type' => 'notice_wms_pur_end',
            'content' => '通知wms采购单完结',
            'detail' => "request:{$send_data}...response:{$result}...url:{$url}",
        ]);
        try{
            $result = json_decode($result, true);
            if(SetAndNotEmpty($result, 'code') && $result['code'] == 200){
                $res['code'] = 1;
                $res['msg'] = '通知成功！';
                $this->Reject_note_model->cancel_log([
                    'cancel_id'=>$c_id,
                    'operation_type'=>'通知wms完结采购单',
                    'operation_content'=> "通知wms完结采购单成功！",
                ]);
            }elseif(SetAndNotEmpty($result, 'msg')){
                $res['msg'] = $result['msg'];
            }
        }catch (Exception $e){
            $res['msg'] = $e->getMessage();
        }
        return $res;
    }

    /**
     * 更新sku预计供货时间，生成屏蔽审核数据，且推送到产品系统
     */
    private function create_product_scree($scree_add)
    {
        try{
            $skus = array_column( $scree_add,'sku');
            $scree_skus =  $this->product_scree_model->get_scree_skus_data($skus);
            if(!empty($scree_skus)) {
                $scree_skus = array_column($scree_skus,NULL,"sku");
                foreach($scree_add as $key=>$data ) {
                    if(isset($scree_skus[$data['sku']]))unset($scree_add[$key]);
                }
                $scree_skus = array_keys($scree_skus);
                if(empty($scree_add))throw new Exception("申请SKU:".implode(",",$scree_skus)."正在审核中");
            }

            $errors = $success =[];
            foreach($scree_add as $values) {
//                $result = $this->product_scree_model->set_scree_create([$values], $values['apply_remark'], $values['estimate'], $values['remark'], true, true);
                $result = $this->product_scree_model->scree_create($values);
                // 如果返回数组
                if(isset($result['code']) && $result['code'] === true){
                    $success[] = $values['sku'];
                }else{
                    $errors[] = $values['sku'];
                }

                /*
                if(is_array($result)) {
                    foreach($result as $errorSkus){
                        $errors[] = $errorSkus;
                    }
                }
                if (True === $result) {
                    $success[] =$result;
                }*/
            }
        }catch (Exception $e){
            echo $e->getMessage()."...Exception";
        }catch (Error $e){
            echo $e->getMessage()."...Error";
        }
    }

    /**
     * 重组提交数据
     * @param array $param
     * @return array
     */
    public function get_cancel_order_items($param=[])
    {
        $ids = explode(',', $param['ids']);
        $data = $this->purchase_db
            ->from("purchase_order_items as it")
            ->join("pur_purchase_order as o", "o.purchase_number=it.purchase_number", "inner")
            ->select("it.id,it.purchase_number,o.warehouse_code")
            ->where_in("it.id", $ids)
            ->get()->result_array();
        $res = [];
        $total_freight = [];
        $total_discount = [];
        $total_process_cost = [];
        if($data && count($data) > 0){
            foreach ($data as $val){
                $pur = $val['purchase_number'];
                $id = $val['id'];
                if(!isset($res[$pur])){
                    $res[$pur] = [
                        'total_cancelled'       => 0, //取消总数数量
                        'total_freight'         => 0, //取消的运费
                        'total_discount'        => 0, //取消的优惠额
                        'total_process_cost'    => 0, //取消的加工费
                        'total_price'           => 0, //取消的总金额
//                        'type'                  => 0, //再次编辑 1
                        'ids'                   => [],
                    ];
                }

                $c_qty = !empty($param['application_cancel_ctq'][$id]) ? $param['application_cancel_ctq'][$id]: 0;
                $freight = !empty($param['freight'][$id]) ? $param['freight'][$id]: 0;
                $discount = !empty($param['discount'][$id]) ? $param['discount'][$id]: 0;
                $process_cost = !empty($param['process_cost'][$id]) ? $param['process_cost'][$id]: 0;
                $one_price = !empty($param['purchase_unit_price'][$id]) ? $param['purchase_unit_price'][$id]: 0;
                $this_total_price = ($one_price * $c_qty);// + $freight + $process_cost - $process_cost;

                if($freight > 0 && !isset($total_freight[$pur]))$total_freight[$pur] = $freight;
                if($discount > 0 && !isset($total_discount[$pur]))$total_discount[$pur] = $discount;
                if($process_cost > 0 && !isset($total_process_cost[$pur]))$total_process_cost[$pur] = $process_cost;

                $res[$pur]['total_cancelled'] += $c_qty;
                $res[$pur]['total_freight'] = isset($total_freight[$pur])?$total_freight[$pur]:$freight;
                $res[$pur]['total_discount'] = isset($total_discount[$pur])?$total_discount[$pur]:$discount;
                $res[$pur]['total_process_cost'] = isset($total_process_cost[$pur])?$total_process_cost[$pur]:$process_cost;
                $res[$pur]['total_price'] += $this_total_price;

                $res[$pur]['cancel_ctq'][$id]               = !empty($param['cancel_ctq'][$id]) ? $param['cancel_ctq'][$id]: 0;
                $res[$pur]['freight'][$id]                  = $freight;
                $res[$pur]['discount'][$id]                 = $discount;
                $res[$pur]['process_cost'][$id]             = $process_cost;
                $res[$pur]['confirm_amount'][$id]           = !empty($param['confirm_amount'][$id]) ? $param['confirm_amount'][$id]: 0;
                $res[$pur]['purchase_unit_price'][$id]      = $one_price;
                $res[$pur]['instock_qty'][$id]              = !empty($param['instock_qty'][$id]) ? $param['instock_qty'][$id]: 0;
                $res[$pur]['loss_amount'][$id]              = !empty($param['loss_amount'][$id]) ? $param['loss_amount'][$id]: 0;
                $res[$pur]['create_note'][$id]              = !empty($param['create_note'][$id]) ? $param['create_note'][$id]: '';
                $res[$pur]['cancel_reason'][$id]            = !empty($param['cancel_reason'][$id]) ? $param['cancel_reason'][$id]: 0;
                $res[$pur]['estimate_time'][$id]            = !empty($param['estimate_time'][$id]) ? $param['estimate_time'][$id]: '';
                $res[$pur]['cancel_reason_img'][$id]        = !empty($param['cancel_reason_img'][$id]) ? $param['cancel_reason_img'][$id]: '';
                $res[$pur]['warehouse_code'][$id]           = $val['warehouse_code'];
                $res[$pur]['application_cancel_ctq'][$id]   = $c_qty;
                $res[$pur]['ids'][] = $id;
                $res[$pur]['is_plan_cancel'] = $param['is_plan_cancel'];
                $res[$pur]['plan_cancel_ids'] = $param['plan_cancel_ids'];
                $res[$pur]['image_list'] = $param['image_list'];
                $res[$pur]['is_submit'] = $param['is_submit'];
            }
        }
        return $res;
    }

    /**
     * 锁定取消单提交
     */
    public function lock_cancel_order($ids, $is_add = true)
    {
        $this->load->library('rediss');
        $lock_str = 'CANCEL_ORDER_LOCK_CACHE';
        $time_out = date("Y-m-d H:i:s"); // n 秒内禁止重复提交
        if($is_add){
            $this->rediss->addHashData($lock_str, $ids, $time_out);
        }else{
            $one = $this->rediss->getHashData($lock_str, $ids);
            try{
                $one = json_decode($one, true);
                if(!isset($one[0]) || empty($one[0])){
                    return false;
                }
                if((strtotime(date('Y-m-d H:i:s')) - strtotime($one[0])) < 60)return true;
            }catch (Exception $e){}catch (Error $e){}
            return false;
        }
    }

    /**
     * 验证订单状态及附加条件是否允许取消
     */
    public function check_order_status($purchase_number = false, $order = [])
    {
        $ret = false; // 默认允许取消
        if(!$purchase_number || count($order) == 0)return $ret;
        // 交易取消状态
        $cancel_t = $order["canceled"] + $order["canceling"];
        if($order["order_all"] == $cancel_t && $cancel_t > 0){
            if(!in_array($order["status"], ['交易成功', '交易取消'])){
                return $ret = "订单号:".$purchase_number." 状态不是“交易取消”或“交易成功”，不能全部取消。";
            }
            $refund_price = 0; // 退款金额
            $purchase_price = 0; // 商品金额
            // return $ret = "订单号:".$purchase_number." 状态不是“交易取消”状态，或者不是“交易成功，且退款成功和退款金额大于等于采购商品额”，不能全部取消。";
            if($order["status"] == '交易成功'){
                $refund = $this->purchase_db->from('purchase_order_pay_type')
                    ->select('product_money,apply_amount')
                    ->where('purchase_number = ', $purchase_number)
                    ->get()
                    ->row_array();
                if($refund && isset($refund['product_money']) && isset($refund['apply_amount'])){
                    $refund_price = (int)$refund['apply_amount'];
                    $purchase_price = (int)$refund['product_money'];
                }

                if($order['refund_status'] != 2 || $refund_price==0 || $purchase_price == 0 || $refund_price < $purchase_price){
                    return $ret = "订单号:".$purchase_number." 未退款成功，或退款金额小于采购商品额，不能全部取消。";
                }
            }
        }
        return $ret;
    }


   /**
    * 获取采购单号
    * @param $ids
    * @param string $type
    * @author harvin 2019-3-6 
    * @return array 
    */
   public function get_purchase_number_list(array $ids,$type){
       $item= $this->purchase_db
               ->select('*')
               ->where_in('id',$ids)
               ->get('purchase_order_items')
               ->result_array();
        return array_column(isset($item)?$item:[],$type,'id');
       
   }

    /**
     * 获取采购明细信息
     * @param $ids
     * @param string $type
     * @author harvin 2019-3-6
     * @return array
     */
    public function get_purchase_list(array $ids){
        $item= $this->purchase_db
            ->select('items.purchase_number,items.id,items.sku,items.demand_number,order.purchase_type_id,order.purchase_order_status,order.purchase_type_id')
            ->from('purchase_order_items items')
            ->join('purchase_order order','order.purchase_number=items.purchase_number','left')
            ->where_in('items.id',$ids)
            ->get()
            ->result_array();



        return !empty($item)?$item:[];

    }
    /**
     * 获取 供应商结算方式的名称
     * harvin
     * @author 2019-2-25
     * @param $settlement_code
     * @return null
     */
    protected function get_account_name($settlement_code) {
        if(empty($settlement_code)) return NULL;
        $res = $this->purchase_db
                ->select('settlement_name')
                ->where('settlement_code', $settlement_code)
                ->get('supplier_settlement')
                ->row_array();
        return isset($res['settlement_name'])?$res['settlement_name']:null;
    }

    /**
     * 获取 采购单明细
     * @author Jolon
     * @param $item_id
     * @return array
     */
    public function get_purchase_order_item($item_id){
        $orderinfo = $this->purchase_db
            ->from('purchase_order_items as oi')
            ->select('oi.*, po.pertain_wms,t.freight as t_freight,t.discount as t_discount, t.process_cost as t_process_cost,po.warehouse_code,po.purchase_type_id')
            ->join('purchase_order as po', 'oi.purchase_number=po.purchase_number')
            ->join('pur_purchase_order_pay_type as t', 'oi.purchase_number=t.purchase_number', "left")
            ->where('oi.id', $item_id)
            ->get()
            ->row_array();
        
        return !empty($orderinfo)?$orderinfo:[];
    }
    /**
     * 显示已取消未到货审核数据
     * @param string $id 
     * @author harvin 2019-1-11
     * @return mixed
     */
    public function get_cancel_unarrived_goods_examine_list($id) {
        $this->load->model('finance/Purchase_order_pay_type_model');
        //获取采购单明细表id
        $cancel_detail = $this->purchase_db
                ->select('items_id,cancel_reason')
                ->where('cancel_id', $id)
                ->get('purchase_order_cancel_detail')
                ->result_array();
        if (empty($cancel_detail)) {
            throw new Exception('参数id,不存在');
        }
        $items_id_list = array_column($cancel_detail, 'items_id');

        $reason_items_list = array_column($cancel_detail,'cancel_reason','items_id');



        if (empty($items_id_list)) {
            throw new Exception('参数items_id,不存在');
        }

        //获取供应商及编码
        $data = $this->get_supplier_name_code($items_id_list);

        $supplier = isset($data['supplier']) ? $data['supplier'] : [];
        $purchase_type_id = isset($data['purchase_type_id']) ? $data['purchase_type_id'] : '';
        $purchase_numbers = isset($data['purchase_number']) ? $data['purchase_number'] : [];
        if (empty($purchase_numbers)) {
            throw new Exception('采购单号,不存在');
        }

        $order_status = isset($data['purchase_order_status']) ? $data['purchase_order_status'] : [];
        if (empty($order_status)){
            throw new Exception('采购单号,不存在');
        }

        foreach ($order_status as $val){
            if (in_array($val['purchase_order_status'],[PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT])){
                throw new Exception('PO:'.$val['purchase_number'].'状态为“信息修改驳回”或者“信息修改待审核”的，不允许编辑');
            }
        }

        //取消明细
        $order_list = [];
        foreach ($purchase_numbers as $row_number) {
            $total_freight_discount = $this->get_total_freight_discount_true($row_number);//查询总运费和总优惠
            //获取拍单号信息
            $pay_pai_info = $this->Purchase_order_pay_type_model->get_pai_number($row_number);
            $order_list[] = [
                'purchase_number' => $row_number,
                'pai_number'      =>$pay_pai_info,
                'purchase_type_id' => empty($purchase_type_id)?'':getPurchaseType($purchase_type_id), //业务线
                'total_order_amount'=> isset($this->get_total_order($row_number)['total_order'])?$this->get_total_order($row_number)['total_order']:0,//订单金额
//                'total_freight'=> isset($this->get_total_freight_discount($row_number)['freight'])?$this->get_total_freight_discount($row_number)['freight']:0,//订单运费
//                'total_discount'=>isset($this->get_total_freight_discount($row_number)['discount'])?$this->get_total_freight_discount($row_number)['discount']:0,//订单优惠额
                'total_freight'=> isset($total_freight_discount['total_freight'])?$total_freight_discount['total_freight']:0,//订单运费
                'total_discount'=>isset($total_freight_discount['total_discount'])?$total_freight_discount['total_discount']:0,//订单优惠额
                'total_process_cost'=>isset($total_freight_discount['total_process_cost'])?$total_freight_discount['total_process_cost']:0,//订单加工费
                'total_confirm_amount'=>isset($this->get_total_order($row_number)['total_confirm_amount'])?$this->get_total_order($row_number)['total_confirm_amount']:0,//订单数量
                'order_info' => $this->get_order_info($row_number,0,[],$items_id_list,$reason_items_list),
            ];
        }

        if (empty($order_list)) {
            throw new Exception('获取取消未到货信息失败');
        }

        //去除没有选中取消未到货的sku
        $data_order_info = [];
        $order_info_list = array_column(isset($order_list) ? $order_list : [], 'order_info');

        if (empty($order_info_list)) {
            throw new Exception('获取取消未到货明细失败');
        }
        $data_order_info_list = [];
        foreach ($order_info_list as $row_data) {
            foreach ($row_data as $v) {
                $data_order_info_list[] = $v;
            }
        }
        $time_id_list = array_column(isset($data_order_info_list) ? $data_order_info_list : [], 'id');
        if (empty($time_id_list)) {
            throw new Exception('采购单明细id,不存在');
        }
        $cancel_ctq_list = $this->get_application_cancel_ctq($id, $time_id_list);
     /*   $freight_list = $this->get_freight_list_ctq($id, $time_id_list);
        $discount_list = $this->get_discount_list_ctq($id, $time_id_list);
        $process_cost_list = $this->get_process_cost_list_ctq($id, $time_id_list);*/
        foreach ($order_list as $key => $v_order) {
            $order_info_cens = isset($v_order['order_info']) ? $v_order['order_info'] : [];
            if (empty($order_info_cens)) {
                throw new Exception('采购单明细,不存在');
            }
            foreach ($order_info_cens as $ke => $vvv) {
                $order_list[$key]['order_info'][$ke]['application_cancel_ctq'] = isset($cancel_ctq_list[$id . '-' . $vvv['id']]['cancel_ctq']) ? $cancel_ctq_list[$id . '-' . $vvv['id']]['cancel_ctq'] : 0;
                $order_list[$key]['order_info'][$ke]['freight'] = isset($cancel_ctq_list[$id . '-' . $vvv['id']]['freight']) ? $cancel_ctq_list[$id . '-' . $vvv['id']]['freight'] : 0;
                $order_list[$key]['order_info'][$ke]['discount'] = isset($cancel_ctq_list[$id . '-' . $vvv['id']]['discount']) ? $cancel_ctq_list[$id . '-' . $vvv['id']]['discount'] : 0;
                $order_list[$key]['order_info'][$ke]['process_cost'] = isset($cancel_ctq_list[$id . '-' . $vvv['id']]['process_cost']) ? $cancel_ctq_list[$id . '-' . $vvv['id']]['process_cost'] : 0;
                $order_list[$key]['order_info'][$ke]['create_note'] = isset($cancel_ctq_list[$id . '-' . $vvv['id']]['create_note']) ? $cancel_ctq_list[$id . '-' . $vvv['id']]['create_note'] : '';

                //取消总金额
                $cancel_ctq=(isset($cancel_ctq_list[$id . '-' . $vvv['id']]['cancel_ctq']) ? $cancel_ctq_list[$id . '-' . $vvv['id']]['cancel_ctq'] : 0);
               /* $freight=isset($freight_list[$id . '-' . $vvv['id']]) ? $freight_list[$id . '-' . $vvv['id']] : 0;
                $discount=isset($discount_list[$id . '-' . $vvv['id']]) ? $discount_list[$id . '-' . $vvv['id']] : 0;
                $process_cost=isset($process_cost_list[$id . '-' . $vvv['id']]) ? $process_cost_list[$id . '-' . $vvv['id']] : 0;*/
                $cancellation_amount = $cancel_ctq * $vvv['purchase_unit_price'];
                $order_list[$key]['order_info'][$ke]['cancellation_amount'] = $cancellation_amount;

                $order_list[$key]['order_info'][$ke]['change_to_report_loss'] = $cancellation_amount+ $order_list[$key]['order_info'][$ke]['freight']+$order_list[$key]['order_info'][$ke]['process_cost'];//可以取消转报损的金额



            }
        }
        if (empty($order_list)) {
            throw new Exception('获取取消未到货信息失败');
        }
        //统计数量
        $total_amount = 0; //订单总金额
        $total_price = 0; //单价总额
        $total_purchases = 0; // 采购总数
        $total_warehousing = 0; //入库总数
        $total_loss_amount = 0; //报损总数
        $total_cancelled = 0; //已取消总数
        $total_application_cancel_ctq = 0; //已取消数量
        $total_freight = 0; //已取总运费
        $total_discount = 0; //已取总优惠额
        $total_process_cost = 0; //已取总加工费
        $total_cancellation_amount = 0; //取消总额
        $total_report_loss_amount  =0;//取消转报损总额
        foreach ($order_list as $key => $value) {
        
            $total_amount += $value['total_order_amount'];
            $cancel_cancellation_amount_pur = 0;//每个采购单的取消金额

            //取消运费取消金额取消优惠额以采购单维度
            foreach ($value['order_info'] as $v) {
                if (empty($v['is_show'])) {
                    continue;

                }
                $total_price += $v['purchase_unit_price'];
                $total_purchases += $v['confirm_amount'];
                $total_warehousing += $v['instock_qty'];
                $total_loss_amount += $v['loss_amount'];
                $total_cancelled += $v['cancel_ctq'];
                $total_application_cancel_ctq += $v['application_cancel_ctq'];
                $cancel_fright_pur = $v['freight'];
                $cancel_discount_pur = $v['discount'];
                $cancel_process_pur = $v['process_cost'];
                $cancel_cancellation_amount_pur+= $v['cancellation_amount'];//采购单取消商品金额

               /* $total_freight += $v['freight'];
                $total_discount += $v['discount'];
                $total_process_cost += $v['process_cost'];
                $total_cancellation_amount += $v['cancellation_amount'];*/
            }

            $order_list[$key]['cancel_fright_pur'] = $order_list[$key]['total_freight'];
            $order_list[$key]['cancel_discount_pur'] = $order_list[$key]['total_discount'];
            $order_list[$key]['cancel_process_pur'] = $order_list[$key]['total_process_cost'];
            $order_list[$key]['cancel_cancellation_amount_pur'] = $cancel_cancellation_amount_pur + $order_list[$key]['total_freight'] - $order_list[$key]['total_discount'] + $order_list[$key]['total_process_cost'];
            //采购单可以转报损金额
            $order_list[$key]['report_loss_amount_pur'] = $cancel_cancellation_amount_pur + $cancel_fright_pur +$cancel_process_pur;


            $total_freight+= $cancel_fright_pur;
            $total_discount+= $cancel_discount_pur;
            $total_process_cost+=$cancel_process_pur;
            $total_cancellation_amount += $order_list[$key]['cancel_cancellation_amount_pur'];
            $total_report_loss_amount += $order_list[$key]['report_loss_amount_pur'];



        }
        $data_total=[];
        $data_total['total_amount'] = format_price($total_amount);
        $data_total['total_price'] = $total_price;
        $data_total['total_purchases'] = $total_purchases;
        $data_total['total_warehousing'] = $total_warehousing;
        $data_total['total_loss_amount'] = $total_loss_amount;
        $data_total['total_cancelled'] = $total_cancelled;
        $data_total['total_application_cancel_ctq'] = $total_application_cancel_ctq;
        $data_total['total_freight'] = format_price($total_freight);
        $data_total['total_discount'] = format_price($total_discount);
        $data_total['total_process_cost'] = format_price($total_process_cost);
        $data_total['total_cancellation_amount'] = format_price($total_cancellation_amount);
        $data_total['total_report_loss_amount'] = format_price($total_report_loss_amount);

        //获取取消未到货主表信息
        $cancelorder = $this->purchase_db->
                select('cancel_number,create_note,cancel_url,audit_note,cancel_image_list')
                ->where('id', $id)
                ->get('purchase_order_cancel')
                ->row_array();
        if (empty($cancelorder)) {
            throw new Exception('参数id,不存在');
        }
        $data_total['cancel_number'] = isset($cancelorder['cancel_number']) ? $cancelorder['cancel_number'] : '';
        $data_total['create_note'] = isset($cancelorder['create_note']) ? $cancelorder['create_note'] : '';
        $data_total['cancel_url'] = json_decode(isset($cancelorder['cancel_url']) ? $cancelorder['cancel_url'] : [], TRUE);
        $data_total['audit_note'] = isset($cancelorder['audit_note']) ? $cancelorder['audit_note'] : '';
        $data_total['image_list'] = isset($cancelorder['cancel_image_list']) ? json_decode($cancelorder['cancel_image_list'], true) : [];

        $data_list = [
            'supplier' => $supplier,
            'order_list' => $order_list,
            'data_total'=>$data_total
        ];
        return $data_list;
    }
    /**
     * 获取申请中优惠额
     * @param string $id
     * @param array  $time_id_list
     * @return array
     */
    public function get_discount_list_ctq($id,$time_id_list){
        die("作废");
//      $data=[];
//    $order_cancel_detail=$this->purchase_db
//              ->select('discount,cancel_id,items_id')
//              ->where('cancel_id',$id)
//              ->where_in('items_id',$time_id_list)
//              ->get('purchase_order_cancel_detail')
//              ->result_array();
//      if(empty($order_cancel_detail)) return [];
//      foreach ($order_cancel_detail as $key => $value) {
//             $data[$value['cancel_id'].'-'.$value['items_id']]=$value['discount'];
//      }
//      return $data;
    }
    
   /**
    * 获取申请中取消数量
    * @param string $id
    * @param array  $time_id_list
    * @return array
    */
  public function get_application_cancel_ctq($id,$time_id_list){
      $data=[];
    $order_cancel_detail=$this->purchase_db
              ->select('cancel_ctq,cancel_id,items_id,freight,discount,process_cost,create_note')
              ->where('cancel_id',$id)
              ->where_in('items_id',$time_id_list)
              ->get('purchase_order_cancel_detail')
              ->result_array();
      if(empty($order_cancel_detail)) return [];
      foreach ($order_cancel_detail as $key => $value) {
             $data[$value['cancel_id'].'-'.$value['items_id']]=$value;
      }
      return $data;
  }
    /**
    * 获取计算后运费
    * @param string $id
    * @param array  $time_id_list
    * @return array
    */
    public function get_freight_list_ctq($id,$time_id_list){
        die("作废");
//          $data=[];
//    $order_cancel_detail=$this->purchase_db
//              ->select('freight,cancel_id,items_id')
//              ->where('cancel_id',$id)
//              ->where_in('items_id',$time_id_list)
//              ->get('purchase_order_cancel_detail')
//              ->result_array();
//      if(empty($order_cancel_detail)) return [];
//      foreach ($order_cancel_detail as $key => $value) {
//             $data[$value['cancel_id'].'-'.$value['items_id']]=$value['freight'];
//      }
//      return $data;
    }

    /**
     * 获取计算后加工费
     * @param string $id
     * @param array  $time_id_list
     * @return array
     */
    public function get_process_cost_list_ctq($id,$time_id_list){
        die("作废");
//        $data=[];
//        $order_cancel_detail=$this->purchase_db
//            ->select('process_cost,cancel_id,items_id')
//            ->where('cancel_id',$id)
//            ->where_in('items_id',$time_id_list)
//            ->get('purchase_order_cancel_detail')
//            ->result_array();
//        if(empty($order_cancel_detail)) return [];
//        foreach ($order_cancel_detail as $key => $value) {
//            $data[$value['cancel_id'].'-'.$value['items_id']]=$value['process_cost'];
//        }
//        return $data;
    }
    /**
     * 取消未到货审核操作
     * @author harvin 2019-1-11
     * @param string $id 参数id
     * @param string $audit_note      审核备注
     * @param int    $type            1 审核通过  2.审核驳回
     * @param bool $is_system       是否系统自动
     * @param array $params       取消全部参数
     * @throws Exception
     * @return mixed|bool
     */
    public function cancel_unarrived_goods_examine_save($id, $audit_note, $type, $is_system=false, $params=[]) {
        $query_builder = $this->purchase_db;
        $this->load->model('Reject_note_model');
        $this->load->model('statement/Charge_against_records_model');
        $this->load->model('finance/Purchase_order_pay_model');
        try {
            //开始事物
            $query_builder->trans_begin();
            //判断审核状态
             $cancel_status = $query_builder
                    ->select('audit_status')
                    ->where('id', $id)
                    ->get('purchase_order_cancel')
                    ->row_array();
            if (empty($cancel_status)) {
                throw new Exception('未找到要审核单');
            }
            if(!$is_system && $cancel_status['audit_status']!=CANCEL_AUDIT_STATUS_CG){
                 throw new Exception('该取消未到货有审核过,请勿重复审核');
            }
            
            //获取采购单明细表id
            $cancel_detail = $query_builder
                    ->select('items_id,purchase_number')
                    ->where('cancel_id', $id)
                    ->get('purchase_order_cancel_detail')
                    ->result_array();
            if (empty($cancel_detail)) {
                throw new Exception('参数id,不存在');
            }
              //获取采购单明细表id
            $order_cancel = $query_builder
                    ->select('create_user_name,create_time,create_user_id')
                    ->where('id', $id)
                    ->get('purchase_order_cancel')
                    ->row_array();
            if(empty($order_cancel)){
                 throw new Exception('id,不存在');
            }
            
            $purchase_number_list = array_column($cancel_detail, 'purchase_number');
            $purchase_number_list = array_unique($purchase_number_list); //去重复值
            if (empty($purchase_number_list)) {
                throw new Exception('参数purchase_number,不存在');
            }
            if ($type == 1) {

                $order_source = $this->Purchase_order_unarrived_model->get_order_source($purchase_number_list[0]); //获取采购单来源  1合同 2网络
                $source = isset($order_source['source']) ? $order_source['source'] : 0;

                if (true or $source == SOURCE_NETWORK_ORDER) {//网采单（对账单功能调整为 合同单逻辑同网采单）

                    foreach ($purchase_number_list as $val) {
                        $order_amount = [];
                        $refund_pay_price = $refund_pay_product_money = $refund_pay_freight = $refund_pay_discount = $refund_pay_process_cost = null;

                        //判断采购是否是 已付款或部分付款
                        $pay_status = $this->Purchase_order_unarrived_model->get_order_pay_status($val);
                        //判断采购单状态
                        $order_status = $this->Purchase_order_unarrived_model->get_order_status($val);

                        //获取该采购单取消数量 及采购数量 入库数量  报损数量  获取取消金额
                        $order_cancel_ctq     = $this->Purchase_order_unarrived_model->get_order_cancel_ctq($id, $val); //历史取消数量
                        $order_cancel_ctq_ben = $this->Purchase_order_unarrived_model->get_order_cancel_ctq_ben($id, $val); //本次取消数量
                        $confirm_amount       = $this->Purchase_order_unarrived_model->get_confirm_amount($val); //采购数量
                        $instock_qty          = $this->Purchase_order_unarrived_model->get_instock_qty($val); //入库数量

                        $cancelinstock_qtyloss_amount = $order_cancel_ctq + $instock_qty + $order_cancel_ctq_ben; //历史消数量+入库数量+本次取消数量
                        $order_source                 = $this->Purchase_order_unarrived_model->get_order_source($val); //获取菜单单来源  1合同 2网络
                        $source                       = isset($order_source['source']) ? $order_source['source'] : 0;


                        //历史取消金额
                        $cancel_amount_old               = $this->Purchase_order_unarrived_model->get_cancel_amount($id, $val);
                        $cancel_amount_old_product_money = isset($cancel_amount_old['product_money'])?$cancel_amount_old['product_money']:0;
                        $cancel_amount_old_freight       = isset($cancel_amount_old['freight'])?$cancel_amount_old['freight']:0;
                        $cancel_amount_old_discount      = isset($cancel_amount_old['discount'])?$cancel_amount_old['discount']:0;
                        $cancel_amount_old_process_cost  = isset($cancel_amount_old['process_cost'])?$cancel_amount_old['process_cost']:0;
                        $cancel_amount_old_real_price    = isset($cancel_amount_old['real_price'])?$cancel_amount_old['real_price']:0;


                        // 采购单实际总金额
                        $order_amount              = $this->get_total_order($val); //该订单金额
                        $order_real_amount         = isset($order_amount['total_real_price']) ? $order_amount['total_real_price'] : 0;
                        $order_total_product_money = isset($order_amount['total_order']) ? $order_amount['total_order'] : 0;
                        $order_total_freight       = isset($order_amount['total_freight']) ? $order_amount['total_freight'] : 0;
                        $order_total_discount      = isset($order_amount['total_discount']) ? $order_amount['total_discount'] : 0;
                        $order_total_process_cost  = isset($order_amount['total_process_cost']) ? $order_amount['total_process_cost'] : 0;
                        $order_total_commission    = isset($order_amount['total_commission']) ? $order_amount['total_commission'] : 0;

                        // 本次取消金额
                        $cancel_amount_ben_list          = $this->Purchase_order_unarrived_model->get_cancel_amount_ben($id, $val); //本次取消金额
                        $cancel_amount_ben_product_money = $cancel_amount_ben_list['product_money'];
                        $cancel_amount_ben_freight       = $cancel_amount_ben_list['freight'];
                        $cancel_amount_ben_discount      = $cancel_amount_ben_list['discount'];
                        $cancel_amount_ben_process_cost  = $cancel_amount_ben_list['process_cost'];
                        $cancel_amount_ben_real_price    = $cancel_amount_ben_list['real_price'];


                        if ($source == SOURCE_COMPACT_ORDER){// 合同单本次取消 应退款金额
                            $charge_against_record = $this->Charge_against_records_model->get_ca_audit_status($val,2,[CHARGE_AGAINST_STATUE_WAITING_AUDIT,CHARGE_AGAINST_STATUE_WAITING_FINANCE_AUDIT]);
                            if ($charge_against_record){
                                throw new Exception('采购单:'.$val.' 存在退款冲销中的记录');
                            }
                            // 采购单维度：退款冲销金额
                            $charge_against_price = $this->Charge_against_records_model->get_ca_total_price_list($val,2,[CHARGE_AGAINST_STATUE_WAITING_PASS]);

                            // 采购单维度：已付金额明细
                            $payed_total        = $this->Purchase_order_pay_model->get_pay_total_by_purchase_number($val);
                            $payed_total        = arrayKeyToColumn($payed_total, 'purchase_number');
                            $payed_total        = isset($payed_total[$val]) ? $payed_total[$val] : [];
                            $paid_product_money = isset($payed_total['paid_product_money']) ? $payed_total['paid_product_money'] : 0;
                            $paid_freight       = isset($payed_total['paid_freight']) ? $payed_total['paid_freight'] : 0;
                            $paid_discount      = isset($payed_total['paid_discount']) ? $payed_total['paid_discount'] : 0;
                            $paid_process_cost  = isset($payed_total['paid_process_cost']) ? $payed_total['paid_process_cost'] : 0;
                            $paid_real_price    = isset($payed_total['paid_real_price']) ? $payed_total['paid_real_price'] : 0;

                            // 采购单维度：尾款总金额 = 合同总金额 - 取消金额 - 已付款金额 - 已退款冲销金额
                            // 采购单维度：尾款商品额、运费、优惠额、加工费、尾款总金额
                            $tail_product_money = format_price($order_total_product_money - $cancel_amount_old_product_money - $paid_product_money - $charge_against_price['charge_against_product']);
                            $tail_freight       = format_price($order_total_freight - $cancel_amount_old_freight - $paid_freight - $charge_against_price['charge_against_freight']);
                            $tail_discount      = format_price($order_total_discount - $cancel_amount_old_discount - $paid_discount - $charge_against_price['charge_against_discount']);
                            $tail_process_cost  = format_price($order_total_process_cost - $cancel_amount_old_process_cost - $paid_process_cost - $charge_against_price['charge_against_process_cost']);
                            $tail_real_money    = format_price($order_real_amount - $cancel_amount_old_real_price - $paid_real_price - $charge_against_price['charge_against_amount']);

                            if($cancel_amount_ben_product_money > 0 and bccomp($cancel_amount_ben_product_money,$tail_product_money,3) > 0 and $cancel_amount_ben_real_price > 0 and $cancel_amount_ben_real_price > $tail_real_money){// 本次应退款金额 = 本次取消金额 - 尾款金额
                                // 尾款小于0表示 尾款以扣完，取消多少就需要退多少
                                $refund_pay_price         = format_price($cancel_amount_ben_real_price - (($tail_real_money > 0)?$tail_real_money:0));// PO维度：实际应退款金额 = 本次取消实际总额 - 尾款实际总额
                                // $refund_pay_product_money = format_price($cancel_amount_ben_product_money - $tail_product_money);
                                $refund_pay_freight       = format_price($cancel_amount_ben_freight - ($tail_freight > 0?$tail_freight:0));
                                $refund_pay_discount      = format_price($cancel_amount_ben_discount - ($tail_discount>0?$tail_discount:0));
                                $refund_pay_process_cost  = format_price($cancel_amount_ben_process_cost - ($tail_process_cost>0?$tail_process_cost:0));

                                $refund_pay_product_money = $refund_pay_price;// 根据退款总金额 倒推 应退款商品金额（退款商品金额 = 退款总金额 - 退款运费 - 退款加工费 + 退款优惠额）
                                if($refund_pay_freight > 0){// 应退款运费
                                    $refund_pay_product_money -= $refund_pay_freight;
                                }else{
                                    $refund_pay_freight = 0;
                                }
                                if($refund_pay_discount > 0){// 应退款优惠额
                                    $refund_pay_product_money += $refund_pay_discount;
                                }else{
                                    $refund_pay_discount = 0;
                                }
                                if($refund_pay_process_cost > 0){// 应退款加工费
                                    $refund_pay_product_money -= $refund_pay_process_cost;
                                }else{
                                    $refund_pay_process_cost = 0;
                                }
                            }
                        }else{// 网采单（如果付款则付的是 剩余可请款金额） 则本次退款金额 = 本次取消金额
                            // 本次应退款金额
                            $refund_pay_product_money = $cancel_amount_ben_product_money;
                            $refund_pay_freight       = $cancel_amount_ben_freight;
                            $refund_pay_discount      = $cancel_amount_ben_discount;
                            $refund_pay_process_cost  = $cancel_amount_ben_process_cost;
                            $refund_pay_price         = $cancel_amount_ben_real_price;
                        }

                        if (($source == SOURCE_NETWORK_ORDER && in_array($pay_status, [PART_PAID, PAY_PAID]))
                            || ($source == SOURCE_COMPACT_ORDER && isset($refund_pay_price) && $refund_pay_price>0)) {// 已付付款订单
                            //判断该采购单是否是网采单还是合同单
                            if (true) {
                                //生成收款单
                                $order_receipt  = [
                                    'cancel_id'         => $id,
                                    'purchase_number'   => $val, //采购单号
                                    'supplier_code'     => isset($order_source['supplier_code']) ? $order_source['supplier_code'] : '', //供应商代码
                                    'supplier_name'     => isset($order_source['supplier_name']) ? $order_source['supplier_name'] : '', //供应商名称
                                    'is_cross_border'   => isset($order_source['is_cross_border']) ? $order_source['is_cross_border'] : '', //是否是跨境宝(默认0.否,1.跨境宝)
                                    'settlement_method' => isset($order_source['account_type']) ? $order_source['account_type'] : '', //结算方式
                                    'pay_type'          => isset($order_source['pay_type']) ? $order_source['pay_type'] : '', //支付方式（从供应商拉取）
                                    'pay_name'          => '供应商退款', //退款名称

                                    'original_pay_product_money' => $refund_pay_product_money,//原始退款商品额
                                    'original_pay_freight'       => $refund_pay_freight,//原始退款运费
                                    'original_pay_discount'      => $refund_pay_discount,//原始退款优惠额
                                    'original_pay_process_cost'  => $refund_pay_process_cost,//原始退款加工费
                                    'original_pay_price'         => $refund_pay_price,//原始实际退款金额（冲销前的金额）

                                    'pay_price'         => $refund_pay_price, //金额(实际退款金额)
                                    'cancel_ctq'        => $order_cancel_ctq_ben, //取消数量
                                    'application_id'    => $order_cancel['create_user_id'], //申请人ID
                                    'apply_user_name'   => $order_cancel['create_user_name'], //申请人名
                                    'apply_time'        => $order_cancel['create_time'], //申请时间
                                    'apply_notice'      => '取消未到货退款', //申请备注
                                    'audit_id'          => $is_system ? 0 : getActiveUserId(), //审核人id
                                    'audit_user_name'   => $is_system ? "system" : getActiveUserName(), //审核人名
                                    'audit_time'        => date('Y-m-d H:i:s'), //审核时间
                                    'audit_notice'      => '',//审核备注
                                ];
                                $receipt_insert = $this->purchase_db->insert('purchase_order_cancel_to_receipt', $order_receipt);
                                if (empty($receipt_insert)) {
                                    throw new Exception('记录收款表失败');
                                }
                                //变更取消未到货主表状态
                                $cancel_update = $this->Purchase_order_unarrived_model->change_order_cancel(CANCEL_AUDIT_STATUS_SCJT, $id, $audit_note);
                                if (empty($cancel_update)) {
                                    throw new Exception('采购单-取消未到货数量-主表更新失败');
                                }
                                //订单状态变为 '作废订单待退款'
                                $order_update = $this->purchase_order_model->change_status($val);
                                if (empty($order_update)) {
                                    throw new Exception('采购单更新失败');
                                }
                            }
                        } else {  //未付款订单  直接走系统审核
                            if($source == SOURCE_COMPACT_ORDER && SetAndNotEmpty($params, 'status')){
                                $order_status = $params['status'];
                            }
                            $this->Purchase_order_unarrived_model->canceled_status($order_status, $cancelinstock_qtyloss_amount, $val, $confirm_amount, $id, $instock_qty, $is_system);
                        }
                    }
                }
                else
                    {
                    $this->load->model('compact/Compact_items_model');
                    $this->load->model('compact/Compact_model');
                    $this->load->model('finance/Payment_order_pay_model');
                    //获取采购单的合同号
                    $compact_number = $this->Compact_items_model->get_compact_by_purchase($purchase_number_list[0]);

                    //判断采购是否是 已付款或部分付款
                    $pay_status = $this->Purchase_order_unarrived_model->get_order_pay_status($purchase_number_list[0]);
                    //合同单 - 请款相关金额
                    $compact_related_data = $this->Compact_model->calculate_compact_related_amount($compact_number['compact_number']);
                    $compact_related_data = $compact_related_data['data'];

                    $cancel_amount_ben  = $this->Purchase_order_unarrived_model->get_cancel_amount_ben_by_id($id); //本次取消总金额
                    $sum_total    = $compact_related_data['product_money'] + $compact_related_data['freight'] - $compact_related_data['discount'] + $compact_related_data['process_cost'];
                    $cancel_total = $compact_related_data['cancel_total_product_money'] + $compact_related_data['cancel_total_freight'] - $compact_related_data['cancel_total_discount'] + $compact_related_data['cancel_total_process_cost'];
                    $payed_total  = $compact_related_data['paid_total_product_money'] + $compact_related_data['paid_total_freight'] - $compact_related_data['paid_total_discount'] + $compact_related_data['paid_total_process_cost'];

                    $tail_money = $sum_total - $cancel_total - $payed_total;// 尾款金额 = 合同总金额 - 取消金额 - 已付款金额

                    //取消金额总金额 小于尾款
                    $cancel_amount_cancel_amount_ben = $cancel_amount_ben; //取消总金额（本次）
                    $cancel_ben_total = $this->Purchase_order_unarrived_model->get_cancel_cqt_ben_by_id($id);//本次取消总数量

                    //判断合同单是否是 部分付款 还是全付款
                    if ($pay_status == PART_PAID and $cancel_amount_cancel_amount_ben > $tail_money) { //已部分付款 且 取消金额 大于尾款金额 >>> 生成收款单
                        foreach ($purchase_number_list as $val){
                            $order_cancel_ctq_ben = $this->Purchase_order_unarrived_model->get_order_cancel_ctq_ben($id, $val); //本次取消数量

                            //流程调整,先将记录存在预推送表中,待上传截图后,将记录插入待收款表
                            //生成收款单预推送数据
                            $order_receipt  = [
                                'cancel_id'         => $id,
                                'purchase_number'   => $val, //采购单号
                                'supplier_code'     => isset($order_source['supplier_code']) ? $order_source['supplier_code'] : '', //供应商代码
                                'supplier_name'     => isset($order_source['supplier_name']) ? $order_source['supplier_name'] : '', //供应商名称
                                'is_cross_border'   => isset($order_source['is_cross_border']) ? $order_source['is_cross_border'] : '', //是否是跨境宝(默认0.否,1.跨境宝)
                                'settlement_method' => isset($order_source['account_type']) ? $order_source['account_type'] : '', //结算方式
                                'pay_type'          => isset($order_source['pay_type']) ? $order_source['pay_type'] : '', //支付方式（从供应商拉取）
                                'pay_name'          => '供应商退款', //退款名称
                                'cancel_ctq'        => $order_cancel_ctq_ben, //取消数量
                                'application_id'    => $order_cancel['create_user_id'], //申请人ID
                                'apply_user_name'   => $order_cancel['create_user_name'], //申请人名
                                'apply_time'        => $order_cancel['create_time'], //申请时间
                                'apply_notice'      => '取消未到货退款', //申请备注
                                'audit_id'          => $is_system ? 0:getActiveUserId(), //审核人id
                                'audit_user_name'   => $is_system ? "system":getActiveUserName(), //审核人名
                                'audit_time'        => date('Y-m-d H:i:s'), //审核时间
                                'audit_notice'      => '',//审核备注
                            ];
                            if($tail_money<0){
                                $pay_price = sprintf("%.2f",($cancel_amount_cancel_amount_ben)/$cancel_ben_total*$order_cancel_ctq_ben);//尾款小于0,退款金额等于申请取消总金额
                            }else{
                                $pay_price = sprintf("%.2f",($cancel_amount_cancel_amount_ben - $tail_money)/$cancel_ben_total*$order_cancel_ctq_ben); //金额(实际退款金额) 生成退款单的金额=申请的取消金额-合同单总尾款金额
                            }

                            $order_receipt['pay_price'] = $pay_price;

                            $receipt_insert = $this->purchase_db->insert('purchase_order_cancel_to_receipt', $order_receipt);
                            if (empty($receipt_insert)) {
                                throw new Exception('记录流向收款表数据失败');
                            }
                        }


                        //变更取消未到货主表状态
                        $cancel_update = $this->Purchase_order_unarrived_model->change_order_cancel(CANCEL_AUDIT_STATUS_SCJT, $id, $audit_note);
                        if (empty($cancel_update)) {
                            throw new Exception('采购单-取消未到货数量-主表更新失败');
                        }
                        //订单状态变为 '作废订单待退款'
                        $order_update = $this->purchase_order_model->change_status($val);
                        if (empty($order_update)) {
                            throw new Exception('采购单更新失败');
                        }
                    } elseif ($pay_status == PAY_PAID) {  //合同单已付全款
                        foreach ($purchase_number_list as $val) {
                            $order_cancel_ctq_ben = $this->Purchase_order_unarrived_model->get_order_cancel_ctq_ben($id, $val); //本次取消数量

                            //流程调整,先将记录存在预推送表中,待上传截图后,将记录插入待收款表
                            //生成收款单预推送数据
                            $order_receipt  = [
                                'cancel_id'         => $id,
                                'purchase_number'   => $val, //采购单号
                                'supplier_code'     => isset($order_source['supplier_code']) ? $order_source['supplier_code'] : '', //供应商代码
                                'supplier_name'     => isset($order_source['supplier_name']) ? $order_source['supplier_name'] : '', //供应商名称
                                'is_cross_border'   => isset($order_source['is_cross_border']) ? $order_source['is_cross_border'] : '', //是否是跨境宝(默认0.否,1.跨境宝)
                                'settlement_method' => isset($order_source['account_type']) ? $order_source['account_type'] : '', //结算方式
                                'pay_type'          => isset($order_source['pay_type']) ? $order_source['pay_type'] : '', //支付方式（从供应商拉取）
                                'pay_name'          => '供应商退款', //退款名称
                                'cancel_ctq'        => $order_cancel_ctq_ben, //取消数量
                                'application_id'    => $order_cancel['create_user_id'], //申请人ID
                                'apply_user_name'   => $order_cancel['create_user_name'], //申请人名
                                'apply_time'        => $order_cancel['create_time'], //申请时间
                                'apply_notice'      => '取消未到货退款', //申请备注
                                'audit_id'          => $is_system ? 0:getActiveUserId(), //审核人id
                                'audit_user_name'   => $is_system ? "system":getActiveUserName(), //审核人名
                                'audit_time'        => date('Y-m-d H:i:s'), //审核时间
                                'audit_notice'      => '',//审核备注
                            ];

                            if($tail_money<0){
                                $pay_price = sprintf("%.2f",($cancel_amount_cancel_amount_ben)/$cancel_ben_total*$order_cancel_ctq_ben);//尾款小于0,退款金额等于申请取消总金额
                            }else{
                                $pay_price = sprintf("%.2f",($cancel_amount_cancel_amount_ben - $tail_money)/$cancel_ben_total*$order_cancel_ctq_ben); //金额(实际退款金额) 生成退款单的金额=申请的取消金额-合同单总尾款金额
                            }

                            $order_receipt['pay_price'] = $pay_price;

                            $receipt_insert = $this->purchase_db->insert('purchase_order_cancel_to_receipt', $order_receipt);
                            if (empty($receipt_insert)) {
                                throw new Exception('记录流向收款表数据失败');
                            }
                            //变更取消未到货主表状态
                            $cancel_update = $this->Purchase_order_unarrived_model->change_order_cancel(CANCEL_AUDIT_STATUS_SCJT, $id, $audit_note);
                            if (empty($cancel_update)) {
                                throw new Exception('采购单-取消未到货数量-主表更新失败');
                            }
                            //订单状态变为 '作废订单待退款'
                            $order_update = $this->purchase_order_model->change_status($val);
                            if (empty($order_update)) {
                                throw new Exception('采购单更新失败');
                            }
                        }
                    } else {  //未付款订单  直接走系统审核
                        foreach ($purchase_number_list as $val){
                            //获取该采购单取消数量 及采购数量 入库数量  报损数量  获取取消金额
                            $order_cancel_ctq     = $this->Purchase_order_unarrived_model->get_order_cancel_ctq($id, $val); //历史取消数量
                            $order_cancel_ctq_ben = $this->Purchase_order_unarrived_model->get_order_cancel_ctq_ben($id, $val); //本次取消数量
                            $confirm_amount       = $this->Purchase_order_unarrived_model->get_confirm_amount($val); //采购数量
                            $instock_qty          = $this->Purchase_order_unarrived_model->get_instock_qty($val); //入库数量
                            $cancelinstock_qtyloss_amount = $order_cancel_ctq + $instock_qty + $order_cancel_ctq_ben; //历史消数量+入库数量+本次取消数量
                            //判断采购单状态
                            $order_status = $this->Purchase_order_unarrived_model->get_order_status($val);
                            $this->Purchase_order_unarrived_model->canceled_status($order_status, $cancelinstock_qtyloss_amount, $val, $confirm_amount, $id, $instock_qty);
                        }
                    }
                }
                //判断收款单是否有取消未到货生成的订单  如果有 取消未到货主表状态 是'财务收款' 
                $receipt=$this->purchase_db->select('cancel_id')->where('cancel_id',$id)->get('purchase_order_cancel_to_receipt')->row_array();
                if(!empty($receipt)){
                       $cancel_update = $this->Purchase_order_unarrived_model->change_order_cancel(CANCEL_AUDIT_STATUS_SCJT, $id,$audit_note);
                        if (empty($cancel_update)) {
                            throw new Exception('采购单-取消未到货数量-主表更新失败');
                        }
                }
                 //记录取消未到货操作日志
               $this->Reject_note_model->cancel_log([
                'cancel_id'=>$id,
                'operation_type'=>'采购经理审核通过',
                'operation_content'=>$audit_note,
               ]); 
               $data_temp = [
                    'audit_note' => $audit_note, //审核备注
                ];
                $query_builder->where('id', $id)->update('purchase_order_cancel', $data_temp);

                if ($query_builder->trans_status() === FALSE) {
                    $query_builder->trans_rollback();
                    return ['msg' => '操作失败', 'bool' => FALSE];
                } else {
                    $query_builder->trans_commit();
                    $recal_uninvoiced_qty_key = 'recal_uninvoiced_qty';//取消,审核通过,重新计算未开票数量
                    $sku = '';//(采购单维度);
                    foreach ($purchase_number_list as $purchase_number) {
                        $this->rediss->set_sadd($recal_uninvoiced_qty_key, sprintf('%s$$%s', $purchase_number, $sku));
                    }
                    $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$recal_uninvoiced_qty_key);

                    return ['msg' => '操作成功', 'bool' => TRUE];
                }

            }
            elseif ($type == 2) {
                $data_temp = [
                    'audit_status' => CANCEL_AUDIT_STATUS_CGBH, //采购驳回
                    'audit_note' => $audit_note, //审核备注
                    'audit_user_name' => $is_system ? "system" : getActiveUserName(), //审核人
                    'audit_time' => date('Y-m-d H:i:s'), //审核时间
                    'is_edit'=>2
                ];
                $query_builder->where('id', $id)->update('purchase_order_cancel', $data_temp);
                  //驳回推送仓库
            //   $this->Purchase_order_unarrived_model->preservation_warehouse_reject($id);
                foreach ($purchase_number_list as $purchase_number) {
                      //获取采购单 原来的状态
                    $order_cancel_detail= $this->purchase_db
                            ->select('purchase_order_status')
                            ->where('cancel_id',$id)
                            ->where('purchase_number',$purchase_number)
                            ->get('purchase_order_cancel_detail')
                            ->row_array();
                    if(empty($order_cancel_detail)){
                        throw new Exception('参数purchase_number,不存在');
                    }

                     //订单状态变为 '退回原来的状态'
                    $order_update = $this->purchase_order_model->change_status($purchase_number);
                    if (empty($order_update)) {
                        throw new Exception('采购单更新失败');
                    }
                    //记录操作日志
                    $log = [
                        'record_number' => $purchase_number,
                        'record_type' => 'PURCHASE_ORDER',
                        'content' => $audit_note,
                        'content_detail' => '采购单号' . $purchase_number . "取消未到货驳回" . $audit_note,
                    ];
                    $this->Reject_note_model->get_insert_log($log);



                }
                $this->Message_model->AcceptMessage('determine',['data'=>[$id],'message'=>$audit_note,'user'=>getActiveUserName(),'type'=>'待采购经理']);
                 //记录取消未到货操作日志
               $this->Reject_note_model->cancel_log([
                'cancel_id'=>$id,
                'operation_type'=>'采购经理驳回',
                'operation_content'=>$audit_note,
               ]); 
                if ($query_builder->trans_status() === FALSE) {
                    $query_builder->trans_rollback();
                    return ['msg' => '提交失败', 'bool' => FALSE];
                } else {
                    $query_builder->trans_commit();
                    return ['msg' => '操作成功', 'bool' => TRUE];
                }
            } else {
                throw new Exception("type 非法");
            }
        } catch (Exception $exc) {
            $query_builder->trans_rollback();
            return ['msg' => $exc->getMessage(), 'bool' => FALSE];
        }
    }

    
    
    /**
     * 保存流水截图
     * @param  $cancel_url 
     * @param $id 
     * @author harvin
     * @return  bool
     */
    
   public function  cancel_upload_screenshots_save($cancel_url,$id='',$serial_number,$upload_note=''){
       $this->load->model('Reject_note_model');
       $cancel_url= explode(',', $cancel_url);
       if(empty($cancel_url)){
           throw new Exception('参数cancel_url格式不对');
       }
        $cancel_url= json_encode($cancel_url);
        //保存图片
        $temp= $this->purchase_db->where('id',$id)
                ->update('purchase_order_cancel',['cancel_url'=>$cancel_url,'audit_status'=>CANCEL_AUDIT_STATUS_CF,'serial_number'=>$serial_number,'upload_note'=>$upload_note]);
      //记录取消未到货操作日志
        $this->Reject_note_model->cancel_log([
         'cancel_id'=>$id,
         'operation_type'=>'上传了截图',
         'operation_content'=>'上传流水号及截图',
        ]); 
        if(empty($temp)){
             throw new Exception('保存图片及流水号失败');
        }else{
            return true;
        }
   }

   /**
     * 保存流水截图最新版
     * @param  $cancel_url
     * @param $id
     * @author jeff 2019-09-25
     * @return  bool
     */

    public function  cancel_upload_screenshots_save_v2($id='',$is_submit=0,$upload_note='',$cancel_url,$serial_number,$real_refund,$completed_time){
        $this->load->model('Reject_note_model');
        $this->load->model('finance/Purchase_order_cancel_to_receipt_model');
        $error_msg='';
        $success=false;

        $this->purchase_db->trans_begin();
        try {

            $real_refund_total=0;

            foreach ($cancel_url as $purchase_number => $value) {
                $url='';
                if(is_array($value)){
                    $url = json_encode($value);
                }else{
                    if (!empty($value)){
                        $url = explode(',', $value);
                        $url = json_encode($url);
                    }
                }

                $update = [
                    'cancel_url' => $url, //截图url
                    'serial_number' => isset($serial_number[$purchase_number])?$serial_number[$purchase_number]:'', //取消流水
                    'real_refund' => !empty($real_refund[$purchase_number])?$real_refund[$purchase_number]:0,//实际退款金额
                    'screenshot_time'=> date('Y-m-d H:i:s')
                ];
                $cancel_detail_update = $this->purchase_db
                    ->where('cancel_id',$id)
                    ->where('purchase_number',$purchase_number)
                    ->update('purchase_order_cancel_detail', $update);
                if (empty($cancel_detail_update)) {
                    throw new Exception('采购单-取消未到货数量-子表记录失败');
                }

                $pay_type_update = $this->purchase_db
                    ->where('purchase_number',$purchase_number)
                    ->update('purchase_order_pay_type', ['completed_time'=>$completed_time[$purchase_number]]);
                if (empty($pay_type_update)) {
                    throw new Exception('采购单-退款时间-更新失败');
                }

                $real_refund_total += floatval($update['real_refund']);
            }

            //更新取消主表总实际退款金额
            $update_cancel_main = $this->purchase_db->where('id',$id)->update('purchase_order_cancel',['real_refund_total'=>$real_refund_total,'upload_note'=>$upload_note]);
            if (empty($update_cancel_main)) throw new Exception('更新取消主表实际退款金额失败');

            //上传截图提交
            if ($is_submit){
                $submit_res= $this->purchase_db->where('id',$id)
                    ->update('purchase_order_cancel',['audit_status'=>CANCEL_AUDIT_STATUS_CF]);
                if (empty($submit_res)) throw new Exception('取消未到货数量上传截图提交失败');

                $param['cancel_id'] = $id;
                $param['upload_status'] = 1;//未上传截图
                $param['is_in_receipt'] = 1;//未推送应收款单

                //获取流向待收款数据
                $receipt_datas = $this->Purchase_order_cancel_to_receipt_model->get_receipt_by_cancel_id($param);

                if (empty($receipt_datas)) throw new Exception('没有可流向应收款页面的数据');

                foreach ($receipt_datas as $key => $receipt_data){
                    $real_refund_time = isset($completed_time[$receipt_data['purchase_number']])?$completed_time[$receipt_data['purchase_number']]:null;
                    if(is_null($real_refund_time)){
                        throw new Exception('应收款表实际退款时间缺失');
                    }
                    //插入应收款表
                    $order_receipt = [
                        'cancel_id'=>$receipt_data['cancel_id'],
                        'purchase_number' => $receipt_data['purchase_number'], //采购单号
                        'supplier_code' => isset($receipt_data['supplier_code']) ? $receipt_data['supplier_code'] : '', //供应商代码
                        'supplier_name' => isset($receipt_data['supplier_name']) ? $receipt_data['supplier_name'] : '', //供应商名称
                        'is_cross_border' => isset($receipt_data['is_cross_border']) ? $receipt_data['is_cross_border'] : '', //是否是跨境宝(默认0.否,1.跨境宝)
                        'settlement_method' => isset($receipt_data['settlement_method']) ? $receipt_data['settlement_method'] : '', //结算方式
                        'pay_type' => isset($receipt_data['pay_type']) ? $receipt_data['pay_type'] : '', //支付方式（从供应商拉取）
                        'pay_name' => '供应商退款', //退款名称
                        'pay_price' => $receipt_data['pay_price'], //金额(实际退款金额)
                        'cancel_ctq' => $receipt_data['cancel_ctq'], //取消数量
                        'application_id' => $receipt_data['application_id'], //申请人ID
                        'apply_user_name' => $receipt_data['apply_user_name'], //申请人名
                        'apply_time'=>$receipt_data['apply_time'], //申请时间
                        'apply_notice' => '取消未到货退款', //申请备注
                        'audit_id' => $receipt_data['audit_id'], //审核人id
                        'audit_user_name' => $receipt_data['audit_user_name'], //审核人名
                        'audit_time' => date('Y-m-d H:i:s'), //审核时间
                        'audit_notice'=>'',//审核备注
                        'real_refund_time' => $real_refund_time,
                    ];
                    $receipt_insert = $this->purchase_db->insert('purchase_order_receipt', $order_receipt);
                    if (empty($receipt_insert)) {
                        throw new Exception('插入应收款表失败');
                    }

                    //更新流向表
                    $update_res = $this->purchase_db->where('id',$receipt_data['id'])->update('purchase_order_cancel_to_receipt',['upload_status'=>2,'is_in_receipt'=>2]);
                    if (empty($update_res)) throw new Exception('流向收款表数据更新失败');

                    //记录取消未到货操作日志
                    $this->Reject_note_model->cancel_log([
                        'cancel_id'=>$id,
                        'operation_type'=>'上传了截图',
                        'operation_content'=>'上传流水号及截图',
                    ]);
                }

            }

            $this->purchase_db->trans_commit();
            $success=true;
        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $error_msg.=$e->getMessage();
        }

        $return['success'] = $success;
        $return['message'] = $error_msg;
        return $return;
    }

   /**
    * 获取取消未到货操作日志
    * @author harvin
    * @param type $id
    * @return type
    * @throws Exception
    */
   public function get_cancel_log_info($id){
      $log= $this->purchase_db
               ->select("*")
               ->where('cancel_id',$id)
              ->order_by('create_time','desc')
               ->get('purchase_cancel_log')
               ->result_array();
       if(empty($log)){
           throw new Exception('未有操作日志');
       }
       foreach ($log as &$v){
            $v['create_user'].=$v['create_id'];
       }
       return $log;       
   }


   /**
     * 显示变更采购员显示
     * @param array $id 数组
     * @author harivn 2019-1-12
     * @return mixed
     * * */
    public function get_change_purchaser_list($id) {
        $data = [];
        foreach ($id as $v) {
            $items = $this->purchase_db->select('purchase_number')->where('id', $v)->get('purchase_order_items')->row_array();
            !empty($items) && $data[] = $items['purchase_number'];
        }
        //去除相同的元素
        $data = array_unique($data);
        $data_list = [];
        if(empty($data)){
            throw new Exception('采购明细表，不存在');
        }
        foreach ($data as $key => $value) {
            $res = [];
            $order = $this->purchase_db->select('purchase_number,buyer_name')->where('purchase_number', $value)->get('purchase_order')->row_array();
            $res['purchase_number'] = isset($order['purchase_number']) ? $order['purchase_number'] : '';
            $res['buyer_name'] = isset($order['buyer_name']) ? $order['buyer_name'] : '';
            $data_list[] = $res;
        }
        return $data_list;
    }

    /**
     * 保存变更采购员信息
     * @param string $purchase_number 
     * @author harvin 2019-11-12
     * @return mixed
     * */
    public function change_purchaser_save($purchase_number, $buyer_id) {
        $query_builder = $this->purchase_db;
        try {
             //开始事物
            $query_builder->trans_begin();
            $this->load->model('Reject_note_model');
            if (empty($purchase_number))
                throw new Exception('purchase_number 不存在');
            $purchase_number = explode(',', $purchase_number);
            //调用采购接口 获取采购员名
            $this->load->model('user/Purchase_user_model');
            $this->load->model('purchase_suggest/purchase_suggest_map_model');
            $resurs = $this->Purchase_user_model->get_user_info($buyer_id);
            $buyer_name = isset($resurs['user_name']) ? $resurs['user_name'] : 'admin';
            $data = [
                'buyer_id' => $buyer_id,
                'buyer_name' => $buyer_name,
            ];
            $query_builder->where_in('purchase_number', $purchase_number)->update('purchase_order', $data);
            //记录操作日志
            foreach ($purchase_number as $key => $value) {
                $log = [
                    'record_number' => $value,
                    'record_type' => 'PURCHASE_ORDER',
                    'content' => '修改采购单号的采购员',
                    'content_detail' => '采购单号' . $value . "的采购员修改为" . $buyer_name,
                ];
                $this->Reject_note_model->get_insert_log($log);
                $demand = $this->purchase_suggest_map_model->get_demand_number_list($value);
                $res = $this->purchase_db->where_in('demand_number', $demand)->update('purchase_suggest', $data);
                if (empty($res)) throw new Exception('采购单号' .$value.'更新备货单的采购员失败');
            }
            if ($query_builder->trans_status() === FALSE) {
                $query_builder->trans_rollback();
                return ['msg' => '操作失败', 'bool' => false];
            } else {
                $query_builder->trans_commit();
                return ['msg' => '操作成功', 'bool' => true];
            }
        } catch (Exception $exc) {
            return ['msg' => $exc->getMessage(), 'bool' => false];
        }
    }
    /**
     * 获取历史取消的运费及优惠额
     * @author harvin 2019-4-9
     * @param type $id
     * @return array
     */
    public function get_freight_discount($purchase_number){
        // 转成数组
        if(is_array($purchase_number)){
            $purchase_number_arr = $purchase_number;
        }else{
            $purchase_number_arr = [$purchase_number];
        }
        //获取历史取消优惠及运费
        $history_freight = $history_discount = $history_process_cost = 0;
       /* $detail=  $this->purchase_db
                ->select('purchase_number')
                ->where('items_id', (int)$id)
                ->get('purchase_order_cancel_detail')
                ->row_array();
        if(empty($detail)){
             return ['history_freight' => $history_freight, 'history_discount' => $history_discount,'history_process_cost' => $history_process_cost];
        }*/
       /* $cancel_id_list= is_array($detail)?array_column($detail, 'cancel_id'):[];
        if(empty($cancel_id_list)){
            return ['history_freight' => $history_freight, 'history_discount' => $history_discount,'history_process_cost' => $history_process_cost];
        }
       $order= $this->purchase_db
                ->select('id,audit_status')
                ->where_in('id', $cancel_id_list)
                ->get('purchase_order_cancel')
                ->result_array(); 
       if(empty($order)){
            return ['history_freight' => $history_freight, 'history_discount' => $history_discount,'history_process_cost' => $history_process_cost];
       }
       $data_id=[];
        foreach ($order as $key => $value) {
            if(!in_array($value['audit_status'],[CANCEL_AUDIT_STATUS_CGBH,CANCEL_AUDIT_STATUS_CFBH,CANCEL_AUDIT_STATUS_YZBS])){
                $data_id[]=$value['id'];
            }
        }        
        if(empty($data_id)){
            return ['history_freight' => $history_freight, 'history_discount' => $history_discount,'history_process_cost' => $history_process_cost];
        }  
        $data_id= array_unique($data_id);;*/

        //获取历史审核通过的取消运费
        $cancel_detail = $this->purchase_db
            ->select('detail.freight,detail.discount,detail.process_cost')
            ->from('purchase_order_cancel_detail detail')
            ->join('purchase_order_cancel main','main.id=detail.cancel_id')
            ->where_in('detail.purchase_number',$purchase_number_arr)
            ->where_in('main.audit_status',[CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC])
            ->group_by('detail.cancel_id,detail.purchase_number')
            ->get()
            ->result_array();
        if (empty($cancel_detail)) {
            return ['history_freight' => $history_freight, 'history_discount' => $history_discount,'history_process_cost' => $history_process_cost];
        }
        foreach ($cancel_detail as $row) {
            $history_freight += $row['freight'];
            $history_discount += $row['discount'];
            $history_process_cost += $row['process_cost'];
        }
        return ['history_freight' => $history_freight, 'history_discount' => $history_discount,'history_process_cost' => $history_process_cost];
    }

    /** 根据勾选ID获取采购单号下所有采购明细
     * @param $items_ids
     * @return array
     */
    public function get_all_ids($items_ids){
        if(!is_array($items_ids) || empty($items_ids)){
            return [];
        }
        $purchase_number_arr = $this->purchase_db->select('purchase_number')
                                    ->where_in('id',$items_ids)
                                    ->get('purchase_order_items')
                                    ->result_array();
        $purchase_number_list = array_unique(array_column($purchase_number_arr,'purchase_number'));
        $ids = $this->purchase_db->select('id')
            ->where_in('purchase_number',$purchase_number_list)
            ->get('purchase_order_items')
            ->result_array();
        return array_column($ids,'id');
    }

    /**
     * 获取退款金额
     * @param $id 参数
     * **/
    public function get_receivable_pay_price($ids){
        if (empty($ids)) return [];

        $receipt = $this->purchase_db
            ->select('cancel_id,pay_price')
            ->where_in('cancel_id', $ids)
            ->get('purchase_order_receipt')
            ->result_array();

        if(empty($receipt)){
            return [];
        }

        return array_column($receipt, 'pay_price','cancel_id');
    }

    /**
     * 获取退款金额
     * @param $ids 参数
     * @param bool $more
     * @return array
     */
    public function get_receivable_pay_price_v2($ids, $more = false)
    {
        if (empty($ids)) return [];

        $receipt = $this->purchase_db
            ->select('pay_price,original_pay_price')
            ->where_in('cancel_id', $ids)
            ->get('purchase_order_cancel_to_receipt')
            ->result_array();

        if (empty($receipt)) {
            return [];
        }
        if ($more) {
            return [
                'pay_price'=>array_column($receipt, 'pay_price'),
                'original_pay_price'=>array_column($receipt, 'original_pay_price'),
            ];
        } else {
            return array_column($receipt, 'pay_price');
        }
    }

    /**
     * 根据取消主表id获取采购单号
     * @param $ids
     * @param string $type
     * @author harvin 2019-3-6
     * @return array
     */
    public function get_purchase_numbers($id){
        $item= $this->purchase_db
            ->select('purchase_number,serial_number,relative_superior_number')
            ->where('cancel_id',$id)
            ->get('purchase_order_cancel_detail')
            ->result_array();

        if(empty($item)){
            return [];
        }

        return ['purchase_numbers'=>array_column($item, 'purchase_number'),'serial_number'=>array_column($item, 'serial_number'),'relative_superior_number'=>array_column($item,'relative_superior_number')];
    }

    /**
     * 根据收款的订单信息获取1688退款信息
     * @param $limit
     * @throws Exception
     */
    public function getAliQueryOrderRefund($data_list){
        $this->load->library('alibaba/AliOrderApi');
        $error_msg='';
        $success=true;
        $this->purchase_db->trans_begin();
        try{

            foreach ($data_list as $value){
                if (empty($value['pai_numbers'])) continue;

                //将多个拍单号转换为数组
                $pai_arr = explode(',',$value['pai_numbers']);

                foreach ($pai_arr as $pai_number){
                    $aliRefund = $this->aliorderapi->getQueryOrderRefund($pai_number);
                    if($aliRefund['code'] and isset($aliRefund['data']) and is_array($aliRefund['data'])){

                        //查询拍单号的退款金额是否一致,不一致则更新
                        $apply_amount = $this->purchase_db->select('apply_amount')->from('purchase_order_pay_type')
                            ->where('pai_number',$pai_number)
                            ->get()->row_array();

                        //退款金额不一致,更新
                        if ($apply_amount!=$aliRefund['data']['applyCarriage']+$aliRefund['data']['applyPayment']){
                            $updateArr = array(
                                'apply_carriage'=>$aliRefund['data']['applyCarriage'],
                                'apply_payment'=>$aliRefund['data']['applyPayment'],
                                'apply_amount'=>$aliRefund['data']['applyCarriage']+$aliRefund['data']['applyPayment'],
                                'apply_reason' => $aliRefund['data']['applyReason'],
                                'completed_time' => $aliRefund['data']['completedTime'],//退款成功时间
                                );

                            $update_res = $this->purchase_db->where('pai_number',$pai_number)->update('purchase_order_pay_type',$updateArr);
                            if(empty($update_res)) throw new Exception('更新退款金额失败');

                        }
                    }
                }
            }

            $this->purchase_db->trans_commit();
        }catch (Exception $e){
            $this->purchase_db->trans_rollback();
            $success=false;
            $error_msg.=$e->getMessage();
        }

        return [
            'success'=>$success,
            'error_msg'=>$error_msg
        ];

    }

    /**
     * @desc 获取上传截图预览数据
     * @author Jeff
     * @Date 2019/9/24 17:05
     * @param $id 取消主表id
     * @return
     */
    public function get_upload_preview_data($id, $real_refund_time='')
    {
        //根据取消id 获取所有采购单号和采购来源
        $cancel_detail = $this->purchase_db
            ->select('a.cancel_id,a.purchase_number,a.cancel_url,a.serial_number,a.real_refund,b.source')
            ->from('purchase_order_cancel_detail a')
            ->join('purchase_order b','a.purchase_number=b.purchase_number')
            ->where('a.cancel_id', $id)
            ->group_by('a.purchase_number')
            ->get()
            ->result_array();

        $this->load->model('finance/Purchase_order_pay_type_model');
        $this->load->model('compact/Compact_items_model');

        $source_network = [];//网采单
        $source_compact = [];//合同单

        foreach ($cancel_detail as $key => $value){
            if ($value['source']==SOURCE_COMPACT_ORDER){//放入合同单中
                $source_compact[$value['purchase_number']]=$value;
            }else{
                $source_network[$value['purchase_number']]=$value;
            }
        }

        $pai_arr = [];
        if (!empty($source_network)){
            //根据采购单号获取拍单号
            foreach ($source_network as $purchase_number => &$value){
                $pai_numbers = $this->Purchase_order_pay_type_model->get_pai_number($purchase_number);

                //根据拍单号查询1688退款金额
                $apply_amounts = $this->Purchase_order_pay_type_model->get_apply_amount($pai_numbers[0]);
                $value['apply_amounts'] = array_sum($apply_amounts)==0?'':array_sum($apply_amounts);//1688退款金额
                $value['cancel_url'] = json_decode(isset($value['cancel_url']) ? $value['cancel_url'] : [], TRUE);//流水图url
                //根据拍单号查询1688退款金额
                $refund_time = $real_refund_time;
                if($refund_time == '' || $refund_time == '0000-00-00 00:00:00') {
                    $refund_time = $this->Purchase_order_pay_type_model->get_refund_time_by_pai($pai_numbers[0]);
                }

                $value['completed_time'] = $refund_time=='0000-00-00 00:00:00'?"":$refund_time;

                $pai_arr[$pai_numbers[0]][] = $value;
            }

        }

        $final_arr = [];

        if (!empty($pai_arr)){
            foreach ($pai_arr as $order_number => $value){
                $final_arr[] = [
                    'order_number' => (string)$order_number,
                    'apply_amounts' => $value[0]['apply_amounts'],
                    'records' => $value,
                ];
            }
        }

        $compact_arr = [];
        if (!empty($source_compact)){
            //根据采购单号获取合同号
            foreach ($source_compact as $purchase_number => &$value){
                $compact_numbers = $this->Compact_items_model->get_compact_by_purchase($purchase_number);
                $value['cancel_url'] = json_decode(isset($value['cancel_url']) ? $value['cancel_url'] : [], TRUE);//流水图url
                $refund_time = $real_refund_time;
                if($refund_time == '' || $refund_time == '0000-00-00 00:00:00') {
                    $refund_time = $this->Purchase_order_pay_type_model->get_refund_time_by_purchase_number($purchase_number);
                }
                $value['completed_time'] = $refund_time=='0000-00-00 00:00:00'?"":$refund_time;//退款时间

                $compact_arr[$compact_numbers['compact_number']][] = $value;
            }
        }

        if (!empty($compact_arr)){
            foreach ($compact_arr as $order_number => $value){
                $final_arr[] = [
                    'order_number' => (string)$order_number,
                    'apply_amounts' => 0,
                    'records' => $value
                ];
            }
        }

        return $final_arr;
    }
    public function is_same_compact_or_source($purchase_numbers)
    {

        //判断采购单号的供应商是否相同
        $order=$this->purchase_db->select('source')
            ->where_in('purchase_number',$purchase_numbers)
            ->get('purchase_order')
            ->result_array();
        $source= array_column(isset($order)?$order:[], 'source');
        if(empty($source)) return FALSE;
        $source= array_unique($source);
        if(count($source)>1){
            return 1;
        }

        $this->load->model('compact/Compact_items_model');
        $compact_numbers = [];
        foreach ($purchase_numbers as $value){
            //获取采购单的合同号
            $compact_number = $this->Compact_items_model->get_compact_by_purchase($value);
            if($compact_number){
                $compact_numbers[] = $compact_number['compact_number'];
            }
        }
        $compact_numbers = array_unique($compact_numbers);
        if(count($compact_numbers)>1){
            return 2;
        }else{
            return FALSE;
        }
    }


    /**
     * 获取报损金额包含运费
     * @param string  $purchase_number
     * @param array $sku
     * @return array
     * totoro
     */
    public function get_loss_totalprice($purchase_number,$sku){
        $data = [];
        $purchase_order_reportloss = $this->purchase_db->select('sku,pur_number,loss_totalprice')
            ->where('pur_number', $purchase_number)
            //  ->where('status',REPORT_LOSS_STATUS_FINANCE_PASS)
            ->where_in('sku', $sku)
            ->get('purchase_order_reportloss')
            ->result_array();
        if (empty($purchase_order_reportloss))
            return [];
        foreach ($purchase_order_reportloss as $key => $value) {
            $data[$value['pur_number'] . "-" . $value['sku']] = $value['loss_totalprice'];
        }
        return $data;
    }

    /**
     * 根据取消未到货记录id获取取消未到货编码
     * @param $cancel_id
     * @return array|null
     */
    public function get_cancel_number_by_id($cancel_id){
        return $this->purchase_db
            ->select('cancel_number')
            ->from('purchase_order_cancel')
            ->where('id', $cancel_id)
            ->get()->row_array();
    }

    /**
     * 取消未到货列表
     * @param $params
     * @author harvin 2019-3-9
     * @return  array
     */
    public function get_cancel_lists_sum($params){
        $query_builder=$this->purchase_db;
        $query_builder->select('A.id,A.total_price,A.real_refund_total,F.pay_price,F.original_pay_price');
        $query_builder->from('purchase_order_cancel as A');
        $query_builder->join('purchase_order_cancel_detail as B','A.id=B.cancel_id','left');
        $query_builder->join('purchase_order as C','C.purchase_number=B.purchase_number','left');
        $query_builder->join('purchase_order_pay_type as D','D.purchase_number=B.purchase_number','left');
        //$query_builder->join('purchase_order_receipt as E','E.purchase_number=B.purchase_number','left');
        $query_builder->join('purchase_order_cancel_to_receipt as F','F.cancel_id=B.cancel_id AND F.purchase_number=B.purchase_number','left');


        if(isset($params['ids']) && $params['ids']){ //取消id
            $ids= explode(',', $params['ids']);
            $query_builder->where_in('A.id',$ids);
        }

        if(isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->purchase_db->where_in('A.create_user_id',$params['groupdatas']);
        }

        if(isset($params['cancel_number']) && trim($params['cancel_number'])){ //申请编码
            $query_builder->where('A.cancel_number', trim($params['cancel_number']));
        }
        if(isset($params['serial_number']) && trim($params['serial_number'])){ //收款流水号
            $serial_number= explode(' ',trim($params['serial_number']));
            if (count($serial_number)>1){
                $query_builder->where('B.serial_number', trim($params['serial_number']));
            }else{
                $query_builder->like('B.serial_number', trim($params['serial_number']));
            }

        }
        if(isset($params['sku']) && trim($params['sku'])){ //sku
            //支持批量查询
            $sku_list= explode(' ', $params['sku']);
            $sku= array_filter($sku_list);
            $query_builder->where_in('B.sku',$sku);
        }
        if(isset($params['purchase_number']) && trim($params['purchase_number'])){ //采购单号
            $purchase_number_list= explode(' ', trim($params['purchase_number']));
            $purchase_number= array_filter($purchase_number_list);
            $query_builder->where_in('B.purchase_number',$purchase_number);
        }
        if(isset($params['create_user_id']) && $params['create_user_id']){
            $query_builder->where_in('A.create_user_id',$params['create_user_id']);
        }
        if(isset($params['audit_status']) && $params['audit_status']){
            $query_builder->where_in('A.audit_status',$params['audit_status']);
        }
        if(isset($params['create_time_start'])&& $params['create_time_start']){ //申请时间开始
            $query_builder->where('A.create_time >=',$params['create_time_start']);
        }
        if(isset($params['create_time_end'])&& $params['create_time_end']){ //申请时间开始
            $query_builder->where('A.create_time <=',$params['create_time_end']);
        }
        if(isset($params['relative_superior_number'])&& $params['relative_superior_number']){ //申请时间开始
            $query_builder->where('B.relative_superior_number',$params['relative_superior_number']);
        }
        if(isset($params['purchase_type_id'])&& $params['purchase_type_id']){ //业务线
            $query_builder->where_in('C.purchase_type_id ',$params['purchase_type_id']);
        }
        if(isset($params['ali_refund'])&& $params['ali_refund']==1){ //1688刷新退款信息
            $query_builder->where('C.source =',SOURCE_NETWORK_ORDER);
            $query_builder->where('A.audit_status =',CANCEL_AUDIT_STATUS_SCJT);
        }

        if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
            $this->purchase_db->where('C.supplier_code',$params['supplier_code']);
        }

        if( isset($params['cancel_price_min']) && !empty($params['cancel_price_min'])&&
            isset($params['cancel_price_max']) && !empty($params['cancel_price_max']) ){
            $this->purchase_db->where('A.total_price >',$params['cancel_price_min']);
            $this->purchase_db->where('A.total_price <',$params['cancel_price_max']);
        }

        if(isset($params['completed_time_start'])&& $params['completed_time_start']){ //退款时间开始
            $query_builder->where('D.completed_time >=',$params['completed_time_start']);
        }
        if(isset($params['completed_time_end'])&& $params['completed_time_end']){ //退款时间结束
            $query_builder->where('D.completed_time <=',$params['completed_time_end']);
        }

        //拍单号(1-≠0)
        if (isset($params['pai_number']) && 1 == $params['pai_number']) {
            $query_builder->where('D.pai_number <>', '');
        }
        //1688退款金额（1-≠0）
        if (isset($params['apply_amount']) && 1 == $params['apply_amount']) {
            $query_builder->where('D.apply_amount <>', 0);
        }
        //采购来源（1-合同，2-网采）
        if (!empty($params['source'])) {
            $query_builder->where('C.source', $params['source']);
        }

        $count_qb       = clone $query_builder;
        $count_select2  = clone $query_builder;

        // 汇总 取消记录个数
        $count_select3  = $count_qb->select("count(distinct(A.id)) as num")->get()->row_array();
        $total_count    = isset($count_select3['num']) ? (int)$count_select3['num'] : 0;

        $results_sql_1  = $query_builder->group_by('A.id')->get_compiled_select();// 汇总金额
        $results_sql_2  = $count_select2->group_by('A.id,F.purchase_number')->get_compiled_select();// 汇总金额

        // 汇总 取消金额
        $select_main1 = $query_builder->query("SELECT SUM(total_price) AS total_price,SUM(real_refund_total) AS real_refund_total
            FROM (
              $results_sql_1
            
            ) AS tmp ")->row_array();

        // 汇总 退款金额
        $select_main2 = $query_builder->query("SELECT SUM(pay_price) AS pay_price,SUM(original_pay_price) AS original_pay_price
            FROM (
              $results_sql_2
            ) AS tmp ")->row_array();
        
        $total_price_sum = isset($select_main1['total_price'])?$select_main1['total_price']:0;
        $real_refund_total_sum = isset($select_main1['real_refund_total'])?$select_main1['real_refund_total']:0;
        $pay_price_sum = isset($select_main2['pay_price'])?$select_main2['pay_price']:0;

        //组装需要的数据
        $return_data = [
            'total_count'=>$total_count,
            'total_price_sum'=>sprintf("%.3f",$total_price_sum),
            'pay_price_sum'  =>sprintf("%.3f",$pay_price_sum),
            'real_refund_total_sum'  =>sprintf("%.3f",$real_refund_total_sum)
        ];
        return $return_data;
    }

    /**
     * 取消未到货批量上传图片 34130
     */
    public function get_cancel_upload_data($ids=[])
    {
        if(is_string($ids))$ids = explode(',', $ids);
        $data = $this->purchase_db->from("purchase_order_cancel_detail as d")
            ->select("d.*,d.id as deid,p.pai_number as qx_number,p.real_price as real_refund_ali,p.apply_carriage,p.apply_payment,
            p.apply_amount,p.completed_time as completion_time,c.cancel_image_list,c.id")
            ->join("pur_purchase_order_pay_type as p", "d.purchase_number=p.purchase_number", "left")
            ->join("pur_purchase_order_cancel as c", "d.cancel_id=c.id", "left")
            ->where_in("d.cancel_id", $ids)
            ->group_by("d.purchase_number")
            ->get()
            ->result_array();
        if($data && count($data) > 0){
            $temp = [];
            foreach ($data as $val){
                $val['cancel_url'] = !empty($val['cancel_url']) ? json_decode($val['cancel_url'], true): $val['cancel_url'];
                $val['completion_time'] = $val['completion_time'] == '' || $val['completion_time'] == "0000-00-00 00:00:00"?'': $val['completion_time'];
                $temp[] = $val;
            }
            return $temp;
        }
        return [];
    }


    /**
     * 保存取消未到货批量上传图片 34130
     */
    public function save_cancel_upload_data($param=[], $create_note='')
    {
        $res = ['msg'=>'更新失败','code'=>0];
        try{
            $err = [];
            $success = [];
            foreach ($param as $key=>$val){
                $val = json_decode($val, true);
                $pur = $val['purchase_number'];
                $cancel_data = $this->purchase_db->from("purchase_order_cancel_detail")->select("cancel_id")->where("id=", $key)->get()->row_array();
                $cancel_id = false;
                if(SetAndNotEmpty($cancel_data, 'cancel_id'))$cancel_id = $cancel_data['cancel_id'];
                $img = '';
                if(SetAndNotEmpty($val, 'images'))$img = $val['images'];//json_encode($val['images']);

                $res_data = $this->cancel_upload_screenshots_save_v2(
                    $cancel_id,
                    1,
                    $create_note,
                    [$pur => $img],
                    [$pur => $val['serial_number']],
                    [$pur => $val['real_refund']],
                    [$pur => $val['completed_time']]
                );
                if(!$res_data['success']){
                    $err[] = $pur.$res_data['message'];
                }else{
                    $success[] = $pur;
                }
            }
            if(count($success) > 0 && count($success) != count($param) && count($err) > 0){
                $res['msg'] = "部分上传成功：".implode('，', $err);
                $res['code'] = 1;
            }elseif(count($success) > 0 && count($success) == count($param)){
                $res['code'] = 1;
                $res['msg'] = "全部上传成功！";
            }else{
                $res['msg'] = "全部上传失败！";
            }
            return $res;
        }catch (Exception $e){
            $res['msg'] = $e->getMessage();
        }
        return $res;
    }

    //给导出使用
    public function get_cencel_list_server($params,$offsets,$limit,$page){
        $query_builder=$this->purchase_db;
        $query_builder->select('D.apply_amount,A.id,A.cancel_number,A.audit_status,A.cancel_ctq,A.total_price,A.create_user_name,
         A.create_time,A.audit_user_name,A.audit_time,A.is_edit,A.upload_note,A.real_refund_total,A.freight,A.discount,A.process_cost,
         B.serial_number,E.real_refund_time as completed_time,
         B.relative_superior_number,B.purchase_number,B.sku,B.cancel_reason,A.cancel_source,C.buyer_id,C.buyer_name
         ');
        $query_builder->from('purchase_order_cancel as A');
        $query_builder->join('purchase_order_cancel_detail as B','A.id=B.cancel_id','left');
        $query_builder->join('purchase_order as C','C.purchase_number=B.purchase_number','left');
        $query_builder->join('purchase_order_pay_type as D','D.purchase_number=B.purchase_number','left');
        $query_builder->join('purchase_order_receipt as E','E.cancel_id=A.id','left');

        if(isset($params['ids']) && $params['ids']){ //取消id
            if(is_string($params['ids'])){
                $query_builder->where('A.id',$params['ids']);
            }else{
                $query_builder->where_in('A.id',$params['ids']);
            }
        }

        if(SetAndNotEmpty($params, 'buyer_id', 'ar')){
            $this->purchase_db->where_in("C.buyer_id", $params['buyer_id']);
        }

        if(isset($params['group_ids']) && !empty($params['group_ids'])){
            $this->purchase_db->where_in('A.create_user_id',$params['groupdatas']);
        }
        if(isset($params['upload_files_time'][0]) && isset($params['upload_files_time'][1]) && !empty($params['upload_files_time'][0]) && !empty($params['upload_files_time'][1])){
            $upt1 = $params['upload_files_time'][0];
            $upt2 = $params['upload_files_time'][1];
            $this->purchase_db->where("B.screenshot_time between '{$upt1}' and '{$upt2}'");
        }
        if(isset($params['refund_price'][0]) && !empty($params['refund_price'][0])){
            $upt1 = $params['refund_price'][0];
            $this->purchase_db->where("A.total_price >=".$upt1);
        }
        if(isset($params['refund_price'][1]) && !empty($params['refund_price'][1])){
            $upt2 = $params['refund_price'][1];
            $this->purchase_db->where("A.total_price <=".$upt2);
        }
        if(SetAndNotEmpty($params, 'cancel_source', 'n')){
            $query_builder->where('A.cancel_source', $params['cancel_source']);
        }

        if(isset($params['cancel_number']) && trim($params['cancel_number'])){ //申请编码
            $query_builder->where('A.cancel_number', trim($params['cancel_number']));
        }
        if(isset($params['serial_number']) && trim($params['serial_number'])){ //收款流水号
            $serial_number= explode(' ',trim($params['serial_number']));
            if (count($serial_number)>1){
                $query_builder->where('B.serial_number', trim($params['serial_number']));
            }else{
                $query_builder->like('B.serial_number', trim($params['serial_number']));
            }
        }
        if(isset($params['sku']) && trim($params['sku'])){ //sku
            //支持批量查询
            $sku_list= explode(' ', $params['sku']);
            $sku= array_filter($sku_list);
            $query_builder->where_in('B.sku',$sku);
        }
        if(isset($params['purchase_number']) && trim($params['purchase_number'])){ //采购单号
            $purchase_number_list= explode(' ', trim($params['purchase_number']));
            $purchase_number= array_filter($purchase_number_list);
            $query_builder->where_in('B.purchase_number',$purchase_number);
        }
        if(isset($params['create_user_id']) && $params['create_user_id']){
            $query_builder->where_in('A.create_user_id',$params['create_user_id']);
        }
        if(isset($params['audit_status']) && $params['audit_status']){
            $query_builder->where_in('A.audit_status',$params['audit_status']);
        }
        if(isset($params['create_time_start'])&& $params['create_time_start']){ //申请时间开始
            $query_builder->where('A.create_time >=',$params['create_time_start']);
        }
        if(isset($params['create_time_end'])&& $params['create_time_end']){ //申请时间开始
            $query_builder->where('A.create_time <=',$params['create_time_end']);
        }
        if(isset($params['relative_superior_number'])&& $params['relative_superior_number']){ //申请时间开始
            $query_builder->where('B.relative_superior_number',$params['relative_superior_number']);
        }
        if(isset($params['purchase_type_id'])&& $params['purchase_type_id']){ //业务线
            $query_builder->where_in('C.purchase_type_id ',$params['purchase_type_id']);
        }
        if(isset($params['ali_refund'])&& $params['ali_refund']==1){ //1688刷新退款信息
            $query_builder->where('C.source =',SOURCE_NETWORK_ORDER);
            $query_builder->where('A.audit_status =',CANCEL_AUDIT_STATUS_SCJT);
        }

        if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
            $this->purchase_db->where('C.supplier_code',$params['supplier_code']);
        }

        if( isset($params['cancel_price_min']) && !empty($params['cancel_price_min'])&&
            isset($params['cancel_price_max']) && !empty($params['cancel_price_max']) ){
            $this->purchase_db->where('A.total_price >',$params['cancel_price_min']);
            $this->purchase_db->where('A.total_price <',$params['cancel_price_max']);
        }

        if(isset($params['completed_time_start'])&& $params['completed_time_start']){ //退款时间开始
            $query_builder->where('E.real_refund_time >=',$params['completed_time_start']);
        }
        if(isset($params['completed_time_end'])&& $params['completed_time_end']){ //退款时间结束
            $query_builder->where('E.real_refund_time <=',$params['completed_time_end']);
        }

        //拍单号(1-≠0)
        if (isset($params['pai_number']) && 1 == $params['pai_number']) {
            $query_builder->where('D.pai_number <>', '');
        }
        //1688退款金额（1-≠0）
        if (isset($params['apply_amount']) && 1 == $params['apply_amount']) {
            $query_builder->where('D.apply_amount <>', 0);
        }
        //采购来源（1-合同，2-网采）
        if (!empty($params['source'])) {
            $query_builder->where('C.source', $params['source']);
        }

        $count_qb = clone $query_builder;
        $results = $query_builder->limit($limit, $offsets)->order_by('A.id', 'desc')->get()->result_array();
        $count_row = $count_qb->select("count(distinct(A.id)) as num")->get()->row_array();
        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;

        $this->load->model('finance/Purchase_order_pay_type_model');

        //组装需要的数据
        foreach ($results as $key => &$row){
            $pay_price_list = $this->get_receivable_pay_price_v2($row['id'], true);
            $results[$key]['pay_price'] = sprintf("%.3f", isset($pay_price_list['pay_price']) ? array_sum($pay_price_list['pay_price']) : 0);
            $results[$key]['original_pay_price'] = sprintf("%.3f", isset($pay_price_list['original_pay_price']) ? array_sum($pay_price_list['original_pay_price']) : 0);
            $cancel_detail_info = $this->get_purchase_numbers($row['id']);
            $purchase_numbers_unique = array_unique($cancel_detail_info['purchase_numbers']);
            $serial_number_unique   =  array_unique($cancel_detail_info['serial_number']);
            $relative_superior_number_unique  =  array_unique($cancel_detail_info['relative_superior_number']);


            if(!empty($purchase_numbers_unique)){
                $results[$key]['purchase_numbers'] = implode(',',$purchase_numbers_unique);//采购单号

                $pai_numbers = $this->Purchase_order_pay_type_model->get_pai_number($purchase_numbers_unique);
                $pai_number_unique = array_unique($pai_numbers);
                $results[$key]['pai_numbers'] = implode(',',$pai_number_unique);//拍单号

                if (!empty($results[$key]['pai_numbers'])){
                    //根据拍单号查询1688退款金额
                    $apply_amounts = $this->Purchase_order_pay_type_model->get_apply_amount($pai_number_unique);

                    $results[$key]['apply_amounts'] = array_sum($apply_amounts)==0?'':array_sum($apply_amounts);//1688退款金额

                }else{
                    $results[$key]['apply_amounts'] = '';
                }


            }else{
                $results[$key]['purchase_numbers'] = '';
                $results[$key]['pai_numbers'] = '';
            }

            if (!empty($serial_number_unique)) {
                $results[$key]['serial_number'] = implode(',',$serial_number_unique);//流水号
            }

            if (!empty($serial_number_unique)) {
                $results[$key]['relative_superior_number'] = implode(',',$relative_superior_number_unique);//采购单号
            }

            $row['completed_time'] = $row['completed_time']=='0000-00-00 00:00:00'?"":$row['completed_time'];//退款完成时间
            $row['cancel_source'] = $row['cancel_source'] == 2 ? "系统自动取消" : "手动取消";
            //备货单
            // $this->purchase_db->select('purchase_number')->where('id', $v)->get('purchase_order_items')->row_array();



            $cancel_times  = $this->purchase_db->select('count(cancel_detail.id) as num')->from('purchase_order_cancel_detail cancel_detail')->join('purchase_order_cancel','purchase_order_cancel.id=cancel_detail.cancel_id')
                ->where(['cancel_detail.purchase_number'=>$row['purchase_number'],'cancel_detail.sku'=>$row['sku'],'purchase_order_cancel.is_edit'=>2])
                ->where('purchase_order_cancel.create_time<=',date('Y-m-d H:i:s'))
                ->where('purchase_order_cancel.create_time>=',date('Y-m-d H:i:s',strtotime('-30days')))
                ->get()->row_array();

            $row['cancel_times'] = $cancel_times['num']??0;

            $row['cancel_reason'] = !empty($row['cancel_reason'])?getOrderCancelReason($row['cancel_reason']):'';

            unset($purchase_numbers);
            unset($purchase_numbers_unique);
            unset($pai_numbers);
            unset($pai_number_unique);
        }

        $data_list['cancel_source']= ["1" => "手动取消", "2"=>"系统自动取消"]; //申请人
        $data_list['create_user_id']=get_buyer_name(); //申请人
        $data_list['audit_status']= get_cancel_status(); //取消未到货状态
        $data_list['purchase_type_id']= getPurchaseType(); //业务线
        $data_list['pai_number']= [1=>'≠0']; //拍单号
        $data_list['apply_amount']= [1=>'≠0']; //1688退款金额
        $data_list['source']= getPurchaseSource(); //采购来源
        $key_table=['申请编码','取消未到货状态','取消数量','取消金额','取消运费','取消优惠额','取消加工费','应退款金额','收款流水号','申请人/申请时间','审核人/审核时间','操作'];
        $return_data = [
            'drop_down_box'=>$data_list,
            'key'=>$key_table,
            'values'=>$results,
            'page_data'=>[
                'total'=>$total_count,
                'offset'=>$page,
                'limit'=>$limit,
                'pages'=> ceil($total_count/$limit),
            ]
        ];
        return $return_data;
    }

}
