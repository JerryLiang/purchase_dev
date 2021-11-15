<?php

/**
 * Created by PhpStorm.
 * 请款单
 * User: Jolon
 * Date: 2019/01/10 0027 11:23
 */
class Purchase_order_pay_model extends Purchase_model
{

    protected $table_name = 'purchase_order_pay';
    const IS_AUTO_Y = 1;//是自动请款
    const IS_AUTO_N = 2;//非自动请款

    public function __construct()
    {
        parent::__construct();

        $this->load->model('finance/purchase_order_pay_detail_model', 'pay_detail'); // 请款单 SKU 明细
        $this->load->model('finance/payment_order_pay_model'); // 请款单 SKU 明细
        $this->load->helper('status_finance');
        $this->load->helper('status_order');
        $this->load->model('approval_model');
        $this->config->load('key_name', FALSE, TRUE);

        $this->load->model('Message_model');
        $this->load->library('rediss');
    }

    /**
     * 获取 付款单 - 已付款总金额
     * @author Jolon
     * @param string $pur_number 合同单|采购单号
     * @return mixed
     */
    public function get_pay_total_by_compact_number($pur_number,$purchase_numbers = [])
    {
        // 付款单 - 已付款总金额（pur_number 可以是合同号或采购单号）
        // product_money 商品金额，pay_price 请款金额，freight 运费，discount 优惠额
        if($purchase_numbers){// 合同单要按 采购单去找所以已付款的金额（对账单请款 导致）
            $paid_info = $this->get_pay_total_by_purchase_number($purchase_numbers);
            $paid_infoTmp = [
                'product_money' => format_price(array_sum(array_column($paid_info, 'paid_product_money'))),
                'freight'       => format_price(array_sum(array_column($paid_info, 'paid_freight'))),
                'discount'      => format_price(array_sum(array_column($paid_info, 'paid_discount'))),
                'process_cost'  => format_price(array_sum(array_column($paid_info, 'paid_process_cost'))),
                'pay_price'     => format_price(array_sum(array_column($paid_info, 'paid_real_price'))),
            ];
            $paid_info = $paid_infoTmp;
        }else{// 网采购、对账单
        $paid_info = $this->purchase_db->select('poy.pur_number,sum(poy.pay_price) as pay_price,sum(poy.product_money) as product_money,sum(poy.freight) as freight,sum(poy.discount) as discount,sum(poy.process_cost) as process_cost,sum(poy.commission) as commission')
            ->from('purchase_order_pay as poy')
            ->where('poy.pay_status', PAY_PAID)// 付款状态 51.已付款
            ->where('poy.pur_number', $pur_number)
            ->get()->row_array();
        }

        return $paid_info;
    }

    /**
     * 获取采购单 已付金额明细
     * @author Jolon
     * @param array $purchase_numbers 采购单号
     * @param array $pay_category 请款类型
     * @param array $requisition_method 请款方式
     * @return array
     */
    public function get_pay_total_by_purchase_number($purchase_numbers,$pay_category = [],$requisition_method = []){
        $this->purchase_db
            ->select(
                'poypd.purchase_number,'
                .'sum(poypd.product_money) as paid_product_money,'
                .'sum(poypd.freight) as paid_freight,'
                .'sum(poypd.discount) as paid_discount,'
                .'sum(poypd.process_cost) as paid_process_cost,'
                .'sum(poypd.commission) as paid_commission,'
                .'sum(poypd.pay_total) as paid_real_price'
            )
            ->from('purchase_order_pay as poy')
            ->join('purchase_order_pay_po_detail as poypd', 'poy.requisition_number=poypd.requisition_number', 'INNER')
            ->where('poy.pay_status', PAY_PAID)// 付款状态 51.已付款
            ->where_in('poypd.purchase_number', $purchase_numbers);

        if($pay_category){
            $this->purchase_db->where_in('poy.pay_category',$pay_category);
        }

        if($requisition_method){
            $this->purchase_db->where_in('poy.requisition_method',$requisition_method);
        }

        $paid_price_list = $this->purchase_db->group_by('poypd.purchase_number')
            ->get()
            ->result_array();

        return $paid_price_list;
    }

    /**
     * 获取 请款单记录
     * @author Jolon
     * @param string $pur_number 合同单|采购单号
     * @return mixed
     */
    public function get_pay_records_by_pur_number($pur_number)
    {
        $paid_info = $this->purchase_db->select('poy.*')
            ->from('purchase_order_pay as poy')
            ->where('poy.pur_number', $pur_number)
            ->get()
            ->result_array();

        return $paid_info;
    }

    /**
     * 获取 请款单记录
     * @author Jolon
     * @param string $requisition_number 申请单号
     * @return mixed
     */
    public function get_pay_records_by_requisition_number($requisition_number)
    {
        $pay_info = $this->purchase_db->select('poy.*')
            ->from('purchase_order_pay as poy')
            ->where('poy.requisition_number', $requisition_number)
            ->get()
            ->row_array();

        return $pay_info;
    }

    /**
     * 获取 采购单 - SKU 已付款总金额
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @param string $sku SKU
     * @return mixed
     */
    public function get_pay_total_by_sku($purchase_number, $sku)
    {
        // SKU 已付款总金额(pay_total 采购单 SKU 已付款金额)
        $paid_info = $this->purchase_db->select('poy.requisition_number,poyd.purchase_number,sku,sum(poyd.product_money) as product_money,sum(poyd.pay_total) as pay_total')
            ->from('purchase_order_pay as poy')
            ->join('purchase_order_pay_detail as poyd', 'poy.requisition_number=poyd.requisition_number', 'inner')
            ->where('poy.pay_status', PAY_PAID)// 付款状态 51.已付款
            ->where('poyd.purchase_number', $purchase_number)
            ->where('poyd.sku', $sku)
            ->get()->row_array();

        return $paid_info;
    }

//    /**
//     * 获取 采购单 - SKU 已付款总金额
//     * @author Jolon
//     * @param string $purchase_number 采购单号
//     * @return mixed
//     */
//    public function get_pay_total_by_purchase_number_group_by_sku($purchase_number)
//    {
//        // SKU 已付款总金额(pay_total 采购单 SKU 已付款金额)
//        $paid_info = $this->purchase_db
//            ->select('poy.requisition_number,poyd.purchase_number,sku,
//                sum(poyd.product_money) as product_money,
//                sum(poyd.freight) as freight,
//                sum(poyd.discount) as discount,
//                sum(poyd.process_cost) as process_cost,
//                sum(poyd.commission) as commission,
//                sum(poyd.pay_total) as pay_total'
//            )
//            ->from('purchase_order_pay as poy')
//            ->join('purchase_order_pay_detail as poyd', 'poy.requisition_number=poyd.requisition_number', 'inner')
//            ->where('poy.pay_status', PAY_PAID)// 付款状态 51.已付款
//            ->where('poyd.purchase_number', $purchase_number)
//            ->group_by('poyd.purchase_number,poyd.sku')
//            ->get()->result_array();
//
//        return $paid_info;
//    }

    /**
     * 获取 采购单 - SKU 已付款总金额
     * @author Jolon
     * @param string $pur_number 合同号/采购单号
     * @param array $paid_status 目标请款单状态
     * @return mixed
     */
    public function get_pay_total_detail($pur_number,$paid_status = [PART_PAID,PAY_PAID])
    {
        // SKU 已付款总金额(pay_total 采购单 SKU 已付款金额)
        $paid_info = $this->purchase_db->select('poyd.purchase_number,sum(poyd.product_money) as detail_product_money,
            sum(poyd.freight) as detail_freight,
            sum(poyd.discount) as detail_discount,
            sum(poyd.process_cost) as detail_process_cost,
            sum(poyd.commission) as detail_commission,
            sum(poyd.pay_total) as detail_pay_total'
        )
            ->from('purchase_order_pay as poy')
            ->join('purchase_order_pay_po_detail as poyd', 'poy.requisition_number=poyd.requisition_number', 'inner')
            ->where_in('poy.pay_status', $paid_status)
            ->where('poy.pur_number', $pur_number)
            ->group_by('poyd.purchase_number')
            ->get()
            ->result_array();

        return $paid_info;
    }

    /**
     * 获取 采购单 - SKU 已付款总金额
     * @author Jolon
     * @param array $purchase_numbers 采购单号
     * @param array $paid_status 目标请款单状态
     * @return mixed
     */
    public function get_pay_total_detail_by_purchase_number($purchase_numbers,$paid_status = [PART_PAID,PAY_PAID])
    {
        // SKU 已付款总金额(pay_total 采购单 SKU 已付款金额)
        $paid_info = $this->purchase_db->select('poyd.purchase_number,
            sum(poyd.product_money) as detail_product_money,
            sum(poyd.freight) as detail_freight,
            sum(poyd.discount) as detail_discount,
            sum(poyd.process_cost) as detail_process_cost,
            sum(poyd.commission) as detail_commission,
            sum(poyd.pay_total) as detail_pay_total'
        )
            ->from('purchase_order_pay as poy')
            ->join('purchase_order_pay_po_detail as poyd', 'poy.requisition_number=poyd.requisition_number', 'inner')
            ->where_in('poy.pay_status', $paid_status)
            ->where_in('poyd.purchase_number', $purchase_numbers)
            ->group_by('poyd.purchase_number')
            ->get()
            ->result_array();

        if(empty($paid_info)) return [];
        return arrayKeyToColumn($paid_info,'purchase_number');
    }

    /**
     * 更新 采购单 - SKU 已付款总金额
     * @author Jolon
     * @param array $purchase_numbers 采购单号
     * @param array $paid_status 目标请款单状态
     * @param string $update_column 需要更新的明细列
     * @return mixed
     */
    public function update_order_items_paid_price_detail($purchase_numbers,$update_column = null,$paid_status = [PART_PAID,PAY_PAID]){
        if(!is_array($purchase_numbers)) return false;

        $this->load->model('purchase/Purchase_order_determine_model');
        $this->load->model('abnormal/Report_loss_model');

        // 付款后 PO+SKU运费 = 已付款运费+取消运费+报损运费 之和 （优惠额、加工费同理）
        // 用有效的付款记录 去更新采购单明细 运费、优惠额、加工费
        $paid_detail_data   = $this->get_pay_total_detail_by_purchase_number($purchase_numbers,$paid_status);
        $have_pay_type_list = $this->purchase_db->where_in('purchase_number',$purchase_numbers)->get('purchase_order_pay_type')->result_array();
        $have_pay_type_list = arrayKeyToColumn($have_pay_type_list,'purchase_number');

        // 判断是否只覆盖代采佣金
        if( $update_column != 'commission' ){// 覆盖代采佣金时不覆盖其他金额
            // 已取消的运费、优惠额、加工费
            $order_cancel_list  = $this->Purchase_order_determine_model->get_total_cancellation($purchase_numbers,null);

            $surplusList = $this->purchase_db->select('purchase_number,real_refund_freight')
                ->where_in('purchase_number',$purchase_numbers)
                ->get('purchase_order_charge_against_surplus')
                ->result_array();
            $surplusList = arrayKeyToColumn($surplusList,'purchase_number');

            foreach($purchase_numbers as $purchase_number){
                // = 已付金额 + 取消金额
                $update_data = [
                    'freight'      => 0,
                    'discount'     => 0,
                    'process_cost' => 0,
                    'commission'   => 0,
                ];
                $have_pay_type = isset($have_pay_type_list[$purchase_number])?$have_pay_type_list[$purchase_number]:[];
                if(empty($have_pay_type)) continue;

                if(isset($paid_detail_data[$purchase_number])){
                    $update_data['freight'] += $paid_detail_data[$purchase_number]['detail_freight'];
                    $update_data['discount'] += $paid_detail_data[$purchase_number]['detail_discount'];
                    $update_data['process_cost'] += $paid_detail_data[$purchase_number]['detail_process_cost'];
                    $update_data['commission'] += $paid_detail_data[$purchase_number]['detail_commission'];
                }
                if(isset($order_cancel_list[$purchase_number])){
                    $update_data['freight'] += $order_cancel_list[$purchase_number]['freight'];
                    $update_data['discount'] += $order_cancel_list[$purchase_number]['discount'];
                    $update_data['process_cost'] += $order_cancel_list[$purchase_number]['process_cost'];
                }

                if(isset($surplusList[$purchase_number])){
                    $update_data['freight'] -= $surplusList[$purchase_number]['real_refund_freight'];
                }

                $logMessage = [];
                if(bccomp($update_data['freight'],$have_pay_type['freight'],3) != 0){
                    $logMessage[] = '运费由'.$have_pay_type['freight'].'变成'.$update_data['freight'];
                }
                if(bccomp($update_data['discount'],$have_pay_type['discount'],3) != 0){
                    $logMessage[] = '优惠额由'.$have_pay_type['discount'].'变成'.$update_data['discount'];
                }
                if(bccomp($update_data['process_cost'],$have_pay_type['process_cost'],3) != 0){
                    $logMessage[] = '加工费由'.$have_pay_type['process_cost'].'变成'.$update_data['process_cost'];
                }
                if(bccomp($update_data['commission'],$have_pay_type['commission'],3) != 0){
                    $logMessage[] = '代采佣金由'.$have_pay_type['commission'].'变成'.$update_data['commission'];
                }

                if($logMessage){// 更新pay_type表并记录日志
                    $update_data['real_price'] = $have_pay_type['product_money']
                        + $update_data['freight']
                        + $update_data['process_cost']
                        - $update_data['discount']
                        + $update_data['commission'];

                    $this->purchase_db->where('id',$have_pay_type['id'])
                        ->update('purchase_order_pay_type',$update_data,NULL,1);

                    operatorLogInsert([
                        'id' => $purchase_number,
                        'user' => 'admin',
                        'type' => 'purchase_order',
                        'content' => '采购单请款状态',
                        'detail' => '更新采购单信息'.implode(';',$logMessage)
                    ]);

                }
            }

        }else{

            foreach($purchase_numbers as $purchase_number){
                // = 已付金额
                $update_data = [
                    'commission'   => 0
                ];
                $have_pay_type = isset($have_pay_type_list[$purchase_number])?$have_pay_type_list[$purchase_number]:[];
                if(empty($have_pay_type)) continue;

                if(isset($paid_detail_data[$purchase_number])){
                    $update_data['commission'] += $paid_detail_data[$purchase_number]['detail_commission'];
                }

                $logMessage = [];
                if(bccomp($update_data['commission'],$have_pay_type['commission'],3) != 0){
                    $logMessage[] = '代采佣金由'.$have_pay_type['commission'].'变成'.$update_data['commission'];
                }

                if($logMessage){// 更新pay_type表并记录日志
                    $update_data['real_price'] = $have_pay_type['product_money']
                        + $have_pay_type['freight']
                        + $have_pay_type['process_cost']
                        - $have_pay_type['discount']
                        + $update_data['commission'];

                    $this->purchase_db->where('id',$have_pay_type['id'])
                        ->update('purchase_order_pay_type',$update_data,NULL,1);

                    operatorLogInsert([
                        'id' => $purchase_number,
                        'user' => 'admin',
                        'type' => 'purchase_order',
                        'content' => '采购单请款状态',
                        'detail' => '更新采购单信息'.implode(';',$logMessage)
                    ]);

                }
            }
        }

        return true;
    }




