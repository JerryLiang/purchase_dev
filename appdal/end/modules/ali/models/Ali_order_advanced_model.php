<?php
/**
 * 1688 订单数据模型
 * User: Jolon
 * Date: 2019/12/20 10:00
 */
class Ali_order_advanced_model extends Purchase_model {

    protected $table_name             = 'ali_order';
    protected $table_name_item        = 'ali_order_items';
    protected $table_name_pay         = 'purchase_order_pay';
    protected $table_order            = 'purchase_order';
    protected $table_cancel           = 'ali_order_cancel';
    protected $table_cancel_item      = 'ali_order_cancel_items';
    protected $table_order_pay_type   = 'purchase_order';
    protected $table_purchase_suggest = 'purchase_suggest';

    public function __construct(){
        parent::__construct();

        $this->load->model('purchase/purchase_order_model');
        $this->load->model('supplier/Supplier_model');
        $this->load->model('supplier/supplier_payment_account_model');
        $this->load->model('supplier/supplier_buyer_model');
    }

    /**
     * 验证备货单是否满足 1688一键下单 的条件
     * @param array $suggest_ids_list  备货单全量数去
     * @return array
     */
    public function verify_purchase_suggest($suggest_ids_list){
        $suggest_ids_str = implode(',',$suggest_ids_list);

        // 根据目标备货单ID查找备货单
        /*第一步：【1688一键下单】先进行数据检验：
            ——1.是否关联1688=是：根据“待生成备货单”中的“是否关联168”8获取即可
            ——2.采购链接有效：根据“待生成备货单”中的“链接是否有效”即可
            ——3.支付方式=支付宝：调取供应商管理列表，检验该供应商对应的支付方式是否有且仅有一种，且等于线上支付宝
            ——4.产品状态=在售中，试卖在售中：根据“待生成备货单”中的产品状态即可
            ——5.sku近7日未被作废过：即备货单中的sku的作废原因为空
            ——6.sku屏蔽状态≠屏蔽申请中：根据“待生成备货单”中的“是否屏蔽申请中”的状态进行判断即可。
            ——7.货源状态=正常：根据“待生成备货单”中的产品状态即可
            ——8.采购数量≥最新起订量
         */

        $sql_query = "SELECT 
            ps.id,
            ps.demand_number,
            ps.sku,
            pd.is_relate_ali,
            pd.is_invalid,
            pd.supplier_code,
            pd.product_status,
            ps.cancel_reason,
            (SELECT COUNT(1) FROM pur_product_scree AS tmp WHERE tmp.sku=ps.sku AND tmp.status IN(10,20,30) LIMIT 1) AS is_scree_count,
            pd.supply_status,
            pd.starting_qty,
            ps.purchase_amount * ( pd.ali_ratio_out / pd.ali_ratio_own) AS actual_purchase_amount,
            ps.is_create_order,
            ps.suggest_status,
            ps.purchase_type_id,
            ps.warehouse_code,
            ps.is_include_tax
            
            FROM pur_purchase_suggest AS ps
            LEFT JOIN pur_product AS pd ON pd.sku=ps.sku
            WHERE ps.id IN($suggest_ids_str) ";

        $suggest_list = $this->purchase_db->query($sql_query)->result_array();

        $success_list        = [];
        $error_list          = [];
        $supplier_cache_list = [];
        foreach($suggest_list as $suggest_value){
            $suggest_id     = $suggest_value['id'];
            $demand_number  = $suggest_value['demand_number'];
            if(empty($suggest_value['purchase_type_id'])){
                $error_list[$suggest_id] = $demand_number.'，业务线缺失';
                continue;
            }
            if(empty($suggest_value['warehouse_code'])){
                $error_list[$suggest_id] = $demand_number.'，仓库缺失';
                continue;
            }
            if($suggest_value['is_create_order']  == 1){
                $error_list[$suggest_id] = $demand_number.'，已经生成采购单';
                continue;
            }
            if($suggest_value['suggest_status'] != SUGGEST_STATUS_NOT_FINISH){
                $error_list[$suggest_id] = $demand_number.'，备货单状态≠未完结';
                continue;
            }
            if(empty($suggest_value['is_relate_ali'])){
                $error_list[$suggest_id] = $demand_number.'，商品未关联1688';
                continue;
            }
            if($suggest_value['is_invalid'] == 1){
                $error_list[$suggest_id] = $demand_number.'，链接已失效';
                continue;
            }
            if($suggest_value['is_scree_count'] >= 1){
                $error_list[$suggest_id] = $demand_number.'，SKU屏蔽申请中';
                continue;
            }
            if(empty($suggest_value['product_status']) or !in_array($suggest_value['product_status'],[PRODUCT_STATUS_IN_SALE,PRODUCT_STATUS_TRY_SELL_ON_SALE])){
                $error_list[$suggest_id] = $demand_number.'，产品状态≠在售中/试卖在售中';
                continue;
            }
            if(!empty($suggest_value['cancel_reason'])){
                $error_list[$suggest_id] = $demand_number.'，sku近7日有被作废过';
                continue;
            }
            if(empty($suggest_value['supply_status']) or $suggest_value['supply_status'] != 1){
                $error_list[$suggest_id] = $demand_number.'，货源状态≠正常';
                continue;
            }
            if(empty($suggest_value['actual_purchase_amount']) or $suggest_value['actual_purchase_amount'] < $suggest_value['starting_qty']){
                $error_list[$suggest_id] = $demand_number.'，采购数量必须大于等于最新起订量';
                continue;
            }


            // 验证供应商数据（生成采购单的时候也验证了，这里先验证，提前抛出错误）
            if(empty($suggest_value['supplier_code'])){
                $error_list[$suggest_id] = $demand_number.'，默认供应商缺失';
                continue;
            }else{
                // 1.基本信息
                if(isset($supplier_cache_list[$suggest_value['supplier_code']])){
                    if($supplier_cache_list[$suggest_value['supplier_code']] !== true){
                        $error_list[$suggest_id] = $demand_number.$supplier_cache_list[$suggest_value['supplier_code']];
                        continue;
                    }
                }else{
                    // 判断采购员的业务线规则，PFB/FBA/国内一致 都取国内，海外仓的取海外仓
                    if (in_array($suggest_value['purchase_type_id'],[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH])){
                        $suggest_value['purchase_type_id'] = PURCHASE_TYPE_INLAND;
                        $purchase_type_id = PURCHASE_TYPE_INLAND;
                    }elseif(in_array($suggest_value['purchase_type_id'],[PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){ // 海外仓和FBA大货统一使用海外仓信息
                        $purchase_type_id = PURCHASE_TYPE_OVERSEA;
                    }

                    $supplier_info          = $this->supplier_model->get_supplier_info($suggest_value['supplier_code']); // 供应商信息
                    $supplier_buyer_list    = $this->supplier_buyer_model->get_buyer_one($suggest_value['supplier_code'],$suggest_value['purchase_type_id']);
                    if(empty($supplier_info)){
                        $error_msg = '，供应商['.$suggest_value['supplier_code'].']不存在';
                        $error_list[$suggest_id] = $demand_number.$error_msg;
                        $supplier_cache_list[$suggest_value['supplier_code']] = $error_msg;
                        continue;
                    }

                    $is_tax = $suggest_value['is_include_tax'];//是否含税

                    $supplier_payment_info = $supplier_info['supplier_payment_info'][$is_tax][$purchase_type_id]??[];

                    //结算方式
                    $supplier_settlement = $supplier_payment_info['supplier_settlement']??'';
                    //支付方式
                    $supplier_payment_method = $supplier_payment_info['payment_method']??'';
                   if($supplier_settlement == ''){
                        $error_msg = '，供应商['.$suggest_value['supplier_code'].']结算方式缺失，请先维护供应商资料';
                        $error_list[$suggest_id] = $demand_number.$error_msg;
                        $supplier_cache_list[$suggest_value['supplier_code']] = $error_msg;
                        continue;
                    }elseif($supplier_payment_method != 1){
                        $error_msg = '，支付方式≠支付宝';
                        $error_list[$suggest_id] = $demand_number.$error_msg;
                        $supplier_cache_list[$suggest_value['supplier_code']] = $error_msg;
                        continue;
                    }elseif(empty($supplier_buyer_list)){
                        $error_msg = '，供应商['.$suggest_value['supplier_code'].']采购员['.getPurchaseType($suggest_value['purchase_type_id']).']错误';
                        $error_list[$suggest_id] = $demand_number.$error_msg;
                        $supplier_cache_list[$suggest_value['supplier_code']] = $error_msg;
                        continue;
                    }else{
                        $supplier_cache_list[$suggest_value['supplier_code']] = true;
                    }
                }
            }

            if(!isset($error_list[$suggest_id])){
                $success_list[$suggest_id] = $demand_number;
            }
        }

        return array('success_list' => $success_list,'error_list' => $error_list);
    }

    /**
     * 根据备货单信息创建采购单（相同SKU的备货单会自动拆分生成多个PO，避免重复）
     * @param $suggest_list
     * @return array
     */
    public function advanced_create_order($suggest_list){
        $success_pur_numbers      = [];// 新生成的采购单号
        $suggest_list_success     = [];// 已经生成采购单成功的备货单
        $suggest_list_error       = [];// 生成失败的备货单
        $error_message_list       = [];

        do{
            $suggest_list_waiting_now          = $suggest_list;// 本次待生成采购单的备货单

            // Start：处理相同SKU（一个业务线+PO+SKU不能重复）
            $suggest_list_waiting_now_same_sku = [];
            foreach($suggest_list_waiting_now as $key => $suggest_value){
                if(in_array($suggest_value['demand_number'],$suggest_list_success)){
                    unset($suggest_list_waiting_now[$key]);// 剔除已经生成采购单的备货单
                    continue;
                }
                $cache_key = $suggest_value['purchase_type_id'].'_'.$suggest_value['sku'];
                // 获取有相同sku的备货单的sku
                $suggest_list_waiting_now_same_sku[$cache_key][$key]['id'] = $suggest_value['id'];
                $suggest_list_waiting_now_same_sku[$cache_key][$key]['purchase_amount'] = $suggest_value['purchase_amount'];
            }
            // 将相同sku的备货单的备货数量按从高到低排序
            foreach ($suggest_list_waiting_now_same_sku as $key => &$value){
                $purchase_amounts = [];
                foreach($value as $kkk => $row){
                    $purchase_amounts[$kkk] = $row['purchase_amount'];
                }
                array_multisort($purchase_amounts, SORT_DESC,$value);// 根据备货单的备货数量降序排序

                foreach ($value as $k => $v){
                    if ($k == 0){ continue; } // 只保留数量最大的第一条数据
                    foreach ($suggest_list_waiting_now as $kk => $suggest){
                        if($suggest['id'] == $v['id']){
                            unset($suggest_list_waiting_now[$kk]);// 剔除备货数量低的
                        }
                    }
                }
            }
            // End：处理相同SKU（一个业务线+PO+SKU不能重复）

            if(empty($suggest_list_waiting_now)){// 没有待生成采购单的数据
                break;
            }

            // 生成采购单（事务的 要么全部成功 要么全部失败）
            $response = $this->purchase_order_model->create_purchase_order($suggest_list_waiting_now);

            if($response['code']){
                $success_pur_numbers  = array_merge($success_pur_numbers,$response['data']);
                $suggest_list_success = array_merge($suggest_list_success,array_keys($response['success_demand_list']));// 数组：备货单号 => 采购单号
            }else{
                $suggest_list_error   = array_merge($suggest_list_error,array_column($suggest_list_waiting_now,'demand_number'));
                $error_message_list[] = $response['msg'];// 生成采购单报错（一般是备货单被占用）
                break;
            }

        }while(1);

        return array('success_pur_numbers' => $success_pur_numbers,'suggest_list_success' => $suggest_list_success,'suggest_list_error' => $suggest_list_error);
    }




}


