<?php
/**
 * 采购单查询列表
 */
class Purchase_order_list_model extends Purchase_model
{
    /**
     * Purchase_order_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase/purchase_order_sum_model');
        $this->load->model('warehouse/warehouse_model');
        $this->load->model('product/product_model');
        $this->load->model('user/User_group_model');
        $this->config->load('key_name', FALSE, TRUE);
        $this->load->helper('abnormal');
        $this->load->helper('status_order');
        $this->load->model('product_line_model');
        $this->load->helper('status_1688');
        $this->load->helper('common');
    }

    /**
     * 获取SKU 降价审核信息
     * @author:luxu
     * @param $skus array   SKU信息
     **/

    private function get_sku_log($skus = array()){

        if(empty($skus)){

            return [];
        }
        $result = $this->purchase_db->from("product_update_log")->where_in("sku",$skus)->where("new_supplier_price<old_supplier_price")
            ->where("audit_status!=3")->where("audit_status!=4")->select("sku")->get()->result_array();
        if(!empty($result)){

            return array_column($result,'sku');
        }

        return [];
    }

    /**
     * 采购单列表
     */
    public function new_get_list($params = [], $offsets = 0, $limit = 50, $page = 1,$type=true,$is_export = False, $minid=0, $copun_flag = False,$export_user=[], $client_user_id=NULL, $is_sum=false)
    {
        $user_groups_types = user_group_check(); //当前登录用户ID的权限所属的组

        $user_id=jurisdiction(); //当前登录用户ID
        $role_name=get_user_role();//当前登录角色
        $data_role = getRole();//数据权限配置
        $thisTime = date("Y-m-d H:i:s");
        $res_arr = array_intersect($role_name, $data_role);
        $query_builder = $this->purchase_db;
        $sumjoinsql = NULL;
        $insideSql = ' SELECT
            orderdata.id 
          FROM (
            SELECT
                distinct b.id,type.real_price
            FROM
              pur_purchase_order_items AS b
              INNER JOIN pur_purchase_order AS a ON a.purchase_number = b.purchase_number 
              INNER JOIN pur_purchase_suggest AS d ON d.demand_number = b.demand_number
              LEFT JOIN pur_purchase_order_pay_type AS type ON b.purchase_number=type.purchase_number
              LEFT JOIN `pur_supplier` AS `f` ON `f`.`supplier_code` = `a`.`supplier_code`
              LEFT JOIN `pur_warehouse_results_main` AS `ware` ON `ware`.`purchase_number` = `b`.`purchase_number` AND `ware`.`sku` = `b`.`sku`
             ';
        $outsideSql = '';
        $insideWhere='';

        if( isset($params['is_arrive_time_audit_start']) && !empty($params['is_arrive_time_audit_start'])) {
            $insideSql .= ' LEFT JOIN pur_supplier_web_audit AS webaudit ON b.purchase_number=webaudit.purchase_number ';
            $insideSql .= ' AND b.sku=webaudit.sku ';
        }
        $insideSql .= "	LEFT JOIN `pur_purchase_logistics_info` AS `pli` ON `pli`.`purchase_number` = `b`.`purchase_number` AND b.sku=pli.sku ";
        if($params['list_type'] == TIPS_TODAY_WORK){
            $insideSql .= " AND pli.carrier_code != '' AND pli.carrier_code NOT IN ( 'other' )";
        }

        if($type) {
            $insideWhere = " WHERE b.purchase_number NOT LIKE 'YPO%' ";
            if(isset($export_user['user_id'])){
                $user_id = $export_user['user_id'];
                if(!empty($user_id)){
                    $user_id = implode(",", $user_id);
                    $user_id = $this->User_group_model->get_jurisdiction($user_id);
                    $user_groups_types = $this->User_group_model->user_group_check($export_user['export_user_id']);
                }
                $user_id = !empty($user_id) ? $user_id : $export_user['user_id'];
                $role_name = $export_user['role_name'];
                $data_role = $export_user['data_role'];
            }
            $res_arr = array_intersect($role_name, $data_role);
            if(!(!empty($res_arr) || $user_id === true )){// 根据数据权限查询采购单
                $insideWhere .= " AND a.buyer_id IN (".implode(",",$user_id).")";
            }
        }else{
            $insideWhere = " WHERE 1=1 ";
        }

        if(SetAndNotEmpty($params, 'is_merge', 'n')) $insideWhere .= " and d.is_merge = '".$params['is_merge']."' ";
        if(SetAndNotEmpty($params, 'temp_container')) $insideWhere .= " and d.temp_container = '".$params['temp_container']."' ";
        if(SetAndNotEmpty($params, 'is_distribution')) $insideWhere .= " and d.is_distribution = '".$params['is_distribution']."' ";
        if(SetAndNotEmpty($params, 'logistics_type')) $insideWhere .= " and d.logistics_type in ('".implode("','", $params['logistics_type'])."') ";
        if(SetAndNotEmpty($params, 'groupdatas')) $insideWhere .= " AND a.buyer_id IN (".implode(",",$params['groupdatas']).")";
        if(is_array($user_groups_types))  $insideWhere .= " AND a.purchase_type_id in (".implode(",", $user_groups_types).")";
        if(SetAndNotEmpty($params, 'quantity', 'n'))$insideWhere .= " and b.quantity={$params['quantity']} ";
        if(SetAndNotEmpty($params, 'is_long_delivery')) $insideWhere .= " AND b.is_long_delivery=".$params['is_long_delivery'];
        if(SetAndNotEmpty($params, 'delivery')) $insideWhere .= $params['delivery'] == 1 ? " AND b.new_devliy>0" : " AND b.new_devliy=0";
        if(SetAndNotEmpty($params, 'use_wechat_official')) $insideWhere .= " AND f.use_wechat_official = {$params['use_wechat_official']}";
        if(SetAndNotEmpty($params, 'new_is_freight')) $insideWhere .= ' AND type.is_freight ='.$params['new_is_freight'];
        if(SetAndNotEmpty($params, 'is_arrive_time_audit_start')) $insideWhere .=" AND webaudit.audit_status=".$params['is_arrive_time_audit_start'];
        if(SetAndNotEmpty($params, 'gateway_status'))$insideWhere .= " AND a.gateway_status IN (".implode(",",$params['gateway_status']).")";
        if(SetAndNotEmpty($params, 'is_new', 'n')) $insideWhere .= " AND b.is_new=".$params['is_new'];
        if(SetAndNotEmpty($params, 'is_customized')) $insideWhere .= " AND b.is_customized=".$params['is_customized'];
        if(SetAndNotEmpty($params, 'pay_type')) $insideWhere .= " AND a.pay_type=".$params['pay_type'];
        if(SetAndNotEmpty($params, 'ticketed_point')) $insideWhere .=  " AND b.pur_ticketed_point" .($params['ticketed_point'] == 1 ? "=0" : ">0");
        if(SetAndNotEmpty($params, 'is_purchasing')) $insideWhere .= " AND d.is_purchasing=".$params['is_purchasing'];
        if(SetAndNotEmpty($params, 'barcode_pdf')) $insideWhere .= " AND b.barcode_pdf".($params['barcode_pdf'] == 1 ? "!=''": "=''");
        if(SetAndNotEmpty($params, 'label_pdf')) $insideWhere .=  " AND b.label_pdf".($params['label_pdf'] == 1 ? "!=''": "=''");
        if(SetAndNotEmpty($params, 'is_oversea_boutique', 'n')) $insideWhere .= " and d.is_overseas_boutique=".$params['is_oversea_boutique'];
        if(SetAndNotEmpty($params, 'is_fumigation')) $insideWhere .= " AND d.extra_handle ".($params['is_fumigation'] == 1 ? "=":"!=")." 1 ";

        if(SetAndNotEmpty($params, 'track_status')){
            $trackStatusString = $this->get_map_string($params['track_status']);
            if(in_array(0,$params['track_status'])){
                $insideWhere .= " AND (pli.status IS NULL OR pli.status IN(".implode(",",$trackStatusString)."))";
            }else{
                $insideWhere .= " AND pli.status IN(".implode(",",$trackStatusString).")";
            }
        }

        if(SetAndNotEmpty($params, 'exp_no_is_empty')){
            if($params['exp_no_is_empty'] == 1){
                $insideWhere .= " AND (pli.express_no!='' AND pli.express_no IS NOT NULL)";
            }else{
                $insideWhere .= " AND (pli.express_no='' OR pli.express_no IS NULL)";
            }
        }

        if(SetAndNotEmpty($params, 'mude_code')){
            $mude_code = $this->get_map_string($params['mude_code']);
            $insideWhere .= ' AND d.destination_warehouse IN ('.implode(",",$mude_code).')';
        }

        if(SetAndNotEmpty($params, 'transformation')) {
            if( $params['transformation'] == 1){
                $params['transformation'] = 0;
                $insideWhere.=" AND a.sku_state_type!=6";
            }else {
                $insideWhere .= " AND a.sku_state_type=" . $params['transformation'];
            }
        }

        if(!empty($params['unfinished_overdue'])){
            //未完结天数（1-超20天未完结，2-超30天未完结，3-超40天未完结，4-45天以上总未完结，5-45-60天未完结，6-60天以上未完结）
            switch ($params['unfinished_overdue']){
                case 1:
                    //超20天未完结
                    $insideWhere .= " AND TIMESTAMPDIFF(DAY,a.waiting_time,NOW()) >=20 AND TIMESTAMPDIFF(DAY,a.waiting_time,NOW()) <30";
                    break;
                case 2:
                    // 超30天未完结
                    $insideWhere .= " AND TIMESTAMPDIFF(DAY,a.waiting_time,NOW()) >=30 AND TIMESTAMPDIFF(DAY,a.waiting_time,NOW()) <40";
                    break;
                case 3:
                    // 超40天未完结
                    $insideWhere .= " AND TIMESTAMPDIFF(DAY,a.waiting_time,NOW()) >=40";
                    break;
                case 4:
                    // 45天以上总未完结
                    $insideWhere .= " AND TIMESTAMPDIFF(DAY,a.waiting_time,NOW()) >=45";
                    break;
                case 5:
                    // 45-60天未完结
                    $insideWhere .= " AND TIMESTAMPDIFF(DAY,a.waiting_time,NOW()) >=45 AND TIMESTAMPDIFF(DAY,a.waiting_time,NOW()) <=60";
                    break;
                case 6:
                    // 60天以上未完结
                    $insideWhere .= " AND TIMESTAMPDIFF(DAY,a.waiting_time,NOW()) >60";
                    break;
            }
        }

        if(SetAndNotEmpty($params, 'shipment_type', 'n'))$insideWhere .= " AND a.shipment_type = ".$params["shipment_type"];
        if(SetAndNotEmpty($params, 'pur_manager_audit_reject', 'n')) $insideWhere .= " AND a.audit_time != '0000-00-00 00:00:00'";
        if(SetAndNotEmpty($params, 'is_completion_order', 'n')) $insideWhere .= " AND a.completion_time ".($params['is_completion_order'] == 1 ? ">" : "=")." '0000-00-00 00:00:00'";

        //入库数量不等于0(1-是)
        if (isset($params['instock_qty_gt_zero']) && 1 == $params['instock_qty_gt_zero']) {
            $insideSql .= " LEFT JOIN pur_warehouse_results AS wr ON wr.purchase_number=b.purchase_number AND wr.sku=b.sku ";
            $insideWhere .= " AND wr.instock_qty >0 ";
        }
        //逾期天数
        if(!empty($params['overdue_delivery_type'])){
            // [1 => '逾期7天以上',2 => '逾期10天以上',3 => '逾期15天以上',4 => '逾期20天以上',5 => '逾期30天以上',];
            switch ($params['overdue_delivery_type']){
                case 1:
                    // 逾期7天以上
                    $insideWhere .= " AND b.overdue_days >7 AND b.overdue_days <=15";
                    break;
                case 2:
                    // 逾期7天以上
                    $insideWhere .= " AND b.overdue_days >3 AND b.overdue_days <=7";
                    break;
                case 3:
                    // 逾期15天以上
                    $insideWhere .= " AND b.overdue_days >15 AND b.overdue_days <=20";
                    break;
                case 4:
                    // 逾期20天以上
                    $insideWhere .= " AND b.overdue_days >20 AND b.overdue_days <=30";
                    break;
                case 5:
                    // 逾期30天以上
                    $insideWhere .= " AND b.overdue_days >30";
                    break;
            }
            $insideWhere .= " AND plan_arrive_time!='0000-00-00 00:00:00'";
        }

        if(SetAndNotEmpty($params, 'overdue_days_one', 'n')){
            if($params['overdue_days_one'] == 1){
                $insideWhere .= " AND b.overdue_days>0 AND plan_arrive_time!='0000-00-00 00:00:00'";
            }else{
                $insideWhere .= " AND (b.overdue_days=0 OR plan_arrive_time='0000-00-00 00:00:00')";
            }
        }

        if(SetAndNotEmpty($params, 'overdue_day_day', 'n') || SetAndNotEmpty($params, 'overdue_day_one', 'n')){
            $overdue = !empty($params['overdue_day_day']) ? $params['overdue_day_day'] : $params['overdue_day_one'];
            if($overdue == 1) {
                $insideWhere .= " AND now()>= b.plan_arrive_time AND b.plan_arrive_time<ware.instock_date AND b.plan_arrive_time!='0000-00-00 00:00:00'";
            }else{
                $insideWhere .= " AND (now()< b.plan_arrive_time AND b.plan_arrive_time>=ware.instock_date OR  b.plan_arrive_time='0000-00-00 00:00:00')";
            }
        }

        //1688订单状态
        if(!empty($params['ali_order_status'])){
            $aliOrderStatus = aliOrderStatus();
            if(!is_array($params['ali_order_status'])) {
                $status_text = isset($aliOrderStatus[$params['ali_order_status']]) ? $aliOrderStatus[$params['ali_order_status']] : '';
                $insideWhere .= " AND a.ali_order_status = '{$status_text}'";
            }else{
                $aliOrderFlag = [];
                foreach($params['ali_order_status'] as $ali_order_status){
                    $aliflag =  isset($aliOrderStatus[$ali_order_status])?$aliOrderStatus[$ali_order_status]:'';
                    if( $aliflag != '')  $aliOrderFlag[] = $aliflag;
                }
                $stringaliOrderFlag = [];
                if(!empty($aliOrderFlag))$stringaliOrderFlag = $this->get_map_string($aliOrderFlag);

                if(!empty($stringaliOrderFlag)) {
                    $insideWhere .= " AND a.ali_order_status  IN (" . implode(",", $stringaliOrderFlag) . ")";
                }else{
                    $insideWhere .= " AND a.ali_order_status =''";
                }
            }
        }
        //1688退款金额≠0
        if(!empty($params['ali_refund_amount'])) $insideWhere .= " AND type.apply_amount >0 ";
        if(SetAndNotEmpty($params, 'is_gateway', 'n')) $insideWhere .= " AND a.is_gateway=".($params['is_gateway'] == 2 ? SUGGEST_IS_GATEWAY_NO: SUGGEST_IS_GATEWAY_YES);
        if(SetAndNotEmpty($params, 'is_customized', 'n')) $insideWhere .=" AND b.is_customized=".$params['is_customized'];
        if(SetAndNotEmpty($params, 'push_gateway_success', 'n')) $insideWhere .= " AND a.push_gateway_success=".$params['push_gateway_success'];
        if(SetAndNotEmpty($params, 'check_status', "ar")) $insideWhere .= " AND  b.check_status IN (" . implode(",", $params['check_status']) . ")";
        if(SetAndNotEmpty($params, 'is_overseas_first_order')) $insideWhere .= " AND d.is_overseas_first_order IN(" . $params['is_overseas_first_order'] . ")";
        if(SetAndNotEmpty($params, 'devliery_status') ) $insideWhere .= " AND b.devliery_status=".$params['devliery_status'];

        if( SetAndNotEmpty($params, 'devliery_days')){
            $insideWhere .= $params['devliery_days']  == 1 ? " AND (b.devliery_days=0 OR b.plan_arrive_time!='0000-00-00 00:00:00')": " AND b.devliery_days>0 AND  b.plan_arrive_time!='0000-00-00 00:00:00'";
        }
        if (SetAndNotEmpty($params, 'suggest_order_status')){// 备货单状态 查询的时候为数组
            if( is_array($params['suggest_order_status'])) {
                $params['suggest_order_status'] = implode(",",$params['suggest_order_status']);
            }
            $insideWhere .= " AND  d.suggest_order_status IN (".$params['suggest_order_status'].")";
        }

        if(SetAndNotEmpty($params, "buyer_id")){  // 采购员
            if( is_array($params['buyer_id']) ) {
                $params['buyer_id'] = implode(",",$params['buyer_id']);
            }
            $insideWhere .= " AND a.buyer_id IN (".$params['buyer_id'].")";
        }

        if(SetAndNotEmpty($params, "supplier_source")) {
            $insideSql.=" LEFT JOIN `pur_purchase_suggest` AS `nsg` ON `c`.`demand_number` = `nsg`.`demand_number` ";
            $insideWhere .= " AND nsg.supplier_source =".$params['supplier_source'];
        }
        if(isset( $params['is_scree']) ) {
            $insideSql.= " LEFT JOIN pur_product_scree AS scree ON scree.sku=b.sku ";
            $scree_status = [
                PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT,
                PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM,
                PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM,
            ];
            if( $params['is_scree'] == 1 ) {
                $insideWhere .=" AND scree.status IN (".implode(',', $scree_status).")";
            }
            if( $params['is_scree'] == 2 ) {
                $insideWhere .= " AND (scree.status NOT IN (".implode(',', $scree_status).") OR ( b.sku NOT IN ( SELECT sku FROM pur_product_scree GROUP BY sku)) )";
            }
        }

        if(SetAndNotEmpty($params, 'demand_number') && is_array($params['demand_number'])){ //备货单号
            $params['demand_number'] = $this->get_map_string($params['demand_number']);
            $demand_number_str = implode(',',$params['demand_number']);
            $insideWhere .= " AND b.demand_number in (".$demand_number_str.")";
        }

        if(SetAndNotEmpty($params, 'is_invaild') || SetAndNotEmpty($params, 'product_status') || SetAndNotEmpty($params, 'is_inspection') ||
            SetAndNotEmpty($params, 'state_type') ||
            (isset($params['is_relate_ali']) &&  $params['is_relate_ali']!='' ) ||
            (isset($params['is_equal_sup_id']) &&  $params['is_equal_sup_id']!='' ) ||
            (isset($params['is_equal_sup_name']) &&  $params['is_equal_sup_name']!='' ) ||
            (isset($params['first_product_line']) and $params['first_product_line'] != '')) { // 商品状态
            $insideSql .= " LEFT JOIN `pur_product` AS `p` ON `p`.`sku` = `b`.`sku`";

            if (isset($params['first_product_line']) && $params['first_product_line'] != ''){
                //一级转三级产品线
                $this->load->model('product_line_model','product_line',false,'product');
                $third_product_line = $this->product_line->get_all_category($params['first_product_line']);
                $third_product_line = trim($third_product_line,',');
                if($third_product_line != '')$insideWhere .=" AND p.product_line_id IN (".$third_product_line.")";
            }

            if(SetAndNotEmpty($params, 'product_status')) $insideWhere .= " AND p.product_status IN (" . implode(",", $params['product_status']) . ")";
            if(SetAndNotEmpty($params, 'is_inspection')) $insideWhere .= " AND p.is_inspection=".$params['is_inspection'];
            if(SetAndNotEmpty($params, 'is_relate_ali', 'n')) $insideWhere .=" AND p.is_relate_ali=".($params['is_relate_ali'] == 1? 1:0);
            if(SetAndNotEmpty($params, 'state_type')) $insideWhere .= " AND p.state_type".(is_array($params['state_type']) ? "in (".implode(',',$params['state_type']).")": "=".$params['state_type']);
            if(SetAndNotEmpty($params, 'is_invaild', 'n')) $insideWhere .=" AND p.is_invalid=".($params['is_invaild'] == 1? 1:0);
            if(SetAndNotEmpty($params, 'is_equal_sup_id')) $insideWhere .= " AND p.is_equal_sup_id=".intval($params['is_equal_sup_id']);
            if(SetAndNotEmpty($params, 'is_equal_sup_name')) $insideWhere .= " AND p.is_equal_sup_name=".intval($params['is_equal_sup_name']);
        }

        if (SetAndNotEmpty($params, 'supplier_code') || SetAndNotEmpty($params, 'pai_number')) {// 供应商
            if($params['supplier_code']){
                if(!is_array($params['supplier_code']))$params['supplier_code'] = [$params['supplier_code']];
                $insideWhere .= " AND a.supplier_code in ('".implode("','", $params['supplier_code'])."')";
            }

            if($params['pai_number'] && is_array($params['pai_number'])){
                $insideSql .= " LEFT JOIN `pur_purchase_order_pay` AS `po` ON `b`.`purchase_number` = `po`.`pur_number`";
                $params['pai_number'] = $this->get_map_string($params['pai_number']);
                $pai_number_str = implode(',',$params['pai_number']);
                $insideWhere .= " AND py.pai_number in (" . $pai_number_str . ")";
            }
        }

        //对账单号搜索
        if (SetAndNotEmpty($params, 'statement_number')){
            $insideSql .= " LEFT JOIN `pur_purchase_statement_summary` AS `st_sm` ON `b`.`purchase_number` = `st_sm`.`purchase_number`";
            $statement_numbers= explode (' ', trim($params['statement_number']));
            $statement_numbers = $this->get_map_string($statement_numbers);
            $insideWhere .= " AND st_sm.statement_number IN (".implode(",",$statement_numbers).")";
        }

        if(SetAndNotEmpty($params, 'is_drawback', 'n')) $insideWhere .= " AND a.is_drawback=" . $params['is_drawback'];
        if(SetAndNotEmpty($params, 'entities_lock_status', 'n')) $insideWhere .= " AND d.lock_type = ".($params['entities_lock_status']==2 ? LOCK_SUGGEST_ENTITIES: 0);
        if(SetAndNotEmpty($params, 'product_name')) $insideWhere .= " AND b.product_name LIKE '%".trim($params['product_name'])."%'";

        if (!empty($params['sku'])) {
            $sku = query_string_to_array($params['sku']);
            if (count($sku) == 1) {  //单个sku时使用模糊搜索
                $insideWhere .= " AND b.sku like '%".implode(",",$sku)."%'";
            } else {
                $insideWhere .= " AND b.sku IN (".query_array_to_string($sku).")";
            }
        }

        if(SetAndNotEmpty($params, 'purchase_number')){// 采购单号
            $purchase_numbers= explode (' ', trim($params['purchase_number']));
            $purchase_numbers = $this->get_map_string($purchase_numbers);
            $insideWhere .= " AND b.purchase_number IN (".implode(",",$purchase_numbers).")";
        }

        if(SetAndNotEmpty($params, 'pay_status')) {// 付款状态
            if( is_array($params['pay_status'])) {
                $params['pay_status'] = implode(",",$params['pay_status']);
            }
            $insideWhere .= " AND  a.pay_status IN (".$params['pay_status'].")";
        }

        if(SetAndNotEmpty($params, 'is_destroy', 'n')){//是否核销
            $insideWhere .= " AND a.purchase_order_status in(".PURCHASE_ORDER_STATUS_ALL_ARRIVED.",".PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE.",".PURCHASE_ORDER_STATUS_CANCELED.")";
            $insideWhere .= " AND a.is_destroy =".$params['is_destroy'];
        }

        if(SetAndNotEmpty($params, 'purchase_type_id')) $insideWhere .= " AND a.purchase_type_id ".(is_array($params['purchase_type_id']) ? "IN(".implode(",",$params['purchase_type_id']).")": "=".$params['purchase_type_id']);
        if(SetAndNotEmpty($params, 'source')) $insideWhere .= " AND a.source=".$params['source'];

        if(SetAndNotEmpty($params, 'demand_purchase_type_id')) {
            if(is_array($params['demand_purchase_type_id'])){
                $insideWhere .= " AND d.purchase_type_id IN(".implode(",",$params['demand_purchase_type_id']).")";
            }else{
                $insideWhere .= " AND d.purchase_type_id =".$params['demand_purchase_type_id'];
            }
        }
        if(SetAndNotEmpty($params, 'create_time_start')) $insideWhere .= " AND a.create_time>='".trim($params['create_time_start'])."'";
        if(SetAndNotEmpty($params, 'create_time_end')) $insideWhere .= " AND a.create_time<='".trim($params['create_time_end'])."'";
        if(SetAndNotEmpty($params, 'audit_time_start')) $insideWhere .= " AND a.audit_time>='".trim($params['audit_time_start'])."'";
        if(SetAndNotEmpty($params, 'audit_time_end')) $insideWhere .= " AND a.audit_time<='".trim($params['audit_time_end'])."'";

        if(SetAndNotEmpty($params, 'compact_number') && is_array($params['compact_number'])) {// 合同号
            $params['compact_number'] = $this->get_map_string($params['compact_number']);
            $compact_number_str = implode(',',$params['compact_number']);

            $pur_numbers = $query_builder->query("SELECT purchase_number FROM pur_purchase_compact_items WHERE bind=1 and compact_number in ({$compact_number_str})")->result_array();
            $pur_numbers = !empty($pur_numbers)?array_column($pur_numbers,'purchase_number'):[PURCHASE_NUMBER_ZFSTATUS];
            $pur_number = $this->get_map_string($pur_numbers);
            $insideWhere .= " AND a.purchase_number IN (".implode(",",$pur_number).")";
        }

        if(SetAndNotEmpty($params, 'is_cross_border', 'n')) $insideWhere .= " AND a.is_cross_border =".$params['is_cross_border'];
        if(SetAndNotEmpty($params, 'product_is_new', 'n')) $insideWhere .= " AND b.is_new =".$params['product_is_new'];
        if(SetAndNotEmpty($params, 'is_ali_order', 'n')) $insideWhere .= " AND a.is_ali_order =".$params['is_ali_order'];
        if(SetAndNotEmpty($params, 'is_ali_abnormal', 'n'))  $insideWhere .=" AND a.is_ali_abnormal=".$params['is_ali_abnormal'];
        if(SetAndNotEmpty($params, 'is_ali_price_abnormal', 'n')) $insideWhere .=" AND a.is_ali_price_abnormal=".$params['is_ali_price_abnormal'];
        if(SetAndNotEmpty($params, 'ca_amount_search')) $insideWhere .= " AND (b.ca_product_money+b.ca_process_cost ".($params['ca_amount_search'] === '=0' ? "=" : "<>")." 0) ";
        if(SetAndNotEmpty($params, 'lack_quantity_status', 'n')) $insideWhere .=" AND a.lack_quantity_status=1".(intval($params['lack_quantity_status']) == 1 ? 1:0);
        if(SetAndNotEmpty($params, 'express_no')) $insideWhere .= " AND pli.express_no='".$params['express_no']."'";
        if(SetAndNotEmpty($params, 'is_forbidden', 'n')) $insideWhere .= " AND f.status".($params['is_forbidden']==1 ? "=": "<>")."2";
        if(SetAndNotEmpty($params, 'pay_finish_status')) {
            $p_pfs = $params['pay_finish_status'];
            $insideWhere .= " AND a.pay_finish_status ".(is_array($p_pfs) ? "IN (".implode(",", $p_pfs).")" : "=".$p_pfs);
        }
        if(SetAndNotEmpty($params, 'is_overdue', 'n')) {
            if($params['is_overdue'] == 1) {
                $insideWhere .= " AND b.is_overdue=" . $params['is_overdue'] . " AND b.plan_arrive_time!='0000-00-00 00:00:00'";
            }else{
                $insideWhere .= " AND (b.is_overdue=" . $params['is_overdue'] . " OR b.plan_arrive_time='0000-00-00 00:00:00')";
            }
        }

        if (SetAndNotEmpty($params, 'loss_status')) {// 报损状态
            $insideSql .= " LEFT JOIN `pur_purchase_order_reportloss` AS `lo` ON `b`.`purchase_number` = `lo`.`pur_number` AND `b`.`sku` = `lo`.`sku` ";
            $insideWhere .= " AND lo.status".(is_array($params['loss_status']) ? " in (".implode(",", $params['loss_status']).")" : "=".$params['loss_status']);
        }

        if(!isset($params['pay_notice']))$params['pay_notice'] = [];
        if(!is_array($params['pay_notice']))$params['pay_notice'] = [$params['pay_notice']];
        if(
            SetAndNotEmpty($params, 'express_no') ||
            (SetAndNotEmpty($params, 'need_pay_time_start') && SetAndNotEmpty($params, 'need_pay_time_end')) ||
            !in_array(TAP_DATE_WITHOUT_BALANCE, $params['pay_notice']) || SetAndNotEmpty($params, 'pai_number', 'ar')
        ) {
            $insideSql .= "	LEFT JOIN `pur_purchase_order_pay_type` AS `py` ON `py`.`purchase_number` = `a`.`purchase_number`";
        }

        //临时供应商下单大于0,查询条件显示
        if (!empty($params['order_num'][0])||!empty($params['order_num'][1])) {
            $insideWhere .= " AND f.supplier_source=3 ";
            if (!empty($params['order_num'][0])) {
                if ($params['order_num'][0]>0) {
                    $insideWhere .= " AND f.order_num>={$params['order_num'][0]} ";
                }
            }
            if (!empty($params['order_num'][1])) {
                if ($params['order_num'][1] > 0) {
                    $insideWhere .= " AND f.order_num<={$params['order_num'][1]} ";
                }
            }
        }
        if(SetAndNotEmpty($params, 'ids')) {//勾选数据
            if(is_array($params['ids'])) {
                $params['ids'] = explode(",",$params['ids']);
            }
            $insideWhere .= " AND b.id IN (" . $params['ids'] . ")";
        }

        if(SetAndNotEmpty($params, 'warehouse_code')) {//采购仓库
            if(!is_array($params['warehouse_code'])) {
                $insideWhere .= " AND d.warehouse_code='" . $params['warehouse_code'] . "'";
            }else{
                $warehouseCodeArr = $this->get_map_string($params['warehouse_code']);
                $insideWhere .= " AND d.warehouse_code IN (" . implode(",",$warehouseCodeArr) . ")";
            }
        }

        if(SetAndNotEmpty($params, 'pertain_wms')) {//公共仓
            $pertain_wms_list = implode("','", (is_array($params['pertain_wms']) ? $params['pertain_wms'] : explode(',',$params['pertain_wms'])));
            $insideWhere .= " AND a.pertain_wms IN('".$pertain_wms_list."')";
        }

        if(SetAndNotEmpty($params, 'account_type')) { //结算方式
            if(!is_array($params['account_type'])) $params['account_type'] = [$params['account_type']];
            $children_account_type = $this->purchase_db->from("supplier_settlement")->select("settlement_code")
                ->where_in("parent_id",$params['account_type'])
                ->or_where_in('settlement_code',$params['account_type'])
                ->get()->result_array();
            if(!empty($children_account_type)) {
                $children_account_type = array_column($children_account_type, 'settlement_code');
                $insideWhere .= " AND a.account_type IN (" . implode(",", $children_account_type) . ")";
            }
        }

        if(SetAndNotEmpty($params, 'audit_status')) {// 取消未到货状态
            $_sub_sql = "SELECT DISTINCT items_id FROM pur_purchase_order_cancel_detail  WHERE cancel_id IN(SELECT id FROM pur_purchase_order_cancel WHERE audit_status";
            $_sub_sql .= is_array($params['audit_status']) ? " in (".implode(",", $params['audit_status'])."))": " ={$params['audit_status']})";
            $insideWhere .= " AND b.id IN ({$_sub_sql})";
        }

        //按付款时间查询
        if (SetAndNotEmpty($params, 'pay_time_start') && SetAndNotEmpty($params, 'pay_time_end')) {
            $start_time = (strpos($params['pay_time_start'], ':') !== FALSE) ? $params['pay_time_start'] : $params['pay_time_start'] . ' 00:00:00';
            $end_time = (strpos($params['pay_time_end'], ':') !== FALSE) ? $params['pay_time_end'] : $params['pay_time_end'] . ' 23:59:59';
            $start_time = date('Y-m-d 00:00:00', strtotime($start_time));
            $end_time = date('Y-m-d 23:59:59', strtotime($end_time));
            $insideWhere .= " AND a.pay_time BETWEEN '" . $start_time . "' AND '" . $end_time . "'";
        }

        // 预计到货时间
        if(SetAndNotEmpty($params, 'plan_arrive_time_start') && SetAndNotEmpty($params, 'plan_arrive_time_end')){
            $insideWhere .= " AND b.plan_arrive_time between '".date('Y-m-d 00:00:00',strtotime($params['plan_arrive_time_start']))."' AND '"
                .date('Y-m-d 00:00:00',strtotime($params['plan_arrive_time_end']))."'";
        }

        if(SetAndNotEmpty($params, 'need_pay_time_start') && SetAndNotEmpty($params, 'need_pay_time_end')){//应付款时间
            $start_time = date('Y-m-d 00:00:00',strtotime($params['need_pay_time_start']));
            $end_time = date('Y-m-d 00:00:00',strtotime($params['need_pay_time_end']));
            $insideWhere .= " AND py.accout_period_time between '".$start_time."' AND '".$end_time."'";
        }

        if(!empty($params['pay_notice']) && is_array($params['pay_notice'])) {
            $supplier_settlement_online = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;
            $five_days_later = date('Y-m-d H:i:s', strtotime('+ 5 days'));
            //4-额度已满，需紧急支付(查询额度不足的供应商)
            $_sql1 = "SELECT supplier_code FROM pur_supplier WHERE supplier_settlement={$supplier_settlement_online} AND pur_supplier.quota <> 0 AND pur_supplier.surplus_quota <= 0";
            //1-已超期、2-即将到期、3-可继续等待(查询额度足够的供应商)
            $_sql2 = "SELECT supplier_code FROM pur_supplier WHERE supplier_settlement='{$supplier_settlement_online}' AND pur_supplier.quota <> 0 AND pur_supplier.surplus_quota > 0";
            if (count($params['pay_notice']) == 4) {//同时查询（1-已超期、2-即将到期、3-可继续等待、4-额度已满，需紧急支付）
                $insideWhere .= " AND ( (a.supplier_code IN (" . $_sql1 . ")) OR (a.supplier_code IN (" . $_sql2 . ") AND py.accout_period_time != '0000-00-00 00:00:00') )";
            } elseif (count($params['pay_notice']) == 1 && TAP_DATE_WITHOUT_BALANCE == $params['pay_notice'][0]) {//只按照4-额度已满，需紧急支付查询(查询额度不足的供应商)
                $insideWhere .= " AND ( a.supplier_code IN (" . $_sql1 . ") )";
            } elseif (count($params['pay_notice']) == 1 && !in_array(TAP_DATE_WITHOUT_BALANCE, $params['pay_notice'])) {//只查询一种付款提醒方式的情况(查询额度足够的供应商)
                $where = $this->purchase_order_model->_get_pay_notice_sql($params['pay_notice'][0], $_sql2);
                $insideWhere .= ' AND ' . $where;
            } elseif (count($params['pay_notice']) == 2) {//同时查询2种付款提醒方式的情况
                $_where = " AND a.supplier_code IN (" . $_sql2 . ") AND py.accout_period_time != '0000-00-00 00:00:00'";
                //1、(查询额度不足的供应商)和(查询额度足够的供应商)各一种
                if (in_array(TAP_DATE_WITHOUT_BALANCE, $params['pay_notice'])) {//4-额度已满，需紧急支付(查询额度不足的供应商)
                    //删除4-额度已满的方式
                    arrayDelElementByVal($params['pay_notice'], TAP_DATE_WITHOUT_BALANCE);

                    $where = $this->purchase_order_model->_get_pay_notice_sql($params['pay_notice'][0], $_sql2);
                    $insideWhere .= " AND ( (a.supplier_code IN (" . $_sql1 . ")) OR ($where) )";
                } elseif (in_array(TAP_DATE_OVER_TIME, $params['pay_notice']) && in_array(TAP_DATE_COMING_SOON, $params['pay_notice'])) {
                    //2、两种都是(查询额度足够的供应商)的情况(1-已超期、2-即将到期)
                    $insideWhere .= $_where . " AND py.accout_period_time<'{$five_days_later}'";
                } elseif (in_array(TAP_DATE_OVER_TIME, $params['pay_notice']) && in_array(TAP_DATE_CAN_WAIT, $params['pay_notice'])) {
                    //2、两种都是(查询额度足够的供应商)的情况(1-已超期、3-可继续等待)
                    $insideWhere .= $_where . " AND ( (py.accout_period_time<'{$thisTime}') OR (py.accout_period_time>='{$five_days_later}') )";
                } else {
                    //2、两种都是(查询额度足够的供应商)的情况(2-即将到期、3-可继续等待)
                    $insideWhere .= $_where . " AND py.accout_period_time>='{$thisTime}'";
                }
            } elseif (count($params['pay_notice']) == 3 && in_array(TAP_DATE_WITHOUT_BALANCE, $params['pay_notice'])) {
                // 包含4-额度已满，需紧急支付查询(查询额度不足的供应商)+2种(查询额度足够的供应商)付款提醒方式的情况
                $_where4 = "(a.supplier_code IN (" . $_sql1 . "))";
                //删除4-额度已满的方式
                arrayDelElementByVal($params['pay_notice'], TAP_DATE_WITHOUT_BALANCE);

                if (in_array(TAP_DATE_OVER_TIME, $params['pay_notice']) && in_array(TAP_DATE_COMING_SOON, $params['pay_notice'])) {
                    //2、两种都是(查询额度足够的供应商)的情况(1-已超期、2-即将到期)
                    $_where = " AND py.accout_period_time<'{$five_days_later}'";
                } elseif (in_array(TAP_DATE_OVER_TIME, $params['pay_notice']) && in_array(TAP_DATE_CAN_WAIT, $params['pay_notice'])) {
                    //2、两种都是(查询额度足够的供应商)的情况(1-已超期、3-可继续等待)
                    $_where = " AND ( (py.accout_period_time<'{$thisTime}') OR (py.accout_period_time>='{$five_days_later}') )";
                } else {
                    //2、两种都是(查询额度足够的供应商)的情况(2-即将到期、3-可继续等待)
                    $_where = " AND py.accout_period_time>='{$thisTime}'";
                }
                $insideWhere .= " AND ( {$_where4} OR (a.supplier_code IN (" . $_sql2 . ") AND py.accout_period_time != '0000-00-00 00:00:00' {$_where}) )";
            } else {//同时查询3种付款提醒方式的情况，1-已超期、2-即将到期、3-可继续等待(查询额度足够的供应商)
                $insideWhere .= " AND a.supplier_code IN (" . $_sql2 . ") AND py.accout_period_time != '0000-00-00 00:00:00'";
            }
        }

        if(SetAndNotEmpty($params, 'is_expedited', 'n')) $insideWhere .= " AND a.is_expedited=".$params['is_expedited'];
        if(SetAndNotEmpty($params, 'is_generate', 'n')) $insideWhere .= " AND a.is_generate=".$params['is_generate'];

        //交期确认状态
        if( isset($params['is_arrive_time_audit']) && !empty($params['is_arrive_time_audit']) ){
            $insideSql .= "	LEFT JOIN `pur_supplier_web_audit` AS `swa` ON `swa`.`purchase_number` = `b`.`purchase_number` AND b.sku=swa.sku";
            $insideWhere .= " AND swa.audit_status='".$params['is_arrive_time_audit']."'";
        }
        $prices_fba =[];//fba,国内业务线
        $prices_oversea = [];//海外仓业务线
        // 如果用户选择了角色条件
        if(isset($params['level']) && !empty($params['level'])){
            // 多个查询条件需要求并集
            if(in_array('headman',$params['level'])){
                $price_arr = $this->purchase_order_model->get_account_price('headman','headman');
                if(isset($price_arr['2']))$prices_fba[] = $price_arr['2'];
                if(isset($price_arr['5']))$prices_oversea[] = $price_arr['5'];
            }

            // 如果是主管,拥有 headman:组长,director:主管
            if(in_array('director',$params['level'])){
                $price_arr = $this->purchase_order_model->get_account_price('director','director');
                if(isset($price_arr['2']))$prices_fba[] = $price_arr['2'];
                if(isset($price_arr['5']))$prices_oversea[] = $price_arr['5'];
            }

            // 如果角色信息包含副经理的角色,拥有 headman:组长,director:主管  deputy_manager:副经理 审核金额区间
            if(in_array('deputy_manager_start',$params['level'])){
                $price_arr = $this->purchase_order_model->get_account_price('deputy_manager','deputy_manager');
                if(isset($price_arr['2']))$prices_fba[] = $price_arr['2'];
                if(isset($price_arr['5']))$prices_oversea[] = $price_arr['5'];
            }

            // 如果角色信息包含经理的角色,拥有 headman:组长,director:主管  deputy_manager:副经理,manager 经理 审核金额区间
            if(in_array('manager',$params['level'])){
                $price_arr = $this->purchase_order_model->get_account_price('manager','manager');
                if(isset($price_arr['2']))$prices_fba[] = $price_arr['2'];
                if(isset($price_arr['5']))$prices_oversea[] = $price_arr['5'];
            }

            // 如果是总监,拥有 headman:组长,director:主管  deputy_manager:副经理,manager 经理,majordomo 总监 get_account_price
            if(in_array('majordomo',$params['level'])){
                $price_arr = $this->purchase_order_model->get_account_price(NULL,'majordomo');
                if(isset($price_arr['2']))$prices_fba[] = $price_arr['2'];
                if(isset($price_arr['5']))$prices_oversea[] = $price_arr['5'];
            }
        }

        if(isset($params['level']) && !empty($params['level']) ) {
            $insideWhere .= " AND ( 1=1 AND ";
            if( is_array($prices_fba) && !empty($prices_fba) ) {
                $OC_FBA = PURCHASE_TYPE_OVERSEA.",".PURCHASE_TYPE_FBA_BIG;
                $no_OC_FBA = PURCHASE_TYPE_INLAND.",".PURCHASE_TYPE_FBA.",".PURCHASE_TYPE_PFB.",".PURCHASE_TYPE_PFH;
                foreach($prices_fba as $prices_key=>$prices_value){
                    if(isset($prices_value['start']) && isset($prices_value['end'])){
                        $insideWhere .= " ("
                            ."(type.real_price>=".$prices_oversea[$prices_key]['start']
                            ." AND type.real_price<".$prices_oversea[$prices_key]['end']
                            ." AND a.purchase_type_id in ({$OC_FBA})) "
                            ." OR "
                            ."(type.real_price>=".$prices_value['start']
                            ." AND type.real_price<".$prices_value['end']
                            ." AND a.purchase_type_id in ({$no_OC_FBA})) "
                            .") ";
                    }

                    if( isset($prices_value['end']) && !isset($prices_value['start'])){
                        $insideWhere .= " ("
                            ."(type.real_price>=".$prices_oversea[$prices_key]['end']
                            ." AND  a.purchase_type_id in ({$OC_FBA})) "
                            ."OR "
                            ."(type.real_price>=".$prices_value['end']
                            ." AND a.purchase_type_id in ({$no_OC_FBA})) "
                            .") ";
                    }

                    if($prices_key != count($prices_fba)-1)$insideWhere .= " OR ";
                }
            }
            $insideWhere .=" )";
        }

        if($is_export){
            $toffsets = $offsets;
            $tlimit = $toffsets+$limit;
            $insideWhere .=" AND b.id>={$toffsets} AND b.id<={$tlimit}";
        }

        // 待取消页签
        if($params['list_type'] == TIPS_WAIT_CANCEL){
            $ptid_gn = [
                PURCHASE_TYPE_INLAND,
                PURCHASE_TYPE_FBA,
                PURCHASE_TYPE_PFB,
                PURCHASE_TYPE_PFH
            ];
            $ptid_hw = [
                PURCHASE_TYPE_OVERSEA,
                PURCHASE_TYPE_FBA_BIG
            ];
            $w_status = PURCHASE_ORDER_STATUS_WAITING_ARRIVAL;
            $insideWhere .= " and ((
                a.purchase_type_id in (".implode(',', $ptid_gn).") 
                and d.suggest_order_status={$w_status} 
                and a.audit_time < '".date('Y-m-d 00:00:00',strtotime('-4 days'))."' 
                and (pli.status < 1 or pli.status is null)
                and (pli.carrier_code != '' or pli.carrier_code not in ('other'))
                and b.plan_arrive_time < '".date('Y-m-d 00:00:00',strtotime('+6 days'))."'
            ) or (
                a.purchase_type_id in (".implode(',', $ptid_hw).") 
                and d.suggest_order_status={$w_status} 
                and b.plan_arrive_time < '".date('Y-m-d 00:00:00',strtotime('+8 days'))."'
                and (
                    a.source=1 or 
                    (a.source=2 and (pli.status < 1 or pli.status is null))
                )
                and (pli.carrier_code != '' or pli.carrier_code not in ('other'))
            )) ";
        }