    /**
     * 网采单 - 创建请款单
     * @author Jolon
     * @param array $purchase_order_list 采购单数据
     * @param array $pay_data_list       采购单请款信息数据
     * @param bool|string $handle_user   操作人名称
     * @param int $is_auto   是否自动申请请款 1是2否
     * @return array
     * exp: $purchase_order_list = array(
     *         0=> array(
     *              'purchase_number' => '采购单号',
     *              'currency_code'   => '币种',
     *              'account_type'    => '结算方式',
     *              'pay_type'        => '支付方式',
     *              'source'          => '采购来源',
     *              'supplier_code'   => '供应商代码',
     *              'items_list' => array(
     *                  0 => array(
     *                      'purchase_number'  => '采购单号',
     *                      'sku'              => 'SKU',
     *                      'product_name'     => '产品名称',
     *                      'confirm_amount'   => '采购确认数量',
     *                      'purchase_unit_price' => '单价（含税）',
     *                      'freight'          => '运费',
     *                      'discount'         => '优惠',
     *                  ),
     *                  1 => array(
     *                      'purchase_number' => '采购单号',
     *                      'sku'             => 'SKU',
     *                      'product_name'    => '产品名称',
     *                      'confirm_amount'  => '采购确认数量',
     *                      'purchase_unit_price' => '单价（含税）',
     *                      'freight'         => '运费',
     *                      'discount'        => '优惠',
     *                 )
     *             )
     *         )
     *   )
     *
     * exp: $pay_data_list = array(
     *         'ABD123431' => array(
     *              'purchase_number' => 'ABD123431',
     *              'create_notice'   => '请款备注信息'
     *         ),
     *         'ABD123433' => array(
     *              'purchase_number' => 'ABD123433',
     *              'create_notice'   => '请款备注信息'
     *         ),
     *   )
     */
    public function network_pay_order_create($purchase_order_list,$pay_data_list = array(), $handle_user=false,$is_auto=2)
    {
        $return = ['code' => false, 'msg' => '', 'data' => ''];

        $this->load->helper('status_order');
        $this->load->model('purchase_order_pay_type_model', '', false, 'finance');
        $this->load->model('purchase/Purchase_order_determine_model');
        $this->load->model('statement/Charge_against_surplus_model');
        $this->purchase_db->trans_begin();
        $requisition_number_list = []; // 生成的请款单 单号列表
        try {
            foreach ($purchase_order_list as $purchase_order) {
                $pay_type = $this->purchase_order_pay_type_model->get_one($purchase_order['purchase_number']);
                $purchase_number = $purchase_order['purchase_number'];

                // 获取冲销结余信息
                $purSurplusList = $this->Charge_against_surplus_model->get_surplus([ $purchase_number ]);
                $purSurplusList = arrayKeyToColumn($purSurplusList,'purchase_number');
                $purSurplus = isset($purSurplusList[$purchase_number])?$purSurplusList[$purchase_number]:[];
                if(empty($purSurplus)){
                    throw new Exception('采购单号['.$purchase_number.']冲销结余信息未找到');
                }

                // 请款单主表
                $pay_main = [];
                $pay_main['pur_number'] = $purchase_number;

                // 请款类型
                if($purchase_order['warehouse_code'] == 'CG-YPC'){
                    $pay_main['pay_category'] = PURCHASE_PAY_CATEGORY_5;// 样品请款
                }elseif(in_array($purchase_order['warehouse_code'],['CXWLC','wuliaocang','HM_WL','WLCCX-ZZ'])){
                    $pay_main['pay_category'] = PURCHASE_PAY_CATEGORY_6;// 物料请款
                }else{
                    $pay_main['pay_category'] = PURCHASE_PAY_CATEGORY_3;
                }

                $pay_main['currency_code'] = !empty($purchase_order['currency_code']) ? $purchase_order['currency_code'] : 'RMB';
                $pay_main['pay_price'] = 0; // 请款金额（先设置为0，下面汇总后更新）
                $pay_main['settlement_method'] = $purchase_order['account_type'];
                $pay_main['pay_type'] = $purchase_order['pay_type'];
                $pay_main['purchase_name'] = $purchase_order['purchase_name'];
                $pay_main['source'] = SOURCE_NETWORK_ORDER;// 2.网采单
                $pay_main['supplier_code'] = $purchase_order['supplier_code'];
                $pay_main['create_notice'] = isset($pay_data_list[$purchase_number]['create_notice'])?$pay_data_list[$purchase_number]['create_notice']:'网采单请款';
                $pay_main['pay_ratio'] = '';
                $pay_main['js_ratio'] = isset($pay_type['settlement_ratio']) ? $pay_type['settlement_ratio'] : ''; // 结算比例
                $pay_main['purchase_account'] = isset($pay_type['purchase_acccount']) ? $pay_type['purchase_acccount'] : ''; // 采购账号
                $pay_main['pai_number'] = isset($pay_type['pai_number']) ? $pay_type['pai_number'] : ''; // 拍单号
                $pay_main['pay_status'] = PAY_WAITING_FINANCE_PAID; // 待财务付款（网采单请款无须经理审核）
                $pay_main['freight_desc'] = isset($pay_type['freight_note']) ? $pay_type['freight_note'] : ''; // 运费说明
                $pay_main['source_subject'] = SOURCE_SUBJECT_NETWORK_ORDER;// 来源主体（1合同 2网采 3对账单）
                $pay_main['purchase_type_id'] = $purchase_order['purchase_type_id'];// 采购来源
                $pay_main['need_pay_time'] = !empty($pay_type)?substr($pay_type['accout_period_time'],0,10):'0000-00-00';// 应付款时间
                $pay_main['is_auto'] = $is_auto;

                $main_result = $this->savePayData($pay_main);
                if ($main_result['code'] === false or empty($main_result['data'])) {
                    throw new Exception($main_result['msg']); // 请款单主表信息保存失败
                }
                $requisition_number = $main_result['data'];

                $pay_main_pay_price = $pay_main_pay_product_money = 0; // 请款金额 汇总

                // 请款单明细记录
                if ($purchase_order['items_list']) {
                    foreach ($purchase_order['items_list'] as $item) {
                        $pay_detail_main = [];
                        $pay_detail_main['requisition_number'] = $requisition_number;
                        $pay_detail_main['purchase_number'] = $purchase_number;
                        $pay_detail_main['sku'] = $item['sku'];
                        $pay_detail_main['product_name'] = $item['product_name'];
                        $pay_detail_main['purchase_amount'] = $item['confirm_amount'];
                        $pay_detail_main['purchase_unit_price'] = $item['purchase_unit_price']; // 含税价

                        // 请款单明细不计算运费、优惠、加工费
                        $pay_detail_main['freight'] = 0;
                        $pay_detail_main['discount'] = 0;
                        $pay_detail_main['process_cost'] = 0;

                        $product_money = $pay_detail_main['purchase_amount'] * $pay_detail_main['purchase_unit_price'];
                        $order_cancel_list = $this->Purchase_order_determine_model->get_order_cancel_list($purchase_number,$item['sku']); //po+sku 取消数量

                        // 请款金额 汇总
                        $cacel_qt = isset($order_cancel_list[$purchase_number."-".$item['sku']])?$order_cancel_list[$purchase_number."-".$item['sku']]:0;

                        $pay_detail_main['product_money']   = format_price($product_money-($cacel_qt*$item['purchase_unit_price']));// 请款商品额明细
                        $pay_detail_main['pay_total']       = $pay_detail_main['product_money'];// 请款金额明细

                        // 汇总采购单请款商品金额、请款总金额
                        $pay_main_pay_product_money += $pay_detail_main['product_money'];
                        $pay_main_pay_price         += $pay_detail_main['product_money'];

                        $item_result = $this->savePayDetailData($pay_detail_main);
                        if (empty($item_result)) {
                            throw new Exception('创建网采单-请款明细失败');
                        }           
                    }
                    
                } else {
                    throw new Exception('创建网采单-请款单-采购单明细缺失');
                }


                // 请款单PO请款明细
                $pay_po_detail = [
                    'requisition_number' => $requisition_number,
                    'purchase_number' => $purchase_number,
                    'freight' => format_price($pay_type['freight'] - $purSurplus['cancel_freight']),
                    'discount' => format_price($pay_type['discount'] - $purSurplus['cancel_discount']),
                    'process_cost' => format_price($pay_type['process_cost'] - $purSurplus['cancel_process_cost']),
                    'commission' => format_price($pay_type['commission'] - $purSurplus['cancel_commission']),
                    'product_money' => $pay_main_pay_product_money
                ];
                // 请款总金额 = 请款总商品金额+请款运费-请款优惠额+请款加工费+请款代采佣金
                $pay_po_detail['pay_total'] = format_price(
                    $pay_po_detail['product_money']
                    + $pay_po_detail['freight']
                    - $pay_po_detail['discount']
                    + $pay_po_detail['process_cost']
                    + $pay_po_detail['commission']
                );

                // 网采单请款主表数据同 PO请款明细
                $pay_main_pay_update = [// 请款总金额
                    'pay_price' => $pay_po_detail['pay_total'],
                    'product_money' => $pay_po_detail['product_money'],
                    'freight' => $pay_po_detail['freight'],
                    'discount' => $pay_po_detail['discount'],
                    'process_cost' => $pay_po_detail['process_cost'],
                    'commission' => $pay_po_detail['commission']
                ];


                if($pay_main_pay_update['pay_price'] <= 0){
                    throw new Exception('创建网采单-请款单-请款金额 <=0 提交失败');
                }


                $item_result = $this->savePayPoDetailData($pay_po_detail);
                if (empty($item_result)) {
                    throw new Exception('创建网采单-请款PO明细失败');
                }

                // 更新请款单-请款总金额
                $result_1 = $this->purchase_db->where('requisition_number', $requisition_number)->update($this->table_name, $pay_main_pay_update);
                if (empty($result_1)) {
                    throw new Exception('创建网采单-请款单-更新请款单请款总金额失败');
                }
                $result_2 = $this->purchase_db->where('purchase_number', $purchase_number)->update('purchase_order', ['pay_status' => $pay_main['pay_status']]);
                if (empty($result_2)) {
                    throw new Exception('创建网采单-请款单-更新采购单请款状态失败');
                } else {
                    $oli = [
                        'id' => $purchase_number,
                        'type' => 'purchase_order',
                        'content' => '采购单请款状态',
                        'detail' => '更新采购单付款状态为【' . getPayStatus($pay_main['pay_status']) . '】'
                    ];
                    if($handle_user)$oli['user'] = $handle_user;
                    operatorLogInsert($oli);
                }
                $requisition_number_list[] = $requisition_number;
            }
            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('创建请款单事务提交出错');
            } else {
                $this->purchase_db->trans_commit();
            }

            $return['code'] = true;
            $return['data'] = $requisition_number_list;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * 合同单 - 创建请款单
     * @author Jolon
     * @param array $compact_data 合同单数据
     * @return array
     * exp: Array
     * (
     * [post_data] => Array
     * (
     * [pay_type] => 2
     * [requisition_method] =>
     * [pay_ratio] =>
     * [product_money] =>
     * [freight] => 12
     * [discount] => 0.323
     * [pay_price] => 12.323
     * [purchase_account] =>
     * [pai_number] =>
     * [create_notice] =>
     * )
     *
     * [from_data] => Array
     * (
     * [compact_number] => PO-HT000028
     * [invoice_looked_up] => YIBAI TECHNOLOGY LTD
     * [receive_unit] => 许二刚
     * [payment_platform_branch] => 中国工商银行浙江省义乌市支行
     * [account] => 6212261208010786992
     * [pay_date] => 2019年01月12日
     * [pay_price] => 12.323
     * [pay_price_cn] => 壹拾贰元叁角贰分叁厘
     * [check_department] => 总经办
     * [payment_reason] =>
     * )
     *
     * [compact_number] => PO-HT000028
     * )
     */
    public function compact_pay_order_create($compact_data)
    {
        $return = ['code' => false, 'msg' => '', 'data' => ''];

        $this->load->helper('status_order');
        $this->load->helper('status_finance');
        $this->load->model('compact_model', '', false, 'compact');
        $this->load->model('purchase_order_model', '', false, 'purchase');
        $this->load->model('purchase_pay_requisition_model', '', false, 'finance');
        $this->load->model('purchase_suggest_map_model', '', false, 'purchase_suggest');

        $compact_number = $compact_data['compact_number'];

        $this->purchase_db->trans_begin();
        try {
            $post_data = $compact_data['post_data'];// 请款数据
            $from_data = $compact_data['from_data'];// 付款申请书数据

            $compact = $this->compact_model->get_compact_one($compact_number);// 获取合同数据
            if(empty($compact) or empty($compact['items_list'])){
                 throw new Exception('合同单不存在');
            }

            // 合同请款的，应付款时间=合同内所有备货单的应付款时间最晚的那个
            $max_need_pay_time = $this->purchase_db->select('max(accout_period_time) as need_pay_time')
                ->where_in('purchase_number',array_column($compact['items_list'],'purchase_number'))
                ->get('purchase_order_pay_type')
                ->row_array();

            // 请款单主表
            $pay_main = [];
            $pay_main['pur_number'] = $compact_number;
            $pay_main['currency_code'] = 'RMB';
            $pay_main['pay_price'] = $post_data['pay_price'];// 请款金额（先设置为0，下面汇总后更新）
            $pay_main['requisition_method'] = $post_data['requisition_method'];// 请款方式
            $pay_main['settlement_method'] = $compact['settlement_method'];
            $pay_main['pay_type'] = isset($post_data['pay_type'])?$post_data['pay_type']: $compact['pay_type'];
            $pay_main['purchase_name'] = $compact_data['purchase_name'];
            $pay_main['source'] = SOURCE_COMPACT_ORDER;// 1.合同单
            $pay_main['supplier_code'] = $compact['supplier_code'];
            $pay_main['create_notice'] = $post_data['create_notice'];
            $pay_main['pay_ratio'] = $post_data['pay_ratio'];// 当前请款比例
            $pay_main['js_ratio'] = $compact['settlement_ratio'];// 结算比例
            $pay_main['purchase_account'] = $post_data['purchase_account'];// 采购账号
            $pay_main['pai_number'] = $post_data['pai_number'];// 拍单号
            $pay_main['pay_status'] = PAY_WAITING_SOA_REVIEW;// 待对账主管审核
            $pay_main['pay_price'] = $post_data['pay_price'];// 请款金额（实际金额）
            $pay_main['product_money'] = $post_data['product_money'];// 商品金额
            $pay_main['freight'] = $post_data['freight'];// 运费
            $pay_main['discount'] = $post_data['discount'];// 优惠额
            $pay_main['process_cost'] = $post_data['process_cost'];// 加工费
            $pay_main['compact_url'] = $post_data['compact_url'];// 上传合同扫描件
            $pay_main['freight_desc'] = $post_data['freight_desc'];// 运费说明
            $pay_main['abstract_remark'] = $from_data['abstract_remark'];// 请款摘要信息
            // 计算请款类型
            $pay_main['pay_category'] = $this->compact_pay_category($compact['is_drawback'],$pay_main['settlement_method'],$pay_main['pay_ratio'],$pay_main['pay_price'],$pay_main['freight'],$pay_main['discount'],$pay_main['requisition_method']);
            $pay_main['source_subject'] = SOURCE_SUBJECT_COMPACT_ORDER;// 来源主体（1合同 2网采 3对账单）
            $pay_main['purchase_type_id'] = $compact['source'];// 采购来源
            $pay_main['need_pay_time'] = !empty($max_need_pay_time)?$max_need_pay_time['need_pay_time']:'0000-00-00 00:00:00';
    

            $main_result = $this->savePayData($pay_main);
            if ($main_result['code'] === false or empty($main_result['data'])) {
                throw new Exception($main_result['msg']);// 请款单主表信息保存失败
            }

          //  if( $pay_main['pay_category'] != PURCHASE_PAY_CATEGORY_4){
                // 更新采购单请款状态
                $result_2 = $this->purchase_db->where('compact_number', $compact_number)->update('purchase_compact', ['payment_status' => $pay_main['pay_status']]);
                if(empty($result_2)){
                    throw new Exception('创建合同单-更新合同付款状态-待经理审核失败');
                }
          //  }

            $requisition_number = $main_result['data'];

            // 请款单明细记录
            if ($compact['items_list']) {
                $freight = $post_data['freight'];// 请款运费
                $discount = $post_data['discount'];// 请款优惠额
                $item_data_list = $post_data['item_data_list'];// 提交的备货单维度金额明细
                $item_data_list = json_decode($item_data_list,true);
                $po_data_list = $post_data['po_data_list'];// 提交的PO维度金额明细
                $po_data_list = json_decode($po_data_list,true);
                foreach ($compact['items_list'] as $compact_item) {
                    $purchase_number = $compact_item['purchase_number'];
                    $purchase_order = $this->purchase_order_model->get_one($purchase_number);

                    // 采购单明细
                    foreach ($purchase_order['items_list'] as $item) {
                        $demand_info = $this->purchase_suggest_map_model->get_one_by_sku($purchase_number,$item['sku'],null,true);// 获取采购单+SKU与备货单的关联
                        if(empty($demand_info)) throw new Exception('获取采购单+SKU与备货单的关联失败');

                        $demand_number = $demand_info['demand_number'];
                        if(!isset($item_data_list[$demand_number]) or empty($item_data_list[$demand_number])) throw new Exception('提交的备货单维度金额明细异常');
                        $now_item_data = $item_data_list[$demand_number];

                        $pay_detail_main = [];
                        $pay_detail_main['requisition_number'] = $requisition_number;
                        $pay_detail_main['purchase_number'] = $purchase_number;
                        $pay_detail_main['sku'] = $item['sku'];
                        $pay_detail_main['product_name'] = $item['product_name'];
                        $pay_detail_main['purchase_amount'] = $item['confirm_amount'];
                        $pay_detail_main['purchase_unit_price'] = $item['purchase_unit_price'];// 含税价

                        // 保存每个SKU的请款金额明细
                        $pay_detail_main['product_money'] = $now_item_data['item_product_money'];
                        $pay_detail_main['pay_total'] = $now_item_data['item_product_money'];

                        $item_result = $this->savePayDetailData($pay_detail_main);
                        if (empty($item_result)) {
                            throw new Exception('创建合同单-请款明细失败');
                        }
                    }


                    // 请款单PO明细
                    if(!isset($po_data_list[$purchase_number]) or empty($po_data_list[$purchase_number])) throw new Exception('提交的PO维度金额明细异常');
                    $now_po_data = $po_data_list[$purchase_number];

                    $pay_po_detail_main = [];
                    $pay_po_detail_main['requisition_number'] = $requisition_number;
                    $pay_po_detail_main['purchase_number'] = $purchase_number;
                    $pay_po_detail_main['product_money'] = $now_po_data['po_product_money'];
                    $pay_po_detail_main['freight'] = $now_po_data['po_freight'];
                    $pay_po_detail_main['discount'] = $now_po_data['po_discount'];
                    $pay_po_detail_main['process_cost'] = $now_po_data['po_process_cost'];
                    $pay_po_detail_main['pay_total'] = $now_po_data['po_pay_price'];

                    $pay_po_result = $this->savePayPoDetailData($pay_po_detail_main);
                    if (empty($pay_po_result)) {
                        throw new Exception('创建合同单-请款PO明细失败');
                    }


                    // 更新采购单请款状态
                    $result_2 = $this->purchase_db->where('purchase_number', $purchase_number)->update('purchase_order', ['pay_status' => $pay_main['pay_status']]);
                    if (empty($result_2)) {
                        throw new Exception('创建合同单-请款单-更新采购单请款状态失败');
                    } else {
                        operatorLogInsert(
                            ['id'      => $purchase_number,
                             'type'    => 'purchase_order',
                             'content' => '采购单请款状态',
                             'detail'  => '更新采购单付款状态为【'.getPayStatus($pay_main['pay_status']).'】'
                            ]);
                    }

                    //付款状态 推送至计划系统
                    $push_pay_status_to_plan_key = 'push_pay_status_to_plan';
                    $this->rediss->set_sadd($push_pay_status_to_plan_key,$purchase_number);
                    $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$push_pay_status_to_plan_key);
                    
                }
                
                //$this->compact_model->refresh_compact_data($compact_number);// 付款后更新


            } else {
                throw new Exception('创建合同单-请款单-采购单明细缺失');
            }
            // 付款申请书
            $pay_requisition = [
                'compact_number' => $compact_number,
                'requisition_number' => $requisition_number,
                'invoice_looked_up' => $from_data['invoice_looked_up'],
                'receive_unit' => $from_data['receive_unit'],
                'receive_account' => $from_data['account'],
                'payment_platform_branch' => $from_data['payment_platform_branch'],
                'pay_price' => $from_data['pay_price'],
                'pay_price_cn' => $from_data['pay_price_cn'],
                'payment_reason' => $from_data['payment_reason'],
            ];
            // 36253 采购单运费请款时，当支付方式=线下境外，请款方式=运费请款 时 公司主体=香港易佰
            if($pay_main['requisition_method'] == PURCHASE_REQUISITION_METHOD_HAND && $pay_main['pay_type'] == PURCHASE_PAY_TYPE_PRIVATE){
                $pay_requisition['invoice_looked_up'] = 'YIBAI TECHNOLOGY LTD';
            }
            $result_pay = $this->purchase_pay_requisition_model->create($pay_requisition);
            if (empty($result_pay)) {
                throw new Exception('创建合同单-请款单-付款申请书保存失败');
            }
            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('创建请款单事务提交出错');
            } else {
                if($pay_main['pay_status']==20){
                    $this->load->model('Ding_talk_model','ding_talk'); // 钉钉接口
                    $param = array(
                        'role_number' => $pay_main['pay_status'],
                        'msg' => '您有一条新的待审核的请款，请款单号' . $requisition_number . '，请款总额为' . $pay_main['pay_price'] . '元，运费为' . $pay_main['freight'] . '元，请前往采购系统及时处理！'.date('Y-m-d H:i:s'),
                    );
                    $this->ding_talk->pushDingTalkInfo($param);
                }
                $this->purchase_db->trans_commit();
            }
            $return['code'] = true;
            $return['data'] = [$requisition_number];
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;

    }

    /***
     * 请款单排他队列
     */
    public function exclude_pay_order_create($purchase, $is_create = false)
    {
        $handle_ids = false;
        $hash_doc = 'EXCLUDE_PAY_ORDER_CREATE';
        $time = time();
        $exclude_time = 30; // 30 秒内不能再次生成

        if($is_create){
            $this->rediss->addHashData($hash_doc, $purchase, $time);
            return true;
        }

        $has_is = $this->rediss->getHashData($hash_doc, $purchase);
        try{
            $has_is = json_decode($has_is, true);
            $has_is = (int)$has_is[0];
        }catch (Exception $e){}
        if(!$has_is || empty($has_is) || !is_numeric($has_is))$has_is = 0;
        if($has_is > 0 && ($time - $has_is) < $exclude_time){
            $handle_ids = true;
        }

        return $handle_ids;
    }


