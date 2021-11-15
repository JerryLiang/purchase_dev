<?php
/**
 * Created by PhpStorm.
 * 合同单 主表信息
 * User: Jolon
 * Date: 2019/01/208 0027 11:23
 */

class Compact_model extends Purchase_model {

    protected $table_name = 'purchase_compact';

    /**
     * Compact_model constructor.
     */
    public function __construct(){
        parent::__construct();

        $this->load->model('compact_items_model','',false,'compact');// 采购单子表
        $this->load->model('purchase_order_model','',false,'purchase');
        $this->load->model('supplier_joint_model');
        $this->load->helper('common');
    }

    /**
     * 生成最新的 合同单号
     * @author Jolon
     * @param int $type 合同类型
     * @param bool $virtual 虚拟单号（返回如 PO-HTxxxxxx）
     * @param array $purchase_type_ids 业务线列表（多个业务线会组合生成合同号）
     * @return mixed
     */
    public function getCompactNumber($type,$virtual = false,$purchase_type_ids = []){
        if(count(array_unique($purchase_type_ids)) >= 2 ){
            $type = PURCHASE_TYPE_PFB;// PFB
        }

        switch($type){
            case PURCHASE_TYPE_INLAND:// 国内
                $type = 'PO-HT';
                break;
            case PURCHASE_TYPE_OVERSEA:// 海外
                $type = 'ABD-HT';
                break;
            case PURCHASE_TYPE_FBA_BIG:// FBA大货
                $type = 'FBA-HT';
                break;
            case PURCHASE_TYPE_FBA:// FBA
                $type = 'FBA-HT';
                break;
            case PURCHASE_TYPE_PFB:// PFB
                $type = 'PFB-HT';
                break;
            case PURCHASE_TYPE_PFH:// PFH
                $type = 'PO-HT';
                break;
            default:// 默认国内
                $type = 'PO-HT';
                break;
        }

        if($virtual){
            return $type.'xxxxxx';
        }

        $this->load->model('prefix_number_model');// 数据表前缀
        $new_purchase_number = $this->prefix_number_model->get_prefix_new_number($type);
        return $new_purchase_number;
    }


    /**
     * 获取一个指定的合同信息
     * @author Jolon
     * @param string    $compact_number    合同编号
     * @param bool      $have_items         是否附带合同明细
     * @return mixed
     */
    public function get_compact_one($compact_number,$have_items = true){
        $query_builder      = $this->purchase_db;
        $query_builder->where('compact_number',$compact_number);
        $results            = $query_builder->get($this->table_name)->row_array();

        if($results and $have_items){// 附带合同明细信息
            $items = $this->compact_items_model->get_compact_item($compact_number);
            $results['items_list'] = $items;
        }

        return $results;
    }

    /**
     * 创建 合同单
     * @author Jolon
     * @param array $compact_data  合同数据
     * @param array $post_data 用户修改提交的数据
     * @return array
     */
    public function create_compact($compact_data,$post_data){
        $return = ['code' => false, 'msg' => '', 'data' => ''];

        $compact_main    = $compact_data['compact_main'];
        $compact_details = $compact_data['compact_details'];
        if(empty($compact_main) or empty($compact_details)){
            $return['msg'] = '合同主信息或明细信息缺失';
            return $return;
        }

        $require_info = compactRequireInfo();// 合同模板相关要求的数据

        $this->purchase_db->trans_begin();
        try{
            $payment_explain   = $this->get_payment_settlement_requests($compact_main['account_type']);
            if(empty($payment_explain)){
                throw new Exception("结算方式未设定付款说明信息，请确认后操作");
            }
            $payment_explain   = json_encode($payment_explain, JSON_UNESCAPED_UNICODE);
            $ship_method       = $require_info['ship_method'];
            $cooperate_require = json_encode($require_info['cooperate_require_for_text'], JSON_UNESCAPED_UNICODE);
            $contract_require  = json_encode($require_info['contract_require'], JSON_UNESCAPED_UNICODE);
            $quality_require   = json_encode($require_info['quality_require'], JSON_UNESCAPED_UNICODE);
            $warehouseAddress  = $compact_main['receive_address'];
            $a_phone           = $compact_main['a_phone'];
//            if(!empty($post_data['a_user_phone']) and $post_data['a_user_phone']){
//                $a_phone       = $post_data['a_user_phone'];
//                $warehouseAddress  = getTransitWarehouseInfo($compact_main['warehouse_code'],'receive_address',$compact_main['a_linkman'],$a_phone);
//            }

            if(empty($a_phone)){
                $return['msg'] = '甲方联系电话为空';
                throw new Exception($return['msg']);
            }
            // 合同主表信息
            $mk= isset($compact_main['payment_list']['mk'])?$compact_main['payment_list']['mk']:0;
            $compact_main_tmp = [
                'supplier_name'         => $compact_main['supplier_name'],
                'supplier_code'         => $compact_main['supplier_code'],
                'warehouse_name'        => $compact_main['warehouse_name'],
                'warehouse_code'        => $compact_main['warehouse_code'],
                'delivery_date'         => $compact_main['delivery_date'],
                'purchase_name'         => $compact_main['purchase_name'],
                'source'                => $compact_main['purchase_type_id'],
                'pay_type'              => $compact_main['pay_type'],
                'settlement_method'     => $compact_main['account_type'],
                'settlement_ratio'      => $compact_main['settlement_ratio'],
                'is_drawback'           => $compact_main['is_drawback'],
                'is_freight'            => $compact_main['is_freight'],
                'freight_formula_mode'  => $compact_main['freight_formula_mode'],
                'a_company_name'        => $compact_main['a_company_name'],
                'a_address'             => $compact_main['a_address'],
                'a_linkman_id'          => $compact_main['a_linkman_id'],
                'a_linkman'             => $compact_main['a_linkman'],
                'a_phone'               => $a_phone,
                'a_email'               => $compact_main['a_email'],
                'b_company_name'        => $compact_main['b_company_name'],
                'b_corporate'           => $compact_main['b_corporate'],
                'b_address'             => $compact_main['b_address'],
                'b_linkman'             => $compact_main['b_linkman'],
                'b_phone'               => $compact_main['b_phone'],
                'b_email'               => $compact_main['b_email'],
                'note_freight'          => '',
                'note_other'            => '',

                // 合同要求信息
                'ship_method'           => $ship_method,// 送货方式
                'receive_address'       => $warehouseAddress,
                'payment_explain'       => $payment_explain,// 付款说明
                'cooperate_require'     => $cooperate_require,// 合作要求
                'remit_information'     => $compact_main['b_payment_info'],
                'contract_require'      => $contract_require,// 合约要求
                'quality_require'       => $quality_require,// 质检要求
                'product_standard'      => '',
                'package_require'       => '',
                'after_sales_items'     => '',
                'supply_clause'         => '',
                'other_require'         => '',

                'product_money'         => $compact_main['total_price'],
                'freight'               => $compact_main['total_freight'],
                'discount'              => $compact_main['total_discount'],
                'process_cost'          => $compact_main['total_process_cost'],

                'real_money'            => $compact_main['payment_list']['real_money'],
                'earnest_money'         => $compact_main['payment_list']['dj'],
                'tail_money'            => $compact_main['payment_list']['wk']+$mk,
                'tail_total_money'      => $compact_main['payment_list']['wk_t']+$mk,
            ];

            $order_info_list   = arrayKeyToColumn($compact_main['order_info_list'],'purchase_number');
            $purchase_type_ids = array_unique(array_column($order_info_list,'purchase_type_id'));
            $main_result       = $this->saveCompactData($compact_main_tmp,$purchase_type_ids);
            if($main_result['code'] === false or empty($main_result['data'])){
                throw new Exception($main_result['msg']);
            }

            $compact_number = $main_result['data'];

            $compact_sku_spec_tmp = [];
            $item_id = isset($post_data['item_id'])?$post_data['item_id']:[];
            foreach($compact_details as $value_list){
                foreach($value_list as $value){
                    $compact_sku_spec_tmp[$value['purchase_number']][$value['sku']]['sku_spec'] = isset($item_id[$value['item_id']])?$item_id[$value['item_id']]:'';
                }
            }
            $purchase_numbers = array_keys($compact_details);// 获取目标采购单号
            if($purchase_numbers){
                foreach($purchase_numbers as $purchase_number){ // 生成合同明细
                    $sku_spec_tmp = isset($compact_sku_spec_tmp[$purchase_number])?$compact_sku_spec_tmp[$purchase_number]:[];
                    $compact_items_tmp = [
                        'compact_number'    => $compact_number,
                        'purchase_number'   => $purchase_number,
                        'sku_info'          => json_encode($sku_spec_tmp),
                        'buyer_id'          => isset($order_info_list[$purchase_number])?$order_info_list[$purchase_number]['buyer_id']:0,
                        'buyer_name'        => isset($order_info_list[$purchase_number])?$order_info_list[$purchase_number]['buyer_name']:0,
                        'warehouse_code'    => isset($order_info_list[$purchase_number])?$order_info_list[$purchase_number]['warehouse_code']:0,
                    ];
                    $item_result = $this->saveCompactItem($compact_items_tmp);
                    //根据采购单号更新核销入库明细表合同号
                    $this->purchase_db->update('statement_warehouse_results', ['compact_number' => $compact_number], ['purchase_number' => $purchase_number]);
                    $this->purchase_db->update('purchase_order_charge_against_surplus', ['compact_number' => $compact_number], ['purchase_number' => $purchase_number]);
                    if(empty($item_result)){
                        throw new Exception('创建合同明细保存失败');
                    }

                    $order_result_1 = $this->purchase_order_model->change_status($purchase_number, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL);// 采购单状态变为 信息修改待审核
                    if(stripos($purchase_number,'ABD') !== false){
                        $order_result = $this->purchase_db->where('purchase_number',$purchase_number)->update('purchase_order',['push_to_wms' => 1,'is_generate' => 2]);
                    }else{
                        $order_result = $this->purchase_db->where('purchase_number',$purchase_number)->update('purchase_order',['push_to_wms' => 1,'is_generate' => 2]);
                    }
                    if(empty($order_result_1) or empty($order_result)){
                        throw new Exception('创建合同-更新采购单状态为【等待到货】失败');
                    }
                    operatorLogInsert(['id' => $purchase_number,'type' => 'purchase_order','content' => '生成进货单','detail' => '生成合同号'.$compact_number]);

                    $this->load->model('warehouse/parcel_urgent_model');

                    $add_parcel_result = $this->parcel_urgent_model->auto_add_parcel($purchase_number);

                    if (!$add_parcel_result) {
                        throw new Exception('生成包裹加急记录失败');
                    }
                }
            }else{
                throw new Exception('创建采购合同-合同明细缺失');
            }

            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('创建采购合同事务提交出错');
            } else {
                $this->purchase_db->trans_commit();
            }

            $return['code'] = true;
            $return['data'] = $compact_number;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }


