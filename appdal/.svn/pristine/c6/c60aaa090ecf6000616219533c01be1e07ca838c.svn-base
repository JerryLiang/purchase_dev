<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/15
 * Time: 15:08
 */

class Supplier_inventory_items_model extends Purchase_model
{
    protected $table_statement_warehouse_results = 'statement_warehouse_results';        //核销入库明细表
    protected $table_purchase_order_items = 'purchase_order_items';                      //采购订单明细表
    protected $table_purchase_order = 'purchase_order';                                  //采购订单表
    protected $table_label_info = 'purchase_label_info';                                 //标签信息表
    protected $table_purchase_suggest = 'purchase_suggest';                              //采购需求表
    protected $table_purchase_suggest_map = 'purchase_suggest_map';                      //采购单与需求单号关系表
    protected $table_statement_note = 'statement_note';                                  //核销备注信息
    protected $table_charge_against_surplus_po = 'purchase_order_charge_against_surplus';//采购单冲销结余
    protected $table_purchase_statement = 'purchase_statement';                          //对账单列表

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['user', 'status_order']);
    }

    /**
     * 获取入库明细列表数据（门户系统调用）
     * @param array $params
     * @param int $offsets
     * @param int $limit
     * @param int $page
     * @param bool $export
     * @return array
     */
    public function gateway_data_list($params = array(), $offsets = 1, $limit = 20, $page = 1, $export = false)
    {
        // 入库是否异常
        $is_abnormal_list = [ '1' => '正常','2' => '异常','3' => '推送时间异常'];
        $gateWayPayStatus = [
            PAY_UNPAID_STATUS           => '未申请付款',
            PURCHASE_NUMBER_ZFSTATUS    => '等待支付',
            PART_PAID                   => '已部分付款',
            PAY_PAID                    => '已付款'
        ];
        $query = $this->purchase_db;
        $query->from("{$this->table_statement_warehouse_results} a");
        $query->join("{$this->table_purchase_order_items} b", 'b.id=a.items_id','LEFT');
        $query->join("{$this->table_purchase_order} c", 'c.purchase_number=a.purchase_number','LEFT');
        $query->join("{$this->table_purchase_suggest} f", 'f.demand_number=b.demand_number','LEFT');
        $query->join("{$this->table_purchase_statement} ps", 'ps.statement_number=a.statement_number','LEFT');

        // 固定参数
        $query->where('a.source', SOURCE_COMPACT_ORDER);// 只查询合同数据
        $query->where_not_in('a.is_abnormal', 2);// 异常的数据不展示
        $query->where_not_in('c.pay_status', PAY_NONEED_STATUS);// 无需付款的数据不展示
        if(SetAndNotEmpty($params,'supplier_code')){
            $query->where('c.supplier_code',$params['supplier_code']);
        }else{
            $query->where('c.supplier_code',PURCHASE_NUMBER_ZFSTATUS);
        }


        //导出，按照选择记录查询
        if (isset($params['ids']) and !empty($params['ids'])) {
            if (is_array($params['ids'])) {
                $query->where_in('a.id', $params['ids']);
            } else {
                $ids_arr = array_filter(explode(",", trim($params['ids'])));
                if($ids_arr){
                    $query->where_in('a.id', $ids_arr);
                }else{
                    $query->where('a.id', PURCHASE_NUMBER_ZFSTATUS);
                }
            }
        }
        //按业务线查询
        if (!empty($params['buyer_id']) and $params['buyer_id']) {
            if(is_array($params['buyer_id'])){
                $query->where_in('c.buyer_id', $params['buyer_id']);
            }else{
                $query->where('c.buyer_id', $params['buyer_id']);
            }
        }

        //按付款状态查询
        if (!empty($params['pay_status'])) {
            $pay_status_arr = [];
            if(is_string($params['pay_status'])){
                $pay_status_arr[] = $params['pay_status'];// 转成数组统一处理
            }else{
                $pay_status_arr = $params['pay_status'];// 转成数组统一处理
            }

            if (count($pay_status_arr) == 4) {

            }elseif(!in_array(PURCHASE_NUMBER_ZFSTATUS,$pay_status_arr)){
                $query->where_in('c.pay_status', array_filter($pay_status_arr));
            }elseif(in_array(PURCHASE_NUMBER_ZFSTATUS,$pay_status_arr)){
                $not_pay_status = [];
                if(!in_array(PAY_UNPAID_STATUS,$pay_status_arr)) $not_pay_status[] = PAY_UNPAID_STATUS;
                if(!in_array(PART_PAID,$pay_status_arr)) $not_pay_status[] = PART_PAID;
                if(!in_array(PAY_PAID,$pay_status_arr)) $not_pay_status[] = PAY_PAID;
                $query->where_not_in('c.pay_status', $not_pay_status);
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
                $query->where_in('f.demand_number', $demand_numbers);
            }else{
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
            if(is_array($params['pur_warehouse_code'])){
                $query->where_in('f.warehouse_code', $params['pur_warehouse_code']);
            }else{
                $query->where('f.warehouse_code', $params['pur_warehouse_code']);
            }
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
            if(is_array($params['pur_order_status'])){
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
        //是否可对账
        if (!empty($params['enable_statement'])) {
            $query->where('a.enable_statement', $params['enable_statement']);
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
        $count_qb->select('COUNT(1) AS total_count');
        $count_result = $count_qb->get()->row_array();
        $total_count = (int)$count_result['total_count'];

        //导出时不需查询汇总数据
        if (!$export) {

            $this->rediss->setData(md5($params['supplier_code'].'-create_statement_order'),base64_encode($count_qb_tmp->get_compiled_select()));// 缓存查询SQL，便于执行其他操作

        }

        //列表查询
        $query->select('a.id,a.defective_num,a.instock_date,a.instock_qty,a.instock_price,a.statement_number,a.deliery_batch,a.instock_batch,
        a.instock_user_name,a.paste_labeled,a.surplus_charge_against_amount,a.compact_number,a.charge_against_status,a.source,
        a.instock_qty_more,a.is_isolation,a.is_purchasing,a.instock_type,a.is_abnormal,a.upper_end_time,a.create_time,a.need_pay_time,
        a.created_statement');
        $query->select('b.purchase_number,b.sku,b.product_name,b.product_img_url,b.purchase_unit_price,b.coupon_rate,b.confirm_amount');
        $query->select('c.supplier_code,c.supplier_name,c.purchase_type_id,c.currency_code,c.buyer_id,c.buyer_name,c.pay_type,
        c.account_type AS settlement_type,c.purchase_order_status,c.pay_status,c.purchase_name,c.is_drawback,c.waiting_time,c.audit_time');
        $query->select('f.warehouse_code,f.suggest_order_status,f.demand_number,f.product_line_name,f.is_overseas_boutique,f.purchase_type_id as demand_purchase_type_id');
        $query->select('ps.create_time as st_create_time');
        $result = $query->order_by('a.id', 'DESC')->limit($limit, $offsets)->get()->result_array();
        //<editor-fold desc="数据转换">

        //供应商结算方式
        $settlement_codes = !empty($result) ? array_column($result, 'settlement_type') : array();
        $this->load->model("supplier/Supplier_settlement_model");
        $this->load->model('purchase/Purchase_order_determine_model');
        $settlement_code_list = $this->Supplier_settlement_model->get_code2name_list($settlement_codes);
        //获取备注信息
        $inventory_record_ids = !empty($result) ? array_column($result, 'id') : array();
        $remark_list = $this->_get_inventory_record_remark($inventory_record_ids);

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
            //是否退税 => 是否开票
            $result[$key]['is_drawback_cn'] = $item['is_drawback'] == PURCHASE_IS_DRAWBACK_Y ?'开票':'不开票';
            //采购单付款状态
            $result[$key]['pay_status_cn'] = in_array($item['pay_status'],array_keys($gateWayPayStatus))?$gateWayPayStatus[$item['pay_status']]:'等待支付';
            //冲销状态
            $result[$key]['charge_against_status_cn'] = !is_null($item['charge_against_status']) ? getChargeAgainstStatus($item['charge_against_status']) : '';
            //备注
            $result[$key]['remark'] = isset($remark_list[$item['id']]) ? $remark_list[$item['id']] : [];
            //采购仓库
            $result[$key]['warehouse_cn'] = !is_null($item['warehouse_code']) ? getWarehouse($item['warehouse_code']) : '';
            //采购主体
            $result[$key]['purchase_name'] = !empty($item['purchase_name']) ? get_purchase_agent($item['purchase_name']) : '';
            //是否代采
            $result[$key]['is_purchasing'] = $item['is_purchasing'] == 1?'否':'是';
            $result[$key]['is_isolation'] = $item['is_isolation'] == 1?'是':'否';
            $result[$key]['is_abnormal'] = isset($is_abnormal_list[$item['is_abnormal']])?$is_abnormal_list[$item['is_abnormal']]:'';
            $results[$key]['is_oversea_boutique'] = $item['is_overseas_boutique'] == 1?'是': "否";

            $result[$key]['instock_type'] = getWarehouseInStockType($item['instock_type'])??'';

            $order_cancel_list = $this->Purchase_order_determine_model->get_order_cancel_list($item['purchase_number'],$item['sku']);//获取取消数量集合
            $order_cancel_qty = isset($order_cancel_list[$item['purchase_number'].'-'.$item['sku']])?$order_cancel_list[$item['purchase_number'].'-'.$item['sku']]:0;
            $result[$key]['real_confirm_amount'] = $item['confirm_amount'] - $order_cancel_qty;
            $result[$key]['instock_month'] = substr($item['instock_date'],0,7);
            $result[$key]['need_pay_time'] = $item['need_pay_time'] == '0000-00-00'?'-':$item['need_pay_time'];
            $result[$key]['created_statement_cn'] = $item['created_statement'] == 2 ?'已创建':'未创建';
            $result[$key]['instock_price'] = format_price_floatval($item['instock_price'],false,true,2);
        }


        if ($export) {
            $return_data = [
                'values' => $result,
                'total' => $total_count
            ];
        } else {
            $return_data = [
                'values' => $result,
                'page_data' => [
                    'total' => $total_count,
                    'offset' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total_count / $limit),
                ],
                'drop_down_box' => [
                    'buyer_dropdown' => getBuyerDropdown(),                            //采购员下拉
                    'pay_status' => $gateWayPayStatus,                                    //付款状态下拉
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
                    "is_oversea_boutique" => ["1"=> "是", "0"=> "否"],
                ]
            ];
        }
        return $return_data;
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

    /**
     * 获取 门户系统添加的备注信息
     * @param array $ids
     * @return array
     */
    private function _get_inventory_record_remark(array $ids)
    {
        if (empty($ids)) return [];
        $data = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            $data_tmp = $this->purchase_db->select('id,link_number,create_user_name,note,create_time')
                ->from("{$this->table_statement_note}")
                ->where('link_type', 4)
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

}