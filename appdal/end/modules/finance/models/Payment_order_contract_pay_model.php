<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Payment_order_contract_pay_model extends Purchase_model
{
    protected $table_name = 'purchase_order_pay';
    public function __construct()
    {
        parent::__construct();
        $this->load->model('finance/Payment_order_pay_model');
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');
        $this->load->helper('status_finance');
        $this->load->model('system/Bank_card_model', 'bankCart');
        $this->load->model('supplier/Supplier_payment_info_model');
    }
    /**
     * 审核显示(批量)
     * @param $ids 参数id
     * @author harvin 2019-4-4
     * @return  array
     */
    public function get_audit_info($ids,$status=PAY_WAITING_FINANCE_REVIEW){
        $this->load->model('supplier/Supplier_model');
        $data_status=$data=[];
        //转化数据
       $id= explode(',', $ids);  
        if(empty($id)){
            throw new Exception('参数id,不合法');
        }   
       //判断请款单 是否是待财务审核状态 及合同来源
             $order_pay=  $this->purchase_db
               ->select('id,pay_status,source,requisition_number,pur_number,pay_price,application_time,supplier_code,pay_ratio,freight,settlement_method')
               ->where_in('id',$id)
               ->get($this->table_name)
               ->result_array();
        if(empty($order_pay)){
            throw new Exception('请款单不存在');
        }
        foreach ($order_pay as $key =>$val) {
           $order_pay[$key]['supplier_name'] =$this->Supplier_model->getSupplierNameBySupplierCode($val['supplier_code']);
           $order_pay[$key]['settlement_method']=$val['settlement_method']=='20'?'是':"否";
            if($val['source']==SOURCE_NETWORK_ORDER){
                $data[]=$val['requisition_number'];
            }
            if($val['pay_status']!=$status){
                 $data_status[]=$val['requisition_number'].'是'. getPayStatus($val['pay_status']);
            } 
             $order_pay[$key]['pay_status'] = getPayStatus($val['pay_status']);
        }
        if(!empty($data)){
            $error_msg="请款单号". implode(',', $data).'不是合同请款单';
            throw new Exception($error_msg);
        }
        if(!empty($data_status)){
             $error_msg="请款单号". implode(',', $data_status);
            throw new Exception($error_msg);
        }
        return $order_pay;
    }
    /**
     * 财务审核及驳回操作
     * @author harvin 2019-4-4
     * @param int $ids 参数
     * @param string $review_notice  审核备注
     * @param int $type 1 审核通过 2 审核驳回
     * @return mixed
     * @throws Exception
     */
     public function get_contract_order_save($ids, $review_notice, $type,$status=PAY_WAITING_FINANCE_REVIEW) {
        $data=[];
        $ids = explode(',', $ids);
        if (empty($ids) && !is_array($ids)) {  
             return ['msg'=>'参数id,请求格式不正确','bool'=>false];  
        }
        foreach ($ids as $id) {
           $temp=$this->Payment_order_pay_model->get_contract_order_save($id, $review_notice, $type,$status);
           if(empty($temp['bool'])){
               $data[]=$temp['msg'];
           }
        }  
        if(empty($data)){
            return ['msg'=>'操作成功','bool'=>true];
        }else{
            $msg_error= implode(',', $data);
            return ['msg'=>$msg_error,'bool'=>false];  
        }   
    }
    /**.
     * 判断是否同一个供应商,结算方式，支付方式
     * 结算比例 是否退税 运费支付 是否一样 
     * @author harvin 2019-4-4
     * @param array $ids
     * @param $type  false 线下支付  TRUE 富友线上支付
     * @param  $source 1 合同单  2 网采单
     * @param  $platform_type 支付平台类型
     * @return boolean
     * @throws Exception
     */
    public function get_supplier_ststus($ids,$type=false,$source=SOURCE_COMPACT_ORDER,$platform_type = null){
        $data_status=[];
        $order_pay = $this->purchase_db
                ->select('supplier_code,settlement_method,pay_type,pur_number,pay_status,requisition_number')
                ->where_in('id', $ids)
                ->get($this->table_name)
                ->result_array();
        if (empty($order_pay)){
            throw new Exception('参数id,不存在');
        }
         foreach ($order_pay as $key =>$val) {
             if($platform_type == 'cebbank' and $val['pay_type'] != PURCHASE_PAY_TYPE_PUBLIC){
                 $error_msg = "请款单号:". $val['requisition_number'].'只有线下境内才可点击';
                 throw new Exception($error_msg);
             }
            if($val['pay_status']!=PAY_WAITING_FINANCE_PAID){
                 $data_status[]=$val['requisition_number'];
            } 
        }
         if(!empty($data_status)){
             $error_msg="请款单号". implode(',', $data_status).'不是待财务付款状态';
            throw new Exception($error_msg);
        }
        $supplier_code_list = array_column($order_pay, 'supplier_code');
        //去除重复值
        $supplier_code = array_unique($supplier_code_list);
        if (count($supplier_code) > 1) {
            throw new Exception('请选择相同供应商');
        }
        $settlement_method_list = array_column($order_pay, 'settlement_method');
        $settlement_method = array_unique($settlement_method_list);
        if (count($settlement_method) > 1) {
            throw new Exception('请选择相同结算方式');
        }
        $pay_type_list = array_column($order_pay, 'pay_type');
        $pay_type = array_unique($pay_type_list);
        if (count($pay_type) > 1) {
            throw new Exception('请选择相同支付方式');
        }
        //判断是否是线上富友支付
        if($type){
          $payment_platform= $this->purchase_db
                    ->select('payment_platform')
                    ->where('supplier_code',$supplier_code[0])
                    ->where('payment_method',$pay_type[0])
                    ->get('supplier_payment_info')
                    ->row_array();
        if($payment_platform['payment_platform']!=SUPPLIER_PAY_PLATFORM_6){
            throw new Exception('该供应商的支付方式,不支持富友或宝付支付方式');
        }              
       }
       if($source==SOURCE_NETWORK_ORDER){
          //网采单   
        $compact_number_arr= array_column($order_pay, 'pur_number');
        //去除相同采购单号
        $purchase_order_arr= array_unique($compact_number_arr);   
      
       }else{
         //获取合同号
        $compact_number_arr= array_column($order_pay, 'pur_number');
        //去除相同合同号
        $compact_number= array_unique($compact_number_arr);
        //获取采购单号
       $purchase_order_arr=  $this->get_purchase_order($compact_number);    
       }
       //判断是否退税一致
       $purchase_order=$this->purchase_db
                ->select('is_drawback')
                ->where_in('purchase_number',$purchase_order_arr)
                ->get('purchase_order')
                ->result_array();
        if(empty($purchase_order)){
          throw new Exception('采购单不存在');  
        }
        $is_drawback_list = array_column($purchase_order, 'is_drawback');   
        $drawback_list= array_unique($is_drawback_list);
        if (count($drawback_list) > 1) {
            throw new Exception('退税不一致,请选择相同退税');
        }
        //判断是否结算比例 运费支付
        $order_pay_type=$this->purchase_db
                ->select('is_freight,settlement_ratio')
                ->where_in('purchase_number',$purchase_order_arr)
                ->get('purchase_order_pay_type')
                ->result_array();
        if(empty($order_pay_type)){
            throw new Exception('采购单确认金额数据不存在');
        }
//        $is_freight_list = array_column($order_pay_type, 'is_freight');
//        $is_freight= array_unique($is_freight_list);
//        if (count($is_freight) > 1) {
//            throw new Exception('请选择相同的运费方式');
//        }
//        $settlement_ratio_list = array_column($order_pay_type, 'settlement_ratio');
//        $settlement_ratio= array_unique($settlement_ratio_list);
//        if (count($settlement_ratio) > 1) {
//            throw new Exception('请选择相同的结算比例');
//        }
        return TRUE;
    }
    /**
     * 线下支付--确认页
     * @author harvin 2019-4-7
     * @param array $ids
     * @return array
     * @throws Exception
     */
    public function get_offline_payment(array $ids){
        $data=[];
        if(empty($ids)){
            throw new Exception('参数不能为空');
        }
      //获取供应商基本信息
       $data_system= $this->get_supplier($ids[0]);

       if(empty($data_system)){
           throw new Exception('供应商基本信息,不存在');
       }
       //付款信息
       $order_pay = $this->purchase_db
                ->select('id,pur_number,requisition_number,pay_status,pay_price,freight,discount,settlement_method,purchase_account'
                        . ',pay_type,pay_category,application_time,payer_time,'
                        . 'applicant,create_notice,supplier_code,js_ratio,pay_account,pay_number,pay_branch_bank,payment_notice')
                ->where_in('id', $ids)
                ->get($this->table_name)
                ->result_array();
       if(empty($order_pay)){
           throw new Exception('付款信息不存在');
       }

        $requisition_number_list = array_column($order_pay,'requisition_number');
        $application_time_list = array_column($order_pay,'application_time');
        $payer_time_list = array_column($order_pay,'payer_time');
        $pay_detail = $this->purchase_db->select('C.is_freight,A.requisition_number,A.purchase_number,A.check_status,A.is_special')
            ->from('purchase_order_pay_detail as A')
            ->join('purchase_order_pay_type as C', "C.purchase_number=A.purchase_number", 'left')
            ->where_in('A.requisition_number', $requisition_number_list)
            ->get()->result_array();
        // 所有的运费支付方式
        $is_freight_list = array_unique(array_column($pay_detail, 'is_freight'));
        $freight_string  = '';
        foreach($is_freight_list as $fr_value){
            $freight_string .= getFreightPayment($fr_value).'/';
        }

        foreach ($pay_detail as $value){
            if($value['is_special']!=1 and $value['check_status']==11){
                $errer = '请款单号:'.$value['requisition_number'].'中的'.$value['purchase_number'].'验货不合格，不允许付款，只能驳回!';
                throw new Exception($errer);
            }
        }

        $freight_string            = trim($freight_string, '/');
        $data_system['is_freight'] = $freight_string;

        $bank = [];
        //收款方信息
        $payment_linkage = $this->Supplier_payment_info_model->check_payment_info($data_system['supplier_code'],$data_system['is_tax'],$data_system['purchase_type_id']);
        $payment_linkage['payment_platform']= isset($payment_linkage['payment_platform'])?get_supplier_payment_platform($payment_linkage['payment_platform']):'';
        //获取本申请金额
        $total_pay_price = 0;
        foreach ($order_pay as $key => $row) {
            $data[$key]['id'] = $row['id'];
            $data[$key]['pur_number'] = $row['pur_number'];
            $data[$key]['requisition_number'] = $row['requisition_number'];
            $data[$key]['pay_price'] = $row['pay_price'];
            $data[$key]['freight'] = $row['freight'];
            $data[$key]['discount'] = $row['discount'];
            $data[$key]['settlement_method'] = $this->Payment_order_pay_model->get_settlement_method($row['settlement_method']); //结算方式
            $data[$key]['js_ratio'] = $row['js_ratio']; //结算比例
            $data[$key]['pay_type'] = getPayType($row['pay_type']);
            $data[$key]['pay_category'] = !empty($row['pay_category'])?getPayCategory($row['pay_category']):'';
            $data[$key]['applicant'] =  get_buyer_name($row['applicant']);
            $data[$key]['create_notice'] = $row['create_notice'];
            $total_pay_price += $row['pay_price'];

            if($row['pay_status'] == PAY_WAITING_FINANCE_PAID){
                $bankInfo = $this->Payment_order_pay_model->get_payment_bank('跨境宝2.0版-yibaisuperbuyers');
                $bank['account_short'] = isset($bankInfo['account_short'])?$bankInfo['account_short']:'';
            }else{// 非付款状态下查询展示信息
                $bankInfo = $this->Payment_order_pay_model->get_payment_bank($row['pay_account']);
                $bank['account_short'] = isset($bankInfo['account_short'])?$bankInfo['account_short']:'';
            }
        }

        $min_pay_time = max($application_time_list);// 最早付款时间
        $default_pay_time = min($payer_time_list);
        $default_pay_time = ($default_pay_time == '0000-00-00 00:00:00')?date('Y-m-d H:i:s'):$default_pay_time;// 默认付款时间

          $data_list = [
              'supplier_basic' => $data_system,
              'payment_info' => $data,
              'payment_linkage'=>$payment_linkage,
              'total_pay_price' => $total_pay_price,
              'bank'=>$bank,
              'pay_type' => getPayType(),
              'account_short' => $this->Payment_order_pay_model->get_bank(),
              'min_pay_time' => $min_pay_time,
              'default_pay_time' => $default_pay_time
          ];
        return $data_list;
    }
    /**
     * 获取采购单号（从合同单明细 或 对账单明细中获取）
     * @author harvin 
     * @param array $pur_number_arr
     * @return array
     * @throws Exception
     */
    public function get_purchase_order(array $pur_number_arr ){
        // 合同单明细采购单号
        $purchase_number_arr_1 = $this->purchase_db
                ->select('purchase_number')
                ->where_in('compact_number',$pur_number_arr)
                ->get('purchase_compact_items')
                ->result_array();

        // 对账单明细中获取采购单号
        $purchase_number_arr_2 = $this->purchase_db
            ->select('purchase_number')
            ->where_in('statement_number',$pur_number_arr)
            ->get('purchase_statement_items')
            ->result_array();
        $purchase_number_arr_1 = array_column($purchase_number_arr_1, 'purchase_number');
        $purchase_number_arr_2 = array_column($purchase_number_arr_2, 'purchase_number');

        // 去除相同的元素
        $purchase_number_arr = array_unique(array_merge($purchase_number_arr_1,$purchase_number_arr_2));

        if(empty($purchase_number_arr)){
            throw new Exception('合同单或对账单不存在绑定采购单关系');
        }
        return $purchase_number_arr;
    }
    /**
     * 获取供应商基本信息
     * @author harvin
     * @param type $id
     * @return array
     * @throws Exception
     */
    public function get_supplier($id){
          $order_pay = $this->purchase_db
                ->select('id,supplier_code,pay_type,pur_number,requisition_number,js_ratio,settlement_method,pay_price')
                ->where('id', $id)
//                ->where('source', SOURCE_COMPACT_ORDER)
                ->get('purchase_order_pay')
                ->row_array();
        if (empty($order_pay)) {
            throw new Exception('参数id,不存在');
        }
        //获取采购单单号
        $order_pay_detail = $this->purchase_db
                ->select('purchase_number')
                ->where('requisition_number', $order_pay['requisition_number'])
                ->get('purchase_order_pay_detail')
                ->result_array();
        if (empty($order_pay_detail)) {
            throw new Exception('请款明细表不存在');
        }
        $purchase_number = array_column($order_pay_detail, 'purchase_number');
        //判断改单是否是退税 (默认 0.否,1.退税)
        $purchase_order = $this->purchase_db
                ->select('is_drawback,purchase_type_id')
                ->where_in('purchase_number', $purchase_number)
//                ->where('source', SOURCE_COMPACT_ORDER)
                ->get('purchase_order')
                ->row_array();
        if (empty($purchase_order)) {
            throw new Exception('请款单明细为绑定采购单');
        }
        //获取运费支付方式
        $freight = $this->purchase_db->select('is_freight')
                ->where_in('purchase_number', $purchase_number)
                ->get('purchase_order_pay_type')
                ->row_array();
        if (empty($freight)) {
            throw new Exception('purchase_order_pay_type 记录表不存在');
        }
        //供应商
        $supplier_list = $this->Payment_order_pay_model->get_supplier_name([$order_pay['supplier_code']]);
        $supplier_name = isset($supplier_list[$order_pay['supplier_code']]) ? $supplier_list[$order_pay['supplier_code']] : '';
        $this->load->helper();
        $data_system = [
            'id' => $order_pay['id'],
            'supplier_name' => $supplier_name, //供应商
            'supplier_code' => $order_pay['supplier_code'], //供应商编码
            'settlement_method' => $this->Payment_order_pay_model->get_settlement_method($order_pay['settlement_method']), //结算方式
            'payment_method' => getPayType($order_pay['pay_type']), //支付方式
            'is_drawback' => isset($purchase_order['is_drawback'])?getIsDrawbackShow($purchase_order['is_drawback']):'',
            'purchase_type_id' => $purchase_order['purchase_type_id'],
            'is_tax' => $purchase_order['is_drawback'],
            'js_ratio' => '', //结算比例
            'is_freight' => '', //运费支付
        ];
        return $data_system;
    }
    /**
     * 保存线下支付（合同单）
     * @author harvin 2019-4-7
     * @param string $ids
     * @param string $payer_time
     * @param string $account_short
     * @param string $pay_type
     * @param string $supplier_code
     * @param array $images
     * @param array $pay_price
     * @param string $remarks
     * @return array
     * @throws Exception
     */
    public function get_offline_payment_save($ids,$payer_time,$account_short,$pay_type,$supplier_code,$images,$pay_price,$remarks){

        $this->load->model('Reject_note_model');
        $this->load->model('finance/purchase_order_pay_model');
        $query = $this->purchase_db;
        try{
            //转化数组
            $ids = explode(',', $ids);
            if(!is_array($ids) || empty($ids)){
                throw new Exception('参数id,不合法');
            }
            //获取付款账号信息
            $payment = $this->Payment_order_pay_model->get_payment_bank($account_short);
            if(empty($payment)){
                throw new Exception('付款账号信息不存在');
            }

            $purchase_number_list = [];
            $query->trans_begin();
            foreach($ids as $id){
                //获取请款单
                $order_pay = $query->select(
                    'pop.pay_status,pop.pur_number, pop.product_money, pop.freight, pop.discount,'
                    .'pop.requisition_number, pop.pay_category, pop.source,pop.source_subject, pop.pay_type,'
                    .'po.is_drawback, po.purchase_type_id')
                    ->from('purchase_order_pay pop')
                    ->join('purchase_order po', 'pop.pur_number = po.purchase_number', 'left')
                    ->where('pop.id', $id)
                    ->get()
                    ->row_array();
                if(empty($order_pay)){
                    throw new Exception('请款单不存在');
                }
                $supplier_payment_info = $this->Supplier_payment_info_model->check_payment_info($supplier_code, $order_pay['is_drawback'], $order_pay['purchase_type_id']);

                if($order_pay['pay_status'] == PAY_WAITING_FINANCE_PAID){
                    //记录收款方信息
                    $order_pay_water = [
                        'supplier_code'              => $supplier_code,
                        'pur_number'                 => $order_pay['pur_number'],
                        'billing_object_type'        => 1,
                        'transaction_number'         => '',
                        'is_bill'                    => 2,
                        'price'                      => $pay_price[$id],
                        'original_price'             => $pay_price[$id],
                        'original_currency'          => 'RMB',
                        'remarks'                    => $order_pay['source'] == SOURCE_COMPACT_ORDER ? '合同单线下支付' : '网采单线下支付',
                        'create_id'                  => getActiveUserId(),
                        'create_name'                => getActiveUserName(),
                        'create_time'                => date('Y-m-d H:i:s'),
                        'beneficiary_payment_method' => $pay_type,
                        'beneficiary_branch'         => isset($supplier_payment_info['payment_platform_branch']) ? $supplier_payment_info['payment_platform_branch'] : '',
                        'beneficiary_account'        => isset($supplier_payment_info['account']) ? $supplier_payment_info['account'] : '',
                        'beneficiary_account_name'   => isset($supplier_payment_info['account_name']) ? $supplier_payment_info['account_name'] : '',
                        'our_branch'                 => isset($payment['branch']) ? $payment['branch'] : '',
                        'our_account_abbreviation'   => $account_short,
                        'our_account_holder'         => isset($payment['account_holder']) ? $payment['account_holder'] : '',
                        'pay_time'                   => $payer_time,
                    ];
                    $this->purchase_db->insert('purchase_order_pay_water', $order_pay_water);
                    $data = [
                        'pay_status'        => PAY_PAID,
                        'real_pay_price'    => $pay_price[$id], //申请金额
                        'images'            => isset($images[$id]) ? $images[$id] : '',
                        'pay_account'       => isset($payment['account_short']) ? $payment['account_short'] : '',
                        'pay_number'        => isset($payment['account_holder']) ? $payment['account_holder'] : '',
                        'pay_branch_bank'   => isset($payment['branch']) ? $payment['branch'] : '',
                        'k3_account'        => isset($payment['k3_bank_account']) ? $payment['k3_bank_account'] : '',
                        'payer_time'        => $payer_time,
                        'payer_id'          => getActiveUserId(),
                        'payer_name'        => getActiveUserName(),
                        'payment_notice'    => $remarks,
                        'approver'          => getActiveUserId(),
                        'processing_time'   => date('Y-m-d H:i:s'),
                        'processing_notice' => make_semiangle($remarks),
                    ];
                    //更新请款单状态
                    $this->purchase_db->where('id', $id)->update('purchase_order_pay', $data);

                    if(stripos($order_pay['pur_number'], '-HT') !== false or $order_pay['source'] == SOURCE_COMPACT_ORDER){ //合同请款
                        if($order_pay['source_subject'] == SOURCE_SUBJECT_COMPACT_ORDER){// 合同单请款
                            //获取合同单总金额
                            $compact = $query->select('real_money,compact_number')
                                ->where('compact_number', $order_pay['pur_number'])
                                ->get('purchase_compact')
                                ->row_array();
                            if(empty($compact)){
                                throw new Exception('合同单不存在');
                            }

                            $this->load->model('compact/Compact_model');
                            $result = $this->Compact_model->calculate_compact_related_amount($compact['compact_number']);
                            if(!$result['code']){
                                throw new Exception($result['msg']);
                            }

                            //采购单商品总额
                            $product_money              = isset($result['data']['product_money'])?$result['data']['product_money']:0; //总商品额
                            $paid_total_product_money   = isset($result['data']['paid_total_product_money'])?$result['data']['paid_total_product_money']:0; //已取消金额
                            $cancel_total_real_money    = isset($result['data']['cancel_total_real_money'])?$result['data']['cancel_total_real_money']:0; //已取消金额
                            $has_pay                    = $paid_total_product_money + $pay_price[$id];

                            $pay_status = PAY_PAID;
                            if( $product_money - $cancel_total_real_money - $has_pay > IS_ALLOWABLE_ERROR){// 误差超过 1元是已部分付款，小于1元是 已付款
                                //该采购单付款状态 为部分付款
                                $pay_status = PART_PAID;
                            }
                            $result = $this->Compact_model->change_compact_pay_status($order_pay['pur_number'], $pay_status, $payer_time);
                            if(!$result['code']){
                                throw new Exception($result['msg']);
                            }
                        }elseif($order_pay['source_subject'] == SOURCE_SUBJECT_STATEMENT_ORDER){// 对账单请款
                            $this->load->model('statement/Purchase_statement_model');// 根据对账单查找
                            $this->Purchase_statement_model->change_statement_pay_status($order_pay['pur_number'], PAY_PAID, $payer_time);
                        }else{
                            throw new Exception('请款单来源主体错误');
                        }
                        //记录操作日志
                        $log = [
                            'record_number'  => $order_pay['pur_number'],
                            'record_type'    => 'PUR_PURCHASE_ORDER_PAY',
                            'content'        => '财务付款',
                            'content_detail' => '请款单号'.$order_pay['requisition_number'].'财务付款金额:'.$pay_price[$id],
                        ];
                        $this->Reject_note_model->get_insert_log($log);
                    }elseif($order_pay['source'] == SOURCE_NETWORK_ORDER){ //网采单
                        $this->purchase_db
                            ->where('purchase_number', $order_pay['pur_number'])
                            ->update('purchase_order', ['pay_status' => PAY_PAID, 'pay_time' => $payer_time]);

                        $purchase_number_list[] = $order_pay['pur_number'];

                        //记录操作日志
                        $log = [
                            'record_number'  => $order_pay['requisition_number'],
                            'record_type'    => 'PUR_PURCHASE_ORDER_PAY',
                            'content'        => '财务付款',
                            'content_detail' => '请款单号'.$order_pay['requisition_number'].'财务付款金额:'.$pay_price[$id],
                        ];
                        $this->Reject_note_model->get_insert_log($log);

                        //记录操作日志
                        $log_order = [
                            'record_number'  => $order_pay['pur_number'],
                            'record_type'    => 'PUR_PURCHASE_ORDER',
                            'content'        => '财务付款',
                            'content_detail' => '采购单号'.$order_pay['pur_number'].'由【待财务付款】变为【已付款】',
                        ];
                        $this->Reject_note_model->get_insert_log($log_order);

                        $this->purchase_order_pay_model->push_purchase_order_pay_status($order_pay['pur_number']);// 推送采购单付款状态
                    }else{
                        throw new Exception('请款类型不存在');
                    }
                }else{
                    // 更新银行支付信息
                    $data = [
                        'pay_account'       => isset($payment['account_short']) ? $payment['account_short'] : '',
                        'pay_number'        => isset($payment['account_holder']) ? $payment['account_holder'] : '',
                        'pay_branch_bank'   => isset($payment['branch']) ? $payment['branch'] : '',
                        'k3_account'        => isset($payment['k3_bank_account']) ? $payment['k3_bank_account'] : '',
                        'payment_notice'    => $remarks,
                        'processing_notice' => make_semiangle($remarks),
                        'is_update_for_summary' => 2,// 2.已更新
                    ];
                    //更新请款单状态
                    $this->purchase_db->where('id', $id)->update('purchase_order_pay', $data);
                }
            }

            if($purchase_number_list){
                // 采购单状态变更需要刷新冲销汇总
                $this->load->library('Rabbitmq');
                $mq = new Rabbitmq();//创建消息队列对象
                $mq->setQueueName('STATEMENT_CHANGE_PAY_STATUS_REFRESH');//设置参数
                $mq->setExchangeName('STATEMENT_CHANGE_PAY_STATUS');//构造存入数据
                $mq->setRouteKey('SO_REFRESH_FOR_003');
                $mq->setType(AMQP_EX_TYPE_DIRECT);
                $mq->sendMessage(['purchase_number' => $purchase_number_list,'add_time' => time()]);// 保持格式一致
            }

            if($query->trans_status() === false){
                $query->trans_rollback();
                return ['msg' => '保存失败', 'bool' => false];
            }else{
                $query->trans_commit();
                return ['msg' => '保存成功', 'bool' => true];
            }
        }catch(Exception $exc){
            return ['msg' => $exc->getMessage(), 'bool' => false];
        }
    }
    /**
     * 线上支付驳回
     * @author harvin 2019-4-8
     * @param array $ids
     * @return type
     * @throws Exception
     */
    public function get_offline_payment_reject(array $ids,$reject_notice='财务付款驳回'){
        $this->load->model('Reject_note_model');
        $this->load->model('finance/purchase_order_pay_model');
        $query = $this->purchase_db;
        try {
            //开始事物
            $query->trans_begin();
            foreach ($ids as $id) {
                $order_pay = $this->purchase_db
                        ->select('requisition_number,pur_number,source,pay_category,pay_status')
                        ->where('id', $id)
                        ->get('purchase_order_pay')
                        ->row_array();
                if (empty($order_pay)) {
                    throw new Exception('参数id,不存在');
                }
                if ($order_pay['pay_status'] !== PAY_WAITING_FINANCE_PAID) {
                    throw new Exception('请款单号 '.$order_pay['requisition_number'].' 非待财务付款状态');
                }
                $data = [
                    'pay_status' => PAY_FINANCE_REJECT,
                    'approver' => getActiveUserId(),
                    'processing_notice' => $reject_notice,
                    'payment_notice' => $reject_notice,
                    'processing_time' => date('Y-m-d H:i:s'),
                    'payer_id'      => getActiveUserId(),
                    'payer_name'    => getActiveUserName(),
                ];
                 $temp = [
                       'pay_status' => PAY_FINANCE_REJECT,
                   ];
                //记录操作日志
                $log = [
                    'record_number' => $order_pay['pur_number'],
                    'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                    'content' => '财务审核驳回',
                    'content_detail' => '请款单号' . $order_pay['requisition_number'] . '财务审核驳回'
                ];
                //更新请款单状态
                $this->purchase_db->where('id', $id)->update('purchase_order_pay', $data);
              //   if ($order_pay['pay_category'] != PURCHASE_PAY_CATEGORY_4) { //请款类型 不是 运费请款
                    //更新采购单付款状态 更新采购单付款状态
                    if ($order_pay['source'] == SOURCE_NETWORK_ORDER) {
                        //更新采购单付款状态
                        $this->purchase_db->where_in('purchase_number', $order_pay['pur_number'])->update('purchase_order', $temp);
                        $this->purchase_order_pay_model->push_purchase_order_pay_status($order_pay['pur_number']);// 推送采购单付款状态
                    } elseif ($order_pay['source'] == SOURCE_COMPACT_ORDER) {
                        $this->load->model('compact/Compact_model');
                        $result = $this->Compact_model->change_compact_pay_status($order_pay['pur_number'], $temp['pay_status']);
                        if (!$result['code']) {
                            throw new Exception($result['msg']);
                        }
                    } else {
                        throw new Exception('请款类型不存在');
                    }
           //     }
                //记录操作日志
                $this->Reject_note_model->get_insert_log($log);
                unset($order_pay);
            }
            if ($query->trans_status() === FALSE) {
                $query->trans_rollback();
                return ['msg' => '驳回失败', 'bool' => false];
            } else {
                $query->trans_commit();
                return ['msg' => '驳回成功', 'bool' => TRUE];
            }
        } catch (Exception $exc) {
            return ['msg' => $exc->getMessage(), 'bool' => false];
        }
    }

    /**
     * 富友支付--确认页
     * @author harvin 2019-4-9
     * @param array $ids 请款单IDS
     * @param string $type 支付平台类型
     * @param int $show_pay_details 返回隐藏的信息
     * @return array
     * @throws Exception
     */
    public function get_online_payment(array $ids,$type='ufxfuiou',$show_pay_details = 0){
        if(empty($ids)){
            throw new Exception('参数不能为空');
        }

       //付款信息
       $order_pay = $this->purchase_db
                ->select('id,pur_number,requisition_number,pay_price,freight,discount,settlement_method'
                        . ',pay_type,pay_category,application_time,'
                        . 'applicant,create_notice,supplier_code')
                ->where_in('id', $ids)
                ->get($this->table_name)
                ->result_array();
       if(empty($order_pay)){
           throw new Exception('付款信息不存在');
       }

       if(empty($show_pay_details) or $show_pay_details != 1){
           //获取供应商基本信息
           $data_system= $this->get_supplier($ids[0]);
           if(empty($data_system)){
               throw new Exception('供应商基本信息,不存在');
           }

           //收款方信息
           $payment_linkage = $this->Supplier_payment_info_model->check_payment_info($data_system['supplier_code'],$data_system['is_tax'],$data_system['purchase_type_id']);
           $payment_linkage['payment_platform']= isset($payment_linkage['payment_platform'])?get_supplier_payment_platform($payment_linkage['payment_platform']):'';

           //获取请款明细
           $requisition_number_list= array_column($order_pay, 'requisition_number');

           // 所有的运费支付方式
           $is_freight_list = $this->get_pay_type($requisition_number_list);
           $is_freight_list = array_unique(array_column($is_freight_list, 'is_freight'));
           $freight_string  = '';
           foreach($is_freight_list as $fr_value){
               $freight_string .= getFreightPayment($fr_value).'/';
           }
           $freight_string            = trim($freight_string, '/');
           $data_system['is_freight'] = $freight_string;

           //获取本申请金额
           $total_pay_price = array_sum(array_column($order_pay,'pay_price'));
           $bank = $this->bankCart->get_payment_account_by_platform($type);//获取银行卡信息
           $data_list = [
               'supplier_basic'  => $data_system,
               'payment_linkage' => $payment_linkage,
               'total_pay_price' => format_price($total_pay_price),
               'bank'            => $bank,
           ];
       }else{
           //获取请款明细
           $requisition_number_list = array_column($order_pay, 'requisition_number');
           $pay_detail = $this->get_order_pay_detail($requisition_number_list);
           $id_list = array_column($order_pay, 'id', 'requisition_number');

           $data = [];
           foreach ($pay_detail as $key => $row) {
               $data[$key]['id'] = $id_list[$row['requisition_number']];
               $data[$key]['purchase_number'] = $row['purchase_number'];
               $data[$key]['requisition_number'] = $row['requisition_number'];
               $data[$key]['pay_total'] = $row['pay_total'];
               $data[$key]['freight'] = $row['freight'];
               $data[$key]['discount'] = $row['discount'];
               $data[$key]['settlement_method'] = $this->Payment_order_pay_model->get_settlement_method($row['settlement_method']); //结算方式
               $data[$key]['js_ratio'] = $row['js_ratio']; //结算比例
               $data[$key]['pay_type'] = getPayType($row['pay_type']);
               $data[$key]['pay_category'] = !empty($row['pay_category']) ? getPayCategory($row['pay_category']) : '';
               $data[$key]['applicant'] = get_buyer_name($row['applicant']);
               $data[$key]['application_time'] = $row['application_time'];
               $data[$key]['create_notice'] = $row['create_notice'];
           }
           $data_list = [
               'payment_info' => $data,
           ];
       }
        return $data_list;
    }

    /**
     * 获取采购单支付信息
     * @param array $requisition_number_list
     * @return array
     * @throws Exception
     */
    public function get_pay_type(array $requisition_number_list){
        if(empty($requisition_number_list)){
            throw new Exception('请款单，不存在');
        }
        $query = $this->purchase_db;
        $query->select('C.is_freight');
        $query->from('purchase_order_pay_detail as A');
        $query->join('purchase_order_pay_type as C', "C.purchase_number=A.purchase_number", 'left');
        $query->where_in('A.requisition_number', $requisition_number_list);
        $detail = $query->group_by('C.is_freight')
            ->get()
            ->result_array();
        if(empty($detail))  {
            throw new Exception('请款单明细，不存在');
        }
        return   $detail;
    }

    /**
     * 获取请款明细数据
     * @author harvin
     * @param array $requisition_number_list
     * @return array
     * @throws Exception
     */
    public function get_order_pay_detail(array $requisition_number_list){
        if(empty($requisition_number_list)){
            throw new Exception('请款单，不存在');
        }
        $query = $this->purchase_db;
        $query->select('B.id,A.requisition_number,A.purchase_number,A.freight,A.discount,A.pay_total,B.js_ratio,'
            . 'B.settlement_method,B.pay_type,B.pay_category,B.applicant,B.create_notice,B.application_time,C.is_freight,A.check_status,A.is_special');
        $query->from('purchase_order_pay_detail as A');
        $query->join('purchase_order_pay as B', "A.requisition_number=B.requisition_number", 'left');
        $query->join('purchase_order_pay_type as C', "C.purchase_number=A.purchase_number", 'left');
        $query->where_in('A.requisition_number', $requisition_number_list);
        $detail = $query->get()->result_array();
        if(empty($detail))  {
            throw new Exception('请款单明细，不存在');
        }
        if(!empty($detail)){
            foreach ($detail as $value){
                if($value['is_special']!=1 and $value['check_status']==11){
                    throw new Exception('请款单号:'.$value['requisition_number'].'中的'.$value['purchase_number'].'验货不合格，不允许付款，请驳回!');
                }
            }
        }
        return   $detail;
    }
}
