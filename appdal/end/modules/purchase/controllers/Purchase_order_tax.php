<?php

/**
 * Created by PhpStorm.
 * 含税订单控制器
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */
class Purchase_order_tax extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('purchase_order_tax_model','purchase_tax');
        $this->load->model('warehouse_results_model','warehouse_results',false,'warehouse');
        $this->load->model('warehouse_model','warehouse_model',false,'warehouse');
        $this->load->model('purchase_user_model','purchase_user_model',false,'user');
        $this->load->model('product_model','product_model',false,'product');
        $this->load->model('report_loss_model','report_loss_model',false,'abnormal');
        $this->load->model('declare_customs_model','declare_customs');
        $this->load->model('purchase_invoice_model','invoice_model');

        $this->load->model('Purchase_financial_audit_model', 'm_financial_audit', false, 'purchase');
        $this->load->model('Purchase_order_tax_list_model', 'm_tax_list', false, 'purchase');
        $this->load->model('Purchase_invoice_list_model', 'm_invoice_list', false, 'purchase');
        $this->load->model('Purchase_order_cancel_model', 'm_cancel', false, 'purchase');
        $this->load->model('Purchase_order_model', 'orders_model', false, 'purchase');
        $this->load->helper('status_order');

    }

// **************** 以下为旧版本 ***************/

    /**
     * 含税订单跟踪页面-点击入库数量弹出的列表
     * /purchase/purchase_order_tax/warehousing_list
     * @author Jaden 2019-1-10
     */
    public function warehousing_list(){
        $purchase_number = $this->input->get_post('purchase_number'); // 采购单号
        $sku = $this->input->get_post('sku');
        //分页
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page-1)*$limit;

        if(empty($purchase_number) or empty($sku)){
            $this->error_data_json('采购单号和SKU不能为空！');
        }else{
            $field='sku,purchase_number,instock_date,receipt_number,instock_qty,instock_user_name';
            $warehousing_list_info = $this->warehouse_results->get_warehousing_list($purchase_number,$sku,$offset, $limit,$field);
            $warehousing_list_info['key'] = array('sku','采购单号','入库时间','入库单号','数量','入库人');
            $warehousing_list_info['page_data']['pages'] = ceil($warehousing_list_info['page_data']['total']/$limit);
            $warehousing_list_info['page_data']['offset'] = $page;
            if(!empty($warehousing_list_info['value'])){
                $this->success_json($warehousing_list_info);
            }else{
                $this->error_data_json('暂无相关数据');
            }
        }
    }


    /**
     * 含税订单跟踪页面-点击已报关数量弹出的列表
     * /purchase/purchase_order_tax/declare_customs
     * @author Jaden 2019-1-10
     */
    public function declare_customs(){
        $purchase_number = $this->input->get_post('purchase_number'); // 采购单号
        $sku = $this->input->get_post('sku');
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page-1)*$limit;
        if(empty($purchase_number) or empty($sku)){
            $this->error_data_json('采购单号和SKU不能为空！');
        }else{
            $field='sku,purchase_number,customs_time,customs_number,customs_name,customs_unit,customs_quantity,unit_price';
            $declare_customs_list_info = $this->declare_customs->declare_customs_list($purchase_number,$sku,$offset, $limit,$field);
            $declare_customs_list_info['key'] = array('sku','采购单号','报关时间','报关单号','报关品名','报关单位','报关数量','含税单价');
            $declare_customs_list_info['page_data']['pages'] = ceil($declare_customs_list_info['page_data']['total']/$limit);
            $declare_customs_list_info['page_data']['offset'] = $page;
            if(!empty($declare_customs_list_info['value'])){
                $this->success_json($declare_customs_list_info);
            }else{
                $this->error_data_json('暂无相关数据');
            }
        }
    }

    /**
     * 含税订单跟踪页面-点击已开票弹出的列表
     * /purchase/purchase_order_tax/invoiced_list
     * @author Manson 2019-1-10
     */
    public function invoiced_list(){
        $this->load->model('Purchase_invoice_list_model','m_invoice_list',false,'purchase');
        $purchase_number = $this->input->get_post('purchase_number'); // 采购单号
        $sku = $this->input->get_post('sku');
        //分页
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page-1)*$limit;

        if(empty($purchase_number) or empty($sku)){
            $this->error_data_json('采购单号和SKU不能为空！');
        }else{
            $params = ['purchase_number'=>$purchase_number,'sku'=>$sku];
            $field='a.*,b.create_time as create_invoice_time';
            $warehousing_list_info = $this->m_invoice_list->get_invoice_detail($params,$field,$offset, $limit);
            $warehousing_list_info['key'] = ['SKU','采购单号','发票清单号','生成发票清单时间','发票代码(左)','发票代码(右)','已开票数量','票面税率','发票金额','开票时间','审核状态'];
            $warehousing_list_info['page_data']['pages'] = ceil($warehousing_list_info['page_data']['total']/$limit);
            $warehousing_list_info['page_data']['offset'] = $page;
            if(!empty($warehousing_list_info['value'])){
                $this->success_json($warehousing_list_info);
            }else{
                $this->error_data_json('暂无相关数据');
            }
        }
    }

    public function tax_order_tacking_sum(){

        $params = [
            'purchase_number' => $this->input->get_post('purchase_number'), // 采购单号
            'buyer_id' => $this->input->get_post('buyer_id'), // 采购员
            'customs_number' => $this->input->get_post('customs_number'),//报关单号
            'sku' => $this->input->get_post('sku'), // SKU
            'is_check_goods' => $this->input->get_post('is_check_goods'),//是否商检
            'customs_name' => $this->input->get_post('customs_name'),//报关品名
            'invoice_code_left' => $this->input->get_post('invoice_code_left'),//发票号码(左)
            'invoice_code_right' => $this->input->get_post('invoice_code_right'),//发票号码(右)
            'supplier_code' => $this->input->get_post('supplier_code'), // 供应商
            'purchase_type_id' => $this->input->get_post('purchase_type_id'), // 业务线(1国内2海外3FBA)
            'order_create_time_start' => $this->input->get_post('order_create_time_start'),//采购下单开始时间
            'order_create_time_end' => $this->input->get_post('order_create_time_end'),//采购下单结束时间
            'customs_time_start' => $this->input->get_post('customs_time_start'),//报关日期开始时间
            'customs_time_end' => $this->input->get_post('customs_time_end'),//报关日期结束时间
            'invoice_is_abnormal' => $this->input->get_post('invoice_is_abnormal'),//开票是否异常 1[是] 2[否]
//            'is_end' => $this->input->get_post('is_end'),//是否完结 1[是] 2[否]
            'customs_status' => $this->input->get_post('customs_status'),//报关状态 1[已报关] 2[部分报关] 3[未报关]
            'purchase_order_status' => $this->input->get_post('purchase_order_status'),//采购单状态
            'min_no_invoice' => $this->input->get_post('min_no_invoice'),//未开票数(最小值)
            'max_no_invoice' => $this->input->get_post('max_no_invoice'),//未开票数(最大值)
            'customs_code' => $this->input->get_post('customs_code'),//出口海关编码
            'invoice_number' => $this->input->get_post('invoice_number'),//发票清单号
            'pay_status' => $this->input->get_post('pay_status'),//付款状态
            'compact_number' => $this->input->get_post('compact_number'),//合同号
            'invoice_status' => $this->input->get_post('invoice_status'),//开票状态
            'contract_invoicing_status' => $this->input->get_post('contract_invoicing_status'),//合同开票状态
            'instock_time_start' => $this->input->get_post('instock_time_start'),//入库开始时间
            'instock_time_end' => $this->input->get_post('instock_time_end'),//入库结束时间
            'export_save' => 1,
            'is_gateway' => $this->input->get_post('is_gateway'),
            'is_drawback' => $this->input->get_post('is_drawback'),     // 是否退税 0否，1是
            'demand_number' => $this->input->get_post('demand_number'),
            'completion_time_start' => $this->input->get_post('completion_time_start'),
            'completion_time_end' => $this->input->get_post('completion_time_end'),
            'statement_number' => $this->input->get_post('statement_number')
        ];

        $limit = 1;
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = 1;

        $orders_info_all = $this->m_tax_list->get_tax_order_tacking_list($params, $offset, $limit,'ppoi.*','');
        $total = $orders_info_all['page_data']['total'];
        $limit = 2000;
        $page = ceil($total/$limit);
        $money = $warehouse_money = 0;
        for($i=1;$i<=$page;++$i){

            $offset = ($i - 1) * $limit;

            $orders_info_all = $this->m_tax_list->get_tax_order_tacking_list($params, $offset, $limit);
            $orders_info_all = $orders_info_all['value'];
            $orders_count = $this->format_list_data_sum($orders_info_all);
            if(!empty($orders_count)) {
                $money += isset($orders_count['money']) ? round($orders_count['money'], 3) : 0;
                $warehouse_money += isset($orders_count['warehouse_money']) ? round($orders_count['warehouse_money'], 3) : 0;
            }
        }
        $this->success_json(['money'=>$money,'warehouse_money'=>$warehouse_money]);
    }



    // -------------------------- new -----------------------

    /**
     * 含税订单跟踪列表
     * 合同单,是退税,变成等待到货后推送到该页面
     * /purchase/purchase_order_tax/tax_order_tacking_list
     * @author Manson 2019-11-27
     */
    public function tax_order_tacking_list()
    {
        //筛选项参数
        $params = [
            'purchase_number' => $this->input->get_post('purchase_number'), // 采购单号
            'buyer_id' => $this->input->get_post('buyer_id'), // 采购员
            'customs_number' => $this->input->get_post('customs_number'),//报关单号
            'sku' => $this->input->get_post('sku'), // SKU
            'is_check_goods' => $this->input->get_post('is_check_goods'),//是否商检
            'customs_name' => $this->input->get_post('customs_name'),//报关品名
            'invoice_code_left' => $this->input->get_post('invoice_code_left'),//发票号码(左)
            'invoice_code_right' => $this->input->get_post('invoice_code_right'),//发票号码(右)
            'supplier_code' => $this->input->get_post('supplier_code'), // 供应商
            'purchase_type_id' => $this->input->get_post('purchase_type_id'), // 业务线(1国内2海外3FBA)
            'order_create_time_start' => $this->input->get_post('order_create_time_start'),//采购下单开始时间
            'order_create_time_end' => $this->input->get_post('order_create_time_end'),//采购下单结束时间
            'customs_time_start' => $this->input->get_post('customs_time_start'),//报关日期开始时间
            'customs_time_end' => $this->input->get_post('customs_time_end'),//报关日期结束时间
            'invoice_is_abnormal' => $this->input->get_post('invoice_is_abnormal'),//开票是否异常 1[是] 2[否]
//            'is_end' => $this->input->get_post('is_end'),//是否完结 1[是] 2[否]
            'customs_status' => $this->input->get_post('customs_status'),//报关状态 1[已报关] 2[部分报关] 3[未报关]
            'purchase_order_status' => $this->input->get_post('purchase_order_status'),//采购单状态
            'min_no_invoice' => $this->input->get_post('min_no_invoice'),//未开票数(最小值)
            'max_no_invoice' => $this->input->get_post('max_no_invoice'),//未开票数(最大值)
            'customs_code' => $this->input->get_post('customs_code'),//出口海关编码
            'invoice_number' => $this->input->get_post('invoice_number'),//发票清单号
            'pay_status' => $this->input->get_post('pay_status'),//付款状态
            'compact_number' => $this->input->get_post('compact_number'),//合同号
            'invoice_status' => $this->input->get_post('invoice_status'),//开票状态
            'contract_invoicing_status' => $this->input->get_post('contract_invoicing_status'),//合同开票状态
            'instock_time_start' => $this->input->get_post('instock_time_start'),//入库开始时间
            'instock_time_end' => $this->input->get_post('instock_time_end'),//入库结束时间
            'export_save' => 1,
            'is_gateway' => $this->input->get_post('is_gateway'),
            'is_drawback' => $this->input->get_post('is_drawback'),     // 是否退税 0否，1是
            'demand_number' => $this->input->get_post('demand_number'), // 备货单号
            'completion_time_start' => $this->input->get_post('completion_time_start'),
            'completion_time_end' => $this->input->get_post('completion_time_end'),
            'statement_number' => $this->input->get_post('statement_number')
        ];
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;

        $orders_info = $this->m_tax_list->get_tax_order_tacking_list($params, $offset, $limit);
//        pr($orders_info);exit;
        $purchase_tax_list = $orders_info['value'];


        $role_name=get_user_role();//当前登录角色
        $data_role= getRolexiao();
        $purchase_tax_list = ShieldingData($purchase_tax_list,['supplier_name','supplier_code'],$role_name,NULL);

        $this->format_list_data($purchase_tax_list);
        //采购员
        $user_list = $this->purchase_user_model->get_list();
        $drop_down_box['purchase_user_list'] = array_column($user_list, 'name','id');

        $drop_down_box['is_check_goods_list'] = check_goods();//是否商检
        //业务线
        $purchase_type_list = getPurchaseType();
        unset($purchase_type_list[1]);
        $drop_down_box['purchase_type_id'] = $purchase_type_list;
        $drop_down_box['is_abnormal'] = abnormal_status_type();//开票是否异常
        $drop_down_box['is_end'] = end_status();//是否完结
        $drop_down_box['customs_status'] = customs_statu_type();//报关状态
        $drop_down_box['invoice_status'] = getInvoiceStatus();//开票状态
        $drop_down_box['contract_invoice_status'] = getContractInvoiceStatus();//合同开票状态
        $drop_down_box['is_gateway'] = [1=>'是',2=>'否'];
        $order_status_list = getPurchaseStatus();//采购单状态
        foreach ($order_status_list as $key => $value) {
            if($key<7){
                if ($key==2){

                }else{
                    unset($order_status_list[$key]);
                }
            }
        }
        $drop_down_box['order_status_list'] = $order_status_list;
        $drop_down_box['pay_status_list'] = getPayStatus();
        $orders_info['value'] = $purchase_tax_list;
        $orders_info['drop_down_box'] = $drop_down_box;
        $orders_info['page_data']['pages'] = ceil($orders_info['page_data']['total']/$limit);
        $orders_info['page_data']['offset'] = $page;
        $page_data = $orders_info['page_data'];
        unset($orders_info['page_data']);
//        $orders_info_all = $this->m_tax_list->get_tax_order_tacking_list($params, $offset, $limit,'ppoi.*','',true);
//        $orders_info_all = $orders_info_all['value'];
//        $orders_count = $this->format_list_data_sum($orders_info_all);
//        $page_data['money'] = isset($orders_count['money'])?round($orders_count['money'],3):0;
//        $page_data['warehouse_money'] = isset($orders_count['warehouse_money'])?round($orders_count['warehouse_money'],3):0;
        $this->success_json($orders_info,$page_data);
    }


    public function format_list_data(&$purchase_tax_list)
    {
        //报关信息
        $purchase_number_list = array_column(isset($purchase_tax_list)?$purchase_tax_list:[], 'purchase_number');
        $declare_customs_map = $this->declare_customs->get_customs_clearance_details($purchase_number_list);

        //报损数量
        $loss_amount_map = $this->report_loss_model->get_loss_amount_by_purchase_number($purchase_number_list);

        //开票信息
        $invoice_map = $this->m_invoice_list->get_invoice_info($purchase_number_list);
        unset($purchase_number_list);

        //SKU集合
        $skus = array_unique(array_column(isset($purchase_tax_list)?$purchase_tax_list:[], 'sku'));
        $product_field = 'sku,customs_code,declare_cname,declare_unit,export_cname,is_inspection';
        $sku_arr = $this->product_model->get_list_by_sku($skus,$product_field);

        //仓库名称
        $warehouse_code_arr = array_column($purchase_tax_list, 'warehouse_code');
        $warehouse_name_arr = $this->warehouse_model->get_code2name_list($warehouse_code_arr);

        //取消
        $items_id_list = array_column($purchase_tax_list,'id');
        $cancel_qty_map = $this->m_cancel->get_cancel_qty_by_item_id($items_id_list);

        //入库
        $instock_field = 'items_id,instock_date';
        $instock_map = $this->warehouse_results->get_instock_list_by_items_ids($items_id_list,$instock_field);



        $compactNumbers = array_column($purchase_tax_list,'compact_number');
        $comData = [];
        if(!empty($compactNumbers)){
            foreach($compactNumbers as $numbersdata){

                $flag =  $this->m_financial_audit->getCompactData($numbersdata);
                if(!empty($flag)){

                    $comData[$numbersdata] = CONTRACT_INVOICING_STATUS_NOT;
                }else{
                    $comData[$numbersdata] = CONTRACT_INVOICING_STATUS_END;
                }
            }
        }
        foreach ($purchase_tax_list as $key => &$item) {
            //查找报关数量
            $_tag = sprintf('%s_%s',$item['purchase_number'],$item['sku']);
            $item['customs_number'] = $declare_customs_map[$_tag]['customs_number']??[];//报关单号
            $item['total_customs_quantity'] = $declare_customs_map[$_tag]['customs_quantity']??0;//报关数量
            $item['customs_type'] = $declare_customs_map[$_tag]['customs_type']??'';//报关型号
            $item['customs_name'] = $declare_customs_map[$_tag]['customs_name']??'';//报关品名
            $item['customs_time'] = $declare_customs_map[$_tag]['customs_time']??'';//报关时间
            $item['customs_unit'] = $declare_customs_map[$_tag]['customs_unit']??'';//报关单位

            //发票清单号
            $item['invoice_number_list'] = isset($invoice_map[$_tag]['invoice_number_list'])?array_unique($invoice_map[$_tag]['invoice_number_list']):[];
            //已生成发票清单
            $item['invoice_count'] = count($item['invoice_number_list']);
            //发票代码(左)
            $item['invoice_code_left_list'] = $invoice_map[$_tag]['invoice_code_left_list']??[];
            //发票代码(右)
            $item['invoice_code_right_list'] = $invoice_map[$_tag]['invoice_code_right_list']??[];


            //报损数量
            $loss_quantity = $loss_amount_map[$_tag]['loss_amount']??0;
            $item['loss_quantity'] = $loss_quantity;

            //取消数量
            $cancel_qty = $cancel_qty_map[$item['id']]??0;
            $item['cancel_qty'] = $cancel_qty;
//            //实际采购数量 【实际采购数量 = 采购数量-取消数量】
            $item['act_purchase_qty'] = $item['confirm_amount'] - $cancel_qty;

            //实际采购金额 【实际采购金额 = 实际采购数量*含税单价】
            $item['act_purchase_amount'] = bcmul($item['act_purchase_qty'],$item['purchase_unit_price'],3);
            //实际入库金额 【实际入库金额 = 入库数量*含税单价】
            $item['act_in_stock_amount'] = bcmul($item['upselft_amount'],$item['purchase_unit_price'],3);


            //开票单位中含有2个或者以上单位时，生成的开票合同中的开票单位需要删除千克
            $item['declare_unit'] = $this->purchase_tax->get_new_declare_unit($item['declare_unit']);

            $item['customs_code'] = isset($sku_arr[$item['sku']])?$sku_arr[$item['sku']]['customs_code']:'';
            //最新开票品名
            $item['new_export_cname'] = isset($sku_arr[$item['sku']])?$sku_arr[$item['sku']]['export_cname']:'';
            // 最新开票单位
            $item['new_declare_unit'] = isset($sku_arr[$item['sku']])?$sku_arr[$item['sku']]['declare_unit']:'';
            $item['is_check_goods'] = $sku_arr[$item['sku']]['is_inspection']??'';

            //入库时间
            $item['instock_date'] = $instock_map[$item['id']]['instock_date']??'';
            //币种
            $item['currency'] =$item['currency_code'];
            //报关状态
            $item['customs_status_type'] = $item['customs_status'];
            $item['customs_status'] = !empty($item['customs_status'])?customs_statu_type($item['customs_status']):'';
            //是否商检
            $item['is_check_goods'] = check_goods($item['is_check_goods']);
            //是否完结
            $item['is_end'] = end_status($item['is_end']);
            //采购单状态
            $item['purchase_order_status'] = getPurchaseStatus($item['purchase_order_status']);
            //付款状态
            $item['pay_status'] = getPayStatus($item['pay_status']);
            //是否退税
            $item['is_drawback'] = getIsDrawback($item['is_drawback']);
            $item['coupon_rate'] = ($item['coupon_rate']*100)."%";

            //未开票，实际采购数量为0，开票状态修改成:无法开票,合同状态修改成已完结
//            if( $item['invoice_status'] == 1 &&  $item['act_purchase_qty'] == 0){
//
//                $item['invoice_status'] = '无法开票';
//
//
//            }else {
//                //开票状态
//                $item['invoice_status'] = getInvoiceStatus($item['invoice_status']);
//
//            }

//            // 如果已开票数量大于0
            $f = true;
            $invoice_status_other = $item['invoice_status'];
            if( $item['invoice_status'] != 5 && $item['invoice_status']!=2) {
                if ($item['invoiced_qty'] > 0) {

                    if ($item['invoiced_qty'] >= $item['act_purchase_qty']) {

                        $item['invoice_status'] = '已开票';

                        $f = false;
                        $item['uninvoiced_qty'] = $item['act_purchase_qty'] - $item['invoiced_qty'];
                    } else {
                        $item['invoice_status'] = '部分已开票';

                        $f = false;
                        $item['uninvoiced_qty'] = $item['act_purchase_qty'] - $item['invoiced_qty'];
                    }
                } else {

                    if ($item['act_purchase_qty'] > 0) {
                        $item['invoice_status'] = '未开票';
                        $item['uninvoiced_qty'] = $item['act_purchase_qty'];
                    }
                }

                if ($item['act_purchase_qty'] == 0) {

                    $item['invoice_status'] = '无需开票';

                }
            }else{
                if($item['invoice_status'] == 5) {
                    $item['invoice_status'] = '无法开票';

                    $f = false;
                    $item['uninvoiced_qty'] = $item['act_purchase_qty'] - $item['invoiced_qty'];
                }
                if($item['invoice_status'] == 2) {
                    $item['invoice_status'] = '开票中';

                    $f = false;
                    $item['uninvoiced_qty'] = $item['act_purchase_qty'] - $item['invoiced_qty'];

                }

            }


            if( $item['act_purchase_qty'] == 0){

                $item['contract_invoicing_status'] = "已完结";


                if($item['uninvoiced_qty'] ==0 ){

                    $item['uninvoiced_qty'] = 0;
                }else{
                    if($f == true) {
                        $item['uninvoiced_qty'] = $item['act_purchase_qty'] - $item['invoiced_qty'];
                    }
                }

            }else {

                //合同开票状态
                if(($item['confirm_amount'] - $item['invoiced_qty']) == 0){

                    $item['contract_invoicing_status'] = '已完结';
                }else {
                    $item['contract_invoicing_status'] = getContractInvoiceStatus($item['contract_invoicing_status']);
                }
            }

//            if( $item['uninvoiced_qty'] <0){
//
//                $item['uninvoiced_qty'] = 0;
//            }

            if( isset($comData[$item['compact_number']])){

                if($comData[$item['compact_number']] == CONTRACT_INVOICING_STATUS_NOT){

                    $item['contract_invoicing_status'] = '未完结';
                }else{
                    $item['contract_invoicing_status'] = '已完结';
                }
            }

            // 手动处理数据

            if($invoice_status_other == 12){

                $item['invoice_status'] = '已开票';
                if ($item['act_purchase_qty'] == 0) {

                    $item['invoice_status'] = '无需开票';

                }
                $item['contract_invoicing_status'] = "已完结";
                $f = false;
            }

            //采购仓库
            $item['warehouse_code'] = $warehouse_name_arr[$item['warehouse_code']] ?? '';
//            $item['uninvoiced_qty'] = $item['uninvoiced_qty'] - $item['cancel_qty'];
        }
    }


    public function format_list_data_sum(&$purchase_tax_list)
    {
        //取消
        $items_id_list = array_column($purchase_tax_list,'id');
        $cancel_qty_map = $this->m_cancel->get_cancel_qty_by_item_id($items_id_list);
        $act_purchase_amount = $act_in_stock_amount = 0;
        foreach ($purchase_tax_list as $key => $item) {
            //取消数量
            $cancel_qty = $cancel_qty_map[$item['id']]??0;

            $item['cancel_qty'] = $cancel_qty;

            //实际采购数量 【实际采购数量 = 采购数量-取消数量】
            $item['act_purchase_qty'] = $item['confirm_amount'] - $cancel_qty;

            //实际采购金额 【实际采购金额 = 实际采购数量*含税单价】
            $act_purchase_amount +=  bcmul($item['act_purchase_qty'],$item['purchase_unit_price'],3);
            //实际入库金额 【实际入库金额 = 入库数量*含税单价】
            $act_in_stock_amount += bcmul($item['upselft_amount'],$item['purchase_unit_price'],3);

        }

        return ['money' => $act_purchase_amount,'warehouse_money' =>$act_in_stock_amount ];
    }

    /**
     * 批量生成发票清单
     * purchase/purchase_order_tax/batch_create_invoice_listing
     * @author Manson 2019-11-27
     */
    public function batch_create_invoice_listing()
    {
        ini_set('memory_limit', '1024M');
        try{
            //接收参数
            $ids = $this->input->get_post('ids');
            $one_key = $this->input->get_post('one_key');//一键生成
            $continue_create = $this->input->get_post('continue_create');//继续生成标识,跳过不满足条件的
            $skip_confirm = $this->input->get_post('skip_confirm');//跳过提醒
            if (!empty($ids)){
                $ids = explode(',',$ids);
                $params['ids'] = $ids;

            }else{
                $this->load->service('basic/SearchExportCacheService');
                $quick_sql = $this->searchexportcacheservice->setScene($this->searchexportcacheservice::PURCHASE_TAX_ORDER_TACKING_LIST_SEARCH_EXPORT)->get();

                $total = substr($quick_sql, 0, 10);
                $quick_sql = substr($quick_sql, 10);
                if (empty($quick_sql))
                {
                    throw new \Exception(sprintf('请刷新列表后重试'));
                }

                $result = $this->m_tax_list->query_quick_sql($quick_sql);
                if (empty($result)){
                    throw new \Exception(sprintf('未查到要执行的数据'));
                }

                $params['ids'] = array_column($result,'id');

            }
            if (empty($params)){
                throw new Exception('参数为空');
            }
//pr($params);exit;
            //定义加载
            $this->load->model('prefix_number_model'); // 数据表前缀
            $success_list = [];
            $update_invoice_status = [];
            $unique_compact = [];
            $error_info = '';
            $confirm_msg = '';
            $total_price = 0;//发票总金额

            //一键生成 需将合同号下的所有备货单都查询出来
            if($one_key){
                $params['one_key'] = $one_key;
            }

            //根据合同号排序查询所有po+sku维度信息

            $invoice_data = array();
            $group='ppoi.sku,ppoi.purchase_number';
            $pageSize = 300;

                    $chunk_ids = array_chunk($params['ids'],$pageSize);
                    foreach ($chunk_ids as $chunk_val){
                        $params['ids'] = $chunk_val;
                        $confirmData = $this->m_tax_list->getPurchaseOrders($params['ids']);

                        foreach($chunk_val as $chunkIds){

                            if( isset($confirmData[$chunkIds]) && $confirmData[$chunkIds]['confirm_amount']==0 ){

                                throw new Exception("采购单：".$confirmData[$chunkIds]['purchase_number']."，实际采购数量为0，无法开票");
                            }
                        }


                $product_incomplete_list_arr = $this->m_tax_list->get_create_invoice_listing($params,$group,False);
                $tax_data_list = $product_incomplete_list_arr['data_list'];
                if (empty($tax_data_list)){
                    throw new Exception('查找不到对应的数据');
                }
                $items_id_list = array_column($tax_data_list,'id');
                $cancel_qty_map = $this->m_cancel->get_cancel_qty_by_item_id($items_id_list);
//                pr($cancel_qty_map);exit;
                $compact = array_column($tax_data_list,'compact_number');
                //条件: 属于同一个合同
                if (!$one_key){
                    if (count(array_unique($compact))>1){
                        throw new Exception('不属于同一合同,那么不允许开票');
                    }
                }
                unset($compact);
                        $compactDatas = []; // 合同单号缓冲区
                        if(!empty($tax_data_list)){

                            foreach($tax_data_list as $key=>$value){

                                if( !isset($compactDatas[$value['compact_number']])){

                                    $compactDatas[$value['compact_number']] = [];
                                }

                                $compactDatas[$value['compact_number']][] = $value['purchase_number'];
                            }
                        }

                        if(!empty($compactDatas)){

                            foreach( $compactDatas as $key=>$value){
                                $purchaseNumbers = $this->orders_model->VerifyPurchaseGateWays($value);
                                if( NULL == $purchaseNumbers || empty($purchaseNumbers)){
                                    $compactDatas[$key]['is_gateways'] = 0;
                                }else{
                                    $compactDatas[$key]['is_gateways'] = 1;
                                }
                            }
                        }

                        $isGatewayData = [];
                foreach ($tax_data_list as $key=>$value)
                {
                    //合同号为空的不允许开票
                    if (empty($value['compact_number'])){
                        $error_info .= sprintf('备货单%s无合同号不允许开票',$value['demand_number']).PHP_EOL;
                        continue;
                    }

                    //订单状态=已作废待审核、已作废，不允许开票
                    if (in_array($value['purchase_order_status'],[PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,PURCHASE_ORDER_STATUS_CANCELED])){
                        $error_info .= sprintf('备货单%s订单状态作废待审核/已作废状态，不允许生成发票清单',$value['demand_number']).PHP_EOL;
                        continue;
                    }

                            //条件:  (采购数量+取消数量+报损数量) - 已开票数量1027 0

                                $cancel_qty = $cancel_qty_map[$value['id']] ?? 0;
                                if( $value['is_tovoid'] == 2){

                                    $value['invoiced_qty'] = 0;
                                }
                                $uninvoice_qty = $value['purchase_qty'] - $cancel_qty - $value['invoiced_qty'];
                                if ($uninvoice_qty <= 0) {
                                    $error_info .= sprintf('备货单%s的可开票数量=0,无法开票;', $value['demand_number']) . PHP_EOL;
                                    continue;
                                }

                            //上一次开票状态已经完结 即开票状态!=['开票中']
                            if (in_array($value['invoice_status'],[INVOICE_STATUS_ING])){
                                    $error_info .= sprintf('备货单%s还在开票,请结束后进行;',$value['demand_number']).PHP_EOL;
                                    continue;
                            }

                            if (in_array($value['invoice_status'],[INVOICE_STATUS_PROHBIT])){
                                $error_info .= sprintf('备货单%s无法开票;',$value['demand_number']).PHP_EOL;
                                continue;
                            }



                    if (isset($update_invoice_status[$value['id']])){//开票中的不允许开票
                        continue;
                    }
//                    echo 123;exit;
// ------ 以上条件必须满足 -----------

                    //判断开票品名
                    if ($value['new_export_cname'] != $value['export_cname']){
                        $confirm_msg .= sprintf('备货单%s的开票品名和最新的开票品名不一致,是否继续生成?;',$value['demand_number']).PHP_EOL;
                    }


                    $one_total_price = bcmul($uninvoice_qty,$value['purchase_unit_price'],3);//可开票数量*含税单价

                    if (!isset($unique_compact[$value['compact_number']])){//存在不同的合同 ,一键开票时 同合同号的为一个发票清单
                        //生成一个新的发票清单号
                        $new_fp_number = $this->prefix_number_model->get_prefix_new_number('FP'.date('Ymd'),1,4);
                        if (!$new_fp_number){
                            throw new Exception('新的发票清单号,生成失败');
                        }
                        $total_price = '';//初始化
                        $total_price = bcadd($total_price,$one_total_price,3);//发票总金额

                        $unique_compact[$value['compact_number']] = [//发票清单表list
                            'invoice_number' => $new_fp_number,
                            'supplier_name' => $value['supplier_name'],
                            'supplier_code' => $value['supplier_code'],
                            'purchase_user_name' => $value['buyer_name'],
                            'purchase_user_id' => $value['buyer_id'],
                            'purchase_name' => $value['purchase_name'],
                            'compact_number' => $value['compact_number'],
                            'currency_code' => $value['currency_code'],
                            'create_user' => getActiveUserName(),
                            'create_time' => date('Y-m-d H:i:s'),
                            'audit_status' => INVOICE_STATES_WAITING_CONFIRM,
                            'invoice_amount' => $total_price,
                            'is_gateway' => isset($compactDatas[$value['compact_number']]['is_gateways'])?$compactDatas[$value['compact_number']]['is_gateways']:0,
                        ];
                    }else{
                        $total_price = bcadd($total_price,$one_total_price,3);//发票总金额
                        $unique_compact[$value['compact_number']]['invoice_amount'] = $total_price;
                    }

                    // 判断采购单是否对接门户
                    $success_list[] = [//invoice_item表的
                        'invoice_number' => $unique_compact[$value['compact_number']]['invoice_number'],
                        'purchase_number' => $value['purchase_number'],
                        'sku' => $value['sku'],
                        'demand_number' => $value['demand_number'],
                        'unit_price' => $value['purchase_unit_price'],//含税单价
                        'invoice_coupon_rate' => $value['coupon_rate'],//票面税率
                        'app_invoice_qty' => $uninvoice_qty,
                    ];

                    $update_invoice_status[$value['id']] = [//更新items表的开票状态
                        'id' => $value['id'],
                        'invoice_status' => INVOICE_STATUS_ING
                    ];
                    // 对接门户系统采购单

                    $declareCustoms = $this->m_tax_list->getDeclareCustoms($value['purchase_number'],$value['sku']);
                    $tax_data_list[$key]['customsName'] =  isset($declareCustoms['customs_name'])?$declareCustoms['customs_name']:NULL;//报关品名
                    $tax_data_list[$key]['customsQuantity']  = isset($declareCustoms['customs_quantity'])?$declareCustoms['customs_quantity']:NULL;//报关数量
                    $tax_data_list[$key]['customsType'] = isset($declareCustoms['customs_type'])?$declareCustoms['customs_type']:NULL; //报关型号
                    $tax_data_list[$key]['customsUnit'] = isset($declareCustoms['customs_unit'])?$declareCustoms['customs_unit']:NULL; //报关单位
                    $tax_data_list[$key]['appInvoiceQty'] = isset($declareCustoms['uumber_invoices'])?$declareCustoms['uumber_invoices']:NULL;
                    $tax_data_list[$key]['invoiceNumber'] = $new_fp_number;
                    $tax_data_list[$key]['customs_number'] = isset($declareCustoms['customs_number'])?$declareCustoms['customs_number']:NULL; // 报关单号
                }
                //$this->m_tax_list->push_gateways($tax_data_list);// 推送到门户系统
            }
            if (count($unique_compact)>1){
                $error_info .= sprintf('存在多个合同号:[%s]',implode(',',array_keys($unique_compact))).PHP_EOL;
            }

            if(!empty($error_info) && $one_key && !$continue_create){//返回报错信息
                $this->success_json(['is_confirm'=>1, 'confirm_msg'=>$error_info]);
            }elseif(!empty($error_info) && !$one_key){
                $this->error_json($error_info);
            }elseif (empty($success_list)){
                throw new Exception('所有备货单都不满足条件,无法继续生成;'.$error_info);
            }elseif (!empty($confirm_msg) && !$skip_confirm){
                $this->success_json(['is_confirm'=>2,'confirm_msg'=>$confirm_msg]);
            }elseif ((empty($error_info) || $continue_create) && (empty($confirm_msg) || $skip_confirm) && !empty($success_list)){
                if (!$this->purchase_tax->batch_create_invoice_listing($success_list,$update_invoice_status,$unique_compact)){
                    throw new Exception('生成失败,请稍后再试');
                }else{
                    $this->success_json(sprintf('生成发票清单成功,%s',$new_fp_number));
                }
            }else{
                throw new Exception('数据有误');
            }
        }catch ( Exception $exp ) {

            $this->error_json($exp->getMessage());
        }
    }



    /**
     * 退税订单跟踪列表-导出
     * purchase/purchase_order_tax/export_list
     * @author Manson
     */
    public function export_list()
    {
        try{
            ini_set('memory_limit','1024M');
            set_time_limit(0);
            $this->load->helper('export_csv');
            $this->load->helper('status_product');
            $this->load->helper('status_order');
            $params =[];
            foreach($_POST as $cli_key=>$cli_value){
                $params[$cli_key] = $this->input->get_post($cli_key);
            }
            $params['ids'] = $this->input->get_post('ids');
            $params['import_data'] = true;
            $result = $this->m_tax_list->get_tax_order_tacking_list($params,0,1);
            $total = $result['page_data']['total'];
//            if (!empty($params['ids'])){
//                $result = $this->m_tax_list->get_tax_order_tacking_list($params);
//                $total = $result['total_count']??0;
//                $quick_sql = $result['quick_sql']??'';
//            }else{
//                $this->load->service('basic/SearchExtax_order_tacking_listportCacheService');
//                $quick_sql = $this->searchexportcacheservice->setScene($this->searchexportcacheservice::PURCHASE_TAX_ORDER_TACKING_LIST_SEARCH_EXPORT)->get();
//
//                $total = substr($quick_sql, 0, 10);
//                $quick_sql = substr($quick_sql, 10);
//
//                if (empty($quick_sql))
//                {
//                    throw new \Exception(sprintf('请选择要导出的资源'));
//                }
//            }

//pr($quick_sql);exit;
            $file_name = sprintf('tax_%s.csv',time());//文件名称
            $product_file = get_export_path('purchase_order_tax').$file_name;//文件下载路径
            if (file_exists($product_file)) {
                unlink($product_file);
            }
            fopen($product_file,'w');
            $fp = fopen($product_file, "a");
            $this->load->classes('purchase/classes/TaxOrderTackingTemplate');
            $pick_cols = $this->TaxOrderTackingTemplate->get_default_template_cols();
            if(!isset($pick_cols['对接门户系统'])){

                $pick_cols['对接门户系统'] = [

                    'col' => 'is_gateway',
                    'width' => 18
                ];
            }

            if(!isset($pick_cols['定单付款完结时间'])){

                $pick_cols['定单付款完结时间'] = [

                    'col' => 'completion_time',
                    'width' => 18
                ];
            }

            if(!isset($pick_cols['付款完结状态'])){

                $pick_cols['付款完结状态'] = [

                    'col' => 'pay_finish_status_ch',
                    'width' => 18
                ];
            }

            if(!isset($pick_cols['最新开票单位'])){

                $pick_cols['最新开票单位'] = [

                    'col' => 'new_declare_unit',
                    'width' => 18
                ];
            }

            $pick_cols['结算方式'] = [
                'col' => 'settlement_ch',
                'width' => 18
            ];
            $pick_cols['已付金额'] = [
                'col' => 'amount_paid',
                'width' => 18
            ];
            $pick_cols['备货单状态'] = [
                'col' => 'suggest_status_ch',
                'width' => 18
            ];
            $pick_cols['对账单号'] = [
                'col' => 'statement_number',
                'width' => 18
            ];
//        pr($pick_cols);exit;
            foreach( $pick_cols as $key => $val) {

                $title[$val['col']] =iconv("UTF-8", "GBK//IGNORE",$key);
            }
//        pr($title);exit;
            $pick_cols = array_column($pick_cols,'col');


            //将标题写到标准输出中
            fputcsv($fp, $title);
            if($total>=1) {
                $limit      = 1000;
                $total_page = ceil($total / $limit);
                $time_cols  = ['audit_time', 'submit_time','order_create_time', 'instock_date', 'customs_time'];
                $too_long_cols = ['customs_code'];
                $special_cols = ['product_name'];
                for ($i = 1; $i <= $total_page; ++$i) {

                    $offset    = ($i - 1) * $limit;
                    $result = $this->m_tax_list->get_tax_order_tacking_list($params,$offset,$limit);
                    $this->format_list_data($result['value']);
                    foreach ($result['value'] as $row) {
                        $new = [];
                        foreach ($pick_cols as $col) {
                            if(in_array($col, $special_cols)){
                                $row[$col] = str_replace(array("\r\n", "\r", "\n"), '', $row[$col]);//将换行
                                $row[$col] = str_replace(',',"，",$row[$col]);//将英文逗号转成中文逗号
                                $row[$col] = str_replace('"',"”",$row[$col]);//将英文引号转成中文引号
                            }

                            if (in_array($col, $time_cols)) {
                                $new[$col] = empty($row[$col]) || $row[$col] == '0000-00-00 00:00:00' ? '' : $row[$col] . "\t";

                            } elseif ($col == 'customs_number' && isset($row['customs_number'])) {
                                $new[$col] = implode(' ', $row['customs_number']). "\t";

                            } elseif ($col == 'invoice_code_left_list' && isset($row['invoice_code_left_list'])){
                                $new['invoice_code_left_list'] = implode(' ', $row['invoice_code_left_list']). "\t";

                            }elseif ($col == 'invoice_code_right_list' && isset($row['invoice_code_right_list'])){
                                $new['invoice_code_right_list'] = implode(' ', $row['invoice_code_right_list']). "\t";

                            }elseif ($col == 'invoice_number' && isset($row['invoice_number_list'])){
                                $new['invoice_number'] = implode(' ', $row['invoice_number_list']). "\t";

                            } elseif (in_array($col,$too_long_cols)){
                                $new[$col] = $row[$col]. "\t";

                            } elseif($col=='is_gateway' && isset($row['is_gateway'])){
                                $new['is_gateway'] = $row['is_gateway']==1?"是\t":"否\t";

                            } elseif($col=='completion_time' && isset($row['completion_time'])){
                                $new['completion_time'] = $row['completion_time'];
                            } elseif($col=='pay_finish_status_ch' && isset($row['pay_finish_status'])){
                                $new['pay_finish_status_ch'] = !empty($row['pay_finish_status'])?getPayFinishStatus($row['pay_finish_status']):'';
                            }else{
                                $new[$col] = $row[$col];
                            }



                            if (!empty($new[$col])) {
                                $new[$col] = iconv("UTF-8", "GBK//IGNORE", $new[$col]);
                            } else {
                                $new[$col] = '';
                            }
                        }
                        fputcsv($fp, $new);
                    }
                    //刷新缓冲区
                    ob_flush();
                    flush();
                }
            }

            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url=get_export_path_replace_host(get_export_path('purchase_order_tax'),$down_host).$file_name;
            $this->success_json($down_file_url);
        }catch (Exception $e){
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 获取待采购审核数据
     * @MTHOD GET
     * @author :luxu
     * @time:2020/5/18
     **/
    public function purchase_review(){

        try{
            // 发票清单号
            $invoice_number = $this->input->get_post('invoice_number');
            if(empty($invoice_number)){

                throw new Exception("请传入发票清单号");
            }

            $result = $this->m_tax_list->get_purchase_review($invoice_number);
            $this->success_json($result);

        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 驳回接口
     * @MTHOD POST
     * @author:luxu
     * @time:2020/5/19
     **/

    public function reject(){

        try{
            $this->load->model('Purchase_financial_audit_model', 'm_financial_audit', false, 'purchase');
            //  获取HTTP 协议传输的主体数据
            $subjectData = $this->input->get_post('data');
            //  获取HTTP 协议传入的审核状态
            $examine = $this->input->get_post('status');
            //  获取HTTP 协议传入的备注信息
            $remark  = $this->input->get_post('remark');
            if( $examine == 2 && empty($remark)){

                // 当审核状态为驳回时，备注信息必填
                throw new Exception("请填写备注信息");
            }

            $subjectData = json_decode($subjectData,True);
            if(empty($subjectData)){

               throw new Exception("请传入正确的数据");
            }

            foreach($subjectData as $key=>$value){
                if( empty($value['children_invoice_number']) || empty($value['sku'])
                   || empty($value['demand_number']) || empty($value['purchase_number'])
                ){
                    throw new Exception("缺少参数");
                }

                if(!isset($value['invoice_image'])){
                    $subjectData[$key]['invoice_image'] = "";
                }
            }

            $result = $this->m_tax_list->reject($subjectData,$examine,$remark);
            if(True == $result){

                $this->success_json();
            }

        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     *  上传发票图片
     *  @METHODS  POST
     *  @author:luxu
     *  @time: 2020/5/19
     **/
    public function uplodeImage(){

        try{
            // zip 包解压路径
            $zipDirs = dirname(dirname(APPPATH)).'/webfront/download_csv/zipData/';
            $zip = new ZipArchive();
            $dir =  $this->input->get_post("dirs");

            if(empty($dir)){

                throw new Exception("请传入压缩包地址");
            }
            //$dir = $this->m_tax_list->downFiles($dir,$zipDirs);
            if ($zip->open($dir)) {
                list($dirname,$dirext) = explode(".",basename($dir));

                // 判断文件名称是否发票清单号
                $invoiceData = $this->m_tax_list->getInvoiceNumber($dirname);
                if(empty($invoiceData)){

                    throw new Exception("发票清单:".$dirname."不存在. ZIP 文件包请用发票清单号命名,图片名称以子发票清单命名");
                }
                $mcw =  $zip->extractTo($zipDirs);//解压到$route这个目录中
                $zip->close();
                // 获取解压文件图片信息
                $diraddress = $zipDirs.$dirname;
                $file_list = [];
                if(is_dir($diraddress)) {
                    $handler = opendir($diraddress);
                    while(($filename = readdir($handler)) !== false ){
                        if($filename != "." && $filename != ".."){

                            $file_list[] = $filename;
                        }

                    }
                    if( !empty($file_list)){

                        $result = $this->m_tax_list->uplodeImage($file_list,$diraddress);
                        if( True == $result){
                            $this->success_json();

                        }else{
                            throw new Exception("压缩包上传失败");

                        }
                    }
                }
            }
            throw new Exception("请以主发票清单号命名文件夹、以子发票清单号命名发票图片");
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 获取发票图片地址
     * @METHODS GET
     * @author:luxu
     * @time:2020/5/19
     **/

    public function getImage(){

        try{

            $children_invoice_number = $this->input->get_post("children_invoice_number");
            if(empty($children_invoice_number)){

                throw new Exception("请传入子发票清单");
            }
            $result = $this->m_tax_list->getImage($children_invoice_number);
            $this->success_json($result);
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    public function getLogs(){

        try{

            $ids = $this->input->get_post("ids");
            if(empty($ids)){

                throw new Exception("请传入ID");
            }
            $result = $this->m_tax_list->getLogs($ids);
            $this->success_json($result);
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

}
