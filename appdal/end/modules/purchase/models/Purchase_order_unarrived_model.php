<?php

class Purchase_order_unarrived_model extends Purchase_model {
    public function __construct(){
        parent::__construct();
        $this->load->helper('user');
        $this->load->model('Arrival_record_model');
        $this->load->model('purchase/purchase_order_model');
        $this->load->model('supplier_joint_model');
        $this->load->model('Purchase_financial_audit_model', 'm_financial_audit', false, 'purchase');
    }
    
    
    
    /**
     * 获取菜单付款状态
     * @param string $purchase_number
     * @author harvin 2019-3-8
     * @return  mixed
     */
   public function get_order_pay_status($purchase_number){
       $order=$this->purchase_db->select('pay_status')
               ->where('purchase_number',$purchase_number)
               ->get('purchase_order')->row_array();
       if(empty($order)){
           throw new Exception('采购单号，不存在');
       }
       return isset($order['pay_status'])?$order['pay_status']:''; 
   }
   /**
    * 判断付款状态
    * @param string $purchase_number
    * @author harvin 2019-3-8
    * @return  mixed
    */  
   public function get_order_status($purchase_number){
       $order=$this->purchase_db->select('purchase_order_status')
               ->where('purchase_number',$purchase_number)
               ->get('purchase_order')->row_array();
       if(empty($order)){
           throw new Exception('采购单号，不存在');
       }
       return isset($order['purchase_order_status'])?$order['purchase_order_status']:''; 
   }
    /**
     * 获取该采购单取消数量
     * @param string $id
     * @param string $purchase_number
     * @author harvin 2019-3-8
     * @return  mixed
     */
    public function  get_order_cancel_ctq($id,$purchase_number){
        $cancel_ctq = $this->purchase_db->select('SUM(B.cancel_ctq) as cancel_ctq')
            ->from('purchase_order_cancel AS A')
            ->join('purchase_order_cancel_detail AS B','A.id=B.cancel_id','INNER')
            ->where('B.cancel_id !=',$id)
            ->where('B.purchase_number',$purchase_number)
            ->where_in('A.audit_status', [CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC])
            ->get()
            ->row_array();

        return isset($cancel_ctq['cancel_ctq'])?intval($cancel_ctq['cancel_ctq']):0;
    }
    /**
     * 获取该采购单取消数量
     * @param string $id
     * @param string $purchase_number
     * @author harvin 2019-3-8
     * @return  mixed
     * @throws Exception
     */
    public function get_order_cancel_ctq_ben($id,$purchase_number){
        $order_cancel_ctq_ben = 0;
        $cancel_detail=  $this->purchase_db->select('cancel_ctq')
            ->where('cancel_id',$id)
            ->where('purchase_number',$purchase_number)
            ->get('purchase_order_cancel_detail')
            ->result_array();
      if(empty($cancel_detail)){
          throw new Exception('取消未到货数量-子表不存在');
      }
      foreach ($cancel_detail as $row) {
          $order_cancel_ctq_ben+=$row['cancel_ctq'];
      }
        return $order_cancel_ctq_ben;
    }