    /**
     * 保存  合同主表数据
     * @author Jolon
     * @param array $compact_main 合同数据
     * @param array $purchase_type_ids 合同的所有业务线
     * @return array
     */
    public function saveCompactData($compact_main,$purchase_type_ids = []){
        $return = ['code' => true, 'msg' => '', 'data' => ''];

        $insert_compact_main = [];
        isset($compact_main['supplier_id']) and $insert_compact_main['supplier_id'] = $compact_main['supplier_id'];
        isset($compact_main['supplier_name']) and $insert_compact_main['supplier_name'] = $compact_main['supplier_name'];
        isset($compact_main['supplier_code']) and $insert_compact_main['supplier_code'] = $compact_main['supplier_code'];
        isset($compact_main['warehouse_name']) and $insert_compact_main['warehouse_name'] = $compact_main['warehouse_name'];
        isset($compact_main['warehouse_code']) and $insert_compact_main['warehouse_code'] = $compact_main['warehouse_code'];
        isset($compact_main['delivery_date']) and $insert_compact_main['delivery_date'] = $compact_main['delivery_date'];
        isset($compact_main['purchase_name']) and $insert_compact_main['purchase_name'] = $compact_main['purchase_name'];
        isset($compact_main['source']) and $insert_compact_main['source'] = $compact_main['source'];
        isset($compact_main['pay_type']) and $insert_compact_main['pay_type'] = $compact_main['pay_type'];
        isset($compact_main['settlement_method']) and $insert_compact_main['settlement_method'] = $compact_main['settlement_method'];
        isset($compact_main['settlement_ratio']) and $insert_compact_main['settlement_ratio'] = $compact_main['settlement_ratio'];
        isset($compact_main['is_drawback']) and $insert_compact_main['is_drawback'] = $compact_main['is_drawback'];
        isset($compact_main['is_freight']) and $insert_compact_main['is_freight'] = $compact_main['is_freight'];
        isset($compact_main['freight_formula_mode']) and $insert_compact_main['freight_formula_mode'] = $compact_main['freight_formula_mode'];

        // 甲方公司信息
        isset($compact_main['a_company_name']) and $insert_compact_main['a_company_name'] = $compact_main['a_company_name'];
        isset($compact_main['a_address']) and $insert_compact_main['a_address'] = $compact_main['a_address'];
        isset($compact_main['a_linkman_id']) and $insert_compact_main['a_linkman_id'] = $compact_main['a_linkman_id'];
        isset($compact_main['a_linkman']) and $insert_compact_main['a_linkman'] = $compact_main['a_linkman'];
        isset($compact_main['a_phone']) and $insert_compact_main['a_phone'] = $compact_main['a_phone'];
        isset($compact_main['a_email']) and $insert_compact_main['a_email'] = $compact_main['a_email'];
        isset($compact_main['receive_address']) and $insert_compact_main['receive_address'] = $compact_main['receive_address'];

        // 乙方公司信息
        isset($compact_main['b_company_name']) and $insert_compact_main['b_company_name'] = $compact_main['b_company_name'];
        isset($compact_main['b_corporate']) and $insert_compact_main['b_corporate'] = $compact_main['b_corporate'];
        isset($compact_main['b_address']) and $insert_compact_main['b_address'] = $compact_main['b_address'];
        isset($compact_main['b_linkman']) and $insert_compact_main['b_linkman'] = $compact_main['b_linkman'];
        isset($compact_main['b_phone']) and $insert_compact_main['b_phone'] = $compact_main['b_phone'];
        isset($compact_main['b_email']) and $insert_compact_main['b_email'] = $compact_main['b_email'];

        isset($compact_main['note_freight']) and $insert_compact_main['note_freight'] = $compact_main['note_freight'];
        isset($compact_main['note_other']) and $insert_compact_main['note_other'] = $compact_main['note_other'];
        isset($compact_main['ship_method']) and $insert_compact_main['ship_method'] = $compact_main['ship_method'];

        // 合同要求详情信息
        isset($compact_main['payment_explain']) and $insert_compact_main['payment_explain'] = $compact_main['payment_explain'];
        isset($compact_main['cooperate_require']) and $insert_compact_main['cooperate_require'] = $compact_main['cooperate_require'];
        isset($compact_main['remit_information']) and $insert_compact_main['remit_information'] = $compact_main['remit_information'];
        isset($compact_main['contract_require']) and $insert_compact_main['contract_require'] = $compact_main['contract_require'];
        isset($compact_main['quality_require']) and $insert_compact_main['quality_require'] = $compact_main['quality_require'];
        isset($compact_main['product_standard']) and $insert_compact_main['product_standard'] = $compact_main['product_standard'];
        isset($compact_main['package_require']) and $insert_compact_main['package_require'] = $compact_main['package_require'];
        isset($compact_main['after_sales_items']) and $insert_compact_main['after_sales_items'] = $compact_main['after_sales_items'];
        isset($compact_main['supply_clause']) and $insert_compact_main['supply_clause'] = $compact_main['supply_clause'];
        isset($compact_main['other_require']) and $insert_compact_main['other_require'] = $compact_main['other_require'];


        // 合同金额
        isset($compact_main['product_money']) and $insert_compact_main['product_money'] = $compact_main['product_money'];
        isset($compact_main['freight']) and $insert_compact_main['freight'] = $compact_main['freight'];
        isset($compact_main['discount']) and $insert_compact_main['discount'] = $compact_main['discount'];
        isset($compact_main['process_cost']) and $insert_compact_main['process_cost'] = $compact_main['process_cost'];
        isset($compact_main['real_money']) and $insert_compact_main['real_money'] = $compact_main['real_money'];
        isset($compact_main['earnest_money']) and $insert_compact_main['earnest_money'] = $compact_main['earnest_money'];
        isset($compact_main['tail_money']) and $insert_compact_main['tail_money'] = $compact_main['tail_money'];
        isset($compact_main['tail_total_money']) and $insert_compact_main['tail_total_money'] = $compact_main['tail_total_money'];
        isset($compact_main['other_require']) and $insert_compact_main['other_require'] = $compact_main['other_require'];

        if(empty($compact_main['compact_number'])){
            $compact_number = $this->getCompactNumber($compact_main['source'],false,$purchase_type_ids);
            $insert_compact_main['compact_number']  = $compact_number;
            $insert_compact_main['compact_status']  = COMPACT_STATUS_AUDIT_PASS;// 10.待处理,20.已审核
            $insert_compact_main['payment_status']  = PAY_UNPAID_STATUS;// 10.未申请付款

            $insert_compact_main['create_user_id']  = getActiveUserId();
            $insert_compact_main['create_user_name'] = getActiveUserName();
            $insert_compact_main['create_time']      = date('Y-m-d H:i:s');

            $result = $this->purchase_db->insert($this->table_name,$insert_compact_main);
        }else{
            $compact_number                         = $compact_main['compact_number'];
            $insert_compact_main['modify_user_id']  = getActiveUserId();
            $insert_compact_main['modify_user_name'] = getActiveUserName();
            $insert_compact_main['modify_time']      = date('Y-m-d H:i:s');

            $result = $this->purchase_db->where('compact_number',$compact_number)
                ->update($this->table_name,$insert_compact_main);
        }

        if($result){
            $return['data'] = $compact_number;
        }else{
            $return['code'] = false;
            $return['msg'] = '合同主表记录保存失败';
        }
        return $return;

    }


