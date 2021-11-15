<?php
/**
 * 采购单冲销结余
 * User: Jolon
 * Date: 2020/04/14 10:00
 */
class Charge_against_surplus_model extends Purchase_model {

    protected $table_name = 'purchase_order_charge_against_surplus';               //采购单冲销结余
    protected $table_purchase_order = 'purchase_order';                            //采购单信息表
    protected $table_purchase_order_pay_type = 'purchase_order_pay_type';          //采购单请款金额相关信息表
    protected $table_purchase_order_cancel = 'purchase_order_cancel';              //采购单取消未到货表
    protected $table_purchase_order_cancel_detail = 'purchase_order_cancel_detail';//采购单取消未到货明细表
    protected $table_purchase_order_reportloss = 'purchase_order_reportloss';      //采购单报损表
    protected $table_charge_against_records = 'purchase_order_charge_against_records';//采购单冲销记录
    protected $table_purchase_statement = 'purchase_statement';//对账单表
    protected $table_purchase_statement_items = 'purchase_statement_items';//对账单表

    public function __construct(){
        parent::__construct();
        $this->load->helper(['status_order','abnormal']);
        $this->lang->load('common_lang');
        $this->load->model('user/User_group_model');
    }

    /**
     * 创建 采购单冲销结余 记录
     * @param $purchase_numbers
     * @return array
     */
    public function insertBatch($purchase_numbers){
        $return = ['code' => false, 'message' => '', 'data' => ''];

        $exists_pur_list = $this->purchase_db->select('purchase_number')
            ->where_in('purchase_number',$purchase_numbers)
            ->get($this->table_name)
            ->result_array();
        $exists_pur_list = array_column($exists_pur_list,'purchase_number');
        $insert_pur_list = array_diff($purchase_numbers,$exists_pur_list);
        if(empty($insert_pur_list)){
            $return['message'] = '没有需要添加的数据';
            return $return;
        }

        $insert_surplus_list = $this->purchase_db
            ->select(
                'PO.purchase_number,'
                .'PO.purchase_type_id,'
                .'PO.source,'
                .'IFNULL(PCI.compact_number,"") AS compact_number,'
                .'SUM(POI.confirm_amount*purchase_unit_price) AS product_money,'
                .'0 AS charge_against_status,'
                .'NOW() AS create_time'
            )
            ->from('purchase_order AS PO')
            ->join('purchase_order_items AS POI', 'PO.purchase_number=POI.purchase_number', 'LEFT')
            ->join('purchase_order_pay_type AS POPT', 'POPT.purchase_number=PO.purchase_number', 'LEFT')
            ->join('purchase_compact_items AS PCI', 'PCI.purchase_number=PO.purchase_number', 'LEFT')
            ->where_in('PO.purchase_number', $insert_pur_list)
            ->group_by('PO.purchase_number')
            ->get()
            ->result_array();

//        print_r($this->purchase_db->last_query());exit;
//        print_r($insert_surplus_list);exit;

        if(empty($insert_surplus_list)){
            $return['message'] = '没有需要添加的数据';
            return $return;
        }
        $result = $this->purchase_db->insert_batch($this->table_name,$insert_surplus_list);
        if($result){
            $return['message'] = '执行成功，新增了：'.$result.'条数据';
            return $return;
        }else{
            $return['message'] = '执行失败';
            return $return;
        }
    }