        // 待到货页签
        if($params['list_type'] == TIPS_WAITING_ARRIVE && SetAndNotEmpty($params, 'tab_tips_count', 'n')){
            switch ((int)$params['tab_tips_count']){
                case 2:
                    $day_2 = "'".date('Y-m-d',strtotime('-5 days'))." 00:00:00' and '".date('Y-m-d',strtotime('-3 days'))." 23:59:59'";
                    $insideWhere .= " and a.audit_time between ".$day_2;
                    break;
                case 5:
                    $day_5 = "'".date('Y-m-d',strtotime('-8 days'))." 00:00:00' and '".date('Y-m-d',strtotime('-6 days'))." 23:59:59'";
                    $insideWhere .= " and a.audit_time between ".$day_5;
                    break;
                case 8:
                    $day_8 = "'".date('Y-m-d',strtotime('-30 days'))." 00:00:00' and '".date('Y-m-d',strtotime('-9 days'))." 23:59:59'";
                    $insideWhere .= " and a.audit_time between ".$day_8;
                    break;
                case 30:
                    $day_30 = "'".date('Y-m-d',strtotime('-40 days'))." 00:00:00' and '".date('Y-m-d',strtotime('-31 days'))." 23:59:59'";
                    $insideWhere .= " and a.audit_time between ".$day_30;
                    break;
                case 40:
                    $day_40 = "'".date('Y-m-d',strtotime('-60 days'))." 00:00:00' and '".date('Y-m-d',strtotime('-41 days'))." 23:59:59'";
                    $insideWhere .= " and a.audit_time between ".$day_40;
                    break;
                case 60:
                    $day_60 = "'".date('Y-m-d',strtotime('-60 days'))." 00:00:00'";
                    $insideWhere .= " and a.audit_time < ".$day_60;
                    break;
            }
        }