    /**
     * 保存  合同明细表数据
     * @author Jolon
     * @param array $compact_item 合同明细数据
     * @param int   $bind         绑定或解绑（默认 1.绑定,2.解绑）
     * @return bool  true.成功，false.失败
     */
    public function saveCompactItem($compact_item,$bind = 1){
        $compact_number     = isset($compact_item['compact_number'])?$compact_item['compact_number']:'';
        $purchase_number    = isset($compact_item['purchase_number'])?$compact_item['purchase_number']:'';
        $sku_info           = isset($compact_item['sku_info'])?$compact_item['sku_info']:'';
        $buyer_id           = isset($compact_item['buyer_id'])?$compact_item['buyer_id']:'';
        $buyer_name         = isset($compact_item['buyer_name'])?$compact_item['buyer_name']:'';
        $warehouse_code     = isset($compact_item['warehouse_code'])?$compact_item['warehouse_code']:'';

        if(empty($compact_number) or empty($purchase_number)) return false;

        $exists = $this->purchase_db->where('compact_number',$compact_number)
            ->where('purchase_number',$purchase_number)
            ->get('purchase_compact_items')
            ->row_array();
        if($exists){
            $compact_items_tmp = [
                'modify_user_name' => getActiveUserName(),
                'modify_time'      => date('Y-m-d H:i:s'),
                'bind'             => $bind,
                'sku_info'         => $sku_info,
            ];

            $result = $this->purchase_db->where('id', $exists['id'])->update('purchase_compact_items', $compact_items_tmp);

            if($bind != 1){
                operatorLogInsert(['id' => $compact_number,'type' => 'purchase_compact_items','content' => '删除合同明细','detail' => '采购单号'.$purchase_number]);
            }
        }else{
            $compact_items_tmp = [
                'compact_number'   => $compact_number,
                'purchase_number'  => $purchase_number,
                'sku_info'         => $sku_info,
                'buyer_id'         => $buyer_id,
                'buyer_name'       => $buyer_name,
                'warehouse_code'   => $warehouse_code,
                'bind'             => 1,
                'create_user_name' => getActiveUserName(),
                'create_time'      => date('Y-m-d H:i:s'),
            ];
            $result = $this->purchase_db->insert('purchase_compact_items',$compact_items_tmp);
            operatorLogInsert(['id' => $compact_number,'type' => 'purchase_compact_items','content' => '创建合同明细','detail' => '采购单号'.$purchase_number]);
        }

        if($result){
            return $result;
        }else{
            return false;
        }

    }


    /**
     * 获取 合同单 - 请款相关金额
     *      合同金额、合同已付款、合同已取消、合同剩余可请款（商品金额，请款金额，运费，优惠额，加工费）
     * @author Jolon
     * @param string $compact_number 合同号
     * @return array|bool
     */
    public function calculate_compact_related_amount($compact_number){
        $return = ['code' => false, 'msg' => '', 'data' => ''];

        $compact = $this->get_compact_one($compact_number);
        if(empty($compact)){
            $return['msg'] = '未找到合同信息';
            return $return;
        }

        $this->load->model('finance/purchase_order_pay_model','',false,'finance');
        $this->load->model('purchase_order_model','',false,'purchase');
        $this->load->model('purchase_order_cancel_model','',false,'purchase');
        $this->load->model('statement/Charge_against_records_model');
        $this->load->model('statement/Charge_against_surplus_model');

        $amount_list = [];// 必须是一维数组
        // 合同金额 （商品金额，请款金额，运费，优惠额）
        $amount_list['product_money'] = $compact['product_money'];// 订单总额（商品总额）
        $amount_list['real_money']    = $compact['real_money'];// 实际总额（请款金额）
        $amount_list['freight']       = $compact['freight'];// 运费
        $amount_list['discount']      = $compact['discount'];// 优惠额
        $amount_list['process_cost']  = $compact['process_cost'];// 加工费
    
        // 合同已付款 （商品金额，请款金额，运费，优惠额）
        $compact_paid_info                       = $this->purchase_order_pay_model->get_pay_total_by_compact_number($compact_number,array_column($compact['items_list'],'purchase_number'));
        $amount_list['paid_total_product_money'] = isset($compact_paid_info['product_money']) ? $compact_paid_info['product_money'] : 0;// 已请商品金额
        $amount_list['paid_total_real_money']    = isset($compact_paid_info['pay_price']) ? $compact_paid_info['pay_price'] : 0;// 已请款金额
        $amount_list['paid_total_freight']       = isset($compact_paid_info['freight']) ? $compact_paid_info['freight'] : 0;// 已请运费
        $amount_list['paid_total_discount']      = isset($compact_paid_info['discount']) ? $compact_paid_info['discount'] : 0;// 已请优惠额
        $amount_list['paid_total_process_cost']  = isset($compact_paid_info['process_cost']) ? $compact_paid_info['process_cost'] : 0;// 已请加工费

        // 合同已取消 （商品金额，请款金额，运费，优惠额）
        $amount_list['cancel_total_product_money'] = 0;// 已取消商品金额
        $amount_list['cancel_total_real_money']    = 0;// 已取消请款金额
        $amount_list['cancel_total_freight']       = 0;// 已取消运费
        $amount_list['cancel_total_discount']      = 0;// 已取消优惠额
        $amount_list['cancel_total_process_cost']  = 0;// 已取消加工费

        $amount_list['ca_total_product_money']     = 0;// 已退款冲销商品额-已抵扣金额
        $amount_list['ca_total_process_cost']      = 0;// 已退款冲销加工费-已抵扣金额
        $amount_list['ca_total_real_money']        = 0;// 已退款冲销总额-已抵扣金额

        $amount_list['real_refund_product_money']  = 0;// 供应商总退款商品额
        $amount_list['real_refund_freight']        = 0;// 供应商总退款运费
        $amount_list['real_refund_discount']       = 0;// 供应商总退款优惠额
        $amount_list['real_refund_process_cost']   = 0;// 供应商总退款加工费
        $amount_list['real_refund_amount']         = 0;// 供应商总退款金额

        $purchase_numbers = array_unique(array_column($compact['items_list'],'purchase_number'));
        if(empty($purchase_numbers)) {
            $return['code'] = true;
            $return['data'] = [];
            return $return;
        }

        $po_surplus_list = $this->Charge_against_surplus_model->get_surplus($purchase_numbers);// 获取冲销剩余汇总信息

        foreach($po_surplus_list as $po_surplus_value){
            // 供应商已退款金额
            $amount_list['real_refund_product_money']  += $po_surplus_value['real_refund_product_money'];
            $amount_list['real_refund_freight']        += $po_surplus_value['real_refund_freight'];
            $amount_list['real_refund_discount']       += $po_surplus_value['real_refund_discount'];
            $amount_list['real_refund_process_cost']   += $po_surplus_value['real_refund_process_cost'];
            $amount_list['real_refund_amount']         += $po_surplus_value['real_refund_amount'];

            // 取消金额
            $amount_list['cancel_total_product_money'] += $po_surplus_value['cancel_product_money'];
            $amount_list['cancel_total_freight']       += $po_surplus_value['cancel_freight'];
            $amount_list['cancel_total_discount']      += $po_surplus_value['cancel_discount'];
            $amount_list['cancel_total_process_cost']  += $po_surplus_value['cancel_process_cost'];
            $amount_list['cancel_total_real_money']    += $po_surplus_value['cancel_real_price'];
        }

        foreach($purchase_numbers as $purchase_number_value){
            $charge_against_price                   = $this->Charge_against_records_model->get_ca_total_price_list($purchase_number_value,2,[CHARGE_AGAINST_STATUE_WAITING_PASS]);
            $amount_list['ca_total_process_cost']   += $charge_against_price['charge_against_process_cost'];// 已退款冲销商品额
            $amount_list['ca_total_product_money']  += $charge_against_price['charge_against_product'];// 已退款冲销商品额
        }
        $amount_list['ca_total_real_money']         = $amount_list['ca_total_process_cost'] + $amount_list['ca_total_product_money'];// 已退款冲销商品额

        // 退款冲销金额 累加到已付金额里面
        $amount_list['paid_total_product_money'] += $amount_list['ca_total_product_money'];
        $amount_list['paid_total_real_money']    += $amount_list['ca_total_real_money'];
        $amount_list['paid_total_process_cost']  += $amount_list['ca_total_process_cost'];

        // 剩余可请款 （商品金额，请款金额，运费，优惠额）
        $amount_list['available_product_money'] = format_price($amount_list['product_money'] - $amount_list['paid_total_product_money'] - $amount_list['cancel_total_product_money'] + $amount_list['real_refund_product_money']);
        $amount_list['available_real_money']    = format_price($amount_list['real_money'] - $amount_list['paid_total_real_money'] - $amount_list['cancel_total_real_money'] + $amount_list['real_refund_amount']);
        $amount_list['available_freight']       = format_price($amount_list['freight'] - $amount_list['paid_total_freight'] - $amount_list['cancel_total_freight'] + $amount_list['real_refund_freight']);
        $amount_list['available_discount']      = format_price($amount_list['discount'] - $amount_list['paid_total_discount'] - $amount_list['cancel_total_discount'] + $amount_list['real_refund_discount']);
        $amount_list['available_process_cost']  = format_price($amount_list['process_cost'] - $amount_list['paid_total_process_cost'] - $amount_list['cancel_total_process_cost'] + $amount_list['real_refund_process_cost']);

        foreach($amount_list as &$amount_list_value){// 格式化 小数点位数
            $amount_list_value  = format_price($amount_list_value);
        }

        $return['code'] = true;
        $return['data'] = $amount_list;
        return $return;
    }