    /**
     * 重新计算 采购单冲销结余
     * @param array $purchase_numbers  采购单号
     * @param int $type  1.入库记录，2.采购单状态变更，3.支付状态变更
     * @return array
     */
    public function recalculate_surplus($purchase_numbers,$type = 1){
        $return = ['code' => false, 'message' => '', 'data' => ''];

        if(is_string($purchase_numbers)) $purchase_numbers = [ $purchase_numbers ];// 转成数组


        // 记录计算日志 便于排查问题
        $this->load->library('mongo_db');
        $this->load->model('statement/Charge_against_records_model');
        $this->load->model('finance/Purchase_order_pay_model');
        $this->load->model('purchase/Purchase_order_determine_model');
        $this->load->model('abnormal/Report_loss_model');

        // 采购单总冲销金额 汇总
        $instock_charge_against_product_list = $this->Charge_against_records_model->get_charge_against_records_gather(['purchase_number' => $purchase_numbers,'charge_against_status' => CHARGE_AGAINST_STATUE_WAITING_PASS],1,'purchase_number');
        $refund_charge_against_product_list = $this->Charge_against_records_model->get_charge_against_records_gather(['purchase_number' => $purchase_numbers,'charge_against_status' => CHARGE_AGAINST_STATUE_WAITING_PASS],2,'purchase_number');

        // 采购单信息
        $order_info_list = $this->purchase_db
            ->select('purchase_number,pay_status,account_type')
            ->where_in('purchase_number',$purchase_numbers)
            ->group_by('purchase_number')
            ->get('purchase_order')
            ->result_array();

        // 采购单总入库金额、总入库重量
        $total_instock_price_list = $this->purchase_db
            ->select('A.purchase_number,SUM(A.instock_price) AS total_instock_price,SUM(A.instock_qty * B.product_weight) AS total_instock_weight')
            ->from('statement_warehouse_results AS A')
            ->join('product AS B','A.sku=B.sku','LEFT')
            ->where_in('A.purchase_number',$purchase_numbers)
            ->group_by('A.purchase_number')
            ->get()
            ->result_array();

        // 采购单运费优惠额 汇总
        $order_price_list = $this->purchase_db
            ->select('purchase_number,product_money,freight,discount,process_cost,commission,real_price')
            ->where_in('purchase_number',$purchase_numbers)
            ->group_by('purchase_number')
            ->get('purchase_order_pay_type')
            ->result_array();

        // 已付金额（采购单）
        $paid_price_list = $this->Purchase_order_pay_model->get_pay_total_by_purchase_number($purchase_numbers);


        // 已取消金额（取消未到货）
        $cancel_price_list = $this->Purchase_order_determine_model->get_total_cancellation($purchase_numbers,null);

        // 供应商总退款金额
        $real_refund_amount_list = $this->purchase_db
            ->select(
                'A.purchase_number,'
                .'SUM(A.original_pay_product_money) AS real_refund_product_money,'
                .'SUM(A.original_pay_freight) AS real_refund_freight,'
                .'SUM(A.original_pay_discount) AS real_refund_discount,'
                .'SUM(A.original_pay_process_cost) AS real_refund_process_cost,'
                .'SUM(A.original_pay_price) AS real_refund_amount'
            )
            ->from('purchase_order_cancel_to_receipt AS A')
            ->join('purchase_order_cancel AS B','A.cancel_id=B.id','INNER')
            ->where_in('A.purchase_number',$purchase_numbers)
            ->where_in('B.audit_status',[CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_YDC])
            ->group_by('A.purchase_number')
            ->get()
            ->result_array();


        // 已报损金额
        $loss_price_list = $this->Report_loss_model->get_loss_money_by_purchase_number($purchase_numbers);

        $order_info_list = arrayKeyToColumn($order_info_list,'purchase_number');
        $order_price_list = arrayKeyToColumn($order_price_list,'purchase_number');
        $instock_ca_product_list = array_column($instock_charge_against_product_list,'charge_against_product','purchase_number');
        $refund_ca_product_list = array_column($refund_charge_against_product_list,'charge_against_product','purchase_number');
        $refund_ca_process_cost_list = array_column($refund_charge_against_product_list,'charge_against_process_cost','purchase_number');
        $total_instock_price_list = arrayKeyToColumn($total_instock_price_list,'purchase_number');
        $paid_price_list = arrayKeyToColumn($paid_price_list,'purchase_number');
        $real_refund_amount_list = arrayKeyToColumn($real_refund_amount_list,'purchase_number');
        $loss_price_list = arrayKeyToColumn($loss_price_list,'pur_number');

        $update_list = [];
        foreach($purchase_numbers as $purchase_number){
            $update_data = [];
            $order_info = isset($order_info_list[$purchase_number])?$order_info_list[$purchase_number]:[];
            $order_price = isset($order_price_list[$purchase_number])?$order_price_list[$purchase_number]:[];
            $instock_ca_product = isset($instock_ca_product_list[$purchase_number])?$instock_ca_product_list[$purchase_number]:0;
            $refund_ca_product = isset($refund_ca_product_list[$purchase_number])?$refund_ca_product_list[$purchase_number]:0;
            $refund_ca_process_cost = isset($refund_ca_process_cost_list[$purchase_number])?$refund_ca_process_cost_list[$purchase_number]:0;
            $total_instock_price = isset($total_instock_price_list[$purchase_number])?$total_instock_price_list[$purchase_number]:[];
            $paid_price = isset($paid_price_list[$purchase_number])?$paid_price_list[$purchase_number]:0;
            $cancel_price = isset($cancel_price_list[$purchase_number])?$cancel_price_list[$purchase_number]:0;
            $real_refund_amount = isset($real_refund_amount_list[$purchase_number])?$real_refund_amount_list[$purchase_number]:0;
            $loss_price = isset($loss_price_list[$purchase_number])?$loss_price_list[$purchase_number]:0;

            $charge_against_product = $instock_ca_product + $refund_ca_product;

            if(empty($order_price) or empty($order_info)){// 数据异常的不处理
                continue;
            }

            $update_data['product_money'] = $order_price['product_money'];

            if($total_instock_price){
                $update_data['total_instock_price'] = $total_instock_price['total_instock_price'];
                $update_data['total_instock_weight'] = $total_instock_price['total_instock_weight'];
            }else{
                $update_data['total_instock_price'] = 0;
                $update_data['total_instock_weight'] = 0;
            }

            if($paid_price){
                $update_data['paid_product_money'] = $paid_price['paid_product_money'];
                $update_data['paid_freight'] = $paid_price['paid_freight'];
                $update_data['paid_discount'] = $paid_price['paid_discount'];
                $update_data['paid_process_cost'] = $paid_price['paid_process_cost'];
                $update_data['paid_commission'] = $paid_price['paid_commission'];// 代采佣金暂时不参与核销计算逻辑
                $update_data['paid_real_price'] = $paid_price['paid_real_price'];
            }else{
                $update_data['paid_product_money'] = 0;
                $update_data['paid_freight'] = 0;
                $update_data['paid_discount'] = 0;
                $update_data['paid_process_cost'] = 0;
                $update_data['paid_commission'] = 0;
                $update_data['paid_real_price'] = 0;
            }

            // 退款冲销金额算 已付金额
            $update_data['paid_product_money'] += $refund_ca_product;// 已付商品额 = 请款付款商品额 + 退款冲销商品额
            $update_data['paid_process_cost']  += $refund_ca_process_cost;
            $update_data['paid_real_price']    += $refund_ca_product + $refund_ca_process_cost;

            if($cancel_price){
                $update_data['cancel_product_money'] = $cancel_price['item_total_price'];
                $update_data['cancel_freight'] = $cancel_price['freight'];
                $update_data['cancel_discount'] = $cancel_price['discount'];
                $update_data['cancel_process_cost'] = $cancel_price['process_cost'];
                $update_data['cancel_real_price'] = $cancel_price['total_cancellation'];
            }else{
                $update_data['cancel_product_money'] = 0;
                $update_data['cancel_freight'] = 0;
                $update_data['cancel_discount'] = 0;
                $update_data['cancel_process_cost'] = 0;
                $update_data['cancel_real_price'] = 0;
            }

            if($real_refund_amount){
                $update_data['real_refund_product_money'] = $real_refund_amount['real_refund_product_money'];
                $update_data['real_refund_freight']       = $real_refund_amount['real_refund_freight'];
                $update_data['real_refund_discount']      = $real_refund_amount['real_refund_discount'];
                $update_data['real_refund_process_cost']  = $real_refund_amount['real_refund_process_cost'];
                $update_data['real_refund_amount']        = $real_refund_amount['real_refund_amount'];
            }else{
                $update_data['real_refund_product_money'] = 0;
                $update_data['real_refund_freight']       = 0;
                $update_data['real_refund_discount']      = 0;
                $update_data['real_refund_process_cost']  = 0;
                $update_data['real_refund_amount']        = 0;
            }

            if($loss_price){
                $update_data['loss_product_money'] = $loss_price['loss_product_money'];
                $update_data['loss_freight'] = $loss_price['loss_freight'];
                $update_data['loss_process_cost'] = $loss_price['loss_process_cost'];
                $update_data['loss_discount'] = 0;
                $update_data['loss_real_price'] = $loss_price['loss_totalprice'];
            }else{
                $update_data['loss_product_money'] = 0;
                $update_data['loss_freight'] = 0;
                $update_data['loss_process_cost'] = 0;
                $update_data['loss_discount'] = 0;
                $update_data['loss_real_price'] = 0;
            }

            // 采购单剩余可冲销商品金额：已付款商品金额 - 已冲销商品金额（入库记录） - 报损总商品额
            $update_data['surplus_able_charge_against_money'] = format_price($update_data['paid_product_money'] - $instock_ca_product - $update_data['loss_product_money']);
            if($update_data['surplus_able_charge_against_money'] < 0) $update_data['surplus_able_charge_against_money'] = 0;// 不能小于0

            // 采购金额冲销后余额：
            //  =实际采购金额-实际付款金额
            //  =采购商品额-取消商品额
            //   +采购运费+实际加工费
            //   -取消运费-取消加工费
            //   -采购单优惠额+取消优惠额
            //   -已付商品额-已付运费-已付加工费+已付优惠额
            //   +已退金额
            //
            //     正数，意味着我司需付供应商款
            //     负数，意味着供应商应退我司款项
            $update_data['real_price_after_charge_against'] = $order_price['product_money'] - $update_data['cancel_product_money']
                + $order_price['freight'] + $order_price['process_cost']
                - $update_data['cancel_freight'] - $update_data['cancel_process_cost']
                - ($order_price['discount'] - $update_data['cancel_discount'])
                - $update_data['paid_product_money'] - $update_data['paid_freight'] - $update_data['paid_process_cost'] + $update_data['paid_discount']
                + $update_data['real_refund_amount'];
            $update_data['real_price_after_charge_against'] = format_price($update_data['real_price_after_charge_against']);

            $update_data['realp_after_ca_product']      = $order_price['product_money'] - $update_data['cancel_product_money'] - $update_data['paid_product_money'] + $update_data['real_refund_product_money'];
            $update_data['realp_after_ca_freight']      = $order_price['freight'] - $update_data['cancel_freight'] - $update_data['paid_freight'] + $update_data['real_refund_freight'];
            $update_data['realp_after_ca_discount']     = $order_price['discount'] - $update_data['cancel_discount'] - $update_data['paid_discount'] + $update_data['real_refund_discount'];
            $update_data['realp_after_ca_process_cost'] = $order_price['process_cost'] - $update_data['cancel_process_cost'] - $update_data['paid_process_cost'] + $update_data['real_refund_process_cost'];


            // 入库金额冲销后余额：
            // =应付款金额-实际付款金额
            // =入库商品额
            //  +实际运费+实际加工费-实际优惠额-取消运费-取消加工费+取消优惠额
            //  +报损商品额 //+报损运费+报损加工费
            //  -已付金额-已付运费-已付加工费+已付优惠额
            //  +供应商退款金额
            //
            //     正数，意味着我司需付供应商款
            //     负数，意味着供应商应退我司款项
            $update_data['instock_price_after_charge_against'] =
                $update_data['total_instock_price']
                + $order_price['freight'] - $update_data['cancel_freight']
                + $order_price['process_cost'] - $update_data['cancel_process_cost']
                - ($order_price['discount'] - $update_data['cancel_discount'])

                + $update_data['loss_product_money'] //+ $update_data['loss_freight'] + $update_data['loss_process_cost']
                - $update_data['paid_product_money'] - $update_data['paid_freight'] - $update_data['paid_process_cost'] + $update_data['paid_discount']
                + $update_data['real_refund_amount'];
            $update_data['instock_price_after_charge_against'] = format_price($update_data['instock_price_after_charge_against']);

            $update_data['inp_after_ca_product']      = $update_data['total_instock_price'] + $update_data['loss_product_money'] - $update_data['paid_product_money'] + $update_data['real_refund_product_money'];
            $update_data['inp_after_ca_freight']      = $update_data['realp_after_ca_freight'];
            $update_data['inp_after_ca_discount']     = $update_data['realp_after_ca_discount'];
            $update_data['inp_after_ca_process_cost'] = $update_data['realp_after_ca_process_cost'];


            // 采购单剩余可申请商品金额
            // = 采购单总商品金额 - 已付商品额 - 已取消商品额 - 已退款冲销商品额（不需要该项：已付商品额 里面包含该值）
            $update_data['surplus_able_pay_product_money'] = $order_price['product_money'] - $update_data['paid_product_money'] - $update_data['cancel_product_money'];
            $update_data['surplus_able_pay_product_money'] = format_price($update_data['surplus_able_pay_product_money']);

            // 冲销是否完结（1.未完结，2.完结）
            // 冲销状态（0.无需冲销，1.未冲销，2.部分冲销，3.全部冲销）
            if($update_data['total_instock_price'] > 0 and intval($update_data['instock_price_after_charge_against'] * 100) == 0 and intval($update_data['real_price_after_charge_against'] * 100) == 0){
                $update_data['finished']              = 2;// 冲销是否完结（1.未完结，2.完结）
                $update_data['finished_time']         = date('Y-m-d H:i:s');// 冲销完结时间
                $update_data['charge_against_status'] = 3;// 全部冲销
            }elseif(bccomp($order_price['product_money'] , $update_data['cancel_product_money'],3) == 0){
                $update_data['finished']              = 2;// 冲销是否完结（1.未完结，2.完结）
                $update_data['finished_time']         = date('Y-m-d H:i:s');// 冲销完结时间
            }elseif($charge_against_product > 0){
                $update_data['charge_against_status'] = 2;// 部分冲销
            }else{
                $update_data['charge_against_status'] = 1;// 未冲销
            }

            if(bccomp($update_data['inp_after_ca_product'],0.00,2) == 0 and bccomp($update_data['realp_after_ca_product'],0.00,2) == 0){
                $update_data['product_finished']        = 2;// 冲销是否完结-商品金额（1.未完结，2.完结）
                $update_data['product_finished_time']   = date('Y-m-d H:i:s');// 冲销完结时间
            }
            if(bccomp($update_data['inp_after_ca_freight'],0.00,2) == 0 and bccomp($update_data['realp_after_ca_freight'],0.00,2) == 0){
                $update_data['freight_finished']        = 2;// 冲销是否完结-运费（1.未完结，2.完结）
            }
            if(bccomp($update_data['inp_after_ca_discount'],0.00,2) == 0 and bccomp($update_data['realp_after_ca_discount'],0.00,2) == 0){
                $update_data['discount_finished']        = 2;// 冲销是否完结-优惠额（1.未完结，2.完结）
            }
            if(bccomp($update_data['inp_after_ca_process_cost'],0.00,2) == 0 and bccomp($update_data['realp_after_ca_process_cost'],0.00,2) == 0){
                $update_data['process_cost_finished']    = 2;// 冲销是否完结-加工费（1.未完结，2.完结）
            }

            if(isset($order_info['pay_status']) and $order_info['pay_status'] == PAY_NONEED_STATUS){
                $update_data['charge_against_status'] = 0;// 无需冲销

                // 更新入库明细记录为无需冲销
                $this->purchase_db->where('purchase_number',$purchase_number)
                    ->update('statement_warehouse_results',['charge_against_status' => 0,'surplus_charge_against_amount' => 0]);
            }

            /**
             * 付款完结状态：初始值=未付。包含：1.未付、2.无需付、3.部分已付、4.已付4个数值
             *      已付款金额≠0，且可申请商品额≠0，那么则为-部分已付
             *      可申请商品额=0，那么则为-已付
             *      提交【无需付款】后，那么则为无需付款。
             */
            if(isset($order_info['pay_status']) and $order_info['pay_status'] == PAY_NONEED_STATUS){
                $pay_finish_status = 2;
            }elseif($update_data['paid_product_money'] > 0 and $update_data['surplus_able_pay_product_money'] > 0){
                $pay_finish_status = 3;
            }elseif($update_data['paid_product_money'] > 0 and $update_data['surplus_able_pay_product_money'] <= 0){
                $pay_finish_status = 4;
            }else{
                $pay_finish_status = 1;
            }
            $this->purchase_db->where('purchase_number',$purchase_number)->update('purchase_order',['pay_finish_status' => $pay_finish_status]);

            // 部分付款的需要刷新 应付款时间
            if($pay_finish_status == 3 and in_array($order_info['account_type'],[30,31,32,40,17,18,19,39])){
                $this->rediss->set_sadd('calc_pay_time_paid_service',$purchase_number);
                $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_calc_pay_time_paid_service');
            }

            // 调试信息-Start
            $insert_data = [];
            $insert_data['purchase_number']       = $purchase_number;
            $insert_data['create_time']           = date('Y-m-d H:i:s');
            $insert_data['type']                  = $type;
            $insert_data['operating_elements']    = json_encode($update_data);// 保存计算时的操作元素
            $this->mongo_db->insert('recalculateSurplusLog', $insert_data);
            // 调试信息-End

            $update_list[$purchase_number] = $update_data;
        }

        if(empty($update_list)){
            $return['message'] = '没有待执行的数据';
            return $return;
        }

//        print_r($update_list);exit;

        foreach($update_list as $update_key => $update_value){
            $this->purchase_db->where('purchase_number',$update_key)->update($this->table_name,$update_value,NULL,1);
        }


        if($type == 2 or $type == 3){// 采购单状态更新和付款状态更新时 更新合同单的付款状态
            $this->load->model('compact/Compact_model');
            $this->load->model('finance/purchase_order_pay_model');

            // 计算合同付款状态
            $compact_number_list = $this->purchase_db->select('A.compact_number,A.payment_status')
                ->from('purchase_compact AS A')
                ->join('purchase_compact_items AS B','A.compact_number=B.compact_number','LEFT')
                ->where_in('B.purchase_number',$purchase_numbers)
                ->group_by('A.compact_number')
                ->get()
                ->result_array();
            foreach($compact_number_list as $value){
                $amount_list = $this->Compact_model->calculate_compact_related_amount($value['compact_number']);
                if($amount_list['code']){
                    $amount_list = $amount_list['data'];
                    $result      = $this->purchase_order_pay_model->verify_order_status_enable_pay($value['compact_number']);
                    if($result === true){// 仅在合同单请款完结的情况下刷新付款状态
                        if($amount_list['paid_total_product_money'] > 0 and abs($amount_list['available_product_money']) > IS_ALLOWABLE_ERROR){
                            $this->purchase_db->where('compact_number', $value['compact_number'])->update('purchase_compact', ['payment_status' => PART_PAID]);
                        }elseif($amount_list['paid_total_product_money'] > 0 and abs($amount_list['available_product_money']) <= IS_ALLOWABLE_ERROR){
                            $this->purchase_db->where('compact_number', $value['compact_number'])->update('purchase_compact', ['payment_status' => PAY_PAID]);
                        }
                    }
                }
            }
        }

        if(!is_null($type)){
            // 已完结（采购单状态已完结，付款状态已完结）的覆盖 运费、优惠额、加工费、代采佣金
            $refresh_po_price_number_list = $this->purchase_db->select('A.purchase_number,A.purchase_order_status,A.pay_status,A.pay_finish_status')
                ->from('purchase_order AS A')
                ->where_in('A.purchase_number',$purchase_numbers)
                ->where('A.pay_finish_status',4)
                ->where_in('A.purchase_order_status',[PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,PURCHASE_ORDER_STATUS_CANCELED])
                ->get()
                ->result_array();
            if($refresh_po_price_number_list){
                // 覆盖 运费、优惠额、加工费、代采佣金
                $this->Purchase_order_pay_model->update_order_items_paid_price_detail(array_column($refresh_po_price_number_list,'purchase_number'));

                // 重新刷新冲销状态
                foreach ($refresh_po_price_number_list as $rf_po_number){
                    $this->recalculate_surplus($rf_po_number['purchase_number'],null);// 这里只能为null，否则死循环
                }
            }
        }

        if($update_list){
            $return['code'] = true;
            $return['message'] = '更新成功：'.count($update_list).' 条数据';
        }else{
            $return['message'] = '更新失败';
        }

        return $return;
    }


