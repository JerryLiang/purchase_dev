<?php
/**
 * 生成1688订单和拍单
 * User: 叶凡立
 * Date: 2020/11/03
 */
class Ali_order_advanced_new_model extends Purchase_model
{
    /**
     * Ali_order_advanced_new_model constructor.
     */
    public function __construct(){
        parent::__construct();
        $this->load->model('supplier/Supplier_model');
        $this->load->model('supplier/Supplier_buyer_model');
    }

    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd-H:i:s")."-".($b[0] * 1000);
    }

    /**
     * 验证备货单信息.是否符合生成条件
     * 第一步：【1688一键下单】先进行数据检验：
     * ——1.是否关联1688=是：根据“待生成备货单”中的“是否关联168”8获取即可
     * ——2.采购链接有效：根据“待生成备货单”中的“链接是否有效”即可
     * ——3.支付方式=支付宝：调取供应商管理列表，检验该供应商对应的支付方式是否有且仅有一种，且等于线上支付宝
     * ——4.产品状态=在售中，试卖在售中：根据“待生成备货单”中的产品状态即可
     * ——5.sku近7日未被作废过：即备货单中的sku的作废原因为空
     * ——6.sku屏蔽状态≠屏蔽申请中：根据“待生成备货单”中的“是否屏蔽申请中”的状态进行判断即可。
     * ——7.货源状态=正常：根据“待生成备货单”中的产品状态即可
     * ——8.采购数量≥最新起订量
     */
    public function verify_purchase_suggest($ids=[], $action=0)
    {
        $query = "SELECT 
            ps.id,
            d.id as demand_id,
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
            ps.source_from,
            ps.shipment_type,
            ps.suggest_status,
            ps.buyer_id,
            ps.is_drawback,
            ps.purchase_name,
            ps.purchase_type_id,
            ps.product_line_id,
            ps.destination_warehouse,
            ps.logistics_type,
            ps.warehouse_code,
            ps.is_include_tax
            FROM pur_purchase_suggest AS ps
            LEFT JOIN pur_purchase_demand as d on ps.demand_number=d.suggest_demand
            LEFT JOIN pur_product AS pd ON pd.sku=ps.sku
            WHERE 1=1";

        $suggest_list = [];
        $handle_one = 500;
        if(count($ids) > $handle_one){
            $ids = array_chunk($ids, $handle_one);
            foreach($ids as $val){
                if(count($val) == 0)continue;
                $h_one = $this->purchase_db->query($query." and ps.id IN(".implode(',', $val).");")->result_array();
                if($h_one && count($h_one) > 0)$suggest_list = count($suggest_list) == 0?$h_one:array_merge($suggest_list, $h_one);
            }
        }else{
            $res = $this->purchase_db->query($query." and ps.id IN(".implode(',', $ids).");")->result_array();
            if($res && count($res) > 0)$suggest_list = $res;
        }
        if(!$suggest_list || count($suggest_list) == 0)return [];

        $success_list           = [];
        $error_list             = [];
        $supplier_cache_list    = [];

        $create_purchase = 3; // 生成采购单

        // 如果是一键生成采购单
        if($action == $create_purchase){
            $sku_message_supplier = $sku_message = [];
            $this->load->model('product/product_model');
            $tar_rate_verify_data = array_map(function($data){
                if( isset($data['purchase_type_id']) && !empty($data['purchase_type_id']) && in_array($data['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG]))return $data;
            },$suggest_list);
            if(!empty($tar_rate_verify_data)){
                $verify_skus = array_column( $tar_rate_verify_data,"sku");
                $sku_message = $this->product_model->get_list_by_sku($verify_skus,'sku,maintain_ticketed_point,supplier_code');

                $sku_message = array_map( function($skus){
                    if($skus['maintain_ticketed_point'] == 0)return $skus;
                },$sku_message);
            }
            if(!empty($sku_message))$sku_message_supplier = array_column($sku_message,"supplier_code");
        }

        $this->load->model('purchase/purchase_order_model');
        foreach($suggest_list as $val){
            $id = $val['id'];
            $demand_number  = $val['demand_number'];
            if($val['is_create_order']  == 1){
                $error_list[$demand_number] = '已经生成采购单';
                continue;
            }

            if(empty($val['demand_id']) && !in_array($val['source_from'], [2, 3])){
                $error_list[$demand_number] = '需求单不存在！';
                $this->purchase_db->where(['demand_number' => $demand_number])->update("purchase_suggest", [
                    "suggest_status" => SUGGEST_STATUS_CANCEL,
                    "cancel_reason"  => '生成采购单时，未检测到需求单号！'
                ]);
                continue;
            }
            if(!in_array($val['suggest_status'], [SUGGEST_STATUS_NOT_FINISH, SUGGEST_STATUS_REBORN])){
                $error_list[$demand_number] = $action == 0 ? '备货单状态≠未完结': "备货单号已过期";
                continue;
            }
            if(empty($val['purchase_type_id'])){
                $error_list[$demand_number] = '业务线缺失';
                continue;
            }

            // 如果是一键生成采购单
            if($action == $create_purchase && in_array($val['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){
                if(!empty($sku_message) && isset($sku_message[$val['sku']]) &&( $sku_message[$val['sku']]['maintain_ticketed_point'] == 0)){
                    $error_list[$demand_number] = '开票点数未维护！';
                    continue;
                }
                if(in_array($val['supplier_code'], $sku_message_supplier)){
                    $error_list[$demand_number] = '开票点数未维护！';
                    continue;
                }
            }
            if($action == $create_purchase){
                $success_list[$demand_number] = $val;
                continue;
            }
            if(empty($val['warehouse_code'])){
                $error_list[$demand_number] = '仓库缺失';
                continue;
            }
            //  调试代码注释
            if(empty($val['is_relate_ali'])){
                $error_list[$demand_number] = '商品未关联1688';
                continue;
            }
            if($val['is_invalid'] == 1){
                $error_list[$demand_number] = '链接已失效';
                continue;
            }
            if($val['is_scree_count'] >= 1){
                $error_list[$demand_number] = 'SKU屏蔽申请中';
                continue;
            }
            if(empty($val['product_status']) or !in_array($val['product_status'],[PRODUCT_STATUS_IN_SALE,PRODUCT_STATUS_TRY_SELL_ON_SALE])){
                $error_list[$id] = $demand_number.'，产品状态≠在售中/试卖在售中';
                continue;
            }
            /*
            if(!empty($val['cancel_reason'])){
                $error_list[$demand_number] = 'sku近7日有被作废过';
                continue;
            }
            */
            if(empty($val['supply_status']) or $val['supply_status'] != 1){
                $error_list[$demand_number] = '货源状态≠正常';
                continue;
            }
            if(empty($val['actual_purchase_amount']) or $val['actual_purchase_amount'] < $val['starting_qty']){
                $error_list[$demand_number] = '采购数量必须大于等于最新起订量';
                continue;
            }
            // 验证供应商数据（生成采购单的时候也验证了，这里先验证，提前抛出错误）
            if(empty($val['supplier_code'])){
                $error_list[$demand_number] = '默认供应商缺失';
                continue;
            }else{
                // 1.基本信息
                if(isset($supplier_cache_list[$val['supplier_code']])){
                    if($supplier_cache_list[$val['supplier_code']] !== true){
                        $error_list[$demand_number] = $supplier_cache_list[$val['supplier_code']];
                        continue;
                    }
                }else{
                    // 判断采购员的业务线规则，PFB/FBA/国内一致 都取国内，海外仓的取海外仓
                    $status = [PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH,PURCHASE_TYPE_PFH];
                    if (in_array($val['purchase_type_id'], $status)){
                        $val['purchase_type_id'] = PURCHASE_TYPE_INLAND;
                        $purchase_type_id = PURCHASE_TYPE_INLAND;
                    }elseif(in_array($val['purchase_type_id'],[PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){ // 海外仓和FBA大货统一使用海外仓信息
                        $purchase_type_id = PURCHASE_TYPE_OVERSEA;
                    }

                    $supplier_info = $this->supplier_model->get_supplier_info($val['supplier_code']); // 供应商信息
                    $supplier_buyer_list = $this->supplier_buyer_model->get_buyer_one($val['supplier_code'],$val['purchase_type_id']);
                    if(empty($supplier_info)){
                        $error_msg = '供应商['.$val['supplier_code'].']不存在';
                        $error_list[$demand_number] = $error_msg;
                        $supplier_cache_list[$val['supplier_code']] = $error_msg;
                        continue;
                    }

                    if ($supplier_info['supplier_source'] == 3) {// 备用供应商是否超过下单次数
                        $reject_info = $this->purchase_order_model->temporary_supplier_order_number($val['supplier_code']);
                        if ($reject_info) {
                            $error_msg = '供应商[' . $val['supplier_code'] . '] ' . implode(',', $reject_info);
                            $error_list[$demand_number] = $error_msg;
                            $supplier_cache_list[$val['supplier_code']] = $error_msg;
                            continue;
                        }
                    }

                    $is_tax = $val['is_include_tax'];//是否含税
                    $supplier_payment_info = $supplier_info['supplier_payment_info'][$is_tax][$purchase_type_id]??[];

                    //结算方式
                    $supplier_settlement = $supplier_payment_info['supplier_settlement']??'';
                    //支付方式
                    $supplier_payment_method = $supplier_payment_info['payment_method']??'';
                    if($supplier_settlement == ''){
                        $error_msg = '供应商['.$val['supplier_code'].']结算方式缺失，请先维护供应商资料';
                        $error_list[$demand_number] = $error_msg;
                        $supplier_cache_list[$val['supplier_code']] = $error_msg;
                        continue;
                    }else if($supplier_payment_method != 1){
                        $error_msg = '支付方式≠支付宝';
                        $error_list[$demand_number] = $error_msg;
                        $supplier_cache_list[$val['supplier_code']] = $error_msg;
                        continue;
                    }else if(empty($supplier_buyer_list)){
                        $error_msg = '供应商['.$val['supplier_code'].']采购员['.getPurchaseType($val['purchase_type_id']).']错误';
                        $error_list[$demand_number] = $error_msg;
                        $supplier_cache_list[$val['supplier_code']] = $error_msg;
                        continue;
                    }else{
                        $supplier_cache_list[$val['supplier_code']] = true;
                    }
                }
            }

            $success_list[$demand_number] = $val;
        }
        return ['success' => $success_list,'error' => $error_list];
    }

    /**
     * 分组并且生成采购单
     */
    public function grouping_suggest_and_create($data=[], $userInfo=[])
    {
        $k_one = 30; // 一个采购单允许最多备货单数量
        $create_all = []; // 满足业务线相同，且sku不重复
        $warehouse_list = $this->purchase_db->from('warehouse')->select('warehouse_code,pertain_wms')->get()->result_array();
        $warehouse = [];
        if($warehouse_list && count($warehouse_list) > 0){
            foreach ($warehouse_list as $val){
                if(!empty($val['warehouse_code']) && !empty($val['pertain_wms']))$warehouse[$val['warehouse_code']] = $val['pertain_wms'];
            }
        }
        foreach ($data as $val){
            $p_id = $this->handle_merge_query($val, $warehouse);  // 合单分组条件
            if(!$p_id)continue;
            if(!in_array($p_id, array_keys($create_all))){
                $create_all[$p_id] = [[$val]];
            }else{
                foreach ($create_all as $kca => $vca){
                    if($kca != $p_id)continue;
                    $is_handle = false;
                    $is_break = false;
                    foreach ($vca as $k=>$v){
                        if(count($v) < $k_one && !in_array($val['sku'], array_column($v, 'sku'))){
                            $is_handle = true;
                            $is_break = true;
                            $create_all[$kca][$k][] = $val;
                            break;
                        };
                    }
                    if($is_break)break;

                    // 如果没处理，则需要新建一条
                    if(!$is_handle){
                        $create_all[$kca][] = [$val];
                        break;
                    }
                }
            }
        }
        $create_list = [];
        foreach ($create_all as $key=>$val){
            if(count($val) == 0)continue;
            foreach ($val as $v){
                if(count($v) > 0)$create_list[]= $v;
            }
        }

        $res = [
            "success"   => [],      // 用于向1688下单
            "handle_res"=> [],      // 用于判断生成采购单成功与否
        ];
        foreach ($create_list as $val){
            $suggest_list = $this->purchase_db
                ->from('pur_purchase_suggest')
                ->where_in('id',array_column($val, 'id'))
                ->get()
                ->result_array();
            $response = $this->purchase_order_model->create_purchase_order($suggest_list, $userInfo);
            $is_success = false;
            if($response['code']){
                $is_success = true;
                if(is_array($response['success_demand_list']) && count($response['success_demand_list']) > 0){
                    $res['success'] = array_merge($res['success'], $response['success_demand_list']);
                }
                if(is_array($response['data']) && count($response['data']) > 0){
                    foreach ($response['data'] as $dv){
                        apiRequestLogInsert(
                            [
                                'record_number'    => $dv,
                                'record_type'      => '1688一键下单',
                                'post_content'     => '第二步：自动建单：成功',
                                'response_content' => '',
                                'status'           => '1',
                            ],
                            'api_request_ali_log'
                        );
                    }
                }
            }
            foreach ($val as $v){
                $res['handle_res'][] = [
                    "id"                => $v['id'],
                    "demand_number"     => $v['demand_number'],
                    "is_success"        => $is_success,
                ];
            }
        }
        return $res;
    }

    /**
     * 判断合并条件
     * 初步分配条件，仅按供应商+业务线+是否退税+仓库进行分配
     */
    private function handle_merge_query($data, $warehouse)
    {
        if(!in_array($data['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){
            // 国内、PFB、FBA
            $base = $data['supplier_code'].'_'.$warehouse[$data['warehouse_code']].$data['is_drawback'];
        }else{
            if($data['purchase_type_id'] == PURCHASE_TYPE_OVERSEA && $data['source_from']!=1 && empty($data['destination_warehouse'])){
                return false;
            }
            // 海外
            if($data['source_from']!=1 && empty($data['logistics_type'])){
                return false;
            }
            $base = $data['purchase_type_id']
                . '_' . $data['supplier_code']
                . '_' . $warehouse[$data['warehouse_code']]
                . '_' . $data['is_drawback']
                . '_' . $data['source_from']
                . '_' . $data['shipment_type'];
        }
        return $base;
    }

    /**
     * 回写处理失败结果
     */
    public function callback_create_error($data, $query=[], $action="update")
    {
        try{
            $table = 'handle_create_order_log';
            if($action == 'create'){
                $this->purchase_db->insert($table, $data);
                return $this->getLastInsertID();
            }else{
                if(!$data || !is_array($data) || count($data) == 0)return;
                $this->purchase_db->update($table, $data, $query);
            }
        }catch (Exception $e){}
    }

    /**
     * 单个采购单处理失败提示
     */
    public function callback_create_error_items($data)
    {
        try{
            $table = 'handle_create_order_items';
            $this->purchase_db->replace($table, $data);
        }catch (Exception $e){}
    }

    /**
     * 获取处理结果列表
     */
    public function get_handle_create_list($params)
    {
        $res = ['code' => 0, 'msg' => '获取数据失败'];
        $query = $this->purchase_db->from('handle_create_order_log');
        if(!empty($params['id']))$query->where('id =', $params['id']);
        if(!empty($params['user_id']))$query->where('user_id =', $params['user_id']);
        if(is_numeric($params['handle_status']))$query->where('handle_status = ', $params['handle_status']);
        if(!empty($params['handle_action']))$query->where('handle_action = ', $params['handle_action']);
        if(!empty($params['create_at_start']) && !empty($params['create_at_end'])){
            $query->where("create_at between '{$params['create_at_start']}' and '{$params['create_at_end']}'");
        }
        $sum_query = clone $query;
        $limit = 20;
        $page = 1;
        if($params['page'])$page = (int)$params['page'];
        if($params['limit'])$limit = (int)$params['limit'];

        if (empty($page) || $page < 0){
            $page = 1;
        }
        $offset = ($page - 1) * $limit;
        $query->limit($limit, $offset);
        $data = $query->order_by('id', 'desc')->get()->result_array();
        $sum = $sum_query->count_all_results();
        if($data && count($data) > 0 && $sum > 0) {
            $d = [];
            foreach ($data as $val) {
                switch ($val['handle_status']) {
                    case 0:
                        $val['status'] = "未处理";
                        break;
                    case 1:
                        $val['status'] = "处理中";
                        break;
                    case 2:
                        $val['status'] = "处理完成";
                        break;
                    case 3:
                        $val['status'] = "处理失败";
                        break;
                    default:
                        $val['status'] = "未处理";
                        break;
                }
                $d[] = $val;
            }
            $res['code'] = 1;
            $res['msg'] = [
                "data_list" => $d,
                "page_data" => [
                    "total_all" => $sum,
                    "page" => $page,
                    "limit" => $limit
                ]
            ];
        }else{
            $res['msg'] = '暂无数据';
        }
        return $res;
    }

    /**
     * 获取下拉选项
     */
    public function get_handle_select_data()
    {
        return [
            "user_list" => $this->purchase_db->from('handle_create_order_log')->select("user_id,user_name as username")
                ->group_by('user_id')->get()->result_array(),
            "status_list" => [
                [
                    "handle_status" => 0,
                    "handle_status_str" => "未处理",
                ],
                [
                    "handle_status" => 1,
                    "handle_status_str" => "处理中",
                ],
                [
                    "handle_status" => 2,
                    "handle_status_str" => "处理完成",
                ],
                [
                    "handle_status" => 3,
                    "handle_status_str" => "处理失败",
                ]
            ],
            "handle_action"=>[
                "一键生成采购单" => "一键生成采购单",
                "1688一键下单" => "1688一键下单",
                "1688一键拍单" => "1688一键拍单",
            ]
        ];
    }

    /**
     * 获取处理结果下的采购单列表
     */
    public function get_handle_create_order_list($id, $page, $limit)
    {
        $res = ['code' => 0, 'msg' => '获取数据失败'];
        if(empty($page))$page = 1;
        if(empty($limit))$limit = 20;

        if (empty($page) || $page < 0){
            $page = 1;
        }
        $offset = ($page - 1) * $limit;
        $query = $this->purchase_db->from('handle_create_order_items')->where("handle_id = ", $id);
        $base_query = $query->select('id,demand_number,GROUP_CONCAT("", handle_msg) as msg')->group_by("demand_number");

        $limit_query = clone $base_query;
        $sum = $base_query->count_all_results();
        $data = $limit_query->limit($limit, $offset)->get()->result_array();
        if($data && count($data) > 0 && $sum > 0){
            $res['code'] = 1;
            $res['msg'] = [
                "value"     => $data,
                "total_all" => $sum
                ];
        }else{
            $res['msg'] = '暂无数据';
        }
        return $res;
    }

    /**
     * 获取采购单对应的所有备货单
     */
    public function get_purchase_all_suggest($purchase = false)
    {
        $res = [];
        if(!$purchase)return $res;
        $data = $this->purchase_db->from('purchase_suggest_map')
            ->where("purchase_number = ", $purchase)
            ->get()
            ->result_array();
        return $data && count($data) > 0?array_column($data, "demand_number"): $res;
    }

}