        // 37174 将创建时间锁定在当天7点之前
        if($params['list_type'] == TIPS_TODAY_WORK){
            $this_date = date("Y-m-d");
            $create_date = $this_date." 07:00:00";
            $insideSql .= " left join pur_purchase_order_remark as ork on a.purchase_number=ork.purchase_number and b.id=ork.items_id and ork.items_id != 0";
            $work_suggest_status = [
                PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER,
                PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,
                PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND
            ];
            $insideWhere .= " and a.create_time <'{$create_date}' and d.suggest_order_status in (".implode(',', $work_suggest_status).")";
            $insideWhere .= " and b.today_work='{$this_date}'";
            $insideWhere .= " and (pli.`status` =0 or pli.`status` is null)";
            $insideWhere .= " and (ork.items_id =0 or ork.items_id is null)";
            $insideWhere .= " AND a.purchase_type_id NOT IN (".PURCHASE_TYPE_OVERSEA.','.PURCHASE_TYPE_FBA_BIG.")";
        }

        // 降价中
        if(SetAndNotEmpty($params, 'price_reduction') && in_array($params['price_reduction'], [1, 2])){
            $insideSql .= ' LEFT JOIN pur_product_update_log AS u_log ON b.sku=u_log.sku and u_log.audit_status not in (3, 4)';
            if($params['price_reduction'] == 1){
                $insideWhere .= " and u_log.old_supplier_price > u_log.new_supplier_price";
            }elseif($params['price_reduction'] == 2){
                $insideWhere .= " and u_log.old_supplier_price <= u_log.new_supplier_price or u_log.id is null";
            }
        }

