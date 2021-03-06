<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Payment_order_pay_model extends Purchase_model {

    protected $table_name = 'purchase_order_pay';//采购单请款单表
    protected $table_entry = 'purchase_order_pay_detail';//采购单详情请款单表
    public function __construct() {
        parent::__construct();

        $this->load->model('supplier/supplier_payment_account_model'); // 请款单 SKU 明细
        $this->load->model('compact/Compact_list_model');
        $this->load->model('supplier/Supplier_images_model');
        $this->load->model('purchase/Purchase_order_determine_model'); 
        $this->load->model('user/purchase_user_model');
        $this->load->model('compact/Compact_model');
        $this->load->model("purchase/Purchase_order_model");
        $this->load->model("system/Bank_card_model");
    }
    /**
     * 拉取结算方式
     * @author harvin 2019-1-16
     * @param type $settlement_code
     * @return type
     */
    public function get_settlement_method($settlement_code = null) {
        if (isset($settlement_code)) {
            $settlement = $this->purchase_db
                            ->select('settlement_name,parent_id')->where('settlement_code', $settlement_code)
                            ->get('supplier_settlement')->row_array();
            if(empty($settlement)){
                return '';
            }
            if(!isset($settlement['parent_id']) || $settlement['parent_id']==0){
                  return isset($settlement)?$settlement['settlement_name']:'';
            }
             $settlement_parent = $this->purchase_db
                            ->select('settlement_name')->where('settlement_code', $settlement['parent_id'])
                            ->get('supplier_settlement')->row_array();
            return isset($settlement_parent)?$settlement_parent['settlement_name'].$settlement['settlement_name']:'';
        } else {
            $settlement = $this->purchase_db
                            ->select('settlement_name,settlement_code')
                            ->get('supplier_settlement')->result_array();
            if(empty($settlement)){
                return [];
            }
            $data = [];
            foreach ($settlement as $value) {
                $data[$value['settlement_code']] = $value['settlement_name'];
            }
            return $data;
        }
    }

    /**
     * get_settlement_method 方法的批量获取
     * @author liwuxue
     * @date 2019/2/15 17:32
     * @param $codes
     * @return array
     */
    public function get_settlement_method_list(array $codes)
    {
        $codes = array_unique(array_filter($codes));
        if (empty($codes)) {
            return [];
        }
        $rows = $this->purchase_db
            ->select("settlement_code,settlement_name")
            ->where_in("settlement_code", $codes)
            ->get("supplier_settlement")
            ->result_array();
        return is_array($rows) ? array_column($rows, "settlement_name", "settlement_code") : [];
    }

    /**
     * 查询 应付款列表
     * @author harvin jolon
     * @date 2019-1-16
     * @param array $params 参数
     * @param int $type 1为合同 2 网络 
     * @return array 
     * * */
    public function get_pay_list($params, $type, $offsets, $limit,$id='',$page=1) {
        $role_name      = get_user_role();//当前登录角色
        $taobao_account = $this->get_account_list(['account_type' => 1]);// 获取 淘宝账号
        $taobao_account = array_column($taobao_account,'purchase_account');

        $query = $this->purchase_db;
        $bulider = clone $query;
        // distinct 去除重复数据
        $query->select('p.id,'
                . 'p.pay_status,'
                . 'p.pur_number,'
                . 'p.requisition_number,'
                . 'p.pai_number,'
                . 'p.supplier_code,'
                . 'p.pay_type,'
                . 'p.pay_category,'
                . 'p.settlement_method,'
                . 'p.payment_platform,'
                . 'p.pay_price,'
                . 'p.product_money,'
                . 'p.freight,'
                . 'p.discount,'
                . 'p.process_cost,'
                . 'p.commission,'
                . 'p.applicant,'
                . 'p.application_time,'
                . 'p.auditor,'
                . 'p.review_time,'
                . 'p.create_notice,'  //请款备注
                . 'p.review_notice,' //审核备注
                . 'p.processing_notice,' //审批备注
                . 'p.purchase_account,'
                . 'p.payment_notice,'   //付款备注
                . 'p.payer_name,sp.is_cross_border,'
                . 'p.payer_time,'
                . 'p.pur_tran_num,'
                . 'p.trans_orderid,'
                . 'p.voucher_address,'
                . 'ppy.accout_period_time as need_pay_time,'
                . 'sp.surplus_quota,'
                . 'sp.tap_date_str,p.payer_id,p.source,p.source_subject,'
                . 'po.is_drawback,'
                . 'po.is_ali_order,'
                . 'po.purchase_order_status,'
                . 'po.purchase_type_id');
        $query->from('purchase_order_pay_detail as pp');
        $query->join('purchase_order_pay as p', 'pp.requisition_number=p.requisition_number', 'left');
        $query->join('pur_supplier as sp', 'sp.supplier_code=p.supplier_code','left');
        $query->join('purchase_order_pay_type as ppy', 'ppy.purchase_number=p.pur_number','left');

        $query->join('purchase_order as po', 'po.purchase_number=pp.purchase_number', 'left');
        if($type==SOURCE_NETWORK_ORDER) {
//            $query->join('purchase_order as po', 'po.purchase_number=p.pur_number', 'left');
        }

        if($type==SOURCE_COMPACT_ORDER){

            // 根据 交易流水号、商户订单号 查询
            if (isset($params['pur_tran_num']) && trim($params['pur_tran_num'])) {
                $pur_tran_num = explode(' ', trim($params['pur_tran_num']));
                $query->where_in('p.pur_tran_num', array_filter($pur_tran_num));
            }
            if (isset($params['trans_orderid']) && trim($params['trans_orderid'])) {
                $trans_orderid = explode(' ', trim($params['trans_orderid']));
                $query->where_in('p.trans_orderid', array_filter($trans_orderid));
            }
        }
        $data_role= getRolefinance();
        $res_arr=array_intersect($role_name, $data_role);
        if($res_arr){ //产品开发只能查看样品样品请款 
          $query->like('p.requisition_number','YPP','after');   
        }
        if (isset($params['supplier_code']) && $params['supplier_code']) { //供应商
            $query->where('p.supplier_code', $params['supplier_code']);
        }

        // 应付款-网采：增加拍单号的筛选项，可以多选：多个拍单号之间以空格连接
        if (isset($params['pai_number']) && trim($params['pai_number']) && $type==SOURCE_NETWORK_ORDER) {
            $pai_number = explode(' ', trim($params['pai_number']));
            $query->where_in('p.pai_number', array_filter($pai_number));
        }

        if (isset($params['applicant']) && is_numeric($params['applicant'])) {//申请人 前端选择"空"的时候 applicant =0
            $query->where('p.applicant', $params['applicant']);
        }
        if (isset($params['pay_status']) && $params['pay_status']) {//付款状态
            $query->where('p.pay_status', $params['pay_status']);
        }

        if (isset($params['purchase_type_id']) && $params['purchase_type_id'] != "" && $type==SOURCE_NETWORK_ORDER) {//业务线(网采单)
            $query->where('po.purchase_type_id', $params['purchase_type_id']);
        }

        if (isset($params['purchase_type_id']) && $params['purchase_type_id'] != "" && $type==SOURCE_COMPACT_ORDER) {//业务线(合同单)
            $query->where('po.purchase_type_id', $params['purchase_type_id']);
        }


        if (isset($params['pay_type']) && $params['pay_type'] != '') { //支付方式
            $query->where('p.pay_type', $params['pay_type']);
        }
        if (isset($params['pay_category']) && $params['pay_category']) {//请款类型
            $query->where('p.pay_category', $params['pay_category']);
        }
        if (isset($params['settlement_method']) && $params['settlement_method']) {//结算方式
            $query->where('p.settlement_method', $params['settlement_method']);
        }
        if (isset($params['is_cross_border']) && $params['is_cross_border'] != '') { //跨境宝供应商
            $query->where('sp.is_cross_border', $params['is_cross_border']);
        }
        // 来源主体（1合同 2网采 3对账单）
        if(isset($params['is_statement_pay']) and $params['is_statement_pay']!=""){
            if($params['is_statement_pay'] == '3'){
                $query->where('p.source_subject=3');
            }else{
                $query->where('p.source_subject<>3');
            }
        }
        //审批人筛选
        if (isset($params['approver_type']) && isset($params['approver_user']) && $params['approver_type']!='' && $params['approver_user']!='') { //审批人

            if($params['approver_type']==PAY_WAITING_FINANCE_REVIEW){//财务审核

                $query->where('p.approver', $params['approver_user']);

            }elseif ($params['approver_type']==PAY_UFXFUIOU_SUPERVISOR){  //财务主管审核

                $query->where('p.financial_supervisor_id', $params['approver_user']);

            }elseif ($params['approver_type']==PAY_UFXFUIOU_MANAGER){     //财务经理审核

                $query->where('p.financial_manager_id', $params['approver_user']);

            }elseif ($params['approver_type']==PAY_UFXFUIOU_SUPPLY){      //财务总监审核

                $query->where('p.financial_officer_id', $params['approver_user']);

            }elseif ($params['approver_type']==PAY_GENERAL_MANAGER){      //总经办审核
                $query->where('p.general_manager_id', $params['approver_user']);
            }
        }
        //是否退税
        if(isset($params['is_drawback']) && $params['is_drawback']!=''){
            $query->where('po.is_drawback', $params['is_drawback']);
        }
        if(isset($params['statement_number']) && !empty($params['statement_number'])){//对账单号
            $statement_numbers = query_string_to_array($params['statement_number']);
            $statement_numbers= array_unique($statement_numbers);
            $query->where_in('p.pur_number',$statement_numbers)->where('p.source_subject',SOURCE_SUBJECT_STATEMENT_ORDER);
            unset($compact_number_list);
        }
        //账号绑定付款人
        if(isset($params['pay_user_id']) && $params['pay_user_id']!=''){
            $pay_user_id= trim($params['pay_user_id']);
//            $account_sub=$query->query("SELECT account,user_id FROM pur_alibaba_sub WHERE status=1 and pay_user_id='{$pay_user_id}'")->result_array();
//            if(!empty($account_sub)){
//                $user_id= is_array($account_sub)? array_column($account_sub, 'user_id'):0;
//                $account= is_array($account_sub)? array_column($account_sub, 'account'):'';
//                $query->where_in('p.applicant',$user_id);
//                $query->where_in('p.purchase_account',$account);
//            }else{
//               $query->where_in('p.applicant','');
//               $query->where_in('p.purchase_account','');
//            }
            $query->where('p.payer_id', $pay_user_id);
        }

        //账号绑定付款人
        if(isset($params['account_pay']) && $params['account_pay']!=''){
            $pay_user_id= trim($params['account_pay']);
            $account_sub=$query->query("SELECT account,user_id FROM pur_alibaba_sub WHERE status=1 and pay_user_id='{$pay_user_id}'")->result_array();
            if(!empty($account_sub)){
                $account= is_array($account_sub)? array_column($account_sub, 'account'):'';
                $query->where_in('p.purchase_account',$account);
            }else {
                $query->where('p.purchase_account', 1);
            }
        }

        if(isset($params['pur_number']) && $params['pur_number']){ // 采购单号或合同号批量搜索 
            $pur_numbers = explode (' ', trim($params['pur_number']));
            $requisition_number = $bulider->select('requisition_number')
                                        ->from('purchase_order_pay_detail')
                                        ->where_in('purchase_number',$pur_numbers)
                                        ->get()
                                        ->result_array(); 
            $query->group_start();
            $query->where_in('p.pur_number',  array_filter($pur_numbers));
            if(is_array($requisition_number) && !empty($requisition_number)){
                $requisition_number_arr = array_unique(array_column($requisition_number,'requisition_number'));
                $query->or_where_in('p.requisition_number',$requisition_number_arr);
            }
            $query->group_end();
        }
        if(isset($params['requisition_number']) && $params['requisition_number']){ //请款单号
            $query->where('p.requisition_number', $params['requisition_number']);
        }
        if (isset($params['create_time_start']) and $params['create_time_start'])// 创建时间-开始
            $query->where('p.create_time>=', $params['create_time_start']);
        if (isset($params['create_time_end']) and $params['create_time_end'])// 创建时间-结束
            $query->where('p.create_time<=', $params['create_time_end']);
        if(isset($params['id']) && $params['id']){
            $ids= explode(',', $params['id']);
            $query->where_in('p.id', $ids);
        }

        if(isset($params['purchase_account']) && trim($params['purchase_account'])){//账号查询
            $query->like('p.purchase_account', trim($params['purchase_account']),'after');
        }
        if(isset($params['purchase_account_type']) and trim($params['purchase_account_type'])){
            if(trim($params['purchase_account_type']) == 1){// 账号类型为 1.淘宝
                $query->where_in('p.purchase_account',$taobao_account);
            }else{
                $query->where_not_in('p.purchase_account',$taobao_account);
            }
        }
        if(isset($params['is_ali_order']) and $params['is_ali_order'] != ''){
            $query->where('po.is_ali_order',intval($params['is_ali_order']));
        }
        if (isset($params['purchase_order_status']) and $params['purchase_order_status']){
            if(!is_array($params['purchase_order_status'])) {
                $params['purchase_order_status'] = explode(",",$params['purchase_order_status']);
            }
            $query->where_in("po.purchase_order_status",$params['purchase_order_status']);
        }

        if ($type == SOURCE_COMPACT_ORDER) {//合同单
            $query->where_in('p.pay_status', [PAY_WAITING_FINANCE_REVIEW,PAY_SOA_REJECT, PAY_FINANCE_REJECT, PAY_WAITING_FINANCE_PAID, PART_PAID, PAY_PAID,PAY_UFXFUIOU_REVIEW,PAY_UFXFUIOU_SUPERVISOR,PAY_UFXFUIOU_MANAGER,PAY_UFXFUIOU_SUPPLY,PAY_GENERAL_MANAGER,PAY_REJECT_SUPERVISOR,PAY_REJECT_MANAGER,PAY_REJECT_SUPPLY,PAY_GENERAL_MANAGER_REJECT,PAY_UFXFUIOU_BAOFOPAY,PAY_LAKALA]);
        }

        //应付款时间
        if(isset($params['need_pay_time_start']) and $params['need_pay_time_start'] and isset($params['need_pay_time_end']) and $params['need_pay_time_end']){//应付款时间
            $start_time = date('Y-m-d 00:00:00',strtotime($params['need_pay_time_start']));
            $end_time = date('Y-m-d 00:00:00',strtotime($params['need_pay_time_end']));
            $query->where("ppy.accout_period_time between '{$start_time}' and '{$end_time}' ");// 应付款时间
        }

        //实际付款时间
        if(isset($params['pay_time_start']) and $params['pay_time_start'] and isset($params['pay_time_end']) and $params['pay_time_end']){//应付款时间
            $start_time = date('Y-m-d 00:00:00',strtotime($params['pay_time_start']));
            $end_time = date('Y-m-d 00:00:00',strtotime($params['pay_time_end']));
            $query->where("p.payer_time between '{$start_time}' and '{$end_time}' ");// 应付款时间
        }

        if(isset($params['pay_notice']) and $params['pay_notice']){//付款提醒状态
            if ($params['pay_notice'] == TAP_DATE_WITHOUT_BALANCE){//查询额度不足的供应商

                $supplier_settlement_online = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;

                $supplier_res = $query->query("SELECT supplier_code FROM pur_supplier WHERE supplier_settlement='{$supplier_settlement_online}' and pur_supplier.quota <> 0 and pur_supplier.surplus_quota <= 0")->result_array();
                $supplier_res = !empty($supplier_res)?array_column($supplier_res,'supplier_code'):[ORDER_CANCEL_ORSTATUS];
                $supplier_res= array_unique($supplier_res);

                $query->where_in("p.supplier_code",$supplier_res);

            }

            if ($params['pay_notice'] != TAP_DATE_WITHOUT_BALANCE){//查询额度足够的供应商
                $supplier_settlement_online = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;
                /*$supplier_res = $query->query("SELECT supplier_code FROM pur_supplier WHERE supplier_settlement='{$supplier_settlement_online}' and pur_supplier.quota <> 0 and pur_supplier.surplus_quota > 0")->result_array();
                $supplier_res = !empty($supplier_res)?array_column($supplier_res,'supplier_code'):[ORDER_CANCEL_ORSTATUS];
                $supplier_res= array_unique($supplier_res);

                $query->where_in("p.supplier_code",$supplier_res);*/

                $query->where("ppy.accout_period_time != '0000-00-00 00:00:00'");

                if ($params['pay_notice'] == TAP_DATE_OVER_TIME){//已超期
                    $today = date('Y-m-d H:i:s');
                    $query->where("ppy.accout_period_time<'{$today}'");
                }

                if ($params['pay_notice'] == TAP_DATE_COMING_SOON){//即将到期
                    $today = date('Y-m-d H:i:s');
                    $five_days_later = date('Y-m-d H:i:s',strtotime('+ 5 days'));
                    $query->where("ppy.accout_period_time >= '{$today}'");
                    $query->where("ppy.accout_period_time < '{$five_days_later}'");
                }
                if ($params['pay_notice'] == TAP_DATE_CAN_WAIT){//可继续等待
                    $five_days_later = date('Y-m-d H:i:s',strtotime('+ 5 days'));
                    $query->where("ppy.accout_period_time >= '{$five_days_later}'");
                }

            }
        }

        if(isset($params['payment_platform'])&& $params['payment_platform']=='6'){
//             $supplier_code_list = $query->query("SELECT supplier_code FROM pur_supplier_payment_account WHERE payment_platform='{$params['payment_platform']}'")->result_array();
//             $supplier_code_list= !empty($supplier_code_list)?array_column($supplier_code_list, 'supplier_code'):['xxxxxxx'];
//             $query->where_in("p.supplier_code",$supplier_code_list);
             $query->join('pur_supplier_payment_info spa','p.supplier_code = spa.supplier_code AND spa.is_del = 0','right');
             $query->where('spa.payment_platform',$params['payment_platform']);
             $query->where("p.pay_type",PURCHASE_PAY_TYPE_PRIVATE);

             unset($supplier_code_list);
        }
        //支付方式=线下境内，那么默认支付平台=网银
        if(isset($params['payment_platform'])&& $params['payment_platform']=='5'){
//            $supplier_code_list = $query->query("SELECT supplier_code FROM pur_supplier_payment_account WHERE payment_platform='{$params['payment_platform']}'")->result_array();
//            $supplier_code_list= !empty($supplier_code_list)?array_column($supplier_code_list, 'supplier_code'):['xxxxxxx'];
//            $query->where_in("p.supplier_code",$supplier_code_list);
            $query->join('pur_supplier_payment_info spa','p.supplier_code = spa.supplier_code AND spa.is_del = 0','right');
            $query->where('spa.payment_platform',$params['payment_platform']);
            $query->where("p.pay_type",PURCHASE_PAY_TYPE_PUBLIC);
            unset($supplier_code_list);
        }

        //支付方式≠线下境内/线下境外，那么默认支付平台=其他
        if(isset($params['payment_platform'])&& $params['payment_platform']=='1'){
//            $supplier_code_list = $query->query("SELECT supplier_code FROM pur_supplier_payment_account WHERE payment_platform not in (5,6)")->result_array();
//            $supplier_code_list= !empty($supplier_code_list)?array_column($supplier_code_list, 'supplier_code'):['xxxxxxx'];
//            $query->where_in("p.supplier_code",$supplier_code_list);
            $query->join('pur_supplier_payment_info spa','p.supplier_code = spa.supplier_code AND spa.is_del = 0','right');
            $query->where_not_in('spa.payment_platform',[5,6]);
            $query->where_not_in('p.pay_type',[PURCHASE_PAY_TYPE_PUBLIC,PURCHASE_PAY_TYPE_PRIVATE]);
            unset($supplier_code_list);
        }

        unset($params);
        $query->where('p.source', $type);
//        $count_qb = clone $query;
//        $query->group_by('p.id')->order_by('p.id','desc');
//        $query->limit($limit, $offsets);
        $count_qb = clone $query;
        $count_price = clone $query;
        $data = $query->limit($limit, $offsets)->group_by('p.requisition_number')->order_by('p.id','desc')->get()->result_array();
        //获取供应商名
         $supplier_code= is_array($data)?array_column($data, 'supplier_code'):[];
         $warehouse_code_list = $this->get_supplier_name($supplier_code);
         //获取结算方式
         $settlement_method= is_array($data)?array_column($data, 'settlement_method'):[];
         $this->load->model("supplier/Supplier_settlement_model");
         $supplier_code_list = $this->Supplier_settlement_model->get_code2name_list($settlement_method);
         //获取1688子账号绑定付款人id
         $applicant_list=is_array($data)?array_column($data, 'applicant'):[];
         $pay_user_name_list= $this->get_account_sub($applicant_list);
        //
        $purchase_account_list =is_array($data)?array_column($data, 'purchase_account'):[];

        $pay_user_name_list_new = $this->get_account_sub_new($purchase_account_list);


         //获取对对账号集合
         $pur_number_list= is_array($data)?array_column($data, 'pur_number'):[];
         $statement_number_arr= $this->get_statement_number($pur_number_list);
        //统计总数
        $count_row = $count_qb->select("count(DISTINCT p.id) as num")->get()->row_array();
        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        //总计金额
        $all_pay_id = $count_price->distinct()->select("p.id")->get()->result_array();
        $all_pay_price = 0;
        if(!empty($all_pay_id)){
        $all_pay_price = $this->get_order_pay_price($all_pay_id);
        }

        $sum_price = 0;
        $all_user_list = get_buyer_name();

        foreach ($data as $key => $val) {
            //支付平台
            $payment_platform = $this->Supplier_payment_info_model->get_payment_platform($val['supplier_code'],$val['is_drawback'],$val['purchase_type_id'],$val['pay_type']);
            $data[$key]['payment_platform'] = get_supplier_payment_platform($payment_platform['payment_platform']??'');
            //判断线上账期付款提醒状态
            $data[$key]['pay_notice'] = formatAccoutPeriodTime($data[$key]['settlement_method'], $data[$key]['pay_status'], $data[$key]['need_pay_time'], $data[$key]['surplus_quota']);
            $data[$key]['statement_number']= isset($statement_number_arr[$val['pur_number']])?$statement_number_arr[$val['pur_number']]:'';
            $data[$key]['status'] = $val['pay_status'];
            $data[$key]['pay_status'] = isset($val['pay_status'])?getPayStatus($val['pay_status']):'';
            $data[$key]['supplier_name'] = isset($warehouse_code_list[$val['supplier_code']])?$warehouse_code_list[$val['supplier_code']]:"";
            $data[$key]['pay_type'] = isset($val['pay_type'])?getPayType($val['pay_type']):'';
            $data[$key]['settlement_method'] = isset($supplier_code_list[$val['settlement_method']])?$supplier_code_list[$val['settlement_method']]:'';
            $data[$key]['applicant']= isset($all_user_list[$val['applicant']]) ? $all_user_list[$val['applicant']]: '';
            $data[$key]['auditor']= isset($all_user_list[$val['auditor']]) ? $all_user_list[$val['auditor']] :  '';
            $data[$key]['pay_category'] = isset($val['pay_category']) ? getPayCategory($val['pay_category']) : "";
            $data[$key]['is_drawback'] = isset($val['is_drawback']) ? getIsDrawbackShow($val['is_drawback']) : "";
            $data[$key]['is_ali_order'] = isset($val['is_ali_order'])?getIsAliOrder($val['is_ali_order']):'';
            $sum_price+=$val['pay_price'];
//            if(in_array($val['pay_category'],[2,3])){// 请款类型为-采购货款、采购货款+运费/优惠，才需展示该字段
            $data[$key]['purchase_order_status'] = isset($val['purchase_order_status'])?getPurchaseStatus($val['purchase_order_status']):'';
//            }else{
//                $data[$key]['purchase_order_status'] = '';
//            }

            if($val['source']==SOURCE_NETWORK_ORDER){

                 $pay_user_name= isset($pay_user_name_list[$val['applicant']."-".$val['purchase_account']])?$pay_user_name_list[$val['applicant']."-".$val['purchase_account']]:'';
                 $data[$key]['payer_name'] = empty($val['payer_name']) ? $pay_user_name : $val['payer_name'];

                 if(empty($pay_user_name) and  empty($val['payer_name'])){
                     $pay_user_name = isset($pay_user_name_list_new[$val['purchase_account']])?$pay_user_name_list_new[$val['purchase_account']]:'';
                     $data[$key]['payer_name'] =  $pay_user_name;
                 }

                //根据请款人与请款的采购子账号获取 付款人信息
                $account_sub = $this->purchase_db->query("SELECT pay_user_name FROM pur_alibaba_sub WHERE status=1 and account='{$val['purchase_account']}'")->row_array();
                if(!empty($account_sub)){
                    $data[$key]['account_pay']  = $account_sub['pay_user_name'];
                }else{
                    //不存在是用 采购子账号获取对应的付款人
                    $data[$key]['account_pay'] ='';
                 }
            }else{
                $data[$key]['account_pay'] ='';
            }
            // 采购账号子账号的只显示主账号
            $purchase_account = $data[$key]['purchase_account'];
            if($purchase_account != 'yibaisuperbuyers' and stripos($purchase_account,':') !== false ){
                $accunt_arr = explode(':',$purchase_account);
                $data[$key]['purchase_account'] = isset($accunt_arr[0])?$accunt_arr[0]:$purchase_account;
            }
            if($val['source']==SOURCE_COMPACT_ORDER and $val['source_subject'] == SOURCE_SUBJECT_COMPACT_ORDER){
                $amount_list = $this->Compact_model->calculate_compact_related_amount($val['pur_number']);
                if($amount_list['code'] === false){
                    $return['msg'] = $amount_list['msg'];
                    return $return;
                }
                $data[$key]['paid_total_real_money'] = isset($amount_list['paid_total_real_money']) ? $amount_list['paid_total_real_money'] : 0;
                $data[$key]['available_product_money'] = isset($amount_list['available_product_money']) ? $amount_list['available_product_money'] : 0;
                $data[$key]['account_pay'] ='';//合同单账号付款人默认给空值
            }

            $data[$key]['check_status'] = $this->get_check_status($val['requisition_number']);
            $data[$key]['is_statement_pay'] = getIsStatementPay($val['source_subject']);

            unset($data[$key]['surplus_quota']);
        }


        $this->load->model('user/purchase_user_model');
        $applicant = $this->purchase_user_model->get_list();
        $applicant = array_column($applicant,'name','id');
        $applicant = ['0' => '空'] + $applicant;

        $data_list['applicant'] = $applicant; //申请人->采购员
        $data_list['pay_status'] = payment_status(); //付款状态
        $data_list['purchase_type_id'] = getPurchaseType(); //业务线
        $data_list['approver'] = get_buyer_name(); //审批人
        $data_list['pay_type'] = getPayType(); //支付方式
        $data_list['pay_notice_status'] = getPayNotice_Status(); //付款提醒状态
        $data_list['settlement_method'] = $this->payment_order_pay_model->get_settlement_method(); //结算方式
        $data_list['is_cross_border'] = getCrossBorder(); //跨境宝供应商
        $data_list['pay_category'] = getPayCategory();// 请款类型
        $data_list['payment_platform'] =['6'=>'富友','5'=>'网银','1'=>'其他'];// 支付平台
        $finance = $this->purchase_user_model->get_finance_list();
        $data_list['pay_user_id']= is_array($finance)?array_column($finance, 'name','id'):[];
       $data_list['approver_type'] = $this->approver_type();
       $data_list['is_drawback'] = getIsDrawback();
       if($type == SOURCE_NETWORK_ORDER){// 网采单 专有查询条件
           $data_list['is_ali_order']          = getIsAliOrder();
           $data_list['purchase_order_status'] = getPurchaseStatus(); //付款状态
           $data_list['purchase_account_type'] = purchaseAccountType(); //账号类型
       }else{
           $data_list['is_statement_pay'] = getIsStatementPay();//是否对账单请款
       }
        $key_table = ['付款状态', '单号', '付款信息', '申请金额/运费','请款类型', '申请人/申请时间', '审核人/审核时间', '线上账期时间', '应付款时间', '付款提醒状态', '付款人/付款时间','数量', '请款备注','采购审核备注','财务审核备注','付款备注','是否退税'];
        $return_data = [
           'drop_down_box'=>$data_list,
            'key'=>$key_table,
            'values'=>$data,
             'paging_data'=>[
                'total'=>$total_count,
                'offset'=>$page,
                'limit'=>$limit,
                'pages'=> ceil($total_count/$limit),
            ],

            'aggregate_data' => [
                'sum_price'=> sprintf('%.3f',$sum_price),
                'all_pay_price'=>sprintf('%.3f',$all_pay_price)
            ]
        ];
        return $return_data;
    }

    /**
     * 查询 应付款列表
     * @author yefanli
     * @date 20200901
     * @param array $params 参数
     * @param int $type 1为合同 2 网络
     * @return array
     * * */
    public function new_get_pay_list($params, $type, $offsets=0, $limit=0, $id='',$page=1, $action=false)
    {
        $taobao_account = $this->get_account_list(['account_type' => 1]);// 获取 淘宝账号
        $taobao_account = array_column($taobao_account,'purchase_account');

        $query = clone $this->purchase_db;
        // 24719
        $fileds = 'p.id, p.pay_status, p.pur_number, p.requisition_number, p.pai_number,'
            . 'p.supplier_code, p.pay_type, p.pay_category, p.settlement_method, p.payment_platform,'
            . 'p.pay_price,p.product_money, p.freight, p.discount, p.process_cost, p.commission, p.applicant,'
            . 'p.source_subject, p.application_time, p.auditor, p.review_time, p.create_notice, p.review_notice,' //审核备注
            . 'p.processing_notice, p.purchase_account, p.payment_notice, p.payer_name,sp.is_cross_border,'
            . 'p.payer_time,p.pay_account, p.pur_tran_num, p.trans_orderid, p.voucher_address, pp.check_status, p.need_pay_time,'
            . 'sp.surplus_quota, sp.tap_date_str,p.payer_id,p.source,p.source_subject, po.is_drawback, po.is_ali_order,'
            . 'po.purchase_number, po.purchase_order_status, po.purchase_type_id, bank.account_number';
        $query->from('purchase_order_pay_detail as pp');
        if($action == 'export' || $action == 'export_sum'){
            $fileds = 'p.pur_number,p.source,pp.requisition_number, pp.purchase_number, pp.id as product_line_id, pp.sku, pp.product_name,
                p.purchase_account, p.pai_number, p.application_time, p.payer_time,
                po.warehouse_code, po.buyer_name, po.purchase_order_status,
                pp.purchase_unit_price, pp.purchase_amount, pp.check_status,
                wrm.arrival_qty as receive_amount, wrm.instock_qty as upselft_amount, wrm.instock_qty as upselft_amount_sj,it.product_line_name,
                wrm.instock_qty_more,sum(los.loss_amount) as loss_amount,
                los.status as loss_status,
                wr.defective_num,
                p.product_money, p.freight, p.discount, p.process_cost, p.pay_price, p.supplier_code, p.payment_notice,
                p.pay_status, p.pay_type, p.payment_platform, p.settlement_method, p.applicant, p.payer_name,
                sp.supplier_name,
                p.source_subject,
                po.is_ali_order,
                p.pur_tran_num, p.trans_orderid,
                p.source_subject,
                bank.account_number';

            $query->join('pur_purchase_order_items as it', 'pp.purchase_number=it.purchase_number and pp.sku=it.sku', 'left');
            $query->join('pur_purchase_order_reportloss as los', 'pp.purchase_number=los.pur_number and pp.sku = los.sku', 'left');
            $query->join('pur_warehouse_results as wr', 'pp.purchase_number=wr.purchase_number and pp.sku=wr.sku', 'left');
            $query->join('pur_warehouse_results_main as wrm', 'wrm.items_id=it.id', 'left');
        }
        if($action != 'export_sum')$query->select($fileds);
        $query = $this->pay_list_params($params, $query, $type, $taobao_account, $action);
        $count_qb = clone $query;
        $count_price = clone $query;
        if($limit > 0)$query->limit($limit, $offsets);
        if($action != 'export' && $action != 'export_sum'){
            $query->group_by('p.requisition_number')->order_by('p.id','desc');
        }
        if($action == 'export'){
            $query->group_by('pp.requisition_number,pp.purchase_number,pp.sku')->order_by('p.id','desc');
        }
        $data = $query->get()->result_array();
    //    echo $query->last_query();exit;

        if($action == 'export' || $action == 'export_sum'){
            if($action == 'export_sum'){
                //统计总数
                $count_row = $count_qb->select("count(DISTINCT p.id) as num")->get()->row_array();
                $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
                return $total_count;
            }
            // 获取：、一级产品线、对账单号  array_chunk

            $query_number = array_column($data, "purchase_number");
            $query_sku = array_column($data, "sku");
            //  对账单
            $statement_list = [];
            if(count($query_number) > 0) {
                $statement = $this->purchase_db->from('purchase_statement_items')
                    ->select('statement_number,purchase_number')
                    ->where_in('purchase_number', $query_number)
                    ->or_where_in('compact_number', $query_number)
                    ->get()->result_array();
                foreach ($statement as $val) {
                    $statement_list[$val['purchase_number']] = $val['statement_number'];
                }
            }
            $statement_key = array_keys($statement_list);

            // 产品线
            $pl_list = [];
            if(count($query_number) > 0 && count($query_sku) > 0) {
                $pl_data = $this->purchase_db->from('purchase_suggest_map as m')
                    ->select('s.sku,s.product_line_name,m.purchase_number')
                    ->join('pur_purchase_suggest as s', "m.demand_number=s.demand_number", 'left')
                    ->where_in('s.sku', $query_sku)
                    ->where_in('m.purchase_number', $query_number)
                    ->get()
                    ->result_array();
                foreach ($pl_data as $val) {
                    $str_sku = $val['purchase_number'] . '_' . $val['sku'];
                    if (!in_array($str_sku, array_keys($pl_list))) $pl_list[$str_sku] = $val['product_line_name'];
                }
            }
            $pl_key = array_keys($pl_list);

            $res_data = [];
            foreach ($data as $val){
                $str_sku_d = $val['purchase_number'].'_'.$val['sku'];
                $val['statement_number'] = in_array($val['purchase_number'], $statement_key) ? $statement_list[$val['purchase_number']] : '';
                $val['product_line_name'] = !empty($val['product_line_name'])?$val['product_line_name']:(in_array($str_sku_d, $pl_key) && !empty($val['sku'])?$pl_list[$str_sku_d]: '');
                $res_data[] = $val;
            }

            return $res_data ??false;
        }

        //统计总数
        $count_row = $count_qb->select("count(DISTINCT p.id) as num")->get()->row_array();
        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        //总计金额
        $all_pay_id = $count_price->distinct()->select("p.id")->get()->result_array();

        $all_pay_price = 0;
        if(!empty($all_pay_id)){
            $all_pay_price = $this->get_order_pay_price($all_pay_id);
        }
        $handle = $this->handle_result_data($data);
        $return_data = [
            'values'=>$handle['data'],
            'paging_data' => [
                'total'=>$total_count,
                'offset'=>$page,
                'limit'=>$limit,
                'pages'=> ceil($total_count/$limit),
            ],
        ];
        if($action == 'list'){
            $key_table = ['付款状态', '单号', '付款信息', '申请金额/运费','请款类型', '申请人/申请时间', '审核人/审核时间',
                '线上账期时间', '应付款时间', '付款提醒状态', '付款人/付款时间','数量', '请款备注','采购审核备注',
                '财务审核备注','付款备注','是否退税'];
//            $return_data['drop_down_box']= $this->get_drop_down_box($type);
            $return_data['key'] = $key_table;
            $return_data['aggregate_data'] = [
                'sum_price'=> sprintf('%.3f',$handle['sum_price']),
                'all_pay_price'=>sprintf('%.3f',$all_pay_price)
            ];
        }
        return $return_data;
    }

    /**
     * 获取仓库
     */
    public function get_warehouse_data()
    {
        $res = [];
        $data = $this->purchase_db
            ->from("warehouse")
            ->select('warehouse_name, warehouse_code')
            ->get()
            ->result_array();
        if($data && count($data) > 0){
            foreach ($data as $v){
                $res[$v['warehouse_code']] = $v['warehouse_name'];
            }
        }
        return $res;
    }

    /**
     * 获取结算方式
     */
    public function get_settlement_method_data()
    {
        $res = [];
        $data = $this->purchase_db
            ->from("supplier_settlement")
            ->select('settlement_name, settlement_code')
            ->get()
            ->result_array();
        if($data && count($data) > 0){
            foreach ($data as $v){
                $res[$v['settlement_code']] = $v['settlement_name'];
            }
        }
        return $res;
    }

    /**
     * 获取采购员
     */
    public function get_purchase_user_data()
    {
        $res = [];
        $data = $this->purchase_db
            ->from("purchase_user_info")
            ->select('user_id, user_name')
            ->get()
            ->result_array();
        if($data && count($data) > 0){
            foreach ($data as $v){
                $res[$v['user_id']] = $v['user_name'];
            }
        }
        return $res;
    }

    /**
     * 获取列表的 drop_down_box  数据
     */
    public function get_drop_down_box($type)
    {
        $data_list = [];
        $this->load->model('user/purchase_user_model');
        $applicant = $this->purchase_user_model->get_list();
        $applicant = array_column($applicant,'name','id');
        $applicant = ['0' => '空'] + $applicant;

        $data_list['applicant'] = $applicant; //申请人->采购员
        $data_list['pay_status'] = payment_status(); //付款状态
        $data_list['purchase_type_id'] = getPurchaseType(); //业务线
        $data_list['approver'] = get_buyer_name(); //审批人
        $data_list['pay_type'] = getPayType(); //支付方式
        $data_list['pay_notice_status'] = getPayNotice_Status(); //付款提醒状态
        $data_list['settlement_method'] = $this->payment_order_pay_model->get_settlement_method(); //结算方式
        $data_list['is_cross_border'] = getCrossBorder(); //跨境宝供应商
        $data_list['pay_category'] = getPayCategory();// 请款类型
        $data_list['payment_platform'] =['6'=>'富友','5'=>'网银','1'=>'其他'];// 支付平台
        $finance = $this->purchase_user_model->get_finance_list();
        $data_list['pay_user_id']= is_array($finance)?array_column($finance, 'name','id'):[];
        $data_list['approver_type'] = $this->approver_type();
        $data_list['is_drawback'] = getIsDrawback();
        $this->load->model('user/User_group_model', 'User_group_model');
        $data_list['group_list'] = $this->User_group_model->getGroupList([1,2]);
        if($type == SOURCE_NETWORK_ORDER){// 网采单 专有查询条件
            $data_list['is_ali_order']          = getIsAliOrder();
            $data_list['purchase_order_status'] = getPurchaseStatus(); //付款状态
            $data_list['purchase_account_type'] = purchaseAccountType(); //账号类型
        }else{
            $data_list['is_statement_pay'] = getIsStatementPay();//是否对账单请款
        }

        $data_list['account_number']  = $this->Bank_card_model->get_account_number(); //支付账号筛选
        return $data_list;
    }

    /**
     * 应付款列表、导出、统计条件
     */
    public function pay_list_params($params, $query, $type, $taobao_account, $action=false, $role_list = [])
    {
        $role_name = count($role_list) > 0 ? $role_list : get_user_role();//当前登录角色
        $query->join('pur_purchase_order_pay as p', 'pp.requisition_number=p.requisition_number', 'inner');
        $query->join('pur_supplier as sp', 'sp.supplier_code=p.supplier_code','left');
        $query->join('pur_purchase_order_pay_type as ppy', 'ppy.purchase_number=p.pur_number','left');
        $query->join('pur_purchase_order as po', 'po.purchase_number=pp.purchase_number', 'left');
        $query->join('pur_bank_card as bank', 'p.pay_account = bank.account_short', 'left');
        //if($type==SOURCE_NETWORK_ORDER) {}
        //是否已经关联明细
        $is_entry = false;
        if($type==SOURCE_COMPACT_ORDER){
            // 根据 交易流水号、商户订单号 查询
            if (isset($params['pur_tran_num']) && trim($params['pur_tran_num'])) {
                $pur_tran_num = explode(' ', trim($params['pur_tran_num']));
                $query->where_in('p.pur_tran_num', array_filter($pur_tran_num));
            }
            if (isset($params['trans_orderid']) && trim($params['trans_orderid'])) {
                $trans_orderid = explode(' ', trim($params['trans_orderid']));
                $query->where_in('p.trans_orderid', array_filter($trans_orderid));
            }
            $is_entry = true;
        }
        $data_role= getRolefinance();
        $res_arr=array_intersect($role_name, $data_role);
        if($res_arr){ //产品开发只能查看样品样品请款
            $query->like('p.requisition_number','YPP','after');
        }
        if (isset($params['supplier_code']) && !empty($params['supplier_code'])) { //供应商
            $query->where('p.supplier_code', $params['supplier_code']);
        }

        // 应付款-网采：增加拍单号的筛选项，可以多选：多个拍单号之间以空格连接
        if (isset($params['pai_number']) && trim($params['pai_number']) && $type==SOURCE_NETWORK_ORDER) {
            $pai_number = explode(' ', trim($params['pai_number']));
            $query->where_in('p.pai_number', array_filter($pai_number));
        }

        if (isset($params['group_ids']) && !empty($params['group_ids'])) {//采购组别->组内用户user ID
            $this->load->model('user/User_group_model', 'User_group_model');
            $userGroupList = $this->User_group_model->get_buyer_by_group($params['group_ids']);
            $userGroupListIds = array_unique(array_column($userGroupList,'value'));
            $userGroupListIds = empty($userGroupListIds)?[0]:$userGroupListIds;
            $query->where_in('p.applicant', $userGroupListIds);
        }

        if (isset($params['applicant']) && is_numeric($params['applicant'])) {//申请人 前端选择"空"的时候 applicant =0
            $query->where('p.applicant', $params['applicant']);
        }
        if (isset($params['pay_status']) && $params['pay_status']) {//付款状态
            $query->where('p.pay_status', $params['pay_status']);
        }
        if (isset($params['purchase_type_id']) && $params['purchase_type_id'] != "") {//业务线(网采单)
            if(is_array($params['purchase_type_id'])){
                $query->where_in('p.purchase_type_id', $params['purchase_type_id']);
            }else{
                $query->where('p.purchase_type_id', $params['purchase_type_id']);
            }
        }

        if (isset($params['pay_type']) && $params['pay_type'] != '') { //支付方式
            $query->where('p.pay_type', $params['pay_type']);
        }
        if (isset($params['pay_category']) && $params['pay_category']) {//请款类型
            $query->where('p.pay_category', $params['pay_category']);
        }
        if (isset($params['settlement_method']) && $params['settlement_method']) {//结算方式
            $query->where('p.settlement_method', $params['settlement_method']);
        }
        if (isset($params['is_cross_border']) && $params['is_cross_border'] != '') { //跨境宝供应商
            $query->where('sp.is_cross_border', $params['is_cross_border']);
        }
        // 来源主体（1合同 2网采 3对账单）
        if(isset($params['is_statement_pay']) && $params['is_statement_pay']!=""){
            if($params['is_statement_pay'] == '3'){
                $query->where('p.source_subject=3');
            }else{
                $query->where('p.source_subject<>3');
            }
        }
        //审批人筛选
        if (isset($params['approver_type']) && isset($params['approver_user']) && $params['approver_type']!='' && $params['approver_user']!='') { //审批人
            if($params['approver_type']==PAY_WAITING_FINANCE_REVIEW){//财务审核
                $query->where('p.approver', $params['approver_user']);
            }else if ($params['approver_type']==PAY_UFXFUIOU_SUPERVISOR){  //财务主管审核
                $query->where('p.financial_supervisor_id', $params['approver_user']);
            }else if ($params['approver_type']==PAY_UFXFUIOU_MANAGER){     //财务经理审核
                $query->where('p.financial_manager_id', $params['approver_user']);
            }else if ($params['approver_type']==PAY_UFXFUIOU_SUPPLY){      //财务总监审核
                $query->where('p.financial_officer_id', $params['approver_user']);
            }else if ($params['approver_type']==PAY_GENERAL_MANAGER){      //总经办审核
                $query->where('p.general_manager_id', $params['approver_user']);
            }
        }
        //是否退税
        if(isset($params['is_drawback']) && $params['is_drawback']!=''){
            $query->where('po.is_drawback', $params['is_drawback']);
        }
        if(isset($params['statement_number']) && !empty($params['statement_number'])){//对账单号
            $statement_numbers = query_string_to_array($params['statement_number']);
            $statement_numbers= array_unique($statement_numbers);
            $query->where_in('p.pur_number',$statement_numbers)->where('p.source_subject',SOURCE_SUBJECT_STATEMENT_ORDER);
            unset($compact_number_list);
        }

        if(isset($params['pay_user_id']) && $params['pay_user_id']!=''){
            $pay_user_id= trim($params['pay_user_id']);
            $query->where('p.payer_id', $pay_user_id);
        }

        //账号绑定付款人
        if(isset($params['account_pay']) && $params['account_pay']!=''){
            $pay_user_id= trim($params['account_pay']);
            $account_sub=$this->purchase_db->query("SELECT account,user_id FROM pur_alibaba_sub WHERE status=1 and pay_user_id='{$pay_user_id}'")->result_array();
            if(!empty($account_sub)){
                $account= is_array($account_sub)? array_column($account_sub, 'account'):'';
                $query->where_in('p.purchase_account',$account);
            }else {
                $query->where('p.purchase_account', 1);
            }
        }

        if(isset($params['pur_number']) && $params['pur_number']){ // 采购单号或合同号批量搜索
            $pur_numbers = explode (' ', trim($params['pur_number']));
            $requisition_number = $this->purchase_db->select('requisition_number')
                ->from('purchase_order_pay_detail')
                ->where_in('purchase_number',$pur_numbers)
                ->get()
                ->result_array();
            $query->group_start();
            $query->where_in('p.pur_number',  array_filter($pur_numbers));
            if(is_array($requisition_number) && !empty($requisition_number)){
                $requisition_number_arr = array_unique(array_column($requisition_number,'requisition_number'));
                $query->or_where_in('p.requisition_number',$requisition_number_arr);
            }
            $query->group_end();
        }
        if(isset($params['requisition_number']) && $params['requisition_number']){ //请款单号
            $query->where('p.requisition_number', $params['requisition_number']);
        }
        if (isset($params['create_time_start']) && $params['create_time_start'])// 创建时间-开始
            $query->where('p.create_time>=', $params['create_time_start']);
        if (isset($params['create_time_end']) && $params['create_time_end'])// 创建时间-结束
            $query->where('p.create_time<=', $params['create_time_end']);
        if(isset($params['id']) && $params['id']){
            $ids= explode(',', $params['id']);
            $query->where_in('p.id', $ids);
        }

        if(isset($params['purchase_account']) && trim($params['purchase_account'])){//账号查询
            $query->like('p.purchase_account', trim($params['purchase_account']),'after');
        }
        if(isset($params['purchase_account_type']) && trim($params['purchase_account_type'])){
            if(trim($params['purchase_account_type']) == 1){// 账号类型为 1.淘宝
                $query->where_in('p.purchase_account',$taobao_account);
            }else{
                $query->where_not_in('p.purchase_account',$taobao_account);
            }
        }
        if(isset($params['is_ali_order']) && $params['is_ali_order'] != ''){
            $query->where('po.is_ali_order',intval($params['is_ali_order']));
        }
        if (isset($params['purchase_order_status']) && $params['purchase_order_status']){
            if(!is_array($params['purchase_order_status'])) {
                $params['purchase_order_status'] = explode(",",$params['purchase_order_status']);
            }
            $query->where_in("po.purchase_order_status",$params['purchase_order_status']);
        }

        if ($type == SOURCE_COMPACT_ORDER) {//合同单
            $query->where_in('p.pay_status', [PAY_WAITING_FINANCE_REVIEW,PAY_SOA_REJECT, PAY_FINANCE_REJECT, PAY_WAITING_FINANCE_PAID, PART_PAID, PAY_PAID,
                PAY_UFXFUIOU_REVIEW,PAY_UFXFUIOU_SUPERVISOR,PAY_UFXFUIOU_MANAGER,PAY_UFXFUIOU_SUPPLY,PAY_GENERAL_MANAGER,PAY_REJECT_SUPERVISOR,
                PAY_REJECT_MANAGER,PAY_REJECT_SUPPLY,PAY_GENERAL_MANAGER_REJECT,PAY_UFXFUIOU_BAOFOPAY,PAY_LAKALA]);
        }

        //应付款时间
        if(isset($params['need_pay_time_start']) && $params['need_pay_time_start'] && isset($params['need_pay_time_end']) && $params['need_pay_time_end']){//应付款时间
            $start_time = date('Y-m-d H:i:s',strtotime($params['need_pay_time_start']));
            $end_time = date('Y-m-d H:i:s',strtotime($params['need_pay_time_end']));
            $query->where("p.need_pay_time between '{$start_time}' and '{$end_time}' ");// 应付款时间
        }

        //实际付款时间
        if(isset($params['pay_time_start']) && $params['pay_time_start'] && isset($params['pay_time_end']) && $params['pay_time_end']){//应付款时间
            $start_time = date('Y-m-d H:i:s',strtotime($params['pay_time_start']));
            $end_time = date('Y-m-d H:i:s',strtotime($params['pay_time_end']));
            $query->where("p.payer_time between '{$start_time}' and '{$end_time}' ");// 应付款时间
        }

        if(isset($params['pay_notice']) and $params['pay_notice']){//付款提醒状态
            if ($params['pay_notice'] == TAP_DATE_WITHOUT_BALANCE){//查询额度不足的供应商
                $supplier_settlement_online = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;
                $sup_sql = "SELECT supplier_code FROM pur_supplier WHERE supplier_settlement='{$supplier_settlement_online}' and pur_supplier.quota <> 0 and pur_supplier.surplus_quota <= 0";
                $supplier_res = $this->purchase_db->query($sup_sql)->result_array();
                $supplier_res = !empty($supplier_res)?array_column($supplier_res,'supplier_code'):[ORDER_CANCEL_ORSTATUS];
                $supplier_res= array_unique($supplier_res);
                $query->where_in("p.supplier_code",$supplier_res);
            }

            if ($params['pay_notice'] != TAP_DATE_WITHOUT_BALANCE){//查询额度足够的供应商
                $supplier_settlement_online = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;
                $query->where("ppy.accout_period_time != '0000-00-00 00:00:00'");

                if ($params['pay_notice'] == TAP_DATE_OVER_TIME){//已超期
                    $today = date('Y-m-d H:i:s');
                    $query->where("ppy.accout_period_time<'{$today}'");
                }

                if ($params['pay_notice'] == TAP_DATE_COMING_SOON){//即将到期
                    $today = date('Y-m-d H:i:s');
                    $five_days_later = date('Y-m-d H:i:s',strtotime('+ 5 days'));
                    $query->where("ppy.accout_period_time >= '{$today}'");
                    $query->where("ppy.accout_period_time < '{$five_days_later}'");
                }
                if ($params['pay_notice'] == TAP_DATE_CAN_WAIT){//可继续等待
                    $five_days_later = date('Y-m-d H:i:s',strtotime('+ 5 days'));
                    $query->where("ppy.accout_period_time >= '{$five_days_later}'");
                }
            }
        }

        if(isset($params['payment_platform'])&& $params['payment_platform']=='6'){
            $query->join('pur_supplier_payment_info spa','p.supplier_code = spa.supplier_code AND spa.is_del = 0','right');
            $query->where('spa.payment_platform',$params['payment_platform']);
            $query->where("p.pay_type",PURCHASE_PAY_TYPE_PRIVATE);

            unset($supplier_code_list);
        }
        //支付方式=线下境内，那么默认支付平台=网银
        if(isset($params['payment_platform'])&& $params['payment_platform']=='5'){
            $query->join('pur_supplier_payment_info spa','p.supplier_code = spa.supplier_code AND spa.is_del = 0','right');
            $query->where('spa.payment_platform',$params['payment_platform']);
            $query->where("p.pay_type",PURCHASE_PAY_TYPE_PUBLIC);
            unset($supplier_code_list);
        }

        //支付方式≠线下境内/线下境外，那么默认支付平台=其他
        if(isset($params['payment_platform'])&& $params['payment_platform']=='1'){
            $query->join('pur_supplier_payment_info spa','p.supplier_code = spa.supplier_code AND spa.is_del = 0','right');
            $query->where_not_in('spa.payment_platform',[5,6]);
            $query->where_not_in('p.pay_type',[PURCHASE_PAY_TYPE_PUBLIC,PURCHASE_PAY_TYPE_PRIVATE]);
            unset($supplier_code_list);
        }
        //支付账号筛选
        if(!empty($params['account_number'])){
            $acc_sql = "SELECT account_short FROM pur_bank_card WHERE account_number='{$params['account_number']}'";
            $account_res = $this->purchase_db->query($acc_sql)->row_array();
            $query->where("p.pay_account = '{$account_res['account_short']}'");
        }
//        unset($params);
        if($action && $action == 'sum'){
            //如果筛选条件是单独请款运费的 那么$type修改为合同单   因为合同单单独请款运费时采购来源变成网采
            if(isset($params['pay_category']) && $params['pay_category']==4 && $type==SOURCE_NETWORK_ORDER){
                $type  = 1;
                $query->where('p.source', 2);
            }else{
                $query->where('p.source', $type);
            }
        }else{
            $query->where('p.source', $type);
        }

        return $query;
    }

    /**
     * 处理数据结果
     */
    public function handle_result_data($data)
    {
        $res = [
            'sum_price' =>0,
            "data" => []
        ];
        if(!$data || count($data) <1){
            return $res;
        }
        //获取供应商名
        $supplier_code= is_array($data)?array_column($data, 'supplier_code'):[];
        $warehouse_code_list = $this->get_supplier_name($supplier_code);
        //获取结算方式
        $settlement_method= is_array($data)?array_column($data, 'settlement_method'):[];
        $this->load->model("supplier/Supplier_settlement_model");
        $supplier_code_list = $this->Supplier_settlement_model->get_code2name_list($settlement_method);
        //获取1688子账号绑定付款人id
        $applicant_list=is_array($data)?array_column($data, 'applicant'):[];
        $pay_user_name_list= $this->get_account_sub($applicant_list);
        $purchase_account_list =is_array($data)?array_column($data, 'purchase_account'):[];
        $pay_user_name_list_new = $this->get_account_sub_new($purchase_account_list);

        //获取对账号集合
        $pur_number_list= is_array($data)?array_column($data, 'pur_number'):[];
        $statement_number_arr= $this->get_statement_number($pur_number_list);

        // 所有用户的组别
        $this->load->model('user/User_group_model','User_group_model');
        $userGroupList = $this->User_group_model->getBuyerGroupMessage();
        $userGroupList = arrayKeyToColumnMulti($userGroupList,'user_id');

        $sum_price = 0;
        $all_user_list = get_buyer_name();

        foreach ($data as $key => $val) {
            //支付平台
            $payment_platform = $this->Supplier_payment_info_model->get_payment_platform($val['supplier_code'],$val['is_drawback'],$val['purchase_type_id'],$val['pay_type']);

            $data[$key]['payment_platform'] = get_supplier_payment_platform($payment_platform['payment_platform']??'');
            //判断线上账期付款提醒状态
            $data[$key]['pay_notice'] = formatAccoutPeriodTime($data[$key]['settlement_method'], $data[$key]['pay_status'], $data[$key]['need_pay_time'], $data[$key]['surplus_quota']);
            $data[$key]['statement_number']= isset($statement_number_arr[$val['pur_number']])?$statement_number_arr[$val['pur_number']]:'';
            $data[$key]['status'] = $val['pay_status'];
            $data[$key]['pay_status'] = isset($val['pay_status'])?getPayStatus($val['pay_status']):'';
            $data[$key]['supplier_name'] = isset($warehouse_code_list[$val['supplier_code']])?$warehouse_code_list[$val['supplier_code']]:"";
            $data[$key]['pay_type'] = isset($val['pay_type'])?getPayType($val['pay_type']):'';
            $data[$key]['settlement_method'] = isset($supplier_code_list[$val['settlement_method']])?$supplier_code_list[$val['settlement_method']]:'';
            $data[$key]['applicant']= isset($all_user_list[$val['applicant']]) ? $all_user_list[$val['applicant']]: ($val['applicant'] == 1?'admin':'');
            $data[$key]['auditor']= isset($all_user_list[$val['auditor']]) ? $all_user_list[$val['auditor']] :  '';
            $data[$key]['pay_category'] = isset($val['pay_category']) ? getPayCategory($val['pay_category']) : "";
            $data[$key]['is_drawback'] = isset($val['is_drawback']) ? getIsDrawbackShow($val['is_drawback']) : "";
            $data[$key]['is_ali_order'] = isset($val['is_ali_order'])?getIsAliOrder($val['is_ali_order']):'';
            $data[$key]['accout_period_time'] = isset($val['accout_period_time'])?$val['accout_period_time']:'';
            $sum_price+=$val['pay_price'];
//            if(in_array($val['pay_category'],[PURCHASE_PAY_CATEGORY_2,PURCHASE_PAY_CATEGORY_3])){// 请款类型为-采购货款、采购货款+运费/优惠，才需展示该字段
            $data[$key]['purchase_order_status'] = isset($val['purchase_order_status'])?getPurchaseStatus($val['purchase_order_status']):'';
//            }else{
//                $data[$key]['purchase_order_status'] = '';
//            }

            if($val['source']==SOURCE_NETWORK_ORDER){

                $pay_user_name= isset($pay_user_name_list[$val['applicant']."-".$val['purchase_account']])?$pay_user_name_list[$val['applicant']."-".$val['purchase_account']]:'';
                $data[$key]['payer_name'] = empty($val['payer_name']) ? $pay_user_name : $val['payer_name'];

                if(empty($pay_user_name) and  empty($val['payer_name'])){
                    $pay_user_name = isset($pay_user_name_list_new[$val['purchase_account']])?$pay_user_name_list_new[$val['purchase_account']]:'';
                    $data[$key]['payer_name'] =  $pay_user_name;
                }

                //根据请款人与请款的采购子账号获取 付款人信息
                $account_sub = $this->purchase_db->query("SELECT pay_user_name FROM pur_alibaba_sub WHERE status=1 and account='{$val['purchase_account']}'")->row_array();
                if(!empty($account_sub)){
                    $data[$key]['account_pay']  = $account_sub['pay_user_name'];
                }else{
                    //不存在是用 采购子账号获取对应的付款人
                    $data[$key]['account_pay'] ='';
                }
            }else{
                $data[$key]['account_pay'] ='';
            }
            // 采购账号子账号的只显示主账号
            $purchase_account = $data[$key]['purchase_account'];
            if($purchase_account != 'yibaisuperbuyers' and stripos($purchase_account,':') !== false ){
                $accunt_arr = explode(':',$purchase_account);
                $data[$key]['purchase_account'] = isset($accunt_arr[0])?$accunt_arr[0]:$purchase_account;
            }
            if($val['source']==SOURCE_COMPACT_ORDER and $val['source_subject'] == SOURCE_SUBJECT_COMPACT_ORDER){
                $amount_list = $this->Compact_model->calculate_compact_related_amount($val['pur_number']);
                if($amount_list['code'] === false){
                    $return['msg'] = $amount_list['msg'];
                    return $return;
                }
                $data[$key]['paid_total_real_money'] = isset($amount_list['paid_total_real_money']) ? $amount_list['paid_total_real_money'] : 0;
                $data[$key]['available_product_money'] = isset($amount_list['available_product_money']) ? $amount_list['available_product_money'] : 0;
                $data[$key]['account_pay'] ='';//合同单账号付款人默认给空值
            }

            $data[$key]['check_status'] = $this->get_check_status($val['requisition_number']);
            $data[$key]['is_statement_pay'] = getIsStatementPay($val['source_subject']);
            $data[$key]['group_name_str'] = isset($userGroupList[$val['applicant']])?implode(',',array_column($userGroupList[$val['applicant']],'group_name')):'';

            unset($data[$key]['surplus_quota']);
        }
        $res['data'] = $data;
        $res['sum_price'] = $sum_price;
        return $res;
    }

    /**
     * 列表导出
     */
    public function new_export_pay_list($params, $type, $offsets, $limit,$id='',$page=1)
    {
        $role_name      = get_user_role();//当前登录角色
        $taobao_account = $this->get_account_list(['account_type' => 1]);// 获取 淘宝账号
        $taobao_account = array_column($taobao_account,'purchase_account');

        $query = clone $this->purchase_db;
        // distinct 去除重复数据
        $query->select('p.id,'
            . 'p.pay_status,'
            . 'p.pur_number,'
            . 'p.requisition_number,'
            . 'p.pai_number,'
            . 'p.supplier_code,'
            . 'p.pay_type,'
            . 'p.pay_category,'
            . 'p.settlement_method,'
            . 'p.payment_platform,'
            . 'p.pay_price,'
            . 'p.product_money,'
            . 'p.freight,'
            . 'p.discount,'
            . 'p.process_cost,'
            . 'p.commission,'
            . 'p.applicant,'
            . 'p.application_time,'
            . 'p.auditor,'
            . 'p.review_time,'
            . 'p.create_notice,'  //请款备注
            . 'p.review_notice,' //审核备注
            . 'p.processing_notice,' //审批备注
            . 'p.purchase_account,'
            . 'p.payment_notice,'   //付款备注
            . 'p.payer_name,sp.is_cross_border,'
            . 'p.payer_time,'
            . 'p.pur_tran_num,'
            . 'p.trans_orderid,'
            . 'p.voucher_address,'
            . 'ppy.accout_period_time as need_pay_time,'
            . 'sp.surplus_quota,'
            . 'sp.tap_date_str,p.payer_id,p.source,p.source_subject,'
            . 'po.is_drawback,'
            . 'po.is_ali_order,'
            . 'po.purchase_order_status,'
            . 'po.purchase_type_id,'
            . 'bank.account_number');
        $query->from('purchase_order_pay_detail as pp');

        $query = $this->pay_list_params($params, $query, $type, $taobao_account);
        $count_qb = clone $query;
        $count_price = clone $query;
        $data = $query->limit($limit, $offsets)->group_by('p.requisition_number')->order_by('p.id','desc')->get()->result_array();
        //获取供应商名
        $supplier_code= is_array($data)?array_column($data, 'supplier_code'):[];
        $warehouse_code_list = $this->get_supplier_name($supplier_code);
        //获取结算方式
        $settlement_method= is_array($data)?array_column($data, 'settlement_method'):[];
        $this->load->model("supplier/Supplier_settlement_model");
        $supplier_code_list = $this->Supplier_settlement_model->get_code2name_list($settlement_method);
        //获取1688子账号绑定付款人id
        $applicant_list=is_array($data)?array_column($data, 'applicant'):[];
        $pay_user_name_list= $this->get_account_sub($applicant_list);
        $purchase_account_list =is_array($data)?array_column($data, 'purchase_account'):[];

        $pay_user_name_list_new = $this->get_account_sub_new($purchase_account_list);

        //获取对对账号集合
        $pur_number_list= is_array($data)?array_column($data, 'pur_number'):[];
        $statement_number_arr= $this->get_statement_number($pur_number_list);
        //总计金额
        $all_pay_id = $count_price->distinct()->select("p.id")->get()->result_array();
        $all_pay_price = 0;
        if(!empty($all_pay_id)){
            $all_pay_price = $this->get_order_pay_price($all_pay_id);
        }

        $sum_price = 0;
        $all_user_list = get_buyer_name();

        foreach ($data as $key => $val) {
            //支付平台
            $payment_platform = $this->Supplier_payment_info_model->get_payment_platform($val['supplier_code'],$val['is_drawback'],$val['purchase_type_id'],$val['pay_type']);

            $data[$key]['payment_platform'] = get_supplier_payment_platform($payment_platform['payment_platform']??'');
            //判断线上账期付款提醒状态
            $data[$key]['pay_notice'] = formatAccoutPeriodTime($data[$key]['settlement_method'], $data[$key]['pay_status'], $data[$key]['need_pay_time'], $data[$key]['surplus_quota']);
            $data[$key]['statement_number']= isset($statement_number_arr[$val['pur_number']])?$statement_number_arr[$val['pur_number']]:'';
            $data[$key]['status'] = $val['pay_status'];
            $data[$key]['pay_status'] = isset($val['pay_status'])?getPayStatus($val['pay_status']):'';
            $data[$key]['supplier_name'] = isset($warehouse_code_list[$val['supplier_code']])?$warehouse_code_list[$val['supplier_code']]:"";
            $data[$key]['pay_type'] = isset($val['pay_type'])?getPayType($val['pay_type']):'';
            $data[$key]['settlement_method'] = isset($supplier_code_list[$val['settlement_method']])?$supplier_code_list[$val['settlement_method']]:'';
            $data[$key]['applicant']= isset($all_user_list[$val['applicant']]) ? $all_user_list[$val['applicant']]: '';
            $data[$key]['auditor']= isset($all_user_list[$val['auditor']]) ? $all_user_list[$val['auditor']] :  '';
            $data[$key]['pay_category'] = isset($val['pay_category']) ? getPayCategory($val['pay_category']) : "";
            $data[$key]['is_drawback'] = isset($val['is_drawback']) ? getIsDrawbackShow($val['is_drawback']) : "";
            $data[$key]['is_ali_order'] = isset($val['is_ali_order'])?getIsAliOrder($val['is_ali_order']):'';
            $sum_price+=$val['pay_price'];
//            if(in_array($val['pay_category'],[2,3])){// 请款类型为-采购货款、采购货款+运费/优惠，才需展示该字段
            $data[$key]['purchase_order_status'] = isset($val['purchase_order_status'])?getPurchaseStatus($val['purchase_order_status']):'';
//            }else{
//                $data[$key]['purchase_order_status'] = '';
//            }

            if($val['source']==SOURCE_NETWORK_ORDER){

                $pay_user_name= isset($pay_user_name_list[$val['applicant']."-".$val['purchase_account']])?$pay_user_name_list[$val['applicant']."-".$val['purchase_account']]:'';
                $data[$key]['payer_name'] = empty($val['payer_name']) ? $pay_user_name : $val['payer_name'];

                if(empty($pay_user_name) and  empty($val['payer_name'])){
                    $pay_user_name = isset($pay_user_name_list_new[$val['purchase_account']])?$pay_user_name_list_new[$val['purchase_account']]:'';
                    $data[$key]['payer_name'] =  $pay_user_name;
                }

                //根据请款人与请款的采购子账号获取 付款人信息
                $account_sub = $this->purchase_db->query("SELECT pay_user_name FROM pur_alibaba_sub WHERE status=1 and account='{$val['purchase_account']}'")->row_array();
                if(!empty($account_sub)){
                    $data[$key]['account_pay']  = $account_sub['pay_user_name'];
                }else{
                    //不存在是用 采购子账号获取对应的付款人
                    $data[$key]['account_pay'] ='';
                }
            }else{
                $data[$key]['account_pay'] ='';
            }
            // 采购账号子账号的只显示主账号
            $purchase_account = $data[$key]['purchase_account'];
            if($purchase_account != 'yibaisuperbuyers' and stripos($purchase_account,':') !== false ){
                $accunt_arr = explode(':',$purchase_account);
                $data[$key]['purchase_account'] = isset($accunt_arr[0])?$accunt_arr[0]:$purchase_account;
            }
            if($val['source']==SOURCE_COMPACT_ORDER and $val['source_subject'] == SOURCE_SUBJECT_COMPACT_ORDER){
                $amount_list = $this->Compact_model->calculate_compact_related_amount($val['pur_number']);
                if($amount_list['code'] === false){
                    $return['msg'] = $amount_list['msg'];
                    return $return;
                }
                $data[$key]['paid_total_real_money'] = isset($amount_list['paid_total_real_money']) ? $amount_list['paid_total_real_money'] : 0;
                $data[$key]['available_product_money'] = isset($amount_list['available_product_money']) ? $amount_list['available_product_money'] : 0;
                $data[$key]['account_pay'] ='';//合同单账号付款人默认给空值
            }

            $data[$key]['check_status'] = $this->get_check_status($val['requisition_number']);
            $data[$key]['is_statement_pay'] = getIsStatementPay($val['source_subject']);

            unset($data[$key]['surplus_quota']);
        }

        $return_data = [
            'values'=>$data,
        ];
        return $return_data;
    }

    /**
     * 应付统计
     */
    public function new_get_pay_list_sum($params, $type)
    {
        $role_name      = get_user_role();//当前登录角色
        $taobao_account = $this->get_account_list(['account_type' => 1]);// 获取 淘宝账号
        $taobao_account = array_column($taobao_account,'purchase_account');
        $query = clone $this->purchase_db;
        $query->select('p.id');
        $query->from('purchase_order_pay_detail as pp');
        $query = $this->pay_list_params($params, $query, $type, $taobao_account, 'sum');
        unset($params);
        //统计总数
        $count_row = $query->select("count(DISTINCT p.id) as num")->get()->row_array();
        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        $return_data = [
            'paging_data'=>[
                'total'=>$total_count,
            ]
        ];
        return $return_data;
    }

    /**
     * 获取付款人
     * @param array $applicant_list
     * @return array
     */        
    public function get_account_sub(array $applicant_list){
        if(empty($applicant_list)){
            return [];
        }
       $res= $this->purchase_db
                ->select('user_id,pay_user_name,account')
                ->where_in('user_id',$applicant_list)
                ->get('alibaba_sub')->result_array();
        if(empty($res)) {
            return [];
        }
        $data=[];
        foreach ($res as $key => $value) {
         $data[$value['user_id']."-".$value['account']]=$value['pay_user_name'];
        }
       return $data;
    }
    /**
     * 获取对账合同号集合
     * @author harvin
     * @param array $pur_number_list
     * @return array
     */
    public function get_statement_number(array $pur_number_list){
        $data=[];
        if(empty($pur_number_list)){
            return $data;
        }
       $statement= $this->purchase_db
                ->select('statement_number,compact_number')
                ->where_in('compact_number',$pur_number_list)
                ->get('pur_purchase_statement_items')
                ->result_array();
        if(empty($statement)){
            return $data;
        }
        foreach ($statement as $key => $value) {
            $data[$value['compact_number']]=$value['statement_number'];
        }
        return $data;
    }


    /**
     * 获取供应商集合
     * @author liwuxue
     * @date 2019/2/12 11:21
     * @param
     * @return mixed|array
     */
    public function get_supplier_code_list()
    {
        $res = $this->purchase_db->select("supplier_code")->get("purchase_order_pay")->result_array();
        $codes = is_array($res) ? array_filter(array_unique(array_column($res, "supplier_code"))) : [];
        $data = !empty($codes) ? $this->purchase_db->select("supplier_code,supplier_name")
            ->where_in('supplier_code', $codes)->get('supplier')->result_array() : [];
        $resp = is_array($data) ? array_column($data, 'supplier_name', 'supplier_code') : [];
        return $resp;
    }
    /**
     * 获取供应商支付平台集合
     * @author harvin
     * @return array
     */
/*    public function get_payment_platform(array $supplier_code){
        if(empty($supplier_code)){
            return [];
        }
        $supplier_code= is_array($supplier_code)?array_unique($supplier_code):[PURCHASE_NUMBER_ZFSTATUS];
        $supplier_platform= $this->purchase_db
                ->select('supplier_code,payment_platform,payment_method')
                ->where_in('supplier_code',$supplier_code)
                ->get('supplier_payment_account')
                ->result_array();
     if(empty($supplier_platform)){
         return [];
     }
     $data=[];  
     foreach ($supplier_platform as  $key => $value) {
         $data[$value['supplier_code'].'-'.$value['payment_method']]=$value['payment_platform'];
     };
      return $data;

    }*/

    /**
     * 网菜单 付款详情
     * @author harvin 2019-2-16
     * @param string $requisition_number 请款单号
     * @return array
     * @throws Exception
     */
   public function net_order_info($requisition_number){
       $this->load->model('purchase/Purchase_order_determine_model');
       $this->load->model('purchase/Purchase_order_model');

       $pay_order = $this->purchase_db
           ->select('requisition_number,pur_number')
           ->where('requisition_number', $requisition_number)
           ->get('purchase_order_pay')
           ->row_array();
       if (empty($pay_order)) {
           throw  new Exception('请款单号不存在');
       }

       //获取采购单基本信息
       $order = $this->purchase_db
           ->select('purchase_number,supplier_name,buyer_name,pay_type,is_expedited,shipping_method_id'
               . ',account_type,plan_product_arrive_time,purchase_order_status')
           ->where('purchase_number', $pay_order['pur_number'])
           ->get('purchase_order')->row_array();
       if (empty($order)) throw  new Exception('采购单不存在');

       $order_items = $this->purchase_db
           ->select("*")
           ->where('purchase_number', $order['purchase_number'])
           ->get('purchase_order_items')
           ->result_array();
       if (empty($order_items)) throw  new Exception('采购单明细表不存在');

       $confirm_amount_total = 0;
       //获取备货单号集合
       $demand_number_list = $this->get_demand_number_list($order['purchase_number']);
       //获取历史采购单单价最近一次
       $sku_list = is_array($order_items) ? array_column($order_items, 'sku') : [];
       $history_unit_price_list = $this->get_sku_history_unit_price_list($sku_list);
       //取消数量集合
       $order_cancel_list = $this->Purchase_order_determine_model->get_order_cancel_list($order['purchase_number'], $sku_list); //po+sku 取消数量
       //获取入库数量集合
       $warehouse_list = $this->Purchase_order_determine_model->get_warehouse_list($order['purchase_number'], $sku_list);
       //获取入库不良品数量集合
       $warehouse_bad_qty = $this->Purchase_order_determine_model->get_warehouse_bad_qty($order['purchase_number'], $sku_list);
       //获取入库不良品数量集合
       $warehouse_instock_more_qty = $this->Purchase_order_determine_model->get_warehouse_instock_more_qty($order['purchase_number'], $sku_list);
       //报损数量集合
       $purchase_order_reportloss = $this->Purchase_order_determine_model->get_purchase_order_reportloss_list($order['purchase_number'], $sku_list);
       //报损金额集合
       $loss_totalprice_reportloss = $this->Purchase_order_determine_model->get_loss_totalprice($order['purchase_number'], $sku_list);

       $data_order_info = $storage_record = [];
       $real_price = 0;//订单总金额

       foreach ($order_items as $items) {
           //获取入库人及入库时间
           $sku_str = $items['purchase_number'] . '-' . $items['sku'];
           $warehouse_user_date = $this->Purchase_order_determine_model->get_warehouse_user_date($items['purchase_number'], $items['sku']);
           $confirm_amount_total += $items['confirm_amount'];

           $demand_number_info = isset($demand_number_list[$sku_str]) ? $demand_number_list[$sku_str]:[];
           if($demand_number_info){
               $order_info['demand_number'] = $demand_number_info['demand_number'];
               $order_info['product_line_id'] = $demand_number_info['product_line_name'];
               $order_info['product_line_id_first'] = $demand_number_info['product_line_id'];
               $order_info['product_line_name_first'] = $demand_number_info['product_line_name'];
               $order_info['suggest_order_status_int'] = $demand_number_info['suggest_order_status'];
               $order_info['suggest_order_status'] = getPurchaseStatus($demand_number_info['suggest_order_status']);
           }else{
               $order_info['demand_number'] = '';
               $order_info['product_line_id'] = '';
               $order_info['product_line_id_first'] = '';
               $order_info['product_line_name_first'] = '';
               $order_info['suggest_order_status_int'] = '';
               $order_info['suggest_order_status'] = '';
           }

           $combine_key_po =  $items['purchase_number'] . "-" . $items['sku'];
           $order_info['purchase_number'] = $items['purchase_number'];
           $order_info['product_img_url'] = $items['product_img_url'];
           $order_info['sku'] = $items['sku'];
           $order_info['product_name'] = $items['product_name'];
           $order_info['product_url'] = '';//产品链接
           $order_info['plan_product_arrive_time'] = $items['plan_arrive_time'];
           $order_info['purchase_unit_price'] = $items['purchase_unit_price'];
           $order_info['confirm_amount'] = $items['confirm_amount'];
           $order_info['history_unit_price'] = isset($history_unit_price_list[$items['sku']]) ? $history_unit_price_list[$items['sku']] : '';
           $order_info['confirm_amount_money'] = format_price($items['confirm_amount'] * $items['purchase_unit_price']);
           $order_info['purchase_order_status_int'] = $order['purchase_order_status'];
           $order_info['purchase_order_status'] = getPurchaseStatus($order['purchase_order_status']);
           $order_info['pur_ticketed_point'] = $items['pur_ticketed_point'];
           $order_info['cacel_qt'] = isset($order_cancel_list[$combine_key_po]) ? $order_cancel_list[$combine_key_po] : 0;
           $order_info['instock_qty'] = isset($warehouse_list[$combine_key_po]) ? $warehouse_list[$combine_key_po] : 0;
           $order_info['loss_amount'] = isset($purchase_order_reportloss[$combine_key_po]) ? $purchase_order_reportloss[$combine_key_po] : 0;
           $order_info['instock_money'] = format_price($order_info['instock_qty'] * $items['purchase_unit_price']);
           $order_info['bad_qty'] = isset($warehouse_bad_qty[$combine_key_po]) ? $warehouse_bad_qty[$combine_key_po] : 0;
           $order_info['instock_more_qty'] = isset($warehouse_instock_more_qty[$combine_key_po]) ? $warehouse_instock_more_qty[$combine_key_po] : 0;
           $order_info['instock_user_name'] = isset($warehouse_user_date['instock_user_name']) ? $warehouse_user_date['instock_user_name'] : '';
           $order_info['instock_date'] = isset($warehouse_user_date['instock_date']) ? $warehouse_user_date['instock_date'] : '';
           $order_info['loss_totalprice'] = isset($loss_totalprice_reportloss[$combine_key_po]) ? $loss_totalprice_reportloss[$combine_key_po] : 0;

           $data_order_info[] = $order_info;
           $real_price += $order_info['confirm_amount_money'] + $items['freight'] - $items['discount'];
           $warehouse_log = $this->Purchase_order_determine_model->get_warehouse_log($items['purchase_number'], $items['sku']);
           $storage_record[] = $warehouse_log;
           unset($order_info);
           unset($warehouse_log);
       }

       $order_pay = $this->purchase_db
           ->select('freight,discount,real_price,pai_number,purchase_acccount')
           ->where('purchase_number', $order['purchase_number'])
           ->get('purchase_order_pay_type')->row_array();
       if (empty($order_pay)) throw  new Exception('采购单信息确认不存在');

       //account_type 供应商结算方式
       $this->load->model("supplier/Supplier_settlement_model");
       $settlement_code_list = $this->Supplier_settlement_model->get_code2name_list([$order['account_type']]);

       $data_list = [
           'purchase_number' => $order['purchase_number'],
           'supplier_name' => $order['supplier_name'],
           'buyer_name' => $order['buyer_name'],
           'sku_number' => count($order_items),
           'confirm_amount_total' => $confirm_amount_total,
           'real_price' => sprintf("%.3f", $real_price),
           'freight' => $order_pay['freight'],
           'discount' => $order_pay['discount'],
           'account_type' => isset($settlement_code_list[$order['account_type']]) ? $settlement_code_list[$order['account_type']] : '',
           'pay_type' => getPayType($order['pay_type']),
           'shipping_method_id' => getShippingMethod($order['shipping_method_id']),
           'purchase_acccount' => $order_pay['purchase_acccount'],
           'pai_number' => $order_pay['pai_number'],
           'is_expedited' => $order['is_expedited'] == 1 ? '否' : "是",
           'order_info' => $data_order_info,
           'storage_record' => array_filter($storage_record),
       ];
      
      return $data_list;
   }

    /**
     * 获取获取采购单对应的备货单和产品线
     */
    public function get_demand_number_list($purchase_number){
        $data=[];
        $suggest_map = $this->purchase_db
            ->from('purchase_suggest_map as psm')
            ->select('psm.purchase_number,psm.sku,psm.demand_number,ps.product_line_id,ps.product_line_name,ps.suggest_order_status')
            ->join('purchase_suggest as ps', 'psm.demand_number = ps.demand_number', 'left')
            ->where('purchase_number', $purchase_number)
            ->get()
            ->result_array();
        if(empty($suggest_map)){
            return [];
        }
        foreach ($suggest_map as $row) {
            $data[$row['purchase_number'].'-'.$row['sku']]=[
                "demand_number" => $row['demand_number'],
                "product_line_id" => $row['product_line_id'],
                "product_line_name" => $row['product_line_name'],
                "suggest_order_status" => $row['suggest_order_status'],
            ];
        }
        return $data;

    }

     /**
      * 获取历史采购单价最近一次
      * @author harvin
      * @date 2019-07-06
      * @param array $sku_list
      * @return array
      */       
    public function get_sku_history_unit_price_list(array $sku_list){
        if(empty($sku_list)) return [];
        $query = $this->purchase_db;
        $query->select("A.purchase_unit_price,A.sku");
        $query->from('purchase_order_items as A');
        $query->join('purchase_order as B', 'A.purchase_number=B.purchase_number', 'left');
        $query->where_in('A.sku',$sku_list);
        //$query->where_in('B.purchase_order_status', [PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE]);
        $order=$query ->get()->result_array();
        if(empty($order)) return [];
        $data_list= array_column($order, 'purchase_unit_price','sku');
        return   $data_list;   
    }


    /**
     * 通过供应商编码获取供应商名称
     * @author harvin 2019-1-17
     * @param array $supplier_code  
     * */
    public function get_supplier_name($supplier_code) {
        if(empty($supplier_code)){
            return [];
        }
        //去除相同元素
        $supplier_code= is_array($supplier_code)?array_unique($supplier_code):[PURCHASE_NUMBER_ZFSTATUS];
        $suoolier = $this->purchase_db
                        ->select('supplier_name,supplier_code')
                        ->where_in('supplier_code', $supplier_code)->get('supplier')->result_array();
        $data = is_array($suoolier) ? array_column($suoolier, "supplier_name", "supplier_code") : [];
        return $data;
    }

    /**
     * 财务审核页面详情（合同）
     * @param string $id 参数
     * @author harvin 2019-1-17
     * @throws Exception
     * @return array|mixed
     * * */
    public function contract_order_info($id) {
            //获取请款单信息
        $order_pay = $this->purchase_db
                ->select('id,supplier_code,pay_type,pur_number,requisition_number,pay_ratio,'
                        . 'js_ratio,product_money,freight,discount,process_cost,pay_price,applicant,freight_desc,'
                        . 'application_time,compact_url,supplier_code,review_notice,waiting_notice,processing_notice,financial_manager_notice,'
                        . 'financial_officer_notice,general_manager_notice,create_notice,financial_supervisor_notice,requisition_method')
                ->where('id', $id)
                ->where('source', SOURCE_COMPACT_ORDER)
                ->get('purchase_order_pay')
                ->row_array();
        if (empty($order_pay)) {
            throw new Exception("错误的id，数据不存在!");
        }

        //查找采购单号
        $order_pay_info = $this->purchase_db
                ->select('purchase_number')
                ->where('requisition_number', $order_pay['requisition_number'])
                ->get('purchase_order_pay_detail')
                ->result_array();
        if (empty($order_pay_info)) {
            throw new Exception("请款单详情不存在");
        }
        $ppurchase_numbers = array_column($order_pay_info, 'purchase_number');
        //判断该单是否是退税 (默认 0.否,1.退税)
        $purchase_order = $this->purchase_db
                ->select('is_drawback,purchase_type_id')
                ->where_in('purchase_number', $ppurchase_numbers)
                ->where('source', SOURCE_COMPACT_ORDER)
                ->get('pur_purchase_order')
                ->row_array();
        if (empty($purchase_order)) {
            throw new Exception('采购单不存在');
        }

        //获取合同信息
        $compact = $this->purchase_db
                ->select('create_time,create_user_name')
                ->where('compact_number', $order_pay['pur_number'])
                ->get('purchase_compact')
                ->row_array();
        if (empty($compact)) {
            throw new Exception('合同单不存在');
        }
        //系统信息
        $supplier_list = $this->get_supplier_name([$order_pay['supplier_code']]);
        $supplier_name = isset($supplier_list[$order_pay['supplier_code']]) ? $supplier_list[$order_pay['supplier_code']] : '';


        $data_system = [
            'supplier_name' => $supplier_name,
            'pay_type' => getPayType($order_pay['pay_type']),
            'is_drawback' => $purchase_order['is_drawback'] == PURCHASE_IS_DRAWBACK_Y ? '是' : '否',
            'create_time' => $compact['create_time'],
            'create_user_name' => $compact['create_user_name'],
        ];
        //供应商开户信息
        $this->load->model('supplier/Supplier_payment_info_model', 'paymentInfoModel');
        $payment_info = $this->paymentInfoModel->check_payment_info($order_pay['supplier_code'],$purchase_order['is_drawback'],$purchase_order['purchase_type_id'],$order_pay['pay_type']);
        $data_bank = [
            'bank_name' => $payment_info['account_name'], //开户名
            'account' => $payment_info['account'], //账号
            'payment_platform' => isset($payment_info['payment_platform'])? get_supplier_payment_platform($payment_info['payment_platform']) : '',
            'payment_platform_bank' => $payment_info['payment_platform_bank'],
            'payment_platform_branch' => $payment_info['payment_platform_branch'],
        ];

        //商品信息
        $orderinfo = $this->purchase_db
                ->where('requisition_number', $order_pay['requisition_number'])
                ->get('purchase_order_pay_detail')
                ->result_array();
        if (empty($orderinfo)) {
            throw new Exception('参数requisition_number,不存在');
        }
        foreach ($orderinfo as  $value) {
            $items = $this->purchase_db->select('product_img_url,pur_ticketed_point,product_base_price,confirm_amount')
                    ->where('purchase_number', $value['purchase_number'])
                    ->where('sku', $value['sku'])
                    ->get('purchase_order_items')
                    ->row_array();
           $order_cancel_list= $this->Purchase_order_determine_model->get_order_cancel_list($value['purchase_number'],$value['sku']);
           $cancel_ctq =isset($order_cancel_list[$value['purchase_number'].'-'.$value['sku']])?$order_cancel_list[$value['purchase_number'].'-'.$value['sku']]:0; //取消数量
           $warehouse = $this->purchase_db->where('purchase_number', $value['purchase_number'])
                            ->where('sku', $value['sku'])->get('warehouse_results')->result_array();
            $arrival_qty = $bad_qty = $instock_qty = 0;
            foreach ($warehouse as $v) {
                $arrival_qty += $v['arrival_qty'];
                $bad_qty += $v['bad_qty'];
                $instock_qty += $v['instock_qty'];
            }
            $data_pur['purchase_number'] = $value['purchase_number']; //采购单号
            $data_pur['product_img_url'] = isset($items['product_img_url'])?$items['product_img_url']:''; //商品sku图片
            $data_pur['sku'] = $value['sku']; //sku
            $data_pur['purchase_unit_price'] = $value['purchase_unit_price']; //单价
            $data_pur['product_base_price'] = isset($items['product_base_price'])?$items['product_base_price']:''; //原价单价
            $data_pur['pur_ticketed_point'] = isset($items['pur_ticketed_point'])?$items['pur_ticketed_point']:''; //开票点
            $data_pur['product_name'] = $value['product_name']; //产品名称
            $data_pur['confirm_amount'] =(int)$items['confirm_amount']; //订单数量
            $data_pur['cancel_ctq'] = (int)$cancel_ctq; //取消数量
            $data_pur['arrival_qty'] = (int)$arrival_qty; //收货数量
            $data_pur['unarrived_quantity'] =(int)($items['confirm_amount'] - $arrival_qty); //未到货数量
            $data_pur['instock_qty'] = (int)$instock_qty; //入库数量
            $data_pur['bad_qty'] = (int)$bad_qty; //不良品数量
            $data_pur['subtotal'] = format_price($items['confirm_amount'] * $value['purchase_unit_price']); //小计
            $data_commodity[] = $data_pur;
            unset($data_pur);
        }
        //获取合同单商品总金额
        $this->load->model('compact/Compact_model');
        $result = $this->Compact_model->calculate_compact_related_amount($order_pay['pur_number']);
        if (!$result['code']) {
            throw new Exception($result['msg']);
        }
        $compact_pay_data = $this->Compact_model->get_compact_pay_data($order_pay['pur_number']);
        if(empty($compact_pay_data['code'])){
            throw new Exception($compact_pay_data['msg']);
        }
        $compact_pay_data = $compact_pay_data['data'];

        //费用结算
        $data_cost = [
            'pay_ratio'        => $order_pay['pay_ratio'], //本次请款比例
            'js_ratio'         => $order_pay['js_ratio'], //结算比例
            'product_money'    => $order_pay['product_money'], //请款金额
            'freight'          => $order_pay['freight'], //请款金额
            'discount'         => $order_pay['discount'], //请款金额
            'process_cost'     => $order_pay['process_cost'], //请款金额
            'pay_price'        => $order_pay['pay_price'], //请款金额
            'applicant'        => get_buyer_name($order_pay['applicant']), //申请人
            'application_time' => $order_pay['application_time'], //申请时间


            'order_total_product_money'  => $compact_pay_data['product_money'],
            'order_total_freight'        => $compact_pay_data['freight'],
            'order_total_discount'       => $compact_pay_data['discount'],
            'order_total_process_cost'   => $compact_pay_data['process_cost'],
            'order_total_real_money'     => $compact_pay_data['real_money'],
            'ca_total_real_money'        => $compact_pay_data['ca_total_real_money'],
            'ca_total_product_money'     => $compact_pay_data['ca_total_product_money'],
            'ca_total_process_cost'      => $compact_pay_data['ca_total_process_cost'],
            'paid_total_product_money'   => $compact_pay_data['paid_total_product_money'],
            'paid_total_freight'         => $compact_pay_data['paid_total_freight'],
            'paid_total_discount'        => $compact_pay_data['paid_total_discount'],
            'paid_total_process_cost'    => $compact_pay_data['paid_total_process_cost'],
            'paid_total_real_money'      => $compact_pay_data['paid_total_real_money'],
            'cancel_total_product_money' => $compact_pay_data['cancel_total_product_money'],
            'cancel_total_real_money'    => $compact_pay_data['cancel_total_real_money'],
            'cancel_total_freight'       => $compact_pay_data['cancel_total_freight'],
            'cancel_total_discount'      => $compact_pay_data['cancel_total_discount'],
            'cancel_total_process_cost'  => $compact_pay_data['cancel_total_process_cost'],
            'loss_total_product_money'   => $compact_pay_data['loss_total_product_money'],
            'loss_total_freight'         => $compact_pay_data['loss_total_freight'],
            'loss_total_discount'        => $compact_pay_data['loss_total_discount'],
            'loss_total_process_cost'    => $compact_pay_data['loss_total_process_cost'],
            'loss_total_real_money'      => $compact_pay_data['loss_total_real_money'],
            'available_product_money'    => $compact_pay_data['available_product_money'],
            'available_freight'          => $compact_pay_data['available_freight'],
            'available_discount'         => $compact_pay_data['available_discount'],
            'available_process_cost'     => $compact_pay_data['available_process_cost'],
            'available_real_money'       => $compact_pay_data['available_real_money'],
        ];

        if($order_pay['requisition_method']==4){
            //获取结算比例
            $order_pay_type = $this->purchase_db
                ->select('settlement_ratio')
                ->where_in('purchase_number', $ppurchase_numbers)
                ->get('purchase_order_pay_type')
                ->row_array();
            $settlement_ratio = '';
            if(isset($order_pay_type['settlement_ratio'])) $settlement_ratio =$order_pay_type['settlement_ratio'];
            $data_cost['pay_ratio'] =0;//获取采购的结算比例
            $data_cost['js_ratio'] =$settlement_ratio;
        }
        unset($result);
        //付款申请书
        $results = $this->Compact_list_model->get_pay_requisition($order_pay['pur_number'], $order_pay['requisition_number']);
        //获取供应商图片
        $supplier_img = $this->Supplier_images_model->get_image_list($order_pay['supplier_code']);
        $data_list = [
            'id' => $order_pay['id'],
            'system' => $data_system,
            'bank' => $data_bank,
            'commodity' => $data_commodity,
            'cost' => $data_cost,
            'pay_requisition' => $results['data'],
            'freight_desc' => isset($order_pay['freight_desc'])?$order_pay['freight_desc']:'',
            'compact_url' => isset($order_pay['compact_url'])? explode(',', $order_pay['compact_url']):[],
            'supplier_img' => $supplier_img,
            'notice' => [
                'create_notice' => isset($order_pay['create_notice'])?$order_pay['create_notice']:'', //请款备注
                'review_notice' => isset($order_pay['review_notice'])?$order_pay['review_notice']:'', //采购经理审核备注
                'waiting_notice' => isset($order_pay['waiting_notice'])?$order_pay['waiting_notice']:'', //供应链审核备注
                'processing_notice' => isset($order_pay['processing_notice'])?$order_pay['processing_notice']:'', //财务审核备注
                'financial_supervisor_notice' => isset($order_pay['financial_supervisor_notice'])?$order_pay['financial_supervisor_notice']:'', //财务审核备注
                'financial_manager_notice' => isset($order_pay['financial_manager_notice'])?$order_pay['financial_manager_notice']:'', //财务经理审核备注
                'financial_officer_notice' => isset($order_pay['financial_officer_notice'])?$order_pay['financial_officer_notice']:'', //财务经理审核备注
                'general_manager_notice' => isset($order_pay['general_manager_notice'])?$order_pay['general_manager_notice']:'', //总经办审核备注
            ],
        ];
        return $data_list;
    }

    /**
     * 财务审核及驳回操作
     * @author harvin 2019-1-17
     * @param int $id 参数
     * @param string $review_notice  审核备注
     * @param int $type 1 审核通过 2 审核驳回
     * @param int $status 审核级别
     * @return mixed
     * @throws Exception
     * * */
    public function get_contract_order_save($id, $review_notice, $type,$status=PAY_WAITING_FINANCE_REVIEW) {
          $this->load->model('Reject_note_model');
          $this->load->model('purchase_order_pay_model'); // 请款单
          $this->load->model('Ding_talk_model','ding_talk'); // 钉钉接口
          $query = $this->purchase_db;
        try {
                //开始事物
                $query->trans_begin();
                $order_pay = $this->purchase_db
                    ->select('pur_number,pay_status,requisition_number,source,source_subject,pay_type,pay_category,pay_price,freight')
                    ->where('id', $id)
                    ->get('purchase_order_pay')
                    ->row_array();
                if(empty($order_pay)){
                    throw new Exception("未知的id");
                }
                if(in_array($order_pay['pay_status'],[PART_PAID,PAY_PAID])){
                    throw new Exception("已付款的不能执行此操作");
                }
                if($order_pay['source'] != SOURCE_NETWORK_ORDER && $order_pay['pay_status'] != $status){
                    throw new Exception("请款单状态已发生变更，请刷新数据后操作");
                }
               //获取菜单单号
               $order_pay_detail=$this->purchase_db
                       ->select('purchase_number')
                       ->where('requisition_number',$order_pay['requisition_number'])
                       ->get('purchase_order_pay_detail')
                       ->result_array();
               if(empty($order_pay_detail)){
                    throw new Exception("未知的请款单号");
               }
               $purchase_number= array_column($order_pay_detail, 'purchase_number');
               //去除相同的元素
               $purchase_numbers= array_unique($purchase_number);
                if($type == 1){
                    //根据请款金额及运费判断是否要总经办审核
                    $data_list = $this->get_aduit_lave_adopt($order_pay, $order_pay['source'], $review_notice, $status);
                }elseif($type == 2){
                    $data_list = $this->get_aduit_lave_reject($order_pay, $order_pay['source'], $review_notice, $status);
                }else{
                    throw new Exception("未知的type");
                }
               if($data_list['temp']['pay_status']==PAY_WAITING_FINANCE_PAID){ //待财务付款
                     //判断支付方式（单独请运费）支付方式为“线上支付宝”时，财务经理审核通过后，数据流向“网采单”列表
                    if($order_pay['pay_type']==PURCHASE_PAY_TYPE_ALIPAY && $order_pay['pay_category']==PURCHASE_PAY_CATEGORY_4){
                        $source=SOURCE_NETWORK_ORDER;
                    }else{
                        $source=SOURCE_COMPACT_ORDER;
                    }
                   $data_list['pay_status']['source']=$source;
               }
                //更新请款单状态
               $this->purchase_db->where('id', $id)->update('purchase_order_pay', $data_list['pay_status']);
                //更新采购单付款状态
                if ($order_pay['source'] == SOURCE_NETWORK_ORDER) {
                    $this->purchase_db->where('purchase_number', $order_pay['pur_number'])->update('purchase_order', $data_list['temp']);
                    $this->purchase_order_pay_model->push_purchase_order_pay_status($order_pay['pur_number'], $review_notice); // 推送采购单付款状态

                    if(stripos($order_pay['pur_number'], 'HT') !== false){// 合同单请运费走的是 网采付款方式，也要更新合同的付款状态
                        $this->load->model('compact/Compact_model');
                        $this->Compact_model->change_compact_pay_status($order_pay['pur_number'], $data_list['temp']['pay_status']);
                    }
                } elseif ($order_pay['source'] == SOURCE_COMPACT_ORDER) {
                    $this->load->model('compact/Compact_model');
                    $result = $this->Compact_model->change_compact_pay_status($order_pay['pur_number'], $data_list['temp']['pay_status']);
                    if (!$result['code']) {
                        throw new Exception($result['msg']);
                    }
                } else {
                    throw new Exception('请款类型不存在');
                }
             
              //记录操作日志
               $this->Reject_note_model->get_insert_log($data_list['log']);

                $role_number = $data_list['temp']['pay_status'];

               unset($data_list);
               if ($query->trans_status() === FALSE) {
                   $query->trans_rollback();
                   return ['msg'=>'保存失败','bool'=>FALSE];
                 
               } else {
                   $query->trans_commit();
                   if($type==1) {
                       //钉钉消息通知
                       $param = array(
                           'role_number' => $role_number,
                           'msg' => '您有一条新的待审核的请款，请款单号' . $order_pay['requisition_number'] . '，请款总额为' . $order_pay['pay_price'] . '元，运费为' . $order_pay['freight'] . '元，请前往采购系统及时处理！'.date('Y-m-d H:i:s'),
                       );
                       $this->ding_talk->pushDingTalkInfo($param);
                   }else{

                       if($status == PAY_UFXFUIOU_SUPERVISOR){

                           $swooleTitle = "财务主管审核";
                       }

                       if( $status == PAY_UFXFUIOU_MANAGER){
                           $swooleTitle = "财务经理审核";
                       }

                       if( $status == PAY_WAITING_FINANCE_REVIEW){

                           $swooleTitle = '财务审核';
                       }
                       if( $status == PAY_UFXFUIOU_SUPPLY){

                           $swooleTitle = '财务副总监审核';
                       }
                       $this->Message_model->AcceptMessage('money',['data'=>[$id],'message'=>$review_notice,'user'=>getActiveUserName(),'type'=>$swooleTitle]);
                   }
                   return ['msg'=>'保存成功','bool'=>TRUE];
            }
        } catch (Exception $exc) {
           return ['msg'=>$exc->getMessage(),'bool'=>FALSE];
        } 
    }
            
    /**
     * 获取审核级别数据(审核通过)
     * @author harvin
     * @param array $order_arr
     * @param string $source
     * @param string $review_notice
     * @param string $status
     * @return array
     */
    public function get_aduit_lave_adopt(array $order_arr,$source=1,$review_notice='',$status=PAY_WAITING_FINANCE_REVIEW){
        $data=$log=$temp=[];

        $this->load->model('compact/compact_model');
        $this->load->model('statement/Purchase_statement_model');

        if ($status == PAY_WAITING_FINANCE_REVIEW) { //待财务审核
            $data = [
                //'pay_status' => PAY_UFXFUIOU_SUPERVISOR,
                'pay_status' => PAY_UFXFUIOU_MANAGER,
                'source' => $source,
                'approver' => getActiveUserId(),
                'processing_notice' => $review_notice,
                'processing_time' => date('Y-m-d H:i:s'),
            ];

            $log = [
                'record_number' => isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '',
                'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                'content' => '财务审核成功',
                'content_detail' =>(isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '') . $review_notice
            ];
            $temp = [
                //'pay_status' => PAY_UFXFUIOU_SUPERVISOR,
                'pay_status' => PAY_UFXFUIOU_MANAGER,
            ];
            return ['pay_status' => $data, 'log' => $log, 'temp' => $temp];
        } elseif ($status == PAY_UFXFUIOU_SUPERVISOR) { //待财务主管审核
            $data = [
                'pay_status' => PAY_UFXFUIOU_MANAGER,
                'source' => $source,
                'financial_supervisor_id' => getActiveUserId(),
                'financial_supervisor_notice' => $review_notice,
                'financial_supervisor_time' => date('Y-m-d H:i:s'),
            ];
            $log = [
                'record_number' => isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '',
                'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                'content' => '财务主管审核成功',
                'content_detail' => '请款单号' . (isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '') . $review_notice
            ];
            $temp = [
                'pay_status' => PAY_UFXFUIOU_MANAGER,
            ];
            return ['pay_status' => $data, 'log' => $log, 'temp' => $temp];
        } elseif ($status == PAY_UFXFUIOU_MANAGER) { //待财务经理审核
            $data = [
                'pay_status' => PAY_UFXFUIOU_SUPPLY,
                'source' => $source,
                'financial_manager_id' => getActiveUserId(),
                'financial_manager_notice' => $review_notice,
                'financial_manager_time' => date('Y-m-d H:i:s'),
            ];
            $log = [
                'record_number' => isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '',
                'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                'content' => '财务主管审核成功',
                'content_detail' => '请款单号' . (isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '') . $review_notice
            ];
            $temp = [
                'pay_status' => PAY_UFXFUIOU_SUPPLY,
            ];
            return ['pay_status' => $data, 'log' => $log, 'temp' => $temp];
        } elseif ($status == PAY_UFXFUIOU_SUPPLY) { //财务总监审核
            if($order_arr['source_subject'] == SOURCE_SUBJECT_COMPACT_ORDER){// 合同单
                $compactInfo = $this->compact_model->get_compact_one($order_arr['pur_number'],false);// 获取总采购商品额
                $total_product_money = $compactInfo['product_money'];
            }else{// 对账单
                $total_product_money = $this->Purchase_statement_model->get_total_instock_price($order_arr['pur_number']);// 获取总入库金额
            }


            if ($order_arr['freight'] == 0) { //运费等于0
                $data = [
                    'pay_status' => PAY_WAITING_FINANCE_PAID,
                    'source' => $source,
                    'financial_officer_id' => getActiveUserId(),
                    'financial_officer_notice' => $review_notice,
                    'financial_officer_time' => date('Y-m-d H:i:s'),
                ];
                $log = [
                    'record_number' => isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '',
                    'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                    'content' => '财务副总监审核成功',
                    'content_detail' =>(isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '') . $review_notice
                ];
                $temp = [
                    'pay_status' => PAY_WAITING_FINANCE_PAID,
                ];
                return ['pay_status' => $data, 'log' => $log, 'temp' => $temp];
            } else { //有运费 直接变成待总经办审核（去除 财务总监审核 流程）

                if (bccomp($total_product_money, 200000, 3) >= 0) {// 采购金额>20万
                    $data = [
                        'pay_status' => PAY_GENERAL_MANAGER,//修改为总经办审核
                        'source' => $source,
                        'financial_officer_id' => getActiveUserId(),
                        'financial_officer_notice' => $review_notice,
                        'financial_officer_time' => date('Y-m-d H:i:s'),
                    ];
                    $temp = [
                        'pay_status' => PAY_GENERAL_MANAGER,//修改为总经办审核
                    ];
                }else {
                    $data = [
                        'pay_status' => PAY_WAITING_FINANCE_PAID,//变成待财务付款
                        'source' => $source,
                        'financial_officer_id' => getActiveUserId(),
                        'financial_officer_notice' => $review_notice,
                        'financial_officer_time' => date('Y-m-d H:i:s'),
                    ];
                    $temp = [
                        'pay_status' => PAY_WAITING_FINANCE_PAID,//修改为总经办审核
                    ];
                }

                $log = [
                    'record_number' => isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '',
                    'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                    'content' => '财务副总监审核成功',
                    'content_detail' =>(isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '') . $review_notice
                ];
                return ['pay_status' => $data, 'log' => $log, 'temp' => $temp];
            }
        }elseif($status == PAY_GENERAL_MANAGER){ //总经办审核
            $data = [
                'pay_status' => PAY_WAITING_FINANCE_PAID,
                'source' => $source,
                'general_manager_id' => getActiveUserId(),
                'general_manager_notice' => $review_notice,
                'general_manager_time' => date('Y-m-d H:i:s'),
            ];
            $log = [
                'record_number' => isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '',
                'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                'content' => '总经办审核成功',
                'content_detail' => (isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '') . $review_notice
            ];
            $temp = [
                'pay_status' => PAY_WAITING_FINANCE_PAID,
            ];
            return ['pay_status' => $data, 'log' => $log, 'temp' => $temp];
        }else{
             return ['pay_status' => $data, 'log' => $log, 'temp' => $temp];
        }
    }
     /**
     * 获取审核级别数据(审核驳回)
     * @author harvin
     * @param array $order_arr
     * @param string $source
     * @param string $review_notice
     * @param string $status
     * @return array
     */
    public function get_aduit_lave_reject(array $order_arr,$source=1,$review_notice='',$status=PAY_WAITING_FINANCE_REVIEW){
        $data = $log = $temp = [];
        if ($status == PAY_WAITING_FINANCE_REVIEW) { //待财务审核
            $data = [
                'pay_status' => PAY_FINANCE_REJECT,
                'source' => $source,
                'approver' => getActiveUserId(),
                'processing_notice' => $review_notice,
                'processing_time' => date('Y-m-d H:i:s'),
                'payment_notice' => $review_notice,
            ];

            $log = [
                'record_number' => isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '',
                'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                'content' => '财务审核驳回',
                'content_detail' =>(isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '') . $review_notice
            ];
            $temp = [
                'pay_status' => PAY_FINANCE_REJECT,
            ];
            return ['pay_status' => $data, 'log' => $log, 'temp' => $temp];
        } elseif ($status == PAY_UFXFUIOU_SUPERVISOR) { //待财务主管审核
            $data = [
                'pay_status' => PAY_REJECT_SUPERVISOR,
                'source' => $source,
                'financial_supervisor_id' => getActiveUserId(),
                'financial_supervisor_notice' => $review_notice,
                'financial_supervisor_time' => date('Y-m-d H:i:s'),
            ];
            $log = [
                'record_number' => isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '',
                'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                'content' => '财务主管审核驳回',
                'content_detail' =>(isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '') . $review_notice
            ];
            $temp = [
                'pay_status' => PAY_REJECT_SUPERVISOR,
            ];
            return ['pay_status' => $data, 'log' => $log, 'temp' => $temp];
        } elseif ($status == PAY_UFXFUIOU_MANAGER) { //待财务经理审核                       
            $data = [
                'pay_status' => PAY_REJECT_MANAGER,
                'source' => $source,
                'financial_manager_id' => getActiveUserId(),
                'financial_manager_notice' => $review_notice,
                'financial_manager_time' => date('Y-m-d H:i:s'),
            ];
            $log = [
                'record_number' => isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '',
                'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                'content' => '财务经理审核驳回',
                'content_detail' =>(isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '') . $review_notice
            ];
            $temp = [
                'pay_status' => PAY_REJECT_MANAGER,
            ];
            return ['pay_status' => $data, 'log' => $log, 'temp' => $temp];
        } elseif ($status == PAY_UFXFUIOU_SUPPLY) { //财务总监审核
            $data = [
                'pay_status' => PAY_REJECT_SUPPLY,
                'source' => $source,
                'financial_officer_id' => getActiveUserId(),
                'financial_officer_notice' => $review_notice,
                'financial_officer_time' => date('Y-m-d H:i:s'),
            ];
            $log = [
                'record_number' => isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '',
                'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                'content' => '财务副总监审核驳回',
                'content_detail' =>(isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '') . $review_notice
            ];
            $temp = [
                'pay_status' => PAY_REJECT_SUPPLY,
            ];
            return ['pay_status' => $data, 'log' => $log, 'temp' => $temp];
        } elseif ($status == PAY_GENERAL_MANAGER) { //总经办审核
            $data = [
                'pay_status' => PAY_GENERAL_MANAGER_REJECT,
                'source' => $source,
                'general_manager_id' => getActiveUserId(),
                'general_manager_notice' => $review_notice,
                'general_manager_time' => date('Y-m-d H:i:s'),
            ];
            $log = [
                'record_number' => isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '',
                'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                'content' => '总经办审核驳回',
                'content_detail' =>(isset($order_arr['requisition_number']) ? $order_arr['requisition_number'] : '') . $review_notice
            ];
            $temp = [
                'pay_status' => PAY_GENERAL_MANAGER_REJECT,
            ];
            return ['pay_status' => $data, 'log' => $log, 'temp' => $temp];
        } else {
            return ['pay_status' => $data, 'log' => $log, 'temp' => $temp];
        }
    }
    /**
     * 财务付款页面
     * @param srting $id 参数id
     * @author harvin 2019-1-17
     * */
    public function get_payment_contract($id) {
        //获取请款单信息
        try {
            $order_pay = $this->purchase_db
                    ->select('id,supplier_code,pay_type,pur_number,requisition_number,js_ratio,settlement_method,pay_price')
                    ->where('id', $id)
                    ->where('source', SOURCE_COMPACT_ORDER)
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
                    ->select('is_drawback, purchase_type_id')
                    ->where_in('purchase_number', $purchase_number)
                    ->where('source', SOURCE_COMPACT_ORDER)
                    ->get('purchase_order')
                    ->row_array();
            if (empty($purchase_order)) {
                throw new Exception('采购单不存在');
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
            $supplier_list = $this->get_supplier_name([$order_pay['supplier_code']]);
            $supplier_name = isset($supplier_list[$order_pay['supplier_code']])?$supplier_list[$order_pay['supplier_code']]:'';
            $supplier_pay =  $this->Supplier_payment_info_model->check_payment_info($order_pay['supplier_code'], $purchase_order['is_drawback'], $purchase_order['purchase_type_id']);

            $account_pay=$this-> get_payment_bank($account_short='中国银行锦绣支行（尾号6681）');
            $data_system = [
                'id' => $order_pay['id'],
                'supplier_name' => $supplier_name, //供应商
                'supplier_code' => $order_pay['supplier_code'], //供应商编码
                'settlement_method' => $this->get_settlement_method($order_pay['settlement_method']), //结算方式
                'payment_method' => getPayType($order_pay['pay_type']), //支付方式
                'is_drawback' => $purchase_order['is_drawback'] == PURCHASE_IS_DRAWBACK_Y ? '是' : '否',
                'js_ratio' => $order_pay['js_ratio'], //结算比例
                'is_freight' => getFreightPayment($freight['is_freight']), //运费支付
                'pay_type' => getPayType(),
                'payment_platform_branch'=> isset($supplier_pay['payment_platform_branch'])?$supplier_pay['payment_platform_branch']:'',// 收款方--支付
                'account'=>isset($supplier_pay['account'])?$supplier_pay['account']:'',//收款方--账号
                'account_name'=>isset($supplier_pay['account_name'])?$supplier_pay['account_name']:'',//收款方--开户名
                'account_short' => $this->get_bank(),
                'branch'=> isset($account_pay['branch'])?$account_pay['branch']:'', //我司--支行
                'account_number'=> isset($account_pay['account_number'])?$account_pay['account_number']:'',//我司--账号
                'account_holder'=>isset($account_pay['account_holder'])?$account_pay['account_holder']:'',//我司--开户人
                'pay_price' => $order_pay['pay_price'], //申请金额
            ];
          return  ['data'=>$data_system,'msg'=>'成功'];
        } catch (Exception $exc) {   
             return  ['data'=>[],'msg'=>$exc->getMessage()];
        }
    }

    /**
     * 获取银行卡信息
     * @author harvin 2019-1-18 
     * */
    public function get_bank() {
        $bank = $this->purchase_db->select('account_short')->where('status',1)->get('bank_card')->result_array();
        $data = [];
        foreach ($bank as $key => $value) {
            $data[$value['account_short']] = $value['account_short'];
        }
        return $data;
    }

    /**
     * 获取银行卡信息
     * @author harvin 2019-1-18 
     * @param sring $account_short 
     * */
    public function get_payment_bank($account_short) {
        $bank = $this->purchase_db
                ->select('branch,account_number,account_holder,k3_bank_account,account_short')
                ->where('account_short', $account_short)
                ->get('bank_card')
                ->row_array();
        
        return !empty($bank)?$bank:[];
    }

    /**
     * 获取指定供应商账户信息
     * @author harvin 2019-1-17
     * @param srting $supplier_code 供应商编码
     * @param int   $payment_method  支付方式:1.支付宝,2.对公支付，3.对私支付
     * @return mixed
     */
    public function get_payment_linkage($supplier_code, $payment_method) {
        $this->load->helper('status_supplier');
        if(!isset($supplier_code) || !isset($supplier_code)){
            return [];
        }
        $data = $this->purchase_db->select('payment_platform_branch,account,account_name,id_number,phone_number,payment_platform,payment_platform_bank')
                ->where('supplier_code', $supplier_code)
                ->where('payment_method', $payment_method)
                ->where('is_del',0)
                ->get('supplier_payment_info')
                ->row_array();
        if(empty($data)){
             return [];
        }
        $data['payment_platform']= get_supplier_payment_platform($data['payment_platform']);
      
        return !empty($data)?$data:[];
    }
    /**
     * 网采单线下付款
     * @author harvin
     * @param type $requisition_number
     * @return type
     * @throws Exception
     */
    public function get_order_net_pay($requisition_number,$payment_notice){
       $this->load->model('Reject_note_model');
            $query = $this->purchase_db;
        try {
            $query->trans_begin();
            //获取请款单
            $order_pay = $query->select('pur_number,product_money,freight,process_cost,supplier_code,'
                    . 'discount,requisition_number,pay_category,pay_price,pay_status,source')
                    ->where('requisition_number', $requisition_number)
                    ->get('purchase_order_pay')
                    ->row_array();

            if (empty($order_pay)) {
                throw new Exception('请款单不存在');
            }
            if($order_pay['pay_status']!=PAY_WAITING_FINANCE_PAID){
                throw new Exception('付款状态不是待财务付款状态');
            }
            $data = [
                'pay_status' => PAY_PAID,
                'real_pay_price' => $order_pay['pay_price'], //申请金额
                'payer_time' => date('Y-m-d H:i:s'),
                'payment_notice'=>$payment_notice,
                'payer_id' => getActiveUserId(),
                'payer_name' => getActiveUserName(),
            ];
            //更新请款单状态
            $this->purchase_db->where('requisition_number', $requisition_number)->update('purchase_order_pay', $data);
            //记录操作日志
            $log = [
                'record_number' => $order_pay['pur_number'],
                'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                'content' => '财务付款',
                'content_detail' => '请款单号' . $order_pay['requisition_number']
            ];
            $this->Reject_note_model->get_insert_log($log);
             //记录收款方信息
              $order_pay_water = [
                 'supplier_code' => $order_pay['supplier_code'],
                 'pur_number' => $order_pay['pur_number'],
                 'billing_object_type' => 1,
                 'transaction_number' => '',
                 'is_bill' => 2,
                 'price' => $order_pay['pay_price'],
                 'original_price' => $order_pay['pay_price'],
                 'original_currency' => 'RMB',
                 'remarks' =>'网采单线下支付',
                 'create_id' => getActiveUserId(),
                 'create_name' => getActiveUserName(),
                 'create_time' => date('Y-m-d H:i:s'),
                 'beneficiary_payment_method' =>PURCHASE_PAY_TYPE_PUBLIC,
                 'beneficiary_branch' => isset($payment_linkage['payment_platform_branch']) ? $payment_linkage['payment_platform_branch'] : '',
                 'beneficiary_account' => isset($payment_linkage['account']) ? $payment_linkage['account'] : '',
                 'beneficiary_account_name' => isset($payment_linkage['account_name']) ? $payment_linkage['account_name'] : '',
                 'our_branch' => isset($payment['branch']) ? $payment['branch'] : '',
                 'our_account_abbreviation' =>'',
                 'our_account_holder' => isset($payment['account_holder']) ? $payment['account_holder'] : '',
                 'pay_time' =>date('Y-m-d H:i:s'),
             ];
             $this->purchase_db->insert('purchase_order_pay_water', $order_pay_water);
           
             
            //更新采购单付款状态
               $data_order=[
                   'pay_time'=>date('Y-m-d H:i:s'),
                   'pay_status'=>PAY_PAID,
               ];
			    if ($order_pay['pay_category'] == PURCHASE_PAY_CATEGORY_4) { //请款类型 不是 运费请款 
			
			    $this->load->model('compact/Compact_model');
                $result = $this->Compact_model->change_compact_pay_status($order_pay['pur_number'], PAY_PAID, $data_order['pay_time']);
                if (!$result['code']) {
                    throw new Exception($result['msg']);
                }
			
			
			} else{
				$this->purchase_db->where('purchase_number',$order_pay['pur_number'])->update('purchase_order',$data_order); 
			}
                 
             $order_log=[
                'record_number' => $order_pay['pur_number'],
                'record_type' => 'PUR_PURCHASE_ORDER',
                'content' => '财务付款',
                'content_detail' => '请款单号' . $order_pay['requisition_number']."采购单付款状态变更为【已付款】"
             ];     
              $this->Reject_note_model->get_insert_log($order_log);

              //样品线上付款时更新产品系统
             if($order_pay['pay_category'] ==5){
                 $request_url = getConfigItemByName('api_config', 'product_system', 'prodSamplePurchaseOrder-updateStatus');
                 $params         = ['purchaseNnumber' => $order_pay['pur_number']];
                 $header         = array('Content-Type: application/json');
                 $access_token   = getOASystemAccessToken();
                 $request_url    = $request_url.'?access_token='.$access_token;
                 $results        = getCurlData($request_url,json_encode($params),'post',$header);
                 $results        = json_decode($results,true);
                 if(isset($results['code'])){
                     if($results['code'] == 200){
                         $status = 1;
                     }else{
                         $status = 0;
                     }
                     if($status){
                         if ($query->trans_status() === FALSE) {
                             $query->trans_rollback();
                             return  ['msg'=>'付款失败','bool'=>FALSE];
                         } else {
                             $query->trans_commit();
                             return  ['msg'=>'付款成功','bool'=>TRUE];
                         }
                     }else{
                         $message = isset($results['msg'])?$results['msg']:'接口返回错误码：'.$results['code'];
                         throw new Exception($message);
                     }
                 }else{
                     throw new Exception('推送采购单(YPO)付款状态到产品系统执行出错');
                 }
             }else {
                 if ($query->trans_status() === FALSE) {
                     $query->trans_rollback();
                     return ['msg' => '付款失败', 'bool' => FALSE];
                 } else {
                     $query->trans_commit();
                     return ['msg' => '付款成功', 'bool' => TRUE];

                 }
             }
        } catch (Exception $exc) {
             return  ['msg'=>$exc->getMessage(),'bool'=>FALSE];
           
        }
        
    }

    /**
     * 保存以财务付款操作通过
     * @author harvin 2019-1-18
     * @param string $pay_account     支付账号
     * @param string $pay_branch_bank 付款银行
     * @param string $pay_number      付款账号名
     * @param string $pay_price       申请金额
     * @param string $id              参数id
     * @param string $images          付款回执
     * @param string $payer_time      付款时间
     * @param string $payment_notice  付款备注
     * @return array
     */
    public function get_payment_contract_save($pay_account, $pay_branch_bank, $pay_number, $pay_price, $id, $images, $payer_time,$payment_notice='') {
        $this->load->model('Reject_note_model');
        $query = $this->purchase_db;
        try{
            $query->trans_begin();
            //获取请款单
            $order_pay = $query->select('pur_number,source,source_subject,product_money,freight,discount,process_cost,requisition_number,pay_category')
                ->where('id', $id)
                ->get('purchase_order_pay')
                ->row_array();

            if(empty($order_pay)){
                throw new Exception('请款单不存在');
            }


            $data = [
                'pay_status'      => PAY_PAID,
                'real_pay_price'  => $pay_price, //申请金额
                'images'          => $images,
                'pay_account'     => $pay_account,
                'pay_number'      => $pay_number,
                'pay_branch_bank' => $pay_branch_bank,
                'payer_time'      => $payer_time,
                'payment_notice'  => $payment_notice,
                'payer_id'        => getActiveUserId(),
                'payer_name'      => getActiveUserName(),
            ];
            //更新请款单状态
            $this->purchase_db->where('id', $id)->update('purchase_order_pay', $data);

            if($order_pay['source_subject'] == SOURCE_SUBJECT_COMPACT_ORDER){// 合同单请款
                $this->load->model('compact/Compact_model');

                //获取合同单总金额
                $compact = $query->select('real_money,compact_number')
                    ->where('compact_number', $order_pay['pur_number'])
                    ->get('purchase_compact')
                    ->row_array();
                if(empty($compact)){
                    throw new Exception('合同单不存在');
                }
                $result = $this->Compact_model->calculate_compact_related_amount($compact['compact_number']);
                if(!$result['code']){
                    throw new Exception($result['msg']);
                }
                //采购单商品总额
                $available_product_money = $result['data']['cancel_total_real_money']; //已取消金额
                $total                   = $compact['real_money'] - $available_product_money;
                //判断采购单付款状态是否 已付款 已部分付款
                $res = $this->purchase_db->select('pay_price')->where('pur_number', $order_pay['pur_number'])->where('pay_status', PAY_PAID)->get('purchase_order_pay')->result_array();
                //历史请款金额总额
                $li_tatal = 0;
                if(!empty($res)){
                    foreach($res as $key => $value){
                        $li_tatal += $value['pay_price'];
                    }
                    $li_tatal = $li_tatal + $pay_price;
                }else{
                    $li_tatal = $pay_price;
                }
                if($total - $li_tatal > IS_ALLOWABLE_ERROR){// 误差超过 1元是已部分付款，小于1元是 已付款
                    //该采购单付款状态 为部分付款
                    $result = $this->Compact_model->change_compact_pay_status($order_pay['pur_number'], PART_PAID, $payer_time);
                    if(!$result['code']){
                        throw new Exception($result['msg']);
                    }
                }else{
                    $result = $this->Compact_model->change_compact_pay_status($order_pay['pur_number'], PAY_PAID, $payer_time);
                    if(!$result['code']){
                        throw new Exception($result['msg']);
                    }
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
                'content_detail' => '请款单号'.$order_pay['requisition_number']
            ];
            $this->Reject_note_model->get_insert_log($log);

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
     * 财务驳回(合同单)
     * @author harvin 2019-7-15
     * @param int $id
     * @param string $payment_notice
     * @return type
     * @throws Exception
     */
  public function get_payment_contract_reject($id,$payment_notice='财务付款驳回'){
        $this->load->model('Reject_note_model');
        $query = $this->purchase_db;
        try {
            //开始事物
            $query->trans_begin();
            $order_pay = $this->purchase_db
                    ->select('requisition_number,pur_number,pay_category')
                    ->where('id', $id)
                    ->get('purchase_order_pay')
                    ->row_array();
            if (empty($order_pay)) {
                throw new Exception('参数id,不存在');
            }
            $data = [
                'pay_status' => PAY_FINANCE_REJECT,
                'payer_id' => getActiveUserId(),
                'payer_name'=> getActiveUserName(),  
                'payment_notice'=>$payment_notice,
            ];
            //记录操作日志
            $log = [
                'record_number' => $order_pay['pur_number'],
                'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                'content' => '财务付款驳回',
                'content_detail' => '请款单号' . $order_pay['requisition_number'] . '财务付款驳回'
            ];      
            //更新请款单状态
            $this->purchase_db->where('id', $id)->update('purchase_order_pay', $data);
          //   if ($order_pay['pay_category'] != PURCHASE_PAY_CATEGORY_4) { //请款类型 不是 运费请款  
                //更新采购单付款状态 更新采购单付款状态
                $this->load->model('compact/Compact_model');
                $result = $this->Compact_model->change_compact_pay_status($order_pay['pur_number'], PAY_FINANCE_REJECT);
                if (!$result['code']) {
                    throw new Exception($result['msg']);
                }
          //  }
            //记录操作日志
            $this->Reject_note_model->get_insert_log($log);
            if ($query->trans_status() === FALSE) {
                $query->trans_rollback();
                return ['msg' => '驳回失败', 'bool' => false];
            } else {
                $query->trans_commit();
                $this->Message_model->AcceptMessage('money',['data'=>[$id],'message'=>$payment_notice,'user'=>getActiveUserName(),'type'=>'财务付款']);
                return ['msg' => '驳回成功', 'bool' => TRUE];
            }
        } catch (Exception $exc) {
            return ['msg' => $exc->getMessage(), 'bool' => false];
        }
    }

    /**
     * 根据id查询单条记录某字段的值
     * @author liwuxue
     * @date 2019/2/13 10:04
     * @param
     * @return mixed
     * @throws Exception
     */
    public function get_field_by_id($id, $field)
    {
        if ($id <= 0) {
            throw new Exception("错误的id");
        }
        if (empty($field) || !is_string($field)) {
            throw new Exception("error field[{$field}]");
        }
        $row = $this->purchase_db->select($field)->where("id", (int)$id)->get("purchase_order_pay")->row_array();
        if (empty($row)) {
            throw new Exception("错误的id");
        }
        return $row[$field];
    }

    /**
     * 财务-应付款-合同-付款备注显示 显示付款备注api
     * @author liwuxue
     * @date 2019/2/13 10:09
     * @param int $id
     * @return mixed
     * @throws Exception
     */
    public function api_get_note($id)
    {
        $note = $this->get_field_by_id($id, "payment_notice");
        $note = !empty($note) ? json_decode($note, true) : [];
        return $note;
    }

    /**
     *财务-应付款-合同- 添加付款备注
     * @author liwuxue
     * @date 2019/2/13 11:08
     * @param $id
     * @param $note
     * @return mixed
     * @throws Exception
     */
    public function api_add_note($id, $note)
    {
        if (!is_string($note) || empty(trim($note))) {
            throw new Exception("备注信息不能为空");
        }
        $notes = $this->api_get_note($id);
        $notes[] = [
            "user" => getActiveUserName(),
            "time" => date('Y-m-d H:i:s'),
            "note" => $note,
        ];
        $row = [
            "payment_notice" => json_encode($notes, JSON_UNESCAPED_UNICODE),
        ];
        if (!$this->purchase_db->where("id", $id)->update("purchase_order_pay", $row)) {
            throw new Exception("保存失败");
        }
        return true;
    }

    /**
     * 获取请款单的金额信息
     * @param  object  $model        请款单对象 [PurchaseOrderPaySearch extends PurchaseOrderPay]
     * @param  boolean $isOnlyTotal [仅显示：优惠后的总额]
     * @param  integer $source      [判断是否是合同：1合同，2网采]
     * @param  boolean  $money_list  是否返回所有金额的值
     * @return mixed
     */
    public function getPrice($model, $isOnlyTotal = false, $source=SOURCE_NETWORK_ORDER ,$money_list = false)
    {
        $freight = 0;
        $discount = 0;

        /**@ Table pur_order_pay_demand_map 中的 requisition_number（请款单编号关联） **/
        //if(!empty($model->orderPayDemandMap)) {
           // return self::getNewPrice($model,$isOnlyTotal,$source,$money_list);
       // }

        /**@ Table pur_purchase_order_pay_type 中的 freight,freight（采购单信息确认-请款金额相关信息） 关联字段是：purchase_number 采购单号 **/
        if(isset($model->freight)) {
            $freight = $model->freight ? $model->freight : 0;
            $discount = $model->discount ? $model->freight : 0;
        }
        //$final_money = $model->pay_price + $freight - $discount;

        //判断是否有勾选运费和折扣
        $this->load->model('Purchase_order_pay_detail_model','orderPayDetail');
        $detailDatas = (object)$this->orderPayDetail->findOnes(['requisition_number'=>$model->requisition_number],'id,freight,discount');
        $is_check_freight = 1;
        $is_check_discount = 1;

        //计算原始金额  因为pay_price是如果勾选了运费、优惠 已经计算了的值
        $freight = isset($detailDatas->freight)?0:$detailDatas->freight;
        $discount = isset($detailDatas->discount)?0:$detailDatas->discount;

        //如果运费、优惠为0  判断是不是批量付款过来付款通知
        if( (stripos($model->pur_number,'FBA') !== false) AND $model->pay_category != 30 ){
            // FBA 非批量请款不读取订单运费、优惠
            //todo
        }else{
            if( $freight==0 && $discount==0 ){
                $pay_type = (object)$this->orderPayType->get_one($model->pur_number);
                $freight  = !isset($pay_type->freight) ? 0 : $pay_type->freight;
                $discount = !isset($pay_type->discount) ? 0 : $pay_type->discount;
            }
        }

        //如果该采购单位合同单，pay_price存的是计算优惠和运费后的请款金额，所以原请款额要 减运费+优惠
        if ($source==1) $model->pay_price = $model->pay_price-$freight+$discount;

        $data  = '<span style="color: #E06B26;font-weight: bold;">金额：'. $model->pay_price .' '. $model->currency_code . '</span><br/>';
        $final_money = $model->pay_price;
        $data .= '<span style="color: #E06B26;font-weight: bold;">运费：' . $freight . ' ' . $model->currency_code . '</span><br/>';
        if($is_check_freight) {
            $final_money += $freight;
        }
        $data .= '<span style="color: #E06B26;font-weight: bold;">优惠：' . $discount . ' ' . $model->currency_code . '</span><br/>';
        if($is_check_discount) {
            $final_money -= $discount;
        }
        if ($isOnlyTotal) {
            return $final_money;
        }

        // 返回显示的金额的值
        if($money_list === true){
            return [
                'final_money'   => $final_money,
                'freight'       => $freight,
                'discount'      => $discount,
                'currency'      => $model->currency_code
            ];
        }

        $data .= '<span style="color: #E06B26;font-weight: bold;">优惠后：'. $final_money .' '. $model->currency . '</span><br/>';
        return $data;
    }
    /**
     * 获取请款单的金额信息
     * @param  object  $model        请款单对象 [PurchaseOrderPaySearch extends PurchaseOrderPay]
     * @param  boolean $isOnlyTotal [仅显示：优惠后的总额]
     * @param  integer $source      [判断是否是合同：1合同，2网采]
     * @param  boolean $money_list  是否返回所有金额的值
     * @return mixed
     */
    public static function getNewPrice($model, $isOnlyTotal = false, $source=SOURCE_NETWORK_ORDER , $money_list = false)
    {
        $freight = 0;
        $discount = 0;
        /**@ Table pur_order_pay_demand_map 中的 freight,discount  以字段 requisition_number（请款单编号关联） **/
        $orderPayDemandMap = $model->orderPayDemandMap;
        foreach ($orderPayDemandMap as $ordermap){
            $freight += $ordermap['freight'];
            $discount += $ordermap['discount'];
        }

        //如果该采购单位合同单，pay_price存的是计算优惠和运费后的请款金额，所以原请款额要 减运费+优惠
        if ($source==SOURCE_COMPACT_ORDER) $model->pay_price = $model->pay_price-$freight+$discount;

        $data  = '<span style="color: #E06B26;font-weight: bold;">金额：'. $model->pay_price .' '. $model->currency . '</span><br/>';
        $final_money = $model->pay_price;
        $data .= '<span style="color: #E06B26;font-weight: bold;">运费：' . $freight . ' ' . $model->currency . '</span><br/>';

        $final_money += $freight;

        $data .= '<span style="color: #E06B26;font-weight: bold;">优惠：' . $discount . ' ' . $model->currency . '</span><br/>';

        $final_money -= $discount;

        if ($isOnlyTotal) {
            return $final_money;
        }

        if($money_list === true){// 返回显示的金额的值
            return [
                'final_money'   => $final_money,
                'freight'       => $freight,
                'discount'      => $discount,
                'currency'      => $model->currency
            ];
        }

        $data .= '<span style="color: #E06B26;font-weight: bold;">优惠后：'. $final_money .' '. $model->currency . '</span><br/>';
        return $data;
    }

    /**
     * 获取产品线
     */
    public function get_product_line_list()
    {
        $prod_line = $this->purchase_db
            ->from('product_line')
            ->where(["linelist_is_new" => 1])
            ->get()
            ->result_array();
        $prod_line_list = [];
        if($prod_line && count($prod_line) > 0){
            foreach ($prod_line as $val){
                $prod_line_list[(string)$val['product_line_id']] = $val['linelist_cn_name'];
            }
        }
        return $prod_line_list;
    }

    /**
     * 获取导出组装数据
     * @author harvin 2019-5-10
     * @param array $data
     * @param type $type
     * @return array
     */
    public function get_assembly_data(array $data){
        $data_pay=[];
        //获取采购单集合
        $purchase_number= array_column($data, 'pur_number');
        //转化采购单号
        $compact_items=$this->purchase_db->select('purchase_number,compact_number')
            ->where_in('compact_number',$purchase_number)
            ->get('purchase_compact_items')
            ->result_array();

        $statement_items=$this->purchase_db->select('purchase_number,statement_number')
            ->where_in('statement_number',$purchase_number)
            ->get('purchase_statement_items')
            ->result_array();

        //解决 一个PO存在网采与合同单的情况
        if(!empty($compact_items) or !empty($statement_items)){
            $compact_itemList = is_array($compact_items)?array_column($compact_items, 'compact_number'):[];
            $statement_itemList = is_array($statement_items)?array_column($statement_items, 'statement_number'):[];
            $purchase_numberList1 = is_array($compact_items)?array_column($compact_items, 'purchase_number'):[];
            $purchase_numberList2 = is_array($statement_items)?array_column($statement_items, 'purchase_number'):[];
            $purchase_number = array_merge($purchase_numberList1,$purchase_numberList2,$purchase_number);//合并数组
            $purchase_number = array_diff($purchase_number,$compact_itemList,$statement_itemList);//去除合同单号
        }

        $params['purchase_number']= implode(' ', $purchase_number);

        $this->load->model('purchase/purchase_order_list_model');
        $data_list= $this->purchase_order_list_model->new_get_list($params, 0, 1000, 1,FALSE);

        if(empty($data_list['value'])){
            return [];
        }
        //获取仓库次品数量
        $order_number_list= is_array($data_list['value'])?array_column($data_list['value'], 'purchase_number'):[];
        //去除相同元素
        $order_number_list= array_filter($order_number_list);
        $defective_num_list= $this->get_defective_num($order_number_list);
        $temp=[];
        foreach ($data_list['value'] as $key => $val) {
            $temp['compact_number']=$val['compact_number'];
            foreach ($data as $vv) {
                $requisition_number_arr= explode(',', $val['requisition_number']);
                if(in_array($vv['requisition_number'], $requisition_number_arr)){
                    $requisition_number=$vv['requisition_number'];
                    $product_money=$vv['product_money'];
                    $freight=$vv['freight'];
                    $discount=$vv['discount'];
                    $process_cost=$vv['process_cost'];
                    $pay_price=$vv['pay_price'];
                    $payment_notice=$vv['payment_notice'];
                    $payment_platform=$vv['payment_platform'];
                    $application_time=$vv['application_time'];
                    $applicant  =$vv['applicant'];
                    $payer_name  =$vv['payer_name'];
                    $pur_tran_num =$vv['pur_tran_num'];
                    $trans_orderid =$vv['trans_orderid'];
                    $check_status = $vv['check_status'];
                    $is_statement_pay = $vv['is_statement_pay'];
                    break;
                }
            }
            $temp['requisition_number']= isset($requisition_number)?$requisition_number:'';
            $temp['purchase_number']=$val['purchase_number'];
            $temp['product_line_id']= $this->get_product_line_id($val['purchase_number'], $val['sku']);
            $temp['purchase_acccount']=$val['purchase_acccount'];
            $temp['pai_number']=$val['pai_number'];
            $temp['warehouse_code']=$val['warehouse_code'];
            $temp['buyer_name']=$val['buyer_name'];
            $temp['create_time']= isset($application_time)?$application_time:'';
            $temp['pay_time']=$val['pay_time'];
            $temp['sku']=$val['sku'];
            $temp['product_name']=$val['product_name'];
            $temp['purchase_unit_price']=$val['purchase_unit_price'];
            $temp['purchase_amount']=$val['confirm_amount'];
            $temp['arrival_qty']=$val['arrival_qty'];
            $temp['instock_qty']=$val['instock_qty'];
            $temp['loss_amount']=$val['loss_amount'];
            $temp['loss_status']=$val['loss_status'];
            $actual_amount = $val['instock_qty']-$val['loss_amount'];
            if($actual_amount<0) $actual_amount=0;
            $temp['actual_amount']= $actual_amount;
            $temp['defective_num']= isset($defective_num_list[$val['purchase_number']."_".$val['sku']])?$defective_num_list[$val['purchase_number']."_".$val['sku']]:0;
            $temp['product_money']= isset($product_money)?$product_money:0;
            $temp['freight']= isset($freight)?$freight:0;
            $temp['discount']= isset($discount)?$discount:0;
            $temp['process_cost']= isset($process_cost)?$process_cost:0;
            $temp['pay_price']= isset($pay_price)?$pay_price:0;
            $temp['supplier_code']=$val['supplier_code'];
            $temp['supplier_name']=$val['supplier_name'];
            $temp['payment_notice']= isset($payment_notice)?$payment_notice:'';

            $temp['pay_status']= getPayStatus($val['pay_status']);
            $temp['purchase_order_status_name']=$val['purchase_order_status_name'];
            $temp['pay_type']=$val['pay_type'];
            $temp['payment_platform']= isset($payment_platform)?$payment_platform:'';
            $temp['account_type']=$val['account_type'];
            $temp['statement_number']=$val['statement_number'];
            $temp['is_drawback']=$val['is_drawback'];
            $temp['is_ali_order']=$val['is_ali_order'];
            $temp['is_statement_pay'] = isset($is_statement_pay)?$is_statement_pay:'';
            $temp['applicant']=isset($applicant)?$applicant:'';
            $temp['payer_name']=isset($payer_name)?$payer_name:'';
            $temp['pur_tran_num']=isset($pur_tran_num)?$pur_tran_num:'';
            $temp['trans_orderid']=isset($trans_orderid)?$trans_orderid:'';
            $temp['check_status']=isset($check_status)?$check_status:'';
            $data_pay[]=$temp;
            unset($temp);
        }
        return $data_pay;
    }


    public function get_assembly_data_new(array $data, $type=2, $base_data=null){
        $data_pay=[];
        $params['purchase_number']= implode(' ', $data);

        $this->load->model('purchase/purchase_order_list_model');
        $data_list= $this->purchase_order_list_model->new_get_list($params, 0, 2000, 1,FALSE);
//        return $data_list;
        if(empty($data_list['value'])){
            return [];
        }
        //获取仓库次品数量
        $order_number_list= is_array($data_list['value'])?array_column($data_list['value'], 'purchase_number'):[];
        //去除相同元素
        $order_number_list= array_filter($order_number_list);
        $defective_num_list= $this->get_defective_num($order_number_list);
        $temp=[];
        foreach ($data_list['value'] as $key => $val) {
            $temp['compact_number']=$val['compact_number'];
            foreach ($base_data as $vv) {
                $requisition_number_arr= explode(',', $val['requisition_number']);
                if(in_array($vv['requisition_number'], $requisition_number_arr)){
                    $requisition_number=$vv['requisition_number'];
                    $product_money=$vv['product_money'];
                    $freight=$vv['freight'];
                    $discount=$vv['discount'];
                    $process_cost=$vv['process_cost'];
                    $pay_price=$vv['pay_price'];
                    $payment_notice=$vv['payment_notice'];
                    $payment_platform=$vv['payment_platform'];
                    $application_time=$vv['application_time'];
                    $applicant  =$vv['applicant'];
                    $payer_name  =$vv['payer_name'];
                    $pur_tran_num =$vv['pur_tran_num'];
                    $trans_orderid =$vv['trans_orderid'];
                    $check_status = $vv['check_status'];
                    $is_statement_pay = getIsStatementPay($vv['source_subject']);
                    break;
                }
            }
            $temp['requisition_number']= isset($requisition_number)?$requisition_number:'';
            $temp['purchase_number']=$val['purchase_number'];
            $temp['product_line_id']= $this->get_product_line_id($val['purchase_number'], $val['sku']);
            $temp['purchase_acccount']=$val['purchase_acccount'];
            $temp['pai_number']=$val['pai_number'];
            $temp['warehouse_code']=$val['warehouse_code'];
            $temp['buyer_name']=$val['buyer_name'];
            $temp['create_time']= isset($application_time)?$application_time:'';
            $temp['pay_time']=$val['pay_time'];
            $temp['sku']=$val['sku'];
            $temp['product_name']=$val['product_name'];
            $temp['purchase_unit_price']=$val['purchase_unit_price'];
            $temp['purchase_amount']=$val['confirm_amount'];
            $temp['arrival_qty']=$val['arrival_qty'];
            $temp['instock_qty']=$val['instock_qty'];
            $temp['loss_amount']=$val['loss_amount'];
            $temp['loss_status']=$val['loss_status'];
            $actual_amount = $val['instock_qty']-$val['loss_amount'];
            if($actual_amount<0) $actual_amount=0;
            $temp['actual_amount']= $actual_amount;
            $temp['defective_num']= isset($defective_num_list[$val['purchase_number']."_".$val['sku']])?$defective_num_list[$val['purchase_number']."_".$val['sku']]:0;
            $temp['product_money']= isset($product_money)?$product_money:0;
            $temp['freight']= isset($freight)?$freight:0;
            $temp['discount']= isset($discount)?$discount:0;
            $temp['process_cost']= isset($process_cost)?$process_cost:0;
            $temp['pay_price']= isset($pay_price)?$pay_price:0;
            $temp['supplier_code']=$val['supplier_code'];
            $temp['supplier_name']=$val['supplier_name'];
            $temp['payment_notice']= isset($payment_notice)?$payment_notice:'';

            $temp['pay_status']= getPayStatus($val['pay_status']);
            $temp['purchase_order_status_name']=$val['purchase_order_status_name'];
            $temp['pay_type']=$val['pay_type'];
            $temp['payment_platform']= isset($payment_platform)?$payment_platform:'';
            $temp['account_type']=$val['account_type'];
            $temp['statement_number']=$val['statement_number'];
            $temp['is_drawback']=$val['is_drawback'];
            $temp['is_ali_order']=$val['is_ali_order'];
            $temp['is_statement_pay'] = isset($is_statement_pay)?$is_statement_pay:'';
            $temp['applicant']=isset($applicant)?$applicant:'';
            $temp['payer_name']=isset($payer_name)?$payer_name:'';
            $temp['pur_tran_num']=isset($pur_tran_num)?$pur_tran_num:'';
            $temp['trans_orderid']=isset($trans_orderid)?$trans_orderid:'';
            $temp['check_status']=isset($check_status)?$check_status:'';
            $data_pay[]=$temp;
            unset($temp);
        }
        return $data_pay;
    }

    /**
     * 获取次品数量集合
     * @author harvin
     * @data2019-5-10
     * @param array $order_number_list
     * @return array
     */
    public function  get_defective_num(array $order_number_list){
            if(empty($order_number_list)){
                return [];
            }
          $warehouse_results=  $this->purchase_db
                    ->select('sku,purchase_number,defective_num')
                   ->where_in('purchase_number',$order_number_list)
                    ->get('warehouse_results')
                    ->result_array();

           if(empty($warehouse_results)) {
               return [];
           }
           foreach ($warehouse_results as $val) {
               $data[$val['purchase_number'].'_'.$val['sku']][]=$val['defective_num']; 
           } 
        foreach ($data as $key => $value) {
            $data[$key]= array_sum($value);
        }
        
        return  $data;
        
    }


    /** 主体账号模糊查询
     * @param $params
     * @return array
     */
    public function get_account_list($params){
        if(empty($params)) return [];

        $result = [];
        if(isset($params['purchase_account'])){// 根据账号关键字 模糊查询 账号列表
            $purchase_account = trim($params['purchase_account']);
            $result = $this->purchase_db
                ->select('distinct(`account`) as purchase_account')
                ->like('account',$purchase_account,'both')
                ->get('alibaba_account')
                ->result_array();
        }
        if(isset($params['account_type']) and $params['account_type'] == '1'){// 根据账号类型查找  1.查找 淘宝账号
            $result = $this->purchase_db
                ->select('distinct(`account`) as purchase_account')
                ->where_in('account',['琦LL113','琦LL114','琦LL115','琦LL217','琦LL213','琦LL214','琦LL217'])
                ->get('alibaba_account')
                ->result_array();
        }
        return $result;

    }

    /** 付款申请单添加备注 wangliang
     * @param $params
     * @return array
     */
    public function remark_save($params){
        $requisition_number = trim($params['requisition_number']);
        $remark = trim($params['remark']);
        try{
            $info = $this->_db->where('requisition_number',$requisition_number)->get('purchase_order_pay')->row_array();
            if(!$info) throw new Exception('记录不存在');

            $insert_data = [
                'requisition_number'        => $requisition_number,
                'purchase_number'           => $info['pur_number'],
                'remark'                    => $remark,
                'add_user_id'               => getActiveUserId(),
                'add_user_name'             => getActiveUserName(),
                'add_time'                  => date('Y-m-d H:i:s')
            ];
            $result = $this->purchase_db->insert('purchase_order_pay_remark',$insert_data);
            if(!$result) throw new Exception('备注添加失败');

            $return = ['bool'=>1,'msg'=>'操作成功'];
        }catch (Exception $e){
            $return = ['bool'=>0,'msg'=>$e->getMessage()];
        }
        return $return;
    }


    /** 根据申请单号获取申请单备注 wangliang
     * @param $params
     * @return array
     */
    public function get_remark_list($params){
        $requisition_number = trim($params['requisition_number']);
        $return = $this->purchase_db->where('requisition_number',$requisition_number)->get('purchase_order_pay_remark')->result_array();
        return $return;
    }
    /**
     * 获取请款单日志信息
     * @author harvin
     * @date 2019-06-28
     * @param int $id
     * @return array
     */
    public function  remark_log_list($id){
        $log= $this->purchase_db
                 ->select("*")
                 ->where('pay_id',$id)
                 ->order_by('create_time','desc')
                 ->get('purchase_order_pay_log')
                 ->result_array();   
        return $log;
    }
    /**
     * 保存日志
     * @author harvin
     * @date 2019-06-28
     * @param int $id
     * @param string $remark
     * @return boolean
     * @throws Exception
     */
    public function add_remark_log( $id, $remark){
        $data=[
            'pay_id'=>$id,
            'remark'=>$remark,
            'create_user'=> getActiveUserName(),
            'create_time'=>date('Y-m-d H:i:s'),
        ];
        $inset=$this->purchase_db->insert('purchase_order_pay_log',$data);
        if($inset){
            unset($data);
            return TRUE;
        }else{
            throw new Exception('插入失败');
        }
    }

    /**
     * 审核类型
     */
    public function approver_type(){
        $user =$this->purchase_user_model;
        $approver_list =[
            ['id'=>PAY_WAITING_FINANCE_REVIEW,'name'=>'财务审核','list'=>$user->get_finance_all_list()],
            ['id'=>PAY_UFXFUIOU_SUPERVISOR,'name'=>'财务主管审核','list'=>$user->get_finance_Supervisor_list()],
            ['id'=>PAY_UFXFUIOU_MANAGER,'name'=>'财务经理审核','list'=>$user->get_finance_Manager_list()],
            ['id'=>PAY_UFXFUIOU_SUPPLY,'name'=>'财务总监审核','list'=>$user->get_finance_Officer_list()],
            ['id'=>PAY_GENERAL_MANAGER,'name'=>'总经办审核','list'=>$user->get_finance_General_list()],
        ];
        return $approver_list;
    }

    /**
     * 根据ID获取付款单的应付金额
     */
    public function get_order_pay_price($all_pay_id){
        $all_unique =[];
         foreach ($all_pay_id as $value){
             $all_unique[$value['id']]=$value['pay_price'];
         }
        $price = array_sum(array_values($all_unique));
      return  $price;
    }

    /**
     * totoro
     * @param $params
     * @param $type
     * @return array
     */
    public function get_pay_list_sum($params, $type) {
        $role_name      = get_user_role();//当前登录角色
        $taobao_account = $this->get_account_list(['account_type' => 1]);// 获取 淘宝账号
        $taobao_account = array_column($taobao_account,'purchase_account');
        $query = $this->purchase_db;
        $bulider = clone $query;
        // distinct 去除重复数据
        $query->select('p.id');
        $query->from('purchase_order_pay_detail as pp');
        $query->join('purchase_order_pay as p', 'pp.requisition_number=p.requisition_number', 'left');
        $query->join('pur_supplier as sp', 'sp.supplier_code=p.supplier_code','left');
        $query->join('purchase_order_pay_type as ppy', 'ppy.purchase_number=p.pur_number','left');

        //如果筛选条件是单独请款运费的 那么$type修改为合同单   因为合同单单独请款运费时采购来源变成网采
        if(isset($params['pay_category']) && $params['pay_category']==4 && $type==SOURCE_NETWORK_ORDER){
            $type  = 1;
            $query->where('p.source', 2);
        }else{
            $query->where('p.source', $type);
        }

        $query->join('purchase_order as po', 'po.purchase_number=pp.purchase_number', 'left');
        if($type==SOURCE_NETWORK_ORDER) {
        }

        if($type==SOURCE_COMPACT_ORDER){

            // 根据 交易流水号、商户订单号 查询
            if (isset($params['pur_tran_num']) && trim($params['pur_tran_num'])) {
                $pur_tran_num = explode(' ', trim($params['pur_tran_num']));
                $query->where_in('p.pur_tran_num', array_filter($pur_tran_num));
            }
            if (isset($params['trans_orderid']) && trim($params['trans_orderid'])) {
                $trans_orderid = explode(' ', trim($params['trans_orderid']));
                $query->where_in('p.trans_orderid', array_filter($trans_orderid));
            }
        }
        $data_role= getRolefinance();
        $res_arr=array_intersect($role_name, $data_role);
        if($res_arr){ //产品开发只能查看样品样品请款
            $query->like('p.requisition_number','YPP','after');
        }
        if (isset($params['supplier_code']) && $params['supplier_code']) { //供应商
            $query->where('p.supplier_code', $params['supplier_code']);
        }

        // 应付款-网采：增加拍单号的筛选项，可以多选：多个拍单号之间以空格连接
        if (isset($params['pai_number']) && trim($params['pai_number']) && $type==SOURCE_NETWORK_ORDER) {
            $pai_number = explode(' ', trim($params['pai_number']));
            $query->where_in('p.pai_number', array_filter($pai_number));
        }

        if (isset($params['applicant']) && is_numeric($params['applicant'])) {//申请人 前端选择"空"的时候 applicant =0
            $query->where('p.applicant', $params['applicant']);
        }
        if (isset($params['pay_status']) && $params['pay_status']) {//付款状态
            $query->where('p.pay_status', $params['pay_status']);
        }

        if (isset($params['purchase_type_id']) && $params['purchase_type_id'] != "" && $type==SOURCE_NETWORK_ORDER) {//业务线(网采单)
            if(is_array($params['purchase_type_id'])){
                $query->where_in('po.purchase_type_id', $params['purchase_type_id']);
            }else{
                $query->where('po.purchase_type_id', $params['purchase_type_id']);
            }
        }

        if (isset($params['purchase_type_id']) && $params['purchase_type_id'] != "" && $type==SOURCE_COMPACT_ORDER) {//业务线(合同单)
            if(is_array($params['purchase_type_id'])){
                $query->where_in('po.purchase_type_id', $params['purchase_type_id']);
            }else{
                $query->where('po.purchase_type_id', $params['purchase_type_id']);
            }
        }


        if (isset($params['pay_type']) && $params['pay_type'] != '') { //支付方式
            $query->where('p.pay_type', $params['pay_type']);
        }
        if (isset($params['pay_category']) && $params['pay_category']) {//请款类型
            $query->where('p.pay_category', $params['pay_category']);
        }
        if (isset($params['settlement_method']) && $params['settlement_method']) {//结算方式
            $query->where('p.settlement_method', $params['settlement_method']);
        }
        if (isset($params['is_cross_border']) && $params['is_cross_border'] != '') { //跨境宝供应商
            $query->where('sp.is_cross_border', $params['is_cross_border']);
        }

        // 来源主体（1合同 2网采 3对账单）
        if(isset($params['is_statement_pay']) and $params['is_statement_pay']!=""){
            if($params['is_statement_pay'] == '3'){
                $query->where('p.source_subject=3');
            }else{
                $query->where('p.source_subject<>3');
            }
        }
        //审批人筛选
        if (isset($params['approver_type']) && isset($params['approver_user']) && $params['approver_type']!='' && $params['approver_user']!='') { //审批人

            if($params['approver_type']==PAY_WAITING_FINANCE_REVIEW){//财务审核

                $query->where('p.approver', $params['approver_user']);

            }elseif ($params['approver_type']==PAY_UFXFUIOU_SUPERVISOR){  //财务主管审核

                $query->where('p.financial_supervisor_id', $params['approver_user']);

            }elseif ($params['approver_type']==PAY_UFXFUIOU_MANAGER){     //财务经理审核

                $query->where('p.financial_manager_id', $params['approver_user']);

            }elseif ($params['approver_type']==PAY_UFXFUIOU_SUPPLY){      //财务总监审核

                $query->where('p.financial_officer_id', $params['approver_user']);

            }elseif ($params['approver_type']==PAY_GENERAL_MANAGER){      //总经办审核
                $query->where('p.general_manager_id', $params['approver_user']);
            }
        }
        //是否退税
        if(isset($params['is_drawback']) && $params['is_drawback']!=''){
            $query->where('po.is_drawback', $params['is_drawback']);
        }
        if(isset($params['statement_number']) && !empty($params['statement_number'])){//对账单号
            $statement_numbers = query_string_to_array($params['statement_number']);
            $statement_numbers= array_unique($statement_numbers);
            $query->where_in('p.pur_number',$statement_numbers)->where('p.source_subject',SOURCE_SUBJECT_STATEMENT_ORDER);
            unset($compact_number_list);
        }

        if(isset($params['pay_user_id']) && $params['pay_user_id']!=''){
            $pay_user_id= trim($params['pay_user_id']);
            $query->where('p.payer_id', $pay_user_id);
        }

        //账号绑定付款人
        if(isset($params['account_pay']) && $params['account_pay']!=''){
            $pay_user_id= trim($params['account_pay']);
            $account_sub=$query->query("SELECT account FROM pur_alibaba_sub WHERE status=1 and pay_user_id='{$pay_user_id}'")->result_array();
            if(!empty($account_sub)){
                $user_id= is_array($account_sub)? array_column($account_sub, 'account'):0;
                $query->where_in('p.purchase_account',$user_id);
            }else{
                $query->where('p.purchase_account',1);
            }
        }

        if(isset($params['pur_number']) && $params['pur_number']){ // 采购单号或合同号批量搜索
            $pur_numbers = explode (' ', trim($params['pur_number']));
            $requisition_number = $bulider->select('requisition_number')
                ->from('purchase_order_pay_detail')
                ->where_in('purchase_number',$pur_numbers)
                ->get()
                ->result_array();
            $query->group_start();
            $query->where_in('p.pur_number',  array_filter($pur_numbers));
            if(is_array($requisition_number) && !empty($requisition_number)){
                $requisition_number_arr = array_unique(array_column($requisition_number,'requisition_number'));
                $query->or_where_in('p.requisition_number',$requisition_number_arr);
            }
            $query->group_end();
        }
        if(isset($params['requisition_number']) && $params['requisition_number']){ //请款单号
            $query->where('p.requisition_number', $params['requisition_number']);
        }
        if (isset($params['create_time_start']) and $params['create_time_start'])// 创建时间-开始
            $query->where('p.create_time>=', $params['create_time_start']);
        if (isset($params['create_time_end']) and $params['create_time_end'])// 创建时间-结束
            $query->where('p.create_time<=', $params['create_time_end']);
        if(isset($params['id']) && $params['id']){
            $ids= explode(',', $params['id']);
            $query->where_in('p.id', $ids);
        }

        if(isset($params['purchase_account']) && trim($params['purchase_account'])){//账号查询
            $query->like('p.purchase_account', trim($params['purchase_account']),'after');
        }
        if(isset($params['purchase_account_type']) and trim($params['purchase_account_type'])){
            if(trim($params['purchase_account_type']) == 1){// 账号类型为 1.淘宝
                $query->where_in('p.purchase_account',$taobao_account);
            }else{
                $query->where_not_in('p.purchase_account',$taobao_account);
            }
        }
        if(isset($params['is_ali_order']) and $params['is_ali_order'] != ''){
            $query->where('po.is_ali_order',intval($params['is_ali_order']));
        }
        if (isset($params['purchase_order_status']) and $params['purchase_order_status']){
            if(!is_array($params['purchase_order_status'])) {
                $params['purchase_order_status'] = explode(",",$params['purchase_order_status']);
            }
            $query->where_in("po.purchase_order_status",$params['purchase_order_status']);
        }

        if ($type == SOURCE_COMPACT_ORDER) {//合同单
            $query->where_in('p.pay_status', [PAY_WAITING_FINANCE_REVIEW,PAY_SOA_REJECT, PAY_FINANCE_REJECT, PAY_WAITING_FINANCE_PAID, PART_PAID, PAY_PAID,PAY_UFXFUIOU_REVIEW,PAY_UFXFUIOU_SUPERVISOR,PAY_UFXFUIOU_MANAGER,PAY_UFXFUIOU_SUPPLY,PAY_GENERAL_MANAGER,PAY_REJECT_SUPERVISOR,PAY_REJECT_MANAGER,PAY_REJECT_SUPPLY,PAY_GENERAL_MANAGER_REJECT,PAY_UFXFUIOU_BAOFOPAY]);
        }

        //应付款时间
        if(isset($params['need_pay_time_start']) and $params['need_pay_time_start'] and isset($params['need_pay_time_end']) and $params['need_pay_time_end']){//应付款时间
            $start_time = date('Y-m-d 00:00:00',strtotime($params['need_pay_time_start']));
            $end_time = date('Y-m-d 00:00:00',strtotime($params['need_pay_time_end']));
            $query->where("ppy.accout_period_time between '{$start_time}' and '{$end_time}' ");// 应付款时间
        }

        //实际付款时间
        if(isset($params['pay_time_start']) and $params['pay_time_start'] and isset($params['pay_time_end']) and $params['pay_time_end']){//应付款时间
            $start_time = date('Y-m-d 00:00:00',strtotime($params['pay_time_start']));
            $end_time = date('Y-m-d 00:00:00',strtotime($params['pay_time_end']));
            $query->where("p.payer_time between '{$start_time}' and '{$end_time}' ");// 应付款时间
        }
        if(isset($params['pay_notice']) and $params['pay_notice']){//付款提醒状态
            if ($params['pay_notice'] == TAP_DATE_WITHOUT_BALANCE){//查询额度不足的供应商
                $supplier_settlement_online = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;
                $supplier_res = $query->query("SELECT supplier_code FROM pur_supplier WHERE supplier_settlement='{$supplier_settlement_online}' and pur_supplier.quota <> 0 and pur_supplier.surplus_quota <= 0")->result_array();
                $supplier_res = !empty($supplier_res)?array_column($supplier_res,'supplier_code'):[ORDER_CANCEL_ORSTATUS];
                $supplier_res= array_unique($supplier_res);
                $query->where_in("p.supplier_code",$supplier_res);

            }
            if ($params['pay_notice'] != TAP_DATE_WITHOUT_BALANCE){//查询额度足够的供应商
                $query->where("ppy.accout_period_time != '0000-00-00 00:00:00'");
                if ($params['pay_notice'] == TAP_DATE_OVER_TIME){//已超期
                    $today = date('Y-m-d H:i:s');
                    $query->where("ppy.accout_period_time<'{$today}'");
                }
                if ($params['pay_notice'] == TAP_DATE_COMING_SOON){//即将到期
                    $today = date('Y-m-d H:i:s');
                    $five_days_later = date('Y-m-d H:i:s',strtotime('+ 5 days'));
                    $query->where("ppy.accout_period_time >= '{$today}'");
                    $query->where("ppy.accout_period_time < '{$five_days_later}'");
                }
                if ($params['pay_notice'] == TAP_DATE_CAN_WAIT){//可继续等待
                    $five_days_later = date('Y-m-d H:i:s',strtotime('+ 5 days'));
                    $query->where("ppy.accout_period_time >= '{$five_days_later}'");
                }
            }
        }
        if(isset($params['payment_platform'])&& $params['payment_platform']=='6'){
            $query->join('pur_supplier_payment_info spa','p.supplier_code = spa.supplier_code AND spa.is_del = 0','right');
            $query->where('spa.payment_platform',$params['payment_platform']);
            $query->where("p.pay_type",PURCHASE_PAY_TYPE_PRIVATE);

            unset($supplier_code_list);
        }
        //支付方式=线下境内，那么默认支付平台=网银
        if(isset($params['payment_platform'])&& $params['payment_platform']=='5'){
            $query->join('pur_supplier_payment_info spa','p.supplier_code = spa.supplier_code AND spa.is_del = 0','right');
            $query->where('spa.payment_platform',$params['payment_platform']);
            $query->where("p.pay_type",PURCHASE_PAY_TYPE_PUBLIC);
            unset($supplier_code_list);
        }

        //支付方式≠线下境内/线下境外，那么默认支付平台=其他
        if(isset($params['payment_platform'])&& $params['payment_platform']=='1'){
            $query->join('pur_supplier_payment_info spa','p.supplier_code = spa.supplier_code AND spa.is_del = 0','right');
            $query->where_not_in('spa.payment_platform',[5,6]);
            $query->where_not_in('p.pay_type',[PURCHASE_PAY_TYPE_PUBLIC,PURCHASE_PAY_TYPE_PRIVATE]);
            unset($supplier_code_list);
        }

        $count_qb = clone $query;
        unset($params);
        $query->limit(1)->group_by('p.id')->order_by('p.id','desc')->get()->result_array();
        //统计总数
        $count_row = $count_qb->select("count(DISTINCT p.id) as num")->get()->row_array();

        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        $return_data = [
            'paging_data'=>[
                'total'=>$total_count,
            ]
        ];
        return $return_data;
    }

    /**
     * 获取一级产品线
     */
    public function get_product_line_id($purchase_number, $sku=null)
    {
        $query = ['psm.purchase_number' => $purchase_number];
        if(!empty($sku))$query['ps.sku'] = $sku;
        $data = $this->purchase_db
            ->from('purchase_suggest as ps')
            ->join('purchase_suggest_map as psm', 'ps.demand_number=psm.demand_number', 'left')
            ->select('ps.product_line_name')
            ->where($query)
            ->get()
            ->row_array();
        if($data && isset($data['product_line_name']))return $data['product_line_name'];
        return '';
    }

    /**
     * totoro
     * @param $params
     * @param $type
     * @param $offsets
     * @param $limit
     * @param string $id
     * @param int $page
     * @return array
     */
    public function export_pay_list($params, $type, $offsets, $limit,$id='',$page=1) {

        $role_name      = get_user_role();//当前登录角色
        $taobao_account = $this->get_account_list(['account_type' => 1]);// 获取 淘宝账号
        $taobao_account = array_column($taobao_account,'purchase_account');
        $query = $this->purchase_db;
        $bulider = clone $query;
        // distinct 去除重复数据
        $query->select('p.id,'
            . 'p.pay_status,'
            . 'p.pur_number,'
            . 'p.requisition_number,'
            . 'p.pai_number,'
            . 'p.supplier_code,'
            . 'p.pay_type,'
            . 'p.pay_category,'
            . 'p.settlement_method,'
            . 'p.payment_platform,'
            . 'p.pay_price,'
            . 'p.product_money,'
            . 'p.freight,'
            . 'p.discount,'
            . 'p.process_cost,'
            . 'p.applicant,'
            . 'p.application_time,'
            . 'p.auditor,'
            . 'p.review_time,'
            . 'p.create_notice,'  //请款备注
            . 'p.review_notice,' //审核备注
            . 'p.processing_notice,' //审批备注
            . 'p.purchase_account,'
            . 'p.payment_notice,'   //付款备注
            . 'p.payer_name,sp.is_cross_border,'
            . 'p.payer_time,'
            . 'p.pur_tran_num,'
            . 'p.trans_orderid,'
            . 'p.voucher_address,'
            . 'ppy.accout_period_time as need_pay_time,'
            . 'sp.surplus_quota,'
            . 'sp.tap_date_str,p.payer_id,p.source,p.source_subject,'
            . 'po.is_drawback,'
            . 'po.purchase_number,'
            . 'po.is_ali_order,'
            . 'po.warehouse_code,'
            . 'po.buyer_name,'
            . 'po.account_type,'
            . 'po.purchase_order_status,'
            . 'po.purchase_type_id'
        );
        $query->from('purchase_order_pay_detail as pp');
        $query->join('purchase_order_pay as p', 'pp.requisition_number=p.requisition_number', 'left');
        $query->join('pur_supplier as sp', 'sp.supplier_code=p.supplier_code','left');
        $query->join('purchase_order_pay_type as ppy', 'ppy.purchase_number=p.pur_number','left');

        //如果筛选条件是单独请款运费的 那么$type修改为合同单   因为合同单单独请款运费时采购来源变成网采
        if(isset($params['pay_category']) && $params['pay_category']==4 && $type==SOURCE_NETWORK_ORDER){
            $type  = 1;
            $query->where('p.source', 2);
        }else{
            $query->where('p.source', $type);
        }

        $query->join('purchase_order as po', 'po.purchase_number=pp.purchase_number', 'inner');
        if($type==SOURCE_NETWORK_ORDER) {
        }
        $is_entry = false;
        if($type==SOURCE_COMPACT_ORDER){

            // 根据 交易流水号、商户订单号 查询
            if (isset($params['pur_tran_num']) && trim($params['pur_tran_num'])) {
                $pur_tran_num = explode(' ', trim($params['pur_tran_num']));
                $query->where_in('p.pur_tran_num', array_filter($pur_tran_num));
            }
            if (isset($params['trans_orderid']) && trim($params['trans_orderid'])) {
                $trans_orderid = explode(' ', trim($params['trans_orderid']));
                $query->where_in('p.trans_orderid', array_filter($trans_orderid));
            }
            $is_entry = true;
        }
        $data_role= getRolefinance();
        $res_arr=array_intersect($role_name, $data_role);
        if($res_arr){ //产品开发只能查看样品样品请款
            $query->like('p.requisition_number','YPP','after');
        }
        if (isset($params['supplier_code']) && $params['supplier_code']) { //供应商
            $query->where('p.supplier_code', $params['supplier_code']);
        }

        // 应付款-网采：增加拍单号的筛选项，可以多选：多个拍单号之间以空格连接
        if (isset($params['pai_number']) && trim($params['pai_number']) && $type==SOURCE_NETWORK_ORDER) {
            $pai_number = explode(' ', trim($params['pai_number']));
            $query->where_in('p.pai_number', array_filter($pai_number));
        }

        if (isset($params['applicant']) && is_numeric($params['applicant'])) {//申请人 前端选择"空"的时候 applicant =0
            $query->where('p.applicant', $params['applicant']);
        }
        if (isset($params['pay_status']) && $params['pay_status']) {//付款状态
            $query->where('p.pay_status', $params['pay_status']);
        }

        if (isset($params['purchase_type_id']) && $params['purchase_type_id'] != "" && $type==SOURCE_NETWORK_ORDER) {//业务线(网采单)
            if(is_array($params['purchase_type_id'])){
                $query->where_in('po.purchase_type_id', $params['purchase_type_id']);
            }else{
                $query->where('po.purchase_type_id', $params['purchase_type_id']);
            }
        }

        if (isset($params['purchase_type_id']) && $params['purchase_type_id'] != "" && $type==SOURCE_COMPACT_ORDER) {//业务线(合同单)
            if(is_array($params['purchase_type_id'])){
                $query->where_in('po.purchase_type_id', $params['purchase_type_id']);
            }else{
                $query->where('po.purchase_type_id', $params['purchase_type_id']);
            }
        }


        if (isset($params['pay_type']) && $params['pay_type'] != '') { //支付方式
            $query->where('p.pay_type', $params['pay_type']);
        }
        if (isset($params['pay_category']) && $params['pay_category']) {//请款类型
            $query->where('p.pay_category', $params['pay_category']);
        }
        if (isset($params['settlement_method']) && $params['settlement_method']) {//结算方式
            $query->where('p.settlement_method', $params['settlement_method']);
        }
        if (isset($params['is_cross_border']) && $params['is_cross_border'] != '') { //跨境宝供应商
            $query->where('sp.is_cross_border', $params['is_cross_border']);
        }

        // 来源主体（1合同 2网采 3对账单）
        if(isset($params['is_statement_pay']) and $params['is_statement_pay']!=""){
            if($params['is_statement_pay'] == '3'){
                $query->where('p.source_subject=3');
            }else{
                $query->where('p.source_subject<>3');
            }
        }
        //审批人筛选
        if (isset($params['approver_type']) && isset($params['approver_user']) && $params['approver_type']!='' && $params['approver_user']!='') { //审批人

            if($params['approver_type']==PAY_WAITING_FINANCE_REVIEW){//财务审核

                $query->where('p.approver', $params['approver_user']);

            }elseif ($params['approver_type']==PAY_UFXFUIOU_SUPERVISOR){  //财务主管审核

                $query->where('p.financial_supervisor_id', $params['approver_user']);

            }elseif ($params['approver_type']==PAY_UFXFUIOU_MANAGER){     //财务经理审核

                $query->where('p.financial_manager_id', $params['approver_user']);

            }elseif ($params['approver_type']==PAY_UFXFUIOU_SUPPLY){      //财务总监审核

                $query->where('p.financial_officer_id', $params['approver_user']);

            }elseif ($params['approver_type']==PAY_GENERAL_MANAGER){      //总经办审核
                $query->where('p.general_manager_id', $params['approver_user']);
            }
        }
        //是否退税
        if(isset($params['is_drawback']) && $params['is_drawback']!=''){
            $query->where('po.is_drawback', $params['is_drawback']);
        }


        if(isset($params['statement_number']) && !empty($params['statement_number'])){//对账单号
            $statement_numbers = query_string_to_array($params['statement_number']);
            $statement_numbers= array_unique($statement_numbers);
            $query->where_in('p.pur_number',$statement_numbers)->where('p.source_subject',SOURCE_SUBJECT_STATEMENT_ORDER);
            unset($compact_number_list);
        }

        if(isset($params['pay_user_id']) && $params['pay_user_id']!=''){
            $pay_user_id= trim($params['pay_user_id']);
            $query->where('p.payer_id', $pay_user_id);
        }

        //账号绑定付款人
        if(isset($params['account_pay']) && $params['account_pay']!=''){
            $pay_user_id= trim($params['account_pay']);
            $account_sub=$query->query("SELECT account,user_id FROM pur_alibaba_sub WHERE status=1 and pay_user_id='{$pay_user_id}'")->result_array();
            if(!empty($account_sub)){
                $account= is_array($account_sub)? array_column($account_sub, 'account'):'';
                $query->where_in('p.purchase_account',$account);
            }else{
                $query->where('p.purchase_account',1);
            }


        }

        if(isset($params['pur_number']) && $params['pur_number']){ // 采购单号或合同号批量搜索
            $pur_numbers = explode (' ', trim($params['pur_number']));
            $requisition_number = $bulider->select('requisition_number')
                ->from('purchase_order_pay_detail')
                ->where_in('purchase_number',$pur_numbers)
                ->get()
                ->result_array();
            $query->group_start();
            $query->where_in('p.pur_number',  array_filter($pur_numbers));
            if(is_array($requisition_number) && !empty($requisition_number)){
                $requisition_number_arr = array_unique(array_column($requisition_number,'requisition_number'));
                $query->or_where_in('p.requisition_number',$requisition_number_arr);
            }
            $query->group_end();
        }
        if(isset($params['requisition_number']) && $params['requisition_number']){ //请款单号
            $query->where('p.requisition_number', $params['requisition_number']);
        }
        if (isset($params['create_time_start']) and $params['create_time_start'])// 创建时间-开始
            $query->where('p.create_time>=', $params['create_time_start']);
        if (isset($params['create_time_end']) and $params['create_time_end'])// 创建时间-结束
            $query->where('p.create_time<=', $params['create_time_end']);
        if(isset($params['id']) && $params['id']){
            $ids= explode(',', $params['id']);
            $query->where_in('p.id', $ids);
        }

        if(isset($params['purchase_account']) && trim($params['purchase_account'])){//账号查询
            $query->like('p.purchase_account', trim($params['purchase_account']),'after');
        }
        if(isset($params['purchase_account_type']) and trim($params['purchase_account_type'])){
            if(trim($params['purchase_account_type']) == 1){// 账号类型为 1.淘宝
                $query->where_in('p.purchase_account',$taobao_account);
            }else{
                $query->where_not_in('p.purchase_account',$taobao_account);
            }
        }
        if(isset($params['is_ali_order']) and $params['is_ali_order'] != ''){
            $query->where('po.is_ali_order',intval($params['is_ali_order']));
        }
        if (isset($params['purchase_order_status']) and $params['purchase_order_status']){
            if(!is_array($params['purchase_order_status'])) {
                $params['purchase_order_status'] = explode(",",$params['purchase_order_status']);
            }
            $query->where_in("po.purchase_order_status",$params['purchase_order_status']);
        }

        if ($type == SOURCE_COMPACT_ORDER) {//合同单
            $query->where_in('p.pay_status', [PAY_WAITING_FINANCE_REVIEW,PAY_SOA_REJECT, PAY_FINANCE_REJECT, PAY_WAITING_FINANCE_PAID, PART_PAID, PAY_PAID,PAY_UFXFUIOU_REVIEW,PAY_UFXFUIOU_SUPERVISOR,PAY_UFXFUIOU_MANAGER,PAY_UFXFUIOU_SUPPLY,PAY_GENERAL_MANAGER,PAY_REJECT_SUPERVISOR,PAY_REJECT_MANAGER,PAY_REJECT_SUPPLY,PAY_GENERAL_MANAGER_REJECT,PAY_UFXFUIOU_BAOFOPAY,PAY_LAKALA]);
        }

        //应付款时间
        if(isset($params['need_pay_time_start']) and $params['need_pay_time_start'] and isset($params['need_pay_time_end']) and $params['need_pay_time_end']){//应付款时间
            $start_time = date('Y-m-d 00:00:00',strtotime($params['need_pay_time_start']));
            $end_time = date('Y-m-d 00:00:00',strtotime($params['need_pay_time_end']));
            $query->where("ppy.accout_period_time between '{$start_time}' and '{$end_time}' ");// 应付款时间
        }

        //实际付款时间
        if(isset($params['pay_time_start']) and $params['pay_time_start'] and isset($params['pay_time_end']) and $params['pay_time_end']){//应付款时间
            $start_time = date('Y-m-d 00:00:00',strtotime($params['pay_time_start']));
            $end_time = date('Y-m-d 00:00:00',strtotime($params['pay_time_end']));
            $query->where("p.payer_time between '{$start_time}' and '{$end_time}' ");// 应付款时间
        }

        if(isset($params['pay_notice']) and $params['pay_notice']){//付款提醒状态
            if ($params['pay_notice'] == TAP_DATE_WITHOUT_BALANCE){//查询额度不足的供应商

                $supplier_settlement_online = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;

                $supplier_res = $query->query("SELECT supplier_code FROM pur_supplier WHERE supplier_settlement='{$supplier_settlement_online}' and pur_supplier.quota <> 0 and pur_supplier.surplus_quota <= 0")->result_array();
                $supplier_res = !empty($supplier_res)?array_column($supplier_res,'supplier_code'):[ORDER_CANCEL_ORSTATUS];
                $supplier_res= array_unique($supplier_res);

                $query->where_in("p.supplier_code",$supplier_res);

            }

            if ($params['pay_notice'] != TAP_DATE_WITHOUT_BALANCE){//查询额度足够的供应商
                $supplier_settlement_online = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;
                /*$supplier_res = $query->query("SELECT supplier_code FROM pur_supplier WHERE supplier_settlement='{$supplier_settlement_online}' and pur_supplier.quota <> 0 and pur_supplier.surplus_quota > 0")->result_array();
                $supplier_res = !empty($supplier_res)?array_column($supplier_res,'supplier_code'):[ORDER_CANCEL_ORSTATUS];
                $supplier_res= array_unique($supplier_res);

                $query->where_in("p.supplier_code",$supplier_res);*/

                $query->where("ppy.accout_period_time != '0000-00-00 00:00:00'");

                if ($params['pay_notice'] == TAP_DATE_OVER_TIME){//已超期
                    $today = date('Y-m-d H:i:s');
                    $query->where("ppy.accout_period_time<'{$today}'");
                }

                if ($params['pay_notice'] == TAP_DATE_COMING_SOON){//即将到期
                    $today = date('Y-m-d H:i:s');
                    $five_days_later = date('Y-m-d H:i:s',strtotime('+ 5 days'));
                    $query->where("ppy.accout_period_time >= '{$today}'");
                    $query->where("ppy.accout_period_time < '{$five_days_later}'");
                }
                if ($params['pay_notice'] == TAP_DATE_CAN_WAIT){//可继续等待
                    $five_days_later = date('Y-m-d H:i:s',strtotime('+ 5 days'));
                    $query->where("ppy.accout_period_time >= '{$five_days_later}'");
                }

            }
        }


        if(isset($params['payment_platform'])&& $params['payment_platform']=='6'){
//             $supplier_code_list = $query->query("SELECT supplier_code FROM pur_supplier_payment_account WHERE payment_platform='{$params['payment_platform']}'")->result_array();
//             $supplier_code_list= !empty($supplier_code_list)?array_column($supplier_code_list, 'supplier_code'):['xxxxxxx'];
//             $query->where_in("p.supplier_code",$supplier_code_list);
            $query->join('pur_supplier_payment_info spa','p.supplier_code = spa.supplier_code AND spa.is_del = 0','right');
            $query->where('spa.payment_platform',$params['payment_platform']);
            $query->where("p.pay_type",PURCHASE_PAY_TYPE_PRIVATE);

            unset($supplier_code_list);
        }
        //支付方式=线下境内，那么默认支付平台=网银
        if(isset($params['payment_platform'])&& $params['payment_platform']=='5'){
//            $supplier_code_list = $query->query("SELECT supplier_code FROM pur_supplier_payment_account WHERE payment_platform='{$params['payment_platform']}'")->result_array();
//            $supplier_code_list= !empty($supplier_code_list)?array_column($supplier_code_list, 'supplier_code'):['xxxxxxx'];
//            $query->where_in("p.supplier_code",$supplier_code_list);
            $query->join('pur_supplier_payment_info spa','p.supplier_code = spa.supplier_code AND spa.is_del = 0','right');
            $query->where('spa.payment_platform',$params['payment_platform']);
            $query->where("p.pay_type",PURCHASE_PAY_TYPE_PUBLIC);
            unset($supplier_code_list);
        }

        //支付方式≠线下境内/线下境外，那么默认支付平台=其他
        if(isset($params['payment_platform'])&& $params['payment_platform']=='1'){
//            $supplier_code_list = $query->query("SELECT supplier_code FROM pur_supplier_payment_account WHERE payment_platform not in (5,6)")->result_array();
//            $supplier_code_list= !empty($supplier_code_list)?array_column($supplier_code_list, 'supplier_code'):['xxxxxxx'];
//            $query->where_in("p.supplier_code",$supplier_code_list);
            $query->join('pur_supplier_payment_info spa','p.supplier_code = spa.supplier_code AND spa.is_del = 0','right');
            $query->where_not_in('spa.payment_platform',[5,6]);
            $query->where_not_in('p.pay_type',[PURCHASE_PAY_TYPE_PUBLIC,PURCHASE_PAY_TYPE_PRIVATE]);
            unset($supplier_code_list);
        }
        $data = $query->limit($limit, $offsets)->group_by('p.id')->order_by('p.id','desc')->get()->result_array();

        //获取供应商名
        $supplier_code= is_array($data)?array_column($data, 'supplier_code'):[];
        $warehouse_code_list = $this->get_supplier_name($supplier_code);
        //获取结算方式
        $settlement_method= is_array($data)?array_column($data, 'settlement_method'):[];
        $this->load->model("supplier/Supplier_settlement_model");
        $supplier_code_list = $this->Supplier_settlement_model->get_code2name_list($settlement_method);
        //获取1688子账号绑定付款人id
        $applicant_list=is_array($data)?array_column($data, 'applicant'):[];
        $pay_user_name_list= $this->get_account_sub($applicant_list);
        //获取对对账号集合
        $pur_number_list= is_array($data)?array_column($data, 'pur_number'):[];
        $statement_number_arr= $this->get_statement_number($pur_number_list);
        //统计总数
        $all_user_list = get_buyer_name();

        foreach ($data as $key => $val) {
            //支付方式
            $payment_platform = $this->Supplier_payment_info_model->get_payment_platform($val['supplier_code'],$val['is_drawback'],$val['purchase_type_id'],$val['pay_type']);
            $data[$key]['payment_platform'] = get_supplier_payment_platform($payment_platform['payment_platform']??'');
            //判断线上账期付款提醒状态
            $data[$key]['product_line_id'] = $this->get_product_line_id($val['purchase_number']);
            $data[$key]['pay_notice'] = formatAccoutPeriodTime($data[$key]['settlement_method'], $data[$key]['pay_status'], $data[$key]['need_pay_time'], $data[$key]['surplus_quota']);
            $data[$key]['statement_number']= isset($statement_number_arr[$val['pur_number']])?$statement_number_arr[$val['pur_number']]:'';
            $data[$key]['status'] = $val['pay_status'];
            $data[$key]['pay_status'] = isset($val['pay_status'])?getPayStatus($val['pay_status']):'';
            $data[$key]['supplier_name'] = isset($warehouse_code_list[$val['supplier_code']])?$warehouse_code_list[$val['supplier_code']]:"";
            $data[$key]['pay_type'] = isset($val['pay_type'])?getPayType($val['pay_type']):'';
            $data[$key]['settlement_method'] = isset($supplier_code_list[$val['settlement_method']])?$supplier_code_list[$val['settlement_method']]:'';
            $data[$key]['applicant']= isset($all_user_list[$val['applicant']]) ? $all_user_list[$val['applicant']]: '';
            $data[$key]['auditor']= isset($all_user_list[$val['auditor']]) ? $all_user_list[$val['auditor']] :  '';
            $data[$key]['pay_category'] = isset($val['pay_category']) ? getPayCategory($val['pay_category']) : "";
            $data[$key]['is_drawback'] = isset($val['is_drawback']) ? getIsDrawbackShow($val['is_drawback']) : "";
            $data[$key]['is_ali_order'] = isset($val['is_ali_order'])?getIsAliOrder($val['is_ali_order']):'';
//            if(in_array($val['pay_category'],[2,3])){// 请款类型为-采购货款、采购货款+运费/优惠，才需展示该字段
            $data[$key]['purchase_order_status'] = isset($val['purchase_order_status'])?getPurchaseStatus($val['purchase_order_status']):'';
//            }else{
//                $data[$key]['purchase_order_status'] = '';
//            }
            if($val['source']==SOURCE_NETWORK_ORDER){
                $pay_user_name= isset($pay_user_name_list[$val['applicant']."-".$val['purchase_account']])?$pay_user_name_list[$val['applicant']."-".$val['purchase_account']]:'';
                $data[$key]['payer_name'] = empty($val['payer_name']) ? $pay_user_name : $val['payer_name'];
            }
            // 采购账号子账号的只显示主账号
            $purchase_account = $data[$key]['purchase_account'];
            if($purchase_account != 'yibaisuperbuyers' and stripos($purchase_account,':') !== false ){
                $accunt_arr = explode(':',$purchase_account);
                $data[$key]['purchase_account'] = isset($accunt_arr[0])?$accunt_arr[0]:$purchase_account;
            }
            if($val['source']==SOURCE_COMPACT_ORDER and $val['source_subject'] == SOURCE_SUBJECT_COMPACT_ORDER){
                $amount_list = $this->Compact_model->calculate_compact_related_amount($val['pur_number']);
                if($amount_list['code'] === false){
                    $return['msg'] = $amount_list['msg'];
                    return $return;
                }
                $data[$key]['paid_total_real_money'] = isset($amount_list['paid_total_real_money']) ? $amount_list['paid_total_real_money'] : 0;
                $data[$key]['available_product_money'] = isset($amount_list['available_product_money']) ? $amount_list['available_product_money'] : 0;
            }

            $data[$key]['check_status'] = $this->get_check_status($val['requisition_number']);
            $data[$key]['is_statement_pay'] = getIsStatementPay($val['source_subject']);
            unset($data[$key]['surplus_quota']);
        }

        $return_data = [
            'values'=>$data,
            'paging_data'=>[
                'offset'=>$page,
                'limit'=>$limit,
            ],
        ];
        return $return_data;
    }


    /**
     * 第一种：当该合同/po存在一个验货状态=验货不合格、不合格待确认、转合格申请中，且“是否特批出货=否”
     * 第二种：最近一次验货结果=验货不合格，且验货状态=待采购确认、待品控确认、待品控排期，且“是否特批出货=否”
     * 满足上述两种的任何一种，即属于验货不合格
     */

    public function is_check_status($requisition_number = ''){
        $errer = '';
        $data  = $this->purchase_db->select('check_status,is_special,purchase_number')
            ->from($this->table_entry)
            ->where('requisition_number',$requisition_number)
            ->get()->result_array();
        if($data){
            foreach ($data as $val){
                if($val['is_special']!=1 and $val['check_status']==11){
                    $errer = '请款单号:'.$requisition_number.'中的'.$val['purchase_number'].'验货不合格，不允许付款，只能驳回';
                    break;
                }
            }
        }
        return $errer;
    }

    /***
     * 获取明细的
     */
    public function get_check_status($requisition_number){
        $data  = $this->purchase_db->select('check_status')
            ->from($this->table_entry)
            ->where('requisition_number',$requisition_number)
            ->get()->row_array();
        $check_status ='';
        if(!empty($data)){
            $check_status =getCheckStatus($data['check_status']);
        }
        return $check_status;
    }

    /**
     * 获取付款人
     * @param array $applicant_list
     * @return array
     */
    public function get_account_sub_new(array $account_list){
        if(empty($account_list)){
            return [];
        }
        $res= $this->purchase_db
            ->select('user_id,pay_user_name,account')
            ->where_in('account',$account_list)
            ->get('alibaba_sub')->result_array();
        if(empty($res)) {
            return [];
        }
        $data=[];
        foreach ($res as $key => $value) {
            $data[$value['account']]=$value['pay_user_name'];
        }
        return $data;
    }

}