    /**
     * 计算合同单  可申请报损金额
     *      在原有数据的基础上追加 可申请报损金额
     * @param string $compact_number
     * @param array $items_list 合同明细（二维数组）
     * @return array|bool
     */
    public function calculate_compact_available_reportloss($compact_number = null,$items_list = null){
        $return = ['code' => false, 'msg' => '', 'data' => ''];

        if(empty($items_list) and empty($compact_number)) return false;

        if(empty($items_list)){
            $compact = $this->get_compact_one($compact_number);
            if(empty($compact)){
                $return['msg'] = '未找到合同信息';
                return $return;
            }
            $items_list = $compact['items_list'];
        }

        $this->load->model('abnormal/Report_loss_model');
        $this->load->model('purchase_order_model','',false,'purchase');
        $this->load->model('purchase_order_cancel_model','',false,'purchase');

        $purchase_numbers = array_unique(array_column($items_list,'purchase_number'));

        // 报损请款方式 SKU的已付款金额
        $paid_price_list = $this->Report_loss_model->get_paid_loss_money_by_purchase_number_sku($purchase_numbers);
        $paid_price_list = arrayKeyToColumnMulti($paid_price_list,'purchase_number','sku');
        // 报损请款方式 PO的已付款金额明细（PO维度）
        $po_paid_price_list = $this->Report_loss_model->get_paid_loss_money_by_purchase_number($purchase_numbers);
        $po_paid_price_list = arrayKeyToColumn($po_paid_price_list,'purchase_number');
        // 已审核通过的报损金额（PO维度）
        $po_loss_price_list = $this->Report_loss_model->get_loss_money_by_purchase_number($purchase_numbers);
        $po_loss_price_list = arrayKeyToColumn($po_loss_price_list,'pur_number');
        // 已审核通过的报损金额（SKU维度）
        $loss_price_list = $this->Report_loss_model->get_loss_money_by_purchase_number_sku($purchase_numbers);
        $loss_price_list = arrayKeyToColumnMulti($loss_price_list,'pur_number','sku');


        // 在原有数据的基础上追加 可申请报损金额
        foreach($items_list as &$compact_item){
            $purchase_number = $compact_item['purchase_number'];
            $sku = $compact_item['sku'];

            // 按SKU维度 获取已付款报损商品金额
            if(isset($paid_price_list[$purchase_number][$sku])){
                $paid_product_money = $paid_price_list[$purchase_number][$sku]['paid_product_money'];
            }else{
                $paid_product_money = 0;
            }

            // 按PO维度 获取已付款报损运费、加工费
            if(isset($po_paid_price_list[$purchase_number])){
                $po_paid_freight       = $po_paid_price_list[$purchase_number]['paid_freight'];
                $po_paid_process_cost  = $po_paid_price_list[$purchase_number]['paid_process_cost'];
                $po_paid_real_price    = $po_paid_price_list[$purchase_number]['paid_real_price'];
            }else{
                $po_paid_freight       = 0;
                $po_paid_process_cost  = 0;
                $po_paid_real_price    = 0;
            }

            // 按SKU维度 获取总的审核通过的报损商品金额
            if(isset($loss_price_list[$purchase_number][$sku])){
                $loss_amount        = $loss_price_list[$purchase_number][$sku]['loss_amount'];
                $loss_product_money = $loss_price_list[$purchase_number][$sku]['loss_product_money'];
            }else{
                $loss_amount        = 0;
                $loss_product_money = 0;
            }


            // 按PO维度 获取已总的审核通过的报损运费、加工费
            if(isset($po_loss_price_list[$purchase_number])){
                $loss_totalprice    = $po_loss_price_list[$purchase_number]['loss_totalprice'];
                $loss_freight       = $po_loss_price_list[$purchase_number]['loss_freight'];
                $loss_process_cost  = $po_loss_price_list[$purchase_number]['loss_process_cost'];
            }else{
                $loss_totalprice    = 0;
                $loss_freight       = 0;
                $loss_process_cost  = 0;
            }

            // SKU维度
            // 可申请报损金额
            $compact_item['loss_amount']                  = $loss_amount;// 报损数量
            $compact_item['available_loss_product_money'] = $loss_product_money - $paid_product_money;// 报损请款的 分摊金额按照 报损金额分摊

            // PO维度（每个SKU的PO维度金额相同）
            $compact_item['po_available_loss_freight']       = $loss_freight - $po_paid_freight;
            $compact_item['po_available_loss_process_cost']  = $loss_process_cost - $po_paid_process_cost;
            $compact_item['po_available_loss_real_price']    = $loss_totalprice - $po_paid_real_price;
        }

        $return['code'] = true;
        $return['data'] = $items_list;
        return $return;
    }

    /**
     * 获取 合同单 - 请款数据
     * @author Jolon
     * @param string $compact_number 合同号
     * @return array
     */
    public function get_compact_pay_data($compact_number){
        $return = ['code' => false, 'msg' => '', 'data' => ''];
        $this->load->model('purchase_order_model','',false,'purchase');
        $this->load->model('supplier_settlement_model','',false,'supplier');
        $this->load->model('purchase/Purchase_order_determine_model');
        $this->load->model('purchase_suggest/Purchase_suggest_map_model');
        $this->load->model('compact/Compact_file_model');
        $this->load->model('abnormal/Report_loss_model');
        $compact = $this->get_compact_one($compact_number);

        // 合同信息
        $compact_tmp                          = [];

        $compact_tmp['settlement_method']     = $compact['settlement_method'];
        $settlement_model                     = $this->supplier_settlement_model->get_settlement_one($compact['settlement_method']);// 结算费方式

        $compact_tmp['source']                = $compact['source'];
        $compact_tmp['compact_number']        = $compact['compact_number'];
        $compact_tmp['supplier_code']         = $compact['supplier_code'];
        $compact_tmp['supplier_name']         = $compact['supplier_name'];
        $compact_tmp['compact_status']        = $compact['compact_status'];
        $compact_tmp['purchase_name']         = $compact['purchase_name'];
        $compact_tmp['is_drawback']           = $compact['is_drawback'];
        $compact_tmp['is_drawback_cn']        = getIsDrawbackShow($compact['is_drawback']);
        $compact_tmp['settlement_ratio']      = $compact['settlement_ratio'];
        $compact_tmp['settlement_method_cn']  = isset($settlement_model['settlement_name']) ? $settlement_model['settlement_name'] : '';
        $compact_tmp['pay_type']              = getPayType($compact['pay_type']);
        $compact_tmp['is_freight']            = getFreightPayment($compact['is_freight']);
        $compact_tmp['freight_formula_mode']  = freight_formula_mode($compact['freight_formula_mode']);
   
        // 合同金额
        $amount_list                            = $this->calculate_compact_related_amount($compact_number);
        if($amount_list['code'] === false){
            $return['msg'] = $amount_list['msg'];
            return $return;
        }
        $amount_list                            = $amount_list['data'];
        $loss_money_list = $this->Report_loss_model->get_loss_money_by_purchase_number(array_column($compact['items_list'],'purchase_number'));

        // 采购金额
        $compact_tmp['product_money']           = isset($amount_list['product_money']) ? $amount_list['product_money'] : 0;// 订单总额（商品总额）
        $compact_tmp['freight']                 = isset($amount_list['freight']) ? $amount_list['freight'] : 0;// 运费
        $compact_tmp['discount']                = isset($amount_list['discount']) ? $amount_list['discount'] : 0;// 优惠额
        $compact_tmp['process_cost']            = isset($amount_list['process_cost']) ? $amount_list['process_cost'] : 0;// 加工费
        $compact_tmp['real_money']              = isset($amount_list['real_money']) ? $amount_list['real_money'] : 0;// 实际总额

        // 已付款金额
        $compact_tmp['paid_total_product_money'] = isset($amount_list['paid_total_product_money']) ? $amount_list['paid_total_product_money'] : 0;// 合同已付款金额
        $compact_tmp['paid_total_freight']       = isset($amount_list['paid_total_freight']) ? $amount_list['paid_total_freight'] : 0;// 合同已付款运费
        $compact_tmp['paid_total_discount']      = isset($amount_list['paid_total_discount']) ? $amount_list['paid_total_discount'] : 0;// 合同已付款优惠额
        $compact_tmp['paid_total_process_cost']  = isset($amount_list['paid_total_process_cost']) ? $amount_list['paid_total_process_cost'] : 0;// 合同已付款加工费
        $compact_tmp['paid_total_real_money']    = isset($amount_list['paid_total_real_money']) ? $amount_list['paid_total_real_money'] : 0;// 合同已付款总金额

        // 取消金额
        $compact_tmp['cancel_total_product_money'] = isset($amount_list['cancel_total_product_money']) ? $amount_list['cancel_total_product_money'] : 0;// 合同已取消金额
        $compact_tmp['cancel_total_real_money']    = isset($amount_list['cancel_total_real_money']) ? $amount_list['cancel_total_real_money'] : 0;// 合同已取消金额
        $compact_tmp['cancel_total_freight']       = isset($amount_list['cancel_total_freight']) ? $amount_list['cancel_total_freight'] : 0;// 合同已取消运费
        $compact_tmp['cancel_total_discount']      = isset($amount_list['cancel_total_discount']) ? $amount_list['cancel_total_discount'] : 0;// 合同已取消优惠
        $compact_tmp['cancel_total_process_cost']  = isset($amount_list['cancel_total_process_cost']) ? $amount_list['cancel_total_process_cost'] : 0;// 合同已取消加工费

        // 报损金额
        $compact_tmp['loss_total_product_money'] = array_sum(array_column($loss_money_list, 'loss_product_money'));// 报损商品金额
        $compact_tmp['loss_total_freight']       = array_sum(array_column($loss_money_list, 'loss_freight'));// 报损运费
        $compact_tmp['loss_total_discount']      = 0;// 报损优惠额
        $compact_tmp['loss_total_process_cost']  = array_sum(array_column($loss_money_list, 'loss_process_cost'));// 报损加工费
        $compact_tmp['loss_total_real_money']    = array_sum(array_column($loss_money_list, 'loss_totalprice'));// 报损总金额

        // 剩余可请款金额
        $compact_tmp['available_product_money'] = isset($amount_list['available_product_money']) ? $amount_list['available_product_money'] : 0;// 合同可请款商品额
        $compact_tmp['available_freight']       = isset($amount_list['available_freight']) ? $amount_list['available_freight'] : 0;// 合同可请款运费
        $compact_tmp['available_discount']      = isset($amount_list['available_discount']) ? $amount_list['available_discount'] : 0;// 合同可请款优惠额
        $compact_tmp['available_process_cost']  = isset($amount_list['available_process_cost']) ? $amount_list['available_process_cost'] : 0;// 合同可请款商加工费
        $compact_tmp['available_real_money']    = isset($amount_list['available_real_money']) ? $amount_list['available_real_money'] : 0;// 合同可请款总额

        // 抵扣金额
        $compact_tmp['ca_total_product_money'] = isset($amount_list['ca_total_product_money']) ? $amount_list['ca_total_product_money'] : 0;// 抵扣金额-商品额
        $compact_tmp['ca_total_process_cost']  = isset($amount_list['ca_total_process_cost']) ? $amount_list['ca_total_process_cost'] : 0;// 抵扣金额-加工费
        $compact_tmp['ca_total_real_money']    = $compact_tmp['ca_total_product_money'] + $compact_tmp['ca_total_process_cost'];// 抵扣金额-总额

        // 已退款金额
        $compact_tmp['real_refund_product_money'] = isset($amount_list['real_refund_product_money']) ? $amount_list['real_refund_product_money'] : 0;// 供应商总退款商品额
        $compact_tmp['real_refund_freight']       = isset($amount_list['real_refund_freight']) ? $amount_list['real_refund_freight'] : 0;// 供应商总退款运费
        $compact_tmp['real_refund_discount']      = isset($amount_list['real_refund_discount']) ? $amount_list['real_refund_discount'] : 0;// 供应商总退款优惠额
        $compact_tmp['real_refund_process_cost']  = isset($amount_list['real_refund_process_cost']) ? $amount_list['real_refund_process_cost'] : 0;// 供应商总退款加工费
        $compact_tmp['real_refund_amount']        = isset($amount_list['real_refund_amount']) ? $amount_list['real_refund_amount'] : 0;// 供应商总退款金额

        //合同扫描件URL 2019-09-17
        $order_pay = $this->purchase_db->select('id')
            ->where('pur_number', $compact_number)
            ->where('source','1') // 合同请款单
            ->order_by('id desc')
            ->get('purchase_order_pay')
            ->row_array();
        if(empty($order_pay)){// 首次请款的，即付款状态=未申请付款时，请款单中的合同扫描件=默认等于合同列表中的原始合同扫描件
            $file_list = $this->Compact_file_model->see_compact_scanning_file($compact['id']);
            $file_list = !empty($file_list)?[0 => $file_list]:[];// 转成二维数组
        }else{// 再下一次请款，则默认等于就近一次请款单号关联的合同扫描件
            $file_list = $this->Compact_file_model->see_compact_file($order_pay['id'],$compact['id']);
        }
        $compact_tmp['file_path'] = $file_list;

        // 合同 中采购单明细
        $compact_item_tmp = [];
        foreach($compact['items_list'] as $compact_item){

            $now_compact_item = $this->get_compact_pay_data_details($compact_item['purchase_number']);

            $compact_item_tmp = array_merge($compact_item_tmp,$now_compact_item);// 合并采购单明细
        }

        $compact_tmp['items_list'] = $compact_item_tmp;

        $return['code'] = true;
        $return['data'] = $compact_tmp;
        return $return;
    }