        // 采购单状态 放在各个页签之后
        if (SetAndNotEmpty($params, 'purchase_order_status')) {
            $order_status = $params['purchase_order_status'];
            if (is_array($order_status)) $order_status = implode(",", $order_status);
            $insideWhere .= " AND  a.purchase_order_status IN (".$order_status.")";
        }

        $insideWhere .= "  GROUP BY b.id ";
        $insideWhere .= " ORDER BY b.id DESC ";

        $select_field = $this->select_field();
        if($is_sum)$select_field = $this->sum_field();
        $sql = "
             SELECT
             {$select_field}
            FROM
              `pur_purchase_order_items` AS `ppoi`
              INNER JOIN `pur_purchase_order` AS `ppo` ON `ppoi`.`purchase_number` = `ppo`.`purchase_number`
              INNER JOIN `pur_purchase_suggest` AS `sg` ON `ppoi`.`demand_number` = `sg`.`demand_number` 
              INNER JOIN `pur_product` AS `pro` ON `pro`.`sku` = `ppoi`.`sku`
              LEFT JOIN `pur_purchase_logistics_info` AS `pli` ON `pli`.`purchase_number` = `ppoi`.`purchase_number` AND `ppoi`.sku=`pli`.sku
              LEFT JOIN `pur_purchase_order_pay_type` AS `ppy` ON `ppy`.`purchase_number` = `ppo`.`purchase_number`
              LEFT JOIN `pur_purchase_product_invoice` AS `ice` ON `ice`.`items_id` = `ppoi`.`id`
              LEFT JOIN `pur_warehouse_results_main` AS `ware` ON `ware`.`purchase_number` = `ppoi`.`purchase_number` AND `ware`.`sku` = `ppoi`.`sku`
              LEFT JOIN `pur_purchase_order_reportloss` AS `loss` ON `ppoi`.`purchase_number` = `loss`.`pur_number` AND `ppoi`.`sku` = `loss`.`sku`
              LEFT JOIN `pur_supplier` AS `sp` ON `sp`.`supplier_code` = `ppo`.`supplier_code`
              LEFT JOIN `pur_purchase_order_pay` AS `pop` ON `ppoi`.`purchase_number` = `pop`.`pur_number`
              LEFT JOIN `pur_purchase_statement_summary` AS `sta` ON `sta`.`purchase_number` = `ppoi`.`purchase_number` 
              LEFT JOIN `pur_supplier_web_audit` AS `swa` ON `swa`.`purchase_number` = `ppoi`.`purchase_number` AND `ppoi`.`sku`=`swa`.`sku`
            WHERE
              ppoi.id IN (
            ";
        $sql .= $insideSql.$insideWhere.") AS orderdata )  ";
        // 缓存查询SQL，便于执行其他操作，如1688批量刷新
        $this->rediss->setData(md5(getActiveUserId().'-new_get_list'),base64_encode($insideSql.$insideWhere.") AS orderdata"));

        if(!empty($params['is_inspection'])) $sql .= " AND pro.is_inspection<>0  AND pro.is_inspection=".$params['is_inspection'];
        if(!empty($params['free_shipping'])) $sql .= " AND sp.is_postage =".$params['free_shipping'];
        if(SetAndNotEmpty($params, 'supply_status'))$sql .= " AND pro.supply_status IN (".implode(",",$params['supply_status']).")";
        if(SetAndNotEmpty($params, 'demand_number', 'ar')) $sql .= " AND ppoi.demand_number IN (".implode(",",$params['demand_number']).")";
        if(SetAndNotEmpty($params, 'supplier_source')) $sql .= " AND sg.supplier_source =".$params['supplier_source'];
        if(SetAndNotEmpty($params, 'gateway_status')) $sql .= " AND ppo.gateway_status IN (".implode(",",$params['gateway_status']).")";
        if(SetAndNotEmpty($params, 'tap_date_str', 'ar')) $sql .= " AND sp.tap_date_str in ('".implode("','", $params['tap_date_str'])."')";
        if(SetAndNotEmpty($params, 'entities_lock_status')) $sql .= " AND sg.lock_type = ".($params['entities_lock_status']==2 ? LOCK_SUGGEST_ENTITIES: 0);

        if(isset($outsideSql) && $outsideSql) $sql .= $outsideSql;

        // 入库时间
        if(SetAndNotEmpty($params, 'instock_date') && count($params['instock_date']) == 2){
            $instock_date_start = date('Y-m-d 00:00:00',strtotime($params['instock_date'][0]));
            $instock_date_end = date('Y-m-d 00:00:00',strtotime($params['instock_date'][1]));
            $sql .= " AND ware.instock_date between '".$instock_date_start."' AND '".$instock_date_end."' ";
        }


        $sql .= " GROUP BY  `ppoi`.`id`";
        if(!SetAndNotEmpty($params, "order_by")){
            $sql .= " ORDER BY `ppoi`.`id` DESC ";
        }else{
            $order_by = [
                "id_asc" => "ppoi.id ASC",
                "id_desc" => "ppoi.id DESC",
                "supplier_code_asc" => "sp.supplier_code ASC",
                "supplier_code_desc" => "sp.supplier_code DESC",
                "purchase_price_asc" => "ppy.real_price ASC",
                "purchase_price_desc" => "ppy.real_price DESC",
                "confirm_amount_asc" => "ppoi.confirm_amount ASC",
                "confirm_amount_desc" => "ppoi.confirm_amount DESC",
            ];
            $order_by_temp = [];
            foreach ($params['order_by'] as $ob_val){
                if(isset($order_by[$ob_val]))$order_by_temp[] = $order_by[$ob_val];
            }
            if(count($order_by_temp) > 0)$sql .= " ORDER BY ".implode(",", $order_by_temp);
        }
        if(!$is_export && !$is_sum) {
            $sql .= " LIMIT {$offsets},{$limit}";
        }

        if($is_sum){
            $sql = "SELECT
                count(distinct sum_sql.sku ) AS sku_all,
                count( sum_sql.id ) AS total_all,
                sum( sum_sql.confirm_amount ) AS purchase_amount_all,
                count(distinct sum_sql.supplier_code ) AS supplier_code_all,
                sum( sum_sql.purchase_unit_price * sum_sql.confirm_amount ) AS purchase_unit_price_all,
                count(distinct sum_sql.purchase_number ) AS purchase_number_all 
            FROM ({$sql}) as sum_sql where 1=1";
        }
        $results = $query_builder->query($sql)->result_array();
//        exit($query_builder->last_query());
        if($is_sum){
            return $results;
        }

        if(True == $is_export && empty($results)){
            return NULL;
        }

        // 统一查询需要转换的字段，避免循环嵌套查询
        $warehouse_codes = is_array($results) ? array_column($results, "warehouse_code") : [];
        $transfer_warehouse_codes = is_array($results) ? array_column($results, "transfer_warehouse") : [];
        $warehouse_codes = array_unique(array_merge($warehouse_codes, $transfer_warehouse_codes));
        $warehouse_code_list = $this->warehouse_model->get_code2name_list($warehouse_codes);
        $pertain_wms_list = $this->warehouse_model->get_pertain_wms_list();
        $pertain_wms_list = array_column($pertain_wms_list,'pertain_wms_name','pertain_wms_code');

        $this->load->model("supplier/Supplier_settlement_model");
        $settlement_code_list = $this->Supplier_settlement_model->get_code_by_name_list();

        //入库金额 amount_storage
        $ids = is_array($results) ? array_column($results, "id") : [];

        $amount_storage_list = $this->purchase_order_model->get_amount_storage_list($ids);
        $cancel_ctq_data = $this->purchase_order_model->count_arrival_total_price($ids);
        //amount_paid
        $pur_nums = is_array($results) ? array_column($results, "purchase_number") : [];
        $amount_paid_list = $this->purchase_order_model->get_list_by_purchase_numbers(array_unique(array_filter($pur_nums)));
        //获取合同号集集合
        $compact_numbe_list=$this->purchase_order_model->get_compact_number_list($pur_nums);

        //获取请款单号集合
        $requisition_number_list=$this->purchase_order_model->get_requisition_number_list($pur_nums);
        $category_requisition_number_list = $this->purchase_order_model->get_pay_category_requisition_number_list($pur_nums);
        //获取取消未到货状态集合
        $items_id = is_array($results) ? array_column($results, "id") : [];
        $items_id_id=$this->purchase_order_model->get_cancel_id($items_id);

        //增加汇总信息
        $skus = array_column($results, 'sku');
        $skus_num = count(array_unique($skus));//当前页SKU数量
        $purchase_amount_num = 0;//当前页PCS数(采购数量)
        $purchase_total_price_all = 0.00;//当前页订单总金额
        $purchase_number_total = count(array_unique($pur_nums)); //当前页PO数
        //当前页供应商数
        $supplier_codes = is_array($results) ? array_column($results, "supplier_name") : [];
        $supplier_code_total = count(array_unique($supplier_codes)); //当前页供应商数

        //查开票品名
        $product_field = 'sku,declare_cname,declare_unit,export_cname,tax_rate';
        $sku_arr = $this->product_model->get_list_by_sku(array_unique($skus),$product_field);
        //获取出口海关编码集合
        $sku_list= is_array($results) ? array_column($results, "sku") : [];
        $customs_code_list=$this->purchase_order_model->get_customs_code($sku_list);

        $this->load->model('warehouse/Logistics_type_model');
        $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
        $logistics_type_list = array_column($logistics_type_list,'type_name','type_code');

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');
        $this->load->model('ali/Ali_order_model');

        // 异常数据
        $warehouse_abnormal = [];
        $price_value = $this->purchase_db->from("audit_amount")->where_in("id",[2,5])->get()->result_array();
        $price_value_arr = array_column($price_value,null,'id');
        $purchaseWarehouseData = $remark_list = [];

        if(!empty($results)) {
            $purchaseSkusData = array_map(function ($data) {
                return ['purchase_number' => $data['purchase_number'],
                    'sku' => $data['sku'],
                    'id' => $data['id'],
                ];
            }, $results);
            $purchaseSkusDataNumber = array_column($purchaseSkusData,"purchase_number");
            $purchaseSkus = array_column($purchaseSkusData,"sku");
            $warehouseDatas = $this->purchase_db->from("warehouse_results")->where_in("purchase_number",$purchaseSkusDataNumber)
                ->where_in("sku",$purchaseSkus)->select("MIN(id) AS id,purchase_number,sku,instock_date")->group_by("purchase_number,sku")->get()->result_array();

            if (!empty($warehouseDatas)) {
                foreach ($warehouseDatas as $wareKey => $wareValue) {
                    $purchaseWarehouseData[$wareValue['purchase_number'] . "." . $wareValue['sku']] = $wareValue['instock_date'];
                }
            }
            $abnormal_list_type = [
                TIPS_ALL_ORDER,
                TIPS_WAITING_ARRIVE,
                TIPS_ORDER_FINISH,
                TIPS_TODAY_WORK,
                TIPS_WAIT_CANCEL,
            ];
            if(in_array($params['list_type'], $abnormal_list_type))$warehouse_abnormal = $this->get_purchase_abnomal($purchaseSkusDataNumber);

            // 获取备注
            $ids_list = array_column($purchaseSkusData,"id");
            $remark_temp = $this->purchase_db->from("purchase_order_remark")->where_in("items_id",$ids_list)->order_by("id desc")->get()->result_array();
            $remark_items = [];
            $remark_images = [];
            if($remark_temp && count($remark_temp) > 0){
                foreach ($remark_temp as $val){
                    $v_id = $val['items_id'];
                    $remark_list[$val['purchase_number']] = $val['remark'];
                    $remark_items[$v_id] = $val['remark'];
                    if(!isset($remark_images[$v_id]))$remark_images[$v_id] = [];
                    if(count($remark_images[$v_id]) < 5)$remark_images[$v_id][] = $val['images'];
                }
            }
        }

        $refund_data = $this->purchase_order_model->get_ali_order_refund_data(array_column($results, "pai_number"));

        // 获取物流信息
        $cc_pur_number = array_column($results, "purchase_number");
        $cc_sku = array_column($results, "sku");
        $cargo_list = [];
        if(!empty($cc_pur_number) && !empty($cc_sku)){
            $cargo_companys = $this->purchase_db->from("purchase_logistics_info")
                ->select("purchase_number,sku,cargo_company_id,carrier_code,express_no,is_manually,status AS track_status")
                ->where_in("purchase_number", $cc_pur_number)
                ->where_in("sku",$cc_sku)
                ->get()->result_array();
            if($cargo_companys && !empty($cargo_companys)){
                foreach ($cargo_companys as $val){
                    $c_row = $val["purchase_number"]."_".$val['sku'];
                    if(!isset($cargo_list[$c_row]))$cargo_list[$c_row] = [];
                    $cargo_list[$c_row][] = $val;
                }
            }
        }
        $abnormal_list = $all_sku = $all_id = $deduction = $supplier_list = [];  // PO维度抵扣项目
        // 针对po维度预处理
        foreach($results as $vo){
            $all_sku[] = $vo['sku'];
            $all_id[] = $vo['id'];
            $pur_number = $vo['purchase_number'];
            $supplier_list[] = $vo['supplier_code'];
            if(!isset($deduction[$pur_number]))$deduction[$pur_number] = 0;
            $deduction[$pur_number] += $vo['ca_process_cost'];
            if(!empty($warehouse_abnormal)){
                foreach ($warehouse_abnormal as $val_wa){
                    if($pur_number == $val_wa['pur_number'] &&
                        $vo['sku'] == $val_wa['sku'] &&
                        in_array($val_wa['is_handler'], [0,2])){
                        $abnormal_list[] = $pur_number;
                    }
                }
            }
        }
        // sku 屏蔽
        $sku_scree = $this->get_sku_scree($all_sku);
        // 上次采购价格
        $LastPurPrice = $this->getSkuLastPurchasePrice($all_sku);
        // 获取已付费用
        $payend_price = $this->get_pay_other_price($cc_pur_number);
        // 获取阿里旺旺
        $WangwangList = count($supplier_list) > 0 ? $this->WangwangList($supplier_list) : [];
        // 取消次数
        $cancel_time = $this->get_cancel_time($all_sku);
        // 获取SKU 是否降价
        $sku_product_logs = $this->get_sku_log($all_sku);

        foreach ($results as $key => &$vo) {
            $payend_price_row = isset($payend_price[$vo['purchase_number']]) ? $payend_price[$vo['purchase_number']]: ["paid_freight" => 0,"paid_discount" => 0,"paid_process_cost" => 0];
            $vo['freight_pay'] = $payend_price_row['paid_freight'];
            $vo['discount_pay'] = $payend_price_row['paid_discount'];
            $vo['process_cost_pay'] = $payend_price_row['paid_process_cost'];
            $other_devliery_status = $vo['is_overdue'];
            $vo['devliery_status'] = $vo['devliery_status'] == 1 || $vo['plan_arrive_time'] =='0000-00-00 00:00:00'?"否":"是";
            if( isset($vo['supply_status'])){
                //货源状态(1.正常,2.停产,3.断货)
                if( $vo['supply_status'] == 1)$vo['supply_status_ch'] = "正常";
                if( $vo['supply_status'] == 2)$vo['supply_status_ch'] = "停产";
                if( $vo['supply_status'] == 3)$vo['supply_status_ch'] = "断货";
                if( $vo['supply_status'] == 10)$vo['supply_status_ch'] = "停产找货中";
            }
            $vo['is_down_price'] =0;

            if( in_array($vo['sku'],$sku_product_logs)){

                $vo['is_down_price'] =1;
            }
            if(empty($vo['devliery_days'])) $vo['devliery_days'] = 0;
            if($vo['new_devliy'] == 0)$vo['new_devliy'] = '-';
            if($vo['plan_arrive_time'] == '0000-00-00 00:00:00')$vo['overdue_days'] = 0;

            if($vo['need_pay_time_item'] != '0000-00-00') $vo['need_pay_time'] = $vo['need_pay_time_item'];// 显示采购单明细的
            if($vo['plan_arrive_time'] != '0000-00-00 00:00:00') {
                if($vo['instock_date'] !='0000-00-00 00:00:00' || !empty($vo['instock_date'])){
                    $vo['overdue_day'] = strtotime($vo['instock_date']) - strtotime($vo['plan_arrive_time']) < 0?"否":"是";
                }else {
                    $vo['overdue_day'] = time() - strtotime($vo['plan_arrive_time']) < 0 ? "否" : "是";
                }
                if( time()-strtotime($vo['plan_arrive_time']) <0){
                    $vo['overdue_day_data'] = NULL;
                }else{
                    $adays = $this->purchase_order_model->timediff(time(),strtotime($vo['plan_arrive_time']));
                    $vo['overdue_day_data'] = $adays['day'];
                }
            }
            if( (empty($vo['instock_date']) && $thisTime > $vo['plan_arrive_time']) || (!empty($vo['instock_date']) && $vo['instock_date'] <= $vo['plan_arrive_time']) || $vo['plan_arrive_time'] == '0000-00-00 00:00:00'){
                $vo['overdue_day'] = "否";
                $vo['overdue_day_data'] = NULL;
            }
            $vo['track_status_ch'] = !empty($vo['track_status'])?getTrackStatus($vo['track_status']):'';
            $vo['is_customized'] = $vo['is_customized'] == 1? "是":"否";
            $vo['sku_state_type_ch'] = $vo['sku_state_type'] != 6?'否': '是';
            $vo['is_gateway_ch'] = $vo['is_gateway'] == 1 ? "是":"否";

            if( $vo['is_drawback'] == 1)$vo['coupon_rate_price'] = sprintf("%.2f",$vo['purchase_unit_price'] / (1+$vo['coupon_rate']));
            if( $vo['maintain_ticketed_point'] == 0 && $vo['pur_ticketed_point']==0.000 )$vo['pur_ticketed_point'] = NULL;

            if( $vo['purchase_order_status'] == 3) {
                $price_value = in_array($vo['purchase_type_id'], [PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])?$price_value_arr[5]:$price_value_arr[2];
                if (!empty($price_value)) {
                    if (isset($price_value['headman_start']) && $price_value['headman_start'] <= $vo['real_price'] && $price_value['headman_end'] >=$vo['real_price']) $vo['amount_name'] = "组长";
                    if (isset($price_value['director_start']) && $price_value['director_start'] <= $vo['real_price'] && $price_value['director_end'] >= $vo['real_price']) $vo['amount_name'] = "主管";
                    if (isset($price_value['manager_start']) && $price_value['manager_start'] <= $vo['real_price'] && $price_value['manager_end'] >= $vo['real_price'])$vo['amount_name'] = "经理";
                    if (isset($price_value['deputy_manager_start']) && $price_value['deputy_manager_start'] <=$vo['real_price'] && $price_value['deputy_manager_end'] >= $vo['real_price'])$vo['amount_name'] = "副经理";
                    if (isset($price_value['majordomo']) && $price_value['majordomo'] <= $vo['real_price']) $vo['amount_name'] = "总监";
                }
            }

            if( empty($vo['is_invalid']) || $vo['is_invalid'] ==0 ) {
                $vo['is_invalid'] = "是";
            }else if( $vo['is_invalid'] == 1 ) {
                $vo['is_invalid'] = "否";
            }
            if( !empty($vo['purchase_packaging']) ) {
                $search_start_index = strpos($vo['purchase_packaging'],"[");
                $packaging_string = substr_replace($vo['purchase_packaging']," ",$search_start_index);
                $packaging_string_new = str_replace(" ","",trim($packaging_string));
                $vo['purchase_packaging'] = $vo['purchase_type_id'] == 2 &&  strcmp($packaging_string_new,"Q2:PE袋包装") ===0?"白色快递袋": $packaging_string;
            }

            $vo['is_scree'] = in_array($vo['sku'], $sku_scree) ? 1: 0;//$this->get_sku_scree($vo['sku']);

            $vo['is_long_delivery_ch'] = '';
            if( $vo['is_long_delivery'] == 1){
                //是否为超长交期 1表示 否，2表示是
                $vo['is_long_delivery_ch'] = "否";
            }
            if( $vo['is_long_delivery'] == 2){
                $vo['is_long_delivery_ch'] = '是';
            }
            //是否退税
            if( isset($vo['is_drawback']) && !empty($vo['is_drawback']) ){
                $is_drawback =  $vo['is_drawback'];
            }else{
                $is_drawback =  isset($vo['sg_is_drawback']) ? $vo['sg_is_drawback'] :'';
            }
            //采购主体
            if( isset($vo['sg_purchase_name']) && !empty($vo['sg_purchase_name']) ){
                $purchase_name =  $vo['sg_purchase_name'];
            }else{
                $purchase_name = isset($vo['purchase_name']) ? $vo['purchase_name'] :'';
            }

            //结算方式
            if( isset($vo['account_type']) && !empty($vo['account_type']) ){
                $account_type =  $vo['account_type'];
            }else{
                $account_type = isset($vo['sg_account_type']) ? $vo['sg_account_type'] :'';
            }

            //支付方式
            if( isset($vo['pay_type']) && !empty($vo['pay_type']) ){
                $pay_type =  $vo['pay_type'];
            }else{
                $pay_type = isset($vo['sg_pay_type']) ? $vo['sg_pay_type'] :'';
            }

            if( isset($vo['supplier_source']) ) {
                if( $vo['supplier_source'] == 1) {
                    $vo['supplier_source_ch'] = "常规";
                }else if( $vo['supplier_source'] == 2) {
                    $vo['supplier_source_ch'] = "海外";
                }else if( $vo['supplier_source'] == 3) {
                    $vo['supplier_source_ch'] = "临时";
                }
            }
            //验货状态中文转换
            if (!is_null($vo['check_status'])) $vo['check_status_cn'] = getCheckStatus($vo['check_status']);
            if( empty($vo['supplier_source']) )  $vo['supplier_source_ch'] = "未知";

            $vo['supplier_source'] = $vo['supplier_source_ch'];

            //采购员
            if( isset($vo['buyer_name']) && !empty($vo['buyer_name']) ){
                $results[$key]['buyer_name'] =  $vo['buyer_name'];
            }else{
                $results[$key]['buyer_name'] =  isset($vo['sg_buyer_name']) ? $vo['sg_buyer_name'] :'';
            }
            $results[$key]['is_purchasing'] = $vo['is_purchasing']==1?'否':'是';
            $results[$key]['order_num'] = $vo['real_supplier_source'] == 3?$vo['order_num']:'';
            $results[$key]['barcode_pdf'] = !empty($vo['barcode_pdf'])? '是':'否';
            $results[$key]['label_pdf'] = !empty($vo['label_pdf'])?'是':'否';

            //供应商
            if( isset($vo['supplier_name']) && !empty($vo['supplier_name']) ){
                $results[$key]['supplier_name'] = $vo['supplier_name'];
                $results[$key]['supplier_code'] = $vo['supplier_code'];
            }else{
                $results[$key]['supplier_name'] =  isset($vo['sg_supplier_name']) ? $vo['sg_supplier_name'] :'';
                $results[$key]['supplier_code'] =  isset($vo['sg_supplier_code']) ? $vo['sg_supplier_code'] :'';
            }

            if( isset($vo['is_expedited']) ) {
                if( $vo['is_expedited'] == 1) {
                    $vo['is_expedited_ch'] = "否";
                }else if( $vo['is_expedited'] == 2) {
                    $vo['is_expedited_ch'] = "是";
                }
            }
            //交期确认状态
            if( isset($vo['audit_time_status']) ) {
                if( $vo['audit_time_status'] == 1) {
                    $vo['audit_time_status'] = "待确认";
                }else if( $vo['audit_time_status'] == 2) {
                    $vo['audit_time_status'] = "已确认";
                }else if( $vo['audit_time_status'] == 3) {
                    $vo['audit_time_status'] = "采购驳回";
                }
            }

            if( isset($vo['plan_arrive_time']) && !empty($vo['plan_arrive_time']))$results[$key]['plan_arrive_time'] = date("Y-m-d",strtotime($vo['plan_arrive_time']));
            if( isset($vo['first_plan_arrive_time']) && !empty($vo['first_plan_arrive_time']) && $vo['first_plan_arrive_time']!='0000-00-00 00:00:00')$results[$key]['first_plan_arrive_time'] = date("Y-m-d",strtotime($vo['first_plan_arrive_time']));

            $results[$key]['audit_time_status'] = !empty($vo['audit_time_status'])?$vo['audit_time_status'] : '';
            //判断线上账期付款提醒状态
            $productStatus = $this->purchase_order_model->get_productStatus($vo['product_status']);
            $results[$key]['product_status'] = ( NULL != $productStatus )?$productStatus:"未知";
            $results[$key]['pay_notice'] = formatAccoutPeriodTime($results[$key]['account_type'], $results[$key]['pay_status'], $results[$key]['need_pay_time'], $results[$key]['surplus_quota']);
            $results[$key]['purchase_order_status_name'] = isset($vo['purchase_order_status'])?getPurchaseStatus($vo['purchase_order_status']):'';
            $results[$key]['suggest_order_status_name'] = isset($vo['suggest_order_status'])?getPurchaseStatus($vo['suggest_order_status']):'';
            $results[$key]['source_name'] = isset($vo['source'])?getPurchaseSource($vo['source']):'';//采购单来源
            $results[$key]['is_new'] = isset($vo['is_new_flag']) && $vo['is_new_flag'] == 1 ? '是' : '否';//是否新品
            $results[$key]['is_overseas_first_order'] = isset($vo['is_overseas_first_order'])? getProductIsNew($vo['is_overseas_first_order']):'';//海外仓首单
            $results[$key]['is_drawback'] = isset($is_drawback)?getIsDrawbackShow($is_drawback):'';//是否退税
            $results[$key]['is_expedited'] = isset($vo['is_expedited'])?getIsExpedited($vo['is_expedited']):'';//采购单加急
            $results[$key]['is_ali_order'] =getIsAliOrder(isset($vo['is_ali_order'])?$vo['is_ali_order']:0);
            $results[$key]['is_ali_price_abnormal'] =getIsAliPriceAbnormal(isset($vo['is_ali_price_abnormal'])?$vo['is_ali_price_abnormal']:0);
            $results[$key]['remark'] = $vo['ali_order_status'];
            $is_inspection = (isset($vo['is_inspection']) && $vo['is_inspection'] == 1)?"否":"是";
            if((isset($vo['is_inspection']) && $vo['is_inspection'] == 0)) $is_inspection="未知";

            $results[$key]['is_inspection'] = $is_inspection;
            $results[$key]['warehouse_code'] = isset($warehouse_code_list[$vo['warehouse_code']])?$warehouse_code_list[$vo['warehouse_code']]:'';
            $results[$key]['pertain_wms_name'] = isset($pertain_wms_list[$vo['pertain_wms']])?$pertain_wms_list[$vo['pertain_wms']]:'';
            $results[$key]['is_destroy'] = in_array($vo['purchase_order_status'],[PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,PURCHASE_ORDER_STATUS_CANCELED])?getDestroy($vo['is_destroy']): "";
            $results[$key]['account_type'] = isset($settlement_code_list[$account_type])?$settlement_code_list[$account_type]:'';
            $results[$key]['purchase_price'] = round($vo['purchase_unit_price'] * $vo['confirm_amount'],3);
            $results[$key]['purchase_name'] = !empty($purchase_name) ? get_purchase_agent($purchase_name) : '';
            $results[$key]['pay_status'] = $vo['pay_status'];
            $results[$key]['pay_status_name'] = isset($vo['pay_status'])?getPayStatus($vo['pay_status']):"";
            $results[$key]['pay_finish_status'] = getPayFinishStatus($vo['pay_finish_status']);
            $results[$key]['pull_gateway_status_ch'] = isset($vo['gateway_status'])?getPullGateWayStatus($vo['gateway_status']):"";
            if ($results[$key]['pay_status'] == 95) {//无需付款需要查询类型
                $no_pay_arr =[11=>'赞助',12=>'其他',13=>'多货入库'];
                $no_pay_info = $this->purchase_db
                    ->select('reject_type_id,reject_remark')
                    ->where('link_code',$results[$key]['purchase_number'])
                    ->where_in('reject_type_id',[11,12,13])
                    ->get('pur_reject_note')->row_array();
                $npa = isset($no_pay_info['reject_type_id']) && isset($no_pay_arr[$no_pay_info['reject_type_id']]) ? $no_pay_arr[$no_pay_info['reject_type_id']]: '';
                $rj = isset($no_pay_info['reject_remark']) ? $no_pay_info['reject_remark']:'';

                $results[$key]['pay_status_name'] .="\n".$npa;
                $results[$key]['pay_status_name'] = $results[$key]['pay_status_name']."\n".$rj;
                $results[$key]['pay_status_name'] =trim($results[$key]['pay_status_name'],"\n");
            }
//            $results[$key]['logistics_trajectory'] = '接口获取1688物流信息';
            $order_item = isset($amount_storage_list[$vo['id']]) ? $amount_storage_list[$vo['id']] : [];
            $results[$key]['amount_storage'] = $this->purchase_order_model->count_amount_storage($order_item);
            $amount_paid_key = $vo['purchase_number'] . "_" . $vo['sku'];
            $results[$key]['amount_paid'] = isset($amount_paid_list[$amount_paid_key])?sprintf("%.3f",$amount_paid_list[$amount_paid_key]):0;
            $results[$key]['amount_paid'] += $vo['ca_product_money'] + $vo['ca_process_cost'];
            $results[$key]['ca_process_cost'] = isset($deduction[$vo['purchase_number']])? $deduction[$vo['purchase_number']]: $vo['ca_process_cost'];

            $results[$key]['is_overdue'] = $vo['is_overdue'] == 1?'是':'否';

            $results[$key]['plan_arrive_time'] = strtotime($vo['plan_arrive_time']) > 0 ? $vo['plan_arrive_time'] : $thisTime;
            //$results[$key]['breakage_qty'] = isset($qty['breakage_qty']) ? $qty['breakage_qty'] : 0;
            $cancel_item = isset($cancel_ctq_data[$vo['id']]) ? $cancel_ctq_data[$vo['id']] : [];
            $results[$key]['cancel_ctq'] = !empty($cancel_item) ? $cancel_item['cancel_ctq'] : 0;
            $results[$key]['item_total_price'] = !empty($cancel_item) ? sprintf("%.3f",$cancel_item['item_total_price']) : 0;//取消金额
            $results[$key]['compact_number']= isset($compact_numbe_list[$vo['purchase_number']])?$compact_numbe_list[$vo['purchase_number']]:''; //合同号
            $results[$key]['requisition_number']= isset($requisition_number_list[$amount_paid_key])?$requisition_number_list[$amount_paid_key]:''; //请款单号
            $results[$key]['loss_status']=isset($vo['loss_status'])?getReportlossApprovalStatus($vo['loss_status']):'未申请报损'; //报损状态
            $results[$key]['audit_status']= isset($items_id_id[$vo['id']])?get_cancel_status($items_id_id[$vo['id']]):'未申请取消未到货'; //取消未到货状态
            $purchase_amount_num += $vo['confirm_amount'];
            $purchase_total_price_all += $vo['purchase_unit_price'] * $vo['confirm_amount'];//采购总金额
            $results[$key]['pay_type'] = isset($pay_type)?getPayType($pay_type):""; //支付方式
            $results[$key]['shipping_method_id']= isset($vo['shipping_method_id'])?getShippingMethod($vo['shipping_method_id']):''; //供应商运输
            $results[$key]['shipment_type']= isset($vo['shipment_type'])?getShipmentType($vo['shipment_type']):''; //发运类型
            $results[$key]['is_freight']= isset($vo['is_freight'])? getFreightPayment($vo['is_freight']):''; //运费转化
            $results[$key]['freight_formula_mode']= isset($vo['freight_formula_mode'])? freight_formula_mode($vo['freight_formula_mode']):''; //运费计算方式

            //报损数量审核通过才显示 其他状态默认为0
            $results[$key]['loss_amount'] = $vo['loss_status']=='已通过'?$vo['loss_amount']:0;
            $results[$key]['demand_purchase_type_id'] = !empty($vo['demand_purchase_type_id'])?getPurchaseType($vo['demand_purchase_type_id']):"";
            $results[$key]['is_fumigation'] = '';
            $results[$key]['cancel_time'] = isset($cancel_time[$vo['sku']]) ? $cancel_time[$vo['sku']]: 0; // 取消次数
            if($vo['extra_handle'] == 1){
                $results[$key]['is_fumigation'] = '熏蒸';
            }elseif($vo['extra_handle'] == 1){
                $results[$key]['is_fumigation'] = '不熏蒸';
            };

            // 获取物流信息
            $source_status = $vo['source'] == 2?1:0;
            $results[$key]['logistics_info'] = [];
            $res_number = $vo['purchase_number']."_".$vo['sku'];
            if(isset($cargo_list[$res_number])){
                foreach ($cargo_list[$res_number] as $crVal){
                    $results[$key]['logistics_info'][] = [
                        'cargo_company_id' => $crVal['cargo_company_id'],
                        'express_no'       => $crVal['express_no'],
                        'status' => $source_status,
                        'carrier_code' => $crVal['carrier_code'],
                        'is_manually' => $crVal['is_manually'],
                        'logistics_status' => $crVal['track_status'],
                        'logistics_status_cn' => getTrackStatus($crVal['track_status']),
                    ];
                }
            }
            //一级产品线
            $results[$key]['first_product_line'] = $vo['product_line_name'];

            $results[$key]['is_relate_ali'] = !empty($vo['is_relate_ali'])?$vo['is_relate_ali']:0;
            //开票品名、开票单位
            if(in_array($vo['purchase_order_status'], [
                PURCHASE_ORDER_STATUS_WAITING_QUOTE,
                PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,
                PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT,
                PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER
            ])){
                $results[$key]['invoice_name'] = isset($sku_arr[$vo['sku']])?$sku_arr[$vo['sku']]['export_cname']:'';
                $results[$key]['issuing_office'] = isset($sku_arr[$vo['sku']])?$sku_arr[$vo['sku']]['declare_unit']:'';
            }
            //已开票金额 已开票数量*含税单价
            $results[$key]['invoiced_amount'] = bcmul($vo['invoices_issued'],$vo['purchase_unit_price']);

            $results[$key]['export_tax_rebate_rate'] = (isset($sku_arr[$vo['sku']]) && !empty( $sku_arr[$vo['sku']]['tax_rate'] ))?$sku_arr[$vo['sku']]['tax_rate']:'';
            $results[$key]['customs_code'] = isset($customs_code_list[$vo['sku']])?$customs_code_list[$vo['sku']]:'';
            unset($results[$key]['surplus_quota']);
            $results[$key]['modify_remark'] = '点击查看其他备注';

            $results[$key]['destination_warehouse'] = isset($warehouse_list[$vo['destination_warehouse']])?$warehouse_list[$vo['destination_warehouse']]:'';
            $results[$key]['logistics_type'] = isset($logistics_type_list[$vo['logistics_type']])?$logistics_type_list[$vo['logistics_type']]:'' ;
            $results[$key]['state_type'] = getProductStateType((int)$vo['state_type']);
            $results[$key]['last_purchase_price'] = isset($LastPurPrice[$vo['sku']])? $LastPurPrice[$vo['sku']]: 0;
            $results[$key]['lack_quantity_status']     = intval($results[$key]['lack_quantity_status'])==1 ?'是':'否';//是否欠货
            $results[$key]['is_entities_lock']      = ($vo['lock_type'] == LOCK_SUGGEST_ENTITIES) ? '是' : '否';
            $results[$key]['supplier_status']      = ($vo['supplier_status'] == IS_DISABLE) ? '禁用' : '未禁用';
            $results[$key]['product_img_url'] = erp_sku_img_sku($vo['product_img_url']);
            $results[$key]['product_img_url_thumbnails'] = $vo['product_thumb_url']?erp_sku_img_sku_thumbnail($vo['product_thumb_url']):erp_sku_img_sku($vo['product_img_url']);
            $results[$key]['link_me'] = isset($WangwangList[$vo['supplier_code']]) ? $WangwangList[$vo['supplier_code']]:"";

            preg_match("/offer\/[\w]+\.html/",$vo['product_cn_link'],$pdt_tongkuan);
            $results[$key]['pdt_tongkuan'] = !empty($pdt_tongkuan)?$vo['product_cn_link']:'';
            $results[$key]['pay_category_number']= $category_requisition_number_list;
            $results[$key]['is_equal_sup_id'] = ($vo['is_equal_sup_id'] == 2) ? 'ID不一致' : '';
            $results[$key]['is_equal_sup_name'] = ($vo['is_equal_sup_name'] == 2) ? '供应商不一致' : '';

            $results[$key]['instock_qty_more'] = is_null($vo['instock_qty_more']) ? 0 :$vo['instock_qty_more'];//多货数量
            $results[$key]['refund_status'] = in_array($vo['pai_number'], array_keys($refund_data))? getAliOrderRefundStatus($refund_data[$vo['pai_number']]): "";

            // 29779
            $results[$key]['is_oversea_boutique'] = $vo['is_overseas_boutique'] == 1?'是': "否";
            if(isset($vo['is_distribution'])){
                if($vo['is_distribution'] == 1){
                    $results[$key]['is_distribution'] = "是";
                }elseif($vo['is_distribution'] == 2){
                    $results[$key]['is_distribution'] = "否";
                }else {
                    $results[$key]['is_distribution'] = "--";
                }
            }
            // 来货异常
            $results[$key]['warehouse_abnormal'] = in_array($vo['purchase_number'], $abnormal_list)?true:false;
            $results[$key]['temp_container'] = $vo['temp_container']; // 虚拟柜号
            $results[$key]['is_merge'] = $vo['is_merge'] == 1?"已合单":"正常"; // 合单状态
            $results[$key]['free_shipping'] = $vo['free_shipping'] == "1"?"包邮":false; // 是否包邮
            $results[$key]['remark_images'] = [];
            if(SetAndNotEmpty($remark_images, $vo['id'])){
                $temp_img = [];
                foreach ($remark_images[$vo['id']] as $i_val){
                    $i_img_l = [];
                    $first = substr($i_val, 0, 1 );
                    $last = substr($i_val, strlen($i_val) - 1,1);
                    if($first == "[" && $last == "]"){
                        $i_img_l = json_decode($i_val, true);
                    }else{
                        $i_img_l = [$i_val];
                    }
                    if(count($i_img_l) > 0){
                        foreach ($i_img_l as $ii_v){
                            if(count($temp_img) < 5)$temp_img[] = $ii_v;
                        }
                    }
                }
                $results[$key]['remark_images'] = $temp_img;
            }
            $results[$key]['order_remark'] = isset($remark_items[$vo['id']]) ? $remark_items[$vo['id']]:(isset($remark_list[$vo['purchase_number']]) ? $remark_list[$vo['purchase_number']]: ''); // 备注

        }

        $aggregate_data['page_limit'] =count($results);//当前记录数
        $aggregate_data['page_sku'] = $skus_num;//当前页sku
        $aggregate_data['page_purchase_amount'] =  $purchase_amount_num;//当前页PCS数
        $aggregate_data['page_purchase_total_price'] = sprintf("%.3f",$purchase_total_price_all);//当前页订单总金额
        $aggregate_data['page_purchase_number_total'] =  $purchase_number_total;//当前页PO数
        $aggregate_data['page_supplier_code_total'] =  $supplier_code_total;//当前页供应商数
        //判断改登录用户是否是销售 如果是就屏蔽敏感字段
        $data_role= getRolexiao();
        $res_xiao=array_intersect($role_name, $data_role);
        if($res_xiao){
            foreach ($results as $key=>$row) {
                $results[$key]['purchase_price']="***";
                $results[$key]['product_base_price']="***";
                $results[$key]['purchase_unit_price']="***";
                $results[$key]['amount_storage']="***";
                $results[$key]['amount_paid']="***";
                $results[$key]['item_total_price']="***";
                $results[$key]['supplier_code'] = "***";
                $results[$key]['supplier_name'] = "***";
                $results[$key]['sg_supplier_name'] = "***";
                $results[$key]['sg_supplier_code'] = "***";
            }
        }
        $return_data = [
            'value' => $results,
            'offset' => $page,
            'limit' => $limit,
            'aggregate_data' => $aggregate_data,
        ];
        return $return_data;
    }