    /**
     * 采购单数量
     * @param string $purchase_number
     * @author harvin 2019-3-8
     * @return  mixed  
     */
    public function get_confirm_amount($purchase_number){
        $confirm_amount = 0;
        $order_items = $this->purchase_db->select('confirm_amount')
                ->where('purchase_number', $purchase_number)
                ->get('purchase_order_items')
                ->result_array();
        if (empty($order_items)) {
            throw new Exception('采购单' . $purchase_number . '，不存在');
        }
        foreach ($order_items as $val) {
            $confirm_amount += $val['confirm_amount'];
        }
        return $confirm_amount;
    }
    /**
     * 获取采购入库数量
     * @param string $purchase_number
     * @author harvin 2019-3-8
     * @return  mixed  
     */
    public function get_instock_qty($purchase_number){
         $instock_qty = 0;
        $warehouse_results = $this->purchase_db->select('instock_qty')
                ->where('purchase_number', $purchase_number)
                ->get('warehouse_results')
                ->result_array();
        if (empty($warehouse_results)) {
            return $instock_qty;
        }
        foreach ($warehouse_results as $val) {
            $instock_qty += $val['instock_qty'];
        }
        return $instock_qty;
    }
    /**
     * 获取采购单报损数量
     * @param string $purchase_number
     * @author harvin 2019-3-8
     * @return  mixed  
    */
   public function get_loss_amount($purchase_number){
       $loss_amount=0;
       $order_reportloss=$this->purchase_db
               ->select('loss_amount')
               ->where('status',REPORT_LOSS_STATUS_FINANCE_PASS)
               ->where('pur_number',$purchase_number)
               ->get('purchase_order_reportloss')
               ->result_array();
       if(empty($order_reportloss)){
           return $loss_amount;
       }
        foreach ($order_reportloss as $val) {
            $loss_amount += $val['loss_amount'];
        }
        return $loss_amount;     
   }
   /**
    * 变更采购单状态
    *@param int $status
    * @param string $purchase_number
    *@author harvin 2019-3-8
    *@return mexde 
    */
   public function change_order_status($purchase_number,$status){
       $re = $this->purchase_order_model->change_status($purchase_number,$status); // 统一入口修改采购单状态
       $this->purchase_order_model->purchase_track($purchase_number,$status);
       return $re;
   }
   /**
    * 更新取消未到货主表
    * @param int $status
    * @param int $id
    * @param string $audit_note
    *@author harvin 2019-3-8
    *@return mexde 
    */
   public function change_order_cancel($status,$id,$audit_note=''){
        $data_temp = [
                    'audit_status' => $status, //采购驳回
                    'audit_user_name' => getActiveUserName(), //审核人
                    'audit_note' => $audit_note, //审核备注
                    'audit_time' => date('Y-m-d H:i:s'), //审核时间
                    'is_edit'=>2,
                ];   
         $re=$this->purchase_db
               ->where('id',$id)
               ->update('purchase_order_cancel',$data_temp);

       if (in_array($status,array(CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM))){

           $cancelDetail = $this->purchase_db->from("purchase_order_cancel_detail AS detail")
               ->join("purchase_order_cancel AS cancel", "detail.cancel_id=cancel.id", "LEFT")
               ->where("cancel.id", $id)
               ->where_in("cancel.audit_status", [60, 70])
               ->select("detail.items_id,detail.purchase_number,detail.sku,SUM(detail.cancel_ctq) AS cancel_ctq")
               ->group_by("detail.items_id")->get()->result_array();

           if (!empty($cancelDetail)) {

               foreach ($cancelDetail as $cancelKey => $cancelValue) {

                   $detailData = $this->purchase_db->from("purchase_order_items")->where("id", $cancelValue['items_id'])
                       ->select("confirm_amount,invoiced_qty,purchase_number")->get()->row_array();
                   if( $cancelValue['cancel_ctq'] >= ($detailData['confirm_amount'] - $detailData['invoiced_qty']) ){

                       $compactNumbers = $this->purchase_db->from("purchase_compact_items")->where("purchase_number",$detailData['purchase_number'])
                           ->where("bind",1)
                           ->select("compact_number")
                           ->get()->row_array();
                       if(!empty($compactNumbers)) {

                           $flag = $this->m_financial_audit->getCompactData($compactNumbers['compact_number']);
                           if(!empty($flag)) {
                               $itemsData['contract_invoicing_status'] = CONTRACT_INVOICING_STATUS_NOT;
                           }else{
                               $itemsData['contract_invoicing_status'] = CONTRACT_INVOICING_STATUS_END;
                           }
                           $this->purchase_db->where("id", $cancelValue['items_id'])->update('purchase_order_items', $itemsData);
                       }

                   }
               }
           }
   }


         //如果状态为60,财务已收款,70系统自动通过,90已抵冲
       if (in_array($status,array(CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC))) {//将取消细节推送门户系统
           $this->send_cancel_detail_to_provider($id);

       }

       return $re;
   }
    /**
     * 获取已经历史取消的金额
     * @param string $id
     * @param string $purchase_number
     * @author harvin 2019-3-8
     * @return  mixed
     */
    public function get_cancel_amount($id,$purchase_number){
        // 获取非当前取消批次的 历次取消记录
        $po_cancel_sql_sub = " AND A.id !='{$id}' AND B.purchase_number='{$purchase_number}'";
        
        $audit_status_list = [CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_YDC];
        $po_cancel_sql_sub .= " AND A.audit_status IN(".implode(",",$audit_status_list).")";

        // 获取采购单
        $po_cancel_sql = "
        SELECT 
            combine_key,
            audit_status,
            SUM(cancel_ctq) AS cancel_ctq,
            SUM(item_total_price) AS product_money,
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

        $cancel_detail = $this->purchase_db->query($po_cancel_sql)->row_array();
        if($cancel_detail){
            $cancel_detail['real_price'] = $cancel_detail['product_money']
                + $cancel_detail['freight']
                - $cancel_detail['discount']
                + $cancel_detail['process_cost'];

            return $cancel_detail;
        }else{
            return [];
        }
    }

