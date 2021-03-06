<?php
/**
 * 采购需求 重构
 * @author : yefanli
 */
class Purchase_demand_model extends Purchase_model
{
    protected $table_name = 'purchase_suggest';// 数据表名称
    protected $table_name_map = 'purchase_suggest_map';// 数据表名称
    protected $table_product_name = 'product'; // 产品表

    public function __construct(){
        parent::__construct();
        $this->load->helper('common');
    }

    /**
     * 获取待生成列表
     */
    public function get_demanding_list($params=[], $export=false, $is_sum=false, $get_ids=false)
    {
        $def_select = ' ps.*,ps.is_merge as combination_status,pd.supply_status,pd.is_purchasing as tis_purchasing,pd.maintain_ticketed_point,
        pd.ticketed_point,pd.supply_status,pd.state_type,pd.starting_qty,pd.starting_qty_unit,pd.tax_rate,pd.declare_unit,pd.product_status,
        pd.product_thumb_url,ps.purchase_amount as suggest_amount,pd.unsale_reason,ps.is_distribution,de.is_distribution as distribution ';
        if($params['list_type'] == 2){
            $def_select .= ',GROUP_CONCAT("", de.demand_number) as suggest_demand ';
        }else{
            $def_select .= ' ,de.demand_number as suggest_demand,de.purchase_type_id as de_demand_ty_id,de.demand_data as demand_qty,de.demand_status ';
        }
        if($is_sum){
            $def_select = 'count(DISTINCT ps.id) AS total_count,sum(ps.purchase_amount) as purchase_amount_all,
            sum(ps.purchase_total_price) as purchase_unit_price_all,count(distinct ps.sku) as sku_all,count(distinct ps.supplier_code) as supplier';
        }
        if($get_ids)$def_select = ' ps.demand_number ';
        $userid=jurisdiction(); //当前登录用户ID
        $user_groups_types = user_group_check(); //当前登录用户ID的权限所属的组
        $role=get_user_role();//当前登录角色
        $thisTime = date("Y-m-d");
        $data_role = getRole();//数据权限配置
        $res_arr = array_intersect($role, $data_role);

        // 基础查询条件
        $query_sql = $query_join = $scree_sql = $scree_where = "";
        $query_last = " order by ";

        $scree_query = "( SELECT sku,MAX(estimate_time) as estimate_time FROM pur_product_scree AS screet WHERE screet.status=50 
                        AND apply_remark IN(4,10) GROUP BY sku ORDER BY estimate_time DESC ) as screed";
        if(SetAndNotEmpty($params, 'is_scree')){
            $screet_status = [
                PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT,
                PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM,
                PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM
            ];
            $scree_query = "SELECT sku from ( SELECT `sku`, `status`, MAX( estimate_time ) AS estimate_time FROM `pur_product_scree` 
                WHERE  `status` IN (".implode(",", $screet_status).")  GROUP BY `sku` HAVING `estimate_time` > '{$thisTime}') as scree";