    /**
     * 取消次数
     */
    private function get_cancel_time($sku=[])
    {
        if(empty($sku))return [];
        $date = date("Y-m-d 00:00:00", strtotime(' -30 days'));
        $sql = "SELECT
                d.sku,
                count( d.id ) AS cancel_time
            FROM
                pur_purchase_order_cancel_detail AS d
                INNER JOIN pur_purchase_order_cancel AS c ON d.cancel_id = c.id 
            WHERE
                c.create_time >= '{$date}' 
                and d.sku in ('".implode("','", $sku)."')
            GROUP BY
                d.sku";
        $data = $this->purchase_db->query($sql)->result_array();
        $res = [];
        foreach ($data as &$val){
            $res[$val['sku']] = $val['cancel_time'];
        }
        return $res;
    }

    /**
     * 批量获取阿里旺旺
     */
    public function WangwangList($supplier_code)
    {
        $wangwang = $this->purchase_db->select('want_want,supplier_code')
            ->where_in('supplier_code', $supplier_code)
            ->get('supplier_contact')->result_array();
        $temp = [];
        foreach ($wangwang as &$val){
            $uid  = urlencode($val['want_want']);
            $html = '<a target="_blank" href="'.WANG_IP.$uid.'&site=cnalichn&s=10&charset=UTF-8" ><img border="0" src="'.WANG_IP_IMG.$uid.'&site=cntaobao&s=1&charset=utf-8" alt="点击这里给我发消息" /></a>';
            $temp[$val['supplier_code']] = $html;
        }
        $list = [];
        foreach ($supplier_code as $val){
            $list[$val] = isset($temp[$val]) ? $temp[$val] : "";
        }
        return $list;
    }

