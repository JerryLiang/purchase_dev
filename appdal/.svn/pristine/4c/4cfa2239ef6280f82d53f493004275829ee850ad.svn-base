<?php
/**
 * 应付单报表
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/12/25
 * Time: 9:48
 */

class Payment_order_report_model extends Purchase_model{

    //getPayType 付款类型
    //getPayStatus 付款状态
    //getPayCategory 请款类型
    //getIsDrawbackShow 是否退税
    //getIsAliOrder 是否1688下单
    //getPurchaseStatus 采购状态
    // getPurchaseType  业务线
    //['6'=>'富友','5'=>'网银','1'=>'其他'];// 支付平台
    public function __construct(){
        parent::__construct();
//        $this->library = $this->load->database('library', true);//获取从库数据
        $this->load->helper('status_finance');
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');
        $this->load->helper('export_csv');
        $this->table_detail ='pur_purchase_order_pay_detail';
        $this->table_name ='pur_purchase_order_pay';
        $this->table_requisition = 'purchase_pay_requisition';
        $this->table_ufxfuiou = 'purchase_order_pay_ufxfuiou';
        $this->table_ufxfuiou_detail = 'ufxfuiou_pay_detail';
        $this->table_baofppay = 'purchase_order_pay_baofppay';
        $this->table_baofo_detail = 'purchase_order_pay_baofo_detail';
        $this->load->model('finance/Payment_order_pay_model');
    }