    /**
     * 获取本次采购单取消金额
     * @param int $id
     * @param string $purchase_number
     * @author harvin 2019-3-8
     * @return mixed
     * @throws Exception
     */
  public function get_cancel_amount_ben($id,$purchase_number){
      $cancel_detail = $this->purchase_db->select(
              'SUM(item_total_price) as product_money,'
              .'freight,'
              .'discount,'
              .'process_cost,'
              .'SUM(item_total_price) + freight - discount + process_cost AS real_price'
          )
          ->where('cancel_id', $id)
          ->where('purchase_number', $purchase_number)
          ->get('purchase_order_cancel_detail')
          ->row_array();
      if(empty($cancel_detail)){
          throw new Exception('取消未到货数量-子表不存在');
      }
      return $cancel_detail;
  }

    /**
     * 获取本次取消金额
     * @param int $id
     * @param string $purchase_number
     * @author harvin 2019-3-8
     * @return mixed
     */
    public function get_cancel_amount_ben_by_id($id){
        $cancel_amount_ben=0;
        $cancel_detail=  $this->purchase_db->select('sum(item_total_price)+freight-discount+process_cost as cancel_amount_ben')
            ->where('cancel_id',$id)
            ->get('purchase_order_cancel_detail')
            ->row_array();
        if(empty($cancel_detail)){
            throw new Exception('取消未到货数量-子表不存在');
        }

        $cancel_amount_ben = $cancel_detail['cancel_amount_ben'];
        return $cancel_amount_ben;
    }

    /**
     * 获取本次取消数量
     * @param int $id
     * @param string $purchase_number
     * @author harvin 2019-3-8
     * @return mixed
     */
    public function get_cancel_cqt_ben_by_id($id){
        $cancel_amount_ben=0;
        $cancel_info=  $this->purchase_db->select('cancel_ctq')
            ->where('id',$id)
            ->get('purchase_order_cancel')
            ->row_array();
        if(empty($cancel_info)){
            throw new Exception('取消未到货数量-主表不存在');
        }

        $cancel_amount_ben = $cancel_info['cancel_ctq'];
        return $cancel_amount_ben;
    }

   /**
    * 获取采购单来源
    * @param string $purchase_number
    * @author harvin 2019-3-8
    * @return mixed
    */
   public function get_order_source($purchase_number){
     $order=$this->purchase_db
             ->select('*')
             ->where('purchase_number',$purchase_number)
             ->get('purchase_order')
             ->row_array();
     if(empty($order)){
         throw new Exception('采购单不存在');
     }
     return $order;
   }

    /**
     * 更新采购单状态并推送仓库
     * @author harvin
     * @param mixed $order_status  订单状态
     * @param mixed $cqa 已取消数量
     * @param mixed $val  采购单号
     * @param mixed $confirm_amount 采购数量
     * @param mixed $id 取消未到货主表
     * @throws
     */
    public function canceled_status($order_status, $cqa, $val, $confirm_amount, $id, $instock_qty, $is_system=false){
        $this->load->model('purchase/purchase_order_model','',false,'purchase');
        if($cqa > $confirm_amount)throw new Exception('采购单取消数据异常【已取消数量大于采购数量】');

        $tc_status = false;
        $od_status = null;
        if($order_status == PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND){
            $tc_status = CANCEL_AUDIT_STATUS_CFYSK;
        }elseif($order_status == PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT && !$is_system){
            $tc_status = CANCEL_AUDIT_STATUS_SYSTEM;
        }elseif(in_array($order_status, [PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT, PURCHASE_ORDER_STATUS_CANCELED]) && $is_system){
            $tc_status = CANCEL_AUDIT_STATUS_SYSTEM;
            $od_status = $order_status;
        }else{
            throw new Exception('采购单状态不是处于作废中');
        }

        //变更取消未到货主表状态
        $cancel_update = $this->change_order_cancel($tc_status, $id);
        if (empty($cancel_update)) {
            throw new Exception('采购单-取消未到货数量-主表更新失败');
        }

        $order_update = $this->purchase_order_model->change_status($val, $od_status);
        if(empty($order_update)){
            throw new Exception('采购单更新失败');
        }
    }