    /**
     * 搜索字段
     */
    private function select_field()
    {
        $list = [
            "ppoi" => [
                "coupon_rate", "is_long_delivery", "new_devliy", "coupon_rate_price", "purchase_unit_price",
                "ca_product_money", "ca_process_cost", "purchase_number", "id", "sku", "product_name", "is_new as is_new_flag",
                "confirm_amount AS purchase_amount", "confirm_amount", "tax_rate", "is_overdue as item_is_overdue",
                "pur_ticketed_point", "product_base_price", "modify_remark", "maintain_ticketed_point","barcode_pdf",
                "export_cname AS invoice_name", "declare_unit AS issuing_office", "invoiced_qty AS invoices_issued",
                "plan_arrive_time", "first_plan_arrive_time", "abnormal_flag", "abnormal_flag_no_sku", "demand_number",
                "label_pdf", "check_status", "is_customized","es_shipment_time", "devliery_days", "devliery_status", "quantity",
                "is_overdue", "overdue_days", "quantity_time","need_pay_time AS need_pay_time_item"
            ],
            "pli" => [
                "status AS track_status",
            ],
            "ppy" => [
                "freight", "discount", "process_cost", "settlement_ratio", "is_freight", "freight_formula_mode", "purchase_acccount",
                "pai_number", "accout_period_time AS need_pay_time", "cargo_company_id", "express_no", "real_price",
            ],
            "ppo" => [
                "purchase_order_status", "buyer_name", "supplier_name", "supplier_code", "is_gateway",
                "is_drawback", "purchase_name", "pertain_wms", "is_expedited", "account_type", "pay_type", "currency_code",
                "shipping_method_id", "create_time", "audit_time", "source", "is_ali_order", "is_ali_abnormal", "ali_order_amount",
                "is_ali_price_abnormal", "ali_order_status", "is_destroy", "pay_status", "pay_finish_status", "pay_time",
                "completion_time", "shipment_type", "lack_quantity_status", "purchase_type_id", "push_gateway_success",
                "gateway_status", "sku_state_type AS sku_state_type",
            ],
            "sg" => [
                "buyer_name as sg_buyer_name", "suggest_order_status", "temp_container", "supplier_name as sg_supplier_name",
                "supplier_code as sg_supplier_code", "is_boutique as is_boutique_sg", "is_overseas_boutique",
                "is_overseas_first_order as is_overseas_first_order", "is_drawback as sg_is_drawback", "purchase_name as sg_purchase_name",
                "warehouse_code", "account_type as sg_account_type", "pay_type as sg_pay_type", "destination_warehouse", "logistics_type",
                "supplier_source", "lock_type", "extra_handle", "is_purchasing", "purchase_type_id as demand_purchase_type_id",
                "product_line_id", "product_line_name","is_distribution",
            ],
            "ice" => [
                "export_tax_rebate_rate AS export_tax_rebate_rate_ice", "invoice_name AS invoice_name_ice", "issuing_office AS issuing_office_ice",
            ],
            "ware" => [
                "arrival_date", "instock_date", "arrival_qty", "instock_qty", "instock_qty_more", "breakage_qty",
            ],
            "loss" => [
                "loss_amount", "status AS loss_status",
            ],
            "sp" => [
                "surplus_quota", "tap_date_str", "status AS supplier_status", "order_num", "supplier_source AS real_supplier_source",
                "use_wechat_official", "is_postage as free_shipping","tap_date_str_sync"
            ],
            "swa" => [
                "audit_status AS audit_time_status",
            ],
            "pro" => [
                "is_relate_ali", "product_status", "starting_qty", "starting_qty_unit", "supply_status", "is_inspection", "state_type",
                "purchase_packaging", "is_invalid", "product_weight", "product_cn_link", "product_thumb_url", "is_new as new_product",
                "is_equal_sup_id", "is_equal_sup_name", "supply_status","product_img_url",
            ],
            "pop" => [
                "payment_platform",
            ],
        ];
        $field = ["(GROUP_CONCAT(sta.statement_number)) as statement_number"];
        foreach ($list as $key => $value){
            foreach ($value as $val){
                $field[] = $key.".".$val;
            }
        }

        return implode(",", $field);
    }

