<?php

/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2018/12/27 0027 11:23
 */
class Purchase_order_model extends Purchase_model
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
        $this->load->model('purchase/purchase_order_items_model'); // 采购单子表
        $this->load->model('abnormal/report_loss_model'); // 报损数据
        $this->load->model('warehouse/warehouse_model');
        $this->load->model('product/product_model');
        $this->load->model('approval_model');
        $this->load->model('purchase/purchase_order_sum_model');
        $this->load->model('purchase/Purchase_order_progress_model');
        $this->load->model('purchase/purchase_order_extend_model');
        $this->load->model('purchase/Delivery_model');
        $this->load->model('user/User_group_model');
        $this->load->library('rediss');

        $this->config->load('key_name', FALSE, TRUE);
        $this->load->helper('abnormal');
        $this->load->helper('status_order');
        $this->load->model('product_line_model');
        $this->load->model('supplier_joint_model');
        $this->lang->load('logistics_state_config_lang');
        $this->load->model('others/Plan_system_model');
        $this->load->model('purchase_shipment/shipment_track_list_model');
        $this->load->model('purchase_shipment/shipping_report_loss_model');
        $this->load->model('Message_model');
        $this->load->helper('status_1688');
        $this->load->helper('common');


    }
    /**
     * 采购单列表列名称
     * @author Jolon
     * @return array
     */
    public function table_columns()
    {
        $key_value = [
        //    'id' => 'ID号',
            'product_img_url' => '图片',
            'purchase_order_status' => '订单状态',
            'suggest_order_status' => '备货单状态',
            'sku' => 'sku',
            'purchase_number' => '采购单号',
            'demand_number' => '备货单号',
            'product_name' => '产品名称',
            'compact_number' => '合同号',
            'buyer_name' => '采购员',
            'purchase_name' => '采购主体',
            'supplier_name' => '供应商',
            'purchase_amount' => '采购数量',
            'purchase_price' => '采购金额',
            'is_new' => '是否新品',
            'is_drawback' => '是否退税',
            'coupon_rate_message'        => '票面信息',
         //   'is_include_tax' => '是否含税',
            'purchase_unit_price' => '含税单价',
            'product_base_price' => '未税单价',
            'pur_ticketed_point' => '开票点',
            'export_tax_rebate_rate' => '退税率',
            'currency_code' => '币种',
            'invoice_name' => '开票票名',
            'issuing_office' => '开票单位',
            'invoices_issued' => '已开票数量',
            'invoiced_amount' => '已开票金额',
            'warehouse_code' => '采购仓库',
            'is_expedited' => '是否加急',
            'logistics_trajectory' => '物流轨迹',
            'account_type' => '结算方式',
            'pay_type' => '支付方式',
            'payment_platform' => '支付平台',
            'settlement_ratio' => '结算比例',
            'shipping_method_id' => '供应商运输',
            'create_time'=>'创建时间',
            'audit_time' => '审核日期',
            'plan_arrive_time' => '预计到货日期',
            'es_shipment_time' => '预计发货时间',
            'first_plan_arrive_time' => '首次预计到货时间',
            'source' => '采购来源',
            'freight' => '运费',
            'process_cost' => '加工费',
            'is_freight' => '运费支付',
            'discount' => '优惠额',
            'freight_formula_mode' => '运费计算方式',
            'purchase_acccount' => '网拍账号',
            'pai_number' => '拍单号',
            'arrival_date' => '到货时间',
            'arrival_qty' => '到货数量',
            'instock_qty_more' => '多货数量',
            'instock_date' => '入库日期',
            'instock_qty' => '入库数量',
            'logistics_type' => '物流类型',
            'amount_storage' => '入库金额',
            'amount_paid' => "已付金额",
            'overdue_days' => '逾期天数',
            'is_overdue' => '是否逾期',
            'is_destroy' => '是否核销',
            'cancel_ctq' => '取消数量',
            'item_total_price' => '取消金额',
            'loss_amount' => '报损数量',
            'loss_status'  => '报损状态',
            'customs_code'=>'出口海关编码',
            'pay_status' => '付款状态',
            'pay_time' => '付款时间',
            'requisition_number' => '请款单号',
            'audit_status' => '取消未到货状态',
            'tap_date_str' => '线上账期日期',
            'need_pay_time' => '应付款时间',
            'pay_notice' => '付款提醒状态',
            'is_ali_order' => '是否1688下单',
            'remark' => '1688订单状态',
            'modify_remark' => '其他备注',
            'destination_warehouse' => '目的仓',
            'product_status' => '产品状态',
            'last_purchase_price' => '上次采购单价',
            'is_inspection' => '是否商检',
            'shipment_type' => '发运类型',
            'supplier_source' => '供应商来源',
            'statement_number' => '对账单号',
//            'state_type'     => '开发类型',
            'lack_quantity_status' => '是否欠货',
            'purchase_packaging' => '包装类型',
	        'starting_qty' => '最小起订量',
            'starting_qty_unit' => '最小起订量单位',
            'is_invalid'        => '连接是否失效',
            'is_ali_price_abnormal' => '金额异常',
            'coupon_rate'       => '票面税率',
            'coupon_rate_price' => '票面未税单价',
            'completion_time'    => '订单完结时间',
            'is_purchasing' =>'是否代采',
//            'audit_time_status' =>'交期确认状态',
            'barcode_pdf'=>'是否有产品条码',
            'label_pdf'=>'是否有物流标签',
            'is_new_ch'=>'是否新品',
            'is_overseas_first_order_ch'=>'是否海外首单',
            'is_gateway_ch'=>'是否对接门户',
            'check_status_cn'=>'验货状态',
            'demand_purchase_type_id' => '备货单业务线',
            'is_customized' => '是否定制',
            'devliery_days' => '逾期天数（交期）',
            'devliery_status' => '是否逾期(交期)',
            //'quantity' => '门户回货数'

        ];

        return $key_value;
    }

    /**
     * 获取一个指定的采购单信息
     * @author Jolon
     * @param string $purchase_number 采购单编号
     * @param bool $have_items 是否附带采购单明细
     * @return mixed
     */
    public function get_one($purchase_number, $have_items = true)
    {
        $query_builder = $this->purchase_db;
        $query_builder->where('purchase_number', $purchase_number);
        $results = $query_builder->get($this->table_name)->row_array();
        if ($results && $have_items) {// 附带采购明细信息
            $items = $this->purchase_order_items_model->get_item($purchase_number);
            $results['items_list'] = $items;
        }

        return $results;
    }

    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd-H:i:s")."-".($b[0] * 1000);
    }

    /**
     * 获取一个指定的采购单信息,采购明细增加备货单号
     * @author Jolon
     * @param string $purchase_number 采购单编号
     * @param bool $have_items 是否附带采购单明细
     * @return mixed
     */
    public function get_one_with_demand_number($purchase_number, $have_items = true)
    {
        $query_builder = $this->purchase_db;
        $query_builder->where('purchase_number', $purchase_number);
        $results = $query_builder->get($this->table_name)->row_array();
        if ($results and $have_items) {// 附带采购明细信息
            $items = $this->purchase_order_items_model->get_item_with_demand_number($purchase_number);
            $results['items_list'] = $items;
        }

        return $results;
    }
    /**
     * 获取蓝凌fid值
     * @param int 参数id
     * @author harvin
     * @return mixed
     */
    public function get_audit_orders($id){
        $purchase_number=$this->purchase_db
                ->select('purchase_number')
                ->where('id',$id)
                ->get('purchase_order_items')
                ->row_array();
        if(empty($purchase_number)) return null;
        $processid=$this->purchase_db
                ->select('processid')
                ->where('purchase_number',$purchase_number['purchase_number'])
                ->order_by('create_time desc')
                ->get('purchase_blue_process`')
                ->row_array();
        $lan_web_ip=getConfigItemByName('api_config', 'lan_api', 'web_ip');
        $data=[
            'lan_web_ip'=>$lan_web_ip,
            'processid'=>isset($processid['processid'])?$processid['processid']:null,
        ];
        return $data;
    }
    /**
     * 保存采购列表字段判断用户显示
     * @param json $data json数据
     * @author harvin
     * ** */
    public function table_save($data)
    {
        //获取登录用户id
        $userid = getActiveUserId();
        //先查询表是否有数据  有就更新 没有就增加
        $user_role = $this->purchase_db->where('userid', $userid)->get('purchase_user_role')->row_array();
        if (empty($user_role)) {
            $roleuser = [
                'userid' => $userid,
                'role' => $data,
            ];
            $re = $this->purchase_db->insert('purchase_user_role', $roleuser);
        } else {
            $roleuser = [
                'role' => $data,
            ];
            $re = $user_role = $this->purchase_db->where('userid', $userid)->update('purchase_user_role', $roleuser);
        }
        if ($re) {
            return true;
        } else {
            return false;
        }
    }

    public function get_productStatus( $status= NULL ) {
        $this->load->helper('status_product');
        $product_status = getProductStatus();
        if( isset($product_status[$status]) )
        {
            return $product_status[$status];
        }
        return NULL;
    }

    /**
       * SKU 是否在申请屏蔽
     **/
    private function get_sku_scree( $sku ) {

        $result = $this->purchase_db->from("pur_product_scree")->where("sku",$sku)->where_in("status",[PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT,PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM,PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM])->get()->row_array();
        if( !empty($result) ) {
            return 1;
        }

        return 0;
    }

    public function get_mongodb_data($results,$copun_flag=FALSE)
    {
        $userid = getActiveUserId();
        $user_id=jurisdiction(); //当前登录用户ID
        $role_name=get_user_role();//当前登录角色

        $role = $this->purchase_db->where('userid', $userid)->where('list_type', 1)->get('purchase_user_role')->row_array();
        //echo $query_builder->last_query();die();

        //统一查询需要转换的字段，避免循环嵌套查询
        //warehouse_code=>warehouse_name 集合 仓库名称
        $warehouse_codes = is_array($results) ? array_column($results, "warehouse_code") : [];
        $transfer_warehouse_codes = is_array($results) ? array_column($results, "transfer_warehouse") : [];
        $warehouse_codes = array_unique(array_merge($warehouse_codes, $transfer_warehouse_codes));
        $warehouse_code_list = $this->warehouse_model->get_code2name_list($warehouse_codes);

        //account_type 供应商结算方式
        /*
        $settlement_codes = is_array($results) ? array_column($results, "account_type") : [];
        $this->load->model("supplier/Supplier_settlement_model");
        $settlement_code_list = $this->Supplier_settlement_model->get_code2name_list($settlement_codes);
        */
        $this->load->model("supplier/Supplier_settlement_model");
        $settlement_code_list = $this->Supplier_settlement_model->get_code_by_name_list();

        //入库金额 amount_storage
        $ids = is_array($results) ? array_column($results, "id") : [];

        $amount_storage_list = $this->get_amount_storage_list($ids);
        $cancel_ctq_data = $this->count_arrival_total_price($ids);
        //amount_paid
        $pur_nums = is_array($results) ? array_column($results, "purchase_number") : [];
        $amount_paid_list = $this->get_list_by_purchase_numbers(array_unique(array_filter($pur_nums)));
        //统计采购总金额
        $total_sum=0;
        //获取合同号集集合
        $compact_numbe_list=$this->get_compact_number_list($pur_nums);
        //获取请款单号集合
        $requisition_number_list=$this->get_requisition_number_list($pur_nums);
        //获取取消未到货状态集合
        $items_id = is_array($results) ? array_column($results, "id") : [];
        $items_id_id=$this->get_cancel_id($items_id);
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
        $customs_code_list=$this->get_customs_code($sku_list);

        $this->load->model('warehouse/Logistics_type_model');
        $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
        $logistics_type_list = array_column($logistics_type_list,'type_name','type_code');

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');
        $prices_purchase_data = [];

        $this->load->model('ali/Ali_order_model');
        $price_value = $this->purchase_db->from("audit_amount")->where_in("id",[2,5])->get()->result_array();

        $price_value_arr = array_column($price_value,null,'id');

        foreach ($results as $key => &$vo) {
            $vo = get_object_vars($vo);
            if( $vo['is_drawback'] == 1)
            {
                $vo['coupon_rate_price'] = sprintf("%.2f",$vo['purchase_unit_price'] / (1+$vo['coupon_rate']));
            }
            if( $vo['maintain_ticketed_point'] == 0 && $vo['pur_ticketed_point']==0.000 ){

                $vo['pur_ticketed_point'] = NULL;
            }

            if (in_array($vo['purchase_type'], [PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])) {//海外

                if ($vo['purchase_type'] == 2) {//海外
                    $price_value = $price_value_arr[5];

                } else {
                    $price_value = $price_value_arr[2];

                }
                if (!empty($price_value)) {
                    if (isset($price_value['headman_start']) && $price_value['headman_start'] <= $vo['real_price'] && $price_value['headman_end'] >=$vo['real_price']) {

                        $vo['amount_name'] = "组长";
                    }
                    if (isset($price_value['director_start']) && $price_value['director_start'] <= $vo['real_price'] && $price_value['director_end'] >= $vo['real_price']) {

                        $vo['amount_name'] = "主管";
                    }
                    if (isset($price_value['manager_start']) && $price_value['manager_start'] <= $vo['real_price'] && $price_value['manager_end'] >= $vo['real_price']) {

                        $vo['amount_name'] = "经理";
                    }
                    if (isset($price_value['deputy_manager_start']) && $price_value['deputy_manager_start'] <=$vo['real_price'] && $price_value['deputy_manager_end'] >= $vo['real_price']) {

                        $vo['amount_name'] = "副经理";
                    }
                    if (isset($price_value['majordomo']) && $price_value['majordomo'] <= $vo['real_price']) {

                        $vo['amount_name'] = "总监";
                    }
                }
            }

            if( empty($vo['is_invalid']) || $vo['is_invalid'] ==0 ) {

                $vo['is_invalid'] = "正常";
            }else if( $vo['is_invalid'] == 1 ) {

                $vo['is_invalid'] = "失效";
            }
            if( !empty($vo['purchase_packaging']) ) {

                $search_start_index = strpos($vo['purchase_packaging'],"[");
                $packaging_string = substr_replace($vo['purchase_packaging']," ",$search_start_index);
                $vo['purchase_packaging'] = $packaging_string;
            }
            $vo['is_scree'] = $this->get_sku_scree($vo['sku']);
            //是否退税
            if( isset($vo['is_drawback']) AND !empty($vo['is_drawback']) ){
                $is_drawback =  $vo['is_drawback'];
            }else{
                $is_drawback =  isset($vo['sg_is_drawback']) ? $vo['sg_is_drawback'] :'';
            }
            //采购主体
            if( isset($vo['sg_purchase_name']) AND !empty($vo['sg_purchase_name']) ){
                $purchase_name =  $vo['sg_purchase_name'];
            }else{
                $purchase_name = isset($vo['purchase_name']) ? $vo['purchase_name'] :'';
            }

            //结算方式
            if( isset($vo['account_type']) AND !empty($vo['account_type']) ){
                $account_type =  $vo['account_type'];
            }else{
                $account_type = isset($vo['sg_account_type']) ? $vo['sg_account_type'] :'';
            }

            //支付方式
            if( isset($vo['pay_type']) AND !empty($vo['pay_type']) ){
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

            if( empty($vo['supplier_source']) ) {

                $vo['supplier_source_ch'] = "未知";
            }

            $vo['supplier_source'] = $vo['supplier_source_ch'];

            //采购员
            if( isset($vo['buyer_name']) AND !empty($vo['buyer_name']) ){
                $results[$key]['buyer_name'] =  $vo['buyer_name'];
            }else{
                $results[$key]['buyer_name'] =  isset($vo['sg_buyer_name']) ? $vo['sg_buyer_name'] :'';
            }

            //供应商
            if( isset($vo['supplier_name']) AND !empty($vo['supplier_name']) ){
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
            //判断线上账期付款提醒状态
            $productStatus = $this->get_productStatus($vo['product_status']);
            $results[$key]['product_status'] = ( NULL != $productStatus )?$productStatus:"未知";
            $results[$key]['pay_notice'] = formatAccoutPeriodTime($results[$key]['account_type'], $results[$key]['pay_status'], $results[$key]['need_pay_time'], $results[$key]['surplus_quota']);
            $results[$key]['purchase_order_status_name'] = isset($vo['purchase_order_status'])?getPurchaseStatus($vo['purchase_order_status']):'';
            $results[$key]['suggest_order_status_name'] = isset($vo['suggest_order_status'])?getPurchaseStatus($vo['suggest_order_status']):'';
            $results[$key]['source_name'] = isset($vo['source'])?getPurchaseSource($vo['source']):'';//采购单来源
            $results[$key]['is_new'] = isset($vo['is_new'])? getProductIsNew($vo['is_new']):'';//是否新品
            $results[$key]['is_drawback'] = isset($is_drawback)?getIsDrawbackShow($is_drawback):'';//是否退税
            $results[$key]['is_expedited'] = isset($vo['is_expedited'])?getIsExpedited($vo['is_expedited']):'';//采购单加急
            $results[$key]['is_ali_order'] =getIsAliOrder(isset($vo['is_ali_order'])?$vo['is_ali_order']:0);
            $results[$key]['is_ali_price_abnormal'] =getIsAliPriceAbnormal(isset($vo['is_ali_price_abnormal'])?$vo['is_ali_price_abnormal']:0);
            $results[$key]['remark'] = $vo['ali_order_status'];
            $is_inspection = (isset($vo['is_inspection']) && $vo['is_inspection'] == 1)?"不商检":"商检";
            if((isset($vo['is_inspection']) && $vo['is_inspection'] == 0)) {

                $is_inspection="未知";
            }
            $results[$key]['is_inspection'] = $is_inspection;
            $results[$key]['warehouse_code'] = isset($warehouse_code_list[$vo['warehouse_code']])?$warehouse_code_list[$vo['warehouse_code']]:'';
            if(in_array($vo['purchase_order_status'],[PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,PURCHASE_ORDER_STATUS_CANCELED])){
                $results[$key]['is_destroy'] = getDestroy($vo['is_destroy']);
            }else{
                $results[$key]['is_destroy'] = '';
            }
            $results[$key]['account_type'] = isset($settlement_code_list[$account_type])?$settlement_code_list[$account_type]:'';
            $results[$key]['purchase_price'] = round($vo['purchase_unit_price'] * $vo['confirm_amount'],3);
            $results[$key]['purchase_name'] = !empty($purchase_name) ? get_purchase_agent($purchase_name) : '';
            $results[$key]['pay_status'] = $vo['pay_status'];
            $results[$key]['pay_status_name'] = isset($vo['pay_status'])?getPayStatus($vo['pay_status']):"";
            $results[$key]['pay_finish_status'] = getPayFinishStatus($vo['pay_finish_status']);
//            $results[$key]['logistics_trajectory'] = '接口获取1688物流信息';
            $order_item = isset($amount_storage_list[$vo['id']]) ? $amount_storage_list[$vo['id']] : [];
            $results[$key]['amount_storage'] = $this->count_amount_storage($order_item);
            $amount_paid_key = $vo['purchase_number'] . "_" . $vo['sku'];
            $results[$key]['amount_paid'] = isset($amount_paid_list[$amount_paid_key])?sprintf("%.3f",$amount_paid_list[$amount_paid_key]):0;
            $results[$key]['amount_paid'] += (isset($vo['ca_product_money'])?$vo['ca_product_money']:0) + (isset($vo['ca_process_cost'])?$vo['ca_process_cost']:0);
            $results[$key]['overdue_days'] = $this->diffBetweenTwoDays($vo['plan_product_arrive_time'], $vo['arrival_date']);
            $results[$key]['is_overdue'] = $this->diffBetweenTwoDays($vo['plan_product_arrive_time'], $vo['arrival_date']) == 0 ? "否" : '是';
            $results[$key]['plan_product_arrive_time'] = strtotime($vo['plan_product_arrive_time']) > 0 ? $vo['plan_product_arrive_time'] : date('Y-m-d H:i:s');
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

            /*            if (!empty($vo['cargo_company_id'])&&!empty($vo['express_no'])){
                            $results[$key]['logistics_trajectory'] = $vo['cargo_company_id'].'-'.$vo['express_no'];
                        }else{
                            $results[$key]['logistics_trajectory'] = '';
                        }
                        unset($results[$key]['cargo_company_id']);
                        unset($results[$key]['express_no']);*/
            // 获取物流信息
            if($vo['source'] == 2){//1688网采单
                $status = 1;
            }else{
                $status = 0;
            }
            $cargo_companys = $this->purchase_db->from("purchase_logistics_info")->select("cargo_company_id,express_no")
                ->where("purchase_number",$vo['purchase_number'])
                ->where("sku",$vo['sku'])
                ->get()->result_array();

            if(!empty($cargo_companys)){
                foreach ($cargo_companys as $_k => $item){
                    $results[$key]['logistics_info'][] = [
                        'cargo_company_id' => $item['cargo_company_id'],
                        'express_no'       => $item['express_no'],
                        'status' => $status
                    ];
                }
            }else{
                $results[$key]['logistics_info'] = [];
            }

            //一级产品线
            if (isset($vo['product_line_id'])){
                $this->load->model('product_line_model','product_line',false,'product');
                $first_product_line = $this->product_line->get_all_parent_category($vo['product_line_id'],'asc');
                $results[$key]['first_product_line'] = $first_product_line;
            }
            //已开票金额 已开票数量*含税单价
            $results[$key]['invoiced_amount'] = bcmul($vo['invoices_issued'],$vo['purchase_unit_price']);

            $results[$key]['is_relate_ali'] = !empty($vo['is_relate_ali'])?$vo['is_relate_ali']:0;
            //开票品名、开票单位
            if(in_array($vo['purchase_order_status'], [PURCHASE_ORDER_STATUS_WAITING_QUOTE,PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT,PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER])){
                $results[$key]['invoice_name'] = isset($sku_arr[$vo['sku']])?$sku_arr[$vo['sku']]['export_cname']:'';
                $results[$key]['issuing_office'] = isset($sku_arr[$vo['sku']])?$sku_arr[$vo['sku']]['declare_unit']:'';
            }

            $results[$key]['export_tax_rebate_rate'] = (isset($sku_arr[$vo['sku']]) && !empty( $sku_arr[$vo['sku']]['tax_rate'] ))?$sku_arr[$vo['sku']]['tax_rate']:'';
            $results[$key]['customs_code'] = isset($customs_code_list[$vo['sku']])?$customs_code_list[$vo['sku']]:'';
            unset($results[$key]['surplus_quota']);
            $results[$key]['modify_remark'] = '点击查看其他备注';

            $results[$key]['destination_warehouse'] = isset($warehouse_list[$vo['destination_warehouse']])?$warehouse_list[$vo['destination_warehouse']]:'';
            $results[$key]['logistics_type'] = isset($logistics_type_list[$vo['logistics_type']])?$logistics_type_list[$vo['logistics_type']]:'' ;
            $results[$key]['state_type'] = getProductStateType((int)$vo['state_type']);
            $priver_number_price = $this->getSkuLastPurchasePrice($vo['sku']);
            $results[$key]['last_purchase_price'] = $priver_number_price;
            $results[$key]['lack_quantity_status']     = intval($results[$key]['lack_quantity_status'])==1 ?'欠货':'未欠货';//是否欠货
            $results[$key]['is_entities_lock']      = ($vo['lock_type'] == LOCK_SUGGEST_ENTITIES) ? '锁单中' : '未锁单';
            $results[$key]['supplier_status']      = ($vo['supplier_status'] == IS_DISABLE) ? '禁用' : '未禁用';
            $results[$key]['product_img_url_thumbnails'] = '';
            $results[$key]['link_me'] = $this->Ali_order_model->Wangwang($results[$key]['supplier_code']);

            preg_match("/offer\/[\w]+\.html/",$vo['product_cn_link'],$pdt_tongkuan);
            $results[$key]['pdt_tongkuan'] = !empty($pdt_tongkuan)?$vo['product_cn_link']:'';
        }
        $key_lists = [];
        if (!empty($role)) {
            $roles = json_decode($role['role'], TRUE);
            $keyss = $this->table_columns();
            foreach ($roles as $v) {
                if (isset($keyss[$v])){
                    $key_table_l[$v] = $keyss[$v];
                }
            }

            foreach ($key_table_l as $k=>$val) {
                $key_info['key'] = $k;
                $key_info['name'] = $val;
                array_push($key_lists,$key_info);
            }

        } else {

            // $data = $results;
            //未编辑列表显示（默认30列）
            $key_table = $this->table_header();
            foreach ($key_table as $k => $v){
                $key_info['key'] = $k;
                $key_info['name'] = $v;
                array_push($key_lists,$key_info);
            }
        }
        if( False == $copun_flag) {
            if (!empty($key_lists)) {
                foreach ($key_lists as $list_key => $list_value) {
                    if ($list_value['key'] == 'coupon_rate_price' || $list_value['key'] == 'coupon_rate') {
                        unset($key_lists[$list_key]);
                    }
                }
            }
        }
        $key_lists = array_values($key_lists);

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
                //sg_supplier_name
                $results[$key]['sg_supplier_name'] = "***";
                $results[$key]['sg_supplier_code'] = "***";
            }
        }
        $return_data = [
            'key' => $key_lists,
            'value' => $results,
            'aggregate_data' => $aggregate_data,
        ];
        return $return_data;


    }


    /**
     * 获取退款状态信息
     */
    public function get_ali_order_refund_data($query)
    {
        $res = [];
        try{
            $q = [];
            foreach ($query as $val){
                if(!empty($val) && $val != ' ')$q[] = $val;
            }
            if(count($q) == 0)return $res;
            $data = $this->purchase_db->from('ali_order_refund')
                ->select('pai_number,refund_status')
                ->where_in("pai_number", $q)
                ->get()->result_array();
            foreach ($data as $val){
                $res[$val['pai_number']] = $val['refund_status'];
            }
        }catch (Exception $e){}
        return $res;
    }


    /**
     * 获取出口海关编码
     * @author harvin
     * @date 2019-5-17
     * @param array $sku_list
     * @return array
     */
    public function get_customs_code(array $sku_list){
        $data=[];
        //去除相同元素
        $sku_arr= array_unique($sku_list);
        if(empty($sku_arr)){
             return $data;
        }
        $resulet= $this->purchase_db
               ->select('sku,customs_code')
               ->where_in('sku',$sku_arr)
               ->get('product')->result_array();
        if(empty($resulet)){
            return $data;
        }
        return array_column($resulet, 'customs_code','sku');
    }

    /**
    * 获取取消未到货状态集合
    * @author 2019-3-11
    * @return  array
    */
    public function get_cancel_id(array $id){
        $data=[];
        if(empty($id)) return [];
       $items_id = format_query_string($id);//将数组转换为字符串
       $cancel_detail_db= $this->purchase_db;
       $cancel_detail_sql = 'SELECT a.`cancel_id`,a.`items_id`,b.`audit_status` FROM `pur_purchase_order_cancel_detail` a left join `pur_purchase_order_cancel` b on b.id=a.cancel_id
      WHERE `items_id` IN ('.$items_id.')';

       $cancel_detail = $cancel_detail_db->query($cancel_detail_sql)->result_array();
       if(empty($cancel_detail))  {
           return [];
       }
       foreach ($cancel_detail as $val) {
         $data[$val['items_id']] = $val['audit_status'];

       }
       return $data;
    }
    /**
     *获取采购合同集合
     * harvin 2019-3-6
     * @return array
     */
   public function get_compact_number_list(array $pur_nums){
       if (empty($pur_nums)) {
            return [];
        }
        $compact = $this->purchase_db
                ->select('purchase_number,compact_number')
                ->where_in('purchase_number', $pur_nums)
                ->get('pur_purchase_compact_items')
                ->result_array();
        if (empty($compact)) {
            return [];
        }
        return array_column($compact, 'compact_number','purchase_number');
    }
  /**
   * 获取请款单集合
   * @author harvin 2019-3-6
   * @return array
   */
  public function get_requisition_number_list(array $pur_nums){
      if (empty($pur_nums)) {
            return [];
        }

        $order_pay_detail = $this->purchase_db
                ->select('requisition_number,purchase_number,sku')
                ->where_in('purchase_number', $pur_nums)
                ->get('purchase_order_pay_detail')
                ->result_array();
        if (empty($order_pay_detail)) {
            return [];
        }
        $data = [];
        foreach ($order_pay_detail as $val) {
            $data[$val['purchase_number']."_".$val['sku']][] = $val['requisition_number'];
        }
        foreach ($data as $key => $value) {
            $data[$key] = implode(',', $value);
        }
        return $data;
    }

    /**
     * 根据supplier_code和采购时间统计采购金额
     * @author Jaden 2019-3-8
     * @param string $supplier_code
     * @param string $starttime
     * @param string $endtime
     * @return array
     */
    public function get_purchase_amount_by_supplier_code($supplier_code,$starttime,$endtime){
        $purchas_arr = [
            'purchase_total_price' => 0,
            'cancel_total_price'   => 0,
            'baosun_price'         => 0,
            'actual_price'         => 0,//实际采购金额=采购金额-取消金额-报损金额
        ];
        if(empty($supplier_code) or empty($starttime) or empty($endtime)){
            return $purchas_arr;
        }
        $query_builder = $this->purchase_db;
        $query_builder->select('ppoi.id,ppoi.purchase_number,ppoi.purchase_amount,ppoi.purchase_unit_price,sum(ppoi.purchase_amount*ppoi.purchase_unit_price) as total_price, loss.loss_totalprice');
        $query_builder->from('purchase_order_items as ppoi');
        $query_builder->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $query_builder->join('purchase_order_reportloss as loss', 'ppoi.purchase_number=loss.pur_number AND ppoi.sku=loss.sku and loss.status=4','left');
        $query_builder->where('ppo.supplier_code', $supplier_code);
        $query_builder->where('ppo.audit_time>=', $starttime);
        $query_builder->where('ppo.audit_time<=', $endtime);
        $results = $query_builder->group_by('ppoi.id')->get()->result_array();

        $purchase_total_price = !empty($results) ? array_sum(array_column($results, 'total_price')) : 0;//采购金额
        $loss_totalprice      = !empty($results) ? array_sum(array_column($results, 'loss_totalprice')) : 0;//报损金额
        //取消金额
        $ids                = !empty($results) ? array_column($results, "id") : [];
        $cancel_ctq_data    = $this->count_arrival_total_price($ids);
        $cancel_total_price = !empty($cancel_ctq_data) ? array_sum(array_column($cancel_ctq_data, 'item_total_price')) : 0;
        $purchas_arr        = [
            'purchase_total_price' => $purchase_total_price,
            'cancel_total_price'   => $cancel_total_price,
            'baosun_price'         => $loss_totalprice,
            'actual_price'         => $purchase_total_price - $cancel_total_price - $loss_totalprice,//实际采购金额=采购金额-取消金额-报损金额
        ];

        return $purchas_arr;
    }

    /**
     * 采购主体界定
     * @author Jaden
     * @param string $is_drawback  是否退税
     * @param string $supplier_code  供应商代码
     * @return string
     */
    public function get_subject_title($is_drawback,$supplier_code = null){
        // 不退税的，生成采购单时，采购主体都默认为香港易佰
        if(intval($is_drawback) == PURCHASE_IS_DRAWBACK_N) return 'HKYB';

        return 'SZYB';

        $current_month         = date('Y-m');// 当前时间月份

        $purchase_agent = $this->rediss->getData($supplier_code.'-'.$current_month);
        if(empty($purchase_agent)){
            $purchase_agent = $this->get_purchase_agent_by_supplier_code($supplier_code,$is_drawback);
            $this->rediss->setData($supplier_code.'-'.$current_month,$purchase_agent);
        }

        return $purchase_agent;
    }

    /**
     * 采购主体界定（按照 采购单的金额、需求规则计算 ） 计算当月该供应商的采购主体
     * @author Jolon
     * @param string $supplier_code  供应商代码
     * @return string
     */
    public function get_purchase_agent_by_supplier_code($supplier_code,$is_drawback = 0){

        $this->load->model('supplier/Supplier_purchase_amount');
        $current_month         = date('Y-m');// 当前时间月份

        // 退税的，生成采购单时，采购主体是深圳易佰或者前海易佰
        $limit_amount          = 50000;// 界定金额
        $supplierInfo          = $this->supplier_model->get_supplier_info($supplier_code);
        $supplier_create_month = substr($supplierInfo['create_time'], 0, 7);
        $supplier_create_month = (empty($supplier_create_month) or $supplier_create_month == '0000-00') ? $current_month : $supplier_create_month;


        // 步骤1：供应商在19年3月份之前创建的，1-3月月均采购额<5万的，4月份的生成的采购单的采购主体默认为“前海易佰”。
        $current_month_up = '2019-05';// 该功能上线时间
        if($current_month == $current_month_up){// 仅在  该功能上线时间月份 运行
            if(strtotime($supplierInfo['create_time']) < strtotime($current_month_up)){
                $month_3 = date('Y-m', strtotime(" -3 month")).'-01';
                $month_2 = date('Y-m', strtotime(" -2 month")).'-01';
                $month_1 = date('Y-m', strtotime(" -1 month")).'-01';
                $purchase_price_arr3 = $this->Supplier_purchase_amount->get_calculate_amount($month_3,$supplier_code);
                $purchase_price_arr2 = $this->Supplier_purchase_amount->get_calculate_amount($month_2,$supplier_code);
                $purchase_price_arr1 = $this->Supplier_purchase_amount->get_calculate_amount($month_1,$supplier_code);
                $month_3_price = isset($purchase_price_arr3['actual_price'])?$purchase_price_arr3['actual_price']:0;
                $month_2_price = isset($purchase_price_arr2['actual_price'])?$purchase_price_arr2['actual_price']:0;
                $month_1_price = isset($purchase_price_arr1['actual_price'])?$purchase_price_arr1['actual_price']:0;

                $actual_price_total = $month_3_price + $month_2_price + $month_1_price;// 三个月的 总金额
                if(empty($actual_price_total) or $actual_price_total < $limit_amount * 3){
                    return 'QHYB';
                }
            }
        }

        // 步骤2：供应商在19年4月或者4月之后创建，创建当月，生成的采购单的采购主体都默认为“前海易佰”。
        if(strtotime($supplierInfo['create_time']) >= strtotime($current_month_up)
            and $supplier_create_month == $current_month){
            return 'QHYB';
        }

        // 步骤3：在步骤1、步骤，界定清楚深圳易佰、前海易佰后
        // 步骤3：1、一旦某月采购额<5万，需记录接下来2个月的采购总额，如果这3个月（含变化当月）<5万，
        //          则第4个月的生成的采购单的采购主体变更为前海易佰（变化当月，变化次月、变化起第3月采购主体依旧不变）；
        //          如果>5万，则第4个月继续保持为深圳易佰。（分析：如果 前三个月金额都小于 5万 则为前海易佰，否则为深圳易佰）
        // 步骤3：2、一旦某月采购额>5万，则次月立马变成  “深圳易佰”。（分析：金额>5的后三个月 都是 深圳易佰）
        $month_list_3 = [];
        for($i = 3;$i > 0;$i -- ){// 获取前三个月的月份
            $month_list_3[] = date('Y-m',strtotime(" -$i month"));
        }
        $flag = true;// 假设为前海易佰
        foreach($month_list_3 as $month_value){
            $begin_time         = $month_value.'-01';
            $purchase_price_arr = $this->Supplier_purchase_amount->get_calculate_amount($begin_time,$supplier_code);
            if(!empty($purchase_price_arr) and $purchase_price_arr['actual_price'] >= $limit_amount){
                $flag = false;// 有一个月 金额大于 5万就是深圳易佰
            }
        }

        if($flag){
            return 'QHYB';
        }else{
            return 'SZYB';
        }
    }

    /**
     * 计算 对应的采购单明细ID所 生成的取消未到货单 的取消数量和取消金额
     * @author liwuxue
     * @date 2019/2/14 18:36
     * @param $ids
     * @return array
     */
    public function count_arrival_total_price(array $ids)
    {

        $data = [];
        if (empty($ids)) {
            return $data;
        }
        $rows = $this->purchase_db
            ->from("purchase_order_cancel_detail as cd")
            ->join("purchase_order_cancel as c", "cd.cancel_id=c.id", "left")
            ->select("cd.items_id,cd.cancel_ctq,cd.item_total_price")
            ->where_in("cd.items_id", $ids)
            ->where_in("c.audit_status", [CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_YDC])
            ->get()
            ->result_array();
        if (!empty($rows)) {
            foreach ($rows as $row) {
                if (isset($data[$row['items_id']])) {
                    $data[$row['items_id']]['cancel_ctq'] += $row['cancel_ctq'];
                    $data[$row['items_id']]['item_total_price'] += $row['item_total_price'];
                } else {
                    $data[$row['items_id']]['cancel_ctq'] = 0 + $row['cancel_ctq'];
                    $data[$row['items_id']]['item_total_price'] = 0 + $row['item_total_price'];
                }
            }
        }
        return $data;
    }
    /**
     * 判断采购单 是否是样品
     *      根据采购单的SKU 是否是样品SKU来判断
     * @author Jolon
     * @param string|array $purchase_number 采购单号（数组 则批量验证）
     * @return string|array
     */
    public function is_sample($purchase_number)
    {
        if (is_string($purchase_number)) {
            $purchase_numbers_tmp = [$purchase_number];
        } else {
            $purchase_numbers_tmp = $purchase_number;
        }

        $purchase_sample_list = [];// 为样品采购单
        if ($purchase_numbers_tmp and is_array($purchase_numbers_tmp)) {
            foreach ($purchase_numbers_tmp as $v_number) {
                // 根据采购单的SKU 是否是样品SKU来判断
                $is_sample = $this->purchase_db->select('p.is_sample')
                    ->from('purchase_order as po')
                    ->join('purchase_order_items as poi', 'po.purchase_number=poi.purchase_number', 'left')
                    ->join('product as p', 'poi.sku=p.sku', 'left')
                    ->where('po.purchase_number', $v_number)
                    ->where('p.is_sample', 1)
                    ->group_by('p.is_sample')
                    ->get()
                    ->row_array();
                if ($is_sample) {
                    $purchase_sample_list[$v_number] = $is_sample;
                }
            }
        }

        if (is_string($purchase_number)) {// 返回 int
            return isset($purchase_sample_list[$purchase_number]) ? $purchase_sample_list[$purchase_number] : 0;
        } else {// 返回数据
            return $purchase_sample_list;
        }
    }

    /**
     * 判断合同单 是否是样品
     *      根据采购单的SKU 是否是样品SKU来判断
     * @author Jolon
     * @param string $compact_number 合同号
     * @return array|bool|int|string
     */
    public function is_sample_by_compact($compact_number)
    {
        $this->load->model('compact_model', '', false, 'compact');

        $compact = $this->compact_model->get_compact_one($compact_number);
        if (empty($compact) or empty($compact['items_list'])) return false;

        $is_sample = 0;// 是否是样品
        $purchase_numbers = array_column($compact['items_list'], 'purchase_number');
        foreach ($purchase_numbers as $purchase_number) {
            $is_sample = $this->is_sample($purchase_number);
            if ($is_sample) {
                break;
            }
        }

        return $is_sample;
    }

    protected function getarrival_qty($id)
    {
        $str = $this->purchase_db->select('arrival_qty,instock_qty,breakage_qty')->where('items_id', $id)->get('warehouse_results')->result_array();
        $arrival_qty = 0;
        $instock_qty = 0;
        $breakage_qty = 0;
        foreach ($str as $key => $value) {
            $arrival_qty += $value['arrival_qty'];
            $instock_qty += $value['instock_qty'];
        }
        $data = [
            'arrival_qty' => $arrival_qty,
            'instock_qty' => $instock_qty,
            'breakage_qty' => $breakage_qty
        ];
        return $data;
    }

    /**
     *
     * @author liwuxue
     * @date 2019/2/14 18:07
     * @param array $ids
     * @return array
     */
    private function get_qty_by_ids(array $ids)
    {
        $data = [];
        $this->load->model("warehouse/Warehouse_results_model");
        $qty_list = $this->Warehouse_results_model->get_list_by_items_ids($ids, "items_id,arrival_qty,instock_qty,breakage_qty");
        if (!empty($qty_list)) {
            foreach ($qty_list as $item) {
                if (isset($data[$item['items_id']])) {
                    $data[$item['items_id']]['arrival_qty'] += $item['arrival_qty'];
                    $data[$item['items_id']]['instock_qty'] += $item['instock_qty'];
                    $data[$item['items_id']]['breakage_qty'] += $item['breakage_qty'];
                } else {
                    $data[$item['items_id']]['arrival_qty'] = $item['arrival_qty'];
                    $data[$item['items_id']]['instock_qty'] = $item['instock_qty'];
                    $data[$item['items_id']]['breakage_qty'] = $item['breakage_qty'];
                }
            }
        }
        return $data;
    }
    /**
     * 针对 invoices_issued_amount 的优化方法，一次性统一读取数据处理，避免循环嵌套查询db
     * @author liwuxue
     * @date 2019/2/15 10:20
     * @param array $ids purchase_order.id
     * @return array
     */
    public function count_invoices_issued_amount(array $ids)
    {   $data = [];
        if (!empty($ids)) {
            $rows = $this->purchase_db
                ->from("purchase_product_invoice as pi")
                ->join("purchase_order_items as oi", "oi.id=pi.items_id", "left")
                ->join("purchase_order as o", "o.purchase_number=oi.purchase_number", "left")
                ->select("oi.id,pi.invoices_issued,pi.invoiced_amount")
                ->where_in('oi.id', $ids)
                ->get()
                ->result_array();
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    if (isset($data[$row['id']])) {
                        $data[$row['id']]['invoices_issued'] += $row['invoices_issued'];
                        $data[$row['id']]['invoiced_amount'] += $row['invoiced_amount'];
                    } else {
                        $data[$row['id']]['invoices_issued'] = 0 + $row['invoices_issued'];
                        $data[$row['id']]['invoiced_amount'] = 0 + $row['invoiced_amount'];
                    }
                }
            }
        }
        return $data;
    }
    /**
     * 针对 get_amount_storage() 的优化方法，一次性统一读取数据处理，避免循环嵌套查询db
     * @author liwuxue
     * @date 2019/2/15 10:28
     * @param $ids
     * @return array
     */
    public function get_amount_storage_list(array $ids)
    {
        $this->load->model("Purchase_order_items_model");
        $list = [];
        if (!empty($ids)) {
            $list = $this->Purchase_order_items_model->get_list_by_ids($ids, "id,upselft_amount,purchase_unit_price,");
        }
        return is_array($list) ? array_column($list, null, "id") : [];
    }

    /**
     * 计算入库金额
     * get_amount_storage_list 获取列表后，针对单个数据计算 == get_amount_storage()
     * @author liwuxue
     * @date 2019/2/14 17:25
     * @param $order
     * @return float
     */
    public function count_amount_storage(array $order)
    {
        $count = 0;
        if (isset($order['purchase_unit_price']) && isset($order['upselft_amount'])) {
            $count = format_two_point_price($order['purchase_unit_price'] * $order['upselft_amount']);
        }
        return $count;
    }

    /**
     * 求两个日期之间相差的天数
     * (针对1970年1月1日之后，求之前可以采用泰勒公式)
     * @param string $day1 预计时间
     * @param string $day2 实际时间
     * @return number|string
     * @author harvin
     */
    protected function diffBetweenTwoDays($day1, $day2)
    {

        if($day1 == '0000-00-00 00:00:00'){
            
            return 0;
        }
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);
        if ($second1 < $second2) {
            return sprintf('%.1f', ($second2 - $second1) / 86400);
        } else {
            return 0;
        }
    }
    /**
     * get_amount_paid() 方法的一次性批量获取数据，避免循环读表
     * @author liwuxue
     * @date 2019/2/16 9:28
     * @param $purchase_numbers
     * @return array
     */
    public function get_list_by_purchase_numbers(array $purchase_numbers)
    {
        if (empty($purchase_numbers)) {
            return [];
        }

         $data = [];
         //先获取每个采购单对应的请款申请号
         $requisition_number = $this->purchase_db
            ->select("requisition_number")
            ->where_in("purchase_number", $purchase_numbers)
            ->get("purchase_order_pay_detail")
            ->result_array();
          if(empty($requisition_number)) {
              return $data;
          }

         $requisition_numbers= array_column($requisition_number, 'requisition_number');
         $requisition_numbers= array_unique($requisition_numbers);
        //判断这些请款单付款状态 50.已部分付款,51.已付款
        $order_pay= $this->purchase_db->select('pay_status,requisition_number')
                 ->where_in('requisition_number',$requisition_numbers)
                 ->get('purchase_order_pay')
                 ->result_array();

        $purchase_order=[];
       if(!empty($order_pay)){
           foreach ($order_pay as $vv) {
               if(in_array($vv['pay_status'], ['50','51'])){
                   $purchase_order[]=$vv['requisition_number'];
               }
           }
        if(empty($purchase_order)){
          return $data;
        }

          //按id倒序，保持 purchase_number,sku 值一样的数据多条时和 get_amount_paid() 结果一样
        $rows = $this->purchase_db
            ->select("pay_total,purchase_number,sku,requisition_number")
            ->where_in("requisition_number", $purchase_order)
            ->order_by("id desc")
            ->get("purchase_order_pay_detail")
            ->result_array();
        if (!empty($rows)) {
            foreach ($rows as $key=>$row) {
                $data[$row['purchase_number'] . "_" . $row['sku']][] = $row['pay_total'];
            }
        }
        foreach ($data as $key => $value) {
            $data[$key]= array_sum($value);
        }
       }
        return $data;
    }

    /**
     * 删除指定的元素
     * @param string $tmp 指定元素
     * @param array $data 数组
     * @return bool Description
     * @author harvin 2019-1-9
     * * */
    protected function delkeyarray($tmp, $data)
    {
        $key = array_search($tmp, $data);
        array_splice($data, $key, 1);
        return $data;
    }

    /**
     * 查找采购单明细表 采购单号及对应的sku
     * @param array $ids id
     * @author Manson 2019-10-30
     * * */
    public function get_order_binding_logistics($ids)
    {
        $data = [];
        $order_info = $this->purchase_db->select('id,purchase_number,sku')
            ->from($this->item_table_name)
            ->where_in('id',$ids)
            ->get()
            ->result_array();
        $data['order_info'] = $order_info;

        $express_info = $this->purchase_db->select('b.express_no,b.cargo_company_id,b.carrier_code')->distinct()
            ->from($this->item_table_name.' a')
            ->join('purchase_logistics_info b','a.purchase_number=b.purchase_number AND a.sku = b.sku','left')
            ->where_in('a.id',$ids)
            ->get()
            ->result_array();
        $data['express_info'] = $express_info;
        return $data;
    }
    /**
     * 保存物流单号相关信息
     * @param array $$purchase_number 采购单号
     * @param string $express_no 快递单号
     * @param string $cargo_company_id 快递公司
     * @author harvin 2019-1-8
     * @author Manson 2019-10-30
     * * */
    public function get_order_binding_logistics_save($insert_data, $delete_data,$delete = false)
    {
        $this->load->model('Reject_note_model');
        $this->load->model('warehouse/parcel_urgent_model');
        $this->load->model('Purchase_order_progress_model','m_progress',false,'purchase');

        $this->purchase_db->trans_start();
        foreach($delete_data as $key => $item){//删除历史数据
            $this->purchase_db->delete($this->logistics_info_table_name,$item);
        }
        if(!$delete){
            $this->purchase_db->insert_batch($this->logistics_info_table_name,$insert_data);
        }

        //记录操作日志
        foreach ($insert_data as $key => $item) {
            $log = [
                'record_number' => $item['purchase_number'],
                'record_type' => '采购单',
                'content' => '采购单录入物流单号',
                'content_detail' => sprintf('采购单:%s,SKU:%s,录入物流单号:%s,物流公司:%s',$item['purchase_number'],$item['sku'],$item['express_no'],$item['cargo_company_id'])
            ];
            $this->Reject_note_model->get_insert_log($log);

            //查询是否存在加急包裹
            $have_parcel = $this->parcel_urgent_model->get_one($item['purchase_number']);
            if($have_parcel){
                // 更新采购单包裹加急的信息
                $updateOrderData = [
                    'update_time'   => date('Y-m-d H:i:s'),
                    'push_status'   => 0,//改为未推送
                    'push_res'      => '未推送',//改为未推送
                ];
                $this->parcel_urgent_model->update_logistics(['purchase_order_num' => $item['purchase_number']], $updateOrderData);
            }
            //推送物流单号,快递公司到WMS
            //采购单维度推送
            $pushData[$item["purchase_number"]] = 1;
        }
        $this->purchase_db->trans_complete();
        if ($this->db->trans_status() === false) {
            throw new Exception("录入失败!");
        }
        if (!empty($pushData)){
            $newpushData = $pushData = array_keys($pushData);
        }

        //查询要推送的数据
        $pushData = $this->m_progress->get_push_list($pushData);
        if(!$this->m_progress->push_express_info_to_wms($pushData)){
            throw new Exception("推送到仓库系统失败!");
        }
        // 推送到新仓库系统
        $this->m_progress->push_receive_bind_express($newpushData);
        return true;

    }

    /**
     * 判断是否网采单
     * @param srting purchase_number 采购单号
     * @author harvin 2019-1-8
     * @return bool
     * * */
    public function get_source($id)
    {
        $items = $this->purchase_db->select('purchase_number')->where('id', $id)->get('purchase_order_items')->row_array();
        $source = $this->purchase_db->select('source,purchase_number')->where('purchase_number', $items['purchase_number'])->get('purchase_order')->row();
        if ($source->source == SOURCE_NETWORK_ORDER) {
            return $source->purchase_number;
        } else {
            return NULL;
        }
    }

    /**
     * 获取默认结算比例
     * @author harvin
     * @param type $account_type
     * @return string
     */
    public function getSettlementratiodefault($account_type){
        $settlement_ratio='';
       if(in_array($account_type, ['11','12','13','14'])){
           $settlement_ratio='5%+95%';
       }elseif(in_array($account_type, ['16','17','18','19'])){
           $settlement_ratio='10%+90%';
       }elseif(in_array($account_type, ['21','22','23','24'])){
            $settlement_ratio='15%+85%';
       }elseif(in_array($account_type, ['25','26','27','28'])){
           $settlement_ratio='20%+80%';
       }elseif(in_array($account_type, ['29','30','31','32'])){
           $settlement_ratio='30%+70%';
       }elseif(in_array($account_type, ['20','7','8','9','6'])){
           $settlement_ratio='100%';
       }else{
           $settlement_ratio;
       }
        return $settlement_ratio;

    }

    /**
     * 获取采购单业务线信息
     * @param $purchaseNumbers   array    采购单信息
     * @author:luxu
     * @time:2020/4/24
     **/
    public function getPurchaseNumberTypeId($purchaseNumbers){

        $result = $this->purchase_db->from("purchase_order")->where_in("purchase_number",$purchaseNumbers)
            ->select("purchase_number,purchase_type_id,shipment_type,is_drawback")
            ->get()->result_array();

        if(!empty($result)){

            return array_column($result,NULL,"purchase_number");
        }

        return NULL;
    }

    /**
     * 获取采购单对应的备货单是否是计划系统推送
     *
     **/
    public function getDemandNumberSourceFrom($demandNumbers){

        $result = $this->purchase_db->from("purchase_suggest")->where_in("demand_number",$demandNumbers)
            ->where("purchase_type_id",PURCHASE_TYPE_OVERSEA)
//            ->where_in("purchase_type_id",[PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])
            ->where("source_from",1)
            ->select("demand_number")->get()->result_array();
        if(empty($result)){

            return NULL;
        }
        return array_column($result,"demand_number");
    }


    /**
     * 保存相关信息
     * @param array $purchase_number 采购单号
     * @param array $pay_type $pay_type 支付方式
     * @param array $settlement_ratio 结算比例
     * @param array $freight 运费
     * @param array $is_freight 运费支付
     * @param array $discount 优惠额
     * @param array $freight_formula_mode 运费计算方式
     * @param array $purchase_acccount 采购账号
     * @param array $pai_number 拍单号
     * @param array $plan_product_arrive_time 预计到货时间
     * @author harvin 2019-1-8
     * * */
    public function save_purchase($pay_type, $settlement_ratio, $freight, $is_freight, $discount, $freight_formula_mode, $purchase_acccount, $pai_number, $plan_product_arrive_time, $ids, $shipping_method_id,$purchasing_order_audit)
    {
        try {
            $this->load->model('Reject_note_model');
            $this->load->helper('status_supplier');
            $this->load->model('supplier/supplier_model');
            //开始事物
            $this->purchase_db->trans_begin();
            $purchase_number = [];

            // 采购单号对应的 所有的数据验证是否唯一
            $pay_type_list_tmp                 = [];
            $settlement_ratio_list_tmp         = [];
            $is_freight_list_tmp               = [];
            $freight_formula_mode_list_tmp     = [];
            $purchase_acccount_list_tmp        = [];
            $pai_number_list_tmp               = [];
            $plan_product_arrive_time_list_tmp = [];
            $shipping_method_id_list_tmp       = [];

            foreach ($ids as $key => $id) {
                $items             = $this->purchase_db->select('purchase_number')->where('id', $id)->get('purchase_order_items')->row_array();
                $purchase_number[] = $items['purchase_number'];

                $data = [
                    'pay_type'                       => $pay_type[$id],
                    'first_plan_product_arrive_time' => $plan_product_arrive_time[$id],
                    'plan_product_arrive_time'       => $plan_product_arrive_time[$id],
                    'modify_time'                    => date('Y-m-d H:i:s'),
                    'modify_user_name'               => getActiveUserName(),
                    'shipping_method_id'             => $shipping_method_id[$id],
                ];
                if(in_array($pay_type[$id], [PURCHASE_PAY_TYPE_PUBLIC, PURCHASE_PAY_TYPE_PRIVATE])){
                    $data['source'] = SOURCE_COMPACT_ORDER;
                }

                $pay_type_list_tmp[$items['purchase_number']][]                 = $pay_type[$id];
                $plan_product_arrive_time_list_tmp[$items['purchase_number']][] = $plan_product_arrive_time[$id];
                $shipping_method_id_list_tmp[$items['purchase_number']][]       = $shipping_method_id[$id];
                $is_freight_list_tmp[$items['purchase_number']][]               = $is_freight[$id];
                $freight_formula_mode_list_tmp[$items['purchase_number']][]     = $freight_formula_mode[$id];
                $settlement_ratio_list_tmp[$items['purchase_number']][]         = $settlement_ratio[$id];
                $purchase_acccount_list_tmp[$items['purchase_number']][]        = $purchase_acccount[$id];
                $pai_number_list_tmp[$items['purchase_number']][]               = $pai_number[$id];


                $purchase_order = $this->get_one($items['purchase_number'], false);
                if(($is_disable = supplierIsDisabled($purchase_order['supplier_code'])) !== false){// 验证供应商是否禁用
                    throw new Exception($purchase_order['supplier_code'].'-'.$is_disable);
                }
                $result = $this->supplier_model->is_not_completed($purchase_order['supplier_code']);
                if($result['code'] == false){
                    $data    = $result['data'];
                    $message = implode("<br/>", $data);
                    throw new Exception("采购单号[$purchase_number]供应商[".$purchase_order['supplier_name']."]信息不全，请前往供应商页面进行完善，如下信息：<br>".$message);
                }

                //更新采购单主表
                $this->change_status($items['purchase_number'],PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT);// 统一入口修改采购单状态
                $this->purchase_db->where('purchase_number', $items['purchase_number'])->update('purchase_order', $data);
                //记录采购单信息确认-请款金额相关信息
                //计算该订单总价
                $unit = $this->purchase_db
                    ->select('purchase_unit_price,confirm_amount,freight,discount')
                    ->where('purchase_number', $items['purchase_number'])
                    ->get('purchase_order_items')
                    ->result_array();
                if(empty($unit)){
                    throw new Exception("采购单明细不存在");
                }
                $real_price = 0;
                foreach($unit as $key => $value){
                    $real_price += $value['purchase_unit_price'] * $value['confirm_amount'] + $value['freight'] - $value['discount'];
                }
                //更新采购明细表
                $booler = [
                    'freight'  => format_two_point_price($freight[$id]),
                    'discount' => format_two_point_price($discount[$id]),
                ];
                $this->purchase_db->where('id', $id)->update('purchase_order_items', $booler);

                $res = [
                    'purchase_number'      => $items['purchase_number'],
                    'real_price'           => format_two_point_price($real_price),
                    'is_freight'           => $is_freight[$id],
                    'freight_formula_mode' => $freight_formula_mode[$id],
                    'settlement_ratio'     => $settlement_ratio[$id],
                    'purchase_acccount'    => $purchase_acccount[$id],
                    'pai_number'           => $pai_number[$id],
                    'freight'              => '',//总运费
                    'discount'             => '',//总优惠额
                    'note'                 => "采购单确认提交信息"
                ];
                //有值就更新  没有就新增一条记录
                $re = $this->purchase_db->select('purchase_number,confirm_type')->where('purchase_number', $items['purchase_number'])->get('purchase_order_pay_type')->row_array();
                if(!empty($re)){
                    if($re['confirm_type'] == 2){
                        throw new Exception('此单已在批量编辑采购单页面操作过，只能在批量编辑采购单页面操作');
                    }
                    $this->purchase_db->where('purchase_number', $items['purchase_number'])->update('purchase_order_pay_type', $res);
                }else{
                    $this->purchase_db->insert('purchase_order_pay_type', $res);
                }
            }
            //去除重复值
            $purchase_number = array_unique($purchase_number);
            foreach ($purchase_number as $val) {
                // 验证数据是否唯一
                $pay_type_list_now            = array_unique($pay_type_list_tmp[$val]);
                $plan_product_arrive_time_now = array_unique($plan_product_arrive_time_list_tmp[$val]);
                $shipping_method_id_now       = array_unique($shipping_method_id_list_tmp[$val]);
                $is_freight_now               = array_unique($is_freight_list_tmp[$val]);
                $freight_formula_mode_now     = array_unique($freight_formula_mode_list_tmp[$val]);
                $settlement_ratio_now         = array_unique($settlement_ratio_list_tmp[$val]);
                $purchase_acccount_now        = array_unique($purchase_acccount_list_tmp[$val]);
                $pai_number_now               = array_unique($pai_number_list_tmp[$val]);

                if(count($pay_type_list_now) > 1){ throw new Exception("采购单[$val] 支付方式不唯一");}
                if(count($plan_product_arrive_time_now) > 1){ throw new Exception("采购单[$val] 预计到货时间不唯一");}
                if(count($shipping_method_id_now) > 1){ throw new Exception("采购单[$val] 供应商运输方式不唯一");}
                if(count($is_freight_now) > 1){ throw new Exception("采购单[$val] 运费支付不唯一");}
                if(count($freight_formula_mode_now) > 1){ throw new Exception("采购单[$val] 运费计算方式不唯一");}
                if(count($settlement_ratio_now) > 1){ throw new Exception("采购单[$val] 结算比例不唯一");}
                if(count($purchase_acccount_now) > 1){ throw new Exception("采购单[$val] 采购账号不唯一");}
                if(count($pai_number_now) > 1){ throw new Exception("采购单[$val] 拍单号不唯一");}



                // 获取采购单的总运费及优惠额
               $order_items= $this->purchase_db
                       ->select('sum(freight) as freight,sum(discount) as discount')
                       ->where('purchase_number',$val)
                       ->get('purchase_order_items')
                       ->row_array();
                if(empty($order_items)){
                    throw new Exception('订单明细表不存在');
                }
                $data_pay_type = [
                    'freight'  => isset($order_items['freight']) ? $order_items['freight'] : 0,
                    'discount' => isset($order_items['discount']) ? $order_items['discount'] : 0,
                ];
                $this->purchase_db->where('purchase_number',$val)->update('purchase_order_pay_type',$data_pay_type); //更新采购单信息确认-请款金额相关信息

                $this->Reject_note_model->get_insert_log(//记录操作日志
                    [
                        'record_number'  => $val,
                        'record_type'    => 'PURCHASE_ORDER',
                        'content'        => '采购单确认提交信息',
                        'content_detail' => '采购单号'.$val.'添加提交等信息'
                    ]);

                if ($purchasing_order_audit == PUSHING_BLUE_LING) {
                    //推送蓝凌系统
                  $this->pushing_blue_ling($val);
                }
            }
            if ($this->purchase_db->trans_status() === FALSE) {
                $this->purchase_db->trans_rollback();
                throw new Exception('操作失败');
            } else {
                $this->purchase_db->trans_commit();
                return ['msg' => '成功', 'bool' => TRUE];
            }
        } catch (Exception $exc) {
            $this->purchase_db->trans_rollback();
            return ['msg' => $exc->getMessage(), 'bool' => FALSE];
        }
    }


    /**
     * 获取仓储信息
     * @param：  $purchase_number    string    备货单号
     *           $sku                sku       产品的SKU 信息
     * @return   array          返回备货单，SKU 第一次入口和到货日期
     **/
    private function get_purchase_warehouse( $purchase_number, $sku ) {

        if( empty($purchase_number) ) {

            return NULL;
        }

        $where = array(

            "purchase_number" => $purchase_number,
            "sku" => $sku
        );
        //warehouse_results_main
        $query = $this->db->from('warehouse_results')->select("sku,express_no,instock_date,arrival_date")->where($where);
        $result = $query->order_by("id",'ASC')->limit(1)->get()->result_array();
        if( !empty($result) ) {

            return array_column( $result,NULL,"sku");
        }

        return NULL;
    }

    /**
     * 获取采购单支付信息
     * @param   $purchase_number    string    采购单号
     * @return   array
     **/
    private function get_purchase_pay( $purchase_number ) {

        return $this->db->from("purchase_order_pay_type")->where("purchase_number='".$purchase_number."'")->select("cargo_company_id,express_no,pai_number")->get()->row_array();
    }

    /**
      * 获取请款时间
     **/

    private function get_purchase_pay_type( $purchase_number,$source) {

        // 如果是合同订单，到合同表获取合同号
        if( SOURCE_COMPACT_ORDER == $source ) {

            $query = $this->db->from("purchase_compact_items")->select("compact_number")->where("purchase_number='".$purchase_number."'");
            $result = $query->where("bind=1")->get()->row_array();
            $purchase_number = $result['compact_number'];
        }

        $paytime = $this->db->from("purchase_order_pay")->select("application_time,payer_time")->where("pur_number='".$purchase_number."'")->order_by("id","ASC")->get()->row_array();
        return $paytime;

    }

    /**
      * 获取缺货数量
     **/

    private function get_stock_owes( $sku ) {

        return $this->db->from("stock_owes")->select(" SUM(left_stock) AS left_stock ")->where("sku='".$sku."'")->get()->row_array();
    }

    /**
     * 从备货单获取缺货数量
     * @param: $sku  string  商品SKU
     *         $demand_number  string  备货单号
     **/
    private function get_purchase_stock_owers($sku,$demand_number)
    {
        return $this->db->from("purchase_suggest")->select(" SUM(left_stock) AS left_stock ")->where("sku='".$sku."'")->where("demand_number='".$demand_number."'")->get()->row_array();

    }


    /**
     *  采购单审核到等到到货状态时，同步到订单跟踪模块
     *  @param:   $purchase_number     string     采购单号
     *            $purchase_order      array      采购单状态
     *  @author:  luxu
     **/

    public function purchase_track( $purchase_number, $purchase_status  ) {
        //获取采购单的SKU，采购审核是采购单维度审核，一个采购单对应多个SKU
        $skus = $this->db->select("map.sku,map.demand_number,ps.warehouse_code")
            ->from("purchase_suggest_map AS map")
            ->join("purchase_suggest AS ps","map.demand_number=ps.demand_number","LEFT")
            ->where("map.purchase_number='".$purchase_number."'")
            ->get()
            ->result_array();

        // 获取采购单仓库信息
        $result = $this->get_purchase_warehouse( $purchase_number,NULL);
        $payMessage = $this->get_purchase_pay( $purchase_number);

        $purchase_orders = $this->db->from("purchase_order AS orders")->select("orders.warehouse_code,orders.supplier_code,
        orders.supplier_name,
        orders.source,
        orders.merchandiser_id,
        orders.plan_product_arrive_time,
        orders.buyer_name,
        orders.buyer_id,
        orders.ali_order_status,
        orders.source,
        orders.purchase_type_id,
        orders.audit_time,
        orders.merchandiser_name")->where("orders.purchase_number='".$purchase_number."'")->get()->row_array();

        //print_r($result);
        if( !empty($skus) ) {
            $tskus = array_column($skus,"sku");
            $product_mess = $this->db->from("product")->select("product_name,sku,product_line_id,product_img_url,product_status")->where_in("sku",$tskus)->get()->result_array();
            $product_mess = array_column( $product_mess,NULL,"sku");
            $flagresult = array();
            foreach( $skus as $key=>&$value ) {

                //权均交期
                $delivery_item = $this->Delivery_model->get_delivery_info($value['warehouse_code'],$value['sku']);
                if(!empty($delivery_item['avg_delivery_time'])){
                    $delivery_time = strtotime($purchase_orders['audit_time']) + $delivery_item['avg_delivery_time']*86400;
                }else{

                    $skuDevliyData = $this->purchase_db->from("product")->where("sku",$value['sku'])->select("devliy,original_devliy")->get()->row_array();
                    if(empty($skuDevliyData['devliy']) || $skuDevliyData['devliy'] == 0){

                        if(!in_array($purchase_orders['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){

                            $delivery_time =  strtotime($purchase_orders['audit_time']) + 86400 * 7;
                        }else{
                            $delivery_time =  strtotime($purchase_orders['audit_time']) + 86400 * 40;
                        }
                    }else{

                        if(!empty($skuDevliyData['devliy'])) {
                            $delivery_time = strtotime($purchase_orders['audit_time']) + 86400 * $skuDevliyData['devliy'];
                        }else{
                            $delivery_time = strtotime($purchase_orders['audit_time']) + 86400 * $skuDevliyData['original_devliy'];
                        }
                    }
                }
                if($purchase_status == PURCHASE_ORDER_STATUS_WAITING_ARRIVAL || $purchase_status == PURCHASE_ORDER_STATUS_WAITING_QUOTE) {
                    if (isset($delivery_time)) {

                    $OrderitemsData = [

                        'first_plan_arrive_time' => date("Y-m-d H:i:s",$delivery_time),//新增首次预计到货时间
                        //'plan_arrive_time' => date("Y-m-d H:i:s",$delivery_time),//新增预计到货时间
                    ];

                        $this->purchase_db->where(['sku' => $value['sku'], 'purchase_number' => $purchase_number])->update('purchase_order_items', $OrderitemsData);
                    }
                }
                if( isset($result[$value['sku']]) ) {

                    $value['instock_date']   = $result[$value['sku']]['instock_date']; // 入库时间
                    $value['arrival_date']   = $result[$value['sku']]['arrival_date']; // 到货时间
                }else{
                    $value['instock_date']   = NULL; // 入库时间
                    $value['arrival_date']   = NULL; // 到货时间

                }

                $purchase_items = $this->purchase_db->from("purchase_order_items")->select("confirm_amount,plan_arrive_time")->where("purchase_number='".$purchase_number."'")->where("sku='".$value['sku']."'")->get()->row_array();

                $value['purchase_number'] = $purchase_number;
                $value['purchase_status'] = $purchase_status;
                $value['logistics_company'] = isset( $payMessage['cargo_company_id'])?$payMessage['cargo_company_id']:'';
                $value['courier_number'] = isset($payMessage['express_no'])?$payMessage['express_no']:'';
                $value['create_time'] = date("Y-m-d H:i:s");
                //$value['warehouse_code'] = isset( $purchase_orders['warehouse_code'] )? $purchase_orders['warehouse_code']:''; // 公共仓：仓库从备货单表取 Jolon
                $value['supplier_code'] = isset( $purchase_orders['supplier_code'] )? $purchase_orders['supplier_code']:'';
                $value['supplier_name'] = isset( $purchase_orders['supplier_name'] )? $purchase_orders['supplier_name']:'';
                //source , documentary_id ,merchandiser_name,estimate_time
                $value['source'] = isset( $purchase_orders['source'] )? $purchase_orders['source']:'';
                $value['documentary_id'] = isset( $purchase_orders['merchandiser_id'] )? $purchase_orders['merchandiser_id']:'';
                //$value['documentary_name'] = isset( $purchase_orders['merchandiser_name'] )? $purchase_orders['merchandiser_name']:'';
                $value['estimate_time'] = isset( $purchase_items['plan_arrive_time'] )? $purchase_items['plan_arrive_time']:'';
                $value['buyer_name'] = isset( $purchase_orders['buyer_name'] )? $purchase_orders['buyer_name']:'';
                $value['buyer_id'] = isset( $purchase_orders['buyer_id'] )? $purchase_orders['buyer_id']:'';
                $value['pai_number'] = isset( $payMessage['pai_number'] )? $payMessage['pai_number']:'';
                $value['product_name'] = isset( $product_mess[$value['sku']] )? $product_mess[$value['sku']]['product_name']:'';
                $value['product_line_ch'] = isset( $product_mess[$value['sku']] )? $product_mess[$value['sku']]['product_line_id']:'';
                $value['product_img'] = isset( $product_mess[$value['sku']] )? erp_sku_img_sku($product_mess[$value['sku']]['product_img_url']):'';
                $value['ali_order_status'] = isset( $purchase_orders['ali_order_status'] )? $purchase_orders['ali_order_status']:'';
                //$value['application_time'] = $this->get_purchase_pay_type($purchase_number,$purchase_orders['source']);
                $paytime = $this->get_purchase_pay_type($purchase_number,$purchase_orders['source']);
                $value['application_time'] = $paytime['application_time'];
                $value['payer_time'] = $paytime['payer_time'];
                $stock_owes =$this->get_purchase_stock_owers($value['sku'],$value['demand_number']);
                $value['stock_owes'] = empty($stock_owes['left_stock'])?0:$stock_owes['left_stock'];
                $value['purchase_num'] = $purchase_items['confirm_amount']??0;
                $value['product_status'] = isset( $product_mess[$value['sku']] )? $product_mess[$value['sku']]['product_status']:'';
                if( NULL == $value['arrival_date'] || NULL == $value['estimate_time'] )
                {
                    $value['is_overdue'] = 2;
                }else {
                    $overdue = floor((strtotime($value['arrival_date']) - strtotime($value['estimate_time'])) / 86400);
                    $value['is_overdue'] = ($overdue > 0) ? 1 : 2;
                }
                $flag = $this->db->from("purchase_progress")->where("purchase_number='".$value['purchase_number']."'")->where("sku='".$value['sku']."'")->get()->row_array();
                if( !empty($flag) ) {
                    $dataresult = $this->purchase_db->where("purchase_number='".$value['purchase_number']."'")->where("sku='".$value['sku']."'")->update("purchase_progress",$value);
                }else{
                    $value['purchase_on_way_num'] = $value['warehouse_on_way_num'] = $value['purchase_num'];//初始化内部采购在途数量和仓库在途数量

                    $dataresult = $this->purchase_db->insert('purchase_progress',$value);
                }

                if( $dataresult ) {

                    array_push($flagresult,True);
                }else{

                    array_push($flagresult,False);
                }
            }

            if( in_array(False,$flagresult) ) {
                return False;
            }

            return True;
        }

        return False;
    }

    /**
     * 通过采购单信息和SKU 查询对应备货单号
     * @param $purchase_number   string  采购单编号
     *        $sku               string|array  SKU
     * @time:2020/3/17
     * @author:luxu
     **/
    public function getSuggestDemand($purchase_number = NULL,$sku=NULL){

        $query = $this->purchase_db->from("purchase_suggest_map");
        if( NULL != $purchase_number ){
            if(is_array($purchase_number)){
                $query->where_in("purchase_number", $purchase_number);
            }else {
                $query->where("purchase_number", $purchase_number);
            }
        }

        if( NULL != $sku){
            if(is_array($sku)) {
                $query->where_in("sku", $sku);
            }else{
                $query->where("sku",$sku);
            }
        }
        $demandNumbers = $query->select("id,demand_number")->get()->result_array();
        return $demandNumbers;
    }

    /**
     * 通过备货单号获取备货单是否在锁单中
     * @param $demandNumbers  string|array  备货单号
     * @author:luxu
     * @time:2020/3/17
     **/
    public function getDemandLock($demandNumbers){

        $result = $this->purchase_db->from("purchase_suggest")->where_in("demand_number",$demandNumbers)->where("lock_type",LOCK_SUGGEST_ENTITIES)->select("demand_number")->get()->result_array();
        return $result;
    }

    public function getSuggestDemandData($purchase_number = NULL,$sku=NULL){

        $query = $this->purchase_db->from("purchase_suggest_map");
        if( NULL != $purchase_number ){
            if(is_array($purchase_number)){
                $query->where_in("purchase_number", $purchase_number);
            }else {
                $query->where("purchase_number", $purchase_number);
            }
        }

        if( NULL != $sku){
            if(is_array($sku)) {
                $query->where_in("sku", $sku);
            }else{
                $query->where("sku",$sku);
            }
        }
        $demandNumbers = $query->select("id,demand_number,sku")->get()->result_array();
        return $demandNumbers;
    }

    /**
     * 审核 采购单
     *      1.针对网采单，审核通过，订单状态更改为等到货状态
     *      2.针对合同单，审核通过，订单状态更改为等待生成进货单状态
     *
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @param string $check_status 审核状态
     * @param string $reject_order_note 驳回备注
     * @return array
     */
    public function audit_order($purchase_number, $check_status, $reject_order_note,$usercode=null, $purchase_order=null, $is_audit=true, $logid=0)
    {
//        $this->load->model('purchase_suggest_model');
        $return = ['code' => false, 'msg' => '', 'data' => ''];

        $log_file = APPPATH . 'logs/batch_audit_order_save_'.date('Ymd').'.txt';
        if($logid > 0)file_put_contents($log_file, $this->get_microtime() . "**start {$logid} audit_order on model......\n", FILE_APPEND);
        if($is_audit){
            $purchase_order = $this->get_one($purchase_number, true);

            if (empty($purchase_order)) {
                $return['msg'] = '采购单不存在';
                return $return;
            }
            if ($purchase_order['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT) {
                $return['msg'] = '只有待经理审核状态才需要审核';
                return $return;
            }

            $this->load->model('purchase/purchase_order_extend_model');
            $verify_supplier = $this->purchase_order_extend_model->verify_sku_supplier([$purchase_number]);
            if($verify_supplier !== true){
                $return['msg'] = $verify_supplier;
                return $return;
            }

            /* 查询如果同一采购单号，是否退税、供应商代码、结算方式、支付方式、结算比例是否一致 2019-06-21 */
            if($check_status == 1){
                $check_info = $this->purchase_order_items_model->check_purchase_number_is_disabled($purchase_number);
                if(!empty($check_info['msg']) and $check_info['code']==500){
                    $return['msg'] = $check_info['msg'];
                    return $return;
                }

                //禁用供应商不允许通过
                $supplier_code = $purchase_order['supplier_code'];
                $supplier_info = $this->db->select('status')->from('supplier')->where('supplier_code="'.$supplier_code.'"')->get()->row_array();
                if(empty($supplier_info)){

                    $return['msg'] = '找不到'.$purchase_number.'绑定的供应商';
                    return $return;
                }

                if (in_array($supplier_info['status'],[IS_BLACKLIST,IS_DISABLE])) {

                    $return['msg'] = '供应商['.$supplier_code.']已经禁用或者加入黑名单，无法审核通过';
                    return $return;

                }


                //限制 临时供应商最多只能下8个p
                $order_number = $this->temporary_supplier_order_number($purchase_order['supplier_code'],$purchase_order['source']);
                if($order_number){
                    $return['msg']  = '不满足审核条件。临时供应商最多只能下20个po，若要再下单请转常规供应商，或者绑定新供应商';
                    return $return;
                }
//
            }
            /*end*/
        }

        if (!empty($usercode)) {
            //调用采购接口 获取采购员名
            $this->load->model('user/Purchase_user_model');
            $resurs = $this->Purchase_user_model->get_user_info_by_staff_code($usercode);
            if (isset($resurs['user_name']) && $resurs['user_name']) {
                $user = $resurs['user_name'];
            } else {
                $user = '系统';
            }
        } else {
            $user = NULL;
        }

        $_SESSION['user_name'] = $user;

        if ($check_status == 1) {   // 驳回审核：审核通过->状态变为
            $this->load->helper('status_supplier');
            if( ($is_disable = supplierIsDisabled($purchase_order['supplier_code'])) !== false){
                $return['msg'] = $is_disable;
                return $return;
            }
            $check_status = '审核通过';
            $purchase_type_arr = array(PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH);//国内 FBA

            if ($purchase_order['source'] == SOURCE_COMPACT_ORDER && in_array($purchase_order['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])) { // 1.合同
                $result = $this->change_status($purchase_number, PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER, [], $logid);// 6.等待生成进货单
            }elseif ($purchase_order['source'] == SOURCE_COMPACT_ORDER and in_array($purchase_order['purchase_type_id'],$purchase_type_arr)){
                $result = $this->change_status($purchase_number, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL, [], $logid);// 7.等待到货
            }else {
                $result = $this->change_status($purchase_number, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL, [], $logid);// 7.等待到货

                //如果采购单是欠货,则在包裹加急列表生成一条数据
                if ($purchase_order['lack_quantity_status']==1){
                    $this->load->model('warehouse/parcel_urgent_model');

                    $add_parcel_result = $this->parcel_urgent_model->auto_add_parcel($purchase_number);

                    if (!$add_parcel_result) {
                        $return['msg'] = '生成包裹加急记录失败';
                        return $return;
                    }
                }
            }
            //临时供应商下单次数刷新
            $this->set_temporary_supplier_order_number($purchase_order['supplier_code']);

            // 审核通过后，计算采购单预计到货时间 start
            // 采购单审核时还未付款，只需考虑未入库、未付款的情况
            $this->load->model('calc_pay_time_model');
            $baseSet = $this->calc_pay_time_model->getSetParamData('PURCHASE_ORDER_PAY_TIME_SET');

            if(!SetAndNotEmpty($purchase_order, 'items_list'))$purchase_order = $this->get_one($purchase_number, true);
            $items_list = $purchase_order['items_list'];
            $audit_time = date("Y-m-d 00:00:00");
            $accout_period_time = '0000-00-00';
            foreach($items_list as &$item_value){
                $newPayTime = $this->calc_pay_time_model->calc_pay_time_audit_service($baseSet, $purchase_order['account_type'],$purchase_order['source'],$audit_time, $item_value['plan_arrive_time']);
                if(isset($newPayTime['code']) && $newPayTime['code'] === true){
                    // 更新采购单明细的应付款时间（备货单维度）
                    $this->purchase_db->where('id',$item_value['id'])
                        ->update("purchase_order_items", ["need_pay_time" => $newPayTime['data']]);

                    if(strtotime($accout_period_time) < strtotime($newPayTime['data'])){
                        $accout_period_time = $newPayTime['data'];// 保留最大值
                    }
                }
            }

            if($accout_period_time != '0000-00-00'){
                // 更新PO维度应付款时间
                $this->purchase_db->where(["purchase_number" => $purchase_order['purchase_number']])
                    ->update("purchase_order_pay_type", ["accout_period_time" => $accout_period_time]);
            }
            // 审核通过后，计算采购单预计到货时间  end

            operatorLogInsert([
                    'id' => $purchase_number,
                    'type' => $this->table_name,
                    'user'=>$user,
                    'content' => '审核采购单',
                    'detail' => $check_status,
                ]);
        } else {  // 驳回审核：审核不通过
            $result = $this->reject_order($purchase_number,$reject_order_note);
            operatorLogInsert(
                ['id' => $purchase_number,
                    'type' => $this->table_name,
                    'user'=>$user,
                    'content' => '审核采购单',
                    'detail' => '驳回采购单-原因：' . $reject_order_note
                ]);
            $result = $result['code'];
            $this->Message_model->AcceptMessage('purchase', ['data' => [$purchase_number], 'message' => $reject_order_note, 'user' => getActiveUserName(), 'type' => '采购审核']);
        }
        $this->purchase_db->where('purchase_number', $purchase_number)
            ->update($this->table_name, [
                'audit_time' => date('Y-m-d H:i:s'),
                'audit_name' => getActiveUserName(),
                'audit_note' => $reject_order_note
            ]);

        if ($result) {
            $return['code'] = true;
        } else {
            $return['msg'] = '采购单状态变更失败';
        }
        if($logid > 0)file_put_contents($log_file, $this->get_microtime() . "**end {$logid} audit_order on model......\n", FILE_APPEND);
        return $return;
    }

    /**
       * 获取订单追踪操作的历史记录
     **/
    public function get_progess_history($params) {

        $limit = ( isset($params['limit']) && !empty($params['limit']) )? $params['limit']:20;
        $page = ( isset($params['page']) && !empty($params['page']))? $params['page']:1;
        $result = $this->purchase_db->from("purchase_progress_history")->limit($limit,($page-1)*$limit)->get()->result_array();
        $count =  $this->purchase_db->from("purchase_progress_history")->select(" count(id) as total")->get()->row_array();
        return array(

            'total' => $count['total'],
            'list' =>$result,
            'page_total' => count($result),
            'page'=>$page,
            'limit' => $limit
        );
    }

    /**
     * 获取仓库名称
     * @params $warehouse_code  string  仓库CODE
     **/
    private function getWarehouseName($warehouse_code = NULL){

         if( NULL == $warehouse_code){
             return NULL;
         }

         $warehouseName = $this->purchase_db->from("warehouse")->where("warehouse_code",$warehouse_code)
             ->select("warehouse_name")->get()->row_array();
         return $warehouseName['warehouse_name'];
    }


    /**
     * 采购单批量审核---显示
     * @author harvin
     * @date 2019-5-13
     * @param array $purchase_number_arr
     * @return array
     */
    public function get_batch_audit_order_list(array $purchase_number_arr){
        $res = ['msg'=>'未获取到相应的审核数据！','bool'=>false,'data'=>[]];
        //获取采购单主表信息
        $order = $this->purchase_db
            ->select('A.purchase_number,A.source,A.purchase_order_status,A.supplier_name,A.buyer_name,B.pai_number,A.pay_type,
                A.is_ali_order,B.freight_note,A.change_data_apply_note,A.warehouse_code,C.ship_province,A.is_drawback,
                B.freight, B.discount, B.process_cost,B.change_old_price')
            ->from('purchase_order as A')
            ->join('pur_purchase_order_pay_type as B', 'A.purchase_number=B.purchase_number', 'inner')
            ->join('pur_supplier as C', 'A.supplier_code=C.supplier_code', 'inner')
            ->where_in('A.purchase_number',$purchase_number_arr)
            ->get()->result_array();
        if(empty($order)){
            $res['msg'] = '不存在待采购经理审核订单';
            return $res;
        }
        $item_list = $this->get_order_items($purchase_number_arr);
        foreach ($order as $key => $value) {
            $order[$key]['order_items'] = isset($item_list[$value['purchase_number']])?$item_list[$value['purchase_number']]:[];

            $total_discount = $value['discount'];//总优惠
            $total_freight = $value['freight'];//总运费
            $total_process_cost = $value['process_cost'];//总加工费
            $total_order_money = 0;//总花费
            $total_weight = 0;//总重量
            $total_product_weight = 0;//po 所有备货单的样品包装重量

            $order[$key]['warehouse_name']= $this->getWarehouseName($value['warehouse_code']);
            foreach ($order[$key]['order_items'] as $ke => $val) {
                $w = $val['product_weight']*$val['confirm_amount']/1000;//sku的重量
                $order[$key]['order_items'][$ke]['source']=getPurchaseSource($value['source']);
                $order[$key]['order_items'][$ke]['purchase_order_status']=getPurchaseStatus($value['purchase_order_status']);
                $order[$key]['order_items'][$ke]['supplier_name']=$value['supplier_name'];
                $order[$key]['order_items'][$ke]['pai_number']=$value['pai_number'];
                $order[$key]['order_items'][$ke]['freight_note']=$value['freight_note'];
                $order[$key]['order_items'][$ke]['change_data_apply_note']=$value['change_data_apply_note'];//信息修改申请备注
                $order[$key]['order_items'][$ke]['is_ali_order']=$value['is_ali_order'];
                $order[$key]['order_items'][$ke]['modify_remark']=$val['modify_remark'];//修改采购数量货仓库的备注
                $order[$key]['order_items'][$ke]['purchase_amount']=$val['purchase_amount'];//需求数量
                $order[$key]['order_items'][$ke]['last_purchase_price']= $value['is_drawback'] == 1 ? $val['last_purchase_price'] * (1 - ($val['ticketed_point']/100)) : $val['last_purchase_price'];//上次采购单价
                $order[$key]['order_items'][$ke]['product_weight_sku']= $w;
                $order[$key]['order_items'][$ke]['product_img_url']=erp_sku_img_sku($val['product_img_url']);// 图片
                $total_order_money += $val['order_money'];
                $total_product_weight += $order[$key]['order_items'][$ke]['product_weight_sku'];
                $total_weight += $w;

                if( $val['temporary_plan_arrive_time'] == '0000-00-00 00:00:00'){
                    $order[$key]['order_items'][$ke]['temporary_plan_arrive_time'] = NULL;
                }
            }

            //获取采购单的参考运费配置
            $freight_rule = $this->warehouse_model->get_fright_rule_by_warehouse_code($value['warehouse_code'],$value['ship_province']);
            if (empty($freight_rule)){
                $temp_reference_freight = 0;
            }else{
                $temp_reference_freight = $freight_rule['first_weight_cost']+(ceil(format_two_point_price($total_product_weight))-1)*$freight_rule['additional_weight_cost'];
            }
            $old_price = [];
            if(SetAndNotEmpty($value, 'change_old_price')){
                try{
                    $old_price = json_decode($value['change_old_price'], true);
                }catch (Exception $e){}
            }

            $order[$key]["old_freight"]           = isset($old_price['freight']) ? $old_price['freight']: 0;
            $order[$key]["old_discount"]          = isset($old_price['discount']) ? $old_price['discount']: 0;
            $order[$key]["old_process_cost"]      = isset($old_price['process_cost']) ? $old_price['process_cost']: 0;

            $order[$key]['total_discount'] = sprintf("%.3f",$total_discount);
            $order[$key]['total_freight'] = sprintf("%.3f",$total_freight);
            $order[$key]['total_process_cost'] = sprintf("%.3f",$total_process_cost);
            $order[$key]['total_weight'] = sprintf("%.3f",$total_weight);
            $order[$key]['total_order_money'] = sprintf("%.3f",$total_order_money);
            $order[$key]['total_reference_freight'] = sprintf("%.3f",$temp_reference_freight);//po总参考运费
            $order[$key]['is_ali_order'] = $value['is_ali_order'] == 1? "是": "否";
            $order[$key]['purchase_order_status'] = $value['purchase_order_status'];
            $order[$key]['purchase_order_status_name'] = getPurchaseStatus($value['purchase_order_status']);
            unset($order[$key]['source']);
            unset($order[$key]['pai_number']);
            unset($order[$key]['supplier_name']);
            unset($order[$key]['freight_note']);
            unset($order[$key]['change_data_apply_note']);
        }

      return ['msg'=>'获取成功','bool'=>TRUE,'data'=>$order];
    }
    /**
     * 货采购明细表
     * @author harvin
     * @param array $purchase_number
     * @return mixed
     */
    public function get_order_items($purchase_number){
        $res = [];
        $order_times= $this->purchase_db->select('a.temporary_plan_arrive_time,a.plan_arrive_time,a.sku,a.freight,a.discount,
            a.process_cost,a.confirm_amount,a.purchase_unit_price,a.modify_remark,a.purchase_amount,b.product_weight,
            a.product_img_url,a.demand_number,b.ticketed_point,a.purchase_number')
            ->from('purchase_order_items a')
            ->join('product b','a.sku=b.sku','inner')
            ->where_in('a.purchase_number',$purchase_number)
            ->get()
            ->result_array();

        if(empty($order_times)){
            return $res;
        }
        //获取备货单号集合
        $sku = array_column($order_times, "sku");
        $sku_price = [];
        if(!empty($sku))$sku_price = $this->getSkuLastPurchasePrice($sku, "list");
        foreach ($order_times as $key=>$v) {
            $pur = $v['purchase_number'];
            if(!in_array($pur, array_keys($res)))$res[$pur] = [];
            $order_money=$v['confirm_amount']*$v['purchase_unit_price']+$v['freight']-$v['discount']+$v['process_cost'];
            $v['order_money']= format_price($order_money);
            $v['last_purchase_price'] = in_array($v['sku'], array_keys($sku_price))?$sku_price[$v['sku']]: 0;//上次采购单价
            $res[$pur][] = $v;
        }
        return $res;
    }

    /**
     * 获取供应商最新的支付方式
     * @param :  $supplier_code   string    供应商CODE
     **/

    private function get_supplier_pay($supplier_code) {

        if( empty($supplier_code) ) {

            return NULL;
        }

        return $this->purchase_db->from("supplier_payment_account")->select("payment_method")->where("supplier_code",$supplier_code)->where("is_del=0")->order_by("modify_time","DESC")->get()->row_array();


    }
    /**
     * 驳回 采购单
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @param string $reject_order_note 驳回备注
     * @return mixed
     */
    public function reject_order($purchase_number, $reject_order_note)
    {
        $return = ['code' => false, 'msg' => '', 'data' => ''];
        $this->load->model('purchase/Order_initialization_model');
        $this->load->model('supplier/supplier_buyer_model');
        $purchase_order = $this->get_one($purchase_number, false);
            $order_ststus=[
                PURCHASE_ORDER_STATUS_WAITING_QUOTE,
                PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,
                PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER
            ];

        if (!in_array($purchase_order['purchase_order_status'], $order_ststus)) {
            $return['msg'] = '只有待采购询价,待采购审核,等待生成进货单状态才能驳回';
            return $return;
        }


        $this->purchase_db->trans_strict(true);
        $this->purchase_db->trans_begin();
        try {
              //采购单驳回初始化状态
           $result1=  $this->Order_initialization_model->order_initialization($purchase_number);
            if ($result1) {
                $this->load->model('purchase_suggest_map_model', '', false, 'purchase_suggest');
                $demand_maps = $this->purchase_suggest_map_model->get_one($purchase_number);
                if (empty($demand_maps)) throw new Exception('采购单获取关联需求失败');
                $demand_map_numbers = array_column($demand_maps, 'demand_number');
                $result3 = $this->purchase_suggest_map_model->update_suggest($demand_map_numbers);
                if(empty($result3)) throw new Exception('更新需求单失败');
                if( $purchase_order['purchase_order_status'] == PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER ) {

                    // 获取采购单的SKU 信息

                    $purchase_sku = $this->purchase_db->from("purchase_order_items AS items")
                        ->select("items.demand_number,items.sku,items.purchase_number")
                        ->where(['items.purchase_number'=>$purchase_order['purchase_number']]);
                    $purchase_sku = $purchase_sku->get()->result_array();
                    $purchase_data = array_column($purchase_sku,NULL,"sku");
                    $purchase_sku = array_column($purchase_sku,"sku");
                    $product_supplier = $this->purchase_db->from("product AS p")->join("supplier as s","s.supplier_code=p.supplier_code");
                    $product_supplier = $product_supplier->where_in('p.sku',$purchase_sku)
                        ->select("s.supplier_source,p.supplier_code,p.supplier_name,p.sku,s.payment_method,s.supplier_settlement")
                        ->get()->result_array();

                    foreach( $product_supplier as $key=>$value ) {

                        if( isset($purchase_data[$value['sku']]) ) {
                            //查询之前采购单和产品列表供应商编码是否一致
                            $sug_buyer_id = $purchase_order['buyer_id'];
                            $sug_buyer_name = $purchase_order['buyer_name'];
                            if($purchase_order['supplier_code'] != $value['supplier_code']){
                                $supplier_buyer_info = $this->supplier_buyer_model->get_buyer_one($value['supplier_code'],$purchase_order['purchase_type_id']);
                                $sug_buyer_id = $supplier_buyer_info['buyer_id'];
                                $sug_buyer_name = $supplier_buyer_info['buyer_name'];
                            }
                            $where['demand_number'] = $purchase_data[$value['sku']]['demand_number'];
                            $data['supplier_name'] = $value['supplier_name'];
                            $data['supplier_code'] = $value['supplier_code'];
                            $data['supplier_source'] = $value['supplier_source'];
                            $data['buyer_id'] = $sug_buyer_id;
                            $data['buyer_name'] = $sug_buyer_name;
                            $data['pay_type'] = $value['payment_method'];
                            $data['account_type'] = $value['supplier_settlement'];
                            $res = $this->purchase_db->update("pur_purchase_suggest",$data,$where);
                        }
                    }
                }


                // 采购驳回备注-操作日志
                foreach ($demand_map_numbers as $row) {
                    $data = [
                        'id' => $row,
                        'type' => 'PUR_PURCHASE_SUGGEST',
                        'content' => '采购单驳回',
                        'detail' => $reject_order_note,
                    ];
                    operatorLogInsert($data);
                }
                  $data_order = [
                        'id' => $purchase_number,
                        'type' => 'PURCHASE_ORDER',
                        'content' => '采购单驳回',
                        'detail' => $reject_order_note,
                    ];
                    operatorLogInsert($data_order);

            } else {
                throw new Exception('采购单状态变更失败');
            }
            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('驳回采购单事务提交出错');
            } else {
                $this->purchase_db->trans_commit();
            }
            $return['code'] = true;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * 作废 采购单(整个单一起作废)
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @param string $cancel_order_note 作废备注
     * @return array
     */
    public function cancel_order($purchase_number, $cancel_order_note)
    {
        $return = ['code' => false, 'msg' => '', 'data' => ''];
        $purchase_order = $this->get_one($purchase_number, false);
        if (!in_array($purchase_order['purchase_order_status'],[PURCHASE_ORDER_STATUS_WAITING_QUOTE])) {
            $return['msg'] = '只有待采购询价状态才能作废';
            return $return;
        }
        $this->purchase_db->trans_strict(true);
        $this->purchase_db->trans_begin();
        try {
            $result1 = $this->change_status($purchase_number, PURCHASE_ORDER_STATUS_CANCELED);// 采购单状态  14.已作废
            if ($result1) {
                $this->load->model('purchase_suggest/purchase_suggest_model');
                $this->load->model('purchase_suggest/purchase_suggest_map_model');

                $demand_maps = $this->purchase_suggest_map_model->get_one($purchase_number);
                if (empty($demand_maps)) throw new Exception('采购单获取关联需求失败');
                $demand_map_numbers = array_column($demand_maps, 'demand_number');
                $demands = $this->purchase_suggest_model->get_one(0, $demand_map_numbers);
                $demand_ids = array_column($demands, 'id');

                $user_name = getActiveUserName();

                // 采购驳回备注-操作日志
                $demand_ids_number = array_column($demands, 'demand_number', 'id');
                foreach ($demand_ids as $key => $v_id) {
                    $number = isset($demand_ids_number[$v_id]) ? $demand_ids_number[$v_id] : $v_id;

                    // 解除采购单与备货单绑定关系
                    $this->purchase_db->update("purchase_order_items", ["demand_number"=> ''], ["purchase_number"=> $purchase_number, "demand_number" => $number]);

                    $result3 = $this->purchase_suggest_map_model->unbund_map_suggest($purchase_number);
                    if (empty($result3)) throw new Exception('解除采购单号与需求单号绑定关系失败');

                    $result4 = $this->purchase_suggest_model->change_status($v_id);
                    if (empty($result4)) throw new Exception('更新需求单状态失败');

                    //备货单添加作废原因
                    $update_cancel = ['cancel_reason'=>$demands[$key]['cancel_reason'].' '.$cancel_order_note.'-'.date("Y-m-d H:i:s",time()).'-'.$user_name];

                    $result5 = $this->purchase_suggest_model->update_suggest($update_cancel,$v_id);
                    if (empty($result5)) throw new Exception('更新需求单备注失败');


                    $data = [
                        'id'      => $number,
                        'type'    => 'pur_purchase_suggest',
                        'content' => '采购单作废',
                        'detail'  => $cancel_order_note,
                    ];
                    operatorLogInsert($data);
                }


            } else {
                throw new Exception('采购单状态变更失败');
            }


            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('作废采购单事务提交出错');
            } else {
                $this->purchase_db->trans_commit();
            }

            $return['code'] = true;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * 作废 采购单(备货单维度作废)
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @param string $cancel_order_note 作废备注
     * @return array
     */
    public function cancel_order_v2($purchase_number, $cancel_order_note, $demand_number, $cancel_reason_category)
    {
        $return = ['code' => false, 'msg' => '', 'data' => ''];
        $purchase_order = $this->get_one($purchase_number,false);
        $pur_status = $purchase_order['purchase_order_status'];

        if (!in_array($pur_status,[PURCHASE_ORDER_STATUS_WAITING_QUOTE])) {
            $return['msg'] = '只有待采购询价状态才能作废';
            return $return;
        }

        $this->purchase_db->trans_strict(true);
        $this->purchase_db->trans_begin();
        try {

            $this->load->model('purchase_suggest/purchase_suggest_model');
            $this->load->model('purchase_suggest/purchase_suggest_map_model');

            $demand_map = $this->purchase_suggest_map_model->get_one($purchase_number,$demand_number,true);
            if (empty($demand_map)) throw new Exception('采购单获取关联需求失败');

            $demand_info = $this->purchase_suggest_model->get_one(0, $demand_number);
            $sku = $demand_info['sku'];
            $suggest_id = $demand_info['id'];//备货单id
            $purchase_amount = $demand_map['confirm_number'];//确认备货数量

            $demand_map_all = $this->purchase_suggest_map_model->get_one($purchase_number);
            $items_all = $this->purchase_order_items_model->get_item($purchase_number);
            //备货单状态变为“部分到货不等待剩余”和“已作废订单时”，处理状态自动标记为“订单已退款”；(少数少款)
            $lackUpdate =[
                'processing'=>4,
                'update_username' => getActiveUserName(),
                'update_time' => date("Y-m-d H:i:s",time())
            ];
            $this->purchase_db->where("purchase_number",$purchase_number)->update('purchase_lack_data',$lackUpdate);
            $user_name = getActiveUserName();
            //1.先查询数据 避免被解除绑定后查不到,采购单作废直接推送至计划系统
            $purchase_items = $this->Plan_system_model->get_push_data($purchase_number);

            $result3 = $this->purchase_suggest_map_model->update_unbund_status($purchase_number,$demand_number);
            if (empty($result3)) throw new Exception('解除采购单号与需求单号绑定关系失败');

            $result4 = $this->purchase_suggest_model->change_status($suggest_id);
            if (empty($result4)) throw new Exception('更新需求单状态失败');

            //备货单添加作废原因
            $update_cancel = [
                'cancel_reason'=>$demand_info['cancel_reason'].' '.$cancel_order_note.'-'.date("Y-m-d H:i:s",time()).'-'.$user_name,
                'cancel_reason_category' => $cancel_reason_category,//作废原因类别
                'connect_order_cancel' => 2,//关联采购单是否已作废 1.否,2.是
                'is_create_order' => 0,// 是否生成采购单，0=否
            ];

            // 32102 更新供应商和采购员
            $pur_status = $purchase_order['purchase_order_status'];
            $sku_supplier = $sku_supplier = $this->purchase_order_extend_model->get_sku_supplier($sku, $purchase_order['purchase_type_id']);
            if(!empty($sku_supplier)){
                if(SetAndNotEmpty($sku_supplier, 'supplier_code'))$update_cancel['supplier_code'] = $sku_supplier['supplier_code'];
                if(SetAndNotEmpty($sku_supplier, 'supplier_name'))$update_cancel['supplier_name'] = $sku_supplier['supplier_name'];
                if(SetAndNotEmpty($sku_supplier, 'buyer_id'))$update_cancel['buyer_id'] = $sku_supplier['buyer_id'];
                if(SetAndNotEmpty($sku_supplier, 'buyer_name'))$update_cancel['buyer_name'] = $sku_supplier['buyer_name'];
            }

            $result5 = $this->purchase_suggest_model->update_suggest($update_cancel,$suggest_id);
            if (empty($result5)) throw new Exception('更新需求单备注失败');

            // 更新需求单是否已经生成采购单（同备货单）
            $this->purchase_db->where('suggest_demand',$demand_number)->update('purchase_demand',['is_create_order' => 0]);

            $this->purchase_suggest_model->save_sku_cancel_reason(array($sku),$cancel_order_note.'-'.date("Y-m-d H:i:s",time()).'-'.$user_name,$cancel_reason_category);//将拒绝原因保存在后台

            // 解除备货单
            $this->purchase_db->where(["sku"=> $sku, "purchase_number"=>$purchase_number])->update("purchase_order_items", ["demand_number" => 1]);

            //通过sku,与采购单号查询明细表信息
            $item = $this->purchase_order_items_model->get_item($purchase_number,$sku,true);
            if (empty($item)) throw new Exception('更新采购明细不存在');

            if (count($demand_map_all)!=count($items_all)){
                //说明此采购单是一个sku,不同采购单合到一个采购明细中去了
                //此时采购明细的确认采购数量-map表的确认采购数量
                $update_data['confirm_amount'] = $item['confirm_amount']-$purchase_amount;
                $result6 = $this->purchase_order_items_model->update_item($item['id'],$update_data);
                if (empty($result6)) throw new Exception('更新采购明细失败');
            }else{
                //删除采购明细
                $where['id'] = $item['id'];
                $result7 = $this->purchase_db->delete('purchase_order_items',$where);
                if (empty($result7)) throw new Exception('删除采购明细失败');
            }

            //如果map表没有此采购单的数据了,则作废此采购单
            $demand_map_all = $this->purchase_suggest_map_model->get_one($purchase_number);
            if (empty($demand_map_all)){
                $result8 = $this->change_status($purchase_number, PURCHASE_ORDER_STATUS_CANCELED);// 采购单状态  14.已作废
                if (empty($result8)) if (empty($result7)) throw new Exception('采购单状态变更失败');
            }

            //推送计划系统
            if (!empty($purchase_items)){
                if (!$this->Plan_system_model->java_push_purchase_order($purchase_items,2)){
                    throw new Exception('推送计划系统失败');
                }
            }

            $data = [
                'id'      => $demand_number,
                'type'    => 'pur_purchase_suggest',
                'content' => '采购单作废',
                'detail'  => $cancel_order_note,
            ];
            operatorLogInsert($data);

            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('作废采购单事务提交出错');
            } else {
                $this->purchase_db->trans_commit();
                $hash_doc = 'PUR_HANDLE_ONE_KEY_CREATE_DEMAND';
                $this->rediss->delHashData($hash_doc, $suggest_id);
            }

            $return['code'] = true;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * 更新采购单状态
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @param int $new_status 目标状态
     * @return bool
     */
    public function change_status($purchase_number, $new_status = null, $user_info=[], $logid=0)
    {
        $log_file = APPPATH . 'logs/batch_audit_order_save_'.date('Ymd').'.txt';
        if($logid > 0)file_put_contents($log_file, $this->get_microtime() . "** start {$logid} change_status on model......\n", FILE_APPEND);
        try{
            $a_u_id = getActiveUserId();
            $a_u_name = getActiveUserName();
            if(empty($a_u_id))$a_u_id = 0;
            if(empty($a_u_name))$a_u_name = 'admin';
            $user_id = isset($user_info['uid']) ? $user_info['uid']: $a_u_id;
            $username = isset($user_info['username']) ? $user_info['username']: $a_u_name;
            $this->load->model('warehouse/Warehouse_storage_record_model');
            $this->load->model('purchase/Purchase_order_items_model');
            $this->load->model('others/plan_system_model');
            //  $this->load->library('Rabbitmq');
            $purchase_order = $this->get_one($purchase_number, false);

            // 审核通过更新预计到货时间
            if( $new_status == PURCHASE_ORDER_STATUS_WAITING_ARRIVAL){

                $this->audit_estime_data($purchase_number);
                //$this->plan_system_model->push_waiting_arrival_info_to_plan($purchase_number);//等待到货
                $this->plan_system_model->new_push_waiting_arrival_info_to_plan($purchase_number);//等待到
                /**
                    需求:37936 提供接口给ＤＳＳ系统获取ＦＢＡ备货的ＳＫＵ
                 **/
                //$this->plan_system_model->get_waiting_arrival_data($purchase_number);

            }
            //推送采购单状态到计划系统  采购单作废是直接请求java回传的不走这里  需求23437 3.采购单状态变更为[信息修改驳回],[信息修改待审核]不需要回传给计划系统,不回传采购单信息
            if(!in_array($new_status, [PURCHASE_ORDER_STATUS_CANCELED,PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT])){
                $this->push_purchase_order_info_to_plan($purchase_number);
            }

            // 根据入库数量判断采购单、备货单状态
            $new_status_list = $this->Warehouse_storage_record_model->get_order_status_by_instock_qty($purchase_number, $new_status);
            if (empty($new_status_list)){
                $new_status           = $new_status;// 采购单状态
                $suggest_order_status = [];// 备货单状态
            }else{
                $new_status           = $new_status_list['purchase_order_status'];// 采购单状态
                $suggest_order_status = $new_status_list['suggest_order_status'];// 备货单状态
            }

            if ($suggest_order_status) {
                $this->load->model('Redis_model');
                $this->Redis_model->push_send_order_status_queue($purchase_number);
                // 更新备货单的采购状态
                foreach ($suggest_order_status as $demand_number => $status_value) {
                    $result = $this->purchase_db->where('demand_number', $demand_number)
                        ->update($this->suggest_table_name, ['suggest_order_status' => $status_value]);
                }
            }

            if ($purchase_order['purchase_order_status'] == $new_status) return true;

            $old_status_name = getPurchaseStatus($purchase_order['purchase_order_status']);
            $new_status_name = getPurchaseStatus($new_status);

            $update_data = [
                'purchase_order_status' => $new_status,
            ];
            if ($new_status == PURCHASE_ORDER_STATUS_WAITING_ARRIVAL and $purchase_order['waiting_time'] == '0000-00-00 00:00:00') {// 订单变成等待到货时间

                //将等待到货采购单单独回传计划系统
                $this->load->model('others/plan_system_model');
                $this->plan_system_model->push_waiting_arrival_info_to_plan($purchase_number);//等待到货

                $update_data['waiting_time'] = date("Y-m-d H:i:s", time());
                /*************************start*****************************/
                //创建消息队列，待计划任务根据验货规则验证，是否需要生成验货单
                //创建消息队列对象
                $mq = new Rabbitmq();
                //设置参数
                $mq->setQueueName('PUR_SUPPLIER_CHECK_Q_NAME');
                $mq->setExchangeName('PUR_SUPPLIER_CHECK_EX_NAME');
                $mq->setRouteKey('PUR_SUPPLIER_CHECK_R_KEY');
                $mq->setType(AMQP_EX_TYPE_DIRECT);
                //存入消息队列
                $mq->sendMessage($purchase_number);
                /*************************end*****************************/

                $this->load->model('statement/Charge_against_surplus_model');
                $this->Charge_against_surplus_model->insertBatch(['0' => $purchase_number]);// 等待到货的时候加入到 冲销剩余记录

            } elseif (in_array($new_status, [PURCHASE_ORDER_STATUS_ALL_ARRIVED, PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE, PURCHASE_ORDER_STATUS_CANCELED]) and $purchase_order['completion_time'] == '0000-00-00 00:00:00') {// 订单完结时间
                $update_data['completion_time'] = date("Y-m-d H:i:s", time());
            }

            if($new_status == PURCHASE_ORDER_STATUS_WAITING_ARRIVAL and $purchase_order['push_to_wms'] == 0){
                // 变成等待到货的时候如果没有推送仓库则标记为待推送push_to_wms=1
                $update_data['push_to_wms'] = 1;
            }
            if ($new_status == PURCHASE_ORDER_STATUS_WAITING_ARRIVAL and $purchase_order['audit_time'] == '0000-00-00 00:00:00') {// 订单变成等待到货时间
                $update_data['audit_time'] = date("Y-m-d H:i:s", time());
                $update_data['audit_name'] = $username;// 自动审核审核人缺失
            }

            // 处理内部采购在途数量(1,2,3,15状态下不处理)
            if (!in_array($new_status, [PURCHASE_ORDER_STATUS_WAITING_QUOTE,
                PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,
                PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT
            ])
            ) {
                //创建消息队列对象
                $mq = new Rabbitmq();
                //设置参数
                $mq->setExchangeName('PURCHASE_ORDER_INNER_ON_WAY_EX_NAME');
                $mq->setRouteKey('PURCHASE_ORDER_INNER_ON_WAY_R_KEY');
                $mq->setType(AMQP_EX_TYPE_FANOUT);//设置为多消费者模式 分发
                //构造存入数据
                $push_data = [
                    'type'            => 'purchase_order',
                    'purchase_number' => $purchase_number
                ];
                //存入消息队列
                $mq->sendMessage($push_data);
            }

            // 更新采购单状态
            $result = $this->purchase_db->where('purchase_number', $purchase_number)
                ->update($this->table_name, $update_data);
            if( in_array( $new_status,array(PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION,
                PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT,
                PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                PURCHASE_ORDER_STATUS_CANCELED,
                PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT
            ))) {

                $this->purchase_track($purchase_number, $new_status);

                $mq = new Rabbitmq();//创建消息队列对象
                $mq->setQueueName('STATEMENT_CHANGE_STATUS_REFRESH');//设置参数
                $mq->setExchangeName('STATEMENT_CHANGE_STATUS');//构造存入数据
                $mq->setRouteKey('SO_REFRESH_FOR_002');
                $mq->setType(AMQP_EX_TYPE_DIRECT);
                $mq->sendMessage(['purchase_number' => $purchase_number,'add_time' => time()]);// 二维数组，保持格式一致

            }
            //已作废 部分到货不等待剩余状态
            if (in_array($new_status, [PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                PURCHASE_ORDER_STATUS_CANCELED])) {
                //更新开票是否异常状态
                $po_sku_info = $this->Purchase_order_items_model->get_po_sku_info($purchase_number);
                if (!empty($po_sku_info)) {
                    foreach ($po_sku_info as $item) {
                        $this->rediss->set_sadd('INVOICE_IS_ABNORMAL', sprintf('%s$$%s', $item['purchase_number'], $item['sku']));
                    }
                    $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_INVOICE_IS_ABNORMAL');
                }
                //只推送新WMS系统包材的采购单
                $num_rows = $this->purchase_db->select('1')->from("{$this->suggest_map_table_name} a")
                    ->join("{$this->suggest_table_name} b", 'a.demand_number=b.demand_number')
                    ->where(['b.source_from' => 3, 'a.purchase_number' => $purchase_number])->get()->num_rows();
                if($num_rows){
                    /*************************start*****************************/
                    //创建消息队列，采购单完结，存入消息队列，待新wms系统处理
                    //创建消息队列对象
                    $mq = new Rabbitmq();
                    //设置参数
                    $mq->setExchangeName('PUR_NEW_WMS_PM_EX_NAME');
                    $mq->setType(AMQP_EX_TYPE_FANOUT);//设置为多消费者模式 分发
                    //存入消息队列
                    $mq->sendMessage($purchase_number);
                    /*************************end*****************************/
                }
            }

             if ($new_status == PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE){
    /*            $this->load->model('supplier_joint_model');
                $this->supplier_joint_model->pushPurchaseStatus(['purchase_number' => [$purchase_number], 'purchase_status' => PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE]);*/
            }

            if ($new_status == PURCHASE_ORDER_STATUS_WAITING_ARRIVAL) {//变成等待到货状态
                //保存开票品名 开票单位
                $this->Purchase_order_items_model->save_invoice_info($purchase_number);
            }

            if ($new_status == PURCHASE_ORDER_STATUS_WAITING_ARRIVAL) {
                $this->oversea_order_label($purchase_number);//

            }

            if ($new_status == PURCHASE_ORDER_STATUS_WAITING_ARRIVAL) {//变成等待到货状态
              //推送到发运跟踪
               $this->load->model('Shipment_track_list_model','',false,'purchase_shipment');
                $this->Shipment_track_list_model->add_shipment_data($purchase_number);
            }

            // 如果采购单状态变为等待到货状态，就判断对应的SKU 是否为新品或者是否为海外仓首单
            if( $new_status == PURCHASE_ORDER_STATUS_WAITING_ARRIVAL) {
                // 通过采购单查询SKU 是否为新品，如果是新品就更新
                $demandNumbers = $this->getSuggestDemandData($purchase_number);
                $skuDatas = array_column($demandNumbers, "sku");
                $this->Product_model->updateProductNew($skuDatas);

                // 采购单是否为海外仓或者FBA大货
                $isPurchaseType = $this->purchase_db->from("purchase_order")->where("purchase_number", $purchase_number)->where_in("purchase_type_id", [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])->get()->result_array();
                // 如果是海外仓或者FBA大货
                if (!empty($isPurchaseType)) {
                    $purchaseMessage = array_column($isPurchaseType, NULL, "purchase_number");

                    if ((isset($purchaseMessage[$purchase_number]) &&
                        in_array($purchaseMessage[$purchase_number]['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG]))) {
                        $this->Product_model->updateProductOverson($skuDatas);
                    }
                }
            }
            if($logid > 0)file_put_contents($log_file, $this->get_microtime() . " {$logid} cs 17......\n", FILE_APPEND);

            if( in_array($new_status,[PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER,PURCHASE_ORDER_STATUS_WAITING_ARRIVAL])){

                // 1688采购单确认通过，推送采购单新到门户系统
                $purchaseGateWays = $this->getPurchaseGateWays($purchase_number);
                $this->load->model('sync_supplier_model');
                $this->sync_supplier_model->set_push_data($purchaseGateWays, 'ali_order_confirm');
            }
			
            // 合同订单作废时更新到合同上
            if( $new_status == PURCHASE_ORDER_STATUS_CANCELED and $purchase_order['source'] == SOURCE_COMPACT_ORDER){
                //创建消息队列对象
                $mq = new Rabbitmq();
                //设置参数
                $mq->setQueueName('COMPACT_STATUS_REFRESH');//设置参数
                $mq->setExchangeName('COMPACT_STATUS_NAME');
                $mq->setRouteKey('COMPACT_STATUS_UPDATE_R_KEY');
                $mq->setType(AMQP_EX_TYPE_DIRECT);
                $mq->sendMessage(['purchase_number' => $purchase_number]);

            }

            if($logid > 0)file_put_contents($log_file, $this->get_microtime() . " {$logid} cs 18......\n", FILE_APPEND);

            if ($result) {
                operatorLogInsert(['id' => $purchase_number,
                    'type' => $this->table_name,
                    'content' => '变更采购单状态',
                    'detail' => "修改采购单状态，从【{$old_status_name}】 改为【{$new_status_name}】"
                ]);
                return true;
            } else {
                return false;
            }
        }catch (Exception $e){}
        if($logid > 0)file_put_contents($log_file, $this->get_microtime() . "** end {$logid} change_status on model......\n", FILE_APPEND);
    }


    /**
     * 合单规则调度器 - 删除缓存
     * @param string $user_id   当前操作用户
     * @author Jolon
     */
    public function delete_combine_order_cache($user_id){
        // 删除 生成的新PO号
        $po_list_key   = 'onekey_order_counter_'.$user_id;//本key缓存本次请求生成的新PO号
        $po_list_cache = $this->rediss->set_smembers($po_list_key);// 所有生成的采购单号
        if($po_list_cache and is_array($po_list_cache)){
            foreach($po_list_cache as $po_list_cache_value){
                $this->rediss->deleteData($po_list_cache_value);
            }
        }
        $this->rediss->deleteData($po_list_key);

        // 删除 单号计数器
        $num_list_key   = 'onekey_num_counter_'.$user_id;//本key缓存本次请求生成的单号计数器
        $num_list_cache = $this->rediss->set_smembers($num_list_key);// 所有生成的单号计数器
        if($num_list_cache and is_array($num_list_cache)){
            foreach($num_list_cache as $num_list_cache_value){
                $this->rediss->deleteData($num_list_cache_value);
            }
        }
        $this->rediss->deleteData($num_list_key);
    }

    /**
     * 合单规则调度器
     * @desc 根据 指定PO分组编号 生成 不超过30个SKU且SKU不重复的 PO号
     * @param string $num_key   单号计数器（固定值）
     * @param string $user_id   当前操作用户（固定值）
     * @param string $order_sum 合单初级规则PO号（根据业务线、供应商等维度合并分组的PO号，固定值）
     * @param string $sku       当前待合入的SKU
     * @return string 合入的目标单号
     * @author Jolon
     */
    public function get_combine_order_sum($num_key,$user_id,$order_sum,$sku){
        // 缓存数据1必须在 下次生成采购单调用之前清除，否则会与之前的数据重叠
        $num_key      = 'num_counter_'.$user_id."_".$order_sum;
        $num_list_key = 'onekey_num_counter_'.$user_id;//本key缓存本次请求生成的单号计数器
        $po_list_key  = 'onekey_order_counter_'.$user_id;//本key缓存本次请求生成的新PO号

        // 当前PO计数
        $num = $this->rediss->getData($num_key);
        if(empty($num)){
            $this->rediss->setData($num_key,1);
        }
        $num = $this->rediss->getData($num_key);


        $new_order_sum              = $order_sum.'_COUNTER_'.$num;// 组装成新的 PO=1,2,3,4,...,N
        $new_order_sum_set_sku_list = $this->rediss->set_smembers($new_order_sum);// PO下所有的SKU成员

        // 缓存数据1：本次生成的PO号、单号计数器（退出时需要删除、已经其子集合）
        $this->rediss->set_sadd($po_list_key, $new_order_sum);
        $this->rediss->set_sadd($num_list_key, $num_key);

        // 该 PO 下存在 当前SKU 或者 超过 PO下的SKU超过30个
        if(count($new_order_sum_set_sku_list) >= 30 or in_array($sku, $new_order_sum_set_sku_list)){
            $this->rediss->incrData($num_key);// PO单号增加1
            return $this->get_combine_order_sum($num_key,$user_id, $order_sum, $sku);// 当前SKU所属的PO号
        }else{
            $this->rediss->set_sadd($new_order_sum, $sku);// 当前SKU添加到PO集合里面
            return $new_order_sum;// 当前SKU所属的PO号
        }
    }

    /**
     * 采购需求 转换为采购单
     *      （执行分组：需求类型、供应商、采购仓库、是否需要中转仓、转运仓库 分组）
     * @author Jolon
     * @param array $suggest_list 采购需求信息
     * @param int $purchase_type_id 采购需求类型(1.国内仓,2.海外仓，3.FBA)
     * @return array  merchandiser_name
     */
    public function get_convert_suggest($suggest_list)
    {
        $return = ['code' => false, 'msg' => '', 'data' => '','lock_success_list' => []];
//        if ($purchase_type_id != 1 and $purchase_type_id != 2 and $purchase_type_id != 3) {
//            $return['msg'] = '采购需求类型只能是国内仓或海外仓或FBA';
//            return $return;
//        }
        if (empty($suggest_list)) {
            $return['msg'] = '采购需求数据缺失';
            return $return;
        }

        // 缓存有效期，过长导致占用很久才能释放（1个备货单 +1 秒锁定时间，最长锁定120秒）
        $ttl_session_key          = 1 + count($suggest_list) * 0.5;
        if($ttl_session_key > 45){ $ttl_session_key = 45; }

        $this->load->model('product_model', '', false, 'product'); // 产品信息
        $this->load->model('ware/Warehouse_model'); // 仓库信息
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $pertain_wms_list = array_column($warehouse_list,'pertain_wms','warehouse_code');


        $user_id = getActiveUserId();
        $user_id = $user_id ? $user_id : 'admin';

        /*******************************************Start：清除缓存****************************************************/
        $this->delete_combine_order_cache($user_id);// 非常重要，请勿随意改动（分为两部分：需要包括 foreach ($suggest_list) ）
        /*******************************************End：清除缓存******************************************************/

        // 备货单根据 采购数量 降序排序
        $purchase_amount_sort = array_column($suggest_list,'purchase_amount');
        array_multisort($purchase_amount_sort ,SORT_DESC,$suggest_list);

        $orders = [];

        foreach ($suggest_list as $suggest) {
//            if($suggest['purchase_type_id'] == PURCHASE_TYPE_FBA_BIG)$suggest['warehouse_code'] = 'TX30';
            // 验证 需求是否被占用
            $session_key = 'SUGGEST_' . $suggest['demand_number'];
            if (!$this->rediss->getData($session_key)) {
                $this->rediss->setData($session_key, '1', $ttl_session_key); //设置缓存和有效时间
                $return['lock_success_list'][] = $suggest['demand_number'];
            } else {
                foreach($return['lock_success_list'] as $sugg_id){
                    $session_key = 'SUGGEST_' . $sugg_id;
                    $this->rediss->deleteData($session_key);
                }
                $return['msg'] = '采购需求号（' . $suggest['demand_number'] . ' ）已被其他进程占用';
                return $return;
            }
            $sku_info = $this->product_model->get_product_info($suggest['sku']);
            if(!empty($sku_info['product_name'])) $suggest['product_name'] = $sku_info['product_name'];
            if(!empty($sku_info['supplier_code'])) $suggest['supplier_code'] = $sku_info['supplier_code'];
            if(!empty($sku_info['supplier_name'])) $suggest['supplier_name'] = $sku_info['supplier_name'];
            if(!empty($sku_info['purchase_price'])) $suggest['supplier_price'] = format_two_point_price($sku_info['purchase_price']);
            if(!empty($sku_info['ticketed_point'])) $suggest['ticketed_point'] = $sku_info['ticketed_point'];

            // 采购仓库一致 改为 公共仓一致即可
            if(!isset($pertain_wms_list[$suggest['warehouse_code']]) or empty($pertain_wms_list[$suggest['warehouse_code']])){
                $return['msg'] = 'SKU：'.$suggest['sku'].' 仓库：'.$suggest['warehouse_code']." 的公共仓不能为空";
                return $return;
            }else{
                $suggest['pertain_wms'] = $pertain_wms_list[$suggest['warehouse_code']];
            }

            // 根据 需求类型、供应商、采购仓库、是否需要中转仓、转运仓库、物流类型分组
             if (in_array($suggest['purchase_type_id'], [PURCHASE_TYPE_OVERSEA])) {// 海外仓 采购需求数据合并
                 if($suggest['source_from']!=1 && empty($suggest['destination_warehouse'])){
                      $return['msg'] = '业务线为海外需求单号'.$suggest['demand_number']."目的仓库不能为空";
                      return $return;
                 }
                 if($suggest['source_from']!=1 && empty($suggest['logistics_type'])){
                      $return['msg'] = '业务线为海外需求单号'.$suggest['demand_number']."物流类型不能为空";
                      return $return;
                 }
                 $order_num = $suggest['purchase_type_id']
                     . '_' . $suggest['supplier_code']
                     . '_' . $suggest['warehouse_code']
                     . '_' . $suggest['is_drawback']
                     . '_' . $suggest['source_from']
                     . '_' . $suggest['shipment_type']
                     . '_' . $suggest['temp_container']
                     . '_' . $suggest['is_overseas_boutique'];
             }elseif($suggest['purchase_type_id'] == PURCHASE_TYPE_FBA_BIG){
//                 if($suggest['source_from']!=1 && empty($suggest['destination_warehouse'])){
//                     $return['msg'] = '业务线为FBA大货需求单号'.$suggest['demand_number']."目的仓库不能为空";
//                     return $return;
//                 }
                 if($suggest['source_from']!=1 && empty($suggest['logistics_type'])){
                     $return['msg'] = '业务线为FBA大货需求单号'.$suggest['demand_number']."物流类型不能为空";
                     return $return;
                 }
                 $order_num = $suggest['purchase_type_id']
                     . '_' . $suggest['supplier_code']
                     . '_' . $suggest['warehouse_code']
                     . '_' . $suggest['is_drawback']
                     . '_' . $suggest['source_from']
                     . '_' . $suggest['shipment_type'];
             } else { // FBA/国内仓/PFB/平台头程 可以合并到一个po中
                 $order_num = '1345'   //
                     . '_' . $suggest['supplier_code']
                     . '_' . $suggest['pertain_wms']
                     . '_' . $suggest['is_drawback'];
             }

            $order_num = strtoupper($order_num);

            $num_key   = 'num_counter_'.$user_id."_".$order_num;// 当前单号计数器
            $order_num = $this->get_combine_order_sum($num_key,$user_id,$order_num,$suggest['sku']);// 根据规则生成新的PO
            $this->rediss->deleteData($num_key);// 删除当前单号计数器，以从 1 开始遍历

            $orders[$order_num][] = $suggest;
        }

        /*******************************************Start：清除缓存****************************************************/
        $this->delete_combine_order_cache($user_id);// 非常重要，请勿随意改动（分为两部分：需要包括 foreach ($suggest_list) ）
        /*******************************************End：清除缓存******************************************************/


        foreach($orders as $order_key => $order_list){
            $purchase_type_id = array_unique(array_column($order_list,'purchase_type_id'));

            if(count($purchase_type_id) >= 2 and count($purchase_type_id) <= 4){
                foreach($order_list as $list_key => $list_value){
                    $orders[$order_key][$list_key]['purchase_type_id'] = PURCHASE_TYPE_PFB;// 修改 FBA/国内的业务线为 PFB
                }
            }elseif(count($purchase_type_id) > 4){
                $return['msg'] = "数据组合时业务线发生错误！";
                return $return;
            }

        }

        $return['code'] = true;
        $return['data'] = $orders;
        return $return;
    }

    /**
     * 删除需求锁定标志
     * @param $suggest_list
     * @return bool
     */
    private function _delete_lock_demand_flag($suggest_list){
        foreach($suggest_list as $demand_number){
            $session_key = 'SUGGEST_' . $demand_number;
            $this->rediss->deleteData($session_key);
        }
        return true;
    }

    /**
     * 创建 采购单
     * @author Jolon
     * @param $suggest_list
     * @param $purchase_type_id
     * @return array
     */
    public function create_purchase_order($suggest_list, $userInfo=[])
    {
        set_time_limit(0);
        $this->load->model('purchase_suggest_model');
        $this->load->model('purchase_suggest_map_model');

        $return = $this->get_convert_suggest($suggest_list); // 数据转换
        $lock_success_list = $return['lock_success_list'];
        if ($return['code'] == false) {
            $this->_delete_lock_demand_flag($lock_success_list);
            return $return;
        }
        $gateWaysSupplierCode = [];

        if(!empty($suggest_list)){

            $suggestSupplierCodes = array_column($suggest_list,"supplier_code");
            if(!empty($suggestSupplierCodes)) {
                $gateWaysSupplierData = $this->purchase_db->from("supplier")->where_in("supplier_code", $suggestSupplierCodes)->where("is_gateway", SUGGEST_IS_GATEWAY_YES)->where("is_push_purchase",1)->select("supplier_code")->get()->result_array();
                if(!empty($gateWaysSupplierData)){
                    $gateWaysSupplierCode = array_filter(array_column($gateWaysSupplierData,"supplier_code"));
                }
            }
        }

        $hash_doc = 'PUR_HANDLE_ONE_KEY_CREATE_DEMAND';
        $create_id = array_column($suggest_list, 'id');
        $handle_ids = $this->demand_suggest_order_exclude($create_id, $hash_doc);
        if(count($handle_ids) > 0 || (count($create_id) == count($handle_ids) && count($create_id) != 0)){
            return ['code' => false, 'msg' => '已有其他任务在生成采购单，不需要重复生成！'];
        }

        $orders_list = $return['data'];
        if ($orders_list) {
            $purchase_number_list   = []; // 新创建的采购单号
            $success_demand_list    = [];// 创建采购单成功的备货单号

            $this->purchase_db->trans_strict(true);
            $this->purchase_db->trans_begin();
            try {
                foreach ($orders_list as $key=>$orders) {
                    // 验证供应商的下单次数
                    $supplier_code = isset($orders[0]['supplier_code'])?$orders[0]['supplier_code']:null;
                    if(empty($supplier_code)){
                        throw new Exception('备货单参数[supplier_code]缺失');
                    }else{
                        $reject_info = $this->temporary_supplier_order_number($supplier_code);
                        if($reject_info){
                            throw new Exception('供应商：'.$supplier_code.implode(',',$reject_info));
                        }
                    }

                    $lack_flag = false;//是否欠货判断标识
                    foreach ($orders as $k => $suggest){
                        if ($suggest['left_stock']<0){
                            $lack_flag = true;
                        }
                    }

                    if ($lack_flag){
                        $orders[0]['is_lack'] = 1;//只要有一个备货单欠货,则标识采购单为欠货
                    }else{
                        $orders[0]['is_lack'] = 0;
                    }
                    if( isset($orders[0]['supplier_code']) && !empty($orders[0]['supplier_code']) ){
                        if( !empty($gateWaysSupplierCode) && in_array($orders[0]['supplier_code'],$gateWaysSupplierCode)){
                            $orders[0]['is_gateways'] = SUGGEST_IS_GATEWAY_YES;
                        }else{
                            $orders[0]['is_gateways'] = SUGGEST_IS_GATEWAY_NO;
                        }
                    }else{
                        $orders[0]['is_gateways'] = SUGGEST_IS_GATEWAY_NO;
                    }
                    $order_main_result = $this->savePurData($orders[0], $userInfo); // 保存采购单主表
                    // 验证采购单主表记录是否创建成功
                    $new_purchase_number = isset($order_main_result['data']) ? $order_main_result['data'] : '';
                    if (empty($new_purchase_number)) {
                        throw new Exception(isset($order_main_result['msg']) ? $order_main_result['msg'] : '生成采购单失败');
                    }

                    // 更新需求的采购状态、需求状态、采购单状态
                    $suggest_ids = array_column($orders, 'id');
                    //print_r($suggest_ids);die;
                    foreach($suggest_ids as $suggest_id){
                        $demand_res1 = $this->purchase_suggest_model->change_suggest_status($suggest_id);
                        //print_r($demand_res1);die;
                        if (empty($demand_res1)) {
                            throw new Exception('采购需求状态变更失败');
                        }
                    }

                    // 更新需求单状态
                    $upd_demand_sql = 'UPDATE pur_purchase_demand AS d 
                        INNER JOIN pur_purchase_suggest AS sg ON d.suggest_demand=sg.demand_number
                        SET d.demand_status = '.DEMAND_STATUS_FINISHED.' 
                        WHERE sg.id in ('.implode(',', $suggest_ids).')';
                    if(!$this->purchase_db->query($upd_demand_sql)){
                        throw new Exception('更新需求单失败！');
                    };

                    // 采购与需求 映射关系
                    $demands = array_column($orders, 'demand_number');
                    $demand_res3 = $this->purchase_suggest_map_model->save_map_order_suggest($new_purchase_number, $demands, $userInfo);
                    if ($demand_res3 === false) {
                        throw new Exception('采购与需求 映射关系记录创建失败');
                    }

                    // 保存采购明细
                    $order_items_result = $this->savePurDataItems($new_purchase_number, $orders, $userInfo);
                    if (!$order_items_result['code']) {
                        throw new Exception('采购单明细记录 创建失败');
                    }

                    $this->change_status($new_purchase_number,PURCHASE_ORDER_STATUS_WAITING_QUOTE);

                    $purchase_number_list[] = $new_purchase_number;
                    foreach($demands as $demand_number){
                        $success_demand_list[$demand_number] = $new_purchase_number;
                    }
                }

                $commit_status = $this->purchase_db->trans_status();

                // 已生成
                if($commit_status === true){
                    $this_unix = time();
                    foreach ($create_id as $val){
                        $this->rediss->addHashData($hash_doc, $val, $this_unix);
                    }
                }else {
                    throw new Exception('创建采购单事务提交出错');
                }
                $this->purchase_db->trans_commit();

                $return = ['code' => true, 'msg' => '', 'data' => $purchase_number_list,'success_demand_list' => $success_demand_list];
            } catch (\Exception $e) {
                $this->_delete_lock_demand_flag($lock_success_list);
                $this->purchase_db->trans_rollback();
                $return = ['code' => false, 'msg' => $e->getMessage()];
            }
        } else {
            $return = ['code' => false, 'msg' => '未找到目标采购需求'];
        }

        return $return;
    }

    /**
     * 生成备货单排他处理
     */
    public function demand_suggest_order_exclude($ids = [], $hash_doc)
    {
        $handle_ids = [];
        foreach ($ids as $v){
            $has_is = $this->rediss->getHashData($hash_doc, $v);
            try{
                $has_is = json_decode($has_is, true);
                $has_is = (int)$has_is[0];
            }catch (Exception $e){}
            if(!$has_is || empty($has_is) || !is_numeric($has_is))$has_is = 0;
            if($has_is != 0){
                $handle_ids[] = $v;
            }
        }
        return $handle_ids;
    }

    /**
     * 保存采购单主表 数据
     * @author Jolon
     * @param $order_main_data
     * @return array
     */
    public function savePurData($order_main_data, $userInfo=[])
    {
        $return = ['code' => true, 'msg' => '', 'data' => ''];
        $this->load->model('supplier/supplier_buyer_model');
        $this->load->helper('status_order');

        if (!isset($order_main_data['purchase_type_id']) || empty($order_main_data['purchase_type_id'])) {
            $return = ['code' => false, 'msg' => '必须参数不能缺失'];
            return $return;
        }

        //采购订单主表
        $new_order = [];

        $new_purchase_number = $this->getPurNumber($order_main_data['purchase_type_id']); // 生成新的采购单编号
        $supplier_info = $this->supplier_model->get_supplier_info($order_main_data['supplier_code']); // 供应商信息
        $supplier_buyer_info = $this->supplier_buyer_model->get_buyer_one($order_main_data['supplier_code'],$order_main_data['purchase_type_id']);
        $supplier_buyer_id = isset($supplier_buyer_info['buyer_id'])?$supplier_buyer_info['buyer_id']:0;
        $supplier_buyer_name = isset($supplier_buyer_info['buyer_name'])?$supplier_buyer_info['buyer_name']:'';
        if(empty($supplier_info)){
            $return = ['code' => false, 'msg' => '供应商['.$order_main_data['supplier_code'].']不存在'];
            return $return;
        }

        if(empty($supplier_buyer_id) || empty($supplier_buyer_name)){
            $return = ['code' => false, 'msg' => '供应商['.$order_main_data['supplier_code'].']采购员['.getPurchaseType($order_main_data['purchase_type_id']).']错误'];
            return $return;
        }
        if(empty($supplier_info['supplier_payment_info'])){
            $return = ['code' => false, 'msg' => '供应商['.$order_main_data['supplier_code'].']财务结算信息缺失，请先维护供应商资料'];
            return $return;
        }

        //根据供应商是否含税,业务线 获取对应的结算方式和支付方式
        $is_tax = $order_main_data['is_drawback']??'';
        $purchase_type_id = $order_main_data['purchase_type_id']??'';
        if( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]) ){//FBA/国内 一样
            $purchase_type_id = PURCHASE_TYPE_INLAND;
        }elseif(in_array($purchase_type_id,[PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){ // 海外仓和FBA大货统一使用海外仓信息
            $purchase_type_id = PURCHASE_TYPE_OVERSEA;
        }
        if($is_tax == 1){//否退税 对应所有的业务线
            $purchase_type_id = 0;
        }

        $supplier_payment_info = $supplier_info['supplier_payment_info'][$is_tax][$purchase_type_id]??[];

        if (empty($supplier_payment_info)){
            $return = ['code' => false, 'msg' => '供应商['.$order_main_data['supplier_code'].']未找到对应的财务结算信息，请先维护供应商资料'];
            return $return;
        }

        if ($order_main_data['is_lack']){
            $new_order['lack_quantity_status'] = 1;//采购单欠货
        }
        $new_order['pertain_wms'] = isset($order_main_data['pertain_wms']) ? $order_main_data['pertain_wms'] : '';
        // 28329 海外仓订单限制修改仓库
        if($purchase_type_id == PURCHASE_TYPE_OVERSEA){
            $verify_overseas = $this->purchase_order_extend_model->verify_overseas_warehouse($order_main_data);
            if($verify_overseas && $verify_overseas['code'] >= 1){
                $new_order['pertain_wms'] = $verify_overseas['msg'];
//                $order_main_data['warehouse_code'] = $verify_overseas['msg'];
            };
        }

        $new_order['purchase_number'] = $new_purchase_number;
//        $new_order['is_gateway'] = isset($order_main_data['is_gateways'])?$order_main_data['is_gateways']:0;
        $new_order['purchase_type_id'] = $order_main_data['purchase_type_id']; // 业务线(1国内2海外3FBA)
        $new_order['purchase_order_status'] = PURCHASE_ORDER_STATUS_WAITING_QUOTE; // 采购单状态 1.等待采购询价
        $new_order['warehouse_code'] = isset($order_main_data['warehouse_code']) ? $order_main_data['warehouse_code'] : '';
        $new_order['shipment_type'] = $order_main_data['shipment_type'];

        // 供应商相关信息
        $new_order['supplier_code'] = $order_main_data['supplier_code'];
        $new_order['supplier_name'] = !empty($supplier_info['supplier_name']) ? $supplier_info['supplier_name'] : $order_main_data['supplier_name']; // 供应商名字
        $new_order['account_type'] = $supplier_payment_info['supplier_settlement']; // 供应商结算方式
        $new_order['pay_type'] = $supplier_payment_info['payment_method']; // 供应商支付方式
        $new_order['shipping_method_id'] = !empty($supplier_info['shipping_method_id']) ? $supplier_info['shipping_method_id'] : 2; // 供应商运输1:自提,2:快递,3:物流,4:送货
        $new_order['is_cross_border'] = !empty($supplier_info['is_cross_border']) ? $supplier_info['is_cross_border'] : 0; // 是否跨境宝 0.否
        // 采购员 跟单员 创建人
//        $new_order['buyer_id'] = $supplier_buyer_id;// 采购员ID
//        $new_order['buyer_name'] = $supplier_buyer_name; // 采购人名称
        $uid = getActiveUserId();
        $uname = getActiveUserName();
        if(!empty($userInfo) && isset($userInfo['uid']))$uid = $userInfo['uid'];
        if(!empty($userInfo) && isset($userInfo['username']))$uname = $userInfo['username'];
        $new_order['buyer_id'] = isset($order_main_data['buyer_id']) ?  $order_main_data['buyer_id'] : '';
        $new_order['buyer_name'] = isset($order_main_data['buyer_name']) ?  $order_main_data['buyer_name'] : '';
        $new_order['currency_code'] = isset($order_main_data['currency_code']) ? $order_main_data['currency_code'] : 'RMB'; // 币种 RMB|USD
        $new_order['merchandiser_id'] = isset($order_main_data['merchandiser_id']) ? $order_main_data['merchandiser_id'] : $uid; // 跟单员ID
        $new_order['merchandiser_name'] = isset($order_main_data['merchandiser_name']) ? $order_main_data['merchandiser_name'] : $uname; // 跟单员
        $new_order['create_user_name'] = $uname;
        $new_order['create_time'] = date('Y-m-d H:i:s');
        $new_order['create_type_id'] = 2; // 创建类型(1系统生成2手工创建)
        $new_order['is_expedited'] = $order_main_data['is_expedited']; // 是否加急
        $new_order['is_drawback'] = PURCHASE_IS_DRAWBACK_N; // 是否退税  0.否
        $new_order['is_destroy'] = 0; // 是否核销  0.否
        $new_order['sku_state_type'] = $order_main_data['sku_state_type'];
        // 如果支付方式为线上境内或者境外支付，采购来源为合同单
        if(!empty($new_order['pay_type']) && in_array($new_order['pay_type'],[PURCHASE_PAY_TYPE_PUBLIC,PURCHASE_PAY_TYPE_PRIVATE])){

            $new_order['source'] = SOURCE_COMPACT_ORDER;
            if( isset($order_main_data['is_gateways'])  && $order_main_data['is_gateways'] == SUGGEST_IS_GATEWAY_YES){

                $new_order['is_gateway'] = SUGGEST_IS_GATEWAY_YES;
            }else{
                $new_order['is_gateway'] = SUGGEST_IS_GATEWAY_NO;
            }

        }else{
            $new_order['source'] = SOURCE_NETWORK_ORDER;
            $new_order['is_gateway'] = SUGGEST_IS_GATEWAY_NO;
        }

        //限制 临时供应商最多只能下8个p
        $order_number = $this->temporary_supplier_order_number($new_order['supplier_code'], $new_order['source']);
        if($order_number){
            $return['code'] = false;
            $return['msg']  = '临时供应商最多只能下20个po，若要再下单请转常规供应商，或者绑定新供应商';
            return $return;
        }

        // 采购单是否退税

        if (empty($new_order['warehouse_code'])) {
            $return['code'] = false;
            $return['msg'] = '需求ID '.$order_main_data['id'].'-仓库缺失';
            return $return;
        }

        if (isset($order_main_data['is_drawback'])) {
            $new_order['is_drawback'] = $order_main_data['is_drawback'] == 1 ? PURCHASE_IS_DRAWBACK_Y : PURCHASE_IS_DRAWBACK_N; // 是否退税 1.退税，0.否
        }
        if (isset($new_order['warehouse_code'])) {
            if ($new_order['warehouse_code'] == 'TS') {// TS.退税仓
                $new_order['is_include_tax'] = PURCHASE_IS_INCLUDE_TAX_Y;// 是否含税（系统自动生成（针对 FBA订单，东莞仓FBA虚拟仓为不含税，退税仓为含税））
            }else{
                $new_order['is_include_tax'] = PURCHASE_IS_INCLUDE_TAX_N;
            }
        }
        //采购主体界定
        $purchase_name = $this->get_subject_title($new_order['is_drawback'],$new_order['supplier_code']);
        $new_order['purchase_name'] = $purchase_name;


        // 保存数据
        $result = $this->purchase_db->insert($this->table_name, $new_order);

        if ($result) {
            $return['data'] = $new_purchase_number;
        } else {
            $return['code'] = false;
            $return['msg'] = '数据保存出错';
        }

        $log_data = [
            'id' => $new_purchase_number,
            'type' => $this->table_name,
            'content' => '生成采购单',
            'detail' => $new_purchase_number . '-' . ($return['code']) ? '成功' : '失败',
            'is_show' => 2,
        ];
        operatorLogInsert($log_data);

        return $return;
    }

    /**
     * 获取商品的票面税率
     * @param: $sku    string   商品SKU
     * @return : float
     **/
    public function get_product_coupon_rate($sku)
    {
        return $this->purchase_db->from("product")->where("sku",$sku)->select("coupon_rate")->get()->row_array();
    }

    /**
     * 保存采购单明细信息
     * @author Jolon
     * @param $purchase_number
     * @param $order_items
     * @return array
     */
    public function savePurDataItems($purchase_number, $order_items, $userInfo =[])
    {
        $return = ['code' => true, 'msg' => '', 'data' => ''];
        if (empty($purchase_number) or empty($order_items) or !is_array($order_items)) {
            $return = ['code' => false, 'msg' => '必须参数不能缺失'];
            return $return;
        }

        $username = getActiveUserName();
        if(!empty($userInfo) && isset($userInfo['username']))$username = $userInfo['username'];
        $time = date('Y-m-d H:i:s');
        $this->load->model('product/Product_model');

        foreach ($order_items as $k => $value_item) {
            $sku_info = $this->Product_model->get_product_info($value_item['sku']);
            if (empty($sku_info)) {
                $return = ['code' => false, 'msg' => $value_item['sku'].' SKU不存在'];
                return $return;
            }

            // 查询是否已经有 相同的 SKU
            $have_item = $this->purchase_order_items_model->get_item($purchase_number, $value_item['sku'], true);
            if ($have_item) { // 已存在  则数量求和
                $item_update_data = [
                    'purchase_amount' => $have_item['purchase_amount'] + $value_item['purchase_amount'],
                    'confirm_amount' => $have_item['confirm_amount'] + $value_item['purchase_amount'],
                    'modify_user_name' => $username,
                    'modify_time' => $time,
                    'plan_arrive_time' => $value_item['plan_arrive_time'],//预计到货时间
                ];
                if(empty($have_item['product_img_url'])) $item_update_data['product_img_url'] = erp_sku_img_sku($sku_info['product_img_url']);

                $this->purchase_db->where('id', $have_item['id']);
                $result = $this->purchase_db->update('purchase_order_items', $item_update_data);
                $this->insertAuditEstimeData($purchase_number,$value_item['sku'],'',$value_item['plan_arrive_time'],'生成采购单',$username);

                $log_data = [
                    'id' => $purchase_number,
                    'type' => 'purchase_order_items',
                    'content' => '更新采购单明细',
                    'detail' => $value_item['sku'] . '更新成功' . json_encode($item_update_data),
                    'is_show' => 2
                ];
                operatorLogInsert($log_data);
            } else {
                //  业务线为海外仓

                $delivery_time = null;
                if($value_item['purchase_type_id'] == 2){

                    // SKU 是否维护交期，没有维护的情况下,交期为空
                    if( $sku_info['devliy'] == 0.00 && $sku_info['original_devliy'] == 0.00){

                        $delivery_time = NULL;
                    }else{
                        //维护交期的情况下，等于 创建时间+交期
                        $devliyDatas = ($sku_info['original_devliy'] == 0.00)?$sku_info['devliy']:$sku_info['original_devliy'];
                        $delivery_time = time() + 86400 * $devliyDatas;
                    }
                }else{
                    // 非海外仓，判断SKU是否维护了超长交期数据
                    if( $sku_info['long_delivery'] == 1){
                        // SKU 未维护超长交期
                        //权均交期
                        $delivery_item = $this->Delivery_model->get_delivery_info($value_item['warehouse_code'],$value_item['sku']);
//                        if(!empty($delivery_item['avg_delivery_time'])) {
                            $delivery_time = time() + 10 * 86400;
//                        }
                    }

                    if( $sku_info['long_delivery'] == 2){

                        if( $sku_info['devliy'] == 0.00 && $sku_info['original_devliy'] == 0.00){

                            $delivery_time = time() + 10*86400;

                        }else{
                            //维护交期的情况下，等于 创建时间+交期
                            $devliyDatas = ($sku_info['original_devliy'] == 0.00)?$sku_info['devliy']:$sku_info['original_devliy'];
                            $delivery_time = time() + 86400 * $devliyDatas;
                        }
                    }
                }

                $purchase_unit_price = format_two_point_price($value_item['purchase_unit_price']); // 含税价
                // 新增采购单明细
                // 2020/9/25 叶凡立
                if( $delivery_time == NULL){
                    $d_time = '0000-00-00 00:00:00';
                }else {
                    $d_time = date("Y-m-d H:i:s", $delivery_time);
                }

                /**
                 * 需求：28241 采购单页面,增加显示字段和筛选项:"是否超长交期""轨迹状态"
                 * 2.增加显示字段:"交期",取值=采购单审核通过时,跟据SKU抓取产品管理列表的"交期",保存数值,后续不在跟随产品列表的数据更新,若数据为0或空,则显示"-"
                3.增加显示字段:"是否超长交期",取值=采购单审核通过时,跟据SKU抓取产品管理列表的"是否超长交期",保存数值,后续不在跟随产品列表
                 **/

                $deliverData = $sku_info['devliy'];

                if( $sku_info['devliy'] ==0){

                    $deliverData = $sku_info['original_devliy'];
                }
                $item_insert_data = [
                    'purchase_number' => $purchase_number,
                    'sku' => $value_item['sku'],
                    'product_name' => isset($value_item['product_name']) ? $value_item['product_name'] : '',
                    'purchase_amount' => isset($value_item['purchase_amount']) ? $value_item['purchase_amount'] : 0,
                    'purchase_unit_price' => $purchase_unit_price, // 含税单价
                    'confirm_amount' => isset($value_item['purchase_amount']) ? $value_item['purchase_amount'] : 0,
                    'product_img_url' => isset($value_item['product_img_url']) ? erp_sku_img_sku($value_item['product_img_url']) : erp_sku_img_sku($sku_info['product_img_url']),
                    'is_new' => isset($value_item['is_new']) ? $value_item['is_new'] : 0,
                    'pur_ticketed_point' => isset($value_item['ticketed_point']) ? $value_item['ticketed_point'] : 0,
                    'product_base_price' => format_two_point_price($sku_info['purchase_price']), // 不含税单价
                    'create_user_name' => $username,
                    'create_time' => $time,
                    'coupon_rate' => $sku_info['coupon_rate'],
                    'first_plan_arrive_time' => $d_time,//新增首次预计到货时间
                    'plan_arrive_time' => $d_time,//新增预计到货时间
                    'new_devliy' => !empty($deliverData)?$deliverData:0,
                    'is_long_delivery' => $sku_info['long_delivery']
                ];

                // 如果首次下单，则标记为新品
                $has_sku = $this->purchase_db->from('purchase_order_items')->select("sku")->where("sku=", $value_item['sku'])->get()->result_array();
                $item_insert_data['is_new'] = $has_sku && count($has_sku) > 0? 0: 1;

                if(isset($value_item['demand_number']))$item_insert_data['demand_number'] = $value_item['demand_number'];
                $result = $this->purchase_db->insert('purchase_order_items', $item_insert_data);
                $plan_arrive_time = isset($value_item['plan_arrive_time'])?$value_item['plan_arrive_time']:$d_time;
                $this->insertAuditEstimeData($purchase_number,$value_item['sku'],'', $plan_arrive_time,'生成采购单', $username);
            }
        }

        return $return;
    }

    /**
     * 记录导入的历史数据
     * @author: luxu
     **/
    public function insert_data( $params ) {

        $this->purchase_db->insert("purchase_progress_history",$params);
    }

    /**
     * 生成最新的采购单号
     * @author Jolon
     * @param int $type 采购单类型
     * @return mixed
     */
    public function getPurNumber($type)
    {
        $this->load->model('prefix_number_model'); // 数据表前缀

        switch ($type) {
            case PURCHASE_TYPE_INLAND:// 国内
                $type = 'PO';
                break;
            case PURCHASE_TYPE_OVERSEA:// 海外
                $type = 'ABD';
                break;
            case PURCHASE_TYPE_FBA_BIG:// FBA大货
                $type = 'FBA';
                break;
            case PURCHASE_TYPE_FBA:// FBA
                $type = 'FBA';
                break;
            case PURCHASE_TYPE_PFB:// PFB
                $type = 'PFB';
                break;
            case PURCHASE_TYPE_PFH:// PFH
                $type = 'PO';
                break;
            default:// 默认国内
                $type = 'PO';
                break;
        }

        /*
        list($msec, $sec) = explode(' ', microtime());
//        $msec   = ceil($msec * 10000);
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        $mmsec = substr($msectime,strlen($msectime)-3,3);
        $uid = getActiveUID();
        $ustr  = substr($uid,strlen($uid)-2,2);
        $num = '8'.$ustr.$mmsec.rand(100, 999);
        $number = $type.$num;
        return $number;
        */


        $new_purchase_number = $this->prefix_number_model->get_prefix_new_number($type);
        return $new_purchase_number;
    }

    /**
     * 验证采购单 是否可以生成同一个合同单
     * @author Jolon
     * @param array $purchase_numbers 采购单号
     * @return array
     * exp: array(
     *        code => true|false 验证结果,
     *        msg  => 错误提示信息,
     *  )
     */
    public function check_purchase_order_is_same_compact($purchase_numbers)
    {
        $return = ['code' => false, 'msg' => '', 'data' => ''];

        if (!is_array($purchase_numbers)) {
            $return['msg'] = '采购单号参数错误';
            return $return;
        }

        $purchase_numbers = array_unique($purchase_numbers);
        // 采购单信息
        $purchase_orders = $this->purchase_db->where_in('purchase_number', $purchase_numbers)->get($this->table_name)->result_array();
        // 请款信息
        $purchase_orders_pay_type = $this->purchase_db->where_in('purchase_number', $purchase_numbers)->get("purchase_order_pay_type")->result_array();
        // 采购合同信息
        $purchase_compact_items = $this->purchase_db->where_in('purchase_number', $purchase_numbers)->get("purchase_compact_items")->result_array();
        $purchase_compact_items = array_column($purchase_compact_items,'purchase_number','purchase_number');

        // 验证采购单 状态
        $purchase_order_status_arr = array_unique(array_column($purchase_orders, 'purchase_order_status'));

        if($purchase_orders){
            foreach($purchase_orders as $order_value){
                $purchase_number = $order_value['purchase_number'];

                if(in_array($order_value['purchase_order_status'],[
                    PURCHASE_ORDER_STATUS_WAITING_QUOTE,
                    PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                    PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,
                    PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT,
                    PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,
                    PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND,
//                    PURCHASE_ORDER_STATUS_CANCELED,
                    PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT
                ])){
                    $return['msg'] = "订单状态异常：{$order_value['purchase_number']} 该状态不能生成进货单";
                    return $return;
                }
                if($order_value['is_generate'] == 1){// is_generate:是否生成进货单 1否 2是
                    if(isset($purchase_compact_items[$purchase_number])){// 双重验证
                        $return['msg'] = "订单状态异常：采购单【{$purchase_number}】已经生成了合同";
                        return $return;
                    }
                }elseif($order_value['is_generate'] == 2){
                    $return['msg'] = "订单状态异常：采购单【{$purchase_number}】已经生成了合同";
                    return $return;
                }elseif($order_value['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER){
                    $return['msg'] = "订单状态异常：请选择【等待生成进货单】状态下的订单";
                    return $return;
                }
            }
        }

        // 验证采购单 采购来源类型
        $source_arr = array_unique(array_column($purchase_orders, 'source'));
        if (count($source_arr) != 1 or $source_arr[0] != SOURCE_COMPACT_ORDER) {
            $return['msg'] = "订单采购来源异常：请选择采购来源为【合同】的订单";
            return $return;
        }
        // 验证采购单 请款记录信息
        if (empty($purchase_orders_pay_type) or count($purchase_orders_pay_type) != count($purchase_numbers)) {
            $return['msg'] = "订单请款信息异常：请确认所选订单是否已经【确认订单信息】";
            return $return;
        }


        // 数据验证：是否能够生成同一合同
        $check_supplier_code = array_unique(array_column($purchase_orders, 'supplier_code')); // 供应商
        $check_is_drawback = array_unique(array_column($purchase_orders, 'is_drawback')); // 是否退税
        $check_purchase_name = array_unique(array_column($purchase_orders, 'purchase_name')); // 采购主体
        $check_pay_type = array_unique(array_column($purchase_orders, 'pay_type')); // 支付方式
        $check_account_type = array_unique(array_column($purchase_orders, 'account_type')); // 结算方式
        $check_settlement_ratio = array_unique(array_column($purchase_orders_pay_type, 'settlement_ratio')); // 结算比例
        $check_is_freight = array_unique(array_column($purchase_orders_pay_type, 'is_freight')); // 结算比例
        $check_freight_formula_mode = array_unique(array_column($purchase_orders_pay_type, 'freight_formula_mode')); // 结算比例
        $purchase_type_arr = array_unique(array_column($purchase_orders, 'purchase_type_id')); // 业务线
        $warehouse_code_arr = array_unique(array_column($purchase_orders, 'warehouse_code')); // 采购仓库

        $error_message = [];
        if (count($check_supplier_code) != 1)
            $error_message[] = '【供应商】不一致';
        if (count($check_is_drawback) != 1)
            $error_message[] = '【是否退税】不一致';
        if (count($check_purchase_name) != 1)
            $error_message[] = '【采购主体】不一致';
        if (count($check_pay_type) != 1)
            $error_message[] = '【支付方式】';
        if (count($check_account_type) != 1)
            $error_message[] = '【结算方式】不一致';
        if (count($check_settlement_ratio) != 1)
            $error_message[] = '【结算比例】不一致';
        if (count($check_is_freight) != 1)
            $error_message[] = '【运费支付】不一致';

        // FBA/国内仓可以生成一个合同，海外仓单独生成合同
        if(count($purchase_type_arr) > 1 AND !empty(array_diff($purchase_type_arr,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]))){
            $error_message[] = '【业务线不符合要求(仅平台头程/PFB/FBA/国内仓可一起生成)】';
        }

        if (!empty($error_message)) {// 存在错误
            $return['msg'] = implode(' ', $error_message) . "，不能生成同个合同";
            return $return;
        }

        // 验证成功
        $return['code'] = true;
        return $return;
    }


    /**
     * 验证采购单是否属于屏蔽申请中（通过SKU明细验证）
     * @param $purchase_number
     * @return string|bool  false.没有屏蔽中的SKU，否则返回 屏蔽中的SKU
     */
    public function check_order_is_scree_with_sku($purchase_number){
        $order_items = $this->purchase_order_items_model->get_item($purchase_number);

        $this->load->model('abnormal/Product_scree_model');

        $order_items_skus = array_column($order_items,'sku');
        $scree_status = [// 屏蔽中的状态
            PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT,
            PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM,
            PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM
        ];

        $old_waiting_records = $this->Product_scree_model->get_scree_skus_data($order_items_skus,$scree_status);

        if($old_waiting_records) return implode(',',array_column($old_waiting_records,'sku'));
        else return false;
    }

    /**
     * 获取采购单里面最晚 预计到货时间
     * @author Jolon
     * @param $purchase_numbers
     * @return string
     */
    public function get_last_plan_product_arrive_time($purchase_numbers){
        $last_arrive_time = $this->purchase_db->select('max(plan_arrive_time) as last_arrive_time')
            ->where_in('purchase_number', $purchase_numbers)
            ->get($this->item_table_name)
            ->row_array();

        return isset($last_arrive_time['last_arrive_time'])?$last_arrive_time['last_arrive_time']:'';
    }

    /**
     * 获取 合同确认 需要的数据
     * @author Jolon
     * @param array $purchase_numbers 采购单号
     * @return mixed
     */
    public function get_create_compact_data($purchase_numbers)
    {    $this->load->model('purchase/Purchase_order_determine_model');
        if (!is_array($purchase_numbers))
            $purchase_numbers = [$purchase_numbers];

        sort($purchase_numbers);// 排序获取第一个订单

        // 采购单信息
        $query_builder = $this->purchase_db;
        $query_builder->select('po.purchase_type_id,po.buyer_id,po.purchase_name,po.buyer_name,po.supplier_code,po.supplier_name,po.plan_product_arrive_time,po.account_type,po.pay_type,'
            . 'po.is_drawback,po.pay_type,po.account_type,po.pertain_wms AS warehouse_code,ware.warehouse_name as warehouse_name,'
            . 'popy.settlement_ratio,popy.is_freight,popy.freight_formula_mode');
        $query_builder->where('po.purchase_number', $purchase_numbers[0]);
        $query_builder->from('purchase_order as po');
        $query_builder->join('purchase_order_pay_type as popy', 'popy.purchase_number=po.purchase_number', 'left');
        $query_builder->join('warehouse as ware', 'ware.warehouse_code=po.warehouse_code', 'left');

        $compact_main = $query_builder->get()->row_array(); // 合同主信息
        $last_arrive_time = $this->get_last_plan_product_arrive_time($purchase_numbers);

        // 采购单的所有仓库、采购员
        $order_info_list = $this->purchase_db->select('po.purchase_number,po.purchase_type_id,po.buyer_id,po.buyer_name,po.warehouse_code,ware.warehouse_name as warehouse_name,popy.freight_formula_mode')
            ->from('purchase_order as po')
            ->join('purchase_order_pay_type as popy', 'popy.purchase_number=po.purchase_number', 'left')
            ->join('warehouse as ware', 'ware.warehouse_code=po.warehouse_code', 'left')
            ->where_in('po.purchase_number', $purchase_numbers)
            ->get()->result_array();
        $compact_main['order_info_list'] = $order_info_list;
        $purchase_type_ids               = array_unique(array_column($order_info_list,'purchase_type_id'));
        $buyer_ids                       = array_unique(array_column($order_info_list,'buyer_id'));
        $order_info_list                 = arrayKeyToColumn($order_info_list,'purchase_number');

        $find_purchase_type_id          = array_intersect([PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH],$purchase_type_ids)?PURCHASE_TYPE_INLAND:$purchase_type_ids[0];// PFB/PFH的根据国内查找
        $purchase_type_id               = count($purchase_type_ids) > 1?PURCHASE_TYPE_INLAND:$purchase_type_ids[0];// PO/FBA/PFB的业务线设置为 PFB
        $compact_main['purchase_type_id'] = $purchase_type_id;

//        if(count($purchase_type_ids) == 1){
            if(count($buyer_ids) > 1){// 有多个采购员的，那么甲方联系人=在供应商管理模块该供应商对应的采购员
                $this->load->model('supplier_buyer_model', '', false, 'supplier');
                $supplier_buyer = $this->supplier_buyer_model->get_buyer_one($compact_main['supplier_code'],$find_purchase_type_id);
                if($supplier_buyer){
                    $compact_main['buyer_id']   = $supplier_buyer['buyer_id'];
                    $compact_main['buyer_name'] = $supplier_buyer['buyer_name'];
                }
            }
//        }

        // 采购单明细
        $purchase_order_details = $query_builder
            ->select('poi.*,pro.export_cname,pro.declare_unit,popy.freight as t_freight, popy.discount as t_discount,popy.process_cost as t_process_cost')
            ->from('purchase_order_items as poi')
            ->join('pur_product pro','poi.sku = pro.sku','left')
            ->join('purchase_order_pay_type as popy', 'popy.purchase_number=poi.purchase_number', 'left')
            ->where_in('poi.purchase_number', $purchase_numbers)
            ->order_by('poi.purchase_number', 'asc')
            ->get()->result_array();
        $compact_details = [];
        $purchase_order_price = 0;
        $purchase_order_freight = $purchase_order_discount = $purchase_order_process_cost = []; // 合同总金额、总运费、总优惠
        $total_cancellation  =0;
        //获取历史取消数量集合
        $sku= array_column(isset($purchase_order_details)?$purchase_order_details:[], 'sku');
        $order_cancel_list=$this->Purchase_order_determine_model->get_order_cancel_list($purchase_numbers[0],$sku);
        foreach ($purchase_order_details as $detail) {
            $d_pur = $detail['purchase_number'];
            $detail_tmp = [];
            $detail_tmp['item_id'] = $detail['id'];
            $detail_tmp['purchase_number'] = $d_pur;
            $detail_tmp['sku'] = $detail['sku'];
            $detail_tmp['warehouse_code'] = isset($order_info_list[$d_pur])?$order_info_list[$d_pur]['warehouse_code']:'';
            $detail_tmp['warehouse_name'] = isset($order_info_list[$d_pur])?$order_info_list[$d_pur]['warehouse_name']:'';
            $detail_tmp['product_img_url'] = erp_sku_img_sku($detail['product_img_url']);
            $detail_tmp['product_name'] = $detail['product_name'];
            $detail_tmp['pur_ticketed_point'] = $detail['pur_ticketed_point'];
            $detail_tmp['confirm_amount'] = $detail['confirm_amount'];
            $detail_tmp['cancel_ctq'] = isset($order_cancel_list[$d_pur.'-'.$detail['sku']])?$order_cancel_list[$d_pur.'-'.$detail['sku']]:0; //已取消数量
            $detail_tmp['product_base_price'] = $detail['product_base_price']; // 不含税价（单价）
            $detail_tmp['purchase_unit_price'] = $detail['purchase_unit_price']; // 含税单价
            $detail_tmp['freight'] = $detail['t_freight'];
            $detail_tmp['discount'] = $detail['t_discount'];
            $detail_tmp['process_cost'] = $detail['t_process_cost'];
            $detail_tmp['item_total_price'] = format_price($detail['confirm_amount'] * $detail['purchase_unit_price']); // 商品总金额

            $cancel_date  = $this->purchase_order_cancel_model->get_cancel_total_by_sku($d_pur,$detail['sku']);
            $detail_tmp['cancel_total_price'] = isset($cancel_date['cancel_total_price'])?$cancel_date['cancel_total_price']:'0'; //取消金额
            $detail_tmp['cancel_ctq']  = isset($cancel_date['cancel_ctq'])?$cancel_date['cancel_ctq']:'0';//取消数量
            $detail_tmp['export_cname'] = $detail['export_cname'];//开票品名
            $detail_tmp['declare_unit'] = $detail['declare_unit'];//开票单位
            $detail['coupon_rate'] = $detail['coupon_rate']*100;
            $detail_tmp['coupon_rate'] = $detail['coupon_rate'].'%';//票面税率 百分号的形式显示

            $compact_details[$d_pur][] = $detail_tmp;
            $total_cancellation+= format_price($detail_tmp['cancel_ctq']*$detail['purchase_unit_price']);//已取消商品金额
            $purchase_order_price    += $detail_tmp['item_total_price'];// 总金额
            $purchase_order_freight[] = $detail['t_freight'];// 总运费
            $purchase_order_discount[] = $detail['t_discount'];// 总优惠
            $purchase_order_process_cost[] = $detail['t_process_cost'];// 总优惠
        }

        $purchase_order_freight = count($purchase_order_freight) > 0? array_sum(array_values($purchase_order_freight)): 0;
        $purchase_order_discount = count($purchase_order_discount) > 0? array_sum(array_values($purchase_order_discount)): 0;
        $purchase_order_process_cost = count($purchase_order_process_cost) > 0? array_sum(array_values($purchase_order_process_cost)): 0;

        $compact_main['currency_code']        = 'RMB';
        $compact_main['currency_code_symbol'] = '￥';
        $compact_main['total_price']          = format_price($purchase_order_price);
        $compact_main['total_freight']        = format_price($purchase_order_freight);
        $compact_main['total_discount']       = format_price($purchase_order_discount);
        $compact_main['total_process_cost']   = format_price($purchase_order_process_cost);
        $compact_main['total_real_price']     = format_price($purchase_order_price + $purchase_order_freight - $purchase_order_discount + $purchase_order_process_cost);
        $compact_main['plan_product_arrive_time'] = $last_arrive_time;
        $compact_main['delivery_date']            = convert_delivery_date($last_arrive_time);// 转换成文字格式
        $compact_main['total_cancellation']        = $total_cancellation;
        $compact['compact_main'] = $compact_main;
        $compact['compact_details'] = $compact_details;

        return $compact;
    }

    /**
     * 拼接采购合同 详细仓库地址信息
     * @param array $a_linkman_info  合同甲方联系人信息
     * @param array $order_info_list 合同采购单信息列表
     * @return mixed    仓库地址
     **/
    private function get_warehouse_address( $a_linkman_info,$order_info_list ) {
        $this->load->model('warehouse/Warehouse_model');

        $compact_address_list = [];
        if($order_info_list){
            foreach($order_info_list as $order_value){
                $warehouse_name = $order_value['warehouse_name'];
                $result = $this->Warehouse_model->get_warehouse_address_one($order_value['warehouse_code']);// 获取仓库信息

                $address        =  $result['province_text'].$result['city_text'].$result['area_text'].$result['town_text'].$result['address'];
                $a_linkman_name = isset($a_linkman_info['a_linkman'])?$a_linkman_info['a_linkman']:'';
                $a_phone        = isset($a_linkman_info['a_phone'])?$a_linkman_info['a_phone']:'';

                // 优先取仓库的联系人和联系电话
                if( !empty($result['contacts']) and !empty($result['contact_number']) ) {
                    $address .= " 收件人: ".$result['contacts']."、".$result['contact_number'];
                }else{
                    $address .= " 收件人: ".$a_linkman_name."、".$a_phone;
                }

                // 根据地址分组保存
                $compact_address_list[md5($address)][$warehouse_name] = $address;
            }
        }

        // 组装（合并）仓库地址
        $receive_address = '';
        //$compact_address_list_tmp = [];
        foreach($compact_address_list as $address_value){
            $warehouse_name = implode('、',array_keys($address_value));

            $receive_address .= "<b>{$warehouse_name}</b>:".current($address_value)."<br/>";

            //$compact_address_list_tmp[$warehouse_name] = ;
        }
        $receive_address = rtrim($receive_address,"<br/>");

        return $receive_address;
    }

    /**
     * 获取 合同模板 需要的数据
     * @author Jolon
     * @param array $purchase_numbers 采购单号
     * @param array $post_data 提交的修改数据
     * @return mixed
     */
    public function get_compact_confirm_template_data($purchase_numbers,$post_data = [])
    {
        $this->load->helper('status_order');
        $this->load->helper('status_finance');
        $this->load->model('compact/compact_model','compactModelNew' );
        $this->load->model('supplier/supplier_model');
        $this->load->model('supplier/supplier_settlement_model');
        $this->load->model('user/purchase_user_model');

        $compact = $this->get_create_compact_data($purchase_numbers);// 获取 合同确认需要的数据

        $purchase_user_info = $this->purchase_user_model->get_user_info_by_user_id($compact['compact_main']['buyer_id']);// 根据采购员id获取联系电话
        $company_info = compactCompanyInfo($compact['compact_main']['purchase_name']);// 合同采购主体公司信息
        $supplier_info = $this->supplier_model->get_supplier_info($compact['compact_main']['supplier_code']);
        $pay_type = $compact['compact_main']['is_drawback'] ? PURCHASE_PAY_TYPE_PUBLIC : PURCHASE_PAY_TYPE_PRIVATE;// 退税的抓取对公的信息  不退税的抓取对私的信息
        $supplier_pay_info = $this->supplier_model->get_supplier_remit_information($compact['compact_main']['supplier_code'], $compact['compact_main']['is_drawback'], $compact['compact_main']['purchase_type_id']);//支付方式:1.支付宝,2.对公支付，3.对私支付

        $supplier_settlement = $this->supplier_settlement_model->get_settlement_one($compact['compact_main']['account_type']); // 结算方式
        if(isset($post_data['a_user_phone']) and $post_data['a_user_phone']){
            $a_phone = $post_data['a_user_phone'];
        }else{
            $a_phone = isset($purchase_user_info['phone_number']) ? $purchase_user_info['phone_number'] : '';
        }

        $a_linkman_info = [// 甲方联系人信息
            'a_linkman_id' => $compact['compact_main']['buyer_id'],
            'a_linkman'    => $compact['compact_main']['buyer_name'],
            'a_phone'      => $a_phone,
            'a_email'      => isset($company_info['a_email']) ? $company_info['a_email'] : ''
        ];
        $order_info_list = $compact['compact_main']['order_info_list'];
        $receive_address = $this->get_warehouse_address($a_linkman_info,$order_info_list); // 收货地

        $compact['compact_main']['settlement_name'] = $supplier_settlement['settlement_name'];
        $compact['compact_main']['freight_payer'] = getFreightPayment(isset($compact['compact_main']['is_freight']) ? $compact['compact_main']['is_freight'] : ''); // 运费支付方

        $compact['compact_number'] = $this->compactModelNew->getCompactNumber($compact['compact_main']['purchase_type_id'], true,array_unique(array_column($order_info_list,'purchase_type_id'))); // 虚拟的合同单号;

        // 合同-甲方公司信息
        $compact['compact_main']['a_company_name'] = $company_info['name'];
        $compact['compact_main']['a_address'] = $company_info['address'];
        $compact['compact_main']['a_linkman_id'] = $a_linkman_info['a_linkman_id'];
        $compact['compact_main']['a_linkman'] = $a_linkman_info['a_linkman'];
        $compact['compact_main']['a_phone'] = $a_linkman_info['a_phone'];
        $compact['compact_main']['a_email'] = $a_linkman_info['a_email'];
        // 合同-乙方公司信息
        $supplier_info_contact = $this->supplier_model->get_supplier_contact_info($compact['compact_main']['supplier_code']);
        $compact['compact_main']['b_company_name'] = isset($supplier_info['supplier_name']) ? $supplier_info['supplier_name'] : '';
        $compact['compact_main']['b_address'] = isset($supplier_info['register_address']) ? $supplier_info['register_address'] : '';
        $compact['compact_main']['b_linkman'] = isset($supplier_info['compact_linkman']) ? $supplier_info['compact_linkman'] : '';
        $compact['compact_main']['b_phone'] = isset($supplier_info_contact['mobile']) ? $supplier_info_contact['mobile'] : '';
        $compact['compact_main']['b_email'] = isset($supplier_info['compact_email']) ? $supplier_info['compact_email'] : '';
        $compact['compact_main']['b_corporate'] = isset($supplier_info['corporate']) ? $supplier_info['corporate'] : '';// 乙方法人代表
        $compact['compact_main']['b_payment_info'] = $supplier_pay_info;

        $get_payment_explain = $this->compactModelNew->get_payment_settlement_requests($compact['compact_main']['account_type']);
        // 收货地址
        $compactRequireInfo = compactRequireInfo();

        $warehouseAddress = getTransitWarehouseInfo($compact['compact_main']['warehouse_code'],'receive_address',$compact['compact_main']['a_linkman'],$compact['compact_main']['a_phone']);
        $compact['compact_main']['receive_address'] = $receive_address;
        $compact['compact_main']['ship_method'] = $compactRequireInfo['ship_method'];// 送货方式
        $compact['compact_main']['payment_explain'] = $get_payment_explain;// 付款说明
        $compact['compact_main']['cooperate_require'] = $compactRequireInfo['cooperate_require_for_text'];// 合作要求
        $compact['compact_main']['contract_require_attach'] = '货物损毁灭失的风险在货物到达甲方仓库前由乙方承担，到达甲方仓库后由甲方承担。';// 附加合约要求

        // 获取请款比例金额（process_cost：加工费逻辑与运费一致，直接累加到运费上面）
        $payment_list = compactPaymentPlan($compact['compact_main']['settlement_ratio'], $compact['compact_main']['total_price'], $compact['compact_main']['total_freight'] + $compact['compact_main']['total_process_cost'], $compact['compact_main']['total_discount'], $compact['compact_main']['is_drawback']);
        $compact['compact_main']['payment_list'] = $payment_list;
        return $compact;
    }

    /**
     * 获取 采购单-SKU 相关的数量
     *        备货单数量、采购数量（订单数量）、取消数量、收货数量、入库数量
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @param string $sku SKU
     * @return array|bool
     */
    public function calculate_sku_related_quantity($purchase_number, $sku)
    {
        if (empty($purchase_number) or empty($sku)) return false;

        $purchase_sku_qty = [
            'suggest_amount' => 0,// 备货单数量
            'order_amount'   => 0,// 订单数量
            'cancel_amount'  => 0,// 取消数量
            'receive_amount' => 0,// 收货数量
            'upselft_amount' => 0,// 入库数量
            'loss_amount'    => 0,// 报损数量
        ];


        $item_info = $this->purchase_order_items_model->get_item($purchase_number, $sku, true);
        if ($item_info) {
            $purchase_sku_qty['suggest_amount'] = $item_info['purchase_amount'];
            $purchase_sku_qty['order_amount']   = $item_info['confirm_amount'];
            $purchase_sku_qty['receive_amount'] = $item_info['receive_amount'];
            $purchase_sku_qty['upselft_amount'] = $item_info['upselft_amount'];
        }

        // 取消数量（取消记录表中读取汇总数据）
        $cancel_info = $this->purchase_db->select('sku,sum(pocd.cancel_ctq) as cancel_ctq')
            ->from('purchase_order_cancel as poc')
            ->join('purchase_order_cancel_detail as pocd', 'poc.id=pocd.cancel_id', 'inner')
            ->where('pocd.purchase_number', $purchase_number)
            ->where_in('poc.audit_status', [CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC])
            ->where('pocd.sku', $sku)
            ->get()->row_array();
        $purchase_sku_qty['cancel_amount'] = isset($cancel_info['cancel_ctq']) ? $cancel_info['cancel_ctq'] : 0;

        // 报损数量
        $this->load->model('warehouse/Warehouse_storage_record_model');
        $purchase_sku_qty['loss_amount'] = $this->Warehouse_storage_record_model->get_loss_info($purchase_number,$sku);

        return $purchase_sku_qty;
    }


    /**
     * 根据条件获取一条数据
     * @author Jaden 2019-1-10
     * @param string $where 查询条件
     * @param string $field 查询字段
     */
    public function getByWhereorderlist($where, $field='*')
    {
        if (empty($where)) {
            return false;
        }
        $this->purchase_db->select($field);
        $this->purchase_db->from($this->table_name);
        $this->purchase_db->where($where);
        $results = $this->purchase_db->get()->row_array();
        //echo $this->purchase_db->last_query();exit;
        return $results;
    }

    /**
     * 获取采购单历史数据
     * @param string $sku
     * @author harvin 21019-1-24
     * @return array data;
     **/
    public function order_history($sku, $limit, $offsets, $page)
    {
        $query = $this->purchase_db;
        $query->select("B.source,B.supplier_name,B.purchase_number,A.purchase_unit_price,A.purchase_amount,A.upselft_amount,B.audit_time,B.buyer_name,A.id");
        $query->from('purchase_order_items as A');
        $query->join('purchase_order as B', 'A.purchase_number=B.purchase_number', 'left');
        $query->where('A.sku', $sku);
        $query->where_in('B.purchase_order_status', [PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE]);

        $count_qb = clone $query;
        $query->limit($limit, $offsets);
        $data = $query->get()->result_array();

        if(!empty($data)){

            foreach($data as $key=>$value){

                if($value['source'] == 1){

                    $data[$key]['source_ch'] = '合同';
                }

                if($value['source'] == 2){

                    $data[$key]['source_ch'] = '网络';
                }

                if($value['source'] == 3){

                    $data[$key]['source_ch'] = '账期采购';
                }

                $paiNumber = $this->purchase_db->from("purchase_order_pay_type")->where("purchase_number",$value['purchase_number'])
                    ->select("pai_number")->get()->row_array();
                $data[$key]['painumber_data'] = (isset($paiNumber['pai_number']) && !empty($paiNumber['pai_number']))?$paiNumber['pai_number']:'';
            }
        }
        //统计
        $count_row = $count_qb->select("count(A.id) as num")->get()->row_array();
        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        $return_data = [
            'data' => $data,
            'total' => $total_count,
            'offset' => $page,
            'limit' => $limit,
        ];
        return $return_data;
    }

    /**
     * @desc 统计供应商三个有的合作金额
     * @author Jackson
     * @Date 2019-01-30 10:01:00
     * @return array()
     **/
    public function statistics_cooperation_amount(&$object = '')
    {
        if (!empty($object) && $object['count']) {
            //获取所有供应商CODE记录
            $supplierCodes = array_column($object['list'], 'supplier_code');
            $rowData = $this->cooperation_amount($supplierCodes);
            foreach ($object['list'] as $key => &$item) {
                $item['cooperation_amount'] = isset($rowData[$item['supplier_code']]) ? $rowData[$item['supplier_code']] : 0;
            }
        }

    }

    /**
     * @desc 获取统计供应商三个有的合作金额数据
     * @author Jackson
     * @parame array $supplierCode 供应商code
     * @Date 2019-01-30 10:01:00
     * @return array()
     **/
    public function cooperation_amount($supplierCode = array())
    {

        $totals = array();
        if (!empty($supplierCode)) {

            //最近三个月时间点
            $recentDateNode = date('Y-m-d H:i:s', mktime(23, 59, 59, date('m') - 3, date('t'), date('Y')));

            //查询条件
            $condition = array();
            $condition['where_in'] = array('supplier_code' => $supplierCode);
            $condition[$this->table_name . '.create_time>='] = $recentDateNode;

            //查询字段
            $fields = 'sum(oi.purchase_unit_price * oi.confirm_amount) as cooperation_amount,supplier_code';

            //order by
            $orderBy = '';

            //order by
            $groupBy = 'supplier_code';

            // 4. 查询数据
            $joinCondition = array(
                array(
                    "{$this->item_table_name} oi", "{$this->table_name}.purchase_number=oi.purchase_number", 'LEFT JOIN',
                ),
            );

            //数据查询
            $rowData = $this->getDataListByJoin($condition, $fields, $orderBy, 0, 1000, $groupBy, $joinCondition);

            /*获取老采购系统近三月合作金额*/
            $this->load->model('supplier/supplier_model');
            $old_purchase_cooperation = $this->supplier_model->get_old_purchase_cooperation_price($supplierCode);

            if ($rowData['total']) {
                foreach ($rowData['data'] as $key => $item) {
                    $old = isset($old_purchase_cooperation[$item['supplier_code']]) ? $old_purchase_cooperation[$item['supplier_code']]: 0;
                    $totals[$item['supplier_code']] = $item['cooperation_amount'] + $old;

                }
            }else{
                return $old_purchase_cooperation;
            }
        }
        return $totals;
    }

    /**
     * @desc 获取采购单操作日志
     * @author Jaxton
     * @parame  $purchase_number
     * @Date 2019-02-01 10:01:00
     * @return array()
     **/
    public function get_purchase_operator_log($purchase_number,$limit,$offset){
        $list=$this->purchase_db->select('*')->from('operator_log')
        ->where('record_type',$this->table_name)
        ->where('record_number',$purchase_number)
        ->order_by('operate_time','desc')
        ->order_by('id','desc')
        ->limit($limit,$offset)
        ->get()->result_array();
        if(!empty($list)){
            $new_data=[];
            foreach($list as $key => $val){
                $new_data[]=[
                    'operator'=>$val['operator'],
                    'operate_time'=>$val['operate_time'],
                    'operate_type'=>$val['content'],
                    'operate_detail'=>$val['content_detail']
                ];
            }
            return $new_data;

        }else{
            return false;
        }
    }

    /**
    * 获取报损界面sku数据
    * 2019-02-01
    * @author Jaxton
    * @parame $data
    * @return array()
    */
    public function get_reportloss_sku_data($data){
        $error_msg='';
        $success=false;
        if(!empty($data)){
            $this->load->model("purchase/Purchase_order_determine_model");
            $order_no_list = array_unique(array_column($data, 'purchase_number'));

            //检查是否存在数据
            $check_list = [];
            foreach ($data  as $data_info) {
                $check_list[] = $data_info['purchase_number'].'-'.$data_info['sku'];


            }

            foreach($order_no_list as $purchase_number){
                $order_info=$this->getByWhereorderlist(['purchase_number'=>$purchase_number]);
                $change_order_status=in_array($order_info['purchase_order_status'], [PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT]);
                $is_purchase_order_status=in_array($order_info['purchase_order_status'], [PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION,PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE]);
                $is_pay_status=in_array($order_info['pay_status'], [PAY_UNPAID_STATUS,PAY_MANAGER_REJECT,PAY_SOA_REJECT,PAY_FINANCE_REJECT,PART_PAID,PAY_PAID]);
                $error_str='采购单:'.$purchase_number;

                if ($change_order_status){
                    $error_msg.='PO：'.$purchase_number.' 状态是“信息修改驳回”或者“信息修改待审核”的，不允许点击，信息修改通过后再申请';
                    break;
                }

                if(!$is_purchase_order_status){
                    $error_msg.=$error_str.' 状态不是等待到货、已到货待检测、部分到货等待剩余';
                    break;
                }
                if(!$is_pay_status){
                    $error_msg.=$error_str.' 付款状态不是“未申请付款、经理驳回、财务驳回、已部分付款、已付款”';
                    break;
                }

                if ($order_info['is_ali_abnormal'] == 1) {
                    $error_msg.=$error_str.' 1688异常状态不能报损';
                }

                if($this->unfinished_cancel_status($purchase_number)){
                    $error_msg.=$error_str.' 该订单正在“取消未到货”审核中';
                    break;
                }
            }

            //获取sku数据，以采购单号-备货单号-sku为维度
            $list=$this->purchase_db->select('a.purchase_number,d.supplier_code,d.supplier_name,a.sku,a.demand_number,b.product_name,
            b.purchase_unit_price,c.instock_qty as upselft_amount,b.purchase_amount,b.purchase_amount as confirm_amount,b.product_img_url,b.freight,b.process_cost,s.suggest_order_status')
                ->from('purchase_suggest_map a')
                ->join($this->item_table_name.' b','a.sku=b.sku and a.purchase_number=b.purchase_number','left')
                ->join($this->suggest_table_name.' s','s.demand_number=a.demand_number','left')
                ->join($this->table_name.' d','a.purchase_number=d.purchase_number','left')
                ->join('warehouse_results_main c','c.sku=b.sku and c.purchase_number=b.purchase_number','left')
                ->where_in('a.purchase_number',$order_no_list)
                ->get()->result_array();
            $demand_list = [];
            if($list && count($list) >0){
                $demand_number = array_column($list, 'demand_number');
                $demand = $this->purchase_db->from('purchase_suggest')
                    ->select('demand_number,suggest_order_status')
                    ->where_in('demand_number', $demand_number)
                    ->get()
                    ->result_array();
                foreach($demand as $d_val){
                    $demand_list[$d_val['demand_number']] = $d_val['suggest_order_status'];
                }
            }

            $list_tmp = [];
            if(empty($error_msg)){
                $this->load->model('finance/Purchase_order_pay_model');
                if(!empty($list)){
                    $success=true;

                    foreach ($list as &$value){
                        // 24702
                        if(isset($value['demand_number']) && count($demand_list) > 0
                            && in_array($value['demand_number'], array_keys($demand_list))
                            && in_array($demand_list[$value['demand_number']], [
                                PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                                PURCHASE_ORDER_STATUS_CANCELED,
                                PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE
                            ]))continue;
                        $purchase_number = $value['purchase_number'];
                        $sku = $value['sku'];

                        $sign = $purchase_number.'-'.$sku;
                        if (in_array($sign,$check_list)) {
                            $value['is_show'] = 1;

                        } else {
                            $value['is_show'] = 0;


                        }






                        $value['purchase_total_price'] = format_two_point_price($value['confirm_amount'] * $value['purchase_unit_price']);
                        $value['upselft_amount'] = intval($value['upselft_amount']);
                        $order_cancel_list=$this->Purchase_order_determine_model->get_order_cancel_list($purchase_number,$sku);
                        $value['cancel_ctq']=isset($order_cancel_list[$purchase_number.'-'.$sku])?$order_cancel_list[$purchase_number.'-'.$sku]:0; //已取消数量
                        $value['cancel_total_price'] = format_two_point_price($value['cancel_ctq'] * $value['purchase_unit_price']);

                        $suggest_order_status = !empty($value['suggest_order_status'])?getPurchaseStatus($value['suggest_order_status']):'';
                        $value['suggest_order_status']  = $suggest_order_status;
                        // 已付金额
                        if(!isset($list_tmp[$purchase_number]['pur_number'])){
                            $order_paid = $this->Purchase_order_pay_model->get_pay_total_by_compact_number($purchase_number);
                            $list_tmp[$purchase_number]['pur_number'] = $purchase_number;
                            $list_tmp[$purchase_number]['paid_pay_price'] = format_two_point_price($order_paid['pay_price']);
                            $list_tmp[$purchase_number]['paid_product_money'] = format_two_point_price($order_paid['product_money']);
                            $list_tmp[$purchase_number]['paid_freight'] = format_two_point_price($order_paid['freight']);
                            $list_tmp[$purchase_number]['paid_discount'] = format_two_point_price($order_paid['discount']);
                            $list_tmp[$purchase_number]['paid_process_cost'] = format_two_point_price($order_paid['process_cost']);
                            $list_tmp[$purchase_number]['purchase_order_price'] = $value['purchase_total_price'];


                        } else {
                            $list_tmp[$purchase_number]['purchase_order_price'] += $value['purchase_total_price'];
                        }


                        $list_tmp[$purchase_number]['order_items'][] = $value;
                    }

                }else{
                    $error_msg .= '采购单数据获取失败';
                }
            }
            if(!empty($order_no_list)){
                $purchase_order_data = $this->get_purchase_order_data($order_no_list,'source,purchase_number,is_generate,supplier_code');
                if(!empty($purchase_order_data)) {
//                    $source_arr = array_unique(array_column($purchase_order_data, 'source'));
//                    if(count($source_arr)== 1 and $source_arr[0]==SOURCE_COMPACT_ORDER) {
//                        $is_generate_arr = array_unique(array_column($purchase_order_data, 'is_generate'));
//                        if(count($is_generate_arr)!=1 or $is_generate_arr[0]!=2){
//                            $error_msg.='无合同号不允许申请,请在列表中查询【是否生成合同单】';
//                        }
//                    }
                    $supplier_code_arr = array_unique(array_column($purchase_order_data, 'supplier_code'));
                    if(count($supplier_code_arr)> 1) {
                        $error_msg.='相同供应商的采购单才能批量申请报损';
                    }
                }
            }else{
                $error_msg.='采购单号缺失';
            }

            if(empty($error_msg)){
                return [
                    'success'=>$success,
                    'data'=>isset($list_tmp)?array_values($list_tmp):[]
                ];
            }else{
                return [
                    'success'=>false,
                    'data'=>$error_msg
                ];
            }
        }

    }

    /**
    *获取可申请报损的最大数量
    */
    public function max_report_loss_number($purchase_number,$sku){
        $where=['oi.purchase_number'=>$purchase_number,'oi.sku'=>$sku];
        $item_info=$this->purchase_db->select('oi.*,c.instock_qty')->from($this->item_table_name.' oi')
        ->join('warehouse_results_main c','c.sku=oi.sku and c.purchase_number=oi.purchase_number','left')
        ->where($where)->get()->row_array();

        $cancel=$this->purchase_db->select('SUM(a.cancel_ctq) sum_cancle_qty')->from('purchase_order_cancel_detail a')
        ->join('purchase_order_cancel b','a.cancel_id=b.id','left')
        ->where(['a.purchase_number'=>$purchase_number,'a.sku'=>$sku])
        ->group_start()
        ->where_in('b.audit_status',[CANCEL_AUDIT_STATUS_CG,CANCEL_AUDIT_STATUS_CF,CANCEL_AUDIT_STATUS_SCJT,CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC])
            ->or_group_start()
            ->where_in('b.audit_status',[CANCEL_AUDIT_STATUS_CGBH,CANCEL_AUDIT_STATUS_CFBH])
            ->where('b.is_edit', '2')
            ->group_end()
        ->group_end()
        ->get()->row_array();
        $total_cancel_qty=!empty($cancel['sum_cancle_qty'])?$cancel['sum_cancle_qty']:0;//取消数量

        $max_number=$item_info['confirm_amount']-$item_info['instock_qty']-$total_cancel_qty;
        $return['items_info'] = $item_info;
        $return['max_number'] = $max_number;

        return $return;
    }

    /**
    * 验证入库数量是否>=采购数量
    */
    public function is_number_correct($purchase_number,$sku){
        $where=['oi.purchase_number'=>$purchase_number,'oi.sku'=>$sku];
        $item_info=$this->purchase_db->select('oi.*,c.instock_qty')->from($this->item_table_name.' oi')
        ->join('warehouse_results_main c','c.sku=oi.sku and c.purchase_number=oi.purchase_number','left')
        ->where($where)->get()->row_array();
        if($item_info['instock_qty']>=$item_info['confirm_amount']){
            return false;
        }else{
            return true;
        }
    }

    /**
    *验证是否已报损
    */
    public function is_already_reportloss($purchase_number,$sku,$demand_number){
        $where=['pur_number'=>$purchase_number,'sku'=>$sku,'demand_number'=>$demand_number];
        $row=$this->purchase_db->select('*')->from('purchase_order_reportloss')
        ->where($where)->get()->row_array();
        if($row){
            return true;
        }else{
            return false;
        }
    }

    /**
     *判断采购单是否有除了驳回的记录
     */
    public function get_reportloss_info($purchase_number){
        $where=['pur_number'=>$purchase_number];

        $row=$this->purchase_db->select('*')->from('purchase_order_reportloss')
            ->where($where)->where_in('status',[REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT,REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT,
                REPORT_LOSS_STATUS_FINANCE_PASS])->get()->row_array();
        if($row){
            return true;
        }else{
            return false;
        }
    }

    /**
     *判断采购单是否有除了驳回的记录
     */
    public function unfinished_reportloss_info($purchase_number){
        $where=['pur_number'=>$purchase_number];

        $row=$this->purchase_db->select('*')->from('purchase_order_reportloss')
            ->where($where)->where_in('status',[REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT,REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT])
            ->get()
            ->row_array();
        if($row){
            return true;
        }else{
            return false;
        }
    }

    /**
    * 验证取消未到货的未完结的状态
     * @author Jaxton
     * @param string $purchase_number 采购单编号
     * @return boolean
    */
    public function unfinished_cancel_status($purchase_number){

        $cancel_info=$this->purchase_db->select('b.audit_status')->from('purchase_order_cancel_detail a')
                    ->join('purchase_order_cancel b','a.cancel_id=b.id','right')
                    ->where(['a.purchase_number'=>$purchase_number])
                    ->where_in('b.audit_status',[CANCEL_AUDIT_STATUS_CG,CANCEL_AUDIT_STATUS_CF,CANCEL_AUDIT_STATUS_SCJT])
                    ->get()->result_array();
        if($cancel_info){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 申请报损确认
     * 2019-02-01
     * @author Jaxton
     * @parame $data
     * @parame $remark
     * @return array()
     */
    public function reportloss_submit($data,$remark){
        $this->load->helper('user');
        $error_msg='';
        $success=false;
        if(!empty($data)){

            $this->load->model("purchase/Purchase_order_determine_model");
            $this->load->model('statement/Charge_against_records_model');
            $application_loss_fright=[];//申请报损的运费,按采购单汇总
            $application_loss_process_cost=[];//申请报损的加工费,按采购单汇总
            $loss_cancel_fright=[];//历史报损的运费,按采购单汇总
            $loss_cancel_process_cost=[];//历史报损的加工费,按采购单汇总
            $purchase_number_fright=[];//采购单总运费
            $purchase_number_process_cost=[];//采购单总加工费
            $history_cancel_fright=[];//历史取消的运费,按采购单汇总
            $history_cancel_process_cost=[];//历史取消的加工费,按采购单汇总
            $charge_against_process_cost=[];//采购单总退款冲销加工费
            $data = json_decode($data,true);
            foreach($data as $val){
                $error_str='采购单:'.$val['purchase_number'].' sku:'.$val['sku'];
                if(!is_numeric($val['loss_amount']) || $val['loss_amount']<=0){
                    continue;
                    /*$error_msg.=$error_str.' 报损数量填写错误';
                    break;*/
                }else{
                    $order_info=$this->getByWhereorderlist(['purchase_number'=>$val['purchase_number']]);
                    $is_purchase_order_status=in_array($order_info['purchase_order_status'], [PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION,PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE]);
                    $is_pay_status=in_array($order_info['pay_status'], [PAY_UNPAID_STATUS,PAY_SOA_REJECT,PAY_MANAGER_REJECT,PAY_FINANCE_REJECT,PART_PAID,PAY_PAID]);
                    if(!$is_purchase_order_status){
                        $error_msg.=$error_str.' 状态不是等待到货、已到货待检测、部分到货等待剩余';
                        break;
                    }
                    if(!$is_pay_status){
                        $error_msg.=$error_str.' 付款状态不是“未申请付款、经理驳回、财务驳回、已部分付款、已付款”';
                        break;
                    }
                    if(!isset($val['responsible_party']) or empty($val['responsible_party'])){
                        $error_msg.=$error_str.' 责任承担方式必填';
                        break;
                    }
                    $items_info = $this->max_report_loss_number($val['purchase_number'],$val['sku']);
                    if($val['loss_amount']>$items_info['max_number']){
                        $error_msg.=$error_str.' 超出可申请报损的最大数量';
                        break;
                    }
                    if(!$this->is_number_correct($val['purchase_number'],$val['sku'])){
                        $error_msg.=$error_str.' 不允许报损,请与仓库确定已入库数量后再报损';
                        break;
                    }
                    if($this->is_already_reportloss($val['purchase_number'],$val['sku'],$val['demand_number'])){
                        $error_msg.=$error_str.' 已报损过一次，不允许多次报损';
                        break;
                    }
                    if($this->unfinished_cancel_status($val['purchase_number'])){
                        $error_msg.=$error_str.' 该订单正在“取消未到货”审核中';
                        break;
                    }

                    if (!is_two_decimal(isset($val['loss_freight'])?$val['loss_freight']:0)){
                        $error_msg.=$error_str.' 报损运费小数最多只能为两位';
                        break;
                    }

                    if (!is_two_decimal(isset($val['loss_process_cost'])?$val['loss_process_cost']:0)){
                        $error_msg.=$error_str.' 报损加工费小数最多只能为两位';
                        break;
                    }

                    if (!is_two_decimal($val['loss_totalprice'])){
                        $error_msg.=$error_str.' 报损金额小数最多只能为两位';
                        break;
                    }
                    if (bccomp($val['loss_totalprice'] - $val['loss_freight'] - $val['loss_process_cost'],$val['price'] * $items_info['max_number'],2) > 0 ){
                        $error_msg.=$error_str.' 报损商品额必须小于 可申请报损数量*单价';
                        break;
                    }

                    //将同一个采购单的申请的报损运费相加
                    if (!isset($application_loss_fright[$val['purchase_number']])){
                        $application_loss_fright[$val['purchase_number']]=isset($val['loss_freight'])?$val['loss_freight']:0;
                    }/*else{
                        $application_loss_fright[$val['purchase_number']]+=isset($val['loss_freight'])?$val['loss_freight']:0;
                    }*/

                    //将同一个采购单的申请的报损加工费相加
                    if (!isset($application_loss_process_cost[$val['purchase_number']])){
                        $application_loss_process_cost[$val['purchase_number']]=isset($val['loss_process_cost'])?$val['loss_process_cost']:0;
                    }/*else{
                        $application_loss_process_cost[$val['purchase_number']]+=isset($val['loss_process_cost'])?$val['loss_process_cost']:0;
                    }*/

                    //查询是否有报损运费
                    $purchase_order_reportloss_fright = $this->purchase_db->select('pur_number,loss_freight,loss_process_cost')
                        ->where('pur_number', $val['purchase_number'])
                        ->group_by('stamp_number,pur_number')
                        ->get('purchase_order_reportloss')
                        ->result_array();

                    if (!empty($purchase_order_reportloss_fright)){
                        //将报损取消的运费放入数组
                        if (!isset($loss_cancel_fright[$val['purchase_number']])){
                            $loss_cancel_fright_total = array_column($purchase_order_reportloss_fright,'loss_freight');
                            $loss_cancel_fright_total = array_sum($loss_cancel_fright_total);
                            $loss_cancel_fright[$val['purchase_number']]=$loss_cancel_fright_total;

                            $loss_cancel_process_cost_total = array_column($purchase_order_reportloss_fright,'loss_process_cost');
                            $loss_cancel_process_cost_total = array_sum($loss_cancel_process_cost_total);
                            $loss_cancel_process_cost[$val['purchase_number']]=$loss_cancel_process_cost_total;
                        }
                    }else{
                        $loss_cancel_fright[$val['purchase_number']]=0;
                        $loss_cancel_process_cost[$val['purchase_number']]=0;
                    }

                    $charge_against_record = $this->Charge_against_records_model->get_ca_audit_status($val['purchase_number'],2);
                    //将历史取消的运费优惠相加
                    if (!isset($charge_against_record[$val['purchase_number']])){
                        $charge_against_process_cost[$val['purchase_number']]=$charge_against_record['charge_against_process_cost'];
                    }else{
                        $charge_against_process_cost[$val['purchase_number']]+=$charge_against_record['charge_against_process_cost'];
                    }

                    $total_freight_discount = $this->Purchase_order_determine_model->get_total_freight_discount_true($val['purchase_number']);//查询总运费和总优惠

                    if (!isset($purchase_number_fright[$val['purchase_number']])){
                        $purchase_number_fright[$val['purchase_number']]=$total_freight_discount['total_freight'];
                        $purchase_number_discount[$val['purchase_number']]=$total_freight_discount['total_discount'];
                        $purchase_number_process_cost[$val['purchase_number']]=$total_freight_discount['total_process_cost'];
                    }

                    $freight_discount= $this->Purchase_order_determine_model->get_freight_discount([$val['purchase_number']]);  //历史取消的运费及优惠额
                    //将历史取消的运费优惠相加
                    if (!isset($history_cancel_fright[$val['purchase_number']])){
                        $history_cancel_fright[$val['purchase_number']]=$freight_discount['history_freight'];
                    }

                    if (!isset($history_cancel_process_cost[$val['purchase_number']])){
                        $history_cancel_process_cost[$val['purchase_number']]=$freight_discount['history_process_cost'];
                    }
                    $stamp_number          = time();//报损批次

                    $add_datas[]=[
                        'bs_number' => get_prefix_new_number('BS'.date('Ymd'),1,3),
                        'pur_number'=>$val['purchase_number'],
                        'sku'=>$val['sku'],
                        'demand_number' => $val['demand_number'],
                        'product_name'=>$val['product_name'],
                        'purchase_amount'=>$val['purchase_amount'],
                        'price'=>$val['price'],
                        'confirm_amount'=>$val['confirm_amount'],
                        'loss_amount'=>$val['loss_amount'],
                        'loss_totalprice'=>isset($val['loss_totalprice'])?$val['loss_totalprice']:$val['loss_amount']*$val['price'],
                        'loss_freight'=>isset($val['loss_freight'])?$val['loss_freight']:0,
                        'loss_process_cost'=>isset($val['loss_process_cost'])?$val['loss_process_cost']:0,
                        'responsible_user'=>isset($val['responsible_user'])?$val['responsible_user']:'',
                        'responsible_user_number'=>isset($val['responsible_user_number'])?$val['responsible_user_number']:'',
                        'responsible_party'=>isset($val['responsible_party'])?$val['responsible_party']:'',
                        'apply_time'=>date('Y-m-d H:i:s'),
                        'apply_person'=>getActiveUserName(),
                        'remark'=>$remark,
                        'stamp_number'=>$stamp_number
                    ];
                }
            }

            foreach ($application_loss_fright as $purchase_number => $value){
                //判断申请取消运费是否>0 或<=po的总优惠额-已取消的总优惠额
                if ($application_loss_fright[$purchase_number]<0 ||
                    $application_loss_fright[$purchase_number]>($purchase_number_fright[$purchase_number]-$history_cancel_fright[$purchase_number]-$loss_cancel_fright[$purchase_number]) ){
                    $error_msg.= $purchase_number . '报损的运费额总和已超过po的总运费额-已取消的总运费额-已取消的运费，请检查后在操作报损';
                    break;
                }
            }
            foreach ($application_loss_process_cost as $purchase_number => $value){
                //判断申请取消加工费是否>0 或<=po的总加工费-已取消的总加工费
                if ($application_loss_process_cost[$purchase_number]<0 ||
                    $application_loss_process_cost[$purchase_number]>($purchase_number_process_cost[$purchase_number]-$history_cancel_process_cost[$purchase_number]-$loss_cancel_process_cost[$purchase_number] -$charge_against_process_cost[$purchase_number]) ){
                    $error_msg.= $purchase_number . '报损的加工费额总和已超过po的总加工费-已取消的总加工费-已取消的加工费-已退款冲销加工费，请检查后在操作报损';
                    break;
                }
            }

            if(!empty($add_datas) && empty($error_msg)){
                //将报损信息循环写入报损计划表
                foreach ($add_datas as $sys_data) {
                    //查询是否是海外仓计划系统备货单
                    $plan_demand_info = $this->shipment_track_list_model->get_track_by_demand($sys_data['demand_number']);
                    if (!empty($plan_demand_info)) {
                        $this->shipping_report_loss_model->apportion_amount_to_plan($sys_data,$plan_demand_info);

                    }


                }                /*$this->purchase_db->trans_begin();
                try{
                    $add_ids = [];
                    foreach ($add_datas as $add_data){
                        $add_result=$this->purchase_db->insert('purchase_order_reportloss',$add_data);
                        if (empty($add_result)) throw new Exception('新增报损记录失败');
                        $id = $this->purchase_db->insert_id('purchase_order_reportloss');
                        array_push($add_ids,$id);
                    }

                    //请求仓库进行锁仓,锁仓失败的删除掉
                    $msg = $this->report_loss_model->push_wms_to_lock_warehouse($add_ids);
                    if (!empty($msg['msg'])) $error_msg.=$msg['msg'];

                    //推送锁仓成功的报损数据至数据中心
                    $this->report_loss_model->push_report_loss_data_to_service($msg['success_ids']);

                    $this->purchase_db->trans_commit();
                    $success=true;
                }catch(Exception $e){
                    $this->purchase_db->trans_rollback();
                    $error_msg.=$e->getMessage();
                }*/

                //请求java接口报损
                $report_loss_service_url = getConfigItemByName('api_config', 'java_system_service', 'report_loss'); //获取java报损接口地址
                $access_taken = getOASystemAccessToken();//访问java token

                $url_api=$report_loss_service_url."?access_token=".$access_taken;
                $results = getCurlData($url_api, json_encode($add_datas), 'post', array('Content-Type: application/json'));

                $loss_result = json_decode($results, true);

                if (isset($loss_result['code'])&&$loss_result['code']!=200){

                    $error_msg.=isset($loss_result['msg'])?$loss_result['msg']:'报损失败';

                }elseif(!isset($loss_result['code'])){
                    $error_msg.=$results;

                }

                //存入消息队列数据
                $purchase_number = array();
                $success_flag = false;

                if (isset($loss_result['code'])&&$loss_result['code']==200) {
                    foreach ($loss_result['data'] as $value){
                        if (!$value['success']){
                            $error_msg.=isset($value['message'])?'po:'.$value['purNumber'].'报损失败.'.$value['message']:'报损失败';
                        }else{
                            $success_flag = true;
                            $purchase_number[] = $value['purNumber'];
                        }
                    }


                }


                if ($success_flag){
                    $success=true;
                }

                if (!empty($purchase_number)) {
                    /**********报损成功推入消息队列，等待判断在途是否异常处理-START**********/
                    foreach ($purchase_number as $item){
                    $this->load->library('Rabbitmq');
                    //创建消息队列对象
                    $mq = new Rabbitmq();
                    //设置参数
                    $mq->setExchangeName('PURCHASE_ORDER_INNER_ON_WAY_EX_NAME');
                    $mq->setRouteKey('PURCHASE_ORDER_INNER_ON_WAY_R_KEY');
                        $mq->setType(AMQP_EX_TYPE_FANOUT);//设置为多消费者模式 分发
                    //构造存入数据
                    $push_data = array(
                        'type' => 'purchase_order_loss',
                            'purchase_number' => $item
                    );
                    //存入消息队列
                    $mq->sendMessage($push_data);
                        //延迟0.1秒
                        usleep(100000);
                    }
                    /**********报损成功推入消息队列，等待判断在途是否异常处理-END**********/
                }

            }else{
                if(empty($error_msg)){
                    $error_msg.='报损数据填写有误，请检查';
                }

            }
        }else{
            $error_msg.='传入参数有误';
        }
        return [
            'success'=>$success,
            'error_msg'=>$error_msg
        ];
    }
    /**
     * 获取采购单号
     * @param int $id 参数id
     * @author harvin 2019-2-13
     * **/
    public function get_purchase_order($id){

      $order= $this->purchase_db->select('purchase_number')
               ->where_in('id',$id)
               ->get('purchase_order_items')->result_array();

      $data=[];
        foreach ($order as $key => $value) {
          $data[]=$value['purchase_number'];
        }
      //去掉相同的采购单号
         $data= array_unique($data);
         return $data;
   }

    /**
     * 更新采购单状态(动态指定更新状态)
     * @author Jackson
     * @param string $purchase_number 采购单号
     * @param int $new_status 目标状态
     * @return bool
     */
    public function change_designation_status($purchase_number, $new_status = array(),$msg='变更采购单状态',$operator='')
    {
        $result = true;
        //获取被更新字段
        $update_field = array_keys($new_status);
        $purchase_order = $this->get_one($purchase_number, false);
        if(empty($purchase_order)){
            throw new Exception('采购单不存在');
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
            $result = $this->update($new_status, ['purchase_number' => $purchase_number]);
            if(!$result){
                $result = false;
            }
            //记录日志
            if ($result) {
                $oldChangeText = implode(",", $changList['old_status_name']);//原状态名称
                $newChangeText = implode(",", $changList['new_status_name']);//新状态名称

                if(isset($new_status['pay_status']) and in_array($new_status['pay_status'],[PAY_PAID,PART_PAID])){
                    // 采购单付款状态变更需要刷新冲销汇总
                    $mq = new Rabbitmq();//创建消息队列对象
                    $mq->setQueueName('STATEMENT_CHANGE_PAY_STATUS_REFRESH');//设置参数
                    $mq->setExchangeName('STATEMENT_CHANGE_PAY_STATUS');//构造存入数据
                    $mq->setRouteKey('SO_REFRESH_FOR_003');
                    $mq->setType(AMQP_EX_TYPE_DIRECT);
                    $mq->sendMessage(['purchase_number' => $purchase_number,'add_time' => time()]);// 保持格式一致
                }

                operatorLogInsert(
                    [
                        'id' => $purchase_number,
                        'type' => $this->table_name,
                        'content' => $msg,
                        'detail' => $msg."，从【{$oldChangeText}】 改为【{$newChangeText}】",
                        'user'=>!empty($operator)?$operator:getActiveUserName(),
                    ]);
            } else {
                throw new Exception("更新失败Table" . $this->table_name);
            }

        } catch (Eexception $e) {
            throw new Exception($msg."失败: code：" . $e->getMessage());
        }
        return $result;
    }

    /**
     * 更新 采购单  1688相关状态
     * @author Jolon
     * @param   string $purchase_number
     * @param    array $update
     * @return bool
     */
    public function update_order_ali_status($purchase_number,$update){
        $result = $this->purchase_db->where('purchase_number',$purchase_number)
            ->update($this->table_name,$update);

        return $result;
    }

    /**
     * 更新 待采购审核之前状态的 采购单的采购员（供应商修改采购员后变更）
     * @author Jolon
     * @param  int     $purchase_type_id
     * @param   string $supplier_code
     * @param    int   $buyer_id
     * @param string   $buyer_name
     * @return array
     */
    public function update_order_buyer($purchase_type_id,$supplier_code,$buyer_id,$buyer_name = ''){
        $demand_number_list=[];
        $this->load->helper('status_order');
        if(empty($buyer_name)) $buyer_name = get_buyer_name($buyer_id);
          //判断是否有生成采购单
       $order= $this->purchase_db->select('purchase_number')->where('purchase_type_id',$purchase_type_id)
            ->where('supplier_code',$supplier_code)
            ->where_in('purchase_order_status',[PURCHASE_ORDER_STATUS_WAITING_QUOTE])
           ->get($this->table_name)->result_array();
        if(empty($order)){ //没有生成采购单 无需修改采购单的采购员
           return $demand_number_list;
        }
        /* 2019-07-26 采购单的采购员不需要再变更 Jaden
        $this->purchase_db->where('purchase_type_id',$purchase_type_id)
            ->where('supplier_code',$supplier_code)
            ->where_in('purchase_order_status',[PURCHASE_ORDER_STATUS_WAITING_QUOTE,PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT])
            ->update($this->table_name,['buyer_id' => $buyer_id,'buyer_name' => $buyer_name]);
        */
       //获取采购单判断的备货单号
        $purchase_number_list= array_column($order, 'purchase_number');
        $suggest=$this->purchase_db
                ->select('demand_number')
                ->where_in('purchase_number',$purchase_number_list)
                ->get('purchase_suggest_map')
                ->result_array();
        //获取备货单号
        $demand_number_li= is_array($suggest)?array_column($suggest, 'demand_number'):[];
        $demand_number_list= array_unique($demand_number_li);
        operatorLogInsert(
            [
                'id'      => $purchase_type_id.'-'.$supplier_code,
                'type'    => $this->table_name,
                'content' => '变更采购单的采购员',
                'detail'  => "更新 待采购审核之前状态的 采购单的采购员[$purchase_type_id][$supplier_code][$buyer_id][$buyer_name]",
            ]);
        return $demand_number_list;
    }
    /**
     * 更新采购单的结算方式
     * @param type $supplier_settlement
     * @param type $supplier_code
     * @return boolean
     */
    public function update_order_settlement($supplier_settlement,$supplier_code){
             //判断是否有生成采购单
       $order= $this->purchase_db->select('purchase_number')
            ->where('supplier_code',$supplier_code)
            ->where_in('purchase_order_status',[PURCHASE_ORDER_STATUS_WAITING_QUOTE])
            ->get($this->table_name)->result_array();

        if(empty($order)){
            return TRUE;
        }
        $purchase_number= is_array($order)?array_column($order, 'purchase_number'):[];
        if(empty($purchase_number)){
            return TRUE;
        }
        $this->purchase_db
            ->where_in('purchase_number',$purchase_number)
            ->where_in('purchase_order_status',[PURCHASE_ORDER_STATUS_WAITING_QUOTE])
            ->update($this->table_name,['account_type' => $supplier_settlement]);

        $demand_number = $this->purchase_db->select('demand_number')->where_in('purchase_number',$purchase_number)
                        ->get($this->suggest_map_table_name)
                        ->result_array();
        $demand_arr = is_array($order)?array_column($demand_number, 'demand_number'):[];
        if(!empty($demand_arr)){
            $this->purchase_db
                ->where_in('demand_number',$demand_arr)
                ->update($this->suggest_table_name,['account_type' => $supplier_settlement]);
        }

        //未完结需求单
        $suggest_list = $this->purchase_db->select('demand_number')
            ->where('supplier_code',$supplier_code)
            ->where('suggest_status',SUGGEST_STATUS_NOT_FINISH)
           ->get($this->suggest_table_name)->result_array();
        $sug_demand_arr = is_array($suggest_list)?array_column($suggest_list, 'demand_number'):[];
        if(!empty($sug_demand_arr)){
            $this->purchase_db
                ->where_in('demand_number',$sug_demand_arr)
                ->update($this->suggest_table_name,['account_type' => $supplier_settlement]);
        }

       return TRUE;
    }

    /**
     * @desc 通过派单号获取核实是否线上账期交易
     * @author Jeff
     * @Date 2019/03/17 16:07
     * @param $pai_number
     * @return array
     */
    public function get_ali_order_info($pai_number)
    {
        $this->load->library('alibaba/AliOrderApi');
        $data = $this->aliorderapi->getListOrderDetail(null, $pai_number);

        if (!isset($data[$pai_number])){
            return ['status'=>'error','error_message' => '没有要查询的数据'];
        }
        if(!isset($data[$pai_number]['code']) || $data[$pai_number]['code'] != 200 || !isset($data[$pai_number]['data'])){
            return ['status'=>'error','error_message' => '返回信息错误'];
        }

        $p_data = $data[$pai_number]['data'];
        if((isset($p_data['baseInfo']['tradeTypeDesc']) && $p_data['baseInfo']['tradeTypeDesc'] =='账期交易') ||
            (isset($p_data['baseInfo']['flowTemplateCode']) && $p_data['baseInfo']['flowTemplateCode'] == "accountPeriod30min")){
            return [
                'status'=>'success',
                'accoutPeriodTime'=>!isset($p_data['orderBizInfo']['accountPeriodTime']) || empty($p_data['orderBizInfo']['accountPeriodTime']) ? '0000-00-00 00:00:00' : $p_data['orderBizInfo']['accountPeriodTime']
                ];
        }
        return ['status'=>'error','error_message'=>'该拍单号:'.$pai_number.'并未对应“线上账期”结算方式，无法提交'];
    }


    /**
     * 未编辑默认显示30列
     * @author harvin
     * @date 2019-4-1
     */
    public function table_header(){
           $key_value = [
            'product_img_url' => '图片',
            'purchase_order_status' => '订单状态',
            'sku' => 'sku',
            'purchase_number' => '采购单号',
            'product_name' => '产品名称',
            'compact_number' => '合同号',
            'buyer_name' => '采购员',
            'purchase_name' => '采购主体',
            'supplier_name' => '供应商',
            'purchase_amount' => '采购数量',
            'purchase_price' => '采购金额',
            'warehouse_code' => '采购仓库',
            'is_expedited' => '加急采购单',
            'logistics_trajectory' => '物流轨迹',
            'account_type' => '结算方式',
            'pay_type' => '支付方式',
            'settlement_ratio' => '结算比例',
            'shipping_method_id' => '供应商运输',
            'source' => '采购来源',
            'freight' => '运费',
            'is_freight' => '运费支付',
            'discount' => '优惠额',
            'freight_formula_mode' => '运费计算方式',
            'purchase_acccount' => '账号',
            'pai_number' => '拍单号',
            'is_ali_order' => '是否1688下单',
            'remark' => '1688订单状态',
            'modify_remark' => '其他备注',
            'destination_warehouse' => '目的仓',
            'product_status' => '产品状态',
            'state_type'     => '开发类型',
               'is_gateway_ch' => '是否对接门户',
               'is_new_ch' => '是否新品',
               'is_overeas_first_order_ch'=>'是否海外首单',
               'is_customized' => '是否定制',
               'first_plan_arrive_time' => '首次预计到货时间'
        ];
        return $key_value;
    }

    /**
     * @desc 推送采购单状态给计划系统
     * @author Jeff
     * @Date 2019/4/3 11:56
     * @param $purchase_number /采购单号
     * @return
     */
    public function push_purchase_order_info_to_plan($purchase_number)
    {
        if(PUSH_PLAN_SWITCH == false) return true;

        //推送采购单信息至计划系统
        $this->rediss->select(0);
        $this->rediss->lpushData('push_purchase_order_info_to_plan',$purchase_number);
        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_push_purchase_order_info_to_plan');
        return true;
    }

    /**
     * 推送蓝凌系统
     * @author harvin
     * @param $val 采购单号
     */
    public function pushing_blue_ling($val){
        $this->load->model('Reject_note_model');
        $this->load->helper('status_supplier');
        //推送蓝凌系统
        $order = $this->purchase_db->select("*")->where('purchase_number', $val)->get('purchase_order')->row_array();
        if (empty($order)) {
            throw new Exception('采购单不存在');
        }
        $this->load->model("supplier/Supplier_settlement_model");
        $settlement_code_list = $this->Supplier_settlement_model->get_code2name_list([$order['account_type']]);
        $compact_pay = $this->purchase_db
                ->select('settlement_ratio,purchase_acccount,pai_number')
                ->where('purchase_number', $val)
                ->get('purchase_order_pay_type')
                ->row_array();
        if (empty($compact_pay)) {
            throw new Exception('请款单不存在');
        }
        $orderinfo = $this->purchase_db->where('purchase_number', $order['purchase_number'])->get('purchase_order_items')->result_array();
        if (empty($orderinfo)) {
            throw new Exception('采购明细表不存在');
        }

        foreach ($orderinfo as $vvv) {
            $purchase_numbers[] = $order['purchase_number'];
            $buyer_username[] = empty($order['buyer_name']) ? '李四' : $order['buyer_name'];
            $purchase_name[] = compactCompanyInfo($order['purchase_name'],'name');
            $supplier_name[] = $order['supplier_name'];
            $is_drawback[] = $order['is_drawback'] == PURCHASE_IS_DRAWBACK_Y ? '是' : '否';
            $is_include_tax[] = $order['is_include_tax'] == 1 ? "是" : "否";
            $product_img_url[] = erp_sku_img_sku($vvv['product_img_url']);
            $warehouse_name[] = $this->warehouse_model->get_warehouse_one($order['warehouse_code'], 'warehouse_name');
            $sku[] = $vvv['sku'];
            $product_name[] = $vvv['product_name'];
            $purchase_amount[] = $vvv['purchase_amount'];
            $purchase_money[] = format_two_point_price($vvv['purchase_amount'] * $vvv['purchase_unit_price'] + $vvv['freight'] - $vvv['discount']);
            $is_new[] = $vvv['is_new'] == PURCHASE_PRODUCT_IS_NEW_Y ? "是" : "否";
            $purchase_unit_price[] = format_two_point_price($vvv['purchase_unit_price']);
            $pay_types[] = getPayType($order['pay_type']);
            $settlement_ratios[] = $compact_pay['settlement_ratio'];
            $shipping_method_ids[] = getShippingMethod($order['shipping_method_id']);
            $purchase_acccounts[] = $compact_pay['purchase_acccount'];
            $source[] = getPurchaseSource($order['source']); //采购来源
            $is_destroy[] = $order['is_destroy'] == 1 ? "是" : "否";
            $purchase_type_id[] = getPurchaseType($order['purchase_type_id']);
            $purchase_order_statuss[] = getPurchaseStatus($order['purchase_order_status']);
            $is_cross_borders[] = $order['is_cross_border'] == 1 ? "是" : "否";
            $account_types[] = !empty($order['account_type']) ? $settlement_code_list[$order['account_type']] : '';
            $plan_product_arrive_times[] = $order['plan_product_arrive_time'];
            $discounts[] = $vvv['discount'];
            $freights[] = $vvv['freight'];
            $pai_numbers[] = $compact_pay['pai_number'];
        }

        $data_temp = [
            $this->config->item('purchase_order')['purchase_number_test'] => $order['purchase_number'], //采购单号
            $this->config->item('purchase_order')['buyer_name'] => empty($order['buyer_name']) ? '张三' : $order['buyer_name'], //申请人（采购员）
            $this->config->item('purchase_order')['department'] => '采购部', //申请部门
            $this->config->item('purchase_order')['modify_time'] => $order['modify_time'], //申请日期
            $this->config->item('purchase_order')['product_img_url'] => $product_img_url, //SKU
            $this->config->item('purchase_order')['sku'] => $sku, //SKU
            $this->config->item('purchase_order')['product_name'] => $product_name, //产品名称
            $this->config->item('purchase_order')['purchase_name'] => $purchase_name, //采购主体
            $this->config->item('purchase_order')['supplier_name'] => $supplier_name, //供应商
            $this->config->item('purchase_order')['purchase_amount'] => $purchase_amount, //采购数量
            $this->config->item('purchase_order')['purchase_money'] => $purchase_money, //采购金额
            $this->config->item('purchase_order')['is_new'] => $is_new, //是否新品
            $this->config->item('purchase_order')['is_drawback'] => $is_drawback, //是否退税
            $this->config->item('purchase_order')['is_include_tax'] => $is_include_tax, //是否含税
            $this->config->item('purchase_order')['purchase_unit_price'] => $purchase_unit_price, //含税单价
            $this->config->item('purchase_order')['warehouse_name'] => $warehouse_name, //采购仓库
            $this->config->item('purchase_order')['pay_type'] => $pay_types, //支付方式
            $this->config->item('purchase_order')['settlement_ratio'] => $settlement_ratios, //结算比例
            $this->config->item('purchase_order')['shipping_method_id'] => $shipping_method_ids, //供应商运输
            $this->config->item('purchase_order')['purchase_acccount'] => $purchase_acccounts, //账号
            $this->config->item('purchase_order')['source'] => $source, //采购来源
            $this->config->item('purchase_order')['is_destroy'] => $is_destroy, //是否核销
            $this->config->item('purchase_order')['purchase_type_id'] => $purchase_type_id, //业务线
            $this->config->item('purchase_order')['purchase_order_status'] => $purchase_order_statuss, //采购状态
            $this->config->item('purchase_order')['is_cross_border'] => $is_cross_borders, //跨境宝供应商
            $this->config->item('purchase_order')['account_type'] => $account_types, //结算方式
            $this->config->item('purchase_order')['plan_product_arrive_time'] => $plan_product_arrive_times, //预计到货时间
            $this->config->item('purchase_order')['discount'] => $discounts, //优惠额
            $this->config->item('purchase_order')['freight'] => $freights, //运费
            $this->config->item('purchase_order')['pai_number'] => $pai_numbers, //拍单号
        ];

        $formValues = json_encode($data_temp, JSON_UNESCAPED_UNICODE);
        $id = $this->config->item('purchase_order_id'); //表單id;
        $userinfo = getActiveUserInfo();
        $username = $userinfo['staff_code'];
        $docsubject = "采购单审核采购单号:" . $order['purchase_number'];
        $result = $this->approval_model->orderlient($formValues, $username, $docsubject, $id);
        apiRequestLogInsert([
            'api_url'=>$order['purchase_number'],
            'post_content'=>$formValues,
        ]);
        if (!empty($result) && is_object($result) && isset($result->return)) {
            unset($data_temp);
            unset($purchase_numbers);
            unset($buyer_username);
            unset($supplier_name);
            unset($is_drawback);
            unset($is_include_tax);
            unset($product_img_url);
            unset($warehouse_name);
            unset($sku);
            unset($product_name);
            unset($purchase_amount);
            unset($purchase_money);
            unset($is_new);
            unset($purchase_unit_price);
            unset($pay_types);
            unset($settlement_ratios);
            unset($settlement_ratios);
            unset($shipping_method_ids);
            unset($purchase_acccounts);
            unset($source);
            unset($is_destroy);
            unset($purchase_type_id);
            unset($purchase_name);
            unset($purchase_order_statuss);
            unset($is_cross_borders);
            unset($account_types);
            unset($plan_product_arrive_times);
            unset($discounts);
            unset($freights);
            unset($pai_numbers);
            $data_process = [
                'purchase_number' => $order['purchase_number'],
                'processid' => $result->return,
                'create_time' => date("Y-m-d H:i:s"),
            ];
            $this->purchase_db->insert('purchase_blue_process', $data_process);
        } else {
            throw new Exception('推送蓝凌失败,未绑定工号');
        }
    }

    /**
     * function:更新采购单供应商来源
     * @param supplier_code : 供应商代码
     *        supplier_source: 供应商来源
     * @return Bool  成功返回True  失败返回False
     **/

    public function update_relate_supplier_source( $supplier_code, $supplier_source ) {

        if( empty($supplier_code) || empty( $supplier_source) ) {

            return False;
        }

        $order_list = $this->purchase_db->select('id ')
            ->from('purchase_order ')
            ->where('supplier_code', $supplier_code)
            ->where_in('purchase_order_status',
                [
                    PURCHASE_ORDER_STATUS_WAITING_QUOTE
                ]
            )->get()->result_array();
        if( !empty($order_list) ) {

            $order_list = array_column($order_list, 'id');

            $suggest_create_list = $this->purchase_db->select('ps.id as id')
                ->from('purchase_suggest ps')
                ->join('purchase_suggest_map psm', 'ps.demand_number = psm.demand_number', 'left')
                ->join('purchase_order po', 'psm.purchase_number = po.purchase_number', 'left')
                ->where('ps.supplier_code', $supplier_code)
                ->where('is_create_order', 1)
                ->where_in('po.id',$order_list)
                ->get()->result_array();
            //echo $this->purchase_db->last_query();die();

            $suggest_create_list = array_column($suggest_create_list, 'id');


        }else{

            return True;
        }

        $suggest_not_list = $this->purchase_db->select('id')
            ->where('supplier_code', $supplier_code)
            ->where('is_create_order', 0)
            ->from('purchase_suggest')
            ->get()->result_array();
        $suggest_list = [];
        if( !empty($suggest_not_list) ) {

            $suggest_not_list = array_column($suggest_not_list, 'id');
            $suggest_list = array_merge($suggest_create_list, $suggest_not_list);
        }else{
            $suggest_list = $suggest_create_list;

        }
        if ($suggest_list) {
            foreach ($suggest_list as $key => $id) {
                $update_data[$key]['id'] = $id;
                $update_data[$key]['supplier_source'] = $supplier_source;
            }
            $this->purchase_db->update_batch('purchase_suggest', $update_data,'id');

        }

        return True;
    }
    /**
     * @desc 产品表更新供应商名称 （未变成“等待到货”的采购单，采购单关联的合同单）
     * @param $new_supplier_name
     * @return bool
     * @author Sinder
     * @date 2019-06-01
     */
    public function update_relate_supplier_name($supplier_code, $new_supplier_name) {
        if (empty($supplier_code) || empty($new_supplier_name)) {
            return false;
        }
        $suggest_create_list = [];

        $product_list = $this->purchase_db->select('id')
            ->where('supplier_code', $supplier_code)
            ->from('product')
            ->get()->result_array();
        if($product_list){
            $product_list = array_column($product_list, 'id');
            foreach ($product_list as $key => $id) {
                $update_data_pro[$key]['id'] = $id;
                $update_data_pro[$key]['supplier_name'] = $new_supplier_name;
            }
            if(isset($update_data_pro) && $update_data_pro){
                $product_re = $this->purchase_db->update_batch('product', $update_data_pro,'id');

            }
        }

        $order_list = $this->purchase_db->select('id')
            ->from('purchase_order')
            ->where('supplier_code', $supplier_code)
            ->where_in('purchase_order_status',
                [
                    PURCHASE_ORDER_STATUS_WAITING_QUOTE

                ]
            )->get()->result_array();
        // 等待采购询价,不是信息修改待审核状态,待采购审核,待销售审核
        if ($order_list) {
            $order_list = array_column($order_list, 'id');
            $order_re = $this->purchase_db
                ->where_in('id', $order_list)
                ->update('purchase_order', ['supplier_name' => $new_supplier_name]);



            $order_str = format_query_string($order_list);

            $suggest_create_list = $this->purchase_db->select('ps.id as id')
                ->from('purchase_suggest ps')
                ->join('purchase_suggest_map psm', 'ps.demand_number = psm.demand_number', 'left')
                ->join('purchase_order po', 'psm.purchase_number = po.purchase_number', 'left')
                ->where('ps.supplier_code', $supplier_code)
                ->where('is_create_order', 1)
                ->where_in('po.id in (' . $order_str . ')')
                ->get()->result_array();

            $suggest_create_list = array_column($suggest_create_list, 'id');
        }

        $suggest_not_list = $this->purchase_db->select('id')
            ->where('supplier_code', $supplier_code)
            ->where('is_create_order', 0)
            ->from('purchase_suggest')
            ->get()->result_array();
        if($suggest_not_list){
            $suggest_not_list = array_column($suggest_not_list, 'id');
            $suggest_list = array_merge($suggest_create_list, $suggest_not_list);

            if ($suggest_list) {
                foreach ($suggest_list as $key => $id) {
                    $update_data[$key]['id'] = $id;
                    $update_data[$key]['supplier_name'] = $new_supplier_name;
                }
                $suggest_re = $this->purchase_db->update_batch('purchase_suggest', $update_data,'id');


            }
        }

        return true;
    }

    /**
     * 保存采购列表字段判断用户显示
     * @param json $data json数据
     * @author harvin
     * ** */
    public function save_table_list($data, $type, $uid=null)
    {
        //获取登录用户id
        $userid = getActiveUserId();
        $tmp = [];
        $header_data = json_decode($data,true);
        //去除没选的存入role表
        foreach ($header_data as $key => $value){
            if ($value['status']!=0){
                $tmp[$value['index']] = $key;
            }
        }

        if (empty($tmp)){
            return false;
        }

        ksort($tmp);
        $final = $tmp;

        $type_arr = [
            TIPS_SEARCH_ALL_ORDER,
            TIPS_SEARCH_WAITING_CONFIRM,
            TIPS_SEARCH_WAITING_ARRIVE,
            TIPS_SEARCH_ORDER_FINISH,
            TIPS_SEARCH_TODAY_WORK,
            TIPS_SEARCH_WAIT_CANCEL,
        ];
        if(in_array($type, $type_arr)){
            $re1 = true;
            // 写入缓存  HEADER_SEARCH_DATA_  uid _ list_type       HEADER_SEARCH_DATA_DEFAULT
            $hash = "HEADER_SEARCH_DATA_LIST";
            $hash_field = "HEADER_SEARCH_DATA_".$uid."_".$type;
            $this->rediss->addHashData($hash, $hash_field, $data);
            // 默认数据设置
            $hash_def = "HEADER_SEARCH_DATA_DEFAULT";
            if(!$this->rediss->checkHashData($hash, $hash_def)){
                $hash_def_data = [
                    "purchase_order_status"=> [
                        "index"=> 0,
                        "status"=> 1,
                        "name"=> "采购状态"
                    ],
                    "suggest_order_status"=> [
                        "index"=> 1,
                        "status"=> 1,
                        "name"=> "备货单状态"
                    ],
                    "purchase_number"=> [
                        "index"=> 2,
                        "status"=> 1,
                        "name"=> "采购单号"
                    ],
                    "create_time"=> [
                        "index"=> 3,
                        "status"=> 1,
                        "name"=> "创建时间"
                    ]
                ];
                $hash_def_data = json_encode($hash_def_data);
                $this->rediss->addHashData($hash, $hash_def, $hash_def_data);
            }
        }else{
            //先查询表是否有数据  有就更新 没有就增加
            $user_role = $this->purchase_db->where('userid', $userid)->get('purchase_user_role')->row_array();
            if(empty($user_role)){
                $roleuser = [
                    'userid' => $userid,
                    'role'   => json_encode($final),
                ];
                $re1      = $this->purchase_db->insert('purchase_user_role', $roleuser);
            }else{
                $roleuser = [
                    'role' => json_encode($final),
                ];
                $re1      = $user_role = $this->purchase_db->where('userid', $userid)->update('purchase_user_role', $roleuser);
            }
        }

        //先查询表是否有数据  有就更新 没有就增加
        $header = $this->purchase_db->where('user_id', $userid)->where('list_type',$type)->get('list_header')->row_array();
        if (empty($header)) {
            $roleuser = [
                'user_id' => $userid,
                'header_content' => $data,
                'list_type' => $type,
                'create_time' => date("Y-m-d H:i:s"),
            ];
            $re2 = $this->purchase_db->insert('list_header', $roleuser);
        } else {
            $roleuser = [
                'header_content' => $data,
                'modify_time' => date("Y-m-d H:i:s"),
            ];
            $re2 = $user_role = $this->purchase_db->where('id', $header['id'])->update('list_header', $roleuser);
        }

        if ($re1 && $re2) {
            return true;
        } else {
            return false;
        }
    }

    public function get_note_list($purchase_number, $sku)
    {
        $note_list = [];

        $sql = "select 
                it.modify_remark,it.modify_time,it.modify_user_name,
                p.freight_note,
                o.audit_note,o.audit_name,o.audit_time,
                o.change_data_audit_note,
                o.change_data_apply_note 
            from pur_purchase_order_items as it 
            left join pur_purchase_order as o on it.purchase_number=o.purchase_number 
            left join pur_purchase_order_pay_type as p on it.purchase_number=p.purchase_number
            left join pur_purchase_order_remark as k on it.id=k.items_id 
            where it.purchase_number='{$purchase_number}' and it.sku='{$sku}' group by it.id;";
        $data = $this->purchase_db->query($sql)->row_array();

        $note = ["批量编辑的备注", "运费说明", "采购经理审核驳回备注", "信息修改申请备注", "信息修改审核备注"];
        $x = 0;
        foreach ($note as $val){
            $row = ['note' => '', 'user' => '', 'time' => '', 'notice_type' => $val];
            switch ($x){
                case 0:
                    $row['note'] = SetAndNotEmpty($data, 'modify_remark') ? $data['modify_remark'] : '';
                    $row['user'] = SetAndNotEmpty($data, 'modify_user_name') ? $data['modify_user_name'] : '';
                    $row['time'] = SetAndNotEmpty($data, 'modify_time') ? $data['modify_time'] : '';
                    break;
                case 1:
                    $freight_note = SetAndNotEmpty($data, 'freight_note') ? explode('_',$data['freight_note']) : '';
                    if($freight_note == '')break;
                    $row['note'] = isset($freight_note[0]) ? $freight_note[0] : '';
                    $row['user'] = isset($freight_note[1]) ? $freight_note[1] : '';
                    $row['time'] = isset($freight_note[2]) ? $freight_note[2] : '';
                    break;
                case 2:
                    $row['note'] = SetAndNotEmpty($data, 'audit_note') ? $data['audit_note'] : '';
                    $row['user'] = SetAndNotEmpty($data, 'audit_name') ? $data['audit_name'] : '';
                    $row['time'] = SetAndNotEmpty($data, 'audit_time') ? $data['audit_time'] : '';
                    break;
                case 3:
                    $change_apply = SetAndNotEmpty($data, 'change_data_apply_note') ? explode('_',$data['change_data_apply_note']) : '';
                    if($change_apply == '')break;
                    $row['note'] = isset($change_apply[0]) ? $change_apply[0] : '';
                    $row['user'] = isset($change_apply[1]) ? $change_apply[1] : '';
                    $row['time'] = isset($change_apply[2]) ? $change_apply[2] : '';
                    break;
                case 4:
                    $change_audit = SetAndNotEmpty($data, 'change_data_audit_note') ? explode('_',$data['change_data_audit_note']) : '';
                    if($change_audit == '')break;
                    $row['note'] = isset($change_audit[0]) ? $change_audit[0] : '';
                    $row['user'] = isset($change_audit[1]) ? $change_audit[1] : '';
                    $row['time'] = isset($change_audit[2]) ? $change_audit[2] : '';
                    break;
                    break;
            }
            $note_list[$x] = $row;
            $x ++;
        }

        //采购单备注
        $purchase_remark_list = $this->purchase_db->select("k.*")
            ->from("purchase_order_remark as k")
            ->join("pur_purchase_order_items as it", "k.purchase_number=it.purchase_number and it.id=k.items_id", "left")
            ->where(["it.purchase_number" => $purchase_number, "it.sku"=> $sku])
            ->get()
            ->result_array();
        if ($purchase_remark_list){
            foreach ($purchase_remark_list as $key => $item){
                $note_list[] = [
                    'note' => $item['remark'],
                    'user' => $item['user_name'],
                    'time' => $item['create_time'],
                    'notice_type' => '添加备注'
                ];
            }
        }
        return $note_list;

    }

    /**
     * 审核信息修改待审核状态的采购单
     *      1.针对网采单，审核通过，订单状态更改为等到货状态
     *      2.针对合同单，审核通过，订单状态更改为等待生成进货单状态
     *      3.驳回,状态改为信息审核驳回
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @param string $check_status 审核状态
     * @param string $reject_order_note 驳回备注
     * @return array
     */
    public function audit_order_change_order($purchase_number, $check_status, $reject_order_note,$usercode=null)
    {
        $return = ['code' => false, 'msg' => '', 'data' => ''];
        $purchase_order = $this->get_one($purchase_number, false);
        if (empty($purchase_order)) {
            $return['msg'] = '采购单不存在';
            return $return;
        }
        if ($purchase_order['purchase_order_status']!=PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT) {
            $return['msg'] = '不是信息修改待审核状态';
            return $return;
        }

        /* 查询如果同一采购单号，是否退税、供应商代码、结算方式、支付方式、结算比例是否一致 2019-06-21 */
        if(false && $check_status == 1){
            $check_info = $this->purchase_order_items_model->check_purchase_number_is_disabled($purchase_number);
            if(!empty($check_info['msg']) and $check_info['code']==500){
                $return['msg'] = $check_info['msg'];
                return $return;
            }
        }
        /*end*/

        if (!empty($usercode)) {
            //调用采购接口 获取采购员名
            $this->load->model('user/Purchase_user_model');
            $resurs = $this->Purchase_user_model->get_user_info_by_staff_code($usercode);
            if (isset($resurs['user_name']) && $resurs['user_name']) {
                $user = $resurs['user_name'];
            } else {
                $user = '系统';
            }
        } else {
            $user = NULL;
        }

        $user_name = getActiveUserName();
        $user_name = !empty($user_name)?$user_name:' ';
        if ($check_status == 1) {// 驳回审核：审核通过->状态变为
            $this->load->helper('status_supplier');
            if(false && ($is_disable = supplierIsDisabled($purchase_order['supplier_code'])) !== false){
                $return['msg'] = $is_disable;
                return $return;
            }
            $check_status = '审核通过';

            $result = $this->change_status($purchase_number, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL);// 7.等待到货

            operatorLogInsert(
                ['id' => $purchase_number,
                 'type' => $this->table_name,
                 'user'=>$user,
                 'content' => '信息修改审核采购单',
                 'detail' => $check_status,
                ]);
            $this->purchase_db->where('purchase_number', $purchase_number)
                ->update($this->table_name, ['is_ali_abnormal' => 0]);//将异常状态改为非异常,付款状态改为未申请付款

            if($purchase_order['source'] == SOURCE_COMPACT_ORDER){// 更新合同单总金额、总运费、总优惠额
                $this->load->model('compact_model', '', false, 'compact');
                $this->compact_model->refresh_compact_data(null,$purchase_number);
            }

        } else {// 驳回审核：审核不通过
            $check_status = '审核不通过';
            $result = $this->change_status($purchase_number, PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT);// 采购单状态变为  信息修改驳回
            operatorLogInsert(
                ['id' => $purchase_number,
                 'type' => $this->table_name,
                 'user'=>$user,
                 'content' => '信息修改审核采购单',
                 'detail' => '驳回采购单信息修改-原因：' . $reject_order_note
                ]);
            $this->Message_model->AcceptMessage('purchase',['data'=>[$purchase_number],'message'=>$reject_order_note,'user'=>getActiveUserName(),'type'=>'信息修改审核']);

            $this->purchase_db->where('purchase_number', $purchase_number)
                ->update($this->table_name, ['change_data_audit_note' => $reject_order_note.'_'.$user_name.'_'.date('Y-m-d H:i:s')]);
        }

        if ($result) {

            $return['code'] = true;
        } else {
            $return['msg'] = '采购单状态变更失败';
        }

        return $return;
    }

    /**
     * 验证 采购单状态是否可以修改订单信息
     * @param $purchase_order_status
     * @return bool|string
     */
    public function check_status_able_change($purchase_order_status){
        if(!in_array($purchase_order_status,
                     [
                         PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                         PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION,
                         PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                         PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                         PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                         PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT,
                         PURCHASE_ORDER_STATUS_CANCELED
                     ]
            )){
            return '采购单状态为【等待到货】【已到货待检测】【全部到货】【部分到货等待剩余到货】【部分到货不等待剩余到货】【信息修改驳回】【已作废订单】才允许进行此操作';
        }else{
            return true;
        }
    }

    /**
     * 验证 采购单付款状态是否可以修改订单信息
     * @param int $pay_status
     * @return bool|string
     */
    public function check_pay_status_able_change($pay_status){
        if(!in_array($pay_status,
                     [
                         PAY_UNPAID_STATUS,
                         PAY_SOA_REJECT,
                         PAY_MANAGER_REJECT,
                         PAY_FINANCE_REJECT,
                         PAY_WAITING_MANAGER_REJECT,PAY_CANCEL,
                         PAY_REJECT_SUPERVISOR,
                         PAY_REJECT_MANAGER,
                         PAY_REJECT_SUPPLY,
                         PAY_GENERAL_MANAGER_REJECT,
                         PART_PAID,
                         PAY_PAID
                     ]
        )){
            return '只有付款状态为【未申请付款、请款被驳回、请款已取消】状态才允许进行此操作';
        }else{
            return true;
        }
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
                    if(!isset($res[$val['sku']]))$res[$val['sku']] = $val['is_drawback']?$val['purchase_unit_price']:$val['product_base_price'];
                }
            }
            return $res;
        }
        return 0;
    }

    /**
     * 根据采购订单获取采购员的联系方式
     * @param string $purchase_no
     * @return mixed
     */
    public function get_access_purchaser_information($purchase_no =''){
        $sql = "select b.user_name,b.phone_number  AS iphone from  pur_purchase_order a join pur_purchase_user_info  b on a.buyer_id=b.user_id where a.purchase_number='".$purchase_no."'";
        return $this->purchase_db->query($sql)->row_array();
    }


    /**
     * 获取采购订单总金额
     * @param $purchase_number
     * @return int
     */
    public function get_purchase_order_total($purchase_number){
        $sql = "SELECT sum(purchase_unit_price*confirm_amount) total  FROM yibai_purchase.pur_purchase_order_items where purchase_number='".$purchase_number."' GROUP BY purchase_number";
        $data = $this->purchase_db->query($sql)->row_array();
        $total =0;
        if(!empty($data)){
            $total = $data['total'];
        }
        return $total;
    }

    /** 根据供应商编码获取未完结采购单(如果存在  则不允许禁用供应商) 完结状态为（全部到货 部分到货不等待剩余 作废订单）
     * @param $supplier_code 供应商编码
     * @return array
     */
    public function get_unfinished_order_by_supplier_code($supplier_code){
        $result = $this->purchase_db
                        ->select('purchase_number')
                        ->where('supplier_code',$supplier_code)
                        ->where_not_in('purchase_order_status',[9,11,14])
                        ->get($this->table_name)
                        ->row_array();
        return $result;

    }

    /**
     * 获取该采购单取消中数量
     * @param string $purchase_number
     * @author luxu 2019-3-8
     * @return  array
     */
    public function  get_progress_order_cancel_ctq($sku,$purchase_number){
        $cancel_ctq = 0;
        $cancel_detail_order = $this->purchase_db->select('cancel_id')
            ->where('sku=',$sku)
            ->where('purchase_number', $purchase_number)
            ->get('purchase_order_cancel_detail')
            ->result_array();
        if (empty($cancel_detail_order)) {
            return $cancel_ctq;
        }
        //判断是状态是否已完成
        $cancel_id_list = array_column($cancel_detail_order, 'cancel_id');
        $cancel_id_list = array_unique($cancel_id_list);
        $order_cancel = $this->purchase_db
            ->select('audit_status,id')
            ->where_in('id', $cancel_id_list)
            ->get('purchase_order_cancel')
            ->result_array();
        if (empty($order_cancel)) {
            return $cancel_ctq;
        }
        foreach ($order_cancel as $vv) {
            if ($vv['audit_status'] == CANCEL_AUDIT_STATUS_CF ||$vv['audit_status']==CANCEL_AUDIT_STATUS_SCJT || $vv['audit_status'] == CANCEL_AUDIT_STATUS_CG) {
                $data_id[] = $vv['id'];
            }
        }
        if (empty($data_id)) {
            return $cancel_ctq;
        }
        $cancel_detail = $this->purchase_db->select('cancel_ctq')
            ->where_in('cancel_id', $data_id)
            ->where('purchase_number',$purchase_number)
            ->get('purchase_order_cancel_detail')
            ->result_array();
        if(empty($cancel_detail)){
            return $cancel_ctq;
        }
        foreach ($cancel_detail as $val) {
            $cancel_ctq += $val['cancel_ctq'];
        }
        return $cancel_ctq;
    }

    /**
     * 获取历史取消的数量
     * @param string  $purchase_number
     * @param array $sku
     * @return array
     */
    public function get_order_cancel_list($purchase_number,$sku,$type=True){
        $data = [];
        $data_id=[];
        $order_cancel_detail = $this->purchase_db->select('cancel_id')
            ->where('purchase_number', $purchase_number)
            ->where_in('sku', $sku)
            ->get('purchase_order_cancel_detail')
            ->result_array();
        if (empty($order_cancel_detail))
            return [];
        //判断是状态是否已完成
        $cancel_id_list= array_column($order_cancel_detail, 'cancel_id');
        $cancel_id_list= array_unique($cancel_id_list);

        $order_cancel=$this->purchase_db
            ->select('audit_status,id')
            ->where_in('id',$cancel_id_list)
            ->get('purchase_order_cancel')
            ->result_array();

        if(empty($order_cancel)) return [];
        foreach ($order_cancel as $vv) {
            if(in_array($vv['audit_status'],[CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_YDC])){
                $data_id[]=$vv['id'];
            }
        }
        if(empty($data_id)) return [];
        $cancel_detail = $this->purchase_db->select('cancel_ctq,sku,purchase_number')
            ->where_in('cancel_id', $data_id)
            ->get('purchase_order_cancel_detail')
            ->result_array();
        if(empty($cancel_detail)) return [];
        if( False == $type) {
            foreach ($cancel_detail as $key => $value) {
                $data[$value['sku'] . $value['purchase_number']][] = $value['cancel_ctq'];
            }
        }else{

            foreach ($cancel_detail as $key => $value) {
                $data[$value['sku']][] = $value['cancel_ctq'];
            }
        }
        foreach ($data as $key => $val) {
            $data[$key] = array_sum($val);
        }
        return $data;
    }

    /**
     * 报损中的数量
     * author:luxu
     **/
    public function get_reportloss_data( $purchase_number,$sku,$source ) {

        $where = array(

            "pur_number" => $purchase_number,
            "sku" => $sku
         );
        return $this->purchase_db->from("purchase_order_reportloss")->select(" SUM(loss_amount) AS loss_amount ")->where($where)->where_in("status",array(
              REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT,
            REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT
        ))->get()->row_array();

    }

    /**
     * 成功报损数量
     * author:luxu
     **/
    public function get_reportloss_success_data( $purchase_number,$sku,$source ) {
        // 如果是合同订单，到合同表获取合同号
        if( SOURCE_COMPACT_ORDER == $source ) {

            $query = $this->db->from("purchase_compact_items")->select("compact_number")->where("purchase_number='".$purchase_number."'");
            $result = $query->where("bind=1")->get()->row_array();
            $purchase_number = $result['compact_number'];
        }


        $where = array(

            "pur_number" => $purchase_number,
            "sku" => $sku
        );
        return $this->purchase_db->from("purchase_order_reportloss")->select(" SUM(loss_amount) AS loss_amount ")->where($where)->where_in("status",array(
            REPORT_LOSS_STATUS_FINANCE_PASS
        ))->get()->row_array();

    }

    public function get_arrival_qty($purchase_number,$sku ) {

        $result =  $this->purchase_db->from("warehouse_results")->select(" sum(arrival_qty) AS arrival_qty,sum(breakage_qty) AS breakage_qty ")->where("purchase_number='".$purchase_number."'")->where("sku='".$sku."'")->get()->row_array();
        return $result;
    }

    /**
     * 入库数量
     *
     **/
    public function get_instock_qty($purchase_number,$sku) {
        $result =  $this->purchase_db->from("warehouse_results_main")->select(" sum(instock_qty) AS instock_qty ")->where("purchase_number='".$purchase_number."'")->where("sku='".$sku."'")->get()->row_array();
        return $result;

    }


    private function get_purchase_progress_query( $params,$total = False ,$export_user=[]) {

        $user_id=jurisdiction(); //当前登录用户ID
        $user_groups_types = user_group_check(); //当前登录用户ID的权限所属的组
        $role_name=get_user_role();//当前登录角色
        $data_role = getRole();//数据权限配置
        $res_arr = array_intersect($role_name, $data_role);
        $this->purchase_db->reset_query();
        // 如果用户传入一级产品线
        $category_all_ids =[];
        $categroy_flag = False;
        if( isset($params['product_line_id']) && !empty($params['product_line_id']))
        {
            $category_all_ids = $this->product_line_model->get_all_category($params['product_line_id']);

            $children_ids = explode(",",$category_all_ids);
            $category_all_ids = array_filter($children_ids);
            $categroy_flag = True;
        }

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

        $query_builder = $this->purchase_db;
        $query_builder->from("purchase_progress as progress ");
        $query_builder->join("pur_product_line AS line","line.product_line_id=progress.product_line_ch","LEFT");
        $query_builder->join("pur_product AS tproduct","tproduct.sku=progress.sku","LEFT");
        $query_builder->join("pur_purchase_suggest AS suggest","suggest.sku=progress.sku AND suggest.demand_number=progress.demand_number","LEFT");
        $query_builder->join("pur_purchase_order AS orders","orders.purchase_number=progress.purchase_number","LEFT");
        $query_builder->join("pur_purchase_order_items AS items","progress.purchase_number=items.purchase_number AND progress.sku=items.sku","LEFT");

        $query_builder->join("pur_purchase_demand AS demand","demand.suggest_demand=suggest.demand_number","LEFT");
        $query_builder->join("pur_purchase_order_cancel_detail as cancel","items.id = cancel.items_id","LEFT");
        $res_arr = array_intersect($role_name, $data_role);

        if( !isset($params['swoole'])) {
            if (!(!empty($res_arr) OR $user_id === true)) {// 根据数据权限查询采购单
                $query_builder->where_in("progress.buyer_id", $user_id);
                if (is_array($user_groups_types)) {
                    $query_builder->where_in('orders.purchase_type_id', $user_groups_types);
                }
            }
        }
        if( isset($params['cancel_reason'])){
            $query_builder->where_in("cancel.cancel_reason",$params['cancel_reason']);
        }

        if( isset($params['ids']) && !empty($params['ids'])) {
            $params['ids'] = explode(",",$params['ids']);
            $query_builder->where_in("progress.id",$params['ids']);
        }

        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            if(is_array($user_id) && !empty($user_id)) {
                $params['groupdatas'] = array_merge($params['groupdatas'], $user_id);
            }
            $query_builder->or_where_in("progress.buyer_id",$params['groupdatas']);
        }

        //按照是否在途异常查询
        if(isset($params['on_way_abnormal']) && is_numeric($params['on_way_abnormal'])){
            $query_builder->where('progress.on_way_abnormal', $params['on_way_abnormal'], false);
        }
        // 是否新品

        if( isset($params['is_new']) && $params['is_new'] != NULL){

            $query_builder->where("suggest.is_new",$params['is_new']);
        }

        if( isset($params['suggest_demand_number']) && !empty($params['suggest_demand_number'])){

            $suggest_demand_number = array_filter( $params['suggest_demand_number']);
            if(!empty($suggest_demand_number)) {

                $query_builder->where_in("demand.demand_number", $suggest_demand_number);
            }
        }

        if( isset($params['is_merge']) && $params['is_merge']!=NULL){
            if($params['is_merge'] == 2){
                $params['is_merge'] = 0;
            }
            $query_builder->where("suggest.is_merge",$params['is_merge']);
        }

        if( isset($params['demand_type']) && !empty($params['demand_type'])){

            $query_builder->where_in("suggest.demand_name_id",$params['demand_type']);
        }


        //如果用户传入跟单员
        if( isset($params['documentary_name']) && !empty($params['documentary_name'])) {

            $query_builder->where_in('progress.documentary_name',$params['documentary_name']);
        }

        // 货源状态

        if( isset($params['apply_status']) && !empty($params['apply_status'])){

            $query_builder->where_in('tproduct.supply_status',$params['apply_status']);
        }


        if( !empty($category_all_ids) )
        {
            $query_builder->where_in("progress.product_line_ch",$category_all_ids);
        }else if($categroy_flag == True && empty($category_all_ids)){
            $query_builder->where_in("progress.product_line_ch",'');
        }

        if( !empty($params['suggest_order_status']))
        {
            $query_builder->where_in("suggest.suggest_order_status",$params['suggest_order_status']);
        }
        if( !empty($params['demand_purchase_type_id']))
        {
            if(is_array($params['demand_purchase_type_id'])){
                $query_builder->where_in("suggest.purchase_type_id",$params['demand_purchase_type_id']);
            }else{
                $query_builder->where("suggest.purchase_type_id",$params['demand_purchase_type_id']);
            }
        }

        // 用户传入7天销量

        if( isset($params['seven_day_sales']) && !empty($params['seven_day_sales']))
        {
            if( $params['seven_day_sales'] == 1)
            {
                //sku_sale7
                $query_builder->where("tproduct.days_sales_7>0");
            }else{
                $query_builder->where("tproduct.days_sales_7=0");
            }
        }

        // 缺货数量
        if (!empty($params['stock_owes_start']) && !empty($params['stock_owes_end'])) {
            //缺货数量开始范围和结束范围都不为空
            $query_builder->where("suggest.left_stock>=", $params['stock_owes_start'])->where("suggest.left_stock<=", $params['stock_owes_end']);
        } elseif (!empty($params['stock_owes_start'])) {
            //缺货数量开始范围不为空
            $query_builder->where("suggest.left_stock>=", $params['stock_owes_start']);
        } elseif (!empty($params['stock_owes_end'])) {
            //缺货数量结束范围不为空
            $query_builder->where("suggest.left_stock<=", $params['stock_owes_end']);
        }

        // 如果用户选择了备货单
        if( isset($params['demand_number']) && !empty($params['demand_number']) ) {
            $params['demand_number'] = explode(" ",$params['demand_number']);
            $query_builder->where_in(" progress.demand_number",$params['demand_number']);
        }

        // 如果用户选择采购员

        if ( isset($params['buyer_id']) && !empty( $params['buyer_id']) ) {
            $query_builder->where_in( " suggest.buyer_id",$params['buyer_id']);
        }

        // 如果用户传入订单状态
        if( isset($params['purchase_status']) && !empty($params['purchase_status']) ) {

            $query_builder->where_in(" orders.purchase_order_status",$params['purchase_status']);
        }
        // 如果用户传入预计到货时间
        if( isset($params['estimate_time_start']) && !empty($params['estimate_time_start']) ) {

            $query_builder->where("items.plan_arrive_time>=",$params['estimate_time_start']);
        }

        // 如果用户传入预计到货时间
        if( isset($params['estimate_time_end']) && !empty($params['estimate_time_end']) ) {

            $query_builder->where("items.plan_arrive_time<=",$params['estimate_time_end']);
        }

        // 如果用户传入审核时间
        if( isset($params['create_time_start']) && !empty($params['create_time_start']) ) {
            $query_builder->where(" progress.create_time>=", $params['create_time_start']);
        }
        // 如果用户传入审核时间
        if( isset($params['create_time_end']) && !empty($params['create_time_end']) ) {
            $query_builder->where(" progress.create_time<=", $params['create_time_end']);
        }
        //按业务线查询
        if(!empty($params['purchase_type_id']) && is_array($params['purchase_type_id'])){
            $query_builder->where_in("orders.purchase_type_id", $params['purchase_type_id']);
        }

        // 如果用户传入快递单号或者传入物流轨迹状态
        if( (isset($params['courier_number']) && !empty($params['courier_number']))
            OR (isset($params['track_status']) && $params['track_status'] != '')
            OR (isset($params['exp_no_is_empty']) && is_numeric($params['exp_no_is_empty']))
            OR (!empty($params['batch_no']))) {

//            $query_builder->join("purchase_order_pay_type AS purchasetype","purchasetype.purchase_number=progress.purchase_number");
            $query_builder->join("purchase_logistics_info AS pli","pli.purchase_number=progress.purchase_number AND pli.sku = progress.sku",'left');
            //按快递单号查询
            if(!empty($params['courier_number'])){
                $query_builder->where_in(" pli.express_no",$params['courier_number']);
            }
            //按轨迹状态查询
            if(isset($params['track_status']) && $params['track_status'] != ''){
                $track_status = explode(',', $params['track_status']);
                if(in_array(0,$track_status)){
                    $track_status_str = implode(',',$track_status);
                    $query_builder->where("(pli.status IS NULL OR pli.status IN ($track_status_str))");
                }else{
                    $query_builder->where_in("pli.status", $track_status, false);
                }
            }
            //按快递单号是否为空查询
            if (isset($params['exp_no_is_empty']) && is_numeric($params['exp_no_is_empty']) && 0 == $params['exp_no_is_empty']) {
                $query_builder->where("(pli.express_no!='' AND pli.express_no IS NOT NULL)");
            } elseif (isset($params['exp_no_is_empty']) && 1 == $params['exp_no_is_empty']) {
                $query_builder->where("(pli.express_no='' OR pli.express_no IS NULL)");
            }
            //按发货批次号查询
            if (!empty($params['batch_no'])) {
                $batch_no = array_filter(explode(" ", $params['batch_no']));
                if(!empty($batch_no)){
                    $query_builder->where_in("pli.batch_no", $batch_no);
                }
            }
            $query_builder->group_by('progress.demand_number');
        }

        // 如果传入是否缺货

        if ( isset($params['stock_owes']) && $params['stock_owes']!='' ) {

            //缺货
            if( $params['stock_owes'] == 1 ) {

                $query_builder->where("suggest.left_stock>=",1);
            }else{
                $query_builder->where("suggest.left_stock",0);
            }


        }

        // 如果用户传入采购仓库
        if( isset($params['warehouse_code']) && !empty($params['warehouse_code']) ) {

            $query_builder->where_in(" progress.warehouse_code",$params['warehouse_code']);
        }

        if( isset($params['pertain_wms']) && !empty($params['pertain_wms']) ) {
            $query_builder->where(" orders.pertain_wms",$params['pertain_wms']);
        }

        // 如果用户传采购来源
        if( isset($params['source']) && !empty($params['source']) ) {

            $query_builder->where_in(" progress.source",$params['source']);
        }

        // 如果用户输入供应商

        if( isset($params['supplier_code']) && !empty($params['supplier_code']) ) {
            $query_builder->where_in(" progress.supplier_code",$params['supplier_code']);
        }

        // 如果用户传入拍单号 pur_purchase_order_pay_type

        if( isset($params['pai_number']) && !empty($params['pai_number']) ) {

            $query_builder->where_in(" progress.pai_number",$params['pai_number']);
        }
        // 如果用户传入SKU
        if( isset($params['sku']) && !empty($params['sku']) ) {
            // $query_builder->like('documentary_name',$params['documentary_name'],'after');
            // $query_builder->where_in(" progress.sku",$params['sku']);
            $skus = explode(" ",$params['sku']);
            if( count($skus)==1) {
                $query_builder->like('progress.sku', $params['sku'], 'both');
            }else{

                $query_builder->where_in("progress.sku",$skus);
            }
        }

        if( isset($params['purchase_number']) && !empty($params['purchase_number']) ) {

            $params['purchase_number'] =  explode(" ",$params['purchase_number']);
            $query_builder->where_in(" progress.purchase_number",$params['purchase_number']);
        }

        if( isset($params['is_overdue']) && !empty($params['is_overdue']) ) {

            $query_builder->JOIN( "(SELECT id AS id,arrival_date,purchase_number,sku FROM pur_warehouse_results GROUP BY purchase_number,sku ORDER BY id ASC )  AS  warehouse","progress.purchase_number=warehouse.purchase_number  AND progress.sku=warehouse.sku ","LEFT");
//            $query_builder->join("warehouse_results AS warehouse", "progress.purchase_number=warehouse.purchase_number AND progress.sku=warehouse.sku");
            if( $params['is_overdue'] == 1) {
                $where = " 	( warehouse.arrival_date IS NULL AND progress.estimate_time < NOW( ) ) ) ";

                $query_builder->where(" (( warehouse.arrival_date IS NOT NULL AND warehouse.arrival_date > progress.estimate_time ) ")->or_where($where);
            }else{
                $query_builder->where(" (( warehouse.arrival_date IS NULL AND progress.estimate_time > NOW( ) )  ")->or_where("( warehouse.arrival_date IS NOT NULL AND warehouse.arrival_date < progress.estimate_time )) ");
            }
        }

        // 逾期天数

        if( isset($params['estimate_start_days']) && !empty($params['estimate_start_days'])){

            $havingString = '(progress.arrival_date IS NOT NULL AND ((unix_timestamp(progress.arrival_date) - UNIX_TIMESTAMP(progress.estimate_time))/86400) >='.$params['estimate_start_days']." OR ( progress.arrival_date IS NULL  AND ((unix_timestamp(now()) - UNIX_TIMESTAMP(progress.estimate_time))/86400)>=".$params['estimate_start_days']."))";
            $query_builder->where($havingString);

        }

        if( isset($params['estimate_end_days']) && !empty($params['estimate_end_days'])){

            $havingString = '(progress.arrival_date IS NOT NULL AND ((unix_timestamp(progress.arrival_date) - UNIX_TIMESTAMP(progress.estimate_time))/86400) <='.$params['estimate_end_days']." OR ( progress.arrival_date IS NULL AND ((unix_timestamp(now()) - UNIX_TIMESTAMP(progress.estimate_time))/86400)<=".$params['estimate_end_days']."))";
            $query_builder->where($havingString);
        }
        // 1688 异常
        if( isset($params['ali_order_status'])  && $params['ali_order_status']!='' ) {
            if( $params['ali_order_status'] == 2) {
                $params['ali_order_status'] =0;
            }
            $query_builder->where(" progress.ali_order_status",$params['ali_order_status']);
        }

        //缺货数量(新)
        if( (isset($params['lack_qty_start']) && is_numeric($params['lack_qty_start'])) || (isset($params['lack_qty_end']) && is_numeric($params['lack_qty_end'])))
        {
            $query_builder->join('think_lack_info tli','tli.sku = progress.sku', 'left');
            if(isset($params['lack_qty_start']) && $params['lack_qty_start'] != ''){
                $query_builder->where("tli.lack_sum >=",$params['lack_qty_start']);
            }
            if(isset($params['lack_qty_end']) && $params['lack_qty_end'] != ''){
                $query_builder->where("tli.lack_sum <=",$params['lack_qty_end']);
            }
        }


        // 如果用户传入异常类型
        if( isset($params['abnormal_type']) && !empty($params['abnormal_type']) ) {
            $query_builder->select('GROUP_CONCAT(DISTINCT abn.abnormal_type) AS abnormal_type');
            $query_builder->join("purchase_warehouse_abnormal AS abn",
                "progress.purchase_number = abn.pur_number AND (abn.sku=progress.sku OR abn.sku='') AND (abn.is_handler=0 OR abn.is_handler=2)",
                'LEFT');
            $abnormal_type = array_filter(explode(',', $params['abnormal_type']));
            $query_builder->where_in("abn.abnormal_type", $abnormal_type, false);
        }

        $query_builder->group_by('progress.id');

        $query_sum = clone $query_builder;
        $all_sku_sum = clone $query_builder;

//        echo $query_sum->get_compiled_select();exit;
        $query_builder->select("items.id as newitemsId,cancel.cancel_reason,demand.demand_number as dddemand_number,demand.suggest_demand,suggest.demand_name AS demand_types,suggest.is_merge,items.plan_arrive_time,orders.purchase_type_id as purchase_type,progress.buyer_id,orders.audit_time,suggest.demand_type_id AS demand_purchase_type_id,suggest.is_new,suggest.suggest_order_status,orders.purchase_order_status AS orders_status,suggest.left_stock,progress.*,progress.product_line_ch AS progress_product_line,line.product_line_id,line.linelist_cn_name AS product_line_ch,tproduct.supply_status AS apply_status,tproduct.product_status,tproduct.is_boutique,tproduct.days_sales_7 AS sevensale,CONCAT(progress.purchase_number,progress.sku) as purchase_num_sku");

        $limit = (isset($params['limit']) && !empty($params['limit']))? $params['limit']:20;
        $page = (isset($params['page']) && !empty($params['page']))? $params['page']:1;
        if( isset($params['limit'])) {
            $query_builder->limit($limit,($page-1)*$limit);
        }



        return array(
            "query_builder" =>$query_builder,
            "query_sum" => $query_sum,
            "limit" =>$limit,
            "page" => $page,
            "all_sku" => $all_sku_sum
        );

    }

    /**
     * 订单追踪统计(新)
     * @param:   $param  array  客户端传入参数
     * @author:luxu
     **/
    public function get_purchase_progress_total($params){

        $this->purchase_db->reset_query();
        $query_builder = $this->get_purchase_progress_query($params);
        $all_result = $query_builder['query_sum']->select('progress.id')->count_all_results();
        $limit = (isset($params['limit']) && !empty($params['limit']))? $params['limit']:20;
        $page = (isset($params['page']) && !empty($params['page']))? $params['page']:1;

        $all_skus = $query_builder['all_sku']->select(" COUNT( distinct progress.sku ) AS sku")->limit($limit,($page-1)*$limit)->get()->row_array();
       
        $all_skus = $all_skus['sku'];

        return ['limit' => $query_builder['limit'], 'page' => $query_builder['page'], 'all_skus' => ($all_skus),  'total_all' => $all_result];

    }

    /**
     * 订单追踪统计
     * @param:   $param  array  客户端传入参数
     * @author:luxu
     **/

    public function get_purchase_progress_sum( $params ) {
        $this->purchase_db->reset_query();
        $query_builder = $this->get_purchase_progress_query($params);
        $all_result = $query_builder['query_builder']->get()->num_rows();
        return $all_result;
    }

    /**
     * 订单追踪采购单更新时间
     * @param:   $param  $purchase_number   采购单号
     *                   $skus              SKU
     * @author:luxu
     **/
    public function get_purchase_order_audit_time($purchase_number) {

        $result= $this->purchase_db->from("purchase_order")->select("audit_time,purchase_number")->where_in("purchase_number",$purchase_number)->get()->result_array();
        $result_arr = [];
        if(!empty($result) ) {
            foreach( $result as $key=>$value) {

                $k = $value['purchase_number'];
                $result_arr[$k] = $value['audit_time'];
            }
        }

        return $result_arr;
    }

    /**
     * 时间差计算
     * luxu
     **/

    public function timediff($begin_time,$end_time)
    {
        if($begin_time < $end_time){
            $starttime = $begin_time;
            $endtime = $end_time;
        }else{
            $starttime = $end_time;
            $endtime = $begin_time;
        }
        //计算天数
        $timediff = $endtime-$starttime;
        $days = intval($timediff/86400);
        //计算小时数
        $remain = $timediff%86400;
        $hours = intval($remain/3600);
        //计算分钟数
        $remain = $remain%3600;
        $mins = intval($remain/60);
        //计算秒数
        $secs = $remain%60;
        $res = array("day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs);
        return $res;
    }

    public function get_purchase_total($params,$export_user=[])
    {
        $query_builder = $this->get_purchase_progress_query($params,False,$export_user);
        $all_result = $query_builder['query_sum']->select('progress.id')->count_all_results();
        return $all_result;
    }

    /**
     * 获取采购单状态
     * @param: $purchase_numbers   string   采购单号
     * @return array
     * @author:luxu
     **/
    public function get_purchase_order_status( $purchase_numbers)
    {

        if( empty($purchase_numbers))
        {
            return array();
        }

        return $this->purchase_db->from("purchase_order")->where_in("purchase_number",$purchase_numbers)->select('purchase_order_status,purchase_number')->get()->result_array();
    }

    /**
     * 订单追踪从MONGODB 中获取数据
     * @param : 从MONGODB 中获取的结果集合
     * @author:luxu
     **/
    public function get_purchase_mongdb_data($result)
    {

        if (!empty($result)) {

                // 获取采购单SKU 的快递号和入库数量
            $skus = array_unique(array_column($result, "sku"));
            $purchase_numbers = array_unique(array_column($result, "purchase_number"));
            $purchase_numbers_status = $this->get_purchase_order_status($purchase_numbers);
            if( !empty($purchase_numbers_status))
            {
                $purchase_numbers_status = array_column( $purchase_numbers_status,NULL,'purchase_number');
            }
            $demand_numbers = array_unique(array_column($result, "demand_number"));
            $query = $this->purchase_db->from("warehouse_results AS main ");
            $query->select("main.express_no,main.purchase_qty,main.arrival_qty");
            $wareRest = $query->where_in("main.sku", $skus)->where_in("main.purchase_number", $purchase_numbers)->get()->result_array();
            $wareRest = array_column($wareRest, NULL, "sku");

            /**
             * 获取采购单SKU 的采购员信息
             **/
            $sugges_buyers = $this->Purchase_order_progress_model->get_progress_suggest($demand_numbers, $skus, array('buyer_name', 'buyer_id', 'demand_number', 'sku'));
            //获取取消数量
            $query = $this->purchase_db->from("purchase_order_cancel_detail")->select(" sum(cancel_ctq) AS number,sku,purchase_number")->where_in("sku", $skus)->where_in("purchase_number", $purchase_numbers);
            $cancal_result = $query->where("is_push=1")->group_by("sku,purchase_number")->get()->result_array();
            $cancal = array();
            if (!empty($cancal_result)) {

                foreach ($cancal_result as $key => $value) {
                    $cancal[$value['sku'] . $value['purchase_number']] = $value['number'];
                }
            }

            // 审核时间
            // = $this->get_purchase_order_audit_time($purchase_numbers,$skus);
            // 获取仓库信息
            $warehouse_code = array_column($result, "warehouse_code");
            $warehouseResult = $this->Purchase_order_progress_model->get_warhouse($warehouse_code, array('warehouse_name', 'warehouse_code'), 'warehouse_code');
            $result_arr = [];

            $supplier_codes = array_column( $result,"supplier_code");

            $supplier_prvoince_query = $this->purchase_db->from("supplier as supp")->join("pur_region AS parent_region","parent_region.region_code = supp.ship_province AND parent_region.region_type=1")->where_in("supp.supplier_code",$supplier_codes);
            $supplier_prvoince_result = $supplier_prvoince_query->select("supp.supplier_code,parent_region.region_name")->get()->result_array();
            $provinces = array_column( $supplier_prvoince_result,NULL,"supplier_code");

            //获取缺货数量信息
            $this->load->model('product/Shortage_model');
            $lack_map = $this->Shortage_model->get_lack_info($skus);
            foreach ($result as $key => &$value) {
            //    $value =  get_object_vars($value);
                //缺货数量
                $value['lack_qty'] = $lack_map[$value['sku']]['think_lack_qty']??NULL;
                $product_line_ch = $this->product_line_model->get_product_top_line_data($value['progress_product_line']);
                $value['product_line_ch'] = isset($product_line_ch['linelist_cn_name'])?$product_line_ch['linelist_cn_name']:NULL;
                //需求状态 默认1,1.未完结,2.已完结,3.过期,4.作废
                $value['suggest_status_ch'] = NULL;
                $value['suggest_status_ch'] = getPurchaseStatus($value['suggest_order_status']);


                $value['create_time'] = $value['audit_time'];
                $value['provinces'] = isset($provinces[$value['supplier_code']]['region_name'])?$provinces[$value['supplier_code']]['region_name']:NULL;
                // 仓库信息
                $wareRestResult = $this->get_purchase_warehouse($value['purchase_number'],$value['sku']);

                if(!empty($wareRestResult)) {
                    $wareRestResult = array_values($wareRestResult);
                    $value['instock_date'] = $wareRestResult[0]['instock_date'];
                    $value['arrival_date'] =  $wareRestResult[0]['arrival_date'];
                }else {

                    $value['instock_date'] = NULL;
                    $value['arrival_date'] = NULL;
                }

                // 获取采购员
                $keys = $value['demand_number'] . "-" . $value['sku'];
                $value['buyer_name'] = (isset($sugges_buyers[$keys]) && !empty($sugges_buyers[$keys])) ? $sugges_buyers[$keys]['buyer_name'] : NULL;
                $value['buyer_id'] = (isset($sugges_buyers[$keys]) && !empty($sugges_buyers[$keys])) ? $sugges_buyers[$keys]['buyer_id'] : NULL;
                $paytime_result = $this->get_purchase_pay_type($value['purchase_number'], $value['source']);

                // 预计到货时间导入后在前端页面，默认=年月日时分秒。时分秒默认=12:00:00

                if (!empty($value['estimate_time'])) {
                    $estimates = explode(" ", $value['estimate_time']);
                    if (in_array("00:00:00", $estimates)) {

                        $value['estimate_time'] = str_replace("00:00:00", "12:00:00", $value['estimate_time']);
                    }
                }
                if (!empty($value['documentary_time'])) {
                    $progress_time = explode(" ", $value['documentary_time']);
                    if (in_array("00:00:00", $progress_time)) {

                        $value['documentary_time'] = str_replace("00:00:00", "12:00:00", $value['documentary_time']);
                    }
                }

                // 物流信息
                $value['logistics_info'] = $this->get_logistics_info($value['purchase_number'], $value['sku']);
                $value['application_time'] = isset($paytime_result['application_time']) ? $paytime_result['application_time'] : NULL;
                $value['payer_time'] = isset($paytime_result['payer_time']) ? $paytime_result['payer_time'] : NULL;
                $number = $this->get_progress_order_cancel_ctq($value['sku'], $value['purchase_number']);
                $num = $this->get_order_cancel_list($value['purchase_number'], $value['sku'],False);

                //异常类型
                if (!isset($value['abnormal_type'])) {
                    $value['abnormal_type'] = $this->_get_abnormal_type($value['purchase_number'], $value['sku']);
                }
                if (!empty($value['abnormal_type'])) {
                    $abnormal_type_arr = explode(',', $value['abnormal_type']);
                    $abnormal_type_cn = array();
                    foreach ($abnormal_type_arr as $item) {
                        $abnormal_type_cn[] = getWarehouseAbnormalType($item);
                    }
                    $value['abnormal_type_cn'] = implode(',', $abnormal_type_cn);
                } else {
                    $value['abnormal_type_cn'] = '';
                }
                //是否在途异常
                $value['on_way_abnormal_cn'] = getOnWayAbnormalStatus($value['on_way_abnormal']);

                //$arrival_qty = $this->get_instock_qty($value['purchase_number'],$value['sku']);
                $instock_qty = $this->get_instock_qty($value['purchase_number'], $value['sku']);
                $cancanl_num = isset($num[$value['sku'].$value['purchase_number']]) ? $num[$value['sku'].$value['purchase_number']] : 0;
                $lossNumber = $this->get_reportloss_data($value['purchase_number'],$value['sku'],$value['source']);
                $lossSuccNumber = $this->get_reportloss_success_data($value['purchase_number'],$value['sku'],$value['source']);
                $value['no_instock_date'] = $value['purchase_num'] - $number - $cancanl_num - $instock_qty['instock_qty']-$lossNumber['loss_amount'];
                // 10 取消0

                if (empty($value['instock_date']) || empty($value['arrival_date'])) {

                    $value['storage'] = NULL;
                } else {

                    //$value['storage'] = floor((strtotime($value['instock_date']) - strtotime($value['arrival_date'])) % 86400 / 3600);
                    $storage = $this->timediff(strtotime($value['instock_date']),strtotime($value['arrival_date']));
                    $value['storage'] = $storage['day']*24 + $storage['hour'];
                }
                $value['storageday'] = NULL;

                if( $value['estimate_time'] >= $value['arrival_date'] ) {

                    $value['storageday'] = NULL;
                }else {
                    //$value['storageday'] = floor((strtotime($value['arrival_date']) - strtotime($value['estimate_time'])) / 86400);
                    $storageday = $this->timediff(strtotime($value['arrival_date']),strtotime($value['estimate_time']));
                    $value['storageday'] = $storageday['day'];

                }
                if( empty($value['arrival_date']) ) {
                    if( !empty($value['estimate_time']) ) {
                        $estimagetime = strtotime($value['estimate_time']);
                        if( $estimagetime< time()) {
                            //$value['storageday'] = floor((time()-strtotime($value['estimate_time'])) / 86400);
                            $storageday = $this->timediff(time(),strtotime($value['estimate_time']));
                            $value['storageday'] = $storageday['day'];

                        }else{

                            $value['storageday'] = NULL;
                        }
                    }
                }

//                    // 如果用户传入是否逾期
//
//                    if( isset($params['is_overdue']) && !empty($params['is_overdue']) ) {
//
//                       // $query_builder->where_in( "progress.is_overdue",$params['is_overdue']);
//                        if( $params['is_overdue'] ==2  ) {
//
//                            if($value['storageday'] != 0 || $value['storageday'] != NULL){
//
//                                unset($result[$key]);
//                            }
//                        }
//
//                        if( $params['is_overdue'] ==1  ){
//
//                            if($value['storageday'] == 0 || $value['storageday'] == NULL){
//                                unset($result[$key]);
//                            }
//                        }
//                    }



                if (empty($value['payer_time']) || $value['payer_time'] == "0000-00-00 00:00:00") {

                    $value['payer_h'] = NULL;
                } else {
                    $hours = $this->timediff(strtotime($value['payer_time']),strtotime($value['application_time']));
                    $value['payer_h'] = $hours['day']*24 + $hours['hour'];
                }

                $instock_qty = $this->Purchase_order_progress_model->get_purchase_sku_instock_qty($value['purchase_number'], $value['sku']);
                $value['instock_qty'] = $instock_qty['instock_qty'];
                $keystring = $value['sku'] . $value['demand_number'];
                if (isset($cancal[$keystring])) {

                    $value['cancal_number'] = $cancal[$keystring];
                }
                $value['stock_owes'] = $value['left_stock'];


                if( $value['payer_time'] == "0000-00-00 00:00:00" ) {

                    $value['payer_time'] = NULL;
                }
                $value['warehourse'] = isset($warehouseResult[$value['warehouse_code']]) ? $warehouseResult[$value['warehouse_code']]['warehouse_name'] : NULL;
                $purchase_status = isset($purchase_numbers_status[$value['purchase_number']])?$purchase_numbers_status[$value['purchase_number']]['purchase_order_status']:NULL;
                $value['purchase_status_ch'] = getPurchaseStatus($purchase_status); //get_productStatus
                $value['product_status_ch'] = $this->get_productStatus($value['product_status']);
                switch ($value['source']) {

                    case 1:
                        $value['source_ch'] = "合同";
                        break;
                    case 2:
                        $value['source_ch'] = "网采";
                        break;
                    case 3:
                        $value['source_ch'] = "账期采购";
                        break;
                    default:
                        $value['source_ch'] = "未知";
                        break;
                }

                if ($value['stock_owes'] >= 1) {

                    $value['stock_owes_ch'] = "是";
                } else {
                    $value['stock_owes_ch'] = "否";
                }
                if ($value['ali_order_status'] == 0) {

                    $value['ali_order_status_ch'] = "否";
                } else {
                    $value['ali_order_status_ch'] = "是";
                }

            }
            $key_lists = [];
            if (!empty($role)) {
                $roles = json_decode($role['role'], TRUE);
                $keyss = $this->table_columns();
                foreach ($roles as $v) {
                    if (isset($keyss[$v])){
                        $key_table_l[$v] = $keyss[$v];
                    }
                }

                foreach ($key_table_l as $k=>$val) {
                    $key_info['key'] = $k;
                    $key_info['name'] = $val;
                    array_push($key_lists,$key_info);
                }

            } else {

                // $data = $results;
                //未编辑列表显示（默认30列）
                $key_table = $this->table_header();
                foreach ($key_table as $k => $v){
                    $key_info['key'] = $k;
                    $key_info['name'] = $v;
                    array_push($key_lists,$key_info);
                }
            }
        }

        $return_data = [
            'key' => isset($key_lists)?$key_lists:[],
            'value' => $result,
        ];
        return $return_data;
    }

    /**
     * 订单追踪接口
     * @param:   $param  array  客户端传入参数
     * @author:luxu
     **/
    public function get_purchase_progress( $params ) {
            $this->purchase_db->reset_query();
            $query_builder = $this->get_purchase_progress_query($params);

            if( isset($params['stock_owns']) && !empty($params['stock_owns']))
            {
                // 什序
                if( $params['stock_owns'] == 1)
                {
                    $result = $query_builder['query_builder']->order_by("suggest.left_stock", "ASC")
                        ->get()->result_array();
                }else{
                    $result = $query_builder['query_builder']->order_by("suggest.left_stock", "DESC")
                        ->get()->result_array();
                }
            }else {
                $result = $query_builder['query_builder']->order_by("progress.id", "DESC")
                    ->get()->result_array();
            }

            $page_skus = array_unique(array_column($result, "sku"));
            $role_name=get_user_role();//当前登录角色

            if (!empty($result)) {

//                // 获取采购单SKU 的快递号和入库数量
                $skus = array_unique(array_column($result, "sku"));
                $purchase_numbers = array_unique(array_column($result, "purchase_number"));
                $purchase_numbers_status = $this->get_purchase_order_status($purchase_numbers);
                if( !empty($purchase_numbers_status))
                {
                    $purchase_numbers_status = array_column( $purchase_numbers_status,NULL,'purchase_number');
                }
                $demand_numbers = array_unique(array_column($result, "demand_number"));
                $query = $this->purchase_db->from("warehouse_results AS main ");
                $query->select("main.express_no,main.purchase_qty,main.arrival_qty");
                $wareRest = $query->where_in("main.sku", $skus)->where_in("main.purchase_number", $purchase_numbers)->get()->result_array();
                $wareRest = array_column($wareRest, NULL, "sku");

                /**
                 * 获取采购单SKU 的采购员信息
                 **/
                $sugges_buyers = $this->Purchase_order_progress_model->get_progress_suggest($demand_numbers, $skus, array('buyer_name', 'buyer_id', 'demand_number', 'sku'));
                //获取取消数量
                $query = $this->purchase_db->from("purchase_order_cancel_detail")->select(" sum(cancel_ctq) AS number,sku,purchase_number")->where_in("sku", $skus)->where_in("purchase_number", $purchase_numbers);
                $cancal_result = $query->where("is_push=1")->group_by("sku,purchase_number")->get()->result_array();
                $cancal = array();
                if (!empty($cancal_result)) {

                    foreach ($cancal_result as $key => $value) {
                        $cancal[$value['sku'] . $value['purchase_number']] = $value['number'];
                    }
                }

                // 审核时间
                $audit_times = $this->get_purchase_order_audit_time($purchase_numbers,$skus);
                // 获取仓库信息
                $warehouse_code = array_column($result, "warehouse_code");
                $warehouseResult = $this->Purchase_order_progress_model->get_warhouse($warehouse_code, array('warehouse_name', 'warehouse_code'), 'warehouse_code');
                $result_arr = [];

                // 供应商发货省份

                $supplier_codes = array_column( $result,"supplier_code");
                $supplier_prvoince_query = $this->purchase_db->from("supplier as supp")->join("pur_region AS parent_region","parent_region.region_code = supp.ship_province AND parent_region.region_type=1")->where_in("supp.supplier_code",$supplier_codes);
                $supplier_prvoince_result = $supplier_prvoince_query->select("supp.supplier_code,parent_region.region_name")->get()->result_array();
                $provinces = array_column( $supplier_prvoince_result,NULL,"supplier_code");

            //获取缺货数量信息
            $this->load->model('product/Shortage_model');
            $lack_map = $this->Shortage_model->get_lack_info($skus);
            if(!empty($result)) {
                $buyerIds = array_unique(array_column($result, "buyer_id"));

                $buyerName = $this->User_group_model->getBuyerGroupMessage($buyerIds);
                $buyerName = array_column($buyerName, NULL, 'user_id');
            }else{

                $buyerName =[];
            }


                foreach ($result as $key => &$value) {
                    //缺货数量
                    $value['lack_qty'] = $lack_map[$value['sku']]['think_lack_qty']??NULL;
                    $product_line_ch = $this->product_line_model->get_product_top_line_data($value['progress_product_line']);
                    $value['product_line_ch'] = isset($product_line_ch['linelist_cn_name'])?$product_line_ch['linelist_cn_name']:NULL;
                    //需求状态 默认1,1.未完结,2.已完结,3.过期,4.作废
                    $value['suggest_status_ch'] = NULL;
                    $value['suggest_status_ch'] = getPurchaseStatus($value['suggest_order_status']);
                    $value['purchase_type_id'] = $value['purchase_type_id']?getPurchaseType($value['purchase_type_id']):'';
                    $value['demand_purchase_type_id'] = $value['demand_purchase_type_id']?getPurchaseType($value['demand_purchase_type_id']):'';

                    $value['provinces'] = isset($provinces[$value['supplier_code']]['region_name'])?$provinces[$value['supplier_code']]['region_name']:NULL;
                    // 审核时间
                    $value['create_time'] = $value['audit_time'];

                    // 仓库信息
                    $wareRestResult = $this->get_purchase_warehouse($value['purchase_number'],$value['sku']);

                    if(!empty($wareRestResult)) {
                        $wareRestResult = array_values($wareRestResult);
                        $value['instock_date'] = $wareRestResult[0]['instock_date'];
                        $value['arrival_date'] =  $wareRestResult[0]['arrival_date'];
                    }else {

                        $value['instock_date'] = NULL;
                        $value['arrival_date'] = NULL;
                    }

                    if( $value['is_new'] == 1){

                        $value['is_new_ch'] = "是";
                    }else{
                        $value['is_new_ch'] = "否";
                    }

                    if($value['is_merge'] == 0){
                        $value['is_merge'] = "正常";
                    }else{
                        $value['is_merge'] = "合单";
                    }


                    // 获取采购员
                    $keys = $value['demand_number'] . "-" . $value['sku'];
                    $value['buyer_name'] = (isset($sugges_buyers[$keys]) && !empty($sugges_buyers[$keys])) ? $sugges_buyers[$keys]['buyer_name'] : NULL;
                    $value['buyer_id'] = (isset($sugges_buyers[$keys]) && !empty($sugges_buyers[$keys])) ? $sugges_buyers[$keys]['buyer_id'] : NULL;
                    $paytime_result = $this->get_purchase_pay_type($value['purchase_number'], $value['source']);

                    // 预计到货时间导入后在前端页面，默认=年月日时分秒。时分秒默认=12:00:00

                    if( $value['plan_arrive_time'] == '0000-00-00 00:00:00' || empty($value['plan_arrive_time'])){

                        $value['estimate_time'] = date("Y-m-d H:i:s",time());
                    }else{
                        $value['estimate_time'] = $value['plan_arrive_time'];
                    }

                    if(!empty($value['cancel_reason'])) {
                        $value['cancel_reason_ch'] = getOrderCancelReason($value['cancel_reason']);
                    }else{
                        $value['cancel_reason_ch'] = '';
                    }
                    //newitemsId
                    $cancelTotal = $this->purchase_db->from("purchase_order_cancel_detail")->where("items_id",$value['newitemsId'])->count_all_results();
                    $value['cancel_total'] = $cancelTotal;

                    $remarkImages = $this->purchase_db->from("purchase_order_remark")->where("items_id",$value['newitemsId'])->select("images")
                        ->order_by("id DESC")->limit(5)
                        ->get()->result_array();
                    $temp_img = [];
                    foreach ($remarkImages as &$i_val){
                        $i_img = $i_val['images'];
                        $i_img_l = [];
                        $first = substr($i_img, 0, 1 );
                        $last = substr($i_img, strlen($i_img) - 1,1);
                        if($first == "[" && $last == "]"){
                            $i_img_l = json_decode($i_img, true);
                        }else{
                            $i_img_l = [$i_img];
                        }
                        if(count($i_img_l) > 0){
                            foreach ($i_img_l as $ii_v){
                                if(count($temp_img) < 5)$temp_img[] = $ii_v;
                            }
                        }
                    }
                    $value['remark_images'] = $temp_img;

//                    if( $value['estimate_time'] == '0000-00-00 00:00:00' || empty($value['estimate_time'])){
//
//                        $value['estimate_time'] = date("Y-m-d H:i:s",time());
//                    }
//                    if (!empty($value['estimate_time'])) {
//                        $estimates = explode(" ", $value['estimate_time']);
//                        if (in_array("00:00:00", $estimates)) {
//
//                            $value['estimate_time'] = str_replace("00:00:00", "12:00:00", $value['estimate_time']);
//                        }
//                    }

                    if (!empty($value['documentary_time'])) {
                        $progress_time = explode(" ", $value['documentary_time']);
                        if (in_array("00:00:00", $progress_time)) {

                            $value['documentary_time'] = str_replace("00:00:00", "12:00:00", $value['documentary_time']);
                        }
                    }

                    // 物流信息
                    $value['logistics_info'] = $this->get_logistics_info($value['purchase_number'], $value['sku']);
                    $value['application_time'] = isset($paytime_result['application_time']) ? $paytime_result['application_time'] : NULL;
                    $value['payer_time'] = isset($paytime_result['payer_time']) ? $paytime_result['payer_time'] : NULL;
                    $number = $this->get_progress_order_cancel_ctq($value['sku'], $value['purchase_number']);
                $num = $this->get_order_cancel_list($value['purchase_number'], $value['sku'],False);

                    //异常类型
                    if (!isset($value['abnormal_type'])) {
                        $value['abnormal_type'] = $this->_get_abnormal_type($value['purchase_number'], $value['sku']);
                    }
                    if (!empty($value['abnormal_type'])) {
                        $abnormal_type_arr = explode(',', $value['abnormal_type']);
                        $abnormal_type_cn = array();
                        foreach ($abnormal_type_arr as $item) {
                            $abnormal_type_cn[] = getWarehouseAbnormalType($item);
                        }
                        $value['abnormal_type_cn'] = implode(',', $abnormal_type_cn);
                    } else {
                        $value['abnormal_type_cn'] = '';
                    }

                    //是否在途异常
                    $value['on_way_abnormal_cn'] = getOnWayAbnormalStatus($value['on_way_abnormal']);

                    //$arrival_qty = $this->get_instock_qty($value['purchase_number'],$value['sku']);
                    $instock_qty = $this->get_instock_qty($value['purchase_number'], $value['sku']);
                $cancanl_num = isset($num[$value['sku'].$value['purchase_number']]) ? $num[$value['sku'].$value['purchase_number']] : 0;
                    $lossNumber = $this->get_reportloss_data($value['purchase_number'],$value['sku'],$value['source']);
                    $lossSuccNumber = $this->get_reportloss_success_data($value['purchase_number'],$value['sku'],$value['source']);
                    $value['no_instock_date'] = $value['purchase_num'] - $number - $cancanl_num - $instock_qty['instock_qty']-$lossNumber['loss_amount'];
                    // 10 取消0

                    if (empty($value['instock_date']) || empty($value['arrival_date'])) {

                        $value['storage'] = NULL;
                    } else {

                        //$value['storage'] = floor((strtotime($value['instock_date']) - strtotime($value['arrival_date'])) % 86400 / 3600);
						$storage = $this->timediff(strtotime($value['instock_date']),strtotime($value['arrival_date']));
                        $value['storage'] = $storage['day']*24 + $storage['hour'];
                    }
                    $value['storageday'] = NULL;

                    if( $value['estimate_time'] >= $value['arrival_date'] ) {

                        $value['storageday'] = NULL;
                    }else {
                        //$value['storageday'] = floor((strtotime($value['arrival_date']) - strtotime($value['estimate_time'])) / 86400);
                        $storageday = $this->timediff(strtotime($value['arrival_date']),strtotime($value['estimate_time']));
                        $value['storageday'] = $storageday['day'];

                    }
                    if( empty($value['arrival_date']) ) {
                        if( !empty($value['estimate_time']) ) {
                            $estimagetime = strtotime($value['estimate_time']);
                            if( $estimagetime< time()) {
                                //$value['storageday'] = floor((time()-strtotime($value['estimate_time'])) / 86400);
                                $storageday = $this->timediff(time(),strtotime($value['estimate_time']));
                                $value['storageday'] = $storageday['day'];

                            }else{

                                $value['storageday'] = NULL;
                            }
                        }
                    }
                    if (empty($value['payer_time']) || $value['payer_time'] == "0000-00-00 00:00:00") {

                        $value['payer_h'] = NULL;
                    } else {
                        $hours = $this->timediff(strtotime($value['payer_time']),strtotime($value['application_time']));
                        $value['payer_h'] = $hours['day']*24 + $hours['hour'];
                    }

                    $instock_qty = $this->Purchase_order_progress_model->get_purchase_sku_instock_qty($value['purchase_number'], $value['sku']);
                    $value['instock_qty'] = $instock_qty['instock_qty'];
                    $keystring = $value['sku'] . $value['demand_number'];
                    if (isset($cancal[$keystring])) {

                        $value['cancal_number'] = $cancal[$keystring];
                    }
                    $value['stock_owes'] = $value['left_stock'];


                    if( $value['payer_time'] == "0000-00-00 00:00:00" ) {

                        $value['payer_time'] = NULL;
                    }
                    $value['warehourse'] = isset($warehouseResult[$value['warehouse_code']]) ? $warehouseResult[$value['warehouse_code']]['warehouse_name'] : NULL;
                    $purchase_status = isset($purchase_numbers_status[$value['purchase_number']])?$purchase_numbers_status[$value['purchase_number']]['purchase_order_status']:NULL;
                    $value['purchase_status_ch'] = getPurchaseStatus($purchase_status); //get_productStatus
                    $value['product_status_ch'] = $this->get_productStatus($value['product_status']);
                    // $value['product_status_ch'] = getPurchaseStatus($value['product_status']);
                    switch ($value['source']) {

                        case 1:
                            $value['source_ch'] = "合同";
                            break;
                        case 2:
                            $value['source_ch'] = "网采";
                            break;
                        case 3:
                            $value['source_ch'] = "账期采购";
                            break;
                        default:
                            $value['source_ch'] = "未知";
                            break;
                    }

                    if ($value['stock_owes'] >= 1) {

                        $value['stock_owes_ch'] = "是";
                    } else {
                        $value['stock_owes_ch'] = "否";
                    }
                    if ($value['ali_order_status'] == 0) {

                        $value['ali_order_status_ch'] = "否";
                    } else {
                        $value['ali_order_status_ch'] = "是";
                    }
                $value['groupName']                = isset($buyerName[$value['buyer_id']])?$buyerName[$value['buyer_id']]['group_name']:'';

                    $result_value[] = array();
                    $result_value['id'] = $value['id'];
                }
            }

        $data_role= getRolexiao();
        $res_xiao=array_intersect($role_name, $data_role);
        if($res_xiao){
            foreach ($result as $key=>&$row) {
                $row['supplier_name']="***";
            }
        }
        return array(

                "list" =>$result,
                "page" => ['page_sku' => count($page_skus),'page_total' => count($result)]
            );
    }

    /**
     * 获取物流信息
     * @author Manson
     * @Modified by Justin
     * @param $purchase_number
     * @param $sku
     * @return array
     */
    public function get_logistics_info($purchase_number,$sku)
    {
        $result = $this->purchase_db->from("purchase_logistics_info a")->select("a.id,a.status AS track_status,a.is_manually,a.cargo_company_id,a.express_no,b.pai_number,b.source,a.carrier_code,a.batch_no")
            ->join('pur_purchase_progress b','a.purchase_number = b.purchase_number AND a.sku = b.sku','left')
            ->where('a.purchase_number',$purchase_number)
            ->where('a.sku',$sku)
            ->get()->result_array();
        $logistics_info = [];
        if(!empty($result)){
            foreach ($result as $item) {
                if ($item['source'] == 2) {//1688网采单
                    $status = 1;
                } else {
                    $status = 0;
                }

                $logistics_info[] = [
                    'cargo_company_id' => $item['cargo_company_id'],
                    'carrier_code' => $item['carrier_code'],
                    'express_no'       => $item['express_no'],
                    'logistics_status_cn' => !empty($item['track_status']) ? getTrackStatus($item['track_status']) : '',
                    'logistics_status' => $item['track_status'],
                    'status'           => $status,
                    'is_manually'      => $item['is_manually'],
                    'batch_no' => $item['batch_no']
                ];

            }
        }
        return $logistics_info;
    }


    public function update_all($data) {

        return $this->purchase_db->update_batch('purchase_progress',$data,'demand_number');
    }

    public function update_history(){

        $status = array(PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
            PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION,
            PURCHASE_ORDER_STATUS_ALL_ARRIVED,
            PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
            PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
            PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT
        );
        $result = $this->purchase_db->from("purchase_order")->where_in("purchase_order_status",$status)->where("audit_time>='2019-01-01'")->where("audit_time<'2019-08-03'")->limit(5000,0)->get()->result_array();
        if( !empty($result) ) {
            $i=0;
            foreach($result as $key=>$value) {
                ++$i;
                $this->purchase_track($value['purchase_number'],$value['purchase_order_status']);
                }

            echo $i;
        }
    }

    /**
     * function:获取订单号是否存在
     * @param: $purchase_number    string           采购单号
     *         $purchase_status    array|string     采购单状态
     * @return 采购单号和采购员
     **/
    public function get_purchase_data($purchase_number,$purchase_status) {
        $query = $this->purchase_db->from("purchase_order")->where_in("purchase_number",$purchase_number);
        if( is_array($purchase_status) ) {
            $query->where_in("purchase_order_status",$purchase_status);
        }else{

            $query->where_in("purchase_order_status",$purchase_status);
        }

        $result = $query->select("buyer_id,purchase_number")->get()->result_array();
        return $result;

    }

    /**
     * function:更新采购单表和备货单表采购员
     * @param: $purchase_number    string           采购单号
     *         $buyer_id           int              采购员ID
     *         $buyer_name         string           采购员姓名
     * @return  成功返回True  失败返回FALSE
     **/
    public function update_purchase_buery( $purchase_number,$buyer_id,$buyer_name ) {

        $demand_number = $this->purchase_db->from("purchase_suggest_map")->select("demand_number")->where("purchase_number",$purchase_number)->get()->result_array();
        if( !empty($demand_number) ) {

            $demand_numbers = array_column( $demand_number,"demand_number");
            $this->purchase_db->trans_begin();

            $update = array(

                "buyer_name" => $buyer_name,
                "buyer_id"   => $buyer_id,

            );

            $log = [
                'record_number' => $purchase_number,
                'record_type' => 'PURCHASE_ORDER',
                'content' => '修改采购单号的采购员-交接',
                'content_detail' => '采购单号' . $purchase_number . "的采购员修改为" . $buyer_name,
            ];
            $this->Reject_note_model->get_insert_log($log);
            $demand_result = $this->purchase_db->where_in("demand_number",$demand_numbers)->update("pur_purchase_suggest",$update);
            $purchase_result = $this->purchase_db->where_in("purchase_number",$purchase_number)->update("pur_purchase_order",$update);
            if( $demand_result && $purchase_result ) {

                $this->purchase_db->trans_commit();
                return True;
            }else{

                $this->purchase_db->trans_rollback();
                return False;
            }

        }

        return False;
    }

    /**
     * 临时供应商下单次数
     * @param $supplier_code
     * @param int $source
     * @return array
     */
    public function temporary_supplier_order_number($supplier_code,$source=1){
        $reject_info = [];
        $sql = "select COUNT(a.id) order_number from pur_purchase_order a 
            join pur_supplier b on a.supplier_code=b.supplier_code
            where b.supplier_source=3 and a.purchase_order_status 
            in (2,6,7,15,10,9,11) and a.supplier_code='".$supplier_code."' and a.audit_time>='2019-08-12';";
        $number = $this->purchase_db->query($sql)->row_array();

        //查询出下单金额
        $money_calculate = $this->purchase_db->select('sum(a.cooperation_amount) as cooperation_amount')
            ->from('supplier_cooperation_amount a')
            ->join("pur_supplier b", "a.supplier_code=b.supplier_code and b.supplier_source=3", "inner")
            ->where('a.supplier_code',$supplier_code)
            ->get()
            ->row_array();

        if (isset($number['order_number']) && $number['order_number']>=20){
            $reject_info[] = '临时供应商，最多允许下20次单,请转常';
        }

        if (isset($money_calculate['cooperation_amount']) && $money_calculate['cooperation_amount'] > 10000) {
            $reject_info[] = '入库金额已达1万，请转常规';
        }
        return $reject_info;
    }

    /**
     * 获取SKU 信息
     * param: SKU    string   商品SKU
     **/
    public function get_sku_message( $sku)
    {
        if( empty($sku) )
        {
            return NULL;
        }
        # 数据来源(victor)
        # days_sales_3,days_sales_7,days_sales_15,days_sales_30
        # JAVA -> BI API -> PMS
        # http://python3.yibainetwork.com:8801/bi/dwh/platform_sale_volume_skus/
        $product_message = $this->purchase_db->from("product")->where("sku",$sku)->select("days_sales_3,days_sales_7,days_sales_15,days_sales_30")->get()->row_array();
        if( !empty($product_message) )
        {
            // 获取SKU 仓库和库存信息
            $stocks = $this->purchase_db->from("warehouse as warehouse")->JOIN("stock AS stock","stock.warehouse_code=warehouse.warehouse_code","LEFT")->select("SUM(stock.on_way_stock) AS on_way_stock ,SUM(stock.available_stock) AS available_stock,warehouse.warehouse_name")->where("stock.sku",$sku)->group_by("warehouse.warehouse_name")->get()->result_array();
            $product_message['stocks_warehouse'] = $stocks;
            return $product_message;


        }else{
            // 如果查询不到，说明数据不存在SKU 就返回为空
            return NULL;

        }
    }





    /**
     * 采购金额审核权限区间
     * @param: $start     string     开始职位
     *         $end       string     结束职位
     **/

    public function get_account_price( $start=NULL,$end=NULL )
    {

        if( $end != NULL && $end == 'executive_director'){

            $end = "director";
        }

        if( $end != NULL && $end == 'purchasing_manager'){
            $end = "manager";
        }

        if( $end != NULL && $end == "supplier_director"){

            $end = "majordomo";
        }

        if( $start != NULL && $start == 'executive_director'){

            $start = "director";
        }

        if( $start != NULL && $start == 'purchasing_manager'){
            $start = "manager";
        }

        if( $start != NULL && $start == "supplier_director"){

            $start = "majordomo";
        }

        //fba,国内仓
        $query = $this->purchase_db->from("audit_amount")->where_in('id',[2,5]);

        if( NULL != $start)
        {
            $query->select($start."_start AS start");
        }
        if( NULL != $end && $end == 'majordomo')
        {
            $query->select($end." AS end");
        }else if( NULL !=$end ){
            $query->select($end."_end AS end");
        }

        $query->select("id");
        $result = $query->get()->result_array();
        return array_column($result,null,'id');


    }

    /**
     * 验证采购单是否有除了驳回的记录
     * @author jeff
     * @param string $purchase_number 采购单编号
     * @return boolean
     */
    public function check_is_cancel_status($purchase_number){
        $cancel_info=$this->purchase_db->select('b.audit_status')->from('purchase_order_cancel_detail a')
            ->join('purchase_order_cancel b','a.cancel_id=b.id','right')
            ->where(['a.purchase_number'=>$purchase_number])
            ->where_in('b.audit_status',[CANCEL_AUDIT_STATUS_CG,CANCEL_AUDIT_STATUS_CF,CANCEL_AUDIT_STATUS_SCJT,
                CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC])
            ->get()->result_array();
        if($cancel_info){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取
     * @param array $pur_nums
     * @return array|string
     */
    public function get_pay_category_requisition_number_list(array $pur_nums){
        if (empty($pur_nums)) {
            return [];
        }
        $requisition_number = $this->purchase_db
            ->select('requisition_number')
            ->where_in('purchase_number', $pur_nums)
            ->get('purchase_order_pay_detail')
            ->result_array();
        $requisition_number_list = array_column($requisition_number,'requisition_number');

        if(!empty($requisition_number_list)){
            $chunk_number = 0;
            if(count($requisition_number_list) > 2000)
            {
                $check_requistion_number_list = array_chunk( $requisition_number_list,1200,True);
                $chunk_number = ceil(count($requisition_number_list)/1200);
            }
            $order_pay_detail_query = $this->purchase_db
                ->select('requisition_number')
                ->where('pay_category',4);
            if($chunk_number > 0 && !empty($check_requistion_number_list)) {
                for ($i = 0; $i < $chunk_number; ++$i) {
                    $order_pay_detail_query->where_in('requisition_number', $check_requistion_number_list[$i]);
                }

            }else{
                $order_pay_detail_query->where_in('requisition_number',$requisition_number_list);
            }

            $order_pay_detail = $order_pay_detail_query->get('purchase_order_pay')->result_array();


//
//
        }
        if (empty($order_pay_detail)) {
            return '';
        }
        $data = implode(',', array_column($order_pay_detail,'requisition_number'));
        return $data;
    }

    /**
     * 根据采购订单号查询采购订单信息
     */
    public function get_purchase_order_data($purchase_order,$select='*'){
        $order_data  = $this->purchase_db
            ->select($select)
            ->where_in('purchase_number',$purchase_order )
            ->get($this->table_name)
            ->result_array();
        return $order_data;
    }

    /**
     * 根据PO或者PO+sku获取异常类型
     * @param $purchase_number
     * @param $sku
     * @return string
     */
    private function _get_abnormal_type($purchase_number, $sku)
    {
        if (empty($purchase_number) OR (empty($purchase_number) && empty($sku))) {
            return null;
        } else {
            $this->purchase_db->select('GROUP_CONCAT(DISTINCT abnormal_type) AS abnormal_type');
            $this->purchase_db->where('pur_number', $purchase_number);
            if (!empty($sku)) {
                $this->purchase_db->group_start();
                $this->purchase_db->where('sku', $sku);
                $this->purchase_db->or_where('sku', '');
                $this->purchase_db->group_end();
            }else{
                $this->purchase_db->where('sku', '');
            }
            $this->purchase_db->where('is_handler <>', 1,false);
            $this->purchase_db->group_by('pur_number');
            $res = $this->purchase_db->get('purchase_warehouse_abnormal')->row_array();
            return $res['abnormal_type'];
        }
    }

    /*
   * 无需付款操作
   * @params  string $purchase_number 采购单号,$no_payment_type int 无需付款类型,$notes string 无需付款备注
   * @return array
   */

    public function no_payment_opr($purchase_number,$no_payment_type,$note){
        $this->load->model('finance/purchase_order_pay_model');
        $return = ['code' => false, 'msg' => '', 'data' => ''];
        $purchase_order = $this->get_one($purchase_number, false);

        if (empty($purchase_order)) {
            $return['msg'] = $purchase_number.'采购单不存在';
            return $return;
        }

        if (!in_array($purchase_order['source'],[1,2])) {
            $return['msg'] = $purchase_number.'采购单来源异常';
            return $return;

        }


       if (!in_array($purchase_order['pay_status'],[PAY_UNPAID_STATUS,PAY_NONEED_STATUS,PAY_SOA_REJECT,PAY_MANAGER_REJECT,PAY_FINANCE_REJECT,PAY_WAITING_MANAGER_REJECT,PAY_REJECT_SUPERVISOR,PAY_REJECT_MANAGER,PAY_REJECT_SUPPLY,PAY_GENERAL_MANAGER_REJECT])) {
           $return['msg']='采购单号：' . $purchase_number . '只有付款状态=未申请付款、驳回才可点击';
           return $return;

       }
        if ($purchase_order['source'] == 2) {
            //如果是网菜单
      /*      if (!in_array($purchase_order['purchase_order_status'],
                [
                    PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                    PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                    PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                    PURCHASE_ORDER_STATUS_ALL_ARRIVED
                ])) {
                $return['msg'] = '采购单号：' . $purchase_number . ' 请选择【等待到货/部分到货等待剩余/部分到货不等待剩余/全到货】状态的采购单进行无需付款操作';
                return $return;

            }*/
            if ($purchase_order['pay_status']==PAY_NONEED_STATUS) {//已设置无需付款
                $return['code'] = true;
                return $return;

            }




            $have_paid_data = $this->purchase_order_pay_model->get_pay_total_by_compact_number($purchase_number);
            if($have_paid_data and $have_paid_data['pay_price'] > 0){
                $return['msg']='采购单号：' . $purchase_number . ' 已经付过款了';
                return $return;

            }


        /*    if ($purchase_order['is_ali_abnormal'] == 1) {
                $return['msg']='采购单号：' . $purchase_number . ' 1688异常状态不能申请无需付款';
                return $return;
            }*/

           $result = $this->purchase_db->update($this->table_name,['pay_status'=>PAY_NONEED_STATUS],['purchase_number' => $purchase_number]);//无需付款

            if ($result) {
                $insert =['user_id'=>getActiveUserId(),'reject_remark'=>$note,'reject_type_id'=>$no_payment_type,'link_id'=>$purchase_order['id'],'link_code'=>$purchase_number,'create_time'=>date('Y-m-d H:i:s')];
                $this->purchase_db->insert('reject_note', $insert);

                operatorLogInsert(
                    ['id'      => $purchase_number,
                        'type'    => 'purchase_order',
                        'content' => '采购单请款状态',
                        'detail'  => '更新采购单付款状态为【无需付款】'
                    ]);
                $return['code'] = true;

            } else {
                $return['msg']='采购单号：' . $purchase_number . ' 付款状态更新失败';

                return $return;

            }
        }elseif($purchase_order['source'] == 1){//合同单
            $this->load->model('compact/compact_model');

            //获取合同号
            $compact_info = $this->purchase_db->select('compact_number')->where('purchase_number',$purchase_number)->where('bind',1)->get('purchase_compact_items')->row_array();

           if (!empty($compact_info)){//已生成合同单
               $compact_number = $compact_info['compact_number'];
               $compact = $this->compact_model->get_compact_one($compact_number);


           }


          /*  $result = $this->purchase_order_pay_model->verify_order_status_enable_pay($compact_number);
            if($result !== true){  $return['msg']='合同号：'.$compact_number.' 合同存在未完结的请款单['.$result.']';return $return;}*/

        if (!empty($compact['items_list'])) {
            $result = $this->set_compact_order_no_pay($compact['items_list'],$no_payment_type,$note);
        } else {
            $result = $this->purchase_db->update($this->table_name,['pay_status'=>PAY_NONEED_STATUS],['purchase_number' => $purchase_number]);//无需付款
            $insert =['user_id'=>getActiveUserId(),'reject_remark'=>$note,'reject_type_id'=>$no_payment_type,'link_id'=>$purchase_order['id'],'link_code'=>$purchase_number,'create_time'=>date('Y-m-d H:i:s')];
            $this->purchase_db->insert('reject_note', $insert);

            operatorLogInsert(
                ['id'      => $purchase_number,
                    'type'    => 'purchase_order',
                    'content' => '采购单请款状态',
                    'detail'  => '更新采购单付款状态为【无需付款】'
                ]);

        }

            if (!$result) {
                $return['msg']='采购单号：' . $purchase_number . ' 付款状态更新失败';
                return $return;

            } else {
                $return['code'] = true;
            }


        }

        // 采购单状态变更需要刷新冲销汇总
        $this->load->library('Rabbitmq');
        $mq = new Rabbitmq();//创建消息队列对象
        $mq->setQueueName('STATEMENT_CHANGE_PAY_STATUS_REFRESH');//设置参数
        $mq->setExchangeName('STATEMENT_CHANGE_PAY_STATUS');//构造存入数据
        $mq->setRouteKey('SO_REFRESH_FOR_003');
        $mq->setType(AMQP_EX_TYPE_DIRECT);
        $mq->sendMessage(['purchase_number' => $purchase_number,'add_time' => time()]);// 保持格式一致

        return $return;






    }
/*
 * 将合同单下面采购单全部设置为无需付款
 */
   public function set_compact_order_no_pay($items_list,$no_payment_type,$note) {
       if (is_array($items_list)&&count($items_list)>0) {
           $this->load->library('Rabbitmq');
           $mq = new Rabbitmq();//创建消息队列对象
           $mq->setQueueName('STATEMENT_CHANGE_PAY_STATUS_REFRESH');//设置参数
           $mq->setExchangeName('STATEMENT_CHANGE_PAY_STATUS');//构造存入数据
           $mq->setRouteKey('SO_REFRESH_FOR_003');
           $mq->setType(AMQP_EX_TYPE_DIRECT);
           $mq->sendMessage(['purchase_number' => array_column($items_list,'purchase_number'),'add_time' => time()]);// 二维数组，保持格式一致

           foreach ($items_list as $item) {
               $purchase_number = $item['purchase_number'];
               $compact_number = $item['compact_number'];
               $purchase_order = $this->get_one($purchase_number, false);
               if (empty($purchase_order)) {
                   throw new Exception($purchase_number.':'.'采购单号不存在');

               }
               if ($purchase_order['pay_status']==PAY_NONEED_STATUS) {//已设置无需付款
                 continue;

               }

              if (!in_array($purchase_order['pay_status'],[PAY_UNPAID_STATUS,PAY_NONEED_STATUS,PAY_SOA_REJECT,PAY_MANAGER_REJECT,PAY_FINANCE_REJECT,PAY_WAITING_MANAGER_REJECT,PAY_REJECT_SUPERVISOR,PAY_REJECT_MANAGER,PAY_REJECT_SUPPLY,PAY_GENERAL_MANAGER_REJECT])) {
                   throw new Exception('合同单号：' . $compact_number . '中采购单只有付款状态=未申请付款、驳回才可点击');
               }

               $result = $this->purchase_db->update($this->table_name,['pay_status'=>PAY_NONEED_STATUS],['purchase_number' => $purchase_number]);//无需付款

               if ($result) {
                   $insert =['user_id'=>getActiveUserId(),'reject_remark'=>$note,'reject_type_id'=>$no_payment_type,'link_id'=>$purchase_order['id'],'link_code'=>$purchase_number,'create_time'=>date('Y-m-d H:i:s')];
                   $this->purchase_db->insert('reject_note', $insert);

                   operatorLogInsert(
                       ['id'      => $purchase_number,
                           'type'    => 'purchase_order',
                           'content' => '采购单请款状态',
                           'detail'  => '更新采购单付款状态为【无需付款】'
                       ]);

               } else {
                   return false;

               }


           }

       }
       return true;

    }



    /**
     * 初始化临时供应商下单数目
     */
    public function set_temporary_supplier_order_number($supplier_code){
        $sql = "select COUNT(a.id) order_number from pur_purchase_order a 
            join pur_supplier b on a.supplier_code=b.supplier_code
            where b.supplier_source=3 and a.purchase_order_status 
            in (2,6,7,15,10,9,11) and a.supplier_code='".$supplier_code."' and a.audit_time>='2019-08-12';";
        $number = $this->purchase_db->query($sql)->row_array();

        if ($number['order_number']>0) {
            $re  = $this->purchase_db->where('supplier_code', $supplier_code)->update('supplier' ,array('order_num'=>$number['order_number']));
            return $number['order_number'];

        }
        return 0;

    }
    /*
     * 海外仓等待到货写入标签列表
     * @ $purchase_number  string 采购单号
     */
    public function oversea_order_label($purchase_number)
    {

        //以备货单为维度推送计划系统
        $order_info = $this->get_one($purchase_number,false);
        if ($order_info['purchase_type_id']!=2) return ;//非海外仓订单

        $this->load->model('supplier_joint_model');
        $unvalid_supplier = $this->supplier_joint_model->isValidSupplier(array($purchase_number));
        $unvalid_supplier = ($unvalid_supplier==true?1:2);//是否开启门户系统

        $purchase_items=$this->purchase_db->select(
            'sp.demand_number,
            od.purchase_number,
            s.destination_warehouse,
               oi.sku,
                oi.confirm_amount,
                oi.purchase_unit_price,
                od.purchase_order_status,
                od.plan_product_arrive_time,
                od.shipment_type,od.supplier_code,od.supplier_name,od.shipment_type'
             )
            ->from('purchase_order_items oi')
            ->join('purchase_suggest_map sp','sp.sku=oi.sku and sp.purchase_number=oi.purchase_number','left')
            ->join('purchase_suggest s','s.demand_number=sp.demand_number','left')
            ->join('purchase_order od','od.purchase_number=oi.purchase_number','left')
            ->where('oi.purchase_number',$purchase_number)
            ->get()->result_array();


        if (!empty($purchase_items)) {
            foreach ($purchase_items as $item) {
                $demand_info = $this->purchase_db->select('*')->where('demand_number',$item['demand_number'])->get('purchase_label_info')->row_array();
                if (!empty($demand_info)) continue;


                $insert = [
                    'purchase_number'=>$item['purchase_number'],
                    'sku'=>$item['sku'],
                    'demand_number'=>$item['demand_number'],
                    'supplier_code'=>$item['supplier_code'],
                    'supplier_name'=>$item['supplier_name'],
                    'order_time'=>date('Y-m-d H:i:s'),
                    'destination_warehouse'=>$item['destination_warehouse'],
                    'new_des_warehouse'=>$item['destination_warehouse'],
                    'enable'=>$unvalid_supplier,
                    'shipment_type'=>$item['shipment_type']

                ];
                $re = $this->purchase_db->insert('purchase_label_info', $insert);

            }
        }

    }


    /**
     * 订单追踪接口
     * @param:   $param  array  客户端传入参数
     * @author:luxu
     **/
    public function export_purchase_progress( $params,$export_user=[] ) {
        $this->purchase_db->reset_query();
        $query_builder = $this->get_purchase_progress_query($params,False,$export_user);
        if( isset($params['stock_owns']) && !empty($params['stock_owns']))
        {
            // 什序
            if( $params['stock_owns'] == 1)
            {
                $result = $query_builder['query_builder']->order_by("suggest.left_stock", "ASC")
                    ->get()->result_array();
            }else{
                $result = $query_builder['query_builder']->order_by("suggest.left_stock", "DESC")
                    ->get()->result_array();
            }
        }else {
            $result = $query_builder['query_builder']->order_by("progress.id", "DESC")
                ->get()->result_array();
        }

        $role_name=get_user_role();//当前登录角色
        if(isset($export_user['user_id'])){
            $role_name = $export_user['role_name'];
        }
        if (!empty($result)) {

//                // 获取采购单SKU 的快递号和入库数量
            $skus = array_unique(array_column($result, "sku"));
            $purchase_numbers = array_unique(array_column($result, "purchase_number"));
            $purchase_numbers_status = $this->get_purchase_order_status($purchase_numbers);
            if( !empty($purchase_numbers_status))
            {
                $purchase_numbers_status = array_column( $purchase_numbers_status,NULL,'purchase_number');
            }
            $demand_numbers = array_unique(array_column($result, "demand_number"));
            $query = $this->purchase_db->from("warehouse_results AS main ");
            $query->select("main.express_no,main.purchase_qty,main.arrival_qty");
            $wareRest = $query->where_in("main.sku", $skus)->where_in("main.purchase_number", $purchase_numbers)->get()->result_array();
            $wareRest = array_column($wareRest, NULL, "sku");

            /**
             * 获取采购单SKU 的采购员信息
             **/
            $sugges_buyers = $this->Purchase_order_progress_model->get_progress_suggest($demand_numbers, $skus, array('buyer_name', 'buyer_id', 'demand_number', 'sku'));
            //获取取消数量
            $query = $this->purchase_db->from("purchase_order_cancel_detail")->select(" sum(cancel_ctq) AS number,sku,purchase_number")->where_in("sku", $skus)->where_in("purchase_number", $purchase_numbers);
            $cancal_result = $query->where("is_push=1")->group_by("sku,purchase_number")->get()->result_array();
            $cancal = array();
            if (!empty($cancal_result)) {

                foreach ($cancal_result as $key => $value) {
                    $cancal[$value['sku'] . $value['purchase_number']] = $value['number'];
                }
            }

            // 审核时间
            $audit_times = $this->get_purchase_order_audit_time($purchase_numbers,$skus);
            // 获取仓库信息
            $warehouse_code = array_column($result, "warehouse_code");
            $warehouseResult = $this->Purchase_order_progress_model->get_warhouse($warehouse_code, array('warehouse_name', 'warehouse_code'), 'warehouse_code');
            $result_arr = [];

            //获取缺货数量信息
            $this->load->model('product/Shortage_model');
            $lack_map = $this->Shortage_model->get_lack_info($skus);

            // 供应商发货省份

            $supplier_codes = array_column( $result,"supplier_code");

            $supplier_prvoince_query = $this->purchase_db->from("supplier as supp")->join("pur_region AS parent_region","parent_region.region_code = supp.ship_province AND parent_region.region_type=1")->where_in("supp.supplier_code",$supplier_codes);
            $supplier_prvoince_result = $supplier_prvoince_query->select("supp.supplier_code,parent_region.region_name")->get()->result_array();
            $provinces = array_column( $supplier_prvoince_result,NULL,"supplier_code");


            if(!empty($result)) {
                $buyerIds = array_unique(array_column($result, "buyer_id"));

                $buyerName = $this->User_group_model->getBuyerGroupMessage($buyerIds);
                $buyerName = array_column($buyerName, NULL, 'user_id');
            }else{

                $buyerName =[];
            }


            foreach ($result as $key => &$value) {
                //缺货数量
                $value['lack_qty'] = $lack_map[$value['sku']]['think_lack_qty']??NULL;
                $product_line_ch = $this->product_line_model->get_product_top_line_data($value['progress_product_line']);
                $value['product_line_ch'] = isset($product_line_ch['linelist_cn_name'])?$product_line_ch['linelist_cn_name']:NULL;
                //需求状态 默认1,1.未完结,2.已完结,3.过期,4.作废
                $value['suggest_status_ch'] = NULL;
                $value['suggest_status_ch'] = getPurchaseStatus($value['suggest_order_status']);

                $value['provinces'] = isset($provinces[$value['supplier_code']]['region_name'])?$provinces[$value['supplier_code']]['region_name']:NULL;
                $value['create_time'] = isset($audit_times[$value['purchase_number']])?$audit_times[$value['purchase_number']]:NULL;

                // 仓库信息
                $wareRestResult = $this->get_purchase_warehouse($value['purchase_number'],$value['sku']);

                if(!empty($wareRestResult)) {
                    $wareRestResult = array_values($wareRestResult);
                    $value['instock_date'] = $wareRestResult[0]['instock_date'];
                    $value['arrival_date'] =  $wareRestResult[0]['arrival_date'];
                }else {

                    $value['instock_date'] = NULL;
                    $value['arrival_date'] = NULL;
                }

                if( $value['is_new'] ==1){

                    $value['is_new_ch'] = "是";
                }else{
                    $value['is_new_ch'] = "否";
                }
                // 获取采购员
                $keys = $value['demand_number'] . "-" . $value['sku'];
                $value['buyer_name'] = (isset($sugges_buyers[$keys]) && !empty($sugges_buyers[$keys])) ? $sugges_buyers[$keys]['buyer_name'] : NULL;
                $value['buyer_id'] = (isset($sugges_buyers[$keys]) && !empty($sugges_buyers[$keys])) ? $sugges_buyers[$keys]['buyer_id'] : NULL;
                $paytime_result = $this->get_purchase_pay_type($value['purchase_number'], $value['source']);

                // 预计到货时间导入后在前端页面，默认=年月日时分秒。时分秒默认=12:00:00

                if (!empty($value['estimate_time'])) {
                    $estimates = explode(" ", $value['estimate_time']);
                    if (in_array("00:00:00", $estimates)) {

                        $value['estimate_time'] = str_replace("00:00:00", "12:00:00", $value['estimate_time']);
                    }
                }
                if (!empty($value['documentary_time'])) {
                    $progress_time = explode(" ", $value['documentary_time']);
                    if (in_array("00:00:00", $progress_time)) {

                        $value['documentary_time'] = str_replace("00:00:00", "12:00:00", $value['documentary_time']);
                    }
                }

                // 物流信息
                $value['logistics_info'] = $this->get_logistics_info($value['purchase_number'], $value['sku']);
                $value['application_time'] = isset($paytime_result['application_time']) ? $paytime_result['application_time'] : NULL;
                $value['payer_time'] = isset($paytime_result['payer_time']) ? $paytime_result['payer_time'] : NULL;
                $number = $this->get_progress_order_cancel_ctq($value['sku'], $value['purchase_number']);
                $num = $this->get_order_cancel_list($value['purchase_number'], $value['sku'],False);

                //异常类型
                if (!isset($value['abnormal_type'])) {
                    $value['abnormal_type'] = $this->_get_abnormal_type($value['purchase_number'], $value['sku']);
                }
                if (!empty($value['abnormal_type'])) {
                    $abnormal_type_arr = explode(',', $value['abnormal_type']);
                    $abnormal_type_cn = array();
                    foreach ($abnormal_type_arr as $item) {
                        $abnormal_type_cn[] = getWarehouseAbnormalType($item);
                    }
                    $value['abnormal_type_cn'] = implode(',', $abnormal_type_cn);
                } else {
                    $value['abnormal_type_cn'] = '';
                }

                //是否在途异常
                $value['on_way_abnormal_cn'] = getOnWayAbnormalStatus($value['on_way_abnormal']);
                //$arrival_qty = $this->get_instock_qty($value['purchase_number'],$value['sku']);
                $instock_qty = $this->get_instock_qty($value['purchase_number'], $value['sku']);
                $cancanl_num = isset($num[$value['sku'].$value['purchase_number']]) ? $num[$value['sku'].$value['purchase_number']] : 0;
                $lossNumber = $this->get_reportloss_data($value['purchase_number'],$value['sku'],$value['source']);
                $lossSuccNumber = $this->get_reportloss_success_data($value['purchase_number'],$value['sku'],$value['source']);
                $value['no_instock_date'] = $value['purchase_num'] - $number - $cancanl_num - $instock_qty['instock_qty']-$lossNumber['loss_amount'];
                // 10 取消0

                if (empty($value['instock_date']) || empty($value['arrival_date'])) {

                    $value['storage'] = NULL;
                } else {

                    //$value['storage'] = floor((strtotime($value['instock_date']) - strtotime($value['arrival_date'])) % 86400 / 3600);
                    $storage = $this->timediff(strtotime($value['instock_date']),strtotime($value['arrival_date']));
                    $value['storage'] = $storage['day']*24 + $storage['hour'];
                }
                $value['storageday'] = NULL;

                if( $value['estimate_time'] >= $value['arrival_date'] ) {

                    $value['storageday'] = NULL;
                }else {
                    //$value['storageday'] = floor((strtotime($value['arrival_date']) - strtotime($value['estimate_time'])) / 86400);
                    $storageday = $this->timediff(strtotime($value['arrival_date']),strtotime($value['estimate_time']));
                    $value['storageday'] = $storageday['day'];

                }
                if( empty($value['arrival_date']) ) {
                    if( !empty($value['estimate_time']) ) {
                        $estimagetime = strtotime($value['estimate_time']);
                        if( $estimagetime< time()) {
                            //$value['storageday'] = floor((time()-strtotime($value['estimate_time'])) / 86400);
                            $storageday = $this->timediff(time(),strtotime($value['estimate_time']));
                            $value['storageday'] = $storageday['day'];

                        }else{

                            $value['storageday'] = NULL;
                        }
                    }
                }
                if (empty($value['payer_time']) || $value['payer_time'] == "0000-00-00 00:00:00") {

                    $value['payer_h'] = NULL;
                } else {
                    $hours = $this->timediff(strtotime($value['payer_time']),strtotime($value['application_time']));
                    $value['payer_h'] = $hours['day']*24 + $hours['hour'];
                }

                $instock_qty = $this->Purchase_order_progress_model->get_purchase_sku_instock_qty($value['purchase_number'], $value['sku']);
                $value['instock_qty'] = $instock_qty['instock_qty'];
                $keystring = $value['sku'] . $value['demand_number'];
                if (isset($cancal[$keystring])) {

                    $value['cancal_number'] = $cancal[$keystring];
                }
                $value['stock_owes'] = $value['left_stock'];


                if( $value['payer_time'] == "0000-00-00 00:00:00" ) {

                    $value['payer_time'] = NULL;
                }
                $value['warehourse'] = isset($warehouseResult[$value['warehouse_code']]) ? $warehouseResult[$value['warehouse_code']]['warehouse_name'] : NULL;
                $purchase_status = isset($purchase_numbers_status[$value['purchase_number']])?$purchase_numbers_status[$value['purchase_number']]['purchase_order_status']:NULL;
                $value['purchase_status_ch'] = getPurchaseStatus($purchase_status); //get_productStatus
                $value['product_status_ch'] = $this->get_productStatus($value['product_status']);
                // $value['product_status_ch'] = getPurchaseStatus($value['product_status']);
                switch ($value['source']) {

                    case 1:
                        $value['source_ch'] = "合同";
                        break;
                    case 2:
                        $value['source_ch'] = "网采";
                        break;
                    case 3:
                        $value['source_ch'] = "账期采购";
                        break;
                    default:
                        $value['source_ch'] = "未知";
                        break;
                }

                if ($value['stock_owes'] >= 1) {

                    $value['stock_owes_ch'] = "是";
                } else {
                    $value['stock_owes_ch'] = "否";
                }
                if ($value['ali_order_status'] == 0) {

                    $value['ali_order_status_ch'] = "否";
                } else {
                    $value['ali_order_status_ch'] = "是";
                }
                $value['groupName']                = isset($buyerName[$value['buyer_id']])?$buyerName[$value['buyer_id']]['group_name']:'';

                $result_value[] = array();
                $result_value['id'] = $value['id'];
            }
        }

        $data_role= getRolexiao();
        $res_xiao=array_intersect($role_name, $data_role);
        if($res_xiao){
            foreach ($result as $key=>&$row) {
                $row['supplier_name']="***";
            }
        }
        return array(

            "list" => !empty($result)?$result:[],
//            "page" => ['limit' => $query_builder['limit'], 'page' => $query_builder['page'], 'all_skus' => ($all_skus), 'page_sku' => count($page_skus), 'total_all' => $all_result, 'page_total' => count($result)]
        );
    }

    /**
     * 采购单号获取对应的SKU
     * @param  $purchase_number  array 采购单号
     *
     **/
    public function getPurchaseSku($purchase_number){

        return $this->purchase_db->from("purchase_order_items")->where_in("purchase_number",$purchase_number)->select("sku")->get()->result_array();
    }

    /**
     * 采购单号获取对应的信息
     * @param  $purchase_number  array 采购单号
     *
     **/
    public function getPurchaseMessage($purchase_number){

        return $this->purchase_db->from("purchase_order")->where_in("purchase_number",$purchase_number)->select("purchase_type_id,purchase_number,supplier_code")->get()->result_array();
    }

    /**
     * 推送信息到门户系统
     * @param $purchase_number  array|string   采购单号
     * @author:luxu
     * @time:2020/3/27
     **/
    public function pushPurchaseGateWay($purchase_number){

        if( is_string($purchase_number)){

            $purchase_number = [ $purchase_number];
        }
        $purchaseMessage = $this->getPurchaseMessage($purchase_number);
        if(!empty($purchaseMessage)){

            $purchaseSupplier = array_column($purchaseMessage,"supplier_code");
            if( !empty($purchaseSupplier) ){
                $gateWays = $this->purchase_db->from("supplier")->where_in("supplier_code",$purchaseSupplier)->where("is_gateway",SUGGEST_IS_GATEWAY_YES)->get()->result_array();
                if( !empty($gateWays)){

                    $gateWays = array_column($gateWays,"supplier_code");
                    $pushgateWays = [];
                    foreach( $purchaseMessage as $key=>$value){

                        if( in_array($value['supplier_code'],$gateWays)){
                            $pushgateWays[] = $value['purchase_number'];
                        }
                    }

                    if(!empty($gateWays)) {
                        $returnLogs = $this->supplier_joint_model->pushSmcPurchaseData($pushgateWays);
//
//                        apiRequestLogInsert(
//                            [
//                                'record_type'      => '采购单推送门户系统',
//                                'post_content'     => json_encode($purchase_number),
//                                'response_content' => $returnLogs,
//                                'status'           => '1',
//                            ],
//                            'api_request_ali_log'
//                        );
                    }
                }
            }
        }
    }

    /**
     * 通过采购单号获取是否需要推送到们系统,只推送合同单
     * @param $purchase_numbers   array|string   采购单号
     * @author:luxu
     * @time:2020/3/27
     **/
    public function getPurchaseGateWays($purchase_numbers){

        if( is_string($purchase_numbers)){

            $purchase_numbers = [ $purchase_numbers ];
        }

        $result = $this->purchase_db->from("purchase_order")->where("source",SOURCE_COMPACT_ORDER)->where_in("purchase_number",$purchase_numbers)->where("is_gateway",SUGGEST_IS_GATEWAY_YES)->select("purchase_number")->get()->result_array();
        if(!empty($result)){
            return array_column( $result,"purchase_number");
        }

        return NULL;
    }

    public function VerifyPurchaseGateWays($purchase_numbers){

        if( is_string($purchase_numbers)){

            $purchase_numbers = [ $purchase_numbers ];
        }

        $result = $this->purchase_db->from("purchase_order")->where_in("purchase_number",$purchase_numbers)->where("is_gateway",SUGGEST_IS_GATEWAY_YES)->select("purchase_number")->get()->result_array();
        if(!empty($result)){
            return array_column( $result,"purchase_number");
        }

        return NULL;
    }

    /**
     * 获取采购单是否退税
     * @param  $purchaseNumbers  array    采购参
     * @author:luxu
     * @time:2020/4/27
     **/
    public function getPurchaseIsdrawback($purchaseNumbers){

        $result = $this->purchase_db->from("purchase_order")->where_in("purchase_number",$purchaseNumbers)
            ->where("is_drawback",1)->select("purchase_number")->get()->result_array();
        if( !empty($result) ){
            $purchaseNumbers = array_column( $result,"purchase_number");
            return implode(",",$purchaseNumbers);
        }
        return NULL;
    }

    /**
     * 获取采购单是否退税
     * @param  $purchaseNumbers  array    采购参
     * @author:luxu
     * @time:2020/5/21
     **/
    public function getPurchaseNumberData($purchaseNumbers){

        $result = $result = $this->purchase_db->from("purchase_order")->where_in("purchase_number",$purchaseNumbers)
            ->select("purchase_number,purchase_type_id,is_drawback")->get()->result_array();
        if(!empty($result)){

            return array_column($result,NULL,"purchase_number");
        }
        return NULL;
    }

    /**
     * 获取采购单供应商信息
     * @param $purchaseNumbers   array  采购单号
     * @author:luxu
     * @time:2020/5/29
     **/

    public function getPurchaseSupplier($purchaseNumbers){

        $result = $this->purchase_db->from("purchase_order")->where_in("purchase_number",$purchaseNumbers)->select("supplier_code,supplier_name")
            ->group_by("supplier_code")->get()->result_array();

        return $result;

    }

    /**
     * 获取采购单详情数据
     * @param  $purchaseNumbers   array    采购单详情
     * @Author:luxu
     * @Time:2020/5/29
     **/

    public function getViewData($purchaseNumbers){

        try{
            $this->load->library('Product_lib');
            $returnData = $skuData =[];
            $result = $this->purchase_db->from("purchase_order AS orders")
                ->join("purchase_order_items AS items","orders.purchase_number=items.purchase_number","LEFT")
                ->join("product AS prod","items.sku=prod.sku","LEFT")
                ->join("supplier AS supp","orders.supplier_code=supp.supplier_code","LEFT")
                ->join("warehouse AS ware","orders.warehouse_code=ware.warehouse_code","LEFT")
                ->join('purchase_suggest_map as map', 'map.purchase_number=items.purchase_number AND map.sku=items.sku','left')
                //  LEFT JOIN `pur_purchase_suggest` AS `sg` ON `map`.`demand_number` = `sg`.`demand_number`
                ->join("pur_purchase_suggest AS sg","map.demand_number=sg.demand_number","LEFT")
                ->join("purchase_order_pay_type AS ppy","ppy.purchase_number=orders.purchase_number","LEFT")
                ->where_in("orders.purchase_number",$purchaseNumbers)
                ->select("orders.create_time AS create_time_order,orders.*,items.*,
                supp.ship_address,supp.is_postage,supp.*,ware.warehouse_name,ware.warehouse_type,sg.*,prod.declare_cname,prod.declare_unit,prod.export_cname,
                prod.tax_rate,prod.ticketed_point,sg.is_overseas_first_order AS is_overseas_first_order_ch,items.freight,prod.sample_packaging_type,prod.purchase_packaging")
                ->get()->result_array();

            foreach($result as $purchase_key=>$purchase_value){
                if( !isset($returnData[$purchase_value['purchase_number']])){

                    $returnData[$purchase_value['purchase_number']] =
                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]= [];
                }

                $supplierMess = $this->purchase_db->from("supplier_contact")->where("supplier_code",$purchase_value['supplier_code'])->select("contact_person,contact_number")
                    ->get()->row_array();
                $returnData[$purchase_value['purchase_number']] = [

                    'buyer_name' => isset($purchase_value['buyer_name'])?$purchase_value['buyer_name']:'',
                    'supplier_name' => isset($purchase_value['supplier_name'])?$purchase_value['supplier_name']:'',
                    'address'  => isset($purchase_value['ship_address'])?$purchase_value['ship_address']:'',
                    'date' =>  isset($purchase_value['create_time_order'])?$purchase_value['create_time_order']:'',
                    'contact_person' => $supplierMess['contact_person'],
                    'contact_number' => $supplierMess['contact_number']

                ];

                // 27125	“预览海外仓采购单”增加显示质检备注字段   只有是海外仓才需要
                $quality_notes = $this->product_lib->get_special_packing($purchase_value['sku']);
                if($quality_notes != ''){
                    $quality_notes = HtmlStringToText($quality_notes);
                }

                $skuData[$purchase_value['purchase_number']][$purchase_value['sku']] = [

                    'sku' => $purchase_value['sku'],
                    'product_name' => $purchase_value['product_name'],
                    'image_url' => erp_sku_img_sku($purchase_value['product_img_url']),
                    'warehouse_name' => $purchase_value['warehouse_name'],
                    'confirm_amount' => $purchase_value['confirm_amount'],
                    'unit_price' => $purchase_value['purchase_unit_price'], // 含税单价
                    'base_price' => $purchase_value['product_base_price'], // 未税单价
                    'total_price' => sprintf("%.0f",$purchase_value['confirm_amount']*$purchase_value['purchase_unit_price']),
                    'es_shipment_time' => $purchase_value['es_shipment_time'], // 预计交货日期
                    'is_freight' =>$purchase_value['freight'],
                    'accounting' => '',
                    'newprice' => '',
                    'discount' => $purchase_value['discount'],
                    'purchase_number'=>$purchase_value['purchase_number'],
                    'sample_packaging_type' => $purchase_value['sample_packaging_type'],
                    'packing_type' =>  $purchase_value['sample_packaging_type'],
                    'quality_inspection_notes' => $quality_notes,
                ];

                if($purchase_value['is_drawback']){

                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['is_drawback_ch'] = "是";
                }else{
                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['is_drawback_ch'] = "否";
                }

                if( $purchase_value['is_postage'] == 1){

                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['is_postage_ch'] = "是";
                }else{
                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['is_postage_ch'] = "否";
                }

                if($purchase_value['is_overseas_first_order_ch'] == 1){

                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['is_down_ch'] = "5%";
                }else{
                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['is_down_ch'] = "2%";
                }
                $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['jiaoh'] = "";
                $purchase_value['is_drawback'] =1;
                if($purchase_value['is_drawback'] ==1){
                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['ticketed_point'] = $purchase_value['ticketed_point'];
                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['declare_unit'] = $purchase_value['declare_unit'];
                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['export_cname'] = $purchase_value['export_cname'];
                }else{

                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['ticketed_point'] = '';
                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['declare_unit'] = '';
                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['export_cname'] = '';
                }

                if(!empty($purchase_value['purchase_packaging'])) {
                    $search_start_index = strpos($purchase_value['purchase_packaging'], "[");
                    $packaging_string = substr_replace($purchase_value['purchase_packaging'], " ", $search_start_index);
                    $packaging_string_new = str_replace(" ","",trim($packaging_string));
                    if ($purchase_value['purchase_type_id'] == 2 && strcmp($packaging_string_new, "Q2:PE袋包装") === 0) {

                        $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['packaging_type'] = "白色快递袋";
                    } else {
                        $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['packaging_type'] = $packaging_string;
                    }
                }else{
                    $skuData[$purchase_value['purchase_number']][$purchase_value['sku']]['packaging_type'] = '';
                }

            }
            if(!empty($returnData)){

                foreach( $returnData as $return_key=>$return_value){

                    $returnData[$return_key]['items'] = $skuData[$return_key];
                }
            }

            return $returnData;
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 付款提醒状态查询组织sql
     * @param $pay_notice_type
     * @param $sub_sql
     * @return string
     */
    private function _get_pay_notice_sql($pay_notice_type, $sub_sql)
    {
        $supplier_settlement_online = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;
        $where = " a.supplier_code IN (" . $sub_sql . ") AND py.accout_period_time != '0000-00-00 00:00:00'";
        if (TAP_DATE_OVER_TIME == $pay_notice_type) {//已超期
            $today = date('Y-m-d H:i:s');
            $where .= " AND py.accout_period_time<'{$today}'";
        } elseif (TAP_DATE_COMING_SOON == $pay_notice_type) {//即将到期
            $today = date('Y-m-d H:i:s');
            $five_days_later = date('Y-m-d H:i:s', strtotime('+ 5 days'));
            $where .= " AND py.accout_period_time>='{$today}' AND py.accout_period_time<'{$five_days_later}'";
        } else {//可继续等待
            $five_days_later = date('Y-m-d H:i:s', strtotime('+ 5 days'));
            $where .= " AND py.accout_period_time>='{$five_days_later}'";
        }
        return $where;
    }

    /**
     * 更新采购单预计到货时间
     * @param $number  array  采购单号
     * @author:luxu
     * @time:2020/9/23
     **/

    public function audit_estime_data($numbers){

        $result = $this->purchase_db->from("purchase_order_items")->where("purchase_number",$numbers)->get()->result_array();
        if(!empty($result)){

            foreach($result as $key=>$value){

                if(!empty($value['temporary_plan_arrive_time']) && $value['temporary_plan_arrive_time'] != '0000-00-00 00:00:00'){

                    $updata['plan_arrive_time'] = $value['temporary_plan_arrive_time'];
                    $this->purchase_db->where("purchase_number",$value['purchase_number'])->where("id",$value['id'])->update("purchase_order_items",$updata);
                    $this->insertAuditEstimeData($value['purchase_number'],$value['sku'],$value['plan_arrive_time'],$updata['plan_arrive_time'],'变成等待到货');

                }
            }
        }
    }

    public function insertAuditEstimeData($purchase_number,$sku,$old_time,$new_time,$remark=NULL,$username=false){
        if(!$username)$username = getActiveUserName();
        $data = [
            'purchase_number' => $purchase_number,
            'sku' => $sku,
            'old_estimated_arrive_time' => $old_time,
            'new_estimated_arrive_time' => $new_time,
            'audit_status' =>2,
            'remark' =>$remark,
            'created_user' => $username
        ];

        $this->purchase_db->insert('supplier_web_audit_log',$data);
   }



}