            if($params['is_scree'] == 1){
                $query_sql .= " and ps.sku IN ({$scree_query})";
            }
            if($params['is_scree'] == 2){
                $query_sql .= " and ps.sku NOT IN ({$scree_query})";
            }
        }else{
            if(SetAndNotEmpty($params, 'delivery_time_start') && SetAndNotEmpty($params, 'delivery_time_end')){
                $query_join .= " left join {$scree_query} on screed.sku=ps.sku";
            }
        }

        if(!isset($params['buyer_id_flag'])) {
            if(!(!empty($res_arr) || $userid === true))$query_sql .= " and ps.buyer_id in (".implode(",", $userid).")";
        }else if(isset($params['buyer_id_flag']) && isset($params['buyer_id'])  && $params['buyer_id_flag']!=0){
            $query_sql .= " and ps.buyer_id in (".implode(",", $params['buyer_id']).")";
        }

        if(isset($params['user_groups_types'])){
            $user_groups_types = $params['user_groups_types'];
        }
        if(is_array($user_groups_types)){
            $query_sql .= " and ps.purchase_type_id in (".implode(",", $user_groups_types).")";
        }

        // query {where|in|between}
        $query_sql .= SetAndNotEmpty($params, 'payment_method_source') ? " and ps.source in (".($params['payment_method_source'] == 1?1:2).")":"";
        $query_sql .= SetAndNotEmpty($params, 'is_fumigation') ? " and ps.extra_handle ".($params['is_fumigation'] == 1 ? "=": "!=")."1":"";
        $query_sql .= SetAndNotEmpty($params, 'is_thousand') ? " and pd.is_relate_ali=".($params['is_thousand'] == 1?0:1):"";
        $query_sql .= SetAndNotEmpty($params, 'is_oversea_boutique') ?" and ps.is_overseas_boutique={$params['is_oversea_boutique']}": "";
        $query_sql .= SetAndNotEmpty($params, 'delivery_time_start') ?" and screed.estimate_time>='{$params['delivery_time_start']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'delivery_time_end') ?" and screed.estimate_time<='{$params['delivery_time_end']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'group_ids', 'ar') && count($params['group_ids']) > 0 ?" and ps.buyer_id in (".implode(",", $params['groupdatas']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'is_purchasing') ?" and pd.is_purchasing={$params['is_purchasing']}":"";
        $query_sql .= SetAndNotEmpty($params, 'transformation') ?" and ps.sku_state_type".($params['transformation'] == 1?"!=6":"=".$params['transformation']):"";
        $query_sql .= SetAndNotEmpty($params, 'is_overseas_first_order', 'nn') ?" and ps.is_overseas_first_order={$params['is_overseas_first_order']}":"";
        $query_sql .= SetAndNotEmpty($params, 'shipment_type') ?" and ps.shipment_type={$params['shipment_type']}":"";
        $query_sql .= SetAndNotEmpty($params, 'estimate_time_start') && SetAndNotEmpty($params, 'estimate_time_end') ?" and ps.estimate_time between '{$params['estimate_time_start']}' and '{$params['estimate_time_end']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'plan_product_arrive_time_start') ?" and ps.earliest_exhaust_date>='{$params['plan_product_arrive_time_start']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'plan_product_arrive_time_end') ?" and ps.earliest_exhaust_date<='{$params['plan_product_arrive_time_end']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'id') ?" and ps.id in ({$params['id']})":"";
        $query_sql .= SetAndNotEmpty($params, 'is_create_order', 'n') ?" and ps.is_create_order={$params['is_create_order']}":"";
        $query_sql .= SetAndNotEmpty($params, 'is_left_stock', 'n') ?" and ps.left_stock".(intval($params['is_left_stock']) == 1?"<0":">=0"):"";
        $query_sql .= SetAndNotEmpty($params, 'demand_type_id') ?" and de.demand_type_id in (".implode(',', $params['demand_type_id']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'buyer_id') ?" and ps.buyer_id in(".(is_array($params['buyer_id']) ?implode(",", $params['buyer_id']):$params['buyer_id']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'product_line_id') ?" and ps.product_line_id in(".(is_array($params['product_line_id']) ?implode(",", $params['product_line_id']):$params['product_line_id']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'supplier_code') ?" and ps.supplier_code='{$params['supplier_code']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'is_drawback', 'n') ?" and ps.is_drawback=".$params['is_drawback']:"";
        $query_sql .= SetAndNotEmpty($params, 'product_status') ?" and pd.product_status in(".(is_array($params['product_status']) ?implode(",", $params['product_status']):$params['product_status']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'is_ticketed_point') ?" and pd.maintain_ticketed_point=".($params['is_ticketed_point'] == 1?0:1):"";
        $query_sql .= SetAndNotEmpty($params, 'is_new') ?" and ps.is_new=".($params['is_new']==1?1:0):"";
        $query_sql .= SetAndNotEmpty($params, 'purchase_type_id') ?" and ps.purchase_type_id in (".(is_array($params['purchase_type_id']) ?implode(",", $params['purchase_type_id']):$params['purchase_type_id']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'destination_warehouse') ?" and ps.destination_warehouse='{$params['destination_warehouse']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'logistics_type') ?" and ps.logistics_type=binary('".$params['logistics_type']."')":"";
        $query_sql .= SetAndNotEmpty($params, 'warehouse_code') ?" and ps.warehouse_code in ('".(is_array($params['warehouse_code']) ?implode("','", $params['warehouse_code']):$params['warehouse_code'])."')":"";
        $query_sql .= SetAndNotEmpty($params, 'is_expedited') ?" and ps.is_expedited=".$params['is_expedited']:"";
        $query_sql .= SetAndNotEmpty($params, 'create_user_id') ?" and ps.create_user_id=".$params['create_user_id']:"";
        $query_sql .= SetAndNotEmpty($params, 'supply_status') ?" and pd.supply_status in ('".implode("','", $params['supply_status'])."')":"";
        $query_sql .= SetAndNotEmpty($params, 'create_time_start') && SetAndNotEmpty($params, 'create_time_end') ?" and ps.create_time between '{$params['create_time_start']}' and '{$params['create_time_end']}' ":"";
        $query_sql .= SetAndNotEmpty($params, 'is_boutique', 'n') ?" and ps.is_boutique=".$params['is_boutique']:"";
        $query_sql .= SetAndNotEmpty($params, 'state_type') ?" and pd.state_type in ('".(is_array($params['state_type']) ? implode("','", $params['state_type']):$params['state_type'])."')":"";
        $query_sql .= SetAndNotEmpty($params, 'entities_lock_status') ?" and ps.lock_type=".($params['entities_lock_status']==2?LOCK_SUGGEST_ENTITIES:0):"";
        $query_sql .= SetAndNotEmpty($params, 'connect_order_cancel') ?" and ps.connect_order_cancel='{$params['connect_order_cancel']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'lock_type') ?" and ps.audit_status=".SUGGEST_AUDITED_PASS:$params['lock_type'];
        $query_sql .= SetAndNotEmpty($params, 'combination_status', 'n') ?" and ps.is_merge=".$params['combination_status']:'';
        $query_sql .= SetAndNotEmpty($params, 'demand_type') ?" and de.purchase_type_id in (".implode(",", $params['demand_type']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'demand_status') ?" and de.demand_status in (".implode(",", $params['demand_status']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'temp_container', 'n') ?" and ps.temp_container='".$params['temp_container']."'": "";
        $query_sql .= SetAndNotEmpty($params, 'cancel_reason') ?" and ps.cancel_reason_category in ('".(is_array($params['cancel_reason']) ? implode("','", $params['cancel_reason']) : $params['cancel_reason'])."')":"";
        $query_sql .= SetAndNotEmpty($params, 'is_distribution', 'n') ?" and ps.is_distribution=".$params['is_distribution']:"";

        if(SetAndNotEmpty($params, 'demand_number')){
            $demand_arr = array_filter(explode(' ',trim($params['demand_number'])));
            $query_sql .= " and ps.demand_number in ('".implode("','", $demand_arr)."')";
        }

        if(SetAndNotEmpty($params, 'suggest_demand')){
            $demand = array_filter(explode(' ',trim($params['suggest_demand'])));
            $query_sql .= " and de.demand_number in ('".implode("','", $demand)."')";
        }
        if(SetAndNotEmpty($params, 'pertain_wms')){
            $pertain_wms = is_array($params['pertain_wms']) ?implode("','",$params['pertain_wms']):implode("','",explode(',',$params['pertain_wms']));
            $query_sql .= " and ps.warehouse_code IN(SELECT warehouse_code FROM pur_warehouse WHERE pertain_wms IN('{$pertain_wms}'))";
        }
        if(SetAndNotEmpty($params, 'new_lack_qty_start') || SetAndNotEmpty($params, 'new_lack_qty_end')){
            $query_join .= " left join pur_think_lack_info as tli on tli.sku = ps.sku ";
            $query_sql .= SetAndNotEmpty($params, 'new_lack_qty_start') ?" and tli.lack_sum >=".$params['new_lack_qty_start']:"";
            $query_sql .= SetAndNotEmpty($params, 'new_lack_qty_end') ?" and tli.lack_sum <=".$params['new_lack_qty_end']:"";
        }

        if (SetAndNotEmpty($params, 'sku')) {
            $sku = query_string_to_array($params['sku']);
            $query_sql .= " and ps.sku ".(count($sku) == 1?" like '%{$params['sku']}%'":" in ('".implode("','", $sku)."')");
        }
        if($params['list_type'] == 2 && !SetAndNotEmpty($params, 'suggest_status'))$query_sql .= " and ps.suggest_status in (".SUGGEST_STATUS_NOT_FINISH.",".SUGGEST_STATUS_REBORN.")";
        if(SetAndNotEmpty($params, 'suggest_status')){
            $suggest_status = !is_array($params['suggest_status'])?[$params['suggest_status']]:$params['suggest_status'];
            $query_sql .= " and ps.suggest_status in (".implode(',', $suggest_status).")";
        }

        // order by
        $query_last_arr = ['ps.id desc'];
        if(SetAndNotEmpty($params, 'left_stock_order') && in_array($params['left_stock_order'],['asc','desc']))$query_last_arr[]= " ps.left_stock {$params['left_stock_rder']}";
        if(SetAndNotEmpty($params, 'supplier_order') && in_array($params['supplier_order'],['asc','desc']))$query_last_arr[]= " CONVERT(pd.supplier_name USING GBK) {$params['supplier_order']}";
        if(SetAndNotEmpty($params, 'order_by') && SetAndNotEmpty($params, 'order')){
            $order_by = [
                1 => " ps.supplier_name ",
                2 => " ps.buyer_id ",
                3 => " ps.product_name ",
                4 => " ps.is_drawback ",
                5 => " ps.plan_product_arrive_time ",
                6 => " ps.id ",
                7 => " ps.product_line_id ",
                8 => " ps.two_product_line_id ",
                9 => " ps.purchase_total_price ",
                10 => " ps.audit_time ",
            ];
            if(in_array($params['order_by'], array_keys($order_by)) && in_array($params['order'],['desc','asc']))$query_last_arr[] = $order_by[$params['order_by']].$params['order'];
        }
        if(!empty($query_last_arr))$query_last .= implode(",", $query_last_arr);

        $is_demand = $params['list_type'] == 2 && !$is_sum?' group by ps.demand_number ': '';

        $base_sql = "select {$def_select} from pur_purchase_suggest as ps 
                    inner join pur_product as pd on ps.sku=pd.sku 
                    left join pur_purchase_demand as de on ps.demand_number=de.suggest_demand 
                    {$query_join} {$scree_sql} where 1=1 and ps.audit_status = 1 {$query_sql} {$is_demand} {$query_last}";

        // 不是导出，则分页
        if(empty($params["offset"]))$params["offset"] = 0;
        if(empty($params["limit"]))$params["limit"] = 20;
        if(!$export && !$is_sum)$base_sql .= " limit {$params["offset"]}, {$params["limit"]}";

        $res = [
            'data_list'   => [],
            'aggregate_data'  => [],
            'page_data' => [
                'total'     => 0,
                'offset'    => (int)$params['page'],
                'limit'     => $params['limit']
            ],
        ];
        // 返回统计数据
        if($is_sum){
            $keys = $this->purchase_order_sum_model->get_key($params,"suggest");
            $data = $this->purchase_db->query($base_sql)->row_array();
            $query_sql = $this->purchase_db->get_compiled_select();// 获取查询的 SQL
            $this->session->set_tempdata('purchase_suggest-get_list', $query_sql,50);//设置缓存和有效时间
            $res['page_data']['total'] = $data['total_count'];
            $res['aggregate_data'] = $data;
            $res['sql_sum'] = $this->purchase_db->last_query();
            $this->purchase_order_sum_model->set_sum_cache($keys,$res);
            return $res;
        }

        // 列表数据
        $data = $this->purchase_db->query($base_sql)->result_array();
        $res['sql_list'] = $this->purchase_db->last_query();

        if($data && !empty($data)){
            if($get_ids){
                $res_ids = [];
                foreach ($data as $val){
                    $res_ids[] = $val['demand_number'];
                }
                return ["ids" => $res_ids];
            }

            $res_sql = $this->purchase_db->get_compiled_select();// 获取查询的 SQL
            $this->rediss->setData(md5(getActiveUserId().'_purchase_suggest_get_list'),base64_encode($res_sql),1800);// 缓存查询SQL，便于执行其他操作，如1688批量刷新
            $this->session->set_tempdata('purchase_suggest-get_list', $res_sql,500);//设置缓存和有效时间

            // sku 屏蔽
            $skusdata = array_merge(array_unique(array_column($data,'sku')), array());
            $screeSkuData = $this->purchase_db->from("product_scree")->where("status",50)->where_in("apply_remark",[4,10])
                ->where_in("sku",$skusdata)
                ->select("sku,MAX(estimate_time) as estimate_time")
                ->group_by("sku")
                ->order_by("estimate_time DESC")
                ->get()
                ->result_array();

            // 获取采购单取消、报损数量
            $pur_data = [];
            if($params['list_type'] != 2){
                $demand_number = array_unique(array_column($data,'demand_number'));
                $pur_items = $this->purchase_db->from("purchase_order_items")
                    ->select("demand_number,confirm_amount")
                    ->where_in("demand_number", $demand_number)
                    ->get()
                    ->result_array();

                if($pur_items && count($pur_items) > 0){
                    foreach ($pur_items as $val){
                        $pur_data[$val['demand_number']] = $val['confirm_amount'];
                    }
                }
            }

            // 获取需求类型
            $demand_type_data = $this->purchase_db->from("demand_type")->get()->result_array();
            $demand_type = [];
            foreach ($demand_type_data as $val){
                $demand_type[$val['demand_type_id']] = $val['demand_type_name'];
            }

            $screeSkuDatas = !empty($screeSkuData) ?array_column($screeSkuData,NULL,"sku"):[];
            $data_temp = [];
            $unbuy_temp = [];
            $confirm_temp = [];
            foreach ($data as $key=>$val){
                $val['delivery_time'] = isset($screeSkuDatas[$val['sku']]) ?$screeSkuDatas[$val['sku']]['estimate_time']:'';
                if($params['list_type'] != 2){
                    $pur_v = $val['demand_number'];
                    $confirm_amount = isset($pur_data[$pur_v]) ? $pur_data[$pur_v]: 0;
                    $val['unbuy_amount'] = 0;
                    $cancel_qty = $val['suggest_amount'] - $confirm_amount;
                    if(!isset($confirm_temp[$pur_v]))$confirm_temp[$pur_v] = $confirm_amount;
                    if($confirm_amount != 0 && $cancel_qty > 0 && !isset($unbuy_temp[$pur_v]))$unbuy_temp[$pur_v] = $cancel_qty;
                    if(isset($unbuy_temp[$pur_v]) && $unbuy_temp[$pur_v] > 0){
                        if($val['demand_data'] > $unbuy_temp[$pur_v]){
                            $val['unbuy_amount'] = $unbuy_temp[$pur_v];
                            $unbuy_temp[$pur_v] = 0;
                        }else{
                            $val['unbuy_amount'] = $val['demand_data'];
                            $unbuy_temp[$pur_v] = $unbuy_temp[$pur_v] - $val['demand_data'];
                        }
                    }
                    $val['purchase_amount'] = $val['demand_data'] - $val['unbuy_amount'];
                }
                $val['demand_name_id_cn'] = isset($demand_type[$val['demand_type_id']]) ? $demand_type[$val['demand_type_id']] : "";
                if($export)$val['demand_status']    = SetAndNotEmpty($val, 'demand_status', 'n') ? getDemandStatus($val['demand_status']): '未知状态';
                $data_temp[] = $val;
            }
            $res['data_list'] = $data_temp;
        }
        return $res;
    }

    /**
     * 获取全部需求单列表
     */
    public function get_all_demand($params=[], $export=false, $is_sum=false, $get_ids=false)
    {
        $def_select = 'de.*, de.demand_number as suggest_demand,de.purchase_type_id as de_demand_ty_id,de.demand_data as demand_qty,
        ps.purchase_amount as suggest_amount,ps.is_merge as combination_status,pd.supply_status,pd.is_purchasing as tis_purchasing,
        pd.maintain_ticketed_point,pd.ticketed_point,pd.supply_status,pd.state_type,pd.starting_qty,pd.starting_qty_unit,
        pd.tax_rate,pd.declare_unit,pd.product_status,pd.product_thumb_url,ps.suggest_status,ps.cancel_reason as ps_cancel_reason,
        ps.demand_number,ps.is_distribution as distribution,de.is_distribution';

        if($is_sum){
            $def_select = 'count(DISTINCT de.id) AS total_count,sum(de.demand_data) as purchase_amount_all,
            sum(de.purchase_total_price) as purchase_unit_price_all,count(distinct de.sku) as sku_all,count(distinct de.supplier_code) as supplier';
        }
        $userid=jurisdiction(); //当前登录用户ID
        $user_groups_types = user_group_check(); //当前登录用户ID的权限所属的组
        $role=get_user_role();//当前登录角色
        $thisTime = date("Y-m-d");
        $data_role = getRole();//数据权限配置
        $res_arr = array_intersect($role, $data_role);

        // 基础查询条件
        $query_sql = $query_join = $scree_sql = $scree_where = "";
        $query_last = " order by ";

        $scree_query = "( SELECT sku,MAX(estimate_time) as estimate_time FROM pur_product_scree AS screet WHERE screet.status=50 
                        AND apply_remark IN(4,10) GROUP BY sku ORDER BY estimate_time DESC ) as screed";
        if(SetAndNotEmpty($params, 'is_scree', 'n')){
            $screet_status = [
                PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT,
                PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM,
                PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM
            ];
            $scree_query = "SELECT sku from ( SELECT `sku`, `status`, MAX( estimate_time ) AS estimate_time FROM `pur_product_scree` 
                WHERE  `status` IN (".implode(",", $screet_status).")  GROUP BY `sku` HAVING `estimate_time` > '{$thisTime}') as scree";

            if($params['is_scree'] == 1){
                $query_sql .= " and ps.sku IN ({$scree_query})";
            }
            if($params['is_scree'] == 2){
                $query_sql .= " and ps.sku NOT IN ({$scree_query})";
            }
        }else{
            if(SetAndNotEmpty($params, 'delivery_time_start') && SetAndNotEmpty($params, 'delivery_time_end')){
                $query_join .= " left join {$scree_query} on screed.sku=ps.sku";
            }
        }

        if(!isset($params['buyer_id_flag'])) {
            if(!(!empty($res_arr) || $userid === true))$query_sql .= " and de.buyer_id in (".implode(",", $userid).")";
        }else if(isset($params['buyer_id_flag']) && isset($params['buyer_id'])  && $params['buyer_id_flag']!=0){
            $query_sql .= " and de.buyer_id in (".implode(",", $params['buyer_id']).")";
        }

        if(isset($params['user_groups_types'])){
            $user_groups_types = $params['user_groups_types'];
        }
        if(is_array($user_groups_types)){
            $query_sql .= " and de.purchase_type_id in (".implode(",", $user_groups_types).")";
        }

        // query {where|in|between}
        $query_sql .= SetAndNotEmpty($params, 'payment_method_source') ? " and de.source in (".($params['payment_method_source'] == 1?1:2).")":"";
        $query_sql .= SetAndNotEmpty($params, 'is_fumigation') ? " and de.extra_handle ".($params['is_fumigation'] == 1 ? "=": "!=")."1":"";
        $query_sql .= SetAndNotEmpty($params, 'is_thousand') ? " and pd.is_relate_ali=".($params['is_thousand'] == 1?0:1):"";
        $query_sql .= SetAndNotEmpty($params, 'is_oversea_boutique') ?" and ps.is_overseas_boutique={$params['is_oversea_boutique']}": "";
        $query_sql .= SetAndNotEmpty($params, 'delivery_time_start') ?" and screed.estimate_time>='{$params['delivery_time_start']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'delivery_time_end') ?" and screed.estimate_time<='{$params['delivery_time_end']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'group_ids') ?" and de.buyer_id in (".implode(",", $params['groupdatas']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'is_purchasing') ?" and pd.is_purchasing={$params['is_purchasing']}":"";
        $query_sql .= SetAndNotEmpty($params, 'transformation') ?" and ps.sku_state_type".($params['transformation'] == 1?"!=6":"=".$params['transformation']):"";
        $query_sql .= SetAndNotEmpty($params, 'is_overseas_first_order', 'nn') ?" and de.is_overseas_first_order={$params['is_overseas_first_order']}":"";
        $query_sql .= SetAndNotEmpty($params, 'shipment_type') ?" and de.shipment_type={$params['shipment_type']}":"";
        $query_sql .= SetAndNotEmpty($params, 'estimate_time_start') && SetAndNotEmpty($params, 'estimate_time_end') ?" and de.estimate_time between '{$params['estimate_time_start']}' and '{$params['estimate_time_end']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'plan_product_arrive_time_start') ?" and de.earliest_exhaust_date>='{$params['plan_product_arrive_time_start']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'plan_product_arrive_time_end') ?" and de.earliest_exhaust_date<='{$params['plan_product_arrive_time_end']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'id') ?" and de.id in ({$params['id']})":"";
        $query_sql .= SetAndNotEmpty($params, 'is_create_order', 'n') ?" and ps.is_create_order={$params['is_create_order']}":"";
        $query_sql .= SetAndNotEmpty($params, 'is_left_stock', 'n') ?" and de.left_stock".(intval($params['is_left_stock']) == 1?"<0":">=0"):"";
        $query_sql .= SetAndNotEmpty($params, 'demand_type_id') ?" and de.demand_name_id in (".implode(',', $params['demand_type_id']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'buyer_id') ?" and de.buyer_id in(".(is_array($params['buyer_id']) ?implode(",", $params['buyer_id']):$params['buyer_id']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'product_line_id') ?" and de.product_line_id in(".(is_array($params['product_line_id']) ?implode(",", $params['product_line_id']):$params['product_line_id']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'supplier_code') ?" and de.supplier_code='{$params['supplier_code']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'is_drawback', 'n') ?" and de.is_drawback=".$params['is_drawback']:"";
        $query_sql .= SetAndNotEmpty($params, 'product_status') ?" and pd.product_status in(".(is_array($params['product_status']) ?implode(",", $params['product_status']):$params['product_status']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'is_ticketed_point') ?" and pd.maintain_ticketed_point=".($params['is_ticketed_point'] == 1?0:1):"";
        $query_sql .= SetAndNotEmpty($params, 'is_new') ?" and de.is_new=".($params['is_new']==1?1:0):"";
        $query_sql .= SetAndNotEmpty($params, 'purchase_type_id') ?" and de.purchase_type_id in (".(is_array($params['purchase_type_id']) ?implode(",", $params['purchase_type_id']):$params['purchase_type_id']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'destination_warehouse') ?" and de.destination_warehouse='{$params['destination_warehouse']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'logistics_type') ?" and de.logistics_type=binary('".$params['logistics_type']."')":"";
        $query_sql .= SetAndNotEmpty($params, 'warehouse_code') ?" and de.warehouse_code in ('".(is_array($params['warehouse_code']) ?implode("','", $params['warehouse_code']):$params['warehouse_code'])."')":"";
        $query_sql .= SetAndNotEmpty($params, 'is_expedited') ?" and de.is_expedited=".$params['is_expedited']:"";
        $query_sql .= SetAndNotEmpty($params, 'create_user_id') ?" and de.create_user_id=".$params['create_user_id']:"";
        $query_sql .= SetAndNotEmpty($params, 'supply_status') ?" and pd.supply_status in (".implode(',', $params['supply_status']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'create_time_start') && SetAndNotEmpty($params, 'create_time_end') ?" and de.create_time between '{$params['create_time_start']}' and '{$params['create_time_end']}' ":"";
        $query_sql .= SetAndNotEmpty($params, 'is_boutique', 'n') ?" and de.is_boutique=".$params['is_boutique']:"";
        $query_sql .= SetAndNotEmpty($params, 'state_type') ?" and pd.state_type in ('".(is_array($params['state_type']) ? implode("','", $params['state_type']):$params['state_type'])."')":"";
        $query_sql .= SetAndNotEmpty($params, 'entities_lock_status') ?" and ps.lock_type=".($params['entities_lock_status']==2?LOCK_SUGGEST_ENTITIES:0):"";
        $query_sql .= SetAndNotEmpty($params, 'cancel_reason') ?" and de.cancel_reason_category in ('".implode("','", $params['cancel_reason'])."')":"";
        $query_sql .= SetAndNotEmpty($params, 'connect_order_cancel') ?" and ps.connect_order_cancel='{$params['connect_order_cancel']}'":"";
        $query_sql .= SetAndNotEmpty($params, 'lock_type') ?" and ps.audit_status=".SUGGEST_AUDITED_PASS:$params['lock_type'];
        $query_sql .= SetAndNotEmpty($params, 'combination_status', 'n') ?" and ps.is_merge=".$params['combination_status']:'';
        $query_sql .= SetAndNotEmpty($params, 'demand_type') ?" and de.purchase_type_id in (".implode(",", $params['demand_type']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'demand_status') ?" and de.demand_status in (".implode(",", $params['demand_status']).")":"";
        $query_sql .= SetAndNotEmpty($params, 'temp_container', 'n') ?" and de.temp_container='".$params['temp_container']."'":"";
        $query_sql .= SetAndNotEmpty($params, 'is_distribution', 'n') ?" and de.is_distribution=".$params['is_distribution']:"";