    /**
     * 统计字段
     */
    private function sum_field()
    {
        return " ppoi.id, ppoi.sku, ppo.supplier_code, ppoi.purchase_unit_price, ppoi.confirm_amount, ppo.purchase_number ";
    }

    /**
     * 获取当天任务栏配置
     */
    private function get_today_work_set()
    {
        $data = $this->purchase_db->from("param_sets")
            ->where(['pType' => 'PURCHASE_ORDER_TODAY_WORK_KEY', "pSort" => 1])
            ->get()->result_array();
        if(!$data && count($data) == 0)return [];
        $data_temp = [];
        foreach ($data as $val){
            $pVal = !empty($val['pValue']) ? json_decode($val['pValue'], true) : [];
            if(empty($pVal) || !SetAndNotEmpty($pVal, 'suggest_status', 'n'))continue;
            if($pVal['suggest_status'] < 1)continue;
            $data_temp[] = [
                "suggest_status"    => $pVal['suggest_status'],
                "track_log"         => SetAndNotEmpty($pVal, 'track_log', 'n') && $pVal['track_log'] > 0 ? $pVal['track_log'] : false,
                "add_remark"        => SetAndNotEmpty($pVal, 'add_remark', 'n') && $pVal['add_remark'] > 0 ? $pVal['add_remark'] : false,
                "examine_tips"      => SetAndNotEmpty($pVal, 'examine_tips') ? $pVal['examine_tips'] : false,
                "examine_date"      => SetAndNotEmpty($pVal, 'examine_date', 'n') && $pVal['examine_date'] > 0 ? $pVal['examine_date'] : false,
            ];
        }
        return $data_temp;
    }