    /**
     * 重新计算 入库明细 入库单剩余可冲销金额
     * @param array $instock_batch    入库批次号
     * @return array
     */
    public function recalculate_inventory_item_surplus($instock_batch){
        $return = ['code' => false, 'message' => '', 'data' => ''];

        if(is_string($instock_batch)) $instock_batch = [ $instock_batch ];// 转成数组

        $this->load->model('statement/Charge_against_records_model');
        $this->load->model('statement/Purchase_inventory_items_model');

        $charge_against_instock_price_list = $this->Charge_against_records_model->get_charge_against_records_gather(['record_number' => $instock_batch],1,'record_number');


        $inventory_item_list = $this->Purchase_inventory_items_model->get_inventory_items(['instock_batch' => $instock_batch]);
        if(empty($inventory_item_list) or !isset($inventory_item_list[0])){
            $return['message'] = '参数错误，未匹配到入库明细记录';
            return $return;
        }

        $instock_batch_id = $inventory_item_list[0]['id'];

        // 已冲销商品额
        $charge_against_product = isset($charge_against_instock_price_list[0]['charge_against_product'])?$charge_against_instock_price_list[0]['charge_against_product']:0;

        // 入库单剩余可冲销金额
        $surplus_charge_against_amount = $inventory_item_list[0]['instock_price'] - $charge_against_product;

        // 冲销状态（0.无需冲销，1.未冲销，2.部分冲销，3.全部冲销）
        if($inventory_item_list[0]['instock_price'] <= 0 or $inventory_item_list[0]['pay_status'] == PAY_NONEED_STATUS){
            $charge_against_status = 0;// 入库数量=0 或 无需付款的无需冲销
        }elseif($charge_against_product <= 0){
            $charge_against_status = 1;
        }elseif($charge_against_product > 0 and $surplus_charge_against_amount > 0 ){
            $charge_against_status = 2;
        }elseif($charge_against_product > 0 and $surplus_charge_against_amount <= 0){
            $charge_against_status = 3;
        }else{
            $charge_against_status = 0;
        }

        $update_data = [
            'charge_against_status'         => $charge_against_status,
            'surplus_charge_against_amount' => $surplus_charge_against_amount
        ];

        $result = $this->purchase_db->where('id',$instock_batch_id)->update('statement_warehouse_results',$update_data,null,1);

        if($result){
            $return['code'] = true;
            $return['message'] = '更新成功';
        }else{
            $return['message'] = '更新失败';
        }

        return $return;
    }

