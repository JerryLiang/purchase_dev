<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Temporary_test extends MY_API_Controller {

    private $slaveDb = null;

    public function __construct() {
        parent::__construct();
        $this->load->model('abnormal/Abnormal_list_model','abnormal_model');
        $this->load->helper('abnormal');
    }


    /**
     * Temporary_test/temporary_test_create_order?purchase_numbers=ABD9630655
     */
    public function temporary_test_create_order(){
        set_time_limit(0);
        ini_set('memory_limit','2048M');

        $purchase_numbers = $this->input->post_get('purchase_numbers'); //勾选数据
        if (empty($purchase_numbers)) {
            $this->error_json('请选择数据');
        }
        if(!is_string($purchase_numbers)){
            $this->error_json('请使用 字符串');
        }
        $purchase_numbers = explode(',',$purchase_numbers);
        if (empty($purchase_numbers)) {
            $this->error_json('请选择数据');
        }

        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase/purchase_order_edit_model');
        $this->load->model('warehouse/Warehouse_model');
        $this->load->model('purchase_suggest/Purchase_suggest_model');
        $this->load->model('finance/purchase_order_pay_type_model');
        $this->load->model('purchase/purchase_order_edit_model');


        $warehouse_list_tmp = $this->Warehouse_model->get_pertain_wms_list();
        $pertain_wms_list = arrayKeyToColumn($warehouse_list_tmp,'warehouse_code');

        $result = $this->purchase_order_edit_model->get_batch_edit_order($purchase_numbers);
        if ($result['code']) {
            $data                   = $result['data'];
            $purchasing_order_audit = 2;
            $is_submit              = 1;


            $data_tmp = [];

//            print_r($data);exit;


            $data_list = [];

            foreach($data as $order_item_list){

                foreach($order_item_list['items_list'] as $item_list){
                    $now_data_tmp = [
                        "sku"                      => $item_list['sku'],
                        "confirm_amount"           => $item_list['confirm_amount'],
                        "tax_rate"                 => $item_list['tax_rate'],
                        "purchase_unit_price"      => $item_list['purchase_unit_price'],
                        "product_base_price"       => $item_list['product_base_price'],
                        "last_purchase_price"      => $item_list['last_purchase_price'],
                        "starting_qty"             => $item_list['starting_qty'],
                        "starting_qty_unit"        => $item_list['starting_qty_unit'],
                        "item_freight"             => 0,
                        "item_discount"            => 0,
                        "item_process_cost"        => 0,
                        "modify_remark"            => '脚本批量编辑订单',
                        "coupon_rate"              => $item_list['coupon_rate'],
                        "plan_arrive_time"         => date('Y-m-d H:i:s',strtotime("+3 days")),
                        "warehouse_code"           => $item_list['warehouse_code'],
                        "sku_drackback_ch"         => $item_list['sku_drackback_ch'],
                        "purchase_number"          => $order_item_list['purchase_number'],
                        "is_drawback"              => $order_item_list['is_drawback'],
                        "pay_type"                 => $order_item_list['pay_type'],
                        "shipping_method_id"       => $order_item_list['shipping_method_id'],
                        "es_shipment_time"         => date('Y-m-d H:i:s',strtotime("+3 days")),
                        "plan_product_arrive_time" => date('Y-m-d H:i:s',strtotime("+6 days")),
                        "settlement_ratio"         => '100%',
                        "is_freight"               => 2,
                        "freight"                  => 0,
                        "process_cost"             => 0,
                        "freight_formula_mode"     => "weight",
                        "pai_number"               => '1123258690746907071',
                        "purchase_acccount"        => 'yibaisuperbuyers:供应链007',
                        "is_ali_order"             => 0,
                        "freight_note"             => '脚本批量编辑订单',
                        "shipment_type"            => $order_item_list['shipment_type'],
                        "account_type"             => $order_item_list['account_type']?($order_item_list['account_type']['settlement_code']):'10',
                        "reference_freight"        => $order_item_list['reference_freight'],
                        "reference_freight_msg"    => '234',
                        "pertain_wms_name"         => isset($pertain_wms_list[$item_list['warehouse_code']])?$pertain_wms_list[$item_list['warehouse_code']]['pertain_wms_code']:'AFN',
                    ];

                    if($now_data_tmp['account_type'] == 20){
                        $now_data_tmp['pai_number'] = '1560523142081907071';
                    }elseif($now_data_tmp['account_type'] == 10){
                        $now_data_tmp['pai_number'] = '1123001569239907071';
                    }
                    $data_list[] = $now_data_tmp;
//            print_r($data_list);exit;
                }
            }

//            print_r($data_list);exit;



            foreach($data_list as $value){
                if(!isset($value['purchase_number']) or !isset($value['sku'])){
                    $this->error_json('采购单号和SKU缺失');
                }
                $purchase_number = $value['purchase_number'];

                // 判断SKU 是否处于锁单状态

                if(!isset($data_tmp[$purchase_number])){
                    $data_tmp[$purchase_number]['purchase_number']          = $purchase_number;
                    $data_tmp[$purchase_number]['pay_type']                 = intval($value['pay_type']);
                    $data_tmp[$purchase_number]['settlement_ratio']         = strval($value['settlement_ratio']);
                    $data_tmp[$purchase_number]['shipping_method_id']       = strval($value['shipping_method_id']);
                    $data_tmp[$purchase_number]['plan_product_arrive_time'] = strval($value['plan_product_arrive_time']);
                    $data_tmp[$purchase_number]['is_freight']               = strval($value['is_freight']);
                    $data_tmp[$purchase_number]['freight']                  = 0;
                    $data_tmp[$purchase_number]['discount']                 = 0;
                    $data_tmp[$purchase_number]['process_cost']             = 0;
                    $data_tmp[$purchase_number]['freight_formula_mode']     = strval($value['freight_formula_mode']);
                    $data_tmp[$purchase_number]['purchase_acccount']        = strval($value['purchase_acccount']);
                    $data_tmp[$purchase_number]['pai_number']               = strval($value['pai_number']);
                    $data_tmp[$purchase_number]['warehouse_code']           = strval($value['warehouse_code']);
                    $data_tmp[$purchase_number]['purchasing_order_audit']   = intval($purchasing_order_audit);
                    $data_tmp[$purchase_number]['freight_note']             = isset($value['freight_note']) ? $value['freight_note'] : '';//运费说明
                    $data_tmp[$purchase_number]['shipment_type']            = isset($value['shipment_type']) ? $value['shipment_type'] : 2;//发运类型
                    $data_tmp[$purchase_number]['account_type']             = isset($value['account_type']) ? $value['account_type'] : '';//结算方式
                    $data_tmp[$purchase_number]['is_drawback']              = isset($value['is_drawback']) ? $value['is_drawback'] : null; // 是否退税
                }
                $data_tmp[$purchase_number]['items_list'][] = [
                    'sku'                 => $value['sku'],
                    'confirm_amount'      => $value['confirm_amount'],
                    'tax_rate'            => $value['tax_rate'],
                    'modify_remark'       => isset($value['modify_remark']) ? $value['modify_remark'] : '',
                    'freight'             => isset($value['freight']) ? $value['freight'] : 0,
                    'discount'            => isset($value['discount']) ? $value['discount'] : 0,
                    'process_cost'        => isset($value['process_cost']) ? $value['process_cost'] : 0,
                    'purchase_unit_price' => isset($value['purchase_unit_price']) ? $value['purchase_unit_price'] : 0,
                    'coupon_rate'         => isset($value['coupon_rate']) ? $value['coupon_rate'] : 0.000,
                    'plan_arrive_time'    => isset($value['plan_product_arrive_time']) ? $value['plan_product_arrive_time'] : '', //预计到货日期
                    'es_shipment_time'    => isset($value['es_shipment_time']) ? $value['es_shipment_time'] : '', // 预计发货时间
                    'warehouse_code'      => $value['warehouse_code'],
                    'product_base_price'  => $value['product_base_price'],
                ];

                $data_tmp[$purchase_number]['freight']      += isset($value['freight']) ? $value['freight'] : 0;//总运费
                $data_tmp[$purchase_number]['discount']     += isset($value['discount']) ? $value['discount'] : 0;//总优惠
                $data_tmp[$purchase_number]['process_cost'] += isset($value['process_cost']) ? $value['process_cost'] : 0;//总加工费
            }

//            print_r($data_tmp);exit;
            $result = $this->purchase_order_edit_model->save_batch_edit_order($data_tmp, $is_submit);
            if ($result['code']) {
                $purchase_numbers = array_keys($data_tmp);
                $purchase_numbers = array_unique($purchase_numbers);
                foreach ($purchase_numbers as $po_number) {
                    $result = $this->purchase_order_model->audit_order($po_number, 1, '脚本自动审核通过');

                    $this->purchase_order_pay_type_model->refresh_order_price($po_number);// 刷新采购金额汇总
                }


                $this->success_json($result['data']);
            } else {
                $this->error_json($result['message']);
            }


        } else {
            $this->error_json($result['message']);
        }

    }

    /**
     * 备货单 生成采购单
     * Temporary_test/temporary_test_do_create_order?purchase_numbers=ABD9630655
     */
    public function temporary_test_do_create_order(){
        $params = [
            'demand_type_id'                 => PURCHASE_DEMAND_TYPE_PLAN,// 需求类型(计划单)
            'suggest_status'                 => SUGGEST_STATUS_NOT_FINISH,// 需求状态
            'is_create_order'                => SUGGEST_ORDER_STATUS_N,// 是否生成采购单
        ];
        $this->load->model('purchase_suggest/suggest_lock_model');

        // 获取采购建议列表
        $suggest_list = $this->purchase_suggest_model->get_list($params,null,300);
        if(!isset($suggest_list['data_list']) or empty($suggest_list['data_list'])){
            $this->error_json('没有符合条件的需求单数据');
        }

        $suggest_list = $suggest_list['data_list'];
        $tax_rate_error = [];
        $sku_message = $sku_message_supplier = [];
        if($suggest_list){
            $tar_rate_verify_data = array_map(function($data){
                if( isset($data['purchase_type_id']) && !empty($data['purchase_type_id']) && $data['purchase_type_id']==PURCHASE_TYPE_OVERSEA)
                {
                    return $data;
                }
            },$suggest_list);
            if( !empty($tar_rate_verify_data))
            {
                $verify_skus = array_column( $tar_rate_verify_data,"sku");
                $sku_message = $this->product_model->get_list_by_sku($verify_skus,'sku,maintain_ticketed_point,supplier_code');

                $sku_message = array_map( function($skus){
                    if( $skus['maintain_ticketed_point'] == 0) {
                        return $skus;
                    }
                },$sku_message);
            }
            if( !empty($sku_message))
            {
                $sku_message_supplier = array_column( $sku_message,"supplier_code");
            }
            foreach($suggest_list as $key => $suggest_value){
                // 如果备货单不是海外仓，验证开票点是否为0
                if( isset($suggest_value['purchase_type_id']) && $suggest_value['purchase_type_id']==PURCHASE_TYPE_OVERSEA)
                {
                    if( !empty($sku_message) && isset($sku_message[$suggest_value['sku']]) &&( $sku_message[$suggest_value['sku']]['maintain_ticketed_point'] == 0))
                    {
                        $tax_rate_error[] = $suggest_value['sku'];
                        continue;
                    }

                    if( in_array($suggest_value['supplier_code'],$sku_message_supplier))
                    {
                        $tax_rate_error[] = $suggest_value['sku'];
                        continue;
                    }

                }
                $validate_create_purchase_order = $this->purchase_suggest_model->validate_create_purchase_order([$suggest_value]);
                if(!$validate_create_purchase_order){
                    unset($suggest_list[$key]);// 存在已生成采购单 跳过
                }
                $validate_suggest_status = $this->purchase_suggest_model->validate_suggest_status([$suggest_value]);
                if(!empty($validate_suggest_status)){
                    unset($suggest_list[$key]);// 备货单号已过期 跳过;
                }

                //获取有相同sku的备货单的sku
                $same_sku[$suggest_value['sku']][$key]['id'] = $suggest_value['id'];
                $same_sku[$suggest_value['sku']][$key]['purchase_amount'] = $suggest_value['purchase_amount'];
            }
            if(!empty($tax_rate_error))
            {
                $tax_rate_error_str =  implode(",",array_unique($tax_rate_error));
                $this->error_json($tax_rate_error_str."开票点为空，请维护后再点击");
            }
        }

        // 创建采购单
        $this->load->model('purchase_order_model','',true,'purchase');
        $response = $this->purchase_order_model->create_purchase_order($suggest_list);
        if($response['code']){
            $this->success_json($response['data']);
        }else{
            $this->error_json($response['msg']);
        }

    }


    /**
     * 导出线上合同单所有数据到本地
     * Temporary_test/sync_online_order_to_local?compact_number=ABD9630655,ABD9630655
     */
    public function sync_online_order_to_local(){
        set_time_limit(0);
        ini_set('memory_limit','2048M');

        $compact_number = $this->input->post_get('compact_number'); //勾选数据
        if(CG_ENV == 'dev' or CG_ENV == 'test'){

            $compact_number_list = explode(',',$compact_number);
            if (empty($compact_number_list)) {
                exit('请选择数据');
            }
            $this->slaveDb   = $this->load->database('slave',TRUE);

            $compact_number_list = implode("','",$compact_number_list);

            $sub_sql = "SELECT compact_number FROM `pur_purchase_compact` WHERE compact_number IN('{$compact_number_list}')";

            $data_list = $this->slaveDb->query($sub_sql)->result_array();
            if(empty($data_list)) exit('数据缺失');

            // 合同表
            $table_name = 'pur_purchase_compact';
            $columnName = $this->_get_column_name($table_name);
            $columnName = str_replace('is_transfer_warehouse,','',$columnName);
            $columnName = str_replace('transfer_warehouse,','',$columnName);
            $data_list = $this->slaveDb->query("SELECT {$columnName} FROM `pur_purchase_compact` WHERE compact_number IN('{$compact_number_list}') LIMIT 1000000;")->result_array();
            if($data_list) $this->db->insert_batch($table_name,$data_list);

            // 合同明细表
            $table_name = 'pur_purchase_compact_items';
            $columnName = $this->_get_column_name($table_name);
            $data_list = $this->slaveDb->query("SELECT {$columnName} FROM `pur_purchase_compact_items` WHERE compact_number IN('{$compact_number_list}') LIMIT 1000000;")->result_array();
            if($data_list) $this->db->insert_batch($table_name,$data_list);

            // 采购单表
            $table_name = 'pur_purchase_order';
            $columnName = $this->_get_column_name($table_name);
            $data_list = $this->slaveDb->query("SELECT  {$columnName} FROM `pur_purchase_order` WHERE purchase_number IN(
	SELECT purchase_number FROM `pur_purchase_compact_items` WHERE compact_number IN('{$compact_number_list}')
) LIMIT 1000000;")->result_array();
                if($data_list) $this->db->insert_batch($table_name,$data_list);

            // 采购单确认信息表
            $table_name = 'pur_purchase_order_pay_type';
            $columnName = $this->_get_column_name($table_name);
            $data_list = $this->slaveDb->query("SELECT  {$columnName} FROM `pur_purchase_order_pay_type` WHERE purchase_number IN(
	SELECT purchase_number FROM `pur_purchase_compact_items` WHERE compact_number IN('{$compact_number_list}')
) 
 LIMIT 1000000;")->result_array();
                if($data_list) $this->db->insert_batch($table_name,$data_list);

            // 采购单明细表
            $table_name = 'pur_purchase_order_items';
            $columnName = $this->_get_column_name($table_name);
            $columnName = str_replace('is_first_time,','',$columnName);
            $columnName = str_replace(',is_first_time','',$columnName);
            $data_list = $this->slaveDb->query("SELECT  {$columnName} FROM `pur_purchase_order_items` WHERE purchase_number IN(
	SELECT purchase_number FROM `pur_purchase_compact_items` WHERE compact_number IN('{$compact_number_list}')
)
 LIMIT 1000000;")->result_array();
            if($data_list) $this->db->insert_batch($table_name,$data_list);

//            echo 1;exit;

            // 备货单关联表
            $table_name = 'pur_purchase_suggest_map';
            $columnName = $this->_get_column_name($table_name);
            $data_list = $this->slaveDb->query("SELECT  {$columnName} FROM `pur_purchase_suggest_map` WHERE purchase_number IN(
	SELECT purchase_number FROM `pur_purchase_compact_items` WHERE compact_number IN('{$compact_number_list}')
)
 LIMIT 1000000;")->result_array();
            if($data_list) $this->db->insert_batch($table_name,$data_list);

            // 备货单表
            $table_name = 'pur_purchase_suggest';
            $columnName = $this->_get_column_name($table_name);
            $columnName = str_replace('is_ware,','',$columnName);
            $columnName = str_replace(',is_ware','',$columnName);
            $demand_list = $this->slaveDb->query("SELECT demand_number FROM `pur_purchase_suggest_map` WHERE purchase_number IN(
		SELECT purchase_number FROM `pur_purchase_compact_items` WHERE compact_number IN('{$compact_number_list}')
	)")->result_array();
            $demand_list = implode("','",array_column($demand_list,'demand_number'));

            $data_list = $this->slaveDb->query("SELECT  {$columnName} FROM `pur_purchase_suggest` WHERE demand_number IN('{$demand_list}')LIMIT 1000000;")->result_array();
                if($data_list) $this->db->insert_batch($table_name,$data_list);

            // 请款单表
            $table_name = 'pur_purchase_order_pay';
            $columnName = $this->_get_column_name($table_name);
            $columnName = str_replace('voucher_ad,','',$columnName);
            $columnName = str_replace(',voucher_ad','',$columnName);
            $data_list = $this->slaveDb->query("SELECT  {$columnName} FROM `pur_purchase_order_pay` WHERE pur_number IN('{$compact_number_list}')
 LIMIT 1000000;")->result_array();
                if($data_list) $this->db->insert_batch($table_name,$data_list);


            // 请款单明细表
            $table_name = 'pur_purchase_order_pay_detail';
            $columnName = $this->_get_column_name($table_name);
            $data_list = $this->slaveDb->query(" SELECT  {$columnName} FROM `pur_purchase_order_pay_detail` WHERE requisition_number IN(
 
	SELECT requisition_number FROM `pur_purchase_order_pay` WHERE pur_number IN('{$compact_number_list}')
 )
 LIMIT 1000000; ")->result_array();
            if($data_list) $this->db->insert_batch($table_name,$data_list);


            // 取消未到货明细表
            $table_name = 'pur_purchase_order_cancel_detail';
            $columnName = $this->_get_column_name($table_name);
            $data_list = $this->slaveDb->query(" SELECT  {$columnName} FROM `pur_purchase_order_cancel_detail` WHERE purchase_number IN(
	SELECT purchase_number FROM `pur_purchase_compact_items` WHERE compact_number IN('{$compact_number_list}')
)
 LIMIT 1000000;")->result_array();
            if($data_list) $this->db->insert_batch($table_name,$data_list);

            // 取消未到货表
            $table_name = 'pur_purchase_order_cancel';
            $columnName = $this->_get_column_name($table_name);
            $data_list = $this->slaveDb->query(" SELECT  {$columnName} FROM `pur_purchase_order_cancel` WHERE id IN(
	SELECT cancel_id FROM `pur_purchase_order_cancel_detail` WHERE purchase_number IN(
		SELECT purchase_number FROM `pur_purchase_compact_items` WHERE compact_number IN('{$compact_number_list}')
	)
)
 LIMIT 1000000; ")->result_array();
            if($data_list) $this->db->insert_batch($table_name,$data_list);


            // 入库记录明细表
            $table_name = 'pur_warehouse_results';
            $columnName = $this->_get_column_name($table_name);
            $data_list = $this->slaveDb->query(" SELECT  {$columnName} FROM `pur_warehouse_results` WHERE purchase_number IN(

	SELECT purchase_number FROM `pur_purchase_compact_items` WHERE compact_number IN('{$compact_number_list}')
)
LIMIT 1000000;")->result_array();
            if($data_list) $this->db->insert_batch($table_name,$data_list);

            // 入库记录主表
            $table_name = 'pur_warehouse_results_main';
            $columnName = $this->_get_column_name($table_name);
            $data_list = $this->slaveDb->query(" SELECT  {$columnName} FROM `pur_warehouse_results_main` WHERE purchase_number IN(

	SELECT purchase_number FROM `pur_purchase_compact_items` WHERE compact_number IN('{$compact_number_list}')
)
LIMIT 1000000; ")->result_array();
            if($data_list) $this->db->insert_batch($table_name,$data_list);



            echo 'sss';exit;
            exit;
        }
        exit;
    }

    public function _get_column_name($table_name){

        $columnName = "SELECT GROUP_CONCAT(column_name) as columnName  FROM information_schema.columns
                        WHERE table_schema='yibai_purchase' AND table_name='{$table_name}';";
        $columnName = $this->slaveDb->query($columnName)->row_array();
        $columnName = $columnName['columnName'];
        if($table_name == 'pur_warehouse_results' or $table_name == 'pur_warehouse_results_main'){
            $columnName = str_replace('id,items_id','items_id',$columnName);
        }else{
            $columnName = ltrim($columnName,'id,');
        }

        return $columnName;
    }

    //根据表格数据批量取消
    public function cancel_order_by_excel()
    {

        //获取的excel数据
        $error_list = [] ;

        $this->load->model('purchase/purchase_order_determine_model');
        $this->load->model('abnormal/Report_loss_model');
        $this->load->model('statement/Charge_against_records_model');

        $json_data = $this->input->get_post('json_data');

        $json_data = explode(',',$json_data);

        $demand_number_map = [];//采购明细id数量映射

        $demand_number_arr = []; //所有备货单





        //循环excel_data数据
        foreach ($json_data as $data) {
            $info_data = explode('-',$data);
            $demand_number = $info_data[0];
            $cancel_num = (int)$info_data[1];


            //备货单
            $items_info = $this->db->select('id')->from('purchase_order_items ') ->where('demand_number',$demand_number)->get()->row_array();


            if (empty($items_info['id'])) {
                $error_list[]  = $demand_number.'单号不存在';
                continue;

            }
            $demand_number_map[$items_info['id']] = $cancel_num;

            $ids = [$items_info['id']];

            //10.部分到货等待剩余到货 7.等待到货
            $status_array = [PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL];
            $change_status = [PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT];//信息修改状态


            $is_change = $this->purchase_order_determine_model->is_order_change_status($ids, $change_status);
            if ($is_change) {

                $error_list[]  = $demand_number."PO:{$is_change}状态是“信息修改驳回”或者“信息修改待审核”的，不允许点击，信息修改通过后再申请";
                continue;

            }

            $bool = $this->purchase_order_determine_model->order_status($ids, $status_array);
            if (!$bool) {
                $error_list[]  = $demand_number.'只能是部分到货等待剩余到货及等待到货，才能操作';
                continue;

            }
            //请款中的订单状态不可以申请(判断是否有在申请请款中)
            $pay_status=$this->purchase_order_determine_model->order_pay_get($ids);
            if($pay_status){
                $error_list[]  = $demand_number.'请款中的订单不可以申请取消未到货';
                continue;

            }

            //需验证入库数量>=采购数量，如果是，那么不允许操作
            $temp=$this->purchase_order_determine_model->warehouse_results_get($ids);
            if(!empty($temp)){
                $error_list[]  = $demand_number.'采购单号及sku:'.implode(',', $temp).',请与仓库确定已入库数量后再取消未到货';
                continue;

            }

            //如果还有上一次取消未完结的存在，那么不允许进行该操作
            $cencel_status=$this->purchase_order_determine_model->get_cancel_status($ids);
            if(!empty($cencel_status)){
                $error_list[]  = $demand_number.'采购单号及sku'.implode(',', $cencel_status).'取消未到货状态未审核完毕或有驳回单，不允许再次申请';
                continue;

            }


            $order_items = $this->purchase_order_items_model->get_list_by_ids($ids,'purchase_number');
            $purchase_number= array_column(isset($order_items)?$order_items:[], 'purchase_number');
            if(empty($purchase_number)) {
                $error_list[]  = $demand_number.'采购单主表记录不存在';
                continue;

            }
            $purchase_numbers= array_unique($purchase_number);





            $unfinished_info = '';
            $charge_against_record_info ='';
            //如果有报损中的,那么不允许进行该操作
            foreach ($purchase_numbers as $purchase_number){
                $unfinished = $this->Report_loss_model->unfinished_loss_status($purchase_number);
                if ($unfinished){
                    $unfinished_info = $demand_number.'采购单:'.$purchase_number.' 报损状态未完结';
                }

                $charge_against_record = $this->Charge_against_records_model->get_ca_audit_status($purchase_number,2,[CHARGE_AGAINST_STATUE_WAITING_AUDIT,CHARGE_AGAINST_STATUE_WAITING_FINANCE_AUDIT]);
                if ($charge_against_record){
                    $charge_against_record_info = $demand_number.'采购单:'.$purchase_number.' 存在退款冲销中的记录';
                }
            }

            if (!empty($unfinished_info)) {
                $error_list[] = $unfinished_info ;
                continue;

            }

            if (!empty($charge_against_record_info)) {
                $error_list[] = $charge_against_record_info ;
                continue;

            }

            $cancel_info = $this->purchase_order_determine_model->get_cancel_unarrived_goods($ids);
            if (empty($cancel_info)) {

                $error_list[] = $demand_number.'采购单不存在';


            }
            $cancel_info = $cancel_info['order_list'];
            //取消信息
            $cancel_param = [
                'ids' => $items_info['id'],
                'create_note' => '批量取消',
                'total_cancelled'=> $cancel_num,
                'total_freight'  => $cancel_info[0]['total_freight'],
                'total_process_cost'=>$cancel_info[0]['total_process_cost'],
                'total_discount'     =>$cancel_info[0]['total_discount'],
                'total_price'         =>round($cancel_num*$cancel_info[0]['order_info'][0]['purchase_unit_price'],2),
                'cancel_ctq'          =>[$items_info['id']=>$cancel_info[0]['order_info'][0]['cancel_ctq']],
                'confirm_amount'          =>[$items_info['id']=>$cancel_info[0]['order_info'][0]['confirm_amount']],
                'purchase_unit_price'          =>[$items_info['id']=>$cancel_info[0]['order_info'][0]['purchase_unit_price']],
                'instock_qty'          =>[$items_info['id']=>$cancel_info[0]['order_info'][0]['instock_qty']],
                'loss_amount'          =>[$items_info['id']=>$cancel_info[0]['order_info'][0]['loss_amount']],
                'application_cancel_ctq'          =>[$items_info['id']=>$cancel_num],
                'freight'          =>[$items_info['id']=>$cancel_info[0]['order_info'][0]['freight']],
                'process_cost'          =>[$items_info['id']=>$cancel_info[0]['order_info'][0]['process_cost']],
                'discount'          =>[$items_info['id']=>$cancel_info[0]['order_info'][0]['discount']],
                'image_list'        =>['xx.com'],
                'create_user_id'    =>'0',
                'create_user_name'   =>'batch'


            ];


            $temps = $this->purchase_order_determine_model->get_cancel_unarrived_goods_save($cancel_param);


            if($temps['bool']){

                    $audit_info = $this->db->select('cancel_id')->from('purchase_order_cancel_detail')->where('items_id',$items_info['id'])->get()->row_array();
                    $temps = $this->purchase_order_determine_model->cancel_unarrived_goods_examine_save($audit_info['cancel_id'], '批量取消审核', 1);

                if (!$temps['bool']) {

                    $error_list[] = $demand_number.$temps['msg'];
                    continue;

                }







            }else{

                $error_list[] = $demand_number.$temps['msg'];

            }














        }

        print_R($error_list);

        echo 'done';




    }

    public function cancel_order_by_excel_v2()
    {

        //获取的excel数据
        $error_list = [] ;

        $this->load->model('purchase/purchase_order_determine_model');
        $this->load->model('abnormal/Report_loss_model');
        $this->load->model('statement/Charge_against_records_model');
        $this->load->model('purchase/Purchase_order_items_model');



        $json_data = $this->input->get_post('json_data');

        $json_data = explode(',',$json_data);

        $demand_number_map = [];//采购明细id数量映射

        $demand_number_arr = []; //所有备货单


        foreach ($json_data as $data) {
            $info_data = explode('-',$data);
            $demand_number = $info_data[0];
            $cancel_num = (int)$info_data[1];


            //备货单
            $items_info = $this->db->select('id')->from('purchase_order_items ') ->where('demand_number',$demand_number)->get()->row_array();


            if (empty($items_info['id'])) {
               exit ($demand_number.'单号不存在');

            }
            $demand_number_map[$items_info['id']] = $cancel_num;
            $demand_number_arr[] = $demand_number;


        }

        if (!empty($demand_number_arr)) {
            $purchase_number_list = $this->db->select('purchase_number')->from('purchase_order_items ') ->where_in('demand_number',$demand_number_arr)->group_by('purchase_number')->get()->result_array();





        } else {
            exit('数据为空');
        }

        $purchase_number_list = array_column($purchase_number_list,'purchase_number');





        //循环excel_data数据
        foreach ($purchase_number_list as $purchase_no) {

            $items_info_list = $this->Purchase_order_items_model->get_item($purchase_no);


            $ids =array_column($items_info_list,'id');


            //10.部分到货等待剩余到货 7.等待到货
            $status_array = [PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL];
            $change_status = [PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT];//信息修改状态


            $is_change = $this->purchase_order_determine_model->is_order_change_status($ids, $change_status);
            if ($is_change) {

                $error_list[]  = $demand_number."PO:{$is_change}状态是“信息修改驳回”或者“信息修改待审核”的，不允许点击，信息修改通过后再申请";
                continue;

            }

            $bool = $this->purchase_order_determine_model->order_status($ids, $status_array);
            if (!$bool) {
                $error_list[]  = $demand_number.'只能是部分到货等待剩余到货及等待到货，才能操作';
                continue;

            }
            //请款中的订单状态不可以申请(判断是否有在申请请款中)
            $pay_status=$this->purchase_order_determine_model->order_pay_get($ids);
            if($pay_status){
                $error_list[]  = $demand_number.'请款中的订单不可以申请取消未到货';
                continue;

            }

            //需验证入库数量>=采购数量，如果是，那么不允许操作
            $temp=$this->purchase_order_determine_model->warehouse_results_get($ids);
            if(!empty($temp)){
                $error_list[]  = $demand_number.'采购单号及sku:'.implode(',', $temp).',请与仓库确定已入库数量后再取消未到货';
                continue;

            }

            //如果还有上一次取消未完结的存在，那么不允许进行该操作
            $cencel_status=$this->purchase_order_determine_model->get_cancel_status($ids);
            if(!empty($cencel_status)){
                $error_list[]  = $demand_number.'采购单号及sku'.implode(',', $cencel_status).'取消未到货状态未审核完毕或有驳回单，不允许再次申请';
                continue;

            }


            $order_items = $this->purchase_order_items_model->get_list_by_ids($ids,'purchase_number');
            $purchase_number= array_column(isset($order_items)?$order_items:[], 'purchase_number');
            if(empty($purchase_number)) {
                $error_list[]  = $demand_number.'采购单主表记录不存在';
                continue;

            }
            $purchase_numbers= array_unique($purchase_number);







            $unfinished_info = '';
            $charge_against_record_info ='';
            //如果有报损中的,那么不允许进行该操作
            foreach ($purchase_numbers as $purchase_number){
                $unfinished = $this->Report_loss_model->unfinished_loss_status($purchase_number);
                if ($unfinished){
                    $unfinished_info = $demand_number.'采购单:'.$purchase_number.' 报损状态未完结';
                }

                $charge_against_record = $this->Charge_against_records_model->get_ca_audit_status($purchase_number,2,[CHARGE_AGAINST_STATUE_WAITING_AUDIT,CHARGE_AGAINST_STATUE_WAITING_FINANCE_AUDIT]);
                if ($charge_against_record){
                    $charge_against_record_info = $demand_number.'采购单:'.$purchase_number.' 存在退款冲销中的记录';
                }
            }

            if (!empty($unfinished_info)) {
                $error_list[] = $unfinished_info ;
                continue;

            }

            if (!empty($charge_against_record_info)) {
                $error_list[] = $charge_against_record_info ;
                continue;

            }

            $cancel_info = $this->purchase_order_determine_model->get_cancel_unarrived_goods($ids);
            if (empty($cancel_info)) {

                $error_list[] = $demand_number.'采购单不存在';


            }

            $cancel_ctq_arr = array_column($cancel_info['order_list'][0]['order_info'],'cancel_ctq','id');
            $confirm_amount_arr = array_column($cancel_info['order_list'][0]['order_info'],'confirm_amount','id');
            $purchase_unit_price_arr = array_column($cancel_info['order_list'][0]['order_info'],'purchase_unit_price','id');
            $instock_qty_arr    = array_column($cancel_info['order_list'][0]['order_info'],'instock_qty','id');
            $loss_amount_arr    = array_column($cancel_info['order_list'][0]['order_info'],'loss_amount','id');
            $freight_arr    = array_column($cancel_info['order_list'][0]['order_info'],'freight','id');
            $process_cost_arr    = array_column($cancel_info['order_list'][0]['order_info'],'process_cost','id');
            $discount_arr    = array_column($cancel_info['order_list'][0]['order_info'],'discount','id');





            //通过明细组装数据
            $total_cancelled = 0;
            $total_price = 0;
            $total_freight = 0;
            $total_process_cost = 0;
            $total_discount=0;
            $post_param = [];

            foreach ($items_info_list as $one_item) {
                $post_param['cancel_ctq'][$one_item['id']] = $cancel_ctq_arr[$one_item['id']];
                $post_param['confirm_amount'][$one_item['id']] = $confirm_amount_arr[$one_item['id']];
                $post_param['purchase_unit_price'][$one_item['id']] = $purchase_unit_price_arr[$one_item['id']];
                $post_param['instock_qty'][$one_item['id']] = $instock_qty_arr[$one_item['id']];
                $post_param['loss_amount'][$one_item['id']] = $loss_amount_arr[$one_item['id']];
                $post_param['freight'][$one_item['id']] = $freight_arr[$one_item['id']];
                $post_param['process_cost'][$one_item['id']] = $process_cost_arr[$one_item['id']];
                $post_param['discount_arr'][$one_item['id']] = $discount_arr[$one_item['id']];
                $application_cancel_ctq = $demand_number_map[$one_item['id']]??1;
                $post_param['application_cancel_ctq'][$one_item['id']] = $application_cancel_ctq;//如果传入参数没有就为1

                $total_price += $application_cancel_ctq*$purchase_unit_price_arr[$one_item['id']];
                $total_cancelled += $application_cancel_ctq;
                $total_freight   += $freight_arr[$one_item['id']];
                $total_process_cost += $process_cost_arr[$one_item['id']];
                $total_discount     += $discount_arr[$one_item['id']];

            }

            $post_param['ids'] = implode(',',$ids);
            $post_param['create_note'] = '批量取消';
            $post_param['total_cancelled'] = $total_cancelled;
            $post_param['total_freight']   = round($total_freight,2);
            $post_param['total_process_cost']   = round($total_process_cost,2);
            $post_param['total_discount']   = round($total_discount,2);
            $post_param['total_price']   = round($total_price,2);
            $post_param['image_list']   = ['xx.com'];
            $post_param['create_user_id']   = 0;
            $post_param['create_user_name']   = 'batch';









            $temps = $this->purchase_order_determine_model->get_cancel_unarrived_goods_save($post_param);


            if($temps['bool']){

                $audit_info = $this->db->select('cancel_id')->from('purchase_order_cancel_detail')->where('purchase_number',$purchase_no)->get()->result_array();
                if (empty($audit_info)) {
                    $error_list[] = $demand_number.'对应order_cancel_detail不存在';
                    continue;

                }
                $temps = $this->purchase_order_determine_model->cancel_unarrived_goods_examine_save($audit_info[0]['cancel_id'], '批量取消审核', 1);

                if (!$temps['bool']) {

                    $error_list[] = $demand_number.$temps['msg'];
                    continue;

                }





            }else{

                $error_list[] = $demand_number.$temps['msg'];

            }














        }

        print_R($error_list);

        echo 'done';




    }

    //将取消未到货金额有问题的刷一下
    public function handle_cancel_data()
    {
        $sql ="SELECT 
mm.cancel_number 取消批次,mm.audit_status 取消状态,mm.create_time 取消时间,mm.id 取消ID,nn.cancel_id 取消ID,

ROUND(mm.freight,2) AS 主表运费,
ROUND(mm.discount,2) AS 主表优惠额,
ROUND(mm.process_cost,2) AS 主表加工费,

ROUND(SUM(nn.freight),2) AS 明细表PO维度汇总运费,
ROUND(SUM(nn.discount),2) AS 明细表PO维度汇总优惠额,
ROUND(SUM(nn.process_cost),2) AS 明细表PO维度汇总加工费

FROM (
  SELECT 
  A.id,A.`cancel_number`,A.`audit_status`,A.`create_time`,A.`freight`,A.`discount`,A.`process_cost`
  FROM `pur_purchase_order_cancel` AS A 
  WHERE (A.`audit_status` IN(60,70,90) OR (A.`audit_status` IN(30,40) AND A.is_edit=2)) AND A.`create_time`>'2021-01-01 00:00:00'
  GROUP BY A.id
) AS mm
LEFT JOIN (
  SELECT B.cancel_id,B.purchase_number,B.`freight`,B.`discount`,B.`process_cost`
  FROM `pur_purchase_order_cancel_detail` AS B
  LEFT JOIN `pur_purchase_order_cancel` AS A ON A.id=B.`cancel_id`
  WHERE (A.`audit_status` IN(60,70,90) OR (A.`audit_status` IN(30,40) AND A.is_edit=2)) AND A.`create_time`>'2021-01-01 00:00:00'
  GROUP BY B.cancel_id,B.purchase_number
) AS nn ON mm.id=nn.cancel_id

GROUP BY mm.id
HAVING 主表运费 <> 明细表PO维度汇总运费
OR 主表优惠额 <> 明细表PO维度汇总优惠额
OR 主表加工费 <> 明细表PO维度汇总加工费;";

        $cancel_data = $this->db->query($sql)->result_array();
        $update_data_batch = [];//批量更新数据
        if ($cancel_data) {
            foreach ($cancel_data as $data) {
                $application_cancel_fright= [];
                $application_cancel_discount=[];
                $application_cancel_process_cost=[];
                $application_cancel_total_price =[];


                $cancel_detail = $this->db->select('*')->from('pur_purchase_order_cancel_detail')->where('cancel_id',$data['取消ID'])->get()->result_array();
                if (!empty($cancel_detail)) {
                    foreach ($cancel_detail as $detail) {
                        if (!isset($application_cancel_fright[$detail['purchase_number']])){
                            $application_cancel_fright[$detail['purchase_number']]=$detail['freight'];
                            $application_cancel_discount[$detail['purchase_number']]=$detail['discount'];
                            $application_cancel_process_cost[$detail['purchase_number']]=$detail['process_cost'];


                        }

                        if (!isset($application_cancel_total_price[$detail['purchase_number']])) {
                            $application_cancel_total_price[$detail['purchase_number']] = $detail['item_total_price'];

                        } else {
                            $application_cancel_total_price[$detail['purchase_number']]+= $detail['item_total_price'];

                        }

                    }


                    $cal_application_cancel_fright = round(array_sum($application_cancel_fright),2);
                    $cal_application_cancel_discount = round(array_sum($application_cancel_discount),2);
                    $cal_application_cancel_process_cost = round(array_sum($application_cancel_process_cost),2);
                    $cal_application_cancel_total_price  =round(array_sum($application_cancel_total_price)+array_sum($application_cancel_fright)-array_sum($application_cancel_discount)+array_sum($application_cancel_process_cost),2);

                    $update_data_batch[] = ['id'=>$data['取消ID'],'total_price'=>$cal_application_cancel_total_price,'freight'=>$cal_application_cancel_fright,'discount'=>$cal_application_cancel_discount,'process_cost'=>$cal_application_cancel_process_cost];




                }

            }

        }
        if (!empty($update_data_batch)) {
            $this->db->update_batch('pur_purchase_order_cancel', $update_data_batch,'id');


        }
        echo 'done';


    }





}