    /**
     * 对账单 - 创建请款单
     * @author Jolon
     * @param array $statement_data 对账单数据
     * @return array
     */
    public function statement_pay_order_create($statement_data)
    {
        $return = ['code' => false, 'msg' => '', 'data' => ''];

        $this->load->helper('status_order');
        $this->load->helper('status_finance');
        $this->load->model('statement/Purchase_statement_model');
        $this->load->model('statement/Purchase_inventory_items_model');
        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase/purchase_order_items_model');
        $this->load->model('finance/purchase_pay_requisition_model');
        $this->load->model('purchase_suggest/purchase_suggest_map_model');

        $statement_number = $statement_data['statement_number'];

        $this->purchase_db->trans_begin();
        try {
            $post_data = $statement_data['post_data'];// 请款数据
            $from_data = $statement_data['from_data'];// 付款申请书数据

            $statement_data = $this->Purchase_statement_model->get_statement($statement_number);// 获取合同数据
            if(empty($statement_data)){
                throw new Exception('对账单不存在');
            }
            // 请款单主表
            $pay_main = [];
            $pay_main['pur_number'] = $statement_number;
            $pay_main['currency_code'] = 'RMB';
            $pay_main['pay_price'] = $post_data['pay_price'];// 请款金额（先设置为0，下面汇总后更新）
            $pay_main['requisition_method'] = $post_data['requisition_method'];// 请款方式
            $pay_main['settlement_method'] = $statement_data['settlement_method'];
            $pay_main['pay_type'] = isset($post_data['pay_type'])?$post_data['pay_type']: $statement_data['pay_type'];
            $pay_main['purchase_name'] = $statement_data['purchase_name'];
            $pay_main['source'] = SOURCE_COMPACT_ORDER;// 1.合同单
            $pay_main['supplier_code'] = $statement_data['supplier_code'];
            $pay_main['create_notice'] = $post_data['create_notice'];
            $pay_main['pay_ratio'] = $post_data['pay_ratio'];// 当前请款比例
            $pay_main['js_ratio'] = '100%';// 结算比例
            $pay_main['purchase_account'] = '';// 采购账号
            $pay_main['pai_number'] = '';// 拍单号
            $pay_main['pay_status'] = PAY_WAITING_SOA_REVIEW;// 待对账主管审核
            $pay_main['pay_price'] = $post_data['pay_price'];// 请款金额（实际金额）
            $pay_main['product_money'] = $post_data['product_money'];// 商品金额
            $pay_main['freight'] = $post_data['freight'];// 运费
            $pay_main['discount'] = $post_data['discount'];// 优惠额
            $pay_main['process_cost'] = $post_data['process_cost'];// 加工费
            $pay_main['commission'] = $post_data['commission'];// 代采佣金
            $pay_main['commission_percent'] = $post_data['commission_percent'];// 代采佣金占比
            $pay_main['compact_url'] = $post_data['compact_url'];// 上传合同扫描件
            $pay_main['freight_desc'] = $post_data['freight_desc'];// 运费说明
            $pay_main['abstract_remark'] = $from_data['abstract_remark'];// 请款摘要信息
            $pay_main['pay_category'] = PURCHASE_PAY_CATEGORY_3;// 请款类型
            $pay_main['source_subject'] = SOURCE_SUBJECT_STATEMENT_ORDER;// 来源主体（1合同 2网采 3对账单）
            $pay_main['purchase_type_id'] = $statement_data['purchase_type_id'];// 采购来源
            $pay_main['need_pay_time'] = $statement_data['accout_period_time'];// 应付款时间

            // 操作用户参数
            if(isset($post_data['applicant'])) $pay_main['applicant'] = $post_data['applicant'];
            if(isset($post_data['application_time'])) $pay_main['application_time'] = $post_data['application_time'];
            if(isset($post_data['create_user_name'])) $pay_main['create_user_name'] = $post_data['create_user_name'];
            if(isset($post_data['is_auto'])) $pay_main['is_auto'] = $post_data['is_auto'];

            $main_result = $this->savePayData($pay_main);
            if ($main_result['code'] === false or empty($main_result['data'])) {
                throw new Exception($main_result['msg']);// 请款单主表信息保存失败
            }

            // 更新采购单请款状态
            $result_2 = $this->purchase_db->where('statement_number', $statement_number)->update('purchase_statement', ['statement_pay_status' => $pay_main['pay_status']]);
            if(empty($result_2)){
                throw new Exception('创建合同单-更新合同付款状态-待经理审核失败');
            }

            // 更新 对账单对账汇总数据
            $pay_items_list = json_decode($post_data['pay_items_list'],true);
            foreach($pay_items_list as $po_number => $po_pay_item){
                $update_arr = [
                    'pay_product_money' => $po_pay_item['product_money'],
                    'pay_freight'       => $po_pay_item['freight'],
                    'pay_process_cost'  => $po_pay_item['process_cost'],
                    'pay_discount'      => $po_pay_item['discount'],
                    'pay_commission'    => $po_pay_item['commission'],
                    'pay_real_price'    => $po_pay_item['pay_price'],
                ];

                $this->purchase_db->where(['statement_number' => $statement_number,'purchase_number' => $po_number])
                    ->update('purchase_statement_summary',$update_arr);
            }

            $requisition_number = $main_result['data'];

            // 请款单明细记录
            if ($statement_data['items_list']) {
                $item_data_list = $post_data['item_data_list'];// 提交的备货单维度金额明细
                $item_data_list = json_decode($item_data_list,true);
                if(empty($item_data_list) or !is_array($item_data_list)){
                    throw new Exception('创建对账单-请款明细失败-PO+SKU明细金额缺失');
                }

                foreach ($item_data_list as $purchase_number => $item_data_list_sku) {
                    foreach($item_data_list_sku as $sku => $item_data_sku){
                        $order_item_info = $this->purchase_order_items_model->get_item($purchase_number,$sku,true);
                        if(empty($order_item_info)){
                            throw new Exception('创建对账单-请款明细失败-PO+SKU明细不存在');
                        }

                        $pay_detail_main                        = [];
                        $pay_detail_main['requisition_number']  = $requisition_number;
                        $pay_detail_main['purchase_number']     = $purchase_number;
                        $pay_detail_main['sku']                 = $sku;
                        $pay_detail_main['product_name']        = $order_item_info['product_name'];
                        $pay_detail_main['purchase_amount']     = $order_item_info['confirm_amount'];
                        $pay_detail_main['purchase_unit_price'] = $order_item_info['purchase_unit_price'];// 含税价

                        // 保存每个SKU的请款金额明细
                        $pay_detail_main['pay_total']     = $item_data_sku['item_product_money'];
                        $pay_detail_main['product_money'] = $item_data_sku['item_product_money'];

                        $item_result = $this->savePayDetailData($pay_detail_main);
                        if(empty($item_result)){
                            throw new Exception('创建对账单-请款明细失败');
                        }
                    }

                    // 更新采购单请款状态
                    $result_2 = $this->purchase_db->where('purchase_number', $purchase_number)->update('purchase_order', ['pay_status' => $pay_main['pay_status']]);
                    if (empty($result_2)) {
                        throw new Exception('创建对账单-请款单-更新采购单请款状态失败');
                    } else {
                        operatorLogInsert(
                            ['id'      => $purchase_number,
                             'type'    => 'purchase_order',
                             'content' => '采购单请款状态',
                             'detail'  => '更新采购单付款状态为【'.getPayStatus($pay_main['pay_status']).'】'
                            ]);
                    }

                    //付款状态 推送至计划系统
                    $push_pay_status_to_plan_key = 'push_pay_status_to_plan';
                    $this->rediss->set_sadd($push_pay_status_to_plan_key,$purchase_number);
                    $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$push_pay_status_to_plan_key);
                }

            } else {
                throw new Exception('创建对账单-请款单-采购单明细缺失');
            }


            // 请款单PO明细记录
            $pay_items_list = $post_data['pay_items_list'];// 提交的备货单维度金额明细
            $pay_items_list = json_decode($pay_items_list,true);
            foreach($pay_items_list as $pay_po_number => $pay_po_value){
                $pay_po_detail_main = [];
                $pay_po_detail_main['requisition_number'] = $requisition_number;
                $pay_po_detail_main['purchase_number'] = $pay_po_number;
                $pay_po_detail_main['product_money'] = $pay_po_value['product_money'];
                $pay_po_detail_main['freight'] = $pay_po_value['freight'];
                $pay_po_detail_main['discount'] = $pay_po_value['discount'];
                $pay_po_detail_main['process_cost'] = $pay_po_value['process_cost'];
                $pay_po_detail_main['commission'] = $pay_po_value['commission'];
                $pay_po_detail_main['pay_total'] = $pay_po_value['pay_price'];

                $pay_po_result = $this->savePayPoDetailData($pay_po_detail_main);
                if (empty($pay_po_result)) {
                    throw new Exception('创建合同单-请款PO明细失败');
                }
            }


            // 付款申请书
            $pay_requisition = [
                'compact_number' => $statement_number,
                'requisition_number' => $requisition_number,
                'invoice_looked_up' => $from_data['invoice_looked_up'],
                'receive_unit' => $from_data['receive_unit'],
                'receive_account' => $from_data['account'],
                'payment_platform_branch' => $from_data['payment_platform_branch'],
                'pay_price' => $from_data['pay_price'],
                'pay_price_cn' => $from_data['pay_price_cn'],
                'payment_reason' => $from_data['payment_reason'],
            ];
            // 36253 采购单运费请款时，当支付方式=线下境外，请款方式=运费请款 时 公司主体=香港易佰
            if($pay_main['requisition_method'] == PURCHASE_REQUISITION_METHOD_HAND && $pay_main['pay_type'] == PURCHASE_PAY_TYPE_PRIVATE){
                $pay_requisition['invoice_looked_up'] = 'YIBAI TECHNOLOGY LTD';
            }
            $result_pay = $this->purchase_pay_requisition_model->create($pay_requisition);

            $this->load->model('statement/Purchase_statement_note_model');
            $this->Purchase_statement_note_model->add_remark($statement_number, 1, '对账单申请付款', '对账单申请付款');
            if (empty($result_pay)) {
                throw new Exception('创建对账单-请款单-付款申请书保存失败');
            }
            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('创建请款单事务提交出错');
            } else {
                if($pay_main['pay_status']==20){
                    $this->load->model('Ding_talk_model','ding_talk'); // 钉钉接口
                    $param = array(
                        'role_number' => $pay_main['pay_status'],
                        'msg' => '您有一条新的待审核的请款，请款单号' . $requisition_number . '，请款总额为' . $pay_main['pay_price'] . '元，运费为' . $pay_main['freight'] . '元，请前往采购系统及时处理！'.date('Y-m-d H:i:s'),
                    );
                    $this->ding_talk->pushDingTalkInfo($param);
                }
                $this->purchase_db->trans_commit();
            }
            $return['code'] = true;
            $return['data'] = [$requisition_number];
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;

    }

    /**
     * 合同单-同请款分摊运费和优惠额
     * @param array $post_data          分摊的金额等数据
     * @param array $purchase_sku_list  PO+SKU列表
     * @param array $update_table       需要更新的表
     * @return bool
     */
    public function compact_amount_average_distribute($post_data, $purchase_sku_list,$update_table = ['purchase_order_items','purchase_order_pay_detail'])
    {
        return false;// 屏蔽功能

        // 优惠额 和 运费分摊
        $requisition_number = $post_data['requisition_number'];
        $freight            = isset($post_data['freight']) ? $post_data['freight'] : null;
        $discount           = isset($post_data['discount']) ? $post_data['discount'] : null;
        $process_cost           = isset($post_data['process_cost']) ? $post_data['process_cost'] : null;
        $product_money      = isset($post_data['product_money']) ? $post_data['product_money'] : null;
        $pay_price          = isset($post_data['pay_price']) ? $post_data['pay_price'] : null;

        // 数据异常
        if (empty($requisition_number)) return false;

        if ($freight) {// 分摊运费
            $average_distribute_freight = amountAverageDistribute($freight, $purchase_sku_list);
        }
        if ($discount) {// 分摊优惠额
            $average_distribute_discount = amountAverageDistribute($discount, $purchase_sku_list);
        }
        if ($process_cost) {// 分摊加工费
            $average_distribute_process_cost = amountAverageDistribute($process_cost, $purchase_sku_list);
        }
        if ($product_money) {// 分摊 商品总金额
            $average_distribute_product_money = amountAverageDistribute($product_money, $purchase_sku_list);
        }
        if ($pay_price) {// 分摊 实际请款总金额
            $average_distribute_pay_price = amountAverageDistribute($pay_price, $purchase_sku_list);
        }

        // 获取需要更新的数据（运费和优惠额）
        $purchase_sku_list_tmp = [];
        if (isset($average_distribute_freight) or isset($average_distribute_discount)
            or isset($average_distribute_product_money) or isset($average_distribute_pay_price) or isset($average_distribute_process_cost)
        ) {
            foreach ($purchase_sku_list as $key1 => $value1) {
                foreach ($value1 as $key2 => $value2) {
                    if (isset($average_distribute_freight)) {
                        $purchase_sku_list_tmp[$key1][$key2]['freight'] = $average_distribute_freight[$key1][$key2];
                    }
                    if (isset($average_distribute_discount)) {
                        $purchase_sku_list_tmp[$key1][$key2]['discount'] = $average_distribute_discount[$key1][$key2];
                    }
                    if (isset($average_distribute_process_cost)) {
                        $purchase_sku_list_tmp[$key1][$key2]['process_cost'] = $average_distribute_process_cost[$key1][$key2];
                    }
                    if (isset($average_distribute_product_money)) {
                        $purchase_sku_list_tmp[$key1][$key2]['product_money'] = $average_distribute_product_money[$key1][$key2];
                    }
                    if (isset($average_distribute_pay_price)) {
                        $purchase_sku_list_tmp[$key1][$key2]['pay_price'] = $average_distribute_pay_price[$key1][$key2];
                    }
                }
            }
        }

        $this->load->model('purchase/purchase_order_items_model');

        // 更新数据库中数据
        $flag = true;
        foreach ($purchase_sku_list_tmp as $purchase_number => $value1) {
            foreach ($value1 as $sku => $update_arr) {
                $update_arr_tmp1 = [];// $update_arr_tmp1.采购单明细
                $update_arr_tmp2 = [];// $update_arr_tmp2.请款单明细
                if(isset($update_arr['freight'])){
                    $update_arr_tmp1['freight'] = $update_arr['freight'];
                    $update_arr_tmp2['freight'] = $update_arr['freight'];
                }
                if(isset($update_arr['discount'])){
                    $update_arr_tmp1['discount'] = $update_arr['discount'];
                    $update_arr_tmp2['discount'] = $update_arr['discount'];
                }
                if(isset($update_arr['process_cost'])){
                    $update_arr_tmp1['process_cost'] = $update_arr['process_cost'];
                    $update_arr_tmp2['process_cost'] = $update_arr['process_cost'];
                }
                if(isset($update_arr['product_money'])){
                    $update_arr_tmp2['product_money'] = $update_arr['product_money'];
                }
                if(isset($update_arr['pay_price'])){
                    $update_arr_tmp2['pay_total'] = $update_arr['pay_price'];
                }

                $item_result1 = $item_result2 = 1;// 假设都操作成功
                if($update_arr_tmp1 and in_array('purchase_order_items',$update_table)){// 采购单明细
                    $item_result1 = false;
                    $old_order_item = $this->purchase_order_items_model->get_item($purchase_number,$sku,true);
                    if($old_order_item AND isset($old_order_item['id'])){
//                        $update_arr_tmp1_tmp = [];
//                        // 运费、优惠 与历史累加（不能累加）
//                        isset($update_arr_tmp1['freight']) and $update_arr_tmp1_tmp['freight'] = $old_order_item['freight'] + $update_arr_tmp1['freight'];
//                        isset($update_arr_tmp1['discount']) and $update_arr_tmp1_tmp['discount'] = $old_order_item['discount'] + $update_arr_tmp1['discount'];
                        if($update_arr_tmp1){
                            // 更新采购单明细 中运费、优惠额
                            $item_result1 = $this->purchase_db->where('id',$old_order_item['id'])
                                ->update('purchase_order_items', $update_arr_tmp1);
                        }
                    }
                }
                if($update_arr_tmp2 and in_array('purchase_order_pay_detail',$update_table)){// 请款单明细
                    // 更新采购单明细 中运费、优惠额、商品金额、请款总金额
                    $item_result2 = $this->purchase_db->where(['purchase_number' => $purchase_number, 'sku' => $sku, 'requisition_number' => $requisition_number])
                        ->update('purchase_order_pay_detail', $update_arr_tmp2);
                }

                // 有一个出错就失败
                if (empty($item_result1) or empty($item_result2)) {
                    $flag = false;
                    break 2;
                } else {
                    $freight and $update_arr['total_freight'] = $freight;
                    $discount and $update_arr['total_discount'] = $discount;
                    if(in_array('purchase_order_items',$update_table)){
                        operatorLogInsert(
                            ['id'      => $purchase_number,
                             'type'    => 'purchase_order_items',
                             'content' => '合同单请款-分摊运费或优惠额',
                             'detail'  => "{$requisition_number}-{$purchase_number}-{$sku} ".json_encode($update_arr),
                             'is_show' => 2
                            ]);
                    }
                    if(in_array('purchase_order_pay_detail',$update_table)){
                        operatorLogInsert(
                            ['id' => $requisition_number,
                             'type' => 'purchase_order_pay_detail',
                             'content' => '合同单请款-分摊运费或优惠额',
                             'detail' => "{$requisition_number}-{$purchase_number}-{$sku} " . json_encode($update_arr),
                             'is_show' => 2
                            ]);
                    }
                }
            }
        }

        return $flag;
    }


    /**
     * 新增或更新 请款单 主记录
     *      带有请款单号则更新，否则新增
     * @author Jolon
     * @param array $pay_main 要保存的数据
     * @return array
     */
    public function savePayData($pay_main)
    {
        $return = ['code' => false, 'msg' => '', 'data' => ''];

        $insert_pay_main = [];
        isset($pay_main['pur_number']) and $insert_pay_main['pur_number'] = $pay_main['pur_number'];
        isset($pay_main['requisition_method']) and $insert_pay_main['requisition_method'] = $pay_main['requisition_method'];
        isset($pay_main['pay_category']) and $insert_pay_main['pay_category'] = $pay_main['pay_category'];
        isset($pay_main['currency_code']) and $insert_pay_main['currency_code'] = $pay_main['currency_code'];
        isset($pay_main['pay_price']) and $insert_pay_main['pay_price'] = $pay_main['pay_price'];
        isset($pay_main['product_money']) and $insert_pay_main['product_money'] = $pay_main['product_money'];
        isset($pay_main['freight']) and $insert_pay_main['freight'] = $pay_main['freight'];
        isset($pay_main['discount']) and $insert_pay_main['discount'] = $pay_main['discount'];
        isset($pay_main['process_cost']) and $insert_pay_main['process_cost'] = $pay_main['process_cost'];
        isset($pay_main['commission']) and $insert_pay_main['commission'] = $pay_main['commission'];
        isset($pay_main['commission_percent']) and $insert_pay_main['commission_percent'] = $pay_main['commission_percent'];
        isset($pay_main['settlement_method']) and $insert_pay_main['settlement_method'] = $pay_main['settlement_method'];
        isset($pay_main['pay_type']) and $insert_pay_main['pay_type'] = $pay_main['pay_type'];
        isset($pay_main['purchase_name']) and $insert_pay_main['purchase_name'] = $pay_main['purchase_name'];
        isset($pay_main['source']) and $insert_pay_main['source'] = $pay_main['source'];
        isset($pay_main['supplier_code']) and $insert_pay_main['supplier_code'] = $pay_main['supplier_code'];
        isset($pay_main['create_notice']) and $insert_pay_main['create_notice'] = $pay_main['create_notice'];
        isset($pay_main['pay_ratio']) and $insert_pay_main['pay_ratio'] = $pay_main['pay_ratio'];
        isset($pay_main['js_ratio']) and $insert_pay_main['js_ratio'] = $pay_main['js_ratio'];
        isset($pay_main['purchase_account']) and $insert_pay_main['purchase_account'] = $pay_main['purchase_account'];
        isset($pay_main['pai_number']) and $insert_pay_main['pai_number'] = $pay_main['pai_number'];
        isset($pay_main['freight_desc']) and $insert_pay_main['freight_desc'] = $pay_main['freight_desc'];
        $insert_pay_main['pay_status'] = isset($pay_main['pay_status']) ? $pay_main['pay_status'] : 20; // 请款状态，默认 待经理审核
        isset($pay_main['source_subject']) and $insert_pay_main['source_subject'] = $pay_main['source_subject'];
        isset($pay_main['purchase_type_id']) and $insert_pay_main['purchase_type_id'] = $pay_main['purchase_type_id'];
        isset($pay_main['abstract_remark']) and $insert_pay_main['abstract_remark'] = $pay_main['abstract_remark'];
        isset($pay_main['need_pay_time']) and $insert_pay_main['need_pay_time'] = $pay_main['need_pay_time'];
        isset($pay_main['is_auto']) and $insert_pay_main['is_auto'] = $pay_main['is_auto'];


        // 是否是跨境宝供应商
        if (isset($pay_main['is_cross_border'])) {
            $insert_pay_main['is_cross_border'] = $pay_main['is_cross_border'];
        } elseif (isset($pay_main['supplier_code'])) {
            $this->load->model('supplier_model', '', false, 'supplier');
            $insert_pay_main['is_cross_border'] = ($this->supplier_model->is_cross_border($pay_main['supplier_code'])) ? 1 : 0;
        }
        // 是否是样品
        if (isset($pay_main['is_sample'])) {
            $insert_pay_main['is_sample'] = $pay_main['is_sample'];
        } elseif (isset($pay_main['supplier_code'])) {
            $this->load->model('purchase_order_model', '', false, 'purchase');

            if($insert_pay_main['source_subject'] == SOURCE_SUBJECT_COMPACT_ORDER){// 来源：对账单的不验证是否样品

            }else{
                if ($insert_pay_main['source'] == SOURCE_COMPACT_ORDER or strpos($pay_main['pur_number'], '-') !== false) {// 合同
                    $insert_pay_main['is_sample'] = ($this->purchase_order_model->is_sample_by_compact($pay_main['pur_number'])) ? 1 : 0;
                } else {
                    $insert_pay_main['is_sample'] = ($this->purchase_order_model->is_sample($pay_main['pur_number'])) ? 1 : 0;
                }
            }
        }
        if(isset($pay_main['compact_url'])){
            $compact_url_list = $pay_main['compact_url'];
            $compact_url_name_list = isset($compact_url_list[0]['file_name'])?array_column($compact_url_list,'file_name'):[];
            $compact_url_path_list = isset($compact_url_list[0]['file_path'])?array_column($compact_url_list,'file_path'):[];
            $insert_pay_main['compact_url_name'] = implode(',',$compact_url_name_list);
            $insert_pay_main['compact_url'] = implode(',',$compact_url_path_list);
        }else{
            $compact_url_list = [];
            $insert_pay_main['compact_url_name'] = '';
            $insert_pay_main['compact_url'] = '';
        }

        if (empty($pay_main['requisition_number'])) {// 创建记录
            $requisition_number = get_prefix_new_number('PP'); // 生成最新的请款单号
            $insert_pay_main['requisition_number'] = $requisition_number;

            $insert_pay_main['applicant'] = isset($pay_main['applicant'])?$pay_main['applicant']:getActiveUserId();
            $insert_pay_main['application_time'] = isset($pay_main['application_time'])?$pay_main['application_time']:date('Y-m-d H:i:s');
            $insert_pay_main['create_user_name'] = isset($pay_main['create_user_name'])?$pay_main['create_user_name']:getActiveUserName();
            $insert_pay_main['create_time'] = date('Y-m-d H:i:s');

            // 36253 采购单运费请款时，当支付方式=线下境外，请款方式=运费请款 时 公司主体=香港易佰
            if($insert_pay_main['requisition_method'] == PURCHASE_REQUISITION_METHOD_HAND && $insert_pay_main['pay_type'] == PURCHASE_PAY_TYPE_PRIVATE){
                $insert_pay_main['purchase_name'] = 'HKYB';
            }

            // 排他限制
            $exclude = $this->exclude_pay_order_create($insert_pay_main['pur_number']);
            if($exclude){
                $return['msg'] = '请款失败，已请款不需要再次请款！';
                return $return;
            }
            $result = $this->purchase_db->insert($this->table_name, $insert_pay_main);
            if($result){
                $this->exclude_pay_order_create($insert_pay_main['pur_number'], true);
            }
        } else {// 更新数据
            $requisition_number = $pay_main['requisition_number'];
            $insert_pay_main['modify_user_name'] = getActiveUserName();
            $insert_pay_main['modify_time'] = date('Y-m-d H:i:s');

            $result = $this->purchase_db->where('requisition_number', $requisition_number)
                ->update($this->table_name, $insert_pay_main);
        }

        if ($insert_pay_main['source'] == SOURCE_COMPACT_ORDER and $insert_pay_main['source_subject'] == SOURCE_SUBJECT_COMPACT_ORDER){//合同
           //保存合同上传扫描件
           $this->load->model('compact/Compact_list_model');
           $res= $this->Compact_list_model->compact_file_save($insert_pay_main['pur_number'],$compact_url_list,$requisition_number);
           if(!$res['code']){
               $return['msg'] = $res['msg'];
           }
        }

        if ($result) {
            operatorLogInsert(
                [
                    'id' => $requisition_number,
                    'type' => 'purchase_order_pay',
                    'content' => '申请请款',
                    'detail' => '创建成功',
                ]
            );
            $return['code'] = true;
            $return['data'] = $requisition_number;
        } else {
            $return['msg'] = '请款主表记录保存失败';
        }
        return $return;
    }

    /**
     * 新增 请款单 明细记录
     *      【已存在则删除旧的】
     * @author Jolon
     * @param array $pay_detail_main 要保存的数据
     * @return bool
     */
    public function savePayDetailData($pay_detail_main)
    {
        // 验证必须参数
        if (empty($pay_detail_main['requisition_number']) or empty($pay_detail_main['purchase_number'])
            or empty($pay_detail_main['sku'])
        )
            return false;

        $insert_pay_detail = [];
        $insert_pay_detail['requisition_number'] = $pay_detail_main['requisition_number'];
        $insert_pay_detail['purchase_number'] = $pay_detail_main['purchase_number'];
        $insert_pay_detail['sku'] = $pay_detail_main['sku'];
        $insert_pay_detail['product_name'] = isset($pay_detail_main['product_name'])?$pay_detail_main['product_name']:'';
        $insert_pay_detail['purchase_amount'] = isset($pay_detail_main['purchase_amount'])?$pay_detail_main['purchase_amount']:0;
        $insert_pay_detail['purchase_unit_price'] = isset($pay_detail_main['purchase_unit_price'])?$pay_detail_main['purchase_unit_price']:0;
        $insert_pay_detail['freight'] = isset($pay_detail_main['freight'])?$pay_detail_main['freight']:0;
        $insert_pay_detail['discount'] = isset($pay_detail_main['discount'])?$pay_detail_main['discount']:0;
        $insert_pay_detail['process_cost'] = isset($pay_detail_main['process_cost'])?$pay_detail_main['process_cost']:0;
        $insert_pay_detail['commission'] = isset($pay_detail_main['commission'])?$pay_detail_main['commission']:0;
        $insert_pay_detail['pay_total'] = isset($pay_detail_main['pay_total'])?$pay_detail_main['pay_total']:0;
        $insert_pay_detail['product_money'] = isset($pay_detail_main['product_money'])?$pay_detail_main['product_money']:0;

//        // 付款总额（含运费和优惠额）
//        $insert_pay_detail['pay_total'] = $insert_pay_detail['purchase_amount'] * $insert_pay_detail['purchase_unit_price'] + $insert_pay_detail['freight'] - $insert_pay_detail['discount'];

        $insert_pay_detail['create_user_name'] = getActiveUserName();
        $insert_pay_detail['create_time'] = date('Y-m-d H:i:s');
        $insert_pay_detail['modify_user_name'] = getActiveUserName();
        $insert_pay_detail['modify_time'] = date('Y-m-d H:i:s');

        // 获取 SKU 旧的记录
        $where = [
            'requisition_number' => $insert_pay_detail['requisition_number'],
            'purchase_number' => $insert_pay_detail['purchase_number'],
            'sku' => $insert_pay_detail['sku'],
        ];
        $exists = $this->purchase_db->where($where)->get('purchase_order_pay_detail')->row_array();

        $result = $this->purchase_db->insert('purchase_order_pay_detail', $insert_pay_detail);

        if ($result) {
            if ($exists) {// 删除 SKU 旧的记录
                $this->purchase_db->where('id', $exists['id'])->delete('purchase_order_pay_detail');
                operatorLogInsert(
                    ['id' => $insert_pay_detail['requisition_number'],
                        'type' => 'purchase_order_pay_detail',
                        'content' => '删除请款明细记录',
                        'detail' => $exists,
                        'is_show' => 2
                    ]);
            }
            operatorLogInsert(
                ['id' => $insert_pay_detail['requisition_number'],
                    'type' => 'purchase_order_pay_detail',
                    'content' => '创建请款明细记录',
                    'detail' => '采购单号' . $insert_pay_detail['purchase_number'],
                    'is_show' => 2
                ]);
            return $result;
        } else {
            return false;
        }
    }


    /**
     * 新增 请款单PO 明细记录
     *      【已存在则删除旧的】
     * @author Jolon
     * @param array $pay_po_detail_main 要保存的数据
     * @return bool
     */
    public function savePayPoDetailData($pay_po_detail_main)
    {
        // 验证必须参数
        if (empty($pay_po_detail_main['requisition_number']) or empty($pay_po_detail_main['purchase_number'])){
            return false;
        }

        $insert_pay_po_detail = [];
        $insert_pay_po_detail['requisition_number'] = $pay_po_detail_main['requisition_number'];
        $insert_pay_po_detail['purchase_number'] = $pay_po_detail_main['purchase_number'];
        $insert_pay_po_detail['freight'] = isset($pay_po_detail_main['freight'])?$pay_po_detail_main['freight']:0;
        $insert_pay_po_detail['discount'] = isset($pay_po_detail_main['discount'])?$pay_po_detail_main['discount']:0;
        $insert_pay_po_detail['process_cost'] = isset($pay_po_detail_main['process_cost'])?$pay_po_detail_main['process_cost']:0;
        $insert_pay_po_detail['commission'] = isset($pay_po_detail_main['commission'])?$pay_po_detail_main['commission']:0;
        $insert_pay_po_detail['pay_total'] = isset($pay_po_detail_main['pay_total'])?$pay_po_detail_main['pay_total']:0;
        $insert_pay_po_detail['product_money'] = isset($pay_po_detail_main['product_money'])?$pay_po_detail_main['product_money']:0;


        // 获取 PO 旧的记录
        $where = [
            'requisition_number' => $insert_pay_po_detail['requisition_number'],
            'purchase_number' => $insert_pay_po_detail['purchase_number'],
        ];
        $exists = $this->purchase_db->where($where)->get('purchase_order_pay_po_detail')->row_array();

        $result = $this->purchase_db->insert('purchase_order_pay_po_detail', $insert_pay_po_detail);

        if ($result) {
            if ($exists) {// 删除 PO 旧的记录
                $this->purchase_db->where('id', $exists['id'])->delete('purchase_order_pay_po_detail');
                operatorLogInsert(
                    ['id' => $insert_pay_po_detail['requisition_number'],
                        'type' => 'purchase_order_pay_po_detail',
                        'content' => '删除请款PO明细记录',
                        'detail' => $exists,
                        'is_show' => 2
                    ]);
            }
            operatorLogInsert(
                ['id' => $insert_pay_po_detail['requisition_number'],
                    'type' => 'purchase_order_pay_po_detail',
                    'content' => '创建请款PO明细记录',
                    'detail' => '采购单号' . $insert_pay_po_detail['purchase_number'],
                    'is_show' => 2
                ]);
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 判断 请款类型
     * @author Jolon
     * @param string $is_drawback        是否退税
     * @param string $settlement_ratio   结算比例
     * @param string $pay_ratio          当前请款比例
     * @param int    $pay_price          请款金额
     * @param int    $freight            运费
     * @param int    $discount           优惠额
     * @param string $requisition_method 请款方式
     * @return int|string
     */
    public function compact_pay_category($is_drawback,$settlement_ratio = '',$pay_ratio = '',$pay_price = 0,$freight = 0,$discount = 0,$requisition_method = ''){

        if($is_drawback){
            if(floatval($freight) > 0){
                $pay_category = PURCHASE_PAY_CATEGORY_4;
            }else{
                $pay_category = PURCHASE_PAY_CATEGORY_2;
            }
        }else{
            if(floatval($freight) > 0){
                $pay_category = PURCHASE_PAY_CATEGORY_3;
            }else{
                $pay_category = PURCHASE_PAY_CATEGORY_2;
            }
        }

        return $pay_category;
    }

    /**
     * 获取请款单列表
     * @author harvin 2019-11-12
     * * */
    public function get_payment_list($params = [], $offset, $limit, $id = [], $page = 1)
    {
        $query_builder = $this->purchase_db;
        $query_builder->select(
            "A.id,A.pur_number,A.requisition_number,A.pay_status,A.source,A.source_subject,A.pay_category,A.applicant,"
            . "A.application_time,A.auditor,A.review_time,A.supplier_code,A.pay_price,A.real_pay_price,"
            . "A.pay_type,A.settlement_method,A.create_notice,A.is_cross_border,A.review_notice,"
            . "A.waiting_id,A.waiting_time,A.waiting_notice,A.approver,A.processing_time,A.processing_notice,"
            . "A.soa_user_id,A.soa_time,A.soa_notice,"
            . "A.financial_supervisor_id,A.financial_supervisor_time,A.financial_supervisor_notice,A.financial_manager_id,"
            . "A.financial_manager_time,A.financial_manager_notice,A.financial_officer_id,A.financial_officer_time,A.financial_officer_notice,"
            . "A.general_manager_id,A.general_manager_time,A.general_manager_notice,A.payer_id,A.payer_time,A.payment_notice,"
            . "sp.tap_date_str,sp.surplus_quota,A.pur_number AS statement_number,A.need_pay_time AS accout_period_time,"
            . "A.pur_tran_num,A.trans_orderid,A.voucher_address,A.payer_name,A.payer_time,"
            . "A.product_money AS product_money_total,A.freight AS freight_total,A.discount AS discount_total,A.process_cost AS process_cost_total,"
            . "A.commission AS commission_total,A.freight_desc"
        );

        $query_builder->from('purchase_order_pay_detail as B');
        $query_builder->join('pur_purchase_order_pay as A', 'B.requisition_number=A.requisition_number','inner');

        $query_builder->join('pur_supplier as sp', 'sp.supplier_code=A.supplier_code','left');
        $query_builder->join('purchase_order_pay_type as ppy', 'ppy.purchase_number=B.purchase_number','left');
//          //数据权限配置
//          $data_role= getRole();
//          $res_arr=array_intersect($role, $data_role);
//         if( !(!empty($res_arr) OR $userid === true )){
//            $this->purchase_db->where_in('A.applicant',$userid);
//         }
        //不显示样品请款
        $query_builder->not_like('A.requisition_number','YPP','after');
        if (isset($params['supplier_code']) and $params['supplier_code']) {//供应商
            $query_builder->where('A.supplier_code', $params['supplier_code']);
        }
        if(isset($params['pur_number'])&& trim($params['pur_number'])!=''){
            $pur_number = array_filter(explode(' ',trim($params['pur_number'])));
            $query_builder->where_in('A.pur_number', $pur_number);
        }

        if( isset($params['group_ids']) && !empty($params['group_ids']))
        {
            if(count($params['groupdatas'])>2000){

                $params['groupdatas'] = array_chunk($params['groupdatas'],10);
                foreach($params['groupdatas'] as $groupdata){
                    $query_builder->where_in("A.applicant",$groupdata);
                }
            }else{

                $query_builder->where_in("A.applicant",$params['groupdatas']);
            }
        }

        
        if (isset($params['pay_status']) && $params['pay_status']) { //付款状态
            if(is_string($params['pay_status']))$params['pay_status'] = explode(",", $params['pay_status']);
            $query_builder->where_in('A.pay_status', $params['pay_status']);
        }
        
        if (isset($params['requisition_number']) && $params['requisition_number']) { //请款单号
            $requisition_number_arr = array_filter(explode(' ',$params['requisition_number']));
            $query_builder->where_in('A.requisition_number',$requisition_number_arr);
        }
        if (isset($params['applicant']) && $params['applicant']) { //申请人
            if(!is_array($params['applicant'])){
                $applicant = array_filter(explode(',',$params['applicant']));
                $query_builder->where_in('A.applicant', $applicant);
            }else{
                $query_builder->where_in('A.applicant', $params['applicant']);
            }
        }
        if (isset($params['statement_number']) && $params['statement_number']) { //对账单号
            $statement_number = array_filter(explode(' ',trim($params['statement_number'])));
            $query_builder->where_in('A.pur_number', $statement_number)->where('A.source_subject',SOURCE_SUBJECT_STATEMENT_ORDER);
        }

        if (isset($params['pay_type']) && $params['pay_type']) {//支付方式 1:支付宝,2:对公支付 3 对私支付）
            $query_builder->where('A.pay_type', $params['pay_type']);
        }
        if (isset($params['pay_category']) && $params['pay_category']) {//请款类型
            $query_builder->where('A.pay_category', $params['pay_category']);
        }
        if (isset($params['settlement_method']) && $params['settlement_method']) { //结算方式
            $query_builder->where_in('A.settlement_method', $params['settlement_method']);
        }
        if (isset($params['is_cross_border']) && $params['is_cross_border'] != "") { //跨境宝供应商
            $query_builder->where('A.is_cross_border', $params['is_cross_border']);
        }
        if (isset($params['create_time_start']) and $params['create_time_start'])// 创建时间-开始
            $query_builder->where('A.create_time>=', $params['create_time_start']);
        if (isset($params['create_time_end']) and $params['create_time_end'])// 创建时间-结束
            $query_builder->where(' A.create_time<=', $params['create_time_end']);
        if(!empty($id) && is_array($id)){
             $query_builder->where_in(' A.id', $id);
        }
        //按付款时间查询
        if (!empty($params['pay_time_start']) && !empty($params['pay_time_end'])) {
            $start_time = date('Y-m-d H:i:s', strtotime($params['pay_time_start']));
            $end_time = date('Y-m-d H:i:s', strtotime($params['pay_time_end']));
            $query_builder->where('A.payer_time >=', $start_time);
            $query_builder->where('A.payer_time <=', $end_time);
        }
        
        //添加采购来源
        if(isset($params['purchase_source']) and $params['purchase_source']!=""){
            $query_builder->where(' A.source=', $params['purchase_source']);
        }
        //采购来源
        if(isset($params['purchase_type']) and $params['purchase_type']!=""){
            if(is_string($params['purchase_type']))$params['purchase_type'] = explode(',', $params['purchase_type']);
            if(is_array($params['purchase_type'])){
                $query_builder->where_in('A.purchase_type_id',$params['purchase_type']);
            }else{
                $query_builder->where('A.purchase_type_id',$params['purchase_type']);
            }
        }

        // 来源主体（1合同 2网采 3对账单）
        if(isset($params['is_statement_pay']) and $params['is_statement_pay']!=""){
            if($params['is_statement_pay'] == '3'){
                $query_builder->where('A.source=1 AND A.source_subject=3');
            }else{
                $query_builder->where('A.source_subject!=3');
            }
        }
        //付款回单
        if(isset($params['pur_tran_num'])  && $params['pur_tran_num']){
            $requisition_number_arr = array_filter(explode(' ',$params['pur_tran_num']));
            $query_builder->where_in('A.pur_tran_num',$requisition_number_arr);
        }
        if(SetAndNotEmpty($params, 'need_pay_time_start') && SetAndNotEmpty($params, 'need_pay_time_end')){//应付款时间
            $start_time = date('Y-m-d 00:00:00',strtotime($params['need_pay_time_start']));
            $end_time = date('Y-m-d 00:00:00',strtotime($params['need_pay_time_end']));
            $query_builder->where("A.need_pay_time between '{$start_time}' and '{$end_time}' ");
        }
        //是否自动请款
        if(isset($params['is_auto'])  && $params['is_auto']){
            $query_builder->where_in('A.is_auto',$params['is_auto']);
        }

//
//        if(isset($params['pay_notice']) and $params['pay_notice']){  //付款提醒状态
//            if ($params['pay_notice'] == TAP_DATE_WITHOUT_BALANCE){   //查询额度不足的供应商
//
//                $supplier_settlement_online = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;
//
//                $supplier_res = $query_builder->query("SELECT supplier_code FROM pur_supplier WHERE supplier_settlement='{$supplier_settlement_online}' and pur_supplier.quota <> 0 and pur_supplier.surplus_quota <= 0")->result_array();
//                $supplier_res = !empty($supplier_res)?array_column($supplier_res,'supplier_code'):[ORDER_CANCEL_ORSTATUS];
//                $supplier_res= array_unique($supplier_res);
//
//                $query_builder->where_in("A.supplier_code",$supplier_res);
//
//            }
//
//            if ($params['pay_notice'] != TAP_DATE_WITHOUT_BALANCE){   //查询额度足够的供应商
//
//                $supplier_settlement_online = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;
//                $supplier_res = $query_builder->query("SELECT supplier_code FROM pur_supplier WHERE supplier_settlement='{$supplier_settlement_online}' and pur_supplier.quota <> 0 and pur_supplier.surplus_quota > 0")->result_array();
//                $supplier_res = !empty($supplier_res)?array_column($supplier_res,'supplier_code'):[ORDER_CANCEL_ORSTATUS];
//                $supplier_res= array_unique($supplier_res);
//
//                $query_builder->where_in("A.supplier_code",$supplier_res);
//
//                $query_builder->where("ppy.accout_period_time != '0000-00-00 00:00:00'");
//
//                if ($params['pay_notice'] == TAP_DATE_OVER_TIME){//已超期
//                    $today = date('Y-m-d H:i:s');
//                    $query_builder->where("ppy.accout_period_time<'{$today}'");
//                }
//
//                if ($params['pay_notice'] == TAP_DATE_COMING_SOON){//即将到期
//                    $today = date('Y-m-d H:i:s');
//                    $five_days_later = date('Y-m-d H:i:s',strtotime('+ 5 days'));
//                    $query_builder->where("ppy.accout_period_time >= '{$today}'");
//                    $query_builder->where("ppy.accout_period_time < '{$five_days_later}'");
//                }
//
//                if ($params['pay_notice'] == TAP_DATE_CAN_WAIT){//可继续等待
//                    $five_days_later = date('Y-m-d H:i:s',strtotime('+ 5 days'));
//                    $query_builder->where("ppy.accout_period_time >= '{$five_days_later}'");
//                }
//            }
//
//
//        }

            $count_qb = clone $query_builder;

            $results = $query_builder->limit($limit, $offset)->group_by('A.id')->order_by('A.id','desc')->get()->result_array();


            $requisition_order= is_array($results)?array_column($results, 'requisition_number'):[];
            $purchase_order=$this->get_purchase_order($requisition_order); 
            //批量获取 settlement_method
            $codes = is_array($results) ? array_column($results, "settlement_method") : [];
            $settlement_method_arr = $this->payment_order_pay_model->get_settlement_method_list($codes);
            //获取供应商集合
            $supplier_codes= is_array($results)?array_column($results, 'supplier_code'):[];
            $supplier_code_list = $this->get_supplier_name($supplier_codes);
            //计算请款总金额
            $tatal_sum=0;
            $user_list = get_buyer_name();
            //$pur_numbers = array_column($results,"pur_number");

            foreach ($results as $key => $v) {
                $audit_notice = $this->filter_notice($v);// 审核信息 排序

                $results[$key]['review_notice'] = $audit_notice[0]['notice'];//审核人备注请款单页面
                $results[$key]['auditor'] =  isset($user_list[$audit_notice[0]['people']]) ? $user_list[$audit_notice[0]['people']] : '';//审核人请款单页面
                $results[$key]['review_time'] = $audit_notice[0]['time'];
                $results[$key]['accout_period_time'] = $v['accout_period_time'];


                $results[$key]['status'] = $v['pay_status'];
                $results[$key]['settlement_method'] = isset($settlement_method_arr[$v['settlement_method']]) ? $settlement_method_arr[$v['settlement_method']] : "";
                $results[$key]['order_drawback'] = isset($purchase_order[$v['requisition_number']])?$purchase_order[$v['requisition_number']]:"";
                $results[$key]['pay_type'] = isset($v['pay_type'])?getPayType($v['pay_type']):'';
                $results[$key]['pay_category'] = isset($v['pay_category']) ? getPayCategory($v['pay_category']) : "";
                $results[$key]['pay_status'] = isset($v['pay_status'])?getPayStatus($v['pay_status']):'';
                $results[$key]['source'] = isset($v['source']) ? getPurchaseSource($v['source']) : "";
                $results[$key]['is_statement_pay'] = getIsStatementPay($v['source_subject']);
                $results[$key]['note'] = $v['create_notice'];
                $results[$key]['supplier_name'] = isset($supplier_code_list[$v['supplier_code']])?$supplier_code_list[$v['supplier_code']]:"";
                $results[$key]['applicant']= isset($user_list[$v['applicant']]) ? $user_list[$v['applicant']] : '';
                $buyerName = $this->User_group_model->getNameGroupMessage(["'".$results[$key]['applicant']."'"]);
                if(!empty($buyerName)) {
                    $buyerName = array_column($buyerName, 'group_name');
                }else{
                    $buyerName = [];
                }

                $results[$key]['currency'] = CURRENCY;
                $tatal_sum+=$v['pay_price'];
                $results[$key]['payer_time'] = !empty($v['payer_name']) ? $v['payer_time'] : '';

                //导出csv时查询请款单对应的所有采购单号
                if (!empty($params['export_csv'])) {
                    //合同号(网采单为空)
                    $results[$key]['csv_compact_number'] = ($v['source'] == 1) ? $v['pur_number'] : '';
                    //采购单号
                    $results[$key]['csv_purchase_number'] = $this->_get_purchase_number_all($v['requisition_number']);
                }
                $results[$key]['groupName']                = implode(",",$buyerName);
            }
            //获取采购主体
            $purchase_number= is_array($results)?array_column($results, 'order_drawback'):[];
            $drawback=$this->get_purchase_name($purchase_number);
            foreach ($results as $key => $vv) {
                $purchase_agent = isset($drawback[0][$vv['order_drawback']])?get_purchase_agent($drawback[0][$vv['order_drawback']]):'';
                $results[$key]['is_drawback']= $purchase_agent;
                $results[$key]['is_freight']=isset($drawback[1][$vv['order_drawback']]) && $drawback[1][$vv['order_drawback']]!=0? getFreightPayment($drawback[1][$vv['order_drawback']]) : "";
            }
            //统计总数要加上前面筛选的条件
            $count_row = $count_qb->select("count(DISTINCT A.id) as num,sum(A.pay_price) as all_pay_price")->get()->row_array();
            $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
            $data_list['applicant'] = get_buyer_name(); //申请人
            $data_list['applicant'][1]="admin";
            ksort($data_list['applicant']);
            $data_list['pay_status'] = getPayStatus(null,2); //付款状态
            $data_list['pay_type'] = getPayType(); //支付方式    
            $data_list['pay_notice_status'] = getPayNotice_Status(); //付款提醒状态
            $data_list['settlement_method'] = $this->payment_order_pay_model->get_settlement_method(); //结算方式
            $data_list['is_cross_border'] = getCrossBorder(); //跨境宝供应商
            $data_list['pay_category'] = getPayCategory();// 请款类型
            $data_list['purchase_type'] = getPurchaseType();//业务线
            $data_list['purchase_source'] = getPurchaseSource();//采购来源类型
            $data_list['is_statement_pay'] = getIsStatementPay();//是否对账单请款
            $data_list['is_auto'] = [self::IS_AUTO_N=>'否',self::IS_AUTO_Y=>'是'];

            $key_table = ['请款单号',
                '付款状态',
                '采购主体',
                '采购来源',
                '请款类型',
                '申请人',
                '申请时间',
                '审核 /(驳回）人',
                '审核 /(驳回)时间',
                '付款信息',
                '付款备注',
                '供应商',
                '申请金额',
                '已付金额',
                '币种',
                '支付方式',
                '结算方式',
                '运费支付',
                '付款回单',
                '运费说明',
                '请款备注',
                 '操作'];
            $return_data = [
                'drop_down_box' => $data_list,
                'key' => $key_table,
                'tatal_sum'=> sprintf("%.3f",$tatal_sum),
                'values' => $results,
                'aggregate_data'=>[
                   'total_all' => $total_count,
                   'all_pay_price' => isset($count_row['all_pay_price']) ? $count_row['all_pay_price'] : 0,
                ],
                'page_data' => [
                    'total' => $total_count,   
                    'offset' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total_count / $limit)
                ]
            ];

            return $return_data;
        

    }

    /**
     * @desc 过滤审核备注为空,返回最新的备注
     * @author Jeff
     * @Date 2019/9/5 15:21
     * @return
     */
    public function filter_notice($row)
    {
        $filter_arr = [];
        $filter_arr[] = [
            'people'=>$row['soa_user_id'],
            'time'=>$row['soa_time'],
            'notice'=>$row['soa_notice'],
        ];//对张主管审核

        $filter_arr[] = [
            'people'=>$row['auditor'],
            'time'=>$row['review_time'],
            'notice'=>$row['review_notice'],
        ];//采购经理审核

        $filter_arr[] = [
            'people'=>$row['waiting_id'],
            'time'=>$row['waiting_time'],
            'notice'=>$row['waiting_notice'],
        ];//供应链审核

        $filter_arr[] = [
            'people'=>$row['approver'],
            'time'=>$row['processing_time'],
            'notice'=>$row['processing_notice'],
        ];//财务审批

        $filter_arr[] = [
            'people'=>$row['financial_supervisor_id'],
            'time'=>$row['financial_supervisor_time'],
            'notice'=>$row['financial_supervisor_notice'],
        ];//财务主管审批

        $filter_arr[] = [
            'people'=>$row['financial_manager_id'],
            'time'=>$row['financial_manager_time'],
            'notice'=>$row['financial_manager_notice'],
        ];//财务经理审核

        $filter_arr[] = [
            'people'=>$row['financial_officer_id'],
            'time'=>$row['financial_officer_time'],
            'notice'=>$row['financial_officer_notice'],
        ];//财务总监审核

        $filter_arr[] = [
            'people'=>$row['general_manager_id'],
            'time'=>$row['general_manager_time'],
            'notice'=>$row['general_manager_notice'],
        ];//总经办审核

        $filter_arr[] = [
            'people'=>$row['payer_id'],
            'time'=>$row['payer_time'],
            'notice'=>$row['payment_notice'],
        ];//付款人

        foreach ($filter_arr as $key => $value) {
            $times[$key] = $value['time'];
        }

        array_multisort($times, SORT_DESC,$filter_arr);

        return $filter_arr;
    }

    /**
     * 获取网采单的请款状态
     * @author Jolon
     * @param $purchase_number
     * @return array
     */
    public function get_network_order_pay_status($purchase_number){
        // 网采单只能请款一次，所以获取最新一条请款单即可
        $last_pay = $this->purchase_db->where('pur_number',$purchase_number)
            ->order_by('id DESC')
            ->get($this->table_name)
            ->row_array();

        return !empty($last_pay)?$last_pay:[];
    }

    /**
     * 获取对应的采购单号
     * harvin 
     * **/
   public function get_purchase_order(array $requisition_order){
       if(empty($requisition_order)) return [];
        //获取采购单号
       $order_pay_detail= $this->purchase_db->
               select('requisition_number,purchase_number')
               ->where_in('requisition_number',$requisition_order)
               ->get('purchase_order_pay_detail')
               ->result_array();
       if(empty($order_pay_detail)){
           return [];
       }
       $order_pay_detail= array_column($order_pay_detail, 'purchase_number','requisition_number');
       return $order_pay_detail;
   }
    /**
     * 获取对应的采购主体
     * harvin
     * **/        
   public function get_purchase_name(array $purchase_number){
       if(empty($purchase_number)) return [];
       //获取采购单信息
       $order=$this->purchase_db
               ->select('purchase_name,purchase_number')
               ->where_in('purchase_number',$purchase_number)
               ->get('purchase_order')
               ->result_array();
       if(empty($order)){
           return [];
       }
       //获取支付
      $order_pay_type= $this->purchase_db
              ->select('purchase_number,is_freight')
              ->where_in('purchase_number',$purchase_number)
              ->get('purchase_order_pay_type')
              ->result_array();
      if(empty($order_pay_type)){
           return [];
      }
        $data=[
            array_column($order, 'purchase_name','purchase_number'),
            array_column($order_pay_type, 'is_freight','purchase_number'),
        ];
       return $data;
   }
   /**
    * 获取供应商名称
    * harvin
    * **/
  public function get_supplier_name(array $supplier_codes){
      if(empty($supplier_codes)) return [];
      $supplier=$this->purchase_db
              ->select('supplier_name,supplier_code')
              ->where_in('supplier_code',$supplier_codes)
              ->get('supplier')->result_array();
      if(empty($supplier)){
          return [];
      }
      return array_column($supplier, 'supplier_name','supplier_code');      
      
  }
  /**
     * 审核保存数据
     * @param array  $id            数组id
     * @param string $review_notice 审核备注
     * @param int    $type          1 是审核通过  2 是审核驳回
     * @author harvin 2019-1-12
     * @return array
     */
    public function payment_examine_save($id, $review_notice, $type = 1,$status=20){
        $this->load->model('Reject_note_model');
        try {
            $this->purchase_db->trans_begin();
            $this->load->model('compact/compact_model');
            $this->load->model('statement/Purchase_statement_model');
            $this->load->model('purchase/purchase_order_model');
            //记录操作日志
            foreach ($id as $val) {
                $order_pay = $this->purchase_db->select('id,requisition_number,pur_number,freight,discount,process_cost,pay_category,pay_price,product_money,source_subject')
                        ->where('id', $val)
                        ->get('purchase_order_pay')
                        ->row_array();
                if (empty($order_pay)) {
                    throw new Exception('参数id,不存在');
                }

                switch ($type) {
                    case 1:
                        if ($status == PAY_WAITING_MANAGER_REVIEW) { //采购经理审核
                            //判断运费是否大于1000 而且运费比例大于请款金额3% 需要供应链总监审核
                            if($order_pay['source_subject'] == SOURCE_SUBJECT_STATEMENT_ORDER){
                                $total_product_money = $this->Purchase_statement_model->get_total_instock_price($order_pay['pur_number']);// 获取总入库金额
                            }else{
                                $compactInfo = $this->compact_model->get_compact_one($order_pay['pur_number'],false);// 获取总采购商品额
                                $total_product_money = $compactInfo['product_money'];
                            }

                            if (bccomp($total_product_money, 500000, 3) >= 0){
                                // 采购金额>= 50万 需要 供应商总监审核
                                $pay_status = PAY_WAITING_MANAGER_SUPPLY; //待供应链总监审核
                            } elseif(bccomp($order_pay['freight'], 1000, 3) >= 0 && bccomp($order_pay['freight'], format_price($total_product_money * 0.03), 3) >= 0){
                                // 采购金额< 50玩  需要 供应商总监审核
                                $pay_status = PAY_WAITING_MANAGER_SUPPLY; //待供应链总监审核
                            } else {
                                $pay_status = PAY_WAITING_FINANCE_REVIEW; //待财务审核
                            }
                            $content = '采购经理审核通过';
                            $res = [
                                'review_notice' => $review_notice,
                                'auditor' => getActiveUserId(),
                                'review_time' => date('Y-m-d H:i:s'),
                                'pay_status' => $pay_status
                            ];
                        } elseif($status == PAY_WAITING_SOA_REVIEW){// 待对账主管审核
                            $pay_status = PAY_WAITING_MANAGER_REVIEW; //采购经理审核
                            $content = '对账主管审核通过';
                            $res = [
                                'soa_notice' => $review_notice,
                                'soa_user_id' => getActiveUserId(),
                                'soa_time' => date('Y-m-d H:i:s'),
                                'pay_status' => $pay_status
                            ];
                        } else { //供应链总监审核
                            $pay_status = PAY_WAITING_FINANCE_REVIEW; //待财务审核
                            $content = '供应链总监审核通过';
                            $res = [
                                'waiting_notice' => $review_notice,
                                'waiting_id' => getActiveUserId(),
                                'waiting_time' => date('Y-m-d H:i:s'),
                                'pay_status' => $pay_status
                            ];
                        }
                        break;
                    case 2:
                        if ($status == PAY_WAITING_MANAGER_REVIEW) { //采购经理审核
                            $pay_status = PAY_MANAGER_REJECT; //采购经理驳回
                            $content = '采购经理审核驳回';
                            $messageTitle = '采购经理审核';
                            $res = [
                                'review_notice' => $review_notice,
                                'auditor' => getActiveUserId(),
                                'review_time' => date('Y-m-d H:i:s'),
                                'pay_status' => $pay_status
                            ];
                        } elseif($status == PAY_WAITING_SOA_REVIEW){// 待对账主管审核
                            $pay_status = PAY_SOA_REJECT; //对账主管驳回
                            $content = '对账主管审核驳回';
                            $messageTitle = '对账主管审核';
                            $res = [
                                'soa_notice' => $review_notice,
                                'soa_user_id' => getActiveUserId(),
                                'soa_time' => date('Y-m-d H:i:s'),
                                'pay_status' => $pay_status
                            ];
                        } else {
                            $pay_status = PAY_WAITING_MANAGER_REJECT; //供应链总监驳回
                            $content = '供应链总监审核驳回';
                            $messageTitle = '供应链总监审核';
                            $res = [
                                'waiting_notice' => $review_notice,
                                'waiting_id' => getActiveUserId(),
                                'waiting_time' => date('Y-m-d H:i:s'),
                                'pay_status' => $pay_status
                            ];
                        }


                        $this->Message_model->AcceptMessage('money',['data'=>[$val],'message'=>$review_notice,'user'=>getActiveUserName(),'type'=>$messageTitle]);
                        break;

                    default :
                        throw new Exception('参数type错误[1,2]');
                        break;
                }

                $log = [
                    'record_number'  => $order_pay['pur_number'],
                    'record_type'    => 'PUR_PURCHASE_ORDER_PAY',
                    'content'        => $content,
                    'content_detail' => $order_pay['pur_number'].$content,
                ];
                if(isset($res)){
                    $bool = $this->purchase_db->where('id', $val)->update('purchase_order_pay', $res);
                    if (!$bool) {
                        throw new Exception('请款单更新失败');
                    }
                }

                $result = $this->compact_model->change_compact_pay_status($order_pay['pur_number'], $pay_status);
                if (!$result['code']) {
                    throw new Exception($result['msg']);
                }

                if (in_array($pay_status,array(PAY_UFXFUIOU_SUPERVISOR,PAY_WAITING_MANAGER_SUPPLY,PAY_WAITING_FINANCE_REVIEW))){
                    $this->load->model('Ding_talk_model','ding_talk'); // 钉钉接口
                    $param = array(
                        'role_number' => $pay_status,
                        'msg' => '您有一条新的待审核的请款，请款单号' . $order_pay['requisition_number'] . '，请款总额为' . $order_pay['pay_price'] . '元，运费为' . $order_pay['freight'] . '元，请前往采购系统及时处理！'.date('Y-m-d H:i:s'),
                    );
                    $this->ding_talk->pushDingTalkInfo($param);
                }

                $this->Reject_note_model->get_insert_log($log);
                unset($res);
                unset($log);
            }
            if ($this->purchase_db->trans_status() === false) {
                $this->purchase_db->trans_rollback();
                return ['msg' => '操作失败', 'bool' => false];
            } else {
                $this->purchase_db->trans_commit();
                return ['msg' => '', 'bool' => true];
            }
        } catch (Exception $ex) {
            $this->purchase_db->trans_rollback();
            return ['msg' => $ex->getMessage(), 'bool' => false];
        }
    }

    /**
     * 获取采购单
     * @author harvin 2019-1-12
     * @param string $requisition_number 请款单号
     * @return string $purchase_number 采购单号
     * **/
    public function get_payment_info($requisition_number)
    {   
        if(empty($requisition_number)) return NULL;
        $res = $this->purchase_db->
                select('purchase_number')
                ->where('requisition_number', $requisition_number)
                ->get('purchase_order_pay_detail')
                ->result_array();
        if(empty($res)) return [];
        $purchase_number = is_array($res)?array_column($res, 'purchase_number'):[];
        return $purchase_number;
    }
    /**
     * 获取请款单来源及扫描件合同号
     * @author 2019-7-24
     * @param string $requisition_number
     * @return array
     */
    public function order_pay_source($requisition_number){
        if(empty($requisition_number)) return NULL;
         $res = $this->purchase_db
                 ->select('source,compact_url,pur_number,freight_desc,create_notice,product_money,freight,discount,process_cost,pay_price')
                ->where('requisition_number', $requisition_number)
                ->get('purchase_order_pay')
                ->row_array();
        if(empty($res)) return [];
        return $res;
    }
    /**
     * 判断请款单来源及付款状态
     * @param array $id 参数id
     * @param int $type 类型
     * @author harvin
     * @return boolean
     */
    public function payment_order_pay_source($id)
    {    $data=[];
        $order_pay = $this->purchase_db
                ->select('source,requisition_number')
                ->where_in('id', $id)
                ->get('purchase_order_pay')
                ->result_array();
        if(empty($order_pay)){
            return $data;
        }
        foreach ($order_pay as $key => $value) {
            if($value['source']==SOURCE_NETWORK_ORDER){
                $data[]=$value['requisition_number'];
            }
        }
        return $data;
    }
    /**
     * 判断审核级别
     * @author harvin
     * @param int $id
     * @param int $status
     * @return array
     */
    public function pay_audit_lave($id,$status){
        $data=[];
        $order_pay = $this->purchase_db
                ->select('pay_status,requisition_number')
                ->where_in('id', $id)
                ->get('purchase_order_pay')
                ->result_array();
        if(empty($order_pay)){
            return $data;
        }

        $statusMsg = [
            PAY_WAITING_SOA_REVIEW => '待对账主管审核',
            PAY_WAITING_MANAGER_REVIEW => '待采购经理审核',
            PAY_WAITING_MANAGER_SUPPLY => '待供应链总监审核'
        ];
         foreach ($order_pay as $key => $value) {
            if($value['pay_status']!=$status){
                $msg = isset($statusMsg[$value['pay_status']])?$statusMsg[$value['pay_status']]:'状态未知';
                $data[]=$value['requisition_number'].'是'.$msg;
            }
        }
        return $data;        
    }
    /**
     * 获取蓝凌processid参数
     * @author harvin
     * @param type $ids
     * @return type
     */
    public function payment_examine_saves($ids)
    {  if(empty($ids)) return null;
        $requisition_number = $this->purchase_db
            ->select('requisition_number')
            ->where('id', $ids)->get('purchase_order_pay')
            ->row_array();
        if(empty($requisition_number))  return null;
        $processid = $this->purchase_db
            ->select('processid')
            ->where('requisition_number', $requisition_number['requisition_number'])
            ->get('purchase_blue_pay')
            ->row_array();
        if(empty($processid)) return null;
        return $processid['processid'];
    }

    /**
     * @desc 检验批量付款的数据是否能一起付款
     * @author Jackson
     * @parames string $parames 查询值
     * @parames string $parames 查询字段
     * @Date 2019-02-14 20:01:00
     * @return array()
     **/
    public function check_pay_apply_datas($ids)
    {

        if (empty($ids)) {
            throw new Exception('请至少选择一个付款申请');
        }

        //查询采购单请款单数据
        $datas = $this->getDataByCondition(['where_in' => ['id' => explode(",", $ids)]], 'pay_status,source,pur_number,supplier_code');

        //付款状态
        /**@Table: pur_purchase_order_pay
         * 付款状态(10.未申请付款,20.待经理审核,21.经理驳回,30.待财务审核,31.财务驳回,40.待财务付款,50.已部分付款,
         * 51.已付款,90.已取消)
         **/
        $payStatusArray = array_unique(array_column($datas, 'pay_status'));
        if (count($payStatusArray) != 1 || !in_array(PAY_WAITING_FINANCE_PAID, $payStatusArray)) {
            throw new Exception('勾选的数据不全部是待付款的数据');
        }

        //合同来源(1合同 2网络【默认】 3账期采购)
        $drawback_info = array();
        $sourceArray = array_unique(array_column($datas, 'source'));
        if (count($sourceArray) != 1 || empty($sourceArray)) {
            throw new Exception('勾选的请款数据不是同一类型，网采，合同一次只能支付一个类型');
        }

        /**@Table: pur_purchase_order_pay -> source
         * 合同来源（1合同 2网络【默认】 3账期采购）
         **/
        if (in_array(SOURCE_COMPACT_ORDER, $sourceArray)) {

            //采购单号或合同号
            $compact_numbers = array_column($datas, 'pur_number');

            //查询采购合同表
            $drawback_info = $this->get_drawback_information('compact_number', $compact_numbers);

        }

        if (in_array(SOURCE_NETWORK_ORDER, $sourceArray)) {

            //采购单号或合同号
            $pur_numbers = array_column($datas, 'pur_number');

            /**@Table: pur_purchase_order 查询采购单信息表* */
            $drawback_info = $this->get_drawback_information('purchase_number', $pur_numbers, true);

        }

        //获取供应商code
        $supplierCodedArray = array_unique(array_column($datas, 'supplier_code'));
        if (count($supplierCodedArray) != 1 || empty($supplierCodedArray)) {
            throw new Exception('勾选的数据不全部是同一家供应商');
        }
        return ['source' => $sourceArray[0], 'is_drawback' => $drawback_info[0]['is_drawback']];

    }

    /**
     * @desc 获取采购合同是否含退税与不退税
     * @author Jackson
     * @parames string $pur_numbers 采购单号或合同号
     * @Date 2019-02-14 18:01:00
     * @return array()
     **/
    public function get_drawback_information($fields = '', $numbers = '', $flag = false)
    {

        $_model = '';
        if ($flag) {
            /**@Table pur_purchase_order is_drawback 是否退税* */
            $this->load->model("purchase/Purchase_order_model", "purchaseOrder");
            $_model = $this->purchaseOrder;
        } else {
            /**@Table pur_purchase_compact is_drawback 是否退税* */
            $this->load->model('compact/Compact_model', 'purchaseCompact');
            $_model = $this->purchaseCompact;
        }
        $drawback_info = $_model->getDataByCondition(['where_in' => [$fields => $numbers]], 'is_drawback');

        if (count(array_unique(array_column($drawback_info, 'is_drawback'))) != 1 || empty($drawback_info)) {
            throw new Exception('勾选的付款申请对应的采购单不存在或存在多个退税属性');
        }
        return $drawback_info;

    }

    /**
     * @desc 保存富友在线付款结果
     * @author Jackson
     * @parames string $pur_numbers 采购单号或合同号
     * @Date 2019-02-14 18:01:00
     * @return array()
     **/
    public function save_fuiou_pay_result($fuiouPayDatas, $fuiouPay)
    {
        $ids = isset($fuiouPayDatas['ids']) ? $fuiouPayDatas['ids'] : '';
        if (empty($ids)) {
            throw new Exception('付款信息不能为空');
        }

        //获取-采购单请款单
        $ids = !is_array($ids) ? explode(',', $ids) : $ids;
        $payStatusArray = $this->findOne(['where_in' => ['id' => $ids]], 'id,pay_status');
       
        $tranNo = isset($fuiouPay['tran']) ? $fuiouPay['tran'] : '';//交易号

        /**@Table: pur_purchase_order_pay 付款状态
         * (10.未申请付款,20.待经理审核,21.经理驳回,30.待财务审核,31.财务驳回,40.待财务付款,50.已部分付款,51.已付款,90.已取消)
         **/
        unset($payStatusArray['id']);//ID不参与判断
        if (count(array_unique($payStatusArray)) !== 1 || !in_array(PAY_WAITING_FINANCE_PAID, $payStatusArray)) {
            if (isset($fuiouPay['status']) && $fuiouPay['status'] == 'success') {
                $message = '存在重复付款的申请,请联系复核员驳回';
                $_msg = empty($tranNo) ? $message : $message . '交易流水号：' . $tranNo;
                throw new Exception($_msg);

            } else {
                throw new Exception('存在重复付款的申请, 请重新付款');
            }
        }

        //接口请求成功
        if (isset($fuiouPay['status']) && $fuiouPay['status'] == 'success') {
            //数据在富友对接成功
            if (isset($fuiouPay['responseBody']['rspCode']) && $fuiouPay['responseBody']['rspCode'] == '0000') {

                $responseMessage = isset($fuiouPay['responseBody']['rspDesc']) ? $fuiouPay['responseBody']['rspDesc'] : '数据对接成功，请检查是否成功提交转账申请！';
                $bankInfo = $this->bankCart->findOne(['account_short' => $fuiouPayDatas['Fuiou']['PayInfo'], 'status' => 1]);

                //事务开始
                $this->db->trans_start();
                try {
                
                    foreach ($ids as $payApplyId) {

                        /**@Table: pur_purchase_order_pay 付款状态
                         * (10.未申请付款,20.待经理审核,21.经理驳回,30.待财务审核,31.财务驳回,40.待财务付款,
                         * 50.已部分付款,51.已付款,90.已取消,13.富友支付审核)
                         **/
                        //更新条件
                        $_where = ['id' => $payApplyId, 'pay_status' => PAY_WAITING_FINANCE_PAID];
                        //更新数据
                        $_datas['pay_status'] = PAY_UFXFUIOU_REVIEW;//富友付款待审核
                        $_datas['payer_id'] = getActiveUserId();//获取当前登录用户ID
                        $_datas['payer_name'] = getActiveUserName();//获取当前登录用户名
                        $_datas['payer_time'] = date('Y-m-d H:i:s', time());
                        $_datas['pay_account'] = !empty($bankInfo) && !empty($bankInfo['account_short']) ? $bankInfo['account_short'] : 0;
                        $_datas['pay_number'] = !empty($bankInfo) && !empty($bankInfo['account_holder']) ? $bankInfo['account_holder'] : '';
                        $_datas['k3_account'] = !empty($bankInfo) && !empty($bankInfo['k3_bank_account']) ? $bankInfo['k3_bank_account'] : '';
                        $_datas['pay_branch_bank'] = !empty($bankInfo) && !empty($bankInfo['branch']) ? $bankInfo['branch'] : '';

                        //更新付款结果
                        $_update = $this->change_designation_status($_where, $_datas, '更改支付状态'); 
                        if ($_update == false) {
                            throw new Exception("付款状态修改失败" . $this->getErrorMsg());
                        }
                
                
                        //绑定交易流水号和付款申请单号的关系
                        $updateDatas = $this->findOne(['id' => $payApplyId], 'id,requisition_number');
                        if (empty($updateDatas)) {
                            throw new Exception('没有找到付款记录');
                        }
                 
                        $this->load->model("Purchase_order_pay_ufxfuiou_model", "orderPayFuion");
                        $saveBind = $this->orderPayFuion->save_bind_information($updateDatas, $tranNo);
                        if (!$saveBind) {
                            throw new Exception('交易流水号绑定失败');
                        }

                        // 更新请款单记录
                        $data_pay = [
                            'pur_tran_num'  => $tranNo,
                            'trans_orderid' => isset($fuiouPay['responseBody']['fuiouTransNo']) ? is_array($fuiouPay['responseBody']['fuiouTransNo']) ? implode(',', $fuiouPay['responseBody']['fuiouTransNo']) : $fuiouPay['responseBody']['fuiouTransNo'] : '',
                        ];
                        $this->purchase_db->where('requisition_number', $updateDatas['requisition_number'])->update($this->table_name, $data_pay);
                    }
                    //保存交易申请详情
                    $this->load->model("Purchase_order_pay_ufxfuiou_detail_model", "orderPayFuionDetails");
                    $saveDetail = $this->orderPayFuionDetails->save_pay_detail($fuiouPayDatas, $fuiouPay);
                    if (!$saveDetail) {
                        throw new Exception('付款详情保存失败');
                    }
  
                    //判断是否保存成功
                    $this->db->trans_complete();
                    if ($this->db->trans_status() === false) {
                        throw new Exception('更新数据失败');
                    }

                    return ['status' => 'success', 'message' => $responseMessage];

                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }
            } else {
                $responseMessage = isset($fuiouPay['responseBody']['rspDesc']) ? $fuiouPay['responseBody']['rspDesc'] : '数据对接失败，请检查是否成功提交转账申请！';
                throw new Exception($responseMessage);
            }
        } else {
            $_msgs = isset($fuiouPay['message']) ? $fuiouPay['message'] : '付款异常，请联系技术部解决';
            throw new Exception($_msgs);
        }
    }

    /**
     * @desc 富友付款成功执行操作
     * @author Jackson
     * @parames array $orderPayRequestNumbers 申请单号列表
     * @parames string $pur_tran_num 采购单号或合同号
     * @Date 2019-02-14 18:01:00
     * @return array()
     **/
    public function update_success($orderPayRequestNumbers, $pur_tran_num)
    {
        foreach ($orderPayRequestNumbers as $key => $orderPayRequestNumber) {

            //判断数据是否存在
            $payData = $this->findOne(['requisition_number' => $orderPayRequestNumber, 'pay_status' => PAY_UFXFUIOU_REVIEW]);
            if (empty($payData)) {
                continue;
                // 请款信息为空或者请款单状态不是富友付款待复核则跳过;
            } 
            $payData = (object)$payData;

            try {
                //更新请款单号
                $data = ['pay_status' => PAY_PAID, 'real_pay_price'=>$payData->pay_price];
                //更新请款单状态
                $this->purchase_db->where('requisition_number', $payData->requisition_number)->update('purchase_order_pay', $data);

                //1合同 2网采
                $uf_pay_time = $this->getUFPayTime($payData->requisition_number);
                if($uf_pay_time)$payData->pay_time = $uf_pay_time;
                if ($payData->source == SOURCE_COMPACT_ORDER) {
                    $savePay = $this->save_compact_success($payData, $pur_tran_num);
                    if ($savePay) {
                        operatorLogInsert(
                            [
                                'id' => $payData->pur_number,
                                'type' => 'purchase_order_pay',
                                'content' => '富友支付成功',
                                'detail' => '富友支付成功',
                            ]);
                    }
                } elseif ($payData->source == SOURCE_NETWORK_ORDER) {
                    $savePay = $this->save_internet_success($payData, $pur_tran_num);
                    if ($savePay) {
                        operatorLogInsert(
                            [
                                'id' => $payData->pur_number,
                                'type' => 'purchase_order_pay',
                                'content' => '富友支付成功',
                                'detail' => '富友支付成功',
                            ]);
                    }
                } else {
                    //todo
                }
            }catch(Exception $e){
                //记录日志
                $object = (object)array();
                $object->pur_tran_num = $pur_tran_num;
                $object->contents = $e->getMessage();
                $object->userName = '';
                $this->load->model("ufxfuiou_system_log_model","ufxfuiouSystemLog");
                $this->ufxfuiouSystemLog->save_ufxfuiou_error($object);
            }
        }
        return true;
    }

    /**
     * 获取富有的付款时间
     */
    public function getUFPayTime($number)
    {
        $res = false;
        $data = $this->purchase_db->from('pay_ufxfuiou_detail_expand as d')
            ->select("e.drawee_time")
            ->join("pur_pay_ufxfuiou_expand as e", "d.ufxfuiou_number=e.ufxfuiou_number", "inner")
            ->where("d.requisition_number=", $number)
            ->get()
            ->row_array();
        if(isset($data['drawee_time']) && !empty($data['drawee_time']))$res = $data['drawee_time'];
        return $res;
    }

    /**
     * @desc 执行合同请款成功
     * @author Jackson
     * @parames array $payData 支付数据
     * @parames string $pur_tran_num 交易流水号
     * @parames int $status 状态
     * @parames string $notice 提示
     * @Date 2019-02-15 18:01:00
     * @return array()
     **/
    public function save_compact_success($payData, $pur_tran_num, $status = PAY_PAID, $notice = '富友支付成功')
    {
        $this->load->model("Purchase_order_pay_water_model", "orderPayWater");
        $compactCondition = !is_array($payData->pur_number) ? [$payData->pur_number] : $payData->pur_number;
        /**
        pay_status付款状态(10.未申请付款,20.待经理审核,21.经理驳回,30.待财务审核,31.财务驳回,40.待财务付款,
         * 50.已部分付款,51.已付款,90.已取消)
         **/

        if ($status == PAY_PAID) {
            if($payData->source_subject == SOURCE_SUBJECT_COMPACT_ORDER){// 合同单
                $this->load->model("compact/Compact_model", 'compactModel');//查询合同信息根据采购单号
                $pos=$this->purchase_db->select('purchase_number')->where_in('compact_number',$compactCondition)->get('purchase_compact_items')->result_array();
                if (empty($pos)) {
                    throw new Exception('合同信息根据采购单号为空，更新合同单失败');
                }

                // 付款流程
                $relate_amount_list         = $this->compactModel->calculate_compact_related_amount($payData->pur_number);
                $product_money              = isset($relate_amount_list['data']['product_money'])?$relate_amount_list['data']['product_money']:0;
                $paid_total_product_money   = isset($relate_amount_list['data']['paid_total_product_money'])?$relate_amount_list['data']['paid_total_product_money']:0;// 已含 退款冲销商品金额
                $cancel_total_product_money = isset($relate_amount_list['data']['cancel_total_product_money'])?$relate_amount_list['data']['cancel_total_product_money']:0;
                $has_pay                    = $paid_total_product_money + $payData->pay_price;

                $pay_status = $status;
                if ( $product_money - $cancel_total_product_money - $has_pay  > IS_ALLOWABLE_ERROR) {// 误差超过 1元是已部分付款，小于1元是 已付款
                    $pay_status = PART_PAID; // 部分付款
                }

                $payData->pay_status = PAY_PAID;//51.已付款
                $payData->payment_notice = $notice;
                $payData->real_pay_price = $payData->pay_price;

                //获取用户名
                $userName = $payData->payer_name;
                //更新采购单信息表
                $this->load->model("purchase/Purchase_order_model", "purchaseOrder");
                $purchase_number= isset($pos)?array_column($pos, 'purchase_number'):[];
                if(empty($purchase_number)){
                    throw new Exception('采购单不存在');
                }

                $purchase_number_list= array_unique($purchase_number);
                foreach ($purchase_number_list as $row) {
                    $designation_status = ['pay_status' => $pay_status];
                    if($payData->payer_time)$designation_status['pay_time'] = $payData->payer_time;
                    $updatePurchaseOrder = $this->purchaseOrder->change_designation_status($row, $designation_status,'确认付款(合同单富友)',$userName);
                    if ($updatePurchaseOrder === false) {
                        throw new Exception('确认付款(合同单富友)，更新失败');
                    }
                }
                //合同单
                $this->purchase_db->where_in('compact_number',$compactCondition)->update('purchase_compact',['payment_status'=>$pay_status]);
                //记录流水
                $saveWater = $this->orderPayWater->save_pay_water($payData, $pur_tran_num);
                if (!$saveWater) {
                    throw new Exception('确认付款(合同单富友)，更新失败');
                }
                return true;

            }elseif($payData->source_subject == SOURCE_SUBJECT_STATEMENT_ORDER){
                $this->load->model('statement/Purchase_statement_model');// 根据对账单查找
                foreach($compactCondition as $statement_number){
                    $this->Purchase_statement_model->change_statement_pay_status($statement_number, PAY_PAID, $payData->payer_time);
                }

                $payData->pay_status = PAY_PAID;//51.已付款
                $payData->payment_notice = $notice;
                $payData->real_pay_price = $payData->pay_price;

                //记录流水
                $saveWater = $this->orderPayWater->save_pay_water($payData, $pur_tran_num);
                if (!$saveWater) {
                    throw new Exception('确认付款(合同单富友)，更新失败');
                }
                return true;
            }

        }
    }

    /**
     * @desc 执行网采请款成功
     * @author Jackson
     * @parames array $payData 支付数据
     * @parames string $pur_tran_num 交易流水号
     * @parames int $pay_status 状态
     * @Date 2019-02-19 18:01:00
     * @return array()
     **/
    public function save_internet_success($payData,$pur_tran_num,$pay_status=PAY_PAID)
    {

        $model = (object)array();
        $model->pay_status = $pay_status;
        $model->payer_time = date('Y-m-d H:i:s', time());
        $model->payment_notice = '富友支付成功';
        $userName = $payData->payer_name;

        //更新采购单信息表
        $this->load->model("purchase/Purchase_order_model", "purchaseOrder");
        $updatePurchaseOrder = $this->purchaseOrder->change_designation_status($payData->pur_number,['pay_status' => $pay_status, 'pay_time' => $model->payer_time],'确认付款(网采单富友)',$userName);
        if ($updatePurchaseOrder == false) {
            throw new Exception("确认付款(网采单富友)-更新失败");
        }

        $this->load->model("Purchase_order_pay_water_model", "orderPayWater");
        $saveWater = $this->orderPayWater->save_pay_water($payData, $pur_tran_num);
        if (!$saveWater) {
            throw new Exception("保存富有支付流水(付款流水)->网采单->失败");
        }

        //更新采购单请款单支付状态
        $this->change_designation_status(['id'=>$payData->id],(array)$model,'网采单-富友支付状态变更');
        return true;

    }

    /**
     * @desc 获取订单已支付金额
     * @author Jackson
     * @parames array $pur_number 采购单号
     * @Date 2019-02-15 18:01:00
     * @return array()
     **/
    public function get_order_paid_money($pur_number)
    {

        //获取采购单请款单支付金额根据采购单号
        $condition = [
            'pur_number' => $pur_number,
            'where_in' => ['pay_status' => [PART_PAID, PAY_PAID]],
        ];
        $count = $this->getDataByCondition($condition, 'sum(pay_price) as price');

        if (empty($count)) {

            //采购合同和采购单的绑定合同单号
            /**@Table pur_purchase_compact_items bind(绑定状态 1绑定（默认） 2解绑)**/
            $this->load->model("compact/Compact_items_model", 'compactItemModel');
            $compact_number = $this->compactItemModel->findOne(['pur_number' => $pur_number, 'bind' => 1], 'id,compact_number');
            if (!empty($compact_number)) {
                //通过合同单号获取支付查询金额
                $condition['pur_number'] = $compact_number['compact_number'];
                $count = $this->getDataByCondition($condition, 'sum(pay_price) as price');
            }
        }
        return isset($count['price']) ? $count['price'] : 0;

    }

    /**
     * @desc 根据选择的ID获取所有付款记录
     * @author Jackson
     * @parames array $ids
     * @Date 2019-02-12 18:01:00
     * @return array()
     **/
    public function findRecordeByIds($ids = array())
    {
        //查询条件
        $condition = [];
        if (empty($ids)) {
            return null;
        }
        $condition['where_in'] = ['id' => $ids];
        return $this->getDataByCondition($condition);
    }

    /**
     * 自动计算采购单付款状态
     *      采购商品额 - 已取消商品额 - 已付款商品额  >>> 大于0则为已部分付款，小于等于0则为已付款
     * @author Jolon
     * @param $purchase_number
     * @param string $pay_time 付款时间
     * @return bool
     * @throws Exception
     */
    public function calculate_purchase_order_pay_status($purchase_number,$pay_time = null){
        $this->load->model('finance/Purchase_order_pay_type_model');
        $this->load->model('purchase/Purchase_order_determine_model');

        $orderInfo = $this->purchase_db->select('purchase_number,pay_status')
            ->where('purchase_number',$purchase_number)
            ->get('purchase_order')
            ->row_array();


        // 采购单已付商品额
        $paid_price_list    = $this->get_pay_total_by_purchase_number(['0' => $purchase_number]);
        $paid_price_list    = arrayKeyToColumn($paid_price_list, 'purchase_number');
        $paid_product_price = isset($paid_price_list[$purchase_number]) ? $paid_price_list[$purchase_number]['paid_product_money'] : 0;

        // 采购单采购商品金额
        $order_product_money = $this->Purchase_order_pay_type_model->get_one($purchase_number);
        $order_product_money = isset($order_product_money['product_money']) ? $order_product_money['product_money'] : 0;

        // 已取消金额
        $cancel_price_list    = $this->Purchase_order_determine_model->get_total_cancellation($purchase_number);
        $cancel_product_price = isset($cancel_price_list[$purchase_number]) ? $cancel_price_list[$purchase_number]['item_total_price'] : 0;

        if(empty($orderInfo)){
            throw new Exception('采购单单不存在');
        }
        if($order_product_money <= 0){
            throw new Exception('采购单商品金额异常');
        }

        if($paid_product_price <= 0) return true;// 未付款的 是有异常的，不用更新付款状态

        // 状态为付款是更新付款时间到采购单上面
        if($order_product_money - $cancel_product_price - $paid_product_price > 0){// 采购商品额 - 已取消商品额 - 已付款商品额
            $data_pay_status=['pay_status' => PART_PAID,'pay_time'=>$pay_time?$pay_time:date('Y-m-d H:i:s')];// 已部分付款
        }else{
            $data_pay_status=['pay_status' => PAY_PAID,'pay_time'=>$pay_time?$pay_time:date('Y-m-d H:i:s')];// 已付款
        }
        if($pay_time and $pay_time != '0000-00-00 00:00:00'){// 付款时 同步付款时间到采购单表
            $data_pay_status["pay_time"] = $pay_time;
        }

        $old_status_text = getPayStatus($orderInfo['pay_status']);
        $new_status_text = getPayStatus($data_pay_status['pay_status']);
        $detail          = "修改支付状态，从[{$old_status_text}]改为[{$new_status_text}]";

        $result1 = $this->purchase_db->where('purchase_number',$purchase_number)
            ->update('purchase_order',$data_pay_status);
        if(!$result1){
            throw new Exception('采购单支付状态变更失败');
        }else{
            $this->load->library('Rabbitmq');
            $mq = new Rabbitmq();//创建消息队列对象
            $mq->setQueueName('STATEMENT_CHANGE_PAY_STATUS_REFRESH');//设置参数
            $mq->setExchangeName('STATEMENT_CHANGE_PAY_STATUS');//构造存入数据
            $mq->setRouteKey('SO_REFRESH_FOR_003');
            $mq->setType(AMQP_EX_TYPE_DIRECT);
            $mq->sendMessage(['purchase_number' => $purchase_number,'add_time' => time()]);// 保持格式一致

            operatorLogInsert(['id' => $purchase_number,'type' => 'purchase_order','content' => '采购单支付状态','detail' => $detail]);
        }

    }

    /**
     * 采购单请款单(动态指定更新状态)
     * @author Jackson
     * @param array $where 条件
     * @param int $new_status 目标状态
     * @return bool
     */
    public function change_designation_status($where = array(), $new_status = array(), $msg = '变更采购单状态')
    {
        //获取被更新字段
        $update_field = array_keys($new_status);
        $purchase_order = $this->findOne($where);
        if(empty($purchase_order)){
             throw new Exception('参数ids='.$where['id'].'不存在');
        }
        $changList = array();
        //获取被修改的状态名称
        foreach ($update_field as $key => $field) {

            $oldText = '';
            $newText = '';
            switch ($field) {
                case 'pay_status':
                    $oldText = getPayStatus($purchase_order[$field]);//原状态名称
                    $newText = getPayStatus($new_status[$field]);//新状态名称
                    break;
                default:
                    break;
            }
            $changList['old_status_name'][$key] = $oldText;//原状态名称
            $changList['new_status_name'][$key] = $newText;//新状态名称

        }
        try {

            // 更新采购单状态
            $result = $this->update($new_status, $where);

            //记录日志
            if ($result) {
                $oldChangeText = implode(",", array_filter($changList['old_status_name']));//原状态名称 
                $newChangeText = implode(",", array_filter($changList['new_status_name']));//新状态名称
                //获取ID
               // $idKey = key($where);
                operatorLogInsert(
                    [
                        'id' => $purchase_order['pur_number'],
                        'type' => $this->table_name,
                        'content' => $msg,
                        'detail' => $msg . "，从【{$oldChangeText}】 改为【{$newChangeText}】"
                    ]);   
            } else {
                throw new Exception("更新失败Table" . $this->table_name);
            }

        } catch (Eexception $e) {
            throw new Exception($msg . "失败: code：" . $e->getMessage());
        }
        return true;
    }

    /**
     * @desc 保存富友支付回调数据并变更请款单状态
     * @author Jackson
     * @param array $resultDatas 通知结果
     * @return void()
     */
    public function save_pay_notify_result($resultDatas)
    {

        //通知类型
        $notifyType = isset($resultDatas['notifyType']) ? $resultDatas['notifyType'] :'04';
        //通知主体节点
        $notify   = 'notify'.$notifyType;
        //rspCode 5005 划款成功   5007 划款失败，资金已退回  0000（复核拒绝会出现其他情况还不确定） 011105 转账到代收付异常
        //富有请款单详细模块
        $this->load->model("Purchase_order_pay_ufxfuiou_detail_model", "ufxfuiouDetail");
        $pur_tran_num = isset($resultDatas[$notify]['eicSsn']) ? $resultDatas[$notify]['eicSsn'] : null;
        $ufxiouPaydata = $this->ufxfuiouDetail->findOne(['pur_tran_num' => $pur_tran_num]);
        if(empty($ufxiouPaydata)){
            exit('没有找到该交易流水号');
        }
        $_ufxiouPaydata = (object)array();
        $_ufxiouPaydata->ufxfuiou_tran_num = isset($resultDatas[$notify]['fuiouTransNo']) ?$resultDatas[$notify]['fuiouTransNo'] :'';//富友流水号
        $_ufxiouPaydata->tranfer_result_code = isset($resultDatas['rspCode']) ?$resultDatas['rspCode'] :'';//回调状态
        $_ufxiouPaydata->tranfer_result_reason = isset($resultDatas['rspDesc']) ?$resultDatas['rspDesc'] :'';//回调原因
        $_ufxiouPaydata->tranfer_result_money = isset($resultDatas[$notify]['amt']) ?$resultDatas[$notify]['amt'] :'';//转账金额
        $_ufxiouPaydata->pay_status = isset($resultDatas['rspCode']) ?$resultDatas['rspCode'] :'';//回调状态

        //根据交易流水号获取 申请单号
        $this->load->model("Purchase_order_pay_ufxfuiou_model", "orderPayFuion");
        $orderPayRequestNumbers = $this->orderPayFuion->getDataByCondition(['pur_tran_num' => $pur_tran_num,'status'=>1],'requisition_number');
        if(!$orderPayRequestNumbers){
            exit('没有该流水号的请款信息');
        }
        $orderPayRequestNumbers = array_column($orderPayRequestNumbers,'requisition_number');

        if($_ufxiouPaydata->ufxfuiou_tran_num and $orderPayRequestNumbers){
            $this->purchase_db->where_in('requisition_number', $orderPayRequestNumbers)->update($this->table_name, ['trans_orderid' => $_ufxiouPaydata->ufxfuiou_tran_num]);
        }
        if($_ufxiouPaydata->ufxfuiou_tran_num){
            // 更新富友支付拓展表 富友交易号
            $this->purchase_db->where('ufxfuiou_number', $pur_tran_num)->update('pay_ufxfuiou_expand', ['trans_orderid' => $_ufxiouPaydata->ufxfuiou_tran_num]);
        }

        //事务开始
        $this->db->trans_start();
        try{
            if(isset($resultDatas['rspCode'])&&$resultDatas['rspCode']=='5005'){

                //回调状态成功更新付款申请状态
                $saveStatus = $this->update_success($orderPayRequestNumbers,$pur_tran_num);
                if($saveStatus){
                    /**付款状态(10.未申请付款,20.待经理审核,21.经理驳回,30.待财务审核,31.财务驳回,40.待财务付款,
                     * 50.已部分付款,51.已付款,90.已取消)**/
                    $_ufxiouPaydata->pay_status = PAY_PAID;
                    $ufxiouPaydataUpdate = $this->ufxfuiouDetail->change_designation_status(['pur_tran_num' => $pur_tran_num],(array)$_ufxiouPaydata,'付款详情更新01-成功');
                    if($ufxiouPaydataUpdate===false){
                        throw new Exception('付款详情更新失败01');
                    }
                }else{
                    throw new Exception('付款成功状态更新失败02');
                }

            }elseif (isset($resultDatas['rspCode'])&&($resultDatas['rspCode']=='5007'|| $resultDatas['rspCode']=='0000'||$resultDatas['rspCode']=='011105')){

                //回调状态转账失败或者复核拒绝
                $saveStatus = $this->update_fail($orderPayRequestNumbers,$pur_tran_num);
                if($saveStatus){
                    $_ufxiouPaydata->status=PAY_FINANCE_REJECT;
                    $ufxiouPaydataUpdate = $this->ufxfuiouDetail->change_designation_status(['pur_tran_num' => $pur_tran_num],(array)$_ufxiouPaydata,'付款详情更新02-失败');
                    if($ufxiouPaydataUpdate===false){
                        throw new Exception('付款详情更新失败02');
                    }
                }

            }else{
                $rspCode = isset($resultDatas['rspCode']) ? $resultDatas['rspCode'] :'';
                throw new Exception('返回编码未知'.$rspCode);
            }
            //判断是否保存成功
            $this->db->trans_complete();
            if ($this->db->trans_status() === false) {
                throw new Exception('富友支付回调数据并变更请款单状态失败');
            }

        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @desc 富友付款失败执行操作
     * @author Jackson
     * @param array $resultDatas 通知结果
     * @return void()
     */
    public function update_fail($orderPayRequestNumbers,$pur_tran_num)
    {
        try {

            //更新请款单与富有申请绑定关系
            $this->load->model("Purchase_order_pay_ufxfuiou_model", "orderPayFuion");
            $_update = ['status' => 0, 'modify_time' => date('Y-m-d H:i:s', time())];
            $this->orderPayFuion->change_designation_status(['pur_tran_num' => $pur_tran_num], $_update, '富友付款失败');
            //复核失败或者转账失败请款单回退到待财务付款
            foreach ($orderPayRequestNumbers as $orderPayRequestNumber) {

                /**付款状态(10.未申请付款,20.待经理审核,21.经理驳回,30.待财务审核,31.财务驳回,40.待财务付款,
                 * 50.已部分付款,51.已付款,90.已取消,13富友支付审核)**/
                $conidtion = ['requisition_number' => $orderPayRequestNumber, 'pay_status' =>  PAY_UFXFUIOU_REVIEW];
                $_updateSatus = ['pay_status'=>PAY_WAITING_FINANCE_PAID];
                if(empty($this->findOne($conidtion))){
                    continue;
                    //'请款信息为空或者请款单状态不是富友付款待复核';
                }
                $this->change_designation_status($conidtion,$_updateSatus,'付款失败回退到付款状态');
               
//                $this->purchase_db->update('purchase_order_pay',['pay_status'=>PAY_WAITING_FINANCE_PAID,'payer_time'=>'','payer_name'=>'','payer_id'=>'']);
            }
          
        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 验证 采购单号 或 合同单号是否存在未完结的请款单
     * @author Jolon
     * @param $pur_number 采购单号 或 合同单号
     * @return bool
     */
    public function verify_order_status_enable_pay($pur_number){
        // 未完结状态的请款单
        $result = $this->purchase_db->select('requisition_number')
            ->where('pur_number',$pur_number)
            ->where_in('pay_status',[PAY_UNPAID_STATUS,PAY_WAITING_SOA_REVIEW,PAY_WAITING_MANAGER_REVIEW,PAY_WAITING_FINANCE_REVIEW,PAY_WAITING_FINANCE_PAID,PAY_UFXFUIOU_REVIEW,PAY_UFXFUIOU_SUPERVISOR,PAY_UFXFUIOU_MANAGER,PAY_WAITING_MANAGER_SUPPLY,PAY_UFXFUIOU_SUPPLY,PAY_UFXFUIOU_SUPPLY,PAY_GENERAL_MANAGER,PAY_UFXFUIOU_BAOFOPAY])
            ->get($this->table_name)
            ->row_array();

        if($result){
            return $result['requisition_number'];
        }else{
            return true;
        }
    }

    /**
     * 验证 采购单号 是否存在未完结的请款单
     * @author Jolon
     * @param $purchase_number 采购单号 或 合同单号
     * @return bool
     */
    public function verify_order_status_enable_pay_by_po($purchase_number){
        // 未完结状态的请款单
        $result = $this->purchase_db->select('A.requisition_number')
            ->from('purchase_order_pay AS A')
            ->join('purchase_order_pay_detail AS B','A.requisition_number=B.requisition_number','INNER')
            ->where('B.purchase_number',$purchase_number)
            ->where_in('A.pay_status',[PAY_UNPAID_STATUS,PAY_WAITING_SOA_REVIEW,PAY_WAITING_MANAGER_REVIEW,PAY_WAITING_FINANCE_REVIEW,PAY_WAITING_FINANCE_PAID,PAY_UFXFUIOU_REVIEW,PAY_UFXFUIOU_SUPERVISOR,PAY_UFXFUIOU_MANAGER,PAY_WAITING_MANAGER_SUPPLY,PAY_UFXFUIOU_SUPPLY,PAY_UFXFUIOU_SUPPLY,PAY_GENERAL_MANAGER,PAY_UFXFUIOU_BAOFOPAY])
            ->get()
            ->row_array();
        if($result){
            return $result['requisition_number'];
        }else{
            return true;
        }
    }

    /**
     * 验证合同单  验证请款比例是否已经请款
     * @author Jolon
     * @param $compact_number
     * @param $pay_ratio
     * @return bool
     */
    public function verify_compact_percent_paid($compact_number,$pay_ratio){
        $result = $this->purchase_db->select('id')
            ->where('pur_number',$compact_number)
            ->where('pay_ratio',$pay_ratio)
            ->where('pay_status',PAY_PAID)
            ->get($this->table_name)
            ->row_array();

        if($result){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 推送采购单（YPO开头的采购单.网采单）付款状态到产品系统
     * @author Jolon
     * @param      $purchase_number
     * @param null $pay_status
     * @return bool
     * @throws Exception
     */
    public function push_purchase_order_pay_status($purchase_number,$reject='',$pay_status = null){
        $purchase_number = trim($purchase_number);
        if(substr($purchase_number,0,3) !== 'YPO'){// 仅推送 YPO开头  产品系统推过来的采购单（采样的）
            return true;
        }

        if(is_null($pay_status)){
            $last_pay = $this->get_network_order_pay_status($purchase_number);
            $pay_status = $last_pay['pay_status'];
        }

        if($pay_status == PART_PAID or $pay_status == PAY_PAID){// 采购单付款成功
            $request_url = getConfigItemByName('api_config', 'product_system', 'prodSamplePurchaseOrder-updateStatus');
            $params         = ['purchaseNnumber' => $purchase_number];
        }elseif(in_array($pay_status, [PAY_MANAGER_REJECT,PAY_SOA_REJECT, PAY_FINANCE_REJECT, PAY_CANCEL])){// 采购单付款驳回
            $request_url = getConfigItemByName('api_config', 'product_system', 'prodSamplePurchaseOrder-getByPayStatus');
             $params         = [
                                  'purchaseNnumber' => $purchase_number,
                                   'createUser'=> get_buyer_name($last_pay['applicant']),
                                   'createName'=> get_buyer_name($last_pay['applicant']),
                                   'rejectUser'=> getActiveUserName(),
                                   'rejectReason'=>$reject,
                                ];
        }else{
            return true;
        }
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
            apiRequestLogInsert(
                [
                    'record_number'    => $purchase_number,
                    'record_type'      => 'push_purchase_order_pay_status',
                    'api_url'          => $request_url,
                    'post_content'     => $params,
                    'response_content' => $results,
                    'status'           => $status
                ]);

            if($status){
                return true;
            }else{
                $message = isset($results['msg'])?$results['msg']:'接口返回错误码：'.$results['code'];
                throw new Exception($message);
            }
        }else{
            throw new Exception('推送采购单(YPO)付款状态到产品系统执行出错');
        }
    }
    
    
    /**
     * 保存宝付支付待审核
     * @author harvin
     * @date2019-8-7
     * @param string $ids
     * @param float $reslut
     * @param float $procedure_party 运费承担方
     * @param float $procedure_fee 运费金额
     * @param float $account_short 支付账号简称
     * @return mixed
     * @throws Exception
     */
    public function faofopay_save($ids,$reslut,$procedure_party,$procedure_fee,$account_short){
        $this->load->model('Reject_note_model');
        $this->load->model('system/Bank_card_model', 'bankCart');
        try {
            if (empty($ids)) {
                throw new Exception('参数不存在');
            }
            $ids= explode(',', $ids);
            //开启事物
            $this->purchase_db->trans_begin();

            $bankInfo = $this->bankCart->findOne(['account_short' => $account_short, 'status' => 1]);
            if(empty($bankInfo)){
                throw new Exception('支付账号获取失败');
            }
            $pay_update_date = [
                'pay_status'       => PAY_UFXFUIOU_BAOFOPAY,
                'payment_platform' => 'baofoo',
                'procedure_party'  => $procedure_party,
                'procedure_fee'    => $procedure_fee,
                'pay_account'      => $bankInfo['account_short'],
                'pay_number'       => $bankInfo['account_holder'],
                'k3_account'       => $bankInfo['k3_bank_account'],
                'pay_branch_bank'  => $bankInfo['branch'],
            ];

             //记录请款单
            $this->purchase_db->where_in('id', $ids)->update('purchase_order_pay', $pay_update_date);
            $order_pay=$this->purchase_db->select('pur_number,requisition_number,supplier_code')->where_in('id', $ids)->get('purchase_order_pay')->result_array();

            //
            if(empty($order_pay)){
                throw new Exception('请款单不存在');
            }
            foreach ($order_pay as  $row) {
                $this->load->model('compact/Compact_model');
                $result = $this->Compact_model->change_compact_pay_status($row['pur_number'], PAY_UFXFUIOU_BAOFOPAY);
                if (!$result['code']) {
                    throw new Exception($result['msg']);
                }     
                $supplier_code= $row['supplier_code'];
                $baofo_detail=[
                    'pur_tran_num'=>$reslut['pur_tran_num'],
                    'pur_number'=>$row['pur_number'],
                    'requisition_number'=>$row['requisition_number'],
                    'create_time'=>date('Y-m-d H:i:s'),
                ];
                $res_inset= $this->purchase_db->insert('purchase_order_pay_baofo_detail',$baofo_detail);                
               if(empty($res_inset)) {
                    throw new Exception('记录付款流水明细失败');
                }
                //记录操作日志
                 $log = [
                'record_number' => isset($row['requisition_number']) ? $row['requisition_number'] : '',
                'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                'content' => '宝付支付提交成功',
                'content_detail' =>(isset($row['requisition_number']) ? $row['requisition_number'] : '') .'已提交宝付支付,待宝付支付审核'
                ];
               $this->Reject_note_model->get_insert_log($log);
               unset($baofo_detail);
            }   
                $reslut['supplier_code']= $supplier_code;
                $re_inset= $this->purchase_db->insert('purchase_order_pay_baofppay',$reslut);                
               if(empty($re_inset)) {
                    throw new Exception('记录付款流水失败');
                }
            if ($this->purchase_db->trans_status() === FALSE)
            {  
                $this->purchase_db->trans_rollback();
                 return ['code'=>false,'message'=>'宝付支付提交失败'];
            }
            else
            {
                $this->purchase_db->trans_commit();
                 return ['code'=>TRUE,'message'=>'宝付支付提交成功,等待审核'];
            }
        } catch (Exception $exc) {
            return ['code' => false, 'message' => $exc->getMessage()];
        }
    }
    
    /**
     * 更新数据
     * @author harvin
     * @date2019-8-7
     * @param array $reslut
     * @param array $ids
     * @return type
     * @throws Exception
     */
    public function faofopay_pay_update(array $ids, array $reslut, $trans_summary = '宝付付款成功', $type = true){
        $data = [];
        if($type){
            $data = isset($reslut['trans_content']['trans_reqDatas'][0]['trans_reqData']) ? $reslut['trans_content']['trans_reqDatas'][0]['trans_reqData'] : [];
        }else{
            $data = $reslut;
        }
        if(empty($data)){
            return ['code' => false, 'message' => '宝付返回数据为空'];
        }
        $pay_time  = date("Y-m-d H:i:s");
        $data_list = [];
        if(count($data) == count($data, 1)){
            $data_list[] = $data;
        }else{
            $data_list = $data;
        }
        try{
            //开启事物
            $this->purchase_db->trans_begin();
            foreach($data_list as $row){
                $baofppay = $this->purchase_db->select('id')->where('pur_tran_num', $row['trans_no'])->get('purchase_order_pay_baofppay')->row_array();
                if(!in_array($baofppay['id'], $ids)){
                    continue;
                }
                //更新宝付流水表
                $data_baofoo = [
                    'trans_batchid' => $row['trans_batchid'],
                    'trans_orderid' => $row['trans_orderid'],
                    'audit_status'  => BAOFOOPAYSTATUS_2,
                    'trans_summary' => $trans_summary,
                    'trans_date'    => date('Y-m-d H:i:s'),
                    'drawee'        => !empty(getActiveUserName()) ? getActiveUserName() : "system",
                    'drawee_id'     => getActiveUserId(),
                    'pay_time'      => $pay_time,
                    'drawee_time'   => date('Y-m-d H:i:s')
                ];
                $this->purchase_db->where('pur_tran_num', $row['trans_no'])->update('purchase_order_pay_baofppay', $data_baofoo);
                if($type){
                    //存在入redis中  防止网络超时 无法更新数据
                    $key_ids = "BAOFOOPAY".$baofppay['id'];
                    $this->rediss->setData($key_ids, $row);
                    $this->rediss->lpushData('BAOFOOPAY', $baofppay['id']);
                }

                /*
                24896：支付平台在线审核页面付款状态变为【收款成功】的时候，再来更改应付款管理里面的付款状态为【已付款】
                @user Jolon @date 2020-09-22 14:33
                //获取请款单号
                $baofo_detail            = $this->purchase_db->select('requisition_number')->where('pur_tran_num', $row['trans_no'])->get('purchase_order_pay_baofo_detail')->result_array();
                $requisition_number_list = is_array($baofo_detail) ? array_column($baofo_detail, 'requisition_number') : [];
                if(empty($requisition_number_list)){
                    throw new Exception('宝付流水未绑定请款单号');
                }
                $requisition_number_list = array_unique($requisition_number_list);
                $this->update_order_pay_compact($requisition_number_list, $trans_summary, $pay_time);

                // 更新请款单记录
                $data_pay = [
                    'pur_tran_num'  => $row['trans_no'],
                    'trans_orderid' => $row['trans_orderid'],
                    'payer_id'      => getActiveUserId(),
                    'payer_name'    => getActiveUserName(),
                ];
                $this->purchase_db->where_in('requisition_number', $requisition_number_list)->update('purchase_order_pay', $data_pay);
                */

            }
            if($this->purchase_db->trans_status() === false){
                $this->purchase_db->trans_rollback();
                return ['code' => false, 'message' => '宝付支付成功'];
            }else{
                $this->purchase_db->trans_commit();

                return ['code' => true, 'message' => '宝付支付成功'];
            }
        }catch(Exception $exc){
            return ['code' => false, 'message' => $exc->getMessage()];
        }
    }

    /**
     * 更新 宝付订单回执信息
     * @author Jolon
     * @param array $id
     * @param array $result
     * @return array
     */
    public function faofopay_voucher_update($id,$result){
        $update_arr = [];
        if(isset($result['voucher_address']) and $result['voucher_address']) $update_arr['voucher_address'] = $result['voucher_address'];
        if(isset($result['voucherId']) and $result['voucherId']) $update_arr['voucherId'] = $result['voucherId'];
        if(isset($result['voucher_name']) and $result['voucher_name']) $update_arr['voucher_name'] = $result['voucher_name'];
        if(isset($result['effective_time']) and $result['effective_time']) $update_arr['effective_time'] = $result['effective_time'];
        if(isset($result['voucher_size']) and $result['voucher_size']) $update_arr['voucher_size'] = $result['voucher_size'];
        if(isset($result['java_voucher_address']) and $result['java_voucher_address']) $update_arr['java_voucher_address'] = $result['java_voucher_address'];

        if(empty($update_arr)) return ['code' => false, 'message' => '没有要更新的数据'];

        $effects = $this->purchase_db->where('id', $id)->update('purchase_order_pay_baofppay', $update_arr);
        if($effects){
            return ['code' => true, 'message' => '宝付订单回执信息更新成功'];
        }else{
            return ['code' => false, 'message' => '宝付订单回执信息更新失败'];
        }
    }
      
   /**
    * 判断是否是全部付款还是部分付款
    * @param array $requisition_number_list
    * @param type $trans_summary
    * @param type $pay_time
    * @throws Exception
    */
    public function update_order_pay_compact(array $requisition_number_list,$trans_summary,$pay_time){
        $query = $this->purchase_db;
        foreach($requisition_number_list as $requisition_number){
            $order_pay = $query->select('pur_number,product_money,freight,process_cost,pay_price,'
                                        .'discount,requisition_number,pay_category,source,source_subject')
                ->where('requisition_number', $requisition_number)
                ->get('purchase_order_pay')
                ->row_array();
            if(empty($order_pay)){
                throw new Exception('请款单不存在');
            }

            $pay_price = $order_pay['pay_price'];
            $data = [
                'pay_status'      => PAY_PAID,
                'real_pay_price'  => $pay_price, //申请金额
//                'images'          => '',
//                'pay_account'     => '',
//                'pay_number'      => '',
//                'pay_branch_bank' => '',
                'payer_time'      => $pay_time,
                'payment_notice'  => $trans_summary,
                'payer_id'        => getActiveUserId(),
                'payer_name'      => !empty(getActiveUserName()) ? getActiveUserName() : "system",
            ];
            //更新请款单状态
            $this->purchase_db->where('requisition_number', $requisition_number)->update('purchase_order_pay', $data);

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
                $product_money              = isset($result['data']['product_money'])?$result['data']['product_money']:0; //总商品额
                $paid_total_product_money   = isset($result['data']['paid_total_product_money'])?$result['data']['paid_total_product_money']:0; //已取消金额
                $cancel_total_real_money    = isset($result['data']['cancel_total_real_money'])?$result['data']['cancel_total_real_money']:0; //已取消金额
                $has_pay                    = $paid_total_product_money;// 已经累加了 当前付款金额了

                $pay_status = PAY_PAID;
                if( $product_money - $cancel_total_real_money - $has_pay > IS_ALLOWABLE_ERROR){// 误差超过 1元是已部分付款，小于1元是 已付款
                    //该采购单付款状态 为部分付款
                    $pay_status = PART_PAID;
                }
                $result = $this->Compact_model->change_compact_pay_status($order_pay['pur_number'], $pay_status, $pay_time);
                if(!$result['code']){
                    throw new Exception($result['msg']);
                }
            }elseif($order_pay['source_subject'] == SOURCE_SUBJECT_STATEMENT_ORDER){// 对账单请款
                $this->load->model('statement/Purchase_statement_model');// 根据对账单查找
                $this->Purchase_statement_model->change_statement_pay_status($order_pay['pur_number'], PAY_PAID, $pay_time);
            }

            $this->load->model('Reject_note_model');
            //记录操作日志
            $log = [
                'record_number'  => $order_pay['pur_number'],
                'record_type'    => 'PUR_PURCHASE_ORDER_PAY',
                'content'        => '财务付款',
                'content_detail' => '请款单号'.$order_pay['requisition_number'].'宝付在线付款成功'
            ];
            $this->Reject_note_model->get_insert_log($log);
        }
    }

    public function get_order_requisition_method($pur_number){
        // 未完结状态的请款单
        $result = $this->purchase_db->select('requisition_method')
            ->where('pur_number',$pur_number)
            ->where_in('pay_status',[PAY_UNPAID_STATUS,PAY_WAITING_SOA_REVIEW,PAY_WAITING_MANAGER_REVIEW,PAY_WAITING_FINANCE_REVIEW,PAY_WAITING_FINANCE_PAID,PAY_UFXFUIOU_REVIEW,PAY_UFXFUIOU_SUPERVISOR,PAY_UFXFUIOU_MANAGER,PAY_WAITING_MANAGER_SUPPLY,PAY_UFXFUIOU_SUPPLY,PAY_UFXFUIOU_SUPPLY,PAY_GENERAL_MANAGER,PAY_UFXFUIOU_BAOFOPAY,PART_PAID,PAY_PAID])
            ->where(['requisition_method'=>4])
            ->get($this->table_name)
            ->row_array();
       return $result['requisition_method'];
    }

    /**
     * 判断是否尾款
     * product_money 订单总额
     * pay_price 请款金额
     */
    public function get_compact_number_by_id($ids)
    {
        if (empty($ids)){
            return [];
        }
        $map = [];
        $result = $this->purchase_db->select('a.id, a.pur_number, a.requisition_method, a.js_ratio, a.pay_ratio')
            ->from($this->table_name.' a')
            ->where_in('a.id',$ids)
            ->get()
            ->result_array();

        foreach ($result as $key => $item){
            $is_end = false;
            if ($item['requisition_method'] == PURCHASE_REQUISITION_METHOD_PERCENT){//比例请款
                $arr = explode('+',$item['js_ratio']);
                $ding_amount = $arr[0]??'';//订金
                $end_amount = $arr[1]??'';//尾款

                if ($ding_amount == '100%' && $item['pay_ratio'] == '100%'){//100%的也属于尾款
                    $is_end = true;
                }elseif ($end_amount == $item['pay_ratio']){
                    $is_end = true;
                }else{
                    $is_end = false;
                }

                if ($is_end){
                    $map[$item['id']] = [
                        'compact_number' => $item['pur_number'],//合同号
                    ];
                }
            }elseif ($item['requisition_method'] == PURCHASE_REQUISITION_METHOD_MANUAL){//手动请款
                $temp_arr[$item['id']] = $item['pur_number'];
            }
        }
        if (!empty($temp_arr)){
            $result = $this->purchase_db->select('a.id, a.pur_number, sum(a.pay_price) as pay_price, a.product_money, a.freight, a.discount,a.process_cost,a.commission')
                ->from($this->table_name.' a')
                ->where_in('pur_number',$temp_arr)
                ->where_in('requisition_method',PURCHASE_REQUISITION_METHOD_MANUAL)
                ->get()
                ->result_array();
            foreach ($result as $item){
                //实际总额 = 订单总额+运费-优惠额
                $real_money = bcadd(bcadd($item['product_money'],$item['freight'] + $item['process_cost'],3),- $item['discount'],3);

                if (bcsub($real_money,$item['pay_price'] ) < IS_ALLOWABLE_ERROR && bcsub($real_money,$item['pay_price']) > 0){//尾款
                    if (isset($temp_arr[$item['id']])){
                        $map[$item['id']] = $temp_arr[$item['id']];
                    }
                }
            }
        }
        //返回是尾款的数据
        return $map;
    }

    /**
     * 根据请款单号，获取所有采购单号
     * @param $requisition_number
     * @return string
     */
    private function _get_purchase_number_all($requisition_number){
        if(empty($requisition_number)) return '';
        $result = $this->purchase_db->select('GROUP_CONCAT(DISTINCT purchase_number) AS purchase_number')
            ->from('purchase_order_pay_detail')
            ->where('requisition_number', $requisition_number)
            ->group_by('requisition_number')
            ->get()->row_array();
        return !empty($result['purchase_number']) ? $result['purchase_number'] : '';
    }

    /**
     * 保存富友到拓展表
     * @param array $fuiouPayDatas 富友支付数据
     * @param float $procedure_party 运费承担方
     * @param float $procedure_fee 运费金额
     * @param float $account_short 支付账号简称
     * @return array
     * @throws Exception
     */
    public function  save_ufxfuiou($fuiouPayDatas,$procedure_party,$procedure_fee,$account_short){
        $this->load->model('system/Bank_card_model', 'bankCart');

        $ids = $fuiouPayDatas['ids'];
        $this->purchase_db->trans_begin();
        $ids =  explode(',', $ids);
        //校验成功后将数据插入数据表中
        //根据ID获取 合同单号与请款单号
        $wherecondition  = ['where_in' => ['id' =>$ids]];
        $orderPayfileds = 'id,requisition_number,pur_number,supplier_code';
        $pay_info = $this->purchase_order_pay_model->getDataByCondition($wherecondition, $orderPayfileds);
//        echo  $this->purchase_db->last_query();
        if (empty($pay_info)) {
            $message = '付款异常，请联系技术解决';
            $this->send_data(null, $message . " 返回请求地址", false);
        }
        $bankInfo = $this->bankCart->findOne(['account_short' => $account_short, 'status' => 1]);
        if(empty($bankInfo)){
            throw new Exception('支付账号获取失败');
        }

        //根据ID 更新付款单表的状态 为待富友审核
        $pay_update_date = [
            'pay_status'       => PAY_UFXFUIOU_REVIEW,
            'payment_platform' => 'ufxfuiou',
            'procedure_party'  => $procedure_party,
            'procedure_fee'    => $procedure_fee,
            'pay_account'      => $bankInfo['account_short'],
            'pay_number'       => $bankInfo['account_holder'],
            'k3_account'       => $bankInfo['k3_bank_account'],
            'pay_branch_bank'  => $bankInfo['branch'],
        ];
        $this->purchase_db->where_in('id', $ids)->update('purchase_order_pay',$pay_update_date);
        $supplier_code = $supplier_name = '';

        //添加数据到明细富友明细表
        foreach ($pay_info as $val){
            $this->load->model('compact/Compact_model');
            $result = $this->Compact_model->change_compact_pay_status($val['pur_number'], PAY_UFXFUIOU_REVIEW);
            if (!$result['code']) {
                throw new Exception($result['msg']);
            }
            $supplier_code = $val['supplier_code'];

            $ufxfuiou_detail = array(
                'pur_tran_num' => $fuiouPayDatas['Fuiou']['pur_tran_num'],
                'pay_id' => $val['id'],
                'pur_number' => $val['pur_number'],
                'requisition_number' => $val['requisition_number'],
//                'compact_number' => $val['compact_number'],
                'create_time' => date('Y-m-d H:i:s')
            );
            $res_inset= $this->purchase_db->insert('pay_ufxfuiou_detail_expand',$ufxfuiou_detail);
            if(empty($res_inset)) {
                throw new Exception('记录付款流水明细失败');
            }
            //记录操作日志
            $log = [
                'record_number' => isset($row['requisition_number']) ? $val['requisition_number'] : '',
                'record_type' => 'PUR_PURCHASE_ORDER_PAY',
                'content' => '富友支付提交成功',
                'content_detail' =>(isset($val['requisition_number']) ? $val['requisition_number'] : '') .'已提交富友支付,待富友支付审核'
            ];
            $this->Reject_note_model->get_insert_log($log);
            unset($ufxfuiou_detail);
        }
        //======
        $expand = array(
            'pur_tran_num' => $fuiouPayDatas['Fuiou']['pur_tran_num'],//交易流水号
            'to_acc_name' => $fuiouPayDatas['Fuiou']['oppositeName'],//收款方名称
            'to_bank_name' => $fuiouPayDatas['Fuiou']['bankId'],//支行号(或支行名称)
            'to_acc_no' => $fuiouPayDatas['Fuiou']['bankCardNo'],//收款账号
            'trans_card_id' => $fuiouPayDatas['Fuiou']['oppositeIdNo'],//收款方证件号
            'trans_mobile' => $fuiouPayDatas['Fuiou']['oppositeMobile'],//收款方手机号
            'pay_price' => $fuiouPayDatas['Fuiou']['amt'],//付款金额
            'supplier_code' => $supplier_code,//供应商
            'remark' => $fuiouPayDatas['Fuiou']['remark'],//申请备注
            'account_short' =>  $fuiouPayDatas['Fuiou']['PayInfo'],
            'applicant_id'   => getActiveUserId(),
            'applicant'      => getActiveUserName(),
            'applicant_time' => date("Y-m-d H:i:s")
        );
        $re_inset= $this->purchase_db->insert('pay_ufxfuiou_expand',$expand);
        unset($expand);
        if(empty($re_inset)) {
            throw new Exception('记录付款流水失败');
        }
        if($this->purchase_db->trans_status() === FALSE){
            $this->purchase_db->trans_rollback();
            return ['code'=>false,'message'=>'富友支付提交失败'];
        }else{
            $this->purchase_db->trans_commit();
            return ['code'=>TRUE,'message'=>'富友支付提交成功,等待审核'];
        }
    }
}