    /**
     * 获取指定采购单号冲销结余数据
     * @param $purchase_numbers
     * @return array
     */
    public function get_surplus($purchase_numbers){
        $surplus_list = $this->purchase_db->where_in('purchase_number',$purchase_numbers)
            ->get($this->table_name)
            ->result_array();

        return $surplus_list;
    }

    /**
     * 获取采购单冲销汇总列表数据
     * @param array $params
     * @param int $offsets
     * @param int $limit
     * @param int $page
     * @param bool $export
     * @param string $action
     * @return array
     */
    public function get_summary_data_list($params = array(), $offsets = 1, $limit = 20, $page = 1, $export = false,$action = null)
    {
        //--获取权限控制数据--start
        $user_id = jurisdiction(); //当前登录用户ID
        $user_groups_types = user_group_check(); //当前登录用户ID的权限所属的组
        $role_name = get_user_role();//当前登录角色
        $data_role = getRole();
        $res_arr = array_intersect($role_name, $data_role);
        $authorization_user = [];
        if (!(!empty($res_arr) or $user_id === true)) {
            $authorization_user = $user_id;//数据权限采购员id集合
        }
        //--获取权限控制数据--end
        
        if(!empty($params['statement_number'])){
            if(stripos($params['statement_number'],' ') !== false){
                $statement_number_arr = array_filter(explode(' ',$params['statement_number']));
            }else{
                $statement_number_arr = [ $params['statement_number'] ];
            }
            $purchase_numbers = $this->purchase_db->select('purchase_number')
                ->where_in('statement_number',$statement_number_arr)
                ->group_by('purchase_number')
                ->get($this->table_purchase_statement_items)
                ->result_array();
            $purchase_numbers = array_column($purchase_numbers,'purchase_number');
        }

        $query = $this->purchase_db;
        $query->from("{$this->table_name} a");
        $query->join("{$this->table_purchase_order} b", 'b.purchase_number=a.purchase_number');
        $query->join("{$this->table_purchase_order_pay_type} c", 'c.purchase_number=a.purchase_number');


        //<editor-fold desc="查询条件代码块">
        //按采购来源查询
        $query->where('a.source', intval($params['source']));

        if(!empty($params['statement_number']) and isset($purchase_numbers)){// 对账单号查询转成采购单号查询
            if(!empty($purchase_numbers)){
                $purchase_numbers_list = array_chunk($purchase_numbers,1000);
                $query->group_start();
                foreach($purchase_numbers_list as $po_val){
                    $query->or_where_in("a.purchase_number",$po_val);
                }
                $query->group_end();
            }else{
                $query->where('1=0');
            }
        }
        //导出，按照选择记录查询
        if (isset($params['ids'])) {
            if (is_array($params['ids'])) {
                $query->where_in('a.id', $params['ids']);
            } else {
                $query->where('a.id', $params['ids']);
            }
        }

        if(isset($params['group_ids']) && !empty($params['group_ids'])){

            $query->where_in('b.buyer_id',$params['groupdatas']);
        }


        //按采购单号查询
        if (!empty($params['purchase_number'])) {
            if(stripos($params['purchase_number'],' ') !== false){
                $query->where_in('a.purchase_number',array_filter(explode(' ',$params['purchase_number'])));
            }else{
                $query->where('a.purchase_number',$params['purchase_number']);
            }
        }
        //按合同号查询
        if (!empty($params['compact_number'])) {
            if(stripos($params['compact_number'],' ') !== false){
                $compact_number_arr = array_filter(explode(' ',$params['compact_number']));
            }else{
                $compact_number_arr = [ $params['compact_number'] ];
            }
            $query->where_in('a.compact_number', $compact_number_arr);
        }
        //按下单时间范围查询
        if (!empty($params['orders_date_start']) && !empty($params['orders_date_end'])) {
            $query->where('b.waiting_time >=', $params['orders_date_start'] . ' 00:00:00');
            $query->where('b.waiting_time <=', $params['orders_date_end'] . ' 23:59:59');
        }
        //按是否完结查询
        if (!empty($params['finished'])) {
            $finish_type = isset($params['finish_type'])?$params['finish_type']:'finished';
            if($finish_type == 'product_finished'){
                $query->where('a.product_finished', $params['finished']);
            }else{
                $query->where('a.finished', $params['finished']);
            }
        }
        if(!empty($params['after_surplus_type']) and !empty($params['after_surplus_type_comparator']) and is_array($params['after_surplus_type_comparator'])){
            $after_surplus_type = $params['after_surplus_type'];
            $after_surplus_type_comparator = $params['after_surplus_type_comparator'];

            if(count($after_surplus_type_comparator) == 1){
                $after_surplus_type_comparator = current($after_surplus_type_comparator);
                $query->where("a.{$after_surplus_type} $after_surplus_type_comparator");
            }elseif(count($after_surplus_type_comparator) == 2){
                if(in_array('>0',$after_surplus_type_comparator) and in_array('=0',$after_surplus_type_comparator)){
                    $query->where("a.{$after_surplus_type} >= 0");
                }elseif(in_array('>0',$after_surplus_type_comparator) and in_array('<0',$after_surplus_type_comparator)){
                    $query->where("a.{$after_surplus_type} <> 0");
                }elseif(in_array('=0',$after_surplus_type_comparator) and in_array('<0',$after_surplus_type_comparator)){
                    $query->where("a.{$after_surplus_type} <= 0");
                }
            }
        }
        //按冲账完结时间范围查询
        if (!empty($params['finish_date_start']) && !empty($params['finish_date_end'])) {
            $query->where('a.finished_time >=', $params['finish_date_start'] . ' 00:00:00');
            $query->where('a.finished_time <=', $params['finish_date_end'] . ' 23:59:59');
        }
        //按商品额冲账完结时间范围查询
        if (!empty($params['product_finished_time_start']) && !empty($params['product_finished_time_end'])) {
            $query->where('a.product_finished_time >=', $params['product_finished_time_start'] . ' 00:00:00');
            $query->where('a.product_finished_time <=', $params['product_finished_time_end'] . ' 23:59:59');
        }
        //按审核时间范围查询
        if (!empty($params['audit_time_start'])) {
            $query->where('b.audit_time >=', $params['audit_time_start'] . ' 00:00:00');
        }
        if (!empty($params['audit_time_end'])) {
            $query->where('b.audit_time <=', $params['audit_time_end'] . ' 23:59:59');
        }
        //按供应商查询
        if (!empty($params['supplier_code'])) {
            $query->where('b.supplier_code', $params['supplier_code']);
        }

        if(isset($params['user_groups_types'])){
            $user_groups_types = $params['user_groups_types'];
        }
        if(is_array($user_groups_types)){
            $query->where_in('b.purchase_type_id', $user_groups_types);
        }
        //按业务线查询
        if (!empty($params['purchase_type_id'])) {
            if(is_array($params['purchase_type_id'])){
                $query->where_in('a.purchase_type_id',$params['purchase_type_id']);
            }else{
                $query->where('a.purchase_type_id',$params['purchase_type_id']);
            }
        }
        //按采购员查询
        if(!isset($params['swoole'])) {
            if (!empty($params['buyer_id'])) {
                if (!is_array($params['buyer_id'])) {
                    $params['buyer_id'] = [$params['buyer_id']];
                }
                //即有权限集合，又按照指定采购员查询，取两者交集
                if ($authorization_user) {
                    $buyer_id = array_intersect($authorization_user, $params['buyer_id']);
                    //如果两者没有交集，说明没有权限查询指定采购员数据，这里指定一个不存在的采购员
                    if (empty($buyer_id)) $buyer_id = ['xx'];
                    $query->where_in('b.buyer_id', $buyer_id);
                } else {
                    $query->where_in('b.buyer_id', $params['buyer_id']);
                }
            } elseif (!empty($authorization_user) ) {//按权限集合查询
                $query->where_in('b.buyer_id', $authorization_user);
            }
        }else{

            if( isset($params['swoole_userid'])){

                $query->where_in('b.buyer_id', $params['swoole_userid']);
            }
        }

        if(isset($params['groupdatas']) && !empty($params['groupdatas'])){

            $query->where_in('b.buyer_id',$params['groupdatas']);
        }

        // 付款状态
        if(isset($params['pay_status']) and !empty($params['pay_status'])){
            if(is_array($params['pay_status'])){
                $query->where_in('b.pay_status',$params['pay_status']);
            }else{
                $query->where('b.pay_status',$params['pay_status']);
            }
        }
        // 采购单状态
        if(isset($params['purchase_order_status']) and !empty($params['purchase_order_status'])){
            if(is_array($params['purchase_order_status'])){
                $query->where_in('b.purchase_order_status',$params['purchase_order_status']);
            }else{
                $query->where('b.purchase_order_status',$params['purchase_order_status']);
            }
        }
        // 取消未到货状态
        if (isset($params['cancel_audit_status']) and $params['cancel_audit_status']) {// 取消未到货状态
            $cancel_audit_status = $params['cancel_audit_status'];
            if(is_array($params['cancel_audit_status'])){
                $cancel_audit_status = implode(',',$params['cancel_audit_status']);
            }

            $query->where("b.purchase_number IN(
                SELECT tmp2.purchase_number
                FROM pur_purchase_order_cancel AS tmp1
                LEFT JOIN pur_purchase_order_cancel_detail AS tmp2 ON tmp1.id=tmp2.cancel_id
                WHERE tmp1.audit_status IN({$cancel_audit_status}) GROUP BY tmp2.purchase_number
                ) "
            );
        }