    /**
     * 获取应付单报表数据
     */
    public function get_pay_order_report_new($params,$offsets, $limit,$page=1, $action = false){
        if(isset($params['pay_account_number']) && $params['pay_account_number']!=''){
            $pay_account_number = $this->purchase_db->select('account_short')
                ->where('account_number',$params['pay_account_number'])
                ->get('bank_card')
                ->result_array();
            $account_short_list = array_column($pay_account_number,'account_short');
        }
        if(isset($params['k3_bank_account']) && !empty($params['k3_bank_account'])){
            $account_short_list2 = $this->purchase_db->select('account_short')
                ->where('k3_bank_account',$params['k3_bank_account'])
                ->get('bank_card')
                ->result_array();
            $account_short_list2 = !empty($account_short_list2)?array_column($account_short_list2,'account_short'):[PURCHASE_NUMBER_ZFSTATUS];
        }

        $query = $this->purchase_db;
        $fileds = 'p.id, p.purchase_name, p.pur_number, p.requisition_number, p.pai_number,p.procedure_fee,p.applicant,'
            .'p.supplier_code, p.pay_type, p.pay_status, p.pay_category, p.settlement_method, p.pay_price,'
            .'p.product_money, p.freight, p.discount, p.process_cost, p.commission, p.purchase_account,'
            .'p.payer_time, p.payer_name, p.source,p.source_subject, sp.supplier_name, p.pur_tran_num, p.pay_account, p.pay_number,'
            .'p.pay_branch_bank, p.abstract_remark, p.finance_report_remark, p.payment_platform, p.procedure_party,'
            .'GROUP_CONCAT(DISTINCT(de.purchase_number)) as purchase_number,'
            .'GROUP_CONCAT(DISTINCT(pt.pai_number)) as pai_number_o,'
            .'GROUP_CONCAT(DISTINCT(po.buyer_name)) as buyer_name';
        $query->from('purchase_order_pay as p');
        $query->join('pur_supplier as sp', 'sp.supplier_code=p.supplier_code', 'inner');
        $query->join('pur_purchase_order_pay_detail as de', 'de.requisition_number=p.requisition_number', 'left');
        $query->join('pur_purchase_order_pay_type as pt', 'pt.purchase_number=de.purchase_number', 'left');
        $query->join('pur_purchase_order as po', 'po.purchase_number=pt.purchase_number', 'left');

        //ID
        if (isset($params['ids']) && $params['ids']) {
            $ids_ss = query_string_to_array($params['ids']);
            $query->where_in('p.id',$ids_ss);
        }
        if(isset($params['purchase_type_id']) and $params['purchase_type_id']!=''){
            $query->where("p.purchase_type_id", $params['purchase_type_id']);
        }
        // 付款时间
        if(isset($params['pay_time_start']) and $params['pay_time_start'] and isset($params['pay_time_end']) and $params['pay_time_end']){//应付款时间
            $start_time = date('Y-m-d 00:00:00',strtotime($params['pay_time_start']));
            $end_time = date('Y-m-d 23:59:59',strtotime($params['pay_time_end']));
//            $query->where("p.payer_time between '{$start_time}' and '{$end_time}' ");
            $query->where("p.payer_time >=", $start_time);
            $query->where("p.payer_time <=", $end_time);
        }
        //请款类型
        if (isset($params['pay_category']) && $params['pay_category']) {
            $query->where('p.pay_category', $params['pay_category']);
        }
        //采购来源
        if (isset($params['purchase_source']) && $params['purchase_source']) {//采购来源
            $query->where('p.source', $params['purchase_source']);
        }
        //请款单号
        if (isset($params['requisition_number']) && $params['requisition_number']) {
            $requisition_number_ss = query_string_to_array($params['requisition_number']);
            $query->where_in('p.requisition_number',$requisition_number_ss);
        }
        //付款人
        if(isset($params['pay_user_id']) && $params['pay_user_id']!=''){
            $pay_user_id= trim($params['pay_user_id']);
            $query->where('p.payer_id',$pay_user_id);
        }
        if(isset($params['supplier_code']) && $params['supplier_code']!=''){
            $supplier_code = trim($params['supplier_code']);
            $query->where('p.supplier_code',$supplier_code);
        }

        if(isset($params['purchase_name']) && $params['purchase_name']!=''){
            $purchase_name = trim($params['purchase_name']);
            $query->where('p.purchase_name',$purchase_name);
        }

        if(isset($account_short_list) && !empty($account_short_list)){
            $query->where_in('p.pay_account',$account_short_list);
        }
        if(isset($account_short_list2) && !empty($account_short_list2)){
            $query->where_in('p.pay_account',$account_short_list2);
        }

        //支付方式
        if(isset($params['pay_type']) && $params['pay_type'] !=''){
            $query->where_in('p.pay_type',$params['pay_type']);
        }
        if(isset($params['settlement_method']) && $params['settlement_method'] !=''){
            $query->where_in('p.settlement_method',$params['settlement_method']);
        }

        if(isset($params['is_set_report_remark']) && $params['is_set_report_remark'] !=''){
            if($params['is_set_report_remark'] == 1){
                $query->where("p.finance_report_remark <> ''");
            }else{
                $query->where("p.finance_report_remark = ''");
            }
        }

        //合同单号
        if(isset($params['compact_number']) && $params['compact_number']!=''){
            $return_number = explode(' ', trim($params['compact_number']));
            $query->where_in('p.pur_number',$return_number);
        }
        if(isset($params['pay_number']) && $params['pay_number']!=''){
            $query->where('p.pay_number',$params['pay_number']);
        }
        if((isset($params['receive_unit']) && !empty($params['receive_unit'])) || (isset($params['receive_account']) && !empty($params['receive_account']))){
            $query->join('pur_purchase_pay_requisition as ppr', 'ppr.requisition_number=p.requisition_number','inner');
            if($params['receive_unit']){
                $query->where_in('ppr.receive_unit',$params['receive_unit']);
            }
            if($params['receive_account']){
                $query->where_in('ppr.receive_account',$params['receive_account']);
            }
        }

        //采购单号
        if(isset($params['purchase_number'] ) && $params['purchase_number']!=''){
            $return_number = explode(' ', trim($params['purchase_number']));
            $return_number = implode("','",$return_number);
            $return_number = $return_number?$return_number:[PURCHASE_NUMBER_ZFSTATUS];
            $query->where_in("de.purchase_number", $return_number);
        }
        $query->where('p.pay_status',51);

        $query->group_by('p.requisition_number');

        if($action != 'export'){
            //计算总条数
            $count_qb = clone $query;
            if($action == 'sum'){
                $count_select = "count(DISTINCT p.requisition_number) as count_row";
            }else{
                $count_select = "p.requisition_number,p.pay_price,p.product_money,p.freight,p.discount,p.process_cost,p.commission";
            }
            $count_qb->select($count_select);
            $count_qb_sql = $count_qb->get_compiled_select();


            if($action == 'sum'){
                $count_row = $count_qb->query("SELECT COUNT(1) AS count_row 
                        FROM (
                          $count_qb_sql
                        ) AS tmp")->row_array();
                return isset($count_row['count_row']) ? (int)$count_row['count_row'] : 0;
            }else{

                $count_row = $count_qb->query("SELECT COUNT(1) AS count_row, SUM(pay_price) AS all_pay_price, SUM(product_money) AS all_product_money,
                        SUM(freight) AS all_freight, SUM(discount) AS all_discount, SUM(process_cost) AS all_process_cost,SUM(commission) AS all_commission
                        FROM (
                          $count_qb_sql
                        ) AS tmp")->row_array();
                $total_count = isset($count_row['count_row']) ? (int)$count_row['count_row'] : 0;
            }
        }
        if($action != 'sum'){
            $query->select($fileds);
        }

        $query->limit($limit, $offsets);
        $results = $query->order_by('p.id','desc')->get()->result_array();

        $key_table =['付款时间','采购类型','采购主体','供应商编码/供应商名称','是否退税','业务线','请款类型','摘要','我司交易名称',
            '我司开户行','我司交易账号','k3账户','交易对方户名','交易对方开户行','交易对方账号','合同号','采购单号',
            '拍单号','采购员','结算方式','支付方式',
            '付款回单编号/请款单号','商品额','运费','优惠额','请款总额','付款人'];

        $sum_pay_price = $sum_freight = $sum_product_money = $sum_discount = $sum_process_cost = $sum_commission = 0;

        if(!empty($results)){
            $requisition_arr = array_column($results, 'requisition_number');

            $pay_info = $this->get_requisition_by_settlement($requisition_arr); // 获取支付信息
            $bankInfo  = $this->get_all_payment_bank(); // 获取银行支付信息
            // 获取对应的产品线和退税
            $pur_list = [];
            $requisition_list = [];
            foreach ($results as $val){
                if(empty($val['purchase_number']))continue;
                $pur_items = explode(',', $val['purchase_number']);
                if(!in_array($val['requisition_number'], array_keys($requisition_list)))$requisition_list[$val['requisition_number']] = [];
                if(!$pur_items || count($pur_items) == 0)continue;
                foreach ($pur_items as $v){
                    $v = str_replace(' ', '', $v);
                    if(!empty($v) && !in_array($v, $pur_list)){
                        $pur_list[] = $v;
                        $requisition_list[$val['requisition_number']][] = $v;
                    }
                }
            }
            $pur_info = $this->get_purchase_info($pur_list, $requisition_list);

            $settlement_method = $this->Payment_order_pay_model->get_settlement_method_data();

            $r_number_list = $this->get_purchase_by_settlement($requisition_arr);

            $user_list = get_buyer_name();

            foreach ($results as $key => $val){
                //对方交易账户信息、我司交易账户信息
                if($val['pay_category'] == PURCHASE_PAY_CATEGORY_5){// 样品请款
                    $buyer_name = isset($user_list[$val['applicant']]) ? $user_list[$val['applicant']] : '';
                    if(empty($buyer_name)){
                        $buyer_name = get_buyer_name($val['applicant']);
                    }
                    $results[$key]['buyer_name']     = $buyer_name;
                }
                if($val['source_subject'] == 3){// 3.对账单请款
                    $results[$key]['compact_number'] = '';
                    $results[$key]['statement_number'] = $val['pur_number'];
                }elseif($val['source_subject'] == 1){// 1.合同请款
                    $results[$key]['compact_number'] = $val['pur_number'];
                    $results[$key]['statement_number'] = '';
                }else{// 网采单不显示合同号和对账单号
                    $results[$key]['compact_number'] = '';
                    $results[$key]['statement_number'] = '';
                }

                if(empty($val['pai_number'])){
                    $results[$key]['pai_number']     = $val['pai_number_o'];
                }

                $results[$key]['pur_number']     = str_replace(',',' ',$val['purchase_number']);

                $pay_info_items = in_array($val['requisition_number'], array_keys($pay_info))?$pay_info[$val['requisition_number']]: [];
                //合同单
                $results[$key]['receive_unit']            = isset($pay_info_items['receive_unit']) ? $pay_info_items['receive_unit'] : '';
                $results[$key]['receive_account']         = isset($pay_info_items['receive_account']) ? $pay_info_items['receive_account'] : '';
                $results[$key]['payment_platform_branch'] = isset($pay_info_items['payment_platform_branch']) ? $pay_info_items['payment_platform_branch'] : '';
                $results[$key]['pay_account']             = $val['pay_account'];// 我司支付账号名称
                $results[$key]['pay_account_number']      = in_array($val['pay_account'], array_keys($bankInfo)) && isset($bankInfo[$val['pay_account']]['account_number']) ? $bankInfo[$val['pay_account']]['account_number'] : '';// 我司支付账号
                $results[$key]['pay_branch_bank']         = $val['pay_branch_bank'];// 我司开户行
                $results[$key]['pay_number']              = $val['pay_number'];// 我司交易名称
                $results[$key]['k3_bank_account']         = in_array($val['pay_account'], array_keys($bankInfo)) && isset($bankInfo[$val['pay_account']]['k3_bank_account']) ? $bankInfo[$val['pay_account']]['k3_bank_account'] : '';// k3账户


                $is_drawback = '否';
                $purchase_info = in_array($val['requisition_number'], array_keys($pur_info))?$pur_info[$val['requisition_number']]: [];
                //是否退税
                $results[$key]['purchase_type_id'] = isset($purchase_info['purchase_type_id']) ? getPurchaseType($purchase_info['purchase_type_id']): '';
                $results[$key]['is_drawback'] = isset($purchase_info['is_drawback']) ? getIsDrawback($purchase_info['is_drawback']): $is_drawback;
                $results[$key]['pay_status'] = isset($val['pay_status'])?getPayStatus($val['pay_status']):'';
                $results[$key]['pay_type'] = isset($val['pay_type'])?getPayType($val['pay_type']):'';
                $results[$key]['pay_category'] = isset($val['pay_category'])?getPayCategory($val['pay_category']):'';
                $results[$key]['source'] = isset($val['source'])?getPurchaseSource($val['source']):'';

                $results[$key]['purchase_name_cn']  = $val['purchase_name'];
                $results[$key]['purchase_name'] = isset($val['purchase_name'])?get_purchase_agent($val['purchase_name']):'';
                $results[$key]['settlement_method'] = isset($val['settlement_method']) && in_array($val['settlement_method'], array_keys($settlement_method))?$settlement_method[$val['settlement_method']]:'';

                $procedure_fee   = ($val['procedure_party'] == PAY_PROCEDURE_PARTY_B)?$val['procedure_fee']:0;// 只有 乙方承担手续费才需要显示
                $abstract_remark = convertAbstractRemark($val['abstract_remark'],$val['payer_time'],$val['payment_platform'],$procedure_fee,$val['freight'],$val['discount'],$val['process_cost'],$val['commission']);
                $results[$key]['abstract_remark'] = $abstract_remark;

                if($action != 'export') {
                    //页眉金额统计
                    $sum_pay_price += $val['pay_price'];//实际付款金额或请款总额
                    $sum_freight += $val['freight'];//运费
                    $sum_product_money += $val['product_money'];//商品额
                    $sum_discount += $val['discount'];//商品额
                    $sum_process_cost += $val['process_cost'];// 加工费
                    $sum_commission += $val['commission'];// 加工费
                }
            }
        }

        $return_data = [];
        if($action != 'export'){
            $data_list =[];

            $all_pay_price                      = [];
            $all_pay_price['all_freight']       = sprintf('%.3f', $count_row['all_freight']);
            $all_pay_price['all_pay_price']     = sprintf('%.3f', $count_row['all_pay_price']);
            $all_pay_price['all_product_money'] = sprintf('%.3f', $count_row['all_product_money']);
            $all_pay_price['all_discount']      = sprintf('%.3f', $count_row['all_discount']);
            $all_pay_price['all_process_cost']  = sprintf('%.3f', $count_row['all_process_cost']);
            $all_pay_price['all_commission']    = sprintf('%.3f', $count_row['all_commission']);

            $this->load->model('user/purchase_user_model');
            $this->load->model('finance/Payment_order_pay_model');
            $finance = $this->purchase_user_model->get_finance_list();
            $data_list['pay_user_id'] = is_array($finance)?array_column($finance, 'name','id'):[];
            $data_list['settlement_method'] = $this->Payment_order_pay_model->get_settlement_method(); //结算方式

            //获取供应商支付平台
            $data_list['purchase_type_id'] = getPurchaseType();//业务线
            $data_list['pay_category'] = getPayCategory();//请款方法
            $data_list['purchase_source'] = getPurchaseSource();//采购来源
            $data_list['purchase_agent'] = get_purchase_agent();//主体
            $data_list['pay_type'] = getPayType();//支付方式
            $data_list['is_set_report_remark_type'] = ['1' => '不为空','2' => '为空'];//支付方式
            $return_data = [
                'drop_down_box' => $data_list,
                'key'=>$key_table,
                'paging_data'=>[
                    'total'=>$total_count,
                    'offset'=>$page,
                    'limit'=>$limit,
                    'pages'=> ceil($total_count/$limit),
                ],
                //当前页金额
                'aggregate_data' => [
                    'sum_pay_price'=> sprintf('%.3f',$sum_pay_price),
                    'sum_freight'=> sprintf('%.3f',$sum_freight),
                    'sum_product_money'=> sprintf('%.3f',$sum_product_money),
                    'sum_discount'=> sprintf('%.3f',$sum_discount),
                    'sum_process_cost'=> sprintf('%.3f',$sum_process_cost),
                    'sum_commission'=> sprintf('%.3f',$sum_commission),
                ],
                'aggregate_data_all' => $all_pay_price//所有页金额
            ];
        }

        $return_data['values'] = $results;
        $return_data['len'] = count($results);
        return $return_data;
    }

    /**
     * 获取采购单号对应的业务线和是否退税
     */
    private function get_purchase_info($purchase, $list)
    {
        if(!$purchase || count($purchase) == 0 || !$list || count($list) == 0)return [];
        $purchase = implode("','", $purchase);
        $data = $this->purchase_db->query("select purchase_type_id,purchase_number,is_drawback from pur_purchase_order where purchase_number in ('".$purchase."')")->result_array();
        /*
        $data = $this->purchase_db->from('purchase_order')
            ->select('purchase_type_id,purchase_number,is_drawback')
            ->where_in('purchase_number', $purchase)
            ->get()
            ->result_array();
        */
        if(!$data || count($data) == 0)return [];
        $res = [];
        foreach ($data as $val){
            foreach ($list as $k=>$v){
                if(!empty($val['purchase_number']) && !in_array($k, array_keys($res)) && in_array($val['purchase_number'], $v))$res[$k] = $val;
            }
        }
        return $res;
    }

    /**
     * 获取我司所有的支付账号
     */
    private function get_all_payment_bank()
    {
        $bank = $this->purchase_db
            ->select('account_short,account_number,branch,account_holder,k3_bank_account')
            ->get('bank_card')
            ->result_array();
        // ->where('account_short', $account_short)
        if($bank && count($bank) > 0){
            $res = [];
            foreach ($bank as $val){
                $res[$val['account_short']] = $val;
            }
            return $res;
        }
        return [];
    }

    /**
     * 根据请款单获取采购单号
     * @author yefanli
     */
    private function get_purchase_by_settlement($requisition)
    {
        if(!$requisition || !is_array($requisition) || count($requisition) == 0)return [];
        $data = $this->purchase_db->from($this->table_detail)
            ->select('requisition_number, GROUP_CONCAT(",", purchase_number) as purchase_number')
            ->where_in('requisition_number', $requisition)
            ->group_by('requisition_number')
            ->get()
            ->result_array();
        if($data && count($data) > 0){
            $res = [];
            foreach ($data as $val){
                if(!in_array($val['requisition_number'], array_keys($res))){
                    $pur_number = str_replace(" ", '', $val['purchase_number']);
                    $pur_number = explode(',', $pur_number);
                    $pur_list = [];
                    foreach ($pur_number as $v_p){
                        if(!empty($v_p) && !in_array($v_p, $pur_list))$pur_list[]=$v_p;
                    }
                    $pur_list = implode(',', $pur_list);
                    $res[$val['requisition_number']] = $pur_list;
                }
            }
            if(count($res) > 0)return $res;
        }
        return [];
    }

    /**
     * 根据请款单获取支付账号等信息
     * @author yefanli
     */
    private function get_requisition_by_settlement($requisition)
    {
        if(!$requisition || !is_array($requisition) || count($requisition) == 0)return [];
        $data = $this->purchase_db->from('purchase_pay_requisition')
            ->select('requisition_number, receive_unit, receive_account, payment_platform_branch')
            ->where_in('requisition_number', $requisition)
            ->where('status = ', 1)
            ->get()
            ->result_array();
        if($data && count($data) > 0){
            $res = [];
            foreach ($data as $val){
                if(!in_array($val['requisition_number'], array_keys($res)))$res[$val['requisition_number']] = $val;
            }
            if(count($res) > 0)return $res;
        }
        return [];
    }

    /**
     * 转换 请款摘要信息
     * @param      $abstract_remark
     * @param null $payer_time
     * @param null $pay_platform
     * @param null $fee
     * @return mixed
     */
    private function _convertAbstractRemark($abstract_remark,$payer_time = null,$pay_platform = null,$fee = null){
        return $abstract_remark;
    }

    /**
     * 根据请款单号获取采购订单号
     * @param array $pur_tran_num
     * @return array
     */
    public function get_purchase_number($requisition_number){
        if(empty($requisition_number)){
            return '';
        }
        if(gettype($requisition_number) != 'array')$requisition_number = [$requisition_number];
        $order_datail = $this->purchase_db
            ->select('requisition_number,purchase_number')
            ->where_in('requisition_number', $requisition_number)
            ->get($this->table_detail)
            ->result_array();
        $data         = [];
        foreach($order_datail as $key => $value){
            $data[] = $value['purchase_number'];
        }
        $data = array_unique($data);
        if(empty($data)){
            return '';
        }
        return $data;
    }




    /***
     * 根据请款单号获取付款申请书(合同单)
     */
    public function get_pay_requisition($requisition_number){
        $data = [];
        if(empty($requisition_number)){
            return $data;
        }
        $data = $this->get_pay_baofppay($requisition_number);//宝付
        if(empty($data)){
            $data = $this->get_pay_ufxfuiou($requisition_number);//富友
            if(empty($data)){ //请款单
                $data = $this->purchase_db->select('receive_unit,receive_account,payment_platform_branch')->from($this->table_requisition)
                    ->where('status',1)
                    ->where('requisition_number',$requisition_number)
                    ->get()->row_array();
            }
        }
        return $data;
    }

    /***
     * 根据请款单号获取付款申请书(合同单)
     */
    public function get_pay_baofppay($requisition_number){
        $query= $this->purchase_db;
        $query->select('to_acc_name receive_unit,to_acc_no receive_account,to_bank_name payment_platform_branch');
        $query->from($this->table_baofppay.' as a');
        $query->join($this->table_baofo_detail.' as b', ' a.pur_tran_num=b.pur_tran_num');
        $query->where('a.audit_status<>3');
        $query->where('b.requisition_number',$requisition_number);
        $data = $query->get()->row_array();
        return $data;
    }

    /***
     * 根据请款单号获取付款申请书(合同单)
     */
    public function get_pay_ufxfuiou($requisition_number){
        $query= $this->purchase_db;
        $query->select('payee_card_number receive_account,payee_user_name receive_unit,branch_bank payment_platform_branch');
        $query->from($this->table_ufxfuiou.' as a');
        $query->join($this->table_ufxfuiou_detail.' as b', ' a.pur_tran_num=b.pur_tran_num');
        $query->where('a.status=1');
        $query->where('a.requisition_number',$requisition_number);
        $data = $query->get()->row_array();
        return $data;
    }

    /**
     * 添加、修改 财务付款统计表备注
     * @param $ids_arr
     * @param $remark
     * @return bool
     */
    public function add_finance_report_remark($ids_arr,$remark){
        if(empty($ids_arr) or empty($remark)) return false;

        $remark = $remark.' '.getActiveUserName().' '.date('Y-m-d H:i:s');
        $this->purchase_db->where_in('id',$ids_arr)->update($this->table_name,['finance_report_remark' => $remark]);

        return true;
    }



}