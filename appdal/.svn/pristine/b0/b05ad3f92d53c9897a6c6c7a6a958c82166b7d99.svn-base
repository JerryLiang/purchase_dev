<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Financial_system_model extends Purchase_model {

    protected $table_name = 'purchase_order_pay';
    protected $table_supplier='supplier';
    protected $table_pay_type='purchase_order_pay_type';
    
    public function __construct() {
        parent::__construct();
        $this->load->model('purchase/purchase_order_model');
        $this->load->helper('status_order');
        $this->load->helper('common');
        $this->load->model('Reject_note_model');
        $this->load->model('purchase_suggest/purchase_suggest_model');
        $this->load->model('abnormal/abnormal_list_model');
        $this->load->model('finance/Payment_order_pay_model'); 
        $this->load->model('supplier/Supplier_payment_info_model');
    }
    
    /**
     * 获取推送财务数据
     * @param array $params
     * @param string $type
     * @return array
     */
    public function get_financial_inquiry($params,$type){
        // 过滤物料仓
        $filterWarehouseList = $this->purchase_db->select('warehouse_code')
            ->like('warehouse_name','物料仓')
            ->or_like('warehouse_name','样品仓')
            ->get('warehouse')
            ->result_array();
        $filterWarehouseList = empty($filterWarehouseList)?null:array_column($filterWarehouseList,'warehouse_code');

        $latest_time = date('Y-m-d',strtotime('-30 day'));
        $query = $this->purchase_db;
        $query->select(
            'p.id,'
            . 'p.pay_status,'
            . 'p.pur_number,'
            . 'p.requisition_number,'
            . 'p.pai_number,'
            . 'p.supplier_code,'
            . 'po.supplier_name,'
            . 'p.pay_type,'
            . 'p.pay_category,'
            . 'p.settlement_method,'
            . 'p.payment_platform,'
            . 'p.pay_price,'
            . 'p.product_money,'
            . 'p.freight,'
            . 'p.discount,'
            . 'p.applicant,'
            . 'p.application_time,'
            . 'p.auditor,'
            . 'p.review_time,'
            . 'p.create_notice,'  //请款备注
            . 'p.review_notice,' //审核备注
            . 'p.is_sample,'
            . 'p.processing_notice,' //审批备注
            . 'p.purchase_account,'
            . 'p.payment_notice,'   //付款备注
            . 'p.payer_name,p.is_cross_border,'
            . 'p.payer_time,'
            . 'ppy.accout_period_time as need_pay_time,'
            . 'sp.surplus_quota,'
            . 'sp.tap_date_str,'
            . 'p.source,'
            . 'po.purchase_type_id,'
            . 'po.is_drawback,'
            . 'po.purchase_order_status'
        );
        $query->from('purchase_order_pay as p');
        $query->join('pur_supplier as sp', 'sp.supplier_code=p.supplier_code', 'left');
        $query->join('purchase_order_pay_type as ppy', 'ppy.purchase_number=p.pur_number', 'left');
        $query->join('purchase_order as po', 'po.purchase_number=p.pur_number', 'left');

        $query->where("p.pay_status", PAY_PAID);// 已付款
        $query->where('p.is_financial', 0);// 未推送的
        $query->where('p.payer_time >', '2021-01-01 00:00:00');// 只推送 2021年之后的数据
        $query->where('p.payer_time <', $latest_time);// 只推送 2021年之后的数据
        $query->where('p.source', intval($type));// 采购来源=网采
        $query->where('p.pay_category', PURCHASE_PAY_CATEGORY_3);// 请款类型=采购货款+运费/优惠
        $query->where_in('po.purchase_order_status', [PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE]);// 采购单状态=全部到货,部分到货不等待剩余
        $query->like('p.purchase_account', 'yibaisuperbuyers','after');// 账号=yibaisuperbuyers
        $query->not_like('p.requisition_number', 'YPP', 'after');
        $query->where_not_in('po.warehouse_code',$filterWarehouseList);

        $data = $query->limit(200)
            ->order_by('p.id', 'asc')
            ->get()
            ->result_array();
        if (empty($data)) return [];

        //获取结算方式
        $settlement_method = is_array($data) ? array_column($data, 'settlement_method') : [];
        $this->load->model("supplier/Supplier_settlement_model");
        $supplier_code_list = $this->Supplier_settlement_model->get_code2name_list($settlement_method);
        $all_user_list = get_buyer_name();


        foreach ($data as $key => $val) {
            // 转换业务线
            if (in_array($val['purchase_type_id'], [PURCHASE_TYPE_PFB, PURCHASE_TYPE_PFH])) $val['purchase_type_id'] = PURCHASE_TYPE_INLAND;
            $data[$key]['purchase_type_id'] = $val['purchase_type_id'];
            //支付平台
            $payment_platform = $this->Supplier_payment_info_model->get_payment_platform($val['supplier_code'], $val['is_drawback'], $val['purchase_type_id'], $val['pay_type']);
            $data[$key]['payment_platform'] = get_supplier_payment_platform($payment_platform['payment_platform']??'');
            //判断线上账期付款提醒状态
            $data[$key]['pay_notice'] = formatAccoutPeriodTime($data[$key]['settlement_method'], $data[$key]['pay_status'], $data[$key]['need_pay_time'], $data[$key]['surplus_quota']);
            $data[$key]['pay_status'] = isset($val['pay_status']) ? getPayStatus($val['pay_status']) : '';
            $data[$key]['pay_type'] = isset($val['pay_type']) ? getPayType($val['pay_type']) : '';
            $data[$key]['settlement_method'] = isset($supplier_code_list[$val['settlement_method']]) ? $supplier_code_list[$val['settlement_method']] : '';
            $data[$key]['applicant'] = isset($all_user_list[$val['applicant']]) ? $all_user_list[$val['applicant']] : '';
            $data[$key]['auditor'] = isset($all_user_list[$val['auditor']]) ? $all_user_list[$val['auditor']] : '';
            $data[$key]['pay_category'] = isset($val['pay_category']) ? getPayCategory($val['pay_category']) : "";
            $data[$key]['source'] = isset($val['source']) ? getPurchaseSource($val['source']) : "";
            $data[$key]['purchase_order_status_int'] = $val['purchase_order_status'];
            $data[$key]['purchase_order_status'] = getPurchaseStatus($val['purchase_order_status']);
            // 采购账号子账号的只限制主账号
            $purchase_account = $data[$key]['purchase_account'];
            if ($purchase_account != 'yibaisuperbuyers' and stripos($purchase_account, ':') !== false) {
                $account_arr = explode(':', $purchase_account);
                $data[$key]['purchase_account'] = isset($account_arr[0]) ? $account_arr[0] : $purchase_account;
            }
            unset($data[$key]['surplus_quota']);
        }
        return $data;

    }
    
    /**
     * 获取富友数据
     * @author harvin
     * @return array
     */
    public function ufxfuiou_financial_list(){
         //先获取富友支付数据
        $ufxfuiou = $this->purchase_db
                ->select('pur_tran_num')
                ->where('is_push', 0)
                ->limit(20)
                ->get('purchase_order_pay_ufxfuiou')
                ->result_array();
        if (empty($ufxfuiou)) {
            return ['code'=>false,'msg'=>'不存在富友流水号','data'=>[]];
        }
        $pur_tran_num = array_column($ufxfuiou, 'pur_tran_num');
        //判断富友是否付款成功
        $ufxfuioulog = $this->purchase_db->select('pur_tran_no,request_response')
                ->where_in('pur_tran_no', $pur_tran_num)
                ->get('ufxfuiou_request_log')
                ->result_array();
        if (empty($ufxfuioulog)) {
            return ['code'=>false,'msg'=>'富友回调数据不存在','data'=>[]];
        }
        $data_pur_tran_no = $error_data_pur_tran_no = [];
        foreach ($ufxfuioulog as $row) {
            if (strpos($row['request_response'], '成功') == TRUE) {
                $data_pur_tran_no[] = $row['pur_tran_no'];
            } else {
                $error_data_pur_tran_no[] = $row['pur_tran_no'];
                continue;
            }
        }
        if(empty($data_pur_tran_no)){
            if(!empty($error_data_pur_tran_no)){
                $this->purchase_db->where_in('pur_tran_num',$error_data_pur_tran_no)->update('purchase_order_pay_ufxfuiou',['is_push'=>2]);
            }
            return ['code'=>false,'msg'=>'待推送财务,富友支付成功数据不存在','data'=>[]];
        }
        $data_pur_tran_no= array_unique($data_pur_tran_no);
        //获取可用的数据
        $ufxfuioueffective = $this->purchase_db
            ->select('requisition_number,id')
            ->where_in('pur_tran_num',$data_pur_tran_no)
            ->where('is_push', 0)
            ->get('purchase_order_pay_ufxfuiou')
            ->result_array();
        unset($data_pur_tran_no);
        $data=is_array($ufxfuioueffective)?array_column($ufxfuioueffective, 'requisition_number','id'):[];
        return ['code'=>TRUE,'msg'=>'成功','data'=>$data];    
    }

    /**
     * 获取待推送宝付请款单号
     * @return array
     */
    public function abofopay_financial_list(){
         $query=$this->purchase_db;
         $query->select('A.requisition_number,B.id');
         $query->from('purchase_order_pay_baofo_detail as A');
         $query->join('purchase_order_pay_baofppay as B', 'A.pur_tran_num=B.pur_tran_num');
         $query->where('B.audit_status',BAOFOOPAYSTATUS_4);// 宝付只推送 收款成功的记录
         $query->where('B.is_push',0);
         $reslut= $query->get()->result_array();
         $data=is_array($reslut)?array_column($reslut, 'requisition_number','id'):[];
         return ['code'=>TRUE,'msg'=>'成功','data'=>$data];    
    }

    /**
     * 获取待推送拉卡拉请款单号
     * @return array
     */
    public function lakalapay_financial_list(){
        $query = $this->purchase_db;
        $query->select('A.requisition_number,B.id');
        $query->from('pay_lakala_detail as A');
        $query->join('pay_lakala as B', 'A.cust_order_no=B.cust_order_no');
        $query->where('B.audit_status', BAOFOOPAYSTATUS_4);
        $query->where('B.is_push', 0);
        $query->where('B.platform_type', 'lakala');// 按平台推送数据
        $reslut = $query->get()->result_array();
        $data   = is_array($reslut) ? array_column($reslut, 'requisition_number', 'id') : [];

        return ['code' => true, 'msg' => '成功', 'data' => $data];
    }

    /**
     *获取数据
     * @author harvin
     */
    public function get_order_list($data_list,$type='ufxfuiou'){

        $data_order_info = [];
        foreach ($data_list as $key => $row) {
            //获取合同单请款单
            $order_pay = $this->purchase_db
                ->select('pur_number,source,source_subject,payer_time,pay_status')
                ->where('requisition_number', $row)
                ->get('purchase_order_pay')
                ->row_array();
            if (empty($order_pay)) {
                continue;
            }
            if ($order_pay['source'] == SOURCE_NETWORK_ORDER) {
                //如果是网采 就过滤掉
                continue;
            }
            if ($order_pay['pay_status'] != PAY_PAID) {
                //过滤异常
                continue;
            }
            if (empty($order_pay['payer_time']) or $order_pay['payer_time'] == '0000-00-00 00:00:00') {
                //过滤异常
                continue;
            }

            // 获取请款单内所包含的所有采购单
            if ($order_pay['source_subject'] == SOURCE_SUBJECT_COMPACT_ORDER) {// 合同
                //根据合同号 获取采购单
                $compact_items = $this->purchase_db
                    ->select('purchase_number')
                    ->where('compact_number', $order_pay['pur_number'])
                    ->get('purchase_compact_items')
                    ->result_array();
            } elseif ($order_pay['source_subject'] == SOURCE_SUBJECT_STATEMENT_ORDER) {// 对账单
                $compact_items = $this->purchase_db
                    ->select('purchase_number')
                    ->where('statement_number', $order_pay['pur_number'])
                    ->get('purchase_statement_summary')
                    ->result_array();
            } else {
                continue;
            }

            if (empty($compact_items)) {
                continue;
            }

            $purchase_number = is_array($compact_items) ? array_column($compact_items, 'purchase_number') : [];
            if (empty($purchase_number)) {
                continue;
            }

            $res = $this->get_order_info($purchase_number, $key, $type, $order_pay['payer_time']);
            $data_order_info[] = $res;
            unset($res);
        }

       return $data_order_info;
        
    }

    /**
     *
     * 获取数据
     * @param array $purchase_number 采购单号
     * @param $id
     * @param string $type
     * @param string $payer_time  采购单对应的付款时间（请款单的付款时间）
     * @return array
     */
    public function get_order_info(array $purchase_number,$id,$type='ufxfuiou',$payer_time){
        $data = $data_order = [];
        foreach ($purchase_number as $val) {
            $order = $this->purchase_db
                ->select('purchase_number,supplier_code,create_time,pay_time')
                ->where('purchase_number', $val)
                ->get('purchase_order')
                ->row_array();
            if (empty($order)) {
                continue;
            }
            $orderinfo = $this->purchase_db
                ->select('sku,confirm_amount,purchase_unit_price,purchase_number')
                ->where('purchase_number', $val)
                ->get('purchase_order_items')
                ->result_array();
            if (empty($orderinfo)) {
                continue;
            }
            $order_amt = 0;
            $detail_data = [];
            foreach ($orderinfo as $vv) {
                $order_amt += format_two_point_price($vv['purchase_unit_price'] * $vv['confirm_amount']);
                $temp_info['sku'] = $vv['sku'];
                $temp_info['purchase_order_id'] = $vv['purchase_number'];
                $temp_info['amt'] = $vv['purchase_unit_price'];
                $temp_info['qty'] = $vv['confirm_amount'];
                $detail_data[] = $temp_info;
            }
            if ($type == "ufxfuiou") {
                $sett_company = 'FUYOU';
            } elseif ($type == "baofopay") {
                $sett_company = 'BAOFU';
            } elseif ($type == "lakala") {
                $sett_company = 'LAKALA';
            } else {
                $sett_company = '';
            }
            $data['id'] = $id;
            $data['purchase_no'] = $order['purchase_number'];
            //$data['purchase_date']= $order['create_time'];
            // 采购单第一次付款时间（财务汇总系统 只接受第一次推送的PO，第二次推送直接过滤，所以第一次付款时间等于请款单对应的付款时间）
            $data['purchase_date'] = $payer_time;
            $data['supplier_code'] = $order['supplier_code'];
            $data['payment_time'] = $order['pay_time'];
            $data['sett_company'] = $sett_company;
            $data['order_amt'] = $order_amt;
            $data['detail_data'] = $detail_data;
            $data_order[] = $data;
            unset($data);
        }
        return $data_order;
    }

    /**
     * 推送数据给财务
     * @param array $data
     * @param string $type
     * @return array
     */
    public function to_financial_from($data,$type='ufxfuiou'){
       $url = getConfigItemByName('api_config', 'purchase_api', 'purchaseSysInsertData');
       $batch_data=['batch_data'=> json_encode($data)];
       $reslut = getCurlData($url, $batch_data);
       $reslut= json_decode($reslut, TRUE);

        $data_error = [];
       if(isset($reslut['result']) && $reslut['result']){ 
           foreach ($reslut['result'] as $val) {
               if(isset($val['status']) && ($val['status']=='0' && $val['errorCode']='3006') || ($val['status']=='1' && $val['errorCode']='3025')){
                   if($type=='ufxfuiou'){
                        $this->purchase_db->where('id',$val['id'])->update('purchase_order_pay_ufxfuiou',['is_push'=>1]); 
                   }elseif($type=='baofopay'){
                       $this->purchase_db->where('id',$val['id'])->update('purchase_order_pay_baofppay',['is_push'=>1]); 
                   }elseif($type=='lakala'){
                       $this->purchase_db->where('id',$val['id'])->update('pay_lakala',['is_push'=>1]);
                   }else{
                        $data_error[]=$val['id'].'推送支付标识不存在';
                   }
                   $data_error[]=$val['id'].$val['errorMess'];
               }else{ 
                   $data_error[]=$val['id'].$val['errorMess'];
               }  
           } 
       }else{
            $data_error[]=$reslut.'返回数据格式不对';
       }
       return $data_error;
    }

    /**
     * 采购入库单接口——国内仓数据专用
     * @param int $is_push_finance 推送指定推送状态的数据
     * @param bool $force_push 强制推送数据（不限定只在2,3号推送）
     * @return int
     */
    public function Push_warehouse_results($is_push_finance = 1,$force_push = false){
        $now_day = date('d');// 第几天
        if(empty($force_push) and $is_push_finance == 1 and $now_day != 2 and $now_day != 3){// $is_push_finance=1 表示只控制正常状态的推送
            exit('每月的2号和3号开始执行');// 可能导致一个问题：2号3号之后推送过来的入库记录  无法推送到财务系统
        }
        $last_month_end_time = date('Y-m-d H:i:s',strtotime(date('Y-m')) - 1);// 上个月月底的 23:59:59秒

        $this->load->model('user/Purchase_user_model');

        $list = $this->purchase_db->select(
                'A.id,A.purchase_number,A.sku,A.instock_batch,A.upper_end_time AS instock_date,
                B.purchase_name,B.warehouse_code,D.purchase_unit_price,A.instock_qty,
                B.supplier_code,B.buyer_id,C.user_code,B.pay_status'
            )
            ->from('warehouse_results AS A')
            ->join('purchase_order_items AS D','A.items_id=D.id','INNER')
            ->join('purchase_order AS B','A.purchase_number=B.purchase_number','INNER')
            ->join('purchase_user AS C','B.buyer_id=C.user_id','LEFT')
            ->where('A.is_push_finance',$is_push_finance)  // 1.未推送
            ->where('A.instock_date<=',$last_month_end_time)
            ->where('B.purchase_type_id <>',PURCHASE_TYPE_OVERSEA)// 只推送除海外仓之外其他业务线数据
            ->where('A.instock_node',100)  // 100.上架完成后才推送
            ->limit(500)
            ->get()
            ->result_array();

        $record_type = 'Push_warehouse_results';
        if($list){
            $push_list_tmp = [];
            foreach($list as $value){
                // 入库数量为0的不用推送,，入库日期小于2020-10-01 都不推送
                if($value['instock_qty'] == 0 or strtotime($value['instock_date']) < strtotime('2020-10-01 00:00:00')){
                    $this->purchase_db->where('id',$value['id'])->update('warehouse_results',['is_push_finance' => 0]);// 0. 无需推送
                    continue;
                }

                if(empty($value['user_code'])){
                    $userInfo = $this->Purchase_user_model->get_user_info_by_id($value['buyer_id']);
                    if(isset($userInfo['staff_code'])){
                        $buyer_code = $userInfo['staff_code'];
                    }else{
                        $buyer_code = '';
                    }
                }else{
                    $buyer_code = $value['user_code'];
                }


                $org_id = ($value['purchase_name'] == 'HKYB')?'101':'102';

                $push_list_tmp[] = [
                    'buyer_code' => $buyer_code,
                    'bill_no' => $value['instock_batch'],
                    'purchase_no' => $value['purchase_number'],
                    'supplier_code' => $value['supplier_code'],
                    'instock_time' => $value['instock_date'],
                    'org_id' => $org_id,
                    'warehouse_code' => $value['warehouse_code'],
                    'sku' => $value['sku'],
                    'price' => $value['purchase_unit_price'],
                    'qty' => $value['instock_qty'],
                    'pay_status' => $value['pay_status']
                ];
            }

            $push_list = [
                'system_code' => 7,// 系统编号
                'batch_data' => json_encode($push_list_tmp)
            ];


            $url    = getConfigItemByName('api_config', 'finance_system', 'pushWareResults');
            $result = getCurlData($url, $push_list);
            $result = json_decode($result, TRUE);

            if(isset($result['status'])){
                $data_list = $result['data'];
                foreach($data_list as $d_value){
                    if($d_value['status'] == 1){
                        $update = ['is_push_finance' => 2];// 2.推送成功
                        $response_content = '推送成功';
                    }else{
                        $update = ['is_push_finance' => 3];// 3.推送失败
                        $response_content = isset($d_value['errorMess'])?$d_value['errorMess']:'推送失败';
                    }
                    $this->purchase_db->where('instock_batch',$d_value['bill_no'])
                        ->where('purchase_number',$d_value['purchase_no'])
                        ->update('warehouse_results',$update);

                    // 记录推送日志 便于排查问题
                    apiRequestLogInsert(
                        [
                            'record_number'    => $d_value['bill_no'],
                            'record_type'      => $record_type,
                            'post_content'     => '',
                            'response_content' => $response_content,
                            'status'           => $d_value['status'],
                        ],
                        'api_finance_request_log'
                    );
                }
            }
        }

        return count($list);
    }


    /**
     * 采购入库单接口——海外仓数据专用
     * @param int $is_push_finance 推送指定推送状态的数据
     * @param bool $force_push 强制推送数据（不限定只在2,3号推送）
     * @return int
     */
    public function Push_warehouse_results_oversea($is_push_finance = 1,$force_push = false){
        $now_day = date('d');// 第几天
        if(empty($force_push) and $is_push_finance == 1 and $now_day != 2 and $now_day != 3){// $is_push_finance=1 表示只控制正常状态的推送
            exit('每月的2号和3号开始执行');// 可能导致一个问题：2号3号之后推送过来的入库记录  无法推送到财务系统
        }
        $last_month_end_time = date('Y-m-d H:i:s',strtotime(date('Y-m')) - 1);// 上个月月底的 23:59:59秒

        $this->load->model('user/Purchase_user_model');

        $list = $this->purchase_db->select(
            'A.id,A.purchase_number,A.sku,A.instock_batch,A.instock_date,
                B.purchase_name,B.warehouse_code,D.purchase_unit_price,A.instock_qty,
                B.supplier_code,B.buyer_id,C.user_code,B.pay_status'
        )
            ->from('warehouse_results AS A')
            ->join('purchase_order_items AS D','A.items_id=D.id','INNER')
            ->join('purchase_order AS B','A.purchase_number=B.purchase_number','INNER')
            ->join('purchase_user AS C','B.buyer_id=C.user_id','LEFT')
            ->where('A.is_push_finance',$is_push_finance)  // 1.未推送
            ->where('A.instock_date<=',$last_month_end_time)
            ->where('B.purchase_type_id',PURCHASE_TYPE_OVERSEA) // 只推送海外仓业务线数据
            ->where('A.instock_node',100)  // 100.上架完成后才推送
            ->limit(500)
            ->get()
            ->result_array();

        $record_type = 'Push_warehouse_results';
        if($list){
            $push_list_tmp = [];
            foreach($list as $value){
                // 入库数量为0的不用推送,，入库日期小于2020-10-01 都不推送
                if($value['instock_qty'] == 0 or strtotime($value['instock_date']) < strtotime('2020-10-01 00:00:00')){
                    $this->purchase_db->where('id',$value['id'])->update('warehouse_results',['is_push_finance' => 0]);// 0. 无需推送
                    continue;
                }

                if(empty($value['user_code'])){
                    $userInfo = $this->Purchase_user_model->get_user_info_by_id($value['buyer_id']);
                    if(isset($userInfo['staff_code'])){
                        $buyer_code = $userInfo['staff_code'];
                    }else{
                        $buyer_code = '';
                    }
                }else{
                    $buyer_code = $value['user_code'];
                }


                $org_id = ($value['purchase_name'] == 'HKYB')?'101':'102';

                $push_list_tmp[] = [
                    'buyer_code' => $buyer_code,
                    'bill_no' => $value['instock_batch'],
                    'purchase_no' => $value['purchase_number'],
                    'supplier_code' => $value['supplier_code'],
                    'instock_time' => $value['instock_date'],
                    'org_id' => $org_id,
                    'warehouse_code' => $value['warehouse_code'],
                    'sku' => $value['sku'],
                    'price' => $value['purchase_unit_price'],
                    'qty' => $value['instock_qty'],
                    'pay_status' => $value['pay_status']
                ];
            }

            $push_list = [
                'system_code' => 7,// 系统编号
                'batch_data' => json_encode($push_list_tmp)
            ];


            $url    = getConfigItemByName('api_config', 'finance_system', 'pushWareResults');
            $result = getCurlData($url, $push_list);
            $result = json_decode($result, TRUE);

            if(isset($result['status'])){
                $data_list = $result['data'];
                foreach($data_list as $d_value){
                    if($d_value['status'] == 1){
                        $update = ['is_push_finance' => 2];// 2.推送成功
                        $response_content = '推送成功';
                    }else{
                        $update = ['is_push_finance' => 3];// 3.推送失败
                        $response_content = isset($d_value['errorMess'])?$d_value['errorMess']:'推送失败';
                    }
                    $this->purchase_db->where('instock_batch',$d_value['bill_no'])
                        ->where('purchase_number',$d_value['purchase_no'])
                        ->update('warehouse_results',$update);

                    // 记录推送日志 便于排查问题
                    apiRequestLogInsert(
                        [
                            'record_number'    => $d_value['bill_no'],
                            'record_type'      => $record_type,
                            'post_content'     => '',
                            'response_content' => $response_content,
                            'status'           => $d_value['status'],
                        ],
                        'api_finance_request_log'
                    );
                }
            }
        }

        return count($list);
    }

}