    /**
     * 获取合同里面采购单的相关数据
     * @param $purchase_number
     * @return array
     */
    public function get_compact_pay_data_details($purchase_number){
        if(!isset($this->Purchase_order_determine_model)){
            $this->load->model('purchase/Purchase_order_determine_model');
        }
        if(!isset($this->purchase_order_model)){
            $this->load->model('purchase/purchase_order_model');
        }
        if(!isset($this->Purchase_suggest_model)){
            $this->load->model('purchase_suggest/Purchase_suggest_model');
        }
        if(!isset($this->purchase_order_pay_model)){
            $this->load->model('finance/purchase_order_pay_model');
        }

        $compact_item_tmp   = [];
        $purchase_order     = $this->purchase_order_model->get_one($purchase_number);
        $sku                = array_column($purchase_order['items_list'], 'sku');

        $order_cancel_list  = $this->Purchase_order_determine_model->get_order_cancel_list($purchase_number,$sku);//获取取消数量集合
        $warehouse_list     = $this->Purchase_order_determine_model->get_warehouse_list($purchase_number,$sku);//获取入库数量集合

        foreach($purchase_order['items_list'] as $pur_item){
            $sku_qty_list = $this->purchase_order_model->calculate_sku_related_quantity($purchase_number,$pur_item['sku']);
            $demand_info = $this->Purchase_suggest_model->get_one(0,$pur_item['demand_number']);

            $item_tmp                        = [];
            $item_tmp['purchase_number']     = $purchase_number;
            $item_tmp['is_drawback']         = $purchase_order['is_drawback'];
            $item_tmp['is_drawback_cn']      = getIsDrawback($purchase_order['is_drawback']);
            $item_tmp['demand_number']       = $pur_item['demand_number'];
            $item_tmp['suggest_order_status']= $demand_info['suggest_order_status'];
            $item_tmp['sku']                 = $pur_item['sku'];
            $item_tmp['product_name']        = $pur_item['product_name'];
            $item_tmp['product_img_url']     = erp_sku_img_sku($pur_item['product_img_url']);
            $item_tmp['purchase_unit_price'] = $pur_item['purchase_unit_price'];
            $item_tmp['ca_product_money']    = $pur_item['ca_product_money'];
            $item_tmp['ca_process_cost']     = $pur_item['ca_process_cost'];
            $item_tmp['cancel_ctq']          = isset($order_cancel_list[$pur_item['purchase_number'].'-'.$pur_item['sku']])?$order_cancel_list[$pur_item['purchase_number'].'-'.$pur_item['sku']]:0; //取消数量
            $paid_info                       = $this->purchase_order_pay_model->get_pay_total_by_sku($purchase_number,$pur_item['sku']);
            $item_tmp['item_paid_total']     = isset($paid_info['pay_total'])?$paid_info['pay_total']:0;// 采购单-SKU 已请款金额
            $item_tmp['item_paid_product_money'] = isset($paid_info['product_money'])?$paid_info['product_money']:0;// 采购单-SKU 已请款商品金额

            // 数量
            $item_tmp['suggest_amount'] = isset($sku_qty_list['suggest_amount']) ? $sku_qty_list['suggest_amount'] : 0;
            $item_tmp['order_amount']   = isset($sku_qty_list['order_amount']) ? $sku_qty_list['order_amount'] : 0;
            $item_tmp['cancel_amount']  = isset($sku_qty_list['cancel_amount']) ? $sku_qty_list['cancel_amount'] : 0;
            $item_tmp['receive_amount'] = isset($sku_qty_list['receive_amount']) ? $sku_qty_list['receive_amount'] : 0;
            $item_tmp['loss_amount']    = isset($sku_qty_list['loss_amount']) ? $sku_qty_list['loss_amount'] : 0;
            $item_tmp['upselft_amount'] = isset($warehouse_list[$pur_item['purchase_number'].'-'.$pur_item['sku']]) ?$warehouse_list[$pur_item['purchase_number'].'-'.$pur_item['sku']]: 0;

            // 剩余可申请商品金额
            $item_tmp['item_available_product_money'] = format_two_point_price($item_tmp['purchase_unit_price'] * ($item_tmp['order_amount'] - $item_tmp['cancel_amount']) - $item_tmp['item_paid_product_money'] - $item_tmp['ca_product_money']);

            // 可分摊金额占比：用于请款金额的分摊
            if($item_tmp['item_available_product_money'] >= 0){
                $item_tmp['item_distribute_price'] = $item_tmp['item_available_product_money'];
            }else{
                $item_tmp['item_distribute_price'] = 0;
            }

            // 116616
            $sku_weight = 0;
            $prod_weight = $this->purchase_db->from('product')->select('product_weight')->where(['sku' => $pur_item['sku']])->get()->row_array();
            $prod_weight = isset($prod_weight['product_weight']) && count($prod_weight) > 0 ? $prod_weight['product_weight']: 0;
            if(!empty($purchase_order['account_type']) && in_array($purchase_order['account_type'], [33,10,11,12,13,14,15,16,17,18,19,21,22,23,24,25,26,27,28,29,30,31,32,39,40])){
                $sku_weight += $item_tmp['order_amount'] * $prod_weight;
            }else{
                $sku_weight += $item_tmp['upselft_amount'] * $prod_weight;
            }
            $item_tmp['weight'] = $sku_weight;

            $compact_item_tmp[] = $item_tmp;
        }

        return $compact_item_tmp;
    }


