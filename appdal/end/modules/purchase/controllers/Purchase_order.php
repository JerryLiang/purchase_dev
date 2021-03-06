<?php

/**
 * Created by PhpStorm.
 * 采购单控制器
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */
class Purchase_order extends MY_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->model('purchase_order_model');
        $this->load->model('purchase_order_new_model');
        $this->load->model('purchase_order_edit_model');
        $this->load->model('purchase_order_extend_model');
        $this->load->model('purchase_delivery_note_model');
        $this->load->model('Export_mongo_model');
        $this->load->model('purchase_order_progress_model');
        $this->load->model('purchase_order_items_model');
        $this->load->model('purchase_order_determine_model');
        $this->load->helper('status_finance');
        $this->load->helper('status_order');
        $this->load->model('supplier_joint_model');
        $this->load->helper('status_supplier');
        $this->load->helper('export_csv');
        $this->load->model('user/Purchase_user_model', 'userModel');
        $this->load->model('user/User_model', 'user');
        $this->load->model('product_line_model','product_line',false,'product');
        $this->load->model('product_model','product_model',false,'product');
        $this->load->model('Warehouse_model','Warehouse_model',false,'warehouse');
        $this->load->model('purchase_suggest_model');
        $this->load->library('Search_header_data');
    }

    /**
     * 采购单账号
     * @author harvin
     * /purchase/purchase_order/get_status_lists
     */
    public function get_status_lists(){
        $uid               = $this->input->get_post('uid');
        $purchase_acccount = getUserEnablePurchaseAccount();
        $this->success_json($purchase_acccount);
    }

    /**
     * 公共仓
     * @author harvin
     * /purchase/purchase_order/get_pertain_wms
     */
    public function get_pertain_wms()
    {
        $this->load->model('warehouse/Warehouse_model');
        $pertain_wms_list = $this->Warehouse_model->get_pertain_wms_list();
        $pertain_wms_list = array_column($pertain_wms_list,'pertain_wms_name','pertain_wms_code');
        $this->success_json($pertain_wms_list);
    }

    /**
     * 获取OA系统的采购员下拉表
     * @author harvin
     * /purchase/purchase_order/get_buyer
     */
    public function get_buyer(){
        $this->load->model('user/Purchase_user_model');
        $data = $this->Purchase_user_model->get_list();
        $this->success_json($data);

    }

    /**
     * 获取一级产品线下拉
     * @author Manson
     * /purchase/purchase_order/get_first_product_line
     */
    public function get_first_product_line()
    {
        $this->load->model('product/Product_line_model', 'Product_line_model');
        $data = $this->Product_line_model->get_product_line_list_first();
        $this->success_json($data);
    }

    /**
     * 获取一个采购单信息（附带采购单 产品明细）
     * @author Jolon
     */
    public function get_order_one(){
        $purchase_number = $this->input->get_post('purchase_number');

        // 参数错误
        if(empty($purchase_number))
            $this->error_json('参数错误');

        $order_info = $this->purchase_order_model->get_one($purchase_number);

        $this->success_json($order_info);
    }

    private function order_list_params($action=false)
    {
        $params = [
//            'show_cancel_data'      => $this->input->get_post('show_cancel_data'),//显示已作废数据  1显示 2不显示
            'first_product_line' => $this->input->get_post('first_product_line'), // 一级产品线
            'purchase_order_status' => $this->input->get_post('purchase_order_status'), // 采购状态
            'suggest_order_status' => $this->input->get_post('suggest_order_status'), // 备货单状态
            'sku' => $this->input->get_post('sku'), // SKU CW00009,CW00010
            'compact_number' => $this->input->get_post('compact_number'), // 合同号
            'demand_number' => $this->input->get_post('demand_number'), // 备货单号
            'buyer_id' => $this->input->get_post('buyer_id'), // 采购员
            'supplier_code' => $this->input->get_post('supplier_code'), // 供应商
            'is_drawback' => $this->input->get_post('is_drawback'), // 是否退税
            'is_ali_order' => $this->input->get_post('is_ali_order'), // 是否1688下单
            'product_name' => $this->input->get_post('product_name'), // 产品名称
            'is_cross_border' => $this->input->get_post('is_cross_border'), // 跨境宝供应商
            'pay_status' => $this->input->get_post('pay_status'), // 付款状态
            'source' => $this->input->get_post('source'), // 采购来源
            'is_destroy' => $this->input->get_post('is_destroy'), // 是否核销
            'product_is_new' => $this->input->get_post('product_is_new'), // 是否新品
            'purchase_number' => $this->input->get_post('purchase_number'), // 采购单号
            'purchase_type_id' => $this->input->get_post('purchase_type_id'), // 业务线
            'demand_purchase_type_id' => $this->input->get_post('demand_purchase_type_id'), // 备货单业务线
            'create_time_start' => $this->input->get_post('create_time_start'), // 创建时间-开始
            'create_time_end' => $this->input->get_post('create_time_end'), // 创建时间-结束
            'loss_status' => $this->input->get_post('loss_status'),//报损状态
            'audit_status' => $this->input->get_post('audit_status'),//取消未到货状态
            'pay_notice' => $this->input->get_post('pay_notice'),//付款提醒状态
            'need_pay_time_start' => $this->input->get_post('need_pay_time_start'),//应付款开始查询时间
            'need_pay_time_end' => $this->input->get_post('need_pay_time_end'),//应付款结束查询时间
            'audit_time_start' => $this->input->get_post('audit_time_start'), // 审核时间-开始
            'audit_time_end' => $this->input->get_post('audit_time_end'), // 审核时间-结束
            'express_no' => $this->input->get_post('express_no'), //物流单号
            'product_status' => $this->input->get_post('product_status'), //商品状态
            'is_ali_abnormal' => $this->input->get_post('is_ali_abnormal'), //是否1688异常
            'is_ali_price_abnormal' => $this->input->get_post('is_ali_price_abnormal'), //是否1688订单金额异常
            'pai_number' => $this->input->get_post('pai_number'), //拍单号
            'warehouse_code' => $this->input->get_post('warehouse_code'), //采购仓库
            'account_type' => $this->input->get_post('account_type'), //结算方式
            'is_inspection' => $this->input->get_post('is_inspection'),
            'is_overdue' => $this->input->get_post('is_overdue'),//是否逾期
            'supplier_source' => $this->input->get_post('supplier_source'),
            'statement_number' => $this->input->get_post('statement_number'),//对账单号
            'seachuid' => $this->input->get_post('uid'),
            'state_type' => $this->input->get_post('state_type'),//开发类型
            'is_expedited' => $this->input->get_post('is_expedited'),//是否加急
            'ids' => $this->input->get_post('ids'),
            'is_scree' => $this->input->get_post('is_scree'), // 是否在申请中
            'entities_lock_status' => $this->input->get_post('entities_lock_status'),// 是否实单锁单
            'is_invaild' => $this->input->get_post('is_invaild'),
            'lack_quantity_status' => $this->input->get_post('lack_quantity_status'),// 是否欠货
            'is_forbidden' => $this->input->get_post('is_forbidden'),// 供应商是否禁用
            'order_by' => $this->input->get_post('order_by'),// 排序字段(1.供应商)
            'order' => $this->input->get_post('order'),// 排序顺序(desc,asc)
            'level' => $this->input->get_post('level'), // 用户角色
            'ticketed_point' => $this->input->get_post('ticketed_point'), // 开票点是否为空
            'is_relate_ali' => $this->input->get_post('is_relate_ali'), // 是否关联1688
            'is_generate' => $this->input->get_post('is_generate'), // 是否存在合同
            'is_purchasing' => $this->input->get_post('is_purchasing'), // 是否代采
            'is_arrive_time_audit' => $this->input->get_post('is_arrive_time_audit'), // 交期确认状态
            'order_num' => $this->input->get_post('order_num'), // 下单数
            'barcode_pdf' => $this->input->get_post('barcode_pdf'),// 工厂码
            'label_pdf' => $this->input->get_post('label_pdf'), // 标签
            'is_equal_sup_id' => $this->input->get_post('is_equal_sup_id'),// 供应商ID是否一致
            'is_equal_sup_name' => $this->input->get_post('is_equal_sup_name'),// 供应商名称是否一致
            'is_new'   =>$this->input->get_post('is_new'),
            'pertain_wms' => $this->input->get_post('pertain_wms'),// 公共仓
            'is_overseas_first_order' => $this->input->get_post('is_overseas_first_order'), // 是否海外仓首单
            'is_gateway' => $this->input->get_post('is_gateway'), // 是否对接门户
            'check_status' => $this->input->get_post('check_status'), // 验货状态
            'push_gateway' => $this->input->get_post('push_gateway'), // 推送门户系统是否成功
            'push_gateway_success' => $this->input->get_post('push_gateway_success'),
            'gateway_status' =>$this->input->get_post('gateway_status'), // 门户系统订单状态
            'transformation' => $this->input->get_post('transformation'), // 国内转海外
            'pay_finish_status' => $this->input->get_post('pay_finish_status'), // 付款完结状态
            'ca_amount_search' => $this->input->get_post('ca_amount_search'), // 抵扣金额
            'is_customized' => $this->input->get_post('is_customized'), //是否定制
            'devliery_status' =>$this->input->get_post('devliery_status'),
            'devliery_days'  => $this->input->get_post('devliery_days'), // 逾期天数(交期)
            'shipment_type'  => $this->input->get_post('shipment_type'), // 发运类型 1.工厂发运;2.中转仓发运
            'pay_type' => $this->input->get_post('pay_type'),       // 支付方式:1.线上支付宝,2.线下境内（对公支付），3.线下境外（对私支付）
            'unfinished_overdue'  => $this->input->get_post('unfinished_overdue'), // 未完结天数（1-超20天未完结，2-超30天未完结，3-超40天未完结，4-45天以上未完结，5-45-60天未完结，6-60天以上未完结）
            'pur_manager_audit_reject'  => $this->input->get_post('pur_manager_audit_reject'), // 是否采购经理审核驳回
            'overdue_delivery_type'  => $this->input->get_post('overdue_delivery_type'), // 逾期天数类型
            'ali_order_status'  => $this->input->get_post('ali_order_status'), // 1688订单状态
            'ali_refund_amount'  => $this->input->get_post('ali_refund_amount'), // 1688退款金额≠0
            'instock_qty_gt_zero'  => $this->input->get_post('instock_qty_gt_zero'), // 入库数量不等于0
            'pay_time_start'  => $this->input->get_post('pay_time_start'), // 付款时间开始
            'pay_time_end'  => $this->input->get_post('pay_time_end'), // 付款时间结束
            'is_arrive_time_audit_start' => $this->input->get_post('is_arrive_time_audit_start'),
            'is_completion_order'=> $this->input->get_post('is_completion_order'),//订单是否完结（1-是，2-否）
            'group_ids' => $this->input->get_post('group_ids'), // 组别
            'plan_arrive_time_start'=> $this->input->get_post('plan_arrive_time_start'),// 预计到货时间 start
            'plan_arrive_time_end'=> $this->input->get_post('plan_arrive_time_end'),// 预计到货时间 end
            'instock_date'=> $this->input->get_post('instock_date'),// 入库时间
            'list_type' => $this->input->get_post('list_type', 1), // 页签数据不能为空
            'groupname' => $this->input->get_post('groupname'),
            'new_is_freight' => $this->input->get_post('new_is_freight'),
            'mude_code' => $this->input->get_post('mude_code'),
            'tap_date_str' => $this->input->get_post('tap_date_str'),
            'supply_status' => $this->input->get_post('supply_status'),
            'overdue_days_one' => $this->input->get_post('overdue_days_one'),
            'overdue_day_day'  => $this->input->get_post('overdue_day_day'), //逾期天数（预计），按照最新预计到货时间和当前时间判断；
            'overdue_day_one'  => $this->input->get_post('overdue_day_one'), // 是否逾期（预计），按照最新预计到货时间和当前时间判断；
            'use_wechat_official'  => $this->input->get_post('use_wechat_official'), // 是否启用微信公众号

            'is_long_delivery'  => $this->input->get_post('is_long_delivery'),
            'delivery'  =>$this->input->get_post('delivery'),
            'track_status' => $this->input->get_post('track_status'),
            'exp_no_is_empty' =>$this->input->get_post('exp_no_is_empty'),


            'is_fumigation'  => $this->input->get_post('is_fumigation'), // 是否熏蒸
            'is_oversea_boutique' => $this->input->get_post('is_oversea_boutique'),
            'free_shipping' => $this->input->get_post('free_shipping'), // 是否包邮
            'is_distribution' => $this->input->get_post('is_distribution'), // 分销
            'is_merge' => $this->input->get_post('is_merge'), // 合单状态
            'tab_tips_count' => $this->input->get_post('tab_tips_count'), // 合单状态
            'price_reduction' => $this->input->get_post('price_reduction'), // sku 降价中
        ];

        $clientStrackStatus = NULL;
        $trackStatus = $this->input->get_post('track_status');
        if(!empty($trackStatus)){

            foreach( $trackStatus as $track){

                $clientStrackStatus[] = $track-1;
            }
        }

        $params['track_status'] = $clientStrackStatus;

        if($action == 'export'){
            $params['is_csv'] = $this->input->get_post('is_csv', 0);//是否导出为csv
        }
        return $params;
    }

    public function getMudeCode(){

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $data_list = array_column($warehouse_list,'warehouse_name','warehouse_code');
        $this->success_json($data_list);
    }

    /**
     * 根据查询条件获取采购单列表
     * http://www.caigou.com/purchase/purchase_order/get_order_list
     * @author harvin 2019-1-8
     */
    public function get_order_list()
    {
        $params = $this->order_list_params();
        $page  = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if(empty($page) or $page < 0)
        $page = 1;
        $limit       = query_limit_range($limit);
        $offsets     = ($page - 1) * $limit;

        if( isset($params['groupname']) && !empty($params['groupname'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['groupname']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }

        $this->load->model('purchase/purchase_order_list_model');
        $orders_info = $this->purchase_order_list_model->new_get_list($params, $offsets, $limit, $page);

        // 组装显示字段
        $list_type = $this->input->get_post('list_type');
        $list_type = !empty($list_type) ? $list_type : 1;
        $key_model = $this->purchase_order_new_model->get_show_header($list_type);
        $orders_info['key'] = $key_model && $key_model['code'] == 1 ? $key_model['msg'] : $this->purchase_order_new_model->get_purchase_list_head_title();

        $this->success_json($orders_info);
    }


    /***
     * 获取采购单列表汇总数据
     * http://www.caigou.com/purchase/purchase_order/get_order_sum
     * @author luxu 2019-7-8
     **/
    public function get_order_sum()
    {
        try {
            //获取HTTP 传入数据
            $clientData = $this->order_list_params();
            $new = $this->input->get_post('new');

            if (!empty($new)) {
                $clientData['new'] = $new;
            }


            if( isset($clientData['groupname']) && !empty($clientData['groupname'])){

                $this->load->model('user/User_group_model', 'User_group_model');
                $groupids = $this->User_group_model->getGroupPersonData($clientData['groupname']);
                $groupdatas = [];
                if(!empty($groupids)){
                    $groupdatas = array_column($groupids,'value');
                }

                $clientData['groupdatas'] = $groupdatas;
            }

            $this->load->model('purchase/purchase_order_list_model');
            $order_sum = $this->purchase_order_list_model->get_order_sum($clientData);

            $this->success_json($order_sum);
        } catch (Exception $exp) {
            $order_sum = array('errorCode'=>500,"errorMess"=>$exp->getMessage());
            $this->success_json($order_sum);
        }
    }

    private function get_client_data($params)
    {
        $filter = [];
        if( isset($params['is_csv']))
        {
            unset($params['is_csv']);
        }
        foreach($params as $param_key=>$param_value ) {

            if ($param_key != 'new' && $param_key != 'search' && !strstr($param_key, 'time_start') && !strstr($param_key, 'time_end')) {
                if (!empty($param_value)) {
                    if ($param_key == "compact_number" && !empty($param_value)) {
                        $purchase_number = $this->Export_mongo_model->get_purchase_number($param_value);
                        $filter['purchase_number'] = array('$in' => $purchase_number);
                    }else if($param_key == "sku"){
                        $skus = explode(" ",$param_value);
                        if(COUNT($skus) >1){

                            $filter['sku'] = array('$in'=>$skus);
                        }else if(COUNT($skus) == 1){

                            $filter['sku'] = "{$skus[0]}";
                        }
                    }else if($param_key == "purchase_number" && !empty($params['purchase_number'])){
                        $purchase_numbers = explode(" ",$param_value);
                        if(!empty($purchase_numbers[0]))
                        {
                            $filter['purchase_number'] = array('$in'=>$purchase_numbers);
                        }
                    }else if($param_key == "is_destroy" && !empty($params['is_destroy'])){
                        $filter['purchase_order_status'] = array('$in'=>['9','10','14']);//核销的默认只查全部到货,部分到货等待剩余的
                        $filter['is_destroy'] = $param_value;
                    }else if( $param_key == 'is_forbidden'){

                        if( $param_value == 1){

                            $filter['supplier_status'] =2;
                        }else{

                            $filter['supplier_status'] = array('$ne'=>2);
                        }
                    }else if( $param_key == 'product_name'){

                        $filter['product_name'] = $param_value;
                    }else if( $param_key == 'audit_status'){

                        $id_list = $this->db->query("SELECT id FROM pur_purchase_order_cancel WHERE audit_status='{$param_value}'")->result_array();
                        $id_list = !empty($id_list)?array_column($id_list,'id'):[ORDER_CANCEL_ORSTATUS];
                        $id_l= implode(',', $id_list);
                        $items_id = $this->db->query("SELECT items_id FROM pur_purchase_order_cancel_detail WHERE cancel_id in ({$id_l})")->result_array();
                        $items_id = !empty($items_id)?array_column($items_id,'items_id'):[ORDER_CANCEL_ORSTATUS];
                        $items_id= array_unique($items_id);

                        $cancel_id = $this->purchase_order_model->get_cancel_id($items_id);
                        foreach ($cancel_id as $item_id => &$audit_status){
                            if ($audit_status != $params['audit_status']){
                                unset($cancel_id[$item_id]);
                            }
                        }
                        $items_id = array_keys($cancel_id);
                        foreach ($items_id as &$value){
                            $value = (string)$value;
                        }

                        $filter['id'] = array('$in'=>$items_id);
                    }else if($param_key == 'entities_lock_status'){

                        if( $param_value == 2){
                            $filter['entities_lock_status'] = 2;
                        }else{
                            $filter['entities_lock_status'] = 0;
                        }
                    }else if (is_array($param_value)) {
                        if(isset($param_value[0]) && !empty($param_value[0])) {
                            $filter[$param_key] = array('$in' => $param_value);
                        }
                    }else if($param_key == 'is_invaild'){
                        // 如果客户端传入链接是否失效的选项
                        if( $param_value == 1)
                        {
                            $filter['is_invalid'] = 1;
                        }else{
                            $filter['is_invalid'] = 0;
                        }
                    }else if($param_key == 'pay_notice'){

                        // 付款状态提醒
                        if( $param_value == TAP_DATE_WITHOUT_BALANCE){

                            $supplier_settlement_online = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;

                            $supplier_res = $this->db->query("SELECT supplier_code FROM pur_supplier WHERE supplier_settlement='{$supplier_settlement_online}' and pur_supplier.quota <> 0 and pur_supplier.surplus_quota <= 0")->result_array();
                            $supplier_res = !empty($supplier_res)?array_column($supplier_res,'supplier_code'):[ORDER_CANCEL_ORSTATUS];
                            $supplier_res= array_unique($supplier_res);
                            $filter['supplier_code'] = array('$in'=>$supplier_res);
                        }

                        if ($param_value != TAP_DATE_WITHOUT_BALANCE){ //查询额度足够的供应商

                            $supplier_settlement_online = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;
                            $supplier_res = $this->db->query("SELECT supplier_code FROM pur_supplier WHERE supplier_settlement='{$supplier_settlement_online}' and pur_supplier.quota <> 0 and pur_supplier.surplus_quota > 0")->result_array();
                            $supplier_res = !empty($supplier_res)?array_column($supplier_res,'supplier_code'):[ORDER_CANCEL_ORSTATUS];
                            $supplier_res= array_unique($supplier_res);

                            $filter['supplier_code'] = array('$in'=>$supplier_res);

                            if ($param_value == TAP_DATE_OVER_TIME){//已超期
                                $today = date('Y-m-d H:i:s');
                                $filter['accout_period_time'] = array('$gte'=>$today);
                            }

                            if ($param_value == TAP_DATE_COMING_SOON){//即将到期
                                $today = date('Y-m-d H:i:s');
                                $five_days_later = date('Y-m-d H:i:s',strtotime('+ 5 days'));
                                $filter['accout_period_time'] = array('$gte'=>$today);
                                $filter['accout_period_time'] = array('$lte'=>$five_days_later);
                            }

                            if ($param_value == TAP_DATE_CAN_WAIT){//可继续等待
                                $five_days_later = date('Y-m-d H:i:s',strtotime('+ 5 days'));
                                $filter['accout_period_time'] = array('$gte',$five_days_later);
                            }
                        }

                    } else if(!empty($param_value)){
                        $filter[$param_key] = "{$param_value}";
                    }
                }
            }else {

                if (isset($params['create_time_start']) && !empty($params['create_time_start'])) {
                    $filter['create_time'] = array('$gte' => $params['create_time_start'],'$lte'=> $params['create_time_end']);
                }

                if (isset($params['need_pay_time_start']) && !empty($params['need_pay_time_start'])) {
                    $filter['need_pay_time'] = array('$gte' => $params['need_pay_time_start'],'$lte' => $params['need_pay_time_start']);
                }
            }
        }
        return $filter;
    }

    /**
     *function:MONGODB 导出数据
     * @param: $params  string   客户端传入数据
     *         $total   int      总共导出多少数据
     * @author:luxu
     **/
    public function export_to_mongodb_csv($params,$total)
    {
        $this->load->helper('status_product');
        $this->_ci = get_instance();
        //获取redis配置
        $this->_ci->load->config('mongodb');
        $host = $this->_ci->config->item('mongo_host');
        $port = $this->_ci->config->item('mongo_port');
        $user = $this->_ci->config->item('mongo_user');
        $password = $this->_ci->config->item('mongo_pass');
        $author_db = $this->_ci->config->item('mongo_db');
        $mongdb_object = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/$author_db");
        if( isset($params['ids']) && !empty($params['ids']))
        {
            $filter = explode(",",$params['ids']);
            $filter = array('id'=>['$in'=>[$filter]]);
        }else{

            $filter = [];

            if( !empty($params))
            {
                $filter = $this->get_client_data($params);
            }

            $options = array(
                'sort' => ['_id' => -1],
            );
            $limit =1000;
            $page = ceil($total/$limit);
            $is_head = false;
            //前端路径
            $template_file = 'product_'.date('YmdHis').mt_rand(1000,9999).'.csv';
            $webfront_path = dirname(dirname(APPPATH));
            $product_file = $webfront_path.'/webfront/download_csv/'.$template_file;
            if (file_exists($product_file)) {
                unlink($product_file);
            }
            fopen($product_file,'w');
            $is_head = false;
            $fp = fopen($product_file, "a");
            for( $i=0;$i<=$page;++$i)
            {
                $options['skip'] = $i * $limit;
                $options['limit'] = $limit;
                $query = new MongoDB\Driver\Query($filter,$options);
                $cursor =$mongdb_object->executeQuery("{$author_db}.product", $query)->toArray();
                $info = $this->purchase_order_model->get_mongodb_data($cursor);
                if(!empty($info['value'])) {
                    foreach ($info['value'] as $key => $value){
                        $info['value'][$key]['logistics_trajectory'] = '';
                        if (!empty($value['logistics_info'])){
                            foreach ($value['logistics_info'] as $k => $v){
                                $info['value'][$key]['logistics_trajectory'] .= sprintf('%s-%s ',$v['cargo_company_id']??'',$v['express_no']??'');
                            }
                        }
                    }
                }
                $keys_info = $info['key'];
                $keys = array();
                foreach($keys_info AS $key=>$value) {

                    $keys[$value['key']] = $value['name'];
                    if($value['key'] == 'sku'){
                        $keys['first_product_line'] = '一级产品线';//勾选了sku时,一级产品线也要导出
                        $keys['state_type'] = '开发类型';
                        $keys['purchase_packaging'] = '包装类型';
                    }

                    if( $value['key'] == 'modify_remark' ) {

                if (!in_array('batch_note', $keys)) $keys['batch_note'] = '批量编辑备注';
                if (!in_array('freight_note', $keys)) $keys['freight_note'] = '运费说明';
                if (!in_array('purchase_note', $keys)) $keys['purchase_note'] = '采购经理审核驳回备注';
                if (!in_array('message_note', $keys)) $keys['message_note'] = '信息修改审核备注';
                if (!in_array('message_apply_note', $keys)) $keys['message_apply_note'] = '信息修改申请备注';
                        unset($keys_info[$key]);

                    }
                }

                //加是否锁单导出列表
//                if (!in_array('is_entities_lock', $keys)) $keys['is_entities_lock'] = '是否锁单';
//                if (!in_array('supplier_status', $keys)) $keys['supplier_status'] = '供应商是否已禁用';
//                if (!in_array('is_ali_price_abnormal', $keys)) $keys['is_ali_price_abnormal'] = '金额异常';
//                if (!in_array('coupon_rate_price', $keys)) $keys['coupon_rate_price'] = '票面未税单价';
//                if (!in_array('coupon_rate', $keys)) $keys['coupon_rate'] = '票面税率';
//                if( in_array('sku',$flag_keys))
//                {
//                    if (!in_array('purchase_packaging', $keys)) $keys['batch_note'] = '包装类型';
//                }
//                if (in_array('supplier_name',$keys) or in_array('supplier_code',$keys)){
//                    if (!in_array('is_equal_sup_id', $keys)) $keys['is_equal_sup_id'] = '供应商ID是否一致';
//                    if (!in_array('is_equal_sup_name', $keys)) $keys['is_equal_sup_name'] = '供应商名称是否一致';
//                }
//                if (!in_array('audit_time_status', $keys)) $keys['audit_time_status'] = '交期确认状态';
                $keyss = array_keys($keys);

                $datalist= $data_values=$data_key=$heads=[];
                $data = $info['value'];
                if($data){
                    foreach ($data as $row) {
                        $row['purchase_order_status']= getPurchaseStatus($row['purchase_order_status']);
                        $row['suggest_order_status']= isset($row['suggest_order_status'])?getPurchaseStatus($row['suggest_order_status']):'';
                        $row['source']= getPurchaseSource($row['source']);
                        $row['pay_status']= $row['pay_status_name'];
                        $row['is_equal_sup_id'] = getEqualSupId($row['is_equal_sup_id']);
                        $row['is_equal_sup_name'] = getEqualSupName($row['is_equal_sup_name']);

                        $note_data = $this->purchase_order_model->get_note_list($row['purchase_number'], $row['sku']);
                        if(!empty($note_data))
                        {
                            $row['batch_note'] = isset($note_data[0])?$note_data[0]['note']:NULL;
                            $row['freight_note'] = isset($note_data[1])?$note_data[1]['note']:NULL;
                            $row['purchase_note'] = isset($note_data[2])?$note_data[2]['note']:NULL;
                            $row['message_note'] = isset($note_data[3])?$note_data[3]['note']:NULL;
                            $row['message_apply_note'] = isset($note_data[4])?$note_data[4]['note']:NULL;
                        }else{

                            $row['batch_note'] = NULL;
                            $row['freight_note'] = NULL;
                            $row['purchase_note'] = NULL;
                            $row['message_note'] = NULL;
                            $row['message_apply_note'] = NULL;
                        }


                        foreach ($row as $key=>$val) {
                            if(in_array($key, $keyss)){
                                $data_key[$key]=$row[$key];
                            }
                        }
                        $datalist[]=$data_key;
                        unset($data_key);
                    }
                    foreach ($datalist as $key =>$vv) {
                        if( isset($vv['suggest_order_status']) && is_array($vv['suggest_order_status']))
                        {
                            $vv['suggest_order_status'] = NULL;
                        }
                        foreach ($vv as $k => $vvv) {
                            if($key==0){

                                $heads[]= $keys[$k];
                            }
                            if(preg_match("/[\x7f-\xff]/", $vvv)) $vv[$k] = stripslashes(iconv('UTF-8','GBK//IGNORE',$vvv));//中文转
                            if( $k != "purchase_price" ) {
                                if (is_numeric($vvv) && strlen($vvv) > 9) $vv[$k] = $vvv . "\t";//避免大数字在csv里以科学计数法显示
                            }

                        }
                        if($is_head === false){
                            foreach($heads as &$m){
                                $m = iconv('UTF-8','GBK//IGNORE',$m);
                            }

                            fputcsv($fp,$heads);
                            $is_head = true;
                        }
                        fputcsv($fp,$vv);
                    }
                    ob_flush();
                    flush();
                    usleep(5000);
                }
            }
            //CG_SYSTEM_WEB_FRONT_IP
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url= $down_host.'download_csv/'.$template_file;
            $this->success_json($down_file_url);
        }

    }

    /**
     * 导出
     * @author harvin
     * http://www.caigou.com/purchase/purchase_order/export
     * **/
    public function export()
    {
        set_time_limit(0);
        ini_set('memory_limit','512M');
        ini_set('pcre.backtrack_limit', 10000000);
        $params = $this->order_list_params('export');

        if( isset($params['groupname']) && !empty($params['groupname'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['groupname']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }
        if(empty($params['list_type']))$this->error_json("页签数据不能为空，请联系技术人员处理。");

        $this->load->model('purchase/purchase_order_list_model');
        // 页签数据
        $list_type_ARR = [TIPS_ALL_ORDER, TIPS_WAITING_CONFIRM, TIPS_WAITING_ARRIVE, TIPS_ORDER_FINISH, TIPS_TODAY_WORK, TIPS_WAIT_CANCEL];
        if(in_array($params['list_type'], $list_type_ARR) && empty($params['purchase_order_status'])){
            switch ((int)$params['list_type']){
                case TIPS_TODAY_WORK:
                    $params['purchase_order_status'] = [
                        PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                        PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                        PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION,
                        PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                        PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,
                        PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND,
                        PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT,
                    ];
                    break;
                case TIPS_WAITING_CONFIRM:
                    $params['purchase_order_status'] = [
                        PURCHASE_ORDER_STATUS_WAITING_QUOTE,
                        PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,
                        PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER
                    ];
                    break;
                case TIPS_WAITING_ARRIVE:
                    $params['purchase_order_status'] = [
                        PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                        PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                        PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION,
                        PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                        PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,
                        PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND,
                        PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT
                    ];
                    break;
                case TIPS_ORDER_FINISH:
                    $params['purchase_order_status'] = [
                        PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                        PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                        PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                        PURCHASE_ORDER_STATUS_CANCELED,
                        PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT
                    ];
                    break;
                case TIPS_WAIT_CANCEL:
                    $params['purchase_order_status'] = [
                        PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                        PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                    ];
                    break;
                default:
                    $params['purchase_order_status'] = [];
            }
        }
        $ext = ($params['is_csv'] == 1)?'csv':'excel';
        if( $ext == 'csv') {
            $this->load->model('system/Data_control_config_model');
            $orders_info = $this->purchase_order_list_model->get_order_sum($params, $offsets = 0, $limit = 2000, 1, $page = 1, True, True);
            $total = $orders_info['aggregate_data']['total_all'];

            if($total >= 150000){

                $this->error_json('采购单最多只能导出15万条数据，请分批导出');
            }
            try {
                $result = $this->Data_control_config_model->insertDownData($params, 'PURCHASEORDER', '采购单', getActiveUserName(), $ext, $total);
            } catch (Exception $exp) {
                $this->error_json($exp->getMessage());
            }
            if ($result) {
                $this->success_json("添加到下载中心");
            } else {
                $this->error_json("添加到下载中心失败");
            }

            die();
        }
        // 下面代码暂时不用，万不可以删除
        $this->load->helper('status_product');
        $orders_info = $this->purchase_order_list_model->get_order_sum($params, $offsets = 0, $limit = 2000, 1, $page = 1, True, True);
        // 表头 start
        $keys_rows = $this->search_header_data->table_columns();
//                    $keys_info = $this->search_header_data->table_columns();
        $list_type = $this->input->get_post('list_type');
        $list_type = !empty($list_type) ? $list_type : 1;
        $key_model = $this->purchase_order_new_model->get_show_header($list_type);
        $orders_key = $key_model && $key_model['code'] == 1 ? $key_model['msg'] : $this->purchase_order_new_model->get_purchase_list_head_title();
        $keys_info = [];
        foreach ($orders_key as $o_v){
            if(count($o_v['field']) == 0)continue;
            foreach ($o_v['field'] as $o_f){
                if(isset($keys_rows[$o_f]))$keys_info[$o_f] = $keys_rows[$o_f];
            }
        }
        if ($params['is_csv'] == 1) {
            $total = $orders_info['aggregate_data']['total_all'];
            if($total>=10000) {
                $this->export_to_mongodb_csv($params,$total);
                die();
            }
            $template_file = 'purchase_order_'.date('YmdHis').mt_rand(1000,9999).'.csv';
            if($total>100000){//一次最多导出10W条
                $template_file = 'product.xlsx';
                $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
                $down_file_url = get_export_path_replace_host(get_export_path('purchase_order_export'),$down_host). $template_file;
                $this->success_json($down_file_url);
            }

            $product_file = get_export_path('purchase_order_export') . $template_file;
            if (file_exists($product_file)) {
                unlink($product_file);
            }
            fopen($product_file,'w');
            $is_head = false;
            $fp = fopen($product_file, "a");

            if($total > 0){
                $per_page = 1000;
                //$total_page = ceil($orders_info['aggregate_data']['max_id']-$orders_info['aggregate_data']['min_id']/$per_page);
                $total_page = ceil($total/$per_page);

                if(isset($keys_info['modify_remark']))unset($keys_info['modify_remark']);

                    $keys = array();
                    foreach($keys_info AS $key=>$value) {

                        $keys[$value['key']] = $value['name'];
                        if($value['key'] == 'sku'){
                            $keys['first_product_line'] = '一级产品线';//勾选了sku时,一级产品线也要导出
                            $keys['state_type'] = '开发类型';
//                            pr($keys);exit;
                        }
                        if ($value['key'] == 'pay_status') {
                            $keys['pay_finish_status'] = '付款完结状态';//勾选了付款状态时,付款完结状态也要导出
                        }
                        if ($value['key'] == 'amount_paid') {
                            $keys['ca_product_money'] = '抵扣商品额';
                            $keys['ca_process_cost'] = '抵扣加工费';
                        }
                    if($value['key'] == 'account_type' || $value['key'] == 'pay_type'){
                        $keys['settlement_ratio'] = '结算比例';
                    }
                    if($value['key'] == 'supplier_name'){
                        $keys['supplier_code'] = '供应商代码';
                    }

                    if ($value['key'] == 'modify_remark') {
                            if (!in_array('batch_note', $keys)) $keys['batch_note'] = '批量编辑备注';
                            if (!in_array('freight_note', $keys)) $keys['freight_note'] = '运费说明';
                            if (!in_array('purchase_note', $keys)) $keys['purchase_note'] = '采购经理审核驳回备注';
                            if (!in_array('message_note', $keys)) $keys['message_note'] = '信息修改审核备注';
                            if (!in_array('message_apply_note', $keys)) $keys['message_apply_note'] = '信息修改申请备注';
                        }
                    }
                $table_header = array_keys($keys);
                // 表头 end

                for ($i = 1; $i <= $total_page; ++$i) {
                    $offsets = ($i - 1) * $per_page;
                    $info = $this->purchase_order_list_model->new_get_list($params, $offsets, $per_page, 1, True, false, $orders_info['aggregate_data']['min_id'], True);
                    if (!empty($info['value'])) {
                        foreach ($info['value'] as $key => $value) {
                            $info['value'][$key]['logistics_trajectory'] = '';
                            if (!empty($value['logistics_info'])) {
                                foreach ($value['logistics_info'] as $k => $v) {
                                    $info['value'][$key]['logistics_trajectory'] .= sprintf('%s-%s ', $v['cargo_company_id'] ?? '', $v['express_no'] ?? '');
                                }
                            }

                        }
                    }
                    $datalist= $data_values=$data_key=$heads=[];
                    $data = $info['value'];
                    if($data){
                        foreach ($data as $row) {
                            $row['purchase_order_status']= getPurchaseStatus($row['purchase_order_status']);

                            $row['suggest_order_status']= getPurchaseStatus($row['suggest_order_status']);
                            $row['source']= getPurchaseSource($row['source']);
                            $row['pay_status']= $row['pay_status_name'];
                            $row['is_equal_sup_id'] = getEqualSupId($row['is_equal_sup_id']);
                            $row['is_equal_sup_name'] = getEqualSupName($row['is_equal_sup_name']);
                            $note_data = $this->purchase_order_model->get_note_list($row['purchase_number'], $row['sku']);
                            if (!empty($note_data)) {
                                $row['batch_note'] = isset($note_data[0])?$note_data[0]['note']:NULL;
                                $row['freight_note'] = isset($note_data[1])?$note_data[1]['note']:NULL;
                                $row['purchase_note'] = isset($note_data[2])?$note_data[2]['note']:NULL;
                                $row['message_note'] = isset($note_data[3])?$note_data[3]['note']:NULL;
                                $row['message_apply_note'] = isset($note_data[4])?$note_data[4]['note']:NULL;
                            }else{

                                $row['batch_note'] = NULL;
                                $row['freight_note'] = NULL;
                                $row['purchase_note'] = NULL;
                                $row['message_note'] = NULL;
                                $row['message_apply_note'] = NULL;
                            }
                            foreach ($row as $key=>$val) {
                                if (in_array($key, $table_header)) {
                                    $data_key[$key]=$row[$key];
                                }
                            }
                            $datalist[]=$data_key;
                            unset($data_key);
                        }

                        foreach ($datalist as $key =>$vv) {

                            if( isset($vv['suggest_order_status']) && is_array($vv['suggest_order_status']))
                            {
                                $vv['suggest_order_status'] = NULL;
                            }

                            if( isset($vv['purchase_order_status']) && is_array($vv['purchase_order_status']))
                            {
                                $vv['purchase_order_status'] = NULL;
                            }

                            foreach ($vv as $k => $vvv) {
                                if ($key == 0 && !is_array($vvv)) {

                                    $heads[] = $keys[$k];
                                }

                                if (!is_array($vvv) && preg_match("/[\x7f-\xff]/", $vvv))
                                {
                                    $vv[$k] = stripslashes(iconv('UTF-8', 'GBK//IGNORE', $vvv));
                                }//中文转
                                if (!is_array($vvv) && $k != "purchase_price") {
                                        if (is_numeric($vvv) && strlen($vvv) > 9) $vv[$k] = $vvv . "\t";//避免大数字在csv里以科学计数法显示
                                }
                            }
                            if($is_head === false){
                                foreach($heads as &$m){
                                    $m = iconv('UTF-8','GBK//IGNORE',$m);
                                }

                                fputcsv($fp,$heads);
                                $is_head = true;
                            }
                            fputcsv($fp,$vv);
                        }
                        ob_flush();
                        flush();
                        usleep(5000);
                    }

                }
            }
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url= $down_host.'download_csv/'.$template_file;
            $this->success_json($down_file_url);
        }else{
            $total = $orders_info['aggregate_data']['total_all'];
            static $return = [];
            $per_page = 2000;
            $total_page = ceil($total/$per_page);
            $all_page = $total_page > 10 ? 10 : $total_page;//限制2W条数据
            for($i = 1;$i <= $all_page;$i++){
                $offsets = ($i - 1) * $per_page;
//                $info = $this->purchase_order_model->get_list($params, $offsets, $per_page);
                $info = $this->purchase_order_list_model->new_get_list($params, $offsets, $per_page,1,True,false,$orders_info['aggregate_data']['min_id'],True);
//                $info['key'] = $this->search_header_data->table_columns();
//                $orders_info['key'] = $keys_info;

                if(isset($keys_info['modify_remark']))unset($keys_info['modify_remark']);
                if (!empty($info['value'])) {

                    if(!empty($info['value'])) {
                        $buyerIds = array_unique(array_column($info['value'], "buyer_name"));
                        if(!empty($buyerIds)){

                            $buyerIds = array_map(function($name){

                                return sprintf("'%s'",$name);
                            },$buyerIds);

                        }

                        $buyerName = $this->User_group_model->getNameGroupMessage($buyerIds);
                        $buyerName = array_column($buyerName, NULL, 'user_name');
                    }else{
                        $buyerName = [];
                    }

                    foreach ($info['value'] as $key => $value) {

                        $info['value'][$key]['logistics_trajectory'] = '';
                        if (!empty($value['logistics_info'])){
                            foreach ($value['logistics_info'] as $k => $v){
                                $info['value'][$key]['logistics_trajectory'] .= sprintf('%s-%s ',$v['cargo_company_id']??'',$v['express_no']??'');
                            }
                        }
                        $packaging_string_new = str_replace(" ","",trim($value['purchase_packaging']));
                        if($value['purchase_type_id'] == 2 &&  strstr($packaging_string_new,"Q2:PE袋包装")){

                            $info['value'][$key]['purchase_packaging'] = str_replace("Q2:PE袋包装","白色快递袋",$packaging_string_new);
                        }else {
                            $info['value'][$key]['purchase_packaging'] = $value['purchase_packaging'];
                        }

                        $note_data = $this->purchase_order_model->get_note_list($value['purchase_number'], $value['sku']);
                        if (!empty($note_data)) {
                            $info['value'][$key]['batch_note'] = isset($note_data[0])?$note_data[0]['note']:NULL;
                            $info['value'][$key]['freight_note'] = isset($note_data[1])?$note_data[1]['note']:NULL;
                            $info['value'][$key]['purchase_note'] = isset($note_data[2])?$note_data[2]['note']:NULL;
                            $info['value'][$key]['message_note'] = isset($note_data[3])?$note_data[3]['note']:NULL;
                            $info['value'][$key]['message_apply_note'] = isset($note_data[4])?$note_data[4]['note']:NULL;
                        }else{

                            $info['value'][$key]['batch_note'] = NULL;
                            $info['value'][$key]['freight_note'] = NULL;
                            $info['value'][$key]['purchase_note'] = NULL;
                            $info['value'][$key]['message_note'] = NULL;
                            $info['value'][$key]['message_apply_note'] = NULL;
                        }
                        $info['value'][$key]['groupname'] = isset($buyerName[$value['buyer_name']])?$buyerName[$value['buyer_name']]['group_name']:'';
                    }
                    $return = array_merge($return,$info['value']);
                }
                if( !isset($orders_info['key']) || empty($orders_info['key']) ) {
                    $keyss = array();
                    foreach ($keys_info as $k => $v) {

                        array_push($keyss,array('key'=>$k,'name'=>$v));
                        if ($k == 'sku'){
                            array_push($keyss,array('key'=>'first_product_line','name'=>'一级产品线'));//勾选了sku时,一级产品线也要导出
                            array_push($keyss, array('key' => 'state_type', 'name' => '开发类型'));
                            array_push($keyss, array('key' => 'purchase_packaging', 'name' => '包装类型'));
                        }
                        if ($k == 'pay_status') {
                            array_push($keyss, array('key' => 'pay_finish_status', 'name' => '付款完结状态'));
                        }
                        if ($k == 'amount_paid') {
                            array_push($keyss, array('key' => 'ca_product_money', 'name' => '抵扣商品额'));
                            array_push($keyss, array('key' => 'ca_process_cost', 'name' => '抵扣加工费'));
                        }
                        if($k == 'account_type' || $k == 'pay_type'){
                            array_push($keyss, array('key' => 'settlement_ratio', 'name' => '结算比例'));
                        }
                        if($k == 'supplier_name'){
                            array_push($keyss, array('key' => 'supplier_code', 'name' => '供应商代码'));
                        }

                        if ($k == 'modify_remark') {
                            if (!in_array('batch_note', $keyss)) array_push($keyss, array('key' => 'batch_note', 'name' => '批量编辑备注'));
                            if (!in_array('freight_note', $keyss)) array_push($keyss, array('key' => 'freight_note', 'name' => '运费说明'));
                            if (!in_array('purchase_note', $keyss)) array_push($keyss, array('key' => 'purchase_note', 'name' => '采购经理审核驳回备注'));
                            if (!in_array('message_note', $keyss)) array_push($keyss, array('key' => 'message_note', 'name' => '信息修改审核备注'));
                            if (!in_array('message_apply_note', $keyss)) array_push($keyss, array('key' => 'message_apply_note', 'name' => '信息修改申请备注'));
                        }
                    }
                    $keyss_tmp = array_column($keyss, 'key');
                    $keyss[]=[
                        'key' => 'groupname',
                        'name' => '采购小组'
                    ];

                    $keyss[] =[

                        'key' => 'track_status_ch',
                        'name' => '轨迹状态'
                    ];

                    $keyss[] =[

                        'key' => 'is_long_delivery_ch',
                        'name' => '是否超长交期'
                    ];

                    $keyss[] =[

                        'key' => 'new_devliy',
                        'name' => '交期天数'
                    ];
                    //                    $keys['quantity_time'] = "门户回货时间";
                    $keyss[] = [

                        'key' => 'quantity_time',
                        'name' => '门户回货时间'
                    ];
                    $keyss[] =[
                        'key' => 'tap_date_str_sync',
                        'name' => '授信账期日期'
                    ];

                    $orders_info['key'] = $keyss;
                }
                unset($info);
            }
            $orders_info['value'] = $return;
            $this->success_json($orders_info);
        }
    }

    /**
     * 获取历史采购信息
     * @author harvin 2019-1-8 
     * /purchase/purchase_order/get_order_history
     */
    public function get_order_history(){
        $sku = $this->input->get_post('sku');//sku 参数
        if(empty($sku)){
            $this->success_json([], null, '参数错误');
        }

        $page  = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if(empty($page) or $page < 0)
            $page = 1;
        $limit     = query_limit_range($limit);
        $offsets   = ($page - 1) * $limit;
        $data_list = $this->purchase_order_model->order_history($sku, $limit, $offsets, $page);
        $this->success_json($data_list);

    }

    /**
     * 批量编辑采购单
     * @author Jolon
     */
    public function get_batch_edit_order()
    {
        $time = [];
        $time['start'] = $this->get_microtime();
        $purchase_numbers = $this->input->post_get('purchase_numbers'); //勾选数据
        if (empty($purchase_numbers)) {
            $this->error_json('请勾选数据');
        }
        if (!is_array($purchase_numbers)) {
            $this->error_json('数据格式不合法[必须是数组]');
        }

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list_tmp = $this->Warehouse_model->get_pertain_wms_list();
        $pertain_wms_list = arrayKeyToColumn($warehouse_list_tmp,'warehouse_code');
        $result = $this->purchase_order_edit_model->get_batch_edit_order($purchase_numbers);
        $time['end'] = $this->get_microtime();
        if ($result['code']) {
            $this->success_json(['data' => $result['data'],'pertain_wms_list' => $pertain_wms_list, 'time' => $time]);
        }
        $this->error_json($result['message']);
    }

    /**
     * 验证 确然提交信息是否完整
     * @author Jolon
     * @param $value
     * exp:
     *    $value => array(
     *       [purchase_number]          => ABD000096
     *       [pay_type]                 => 2
     *       [settlement_ratio]         => 10%+90%
     *       [shipping_method_id]       => 2
     *       [plan_product_arrive_time] => 2019-04-06 12:12:12
     *       [is_freight]               => 1
     *       [freight]                  => 0.000
     *       [discount]                 => 0.000
     *       [freight_formula_mode]     => weight
     *       [purchase_acccount]        => ''
     *       [pai_number]               => ''
     *   )
     * @return bool
     */
    public function check_edit_data($value){
        if(1){// 只是保存时  不验证必填数据
            if(empty($value['pay_type'])
                or empty($value['settlement_ratio'])
                or empty($value['shipping_method_id'])
                or empty($value['plan_product_arrive_time'])
                or empty($value['is_freight'])
                or empty($value['freight_formula_mode'])
                or empty($value['account_type'])){
                return '支付方式、结算比例、运输方式、预计到货时间、运费支付、运费计算方式不能为空';
            }
            if($value['pay_type'] == PURCHASE_PAY_TYPE_ALIPAY){
                if(empty($value['purchase_acccount'])
                    or empty($value['pai_number'])){
                    return '网采单、网拍账号、拍单号 不能为空';
                }
            }
//            if($value['is_freight'] == PURCHASE_FREIGHT_PAYMENT_A and (!isset($value['freight'])  or ($value['freight'] == '' or empty($value['freight'])))){
//                return '选择甲方支付时，必须填写运费';
//            }
            $freight = isset($value['freight'])?$value['freight']:0;
            if($value['is_freight'] == PURCHASE_FREIGHT_PAYMENT_B and floatval($freight) != 0){
                return '选择乙方支付时，不能填写运费';
            }

            if (!is_two_decimal($freight)){
                return '运费最多为两位小数';
            }

            if (isset($value['discount']) && !is_two_decimal($value['discount'])){
                return '优惠最多为两位小数';
            }
            if (isset($value['process_cost']) && !is_two_decimal($value['process_cost'])) {
                return '加工费最多为两位小数';
            }
        }

        return true;
    }

    /**
     * 批量编辑采购单 - 保存或确认提交
     * @author Jolon
     */
    public function save_batch_edit_order(){
        $contentType = !empty($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        if (strpos($contentType, 'json') > 0) {
            $params = file_get_contents('php://input');
            $params = json_decode($params, true);

            $is_submit  = isset($params['is_submit'])?$params['is_submit']:0;
            $purchasing_order_audit = isset($params['purchasing_order_audit'])?$params['purchasing_order_audit']:2;
            $data = $params['data'];
        }

        if(!isset($data) or empty($data)){
            $this->error_json('数据提交格式错误');
        }
        $data_tmp = [];

        $purchaseNumberSkus = [];
        foreach($data as $key=>$value){
            if(!isset($purchaseNumberSkus['purchase_number']))$purchaseNumberSkus[$value['purchase_number']] = [];
            $purchaseNumberSkus[$value['purchase_number']][] = $value['sku'];
        }
        $purchaseKeys = array_keys($purchaseNumberSkus);
        // 获取采购单的业务线合是否推送信息
//        if(!empty($purchaseKeys))$purchaseKeysDatas = $this->purchase_order_model->getPurchaseNumberData($purchaseKeys);

        // 获取仓库信息
        $warehouseCodes = array_column($data,"warehouse_code");
        if(!empty($warehouseCodes))$warehouseIsDrawback = $this->Warehouse_model->getWarehouseData($warehouseCodes);

        // 获取锁单中的备货单
        $demand_lock = $this->purchase_order_edit_model->get_demand_lock_data(array_column($data, 'purchase_number'), array_column($data, 'sku'));
        if(count($demand_lock) > 0)$this->error_json("备货单:".implode(",", $demand_lock)." 锁单中");

        // 获取备货单信息
//        $sug_extend =  $this->purchase_order_extend_model->get_suggest_info(array_column($data, 'purchase_number'), array_column($data, 'sku'));

        foreach ($data as $value) {
            // 预计到货时间限定:1.不能选择当前时间之前的日期,
            //2.国内仓/FBA/PFB业务线--时间填写范围之内是当天至60天内；海外仓业务线--时间填写范围之内是当天至90天内，
            //提交时进行数据校验。不满足上述任何一条的即报错
            if( $value['plan_product_arrive_time'] < date("Y-m-d H:i:s") && $value['shipment_type'] == 1){
                $this->error_json("采购单号:".$value['purchase_number'].",预计到货时间不能小于当前时间");
                exit;
            }

            if( $value['es_shipment_time'] < date("Y-m-d H:i:s") && $value['shipment_type'] == 1){
                $this->error_json("采购单号:".$value['purchase_number'].",预计发货时间不能小于当前时间");
                exit;
            }
            if( isset($value['purchase_type_id'])){
                $proveData = $value['purchase_type_id'] !=2 ? 60: 90;
                $proveData = date('Y-m-d H:m:s', strtotime("+{$proveData} days"));
                if( strtotime($value['plan_product_arrive_time']) > strtotime($proveData) && $value['shipment_type'] == 1){
                    $this->error_json("采购单号:".$value['purchase_number'].",预计到货时间不能大于".$proveData);
                    exit;
                }

                if( strtotime($value['es_shipment_time']) > strtotime($proveData) && $value['shipment_type'] == 1){
                    $this->error_json("采购单号:".$value['purchase_number'].",预计发货时间不能大于".$proveData);
                    exit;
                }
            }
            if (!isset($value['purchase_number']) or !isset($value['sku'])) {
                $this->error_json('采购单号和SKU缺失');
            }
            $purchase_number = $value['purchase_number'];
            $nowisdrawback =  ($value['is_drawback'] == 2)?0:$value['is_drawback'];

            if(isset($warehouseIsDrawback[$value['warehouse_code']])){
                if($warehouseIsDrawback[$value['warehouse_code']]['is_drawback']!=$nowisdrawback){
                    $this->error_json("备货单:".$value['purchase_number']."是否退税与仓库的对应关系错误");
                }
            }
            if ($is_submit == 1) {
                $settlement_ratio_sum = explode('+', $value['settlement_ratio']);
                $settlement_ratio_res = 0;

                foreach ($settlement_ratio_sum as $ratio) {
                    $settlement_ratio_res += (int)($ratio);
                }
                if ($settlement_ratio_res != 100)
                    $this->error_json('采购单[' . $purchase_number . ']结算比例总和只能为100%');

                $result = $this->check_edit_data($value);

                if ($result !== true) {
                    $this->error_json($result);
                }
                //针对于“备货单业务线=国内/FBA/PFB/平台头程”的数据，恢复编辑采购数量

              /*  if ($value['confirm_amount'] == 0) {
                    $this->error_json('采购单[' . $purchase_number . '],sku[' . $value['sku'] . ']采购数量为0，请重新编辑');
                }*/
            }
            // 判断SKU 是否处于锁单状态
            if (!isset($data_tmp[$purchase_number])) {
                $data_tmp[$purchase_number]['purchase_number'] = $purchase_number;
                $data_tmp[$purchase_number]['pay_type'] = intval($value['pay_type']);
                $data_tmp[$purchase_number]['settlement_ratio'] = strval($value['settlement_ratio']);
                $data_tmp[$purchase_number]['shipping_method_id'] = strval($value['shipping_method_id']);
                $data_tmp[$purchase_number]['plan_product_arrive_time'] = strval($value['plan_product_arrive_time']);
                $data_tmp[$purchase_number]['is_freight'] = strval($value['is_freight']);
                $data_tmp[$purchase_number]['freight'] = 0;
                $data_tmp[$purchase_number]['discount'] = 0;
                $data_tmp[$purchase_number]['process_cost'] = 0;
                $data_tmp[$purchase_number]['freight_formula_mode'] = strval($value['freight_formula_mode']);
                $data_tmp[$purchase_number]['purchase_acccount'] = strval($value['purchase_acccount']);
                $data_tmp[$purchase_number]['pai_number'] = strval($value['pai_number']);
                $data_tmp[$purchase_number]['warehouse_code'] = strval($value['warehouse_code']);
                $data_tmp[$purchase_number]['supplier_code'] = strval($value['supplier_code']);
                $data_tmp[$purchase_number]['purchasing_order_audit'] = intval($purchasing_order_audit);
                $data_tmp[$purchase_number]['freight_note'] = isset($value['freight_note']) ? $value['freight_note'] : '';//运费说明
                $data_tmp[$purchase_number]['shipment_type'] = isset($value['shipment_type']) ? $value['shipment_type'] : 2;//发运类型
                $data_tmp[$purchase_number]['account_type'] = isset($value['account_type']) ? $value['account_type'] : '';//结算方式
                $data_tmp[$purchase_number]['is_drawback'] =isset($value['is_drawback'])? $value['is_drawback']:NULL; // 是否退税
            }

            $data_tmp[$purchase_number]['items_list'][] = [
                'sku' => $value['sku'],
                'confirm_amount' => $value['confirm_amount'],
                'tax_rate' => $value['tax_rate'],
                'modify_remark' => isset($value['modify_remark']) ? $value['modify_remark'] : '',
                'freight' => 0,
                'discount' => 0,
                'process_cost' => 0,
                'purchase_unit_price' => isset($value['purchase_unit_price']) ? $value['purchase_unit_price'] : 0,
                'coupon_rate' => isset($value['coupon_rate']) ? $value['coupon_rate'] : 0.000,
                'plan_arrive_time' => isset($value['plan_product_arrive_time']) ? $value['plan_product_arrive_time'] : '', //预计到货日期
                'es_shipment_time' => isset($value['es_shipment_time'])?$value['es_shipment_time']:'', // 预计发货时间
                'warehouse_code' => $value['warehouse_code'],
                'product_base_price' => $value['product_base_price'],
            ];

            $data_tmp[$purchase_number]['freight'] = isset($value['freight']) ? $value['freight'] : 0;//总运费
            $data_tmp[$purchase_number]['discount'] = isset($value['discount']) ? $value['discount'] : 0;//总优惠
            $data_tmp[$purchase_number]['process_cost'] = isset($value['process_cost']) ? $value['process_cost'] : 0;//总加工费
        }
        $result = $this->purchase_order_edit_model->save_batch_edit_order($data_tmp, $is_submit);
        if ($result['code']) {
            $this->success_json($result['data']);
        }
        $this->error_json($result['message']);
    }

    /**
     * 确认提交信息
     * http://www.caigou.com/purchase/purchase_order/get_confirmation_information
     * @author harvin 2019-1-9
     * * */
    public function get_confirmation_information() {
        $purchase_numbers = $this->input->post_get('purchase_numbers'); //勾选数据
        if(empty($purchase_numbers)){
            $this->error_json('请勾选数据');
        }
        if(!is_array($purchase_numbers)){
            $this->error_json('数据格式不合法[必须是数组]');
        }
        $result = $this->purchase_order_edit_model->get_batch_edit_order($purchase_numbers);
        if(empty($result['code'])){
            $this->error_json($result['message']);
        }
        $result = $result['data'];

        $purchasing_order_audit = $this->input->post_get('purchasing_order_audit');

        $data_tmp = [];
        foreach($result as $value){
            $purchase_number                       = $value['purchase_number'];

            // 拼装数据 - 验证数据是否完整
            $value_tmp                             = [];
            $value_tmp['purchase_number']          = $value['purchase_number'];
            $value_tmp['pay_type']                 = $value['pay_type'];
            $value_tmp['settlement_ratio']         = $value['settlement_ratio'];
            $value_tmp['shipping_method_id']       = $value['shipping_method_id'];
            $value_tmp['plan_product_arrive_time'] = $value['plan_product_arrive_time'];
            $value_tmp['is_freight']               = $value['is_freight'];
            $value_tmp['freight']                  = isset($value['freight'])?$value['freight']:0;
            $value_tmp['discount']                 = isset($value['discount'])?$value['discount']:0;
            $value_tmp['freight_formula_mode']     = $value['freight_formula_mode'];
            $value_tmp['purchase_acccount']        = $value['purchase_acccount'];
            $value_tmp['pai_number']               = $value['pai_number'];
            $value_tmp['warehouse_code']           = $value['warehouse_code'];
            $value_tmp['purchasing_order_audit']   = $purchasing_order_audit;
            $result = $this->check_edit_data($value_tmp);
            if($result !== true){// 数据不完整 提示错误
                $this->error_json($result);
            }

            // 组装要保存的数据
            $data_tmp[$purchase_number] = $value_tmp;
            foreach($value['items_list'] as $item_value){
                $data_tmp[$purchase_number]['items_list'][] = [
                    'sku'            => $item_value['sku'],
                    'confirm_amount' => $item_value['confirm_amount'],
                    'tax_rate'       => $item_value['tax_rate'],
                ];
            }
        }

        $result = $this->purchase_order_edit_model->save_batch_edit_order($data_tmp,1);

        if($result['code']){
            $this->success_json();
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 撤销提交的信息
     * @author harvin
     * 2019-1-10
     * http://www.caigou.com/purchase/purchase_order/get_revoke_order
     * * */
    public function get_revoke_order(){
        $ids = $this->input->post_get('ids'); //勾选数据
        if(empty($ids)){
            $this->error_json('请勾选数据');
        }

        if(!is_string($ids)){
            $this->error_json('数据格式不合法');
        }
        //判断采购的订单的状态 必须是 待采购审核 21
        //转化数组
        $ids = explode(',', $ids);
        //判断订单的状态3
        $bool = $this->purchase_order_determine_model->order_status($ids, [PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT]);
        if(!$bool){
            $this->error_json('只能是待采购经理审核状态，才能操作');
        }
        //还原之前采购单的状态
        $res = $this->purchase_order_determine_model->order_status_save($ids);
        if($res['bool']){
            $this->success_json([], null, '恭喜您,撤销信息提交成功');
        }else{
            $this->error_json($res['msg'].'撤销失败');
        }
    }

    // 获取用户的职位信息
    // 角色标识说明: admin: 表示超级管理员,headman:组长,director:主管,manager:经理,deputy_manager:副经理,majordomo:总监

    private function get_user_jobs($uid)
    {
        $this->user->_init($uid);
        $jobs = $this->user->getActiveRole();
        if( !empty($jobs) && !empty($jobs['role_data'])){
//            $jobs['role_data'][0]['en_name']='majordomo';
            $job_name = array_filter(array_column( $jobs['role_data'],"en_name"));
            $prices = [];

            // 如果是组长,拥有 headman:组长
            if(  in_array('headman',$job_name) )
            {
                $prices = $this->purchase_order_model->get_account_price('headman','headman');
            }


            // 如果是主管,拥有 headman:组长,director:主管
            if( in_array('executive_director',$job_name))
            {
                $prices = $this->purchase_order_model->get_account_price('headman','executive_director');
            }


            // 如果角色信息包含副经理的角色,拥有 headman:组长,director:主管  deputy_manager:副经理 审核金额区间
            if( in_array('deputy_manager',$job_name))
            {
                $prices = $this->purchase_order_model->get_account_price('headman','deputy_manager');
            }

            // 如果角色信息包含经理的角色,拥有 headman:组长,director:主管  deputy_manager:副经理,manager 经理 审核金额区间
            if( in_array('purchasing_manager',$job_name))
            {
                $prices = $this->purchase_order_model->get_account_price('headman','purchasing_manager');
            }
            // 如果是总监,拥有 headman:组长,director:主管  deputy_manager:副经理,manager 经理,majordomo 总监
            if( in_array('supplier_director',$job_name) || in_array('admin',$job_name))
            {
                $prices = True;
            }


            return $prices;

        }
    }
    /**
     * 审核采购单--显示（批量）
     * @author harvin
     * @data 2019-5-13
     */
    public function batch_audit_order_list()
    {
        $uid = $this->input->get_post('uid');
        $getPrices = $this->get_user_jobs($uid);
        if(empty($getPrices) && $getPrices != True){
            $this->error_json('请配置相关审核权限');
        }
        $purchase_number_list = $this->input->get_post('purchase_number'); //采购单号

        if (empty($purchase_number_list)) {
            $this->error_json('请勾选数据');
        }
        $purchase_query = explode(',', $purchase_number_list);

        //判断是否是待采购经理审核状态
        $data_status = $data = $order_number_error = $order_price_error = [];

        $this->load->model('purchase_examine_model');
        $pur_data = $this->purchase_examine_model->get_order($purchase_query);
        if(empty($pur_data))$this->error_json('未获取到相应的审核数据');

        foreach ($purchase_query as $purchase_number) {
            $val = [];
            foreach ($pur_data as $k=>$v) {
                if($k == $purchase_number)$val = $v;
            }
            //根据业务线来判断
            if (!empty($getPrices)) {
                if (in_array($val['purchase_type_id'], [PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])) {
                    $prices = $getPrices[5]??'';
                } else {
                    $prices = $getPrices[2]??'';
                }
            }

            if (isset($val['items_list']) && !empty($val['items_list'])) {
                $total_price = 0;
                foreach ($val['items_list'] AS $list_key => $list_value) {
                    $total_price += ($list_value['confirm_amount'] * $list_value['purchase_unit_price'] + $list_value['freight'] - $list_value['discount'] + $list_value['process_cost']);
                }
                if (is_array($prices) && !empty($prices)) {
                    if ($prices['start'] > $total_price || $prices['end'] < $total_price) {
                        $order_price_error[] = $purchase_number;
                    }
                }
            }

            if(empty($val))$data[] = $purchase_number;

            if (isset($val['purchase_order_status']) && $val['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT) {
                $data_status[] = $purchase_number;
            }

            if (!empty($val)) {
                $order_number = $this->purchase_order_model->temporary_supplier_order_number($val['supplier_code'], $val['source']);
                if ($order_number) {
                    $order_number_error[] = $purchase_number . '供应商:' . $val['supplier_name'];
                }
            }
        }

        if (!empty($order_number_error)) {
            $error = '不满足审核条件。临时供应商最多只能下20个po，若要再下单请转常规供应商，或者绑定新供应商';
            $msg = '采购单号' . implode(',', $order_number_error) . $error;
            $this->error_json($msg);
        }

        if (!empty($data)) {
            $msg = '采购单号' . implode('--', $data) . "不存在";
            $this->error_json($msg);
        }

        if (!empty($data_status)) {
            $msg = '采购单号' . implode('--', $data_status) . "不是待采购经理审核状态";
            $this->error_json($msg);
        }

        if (!empty($order_price_error)) {
            $msg = '采购单号' . implode('--', $order_price_error) . "金额超出审核范围";
            $this->error_json($msg);
        }

        //获取需要数据
        $temp = $this->purchase_order_model->get_batch_audit_order_list($purchase_query);
        if ($temp['bool']) {
            $this->success_json($temp['data']);
        } else {
            $this->error_json($temp['msg']);
        }
    }



    /**
     * 审核采购单
     * @author Jolon
     */
    public function audit_order()
    {
        $ids = $this->input->get_post('id');
        $check_status = $this->input->get_post('check_status');// 1.审核通过，2.审核驳回
        $reject_order_note = $this->input->get_post('reject_order_note');// 驳回原因
        //转化数组
        $id = explode(',', $ids);

        if (empty($id) || !is_array($id) || empty($check_status)) {
            $this->error_json('数据【id或check_status】缺失');
        }
        if ($check_status == 2 && empty($reject_order_note)) {
            $this->error_json('驳回必须填写驳回原因');
        }

        $errorMess = '默认失败！';
        $this->purchase_order_model->purchase_db->trans_begin();

        try {
            $this->load->model('finance/purchase_order_pay_type_model');
            $this->load->model('purchase_examine_model');
            $audit_data = $this->purchase_examine_model->get_examine_data([], $ids, $check_status);

            if(isset($audit_data['error']) && !empty($audit_data['error'])){
                throw new Exception($audit_data['error']);
            }

            if(isset($audit_data['success']) && !empty($audit_data['success'])){
                foreach ($audit_data['success'] as $val){
                    $pur = $val['purchase_number'];
                    $result = $this->purchase_order_model->audit_order($pur, $check_status, $reject_order_note, null, $val, false);
                    if (empty($result['code'])) {// 失败
                        throw new Exception($pur . ':' . $result['msg']);
                    }
                    $this->purchase_order_pay_type_model->refresh_order_price($pur);// 刷新采购金额汇总
                }
            }else{
                throw new Exception('未获取到相应的采购单数据！');
            }
            $this->purchase_order_model->purchase_db->trans_commit();
        } catch (\Exception $e) {
            $errorMess = $e->getMessage();
            $this->purchase_order_model->purchase_db->trans_rollback();
        }

        if ($errorMess) {
            $this->error_json($errorMess);
        } else {
            $this->success_json([]);
        }

    }

    /**
     * 审核采购单--保存（批量）
     * @author harvin
     * @data 2019-5-13
     */
    public function batch_audit_order_save()
    {
        $logid = date('YmdHis').rand(0, 100);
        $purchase_number_list = $this->input->get_post('purchase_number'); //采购单号
        $purchase_numbers = array_unique(explode(',', $purchase_number_list));
        $check_status = $this->input->get_post('check_status');// 1.审核通过，2.审核驳回
        $reject_order_note = $this->input->get_post('reject_order_note');// 驳回原因

        $log_file = APPPATH . 'logs/batch_audit_order_save_'.date('Ymd').'.txt';
        file_put_contents($log_file, $this->get_microtime() . "**start {$logid} {$purchase_number_list} batch_audit_order_save******\n", FILE_APPEND);
        if ($check_status == 2 && empty($reject_order_note)) $this->error_json('驳回备注不能为空');

        $this->purchase_order_model->purchase_db->trans_begin();
        $errorMess = '';
        try {
            $this->load->model('finance/purchase_order_pay_type_model');
            $this->load->model('purchase_examine_model');
            $audit_data = $this->purchase_examine_model->get_examine_data($purchase_numbers, [], $check_status);

            if(isset($audit_data['error']) && !empty($audit_data['error'])){
                throw new Exception($audit_data['error']);
            }

            if(isset($audit_data['success']) && !empty($audit_data['success'])){
                foreach ($audit_data['success'] as $val){
                    $pur = $val['purchase_number'];
                    $result = $this->purchase_order_model->audit_order($pur, $check_status, $reject_order_note, null, $val, false, $logid);
                    if (empty($result['code'])) {// 失败
                        throw new Exception($pur . ':' . $result['msg']);
                    }
                    $this->purchase_order_pay_type_model->refresh_order_price($pur);// 刷新采购金额汇总
                }
            }else{
                throw new Exception('未获取到相应的采购单数据！');
            }
            $this->purchase_order_model->purchase_db->trans_commit();
        } catch (\Exception $e) {
            $errorMess = $e->getMessage();
             $this->purchase_order_model->purchase_db->trans_rollback();
        }

        if (empty($errorMess)) {
            $this->success_json([], null, '审核成功');
        } else {
            $this->error_json($errorMess);
        }
    }

    /**
     * 获取蓝凌 唯一ID （采购单走蓝凌审核接口）
     * @author harvin
     */
    public function audit_orders(){
        $id        = $this->input->get_post('id');
        $processid = $this->purchase_order_model->get_audit_orders($id);
        $this->success_json($processid);
    }

    /**
     * 驳回采购单（采购单管理界面-驳回操作  驳回后直接变为已作废，无需审核）
     * @author Jolon
     */
    public function reject_order(){
        $purchase_numbers  = $this->input->get_post('purchase_numbers');
        $reject_order_note = $this->input->get_post('reject_order_note');
        $remark            = $this->input->get_post('remark');
        if($remark)
            $reject_order_note .= $remark;// 合并驳回原因和备注

        if($purchase_numbers and is_array($purchase_numbers) and $reject_order_note){
            $purchase_numbers = array_unique($purchase_numbers);

            $errorMess = '';
            $this->purchase_order_model->purchase_db->trans_begin();
            try{
                foreach($purchase_numbers as $purchase_number){
                    $result = $this->purchase_order_model->reject_order($purchase_number, $reject_order_note);

                    if(empty($result['code'])){// 失败
                        throw new Exception($purchase_number.':'.$result['msg']);
                    }
                }
                $this->purchase_order_model->purchase_db->trans_commit();
            }catch(\Exception $e){
                $errorMess = $e->getMessage();
                $this->purchase_order_model->purchase_db->trans_rollback();
            }

            if($errorMess){
                $this->error_json($errorMess);
            }else{
                $this->success_json([]);
            }
        }else{
            $this->error_json('参数【purchase_numbers 或 reject_order_note】缺失');
        }
    }

    /**
     * 作废采购单（采购单管理界面-作废操作）
     * @author Jolon
     */
    public function cancel_order(){
        $purchase_numbers  = $this->input->get_post('purchase_numbers');
        $cancel_order_note = $this->input->get_post('cancel_order_note');

        if($purchase_numbers and is_array($purchase_numbers)){
            $purchase_numbers = array_unique($purchase_numbers);

            $errorMess = '';
            $this->purchase_order_model->purchase_db->trans_begin();
            try{
                foreach($purchase_numbers as $purchase_number){
                    $result = $this->purchase_order_model->cancel_order($purchase_number, $cancel_order_note);

                    if(empty($result['code'])){// 失败
                        throw new Exception($purchase_number.':'.$result['msg']);
                    }
                }
                $this->supplier_joint_model->pushPurchaseCancel($purchase_numbers);
                $this->purchase_order_model->purchase_db->trans_commit();
            }catch(\Exception $e){
                $errorMess = $e->getMessage();
                $this->purchase_order_model->purchase_db->trans_rollback();
            }

            if($errorMess){
                $this->error_json($errorMess);
            }else{
                $this->success_json([]);
            }
        }else{
            $this->error_json('采购单号缺失');
        }
    }

    /**
     * 作废采购单（采购单管理界面-作废操作-以备货单维度）
     * @author Jolon
     */
    public function cancel_order_by_demand_number(){
        $purchase_numbers  = $this->input->get_post('purchase_numbers');
        $cancel_order_note = $this->input->get_post('cancel_order_note');
        $demand_numbers    = $this->input->get_post('demand_numbers');
        $cancel_reason_category = $this->input->get_post('cancel_reason_category');//作废原因类别 pur_reason_config 数据

        if (empty($purchase_numbers) || !is_array($purchase_numbers)) $this->error_json('采购单号不正确');
        if (empty($demand_numbers) || !is_array($demand_numbers)) $this->error_json('备货单号不正确');
//        if (empty($cancel_order_note)) $this->error_json('作废原因必填');
        if(empty($cancel_reason_category)) $this->error_json('作废原因类别必选');

        // 21197 网拍账号=1688账号的，作废整个po下的所有备货单时，验证拍单号是否关闭交易,未关闭不能提交
        foreach ($purchase_numbers as $key){
            $cancel_demand_all = $this->purchase_order_model->purchase_db->from("purchase_suggest_map as psm")
                ->select('pps.demand_number, pps.suggest_status')
                ->join('pur_purchase_suggest as pps', 'on psm.demand_number=pps.demand_number', 'left')
                ->where('psm.purchase_number', $key)->get()->result_array();
            $is_continue = false;
            foreach ($cancel_demand_all as $val){
                // 没提交的备货单号，且状态不为作废，即不用验证，剩下的全部验证
                if(!in_array($val['demand_number'], $demand_numbers) && $val['suggest_status'] != 4){
                    $is_continue = true;
                    break;
                }
            }
            if($is_continue)continue;
            $status = $this->purchase_order_model->purchase_db
                ->from("purchase_order")
                ->select('ali_order_status')
                ->where(["is_ali_order" => 1, 'purchase_number' => $key])
                ->where("ali_order_status != ", "交易取消")
                ->get()->result_array();
            if($status && count($status) > 0){
                $this->error_json("订单：".$key."，不是‘交易取消’状态，不能作废！");
            }
        }

        $errorMess = '';
        $this->purchase_order_model->purchase_db->trans_begin();
        try{
            foreach($purchase_numbers as $key => $purchase_number){
                $result = $this->purchase_order_model->cancel_order_v2($purchase_number, $cancel_order_note, $demand_numbers[$key], $cancel_reason_category);

                if(empty($result['code'])){// 失败
                    throw new Exception($purchase_number.':'.$result['msg']);
                }
            }
            $this->supplier_joint_model->pushPurchaseCancel($purchase_numbers);
            $this->purchase_order_model->purchase_db->trans_commit();
        }catch(\Exception $e){
            $errorMess = $e->getMessage();
            $this->purchase_order_model->purchase_db->trans_rollback();
        }

        if($errorMess){
            $this->error_json($errorMess);
        }else{
            $this->success_json([]);
        }

    }

    /**
     * 编辑显示采购单列表字段显示
     * @author harvin 2019-1-8
     * http://www.caigou.com/purchase/purchase_order/get_table_boy
     * * */
    public function get_table_boy(){
        $data_list = $this->purchase_order_model->table_columns();
        $this->success_json($data_list);
    }
    
    //获取自定义表头
    public function get_set_table_header()
    {
        /*
        $list_type  = $this->input->get_post('list_type');
        if (empty($list_type)) $this->error_json('列表类型缺失');
        $table_columns = $this->purchase_order_model->table_columns();
        //pur_list_header 表里查询,没有则给到权限里的表头,再没有,则给到默认表头
        $data_list = $this->purchase_order_model->get_set_table_header($list_type,$table_columns);

        $this->success_json($data_list);
        */
        $list_type = $this->input->get_post('list_type', 1);
        if (empty($list_type)) $this->error_json('列表类型缺失');
        $data = $this->purchase_order_new_model->get_set_table_header($list_type);
        $data_list = [];
        if($data['code'] == 1){
            $data_list = $data['msg'];
        }else{
            $data_list = $this->purchase_order_new_model->get_purchase_list_def();
        }
        $this->success_json($data_list);
    }

    /**
     * 保存编辑显示采购单列表字段显示
     * @author harvin
     * http://www.caigou.com/purchase/purchase_order/get_table_save
     * * */
    public function save_table_list()
    {
        /*
        $data = $this->input->post_get('order_initial'); //数组格式
        $type = $this->input->post_get('list_type'); //列表类型
        if (empty($type)) $this->error_json('列表类型缺失');
        if (empty($data)) $this->error_json('列表数据缺失');
        $res = $this->purchase_order_model->save_table_list($data,$type);
        if($res){
            $this->success_json([], null, '编辑成功');
        }else{
            $this->error_json('编辑失败');
        }
        */
        $req_data = $this->input->post_get('order_initial'); //数组格式
        $type = $this->input->post_get('list_type', 1); //列表类型
        if (empty($type)) $this->error_json('列表类型缺失');
        if (empty($req_data)) $this->error_json('列表数据缺失');
        $req_data = json_decode($req_data,true);
        $res = $this->purchase_order_new_model->save_table_list($req_data, $type);
        if ($res && $res['code'] == 1) {
            $this->success_json([], null, '编辑成功');
        } else {
            $this->error_json('编辑失败');
        }
    }

    /**
     * 保存编辑显示采购单列表字段显示
     * @author harvin
     * http://www.caigou.com/purchase/purchase_order/get_table_save
     * * */
    public function get_table_save()
    {
        /*
        $data = $this->input->post_get('order_initial'); //数组格式
        $data = json_encode($data);
        //保存表里
        $res = $this->purchase_order_model->table_save($data);
        if($res){
            $this->success_json([], null, '编辑成功');
        }else{
            $this->error_json('编辑失败');
        }
        */
        $order_initial = $this->input->post_get('order_initial'); //数组格式
        $order_initial = json_encode($order_initial);
        $base = $this->purchase_order_model->table_columns();
        $initial = $this->purchase_order_new_model->get_header_data_group($base, $order_initial, 2);
        $data = [];
        if($initial['code'] == 1)$data = $initial['data'];
        //保存表里
        $res = $this->purchase_order_model->table_save($data);
        if ($res) {
            $this->success_json([], null, '编辑成功');
        } else {
            $this->error_json('编辑失败');
        }
    }

    /**
     * 采购单列表搜索框配置-查看
     * @author Jolon
     * http://www.caigou.com/purchase/purchase_order/get_table_search_header
     */
    public function get_table_search_header()
    {
        $list_type = $this->input->get_post('list_type');
        if (empty($list_type)) $this->error_json('列表类型缺失');
        $data_list = $this->purchase_order_new_model->get_set_search_table_header($list_type);
        if($data_list && isset($data_list['code']) && $data_list['code'] == 1 && isset($data_list['msg']))$this->success_json($data_list['msg']);
        $this->error_json('获取数据失败');
    }

    /**
     * 采购单列表搜索框配置-保存
     * @author Jolo
     * http://www.caigou.com/purchase/purchase_order/save_table_search_header
     */
    public function save_table_search_header()
    {
        $data = $this->input->post_get('order_initial'); //数组格式
        $type = $this->input->post_get('list_type'); //列表类型
        $uid = $this->input->post_get('uid'); //列表类型
//        $type = 2; //列表类型
        if (empty($type)) $this->error_json('列表类型缺失');
        if (empty($data) or !is_json($data)) $this->error_json('列表数据缺失或不是JSON');

        $data = json_decode($data, true);
        // 必选字段
        if (!isset($data['purchase_order_status']['status']) or empty($data['purchase_order_status']['status'])) {
            $data['purchase_order_status'] = array('index' => 0, 'status' => 1, 'name' => '订单状态');
        }
        if (!isset($data['suggest_order_status']['status']) or empty($data['suggest_order_status']['status'])) {
            $data['suggest_order_status'] = array('index' => 1, 'status' => 1, 'name' => '备货单状态');
        }
        if (!isset($data['purchase_number']['status']) or empty($data['purchase_number']['status'])) {
            $data['purchase_number'] = array('index' => 3, 'status' => 1, 'name' => '采购单号');
        }
        if (!isset($data['create_time']['status']) or empty($data['create_time']['status'])) {
            $data['create_time'] = array('index' => 4, 'status' => 1, 'name' => '创建日期');
        }
        $data = json_encode($data);

        $res = $this->purchase_order_model->save_table_list($data, $type, $uid);//保存表里
        if ($res) {
            $this->success_json([], null, '编辑成功');
        } else {
            $this->error_json('编辑失败');
        }
    }

    /**
     * 显示采购单绑定物流单号
     * 勾选多条采购单 显示出勾选的采购单的
     * @author harvin 2019-1-9
     * @author Manson 2019-10-30
     * http://www.caigou.com/purchase/purchase_order/order_binding_logistics
     * * */
    public function order_binding_logistics(){
        $this->load->model('Logistics_carrier_model', 'm_logistics', false, 'purchase');
        $ids = $this->input->post_get('ids'); //勾选数据
        if(empty($ids)){
            $this->error_json('请勾选数据');
        }
        $ids = explode(',', $ids);

        if(count($ids) > 0){
            $has = $this->purchase_order_model->purchase_db->from("purchase_order_items as i")
                ->join("pur_purchase_order as o", "i.purchase_number=o.purchase_number", "inner")
                ->select("o.supplier_code,o.pertain_wms")
                ->where_in("i.id", $ids)
                ->group_by("o.supplier_code,o.pertain_wms")
                ->get()
                ->result_array();
            if($has && count($has) > 1){
                $this->error_json('多条数据勾选时，供应商+公共仓维度一致才能点击录入单号。');
                return;
            }
        }

        //获取对应的采购单及sku
        $this->data['data_list'] = $this->purchase_order_model->get_order_binding_logistics($ids);
        $this->data['drop_down_box']['cargo_company'] = $this->m_logistics->getLogisticsCompany();
        if(empty($this->data)){
            $this->error_json('获取信息失败');
        }else{
            $this->success_json($this->data);
        }
    }

    /**
     * 保存相物流单号关信息
     * http://www.caigou.com/purchase/purchase_order/order_binding_logistics_save
     * @author harvin 2019-1-8
     * @author Manson 2019-10-30
     * * */
    public function order_binding_logistics_save(){
        try{
            $logistics_info  = $this->input->post_get('logistics_info'); //物流公司 物流单号
            $order_info       = $this->input->post_get('order_info'); //采购单号 sku

            if (empty($logistics_info)){
                $delete = true;
            }else{
                $delete = false;
            }
            foreach ($order_info as $key => $item){
                $delete_data[] = [
                    'sku' => $item['sku'],
                    'purchase_number' => $item['purchase_number']
                ];

                $express_temp = array_column(array_values($logistics_info), 'express_no');
                if(count($express_temp)>0){
                    /*
                    $has = $this->purchase_order_model->purchase_db->from("purchase_logistics_info")
                        ->where(["purchase_number" => $item['purchase_number'], "sku"=> $item['sku']])
                        ->where_in("express_no", $express_temp)
                        ->get()
                        ->result_array();
                    if($has && count($has) > 0){
                        $this->error_json('该供应商中该sku的其他公共仓已存在相同的快递单号，请检查。');
                        return;
                    }
                    */
                }

                if (!empty($logistics_info)){
                    foreach ($logistics_info as $k => $val){
                        $express_no = $val['express_no'];
                        $express_no = preg_replace("/([\x80-\xff|\s|~|\-|?]*)/i", "", $express_no);
                        $express_no = preg_replace("/\(([^()]|(?R))*\)/", "", $express_no);
                        $insert_data[] = [
                            'express_no' => $express_no,
                            'cargo_company_id' => $val['cargo_company_id'],
                            'carrier_code' => $val['carrier_code'],
                            'purchase_number' => $item['purchase_number'],
                            'sku'=> $item['sku'],
                            'is_manually' => 1
                        ];
                    }
                }else{
                    $insert_data[] = [
                        'express_no' => '',
                        'cargo_company_id' => '',
                        'purchase_number' => $item['purchase_number'],
                        'sku'=> $item['sku'],
                        'is_manually' => 1
                    ];
                }
            }


            $bool = $this->purchase_order_model->get_order_binding_logistics_save($insert_data, $delete_data,$delete);
            if($bool){
                $this->success_json([], null, '录入成功');
            }
        }catch (Exception $e){
            $errorMsg = $e->getMessage();
            $this->error_json($errorMsg);
        }
    }






    /**
     * 打印采购单
     * @author harvin 2019-1-10
     * http://www.caigou.com/purchase/purchase_order/printing_purchase_order
     * * */
    public function printing_purchase_order(){
        $ids = $this->input->post_get('ids'); //勾选数据
        if(empty($ids)){
            $this->error_json('请勾选数据');
        }
        $ids = explode(',', $ids);
        if(!is_array($ids)){
             $this->error_json('请求参数格式错误');
        }
        //查询采购单
        $reslut = $this->purchase_order_determine_model->get_printing_purchase_order($ids);
        if($reslut['code']){
            $this->success_json($reslut['data'],null,$reslut['msg']);
        }else{
            $this->error_json($reslut['msg']);
        }
    }

    /**
     * 返回打印采购单数据
     * @author harvin
     * **/
    public function print_menu(){
        $data = $this->input->get_post('data');
        $uid  = $this->input->get_post('uid');
        if(empty($data) || empty($uid)){
            $this->error_json('缺少参数');
        }
        //获取前端地址html
        $html = $this->purchase_order_determine_model->get_print_menu($data, $uid);
        $this->success_json([$html], '成功');
    }

    /**
     * 下载PDF采购单
     * @author Jaxton 2019/02/26
     * /compact/compact/download_compact
     */
    public function download_purchase_order(){
        $ids = $this->input->get_post('ids');
        $uid = $this->input->get_post('uid');
        if (empty($ids) || empty($uid)) {
            $this->error_json('缺少参数');
        }
       // $data =json_decode($data,true);

        //获取前端地址html


        $ids = explode(',', $ids);
        if (!is_array($ids)) {
            $this->error_json('请求参数格式错误');
        }
        //查询采购单
        $reslut = $this->purchase_order_determine_model->get_printing_purchase_order($ids);
        if ($reslut['code']) {
            $data = $reslut['data'];
        } else {
            $this->error_json($reslut['msg']);
        }


        $html = $this->purchase_order_determine_model->get_order_html($data, $uid);

        $this->success_json(['html' => $html], '成功');

    }

    /**
     * 根据指定的 采购单编号 预览采购单合同数据
     *      生成进货单第一步 - 合同采购确认
     * @author Jolon
     */
    public function compact_confirm_purchase(){
        $purchase_numbers = $this->input->post('purchase_numbers'); // 采购单
        if(empty($purchase_numbers) or !is_array($purchase_numbers)){
            $this->error_json('参数【purchase_numbers】缺失或不是数组');
        }
        $return = $this->purchase_order_model->check_purchase_order_is_same_compact($purchase_numbers);
        if($return['code']){
            $compact = $this->purchase_order_model->get_create_compact_data($purchase_numbers); // 获取 合同确认需要的数据

            $this->load->helper('status_finance');
            $this->load->model('finance/payment_order_pay_model');
            $settlement_method = $this->payment_order_pay_model->get_settlement_method(); //结算方式
            // 转成中文
            $account_type                                     = isset($compact['compact_main']['account_type']) ? $compact['compact_main']['account_type'] : '';
            $compact['compact_main']['account_type']          = isset($settlement_method[$account_type]) ? $settlement_method[$account_type] : '';
            // 组合信息
            $freight_formula_mode                             = array_unique(array_column($compact['compact_main']['order_info_list'],'freight_formula_mode'));
            $compact['compact_main']['freight_formula_mode']  = implode('/',array_map('freight_formula_mode',$freight_formula_mode));
            $compact['compact_main']['warehouse_name']        = implode('/',array_unique(array_column($compact['compact_main']['order_info_list'],'warehouse_name')));

            // 数据转成前端需要的格式
            $compact_details = [];
            if($compact['compact_details']){
                foreach($compact['compact_details'] as $value){
                    if(!empty($value) && is_array($value)){
                        foreach ($value as $k => $val) {
                            array_push($compact_details,$val);
                        }    
                    }
                    //$compact_details += array_values($value);
                }
            }
            $compact['compact_details'] = $compact_details;

            $this->success_json($compact);
        }else{
            $this->error_json($return['msg']);
        }
    }

    /**
     * 根据指定的 采购单编号 生成合同模板
     *      生成进货单第二步 - 合同模板确认（在 compact_confirm_purchase 之后）
     * @author Jolon
     */
    public function compact_confirm_template(){
        $purchase_numbers = $this->input->post('purchase_numbers'); // 采购单
        if(empty($purchase_numbers) or !is_array($purchase_numbers)){
            $this->error_json('参数【purchase_numbers】缺失或不是数组');
        }
        $return = $this->purchase_order_model->check_purchase_order_is_same_compact($purchase_numbers);
        if($return['code']){
            $compact = $this->purchase_order_model->get_compact_confirm_template_data($purchase_numbers);
            // 112337 供应商联系方式
            if(!isset($compact['compact_main']['b_linkman']) || empty($compact['compact_main']['b_linkman']) ||
                !isset($compact['compact_main']['b_phone']) || empty($compact['compact_main']['b_phone'])){
                $compact['create_message'] = "供应商：".$compact['compact_main']['b_company_name'].", 联系人/手机号码缺失，请维护后进行";
            }

            $compact['compact_main']['receive_address'] = str_replace($compact['compact_main']['a_phone'],'%s',$compact['compact_main']['receive_address']);
            $this->success_json($compact);
        }else{
            $this->error_json($return['msg']);
        }
    }

    /**
     * 根据指定的 采购单编号 生成采购合同
     *      生成进货单第三步 - 合同创建（在 compact_confirm_template 之后）
     * @author Jolon
     */
    public function compact_create(){
        $purchase_numbers = $this->input->post('purchase_numbers'); // 采购单
        $payment_explain  = $this->input->post('payment_explain'); // 付款说明
        $item_id          = $this->input->post('item_id'); // 规格
        $a_user_phone     = $this->input->post('a_user_phone'); // 联系电话
        if(empty($purchase_numbers) or !is_array($purchase_numbers)){
            $this->error_json('参数【purchase_numbers】缺失或不是数组');
        }
        if($item_id and !is_array($item_id)){
            $this->error_json('规格数据【item_id】必须是数组');
        }
        //        $unvalid_supplier = $this->supplier_joint_model->isValidSupplier($purchase_numbers);
//        if (is_array($unvalid_supplier)) {
//            $unvalid_supplier = implode(',', $unvalid_supplier);
//            $msg = sprintf('供应商%s在供应商门户系统是无效状态', $unvalid_supplier);
//            $this->error_json($msg);
//        }
        //判断是否在供应商门户有效合同单
        $supplier_items = $this->supplier_joint_model->is_valid_status($purchase_numbers);
        if($supplier_items === true){
        }else{
            if(!empty($supplier_items)){
                $supplier_item = implode(',', $supplier_items);
//                $msg = sprintf('采购单%s未确认交期,请确认后再点击', $supplier_item);
//                $this->error_json($msg);
            }
        }

        $post_data = [
            'payment_explain' => $payment_explain,
            'item_id'         => $item_id,
            'a_user_phone'    => $a_user_phone
        ]; // 用户提交的数据

        $this->load->model('compact_model', '', 'false', 'compact');
        $return = $this->purchase_order_model->check_purchase_order_is_same_compact($purchase_numbers);

        if($return['code']){
            $compact_data = $this->purchase_order_model->get_compact_confirm_template_data($purchase_numbers,$post_data);
            // 112337 供应商联系方式
            if(!isset($compact_data['compact_main']['b_linkman']) || empty($compact_data['compact_main']['b_linkman']) ||
                !isset($compact_data['compact_main']['b_phone']) || empty($compact_data['compact_main']['b_phone'])){
                $this->error_json("供应商：".$compact_data['compact_main']['b_company_name'].", 联系人/手机号码缺失，请维护后进行");
            }
            $result       = $this->compact_model->create_compact($compact_data, $post_data);
            if($result['code']){
				foreach ($purchase_numbers as $purchase_number){
					$this->purchase_order_model->purchase_track($purchase_number,PURCHASE_ORDER_STATUS_WAITING_ARRIVAL);
				}
                $purchaseNumberGateWays = $this->purchase_order_model->VerifyPurchaseGateWays($purchase_numbers);
                if(!empty($purchaseNumberGateWays)) {
                    $resul = $this->supplier_joint_model->pushSmcCompactData($purchaseNumberGateWays);
                }
                $this->success_json($result['data']);
            }else{
                $this->error_json($result['msg']);
            }
        }else{
            $this->error_json($return['msg']);
        }
    }

    /**
     * 获取采购单操作日志
     *      2019-02-01
     * @author Jaxton
     * /purchase/purchase_order/get_purchase_operator_log
     */
    public function get_purchase_operator_log(){
        $purchase_number = $this->input->get_post('purchase_number');
        if(empty($purchase_number)){
            $this->error_json('参数【purchase_number】缺失');
        }

        $page  = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if(empty($page) or $page < 0)
            $page = 1;
        $limit  = query_limit_range($limit);
        $offset = ($page - 1) * $limit;

        $result = $this->purchase_order_model->get_purchase_operator_log($purchase_number, $limit, $offset);
        if($result){
            $this->success_json($result);
        }else{
           $this->error_json('没有相关数据'); 
        }
    }



    /**
     * 获取报损界面sku数据
     * 2019-02-01
     * @author Jaxton
     * /purchase/purchase_order/get_reportloss_sku_data
     */
    public function get_reportloss_sku_data(){

        $data = $this->input->get_post('data');//采购单和sku
        if(empty($data)){
            $this->error_json('参数【data】缺失');
        }
        $data   = json_decode($data, true);
        $result = $this->purchase_order_model->get_reportloss_sku_data($data);
        if($result['success']){
            $lossResponsibleParty = getReportlossResponsibleParty();
            $data = ['values' => $result['data'],'down_box_list' => ['lossResponsibleParty' => $lossResponsibleParty]];
            $this->success_json($data);
        }else{
            $this->error_json($result['data']);
        }
    }

    /**
     * 申请报损确认
     * 2019-02-01
     * @author Jaxton
     * /purchase/purchase_order/reportloss_submit
     */
    public function reportloss_submit(){

        $contentType = !empty($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        if (strpos($contentType, 'json') > 0) {
            $params = file_get_contents('php://input');
            $params = json_decode($params, true);
            $data   = $params['data'];
            $remark = $params['remark'];
        }
        $params['data'] = json_decode($params['data'],true);
//        var_dump($params);exit;
        $ss =                 json_encode($params);
        if (!isset($data) or empty($data)) {
            $this->error_json('数据缺失或格式错误');
        }
        if(empty($remark)){
            $this->error_json('参数【remark】缺失');
        }
        $result = $this->purchase_order_model->reportloss_submit($data, $remark);

        if($result['success']){
            if (!empty($result['error_msg'])){
                $this->success_json([], null, $result['error_msg']);
            }else{
                $this->success_json([], null, '操作成功');
            }

        }else{
            $this->error_json($result['error_msg']);
        }
    }


    /**
     * 获取入库记录
     * /purchase/purchase_order/get_storage_record
     */
    public function get_storage_record()
    {
        $purchase_number = $this->input->get_post('purchase_number');
        $sku = $this->input->get_post('sku');
        if (empty($purchase_number) || empty($sku)) {
            $this->error_json('采购单号和sku不可缺少');
        }

        $this->load->model('purchase_order_extend_model');
        $result = $this->purchase_order_extend_model->get_storage_record($purchase_number, $sku);
        if ($result && isset($result['items']) && !empty($result['items'])) {
            foreach ($result['items'] as &$value) {
                unset($value['id'], $value['items_id'], $value['id']);

                $value['quality_all'] = ($value['quality_all'] == 2) ? '是' : '否';
                $value['quality_result'] = ($value['quality_result'] == 2) ? '不合格' : '合格';
                $value['iqc_quality_testing'] = $value['iqc_quality_testing'] == 1 ? '是' : '否';
                $value['is_accumulation'] = $value['is_accumulation'] == 1 ? '是' : '否';
                $value['quality_time'] = $value['quality_time'] == "0000-00-00 00:00:00" ? '-' : $value['quality_time'];
                $value['upper_end_time'] = $value['upper_end_time'] == "0000-00-00 00:00:00" ? '-' : $value['upper_end_time'];
                $value['count_time'] = $value['count_time'] == "0000-00-00 00:00:00" ? '-' : $value['count_time'];
                $value['arrival_date'] = $value['arrival_date'] == "0000-00-00 00:00:00" ? '-' : $value['arrival_date'];
                $value['instock_date'] = $value['instock_date'] == "0000-00-00 00:00:00" ? '-' : $value['instock_date'];
                $value['paste_code_time'] = $value['paste_code_time'] == "0000-00-00 00:00:00" ? '-' : $value['paste_code_time'];
                $value['more_qty'] = $value['instock_qty_more'];
                $value['shipment_type_ch'] = '';
                /*
                if($value['transfer_numbers'] > 0){
                    $value['shipment_type_ch'] = "中转仓发运";
                }
                if($value['traight_numbers'] > 0){
                    $value['shipment_type_ch'] = "工厂直发";
                }
                if($value['transfer_numbers'] > 0 && $value['traight_numbers'] > 0 ){
                    $value['shipment_type_ch'] = "中转仓发运，工厂直发";
                }
                */

                if ($value['upper_end_time'] and $value['upper_end_time'] != '0000-00-00 00:00:00') {
                    $value['aging_time'] = date('Y-m-d H:i:s', strtotime($value['upper_end_time']) + $value['delevery_time_long'] * 3600);// 时效开始时间
                } else {
                    $value['upper_end_time'] = '';
                    $value['aging_time'] = '';// 时效开始时间
                }
            }
        }
        if ($result) {
            $this->success_json($result);
        } else {
            $this->error_json('没有入库数据');
        }
    }


    /**
     * 获取交易订单的物流跟踪信息
     * /purchase/purchase_order/get_logistics_trace_info
     */
    public function get_logistics_trace_info(){
        $purchase_number = $this->input->get_post('purchase_number');
        if(empty($purchase_number)){
            $this->error_json('采购单号不可缺少');
        }
        $this->load->library('alibaba/AliOrderApi');
        $this->load->model('finance/purchase_order_pay_type_model');
        $order_info = $this->purchase_order_pay_type_model->get_one($purchase_number);
        if(empty($order_info) or empty($order_info['pai_number'])){
            $this->error_json('采购单拍单号缺失');
        }
        $result = $this->aliorderapi->getLogisticsTraceInfo($order_info['pai_number']);
        if($result['code'] == 200){
            //返回数据
            $data_list = array();
            //转换对接前端数据格式，兼容通过物流系统接口请求数据格式
            foreach ($result['data'] as $key => $item){
                foreach ($item as $detail){
                    $data_list[$key][] = array(
                        'track_content'=> $detail['remark'],
                        'occur_date'=> $detail['acceptTime'],
                    );
                }
            }
            $this->success_json($data_list);
        }else{
            $this->error_json($result['errorMsg']);
        }
    }

    //获取各种备注
    public function get_note_list()
    {
        $purchase_number = $this->input->get_post('purchase_number');
        $sku = $this->input->get_post('sku');
        if(empty($purchase_number)) $this->error_json('采购单号不可缺少');
        if(empty($sku)) $this->error_json('sku不可缺少');
        $result = $this->purchase_order_model->get_note_list($purchase_number, $sku);

        $this->success_json($result);
    }

    /**
     * @desc 手动刷新1688订单是否异常
     * @author Jeff
     * @Date 2019/6/28 11:55
     */
    public function fresh_ali_order_abnormal()
    {
        $purchase_number = $this->input->get_post('purchase_number');
        if(empty($purchase_number)) $this->error_json('采购单号不可缺少');

        if(stripos($purchase_number,',') !== false){
            $purchase_numbers = explode(',',$purchase_number);
        }elseif(is_array($purchase_number)){
            $purchase_numbers = $purchase_number;
        }else{
            $purchase_numbers = [$purchase_number];
        }

        $success = $error = $total = 0;
        // 刷新1688金额异常
        $this->load->model('ali/Ali_order_model');
        foreach($purchase_numbers as $pur_number){
            $result = $this->Ali_order_model->fresh_ali_order_abnormal($pur_number);
            if($result['code']){
                $success ++;
            }else{
                $error ++;
            }

            $total ++;
        }

        $message = "总个数：{$total}，成功个数：{$success}，失败个数：$error";

        $this->success_json([],null,$message);
    }

    /**
     * 信息修改审核--显示（批量）状态为信息修改待审核的采购单
     * @author jeff
     * @data 2019-5-13
     */
    public function batch_audit_data_change_order(){
        $purchase_number_list = $this->input->get_post('purchase_number'); //采购单号
        if (empty($purchase_number_list)) {
            $this->error_json('请勾选数据');
        }
        $purchase_number_arr = explode(',', $purchase_number_list);
        //判断是否是待采购经理审核状态
        $data_status=$data = [];
        foreach ($purchase_number_arr as $purchase_number) {
            $purchase_order = $this->purchase_order_model->get_one($purchase_number, false);
            if (empty($purchase_order)) {
                $data[] = $purchase_number;
            }
            if ($purchase_order['purchase_order_status'] != PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT) {
                $data_status[]=$purchase_number;
            }
        }
        if(!empty($data)){
            $msg= '采购单号'.implode('--', $data)."不存在";
            $this->error_json($msg);
        }
        if(!empty($data_status)){
            $msg= '采购单号'.implode('--', $data_status)."不是信息修改待审核状态";
            $this->error_json($msg);
        }
        //获取需要数据
        $temp=$this->purchase_order_model->get_batch_audit_order_list($purchase_number_arr);
        if($temp['bool']){
            $this->success_json($temp['data']);
        }else{
            $this->error_json($temp['msg']);
        }
    }
    /**
     * 信息修改审核--保存（批量）状态为信息修改待审核的采购单
     * @author jeff
     * @data 2019-5-13
     */
    public function batch_audit_data_change_save(){
        $purchase_number_list = $this->input->get_post('purchase_number'); //采购单号
        $purchase_numbers= array_unique(explode(',',$purchase_number_list ));
        $check_status      = $this->input->get_post('check_status');// 1.审核通过，2.审核驳回
        $reject_order_note = $this->input->get_post('reject_order_note');// 驳回原因

        if($check_status==2 && empty($reject_order_note)) $this->error_json('驳回备注不能为空');

        $this->purchase_order_model->purchase_db->trans_begin();
        $errorMess='';
        try {
            foreach ($purchase_numbers as $purchase_number) {
                $result = $this->purchase_order_model->audit_order_change_order($purchase_number, $check_status, $reject_order_note);

                if (empty($result['code'])) {// 失败
                    throw new Exception($purchase_number . ':' . $result['msg']);
                }

            }
            $this->purchase_order_model->purchase_db->trans_commit();

            // 刷新1688金额异常
            $this->load->model('ali/Ali_order_model');
            $this->Ali_order_model->refresh_order_price($purchase_numbers);
        } catch (\Exception $e) {
            $errorMess = $e->getMessage();
            $this->purchase_order_model->purchase_db->trans_rollback();
        }
        if(empty($errorMess)){
            $this->success_json([],null,'审核成功');
        }else{
            $this->error_json($errorMess);
        }
    }

    /**
     * @desc 非1688下单订单信息修改-预览
     * @author Jeff
     * @Date 2019/6/29 16:21
     * @return
     */
    public function change_order_data_preview()
    {
        $purchase_number  = $this->input->get_post('purchase_number'); //采购单号
        if (empty($purchase_number)) $this->error_json('采购单号不能为空');

        $result = $this->purchase_order_edit_model->get_change_order_data_preview($purchase_number);

        if ($result['code']){
            $this->success_json($result['data']);
        }else{
            $this->error_json($result['message']);
        }

    }

    /**
     * @desc 非1688下单订单信息修改-保存
     * @author Jeff
     * @Date 2019/6/29 16:21
     * @return
     */
    public function change_order_data_save()
    {
        $post_data = $_REQUEST;// SKU参数带有小数点短横线等字符，参数丢失，只能用 REQUEST 获取数据
        $purchase_number = isset($post_data['purchase_number']) ? $post_data['purchase_number'] : []; //采购单号
        $purchase_account = isset($post_data['purchase_account']) ? $post_data['purchase_account'] : []; //网拍账号
        $pai_number = isset($post_data['pai_number']) ? $post_data['pai_number'] : []; //拍单号
        $freight = isset($post_data['freight']) ? $post_data['freight'] : []; //运费
        $discount = isset($post_data['discount']) ? $post_data['discount'] : []; //优惠额
        $process_cost = isset($post_data['process_cost']) ? $post_data['process_cost'] : []; //加工费
        $apply_note = isset($post_data['apply_note']) ? $post_data['apply_note'] : [];// 信息修改申请备注
        $freight_sku = isset($post_data['freight_sku']) ? $post_data['freight_sku'] : [];// 每个sku的运费 数组
        $discount_sku = isset($post_data['discount_sku']) ? $post_data['discount_sku'] : [];// 每个sku的优惠 数组
        $process_cost_sku = isset($post_data['process_cost_sku']) ? $post_data['process_cost_sku'] : [];// 每个sku的优惠 数组
        $plan_arrive_time = isset($post_data['plan_arrive_time'])?$post_data['plan_arrive_time']:[];

        if (empty($purchase_number)) $this->error_json('采购单号不能为空');
        if (empty($apply_note)) $this->error_json('信息修改申请备注必填');
        if (empty($freight_sku)) $this->error_json('备货单运费必填');
        if (empty($discount_sku)) $this->error_json('备货单优惠必填');

        $result = $this->purchase_order_edit_model->get_change_order_data_save($purchase_number, $purchase_account, $pai_number, $freight, $discount,$process_cost, $apply_note, $freight_sku, $discount_sku,$process_cost_sku,$plan_arrive_time);

        if ($result['code']){
            $this->success_json();
        }else{
            $this->error_json($result['message']);
        }
    }

    public function get_settlement_list()
    {
        $this->load->model('supplier/Supplier_settlement_model', 'settlementModel');
        //下拉列表供应商结算方式
        $data = $this->settlementModel->get_settlement();
        $this->success_json($data);
    }


    /**
     * 预览1688订单信息修改数据返回
     * @author Totoro Jolon
     */
    public function edit_ali_order_data_preview(){
        $purchase_number  = $this->input->get_post('purchase_number'); //采购单号
        if (empty($purchase_number)) $this->error_json('采购单号不能为空');
        $pai_number  = $this->input->get_post('pai_number'); //采购单号
        if (empty($pai_number)) $this->error_json('拍单号不允许为空');
        $result = $this->purchase_order_edit_model->get_edit_ali_order_data_preview($purchase_number,$pai_number);
        if ($result['code']){
            $this->success_json($result['data']);
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 1688订单信息修改数据返回
     */
    public function edit_ali_order_data_save()
    {
        $post_data = $_REQUEST;// SKU参数带有小数点短横线等字符，参数丢失，只能用 REQUEST 获取数据
        $purchase_number = isset($post_data['purchase_number']) ? $post_data['purchase_number'] : [];
        $purchase_account = isset($post_data['purchase_account']) ? $post_data['purchase_account'] : [];// 网拍账号
        $pai_number = isset($post_data['pai_number']) ? $post_data['pai_number'] : []; //拍单号
        $freight = isset($post_data['freight']) ? $post_data['freight'] : []; //运费
        $discount = isset($post_data['discount']) ? $post_data['discount'] : []; //优惠额
        $process_cost = isset($post_data['process_cost']) ? $post_data['process_cost'] : []; //加工费
        $order_total = isset($post_data['order_total']) ? $post_data['order_total'] : [];
        $note = isset($post_data['note']) ? $post_data['note'] : [];
        $freight_sku = isset($post_data['freight_sku']) ? $post_data['freight_sku'] : [];// 每个sku的运费 数组
        $discount_sku = isset($post_data['discount_sku']) ? $post_data['discount_sku'] : [];// 每个sku的优惠 数组
        $process_cost_sku = isset($post_data['process_cost_sku']) ? $post_data['process_cost_sku'] : [];// 每个sku的加工费 数组
        $plan_arrive_time = isset($post_data['plan_arrive_time'])?$post_data['plan_arrive_time']:[];

        if(empty($purchase_number)) $this->error_json('采购单号不能为空');
        if(empty($note)) $this->error_json('备注不允许为空');
        if(empty($order_total)) $this->error_json('订单总额必填');
        if(empty($pai_number)) $this->error_json('拍单号必填');
        if(empty($purchase_account)) $this->error_json('网拍账号必填');
        if (empty($freight_sku)) $this->error_json('备货单运费必填');
        if (empty($discount_sku)) $this->error_json('备货单优惠必填');
//        if(empty($usercode)) $this->error_json('用户编码必填');

        $result = $this->purchase_order_edit_model->get_edit_ali_order_data_save($purchase_number, $purchase_account, $pai_number, $freight, $discount,$process_cost, $order_total, $note, $freight_sku, $discount_sku, $process_cost_sku,$plan_arrive_time);

        if ($result['code']){
            $this->success_json();
        }else{
            $this->error_json($result['message']);
        }
    }


    public function get_purchase_progress() {
        if( !empty($_GET) ) {

            $clientData = array();
            foreach( $_GET as $key=>$value ) {

                $clientData[$key] = $this->input->get_post($key);
            }


            if( isset($clientData['group_ids']) && !empty($clientData['group_ids'])){

                $this->load->model('user/User_group_model', 'User_group_model');
                $groupids = $this->User_group_model->getGroupPersonData($clientData['group_ids']);
                $groupdatas = [];
                if(!empty($groupids)){
                    $groupdatas = array_column($groupids,'value');
                }

                $clientData['groupdatas'] = $groupdatas;
            }



            $result = $this->purchase_order_model->get_purchase_progress($clientData);

            $role_name=get_user_role();//当前登录角色
            $data_role= getRolexiao();
            $result['list'] = ShieldingData($result['list'],['supplier_name','supplier_code'],$role_name);
            $product_line_drop_down = $this->product_line->get_product_line_list(0);
            $result['product_line_drop_down'] = array_column($product_line_drop_down, 'linelist_cn_name','product_line_id');
           

            $result['is_merge'] = [1=>'已合单',2=>'正常'];
            $result['demand_type'] = $this->purchase_suggest_model->get_demand_type();
            $result['purchase_order_cancel'] = getOrderCancelReason();
            $this->success_json($result);

        }
    }

    public function get_purchase_progress_total()
    {

        if (!empty($_GET)) {

            $clientData = array();
            foreach ($_GET as $key => $value) {

                $clientData[$key] = $this->input->get_post($key);
            }

            if( isset($clientData['group_ids']) && !empty($clientData['group_ids'])){

                $this->load->model('user/User_group_model', 'User_group_model');
                $groupids = $this->User_group_model->getGroupPersonData($clientData['group_ids']);
                $groupdatas = [];
                if(!empty($groupids)){
                    $groupdatas = array_column($groupids,'value');
                }

                $clientData['groupdatas'] = $groupdatas;
            }

            $result = $this->purchase_order_model->get_purchase_progress_total($clientData);
            $this->success_json($result);


        }
    }
    public function get_purchase_oa() {
        $progress = $this->userModel->get_all_user_by_dept_id(OA_PURCHASE_PERSON_ID,'progress_key');
        $this->success_json($progress);

    }
    public function import_progress() {
        $import_json = $this->input->get_post('import_arr');
        $result_list = json_decode($import_json,true);
        if(count($result_list)>10000){
            $this->error_json('超出导入限制数量');
        }
        $username = getActiveUserName();
        $user_error = array();
        $demand_number_eror = $demand_user_error= array();
        $demand_number_null = array();
        $count_error = [];
        $repackage_arr = array();
        $logistics_data = [];
        $number = 0;
        $error = 0;
        $progress = $this->userModel->get_all_user_by_dept_id('1079230','progress_key');
        $progressids =  array_column($progress,"staff_code");

        //验证快递公司
        $carrier_info=$this->purchase_order_progress_model->get_carrier_info();
        if(empty($carrier_info)){
            $returnMessage = array('status'=>0,'message'=>'获取快递公司信息失败','errormessage'=>'获取快递公司信息失败');
            $this->success_json($returnMessage);
        }
        $carrier_info = array_column($carrier_info,'carrier_name','carrier_code');
        $carrier_info = array_flip($carrier_info);
        $error_data = $heads = [];
        foreach ($result_list as $key => $value) {
            if ($key == 0) {
                $heads = $value;
                continue;
            }
            $demand_number = trim($value[0]);//备货单号
           // $estimate_time = $value[1];//预计到货时间
            $cargo_company_id = $value[1];//物流公司名称
            $express_no = trim($value[2]);//快递单号
            $progres = $value[3]; // 进度
            $documentary_name = trim($value[4]); // 跟单员
            //$documentary_name  = "徐梦梦"; // 跟单员
            $documentary_time = $value[5]; // 跟进时间
            $remark = $value[6]; // 备注说明

            //物流公司和快递单号不能只填一个
            $cargo_company_id = explode(' ',$cargo_company_id);
            $express_no = explode(' ',$express_no);

            if (count(array_filter($cargo_company_id)) != count(array_filter($express_no))){
                //$express_error[] = sprintf('第%s行,快递公司名称/快递单号必须同时填写;',$key+1);
                array_push($value, '快递公司名称/快递单号必须同时填写');
                ++$error;
                $error_data[] = $value; //快递公司名称/快递单号必须同时填写
                continue;
            }

            if (empty($demand_number)) {
                array_push($value, '备货单号为空');
                $error_data[] = $value;  //备货单号为空;
                ++$error;
                continue;
            }

            if( !empty($documentary_name)) {
                if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', trim($documentary_name), $arr)) {
                    $documentary_name_flag = str_replace($arr[0],'',$documentary_name);
                  //  $documentary_flag = $this->userModel->get_user_info_by_staff_code($documentary_name_flag);

                    if ( !in_array($documentary_name_flag, $progressids) ) {
                        array_push($value, '备货单，跟单员不存在');
                        ++$error;
                        $error_data[] = $value;  //备货单，跟单员不存在;
                        continue;
                    }



                } else {
                    array_push($value, "备货单，跟单员填写请 姓名+工号，或者检测工号是否错误");
//                    $value['error_message'] = "备货单，跟单员填写请 姓名+工号，或者检测工号是否错误";
                    $error_data[] = $value;  //备货单，跟单员填写请 姓名+工号，或者检测工号是否错误;
                    ++$error;
                    continue;
                }
            }


            //备货单是否存在
            $progress = $this->purchase_order_progress_model->get_progress($demand_number,array("id","arrival_date","estimate_time","estimate_import_number","sku","purchase_number"));
            if( empty($progress) ) {
                array_push($value, '备货单不存在');
                ++$error;
//                $value['error_message'] = '备货单不存在';
                $error_data[] = $value; //备货单不存在;
                continue;
            }

            $arrData['demand_number'] = $demand_number;
           
            $arrData['progres'] = $progres;
            $arrData['documentary_name'] = $documentary_name;
            $arrData['remark'] = $remark;
            $arrData['documentary_time'] = $documentary_time;
            if (count(array_filter($cargo_company_id)) != 0 && count(array_filter($express_no)) != 0){
                foreach (array_filter($cargo_company_id) as $_k => $item){

                    if (!isset($carrier_info[$item])) {
                        array_push($value, '快递公司名称不存在');
                        ++$error;
//                        $value['error_message'] = '快递公司名称不存在';
                        $error_data[] = $value; // 快递公司名称不存在;
                        continue;
                    }
                    $logistics_data[] = [
                        'purchase_number' => $progress['purchase_number'],
                        'sku' => $progress['sku'],
                        'cargo_company_id' => $item,
                        'carrier_code' => $carrier_info[$item],
                        'express_no' => $express_no[$_k]??'',
                        'is_manually' => 1
                    ];
                }
            }

            $overdue = floor((strtotime($progress['arrival_date'])-strtotime($progress['estimate_time']))/86400);
            $arrData['is_overdue'] = ($overdue>0)?1:2;
            array_push($repackage_arr,$arrData);
            ++$number;

        }

        // 需要返回的错误数据
        if (!empty($error_data)) {
            $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
            $file_name = 'progress_error' . time() . '.csv';
            $product_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($product_file)) {
                unlink($product_file);
            }
            fopen($product_file, 'w');
            $fp = fopen($product_file, "a");
            array_push($heads, "错误提示");

            foreach ($heads as $key => $item) {
                $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
            }

            //将标题写到标准输出中
            fputcsv($fp, $title);
            foreach ($error_data as $error_key => $error_value) {
                foreach ($error_value as $err_key => $err_value) {
                    if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $err_value)) {
                        $error_value[$err_key] = iconv("UTF-8", "GBK//IGNORE", $err_value);
                    }
                }

                fputcsv($fp, $error_value);
            }
            ob_flush();
            flush();
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名

            $down_file_url = $down_host . 'download_csv/' . $file_name;
            $return_data = array(

                'error_message' => "共有" . count($error_data) . "条数据错误，请确认是否下载",
                'error_list' => $down_file_url
            );
            $this->error_json($return_data);

        }


//        $returnMessage = array('status'=>0,'message'=>'导入失败','errormessage'=>'');
//
//        if ( !empty($carrier_name_error)) {
//            $returnMessage['errormessage'] .= implode(",",$carrier_name_error)." 快递公司名称不存在;";
//            $error++;
//        }
//
//        if ( !empty($demand_number_eror)) {
//            $returnMessage['errormessage'] .= implode(",",$demand_number_eror)."备货单不存在;";
//            $error++;
//        }
//
//        if ( !empty($user_error)) {
//            $returnMessage['errormessage'] .= implode(",",$user_error)."备货单，跟单员不存在;";
//            $error++;
//        }
//
//        if( !empty($demand_user_error) ) {
//            $returnMessage['errormessage'] .= implode(",",$demand_user_error)."备货单，跟单员填写请 姓名+工号，或者检测工号是否错误;";
//            $error++;
//        }
//
//        //$demand_number_null
//
//        if( !empty($demand_number_null) ) {
//            $returnMessage['errormessage'] .= implode(",",$demand_number_null)."行，备货单号为空;";
//            $error++;
//        }
//        if( !empty($express_error) ) {
//
//            $returnMessage['errormessage'] .= implode(",",$express_error);
//            $error++;

    //}
        $returnMessage = array('status' => 1, 'message' => '导入成功', 'errormessage' => '');
        if( !empty($repackage_arr) && $error==0) {
            $result = $this->purchase_order_progress_model->import_data($repackage_arr,$logistics_data);
            if( !$result ) {

                $returnMessage = array('status'=>0,'message'=>'导入失败','errormessage'=>'');
            }else{
                $returnMessage = array('status'=>1,'message'=>'导入成功','errormessage'=>'');

                $inserData = array(

                    "admin_name" => $username,
                    "create_time" => date("Y-m-d H:i:s"),
                    "type" => "导入",
                    "content" =>$number
                );
                $this->purchase_order_model->insert_data($inserData);
            }
        }


        $this->success_json($returnMessage);
    }

    /**
     * 订单追踪导出(EXCEL)
     * @author luxu
     */
    public function progress_export_excel()
    {
        $params = array();
        if( !empty($_POST) ) {

            foreach( $_POST as $key=>$value ) {

                $params[$key]  =  $this->input->get_post($key);
            }
        }
        // 屏蔽原有的导出，代码不能删除
        $total = $this->purchase_order_model->get_purchase_progress_sum($params);
        static $return = [];
        $per_page = 1000;
        $total_page = ceil($total/$per_page);
        $all_page = $total_page > 10 ? 10 : $total_page;//限制2W条数据\
        //++$all_page;
        for($i = 1;$i <=$all_page;++$i){
            $params['page'] = $i;
            $params['limit']  = $per_page;
            
            $info = $this->purchase_order_model->get_purchase_progress($params);
            if(!empty($info['list'])) {
                $return = array_merge($return,$info['list']);
            }
            unset($info);
        }
        $uid = $this->input->get_post('uid');
        $this->user->_init($uid);
        $jobs = $this->user->getActiveRole();
        $saleFlag = False;
        if( isset($jobs['role_data']) && !empty($jobs['role_data'])){

            $rolenameDatas = array_column( $jobs['role_data'],'en_name');
            $salesRolesNames = array('sales_stock','sales_manager','sales_director','sales','sales_chief');
            $commonRoles = array_intersect($salesRolesNames,$rolenameDatas);
            if(!empty($commonRoles)){

                $saleFlag = True;
            }
        }

        $return = ShieldingData($return,['supplier_name','supplier_code'],get_user_role(),NULL);
        foreach($return as $key=>&$value){

            if($saleFlag == True) {
                $value['sevensale'] = '****';
            }
        }
        $orders_info['heads'] = ['序号', '是否新品','发货省份', '图片', '备货单号', '订单状态', '供应商', '采购单号', 'SKU', '产品名称', '采购数量', '入库数量', '未到货数量', '跟进进度', '采购员', '跟单员', '审核时间', '预计到货日期', '到货日期', '入库日期', '入库时效

（h）', '逾期天数', '拍单号', '采购仓库', '跟进日期', '备注说明', '请款时间', '付款时间', '付款时效(h)', '采购来源', '产品线', '产品状态', '缺货数量（新）', '1688异常', '近7天销量', '物流公司', '快递单号', '包装类型'];
        $orders_info['value'] = ($return);
//        var_dump($orders_info);exit;

        $this->success_json($orders_info);
    }
    /**
     * function:获取MONGODB 中数据
     * @param:  $where  array   MONGODB 获取数据的条件
     **/
    private function get_mongdb_data($params = array())
    {
        $filter = [];
        // 备货单号
        if( isset($params['demand_number']) && !empty($params['demand_number']))
        {
            $demand_numbers = explode(" ",$params['demand_number']);
            $filter['demand_number'] = array('$in'=>$demand_numbers);
        }

        // 传入订单追踪ID
        if( isset($params['ids']) && !empty($params['ids']))
        {
            $ids = explode(",",$params['ids']);
            $filter['id'] = array('$in'=>$ids);
        }

        //订单状态
        if( isset($params['purchase_status']) && !empty($params['purchase_status']))
        {
            $filter['orders_status'] = array('$in'=>$params['purchase_status']);
        }

        //供应商
        if( isset($params['supplier_code']) && !empty($params['supplier_code']))
        {
            $filter['supplier_code'] = "{$params['supplier_code']}";
        }
        //采购单
        if( isset($params['purchase_number']) && !empty($params['purchase_number']))
        {
            $purchase_numbers = explode(" ",$params['purchase_number']);
            $filter['purchase_number'] = array('$in'=>$purchase_numbers);
        }
        if( isset($params['sku']) && !empty($params['sku']))
        {
            $skus = explode(" ",$params['sku']);

            if( count($skus)>=2)
            {
                $filter['sku'] = array('$in'=>$skus);
            }else{
                $filter['sku'] = array('$regex'=>$skus[0]);
            }
        }

        if( isset($params['buyer_id']) && !empty($params['buyer_id']))
        {
            $filter['buyer_id'] = array('$in'=>$params['buyer_id']);
        }

        if( isset($params['documentary_name']) && !empty($params['documentary_name']))
        {
            $filter['documentary_name'] = array('$in'=>$params['documentary_name']);
        }
        if( isset($params['ali_order_status']) && !empty($params['ali_order_status']))
        {
            if($params['ali_order_status'] == 2)
            {
                $ali_order_status ='0';
            }else{
                $ali_order_status ='1';
            }
            $filter['ali_order_status'] = "{$ali_order_status}";
        }

        if( isset($params['estimate_time_start']) && !empty($params['estimate_time_start']) && isset($params['estimate_time_end']))
        {
            $filter['estimate_time'] = array('$gte'=>$params['estimate_time_start'],'$lte'=>$params['estimate_time_end']);
        }
        // 是否逾期
        if( isset($params['is_overdue']) && !empty($params['is_overdue']))
        {
            $filter['is_overdue'] = "{$params['is_overdue']}";
        }

        if( isset($params['stock_owes']) && !empty($params['stock_owes'])){

            if( $params['stock_owes'] == 1 ) {
                $filter['left_stock'] = array('$gte'=>'1');
            }else{
                $filter['left_stock'] = "0";
            }
        }

        if( isset($params['warehouse_code']) && !empty($params['warehouse_code']))
        {
            $filter['warehouse_code'] = array('$in'=>$params['warehouse_code']);
        }

        if( isset($params['source']) && !empty($params['source']))
        {
            $filter['source'] = "{$params['source']}";
        }
        if( isset($params['pai_number']) && !empty($params['pai_number']))
        {
            $filter['pai_number'] = "{$params['pai_number']}";
        }

        if( !empty($params['create_time_start']) && !empty($params['create_time_end']))
        {
            $filter['create_time'] = array('$gte'=>$params['create_time_start'],'$lte'=>$params['create_time_end']);
        }
        // 缺货数量查找

        if( isset($params['stock_owes_start']) && isset($params['stock_owes_end']) )
        {
            $filter['left_stock'] = array('$gte'=>$params['stock_owes_start'],'$lte'=>$params['stock_owes_end']);
        }

        if(  isset($params['stock_owes_start']) && !isset($params['stock_owes_end']) )
        {
            $filter['left_stock'] = array('$gte'=>$params['stock_owes_start']);

        }

        if(  !isset($params['stock_owes_start']) && isset($params['stock_owes_end']) )
        {
            $filter['left_stock'] = array('$lte'=>$params['stock_owes_end']);
        }

        // 备货单状态

        if( isset($params['suggest_order_status']) && !empty($params['suggest_order_status']))
        {
            $filter['suggest_order_status'] = array('$in'=>$params['suggest_order_status']);
        }

        // 7天销量

        if( isset($params['seven_day_sales']) && !empty($params['seven_day_sales']))
        {
            if( $params['seven_day_sales'] == 1) {

                $filter['days_sales_7'] = array('$gt'=>'0');
            }else{
                $filter['days_sales_7'] = "0";
            }
        }

        //按快递单号查询
        if( isset($params['courier_number']) && !empty($params['courier_number']))
        {
            $filter['pliexpress_no'] = "{$params['courier_number']}";
        }
        //按发货批次号查询
        if (isset($params['batch_no']) && !empty($params['batch_no'])) {
            $batch_no = array_filter(explode(" ", $params['batch_no']));
            if(!empty($batch_no)){
                $filter['batch_no'] = array('$in' => $batch_no);
            }
        }
        //物流轨迹
        if (isset($params['track_status']) && $params['track_status'] != '') {
            $track_status = explode(',', $params['track_status']);
            $filter['plistatus'] = array('$in' => $track_status);
        }

        //按快递单号是否为空查询
        if (isset($params['exp_no_is_empty']) && !is_null($params['exp_no_is_empty']) && 0 == $params['exp_no_is_empty']) {
            $filter['express_no'] = array('$nin' => ['',null]);//查询快递单号不等于空
        } elseif (isset($params['exp_no_is_empty']) && 1 == $params['exp_no_is_empty']) {
            $filter['express_no'] = array('$in' => ['',null]);;//查询快递单号等于空
        }

        // 产品线

        if( isset($params['product_line_id']) && !empty($params['product_line_id'])){

            $category_all_ids = $this->product_line->get_all_category($params['product_line_id']);

            $children_ids = explode(",",$category_all_ids);
            $category_all_ids = array_filter($children_ids);
            if( !empty($category_all_ids))
            {
                $filter['progress_product_line'] = array('$in'=>$category_all_ids);
            }else if( empty($category_all_ids) ){
                $filter['progress_product_line'] = '';
            }

        }

        // 异常类型
        if( isset($params['abnormal_type']) && !empty($params['abnormal_type']) ) {

            $abnormal_type = array_filter(explode(',', $params['abnormal_type']));
            $filter['abnormal_type'] = array('$in'=>$abnormal_type);
        }

        // 在途异常
        if( isset($params['on_way_abnormal']) && $params['on_way_abnormal'] !='' ) {
            $filter['on_way_abnormal'] = "{$params['on_way_abnormal']}";
        }

        return $filter;


    }
    /**
     * function:MONGODB 缓存数据中导出订单数据
     * @param:  $params   array   客户端传入参数
     **/
    private function progress_export_mongdb_data_csv($params = array(),$total)
    {
        $filter = $this->get_mongdb_data($params);
        $this->_ci = get_instance();
        //获取redis配置
        $this->_ci->load->config('mongodb');
        $host = $this->_ci->config->item('mongo_host');
        $port = $this->_ci->config->item('mongo_port');
        $user = $this->_ci->config->item('mongo_user');
        $password = $this->_ci->config->item('mongo_pass');
        $author_db = $this->_ci->config->item('mongo_db');
        $mongdb_object = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
        $webfront_path = dirname(dirname(APPPATH));

        $file_name = rand(1,100).'-progre.csv';
        $product_file = $webfront_path.'/webfront/download_csv/'.$file_name;
        if (file_exists($product_file)) {
            unlink($product_file);
        }

        fopen($product_file,'w');
        $fp = fopen($product_file, "a");
        $heads = ['序号','发货省份','图片', '备货单号','在途异常', '订单状态', '供应商', '采购单号', 'SKU', '产品名称', '采购数量', '入库数量','内部采购在途','仓库采购在途', '未到货数量', '跟进进度', '采购员', '跟单员', '审核时间', '预计到货日期', '到货日期', '入库日期', '入库时效

（h）','逾期天数','拍单号','采购仓库','跟进日期','备注说明','请款时间','付款时间','付款时效(h)','采购来源','产品线','产品状态','缺货数量','1688异常','近7天销量','物流公司','快递单号','轨迹状态','异常类型'];
        foreach($heads as $key => $item) {
            $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
        }
        fputcsv($fp, $title);
        $limit  = 2000;
        $page = ceil($total/$limit);
        $numi=0;
        for( $i=0;$i<=$page;++$i) {

            $options['skip'] = $i * $limit;
            $options['limit'] = $limit;
            $query = new MongoDB\Driver\Query($filter, $options);
            $cursor = $mongdb_object->executeQuery("{$author_db}.progress_detail", $query)->toArray();
            $warehouse_code = array_column($cursor, "warehouse_code");
            $warehouseResult = [];
            if(!empty($warehouse_code)) {
                // $this->purchase_db->reset_query();
                $warehouseResult = $this->Purchase_order_progress_model->get_warhouse($warehouse_code, array('warehouse_name', 'warehouse_code'), 'warehouse_code');
            }
            $result = $this->purchase_order_model->get_purchase_mongdb_data($cursor);
            foreach ($result['value'] as $key => $v_value) {

                $v_value_tmp = [];
                $v_value_tmp['id'] = ++$numi;
                $v_value_tmp['provinces'] = iconv('UTF-8', 'GBK//IGNORE',$v_value['provinces']);
                $v_value_tmp['product_img'] = isset($v_value['product_img'])?$v_value['product_img']:NULL;
                $v_value_tmp['demand_number'] = $v_value['demand_number'];
                $v_value_tmp['on_way_abnormal_cn'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['on_way_abnormal_cn']);
                $v_value_tmp['purchase_status_ch'] =  iconv('UTF-8','GBK//IGNORE',getPurchaseStatus($v_value['purchase_status']));
                $v_value_tmp['supplier_name'] =  iconv('UTF-8','GBK//IGNORE',$v_value['supplier_name']);
                $v_value_tmp['purchase_number'] = $v_value['purchase_number'];
                $v_value_tmp['sku'] = $v_value['sku'];
                $v_value_tmp['product_name'] =  iconv('UTF-8','GBK//IGNORE',$v_value['product_name']);
                $v_value_tmp['purchase_num'] = $v_value['purchase_num'];
                $instock_qty = $this->Purchase_order_progress_model->get_purchase_sku_instock_qty($v_value['purchase_number'], $v_value['sku']);
                $v_value_tmp['instock_number'] = $instock_qty['instock_qty'];
                $v_value_tmp['purchase_on_way_num'] = $v_value['purchase_on_way_num'] ;
                $v_value_tmp['warehouse_on_way_num'] = $v_value['warehouse_on_way_num'] ;
                $v_value_tmp['no_instock_date'] = $v_value['no_instock_date'];
                $v_value_tmp['progres'] =  iconv('UTF-8','GBK//IGNORE',$v_value['progres']);
                $v_value_tmp['buyer_name'] = iconv('UTF-8','GBK//IGNORE',$v_value['buyer_name']);
                $v_value_tmp['documentary'] =  iconv('UTF-8','GBK//IGNORE',$v_value['documentary_name']);
                $v_value_tmp['create_time'] = $v_value['create_time'];
                $v_value_tmp['estimate_time'] = $v_value['estimate_time'];
                $v_value_tmp['arrival_date'] = $v_value['arrival_date'];
                $v_value_tmp['instock_date'] = $v_value['instock_date'];
                if (empty($v_value['instock_date']) || empty($v_value['arrival_date'])) {

                    $v_value['storage'] = NULL;
                } else {
                    $storage = $this->purchase_order_model->timediff(strtotime($v_value['instock_date']),strtotime($v_value['arrival_date']));
                    $v_value['storage'] = $storage['day']*24 + $storage['hour'];
                }
                $v_value_tmp['storage'] = $v_value['storage'];

                $v_value_tmp['storageday'] = $v_value['storageday'];
                $v_value_tmp['pai_number'] = $v_value['pai_number']."\t";
                $v_value['warehourse'] = isset($warehouseResult[$v_value['warehouse_code']]) ? $warehouseResult[$v_value['warehouse_code']]['warehouse_name'] : NULL;
                $v_value_tmp['warehourse'] = iconv('UTF-8','GBK//IGNORE',$v_value['warehourse']);
                $v_value_tmp['documentary_time'] = $v_value['documentary_time'];
                $v_value_tmp['remark'] = iconv('UTF-8','GBK//IGNORE',$v_value['remark']);
                $v_value_tmp['application_time'] = $v_value['application_time'];
                $v_value_tmp['payer_time'] = $v_value['payer_time'];
                if (empty($v_value['payer_time']) || $v_value['payer_time'] == "0000-00-00 00:00:00") {

                    $v_value['payer_h'] = NULL;
                } else {
                    $hours = $this->purchase_order_model->timediff(strtotime($v_value['payer_time']),strtotime($v_value['application_time']));
                    $v_value['payer_h'] = $hours['day']*24 + $hours['hour'];
                }
                $v_value_tmp['payer_h'] = $v_value['payer_h'];
                switch ($v_value['source']) {

                    case 1:
                        $v_value['source_ch'] = "合同";
                        break;
                    case 2:
                        $v_value['source_ch'] = "网采";
                        break;
                    case 3:
                        $v_value['source_ch'] = "账期采购";
                        break;
                    default:
                        $v_value['source_ch'] = "未知";
                        break;
                }
                $v_value_tmp['source_ch'] = iconv('UTF-8','GBK//IGNORE',$v_value['source_ch']);
                $v_value_tmp['product_line_ch'] = iconv('UTF-8','GBK//IGNORE',$v_value['product_line_ch']);
                $v_value['product_status_ch'] = $this->purchase_order_model->get_productStatus($v_value['product_status']);
                $v_value_tmp['product_status_ch'] = iconv('UTF-8','GBK//IGNORE',$v_value['product_status_ch']);
                $v_value_tmp['stock_owes'] = $v_value['stock_owes'];
                if ($v_value['ali_order_status'] == 0) {

                    $v_value['ali_order_status_ch'] = "否";
                } else {
                    $v_value['ali_order_status_ch'] = "是";
                }
                $v_value_tmp['ali_order_status_ch'] = iconv('UTF-8','GBK//IGNORE',$v_value['ali_order_status_ch']);
                $v_value_tmp['sevensale'] = iconv('UTF-8','GBK//IGNORE',$v_value['sevensale']);

                //快递公司 快递单号 轨迹状态
                if (isset($v_value['logistics_info']) && is_array($v_value['logistics_info']) && !empty($v_value['logistics_info'])){
                    $cargo_company_id = array_column($v_value['logistics_info'],'cargo_company_id');
                    $express_no = array_column($v_value['logistics_info'],'express_no');
                    $logistics_status = array_column($v_value['logistics_info'],'logistics_status_cn');
                    $cargo_company_id = implode(' ',$cargo_company_id);
                    $express_no = implode(' ',$express_no);
                    $logistics_status = implode(' ',$logistics_status);
                    $v_value_tmp['logistics_company'] = iconv('UTF-8','GBK//IGNORE',$cargo_company_id);
                    $v_value_tmp['courier_number'] =$express_no."\t";
                    $v_value_tmp['logistics_status'] = iconv('UTF-8','GBK//IGNORE',$logistics_status);
                }else{
                    $v_value_tmp['logistics_company'] = '';
                    $v_value_tmp['courier_number'] ='';
                    $v_value_tmp['logistics_status'] ='';
                }

                //异常类型
                $abnormal_type_arr = explode(',',$v_value['abnormal_type_cn']);
                $abnormal_type_tmp = array();
                foreach ($abnormal_type_arr as $item) {
                    $abnormal_type_tmp[]=iconv('UTF-8','GBK//IGNORE',$item);
                }
                $v_value_tmp['abnormal_type'] = implode(',',$abnormal_type_tmp);

                $tax_value_temp = $v_value_tmp;
                fputcsv($fp, $tax_value_temp);
            }
            ob_flush();
            flush();
        }


        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url=$down_host.'download_csv/'.$file_name;
        $this->success_json($down_file_url);

    }

    /**
     * 订单追踪导出
     * @author: luxu
     **/

    public function progress_export()
    {
        try {
            ini_set('memory_limit', '3000M');
            $ids = $this->input->get_post('ids');
            $clientData = array();
            if (!empty($ids)) {
                $clientData['ids'] = $ids;
            } else {

                foreach ($_GET as $key => $value) {

                $clientData[$key] = $this->input->get_post($key);
              }
            }
            if( isset($clientData['group_ids']) && !empty($clientData['group_ids'])){

                $this->load->model('user/User_group_model', 'User_group_model');
                $groupids = $this->User_group_model->getGroupPersonData($clientData['group_ids']);
                $groupdatas = [];
                if(!empty($groupids)){
                    $groupdatas = array_column($groupids,'value');
                }

                $clientData['groupdatas'] = $groupdatas;
            }

            // 屏蔽MONGODB 导出
            //$data,$modules_en_name,$modules_ch_name,$total_number,$add_user_name
            $total = $this->purchase_order_model->get_purchase_total($clientData);
            if($total <= 0){
                $this->error_json("没有符合要求的数据");
            }

            $this->load->model('system/Data_control_config_model');
            $uid = $this->input->get_post('uid');
            $this->user->_init($uid);
            $jobs = $this->user->getActiveRole();
            $saleFlag = False;
            if( isset($jobs['role_data']) && !empty($jobs['role_data'])){

                $rolenameDatas = array_column( $jobs['role_data'],'en_name');
                $salesRolesNames = array('sales_stock','sales_manager','sales_director','sales','sales_chief');
                $commonRoles = array_intersect($salesRolesNames,$rolenameDatas);
                if(!empty($commonRoles)){

                    $saleFlag = True;
                }
            }

            if($saleFlag == True ){

                $clientData['is_sales'] = 1; // 是销售人员
            }else{
                $clientData['is_sales'] = 2; // 不是销售人员
            }
            $clientData['role_name'] = get_user_role();
            //$data,$modules_en_name,$modules_ch_name,$add_user_name,$ext='csv',$total
            $result = $this->Data_control_config_model->insertDownData($clientData, 'ORDERTACKING', '订单追踪导出', getActiveUserName(), 'csv', $total);
            if ($result) {
                $this->success_json("添加到下载中心");
            } else {
                $this->error_json("添加到下载中心失败");
            }
        }catch ( Exception $exp ){

                $this->error_json($exp->getMessage());
        }
//        $total = $this->purchase_order_model->get_purchase_total($clientData);
//        $this->progress_export_mongdb_data_csv($clientData, $total);

    }



    /**
     * 订单追踪导出
     * @author luxu
     */
    public function progress_export_1(){
        ini_set('memory_limit','3000M');
        set_time_limit(0);
        $this->load->helper('export_csv');
        $this->load->helper('status_product');
        $this->load->helper('status_order');
        $ids = $this->input->get_post('ids');
        $clientData = array();
        if(!empty($ids)){
            $clientData['ids']   = $ids;
        }else{

            foreach( $_POST as $key=>$value ) {

                $clientData[$key] = $this->input->get_post($key);
            }
        }
        $total = $this->purchase_order_model->get_purchase_total($clientData);

        $webfront_path = dirname(dirname(APPPATH));

        $file_name = rand(1,100).'-progre.csv';
     
        $product_file = $webfront_path.'/webfront/download_csv/'.$file_name;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        fopen($product_file,'w');
        $fp = fopen($product_file, "a");
        $heads = ['序号','图片','备货单号','备货单状态','订单状态','供应商','采购单号','SKU','产品名称','采购数量','入库数量','未到货数量','跟进进度','采购员', '跟单员','审核时间','预计到货日期','到货日期','入库日期','入库时效

（h）','逾期天数','拍单号','采购仓库','跟进日期','备注说明','请款时间','付款时间','付款时效(h)','采购来源','产品线','产品状态','缺货数量','1688异常','近7天销量','物流公司','快递单号','轨迹状态','异常类型'];
        foreach($heads as $key => $item) {
            $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
        }
        //将标题写到标准输出中
        fputcsv($fp, $title);
        if($total>=1){
            $per_page = 200;
            $total_page = ceil($total/$per_page);
            $numi=0;

            for($i = 1;$i<= $total_page;++$i) {
                $offsets = ($i - 1) * $per_page;
                $clientData['page'] = $i;
                $clientData['limit'] = $per_page;
                $orders_info = $this->purchase_order_model->get_purchase_progress($clientData);
                if ($orders_info['list']) {
                    $tax_value_temp = [];

                    foreach ($orders_info['list'] as $key => $v_value) {

                        if( is_array($v_value['suggest_status_ch']))
                        {
                            continue;
                        }
                        $v_value_tmp = [];
                        $v_value_tmp['id'] = ++$numi;
                        $v_value_tmp['product_img'] = $v_value['product_img'];
                        $v_value_tmp['demand_number'] = $v_value['demand_number'];
                        $v_value_tmp['suggest_status_ch'] = iconv('UTF-8','GBK//IGNORE',$v_value['suggest_status_ch']);
                        $v_value_tmp['purchase_status_ch'] =  iconv('UTF-8','GBK//IGNORE',$v_value['purchase_status_ch']);
                        $v_value_tmp['supplier_name'] =  iconv('UTF-8','GBK//IGNORE',$v_value['supplier_name']);
                        $v_value_tmp['purchase_number'] = $v_value['purchase_number'];
                        $v_value_tmp['sku'] = $v_value['sku'];
                        $v_value_tmp['product_name'] =  iconv('UTF-8','GBK//IGNORE',$v_value['product_name']);
                        $v_value_tmp['purchase_num'] = $v_value['purchase_num'];
                        $v_value_tmp['instock_number'] = $v_value['instock_qty'];
                        $v_value_tmp['no_instock_date'] = $v_value['no_instock_date'];
                        $v_value_tmp['progres'] =  iconv('UTF-8','GBK//IGNORE',$v_value['progres']);
                        $v_value_tmp['buyer_name'] = iconv('UTF-8','GBK//IGNORE',$v_value['buyer_name']);
                        $v_value_tmp['documentary'] =  iconv('UTF-8','GBK//IGNORE',$v_value['documentary_name']);
                        $v_value_tmp['create_time'] = $v_value['create_time'];
                        $v_value_tmp['estimate_time'] = $v_value['estimate_time'];
                        $v_value_tmp['arrival_date'] = $v_value['arrival_date'];
                        $v_value_tmp['instock_date'] = $v_value['instock_date'];
                        $v_value_tmp['storage'] = $v_value['storage'];
                        $v_value_tmp['storageday'] = $v_value['storageday'];
                        $v_value_tmp['pai_number'] = $v_value['pai_number']."\t";
                        $v_value_tmp['warehourse'] = iconv('UTF-8','GBK//IGNORE',$v_value['warehourse']);
                        $v_value_tmp['documentary_time'] = $v_value['documentary_time'];
                        $v_value_tmp['remark'] = iconv('UTF-8','GBK//IGNORE',$v_value['remark']);
                        $v_value_tmp['application_time'] = $v_value['application_time'];
                        $v_value_tmp['payer_time'] = $v_value['payer_time'];
                        $v_value_tmp['payer_h'] = $v_value['payer_h'];
                        $v_value_tmp['source_ch'] = iconv('UTF-8','GBK//IGNORE',$v_value['source_ch']);
                        $v_value_tmp['product_line_ch'] = iconv('UTF-8','GBK//IGNORE',$v_value['product_line_ch']);
                        $v_value_tmp['product_status_ch'] = iconv('UTF-8','GBK//IGNORE',$v_value['product_status_ch']);
                        $v_value_tmp['stock_owes'] = $v_value['stock_owes'];
                        $v_value_tmp['ali_order_status_ch'] = iconv('UTF-8','GBK//IGNORE',$v_value['ali_order_status_ch']);
                        $v_value_tmp['sevensale'] = iconv('UTF-8','GBK//IGNORE',$v_value['sevensale']);
                        //快递公司 快递单号 轨迹状态
                        if (isset($v_value['logistics_info']) && is_array($v_value['logistics_info']) && !empty($v_value['logistics_info'])){
                            $cargo_company_id = array_column($v_value['logistics_info'],'cargo_company_id');
                            $express_no = array_column($v_value['logistics_info'],'express_no');
                            $logistics_status = array_column($v_value['logistics_info'],'logistics_status_cn');
                            $cargo_company_id = implode(' ',$cargo_company_id);
                            $express_no = implode(' ',$express_no);
                            $logistics_status = implode(' ',$logistics_status);
                            $v_value_tmp['logistics_company'] = iconv('UTF-8','GBK//IGNORE',$cargo_company_id);
                            $v_value_tmp['courier_number'] =$express_no."\t";
                            $v_value_tmp['logistics_status'] = iconv('UTF-8','GBK//IGNORE',$logistics_status);
                        }else{
                            $v_value_tmp['logistics_company'] = '';
                            $v_value_tmp['courier_number'] ='';
                            $v_value_tmp['logistics_status'] ='';
                        }

                        //异常类型
                        $abnormal_type_arr = explode(',',$v_value['abnormal_type_cn']);
                        $abnormal_type_tmp = array();
                        foreach ($abnormal_type_arr as $item) {
                            $abnormal_type_tmp[]=iconv('UTF-8','GBK//IGNORE',$item);
                        }
                        $v_value_tmp['abnormal_type'] = implode(',',$abnormal_type_tmp);

                        $tax_value_temp = $v_value_tmp;
                        fputcsv($fp, $tax_value_temp);
                    }
                }

                ob_flush();
                flush();
            }
                //每1万条数据就刷新缓冲区

        }

        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url=$down_host.'download_csv/'.$file_name;
        $this->success_json($down_file_url);

    }

    /**
     * 获取订单追踪操作历史记录
     **/

    public function get_progess_history() {

        $parmas = array(

            "limit" => $this->input->get_post("limit"),
            "page"  => $this->input->get_post("page"),
        );

        $result = $this->purchase_order_model->get_progess_history($parmas);
        $this->success_json($result);
    }

    public function update_history() {

        $this->purchase_order_model->update_history();
    }

    /**
      * function:变更采购员
     **/
    public function update_purchase_data() {

        try {
            $purchase_number = $this->input->get_post("purchase_number"); // 采购单号
            $purchase = $this->input->get_post("buyer_id");
            $buyer_name = $this->input->get_post("buyer_name");

            if( empty($purchase_number) || empty($purchase) ) {

                throw new Exception("传入参数错误");
            }
            $purchase_mess = $this->purchase_order_model->get_purchase_data($purchase_number,[PURCHASE_ORDER_STATUS_WAITING_ARRIVAL
             , PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT
            ]);
            $result_purchase_number = array_column($purchase_mess,"purchase_number");
            $diff_purchase = array_diff($purchase_number,$result_purchase_number);
            if( !empty($diff_purchase) ) {

                throw new Exception( "采购单号:".implode(",",$diff_purchase)."不能变更采购员");
            }
            if( empty($purchase_mess) ) {

                throw new Exception("采购不能变更采购员");
            }

            $error_purchase_number = [];

            foreach( $purchase_mess as $purchase_message) {
                if (!empty($purchase_message['buyer_id']) && $purchase_message['buyer_id'] == $purchase) {

                    throw new Exception("采购单号".$purchase_message['purchase_number'].",请变更采购员");
                }

                $result = $this->purchase_order_model->update_purchase_buery($purchase_message['purchase_number'], $purchase, $buyer_name);

                if( !$result ) {

                    array_push($error_purchase_number,$purchase_message['purchase_number']);
                }
            }

            if( empty($error_purchase_number)) {

                $this->success_json();
            }else{

                throw new Exception( implode(",",$error_purchase_number)." 采购单号变更采购员失败");
            }

        }catch ( Exception $exp ) {

            $this->error_json($exp->getMessage());
        }

    }

    /**
       * function:获取SKU 信息
     **/

    public function get_sku_message()
    {

        try
        {
            $client_skus =  $this->input->get_post("sku");
            if( empty($client_skus))
            {
                throw new Exception("请传入SKU");
                return;
            }

            $result = $this->purchase_order_model->get_sku_message($client_skus);
            if( !empty($result)) {
                $this->success_json($result);
            }else{

                $this->success_json();
            }

        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 获取计算的采购单运费---动态即时计算
     * @author Jolon
     * @date 2019-12-30
     * @example 提交的数据格式
     *           $orderInfo = array(
     *              array(
     *                  'purchase_number'  => '',
     *                  'warehouse_code'   => '',
     *                  'ship_province'    => '',
     *                  'order_items' = > array(
     *                      array(
     *                          'sku'            => '',
     *                          'confirm_amount' => '',
     *                      ),
     *                      array(
     *                          'sku'            => '',
     *                          'confirm_amount' => '',
     *                      ),
     *                      ...
     *                  )
     *              )
     *          )
     */
    public function  get_calculate_order_freight(){
        $data =  $this->input->get_post("data");

        if(empty($data)){
            $this->error_json('未提交数据');
        }

        $this->load->model('purchase/Purchase_order_transport_model');
        $data_list_tmp = [];
        foreach($data as &$row_data){
            $reference_freight = $this->Purchase_order_transport_model->get_calculate_order_reference_freight($row_data);
            if ($reference_freight['code'] === false) {
                $row_data['reference_freight'] = false;
                $row_data['reference_freight_msg'] = $reference_freight['message'];
            } else {
                $row_data['reference_freight'] = format_two_point_price($reference_freight['data']);
                $row_data['reference_freight_msg'] = $reference_freight['message'];
            }

            $data_list_tmp[$row_data['purchase_number']] = $row_data;
        }

        $this->success_json($data_list_tmp);
    }

    /**
     * 采购需求添加备注
     * @author Manson
     */
    public function batch_add_remark(){
        try
        {
            $purchase_number     = $this->input->get_post('purchase_number');
            $remark = $this->input->get_post('remark');
            $id = $this->input->get_post('id');
            $images = $this->input->get_post('images');
            // 参数错误
            if(empty($remark) && empty($images)){
                $this->error_json('参数错误');
            }

            $this->load->model('Purchase_order_remark_model', 'm_remark', false, 'purchase');

            $result = $this->m_remark->batch_add($purchase_number,$remark, $id, $images);
            if($result){
                $this->success_json();
            }else{
                $this->error_json('备注失败');
            }
        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }

    }

    /*
   * 对采购单进行无需付款操作
   */
    public function no_payment_save()
    {
        $purchase_number_list = $this->input->get_post('purchase_number'); //采购单号
        $purchase_numbers= array_unique(explode(',',$purchase_number_list ));
        $no_payment_type =   $this->input->get_post('type');
        $notes = $this->input->get_post('notes');

        if (!is_array($purchase_numbers)||count($purchase_numbers)<1) {
            $this->error_json('数据格式有误');
        }

        if (!$no_payment_type) {
            $this->error_json('无需付款类型有误');
        }

        $errorMess = '';
        $this->purchase_order_model->purchase_db->trans_begin();
        try{
            // 24580
            $verify_statis = $this->purchase_order_new_model->verify_submit_query($purchase_numbers);
            if(isset($verify_statis['code']) && $verify_statis['code'] == 0 && count($verify_statis['msg']) > 0){
//                $msg = implode("，", $verify_statis['msg']);
                $msg = '';
                $msg .= "请确认是否满足以下条件：\n";
                $msg .= "1、采购单下面所有的备货单状态为“待采购询价”、“等待生成进货单、“等待到货”；\n";
                $msg .= "2、采购单“未生成合同”；\n";
                $msg .= "3、采购单的付款状态为：“未申请付款”、“经理驳回”、“供应链总监驳回”、“财务驳回”、“财务主管驳回”、“财务经理驳回”、“财务总监驳回”、“总经办驳回”；\n";
                $msg .= "4、采购单“未生成对账单”；\n";
                $this->error_json($msg);
            }

            foreach ($purchase_numbers as $purchase_number) {
                $result = $this->purchase_order_model->no_payment_opr($purchase_number,$no_payment_type,$notes);
                if (empty($result['code'])) {
                    throw new Exception($result['msg']);
                }
            }
            $this->purchase_order_model->purchase_db->trans_commit();
        }catch (\Exception $e){
            $errorMess = $e->getMessage();
            $this->purchase_order_model->purchase_db->trans_rollback();
        }
        if(empty($errorMess)){
            $this->success_json([],null,'操作成功');
        }else{
            $this->error_json($errorMess);
        }
    }
  /**
     * 供应商门户-预计到货时间审核
     * purchase_number
     * purchase_type 采购审核状态
     * remark 采购审核备注
     */
    public function audit_arrive_time_status()
    {
        $purchase_items = $this->input->get_post('purchase_items'); //采购单号
//        $purchase_number_list = $this->input->get_post('purchase_number'); //采购审核状态
//        $purchase_number_list = $this->input->get_post('purchase_number'); //采购审核备注
        $purchase_arr = json_decode($purchase_items, true);
        $return = $this->supplier_joint_model->pushPredictTimeStatus($purchase_arr);
//        $return = json_decode($return,true);
        if(isset($return['code']) && $return['code'] == 200){
            $this->success_json('', null, '审核成功');
        }
        elseif (isset($return['code']) && $return['code'] == 500){
            $this->success_json('', null, $return['msg']);
        }
        else{
            $this->error_json('审核失败'.(!empty($return) && is_string($return)?$return:''));
        }
    }

    /**
     * 供应商门户-预计到货时间审核日志
     * purchase_number string
     */
    public function get_audit_arrive_log()
    {
        $purchase_number = $this->input->get_post('purchase_number');
        $sku = $this->input->get_post('sku');
        $return = $this->supplier_joint_model->getWebInfoLog($purchase_number, $sku);
        foreach ($return as &$v){
            if($v['audit_status'] == SRM_TIME_READY_STATUS){
                $v['audit_status'] = '提交修改';
            } elseif ($v['audit_status'] == SRM_TIME_ACCESS_STATUS) {
                $v['audit_status'] = '审核通过';
            } elseif ($v['audit_status'] == SRM_TIME_REFUSE_STATUS) {
                $v['audit_status'] = '驳回';
            }
//            if(!empty( $v['old_estimated_arrive_time'])){
//                $v['old_estimated_arrive_time'] = date("Y-m-d",strtotime($v['old_estimated_arrive_time']));
//            }
//            if(!empty( $v['new_estimated_arrive_time'])) {
//                $v['new_estimated_arrive_time'] = date("Y-m-d", strtotime($v['new_estimated_arrive_time']));
//            }
        }
        if(!empty($return)){
        $this->success_json($return, null);
        }else{
            $this->error_json('无记录');
        }
    }

    /**
     * purchase_numbers 11222,23344
     * 供应商门户-预计到货时间确认弹框
     */
    public function get_arrive_audit_info()
    {
        echo "saaa";exit;
        $purchase_number = $this->input->get_post('purchase_number');
        $purchase_arr = explode(',', $purchase_number);
        $return = $this->supplier_joint_model->get_time_audit_info($purchase_arr);
        if (!empty($return)) {
            $pur_item = '';
            foreach ($return as $val) {
                if ($val['audit_status'] != 1) {
                    $pur_item = $val['purchase_number'];
                }
            }
        }
        if(isset($pur_item) && empty($pur_item)){
            $this->success_json($return, null);
        }else{
            $pur_item = isset($pur_item) ? $pur_item : '';
//            $this->success_json('采购单'.$pur_item.'不需审核或状态异常', null);
            $this->error_json('采购单'.$pur_item.'只有待确认状态才需点击');
        }
    }

    /**
     * 需求：20669 增加【预览海外仓采购单】的功能，并下载excel。海外仓包装类型为“Q2：PE袋包装”，
     *  那么系统自动将该备货单进行替换为：备货单的包装类= 白色快递袋；
     * @Methods:GET
     * @Author:luxu
     * @Time:2020/5/29​
     **/

    public function getViewData(){

        try{

            $ClientData = $this->input->get_post("purchase_numbers");
            if( empty($ClientData) || $ClientData == NULL){

                throw new Exception("请传入采购单号");
            }

            // 当传入多个PO 单号时，验证是否同一个供应商
            if( count($ClientData) >1){

                $supplierData = $this->purchase_order_model->getPurchaseSupplier($ClientData);
                if( count($supplierData) > 1){

                    throw new Exception("必须满足是同一同一供应商");
                }
            }
            $resultData = [];
            // 获取PO单数据
            $result = $this->purchase_order_model->getViewData($ClientData);
            if(!empty($result)){
                foreach($result as $value){

                    $resultData[] = $value;
                }
            }
            $this->success_json($resultData);
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 需求：20669 增加【预览海外仓采购单】的功能，并下载excel。海外仓包装类型为“Q2：PE袋包装”，
     *  那么系统自动将该备货单进行替换为：备货单的包装类= 白色快递袋；(下载EXCEL)
     * @Methods:GET
     * @Author:luxu
     * @Time:2020/6/3​
     **/
    public function downViewData(){

      try{

          $ClientData = $this->input->get_post("purchase_numbers");
          if( empty($ClientData) || $ClientData == NULL){

              throw new Exception("请传入采购单号");
          }

          // 当传入多个PO 单号时，验证是否同一个供应商
          if( count($ClientData) >1){

              $supplierData = $this->purchase_order_model->getPurchaseSupplier($ClientData);
              if( count($supplierData) > 1){

                  throw new Exception("必须满足是同一同一供应商");
              }
          }
          $resultData = [];
          // 获取PO单数据
          $result = $this->purchase_order_model->getViewData($ClientData);
          if(!empty($result)){
              foreach($result as $value){

                  $resultData[] = $value;
              }
          }
          $this->success_json($resultData);


      }catch ( Exception $exp ){

          $this->error_json($exp->getMessage());
      }
    }

    /**
     * 获取PO 回货日志数据
     * 数据中心接口文档：http://dp.yibai-it.com:33344/web/#/118?page_id=15758
     * @author:luxu
     * @time:2020/7/3
     **/
    public function getShipmentsQty(){

      try{

          $purchaseNumbers = $this->input->get_post("purchaseNumber");
          $skus = $this->input->get_post("sku");
          if( empty($purchaseNumbers) || empty($skus)){

              throw new Exception("缺少参数");
          }
          $header        = array('Content-Type: application/json');
          $access_taken  = getOASystemAccessToken();
          $url           = getConfigItemByName('api_config','purchase','shipmentsqty');
          $url           = $url."?access_token=".$access_taken;
          $result        = getCurlData($url,json_encode(['purchaseNumber'=>$purchaseNumbers,'sku'=>$skus],JSON_UNESCAPED_UNICODE),'post',$header);
          $result        = json_decode($result,True);
          if( isset($result['code']) && $result['code'] == 200){

              $this->success_json($result['data']);
          }else{
              throw new Exception($result['msg']);
          }
      }catch ( Exception $exp ){

          $this->error_json($exp->getMessage());
      }
    }

    public function testabc(){
        $this->user->_init(1671);
        $jobs = $this->user->getActiveRole();
        print_r($jobs);die();
    }

    /**
     * 109082 下载发货单
     * @author yefanli
     * @time 2020-07-28
     */
    public function download_purchase_delivery_note()
    {
        $pur_number = $this->input->get_post("purchase_numbers");
        $type = $this->input->get_post("behavior");  // 动作：1预览数据不生成pdf，2生成pdf并下载
        if(!is_array($pur_number) || count($pur_number) == 0){
            $this->error_json('采购单号不能为空');
            return;
        }

        // 获取订单信息
        $order_info = $this->purchase_delivery_note_model->get_order_and_items($pur_number);
        if(!$order_info || $order_info['code'] != 1)$this->error_json('查询不到相应的数据');

        // 校验 退税一致，且供应商一致，公共仓一致
        $verify = $this->purchase_delivery_note_model->verify_DSW_uniformity($order_info['data']);
        if($verify)$this->error_json($verify);

        $suggest_wms = $this->purchase_delivery_note_model->get_suggest_wms_contacts($pur_number); // 获取采购单对应的备货单仓库
        $contacts = [];

        $uid = 0;
        foreach ($order_info['data'] as $val){
            if(isset($val['buyer_id']) && !empty($val['buyer_id'])){
                $uid = $val['buyer_id'];
                break;
            }
        }
        if($uid == 0){
            $this->error_json('没有相应的收货人，请确认采购单中信息是否完整。');
        }
        if(is_array($suggest_wms) && count($suggest_wms) > 0 && $wms_list = $this->purchase_delivery_note_model->get_contacts($uid)){
            $contacts = $wms_list['code'] == 1 ? $wms_list["data"] : [];
        }
        $data = $this->purchase_delivery_note_model->combination_show_data($order_info['data'], $contacts);

        // 只返回预览数据
        if($type == 1)$this->success_json($data);

        // 返回带 dom 数据
        if($type == 2){
            $url = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'purchase_delivery_note');
            $header = array('Content-Type: application/json');
            $html = getCurlData($url,json_encode($data, JSON_UNESCAPED_UNICODE),'post',$header);//翻译结果
            $this->success_json($html);
        }

    }


    /**
     * 111428 从1688获取数据，刷新PO运费、优惠金额、总金额
     * @author yefanli
     * @time    20200807
     */
    public function ali_order_refresh_purchase_SDP()
    {
        $pur_number = $this->input->get_post("purchase_numbers");

        if(empty($pur_number))$this->error_json("采购单号不能为空");
        if(!is_array($pur_number))$pur_number = [$pur_number];

        $data = $this->purchase_order_new_model->get_ali_order_refresh_purchase_SDP($pur_number);
        if($data['code'] == 1)$this->success_json($data['data']);
        $res = $data['code'] != 1 ? $data['msg'] : "获取数据失败";
        $this->error_json($res);
    }
    

    /**
     * 获取采购系统组别
     * @param GET
     * @author:luxu
     * @time:2020/9/8 11 19
     **/
    public function getGrupData(){
        $this->load->model('user/User_group_model', 'User_group_model');
        $result['alias'] = $this->User_group_model->getGroupList([1,2]);
        $groupByData = $this->User_group_model->getGroupByData([1,2]);

        $result['overseas'] = [];

        foreach( $groupByData as $key=>$value){

            if( $value['category_id'] == 2){

                $result['overseas'][$value['value']] = $value['label'];
            }

            if( $value['category_id'] == 1){

                $result['domestic'][$value['value']] = $value['label'];
            }
        }


        $this->success_json($result, null, '操作成功');
    }

    /**
     * 获取采购单对应的结算方式、支付方式
     */
    public function get_order_payment_pay()
    {
        $pur_number = $this->input->get_post("purchase_number");
        $is_drawback = $this->input->get_post("is_drawback");
        $supplier_code = $this->input->get_post("supplier_code");
        $purchase_type_id = $this->input->get_post("purchase_type_id");
        if($is_drawback == 2)$is_drawback = 0;
        if(!in_array($is_drawback, [0,1])){
            $this->error_json("是否退税不能为空");
        }
        $result = $this->purchase_order_new_model->get_order_payment_pay($pur_number, $is_drawback, $supplier_code,$purchase_type_id);
        if(isset($result['code']) && $result['code'] == 1){
            $this->success_json($result['msg']);
        }
        $this->error_json($result['msg']);
    }

    /**
     * 多货调拨--获取数据
     * @author yefnali
     */
    public function get_order_sku_allocation_info()
    {
        $demand_number      = $this->input->get_post("demand_number");
        $purchase_number    = $this->input->get_post("purchase_number");
        $sku                = $this->input->get_post("sku");
        $this->load->model('Purchase_allocation_model');
        $res = $this->Purchase_allocation_model->get_order_sku_allocation_base($demand_number, $purchase_number, $sku);
        if(!isset($res['code']))$this->error_json('申请失败，调拨信息获取失败！');
        if($res['code'] == 1)$this->success_json($res['msg']);
        $this->error_json($res['msg']);
    }

    /**
     * 多货调拨--保存数据
     * @author yefnali
     */
    public function save_order_sku_allocation()
    {
        $params = [
            "purchase_number"       => $this->input->get_post("purchase_number"),
            "demand_number"         => $this->input->get_post("demand_number"),
            "sku"                   => $this->input->get_post("sku"),
            "surplus"               => $this->input->get_post("surplus"), // 调入数量
            "out_surplus"           => $this->input->get_post("out_surplus"), // 调出信息
        ];

        // 验证提交数据
        $out_surplus = [];
        if(empty($params['out_surplus']) || !is_array($params['out_surplus'])){
            try{
                $out_surplus_num = 0;
                foreach ($params['out_surplus'] as $val){
                    foreach ($val as $k=>$v){
                        $out_surplus_num = $out_surplus_num + (int)$v;
                        $out_surplus[$k] = (int)$v;
                    }
                }
                if(empty($params['surplus']) || $out_surplus_num != $params['surplus'])$this->error_json('调拨申请失败，调入数量于调出数量不相等');
            }catch (Exception $e){
                $this->error_json('调拨申请失败，提交数据不符合格式！');
            }
        }

        // 验证调拨基本信息
        $this->load->model('Purchase_allocation_model');
        $allocation = $this->Purchase_allocation_model->verify_allocation_info($params);
        if(!isset($allocation['code']) || $allocation['code'] == 0)$this->error_json('调拨失败，错误信息：'.$allocation['msg']);

        // 保存数据
        $save = $this->Purchase_allocation_model->save_order_sku_allocation($params);
        if($save && isset($save['code']) && $save['code'] == 1){
            $this->success_json([],0,'调拨申请成功');
        }
        $msg = isset($save['msg'])?$save['msg']: '调拨失败，保存数据失败！';
        $this->error_json($msg);
    }

    /**
     * 催发货
     * @author yefnali
     * @time 20201123
     */
    public function urge_send_order()
    {
        $urge_list  = $this->input->get_post("urge_list");
        $action     = $this->input->get_post("action");
        if(empty($urge_list) || !is_array($urge_list))$this->error_json('参数不正确');

        $list = [];
        try{
            foreach ($urge_list as $k=>$val){
                $list[] = json_decode($val, true);
            }
        }catch (Exception $e){}
        if(empty($list))$this->error_json('参数不正确');

        $res = [];
        $pur_number = array_column($list, 'purchase_number');
        if(empty($pur_number))$this->error_json('参数不正确');
        $pur_num_list = $this->purchase_order_new_model->handle_purchase_urge_data($pur_number);
        if(isset($pur_num_list['status']))$this->error_json($pur_num_list['status']);//$res[] = ["status"=> 0,"msg"=> $pur_num_list['status']];
        if(isset($pur_num_list['wechat']))$this->error_json($pur_num_list['wechat']);//$res[] = ["status"=> 0,"msg"=> $pur_num_list['wechat']];
        if(isset($pur_num_list['time']))$this->error_json($pur_num_list['time']);//$res[] = ["status"=> 0,"msg"=> $pur_num_list['time']];
        if(!isset($pur_num_list['success']) || count($pur_num_list['success']) == 0){
            $this->error_json('暂无符合催发货的供应商！');
        }

        // 不等于1为获取数据
        if($action != 1) $this->success_json($res);

        // 调用发送信息
        $send_data = $this->purchase_order_new_model->send_official_order_data($pur_num_list['success'], 3);
        $status = 0;
        if(isset($send_data['status']) && $send_data['status'] === 1)$status = 1;
        $res[] = ["status"=> $status,"msg"=> $send_data['list']];
        $this->success_json($res);
    }

    /**
     * 催改价
     * @author yefnali
     * @time 20201123
     */
    public function urge_change_order_price()
    {
        $pur_number     = $this->input->get_post("purchase_numbers");
        $action         = $this->input->get_post("action");
        if(empty($pur_number) || !is_array($pur_number))$this->error_json('参数不正确');
        $pur_list = $this->purchase_order_new_model->handle_purchase_change_price_data($pur_number);

        $res = [];
        if(isset($pur_list['type']))$this->error_json($pur_list['type']);//$res[] = ["status"=> 0,"msg"=> $pur_list['type']]; // 只有采购来源为“网采”
        if(isset($pur_list['status']))$this->error_json($pur_list['status']);//$res[] = ["status"=> 0,"msg"=> $pur_list['status']]; // 采购单状态
        if(isset($pur_list['wechat']))$this->error_json($pur_list['wechat']);//$res[] = ["status"=> 0,"msg"=> $pur_list['wechat']]; // 微信公众号
        if(isset($pur_list['time']))$this->error_json($pur_list['time']);//$res[] = ["status"=> 0,"msg"=> $pur_list['time']]; // 30分钟
        if(!isset($pur_list['success']) || count($pur_list['success']) == 0)$this->error_json('暂无符合催改价的供应商！');

        // 不等于1为获取数据
        if($action != 1) $this->success_json($res);

        // 调用发送信息
        $send_data = $this->purchase_order_new_model->send_official_order_data($pur_list['success'], 2);
        $status = 0;
        if(isset($send_data['status']) && $send_data['status'] === 1)$status = 1;
        $res[] = ["status"=> $status,"msg"=> $send_data['list']];
        $this->success_json($res);

    }

    /**
     * 获取虚拟入库
     * @author 叶凡立
     * @time  20201125
     */
    public function get_imitate_purchase_instock()
    {
        $purchase  = $this->input->get_post("purchase_number");
        if(empty($purchase))$this->error_json('采购单号不能为空！');

        $this->load->model('purchase_order_extend_model');
        $data = $this->purchase_order_extend_model->get_imitate_purchase_instock($purchase);
        if($data && is_string($data))$this->error_json($data);
        if($data && is_array($data) && count($data) > 0)$this->success_json($data);
        $this->error_json('暂无查询数据！');
    }

    /**
     * 等待带货后修改采购单信息
     */
    public function get_change_order_preview()
    {
        $ids  = $this->input->get_post("ids");
        $ids = explode(",",  $ids);
        $ids = array_unique($ids);
        if(empty($ids))$this->error_json('采购单号不能为空！');
        $data = $this->purchase_order_edit_model->get_change_order_preview($ids);
        if(isset($data['code']) && $data['code'] == 1)$this->success_json($data['data']);
        $this->error_json('获取数据失败，没有要操作的数据！');
    }

    /**
     * 等待带货后修改采购单信息 保存
     */
    public function save_change_order_preview()
    {
        $param = $this->input->get_post("data");
        try{
            $param = json_decode($param, true);
            if(!is_array($param) || count($param) == 0)$this->error_json("提交的数据不能为空！");
        }catch (Exception $e){}
        $data = $this->purchase_order_edit_model->save_change_order_preview($param);
        if(isset($data['code']) && $data['code'] == 1){
            $this->success_json([], [], "修改成功");
        }elseif (isset($data['msg'])){
            $this->error_json($data['msg']);
        }
        $this->error_json("提交数据失败！");
    }

    /**
     * 保存虚拟入库
     * @author 叶凡立
     * @time  20201125
     */
    public function save_imitate_purchase_instock()
    {
        $purchase_number  = $this->input->get_post("purchase_number");
        $data_list     = $this->input->get_post("data_list");
        if(empty($purchase_number) || empty($data_list) || !is_array($data_list))$this->error_json('参数不正确');

        $list = [];
        try{
            foreach ($data_list as $k=>$val){
                $list[] = json_decode($val, true);
            }
        }catch (Exception $e){}
        if(empty($list))$this->error_json('参数不正确');

        $this->load->model('purchase_order_extend_model');
        $data = $this->purchase_order_extend_model->save_imitate_purchase_instock($purchase_number, $list);
        if(isset($data['code']) && $data['code'] == 1)$this->success_json([], [], '虚拟入库成功');
        if(isset($data['code']) && $data['code'] != 1)$this->error_json($data['msg']);
        $this->error_json('入库失败！');
    }

    /**
     * 获取物流类型
     */
    public function get_logistics_type()
    {
        $data = $this->purchase_order_extend_model->get_logistics_type();
        if(count($data) > 0)$this->success_json($data, [], '');
        $this->error_json('没数据！');
    }

    /**
     * 重推数据到计划系统
     */
    public function reset_data_push_plan()
    {
        $pur = $this->input->get_post("purchase_number");
        $pur = explode(",", $pur);
        if(empty($pur))$this->error_json('没有要处理的采购单！');
        foreach ($pur as $val){
            if(!empty($val))$this->purchase_order_model->push_purchase_order_info_to_plan($val);
        }
    }
}