    /**
     * 推送仓库信息
     * @param array $purchase_number 采购单号
     * @param int $id
     * @author harvin 2019-3-11
     * @return  
     */
    public function preservation_warehouse(array $purchase_number,$id){ 
        if(CG_ENV == 'dev') return true;

        $this->load->model('purchase_suggest/Purchase_suggest_map_model');
        //取消未到货明细
        $cancel_detail = $this->purchase_db
                ->select('*')->where('cancel_id', $id)
                ->where_in('purchase_number', $purchase_number)
                ->get('purchase_order_cancel_detail')
                ->result_array();
       
        if (empty($cancel_detail)) {
            throw new Exception('取消未到货明细 不存在');
        }
        //取消未到货主表
        $order_cancel = $this->purchase_db
                ->select('create_user_name,audit_user_name')
                ->where('id', $id)
                ->get('purchase_order_cancel')
                ->row_array();
        if (empty($order_cancel)) {
            throw new Exception('取消未到货主表 不存在');
        }
        //获取采购单信息
        $order = $this->purchase_db
                ->select('o.warehouse_code,o.transfer_warehouse,o.purchase_order_status,s.purchase_type_id,o.purchase_number,s.destination_warehouse')
                ->from('purchase_order as o')
                ->where_in('o.purchase_number', $purchase_number)
                ->join('purchase_suggest_map as b', 'o.purchase_number=b.purchase_number', 'left')
                ->join('purchase_suggest as s', 'b.demand_number=s.demand_number', 'left')
                ->get()
                ->result_array();
        if (empty($order)) {
            throw new Exception('采购主表不存在');
        }
     
        $warehouse_code= is_array($order)?array_column($order, 'warehouse_code','purchase_number'):[];
        $transit_warehouse=is_array($order)?array_column($order, 'transfer_warehouse','purchase_number'):[];
        $purchase_order_status=is_array($order)?array_column($order, 'purchase_order_status','purchase_number'):[];
        $purchase_type_id=is_array($order)?array_column($order, 'purchase_type_id','purchase_number'):[];
        $destination_warehouse=is_array($order)?array_column($order, 'destination_warehouse','purchase_number'):[];
        //查询需求单号
        $temp = [];
        $first = [];
        foreach ($purchase_number as $order_number) {
            $demand_number = $this->Purchase_suggest_map_model->get_one($order_number);
            if (!empty($demand_number)) {
                foreach ($demand_number as $row) {
                    $first[$row['purchase_number'] . '-' . $row['sku']] = $row['demand_number'];
                }
            }
        }

        $add_ids = [];
        foreach ($cancel_detail as $key => $value) {
            $temp['items_id'] = $value['items_id'];
            $temp['cancel_id'] = $value['cancel_id'];
            $temp['purchase_number'] = $value['purchase_number'];
            $temp['sku'] = $value['sku'];
            $temp['ctq'] = $value['cancel_ctq'];
            $temp['cancel_operator'] = $order_cancel['create_user_name'];
            $temp['type'] = WAREHOUSE_TYPE;
            $temp['check_operator'] = $order_cancel['audit_user_name'];
            $temp['status'] = isset($purchase_order_status[$value['purchase_number']])?$purchase_order_status[$value['purchase_number']]:'';
            $temp['demand_number'] = isset($first[$value['purchase_number'] . '-' . $value['sku']]) ? $first[$value['purchase_number'] . '-' . $value['sku']] : NULL;
            $temp['purchase_type'] = isset($purchase_type_id[$value['purchase_number']])?$purchase_type_id[$value['purchase_number']]:'';; // 
            $temp['create_time'] = date("Y-m-d H:i:s"); //

            if(in_array($temp['purchase_type'], [PURCHASE_TYPE_OVERSEA])){//海外仓的warehouse_code 取需求单目的仓的值
                $temp['warehouse_code'] = isset($destination_warehouse[$value['purchase_number']])?$destination_warehouse[$value['purchase_number']]:'';
                $temp['transit_warehouse'] =isset($warehouse_code[$value['purchase_number']])?$warehouse_code[$value['purchase_number']]:'';
            }else{
                $temp['warehouse_code'] = isset($warehouse_code[$value['purchase_number']])?$warehouse_code[$value['purchase_number']]:'';
                $temp['transit_warehouse'] = '';
            }

            $cancel_warehouse_insert = $this->purchase_db->insert('purchase_cancel_warehouse', $temp);
            if (empty($cancel_warehouse_insert)) {
                throw new Exception('保存推送仓库信息失败');
            }
            $insert_id = $this->purchase_db->insert_id('purchase_cancel_warehouse');
            array_push($add_ids,$insert_id);
        }
        unset($warehouse_code);
        unset($transit_warehouse);
        unset($purchase_order_status);
        unset($purchase_type_id);
        //获取待推送的数据
        $data = $this->Arrival_record_model->get_cancel_warehouse($add_ids);
        if (empty($data)) {
            throw new Exception('没有相关数据推送仓库');
        }
        $purchase = json_encode($data);
        $data_list = [
            'purchase' => $purchase,
        ];
        //推送仓库
        $url = getConfigItemByName('api_config', 'warehouse', 'warehouse_route');
        $res = getCancelCurlData($url, $data_list, 'POST');
        $api_log=[
         'record_number'=>$id,
         'api_url'=>$url,
         'record_type'=>'取消未到货推送仓库',
         'post_content'=>$purchase,
         'response_content'=>$res,
         'create_time'=>date('Y-m-d H:i:s')
         ];
        $this->purchase_db->insert('api_cancel_log',$api_log);    
        $reslut = json_decode($res, TRUE);
        if ($reslut['error'] == '1' && isset($reslut['success_list']) && !empty($reslut['success_list'])) {
            $this->rediss->lpushData('CANCEL',$id);
            $this->Arrival_record_model->update_cancel_warehouse($reslut['success_list'],TRUE);
        } else {
            foreach ($cancel_detail as $val) {
                $this->purchase_db
                        ->where('cancel_id', $val['cancel_id'])
                        ->where('purchase_number', $val['purchase_number'])
                        ->where('sku', $val['sku'])
                        ->update('purchase_cancel_warehouse', ['is_push' => 2, 'update_time' => date('Y-m-d H:i:s')]);
            }
            $message= isset($reslut['message'])?$reslut['message']:'';
            $fail_list=isset($reslut['fail_list'])?$reslut['fail_list']:'';
            $msg='';
            if (!empty($fail_list)) {
                foreach ($fail_list as $row_error) {
                    $msg .= $row_error['pur_number'] . "-" . $row_error['sku'] . $row_error['message'];
                }
            }
            throw new Exception('推送仓库失败,数据中心提示:'.$message.$msg);
        }
    }
      /**
     * 驳回推送仓库信息
     * @param int $id  取消未到货主表id
     * @author harvin 2019-3-11
     * @return  
     */   
      public function preservation_warehouse_reject($id){
        $data_list=$temp=[];
        $cancel_warehouse=$this->purchase_db
                ->select("*")
                ->where('cancel_id',$id)
                ->where('is_push',1)
                ->get('purchase_cancel_warehouse')
                ->result_array();
        if(empty($cancel_warehouse)){
            throw new Exception('驳回推送仓库数据不存在');
        }  
        //获取sku+po 采购数量
        $items_id_list= is_array($cancel_warehouse)?array_column($cancel_warehouse, 'items_id'):[];
        if(empty($items_id_list)){
             throw new Exception('未关联采购单明细');
        }
       $order_items= $this->purchase_db->select('confirm_amount,id')->where_in('id',$items_id_list)->get('purchase_order_items')->result_array();
       $confirm_amount_list= is_array($order_items)?array_column($order_items, 'confirm_amount','id'):[];
        foreach ($cancel_warehouse as $row) {
            $temp['id'] = $row['id'];
            $temp['cancel_id'] = $row['cancel_id'];
            $temp['pur_number'] = $row['purchase_number'];
            $temp['warehouse_code'] = $row['warehouse_code'];
            $temp['transit_warehouse'] = $row['transit_warehouse'];
            $temp['sku'] = $row['sku'];
            $temp['ctq'] = $row['ctq'];
            $temp['cancel_operator'] = $row['cancel_operator'];
            $temp['type'] = $row['type'];
            $temp['check_operator'] = $row['check_operator'];
            $temp['status'] = $row['status'];
            $temp['demand_number'] = !empty($row['demand_number'])?$row['demand_number']:NULL;
            $temp['purchase_type'] = $row['purchase_type'];
            $temp['demand_info']=[
                'sku'=>$row['sku'],
                'pur_number'=>$row['purchase_number'],
                'purchase_quantity'=> isset($confirm_amount_list[$row['items_id']])?$confirm_amount_list[$row['items_id']]:0,//采购数量
                'platform_number'=>'',//平台号
                'platform_id'=>$row['id'],//接收是传过来的id
                'transport_style'=>'',//运输方式
                'transit_number'=>$row['id'],//中转数量
                'create_time'=>date('Y-m-d H:i:s'),//
                'sales_note'=>$row['id'],//
                'level_audit_status'=>'2' ,//'审核状态(1是同意2是驳回3撤销)
                'is_purchase'=>'1',//是否已采购(1未采购2已采购)
                'transit_warehouse'=>$row['transit_warehouse'],//中转仓
                'product_category'=>'',//产品类别
                'demand_number'=>!empty($row['demand_number'])?$row['demand_number']:NULL,//备货单号
                'purchase_warehouse'=>$row['warehouse_code'],//采购仓
                'ship_code'=>'',//物流方式
                'create_id'=> getActiveUserName(),//创建人
            ];
            $data_list[]=$temp;
        }
        unset($confirm_amount_list);
        $purchase = json_encode($data_list);
        $data = [
            'purchase' => $purchase,
        ];
        //推送仓库
        $url = getConfigItemByName('api_config', 'warehouse', 'warehouse_reject');
        $res = getCurlData($url, $data, 'POST');    
        $reslut = json_decode($res, TRUE);   
        if ($reslut['error'] == '1' && isset($reslut['success_list']) && !empty($reslut['success_list'])) {
            $this->Arrival_record_model->update_cancel_warehouse($reslut['success_list'],FALSE);
        } else {  
            throw new Exception('驳回推送仓库失败');
        }
    }
       
       
    public function get_ware(){
         $temp['id'] = $row['id'];
            $temp['cancel_id'] = $row['cancel_id'];
            $temp['pur_number'] = $row['purchase_number'];
            $temp['warehouse_code'] = $row['warehouse_code'];
            $temp['transit_warehouse'] = $row['transit_warehouse'];
            $temp['sku'] = $row['sku'];
            $temp['ctq'] = $row['ctq'];
            $temp['cancel_operator'] = $row['cancel_operator'];
            $temp['type'] = $row['type'];
            $temp['check_operator'] = $row['check_operator'];
            $temp['status'] = $row['status'];
            $temp['demand_number'] = !empty($row['demand_number'])?$row['demand_number']:NULL;
            $temp['purchase_type'] = $row['purchase_type'];
            $data_list[]=$temp;
        
        
        
    }

