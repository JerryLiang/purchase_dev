<?php

class Purchase_order_edit_model extends Purchase_model
{
    protected $table_name = 'purchase_order';
    protected $item_table_name = 'purchase_order_items';
    protected $pay_type_table_name = 'purchase_order_pay_type';
    protected $suggest_table_name = 'purchase_suggest';
    protected $suggest_map_table_name = 'purchase_suggest_map';
    protected $logistics_info_table_name = 'purchase_logistics_info';

    /**
     * Purchase_order_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier/supplier_model'); // 供应商
        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase/purchase_order_items_model'); // 采购单子表
        $this->load->model('warehouse/warehouse_model');
        $this->load->model('product/product_model');
        $this->load->model('purchase/purchase_order_extend_model');

        $this->config->load('key_name', FALSE, TRUE);
        $this->load->helper('abnormal');
        $this->load->helper('status_order');
        $this->load->model('Message_model');
        $this->load->helper('status_1688');
        $this->load->library('rediss');
        $this->load->helper('common');
    }


    /**
     * 批量编辑采购单 - 获取数据
     * @author Jolon
     * @param $purchase_numbers
     * @return array
     */
    public function get_batch_edit_order($purchase_numbers){
        $return = ['code' => false,'message' => '','data' => ''];
        $list = $this->purchase_db
            ->select('B.es_shipment_time,A.purchase_type_id,B.coupon_rate,A.purchase_number,A.purchase_order_status,
            A.is_drawback,A.pay_type,A.shipping_method_id,D.warehouse_code,A.is_ali_order,A.supplier_code,A.source,
            E.settlement_ratio,E.is_freight,E.freight,E.discount,E.process_cost,E.freight_formula_mode,E.pai_number,E.purchase_acccount,A.account_type,A.shipment_type,
            B.sku,B.product_name,B.purchase_amount,B.confirm_amount,B.tax_rate,D.demand_number,B.purchase_unit_price,B.product_base_price,E.freight_note,
            B.freight as item_freight,B.pur_ticketed_point,B.discount as item_discount,B.process_cost as item_process_cost,B.modify_remark,A.account_type,
            B.first_plan_arrive_time,B.plan_arrive_time AS plan_product_arrive_time,D.purchase_type_id AS sg_purchase_type_id')
            ->from($this->table_name.' AS A')
            ->join($this->item_table_name. ' AS B','A.purchase_number=B.purchase_number','LEFT')
            ->join($this->suggest_map_table_name. ' AS C','C.purchase_number=B.purchase_number AND C.sku=B.sku','LEFT')
            ->join($this->suggest_table_name. ' AS D','D.demand_number=C.demand_number','LEFT')
            ->join($this->pay_type_table_name. ' AS E','E.purchase_number=A.purchase_number','LEFT')
            ->where_in('A.purchase_number',$purchase_numbers)
            ->get()
            ->result_array();
        //统一查询需要转换的字段，避免循环嵌套查询
        if($list && !empty($list)) {
            $this->load->model('purchase/Purchase_order_transport_model');
            $list_tmp = [];

            $skusDatas = array_column($list, "sku");
            $sku_field = 'supplier_code,original_devliy,devliy,long_delivery,is_drawback,purchase_price,ticketed_point,product_weight,sku,starting_qty,starting_qty_unit,tax_rate';
            $productDatas = $this->purchase_db->from("product")->select($sku_field)->where_in("sku", $skusDatas)->get()->result_array();
            $product_list = [];
            if($productDatas && count($productDatas) > 0){
                foreach ($productDatas as $pv){
                    $product_list[$pv['sku']] = $pv;
                }
            }

            // 20201010 yefanli 获取所有的结算方式
            $settlement_data = [];
            $settlement_db = $this->purchase_db->from('supplier_settlement')->select('settlement_code,settlement_name,settlement_percent')->get()->result_array();
            if($settlement_db && count($settlement_db) > 0){
                foreach ($settlement_db as $val){
                    $settlement_data[$val['settlement_code']] = $val;
                }
            }

            $weight = 0;
            // 计算采购单对应的sku重量
            $calculation_weight = $this->Purchase_order_transport_model->calculation_purchase_number_weight($list, $productDatas);

            // 获取结算方式
            $supplier_payment =  $this->supplier_model->get_suplier_payment_all(array_column($list, 'supplier_code'));

            // 批量获取上次采购单价
            $lastPrice = $this->purchase_order_items_model->getSkuLastPurchasePrice($skusDatas);

            // 批量验证同一采购单供应商、是否退税是否一致
            $check_info_new = $this->purchase_order_extend_model->verify_sku_supplier(array_column($list, 'purchase_number'),'array');

            // 批量验证同一采购单供应商、是否退税是否一致
            $check_info = $this->purchase_order_items_model->new_check_purchase_disabled(array_column($list, 'purchase_number'));

            foreach ($list as $value) {
                if(empty($value['sku']))continue;
                if(!in_array($value['sku'], array_keys($product_list))){
                    $return['message'] = '未查询到sku:'.$value['sku']." 信息。";
                    return $return;
                }
                $sku_info = $product_list[$value['sku']];
                $purchase_number = $value['purchase_number'];
                if ($value['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_QUOTE) {
                    $return['message'] = "采购单号[$purchase_number]非等待采购询价状态";
                    return $return;
                }
                if (!isset($list_tmp[$purchase_number])) {
                    if(isset($check_info_new[$purchase_number]) && $check_info_new[$purchase_number]){
                        $return['message'] = $check_info_new[$purchase_number];
                        return $return;
                    }

                    if(isset($check_info[$purchase_number]) && $check_info[$purchase_number]){
                        $return['message'] = $check_info[$purchase_number];
                        return $return;
                    }

                    //根据供应商维护支付方式提取数据支付方式
                    $pay_type = $this->supplier_model->get_suplier_payment_method_one($supplier_payment, $value['supplier_code'], $value['is_drawback'], $value['purchase_type_id']);
                    $pay_type_box = [];
                    if (!empty($pay_type)) $pay_type_box[$pay_type] = getPayType($pay_type);

                    // 业务线为海外仓时
                    $overseaPayData = $oversettlementMess= [];

                    $supplier_info = $this->supplier_model->get_supplier_info($sku_info['supplier_code'], false);
                    //sku默认供应商 是否包邮 = 是  则运费默认=乙方 是否包邮 = 否 运维支付默认=甲方
                    $is_postage = (isset($supplier_info['is_postage']) && $supplier_info['is_postage'] == 1) ? '2' : ((isset($supplier_info['is_postage']) && $supplier_info['is_postage'] == 2) ? '1' : '');
                    $list_tmp[$purchase_number]['purchase_number'] = $value['purchase_number'];
                    $list_tmp[$purchase_number]['supplier_code'] = $supplier_info['supplier_code'];
                    $list_tmp[$purchase_number]['supplier_name'] = $supplier_info['supplier_name'];
                    $list_tmp[$purchase_number]['warehouse_code'] = $value['warehouse_code'];
                    $list_tmp[$purchase_number]['warehouse_code_edit'] = $value['purchase_type_id'] == 2 ? true : false;
                    $list_tmp[$purchase_number]['is_drawback'] = getIsDrawback(intval($value['is_drawback']));
                    if ($value['is_drawback'] == 0) {
                        $list_tmp[$purchase_number]['is_drawback'] = 2;
                    } else if ($value['is_drawback'] == 1) {
                        $list_tmp[$purchase_number]['is_drawback'] = 1;
                    }


                    $list_tmp[$purchase_number]['purchase_type_id'] = $value['purchase_type_id'];
                    $list_type[$purchase_number]['is_drawback_numbers'] = $value['is_drawback'];
                    $list_tmp[$purchase_number]['oversea_pay']=   $overseaPayData;

                    // 34415
                    $list_tmp[$purchase_number]['is_postage'] = $supplier_info['is_postage'] == 1? true:false;
                    $list_tmp[$purchase_number]['pay_type_list'] = $pay_type_box;
                    $list_tmp[$purchase_number]['shipping_method_id'] = $value['shipping_method_id'];
                    $list_tmp[$purchase_number]['plan_product_arrive_time'] = $value['plan_product_arrive_time'];
                    $list_tmp[$purchase_number]['is_freight'] = $value['is_freight'] ? $value['is_freight'] : $is_postage;
                    $list_tmp[$purchase_number]['freight'] = !empty($value['freight']) ? $value['freight'] : '';
                    $list_tmp[$purchase_number]['discount'] = !empty($value['discount']) ? $value['discount'] : '';
                    $list_tmp[$purchase_number]['process_cost'] = !empty($value['process_cost']) ? $value['process_cost'] : '';
                    $list_tmp[$purchase_number]['freight_formula_mode'] = $value['freight_formula_mode'];
                    $list_tmp[$purchase_number]['pai_number'] = $value['pai_number'];
                    $list_tmp[$purchase_number]['purchase_acccount'] = $value['purchase_acccount'];
                    $list_tmp[$purchase_number]['is_ali_order'] = $value['is_ali_order'];
                    // $list_tmp[$purchase_number]['settlement_ratio'] = !empty($value['settlement_ratio']) ? $value['settlement_ratio'] : $this->getSettlementratiodefault($value['account_type']);
                    $list_tmp[$purchase_number]['freight_note'] = $value['freight_note'];
                    $list_tmp[$purchase_number]['shipment_type'] = $value['shipment_type'];
                    $list_tmp[$purchase_number]['selected_account_type'] = '';
                    $list_tmp[$purchase_number]['pur_ticketed_point'] = '';
                    $list_tmp[$purchase_number]['oversettlementData'] = $oversettlementMess;
                    $list_tmp[$purchase_number]['purchase_name_data'] = [
                        1=>'深圳市易佰网络科技有限公司',
                        2=>'YIBAI TECHNOLOGY LTD'
                    ];
                    $list_tmp[$purchase_number]['sg_purchase_type_id'] = $value['sg_purchase_type_id'];
                    $list_tmp[$purchase_number]['original_devliy'] = $sku_info['original_devliy'];
                    $list_tmp[$purchase_number]['devliy'] = $sku_info['devliy'];
                    $list_tmp[$purchase_number]['long_delivery'] = $sku_info['long_delivery'];

                    // 25421
                    $settlement_null = ["settlement_code"=>$value['account_type'],"settlement_name"=>"","settlement_percent"=>""];
                    $list_tmp[$purchase_number]['account_type'] = isset($settlement_data[$value['account_type']])?$settlement_data[$value['account_type']]:$settlement_null;  // account_type  结算方式
                    $list_tmp[$purchase_number]['pay_type'] = [$value['pay_type'] => getPayType($value['pay_type'])];   // pay_type  支付方式
                    $list_tmp[$purchase_number]['settlement_ratio'] = isset($settlement_data[$value['account_type']])?$settlement_data[$value['account_type']]['settlement_percent']:''; // settlement_ratio 结算比例

                    //运费说明只显示内容
                    $freight_note = explode('_', $value['freight_note']);
                    if (!empty($freight_note)) {
                        $list_tmp[$purchase_number]['freight_note'] = $freight_note[0];
                    }
                    // 参考运费
                    $one_weight = isset($calculation_weight[$purchase_number])?$calculation_weight[$purchase_number]: 0;
                    $reference_freight = $this->Purchase_order_transport_model->get_reference_freight($value['warehouse_code'], $supplier_info['ship_province'], $one_weight);
                    if ($reference_freight['code'] === false) {
                        $list_tmp[$purchase_number]['reference_freight'] = null;
                        $list_tmp[$purchase_number]['reference_freight_msg'] = $reference_freight['message'];
                    } else {
                        $list_tmp[$purchase_number]['reference_freight'] = format_two_point_price($reference_freight['data']);
                        $list_tmp[$purchase_number]['reference_freight_msg'] = $reference_freight['message'];
                    }
                    $list_tmp[$purchase_number]['product_weight'] = $one_weight;

                    //如果是临时供应商显示该供应商的下单次数,并发送到页面显示
                    if ($supplier_info['supplier_source'] == 3) {//临时供应商且下单量>0
                        $order_num = $this->purchase_order_model->set_temporary_supplier_order_number($value['supplier_code']);
                        if ($order_num > 0) $list_tmp[$purchase_number]['order_num'] = $supplier_info['order_num'];
                    }//记录信息
                }

                $prod_drawback = $this->product_model->getProductIsBackTaxNew($sku_info['supplier_code'], $sku_info['tax_rate'],$sku_info['ticketed_point']);
                $sku_drawback_ch = getIsDrawback($prod_drawback);

                // 含税单价计算 采购类型,is_drawback 等于1 表示退税
                $change_price_de = [];
                if( in_array($value['purchase_type_id'],[2,3])) {
                    $change_price_de[1] = format_two_point_price($sku_info['purchase_price'] * (1 + ($sku_info['ticketed_point'] / 100)));
                    $change_price_de[2] = format_two_point_price($sku_info['purchase_price']);
                }else{
                    $change_price_de[1] = format_two_point_price($sku_info['purchase_price']);
                    $change_price_de[2] = format_two_point_price($sku_info['purchase_price']);
                }


                $product_weight = $sku_info['product_weight'] * $value['confirm_amount'];
                $weight += $product_weight;
                $skuLastPrice = isset($lastPrice[$value['sku']])?$lastPrice[$value['sku']]:0;
                $list_tmp[$purchase_number]['items_list'][] = [
                    'sku' => $value['sku'],
                    'product_name' => $value['product_name'],
                    'confirm_amount' => empty($value['confirm_amount']) ? $value['purchase_amount'] : $value['confirm_amount'],// 没有确认数量则取采购数量
                    'product_weight' => $product_weight,
                    'demand_number' => $value['demand_number'],
                    'tax_rate' => $value['tax_rate'],
//                    'purchase_order_status' => getPurchaseStatus($value['purchase_order_status']),
                    'purchase_unit_price' => $value['purchase_unit_price']??0,//含税单价
                    'product_base_price' => $value['product_base_price'],//未税单价
                    'last_purchase_price' => $skuLastPrice,//$this->getSkuLastPurchasePrice($value['sku']),//上一次采购单价
                    'starting_qty' => $sku_info['starting_qty'],
                    'starting_qty_unit' => $sku_info['starting_qty_unit'],
                    'item_freight' => $value['item_freight']??0,
                    'item_discount' => $value['item_discount']??0,
                    'item_process_cost' => $value['item_process_cost']??0,
                    'modify_remark' => $value['modify_remark'],
                    'coupon_rate' => $value['coupon_rate'],
                    'plan_arrive_time' =>($value['plan_product_arrive_time'] == "0000-00-00 00:00:00")?"":$value['plan_product_arrive_time'],
                    'warehouse_code' => $value['warehouse_code'],
                    'sku_drackback_ch' => $sku_drawback_ch,
                    'select_purchase_unit_price' => $change_price_de,
                    'es_shipment_time' => $value['es_shipment_time']
                ];
            }

            // 预计到货时间=批量编辑采购单的时间+sku权均交期。一个po多个预计到货时间的，取最晚的那个时间
            foreach($list_tmp as &$value_tmp){
                if($value_tmp['sg_purchase_type_id'] == 2){
                    if(!empty($value_tmp['original_devliy']) || !empty($value_tmp['devliy'])){
                        $value_tmp['devliy'] = !empty($value_tmp['devliy']) ? $value_tmp['devliy']: 0;
                        $str_devliy = !empty($value_tmp['original_devliy'])? $value_tmp['original_devliy']: $value_tmp['devliy'];
                        $value_tmp['plan_product_arrive_time']= date("Y-m-d H:i:s", strtotime("+{$str_devliy} day"));
                    }else{
                        $value_tmp['plan_product_arrive_time']= '';
                    }
                }else{
                    if(!empty($value_tmp['original_devliy']) && $value_tmp['original_devliy'] == 2 && (!empty($value_tmp['original_devliy']) || !empty($value_tmp['devliy']))){
                        $value_tmp['devliy'] = !empty($value_tmp['devliy']) ? $value_tmp['devliy']: 0;
                        $str_devliy = !empty($value_tmp['original_devliy'])? $value_tmp['original_devliy']: $value_tmp['devliy'];
                        $value_tmp['plan_product_arrive_time']= date("Y-m-d H:i:s", strtotime("+{$str_devliy} day"));
                    }else{
                        $value_tmp['plan_product_arrive_time']= date("Y-m-d H:i:s", strtotime("+10 day"));;
                    }
                }
            }

            $list = array_values($list_tmp);
        }
        $return['code'] = true;
        $return['data'] = $list;
        return $return;
    }


    /**
     * 批量编辑采购单 - 保存或确认提交
     * @author Jolon
     * @param array $data_tmp
     * @param string $is_submit  是否确认提交信息
     * @return array
     */
    public function save_batch_edit_order($data_tmp,$is_submit)
    {
        $return = ['code' => false, 'message' => '', 'data' => ''];

        $this->load->model('finance/purchase_order_pay_type_model');
        $this->load->model('purchase_suggest_model');
        $this->load->model('purchase_suggest_map_model');
        $this->load->model('purchase/purchase_auto_audit_model');
        $this->load->model('purchase_suggest/purchase_demand_model');


        $warehouse_code_list = $this->Warehouse_model->warehouse_code_to_name();
        $pertain_wms_list = $this->Warehouse_model->get_pertain_wms_list();
        $pertain_wms_list = array_column($pertain_wms_list, 'pertain_wms_code', 'warehouse_code');
        $warehouse_list = $this->Warehouse_model->get_warehouse_map();
        $warehouse_map = [];
        foreach ($warehouse_list as $key => $item) {
            $item['purchase_type_id'] = explode(',', $item['purchase_type_id']);
            $warehouse_map[$item['warehouse_code']] = [
                'warehouse_type' => $item['warehouse_type'],
                'purchase_type_id' => $item['purchase_type_id'],
            ];
        }
        unset($warehouse_list);
        $vpl = [];
        try {
            $this->purchase_db->trans_begin();
            $vpl_arr = [];
            $vpl = array_column($data_tmp, "purchase_number");

            // 去除系统自动操作部分
            $verify_task = $this->purchase_order_extend_model->verify_task_handle($vpl, 1, 1);
            if(!empty($verify_task) && is_array($verify_task)){
                $verify_task_tmp = [];
                foreach ($data_tmp as $value){
                    if(in_array($value['purchase_number'], $verify_task))$verify_task_tmp[] = $value;
                }
                if(empty($verify_task_tmp))throw new Exception('当前数据系统已自动提交审核，无需人工再次提交。');
                $vpl = $verify_task;
                $data_tmp = $verify_task_tmp;
            }

            $vpl_res = $this->purchase_order_extend_model->verify_overseas_warehouse($vpl, false, true);
            if($vpl_res['code'] == 1){
                $vpl_arr = $vpl_res['msg'];
            }

            // 32102 验证供应商是否一致
            $verify_supplier = $this->purchase_order_extend_model->verify_sku_supplier($vpl);
            if($verify_supplier !== true){
                throw new Exception($verify_supplier);
            }

            $confirm_amount = [];
            foreach ($data_tmp as $value) {
                $purchasing_order_audit = $value['purchasing_order_audit'];// 是否推送蓝凌标记
                $purchase_number = $value['purchase_number'];
                $orderInfo = $this->purchase_order_model->get_one($purchase_number);
                $value['is_drawback'] = ($value['is_drawback'] == 2) ? 0 : $value['is_drawback'];

                if($value['warehouse_code'] == 'CG-YPC' && $value['supplier_code']){
                    $supplier_name = $this->purchase_db->from("supplier")
                        ->where("supplier_code", $value['supplier_code'])
                        ->select("supplier_name,supplier_code")->get()->row_array();
                    $value['supplier_code'] = SetAndNotEmpty($supplier_name, 'supplier_code') ? $supplier_name['supplier_code'] : '';
                    $value['supplier_name'] = SetAndNotEmpty($supplier_name, 'supplier_name') ? $supplier_name['supplier_name'] : '';
                }

                if ($is_submit == 1) {
                    if ($orderInfo['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_QUOTE) {
                        throw new Exception("采购单号[$purchase_number]非等待采购询价状态");
                    }
                    $result = $this->supplier_model->is_not_completed($orderInfo['supplier_code']);
                    if ($result['code'] == false) {
                        $data = $result['data'];
                        $message = implode("<br/>", $data);
                        throw new Exception("采购单号[$purchase_number]供应商[" . $orderInfo['supplier_name'] . "]信息不全，请前往供应商页面进行完善，如下信息：<br>" . $message);
                    }

                    $order_number = $this->purchase_order_model->temporary_supplier_order_number($orderInfo['supplier_code'], $orderInfo['source']);
                    if ($order_number) {
                        throw new Exception('供应商['.$orderInfo['supplier_name'].']:'.implode(';',$order_number));

                    }
                    $warehouseCodesData = $value['warehouse_code'];

                }
                $is_ali_order = $orderInfo['is_ali_order'];// 判断是否是 1688下单
                $items_list = $value['items_list'];
                $order_items_list = $orderInfo['items_list'];
                if (count($items_list) != count($order_items_list)) {
                    throw new Exception("采购单[$purchase_number]提交的SKU个数与采购单实际SKU个数不符");
                }

                $pertain_wms_code = $this->Warehouse_model->get_warehouse_one($value['warehouse_code'], 'pertain_wms');// 获取公共仓
                // 28329 海外仓订单限制修改仓库
                if($orderInfo['purchase_type_id'] == PURCHASE_TYPE_OVERSEA && !empty($vpl_arr) && isset($vpl_arr[$purchase_number]) && !empty($vpl_arr[$purchase_number])){
//                        $value['warehouse_code'] = $vpl_arr[$purchase_number];
                    if($pertain_wms_code != $vpl_arr[$purchase_number])throw new Exception("采购单[$purchase_number]采购仓库错误,请核实后重新提交");
                    $pertain_wms_code = $vpl_arr[$purchase_number];
                }

                $source = in_array($value['pay_type'], [PURCHASE_PAY_TYPE_PUBLIC, PURCHASE_PAY_TYPE_PRIVATE]) ? SOURCE_COMPACT_ORDER : SOURCE_NETWORK_ORDER;
                // 更新采购单主表信息
                if (!$is_ali_order) {  // 非1688下单订单
                    $save_order_info = [
                        'pay_type' => $value['pay_type'],
                        'source' => $source,
                        'shipping_method_id' => $value['shipping_method_id'],
                        'plan_product_arrive_time' => $value['plan_product_arrive_time'],
                        'shipment_type' => $value['shipment_type'],
                        'account_type' => $value['account_type'],
                        'is_drawback' => $value['is_drawback']
                    ];

                    if ($source == SOURCE_COMPACT_ORDER) {
                        // 如果采购来源 为合同，获取下供应商是否开启门户
                        $isGateWays = $this->purchase_db->from("supplier")->where("supplier_code", $orderInfo['supplier_code'])->select("id,is_gateway,is_push_purchase")->get()->row_array();
                        if (!empty($isGateWays) && $isGateWays['is_gateway'] == SUGGEST_IS_GATEWAY_YES && $isGateWays['is_push_purchase'] == 1) {
                            $save_order_info['is_gateway'] = SUGGEST_IS_GATEWAY_YES;
                        }
                    }

                } else {   // 1688下单：限定修改字段
                    $save_order_info = [
                        'plan_product_arrive_time' => $value['plan_product_arrive_time'],
                        'shipment_type' => $value['shipment_type'],
                        'account_type' => $value['account_type'],
                        'source' => SOURCE_NETWORK_ORDER,
                        'is_drawback' => $value['is_drawback']
                    ];
                    // 1688下单且结算方式为账期的不能修改结算方式
                    if ($orderInfo['account_type'] == SUPPLIER_SETTLEMENT_CODE_TAP_DATE) unset($save_order_info['account_type']);
                }
                if (empty($orderInfo['first_plan_product_arrive_time'])) {
                    $save_order_info['first_plan_product_arrive_time'] = $value['plan_product_arrive_time'];
                }
//                if ($is_submit == 1) {
                if (in_array($orderInfo['purchase_type_id'], [2, 3])) {
                    if ($value['is_drawback']) {
                        $save_order_info['purchase_name'] = 'SZYB';
                    } else {
                        $save_order_info['purchase_name'] = 'HKYB';
                    }
                }
//                }

                $YP_account_type = false;
                $YP_pay_type = false;
                if($value['warehouse_code'] == 'CG-YPC'){
                    $save_order_info['supplier_code'] = $value['supplier_code'];
                    $save_order_info['supplier_name'] = $value['supplier_name'];
                    $save_order_info['account_type']    = $value['account_type']; // 供应商结算方式
                    $save_order_info['pay_type']        = $value['pay_type']; // 供应商支付方式
                    $YP_account_type = $value['account_type']; // 供应商结算方式
                    $YP_pay_type = $value['pay_type']; // 供应商支付方式
                }
                $result1 = $this->purchase_db->update($this->table_name, $save_order_info, ['purchase_number' => $purchase_number]);
                if (empty($result1)) {
                    throw new Exception("采购单[$purchase_number]更新失败");
                }
                $user_name = getActiveUserName();
                $user_name = !empty($user_name) ? $user_name : ' ';

                $real_price = $total_product_money = 0;//计算该订单总价
                if (!empty($items_list)) {
                    foreach ($items_list as $items_key => $items_value) {
                        $real_price += $items_value['purchase_unit_price'] * $items_value['confirm_amount'] + $items_value['freight'] - $items_value['discount'] + $items_value['process_cost'];
                        $total_product_money += $items_value['purchase_unit_price'] * $items_value['confirm_amount'];

                        // 31190 20210304
                        foreach ($order_items_list as $oil){
                            if(
                                $oil['id'] == $items_value['id'] &&
                                $orderInfo['purchase_order_status'] == PURCHASE_ORDER_STATUS_WAITING_QUOTE &&
                                $orderInfo['shipment_type'] == 1 &&
                                $items_value['confirm_amount'] != $oil["confirm_amount"]
                            ){
                                $confirm_amount[] = [
                                    "itemsId"           => $items_value["id"],
                                    "purchaseNumber"    => $purchase_number,
                                    "sku"               => $items_value["sku"],
                                    "cancelCtq"         => $oil["purchase_amount"] - $items_value['confirm_amount'],
                                    "demandNumber"      => $items_value["demand_number"],
                                ];
                            }
                        }
                    }
                }


                // 更新采购单确认信息
                $save_pay_type_info = [
                    'settlement_ratio' => $value['settlement_ratio'],
                    'is_freight' => $value['is_freight'],
                    'product_money' => $total_product_money,
                    'freight' => $value['freight'],
                    'discount' => $value['discount'],
                    'process_cost' => $value['process_cost'],
                    'freight_formula_mode' => $value['freight_formula_mode'],
                    'purchase_acccount' => $value['purchase_acccount'],
                    'pai_number' => trim($value['pai_number']),
                    'real_price' => format_two_point_price($real_price),
                    'confirm_type' => '2',// 确认提交类型（1.采购单列表提交，2.批量编辑采购单提交）
                    'freight_note' => !empty($value['freight_note']) ? $value['freight_note'] . '_' . $user_name . '_' . date("Y-m-d H:i:s", time()) : '',//运费说明
                ];
                if ($is_ali_order) {// 1688下单：限定修改字段
                    unset($save_pay_type_info['purchase_acccount'], $save_pay_type_info['pai_number']);
                }
                $have_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);
                if ($have_pay_type) {
                    $result2 = $this->purchase_order_pay_type_model->update_one($have_pay_type['id'], $save_pay_type_info);
                } else {
                    $save_pay_type_info['purchase_number'] = $purchase_number;
                    $result2 = $this->purchase_order_pay_type_model->insert_one($save_pay_type_info);
                }
                if (empty($result2)) {
                    throw new Exception("采购单[$purchase_number]确认信息更新失败");
                }

                $order_items_list = arrayKeyToColumn($order_items_list, 'sku');

                // 计算分摊的运费、优惠额
                $purchase_sku_list = array_column($items_list, 'confirm_amount', 'sku');


                //更新采购仓库  提交还是保存 都要记录
                if (isset($value['warehouse_code']) && $value['warehouse_code'] && !preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $value['warehouse_code']) && !$is_ali_order) {

                    if (!isset($warehouse_map[$value['warehouse_code']])) {
                        throw new Exception("采购单[$purchase_number]仓库错误");
                    }
                    if ($warehouse_map[$value['warehouse_code']]['warehouse_type'] != 1) {
                        throw new Exception("采购单[$purchase_number]该仓库不属于本地仓库");
                    }
                    if(in_array($orderInfo['purchase_type_id'],[PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH])){
                        if (empty(array_intersect([PURCHASE_TYPE_INLAND, PURCHASE_TYPE_FBA], $warehouse_map[$value['warehouse_code']]['purchase_type_id']))) {
                            throw new Exception("采购单[$purchase_number]仓库与业务线对应关系出错");
                        }
                    } else if (!in_array($orderInfo['purchase_type_id'], $warehouse_map[$value['warehouse_code']]['purchase_type_id'])) {
                        throw new Exception("采购单[$purchase_number]仓库与业务线对应关系出错");
                    }
                    $this->purchase_db->where('purchase_number', $purchase_number)->update('purchase_order', ['warehouse_code' => $value['warehouse_code'], 'pertain_wms' => $pertain_wms_code]);
                    /*
                    //变更需求单仓库
                    $demand_number_list= $this->purchase_suggest_map_model->get_demand_number_list($purchase_number);//查询备货单号
                    if(!empty($demand_number_list)){
                       $results= $this->purchase_suggest_model->update_suggest_warecahouse($demand_number_list,$value['warehouse_code']);
                       if(!$results['code']){
                           throw new Exception($results['message']);
                       }
                    }
                    unset($demand_number_list);
                    */
                    if ($orderInfo['warehouse_code'] != $value['warehouse_code']) {// 变更了仓库才需要日志
                        operatorLogInsert(
                            [
                                'id' => $purchase_number,
                                'type' => $this->table_name,
                                'content' => '批量编辑采购单-采购单确认提交信息',
                                'detail' => '采购单号' . $purchase_number . '变更采购仓库'
                            ]);
                    }
                }

                if (!$is_ali_order or ($orderInfo['account_type'] != SUPPLIER_SETTLEMENT_CODE_TAP_DATE)) {// 1688下单：限定修改字段（1688下单且结算方式为账期的不能修改结算方式）
                    //更新需求单结算方式
                    if (isset($value['account_type']) && $value['account_type'] && is_numeric($value['account_type'])) {
                        $demand_number_arr = $this->purchase_suggest_map_model->get_demand_number_list($purchase_number);//查询备货单号
                        $res = $this->purchase_suggest_model->update_suggest_account_type($demand_number_arr, $value['account_type']);
                        if (!$res['code']) throw new Exception($res['msg']);
                    }
                }


                // 确认提交信息--待采购经理审核
                if ($is_submit == 1) {
                    // 自动审核采购单
                    $orderInfoNew = $this->purchase_order_model->get_one($purchase_number);
                    $automaticResult = $this->purchase_auto_audit_model->checkPurchaseOrderAutomaticAudit($orderInfoNew);
                    if ($automaticResult['code']) {
                        $this->purchase_order_model->change_status($purchase_number, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL);
                    } else {
                        $this->purchase_order_model->change_status($purchase_number, PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT);
                    }

                    if ($purchasing_order_audit == PUSHING_BLUE_LING) {
                        //推送蓝凌系统
                        $this->purchase_order_model->pushing_blue_ling($purchase_number);
                    }
                    operatorLogInsert(
                        [
                            'id' => $purchase_number,
                            'type' => $this->table_name,
                            'content' => '批量编辑采购单-采购单确认提交信息',
                            'detail' => '批量编辑采购单-添加提交等信息'
                        ]);
                }

                // 验证 采购单所有的备货单的公共仓是否相同
                $warehouse_change_list = array_unique(array_column($items_list, 'warehouse_code', 'warehouse_code'));// 提交的仓库code => 仓库name
                $warehouse_change_list = array_intersect($warehouse_change_list, array_merge($warehouse_change_list, $pertain_wms_list));// 提交的仓库code => 公共仓code
                if (count(array_unique(array_values($warehouse_change_list))) > 1) {
                    throw new Exception("PO $purchase_number 的公共仓不一致，请重新提交");
                }

                // 更新采购单SKU明细
                $this_time = date("Y-m-d H:i:s");
                foreach ($items_list as $sku_value) {
                    $oldplantime = $this->purchase_db->from("purchase_order_items")->where("purchase_number",$purchase_number)
                        ->where("sku",$sku_value['sku'])->select("plan_arrive_time")->get()->row_array();
                    if ($is_ali_order) { // 1688下单：限定修改字段（不允许修改采购数量、仓库）
                        $sku = $sku_value['sku'];
                        $had_purchase_amount = $order_items_list[$sku]['purchase_amount'];

                        if ($sku_value['confirm_amount'] > $order_items_list[$sku]['purchase_amount']) {
                            throw new Exception("采购单[$purchase_number]SKU[$sku]修改后的数量必须<=备货单中的备货数量");
                        }
                        $save_order_item = [
                            'freight' => $sku_value['freight'],
                            'discount' => $sku_value['discount'],
                            'process_cost' => $sku_value['process_cost'],
                            'modify_remark' => $sku_value['modify_remark'],
                            'modify_time' => $this_time,
                            'modify_user_name' => getActiveUserName(),
                            'coupon_rate' => $sku_value['coupon_rate'],
                            'plan_arrive_time' => $sku_value['plan_arrive_time'],
                            'es_shipment_time' => $sku_value['es_shipment_time'],
                        ];


                        $this->purchase_order_model->insertAuditEstimeData($purchase_number,$sku,$oldplantime['plan_arrive_time'],$sku_value['plan_arrive_time'],'编辑采购单');

                        $result3 = $this->purchase_db->update($this->item_table_name, $save_order_item, ['purchase_number' => $purchase_number, 'sku' => $sku]);
                        if (empty($result3)) {
                            throw new Exception("采购单明细[$purchase_number - $sku]更新失败");
                        }

                        $suggest_map = $this->purchase_suggest_map_model->get_one_by_sku($purchase_number, $sku, '', true);
                        $suggest_time = [
                            'es_shipment_time' => $sku_value['es_shipment_time'],
                            'plan_product_arrive_time' => $sku_value['plan_arrive_time']
                        ];
                        if($value['warehouse_code'] == 'CG-YPC'){
                            $suggest_time['supplier_code'] = $value['supplier_code'];
                            $suggest_time['supplier_name'] = $value['supplier_name'];
                        }
                        $this->purchase_db->where("demand_number",$suggest_map['demand_number'])
                            ->update('purchase_suggest',$suggest_time);

                        // 分摊下单数量
                        $this->purchase_demand_model->apportionPurchaseAmount($suggest_map['demand_number'],$had_purchase_amount);

                    } else {
                        $sku = $sku_value['sku'];
                        $had_purchase_amount = $sku_value['confirm_amount'];

                        $suggest_map = $this->purchase_suggest_map_model->get_one_by_sku($purchase_number, $sku, '', true);

                        if (!empty($suggest_map)) {
                            $suggest_info = $this->purchase_suggest_model->get_one(0, $suggest_map['demand_number']);

                            if ($sku_value['confirm_amount'] > $order_items_list[$sku]['purchase_amount']) {
                                throw new Exception("采购单[$purchase_number]SKU[$sku]修改后的数量必须<=备货单中的备货数量");
                            }
                            $save_order_item = [
                                'confirm_amount' => $sku_value['confirm_amount'],
                                'tax_rate' => $sku_value['tax_rate'],
                                'freight' => $sku_value['freight'],
                                'discount' => $sku_value['discount'],
                                'process_cost' => $sku_value['process_cost'],
                                'modify_remark' => $sku_value['modify_remark'],
                                'modify_time' => $this_time,
                                'modify_user_name' => getActiveUserName(),
                                'coupon_rate' => $sku_value['coupon_rate'],
                                'plan_arrive_time' => $sku_value['plan_arrive_time'],
                                'es_shipment_time' => $sku_value['es_shipment_time'],

                            ];
                            $this->purchase_order_model->insertAuditEstimeData($purchase_number,$sku,$oldplantime['plan_arrive_time'],$sku_value['plan_arrive_time'],'编辑采购单');

                            //当采购数量、采购仓库变更时，备注信息必填
                            if (($sku_value['confirm_amount'] != $order_items_list[$sku]['purchase_amount']) || ($sku_value['warehouse_code'] != $suggest_info['warehouse_code'])) {
                                if (empty($sku_value['modify_remark'])) {
                                    throw new Exception("$purchase_number - $sku:当采购数量、采购仓库变更时，备注信息必填");
                                }
                            }

                            $save_order_item['modify_remark'] = $sku_value['modify_remark'];

                            if(in_array($orderInfo['purchase_type_id'],[2,3])) {
                                $skusData = $this->purchase_db->from("product")->where("sku", $sku)->select("purchase_price,ticketed_point")->get()->row_array();
                                if ($value['is_drawback']) {

                                    $save_order_item['product_base_price'] = $skusData['purchase_price']; // 未税单价
                                    $save_order_item['purchase_unit_price'] = format_two_point_price($skusData['purchase_price'] * (1 + ($skusData['ticketed_point'] / 100)));
                                } else {
                                    $save_order_item['product_base_price'] = $skusData['purchase_price']; // 未税单价
                                    $save_order_item['purchase_unit_price'] = $skusData['purchase_price'];
                                }
                            }


                            $result3 = $this->purchase_db->update($this->item_table_name, $save_order_item, ['purchase_number' => $purchase_number, 'sku' => $sku]);
                            if (empty($result3)) {
                                throw new Exception("采购单明细[$purchase_number - $sku]更新失败");
                            }

                            $suggest_map = $this->purchase_suggest_map_model->get_one_by_sku($purchase_number, $sku, '', true);

                            $suggest_time = [

                                'es_shipment_time' => $sku_value['es_shipment_time'],
                                'plan_product_arrive_time' => $sku_value['plan_arrive_time']
                            ];
                            if($value['warehouse_code'] == 'CG-YPC'){
                                if($YP_account_type)$suggest_time['account_type'] = $YP_account_type;
                                if($YP_pay_type)$suggest_time['pay_type'] = $YP_pay_type;

                                $suggest_time['supplier_code'] = $value['supplier_code'];
                                $suggest_time['supplier_name'] = $value['supplier_name'];
                            }
                            $this->purchase_db->where("demand_number",$suggest_map['demand_number'])
                                ->update('purchase_suggest',$suggest_time);


                            if (!empty($suggest_map)) {
                                $this->purchase_db->update('purchase_suggest_map',
                                    ['confirm_number' => $sku_value['confirm_amount'],
                                        'purchase_total_price' => $sku_value['confirm_amount'] * $order_items_list[$sku]['purchase_unit_price']],
                                    ['id' => $suggest_map['id']]);
                                $result4 = $this->purchase_suggest_model->change_status(null, $suggest_map['demand_number']);
                                if (empty($result4)) {
                                    throw new Exception("采购单明细[$purchase_number - $sku]备货单[" . $suggest_map['demand_number'] . "]更新失败");
                                }

                                //锁单不得提交采购经理审核
                                if ($suggest_info['lock_type'] == LOCK_SUGGEST_ENTITIES && $is_submit == 1) {
                                    throw new Exception("备货单[" . $suggest_map['demand_number'] . "]锁单中");
                                }

                                $update_suggest_data = [];
                                //采购数量变化时，填写的备注，需要返回到“已生成备货单”页面的备注中
                                if ($sku_value['confirm_amount'] != $order_items_list[$sku]['purchase_amount']) {
                                    $update_suggest_data['sales_note'] = $suggest_info['sales_note'] . ' ' . $sku_value['modify_remark'];
                                }
                                if ($suggest_info['warehouse_code'] != $sku_value['warehouse_code']) {
                                    $update_suggest_data['warehouse_code'] = $sku_value['warehouse_code'];

                                    if (!isset($warehouse_code_list[$sku_value['warehouse_code']])) {
                                        throw new Exception("采购单明细[$purchase_number - $sku] 仓库：" . $sku_value['warehouse_code'] . "不存在");
                                    } else {
                                        $update_suggest_data['warehouse_name'] = $warehouse_code_list[$sku_value['warehouse_code']];
                                    }

                                }
                                if(in_array($orderInfo['purchase_type_id'],[2,3])) {
                                    $skusData = $this->purchase_db->from("product")->where("sku", $sku)->select("purchase_price,ticketed_point")->get()->row_array();
                                    if ($value['is_drawback']) {
                                        $change_price_de = format_two_point_price($skusData['purchase_price'] * (1 + ($skusData['ticketed_point'] / 100)));
                                        $update_suggest_data['purchase_total_price'] = format_two_point_price($skusData['purchase_price'] * (1 + ($skusData['ticketed_point'] / 100))) * $suggest_info['purchase_amount'];
                                        $update_suggest_data['purchase_name'] = 'SZYB';
                                    } else {
                                        $change_price_de = format_two_point_price($skusData['purchase_price']);
                                        $update_suggest_data['purchase_total_price'] = format_two_point_price($skusData['purchase_price']) * $suggest_info['purchase_amount'];
                                        $update_suggest_data['purchase_name'] = 'HKYB';
                                    }
                                    $update_suggest_data['is_drawback'] = $value['is_drawback'];
                                    $update_suggest_data['purchase_unit_price'] = $change_price_de;
                                }
                                if ($update_suggest_data) {
                                    if($value['warehouse_code'] == 'CG-YPC'){
                                        $update_suggest_data['supplier_code'] = $value['supplier_code'];
                                        $update_suggest_data['supplier_name'] = $value['supplier_name'];
                                    }
                                    $result5 = $res = $this->purchase_db->where('demand_number', $suggest_map['demand_number'])->update('purchase_suggest', $update_suggest_data);
                                    if (empty($result5)) {
                                        throw new Exception("采购单明细[$purchase_number - $sku]备货单[" . $suggest_map['demand_number'] . "]更新失败");
                                    }
                                }

                            } else {
                                throw new Exception("采购单明细[$purchase_number - $sku]未找到关联的备货单");
                            }

                            // 分摊下单数量
                            $this->purchase_demand_model->apportionPurchaseAmount($suggest_map['demand_number'],$had_purchase_amount);
                        }
                    }
                }



                if ($value['account_type'] == SUPPLIER_SETTLEMENT_CODE_TAP_DATE) {//因结算方式可能存在变更 用提交的结算方式来判断
                    //如果结算方式为线上账期,则支付方式必须为线上支付宝
                    if ($value['pay_type'] != PURCHASE_PAY_TYPE_ALIPAY) {
                        throw new Exception("采购单明细[$purchase_number] 该供应商结算方式是1688账期，支付方式只能是支付宝，请重新维护支付方式");
                    }

                    //检验该拍单号下单时的交易方式是否为“账期交易”
                    $check_account_type = $this->purchase_order_model->get_ali_order_info($value['pai_number']);
                    $pai_number = $value['pai_number'];
                    if ($check_account_type['status'] === 'error') {
                        throw new Exception('拍单号[' . $pai_number . ']请求异常，请重新确认，原因：' . $check_account_type['error_message']);
                    } elseif ($check_account_type['status'] === 'success') {//更新应付款时间
                        $this->purchase_db->where('purchase_number', $purchase_number)->update('purchase_order_pay_type', ['accout_period_time' => $check_account_type['accoutPeriodTime']]);
                        $this->purchase_db->where('purchase_number', $purchase_number)->update('purchase_order_items', ['need_pay_time' => $check_account_type['accoutPeriodTime']]);
                    }
                }

                if ($this->purchase_db->trans_status() === FALSE) {
                    $this->purchase_db->trans_rollback();
                    throw new Exception('事务提交操作失败');
                } else {
                    $this->purchase_db->trans_commit();
                    $return['code'] = true;
                }
            }

            $this->purchase_order_extend_model->amount_change_push_to_plan($confirm_amount);
            $this->purchase_order_extend_model->verify_task_handle($vpl, 1, 2); // 解锁
        }catch (Exception $exc) {
            if(!empty($vpl))$this->purchase_order_extend_model->verify_task_handle($vpl, 1, 2); // 解锁
            $this->purchase_db->trans_rollback();
            $return['message'] = $exc->getMessage();
        }
        return $return;
    }


    /**
     * 获取锁单中的备货单
     */
    public function get_demand_lock_data($pur_number, $sku=[])
    {
        $res = [];
        if(empty($pur_number) || empty($sku))return $res;
        if(!is_array($pur_number))$pur_number = [$pur_number];
        $data = $this->purchase_db->from('purchase_suggest_map as map')
            ->select("map.demand_number,map.sku")
            ->join('pur_purchase_suggest as su', 'map.demand_number=su.demand_number', 'left')
            ->where("su.lock_type",LOCK_SUGGEST_ENTITIES)
            ->where_in("map.purchase_number", $pur_number)
            ->get()
            ->result_array();
        if($data && count($data) > 0){
            foreach ($data as $val){
                if(!empty($val['sku']) && in_array($val['sku'], $sku))$res[] = $val['demand_number'];
            }
        }
        return $res;
    }


    /**
     * 等待带货后修改采购单信息
     */
    public function get_change_order_preview($ids=[])
    {
        $res = ["code" => 0, "data" => [], "msg" => "获取数据失败！"];
        if(count($ids) == 0)return $res;
        $purchase_list = $this->purchase_db->select("purchase_number")->from("purchase_order_items")->where_in('id', $ids)->get()->result_array();
        $purchase_list = array_column($purchase_list, 'purchase_number');
        $filed = 'o.purchase_number, o.supplier_code,o.supplier_name,o.plan_product_arrive_time, o.ali_order_amount,o.source, o.is_ali_order,
        it.confirm_amount, it.purchase_unit_price, it.sku, it.demand_number,it.id, it.temporary_plan_arrive_time,it.plan_arrive_time,
        pt.real_price,pt.pai_number, sg.suggest_order_status,pt.apply_amount, pt.freight, pt.discount, pt.process_cost,
        pd.product_thumb_url,pd.product_weight,pt.change_old_price';
        $base = $this->purchase_db
            ->select($filed)
            ->from("purchase_order_items as it")
            ->join("pur_purchase_order as o", "o.purchase_number=it.purchase_number", "inner")
            ->join("pur_purchase_suggest as sg", "sg.demand_number=it.demand_number", "left")
            ->join("pur_product as pd", "it.sku=pd.sku", "left")
            ->join("pur_purchase_order_pay_type as pt", "pt.purchase_number=it.purchase_number", "left")
            ->where_in('o.purchase_number', $purchase_list)
            ->get()
            ->result_array();

        if(!$base || empty($base)){
            $res['msg'] = '没有需要修改的数据';
            return $res;
        }
        $data = [];
        foreach ($base as $val){
            $pur_number = $val['purchase_number'];
            // 备货单维度详情
            $row = [];
            $row['id']                      = $val['id'];
            $row['sku']                     = $val['sku'];
            $row['demand_number']           = $val['demand_number'];
            $row['product_thumb_url']       = erp_sku_img_sku_thumbnail($val['product_thumb_url']);
            $row['confirm_amount']          = $val['confirm_amount'];
            $row['purchase_unit_price']     = $val['purchase_unit_price'];
            $row['plan_product_arrive_time']= $val['temporary_plan_arrive_time'];
            $row['plan_arrive_time']        = $val['plan_arrive_time'];
            $row['purchase_price']          = $val['purchase_unit_price'] * $val['confirm_amount'];
            $row['cancel']                  = 0;
            $row['loss']                    = 0;
            $row['suggest_order_status']    = getPurchaseStatus($val['suggest_order_status']);

            if(!isset($data[$pur_number])){
                $supplier_info = $this->supplier_model->get_supplier_info($val['supplier_code']);
                $old_price = [];
                if(SetAndNotEmpty($val, 'change_old_price')){
                    try{
                        $old_price = json_decode($val['change_old_price'], true);
                    }catch (Exception $e){}
                }
                $data[$pur_number] = [
                    "source"                => $val['source'],
                    "is_ali_order"          => $val['is_ali_order'],
                    "purchase_number"       => $pur_number,
                    // 34415
                    "is_postage"            => isset($supplier_info['is_postage']) && $supplier_info['is_postage'] == 1? true:false,
                    "supplier_code"         => $val['supplier_code'],
                    "supplier_name"         => $val['supplier_name'],
                    "ali_order"             => $val['pai_number'],
                    "ali_order_amount"      => $val['ali_order_amount'],
                    "purchase_price"        => $val['real_price'],
                    "apply_amount"          => SetAndNotEmpty($val, 'apply_amount', 'n')? ($val['apply_amount'] / 100): 0,
                    "freight"               => $val['freight'],
                    "reference_freight"     => 0,
                    "discount"              => $val['discount'],
                    "process_cost"          => $val['process_cost'],
                    "old_freight"           => isset($old_price['freight']) ? $old_price['freight']: 0,
                    "old_discount"          => isset($old_price['discount']) ? $old_price['discount']: 0,
                    "old_process_cost"      => isset($old_price['process_cost']) ? $old_price['process_cost']: 0,
                    "weight"                => 0,
                    "remarks"               => '',
                    "items_list"            => [],
                ];
            }

            $data[$pur_number]['purchase_total_price'] += round($row['purchase_price'], 3);
            $data[$pur_number]['weight'] += round($val['product_weight'] / 1000, 3);
            $data[$pur_number]['items_list'][] = $row;
        }

        if(count($data) > 0){
            $data = array_values($data);
            $res['code'] = 1;
            $res['data'] = $data;
        }

        // 获取所有取消和报损
        return $res;
    }


    /**
     * 等待带货后修改采购单信息 保存
     */
    public function save_change_order_preview($params=[])
    {
        $res = ["code" => 0, "msg" => '默认修改失败!'];
        if(empty($params)){
            $res['msg'] = '提交的数据不能为空!';
            return $res;
        }
        $purchase_number = array_column($params, 'purchase_number');
        $return['code'] = true;

        try{
            $this->purchase_db->trans_start();
            $base = $this->get_order($purchase_number); // 基础订单数据

            // 公共验证
            $verify = $this->verify_order_for_base($params, $base);
            if($verify != '')throw new Exception($verify);

            // 差异验证 未完成

            $user_name = getActiveUserName();
            $user_name = !empty($user_name)?$user_name:' ';
            $this_time = date("Y-m-d H:i:s",time());
            $this->load->model('compact/compact_model');

            // 更新数据
            foreach ($params as $val){
                $pur_number = $val['purchase_number'];
                if(empty($pur_number))throw new Exception('采购单号不能为空！');
                $v_data = '';
                foreach ($base as $bv){
                    if($pur_number == $bv['purchase_number'])$v_data = $bv;
                }
                $is_change = false;

                // 当采购单的"采购来源=网采","是否1688下单=否"时,拍单号可以修改, 其余情况不可修改
                $ali_order_change = $v_data['source'] == SOURCE_NETWORK_ORDER && $v_data['is_ali_order'] == 0 ? true:false;

                $pay_type = [];
                $old_data = [];
                $freight = $discount = $process_cost = false;
                if(isset($val['freight'])){
                    $pay_type['freight'] = $val['freight'];
                    $freight = $val['freight']??0;
                }
                if(isset($val['discount'])){
                    $pay_type['discount'] = $val['discount'];
                    $discount = $val['discount']??0;
                }
                if(isset($val['process_cost'])){
                    $pay_type['process_cost'] = $val['process_cost'];
                    $process_cost = $val['process_cost']??0;
                }
                if($freight === false)$freight = $v_data['freight_t']??0;
                if($discount === false)$discount = $v_data['discount_t']??0;
                if($process_cost === false)$process_cost = $v_data['process_cost_t']??0;
                $old_data["freight"] = $v_data['freight_t']??0;
                $old_data["discount"] = $v_data['discount_t']??0;
                $old_data["process_cost"] = $v_data['process_cost_t']??0;

                // 拍单号不一致 ali订单表拍单号
                if($ali_order_change && SetAndNotEmpty($val, 'ali_order') && SetAndNotEmpty($v_data, 'pai_number') && $val['ali_order'] != $v_data['pai_number']){
                    $pay_type['pai_number'] = $val['ali_order'];
                    $is_change = true;
                    $this->purchase_db->update('ali_order', ["order_id" => $val['ali_order']], ['purchase_number' => $pur_number]);
                }

                // 修改 pay_type
                // 计算逻辑 订单总商品额 + 运费 + 加工费 - 优惠额 + 代采佣金   保留两位小数 format_two_point_price
                $product_money = $v_data['product_money'] ?? 0;
                $commission = $v_data['commission'] ?? 0;
                if(count($pay_type) > 0){
                    $pay_type['real_price'] = format_two_point_price($product_money + $freight + $process_cost - $discount + $commission);
                    $pay_type['confirm_type'] = 2;
                    $pay_type['change_old_price'] = json_encode($old_data);
                    if(!empty($val['ali_order']) && $base['pai_number'] != $val['ali_order']){
                        $pay_type['accout_period_time'] = '0000-00-00 00:00:00';// 修改了 拍单号就清空 应付款时间
                        $pay_type['is_request'] = 0;
                    }
                    $this->purchase_db->update('purchase_order_pay_type', $pay_type, ['purchase_number' => $pur_number]);
                    $is_change = true;
                }

                // 修改备注
                $note = $val['remarks'].'_'.$user_name.'_'.$this_time;
                $this->purchase_db->update($this->table_name, [
                    "change_data_apply_note" => $note
                ], ['purchase_number' => $pur_number]);

                if(isset($val['items_list']) && is_array($val['items_list']) && count($val['items_list']) > 0){
                    $items_has_keys = "PURCHASE_PLANE_ARRIVE_TIME_RESTRICT";
                    foreach ($val['items_list'] as $item_val){
                        $items_has_filed = $pur_number.'_'.$item_val['id'];
                        $items_has = $this->rediss->getHashData($items_has_keys, $items_has_filed);
                        $items_has_tmp = 0;
                        try{
                            $items_has = json_decode($items_has, true);
                            $items_has_tmp = isset($items_has[0])?(int)$items_has[0]:0;
                        }catch (Exception $ej){}
                        $ppat = isset($item_val['plan_product_arrive_time'])? $item_val['plan_product_arrive_time'] : '';
                        if(isset($item_val['id']) && !empty($ppat) && $ppat != '0000-00-00 00:00:00' && $items_has_tmp < 3){
                            $this->purchase_db->update($this->item_table_name, ["temporary_plan_arrive_time" => $ppat], ['id' => $item_val['id']]);
                            $is_change = true;
                            $this->rediss->addHashData($items_has_keys, $items_has_filed, $items_has_tmp + 1); // 修改次数
                        }
                    }
                }

                if($v_data['source'] == SOURCE_COMPACT_ORDER){
                    $this->compact_model->refresh_compact_data(null, $pur_number);// 刷新合同金额
                    $is_change = true;
                }

                if($is_change){
                    $this->purchase_order_model->change_status($pur_number, PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT);// 采购单状态变为 信息修改待审核

                    //添加修改日志
                    operatorLogInsert([
                        'id' => $pur_number,
                        'type' => 'UPDATE_ORDER_INFO_IN_WAITING_AUDIT',
                        'content' => '订单修改运费与优惠',
                        'detail' => '信息修改-原因：'.$note,
                    ]);
                    $res['msg'] = '修改成功';
                    $res['code'] = 0;
                }
            }

            $this->purchase_db->trans_commit();
        }catch (Exception $e){
            $res['msg'] = $e->getMessage();
            $this->purchase_db->trans_rollback();
        }

        return $res;
    }

    /**
     * 允许修改信息验证
     */
    private function verify_order_for_base($params, $base)
    {
        $res = '';
        try{
            $not_detail = [];
            $check_res_1 = [];
            $check_res_2 = [];
            $remarks_list = [];
            foreach ($base as $val){
                $val_pur = $val['purchase_number'];
                $p_data = '';
                foreach ($params as $pv){
                    if($val_pur == $pv['purchase_number']){
                        $p_data = $pv;

                        if(!isset($pv['remarks']) or empty($pv['remarks'])) $remarks_list[] = $val_pur;// 备注必填
                    }
                }
                // 验证采购单不存在
                if($p_data == '' || !isset($val['items_list'])){
                    $not_detail[] = $val_pur;
                    continue;
                }
                // 可修改状态
                $check_res1 = $this->purchase_order_model->check_status_able_change($val['purchase_order_status']);
                if($check_res1 !== true){
                    $check_res_1[] = $val_pur;
                    continue;
                }
                // 可修改的支付状态
                $check_res2 = $this->purchase_order_model->check_pay_status_able_change($val['pay_status']);
                if($check_res2 !== true){
                    $check_res_2[] = $val_pur;
                    continue;
                }
            }
            $str1 = '采购单状态为【等待到货、已到货待检测、全部到货、部分到货等待剩余到货、部分到货不等待剩余到货、信息修改驳回、已作废订单】才允许进行此操作';
            if(!empty($not_detail))throw new Exception(implode(',', $not_detail).'采购单或采购单明细缺失');
            if(!empty($check_res_1))throw new Exception(implode(',', $check_res_1).$str1);
            if(!empty($check_res_2))throw new Exception(implode(',', $check_res_2).'只有付款状态为【未申请付款、请款被驳回、请款已取消】状态才允许进行此操作');
            if(!empty($remarks_list))throw new Exception(implode(',', array_unique($remarks_list)).'修改备注不能为空');
        }catch (Exception $e){
            $res = $e->getMessage();
        }
        return $res;
    }

    /**
     * 批量获取采购单数据
     */
    public function get_order($params=[])
    {
        if(empty($params))return [];
        $data = $this->purchase_db->from('purchase_order as o')
            ->select("o.*, t.*, t.discount as discount_t,t.freight as freight_t, t.discount as discount_t")
            ->join("pur_purchase_order_pay_type as t", "t.purchase_number=o.purchase_number", "left")
            ->where_in("o.purchase_number", $params)
            ->get()->result_array();
        if(!$data || empty($data))return [];
        $res = [];
        foreach ($data as $val){
            $val['items_list'] = $this->purchase_db->from('purchase_order')
                ->where("purchase_number=", $val['purchase_number'])
                ->get()->result_array();
            $res[] = $val;
        }
        return $res;
    }


    /**
     * 非1688下单订单信息修改-预览
     */
    public function get_change_order_data_preview($purchase_number)
    {
        $return = ['code' => false,'message' => '','data' => ''];
        $purchase_order_info = $this->purchase_order_model->get_one_with_demand_number($purchase_number);
        if(empty($purchase_order_info) or empty($purchase_order_info['items_list'])){
            $return['message'] = '采购单或采购单明细缺失';
            return $return;
        }

        if ($purchase_order_info['is_ali_order'] == 1){
            $return['message'] = '非1688下单才允许进行此操作';
            return $return;
        }

//        if ($purchase_order_info['source'] == SOURCE_COMPACT_ORDER){
//            $return['message'] = '合同单不允许操作，您可在请尾款时在请款单页面填写运费和优惠额';
//            return $return;
//        }

        $is_create_statement = $this->purchase_db->select('A.statement_number')
            ->from('pur_purchase_statement AS A')
            ->join('pur_purchase_statement_items AS B','A.statement_number=B.statement_number','LEFT')
            ->where('A.status_valid',1)
            ->where('B.purchase_number',$purchase_number)
            ->get()
            ->row_array();
        if($is_create_statement){
            $return['message'] = '已经生成对账号不允许修改，请作废对账单再进行修改';
            return $return;
        }

        if(($check_res1 = $this->purchase_order_model->check_status_able_change($purchase_order_info['purchase_order_status'])) !== true){
            $return['message'] = $check_res1;
            return $return;
        }

        if(($check_res2 = $this->purchase_order_model->check_pay_status_able_change($purchase_order_info['pay_status'])) !== true){
            $return['message'] = $check_res2;
            return $return;
        }

        if ($purchase_order_info['account_type'] != SUPPLIER_SETTLEMENT_CODE_TAP_DATE && $this->purchase_order_model->check_is_cancel_status($purchase_number)){
            $return['message'] = '存在取消未到货中/成功状态的记录,不允许申请';
            return $return;
        }

        if ($purchase_order_info['account_type'] != SUPPLIER_SETTLEMENT_CODE_TAP_DATE && $this->purchase_order_model->get_reportloss_info($purchase_number)){
            $return['message'] = '存在报损中/成功状态的记录,不允许申请';
            return $return;
        }

        $my_order_data['freight'] = 0;
        $my_order_data['discount'] = 0;
        $my_order_data['process_cost'] = 0;
        $my_order_data['purchase_type_id'] = $purchase_order_info['purchase_type_id'];

        $sku_list = [];
        foreach ($purchase_order_info['items_list'] as $value){
            $my_order_data['freight'] += $value['freight'];
            $my_order_data['discount'] += $value['discount'];
            $my_order_data['process_cost'] += $value['process_cost'];
            if(!in_array($value['sku'], $sku_list))$sku_list[] = $value['sku'];
        }

        foreach ($purchase_order_info['items_list'] as &$value){
            $cancel_data = $this->purchase_order_model->get_order_cancel_list($value['purchase_number'],$value['sku']);
            if (!empty($cancel_data)){
                $value['cancel_amount'] = $cancel_data[$value['sku']];
            }else{
                $value['cancel_amount'] = 0;
            }

            $value['suggest_status_ch'] = NULL;
            $value['suggest_status_ch'] = getPurchaseStatus($value['suggest_order_status']);
        }

        $id = getActiveUserId();
        $account = getUserEnablePurchaseAccount($id);
        $my_order_data['order_total'] = $this->purchase_order_model->get_purchase_order_total($purchase_number);//订单总金额
        $my_order_data['real_price'] = format_two_point_price($my_order_data['order_total']+$my_order_data['freight']-$my_order_data['discount']+$my_order_data['process_cost']);//实际总额=订单总额+总运费-总优惠额
        $my_order_data['purchase_acccount'] = $account;
        $my_order_data['order_items'] = $purchase_order_info['items_list'];

        $ship_weight = [
            "ship"  => 0,
            "weight"=> 0,
        ];
        if(!empty($sku_list)){
            $this->load->model('purchase/Purchase_order_extend_model', 'order_extend');
            $ship_weight = $this->order_extend->handle_weight_and_ship($sku_list, $purchase_order_info);
        }
        $my_order_data['total_ship'] = $ship_weight['ship'];
        $my_order_data['total_weight'] = $ship_weight['weight'];

        $return['code'] = true;
        $return['data'] = $my_order_data;
        return $return;
    }


    /**
     * 非1688下单订单信息修改-保存 （已废弃）
     * @param $purchase_number
     * @param $purchase_account
     * @param $pai_number
     * @param $freight
     * @param $discount
     * @param $process_cost
     * @param mixed $apply_note
     * @param $freight_sku
     * @param $discount_sku
     * @param $process_cost_sku
     * @param null $plan_arrive_time
     * @return array
     */
    public function get_change_order_data_save(
        $purchase_number,
        $purchase_account,
        $pai_number,
        $freight,
        $discount,
        $process_cost,
        $apply_note='',
        $freight_sku,
        $discount_sku,
        $process_cost_sku,
        $plan_arrive_time=NULL
    )
    {
        $return = ['code' => false,'message' => ''];
        $this->purchase_db->trans_start();
        try {
            $this->load->model('compact/compact_items_model');
            $this->load->model('finance/purchase_order_pay_model'); // 请款单 SKU 明细
            $this->load->model('finance/purchase_order_pay_type_model');
            if (!is_two_decimal($freight) || !is_two_decimal($discount) || !is_two_decimal($process_cost)) throw new Exception('总运费,优惠,加工费小数最多只能为两位');

            $purchase_order_info = $this->purchase_order_model->get_one($purchase_number);
            if(empty($purchase_order_info) or empty($purchase_order_info['items_list'])){
                throw new Exception('采购单或采购单明细缺失');
            }

            if ($purchase_order_info['is_ali_order'] == 1){
                throw new Exception('非1688下单才允许进行此操作');
            }

            if(($check_res1 = $this->purchase_order_model->check_status_able_change($purchase_order_info['purchase_order_status'])) !== true){
                throw new Exception($check_res1);
            }

            if(($check_res2 = $this->purchase_order_model->check_pay_status_able_change($purchase_order_info['pay_status'])) !== true){
                throw new Exception($check_res2);
            }

            if($purchase_order_info['pay_type']==PURCHASE_PAY_TYPE_ALIPAY && ( empty($pai_number) || empty($purchase_account) )){
                throw new Exception('支付方式为线上支付宝的，拍单号,网拍账号必填');
            }

            if($purchase_order_info['account_type']==SUPPLIER_SETTLEMENT_CODE_TAP_DATE && ( empty($pai_number) || empty($purchase_account) )){
                throw new Exception('结算方式为1688账期的，拍单号,网拍账号必填');
            }

            if ($purchase_order_info['account_type'] == SUPPLIER_SETTLEMENT_CODE_TAP_DATE){
                //如果结算方式为线上账期,则支付方式必须为线上支付宝
                if($purchase_order_info['pay_type']!= PURCHASE_PAY_TYPE_ALIPAY){
                    throw new Exception("供应商结算方式是1688账期，支付方式只能是支付宝，请重新维护支付方式");
                }

                //检验该拍单号下单时的交易方式是否为“账期交易”
                $check_account_type = $this->purchase_order_model->get_ali_order_info($pai_number);
                if($check_account_type['status'] === 'error'){
                    throw new Exception($check_account_type['error_message']);
                }
            }

            //获取采购明细,分摊运费优惠
            $items_list =  $item_info = $this->purchase_order_items_model->get_item($purchase_number);

            // 计算分摊的运费、优惠额
            /*$purchase_sku_list = array_column($items_list,'confirm_amount','sku');
            $purchase_sku_list = [$purchase_number => $purchase_sku_list];
            $average_distribute_freight = amountAverageDistribute(floatval($freight), $purchase_sku_list);
            $average_distribute_discount = amountAverageDistribute(floatval($discount), $purchase_sku_list);*/

            $total_freight = 0;
            $total_discount = 0;
            $total_process_cost = 0;
            $total_product_money = 0;
            // 更新采购单SKU明细
            foreach($items_list as $sku_value) {

                $sku = $sku_value['sku'];
                if (!is_two_decimal($freight_sku[$sku]) || !is_two_decimal($discount_sku[$sku]) || !is_two_decimal($process_cost_sku[$sku])){
                    throw new Exception("采购单明细[$purchase_number - $sku]运费,优惠,加工费小数最多只能为两位");
                }
                $save_order_item = [
                    'freight'  => $freight_sku[$sku],
                    'discount' => $discount_sku[$sku],
                    'process_cost' => $process_cost_sku[$sku],
                ];
                if(isset($plan_arrive_time[$sku])){

                    $save_order_item['temporary_plan_arrive_time'] =$plan_arrive_time[$sku];
                }


                if($purchase_order_info['source'] == SOURCE_COMPACT_ORDER){// 合同单 如果合同已付款则不能修改
                    $compact_item = $this->compact_items_model->get_compact_by_purchase($purchase_number);
                    if($compact_item){
                        $compact_paid = $this->purchase_order_pay_model->get_pay_total_detail($compact_item['compact_number'],[PART_PAID,PAY_PAID]);// 获取已支付的信息
                        $compact_paid = arrayKeyToColumn($compact_paid,'combine_key');
                        $compact_paid = isset($compact_paid[$purchase_number.'_'.$sku])?$compact_paid[$purchase_number.'_'.$sku]:[];
                        if($compact_paid){
                            if(bccomp($sku_value['freight'],$save_order_item['freight'],2) != 0 and (isset($compact_paid['detail_freight']) and intval($compact_paid['detail_freight']) > 0)){
                                $return['message'] = '该合同已经付过运费，不能修改运费';
                                return $return;
                            }
                            if(bccomp($sku_value['discount'],$save_order_item['discount'],2) != 0 and (isset($compact_paid['detail_pay_total']) and intval($compact_paid['detail_pay_total']) > 0)){
                                $return['message'] = '该合同已经付过款了，不能修改优惠额';
                                return $return;
                            }
                            if(bccomp($sku_value['process_cost'],$save_order_item['process_cost'],2) != 0 and (isset($compact_paid['detail_process_cost']) and intval($compact_paid['detail_process_cost']) > 0)){
                                $return['message'] = '该合同已经付过加工费，不能修改加工费';
                                return $return;
                            }
                        }
                    }
                }

                $total_freight += $freight_sku[$sku];
                $total_discount += $discount_sku[$sku];
                $total_process_cost += $process_cost_sku[$sku];
                $total_product_money += $sku_value['purchase_unit_price'] * $sku_value['confirm_amount'];

                $result4 = $this->purchase_db->update($this->item_table_name,$save_order_item,['purchase_number' => $purchase_number,'sku' => $sku]);
                if( empty($result4) ){
                    throw new Exception("采购单明细[$purchase_number - $sku]更新失败");
                }

            }

            $have_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);
            //修改运费,优化,拍单号 更新采购单确认信息
            $save_pay_type_info = [
                'freight'              => format_two_point_price($total_freight),
                'discount'             => format_two_point_price($total_discount),
                'process_cost'         => format_two_point_price($total_process_cost),
                'real_price'           => format_two_point_price($total_product_money + $total_freight - $total_discount + $total_process_cost + $have_pay_type['commission']),
                'purchase_acccount'    => $purchase_account,
                'pai_number'           => trim($pai_number),
                'confirm_type'         => '2',// 确认提交类型（1.采购单列表提交，2.批量编辑采购单提交）
                'freight_note'         => '',//运费说明
            ];

            if($have_pay_type['pai_number'] != $save_pay_type_info['pai_number']){
                $save_pay_type_info['accout_period_time'] = '0000-00-00 00:00:00';// 修改了 拍单号就清空 应付款时间
                $save_pay_type_info['is_request']         = 0;
            }
            $result2 = $this->purchase_order_pay_type_model->update_one($have_pay_type['id'],$save_pay_type_info);

            if(empty($result2))throw new Exception("采购单[$purchase_number]确认信息更新失败");

            if($purchase_order_info['source'] == SOURCE_COMPACT_ORDER){
                $this->load->model('compact/compact_model');
                $this->compact_model->refresh_compact_data(null,$purchase_number);// 刷新合同金额
            }

            $result3 = $this->purchase_order_model->change_status($purchase_number, PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT);// 采购单状态变为 信息修改待审核
            if(empty($result3))throw new Exception("采购单[$purchase_number]修改状态失败");

            $user_name = getActiveUserName();
            $user_name = !empty($user_name)?$user_name:' ';

            $apply_note = $apply_note.'_'.$user_name.'_'.date("Y-m-d H:i:s",time());
            $result5 = $this->purchase_db->update($this->table_name,['change_data_apply_note'=>$apply_note],['purchase_number' => $purchase_number]);
            if(empty($result5))throw new Exception("采购单[$purchase_number]添加信息修改申请备注失败");

            $this->purchase_db->trans_commit();
            $return['code'] = true;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['message'] = $e->getMessage();
        }
        return $return;

    }


