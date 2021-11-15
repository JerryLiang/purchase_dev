<?php
class Payment_order_pay_new_model extends Purchase_model
{
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
    }


    /**
     * @return string
     */
    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd-H:i:s")."-".($b[0] * 1000);
    }

    /**
     * 应付款列表导出的数据
     */
    public function get_pay_list_new($params, $type, $offsets, $limit,$id='',$page=1)
    {

        $log_file = APPPATH . 'logs/yefanli_get_pay_list_new' . date('Ymd') . '.txt';
        file_put_contents($log_file, $this->get_microtime() . "**start get_pay_list_new******\n", FILE_APPEND);
        file_put_contents($log_file, $this->get_microtime() . "** get_user_role ******\n", FILE_APPEND);
        $role_name      = get_user_role();//当前登录角色
        $taobao_account = $this->get_account_list(['account_type' => 1]);// 获取 淘宝账号
        $taobao_account = array_column($taobao_account,'purchase_account');
        file_put_contents($log_file, $this->get_microtime() . "** end_user_role ******\n", FILE_APPEND);
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
            . 'po.is_ali_order,'
            . 'po.warehouse_code,'
            . 'po.buyer_name,'
            . 'po.account_type,'
            . 'po.purchase_order_status,'
            . 'po.purchase_type_id'
        );
        $query->from('purchase_order_pay as p');
        $query->join('pur_supplier as sp', 'sp.supplier_code=p.supplier_code','left');
        $query->join('purchase_order_pay_type as ppy', 'ppy.purchase_number=p.pur_number','left');

        //如果筛选条件是单独请款运费的 那么$type修改为合同单   因为合同单单独请款运费时采购来源变成网采
        if(isset($params['pay_category']) && $params['pay_category']==4 && $type==SOURCE_NETWORK_ORDER){
            $type  = 1;
            $query->where('p.source', 2);
        }else{
            $query->where('p.source', $type);
        }

        if($type==SOURCE_NETWORK_ORDER) {
            $query->join('purchase_order as po', 'po.purchase_number=ppy.purchase_number', 'inner');
        }
        $is_entry = false;
        if($type==SOURCE_COMPACT_ORDER){
            $query->join('purchase_order_pay_detail as pp', 'pp.requisition_number=p.requisition_number', 'left');
            $query->join('purchase_order as po', 'po.purchase_number=pp.purchase_number', 'inner');

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
        file_put_contents($log_file, $this->get_microtime() . "** start getRolefinance ******\n", FILE_APPEND);
        $data_role= getRolefinance();
        $res_arr=array_intersect($role_name, $data_role);
        file_put_contents($log_file, $this->get_microtime() . "** end getRolefinance ******\n", FILE_APPEND);
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

        file_put_contents($log_file, $this->get_microtime() . "** start 采购单号或合同号批量搜索 ******\n", FILE_APPEND);
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
        file_put_contents($log_file, $this->get_microtime() . "** end 采购单号或合同号批量搜索 ******\n", FILE_APPEND);
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
            $query->where_in('p.pay_status', [PAY_WAITING_FINANCE_REVIEW, PAY_SOA_REJECT,PAY_FINANCE_REJECT, PAY_WAITING_FINANCE_PAID, PART_PAID, PAY_PAID,PAY_UFXFUIOU_REVIEW,PAY_UFXFUIOU_SUPERVISOR,PAY_UFXFUIOU_MANAGER,PAY_UFXFUIOU_SUPPLY,PAY_GENERAL_MANAGER,PAY_REJECT_SUPERVISOR,PAY_REJECT_MANAGER,PAY_REJECT_SUPPLY,PAY_GENERAL_MANAGER_REJECT,PAY_UFXFUIOU_BAOFOPAY,PAY_LAKALA]);
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

        file_put_contents($log_file, $this->get_microtime() . "** start 付款提醒状态 ******\n", FILE_APPEND);
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


        file_put_contents($log_file, $this->get_microtime() . "** end 付款提醒状态 ******\n", FILE_APPEND);
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
        file_put_contents($log_file, $this->get_microtime() . "** start data ******\n", FILE_APPEND);
        $data = $query->limit($limit, $offsets)->group_by('p.id')->order_by('p.id','desc')->get()->result_array();

        file_put_contents($log_file, $this->get_microtime() . "** end data ******\n", FILE_APPEND);
        //获取供应商名
        file_put_contents($log_file, $this->get_microtime() . "** start get_supplier_name ******\n", FILE_APPEND);
        $supplier_code= is_array($data)?array_column($data, 'supplier_code'):[];
        $warehouse_code_list = $this->get_supplier_name($supplier_code);
        file_put_contents($log_file, $this->get_microtime() . "** end get_supplier_name ******\n", FILE_APPEND);
        //获取结算方式
        file_put_contents($log_file, $this->get_microtime() . "** start 获取结算方式 ******\n", FILE_APPEND);
        $settlement_method= is_array($data)?array_column($data, 'settlement_method'):[];
        $this->load->model("supplier/Supplier_settlement_model");
        $supplier_code_list = $this->Supplier_settlement_model->get_code2name_list($settlement_method);
        file_put_contents($log_file, $this->get_microtime() . "** end 获取结算方式 ******\n", FILE_APPEND);
        //获取1688子账号绑定付款人id
        file_put_contents($log_file, $this->get_microtime() . "** start 获取1688子账号绑定付款人id ******\n", FILE_APPEND);
        $applicant_list=is_array($data)?array_column($data, 'applicant'):[];
        $pay_user_name_list= $this->get_account_sub($applicant_list);
        file_put_contents($log_file, $this->get_microtime() . "** end 获取1688子账号绑定付款人id ******\n", FILE_APPEND);
        //获取对对账号集合
        file_put_contents($log_file, $this->get_microtime() . "** start 获取对对账号集合 ******\n", FILE_APPEND);
        $pur_number_list= is_array($data)?array_column($data, 'pur_number'):[];
        $statement_number_arr= $this->get_statement_number($pur_number_list);
        //统计总数
        $all_user_list = get_buyer_name();
        file_put_contents($log_file, $this->get_microtime() . "** end 获取对对账号集合 ******\n", FILE_APPEND);
        file_put_contents($log_file, $this->get_microtime() . "** start foreach ******\n", FILE_APPEND);

        foreach ($data as $key => $val) {
            //支付方式
            file_put_contents($log_file, $this->get_microtime() . "** start get_payment_platform ******\n", FILE_APPEND);
            $payment_platform = $this->Supplier_payment_info_model->get_payment_platform($val['supplier_code'],$val['is_drawback'],$val['purchase_type_id'],$val['pay_type']);
            file_put_contents($log_file, $this->get_microtime() . "** end get_payment_platform ******\n", FILE_APPEND);
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
            file_put_contents($log_file, $this->get_microtime() . "** start calculate_compact_related_amount ******\n", FILE_APPEND);
            if($val['source']==SOURCE_COMPACT_ORDER and $val['source_subject'] == SOURCE_SUBJECT_COMPACT_ORDER){
                $amount_list = $this->Compact_model->calculate_compact_related_amount($val['pur_number']);
                if($amount_list['code'] === false){
                    $return['msg'] = $amount_list['msg'];
                    return $return;
                }
                $data[$key]['paid_total_real_money'] = isset($amount_list['paid_total_real_money']) ? $amount_list['paid_total_real_money'] : 0;
                $data[$key]['available_product_money'] = isset($amount_list['available_product_money']) ? $amount_list['available_product_money'] : 0;
            }
            file_put_contents($log_file, $this->get_microtime() . "** end calculate_compact_related_amount ******\n", FILE_APPEND);

            $data[$key]['check_status'] = $this->get_check_status($val['requisition_number']);
            $data[$key]['is_statement_pay'] = getIsStatementPay($val['source_subject']);
            unset($data[$key]['surplus_quota']);
            file_put_contents($log_file, $this->get_microtime() . "** end get_check_status ******\n", FILE_APPEND);
        }

        file_put_contents($log_file, $this->get_microtime() . "** end foreach ******\n", FILE_APPEND);
        $return_data = [
            'values'=>$data,
            'paging_data'=>[
                'offset'=>$page,
                'limit'=>$limit,
            ],
        ];
        return $return_data;
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
}