//        $query_sql .= SetAndNotEmpty($params, 'purchase_order_status') ?" and o.purchase_order_status in (".implode(',', $params['purchase_order_status']).")":"";

        if(SetAndNotEmpty($params, 'demand_number')){
            $demand_arr = array_filter(explode(' ',trim($params['demand_number'])));
            $query_sql .= " and ps.demand_number in ('".implode("','", $demand_arr)."')";
        }

        if(SetAndNotEmpty($params, 'suggest_demand')){
            $demand = array_filter(explode(' ',trim($params['suggest_demand'])));
            $query_sql .= " and de.demand_number in ('".implode("','", $demand)."')";
        }
        if(SetAndNotEmpty($params, 'pertain_wms')){
            $pertain_wms = is_array($params['pertain_wms']) ?implode("','",$params['pertain_wms']):implode("','",explode(',',$params['pertain_wms']));
            $query_sql .= " and de.warehouse_code IN(SELECT warehouse_code FROM pur_warehouse WHERE pertain_wms IN('{$pertain_wms}'))";
        }
        if(SetAndNotEmpty($params, 'new_lack_qty_start') || SetAndNotEmpty($params, 'new_lack_qty_end')){
            $query_join .= " left join pur_think_lack_info as tli on tli.sku = de.sku ";
            $query_sql .= SetAndNotEmpty($params, 'new_lack_qty_start') ?" and tli.lack_sum >=".$params['new_lack_qty_start']:"";
            $query_sql .= SetAndNotEmpty($params, 'new_lack_qty_end') ?" and tli.lack_sum <=".$params['new_lack_qty_end']:"";
        }
        if(SetAndNotEmpty($params, 'cancel_time_start') || SetAndNotEmpty($params, 'cancel_time_end')){
            $query_sql .= SetAndNotEmpty($params, 'cancel_time_start') ?" and de.demand_cancel_time >='".$params['cancel_time_start']."'":"";
            $query_sql .= SetAndNotEmpty($params, 'cancel_time_end') ?" and de.demand_cancel_time <='".$params['cancel_time_end']."'":"";
            $query_sql .= " and de.demand_status= 7 ";
        }

        if (SetAndNotEmpty($params, 'sku')) {
            $sku = query_string_to_array($params['sku']);
            $query_sql .= " and de.sku ".(count($sku) == 1?" like '%{$params['sku']}%'":" in ('".implode("','", $sku)."')");
        }
        if(SetAndNotEmpty($params, 'suggest_status')){
            $suggest_status = !is_array($params['suggest_status'])?[$params['suggest_status']]:$params['suggest_status'];
            $query_sql .= " and ps.suggest_status in (".implode(',', $suggest_status).")";
        }

        // order by
        $query_last_arr = ['de.id desc'];
        if(SetAndNotEmpty($params, 'left_stock_order') && in_array($params['left_stock_order'],['asc','desc']))$query_last_arr[]= " de.left_stock {$params['left_stock_rder']}";
        if(SetAndNotEmpty($params, 'supplier_order') && in_array($params['supplier_order'],['asc','desc']))$query_last_arr[]= " CONVERT(pd.supplier_name USING GBK) {$params['supplier_order']}";
        if(SetAndNotEmpty($params, 'order_by') && SetAndNotEmpty($params, 'order')){
            $order_by = [
                1 => " de.supplier_name ",
                2 => " de.buyer_id ",
                3 => " de.product_name ",
                4 => " de.is_drawback ",
                5 => " de.plan_product_arrive_time ",
                6 => " de.id ",
                7 => " de.product_line_id ",
                8 => " de.two_product_line_id ",
                9 => " de.purchase_total_price ",
            ];
            if(in_array($params['order_by'], array_keys($order_by)) && in_array($params['order'],['desc','asc']))$query_last_arr[] = $order_by[$params['order_by']].$params['order'];
        }
        if(!empty($query_last_arr))$query_last .= implode(",", $query_last_arr);

        $is_demand = !$is_sum?' group by de.demand_number ': '';

        $child = '';
        if(SetAndNotEmpty($params, 'purchase_order_status')){
            $child = " and de.suggest_demand in (
            select it.demand_number from pur_purchase_order_items as it 
            left join pur_purchase_order as o on it.purchase_number=o.purchase_number 
            where o.purchase_order_status in (".implode(',', $params['purchase_order_status']).")) ";
        }

        $base_sql = "select {$def_select} from pur_purchase_demand as de 
                    inner join pur_product as pd on de.sku=pd.sku 
                    left join pur_purchase_suggest as ps on ps.demand_number=de.suggest_demand 
                    {$query_join} {$scree_sql} where 1=1 {$query_sql} {$child} {$is_demand} {$query_last}";
        // 不是导出，则分页
        if(!$export && !$is_sum)$base_sql .= " limit {$params["offset"]}, {$params["limit"]}";

        $res = [
            'data_list'   => [],
            'aggregate_data'  => [],
            'page_data' => [
                'total'     => 0,
                'offset'    => (int)$params['page'],
                'limit'     => $params['limit']
            ],
        ];
        // 返回统计数据
        if($is_sum){
            $keys = $this->purchase_order_sum_model->get_key($params,"suggest");
            $data = $this->purchase_db->query($base_sql)->row_array();
            $query_sql = $this->purchase_db->get_compiled_select();// 获取查询的 SQL
            $this->session->set_tempdata('purchase_suggest-get_list', $query_sql,50);//设置缓存和有效时间
            $res['page_data']['total'] = $data['total_count'];
            $res['aggregate_data'] = $data;
            $res['sql_sum'] = $this->purchase_db->last_query();
            $this->purchase_order_sum_model->set_sum_cache($keys,$res);
            return $res;
        }

        // 列表数据
        $data = $this->purchase_db->query($base_sql)->result_array();
        $res['sql_list'] = $this->purchase_db->last_query();

        if($data && !empty($data)){
            if($get_ids){
                $res_ids = [];
                foreach ($data as $val){
                    $res_ids[] = $val['demand_number'];
                }
                return ["ids" => $res_ids];
            }

            $res_sql = $this->purchase_db->get_compiled_select();// 获取查询的 SQL
            $this->rediss->setData(md5(getActiveUserId().'_purchase_suggest_get_list'),base64_encode($res_sql),1800);// 缓存查询SQL，便于执行其他操作，如1688批量刷新
            $this->session->set_tempdata('purchase_suggest-get_list', $res_sql,500);//设置缓存和有效时间

            // sku 屏蔽
            $skusdata = array_unique(array_column($data,'sku'));
            $screeSkuData = $this->purchase_db->from("product_scree")->where("status",50)->where_in("apply_remark",[4,10])
                ->where_in("sku",$skusdata)
                ->select("sku,MAX(estimate_time) as estimate_time")
                ->group_by("sku")
                ->order_by("estimate_time DESC")
                ->get()
                ->result_array();

            // 获取采购单取消、报损数量
            $pur_data = [];
            if($params['list_type'] != 2){
                $demand_number = array_unique(array_column($data,'demand_number'));
                $pur_items = $this->purchase_db->from("purchase_order_items")
                    ->select("demand_number,confirm_amount")
                    ->where_in("demand_number", $demand_number)
                    ->get()
                    ->result_array();

                if($pur_items && count($pur_items) > 0){
                    foreach ($pur_items as $val){
                        $pur_data[$val['demand_number']] = $val['confirm_amount'];
                    }
                }
            }

            // 获取需求类型
            $demand_type_data = $this->purchase_db->from("demand_type")->get()->result_array();
            $demand_type = [];
            foreach ($demand_type_data as $val){
                $demand_type[$val['demand_type_id']] = $val['demand_type_name'];
            }

            $screeSkuDatas = !empty($screeSkuData) ?array_column($screeSkuData,NULL,"sku"):[];
            $data_temp = [];
            $unbuy_temp = [];
            $confirm_temp = [];
            foreach ($data as $key=>$val){
                $val['delivery_time'] = isset($screeSkuDatas[$val['sku']]) ?$screeSkuDatas[$val['sku']]['estimate_time']:'';
                /*if($params['list_type'] != 2){
                    $pur_v = $val['demand_number'];
                    $confirm_amount = isset($pur_data[$pur_v]) ? $pur_data[$pur_v]: 0;
                    $val['unbuy_amount'] = 0;
                    $cancel_qty = $val['suggest_amount'] - $confirm_amount;
                    if(!isset($confirm_temp[$pur_v]))$confirm_temp[$pur_v] = $confirm_amount;
                    if($confirm_amount != 0 && $cancel_qty > 0 && !isset($unbuy_temp[$pur_v]))$unbuy_temp[$pur_v] = $cancel_qty;
                    if(isset($unbuy_temp[$pur_v]) && $unbuy_temp[$pur_v] > 0){
                        if($val['demand_data'] > $unbuy_temp[$pur_v]){
                            $val['unbuy_amount'] = $unbuy_temp[$pur_v];
                            $unbuy_temp[$pur_v] = 0;
                        }else{
                            $val['unbuy_amount'] = $val['demand_data'];
                            $unbuy_temp[$pur_v] = $unbuy_temp[$pur_v] - $val['demand_data'];
                        }
                    }
                    $val['unsale_reason'] = 0;
                    $val['purchase_amount'] = $val['demand_data'] - $val['unbuy_amount'];
                }*/

                $val['unbuy_amount'] = $val['demand_data'] - $val['had_purchase_amount'];
                $val['purchase_amount'] = $val['had_purchase_amount'];
                
                $cr1 = [];
                if($val['cancel_reason'] != '')$cr1[] = "需求单：".$val['cancel_reason'];
                if($val['ps_cancel_reason'] != '')$cr1[] = "备货单：".$val['cancel_reason'];
                if(count($cr1) > 0)$val['cancel_reason'] = implode("。", $cr1);
                $val['demand_name_id_cn'] = isset($demand_type[$val['demand_name_id']]) ? $demand_type[$val['demand_name_id']] : "";
                if($export)$val['demand_status']    = SetAndNotEmpty($val, 'demand_status', 'n') ? getDemandStatus($val['demand_status']): '未知状态';
                $data_temp[] = $val;
            }
            $res['data_list'] = $data_temp;
        }
        return $res;
    }

    /**
     * 作废采购单到需求单
     */
    public function cancel_suggest_to_demand($ids=[], $type=1, $reasons=1)
    {
        $res = ['code' => 0, "msg" => '默认作废失败！'];
        if(empty($ids)){
            $res['msg'] = '没有要作废的数据.！';
            return $res;
        }
        try{
            $this->purchase_db->trans_begin();

            // 如果已生成采购单，则不允许作废
            $order = $this->purchase_db->select('demand_number')->from('pur_purchase_order_items')->where_in('demand_number', $ids)->get()->result_array();
            $order_list = [];
            if($order && count($order) > 0){
                foreach ($order as $val){
                    $order_list[] = $val['demand_number'];
                }
            }
            if(!empty($order_list))throw new Exception(implode(',', $order_list).'已生成采购单。');

            // 锁单中的备货单不允许作废
            $lock = $this->purchase_db->select('demand_number')
                ->from('pur_purchase_suggest')
                ->where(['lock_type'=> LOCK_SUGGEST_ENTITIES])
                ->where_in('demand_number', $ids)
                ->get()->result_array();
            $lock_list = [];
            if($lock && count($lock) > 0){
                foreach ($lock as $val){
                    $lock_list[] = $val['demand_number'];
                }
            }
            if(!empty($lock_list))throw new Exception('备货单 '.implode(',', $lock_list).' 处于锁单状态,请先解锁后再操作。');

            $suggest = $demand = $push_plan = [];

            $push = $this->purchase_db->from("pur_purchase_demand")->select('demand_number,purchase_type_id,sku,id')->where_in('suggest_demand', $ids)->get()->result_array();
            if($type == 1){
                // 需求单和备货单同时作废
                $suggest = [
                    "suggest_status"=> SUGGEST_STATUS_CANCEL,
                    "cancel_reason_category" => $reasons,
                ];
                $demand= [
                    "demand_status"=> DEMAND_STATUS_CANCEL,
                    "cancel_reason_category" => $reasons,
                    "demand_cancel_time" => date("Y-m-d H:i:s"),
                ];

                $reasonString = $this->purchase_db->from("reason_config")->where("id",$reasons)
                    ->select("reason_name")->get()->row_array();
                if($push && !empty($push)){
                    foreach ($push as $val){
                        $push_plan[] = [
                            "pur_sn"        => $val['demand_number'],
                            "state"         => SUGGEST_STATUS_CANCEL,
                            "business_line" => $val['purchase_type_id'],
                            "cancel_reason" => $reasonString['reason_name']
                        ];
                    }
                }
            }else if($type == 2){
                // 作废到需求单
                $suggest = [
                    "suggest_status"=> SUGGEST_STATUS_CANCEL,
                    "cancel_reason_category" => $reasons,
                ];
                $demand= [
                    "demand_status"=> DEMAND_SKU_STATUS_CONFIR,
//                    "suggest_demand"=> '',
                    "cancel_reason_category" => $reasons,
                    "demand_cancel_time" => date("Y-m-d H:i:s",time()),
                    'is_lock' =>1
                ];
            }
            if(empty($suggest) || empty($demand))throw new Exception('作废失败，作废类型错误。');
            $this->purchase_db->where_in("suggest_demand", $ids)->update("pur_purchase_demand", $demand);
            $this->purchase_db->where_in("demand_number", $ids)->update("pur_purchase_suggest", $suggest);
            // 把数组指针移动到开头
            reset($push);
            $repetition = $no_repetition = [];
            foreach($push as $pushDemand_key=>$pushDemand_value){
                $repeat = $this->purchase_suggest_model->get_judge_sku_repeat($pushDemand_value['sku'],
                    $pushDemand_value['purchase_type_id'],$pushDemand_value['id'],"prev");
                if($repeat == "repetition"){
                    $repetition[] = $pushDemand_value['demand_number'];
                }

                if($repeat == "no_repetition"){
                    $no_repetition[] = $pushDemand_value['demand_number'];
                }


            }

            if(!empty($repetition)){

                $repetition = array_unique($repetition);
                $this->purchase_db->where_in("demand_number",$repetition)->update("pur_purchase_demand",['demand_repeat'=>1]);
            }

            if(!empty($no_repetition)){
                $repetition = array_unique($no_repetition);
                $this->purchase_db->where_in("demand_number",$repetition)->update("pur_purchase_demand",['demand_repeat'=>2]);

            }

            // 备货单作废到需求单时候，SKU 获取最新的单价，供应商，采购员相关信息
            if( $type == 2){

                if(!empty($push)){
                    $this->load->model('product/product_model');
                    foreach($push as $push_key=>$push_value){
                        $productMessage = $this->purchase_db->from("product")->where("sku",$push_value['sku'])
                            ->select("purchase_price,supplier_code,supplier_name,tax_rate,ticketed_point")->get()->row_array();
                        if(!empty($productMessage)){


                            $is_drawback = $this->product_model->getProductIsBackTaxNew($productMessage['supplier_code'],
                                $productMessage['tax_rate'],
                                $productMessage['ticketed_point']);

                            $change_data =[
                                'new_supplier_price' => $productMessage['purchase_price'],
                                'new_supplier_code' => $productMessage['supplier_code'],
                                'new_supplier_name' => $productMessage['supplier_name'],
                                'is_drawback' => $is_drawback,
                                'new_ticketed_point' => $productMessage['ticketed_point']
                            ];
                            $result = $this->purchase_suggest_model->
                            change_demand_purchase_price($push_value['sku'],$change_data,[],$push_value['id']);

                            $this->purchase_suggest_model->change_demand_pay_type($push_value['sku'],
                                $productMessage['supplier_code']
                            ,$push_value['id']);
                        }
                    }
                }
            }

            if($push_plan){
                $this->load->model('approval_model');
                $push_plan = $this->approval_model->push_plan_expiration($push_plan);//推送计划系统作废备货单
                if($push_plan !== true){
                    throw new Exception('推送计划系统作废失败！');
                }
            }

            $this->purchase_db->trans_commit();
            if ($this->purchase_db->trans_status() === true) {
                $res['code'] = 1;
                $res['msg']  = '作废成功！';
            } else {
                $this->purchase_db->trans_rollback();
                throw new Exception('写入数据失败。');
            }
        }catch(Exception $e){
            $res['msg']  = '作废失败，原因：'.$e->getMessage();
            $this->purchase_db->trans_rollback();
        }
        return $res;
    }

    /**
     * 获取备货单对应的采购单信息
     */
    public function get_purchase_list($demand)
    {
        $res = [];
        if(empty($demand))return $res;
        $data = $this->purchase_db->from("purchase_order_items as it")
            ->select("it.demand_number,o.purchase_number,o.purchase_order_status")
            ->join("pur_purchase_order as o", "it.purchase_number=o.purchase_number", "inner")
            ->where_in("it.demand_number", $demand)
            ->get()
            ->result_array();
        if(!$data || count($data) == 0)return $res;
        foreach ($data as $val){
            $res[$val['demand_number']] = $val;
        }
        return $res;
    }

    /**
     * 分摊 备货单实际采购数量到需求单上
     * 如果实际采购数量等于0，需求单状态改为已作废
     * @param string $demand_number 备货单号
     * @param int $confirm_amount 备货单实际采购数量
     * @author Jolon
     * @return bool
     */
    public function apportionPurchaseAmount($demand_number,$confirm_amount){
        if(empty($demand_number)) return false;

        $demandList = $this->purchase_db->select('id,demand_data')
            ->from('purchase_demand')
            ->where('suggest_demand',$demand_number)
            ->order_by('create_time ASC')
            ->get()
            ->result_array();

        $confirm_amount = intval($confirm_amount);
        foreach($demandList as $item_val){

            if($confirm_amount <= 0){
                $had_purchase_amount = 0;

            }else if($confirm_amount > $item_val['demand_data']){
                $had_purchase_amount = $item_val['demand_data'];
                $confirm_amount = $confirm_amount - $item_val['demand_data'];
            }else{
                $had_purchase_amount = $confirm_amount;
                $confirm_amount = 0;
            }
            $update  = ['had_purchase_amount' => $had_purchase_amount];
            if ($had_purchase_amount>0) {
                $update['demand_status'] = DEMAND_STATUS_FINISHED;
            } else {
                $update['demand_status'] = DEMAND_STATUS_CANCEL;

            }

            $this->purchase_db->where('id',$item_val['id'])->update('purchase_demand',$update);
        }

        return true;
    }

}