    /**
     *  获取1688 修改
     * @author Totoro Jolon
     * @param string $purchase_number 采购单号
     * @param string $pai_number 拍单号
     * @return array
     * @throws Exception
     */
    public function get_edit_ali_order_data_preview($purchase_number,$pai_number){
        $return = ['code' => false,'message' => '','data' => ''];
        $purchase_order_info = $this->get_one_with_demand_number($purchase_number);

        if ($purchase_order_info['is_ali_order'] ==0){
            $return['message'] = '1688下单才允许进行此操作';
            return $return;
        }
        if ($purchase_order_info['source'] == SOURCE_COMPACT_ORDER){
            $return['message'] = '合同单不允许操作，您可在请尾款时在请款单页面填写运费和优惠额';
            return $return;
        }

        if(($check_res1 = $this->check_status_able_change($purchase_order_info['purchase_order_status'])) !== true){
            $return['message'] = $check_res1;
            return $return;
        }

        if(($check_res2 = $this->check_pay_status_able_change($purchase_order_info['pay_status'])) !== true){
            $return['message'] = $check_res2;
            return $return;
        }

        if($purchase_order_info['account_type'] == SUPPLIER_SETTLEMENT_CODE_TAP_DATE){// 1688账期且订单已完结 可以修改订单运费优惠额
            if(!in_array($purchase_order_info['purchase_order_status'],
                [
                    PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT,
                    PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                    PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                    PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION,
                    PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                    PURCHASE_ORDER_STATUS_CANCELED])){
                $return['message'] = '账期订单请在信息修改待审核、等待到货、全部到货、部分到货不等待剩余、已作废订单状态下才可以修改';
                return $return;
            }
            if ($this->unfinished_cancel_status($purchase_number)){// 取消未到货未完结
                $return['message'] = '存在取消未到货中状态的记录,不允许申请';
                return $return;
            }
            if ($this->unfinished_reportloss_info($purchase_number)){// 报损未完结
                $return['message'] = '存在报损中状态的记录,不允许申请';
                return $return;
            }
        }else{
            if ($this->check_is_cancel_status($purchase_number)){
                $return['message'] = '存在取消未到货中/成功状态的记录,不允许申请';
                return $return;
            }

            if ($this->get_reportloss_info($purchase_number)){
                $return['message'] = '存在报损中/成功状态的记录,不允许申请';
                return $return;
            }
        }

        $this->load->library('alibaba/AliOrderApi');
        $aliOrderAllPrice = $this->aliorderapi->getAliOrderAllPrice($pai_number);
        $aliPrice         = $aliOrderAllPrice['totalAmount'];// 订单总金额
        $aliRefundPrice   = $aliOrderAllPrice['applyTotalAmount'];// 退款总金额

        $this->load->model('finance/purchase_order_pay_type_model');

        $have_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);