    /**
     * 更新合同单-支付状态（合同支付状态同步至采购单支付状态，两者保持一致，自动更新）
     * @author Jolon
     * @param string $compact_number  合同单号
     * @param int $new_status 目的状态
     * @param string $payer_time  付款时间
     * @return array
     */
    public function change_compact_pay_status($compact_number,$new_status,$payer_time = null){
        $return = ['code' => false, 'msg' => '', 'data' => ''];

        if(empty($new_status)){
            $return['msg'] = '合同支付状态更新-状态错误';
            return $return;
        }

        $compact = $this->get_compact_one($compact_number);
        if(empty($compact)){// 不是合同单 则取查找对账单
            $this->load->model('statement/Purchase_statement_model');
            $statement = $this->Purchase_statement_model->get_statement($compact_number);
            if(empty($statement)){
                $return['msg'] = '请款单来源主体错误';
                return $return;
            }else{
                try{
                    $this->Purchase_statement_model->change_statement_pay_status($compact_number, $new_status, $payer_time);
                    $return['code'] = true;
                    $return['msg']  = '操作成功';
                    return $return;
                }catch(Exception $exception){
                    $return['code'] = false;
                    $return['msg']  = $exception->getMessage();
                    return $return;
                }
            }
        }
        if(empty($compact) or empty($compact['items_list'])){
            $return['msg'] = '合同单或合同明细不存在';
            return $return;
        }

        $this->purchase_db->trans_strict(true);
        $this->purchase_db->trans_begin();
        try {
            $result = $this->purchase_db->where('compact_number',$compact_number)
                ->update($this->table_name,['payment_status' => $new_status]);
            if($result){
                $this->load->library('Rabbitmq');

                // 记录操作日志
                $old_status_text = getPayStatus($compact['payment_status']);
                $new_status_text = getPayStatus($new_status);
                $detail          = "修改支付状态，从[{$old_status_text}]改为[{$new_status_text}]";
                operatorLogInsert(['id' => $compact_number,'type' => $this->table_name,'content' => '合同支付状态','detail' => $detail]);

                if(in_array($new_status,[PART_PAID,PAY_PAID])) {
                    //创建消息队列对象
                    $mq = new Rabbitmq();
                    //设置参数
                    $mq->setQueueName('COMPACT_STATUS_REFRESH');//设置参数
                    $mq->setExchangeName('COMPACT_STATUS_NAME');
                    $mq->setRouteKey('COMPACT_STATUS_UPDATE_R_KEY');
                    $mq->setType(AMQP_EX_TYPE_DIRECT);
                    $mq->sendMessage(['compact_number' => $compact_number]);
                }


                $data_pay_status=['pay_status' => $new_status];

                if($payer_time and $payer_time != '0000-00-00 00:00:00'){// 付款时 同步付款时间到采购单表
                    $data_pay_status["pay_time"] = $payer_time;
                }
                // 采购单 支付状态与 合同支付状态保持一致
                $items_list = $compact['items_list'];
                foreach($items_list as $item){
                    $purchase_number = $item['purchase_number'];
                    $result1 = $this->purchase_db->where('purchase_number',$purchase_number)
                        ->update('purchase_order',$data_pay_status);
                    if(!$result1){
                        throw new Exception('采购单支付状态变更失败');
                    }else{
                        operatorLogInsert(['id' => $purchase_number,'type' => 'purchase_order','content' => '采购单支付状态','detail' => $detail]);
                    }
                }

                $mq = new Rabbitmq();//创建消息队列对象
                $mq->setQueueName('STATEMENT_CHANGE_PAY_STATUS_REFRESH');//设置参数
                $mq->setExchangeName('STATEMENT_CHANGE_PAY_STATUS');//构造存入数据
                $mq->setRouteKey('SO_REFRESH_FOR_003');
                $mq->setType(AMQP_EX_TYPE_DIRECT);
                $mq->sendMessage(['purchase_number' => array_column($items_list,'purchase_number'),'add_time' => time()]);// 保持格式一致

                if(in_array($new_status,[PART_PAID,PAY_PAID])){
                    $this->refresh_compact_data($compact_number);// 刷新合同、采购单金额明细
                }

            }else{
                throw new Exception('合同支付状态变更失败');
            }

            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('驳回采购单事务提交出错');
            } else {
                $this->purchase_db->trans_commit();
                //付款状态 推送至计划系统
                if (!empty($items_list)){
                    $po_list = array_column($items_list,'purchase_number');
                    foreach ($po_list as $purchase_number){
                        $push_pay_status_to_plan_key = 'push_pay_status_to_plan';
                        $this->rediss->set_sadd($push_pay_status_to_plan_key,$purchase_number);
                        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$push_pay_status_to_plan_key);
                    }
                }
            }
            $return['code'] = true;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * 刷新合同信息
     * @author Jolon
     * @param $compact_number
     * @param $purchase_number
     * @return array|bool
     */
    public function refresh_compact_data($compact_number = null,$purchase_number = null){
        if(empty($purchase_number) and empty($compact_number)) return false;

        if(empty($compact_number)){// 获取合同单号
            $item           = $this->compact_items_model->get_compact_by_purchase($purchase_number);
            $compact_number = isset($item['compact_number'])?$item['compact_number']:'';
        }
        if(empty($compact_number)) return false;

        $items              = $this->compact_items_model->get_compact_item($compact_number);
        $purchase_numbers   = array_column($items,'purchase_number');


        $this->load->model('finance/purchase_order_pay_model');
        $this->purchase_order_pay_model->update_order_items_paid_price_detail($purchase_numbers,'commission');// 采购单请款金额明细更细到采购单明细表上（申请中、已付款状态下）

        $result = $this->update_compact_price($compact_number,$purchase_numbers);
        return $result;
    }

    /**
     * 更新合同金额明细
     * @param      $compact_number
     * @param null $purchase_numbers
     * @return array
     */
    public function update_compact_price($compact_number,$purchase_numbers = null){
        $compact_info       = $this->get_compact_one($compact_number,false);

        if(is_null($purchase_numbers)){
            $items              = $this->compact_items_model->get_compact_item($compact_number);
            $purchase_numbers   = array_column($items,'purchase_number');
        }

        // 合同单下 所有采购单的总商品额、总运费、总优惠额、总加工费、总实际金额
        $order_total_price = $this->purchase_db->select('sum(product_money) as product_money,
                    sum(freight) as freight,
                    sum(discount) as discount,
                    sum(process_cost) as process_cost,
                    sum(commission) as commission,
                    sum(real_price) as real_price'
            )
            ->where_in('purchase_number',$purchase_numbers)
            ->get('purchase_order_pay_type')
            ->row_array();

        $compact_update_data = [
            'product_money' => $order_total_price['product_money'],
            'freight'       => $order_total_price['freight'],
            'discount'      => $order_total_price['discount'],
            'process_cost'  => $order_total_price['process_cost'],
            'real_price'    => $order_total_price['real_price'],
        ];

        // 按比例 计算合同金额
        $payment_list = compactPaymentPlan($compact_info['settlement_ratio'], $compact_update_data['product_money'], $compact_update_data['freight'] + $compact_update_data['process_cost'], $compact_update_data['discount'], $compact_info['is_drawback']);

        $mk = isset($payment_list['mk'])?$payment_list['mk']:0;
        $update_data = [
            'compact_number'        => $compact_number,
            'product_money'         => $compact_update_data['product_money'],
            'freight'               => $compact_update_data['freight'],
            'discount'              => $compact_update_data['discount'],
            'process_cost'          => $compact_update_data['process_cost'],
            'real_money'            => $payment_list['real_money'],
            'earnest_money'         => $payment_list['dj'],
            'tail_money'            => $payment_list['wk']+$mk,
            'tail_total_money'      => $payment_list['wk_t']+$mk,
        ];

        $result = $this->saveCompactData($update_data);// 更新合同金额
        return $result;
    }