        //</editor-fold>

        $total_count = 0;
        if($action != 'get'){
            $count_qb = clone $query;
            //统计总数要加上前面筛选的条件
            $total_count = $count_qb->get()->num_rows();
        }
        if (!$export) {
            $count_qb2 = clone $query;
            $count_qb3 = clone $query;
            $count_qb4 = clone $query;
            //合同总个数
            $total_compact_number_count = $count_qb2->select('1')->group_by('a.compact_number')->get()->num_rows();
            //供应商总个数
            $total_supplier_count = $count_qb3->select('1')->group_by('b.supplier_code')->get()->num_rows();
            // 采购总金额、已付总金额、入库金额冲销后总金额、 采购金额冲销后总金额
            $count_result4 = $count_qb4->select(
                'sum(c.real_price) as total_real_price,'
                . 'sum(a.paid_real_price) as total_paid_real_price,'
                . 'sum(a.total_instock_price) as total_instock_price,'
                . 'sum(a.cancel_real_price) as total_cancel_real_price,'
                . 'sum(a.real_refund_amount) as total_real_refund_amount,'
                . 'sum(a.loss_real_price) as total_loss_real_price,'
                . 'sum(a.instock_price_after_charge_against) as total_ip_after_ca,'
                . 'sum(a.real_price_after_charge_against) as total_rp_after_ca')->get()->row_array();
        }
        //列表查询
        $query->select('a.*');
        $query->select('b.purchase_order_status,b.pay_status,b.supplier_name,b.supplier_code,b.buyer_name,b.buyer_id,b.waiting_time,b.audit_time');
        $query->select('c.real_price,c.product_money,c.freight,c.process_cost,c.discount,c.commission');
        $result = $query->limit($limit, $offsets)->order_by('a.id', 'DESC')->get()->result_array();
        //<editor-fold desc="数据转换">