        if($have_pay_type){
            $my_order_data['freight']           = $have_pay_type['freight'];
            $my_order_data['discount']          = $have_pay_type['discount'];
            $my_order_data['process_cost']      = $have_pay_type['process_cost'];
            $my_order_data['purchase_acccount'] = $have_pay_type['purchase_acccount'];
            $my_order_data['pai_number']        = $have_pay_type['pai_number'];
            $my_order_data['order_total']       = $this->get_purchase_order_total($purchase_number);//订单总金额
            $my_order_data['ali_price']         = format_two_point_price($aliPrice);//1688 订单总额
            $my_order_data['ali_refund_price']  = format_two_point_price($aliRefundPrice);// 1688退款金额
            $my_order_data['real_price']        = format_two_point_price($my_order_data['order_total'] + $have_pay_type['freight'] - $have_pay_type['discount'] + $have_pay_type['process_cost']);//实际总额=订单总额+总运费-总优惠额
        }else{
            $return['message'] = '采购单信息确认-请款金额相关信息缺失';
            return $return;
        }

        $sku_list = [];
        foreach ($purchase_order_info['items_list'] as &$value){
            if(!in_array($value['sku'], $sku_list))$sku_list[] = $value['sku'];
            $cancel_data = $this->get_order_cancel_list($value['purchase_number'],$value['sku']);
            if (!empty($cancel_data)){
                $value['cancel_amount'] = $cancel_data[$value['sku']];
            }else{
                $value['cancel_amount'] = 0;
            }
            $value['suggest_status_ch'] =getPurchaseStatus($value['suggest_order_status']);
        }
        $my_order_data['order_items'] = $purchase_order_info['items_list'];