    /**
     * 按维度组合生成合同的采购单数据
     * @author Jolon
     * @param $purchase_numbers
     * @return array
     */
    public function get_convert_data($purchase_numbers){
        $return = ['code' => false, 'message' => '', 'data' => '','error_list' => []];

        // 采购合同信息
        $have_purchase_compact_items = $this->purchase_db->where_in('purchase_number', $purchase_numbers)->get("purchase_compact_items")->result_array();
        $have_purchase_numbers       = array_column($have_purchase_compact_items,'purchase_number');
        foreach($have_purchase_numbers as $have_number){
            $return['error_list'][] = "$have_number 已经生成过合同";
        }
        $have_purchase_numbers = !empty($have_purchase_numbers)?$have_purchase_numbers:[PURCHASE_NUMBER_ZFSTATUS];

        // 采购单信息（排除已经生成了合同的采购单）
        $purchase_orders = $this->purchase_db
            ->select('po.purchase_number,po.source,po.supplier_code,po.purchase_type_id,po.warehouse_code,po.purchase_order_status,
                    po.is_drawback,po.purchase_name,po.pay_type,po.account_type,popt.settlement_ratio,popt.is_freight,popt.freight_formula_mode,')
            ->from('purchase_order as po')
            ->join('purchase_order_pay_type as popt','po.purchase_number=popt.purchase_number','left')
            ->where('po.is_generate=1')
            ->where_in('po.purchase_number', $purchase_numbers)
            ->where_not_in('po.purchase_number',$have_purchase_numbers)
            ->get()
            ->result_array();


        if(empty($purchase_orders)){
            $return['message'] = '没有待生成合同的采购单';
            return $return;
        }

        // 验证采购单 采购来源类型
        $source_arr = array_unique(array_column($purchase_orders, 'source'));
        if (count($source_arr) != 1 or $source_arr[0] != SOURCE_COMPACT_ORDER) {
            $return['message'] = "订单采购来源异常：请选择采购来源为【合同】的订单";
            return $return;
        }

        // 24900
        $canceled = [];
        $canceled_pur = [];
        foreach($purchase_orders as $purchase_order){
            if(isset($purchase_order['purchase_order_status']) && $purchase_order['purchase_order_status'] == PURCHASE_ORDER_STATUS_CANCELED){
                $canceled[] = $purchase_order['purchase_number'];
            }
        }
        if(count($canceled) > 0){
            $canceledData = $this->purchase_db->from('purchase_order_reportloss')
                ->select('pur_number')
                ->where_in('pur_number', $canceled)
                ->where('status !=', REPORT_LOSS_STATUS_FINANCE_PASS) // REPORT_LOSS_STATUS_FINANCE_PASS  报损已通过
                ->get()
                ->result_array();
            if($canceledData && count($canceledData) > 0)$canceled_pur = array_column($canceledData, 'pur_number');
        }

        // 有效数据
        $orders = [];
        foreach($purchase_orders as $purchase_order){
            $order_status = [
                PURCHASE_ORDER_STATUS_WAITING_QUOTE,
                PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,
                PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT,
                PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,
                PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND,
                //PURCHASE_ORDER_STATUS_CANCELED, // 订单已作废
                PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT
            ];
            if(in_array($purchase_order['purchase_order_status'], $order_status) || in_array($purchase_order['purchase_number'], $canceled_pur)){
                $return['message'] = "订单状态异常：{$purchase_order['purchase_number']} 该状态不能生成进货单";
                return $return;
            }

            // 根据 需求类型、供应商、采购仓库、是否需要中转仓、转运仓库、物流类型分组
            if (in_array($purchase_order['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])) {// 海外仓 采购需求数据合并
                $order_num = $purchase_order['purchase_type_id']
                    . '_' . $purchase_order['supplier_code']
                    . '_' . $purchase_order['is_drawback']
                    . '_' . $purchase_order['purchase_name']
                    . '_' . $purchase_order['pay_type']
                    . '_' . $purchase_order['account_type']
                    . '_' . $purchase_order['settlement_ratio']
                    . '_' . $purchase_order['is_freight'];
            } else{// 国内、FBA、平台头程、PFB，且可以合并
                $order_num = $purchase_order['supplier_code']
                    . '_' . $purchase_order['is_drawback']
                    . '_' . $purchase_order['purchase_name']
                    . '_' . $purchase_order['pay_type']
                    . '_' . $purchase_order['account_type']
                    . '_' . $purchase_order['settlement_ratio']
                    . '_' . $purchase_order['is_freight'];
            }

            $order_num = md5(strtoupper($order_num));
            $orders[$order_num][] = $purchase_order;
        }

        $return['code'] = true;
        $return['data'] = $orders;
        return $return;

    }


    /**
     * 一键生成进货单（支持勾选和查询条件）
     * @author Jolon
     * @param $purchase_numbers
     * @return mixed
     */
    public function one_key_compact_create($purchase_numbers){
        $return         = ['code' => false,'message' => '','data' => ''];
        $error_list     = [];
        $success_list   = [];

        // 获取 待刷新1688金额的 订单列表
        $this->load->model('finance/purchase_order_pay_type_model');
        $this->load->model('purchase/purchase_order_model');
        $have_pay_type = $this->purchase_db->select('id,purchase_number,pai_number,freight,discount')
            ->where_in('purchase_number',$purchase_numbers)
            ->get('purchase_order_pay_type')
            ->result_array();
        if(empty($have_pay_type) or (count($have_pay_type) != count($purchase_numbers))){
            $return['message'] = '采购单确认信息有误，请确认采购单是否提交审核';
            return $return;
        }

        $total_count = count($purchase_numbers);
        $error_count = $success_count = 0;
        try{

            // 设置锁定缓存
            foreach($purchase_numbers as $purchase_number){
                $session_key = md5('ONE_KEY_COMPACT_' . $purchase_number);
                if (!$this->rediss->getData($session_key)) {
                    $this->rediss->setData($session_key, '1', 600);//设置缓存和有效时间
                } else {
                    throw new Exception('采购单号（' . $purchase_number . ' ）已被其他进程占用');
                }
            }

            $result = $this->get_convert_data($purchase_numbers);
            if(empty($result['code'])){
                throw new Exception($result['message']);
            }
            $error_list = $result['error_list'];

            $data_list = $result['data'];
            if(empty($data_list)){
                throw new Exception('没有待生成进货单的数据');
            }

            foreach($data_list as $data_value){
                $now_purchase_numbers     = array_column($data_value,'purchase_number');
                $now_purchase_numbers_str = implode('、',$now_purchase_numbers);

                // 创建合同单
                $compact_data = $this->purchase_order_model->get_compact_confirm_template_data($now_purchase_numbers);
                // 必须有联系人和手机号码
                if(!isset($compact_data['compact_main']['b_linkman']) || empty($compact_data['compact_main']['b_linkman']) ||
                    !isset($compact_data['compact_main']['b_phone']) || empty($compact_data['compact_main']['b_phone'])){
                    $error_count += count($now_purchase_numbers);
                    $error_list[] = '失败原因：'.$now_purchase_numbers_str.' 联系人/手机号缺失。';
                    continue;
                }

                $result       = $this->create_compact($compact_data, []);

                if($result['code']){
                    foreach ($now_purchase_numbers as $purchase_number){
                        $this->purchase_order_model->purchase_track($purchase_number,PURCHASE_ORDER_STATUS_WAITING_ARRIVAL);
                    }
                    $success_list[] = '成功，合同号：'.$result['data'] .'：'.$now_purchase_numbers_str;
                    $success_count += count($now_purchase_numbers);
                    $this->supplier_joint_model->pushSmcCompactData($now_purchase_numbers);
                }else{
                    $error_count += count($now_purchase_numbers);
                    $msg = !empty($result['msg'])?$result['msg']:'生成进货单失败';

                    $error_list[] = '失败，原因：'.$now_purchase_numbers_str.' '.$msg;
                }
            }

        }catch(Exception $e){
            // 清除设置锁定缓存
            foreach($purchase_numbers as $purchase_number){
                $session_key = md5('ONE_KEY_COMPACT_' . $purchase_number);
                $this->rediss->deleteData($session_key);
            }

            $return['message'] = $e->getMessage();
            return $return;
        }

        // 清除设置锁定缓存
        foreach($purchase_numbers as $purchase_number){
            $session_key = md5('ONE_KEY_COMPACT_' . $purchase_number);
            $this->rediss->deleteData($session_key);
        }

        $data              = [
            'success_list' => $success_list,
            'error_list'   => $error_list
        ];
        $return['code']    = true;
        $return['data']    = $data;
        $return['message'] = "生成进货单操作（采购单）总数：{$total_count} ，成功个数：{$success_count}，失败个数：{$error_count}";

        return $return;

    }

    /**
     * 获取合同的 付款说明
     * @author Jaden 2019-07-05  Jolon 2020-07-30
     * @param string $account_type 结算方式
     * @param string $settlement_ratio 结算比例
     * @return array|bool
     */
    public function get_payment_settlement_requests($account_type, $settlement_ratio = null) {
        $pay_notes = [
            0 => ''// 索引数组：占用第一个元素，以便索引从 1 开始添加元素
        ];

        switch(intval($account_type)){
            case 10: // 款到发货
                $pay_notes[] = "付款方式：款到发货。";
                $pay_notes[] = "乙方生产完成后出示大货图发于甲方，甲方确认大货图无误后向乙方支付货款，乙方安排发货。";
                $pay_notes[] = "如甲方付款后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 39: // 10%订金+90%尾款款到发货
                $pay_notes[] = "付款方式：10%订金+90%尾款款到发货。";
                $pay_notes[] = "甲方向乙方预付订单10%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方需按甲方要求完成订单，乙方完成订单后出示大货图给于甲方确认，甲方确认大货图无误后向乙方支付90%尾款，乙方安排发货。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 40: // 30%订金+70%尾款款到发货
                $pay_notes[] = "付款方式：30%订金+70%尾款款到发货。";
                $pay_notes[] = "甲方向乙方预付订单30%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方需按甲方要求完成订单，乙方完成订单后出示大货图给于甲方确认，甲方确认大货图无误后向乙方支付70%尾款，乙方安排发货。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 19: // 10%订金+90%尾款货到付款
                $pay_notes[] = "付款方式：10%订金+90%尾款货到付款。";
                $pay_notes[] = "甲方向乙方预付订单10%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于3个工作日内完成对账，甲方于7个工作日内支付90%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 32: // 30%订金+70%尾款货到付款
                $pay_notes[] = "付款方式：30%订金+70%尾款货到付款。";
                $pay_notes[] = "甲方向乙方预付订单30%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于3个工作日内完成对账，甲方于7个工作日内支付70%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 17: // 10%订金+90%尾款半月结
                $pay_notes[] = "付款方式：10%订金+90%尾款月结15天。";
                $pay_notes[] = "甲方向乙方预付订单10%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方当月货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于次月1日至7日对当月入库的全部订单进行对账，对账完成后，甲方于次月15日之前支付90%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 30: // 30%订金+70%尾款半月结
                $pay_notes[] = "付款方式：30%订金+70%尾款月结15天。";
                $pay_notes[] = "甲方向乙方预付订单30%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方当月货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于次月1日至7日对当月入库的全部订单进行对账，对账完成后，甲方于次月15日之前支付70%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 18: // 10%订金+90%尾款月结
                $pay_notes[] = "付款方式：10%订金+90%尾款月结30天。";
                $pay_notes[] = "甲方向乙方预付订单10%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方当月货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于次月1日至7日对当月入库的全部订单进行对账，对账完成后，甲方于次月30日之前支付90%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 31: // 30%订金+70%尾款月结
                $pay_notes[] = "付款方式：30%订金+70%尾款月结30天。";
                $pay_notes[] = "甲方向乙方预付订单30%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方当月货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于次月1日至7日对当月入库的全部订单进行对账，对账完成后，甲方于次月30日之前支付70%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 1: // 货到付款
                $pay_notes[] = "付款方式：货到付款。";
                $pay_notes[] = "乙方货物到达甲方指定仓库并经验收合格后，甲方于7个自然日内完成付款（节假日顺延）。";

                break;
            case 7: // 周结
                $pay_notes[] = "付款方式：周结。";
                $pay_notes[] = "乙方货物到达甲方仓库经甲方全部验收合格并入库后，双方每周对上一周入库的全部订单进行对账，对账完成后，甲方于3个工作日之内支付货款。";

                break;
            case 8: // 半月结
                $pay_notes[] = "付款方式：半月结。";
                $pay_notes[] = "乙方当月到达甲方仓库且经甲方全部验收合格的货物，每半月对账,对账完成后，甲方于15个自然日内完成付款。";

                break;
            case 37: // 月结15天
                $pay_notes[] = "付款方式：月结15天。";
                $pay_notes[] = "乙方当月到达甲方仓库且经甲方全部验收合格的货物，双方于次月1日至7日对账，对账完成后，甲方于次月的15号之前完成付款。";


                break;
            case 9: // 月结30天
                $pay_notes[] = "付款方式：月结30天。";
                $pay_notes[] = "乙方当月到达甲方仓库且经甲方全部验收合格的货物，双方于次月1日至7日对账，对账完成后，甲方于次月的30号之前完成付款。";


                break;
            case 6: // 月结60天
                $pay_notes[] = "付款方式：月结60天。";
                $pay_notes[] = "乙方当月到达甲方仓库且经甲方全部验收合格入库的货物，双方于次月1日至7日对账，对账完成后，甲方于第2月底内完成对乙方的付款(例:3月1日-3月31日所有入库的产品,甲方于5月30日前完成付款)。";

                break;
            case 38: // 月结90天
                $pay_notes[] = "付款方式：月结90天。";
                $pay_notes[] = "乙方当月到达甲方仓库且经甲方全部验收合格入库的货物，双方于次月1日至7日对账，对账完成后，甲方于第3月底内完成对乙方的付款(例:3月1日-3月31日所有入库的产品,甲方于6月30日前完成付款)。";

                break;
            case 11: // 5%订金+95%尾款周结
                $pay_notes[] = "付款方式：5%订金+95%尾款周结。";
                $pay_notes[] = "甲方向乙方预付订单5%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方每周对上一周入库的全部订单进行对账，对账完成后，甲方于3个工作日之内支付95%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 16: // 10%订金+90%尾款周结
                $pay_notes[] = "付款方式：10%订金+90%尾款周结。";
                $pay_notes[] = "甲方向乙方预付订单10%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方每周对上一周入库的全部订单进行对账，对账完成后，甲方于3个工作日之内支付90%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 21: // 15%订金+85%尾款周结
                $pay_notes[] = "付款方式：15%订金+85%尾款周结。";
                $pay_notes[] = "甲方向乙方预付订单15%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方每周对上一周入库的全部订单进行对账，对账完成后，甲方于3个工作日之内支付85%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";
                break;

            case 25: // 20%订金+80%尾款周结
                $pay_notes[] = "付款方式：20%订金+80%尾款周结。";
                $pay_notes[] = "甲方向乙方预付订单20%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方每周对上一周入库的全部订单进行对账，对账完成后，甲方于3个工作日之内支付80%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 29: // 30%订金+70%尾款周结
                $pay_notes[] = "付款方式：30%订金+70%尾款周结。";
                $pay_notes[] = "甲方向乙方预付订单30%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方每周对上一周入库的全部订单进行对账，对账完成后，甲方于3个工作日之内支付70%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 12: // 5%订金+95%尾款半月结
                $pay_notes[] = "付款方式：5%订金+95%尾款半月结。";
                $pay_notes[] = "甲方向乙方预付订单5%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方当月货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于次月1日至7日对当月入库的全部订单进行对账，对账完成后，甲方于次月15日之前支付95%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 22: // 15%订金+85%尾款半月结
                $pay_notes[] = "付款方式：15%订金+85%尾款半月结。";
                $pay_notes[] = "甲方向乙方预付订单15%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方当月货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于次月1日至7日对当月入库的全部订单进行对账，对账完成后，甲方于次月15日之前支付85%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 26: // 20%订金+80%尾款半月结
                $pay_notes[] = "付款方式：20%订金+80%尾款半月结。";
                $pay_notes[] = "甲方向乙方预付订单20%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方当月货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于次月1日至7日对当月入库的全部订单进行对账，对账完成后，甲方于次月15日之前支付80%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 13: // 5%订金+95%尾款月结
                $pay_notes[] = "付款方式：5%订金+95%尾款月结。";
                $pay_notes[] = "甲方向乙方预付订单5%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方当月货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于次月1日至7日对当月入库的全部订单进行对账，对账完成后，甲方于次月30日之前支付95%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 23: // 15%订金+85%尾款月结
                $pay_notes[] = "付款方式：15%订金+85%尾款月结。";
                $pay_notes[] = "甲方向乙方预付订单15%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方当月货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于次月1日至7日对当月入库的全部订单进行对账，对账完成后，甲方于次月30日之前支付85%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 27: // 20%订金+80%尾款月结
                $pay_notes[] = "付款方式：20%订金+80%尾款月结。";
                $pay_notes[] = "甲方向乙方预付订单20%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方当月货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于次月1日至7日对当月入库的全部订单进行对账，对账完成后，甲方于次月30日之前支付80%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 24: // 15%订金+85%尾款货到付款
                $pay_notes[] = "付款方式：15%订金+85%尾款货到付款。";
                $pay_notes[] = "甲方向乙方预付订单15%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于3个工作日内完成对账，甲方于7个工作日内支付85%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 28: // 20%订金+80%尾款货到付款
                $pay_notes[] = "付款方式：20%订金+80%尾款货到付款。";
                $pay_notes[] = "甲方向乙方预付订单20%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于3个工作日内完成对账，甲方于7个工作日内支付80%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 14: // 5%订金+95%尾款货到付款
                $pay_notes[] = "付款方式：5%订金+95%尾款货到付款。";
                $pay_notes[] = "甲方向乙方预付订单5%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方货物到达甲方仓库且该批订单货物经甲方全部验收合格并入库后，双方于3个工作日内完成对账，甲方于7个工作日内支付95%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            case 15: // 10%订金+发货前30%货款+到货后60%尾款月结
                $pay_notes[] = "付款方式：10%订金+发货前30%货款+60%尾款月结。";
                $pay_notes[] = "甲方向乙方预付订单10%货款，乙方进行备料生产。";
                $pay_notes[] = "乙方需按甲方要求完成订单，乙方完成订单后出示大货图给于甲方确认，甲方确认大货图无误后向乙方支付30%货款，乙方安排发货。";
                $pay_notes[] = "乙方当月到达甲方仓库的货物且经甲方全部验收合格并入库后，双方于次月1日至7日对账，对账完成后，甲方于30个自然日内支付60%尾款。";
                $pay_notes[] = "如甲方预付订金后，乙方未按时交货的自逾期起需每日向甲方支付本订单金额的5‰作为违约滞纳金。";

                break;
            default :
                $pay_notes = [];

                break;
        }

        if($pay_notes) unset($pay_notes[0]);// 去除第一个元素

        return $pay_notes;
    }


    /**
     * 获取合同的 付款说明
     * @author Jolon
     * @param $supplier_settlement
     * @param $settlement_ratio
     * @return array
     */
    public function get_payment_explain($supplier_settlement,$settlement_ratio){
        if(is_numeric($supplier_settlement)){
            $this->load->model('supplier/Supplier_settlement_model');
            $supplier_settlement = $this->Supplier_settlement_model->get_settlement_one($supplier_settlement);
            $supplier_settlement = isset($supplier_settlement['settlement_name'])?$supplier_settlement['settlement_name']:'';
        }
        if(is_string($settlement_ratio)){
            $settlement_ratio = explode('+',$settlement_ratio);
        }

        $pay_notes = [];

        $pay_notes[1] = '付款方式：'.$supplier_settlement;
        if ($settlement_ratio[0] != "100%") {
            $pay_notes[] = '甲方向乙方预付订单<font style="color:red">'.$settlement_ratio[0].'</font>货款，进行生产。';
            $pay_percent = isset($settlement_ratio[1])?$settlement_ratio[1]:0;
            if (isset($settlement_ratio[2])) $pay_percent .= '+'.$settlement_ratio[2];
            $pay_notes[] = '大货完成后支付<font style="color:red">'.$pay_percent.'</font>的尾款，乙方出示大货图发于甲方安排发货。';
        }
        $pay_notes[] = '乙方未按时交货，自逾期起需每日向甲方支付尾款金额的<font style="color:red">5‰</font>作为违约滞纳金。';
        return $pay_notes;
    }


}