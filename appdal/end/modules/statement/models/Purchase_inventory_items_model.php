<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/15
 * Time: 15:08
 */

class Purchase_inventory_items_model extends Purchase_model
{
    protected $table_statement_warehouse_results = 'statement_warehouse_results';        //核销入库明细表
    protected $table_purchase_order_items = 'purchase_order_items';                      //采购订单明细表
    protected $table_purchase_order = 'purchase_order';                                  //采购订单表
    protected $table_label_info = 'purchase_label_info';                                 //标签信息表
    protected $table_purchase_suggest = 'purchase_suggest';                              //采购需求表
    protected $table_purchase_suggest_map = 'purchase_suggest_map';                      //采购单与需求单号关系表
    protected $table_statement_note = 'statement_note';                                  //核销备注信息
    protected $table_charge_against_surplus_po = 'purchase_order_charge_against_surplus';//采购单冲销结余

    public function __construct()
    {
        parent::__construct();
        $this->lang->load('common_lang');
        $this->load->helper(['user', 'status_order']);
        $this->load->model('user/User_group_model', 'User_group_model');
    }


    /**
     * 获取 入库明细记录原生数据
     * @param $params
     * @return array|bool
     */
    public function get_inventory_items($params)
    {
        if (empty($params))
            return false;

        // 原生SQL查询
        $querySql = "SELECT WR.*, LEFT(WR.instock_date, 7) AS instock_month, 
                PO.supplier_code, PO.account_type, PO.pay_type,PO.is_drawback, PO.purchase_name, 
                PO.purchase_type_id, PO.supplier_code, PO.supplier_name,PO.buyer_id, PO.buyer_name, PO.pay_status, 
                POI.purchase_unit_price, POI.confirm_amount,POI.product_name, POI.product_img_url, 
                PS.demand_number, PS.warehouse_code, PS.warehouse_name,
                SUR.surplus_able_charge_against_money
            FROM pur_statement_warehouse_results AS WR
            LEFT JOIN pur_purchase_order AS PO ON PO.purchase_number=WR.purchase_number
            LEFT JOIN pur_purchase_order_items AS POI ON POI.purchase_number=WR.purchase_number AND
            POI.sku=WR.sku
            LEFT JOIN pur_purchase_suggest AS PS ON PS.demand_number=POI.demand_number
            LEFT JOIN pur_purchase_order_charge_against_surplus AS SUR ON SUR.purchase_number=PO.purchase_number
            WHERE 1";

        if(isset($params['ids'])){
            if(is_array($params['ids'])){
                $ids_str = implode("','",$params['ids']);
                $querySql .= " AND WR.id IN('{$ids_str}')";
            }else{
                $querySql .= " AND WR.id='".addslashes($params['ids'])."'";
            }
        }
        if (isset($params['instock_batch'])) {
            if(is_array($params['instock_batch'])){
                $batchs_str = implode("','",$params['instock_batch']);
                $querySql .= " AND WR.instock_batch IN('{$batchs_str}')";
            }else{
                $querySql .= " AND WR.instock_batch='".addslashes($params['instock_batch'])."'";
            }
        }

        $inventory_items = $this->purchase_db->query($querySql)->result_array();

        if (empty($inventory_items))
            return [];

        return $inventory_items;
    }

    /**
     * 获取 入库明细记录原生数据，为了生成对账单（如果存在需要冲销的数据 则自动冲销）
     * @param $params
     * @return array|bool
     */
    public function get_inventory_items_for_statement($params)
    {
        if (empty($params)) return $this->res_data(false,'参数缺失');

        // 原生SQL查询
        $querySql = "SELECT WR.*, LEFT(WR.instock_date, 7) AS instock_month, 
                PO.supplier_code, PO.account_type, PO.pay_type,PO.is_drawback, PO.purchase_name, 
                PO.purchase_type_id, PO.supplier_code, PO.supplier_name,PO.buyer_id, PO.buyer_name, PO.pay_status, 
                POI.purchase_unit_price, POI.confirm_amount,POI.product_name, POI.product_img_url, 
                PS.demand_number, PS.warehouse_code, PS.warehouse_name,
                SUR.surplus_able_charge_against_money
            FROM pur_statement_warehouse_results AS WR
            LEFT JOIN pur_purchase_order AS PO ON PO.purchase_number=WR.purchase_number
            LEFT JOIN pur_purchase_order_items AS POI ON POI.purchase_number=WR.purchase_number AND
            POI.sku=WR.sku
            LEFT JOIN pur_purchase_suggest AS PS ON PS.demand_number=POI.demand_number
            LEFT JOIN pur_purchase_order_charge_against_surplus AS SUR ON SUR.purchase_number=PO.purchase_number
            WHERE 1";

        if(isset($params['ids'])){
            if(is_array($params['ids'])){
                $ids_str = implode("','",$params['ids']);
                $querySql .= " AND WR.id IN('{$ids_str}')";
            }else{
                $querySql .= " AND WR.id='".addslashes($params['ids'])."'";
            }
        }
        if (isset($params['instock_batch'])) {
            if(is_array($params['instock_batch'])){
                $batchs_str = implode("','",$params['instock_batch']);
                $querySql .= " AND WR.instock_batch IN('{$batchs_str}')";
            }else{
                $querySql .= " AND WR.instock_batch='".addslashes($params['instock_batch'])."'";
            }
        }

        // 自动冲销START：存在可冲销数据，先自动冲销（非常重要）
        $subSql = " AND SUR.surplus_able_charge_against_money > 0 AND WR.surplus_charge_against_amount > 0";
        $querySqlOne = $querySql.$subSql;
        $querySqlResult = $querySql;
        $inventory_items_waiting_cg_list = $this->purchase_db->query($querySqlOne)->result_array();

        if($inventory_items_waiting_cg_list){
            $inventory_item_surplus_list = [];
            foreach($inventory_items_waiting_cg_list as $inventory_item){
                if($inventory_item['surplus_charge_against_amount'] <= 0)
                    continue;// 入库明细记录无剩余可冲销金额
                if($inventory_item['surplus_able_charge_against_money'] <= 0)
                    continue;// 采购单无剩余可冲销金额

                $inventory_item_surplus_list[] = $inventory_item['purchase_number'].'_'.$inventory_item['instock_batch'];
            }

            if($inventory_item_surplus_list) $inventory_item_surplus_list = array_chunk($inventory_item_surplus_list,3);

            $this->load->model('statement/Charge_against_records_model');
            foreach($inventory_item_surplus_list as $value_arr){
                $result = $this->Charge_against_records_model->check_inventory_item_charge_against($value_arr);
                if($result['code'] !== true){
                    return $this->res_data(false,'入库记录自动冲销失败，请联系技术处理');
                }
            }
        }
        // 自动冲销ENDING

        $inventory_items = $this->purchase_db->query($querySqlResult)->result_array();

        if (empty($inventory_items)) return $this->res_data(false,'未获取到入库记录');
        return $this->res_data(true,'查询成功',$inventory_items);
    }