    /**
     * 已付运费、加工费、优惠额
     */
    public function get_pay_other_price($pur=[])
    {
        $res = [];
        if(empty($pur))return $res;
        $data = $this->purchase_db->from('purchase_order_charge_against_surplus')
            ->select('purchase_number,paid_freight,paid_discount,paid_process_cost')
            ->where_in("purchase_number", $pur)
            ->get()
            ->result_array();
        if($data && count($data)){
            foreach ($data as $val){
                $res[$val['purchase_number']] = $val;
            }
        }
        return $res;
    }

    /**
     * 获取搜索数据
     */
    public function get_order_sum($params = [], $offsets = 0, $limit = 50, $page = 1,$type=true,$searchSum = False,$export_user=[])
    {
        $count_row = $this->new_get_list($params, $offsets, $limit, $page, $type, false, 0, false, $export_user, null, true);
        $count_row = $count_row[0];

        $keys = $this->purchase_order_sum_model->get_key($params,"purchase");

        $total_count = isset($count_row['total_all']) ? (int)$count_row['total_all'] : 0;
        $max_id =  isset($count_row['maxid']) ? (int)$count_row['maxid'] : 0;
        $min_id =  isset($count_row['minid']) ? (int)$count_row['minid'] : 0;
        //数据汇总
        $aggregate_data['total_all'] = $total_count;//总记录数
        $aggregate_data['max_id'] = $max_id;// 最大ID
        $aggregate_data['min_id'] = $min_id;// 最大ID
        $aggregate_data['params'] = $params; //
        $aggregate_data['sku_all'] = isset($count_row['sku_all']) ? (int)$count_row['sku_all'] : 0;//总sku
        $aggregate_data['purchase_amount_all'] = isset($count_row['purchase_amount_all']) ? (int)$count_row['purchase_amount_all'] : 0;//总PCS数
        $aggregate_data['purchase_unit_price_all'] = isset($count_row['purchase_unit_price_all']) ? $count_row['purchase_unit_price_all'] : 0;//所有订单总金额
        $aggregate_data['purchase_number_all'] = isset($count_row['purchase_number_all']) ? (int)$count_row['purchase_number_all'] : 0;//总PO数据
        $aggregate_data['supplier_code_all'] = isset($count_row['supplier_code_all']) ? (int)$count_row['supplier_code_all'] : 0;//总供应商数
        $return_data = [
            'aggregate_data' => $aggregate_data,
        ];
        $this->purchase_order_sum_model->set_sum_cache($keys, json_encode($return_data),500);

        return $return_data;
    }

    /**
     * SKU 是否在申请屏蔽
     **/
    private function get_sku_scree($sku) {
        if(empty($sku))return [];
        $result = $this->purchase_db->from("product_scree")
            ->select('sku')
            ->where_in("sku",$sku)
            ->where_in("status",[
                PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT,
                PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM,
                PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM
            ])
            ->get()->result_array();
        if($result && count($result) > 0) {
            return array_column($result, 'sku');
        }
        return [];
    }

    /**
     * 获取上次采购价
     */
    public function getSkuLastPurchasePrice($sku ='', $get_type= "one"){
        if(!is_array($sku))$sku = [$sku];
        $status = [PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
            PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE];
        $status_str = implode(',',$status);
        $sql = " SELECT b.purchase_unit_price,b.product_base_price,a.is_drawback,b.sku,a.audit_time FROM pur_purchase_order AS a 
                JOIN pur_purchase_order_items  b ON a.purchase_number = b.purchase_number 
                WHERE  b.sku in ('".implode("','", $sku)."') and a.purchase_order_status in (".$status_str.") order by audit_time desc;";
        $query = $this->purchase_db->query($sql);
        if($get_type == "one"){
            $data = $query->row_array();
            $price =0;
            if(!empty($data)){
                $price = $data['product_base_price'];
                if($data['is_drawback']){//是否退税
                    $price = $data['purchase_unit_price'];
                }
            }
            return $price;
        }
        if($get_type == "list"){
            $data = $query->result_array();
            $res = [];
            if($data && !empty($data)){
                foreach ($data as $val){
                    $res[$val['sku']] = $val['is_drawback']?$val['purchase_unit_price']:$val['product_base_price'];
                }
            }
            return $res;
        }
        return 0;
    }

    /**
     * 获取来货异常数据
     */
    private function get_purchase_abnomal($pur=[])
    {
        $data = $this->purchase_db->from('purchase_warehouse_abnormal')
            ->select('pur_number,sku,abnormal_type,is_handler,handler_type')
            ->where_in('pur_number', $pur)
            ->get()->result_array();
        if($data && !empty($data))return $data;
        return [];
    }

    /**
     * 拼接字符串返回
     */
    private function get_map_string($data)
    {
        return array_map(function($data){
            return sprintf("'%s'",$data);
        }, $data);
    }

    /**
     * 生成今日任务栏数据
     */
    public function generate_today_work()
    {
        $this_date = date("Y-m-d");
        $create_date = $this_date." 00:00:00";
        $work_suggest_status = [
            PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
            PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER,
            PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
            PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
            PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,
            PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND
        ];
        $query = " and a.create_time <'{$create_date}' and d.suggest_order_status in (".implode(',', $work_suggest_status).")";
        $query .= " AND a.purchase_type_id NOT IN (".PURCHASE_TYPE_OVERSEA.','.PURCHASE_TYPE_FBA_BIG.")";
        $today_work = $this->get_today_work_set();
        $today_work_list = [];
        foreach ($today_work as $t_val){
            $t_val_sql = " 1=1 ";
            if(SetAndNotEmpty($t_val, 'suggest_status', 'n') && $t_val['suggest_status'] > 0){
                $t_val_sql .= " and d.suggest_order_status = ".$t_val['suggest_status'];
            }
            if(SetAndNotEmpty($t_val, 'track_log') && $t_val['track_log'] > 0){
                $t_val_sql .= $t_val['track_log'] == 2 ? " and plix.`status` >0": " and (plix.`status` =0 or plix.`status` is null)";
            }
            if(SetAndNotEmpty($t_val, 'add_remark') && $t_val['add_remark'] > 0){
                $t_val_sql .= " and (ork.items_id ".($t_val['add_remark'] == 2? ">0": "=0 or ork.items_id is null").")";
            }
            if(SetAndNotEmpty($t_val, 'examine_tips') && SetAndNotEmpty($t_val, 'examine_date')){
                $examine_date = date('Y-m-d',strtotime("-".(int)$t_val['examine_date']." days")).' 00:00:00';
                $examine_tips_list = [
                    ">"     => "<",
                    ">="    => "<=",
                    "="     => "=",
                    "<"     => ">",
                    "<="    => ">=",
                ];
                $examine_tips = isset($examine_tips_list[$t_val['examine_tips']]) ? $examine_tips_list[$t_val['examine_tips']] : false;
                if($examine_tips)$t_val_sql .= " and a.audit_time {$examine_tips} '{$examine_date}'";
            }
            if($t_val_sql != " 1=1 ")$today_work_list[] = "(".$t_val_sql.")";
        }
        if(count($today_work_list) > 0)$query .= " and (".implode(" or ", $today_work_list).")";

        $sql = "update pur_purchase_order_items set today_work='{$this_date}' 
            where id in (
            	SELECT select_a.id FROM (
                SELECT DISTINCT
                    b.id
                FROM
                    pur_purchase_order_items AS b
                    INNER JOIN pur_purchase_order AS a ON a.purchase_number = b.purchase_number
                    INNER JOIN pur_purchase_suggest AS d ON d.demand_number = b.demand_number
                    LEFT JOIN `pur_purchase_logistics_info` AS `plix` ON `plix`.`purchase_number` = `b`.`purchase_number` AND b.sku = plix.sku
                    LEFT JOIN pur_purchase_order_remark AS ork ON a.purchase_number = ork.purchase_number AND b.id = ork.items_id 
                WHERE
                    b.purchase_number NOT LIKE 'YPO%' {$query} 
                GROUP BY
                    b.id 
                ORDER BY
                    b.id DESC 
                    ) as select_a
            )
		";
        if($this->purchase_db->query($sql)){
            return true;
        }
        return false;
    }
}