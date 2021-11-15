<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Receivables_model extends Purchase_model {

    public function __construct() {
        parent::__construct();

        $this->load->model('payment_order_pay_model');
        $this->load->model('Purchase_order_unarrived_model','',false,'purchase');
    }

    /**
     * 获取应收款
     * @author harvin 2019-1-19
     * @param array $params 参数数组
     * @param int $offset 
     * @param int $limit 
     * */
    public function get_rece_list($params, $offset, $limit,$page) {
        if (isset($params['group_ids']) && !empty($params['group_ids'])) {//采购组别->组内用户user ID
            $this->load->model('user/User_group_model', 'User_group_model');
            $userGroupList = $this->User_group_model->get_buyer_by_group($params['group_ids']);
            $userGroupListIds = array_unique(array_column($userGroupList,'value'));
            $userGroupListIds = empty($userGroupListIds)?[0]:$userGroupListIds;
        }

        $query =  $this->purchase_db;
        $query->select('re.id AS receipt_id,re.cancel_id,ca.cancel_number,re.pay_status,'
            . 'group_concat( DISTINCT re.purchase_number) as purchase_number,'
            . 'group_concat( DISTINCT pur.pai_number) as pai_number,'
            . 're.settlement_method,'
            . 'group_concat( DISTINCT cd.serial_number) as serial_number,'
            . 're.application_id,'
            . 're.supplier_code,'
            . 're.supplier_name,re.pay_type,'
            . 're.apply_notice,re.apply_user_name,'
            . 're.apply_time,re.audit_user_name,re.audit_time,'
            . 're.payer_user_name,re.payer_time,payer_notice,'
            //.'sum(pur.apply_amount) as apply_amount,'
            . 'max(re.real_refund_time) as completed_time,'
            . 'max(pur.completed_time) as pur_completed_time,'
            . 'water.our_account,'
            . 'cd.screenshot_time,'
            . 'ca.cancel_order_type,'  //退款类型
            . 'cd.real_refund,' // 实际退款金额
            . 'ctr.pay_price,'  // 应收金额（剩余应收金额）
            . 'ctr.original_pay_price,' // 应收金额(原始)
            . 'ctr.original_pay_product_money,' // 原始退款商品额(原始)
            . 'ctr.original_pay_freight,' // 原始退款运费(原始)
            . 'ctr.original_pay_discount,' // 原始退款优惠额(原始)
            . 'ctr.original_pay_process_cost' // 原始退款加工费(原始)
        );
        $query->from('purchase_order_receipt re');
        $query->join('purchase_order_pay_type as pur','pur.purchase_number=re.purchase_number','LEFT');
        $query->join('purchase_order_cancel_detail as cd','cd.cancel_id=re.cancel_id and pur.purchase_number=cd.purchase_number','LEFT');
        $query->join('purchase_order_cancel_to_receipt as ctr','ctr.cancel_id=re.cancel_id and ctr.purchase_number=re.purchase_number','LEFT');
        $query->join('purchase_order_receipt_water as water','water.receipt_id=re.id','LEFT');
        $query->join('purchase_order_cancel as ca','re.cancel_id=ca.id');

        if (isset($params['receipt_ids']) && $params['receipt_ids']) { //根据ID查询
            $receipt_ids = query_string_to_array($params['receipt_ids']);
            $query->where_in('re.id', $receipt_ids);
            unset($params);// 删除所有查询条件
        }
        if (isset($params['supplier_code']) && $params['supplier_code']) { //供应商
            $query->where('re.supplier_code', $params['supplier_code']);
            unset($params['supplier_code']);
        }

        if (isset($userGroupListIds)) {//采购组别->组内用户user ID
            $query->where_in('re.application_id', $userGroupListIds);
        }

        if (isset($params['application_id']) && $params['application_id']) {//申请人
            $query->where('re.application_id', $params['application_id']);
            unset($params['application_id']);
        }
        if (isset($params['pay_status']) && $params['pay_status']) {//付款状态
            $query->where('re.pay_status', $params['pay_status']);
             unset($params['pay_status']);
        }
        if (isset($params['pay_type']) && $params['pay_type'] != '') { //支付方式
            $query->where('re.pay_type', $params['pay_type']);
            unset($params['pay_type']);
        }
        if(isset($params['is_cross_border']) && $params['is_cross_border'] != ''){ //跨境宝供应商
            $query->where('re.is_cross_border', $params['is_cross_border']);
            unset($params['is_cross_border']);
        }
        if(isset($params['our_account']) && $params['our_account'] != ''){ //收款账号
            $query->where('water.our_account', $params['our_account']);
            unset($params['our_account']);
        }
        if(isset($params['cancel_number']) && trim($params['cancel_number'])){ //取消编码
             //取消未到货编码
              $cancel_number=trim($params['cancel_number']);
              $id_list = $query->query("SELECT id FROM pur_purchase_order_cancel WHERE cancel_number='{$cancel_number}'")->result_array();
              $id_list = !empty($id_list)?array_column($id_list,'id'):[ORDER_CANCEL_ORSTATUS];
              $query->where_in('re.cancel_id', $id_list);
              unset($id_list);
              unset($params['cancel_number']);
        }
        if(isset($params['serial_number']) && trim($params['serial_number'])){
              //收款流水
              $serial_number= explode(' ',trim($params['serial_number']));
              $arr = array_map( function($data){
                  return sprintf("'%s'",$data);
              }, array_filter($serial_number));
              $arr = implode(",", $arr);
              $id_list = $query->query("SELECT cancel_id FROM pur_purchase_order_cancel_detail WHERE serial_number in (".$arr.")  group by cancel_id ")->result_array();
              $id_list = !empty($id_list)?array_column($id_list,'cancel_id'):[ORDER_CANCEL_ORSTATUS];
              $query->where_in('re.cancel_id', $id_list);
              unset($id_list);
              unset($params['serial_number']);
        }
         if(isset($params['purchase_number']) && trim($params['purchase_number'])){ ////采购单号
              $purchase_number = explode(' ',rtrim($params['purchase_number']));
              $purchase_number = implode("','", $purchase_number);

              $cancel_id_list = $query->query("SELECT cancel_id FROM pur_purchase_order_receipt WHERE purchase_number IN('".$purchase_number."')")->result_array();
              $cancel_id_list = !empty($cancel_id_list)?array_column($cancel_id_list,'cancel_id'):[PURCHASE_NUMBER_ZFSTATUS];

              $query->where_in('re.cancel_id', array_unique($cancel_id_list));
              unset($purchase_number);
         }
        if (isset($params['create_time_start']) and $params['create_time_start'])// 创建时间-开始
            $query->where('re.apply_time>=', $params['create_time_start']);
        if (isset($params['create_time_end']) and $params['create_time_end'])// 创建时间-结束
            $query->where('re.apply_time<=', $params['create_time_end']);
        if (isset($params['payer_time_start']) and $params['payer_time_start'])// 收款时间-开始
            $query->where('re.payer_time>=', $params['payer_time_start']);
        if (isset($params['payer_time_end']) and $params['payer_time_end'])// 收款时间-结束
            $query->where('re.payer_time<=', $params['payer_time_end']);

        if(isset($params['pai_number']) and trim($params['pai_number'])){
            $query->where('pur.pai_number=', $params['pai_number']);
        }

        if(isset($params['cancel_order_type']) and trim($params['cancel_order_type'])){
            $cancel_order_type=trim($params['cancel_order_type']);
            $query->where('ca.cancel_order_type',$cancel_order_type);
            unset($params['cancel_order_type']);
        }

        if(isset($params['completed_time_start'])&& $params['completed_time_start']){ //退款时间开始
            $query->where('re.real_refund_time >=',$params['completed_time_start']);
        }
        if(isset($params['payer_user_id']) && $params['payer_user_id']){ //退款时间结束
            $query->where('re.payer_id',$params['payer_user_id']);
        }
        if(isset($params['completed_time_end'])&& $params['completed_time_end']){ //退款时间结束
            $query->where('re.real_refund_time <=',$params['completed_time_end']);
        }

        if(isset($params['screenshot_time_start'])&& $params['screenshot_time_start']){ //上传截图时间开始
            $query->where('cd.screenshot_time >=',$params['screenshot_time_start']);
        }
        if(isset($params['screenshot_time_end'])&& $params['screenshot_time_end']){ //上传截图时间结束
            $query->where('cd.screenshot_time <=',$params['screenshot_time_end']);
        }

        $query->group_by('re.cancel_id,re.purchase_number');
        $count_qb = clone $query;

        $query_sql = $query->get_compiled_select();
        $count_sql = $count_qb->get_compiled_select();

        $having = '';
        if(isset($params['diff_search']) and $params['diff_search']){
            if($params['diff_search'] == 10){
                $having = " HAVING diff_amount < 0";
            }elseif($params['diff_search'] == 20){
                $having = " HAVING diff_amount = 0";
            }else{
                $having = " HAVING diff_amount > 0";
            }
        }

        $base_sql = "SELECT 
            receipt_id,
            cancel_id,
            cancel_number,
            pay_status,
            group_concat( DISTINCT purchase_number) as purchase_number,
            group_concat( DISTINCT pai_number) as pai_number,
            settlement_method,
            group_concat( DISTINCT serial_number) as serial_number,
            application_id,
            supplier_code,
            supplier_name,
            pay_type,
            apply_notice,
            apply_user_name,
            apply_time,
            audit_user_name,
            audit_time,
            payer_user_name,
            payer_time,
            payer_notice,
            completed_time,
            pur_completed_time,
            screenshot_time,
            cancel_order_type,
            real_refund,
            pay_price,
            IFNULL(our_account,'') AS our_account,
            original_pay_product_money,
            original_pay_freight,
            original_pay_discount,
            original_pay_process_cost,
            sum(real_refund) AS real_refund,
            sum(pay_price) as pay_price,
            sum(original_pay_price) as original_pay_price,
            sum(pay_price - real_refund) as diff_amount
            FROM ($query_sql) AS query
            GROUP BY cancel_id
            {$having}
            ORDER BY apply_time DESC
            LIMIT {$offset},{$limit}";
        $data = $query->query($base_sql)->result_array();


        //获取取消未到货集合
        $cancel_number_list=array_column($data,'cancel_number');

        $this->load->model('statement/Charge_against_records_model');
        $refund_cg_list = $this->Charge_against_records_model->get_charge_against_records_gather(['record_number' => $cancel_number_list,'charge_against_status' => CHARGE_AGAINST_STATUE_WAITING_PASS],2,'record_number');
        $refund_cg_list = arrayKeyToColumn($refund_cg_list,'record_number');

        //供应商结算方式
        $settlement_codes = is_array($data) ? array_column($data, "settlement_method") : [];
        $this->load->model("supplier/Supplier_settlement_model");
        $this->load->model("purchase/Purchase_order_determine_model");
        $this->load->model('user/User_group_model', 'User_group_model');
        $settlement_code_list = $this->Supplier_settlement_model->get_code2name_list($settlement_codes);
        //获取支付平台
        $purchase_number_list = is_array($data) ? array_column($data,'purchase_number') : [];
        $this->load->model('supplier/Supplier_payment_info_model','Supplier_payment_info_model');
        //查询该采购单 是否退税 业务线
        $po_info_map = $this->Supplier_payment_info_model->get_is_tax_business_line_by_po($purchase_number_list);

        // 所有用户的组别
        $this->load->model('user/User_group_model','User_group_model');
        $userGroupList = $this->User_group_model->getBuyerGroupMessage();
        $userGroupList = arrayKeyToColumnMulti($userGroupList,'user_id');

        $current_total_pay_price = $current_total_real_refund = 0;
        //组装需要数据
        foreach ($data as $key => $row) {
            $current_total_pay_price += $data[$key]['pay_price'];
            $current_total_real_refund += $data[$key]['real_refund'];
            $data[$key]['diff_amount'] = format_price($data[$key]['diff_amount']);

             $data[$key]['pay_type'] = getPayType($row['pay_type']);
             $data[$key]['pay_status'] = getReceivePayStatus($row['pay_status']);
             $data[$key]['settlement_method']= isset($settlement_code_list[$row['settlement_method']])?$settlement_code_list[$row['settlement_method']]:'';

             if($row['completed_time'] != '0000-00-00 00:00:00'){//退款完成时间;
                 $data[$key]['completed_time'] = $row['completed_time'];
             }elseif($row['pur_completed_time'] != '0000-00-00 00:00:00'){
                 $data[$key]['completed_time'] = $row['pur_completed_time'];
             }else{
                 $data[$key]['completed_time'] = '';
             }

            //根据 供应商编码 是否退税 业务线 支付方式 确认支付平台
            $is_drawback = $po_info_map[$row['purchase_number']]['is_drawback']??'';
            $purchase_type_id = $po_info_map[$row['purchase_number']]['purchase_type_id']??'';
            $payment_platform = $this->Supplier_payment_info_model->get_payment_platform($row['supplier_code'],$is_drawback,$purchase_type_id,$row['pay_type']);
            $data[$key]['payment_platform'] = $payment_platform['payment_platform']??'';
            $data[$key]['payment_platform'] = get_supplier_payment_platform($data[$key]['payment_platform']);

            if($data[$key]['cancel_order_type'] == 2) $data[$key]['screenshot_time'] = $data[$key]['apply_time'];

            $purchase_numbers = explode(',',$row['purchase_number']);
            if($purchase_numbers and is_array($purchase_numbers)){
                $apply_amount = $this->purchase_db->where_in('purchase_number',$purchase_numbers)
                    ->select("sum(apply_amount) as apply_amount")
                    ->get('purchase_order_pay_type')
                    ->row_array();
                $data[$key]['apply_amount'] = isset($apply_amount['apply_amount'])?$apply_amount['apply_amount']:0;
            }else{
                $data[$key]['apply_amount'] = 0;
            }
            $data[$key]['group_name_str'] = isset($userGroupList[$row['application_id']])?implode(',',array_column($userGroupList[$row['application_id']],'group_name')):'';


            if($row['cancel_order_type'] == 2){// 样品采购退款
                $data[$key]['receipt_product_money'] = $row['pay_price'];
                $data[$key]['receipt_others_money']  = '0';
            }else{
                // 冲销扣减掉的商品金额
                $charge_against_product = isset($refund_cg_list[$row['cancel_number']])?$refund_cg_list[$row['cancel_number']]['charge_against_product']:0;
                $receipt_product_money = $row['original_pay_product_money'] - $charge_against_product;
                $data[$key]['receipt_product_money'] = $receipt_product_money;// 原始退款商品金额 - 冲销扣减
                $data[$key]['receipt_others_money']  = $row['pay_price'] - $receipt_product_money;// 退款总金额 - 退款商品金额
            }
        }

        // 根据 cancel_id 维度计算记录个数
        $count_row = $count_qb->query("SELECT count(cancel_id) as num,
            sum(real_refund) as all_real_refund,
            sum(pay_price) as all_pay_price,
            sum(original_pay_price) as all_original_pay_price
            FROM (
                SELECT cancel_id,
                sum(real_refund) AS real_refund,
                sum(pay_price) as pay_price,
                sum(original_pay_price) as original_pay_price,
                sum(pay_price - real_refund) as diff_amount
                FROM ($count_sql) AS query
                GROUP BY cancel_id
                {$having}
            ) AS cc"
        )->row_array();

        $total_count = isset($count_row['num']) ? (int) $count_row['num'] : 0;   
        $data_list['pay_status'] = getReceivePayStatus(); //收款状态
        $data_list['pay_type'] = getPayType(); //支付方式
        $data_list['is_cross_border'] = getCrossBorder(); //跨境宝供应商
        $data_list['cancel_order_type'] = array(1=>'普通采购',2=>'样品采购');//1 普通采购 2样品采购
        $data_list['diff_search_type'] = ['10' => '小于0','20' => '等于0','30' => '大于0'];
        $payer_user_list = $this->purchase_db->query("SELECT payer_id,payer_user_name FROM `pur_purchase_order_receipt` GROUP BY payer_id")->result_array();
        $data_list['payer_user_list'] = array_column($payer_user_list,'payer_user_name','payer_id');
        $application_id_list = $this->purchase_db->query("SELECT application_id,apply_user_name FROM `pur_purchase_order_receipt` GROUP BY application_id")->result_array();
        $data_list['application_id'] = array_column($application_id_list,'apply_user_name','application_id');
        $data_list['group_list'] = $this->User_group_model->getGroupList([1,2]);

        $key_table = [ '收款状态', '申请编码','采购单号','付款信息', '收款金额','实际退款金额','差额','收款流水号', '退款类型', '申请人/申请时间', '审核人/审核时间', '收款人/收款时间','收款备注','操作'];
        $temp=[];
        $return_data = [
           'drop_down_box'=>$data_list,
            'key'=>$key_table,
            'values'=>$data,
             'paging_data'=>[
                'total'=>$total_count,
                'offset'=>$page,
                'limit'=>$limit,
                'pages'=> ceil($total_count/$limit)
            ],
            'statistic_list' => [
                'current_total_pay_price' => format_price($current_total_pay_price),
                'current_total_real_refund' => format_price($current_total_real_refund),
                'all_real_refund' => isset($count_row['all_real_refund']) ? $count_row['all_real_refund'] : 0,
                'all_pay_price' => isset($count_row['all_pay_price']) ? $count_row['all_pay_price'] : 0,
            ]
        ];
        return $return_data;
    }
   /**
    * 获取取消未到货编码集合
    * @author harvin 2019-3-15
    * @param array $cancel_id_list
    * @return array 
    */
   public function get_cancel_id(array $cancel_id_list){
      if(empty($cancel_id_list)){
          return [];
      }
//      $order_cancel= $this->purchase_db
//              ->select('id,cancel_number')
//              ->where_in('id',$cancel_id_list)
//              ->get('purchase_order_cancel')
//              ->result_array();

      $cancel_id_list = array_map(function($id){

          return sprintf("'%s'",$id);
      },$cancel_id_list);
      $order_cancel_sql = " SELECT id,cancel_number FROM pur_purchase_order_cancel WHERE id IN(".implode(",",$cancel_id_list).")";
      $order_cancel = $this->purchase_db->query($order_cancel_sql)->result_array();
    
       if(empty($order_cancel)){
           return [];
       }
       return array_column($order_cancel, 'cancel_number','id');
       
   }
   /**
    * 获取取消未到货流水号
    * @param array $cancel_id_list
    * @return type
    */
   public function get_cancel_serial_number(array $cancel_id_list){
       if(empty($cancel_id_list)){
          return [];
      }
      $order_cancel= $this->purchase_db
              ->select('id,serial_number')
              ->where_in('id',$cancel_id_list)
              ->get('purchase_order_cancel')
              ->result_array();
    
       if(empty($order_cancel)){
           return [];
       }
       return array_column($order_cancel, 'serial_number','id');
   }

    /**
     * 获取取消未到货流水号
     * @param array $cancel_id_list
     * @return type
     */
    public function get_cancel_serial_number_from_detail(array $cancel_id_list){
        if(empty($cancel_id_list)){
            return [];
        }
        $order_cancel= $this->purchase_db
            ->select('cancel_id,serial_number')
            ->where_in('cancel_id',$cancel_id_list)
            ->group_by('cancel_id,purchase_number')
            ->get('purchase_order_cancel_detail')
            ->result_array();

        if(empty($order_cancel)){
            return [];
        }
        return array_column($order_cancel, 'serial_number','cancel_id');
    }

   /**
     * 收款操作显示详情
     * @author harvin 2019-1-19 
     * @param sting $id 参数id
     * */
    public function get_receivable($id) {
        $query = $this->purchase_db;
        $query-> select('cancel_id,pay_status,'
                . 'group_concat(purchase_number) as purchase_number,'
                . 'sum(pay_price) as pay_price,supplier_code,'
                . 'supplier_name,pay_type,real_refund_time,'
                . 'apply_notice,apply_user_name,'
                . 'apply_time,audit_user_name,audit_time,'
                . 'payer_user_name,payer_time,payer_notice,currency');
        $query->from('purchase_order_receipt');
        $receipt= $query->where('cancel_id',$id)->get()->row_array();
     
        if(empty($receipt)){
           throw new Exception('参数id,不存在');
        }

        $receipt['pay_status'] = getReceivePayStatus($receipt['pay_status']);
        $cancel_id= isset($receipt['cancel_id'])?$receipt['cancel_id']:0;
        if(empty($cancel_id)){
            throw new Exception('未找到取消未到货表的信息');
        }
        //获取流水截图 及申请备注  审核备注
        $order_cancel= $this->purchase_db
                   ->select('cancel_url,create_note,audit_note,upload_note,cancel_order_type')->where('id',$receipt['cancel_id'])
                   ->get('purchase_order_cancel')
                   ->row_array();
        $cancel_serial_number_list=$this->get_cancel_serial_number_from_detail([$receipt['cancel_id']]);
        
        $receipt['desc']= '退款金额:'.$receipt['pay_price'];
        $receipt['pay_type']= getPayType($receipt['pay_type']);
     //   $receipt['pay_status']= getReceivePayStatus($receipt['pay_status']);
        //$receipt['payment_method'] =getPayType();
        $receipt['account_short'] = $this->payment_order_pay_model->get_bank();
        $receipt['payer_time'] = (empty($receipt['payer_time']) or $receipt['payer_time'] == '0000-00-00 00:00:00')?date('Y-m-d H:i:s'):$receipt['payer_time'];// 默认为当前时间
        $receipt['cancel_url']= isset($order_cancel['cancel_url'])?json_decode($order_cancel['cancel_url'],TRUE):'';
        $receipt['create_note']=isset($order_cancel['create_note'])?$order_cancel['create_note']:'';
        $receipt['audit_note']=isset($order_cancel['audit_note'])?$order_cancel['audit_note']:'';
        $receipt['serial_number']=isset($cancel_serial_number_list[$receipt['cancel_id']])?$cancel_serial_number_list[$receipt['cancel_id']]:'';
        $receipt['upload_note']=isset($order_cancel['upload_note'])?$order_cancel['upload_note']:'';
        $receipt['cancel_order_type']=isset($order_cancel['cancel_order_type'])?$order_cancel['cancel_order_type']:'';

        //获取上传截图信息
        $this->load->model('purchase/Purchase_order_determine_model');
        $upload_data = $this->Purchase_order_determine_model->get_upload_preview_data($cancel_id, $receipt['real_refund_time']);
        $receipt['upload_data'] = $upload_data;


        return $receipt;
    }
   /**
    * 判断收款状态
    * @param $id 参数
    * **/
  public function get_receivable_status($id){
      $receipt = $this->purchase_db
                ->select('pay_status')
                ->where('cancel_id', $id)
                ->get('purchase_order_receipt')
                ->row_array();
    return isset($receipt['pay_status'])?  $receipt['pay_status'] : null;
  }
    /**
     * 保存收款操作
     * @author harvin 2019-1-19
     * @param int $id  参数id
     * @param  $price 金额（收款金额）
     * @param $collection_time 收款时间
     * @param $remarks 收款备注
     * @param  $account_short 我方支行简称
     * @param  $branch 我方支行
     * @param  $account_number 我方银行卡号
     * @param  $account_holder 我方开户人
     * * */
    public function get_receivable_save($id, $price, $collection_time, $remarks, $account_short, $branch,$account_number, $account_holder, $type,$cancel_order_type=1) {
        $query = $this->purchase_db;
        try {
            $this->load->model('Reject_note_model');
            $fale = true;
            $query->trans_begin();
            $purchase_number = $query->select('id,cancel_id,purchase_number,supplier_code,supplier_name,pay_price,currency')
                    ->where('cancel_id', $id)
                    ->get('purchase_order_receipt')
                    ->result_array();
            if (empty($purchase_number)) {
                throw new Exception('收款单不存在');
            }
             $cancel_id= $id;
             if(empty($cancel_id)){
                 throw new Exception('收款单未关联到取消未到货主表');
             }

            if ($type == 1) { //审核通过
                    foreach ($purchase_number as $val) {
                        //更新收款单主表信息
                        $data = [
                            'pay_status' => RECEIPT_PAY_STATUS_RECEIPTED,
                            'payer_id' => getActiveUserId(),
                            'payer_user_name' => getActiveUserName(),
                            'payer_time' => $collection_time,
                            'payer_notice' => $remarks,
                        ];
                        $receipt = $query->where('id', $val['id'])->update('purchase_order_receipt', $data);
                        if (!$receipt) {
                            throw new Exception('采购单保存失败');
                        }
                        if($cancel_order_type==1) {
                            //判断采购单状态
                            $order_status = $this->Purchase_order_unarrived_model->get_order_status($val['purchase_number']); //判断采购单状态
                            $order_cancel_ctq = $this->Purchase_order_unarrived_model->get_order_cancel_ctq($cancel_id, $val['purchase_number']); //历史取消数量
                            $order_cancel_ctq_ben = $this->Purchase_order_unarrived_model->get_order_cancel_ctq_ben($cancel_id, $val['purchase_number']); //本次取消数量
                            $confirm_amount = $this->Purchase_order_unarrived_model->get_confirm_amount($val['purchase_number']); //采购数量
                            $instock_qty = $this->Purchase_order_unarrived_model->get_instock_qty($val['purchase_number']); //入库数量
                            // $loss_amount = $this->Purchase_order_unarrived_model->get_loss_amount($val['purchase_number']); //报损数量
                            $cancelinstock_qtyloss_amount = $order_cancel_ctq + $instock_qty + $order_cancel_ctq_ben; //取消数量+入库数量+本次取消数量
                            $this->Purchase_order_unarrived_model->canceled_status($order_status, $cancelinstock_qtyloss_amount, $val['purchase_number'], $confirm_amount, $cancel_id, $instock_qty);
                        }
                        //记录收款流水表
                        $water = [
                            'receipt_id' => $val['id'],
                            'purchase_number' => $val['purchase_number'],
                            'supplier_code' => $val['supplier_code'],
                            'supplier_name' => $val['supplier_name'],
                            'price' => $val['pay_price'],
                            'original_price' => $val['pay_price'],
                            'original_currency' => $val['currency'],
                            'our_branch_short' => $account_short,
                            'our_branch' => $branch,
                            'our_account' => $account_number,
                            'our_account_holder' => $account_holder,
                            'collection_time' => $collection_time,
                            'remarks' => $remarks,
                        ];
                        $query->insert('purchase_order_receipt_water', $water);
                        //记录系统操作日志
                        $log = [
                            'record_number' => $val['id'],
                            'record_type' => '取消未到货',
                            'content' => '财务收款',
                            'content_detail' => '财务收款金额' . $val['pay_price']
                        ];
                        $this->Reject_note_model->get_insert_log($log);
                }
                 //变更取消未到货主表状态
                 $cancel_update = $this->Purchase_order_unarrived_model->change_order_cancel(CANCEL_AUDIT_STATUS_CFYSK, $cancel_id);
                if (empty($cancel_update)) {
                    throw new Exception('采购单-取消未到货数量-主表更新失败');
                }

                $recal_uninvoiced_qty_key = 'recal_uninvoiced_qty';//取消,财务已收款,重新计算未开票数量
                $sku = '';//(采购单维度);
                $this->rediss->set_sadd($recal_uninvoiced_qty_key,sprintf('%s$$%s',$val['purchase_number'],$sku));
                $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$recal_uninvoiced_qty_key);

                  //记录取消未到货操作日志
                $this->Reject_note_model->cancel_log([
                 'cancel_id'=>$cancel_id,
                 'operation_type'=>'财务收款',
                 'operation_content'=>$remarks,
                ]);
                if($cancel_order_type==2){//样品收款更新产品系统
                    $cancel_number = $query->select('cancel_number')
                        ->where('id', $cancel_id)
                        ->get('purchase_order_cancel')
                        ->row_array();
                    $request_url = getConfigItemByName('api_config', 'product_system', 'yibaiProdSamplePurchaseDetail-pushWriteOff');
                    $params= [
                        'createName' =>getActiveUserName(),//获取当前用户名称
                        'createUser'=>getActiveUserId(),
                        'status'=>3,
                        'code'=>$cancel_number['cancel_number'],
                        'reason' =>$remarks
                        ];
                    $header         = array('Content-Type: application/json');
                    $access_token   = getOASystemAccessToken();
                    $request_url    = $request_url.'?access_token='.$access_token;
                    $results        = getCurlData($request_url,json_encode($params),'post',$header);
                    $results        = json_decode($results,true);
                    if(isset($results['code'])) {
                        if ($results['code'] == 200) {
                            $fale = true;
                        } else {
                            $fale = false;
                        }
                    }
                }
            } elseif ($type == 2) { //审核驳回
                 $this->load->model('purchase/purchase_order_model');
                 foreach ($purchase_number as $val) {
                    $data = [
                        'pay_status' => RECEIPT_PAY_STATUS_REJECTED,
                        'payer_id' => getActiveUserId(),
                        'payer_user_name' => getActiveUserName(),
                        'payer_time' => $collection_time,
                        'payer_notice' => $remarks,
                    ];
                    $query->where('id', $val['id'])->update('purchase_order_receipt', $data);
                    //判断该取消未到货主表状态 是否已完成
                    $cancel_update = $this->Purchase_order_unarrived_model->change_order_cancel(CANCEL_AUDIT_STATUS_CFBH, $cancel_id);
                    if (empty($cancel_update)) {
                        throw new Exception('采购单-取消未到货数量-主表更新失败');
                    }
                     //记录取消未到货操作日志
                     $this->Reject_note_model->cancel_log([
                         'cancel_id' => $cancel_id,
                         'operation_type' => '财务驳回',
                         'operation_content' => $remarks,
                     ]);
                    //驳回推送仓库
               //     $this->Purchase_order_unarrived_model->preservation_warehouse_reject($cancel_id);
                    //获取采购单 原来的状态
                    $order_cancel_detail = $this->purchase_db
                            ->select('purchase_order_status')
                            ->where('cancel_id', $cancel_id)
                            ->where('purchase_number', $val['purchase_number'])
                            ->get('purchase_order_cancel_detail')
                            ->row_array();
                    if (empty($order_cancel_detail)) {
                        throw new Exception('参数purchase_number,不存在');
                    }

                    if($cancel_order_type !=2) {
                        //订单状态变为 '退回原来的状态'
                        $order_update = $this->Purchase_order_unarrived_model->change_order_status($val['purchase_number'], $order_cancel_detail['purchase_order_status']);
                        if (empty($order_update)) {
                            throw new Exception('采购单更新失败');
                        }
                    }

                    // 退款冲销 变成驳回
                    $cancelInfo = $this->purchase_db->select('cancel_number')
                        ->where('id',$cancel_id)
                        ->get('purchase_order_cancel')
                        ->row_array();
                    $this->purchase_db->where('record_number',$cancelInfo['cancel_number'])
                        ->where('record_number_relate',$val['purchase_number'])
                        ->update('purchase_order_charge_against_records',['charge_against_status' => CHARGE_AGAINST_STATUE_WAITING_AUDIT_REJECT]);

                    //记录操作日志
                    $log = [
                        'record_number' => $val['purchase_number'],
                        'record_type' => 'PURCHASE_ORDER',
                        'content' => '取消未到货财务驳回',
                        'content_detail' => '采购单号' . $val['purchase_number'] . "取消未到货财务驳回",
                    ];
                    $this->Reject_note_model->get_insert_log($log);
                    //记录系统操作日志
                    $log = [
                        'record_number' => $val['id'],
                        'record_type' => '取消未到货',
                        'content' => '财务收款驳回',
                        'content_detail' => '财务收款驳回金额' . $val['pay_price']
                    ];
                    $this->Reject_note_model->get_insert_log($log);


                    //推送采购单信息至
                    if($cancel_order_type==2){

                        $cancel_number = $query->select('cancel_number')
                            ->where('id', $cancel_id)
                            ->get('purchase_order_cancel')
                            ->row_array();
                        $request_url = getConfigItemByName('api_config', 'product_system', 'yibaiProdSamplePurchaseDetail-pushWriteOff');
                        $params= [
                            'createName' =>getActiveUserName(),//获取当前用户名称
                            'createUser'=>getActiveUserId(),
                            'status'=>4,
                            'code'=>$cancel_number['cancel_number'],
                            'reason' =>$remarks
                        ];
                        $header         = array('Content-Type: application/json');
                        $access_token   = getOASystemAccessToken();
                        $request_url    = $request_url.'?access_token='.$access_token;
                        $results        = getCurlData($request_url,json_encode($params),'post',$header);
                        $results        = json_decode($results,true);

                        if(isset($results['code'])) {
                            if ($results['code'] == 200) {
                                $fale = true;
                            } else {
                                $fale = false;
                                $msg = '操作失败'.isset($results['msg'])?$results['msg']:'';
                            }
                        }
                    }
                }
            }
            if ($query->trans_status() === FALSE or $fale==false) {
                $query->trans_rollback();
                return ['msg' => $msg, 'bool' => false];
            } else {
                $query->trans_commit();
                return ['msg' => '操作成功', 'bool' => TRUE];
            }
        } catch (Exception $exc) {
            return ['msg' => $exc->getMessage(), 'bool' => false];
        }
    } 
   /**
   * 收款详情查看
   *@author harvin 2019-1-19
   * @param  $id 参数id 
   **/
 public function get_receivable_info($id){
        $query = $this->purchase_db;
        $query-> select('id,cancel_id,pay_status,'
                . 'group_concat(purchase_number) as purchase_number,'
                . 'sum(pay_price) as pay_price,supplier_code,'
                . 'supplier_name,pay_type,real_refund_time,'
                . 'apply_notice,apply_user_name,'
                . 'apply_time,audit_user_name,audit_time,'
                . 'payer_user_name,payer_time,payer_notice,currency');
        $query->from('purchase_order_receipt');
        $receipt= $query->where('cancel_id',$id)->get()->row_array();
     
        if(empty($receipt)){
           throw new Exception('参数id,不存在');
        }

        $receipt['pay_status'] = getReceivePayStatus($receipt['pay_status']);
        $cancel_id= isset($receipt['cancel_id'])?$receipt['cancel_id']:0;
        if(empty($cancel_id)){
            throw new Exception('未找到取消未到货表的信息');
        }
        //获取流水截图 及申请备注  审核备注
        $order_cancel= $this->purchase_db
                   ->select('cancel_url,create_note,audit_note,cancel_ctq,serial_number,upload_note')->where('id',$receipt['cancel_id'])
                   ->get('purchase_order_cancel')
                   ->row_array();
        if(empty($order_cancel)){
           throw new Exception('未找到取消未到货主表的信息'); 
        } 
        //获取收款流水
        $receipt_water=$this->purchase_db
                ->select('our_branch_short,our_branch,our_account,our_account_holder')
                ->where('receipt_id',$receipt['id'])
                ->get('purchase_order_receipt_water')
                ->row_array(); 
        $receipt['desc']= '取消未到货退款:'.$receipt['pay_price'].',取消数量'.$order_cancel['cancel_ctq'];
        $receipt['pay_type']= getPayType($receipt['pay_type']);
        $receipt['our_branch_short'] = isset($receipt_water['our_branch_short'])?$receipt_water['our_branch_short']:'';//我方支行简称
        $receipt['our_branch'] = isset($receipt_water['our_branch'])?$receipt_water['our_branch']:'';//我方支行
        $receipt['our_account'] = isset($receipt_water['our_account'])?$receipt_water['our_account']:'';//我方银行卡号
        $receipt['our_account_holder'] = isset($receipt_water['our_account_holder'])?$receipt_water['our_account_holder']:"";//我方开户人
        $receipt['payer_time'] = (empty($receipt['payer_time']) or $receipt['payer_time'] == '0000-00-00 00:00:00')?date('Y-m-d H:i:s'):$receipt['payer_time'];// 默认为当前时间
        $receipt['cancel_url']= isset($order_cancel['cancel_url'])?json_decode($order_cancel['cancel_url'],TRUE):'';
        $receipt['create_note']=isset($order_cancel['create_note'])?$order_cancel['create_note']:'';
        $receipt['audit_note']=isset($order_cancel['create_note'])?$order_cancel['create_note']:'';
        $receipt['serial_number']=isset($order_cancel['serial_number'])?$order_cancel['serial_number']:'';
        $receipt['upload_note']=isset($order_cancel['upload_note'])?$order_cancel['upload_note']:'';
        $receipt['account_short'] = $this->payment_order_pay_model->get_bank();
        //获取上传截图信息
        $this->load->model('purchase/Purchase_order_determine_model');
        $upload_data = $this->Purchase_order_determine_model->get_upload_preview_data($cancel_id, $receipt['real_refund_time']);
        $receipt['upload_data'] = $upload_data;

        return $receipt;
    }

    /**
     * 根据收款的订单信息获取1688退款信息
     * @param $limit
     * @throws Exception
     */
    public function getAliQueryOrderRefund($limit){
        $this->load->library('alibaba/AliOrderApi');
        $query = $this->purchase_db;
        $query-> select('re.purchase_number,pur.pai_number');
        $query->from('purchase_order_receipt re');
        $query->join('purchase_order_pay as pur','pur.pur_number=re.purchase_number');
        $query->join('ali_order_refund as ali','ali.pai_number=pur.pai_number','LEFT');
        $query->where('ali.purchase_number is null and pur.source =2');
        $query->limit($limit);
        $reslut= $query->get()->result_array();
        if(!empty($reslut)){
            foreach ($reslut as $value){
                $aliRefund = $this->aliorderapi->getQueryOrderRefund($value['pai_number']);
                $insertArr =[];
                if($aliRefund['code'] and isset($aliRefund['data']) and is_array($aliRefund['data'])){
                    $insertArr = array(
                        'pai_number'=> $value['pai_number'],
                        'purchase_number'=>$value['purchase_number'],
                        'apply_carriage'=>$aliRefund['data']['applyCarriage'],
                        'apply_payment'=>$aliRefund['data']['applyPayment'],
                        'apply_amount'=>$aliRefund['data']['applyCarriage']+$aliRefund['data']['applyPayment'],
                        'create_time'=>$aliRefund['data']['completedTime'],
                        'completed_time'=>$aliRefund['data']['completedTime'],
                        'apply_reason'=>$aliRefund['data']['applyReason'],
                    );
                    try{
                        $this->purchase_db->insert('ali_order_refund',$insertArr);
                    }catch (Exception $exception){
                        throw  $exception;
                    }
                }
            }
        }
    }

    /**
     * 根据申请单获取信息
     */
    public function receivable_info_item($cancel_number){
        $query = $this->purchase_db;
        $query-> select('can.cancel_number,can.serial_number,re.purchase_number,pay.pai_number,re.pay_price,pur.apply_amount,pur.apply_reason');
        $query->from('purchase_order_cancel can');
        $query->join('purchase_order_receipt as re','re.cancel_id=can.id','LEFT');
        $query->join('purchase_order_pay as pay','re.purchase_number=pay.pur_number','LEFT');
        $query->join('purchase_order_pay_type pur','re.purchase_number=pur.purchase_number','LEFT');
        $query->where('can.cancel_number="'.$cancel_number.'"');
        $data = $query->get()->result_array();
        $key_table = [ '申请编码', '采购订单号','采购单号','拍单号', '应收款金额','收款流水号'];
        $return_data = [
            'key'=>$key_table,
            'values'=>$data
            ];
        return $return_data;
    }

    /**
     * 根据采购单号获取仓库，采购员
     * @param $purchase_number
     * @return array|null
     */
    private function _getOrderInfoByPurNumber($purchase_number){
//        $purchase_number = array_unique(array_filter(explode(',',$purchase_number)));
        if(empty($purchase_number)){
            return null;
        }
        $query = $this->purchase_db;
        $query->select('a.buyer_name,b.warehouse_name');
        $query->from('pur_purchase_order a');
        $query->join('pur_warehouse b','a.warehouse_code=b.warehouse_code','LEFT');
        $query->where('purchase_number',$purchase_number);
        $data = $query->get()->row_array();
        return $data;
    }

    /**
     * 获取取消未到货集合
     * @param $cancel_id
     * @return array
     */
    private function _getCancelInfo($cancel_id){
        if(empty($cancel_id)){
            return null;
        }
        $order_cancel= $this->purchase_db
            ->select('id,upload_note,real_refund_total,create_note,freight,discount')
            ->where('id',$cancel_id)
            ->get('purchase_order_cancel')
            ->row_array();
        return $order_cancel;
    }

    /**
     * 根据采购单号获取sku,产品名称，采购单价，采购数量,到货数量，入库数量
     * @param $purchase_number
     * @return array|null
     */
    private function _getOrderItemByPurNumber($purchase_number){
        if(empty($purchase_number)){
            return null;
        }
        $query = $this->purchase_db;
        $query->select('a.sku,a.product_name,a.purchase_unit_price,a.confirm_amount,b.arrival_qty,b.instock_qty');
        $query->from('pur_purchase_order_items a');
        $query->join('pur_warehouse_results b','b.items_id=a.id','LEFT');
        $query->where('a.purchase_number',$purchase_number);
        $data = $query->get()->row_array();
        return $data;
    }

    /**
     * 修改收款备注
     */
    public function edit_receivable_note($ids,$update_params){
        $cancel_info = $this->purchase_db->query("SELECT cancel_order_type FROM pur_purchase_order_cancel WHERE id='{$ids}'")->row_array();
        if (!empty($cancel_info) and $cancel_info['cancel_order_type'] == 2) {
            $receipt_info = $this->purchase_db->query("SELECT purchase_number FROM pur_purchase_order_receipt WHERE cancel_id='{$ids}'")->row_array();
            if (isset($receipt_info['purchase_number']) and !empty($receipt_info['purchase_number'])) {
                $params = [
                    'po' => $receipt_info['purchase_number'],
                    'remark' => $update_params['payer_notice']
                ];
                $request_url = getConfigItemByName('api_config', 'product_system', 'prodSamplePurchaseOrder-updateRemarkByPo');
                $header = array('Content-Type: application/json');
                $access_token = getOASystemAccessToken();
                $request_url = $request_url . '?access_token=' . $access_token;
                $results = getCurlData($request_url, json_encode($params), 'post', $header);
                $results = json_decode($results, true);
                if (isset($results['code'])) {
                    if ($results['code'] == 200) {
                        $fale = true;
                    } else {
                        $fale = false;
                    }
                }
            }else{
                $fale = true;
            }
        } else {
            $fale = true;
        }
        if ($fale) {
            try{
                $receipt_info = $this->purchase_db->select('id,cancel_id,purchase_number,supplier_code,supplier_name,pay_price,currency')
                    ->where('cancel_id', $ids)
                    ->get('purchase_order_receipt')
                    ->row_array();
                if($receipt_info){
                    $this->Reject_note_model->cancel_log([
                        'cancel_id'=>$ids,
                        'operation_type'=>'财务修改收款备注',
                        'operation_content'=>$update_params['payer_notice'],
                    ]);
                    $fale = $this->purchase_db->where('cancel_id', $ids)->update('purchase_order_receipt', [
                        'payer_notice' => $update_params['payer_notice'],
                        'is_update_for_summary' => 2,// 2标记为已更新
                    ]);

                    $flag = $this->purchase_db->where('receipt_id', $receipt_info['id'])->update('purchase_order_receipt_water', [
                        'our_branch_short' => $update_params['account_short'],
                        'our_branch' => $update_params['branch'],
                        'our_account' => $update_params['account_number'],
                        'our_account_holder' => $update_params['account_holder'],
                    ]);
                }
            }catch (Exception $e){
                $fale = false;
            }
            //添加日志
        }
        return $fale;
    }
}