    /**
     * 获取入库明细列表数据
     * @param array $params
     * @param int $offsets
     * @param int $limit
     * @param int $page
     * @param bool $export
     * @return array
     */
    public function get_data_list($params = array(), $offsets = 1, $limit = 20, $page = 1, $export = false)
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

        $query = $this->purchase_db;
        $query->from("{$this->table_statement_warehouse_results} a");
        $query->join("{$this->table_purchase_order_items} b", 'b.id=a.items_id','LEFT');
        $query->join("{$this->table_purchase_order} c", 'c.purchase_number=a.purchase_number','LEFT');
        $query->join("{$this->table_purchase_suggest} f", 'f.demand_number=b.demand_number','LEFT');

        //<editor-fold desc="查询条件代码块">
        //按采购来源查询
        $query->where('a.source', $params['source']);
        //导出，按照选择记录查询
        if (isset($params['ids'])) {
            if (is_array($params['ids'])) {
                $query->where_in('a.id', $params['ids']);
            } else {
                $query->where('a.id', $params['ids']);
            }
        }
        //按供应商查询
        if (!empty($params['supplier_code'])) {
            $query->where('c.supplier_code', $params['supplier_code']);
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
                    $query->where_in('c.buyer_id', $buyer_id);
                } else {
                    $query->where_in('c.buyer_id', $params['buyer_id']);
                }
            } elseif (!empty($authorization_user)) {//按权限集合查询

            $query->where_in('c.buyer_id', $authorization_user);

            }
        }else {

        if(isset($params['swoole_userid']) && !empty($params['swoole_userid'])) {
             $query->where_in('c.buyer_id', $params['swoole_userid']);

            }

            $query->where_in('c.buyer_id', $params['buyer_id']);
        }

        if(isset($params['group_ids']) && !empty($params['group_ids'])){
            $query->where_in('c.buyer_id',$params['groupdatas']);
        }


        //按付款状态查询
        if (!empty($params['pay_status'])) {
            if (is_array($params['pay_status'])) {
                $query->where_in('c.pay_status', array_filter($params['pay_status']));
            } else {
                $query->where('c.pay_status', $params['pay_status']);
            }
        }
        //按合同号查询
        if (!empty($params['compact_number'])) {
            $compact_number = array_filter(explode(" ", trim($params['compact_number'])));
            if(!empty($compact_number)){
                $query->where_in('a.compact_number', $compact_number);
            }else{
                $query->where('a.compact_number', PURCHASE_NUMBER_ZFSTATUS);
            }
        }
        //按采购单号查询
        if (!empty($params['purchase_number'])) {
            $purchase_numbers = array_filter(explode(' ',$params['purchase_number']));
            if(!empty($purchase_numbers)){
                $query->where_in('a.purchase_number', $purchase_numbers);
            }else{
                $query->where('a.purchase_number', PURCHASE_NUMBER_ZFSTATUS);
            }
        }
        //按备货单号查询
        if (!empty($params['demand_number'])) {
            $demand_numbers = array_filter(explode(" ", trim($params['demand_number'])));
            if(!empty($demand_numbers)){
                // 采购单号限定主表(a表)查询范围
                $query->where("a.purchase_number IN(SELECT purchase_number FROM pur_purchase_order_items WHERE demand_number IN('".implode("','",$demand_numbers)."'))");
                $query->where_in('f.demand_number', $demand_numbers);
            }else{
                $query->where('a.purchase_number', PURCHASE_NUMBER_ZFSTATUS);
                $query->where('f.demand_number', PURCHASE_NUMBER_ZFSTATUS);
            }
        }
        //按入库批次号查询
        if (!empty($params['instock_batch'])) {
            $instock_batchs = array_filter(explode(" ", trim($params['instock_batch'])));
            if(!empty($instock_batchs)){
                $query->where_in('a.instock_batch', $instock_batchs);
            }else{
                $query->where('a.instock_batch', PURCHASE_NUMBER_ZFSTATUS);
            }
        }
        //按结算方式查询
        if (isset($params['settlement_type']) && $params['settlement_type']) {
            if (is_array($params['settlement_type'])) {
                $query->where_in('c.account_type', $params['settlement_type']);
            } else {
                $query->where('c.account_type', $params['settlement_type']);
            }
        }
        //按支付方式查询
        if (isset($params['pay_type']) && is_numeric($params['pay_type'])) {
            $query->where('c.pay_type', $params['pay_type']);
        }
        //按是否退税查询
        if (isset($params['is_drawback']) && is_numeric($params['is_drawback'])) {
            $query->where('c.is_drawback', $params['is_drawback']);
        }
        //按入库时间范围查询
        if (!empty($params['instock_date_start']) && !empty($params['instock_date_end'])) {
            $query->where('a.instock_date >=', $params['instock_date_start'] . ' 00:00:00');
            $query->where('a.instock_date <=', $params['instock_date_end'] . ' 23:59:59');
        }
        //按入库时间范围查询
        if (!empty($params['upper_end_time_start']) && !empty($params['upper_end_time_end'])) {
            $query->where('a.upper_end_time >=', $params['upper_end_time_start'] . ' 00:00:00');
            $query->where('a.upper_end_time <=', $params['upper_end_time_end'] . ' 23:59:59');
        }
        //按入库月份查询
        if (!empty($params['instock_month'])){
            $query->where('a.instock_date >=', $params['instock_month'] . '-01 00:00:00');
            $query->where('a.instock_date <=', date('Y-m-d 23:59:59', strtotime("{$params['instock_month']} +1 month -1 day")));
        }
        //按下单时间范围查询
        if (!empty($params['audit_time_start'])) {
            $query->where('c.audit_time >=', $params['audit_time_start'] . ' 00:00:00');
        }
        //按下单时间范围查询
        if (!empty($params['audit_time_end'])) {
            $query->where('c.audit_time <=', $params['audit_time_end'] . ' 23:59:59');
        }

        //按应付款时间范围查询
        if (!empty($params['need_pay_time_start'])) {
            $query->where('a.need_pay_time >=', $params['need_pay_time_start']);
        }
        //按应付款时间范围查询
        if (!empty($params['need_pay_time_ned'])) {
            $query->where('a.need_pay_time <=', $params['need_pay_time_ned']);
        }
        //按创建时间范围查询
        if (!empty($params['create_time_start'])) {
            $query->where('c.create_time >=', $params['create_time_start']);
        }
        //按创建时间范围查询
        if (!empty($params['create_time_end'])) {
            $query->where('c.create_time <=', $params['create_time_end']);
        }
        //按采购仓库查询
        if (!empty($params['pur_warehouse_code'])) {
            $query->where_in('f.warehouse_code', $params['pur_warehouse_code']);
        }
        //按SKU查询
        if (!empty($params['sku'])) {
            $sku_s = array_filter(explode(" ", trim($params['sku'])));
            if(!empty($sku_s)){
                $query->where_in('a.sku', $sku_s);
            }else{
                $query->where('a.sku', PURCHASE_NUMBER_ZFSTATUS);
            }
        }
        //按产品名称查询
        if (!empty($params['product_name'])) {
            $query->like('b.product_name', $params['product_name']);
        }
        //按对账单号查询
        if (!empty($params['statement_number'])) {
            $statement_number = explode(" ", trim($params['statement_number']));
            if(!empty($statement_number))$query->where_in('a.statement_number', $statement_number);
        }
        //按备货单状态查询
        if (isset($params['suggest_order_status']) && !empty($params['suggest_order_status'])) {
            if(is_array($params['suggest_order_status'])){
                $query->where_in('f.suggest_order_status', $params['suggest_order_status']);
            }else{
                $query->where('f.suggest_order_status', $params['suggest_order_status']);
            }
        }
        //按订单状态查询
        if (isset($params['pur_order_status']) && !empty($params['pur_order_status'])) {
            if(is_array($params['suggest_order_status'])){
                $query->where_in('c.purchase_order_status', $params['pur_order_status']);
            }else{
                $query->where('c.purchase_order_status', $params['pur_order_status']);
            }
        }
        //按入库数量查询(1-入库数量≠0，2-入库数量=0)
        if (!empty($params['instock_qty']) && in_array($params['instock_qty'], [1, 2,3])) {
            if (1 == $params['instock_qty']) {
                $query->where('a.instock_qty <>', 0);
            } elseif (3 == $params['instock_qty']) {
                $query->where('a.instock_qty <', 0);
            } else {
                $query->where('a.instock_qty', 0);
            }
        }
        //按入库批次冲销状态查询
        if (isset($params['charge_against_status']) && !empty($params['charge_against_status'])) {
            if(is_array($params['charge_against_status'])){
                $query->where_in('a.charge_against_status', $params['charge_against_status']);
            }else{
                $query->where('a.charge_against_status', $params['charge_against_status']);
            }
        }
        if(isset($params['user_groups_types'])){
            $user_groups_types = $params['user_groups_types'];
        }
        if(is_array($user_groups_types)){
            $query->where_in('c.purchase_type_id', $user_groups_types);
        }
        //按业务线查询
        if (!empty($params['purchase_type_id'])) {
            if(is_array($params['purchase_type_id'])){
                $query->where_in('c.purchase_type_id', $params['purchase_type_id']);
            }else{
                $query->where('c.purchase_type_id', $params['purchase_type_id']);
            }
        }
        if (isset($params['purchase_agent']) && !empty($params['purchase_agent'])) {
            $query->where('c.purchase_name', $params['purchase_agent']);
        }
        if (isset($params['is_purchasing']) && !empty($params['is_purchasing'])) {
            $query->where('a.is_purchasing', $params['is_purchasing']);
        }
        //按合同号查询
        if (!empty($params['is_isolation'])) {
            $query->where('a.is_isolation', $params['is_isolation']);
        }
        //入库是否异常
        if (!empty($params['is_abnormal'])) {
            $query->where('a.is_abnormal', $params['is_abnormal']);
        }
        //是否生成对账单
        if (!empty($params['has_statement_number'])) {
            if($params['has_statement_number'] == '1'){
                $query->where("a.statement_number <> '' ");
            }else{
                $query->where("a.statement_number = '' ");
            }
        }

        // 业务线
        if(isset($params['product_line_id']) && !empty($params['product_line_id'])){
            if(gettype($params['product_line_id']) == 'string')$params['product_line_id'] = [$params['product_line_id']];
            $query->where_in('f.product_line_id', $params['product_line_id']);
        }
        //是否海外精品
        if( isset($params['is_oversea_boutique']) && $params['is_oversea_boutique'] != NULL){
            $query->where('f.is_overseas_boutique', $params['is_overseas_boutique']);
        }

        $count_qb = clone $query;
        $count_qb_tmp = clone $query;
        //统计总数要加上前面筛选的条件
        $count_qb->select_sum('a.instock_price', 'total_instock_amount');
        $count_qb->select_sum('a.surplus_charge_against_amount', 'total_sca_amount');
        $count_qb->select_sum('a.instock_qty', 'total_instock_qty');
        $count_qb->select('COUNT(1) AS total_count');
        $count_result = $count_qb->get()->row_array();
        $total_count = (int)$count_result['total_count'];


        //导出时不需查询汇总数据
        if (!$export) {
            $count_qb2 = clone $query;
            $count_qb3 = clone $query;
            $count_qb4 = clone $query;

            //合同总个数
            $total_compact_number_count = $count_qb2->select('1')->group_by('a.compact_number')->get()->num_rows();
            //供应商总个数
            $total_supplier_count = $count_qb3->select('1')->group_by('c.supplier_code')->get()->num_rows();
            //po剩余可冲销金额
            $count_qb4->select('m.real_price_after_charge_against', 'real_price_after_charge_against');
            $count_qb4->select('m.surplus_able_charge_against_money', 'surplus_able_charge_against_money');
            $count_qb4_sql = $count_qb4->join("{$this->table_charge_against_surplus_po} m", 'm.purchase_number=c.purchase_number', 'LEFT')
                ->group_by('m.purchase_number')
                ->get_compiled_select();

            $count_result4 = $count_qb4->query("SELECT sum(surplus_able_charge_against_money) as total_real_price_after_charge_against FROM (" . $count_qb4_sql . " ) AS tmp")->row_array();

            $count_result['total_instock_batch_count'] = $total_count;// 入库批次号唯一所以=入库记录数
            $count_result['total_compact_number_count'] = (int)$total_compact_number_count;
            $count_result['total_supplier_count'] = (int)$total_supplier_count;
            $count_result['total_real_price_after_charge_against'] = (float)$count_result4['total_real_price_after_charge_against'];


            $this->rediss->setData(md5(getActiveUserId().'-create_statement_order'),base64_encode($count_qb_tmp->get_compiled_select()));// 缓存查询SQL，便于执行其他操作

        }

        //列表查询
        $query->select('a.id,a.defective_num,a.instock_date,a.instock_qty,a.instock_price,a.statement_number,a.deliery_batch,a.instock_batch,
        a.instock_user_name,a.paste_labeled,a.surplus_charge_against_amount,a.compact_number,a.charge_against_status,a.source,
        a.instock_qty_more,a.is_isolation,a.is_purchasing,a.instock_type,a.is_abnormal,a.upper_end_time,a.create_time,a.need_pay_time');
        $query->select('b.purchase_number,b.sku,b.product_name,b.product_img_url,b.purchase_unit_price,b.coupon_rate,b.confirm_amount');
        $query->select('c.supplier_code,c.supplier_name,c.purchase_type_id,c.currency_code,c.buyer_id,c.buyer_name,c.pay_type,
        c.account_type AS settlement_type,c.purchase_order_status,c.pay_status,c.purchase_name,c.is_drawback,c.waiting_time,c.audit_time');
        $query->select('f.warehouse_code,f.suggest_order_status,f.demand_number,f.product_line_name,f.is_overseas_boutique,f.purchase_type_id as demand_purchase_type_id');
        $result = $query->order_by('a.id', 'DESC')->limit($limit, $offsets)->get()->result_array();

        //<editor-fold desc="数据转换">

        // 入库是否异常
        $is_abnormal_list = [ '1' => '正常','2' => '异常','3' => '推送时间异常'];
        //供应商结算方式
        $settlement_codes = !empty($result) ? array_column($result, 'settlement_type') : array();
        $this->load->model("supplier/Supplier_settlement_model");
        $this->load->model('purchase/Purchase_order_determine_model');
        $settlement_code_list = $this->Supplier_settlement_model->get_code2name_list($settlement_codes);
        //获取是否承诺贴码
        $demand_number_arr = !empty($result) ? array_column($result, 'demand_number') : array();
        $paste_label_list = $this->_get_paste_list($demand_number_arr);
        //获取备注信息
        $inventory_record_ids = !empty($result) ? array_column($result, 'id') : array();
        $remark_list = $this->_get_inventory_record_remark($inventory_record_ids);
        //PO剩余可冲销金额
        $purchase_number = !empty($result) ? array_column($result, 'purchase_number') : array();
        $purchase_number_list = $this->_get_surplus_aca_amount($purchase_number);

        if(!empty($result)) {
            $buyerIds = array_unique(array_column($result, "buyer_id"));
            $buyerName = $this->User_group_model->getBuyerGroupMessage($buyerIds);
            $buyerName = array_column($buyerName, NULL, 'user_id');
        }else{
            $buyerName = [];
        }

        $skus_list = $result?array_column($result,'sku'):[];
        $skus_info_list = $skus_list?$this->purchase_db->query("SELECT sku,product_weight FROM pur_product WHERE sku IN('".implode("','",$skus_list)."')")->result_array():[];
        $skus_info_list = $skus_info_list?array_column($skus_info_list,'product_weight','sku'):[];

        foreach ($result as $key => $item) {
            //业务线
            $result[$key]['purchase_type_cn'] = !is_null($item['purchase_type_id']) ? getBusinessLine($item['purchase_type_id']) : '';
            $result[$key]['demand_purchase_type_cn'] = !is_null($item['demand_purchase_type_id']) ? getBusinessLine($item['demand_purchase_type_id']) : '';
            //支付方式
            $result[$key]['pay_type_cn'] = !is_null($item['pay_type']) ? getPayType($item['pay_type']) : '';
            //结算方式
            $result[$key]['settlement_type_cn'] = isset($settlement_code_list[$item['settlement_type']]) ? $settlement_code_list[$item['settlement_type']] : '';
            //采购单状态
            $result[$key]['purchase_order_status_cn'] = !is_null($item['purchase_order_status']) ? getPurchaseStatus($item['purchase_order_status']) : '';
            //备货单状态
            $result[$key]['suggest_order_status_cn'] = !is_null($item['suggest_order_status']) ? getPurchaseStatus($item['suggest_order_status']) : '';
            //是否承诺贴码
            $result[$key]['is_paste_cn'] = isset($paste_label_list[$item['demand_number']]) ? getPasteLabelStatus($paste_label_list[$item['demand_number']]) : '';
            //是否实际贴码
            $result[$key]['paste_labeled_cn'] = 1 == $item['paste_labeled'] ? '是' : '否';
            //是否退税
            $result[$key]['is_drawback_cn'] = !is_null($item['is_drawback']) ? getIsDrawback($item['is_drawback']) : '';
            //采购单付款状态
            $result[$key]['pay_status_cn'] = !is_null($item['pay_status']) ? getPayStatus($item['pay_status']) : '';
            //冲销状态
            $result[$key]['charge_against_status_cn'] = !is_null($item['charge_against_status']) ? getChargeAgainstStatus($item['charge_against_status']) : '';
            //备注
            $result[$key]['remark'] = isset($remark_list[$item['id']]) ? $remark_list[$item['id']] : [];
            //采购仓库
            $result[$key]['warehouse_cn'] = !is_null($item['warehouse_code']) ? getWarehouse($item['warehouse_code']) : '';
            //PO剩余可冲销金额
            $result[$key]['po_surplus_aca_amount'] = isset($purchase_number_list[$item['purchase_number']]) ? $purchase_number_list[$item['purchase_number']] : 0;
            //采购主体
            $result[$key]['purchase_name'] = !empty($item['purchase_name']) ? get_purchase_agent($item['purchase_name']) : '';
            //是否代采
            $result[$key]['is_purchasing'] = $item['is_purchasing'] == 1?'否':'是';
            $result[$key]['is_isolation'] = $item['is_isolation'] == 1?'是':'否';
            $result[$key]['is_abnormal'] = isset($is_abnormal_list[$item['is_abnormal']])?$is_abnormal_list[$item['is_abnormal']]:'';
            $results[$key]['is_oversea_boutique'] = $item['is_overseas_boutique'] == 1?'是': "否";
            $result[$key]['groupName']                = isset($buyerName[$item['buyer_id']])?$buyerName[$item['buyer_id']]['group_name']:'';

            $result[$key]['instock_type'] = getWarehouseInStockType($item['instock_type'])??'';
            $result[$key]['product_weight'] = isset($skus_info_list[$item['sku']])?$skus_info_list[$item['sku']]:'';


            $order_cancel_list = $this->Purchase_order_determine_model->get_order_cancel_list($item['purchase_number'],$item['sku']);//获取取消数量集合
            $order_cancel_qty = isset($order_cancel_list[$item['purchase_number'].'-'.$item['sku']])?$order_cancel_list[$item['purchase_number'].'-'.$item['sku']]:0;
            $result[$key]['real_confirm_amount'] = $item['confirm_amount'] - $order_cancel_qty;
            $result[$key]['instock_month'] = substr($item['instock_date'],0,7);
            $result[$key]['need_pay_time'] = $item['need_pay_time'] == '0000-00-00'?'-':$item['need_pay_time'];
        }
        //</editor-fold>

        //<editor-fold desc="表头字段">
        $key_table = array(
            $this->lang->myline('sequence'),
            $this->lang->myline('hx_product_img'),
            $this->lang->myline('hx_product_name').'/一级产品线',
            $this->lang->myline('hx_instock_batch'),
            $this->lang->myline('hx_deliery_batch'),
            $this->lang->myline('hx_instock_date') . '/' . $this->lang->myline('hx_waiting_time'),
            $this->lang->myline('hx_instock_price') . '/' . $this->lang->myline('hx_instock_qty'),
            $this->lang->myline('hx_instock_qty_more'),
            $this->lang->myline('hx_defective_num'),
            $this->lang->myline('hx_warehouse_code'),
            $this->lang->myline('hx_pur_order') . '/' . $this->lang->myline('hx_sku'),
            $this->lang->myline('hx_contract_number'),
            $this->lang->myline('hx_purchase_name') . '/' . $this->lang->myline('hx_is_drawback'),
            $this->lang->myline('hx_supplier_name'),
            $this->lang->myline('hx_purchase_unit_price') . '/' . $this->lang->myline('hx_currency'),
            $this->lang->myline('hx_coupon_rate'),
            $this->lang->myline('hx_buyer') . '/' . $this->lang->myline('hx_instock_user_name'),
            $this->lang->myline('hx_business_line'),
            $this->lang->myline('hx_pay_type') . '/' . $this->lang->myline('hx_account_type'),
            $this->lang->myline('hx_suggest_order_status') . '/' . $this->lang->myline('hx_pur_order_status'),
            $this->lang->myline('hx_is_paste') . '/' . $this->lang->myline('hx_is_pasted'),
            $this->lang->myline('hx_instock_type'),
            $this->lang->myline('hx_statement_number'),
            $this->lang->myline('hx_pur_order_pay_status'),
            $this->lang->myline('hx_status') . '/' . $this->lang->myline('hx_surplus_amount'),
            $this->lang->myline('hx_po_surplus_amount'),
            $this->lang->myline('hx_remark'),
        );
        //网采单去除合同号表头字段
        if (SOURCE_NETWORK_ORDER == $params['source']) {
            arrayDelElementByVal($key_table, $this->lang->myline('hx_contract_number'));
        }
        //</editor-fold>


        if ($export) {
            $return_data = [
                'values' => $result,
                'total' => $total_count
            ];
        } else {
            // 获取一级产品线
            $product_line_list = $this->purchase_db->from('product_line')->select('product_line_id,linelist_cn_name')
                ->where('linelist_parent_id=', 0)
                ->where('linelist_is_new=', 1)
                ->get()
                ->result_array();
            $product_line = [];
            if($product_line_list && count($product_line_list) > 0){
                foreach ($product_line_list as $val){
                    $product_line[$val['product_line_id']] = $val['linelist_cn_name'];
                }
            }
            $return_data = [
                'key' => $key_table,
                'values' => $result,
                'sum_data' =>
                    [
                        'current_page_instock_amount' => !empty($result) ? array_sum(array_column($result, 'instock_price')) : 0,                                                                                         //当前页入库金额
                        'current_page_sca_amount' => !empty($result) ? array_sum(array_column($result, 'surplus_charge_against_amount')) : 0,                                                                             //当前页剩余可冲销金额
                        'total_instock_qty' => is_null($count_result['total_instock_qty']) ? 0 : $count_result['total_instock_qty'],                                                                                      //所有页入库数量
                        'total_instock_amount' => is_null($count_result['total_instock_amount']) ? 0 : $count_result['total_instock_amount'],                                                                             //所有页入库金额
                        'total_sca_amount' => is_null($count_result['total_sca_amount']) ? 0 : $count_result['total_sca_amount'],                                                                                         //所有页剩余可冲销金额
                        'total_instock_batch_count' => is_null($count_result['total_instock_batch_count']) ? 0 : $count_result['total_instock_batch_count'],
                        'total_compact_number_count' => is_null($count_result['total_compact_number_count']) ? 0 : $count_result['total_compact_number_count'],
                        'total_supplier_count' => is_null($count_result['total_supplier_count']) ? 0 : $count_result['total_supplier_count'],
                        'total_real_price_after_charge_against' => is_null($count_result['total_real_price_after_charge_against']) ? 0 : $count_result['total_real_price_after_charge_against'],
                    ],
                'page_data' => [
                    'total' => $total_count,
                    'offset' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total_count / $limit),
                ],
                'drop_down_box' => [
                    'buyer_dropdown' => getBuyerDropdown(),                            //采购员下拉
                    'pay_status' => getPayStatus(),                                    //付款状态下拉
                    'pay_type' => getPayType(),                                        //支付方式下拉
                    'is_drawback' => getIsDrawback(),                                  //是否退税下拉
                    'settlement_type' => $this->_get_settlement_list(),                //结算方式下拉
                    'warehouse_code' => getWarehouse(),                                //仓库下拉下拉
                    'order_status' => getPurchaseStatus(),                             //采购单状态下拉
                    'suggest_order_status' => getPurchaseStatus(),                     //备货单状态下拉
                    'instock_qty' => getInstockQty(),                                  //入库类型下拉
                    'charge_against_status' => (object)getChargeAgainstStatus(),       //冲销状态下拉
                    'purchase_type' => getPurchaseType(),                              //业务线下拉
                    'purchase_agent' => get_purchase_agent(),                          //采购主体
                    'is_purchasing' => [ '1' => '否','2' => '是'],                     //是否代采
                    'is_isolation' => [ '1' => '是','2' => '否'],                      //是否隔离数据
                    'is_abnormal' => $is_abnormal_list,                               //入库是否异常
                    'has_statement_number' => [ '1' => '是','2' => '否'],              //是否生成对账单
                    'product_line_id' => $product_line,                                // 一级产品线
                    "is_oversea_boutique" => ["1"=> "是", "0"=> "否"],
                ]
            ];
        }
        return $return_data;
    }

    /**
     * 通过 demand_number 集合 获取 demand_number => is_paste 集合
     * demand_number => is_paste
     * @param array $demand_number
     * @return array
     * @author Justin
     * @date 2020/04/17 10:03
     */
    private function _get_paste_list(array $demand_number)
    {
        $data = [];
        $codes = array_unique(array_filter($demand_number));
        if (!empty($codes)) {
            foreach (array_chunk($demand_number, 500) as $chunk) {
                $rows = $this->purchase_db->select("demand_number,is_paste")
                    ->where_in("demand_number", $chunk)
                    ->get($this->table_label_info)
                    ->result_array();
                $data_tmp = is_array($rows) ? array_column($rows, "is_paste", "demand_number") : [];
                $data = array_merge($data, $data_tmp);
            }
        }
        return $data;
    }

    /**
     * 获取下拉列表供应商结算方式
     * @return array
     */
    private function _get_settlement_list()
    {
        $this->load->model('supplier/Supplier_settlement_model', 'settlementModel');
        //下拉列表供应商结算方式
        $data = $this->settlementModel->get_settlement();
        return array_column(!empty($data['list']) ? $data['list'] : [], 'settlement_name', 'settlement_code');
    }

    //获取备注信息
    private function _get_inventory_record_remark(array $ids)
    {
        if (empty($ids)) return [];
        $data = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            $data_tmp = $this->purchase_db->select('id,link_number,create_user_name,note,create_time')
                ->from("{$this->table_statement_note}")
                ->where('link_type', 2)
                ->where_in('link_number', $chunk)
                ->order_by('id', 'DESC')
                ->get()->result_array();
            $data = array_merge($data, $data_tmp);
        }
        $result = [];
        foreach ($data as $key => $val) {
            $result[$val['link_number']][] = $val['note'] . ' ' . $val['create_user_name'] . ' ' . $val['create_time'];
        }
        return $result;
    }

    /**
     * 获取采购单剩余可冲销商品金额
     * @param array $purchase_number
     * @return array
     */
    private function _get_surplus_aca_amount(array $purchase_number)
    {
        if (empty($purchase_number)) return [];
        $purchase_number = array_unique(array_filter($purchase_number));
        $result = [];
        foreach (array_chunk($purchase_number, 500) as $chunk) {
            $rows = $this->purchase_db->select('purchase_number,surplus_able_charge_against_money')
                ->from("{$this->table_charge_against_surplus_po}")
                ->where_in('purchase_number', $chunk)
                ->get()->result_array();
            $data_tmp = is_array($rows) ? array_column($rows, "surplus_able_charge_against_money", "purchase_number") : [];
            $result = array_merge($result, $data_tmp);
        }
        return $result;
    }

    /**
     * 从入库明细表同步数据到核销入库明细表
     * @param array $instock_batch
     * @param int $limit
     * @return array
     */
    public function sync_warehouse_results($instock_batch = [], $limit = 1000)
    {
        $this->load->model('Calc_pay_time_model');
        $set = $this->Calc_pay_time_model->getSetParamData('PURCHASE_ORDER_PAY_TIME_SET');
        $query = $this->purchase_db;
        $query->trans_begin();


        // 保存执行前 核销入库明细表 最大ID
        $latest_id = $query->select('max(id) as max_id')->get('statement_warehouse_results')->row_array();
        $latest_id = intval($latest_id['max_id']);

        $start = time();
        echo '开始1->',$start,'<br/>';
        try {
            $push_data_list = [];

            //获取待同步数据
            $query->select('a.id,a.items_id,a.purchase_number,a.sku,a.purchase_qty,a.deliery_batch,a.instock_batch,'
                .'a.instock_qty,a.instock_qty_more,a.defective_num,a.storage_position,a.defective_position,'
                .'a.instock_user_name,a.instock_date,a.paste_labeled,a.create_time,b.purchase_unit_price,'
                .'c.source,c.pay_finish_status,c.audit_time,b.plan_arrive_time,c.account_type,'
                .'d.compact_number,c.pay_status,f.is_purchasing,a.upper_end_time,pt.accout_period_time');
            $query->from('pur_warehouse_results a');
            $query->join('pur_purchase_order_items b', 'b.id=a.items_id');
            $query->join('pur_purchase_order c', 'c.purchase_number=b.purchase_number');
            $query->join('pur_purchase_order_pay_type pt', 'pt.purchase_number=b.purchase_number');
            $query->join('pur_purchase_compact_items d', 'd.purchase_number=c.purchase_number', 'LEFT');
            $query->join("{$this->table_purchase_suggest} f", 'f.demand_number=b.demand_number','LEFT');
            $query->where('a.sync_status', 0);
            $query->where('a.instock_node', 100);// 100.上架完成后才同步
            if (!empty(array_filter($instock_batch))) {
                $query->where_in('a.instock_batch', $instock_batch);
            }
            $warehouse_results = $query->limit($limit)->get()->result_array();

            //组织核销入库明细表数据
            $insert_data = [];
            $pay_time_purchase_numbers = [];
            foreach ($warehouse_results as $key => $item) {
                $id = $item['id'];// 原纪录ID
                $insert_data[$key]['items_id'] = $item['items_id'];
                $insert_data[$key]['purchase_number'] = $item['purchase_number'];
                $insert_data[$key]['sku'] = $item['sku'];
                $insert_data[$key]['is_purchasing'] = $item['is_purchasing'];
                $insert_data[$key]['purchase_qty'] = $item['purchase_qty'];
                $insert_data[$key]['deliery_batch'] = $item['deliery_batch'];
                $insert_data[$key]['instock_batch'] = $item['instock_batch'];
                $insert_data[$key]['instock_type'] = (int)$item['instock_type'];
                $insert_data[$key]['instock_qty'] = $item['instock_qty'];
                $insert_data[$key]['instock_qty_more'] = $item['instock_qty_more'];
                $insert_data[$key]['defective_num'] = $item['defective_num'];
                $insert_data[$key]['storage_position'] = $item['storage_position'];
                $insert_data[$key]['defective_position'] = $item['defective_position'];
                $insert_data[$key]['instock_user_name'] = $item['instock_user_name'];
                $insert_data[$key]['instock_date'] = $item['instock_date'];
                $insert_data[$key]['paste_labeled'] = $item['paste_labeled'];
                $insert_data[$key]['create_time'] = $item['create_time'];// 为 入库日志表中的创建时间（不可更改）
                $insert_data[$key]['upper_end_time'] = $item['upper_end_time'];
                $insert_data[$key]['source'] = $item['source'];
                $insert_data[$key]['compact_number'] = is_null($item['compact_number']) ? '' : $item['compact_number'];
                //入库金额（入库数量*采购单价）
                $instock_price = bcmul($item['instock_qty'], $item['purchase_unit_price'], 3);
                $insert_data[$key]['instock_price'] = $instock_price;
                $insert_data[$key]['surplus_charge_against_amount'] = $instock_price;
                $insert_data[$key]['charge_against_status'] = ($item['pay_status'] == PAY_NONEED_STATUS or $item['instock_qty'] == 0)? 0 : 1;// 无需付款和入库数量=0的 无需冲销

                // 计算应付款时间
                if($item['account_type'] == SUPPLIER_SETTLEMENT_CODE_TAP_DATE){// 1688线上账期的不计算
                    $insert_data[$key]['need_pay_time'] = $item['accout_period_time'];
                }else{
                    // 计算应付款时间
                    $pay_time_data = $this->Calc_pay_time_model->calc_pay_time_in_service($set,$item['account_type'],$item['source'],$item['pay_finish_status'],$item['audit_time'],$item['instock_date'],$item['plan_arrive_time']);
                    if($pay_time_data['code'] == true){
                        $insert_data[$key]['need_pay_time'] = $pay_time_data['data'];

                        // 更新PO明细应付款时间
                        $this->purchase_db->where('id',$item['items_id'])
                            ->update('purchase_order_items',['need_pay_time' => $pay_time_data['data']]);

                        $pay_time_purchase_numbers[$item['purchase_number']] = $item['purchase_number'];
                    }else{
                        $insert_data[$key]['need_pay_time'] = '0000-00-00';
                    }
                }

                // 结算方式 是否可对账，其他结算方式在 enable_statement_warehouse_result 中
                if(in_array($item['account_type'],[1,7,14,19,24,28,32])){
                    $insert_data[$key]['enable_statement'] = 1;// 1.可对账
                }else{
                    $insert_data[$key]['enable_statement'] = 2;// 2.不可对账
                }

                // 组装数据存入 消息队列（入库记录计算入库商品金额）
                $push_data_list[] = [
                    'purchase_number' => $item['purchase_number'],
                    'instock_batch'   => $item['instock_batch']
                ];
            }


            // 更新PO应付款时间
            if($pay_time_purchase_numbers){
                $purPayTime = $this->purchase_db->select('purchase_number,MAX(need_pay_time) AS need_pay_time')
                    ->where_in('purchase_number',$pay_time_purchase_numbers)
                    ->group_by('purchase_number')
                    ->get('pur_purchase_order_items')
                    ->result_array();
                foreach($purPayTime as $pt_value){
                    $this->purchase_db->where('purchase_number',$pt_value['purchase_number'])
                        ->update("purchase_order_pay_type", ["accout_period_time" => $pt_value['need_pay_time']]);
                }
            }

            // 分批存入 每次 100条
            $push_data_list_tmp = array_chunk($push_data_list,100);
            foreach($push_data_list_tmp as $push_data_list_value){
                //存入消息队列
                $this->load->library('Rabbitmq');
                $mq = new Rabbitmq();//创建消息队列对象
                $mq->setQueueName('STATEMENT_WARE_RECORD_REFRESH');//设置参数
                $mq->setExchangeName('STATEMENT_ORDER');//构造存入数据
                $mq->setRouteKey('SO_REFRESH_FOR_001');
                $mq->setType(AMQP_EX_TYPE_DIRECT);
                $mq->sendMessage($push_data_list_value);
            }

            if (empty($warehouse_results) OR empty($insert_data)) {
                throw new Exception('没有可同步数据');
            }

            //插入核销-入库明细表
            $affected_rows = $query->insert_batch($this->table_statement_warehouse_results, $insert_data);

            if ($query->trans_status() === FALSE) {
                throw new Exception('同步失败');
            }
            //更新同步状态
            $warehouse_results_ids = array_column($warehouse_results, 'id');
            foreach (array_chunk($warehouse_results_ids, 500) as $ids) {
                $query->where_in('id', $ids);
                $query->set('sync_status', 1);
                $query->update('pur_warehouse_results');
            }

            $query->trans_commit();
            $result = ['flag' => true, 'msg' => '同步成功' . $affected_rows . '条'];

        } catch (Exception $e) {
            $query->trans_rollback();
            $result = ['flag' => false, 'msg' => $e->getMessage()];
        }
        
        echo '开始2->',time(),',耗时',time() - $start,'秒<br/>';
        $this->check_abnormal_warehouse_result($latest_id);// 处理入库异常数据判断

        echo '开始3->',time(),',耗时',time() - $start,'秒<br/>';
        $this->enable_statement_warehouse_result();// 设置是否可对账

        echo '结束->',time(),',耗时',time() - $start,'秒<br/>';
        return $result;

    }

    /**
     * 处理入库异常数据判断
     * @param $latest_id
     * @return bool
     */
    public function check_abnormal_warehouse_result($latest_id){
        $query = $this->purchase_db;

        $lasest_month_end = date('Y-m-d H:i:s',strtotime(date("Y-m-01 00:00:00")) - 1);// 上月最后一秒
        $deadline_time = date("Y-m-d H:i:s",strtotime($lasest_month_end) + 1 + 86400);

        // 1).单个入库批次的入库数量＞备货单的采购数量-取消数量-报损数量-已入库数量,判断为异常
        // 2).单个批次的入库数量＜0,判断为异常
        // 3).增加枚举值"推送时间异常",以自然月为标准,上月的入库数据,下月2号0点以后才推送到采购系统的,判断为"推送时间异常"
        // 4).其他的为正常
        // 5).上线时,针对所有历史数据重新判断一次

        // 入库数量<0的情况（2）
        $update_sql = "UPDATE pur_statement_warehouse_results SET is_abnormal=2 WHERE id >{$latest_id} AND instock_qty<0";
        $query->query($update_sql);
        echo $update_sql,'<br/><br/>';

        // 推送时间异常（3）
        $update_sql = "UPDATE pur_statement_warehouse_results SET is_abnormal=3 WHERE id >{$latest_id} AND instock_date <= '$lasest_month_end' and create_time >= '$deadline_time'";
        $query->query($update_sql);
        echo $update_sql,'<br/><br/>';


        // 数量判断异常（1）
        // 查出入库数量异常的采购单号+SKU
        $update_sql = "SELECT *,(confirm_amount - cancel_qty - loss_qty) AS leave_instock_qty
                    FROM (
                        SELECT A.items_id,
                        SUM(A.`instock_qty`) AS instock_qty,
                        IFNULL(B.`confirm_amount`,0) AS confirm_amount,
                        IFNULL((SELECT SUM( ctq ) AS ctq FROM pur_purchase_cancel_warehouse AS cancel WHERE cancel.is_push = 1 AND cancel.purchase_number=A.purchase_number AND cancel.sku=A.sku),0) AS cancel_qty,
                        IFNULL((SELECT SUM( loss_amount ) AS loss_amount FROM pur_purchase_order_reportloss AS loss WHERE loss.status IN ( 0, 1, 2 ,3,4) AND loss.pur_number=A.purchase_number AND loss.sku=A.sku),0) AS loss_qty
                
                        FROM `pur_statement_warehouse_results` AS A
                        LEFT JOIN `pur_purchase_order_items` AS B ON A.items_id=B.id
                        WHERE A.`purchase_number` IN(
                            SELECT purchase_number FROM pur_statement_warehouse_results WHERE id >{$latest_id}
                        )
                        GROUP BY A.items_id
                        HAVING instock_qty > confirm_amount - cancel_qty - loss_qty
                    ) AS tmp_ware";
        $items_list = $query->query($update_sql)->result_array();
        echo $update_sql,'<br/><br/>';

        if(!empty($items_list)){
            $items_ids      = array_column($items_list,'items_id');
            $items_ids      = array_map('intval',$items_ids);
            $items_ids_str  = implode(",",$items_ids);
            $items_list     = array_column($items_list,'leave_instock_qty','items_id');

            $update_sql     = "SELECT id,instock_qty,is_abnormal,items_id,create_time FROM pur_statement_warehouse_results 
                    WHERE items_id IN($items_ids_str) AND instock_qty > 0
                    ORDER BY items_id ASC,create_time ASC";
            $warehouse_result_list = $query->query($update_sql)->result_array();


            // 遍历入库批次扣减 备货单剩余可入库数量，直到该数量<=0时则入库批次为异常入库
            $update_to_abnormal_list = [];// 需更新为入库异常的数据
            foreach ($warehouse_result_list as $key => $warehouse_result_value){
                $items_id = $warehouse_result_value['items_id'];

                if(!isset($items_list[$items_id])) continue;

                $items_list[$items_id] -= $warehouse_result_value['instock_qty'];

                if($warehouse_result_value['is_abnormal'] == 2){
                    continue;
                }else{
                    if($items_list[$items_id] < 0){
                        $update_to_abnormal_list[] = $warehouse_result_value['id'];
                    }
                }
            }

            if($update_to_abnormal_list){
                $update_to_abnormal_list_str = implode(",",$update_to_abnormal_list);
                $update_sql = "UPDATE pur_statement_warehouse_results SET is_abnormal=2 WHERE id IN($update_to_abnormal_list_str)";
                $query->query($update_sql);
            }
        }

        return true;
    }

    /**
     * 设置是否可对账，可对账，不可对账
     * @return bool
     */
    public function enable_statement_warehouse_result(){
        $query = $this->purchase_db;

        $now_date_begin_month       = date('Y-m-01 00:00:00');// 本月1号
        $now_date_middle_month      = date('Y-m-16 00:00:00',strtotime(date('Y-m-01')));// 本月16号
        $before_date_begin_month    = date('Y-m-01 00:00:00',strtotime(date('Y-m-01')) - 1);// 上月1号
        $before_date_middle_month   = date('Y-m-16 00:00:00',strtotime(date('Y-m-01')) - 1);// 上月16号

        // 每个月的1-2号的凌晨5 点执行
        if(CG_ENV != 'prod' or ( date('H') == 5  and ( date('d') == 1 or date('d') == 2) )){

            // 结算方式＝10％订金+90％尾款半月结、10％订金+90％尾款月结、30％订金+70％尾款半月结、30％订金+70％尾款月结、月结15天、月结30天、月结60天、月结90天的，入库时间的当月，为不可对账，超过后变为可对账
            $update_sql = "UPDATE pur_statement_warehouse_results,pur_purchase_order
                        SET pur_statement_warehouse_results.enable_statement=1 
                        WHERE pur_statement_warehouse_results.enable_statement=2 
                        AND pur_statement_warehouse_results.instock_date>='$before_date_begin_month'
                        AND pur_statement_warehouse_results.instock_date<'$now_date_begin_month'
                        AND pur_statement_warehouse_results.purchase_number=pur_purchase_order.purchase_number
                        AND pur_purchase_order.account_type IN(17,18,30,31,37,9,6,38)";
            $query->query($update_sql);
            echo $update_sql,'<br/><br/>';

            // 结算方式＝半月结的，入库时间在1-15号之间的，当月16号以后变为可对账；入库时间在16-31号之间的，次月1号以后变为可对账
            // 2/2.入库时间在16-31号之间的，次月1号以后变为可对账(设置上个月16-31号的数据)
            $update_sql = "UPDATE pur_statement_warehouse_results,pur_purchase_order
                        SET pur_statement_warehouse_results.enable_statement=1 
                        WHERE pur_statement_warehouse_results.enable_statement=2 
                        AND pur_statement_warehouse_results.instock_date>='$before_date_middle_month'
                        AND pur_statement_warehouse_results.instock_date<'$now_date_begin_month'
                        AND pur_statement_warehouse_results.purchase_number=pur_purchase_order.purchase_number
                        AND pur_purchase_order.account_type IN(8)";
            $query->query($update_sql);
            echo $update_sql,'<br/><br/>';
        }

        // 每个月的16-17号的凌晨5 点执行
        if(CG_ENV != 'prod' or ( date('H') == 5  and ( date('d') == 16 or date('d') == 17) )){
            // 结算方式＝半月结的，入库时间在1-15号之间的，当月16号以后变为可对账；入库时间在16-31号之间的，次月1号以后变为可对账
            // 1/2.入库时间在1-15号之间的，当月16号以后变为可对账(设置本月1-15号的数据)
            $update_sql = "UPDATE pur_statement_warehouse_results,pur_purchase_order
                        SET pur_statement_warehouse_results.enable_statement=1 
                        WHERE pur_statement_warehouse_results.enable_statement=2
                        AND pur_statement_warehouse_results.instock_date>='$now_date_begin_month'
                        AND pur_statement_warehouse_results.instock_date<'$now_date_middle_month'
                        AND pur_statement_warehouse_results.purchase_number=pur_purchase_order.purchase_number
                        AND pur_purchase_order.account_type IN(8)";
            $query->query($update_sql);
            echo $update_sql,'<br/><br/>';
        }

        return true;
    }
}