        $purchase_number = !empty($result) ? array_column($result, 'purchase_number') : array();
        //获取取消未到货状态
        $cancel_info = $this->get_unfinish_cancel_info($purchase_number);
        //获取报损状态
        $report_loss_info = $this->_get_report_loss_status($purchase_number);
        // 获取对账单号
        $statement_nums_info = $this->_get_statement_nums_status($purchase_number);
        if(!empty($result)) {
            $buyerIds = array_unique(array_column($result, "buyer_id"));
            $buyerName = $this->User_group_model->getBuyerGroupMessage($buyerIds);
            $buyerName = array_column($buyerName, NULL, 'user_id');
        }else{
            $buyerName =[];
        }
        foreach ($result as $key => $item) {
            //业务线
            $result[$key]['purchase_type_cn'] = !is_null($item['purchase_type_id']) ? getPurchaseType($item['purchase_type_id']) : '';
            //采购单状态
            $result[$key]['purchase_order_status_cn'] = !is_null($item['purchase_order_status']) ? getPurchaseStatus($item['purchase_order_status']) : '';
            //取消未到货状态
            $result[$key]['cancel_status'] = isset($cancel_info[$item['purchase_number']]['audit_status']) ? $cancel_info[$item['purchase_number']]['audit_status'] : '-1';
            $result[$key]['cancel_status_cn'] = '-1' != $result[$key]['cancel_status'] ? get_cancel_status($result[$key]['cancel_status']) : '未申请取消';
            //取消未到货状态是否处理完毕(未处理完毕标记红色)
            if (in_array($result[$key]['cancel_status'], [CANCEL_AUDIT_STATUS_CG, CANCEL_AUDIT_STATUS_CF, CANCEL_AUDIT_STATUS_CGBH, CANCEL_AUDIT_STATUS_CFBH, CANCEL_AUDIT_STATUS_SCJT])) {
                $result[$key]['cancel_status_mark_red'] = 1;
            } else {
                $result[$key]['cancel_status_mark_red'] = 0;
            }
            //报损状态
            $result[$key]['report_loss_status'] = isset($report_loss_info[$item['purchase_number']]) ? $report_loss_info[$item['purchase_number']] : '-1';
            $result[$key]['report_loss_status_cn'] = '-1' != $result[$key]['report_loss_status'] ? getReportlossApprovalStatus($result[$key]['report_loss_status']) : '未申请报损';
            //报损状态是否处理完毕(未处理完毕标记红色)
            if (in_array($result[$key]['report_loss_status'], [REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT, REPORT_LOSS_STATUS_MANAGER_REJECTED, REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT, REPORT_LOSS_STATUS_FINANCE_REJECTED])) {
                $result[$key]['report_loss_status_mark_red'] = 1;
            } else {
                $result[$key]['report_loss_status_mark_red'] = 0;
            }
            $statement_number_list = isset($statement_nums_info[$item['purchase_number']]) ? $statement_nums_info[$item['purchase_number']] : null;
            $result[$key]['statement_number'] = $statement_number_list?explode(',',$statement_number_list):null;

            //采购单付款状态
            $result[$key]['pay_status_cn'] = !is_null($item['pay_status']) ? getPayStatus($item['pay_status']) : '';
            //采购单付款状态是否处理完毕(未处理完毕标记红色)
            $result[$key]['pay_status_mark_red'] = PAY_PAID == $item['pay_status'] ? 0 : 1;
            //是否完结状态
            $result[$key]['finished_cn'] = !is_null($item['finished']) ? getChargeAgainstFinishStatus($item['finished']) : '';
            $result[$key]['product_finished_cn'] = !is_null($item['product_finished']) ? getChargeAgainstFinishStatus($item['product_finished']) : '';
            $result[$key]['freight_finished_cn'] = !is_null($item['freight_finished']) ? getChargeAgainstFinishStatus($item['freight_finished']) : '';
            $result[$key]['discount_finished_cn'] = !is_null($item['discount_finished']) ? getChargeAgainstFinishStatus($item['discount_finished']) : '';
            $result[$key]['process_cost_finished_cn'] = !is_null($item['process_cost_finished']) ? getChargeAgainstFinishStatus($item['process_cost_finished']) : '';
            //采购来源
            $result[$key]['source_cn'] = 1 == $item['source'] ? '合同单' : '网采单';
            //是否显示‘冲销退款’操作按钮(采购单状态=‘作废订单待退款’，并且完结状态=未完结，并且取消未到货状态为‘财务驳回’或‘待上传截图’，
            //并且付款状态未在请款中（只有未请款或者未在请款中，才允许进行申请）)（1-显示，0-不显示）
            if (PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND == $item['purchase_order_status'] &&
                1 == $item['finished'] && in_array($result[$key]['cancel_status'], [CANCEL_AUDIT_STATUS_CFBH, CANCEL_AUDIT_STATUS_SCJT]) &&
                in_array($item['pay_status'], [PAY_UNPAID_STATUS,PAY_SOA_REJECT,PAY_MANAGER_REJECT, PAY_WAITING_MANAGER_REJECT, PAY_FINANCE_REJECT, PART_PAID, PAY_PAID, PAY_REJECT_SUPERVISOR, PAY_REJECT_MANAGER, PAY_REJECT_SUPPLY, PAY_GENERAL_MANAGER_REJECT])) {
                $result[$key]['show_ca_refund_btn'] = 1;
            } else {
                $result[$key]['show_ca_refund_btn'] = 0;
            }
            $result[$key]['groupName']                = isset($buyerName[$item['buyer_id']])?$buyerName[$item['buyer_id']]['group_name']:'';

        }
        //</editor-fold>

