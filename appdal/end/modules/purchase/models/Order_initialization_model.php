<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Order_initialization_model extends Purchase_model {
    
      protected $table_name   = 'purchase_order';
      protected $table_items='purchase_order_items';
      public function __construct(){
           parent::__construct();
           $this->load->model('supplier/Supplier_model');
           $this->load->model('product/Product_model'); // 产品信息
       }
    
      /**
       * 驳回采购单 初始化
       * @author harvin 2019-4-26
       * @param type $purchase_number
       * @return boolean
       * @throws Exception
       */
       public function order_initialization($purchase_number){
            //初始化采购单主表
            $order= $this->purchase_db
                    ->select('supplier_code,purchase_type_id,supplier_name,is_drawback')
                    ->where('purchase_number',$purchase_number)
                    ->get($this->table_name)
                    ->row_array();  
            if(empty($order)){
                throw new Exception('采购单号'.$purchase_number.'不存在'); 
            }
//            $supplier_info = $this->Supplier_model->get_supplier_info($order['supplier_code']); // 供应商信息
            $supplier_buyer_info = $this->supplier_buyer_model->get_buyer_one($order['supplier_code'],$order['purchase_type_id']);
            $supplier_buyer_id = isset($supplier_buyer_info['buyer_id'])?$supplier_buyer_info['buyer_id']:0;
            $supplier_buyer_name = isset($supplier_buyer_info['buyer_name'])?$supplier_buyer_info['buyer_name']:'';
            $new_order['purchase_number'] = $purchase_number;
            // 供应商相关信息
            //$new_order['supplier_code'] = $order['supplier_code'];
            //$new_order['supplier_name'] = $order['supplier_name']; // 供应商名字
            //$new_order['account_type'] = $supplier_info['supplier_settlement']; // 供应商结算方式
            //$new_order['pay_type'] = !empty($supplier_info['payment_method']) ? $supplier_info['payment_method'] : PURCHASE_PAY_TYPE_ALIPAY; // 供应商支付方式
            //$new_order['shipping_method_id'] = !empty($supplier_info['shipping_method_id']) ? $supplier_info['shipping_method_id'] : 2; // 供应商运输1:自提,2:快递,3:物流,4:送货
            //$new_order['is_cross_border'] = !empty($supplier_info['is_cross_border']) ? $supplier_info['is_cross_border'] : 0; // 是否跨境宝 0.否
            // 采购员 跟单员 创建人
            $new_order['buyer_id'] = $supplier_buyer_id;// 采购员ID
            $new_order['buyer_name'] = $supplier_buyer_name; // 采购人名称

           $this->load->model('purchase/Purchase_order_model');
           $this->Purchase_order_model->change_status($purchase_number,PURCHASE_ORDER_STATUS_WAITING_QUOTE); // 统一入口修改采购单状态

            $this->purchase_db->where('purchase_number',$purchase_number)->update($this->table_name,$new_order);
            //初始化采购明细表
            $order_items=$this->purchase_db
                ->select('*')
                ->where('purchase_number',$purchase_number)
                ->get($this->table_items)
                ->result_array();

            if(empty($order_items)){
                  throw new Exception('采购单明细不存在'); 
            }
            foreach ($order_items as $value_item) {
                  $sku_info = $this->Product_model->get_product_info($value_item['sku']);  
                  if(empty($sku_info)){
                       throw new Exception('产品sku,不存在'); 
                  }
                  /*if($sku_info['supplier_code'] != $order['supplier_code']){
                      $new_order = [];
                      $supplier_buyer_info = $this->supplier_buyer_model->get_buyer_one($sku_info['supplier_code'],$order['purchase_type_id']);
                      //$new_order['supplier_code'] = $sku_info['supplier_code'];
                      //$new_order['supplier_name'] = $sku_info['supplier_name'];
                      $new_order['buyer_id'] = isset($supplier_buyer_info['buyer_id'])?$supplier_buyer_info['buyer_id']:0;
                      $new_order['buyer_name'] = isset($supplier_buyer_info['buyer_name'])?$supplier_buyer_info['buyer_name']:'';
                      $this->purchase_db->where('purchase_number',$purchase_number)->update($this->table_name,$new_order);
                  }*/
                  if($order['is_drawback']==PURCHASE_IS_DRAWBACK_Y){
                      $purchase_unit_price = format_two_point_price($sku_info['purchase_price'] * (1 + $sku_info['ticketed_point'] / 100)); // 含税价
                  }else{
                      $purchase_unit_price = format_two_point_price($sku_info['purchase_price']);
                  }                  
                  //修改采购单明细
                $item_insert_data = [
                    'purchase_unit_price' => format_two_point_price($purchase_unit_price), // 含税单价
                    'pur_ticketed_point' => isset($sku_info['ticketed_point']) ? $sku_info['ticketed_point'] : 0,
                    'product_base_price' => format_two_point_price(isset($sku_info['purchase_price'])?$sku_info['purchase_price']:0),  // 不含税单价
                ];
                $this->purchase_db->where('purchase_number',$purchase_number)->where('sku',$value_item['sku'])->update($this->table_items,$item_insert_data); 
            }
//           $order_pay_type=$this->purchase_db->where('purchase_number',$purchase_number)->get('purchase_order_pay_type')->row_array();
//            删除采购单信息确认-请款金额相关信息
//           if(!empty($order_pay_type)){
//               $this->purchase_db->where('purchase_number',$purchase_number)->delete('purchase_order_pay_type');
//           }
           return true;
       }
       
       
       
}