        $ship_weight = [
            "ship"  => 0,
            "weight"=> 0,
        ];
        if(!empty($sku_list)){
            $this->load->model('purchase/Purchase_order_extend_model', 'order_extend');
            $ship_weight = $this->order_extend->handle_weight_and_ship($sku_list, $purchase_order_info);
        }
        $my_order_data['total_ship'] = $ship_weight['ship'];
        $my_order_data['total_weight'] = $ship_weight['weight'];

        $return['code'] = true;
        $return['data'] = $my_order_data;
        return $return;
    }


    /**
     * 保存1688订单修改 （已废弃）
     * @param $purchase_number
     * @param $purchase_account
     * @param $pai_number
     * @param $freight
     * @param $discount
     * @return array
     */
    public function get_edit_ali_order_data_save(
        $purchase_number,
        $purchase_account,
        $pai_number,
        $freight,
        $discount,
        $process_cost,
        $order_total,
        $note,
        $freight_sku,
        $discount_sku,
        $process_cost_sku,
        $plan_arrive_time=NULL
    ){
        $return = ['code' => false,'message' => ''];
        $this->purchase_db->trans_start();
        try {
            if (!is_two_decimal($freight) || !is_two_decimal($discount) || !is_two_decimal($process_cost)) throw new Exception('总运费,优惠,加工费小数最多只能为两位');

            $purchase_order_info = $this->purchase_order_model->get_one($purchase_number);
            if(empty($purchase_order_info) or empty($purchase_order_info['items_list'])) {
                throw new Exception('采购单或采购单明细缺失');
            }

            if ($purchase_order_info['is_ali_order'] == 0){
                throw new Exception('非1688下单才允许进行此操作');
            }

            if(($check_res1 = $this->purchase_order_model->check_status_able_change($purchase_order_info['purchase_order_status'])) !== true){
                throw new Exception($check_res1);
            }

            if(($check_res2 = $this->purchase_order_model->check_pay_status_able_change($purchase_order_info['pay_status'])) !== true){
                throw new Exception($check_res2);
            }

            if(empty($pai_number)){
                throw new Exception('结算方式为1688账期的，拍单号,网拍账号必填');
            }

            //拍单号获取1688订单总额
            $this->load->library('alibaba/AliOrderApi');
            $this->load->model('purchase/Purchase_order_cancel_model', 'orderCancelModel');
            $orderRefund      = $this->orderCancelModel->get_cancel_total_by_sku($purchase_number);// 采购单取消未金额
            $orderCancelPrice = $orderRefund?$orderRefund['cancel_total_price']:0;

            $aliOrderAllPrice = $this->aliorderapi->getAliOrderAllPrice($pai_number);
            $aliPrice         = $aliOrderAllPrice['totalAmount'];// 订单总金额
            $aliRefundPrice   = $aliOrderAllPrice['applyTotalAmount'];// 退款总金额

            $purchasePrice = $order_total+$freight-$discount+$process_cost - $orderCancelPrice;//采购订单应付总额
            $aliTotalPrice = $aliPrice - $aliRefundPrice;
            if(bccomp($aliTotalPrice,$purchasePrice,2)<>0){
                throw new Exception('1688的应付总额为:'.$aliTotalPrice.',与采购应付总额:'.$purchasePrice.'不一致');
            }

            $this->load->model('finance/purchase_order_pay_type_model');
            $have_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);

            $save_pay_type_info = [
                'freight'              => $freight,
                'discount'             => $discount,//
                'process_cost'         => $process_cost,
                'real_price'           => format_two_point_price($order_total + $freight - $discount + $process_cost + $have_pay_type['commission']),
                'purchase_acccount'    => $purchase_account,
                'pai_number'           => $pai_number,
                'confirm_type'         => '2',// 确认提交类型（1.采购单列表提交，2.批量编辑采购单提交）
                'freight_note'         => '',//运费说明
            ];
            $result2 = $this->purchase_order_pay_type_model->update_one($have_pay_type['id'],$save_pay_type_info);

            if(empty($result2))throw new Exception("采购单[$purchase_number]确认信息更新失败");

            //获取采购明细,分摊运费优惠
            $items_list =  $item_info = $this->purchase_order_items_model->get_item($purchase_number);

            // 计算分摊的运费、优惠额
            /*$purchase_sku_list = array_column($items_list,'confirm_amount','sku');
            $purchase_sku_list = [$purchase_number => $purchase_sku_list];
            $average_distribute_freight = amountAverageDistribute(floatval($freight), $purchase_sku_list);
            $average_distribute_discount = amountAverageDistribute(floatval($discount), $purchase_sku_list);*/
            // 更新采购单SKU明细
            foreach($items_list as $sku_value) {
                $sku = $sku_value['sku'];

                if (!is_two_decimal($freight_sku[$sku]) || !is_two_decimal($discount_sku[$sku])){
                    throw new Exception("采购单明细[$purchase_number - $sku]运费,优惠小数最多只能为两位");
                }
                $save_order_item = [
                    'freight'  => $freight_sku[$sku],
                    'discount' => $discount_sku[$sku],
                    'process_cost' => $process_cost_sku[$sku]
                ];

                if(!empty($plan_arrive_time[$sku])){

                    $save_order_item['temporary_plan_arrive_time'] = $plan_arrive_time[$sku];
                }



                $result4 = $this->purchase_db->update($this->item_table_name,$save_order_item,['purchase_number' => $purchase_number,'sku' => $sku]);
                if( empty($result4) ){
                    throw new Exception("采购单明细[$purchase_number - $sku]更新失败");
                }
            }


            $result3 = $this->purchase_order_model->change_status($purchase_number, PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT);// 采购单状态变为 信息修改待审核
            if(empty($result3))throw new Exception("采购单[$purchase_number]修改状态失败");

            $user_name = getActiveUserName();
            $user_name = !empty($user_name)?$user_name:' ';
            $note = $note.'_'.$user_name.'_'.date("Y-m-d H:i:s",time());
            $result5 = $this->purchase_db->update($this->table_name,['change_data_apply_note'=>$note],['purchase_number' => $purchase_number]);
            if(empty($result5))throw new Exception("采购单[$purchase_number]添加信息修改申请备注失败");
            //添加修改日志
            operatorLogInsert(
                [
                    'id' => $purchase_number,
                    'type' => $this->table_name,
                    'content' => '1688订单修改运费与优惠',
                    'detail' => '信息修改-原因：'.$note,
                ]);
            $this->purchase_db->trans_commit();
            $return['code'] = true;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['message'] = $e->getMessage();
        }
        return $return;
    }
}