        //<editor-fold desc="表头字段">
        $key_table = array(
            $this->lang->myline('hx_purchase_number'),
            $this->lang->myline('hx_business_line'),
            $this->lang->myline('hx_pur_order_status'),
            $this->lang->myline('hx_order_pay_status'),
            $this->lang->myline('hx_supplier_name'),
            $this->lang->myline('hx_purchase_amount'),
            $this->lang->myline('hx_instock_price'),
            $this->lang->myline('hx_pay_amount'),
            $this->lang->myline('hx_cancel_amount'),
            $this->lang->myline('hx_refund_amount'),
            $this->lang->myline('hx_loss_amount'),
            $this->lang->myline('hx_instock_price_after_ca'),
            $this->lang->myline('hx_real_price_after_ca'),
            $this->lang->myline('hx_buyer'),
            $this->lang->myline('hx_orders_time'),
            $this->lang->myline('hx_finsh_time'),
            $this->lang->myline('hx_is_finish'),
        );
        //</editor-fold>


        if ($export) {
            $return_data = [
                'values' => $result,
                'total' => $total_count
            ];
        } else {
            $return_data = [
                'key' => $key_table,
                'values' => $result,
                'sum_data' =>
                    [
                        'total_compact_number_count' => isset($total_compact_number_count) ? (int)$total_compact_number_count : 0,
                        'total_supplier_count' => isset($total_supplier_count) ? (int)$total_supplier_count : 0,
                        'total_real_price' => isset($count_result4['total_real_price'])?$count_result4['total_real_price']:0,
                        'total_instock_price' => isset($count_result4['total_instock_price'])?$count_result4['total_instock_price']:0,
                        'total_paid_real_price' => isset($count_result4['total_paid_real_price'])?$count_result4['total_paid_real_price']:0,
                        'total_cancel_real_price' => isset($count_result4['total_cancel_real_price'])?$count_result4['total_cancel_real_price']:0,
                        'total_real_refund_amount' => isset($count_result4['total_real_refund_amount'])?$count_result4['total_real_refund_amount']:0,
                        'total_loss_real_price' => isset($count_result4['total_loss_real_price'])?$count_result4['total_loss_real_price']:0,
                        'total_ip_after_ca' => isset($count_result4['total_ip_after_ca'])?$count_result4['total_ip_after_ca']:0,
                        'total_rp_after_ca' => isset($count_result4['total_rp_after_ca'])?$count_result4['total_rp_after_ca']:0,
                    ],
                'page_data' => [
                    'total' => $total_count,
                    'offset' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total_count / $limit),
                ],
                'drop_down_box' => [
                    'buyer_dropdown' => getBuyerDropdown(),          //采购员下拉
                    'purchase_type' => getPurchaseType(),            //业务线下拉
                    'finish_type'  => ['product_finished' => '商品额冲账完结','finished' => '总额冲账完结'],
                    'finish_status' => getChargeAgainstFinishStatus(),//冲销是否完结
                    'pay_status_list' => getPayStatus(),
                    'purchase_order_status_list' => getPurchaseStatus(),
                    'cancel_audit_status' => get_cancel_status(),
                    'after_surplus_type'            => [
                        'inp_after_ca_product'   => '入库金额冲销后余额：商品额',
                        'instock_price_after_charge_against'     => '入库金额冲销后余额：总额',
                        'realp_after_ca_product' => '采购金额冲销后余额：商品额',
                        'real_price_after_charge_against'   => '采购金额冲销后余额：总额',
                    ],
                    'after_surplus_type_comparator' => ['>0' => '>0', '=0' => '=0', '<0' => '<0'],

                ]
            ];
        }
        return $return_data;
    }

    /**
     * 根据采购单号获取记录
     * @param $purchase_number
     * @return array
     */
    public function get_summary_data_row($purchase_number)
    {
        $result = $this->purchase_db->select('a.*,b.purchase_order_status,b.pay_status,b.supplier_name,b.supplier_code,b.buyer_name,b.buyer_id,b.waiting_time,c.real_price,
                c.product_money,c.freight,c.process_cost,c.discount,c.commission')
            ->from("{$this->table_name} a")
            ->join("{$this->table_purchase_order} b", 'b.purchase_number=a.purchase_number')
            ->join("{$this->table_purchase_order_pay_type} c", 'c.purchase_number=a.purchase_number')
            ->where('a.purchase_number', $purchase_number)
            ->get()->row_array();
        if (empty($result)) {
            return [];
        }
        //<editor-fold desc="数据转换">
        $purchase_number = !empty($result) ? [$result['purchase_number']] : array();
        //获取取消未到货状态
        $cancel_info = $this->get_unfinish_cancel_info($purchase_number);
        //获取报损状态
        $report_loss_info = $this->_get_report_loss_status($purchase_number);
        //业务线
        $result['purchase_type_cn'] = !is_null($result['purchase_type_id']) ? getPurchaseType($result['purchase_type_id']) : '';
        //采购单状态
        $result['purchase_order_status_cn'] = !is_null($result['purchase_order_status']) ? getPurchaseStatus($result['purchase_order_status']) : '';
        //取消未到货状态
        $result['cancel_status'] = isset($cancel_info[$result['purchase_number']]['audit_status']) ? $cancel_info[$result['purchase_number']]['audit_status'] : '';
        $result['cancel_status_cn'] = '' != $result['cancel_status'] ? get_cancel_status($result['cancel_status']) : '未申请取消';
        //报损状态
        $result['report_loss_status'] = isset($report_loss_info[$result['purchase_number']]) ? $report_loss_info[$result['purchase_number']] : '';
        $result['report_loss_status_cn'] = '' != $result['report_loss_status'] ? getReportlossApprovalStatus($result['report_loss_status']) : '未申请报损';
        //采购单付款状态
        $result['pay_status_cn'] = !is_null($result['pay_status']) ? getPayStatus($result['pay_status']) : '';
        //是否完结状态
        $result['finished_cn'] = !is_null($result['finished']) ? getChargeAgainstFinishStatus($result['finished']) : '';
        //采购来源
        $result['source_cn'] = (1 == $result['source']) ? '合同单' : '网采单';
        //</editor-fold>

        return $result;
    }

    /**
     * 获取采购单剩余可冲销商品金额
     * @param $purchase_number
     * @return array
     */
    public function get_able_ca_amount($purchase_number)
    {
        $query = $this->purchase_db;
        $query->select('surplus_able_pay_product_money');
        $query->from("{$this->table_name}");
        $query->where('purchase_number', $purchase_number);
        return $query->get()->row_array();
    }

    /**
     * 根据采购单号获取对应的最新取消未到货相关信息
     * @param array $purchase_number
     * @return array
     */
    public function get_unfinish_cancel_info(array $purchase_number)
    {
        if (empty($purchase_number)) return [];
        $purchase_number = array_unique(array_filter($purchase_number));
        $result = [];
        foreach (array_chunk($purchase_number, 200) as $chunk) {
            //子查询sql
            $sub_query = $this->purchase_db->select('purchase_number')->select_max('cancel_id')
                ->from("{$this->table_purchase_order_cancel_detail} a")
                ->join("{$this->table_purchase_order_cancel} b", 'a.cancel_id=b.id')
                ->where_in('a.purchase_number', $chunk)
                ->group_by('a.purchase_number')
                ->get_compiled_select();
            //获取数据
            $rows = $this->purchase_db->select('a.id,a.cancel_number,a.audit_status,b.purchase_number')
                ->from("{$this->table_purchase_order_cancel} a")
                ->join("({$sub_query}) b", 'a.id=b.cancel_id')
                ->get()->result_array();
            foreach ($rows as $key => $val) {
                $result[$val['purchase_number']]['audit_status'] = $val['audit_status'];
                $result[$val['purchase_number']]['cancel_number'] = $val['cancel_number'];
                $result[$val['purchase_number']]['cancel_id'] = $val['id'];
            }
        }
        return $result;
    }

    /**
     * 根据采购单号获取对应的最新取消未到货状态值
     * @param array $purchase_number
     * @return array
     */
    public function _get_report_loss_status(array $purchase_number)
    {
        if (empty($purchase_number)) return [];
        $purchase_number = array_unique(array_filter($purchase_number));
        $result = [];
        foreach (array_chunk($purchase_number, 200) as $chunk) {
            $rows = $this->purchase_db->select('pur_number,status')
                ->from("{$this->table_purchase_order_reportloss}")
                ->where_in('pur_number', $chunk)
                ->get()->result_array();
            $data_tmp = !empty($rows) ? array_column($rows, 'status', 'pur_number') : [];
            $result = array_merge($result, $data_tmp);
        }
        return $result;
    }

    /**
     * 根据采购单号获取对应的 对账单号
     * @param array $purchase_number
     * @return array
     */
    private function _get_statement_nums_status(array $purchase_number)
    {
        if (empty($purchase_number)) return [];
        $purchase_number = array_unique(array_filter($purchase_number));
        $result = [];
        foreach (array_chunk($purchase_number, 200) as $chunk) {
            $rows = $this->purchase_db->select('purchase_number,GROUP_CONCAT(DISTINCT statement_number) AS statement_number')
                ->from("{$this->table_purchase_statement_items}")
                ->where_in('purchase_number', $chunk)
                ->group_by('purchase_number')
                ->get()->result_array();
            $data_tmp = !empty($rows) ? array_column($rows, 'statement_number', 'purchase_number') : [];
            $result = array_merge($result, $data_tmp);
        }
        return $result;
    }

}