    //将取消细节推送门户系统
    public function send_cancel_detail_to_provider($id)
    {
        //$purchase_number_list = [];
        $send_data = [];
        $query_builder = $this->purchase_db;
        //获取采购单明细表id
        $cancel_detail = $query_builder
            ->select('*')
            ->where('cancel_id', $id)
            ->get('purchase_order_cancel_detail')
            ->result_array();
        if (empty($cancel_detail)) {
            return ;
        }
     
        $purchase_number_arr  = array_unique(array_column($cancel_detail,'purchase_number'));

        $result =$query_builder->select('de.purchase_number,SUM(de.cancel_ctq) AS cancel_ctq,de.sku')
            ->where_in('de.purchase_number',$purchase_number_arr)
            ->where_in('pc.audit_status ',[CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC])
            ->from('pur_purchase_order_cancel_detail as de')
            ->join('pur_purchase_order_cancel as pc', 'pc.id=de.cancel_id', 'INNER')
            ->group_by('de.sku,de.purchase_number')
            ->get()
            ->result_array();

        if (is_array($result)&&count($result)>0){
            foreach ($result as $detail) {
                $temp=[];
                $temp['purchaseNumber'] = $detail['purchase_number'];
                $temp['sku'] = $detail['sku'];
                $temp['cancelCtq'] = $detail['cancel_ctq'];
                $send_data['data'][]=$temp;

            }

/*
        foreach ($cancel_detail as $detail) {
            $temp=[];
            $temp['purchaseNumber'] = $detail['purchase_number'];
            $temp['sku'] = $detail['sku'];
            $temp['cancelCtq'] = $detail['cancel_ctq'];
            $send_data['data'][]=$temp;
            $purchase_number_list[] = $detail['purchase_number'];

        }*/
        $url = SMC_JAVA_API_URL.'/provider/purPush/pushCancelCtq';
        $header = array('Content-Type: application/json');
        $access_taken = getOASystemAccessToken();
        $url = $url . "?access_token=" . $access_taken;
        $send_res = getCurlData($url, json_encode($send_data, JSON_UNESCAPED_UNICODE), 'post', $header);
        $send_res = json_decode($send_res,true);

        }

        $this->supplier_joint_model->RecordGateWayPush($send_res,$purchase_number_arr,$send_data,'SendCancelDetailToProvider');

    }
}