<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_Oauth_Controller.php";

/**
 * Created by PhpStorm.
 * 门户系统对接 - 自动对账
 * User: Jolon
 * Date: 2021/8/2 09 36
 */
class Supplier_gateways extends MY_Oauth_Controller
{

    public $_link_type = 4;

    public function __construct()
    {
        parent::__construct();

        $this->_requestData = json_decode($this->_requestData,true);
        if(SetAndNotEmpty($this->_requestData,'supplier_code') === false){
            $this->error_json($this->_Unauthorized,'供应商CODE必须');
        }

        $this->load->model('supplier/Supplier_model');
        $this->load->model('others/Supplier_inventory_items_model');
        $this->load->model('statement/Purchase_statement_model');
        $this->load->model('statement/Purchase_inventory_items_model');

        $supplierInfo = $this->Supplier_model->get_supplier_info($this->_requestData['supplier_code']);
        if(empty($supplierInfo) or empty($supplierInfo['reconciliation_agent'])){
//            $this->error_json($this->_Unauthorized,'请先维护供应商和对账经办人');
        }


        // 设置当前访问系统的用户（模拟登陆）
        setActiveUserById(0,$supplierInfo['reconciliation_agent']);
    }

    /**
     * 自动对账 - 入库明细表
     * @author:Jolon
     * @time:2021/8/2 09 36
     */
    public function get_inventory_list(){
        $params = $this->_requestData;

        $page = isset($this->_requestData['offset'])?$this->_requestData['offset']:1;
        $limit = isset($this->_requestData['limit'])?$this->_requestData['limit']:20;
        if (empty($page) OR $page < 0) {
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;

        $data = $this->Supplier_inventory_items_model->gateway_data_list($params, $offsets, $limit, $page);

        $this->success_json($this->_OK,$data);


    }


    /**
     * 核销-入库明细列表导出
     * /others/Supplier_gateways/data_list_export
     */
    public function data_list_export()
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $params = $this->_requestData;
        $params['source'] = SOURCE_COMPACT_ORDER;// 固定参数

        if (isset($params['ids']) and !empty($params['ids'])) {
            $params['ids'] = array_filter(explode(',', $params['ids']));
        }


        //获取记录条数
        $page = 1;
        $limit = 1;
        $offsets = ($page - 1) * $limit;

        $data = $this->Supplier_inventory_items_model->gateway_data_list($params, $offsets, $limit, $page, true);
        $total = $data['total'];

        //文件名
        $file_name = '入库明细' . date('Ymd').rand(100,999);
        if (isset($params['export_type']) and $params['export_type'] == 'excel') {
            if($total > 3000) $this->error_json($this->_BadRequest,'数据超过限制[请不要超过3000条数据]');

            //表头字段
            $columns = array(
                'rows' => array([
                    "入库批次号", "入库时间", "产品编码", "产品名称", "产品图片",
                    //"发货批次号","发货时间","SKU",
                    "采购单号", "下单数量", "入库数量", "采购单价", "入库金额", "订单状态", "付款状态", "采购仓库", "采购员", "对账单号",
                    "采购主体", "是否开票", "结算方式", "应付款时间", "下单时间", "生成对账单时间", "入库是否异常", "是否生成对账单", "业务线", "是否代采", "备注"
                ]
                ),
                'keys' => array(
                    'instock_batch', 'instock_date', 'sku', 'product_name', 'product_img_url',
                    'purchase_number', 'real_confirm_amount', 'instock_qty', 'purchase_unit_price', 'instock_price', 'purchase_order_status_cn', 'pay_status_cn', 'warehouse_cn', 'buyer_name', 'statement_number',
                    'purchase_name', 'is_drawback_cn', 'settlement_type_cn', 'need_pay_time', 'audit_time', 'st_create_time', 'is_abnormal', 'created_statement_cn', 'purchase_type_cn', 'is_purchasing', 'remark'
                )
            );

            //创建导出类对象
            $this->load->library('Export');
            $my_export = new Export();
            $down_path = $my_export->ExportExcel($file_name, $total, $columns, $params, $this->Supplier_inventory_items_model, 'gateway_data_list',['product_img_url']);
        } else {
            if($total > 15000) $this->error_json($this->_BadRequest,'数据超过限制[请不要超过15000条数据]');

            //表头字段
            $columns = array(
                'rows' => array([
                    "入库批次号", "入库时间", "产品编码", "产品名称",
                    //"发货批次号","发货时间","SKU",
                    "采购单号", "下单数量", "入库数量", "采购单价", "入库金额", "订单状态", "付款状态", "采购仓库", "采购员", "对账单号",
                    "采购主体", "是否开票", "结算方式", "应付款时间", "下单时间", "生成对账单时间", "入库是否异常", "是否生成对账单", "业务线", "是否代采", "备注"
                ]
                ),
                'keys' => array(
                    'instock_batch', 'instock_date', 'sku', 'product_name',
                    'purchase_number', 'real_confirm_amount', 'instock_qty', 'purchase_unit_price', 'instock_price', 'purchase_order_status_cn', 'pay_status_cn', 'warehouse_cn', 'buyer_name', 'statement_number',
                    'purchase_name', 'is_drawback_cn', 'settlement_type_cn', 'need_pay_time', 'audit_time', 'st_create_time', 'is_abnormal', 'created_statement_cn', 'purchase_type_cn', 'is_purchasing', 'remark'
                )
            );


            // 生成文件
            $file_name = $file_name . '.csv';
            $reduced_file = get_export_path('gateways_in_items/'.date('Ymd')). $file_name;
            if (file_exists($reduced_file)) {
                unlink($reduced_file);
            }
            $fp = fopen($reduced_file, "a+");

            //将标题写到标准输出中
            fputcsv($fp, $columns['rows'][0]);

            $limit = 1000;
            $page  = ceil($total/$limit);

            for($i=1;$i<=$page;++$i){

                $condition['offset'] = $i;
                $condition['limit'] = $limit;
                $offsets = ($i - 1) * $limit;
                $orders_info = $this->Supplier_inventory_items_model->gateway_data_list($params, $offsets, $limit, $page, true);

                if(!isset($orders_info['values']) or empty($orders_info['values'])) break;// 没有数据时退出
                $delivery_list = $orders_info['values'];


                foreach ($delivery_list as $key => $value) {
                    $v_value_tmp = array(
                        'instock_batch' => $value['instock_batch'],
                        'instock_date'=> $value['instock_date'],
                        'sku'=> $value['sku'],
                        'product_name'=> $value['product_name'],
                        'purchase_number'=> $value['purchase_number'],
                        'real_confirm_amount'=> $value['real_confirm_amount'],
                        'instock_qty'=> $value['instock_qty'],
                        'purchase_unit_price'=> $value['purchase_unit_price'],
                        'instock_price'=> $value['instock_price'],
                        'purchase_order_status_cn'=> $value['purchase_order_status_cn'],
                        'pay_status_cn'=> $value['pay_status_cn'],
                        'warehouse_cn'=> $value['warehouse_cn'],
                        'buyer_name'=> $value['buyer_name'],
                        'statement_number'=> $value['statement_number'],
                        'purchase_name'=> $value['purchase_name'],
                        'is_drawback_cn'=> $value['is_drawback_cn'],
                        'settlement_type_cn'=> $value['settlement_type_cn'],
                        'need_pay_time'=> $value['need_pay_time'],
                        'audit_time'=> $value['audit_time'],
                        'st_create_time'=> $value['st_create_time'],
                        'is_abnormal'=> $value['is_abnormal'],
                        'created_statement_cn'=> $value['created_statement_cn'],
                        'purchase_type_cn'=> $value['purchase_type_cn'],
                        'is_purchasing'=> $value['is_purchasing'],
                        'remark'=> is_array($value['remark'])?implode(',',$value['remark']):$value['remark'],
                    );
                    fputcsv($fp, $v_value_tmp);
                }
            }

            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_path = get_export_path_replace_host(get_export_path('gateways_in_items/'.date('Ymd')),$down_host).$file_name;
        }
        $this->success_json($this->_OK,$down_path);
    }

    /**
     * 对账列表
     */
    public function get_statement_list()
    {
        $params = $this->_requestData;
        if(!SetAndNotEmpty($params, 'supplier_code')){
            $this->error_json($this->_Forbidden, '供应商不能为空！');
        }
        if(!SetAndNotEmpty($params, 'pay_finished_type') || !in_array($params['pay_finished_type'], [1, 2])){
            $this->error_json($this->_Forbidden, '页面类型不能为空！');
        }
        if(!SetAndNotEmpty($params, 'group_ids')){
            unset($params['group_ids']);
        }

        $page = SetAndNotEmpty($params, 'offset') ? $params['offset'] : 1;
        $limit = SetAndNotEmpty($params, 'limit') ? $params['limit'] : 20;
        $offset = ($page - 1) * $limit;
        $this->load->model('statement/purchase_statement_model');

        if(isset($params['is_postage']) and !isset($params['free_shipping'])){
            $params['free_shipping'] = $params['is_postage'];
        }

        $result = $this->purchase_statement_model->get_statement_list($params, $offset, $limit, $page,false,null, true);
        $result['data_list']['value'] = $this->purchase_statement_model->format_compact_list($result['data_list']['value']);

        $this->success_json($this->_OK, $result);
    }

    /**
     * 对账详情
     */
    public function get_statement_detail()
    {
        $params = $this->_requestData;
        if(!SetAndNotEmpty($params, 'statement_number'))  $this->error_json($this->_Forbidden, '参数错误！');

        $this->load->helper('status_order');
        $this->load->helper('status_finance');
        $this->load->model('finance/Purchase_order_pay_model');
        $this->load->model('supplier/Supplier_model');

        $statement = $this->Purchase_statement_model->get_statement($params['statement_number']);
        if(empty($statement)){
            $this->error_json($this->_Forbidden, '未获取到数据！');
        }

        // 供应商是否包邮
        $supplierInfo = $this->Supplier_model->get_supplier_info($statement['supplier_code']);
        $statement['is_postage']    = $supplierInfo['is_postage'];
        $statement['is_postage_cn'] = getIsPostage($supplierInfo['is_postage']);

        foreach($statement['summary_list'] as $key_summary => $value_summary){
            // 已付金额
            $paid_price_list = $this->Purchase_order_pay_model->get_pay_total_by_purchase_number([$value_summary['purchase_number']]);

            if($paid_price_list and isset($paid_price_list[0])){
                $statement['summary_list'][$key_summary]['paid_product_money'] = $paid_price_list[0]['paid_product_money'];
                $statement['summary_list'][$key_summary]['paid_freight']       = $paid_price_list[0]['paid_freight'];
                $statement['summary_list'][$key_summary]['paid_discount']      = $paid_price_list[0]['paid_discount'];
                $statement['summary_list'][$key_summary]['paid_process_cost']  = $paid_price_list[0]['paid_process_cost'];
                $statement['summary_list'][$key_summary]['paid_real_price']    = $paid_price_list[0]['paid_real_price'];
            }else{
                $statement['summary_list'][$key_summary]['paid_product_money'] = 0;
                $statement['summary_list'][$key_summary]['paid_freight']       = 0;
                $statement['summary_list'][$key_summary]['paid_discount']      = 0;
                $statement['summary_list'][$key_summary]['paid_process_cost']  = 0;
                $statement['summary_list'][$key_summary]['paid_real_price']    = 0;
            }
        }

        //付款记录
        $pay_record_list = $this->Purchase_order_pay_model->get_pay_records_by_pur_number($params['statement_number']);
        $pay_record_list_tmp = [];
        $statement_first_paid_time = '';
        if(!empty($pay_record_list)){
            foreach($pay_record_list as $k => $v){
                $pay_record_list[$k]['pay_status']=getPayStatus($v['pay_status']);
                if(empty($v['payer_id'])){
                    $pay_record_list[$k]['payer_id'] ='';
                }else{
                    $pay_record_list[$k]['payer_id']=getUserNameById($v['payer_id']);
                }
                if($v['requisition_method']==4){//合同列表、合同详情中的调整
                    $pay_record_list[$k]['js_ratio']=0;
                }
                if($v['pay_status'] == PAY_PAID){
                    $statement_first_paid_time = $v['payer_time'];
                }

                $pay_record_tmp = [
                    'id'                 => $v['id'],
                    'requisition_number' => $v['requisition_number'],
                    'requisition_method' => $v['requisition_method'],
                    'loss_product_money' => $statement['total_loss_product_money'],// 请款报损商品额=对账单里面的报损商品额，只会全部付款，每次都相同
                    'product_money'      => $v['product_money'],
                    'freight'            => $v['freight'],
                    'discount'           => $v['discount'],
                    'process_cost'       => $v['process_cost'],
                    'commission'         => $v['commission'],
                    'commission_percent' => $v['commission_percent'],
                    'pay_price'          => $v['pay_price'],
                    'pay_status'         => getPayStatus($v['pay_status']),
                    'pur_number'         => $v['pur_number'],
                    'applicant'          => getUserNameById($v['applicant']),
                    'application_time'   => $v['application_time'],
                    'freight_desc'       => $v['freight_desc'],
                    'create_notice'      => $v['create_notice'],
                    'compact_url'        => $v['compact_url'],
                ];

                $auditor_user = $audit_time = $audit_notice = '';
                // 取最新一个审核人
                if(!empty($v['general_manager_id'])){
                    $auditor_user = $v['general_manager_id'];
                    $audit_time = $v['general_manager_time'];
                    $audit_notice = $v['general_manager_notice'];
                }elseif(!empty($v['financial_officer_id'])){
                    $auditor_user = $v['financial_officer_id'];
                    $audit_time = $v['financial_officer_time'];
                    $audit_notice = $v['financial_officer_notice'];
                }elseif(!empty($v['financial_manager_id'])){
                    $auditor_user = $v['financial_manager_id'];
                    $audit_time = $v['financial_manager_time'];
                    $audit_notice = $v['financial_manager_notice'];
                }elseif(!empty($v['financial_supervisor_id'])){
                    $auditor_user = $v['financial_supervisor_id'];
                    $audit_time = $v['financial_supervisor_time'];
                    $audit_notice = $v['financial_supervisor_notice'];
                }elseif(!empty($v['approver'])){
                    $auditor_user = $v['approver'];
                    $audit_time = $v['processing_time'];
                    $audit_notice = $v['processing_notice'];
                }elseif(!empty($v['waiting_id'])){
                    $auditor_user = $v['waiting_id'];
                    $audit_time = $v['waiting_time'];
                    $audit_notice = $v['waiting_notice'];
                }elseif(!empty($v['auditor'])){
                    $auditor_user = $v['auditor'];
                    $audit_time = $v['review_time'];
                    $audit_notice = $v['review_notice'];
                }
                $pay_record_tmp['auditor_user'] = getUserNameById($auditor_user);
                $pay_record_tmp['audit_time'] = $audit_time ;
                $pay_record_tmp['audit_notice'] = $audit_notice;
                $pay_record_tmp['is_postage_cn'] = $statement['is_postage_cn'];

                $pay_record_list_tmp[] = $pay_record_tmp;
            }
        }


        // 删除"采购单明细"的子页面
        // 采购单明细、采购单冲销汇总
        //$statement += $this->Purchase_statement_model->get_statement_append_info(array_column($statement['summary_list'],'purchase_number'));

        $statement['pay_record_list']                 = $pay_record_list_tmp;
        $statement['statement_first_paid_time']       = $statement_first_paid_time;

        $this->success_json($this->_OK, $statement);
    }


    /**
     * 【作废】设置 对账单 是否有效
     */
    public function delete_statement(){
        if(!isset($this->_requestData['statement_number_list']) or empty($this->_requestData['statement_number_list'])){
            $this->error_json($this->_BadRequest,'对账单号参数缺失');
        }
        if(!is_array($this->_requestData['statement_number_list'])){
            $this->error_json($this->_BadRequest,'对账单号参数类型错误');
        }


        $supplier_code = $this->_requestData['supplier_code'];
        $statement_number_list = $this->_requestData['statement_number_list'];

        $return_list = [];
        foreach($statement_number_list as $statement_number){
            $statementInfo = $this->Purchase_statement_model->get_statement($statement_number,false);
            if(empty($statementInfo)){
                $return_list['error_list'][$statement_number] = '对账单不存在';
            }elseif($statementInfo['supplier_code'] != $supplier_code){
                $return_list['error_list'][$statement_number] = '抱歉，您没有操作权限';
            }else{
                $result = $this->Purchase_statement_model->set_status_valid($statement_number);
                if($result['code']){
                    $return_list['success_list'][$statement_number] = $result['message'];
                }else{
                    $return_list['error_list'][$statement_number] = $result['message'];
                }
            }
        }

        $this->success_json($this->_OK,$return_list);
    }

    /**
     * 下单对账单 EXCEL 文件
     */
    public function download_statement_html(){
        if(!isset($this->_requestData['statement_number']) or empty($this->_requestData['statement_number'])){
            $this->error_json($this->_BadRequest,'对账单号参数缺失');
        }

        $statement_number = $this->_requestData['statement_number'];


        $statement = $this->Purchase_statement_model->get_statement($statement_number);
        if($statement){
            if($statement['supplier_code'] != $this->_requestData['supplier_code']){
                $this->error_json($this->_BadRequest,'抱歉，您没有操作权限');
            }
            $origin_excel_statement = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_statement_excel');

            $statement       = format_price_multi_floatval($statement);
            $header          = array('Content-Type: application/json');

            $excel_statement = $origin_excel_statement.'?statement_number='.$statement_number;
            $html_excel      = getCurlData($excel_statement,json_encode($statement, JSON_UNESCAPED_UNICODE),'post',$header);//翻译结果


            $fileName       = $statement_number.".xls";
            $file_save_path = get_export_path('cache/statement/excel/'.date('Ymd'));// 生成缓存文件地址
            $fileNameExcel  = $file_save_path.$fileName;


            file_put_contents($fileNameExcel,$html_excel);// 输出EXCEL内容
            if(!file_exists($fileNameExcel)){
                $this->error_json($this->_BadRequest,'对账单excel文件保存失败，请稍后重试');
            }else{

                $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
                $down_file_url = get_export_path_replace_host($file_save_path,$down_host).$fileName;
                $this->success_json($this->_OK,$down_file_url);
            }
        }else{
            $this->error_json($this->_BadRequest,'对账单找不到了');
        }
    }


    /**
     * 对账单CSV导出
     * @link Supplier_gateways/statement_export_csv
     * @author Jolon
     */
    public function statement_export_csv(){
        set_time_limit(0);
        ini_set('memory_limit', '1500M');

        $this->load->helper('status_finance');
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');
        $this->load->helper('export_csv');
        $this->load->helper('user');

        $params = $this->_requestData;
        if(!isset($params['supplier_code']) or empty($params['supplier_code'])){
            $this->error_json($this->_Unauthorized,'您没有访问权限[supplier_code缺失]');
        }


        // 生成文件
        $file_name = 'ST_'.date('YmdHis').mt_rand(1000, 9999) . '.csv';
        $reduced_file = get_export_path('gateways_in_items/'.date('Ymd')). $file_name;
        if (file_exists($reduced_file)) {
            unlink($reduced_file);
        }

        $fp = fopen($reduced_file, "a+");
        $this->load->model('statement/Purchase_statement_model');

        $heads = [
            "对账单号","对账单状态", "付款状态","是否上传扫描件","入库月份","入库金额","运费","其他费用","应付金额",
            "结算方式","应付款时间","是否代采","是否包邮","采购主体","是否开票","创建人","对账人","对账单来源","创建时间"
        ];
        foreach($heads as &$v){
            $v = iconv('UTF-8','GBK//IGNORE',$v);
        }
        fputcsv($fp,$heads);

        $limit = 1000;
        $page = 1;

        do {
            $offsets = ($page - 1) * $limit;

            // 获取数据
            $result = $this->Purchase_statement_model->get_statement_list($params, $offsets, $limit, $page);
            if(!isset($result['data_list']['value']) or count($result['data_list']['value']) == 0){
                break;
            }
            $result['data_list']['value'] = $this->Purchase_statement_model->format_compact_list($result['data_list']['value']);// 格式化数据
            $values = $result['data_list']['value'];
            unset($result);

            foreach($values as $value){
                try {
                    $row = [
                        iconv('UTF-8', 'GBK//IGNORE', $value['statement_number']),
                        iconv('UTF-8', 'GBK//IGNORE', $value['status_cn']),
                        iconv('UTF-8', 'GBK//IGNORE', $value['in_pay_pay_status']),
                        iconv('UTF-8', 'GBK//IGNORE', $value['statement_pdf_status']),
                        iconv('UTF-8', 'GBK//IGNORE', $value['instock_month']),
                        $value['total_instock_price'],
                        $value['in_pay_freight'],
                        $value['in_pay_process_cost'] + $value['in_pay_commission'],
                        $value['in_pay_pay_price'],
                        iconv('UTF-8', 'GBK//IGNORE', $value['settlement_method']),
                        $value['accout_period_time'],
                        iconv('UTF-8', 'GBK//IGNORE', $value['is_purchasing']),
                        iconv('UTF-8', 'GBK//IGNORE', $value['free_shipping_cn']),
                        iconv('UTF-8', 'GBK//IGNORE', $value['purchase_name']),
                        iconv('UTF-8', 'GBK//IGNORE', $value['is_drawback']),
                        iconv('UTF-8', 'GBK//IGNORE', $value['create_user_name']),
                        iconv('UTF-8', 'GBK//IGNORE', $value['statement_user_name']),
                        iconv('UTF-8', 'GBK//IGNORE', $value['source_party_cn']),
                        $value['create_time']
                    ];
                    fputcsv($fp,$row);
                    unset($row);
                }catch (Exception $e){}
            }

            $page ++;

        } while (true);


        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url=get_export_path_replace_host(get_export_path('gateways_in_items/'.date('Ymd')),$down_host).$file_name;

        $this->success_json($this->_OK,$down_file_url);
    }

    /**
     * 入库批次 创建对账单（第一步）
     */
    public function gateway_create_statement_preview(){
        $instock_batchs = $this->_requestData['instock_batchs'];

        if(empty($instock_batchs)){
            // 读取缓存的查询SQL
            $create_statement_order_querySql = $this->rediss->getData(md5($this->_requestData['supplier_code'].'-create_statement_order'));
            if(empty($create_statement_order_querySql)) $this->error_json($this->_BadRequest,"缓存数据读取异常，请您先点击查询按钮刷新页面后，再操作，谢谢！");
            $create_statement_order_querySql = base64_decode($create_statement_order_querySql);
            $create_statement_order_querySql = str_replace("SELECT *","SELECT a.instock_batch",$create_statement_order_querySql);

            $instock_batchs  = $this->Purchase_statement_model->purchase_db->query($create_statement_order_querySql)->result_array();
            $instock_batchs  = array_column($instock_batchs,'instock_batch');
        }

        if(count($instock_batchs) > 10000) $this->error_json($this->_BadRequest,'您操作的数据过多，极耗系统资源，为保证系统稳定，请不要超过1万条数据');
        if(empty($instock_batchs)) $this->error_json($this->_BadRequest,'没有获取到待【生成对账单】的数据，请确认操作');

        // 如果生成对账单成功 则只返回 生成成功的对账单号
        // 如果一个对账单都没有生成成功  则 返回错误提示信息
        $inventory_item_list = $this->Purchase_inventory_items_model->get_inventory_items_for_statement(['instock_batch' => $instock_batchs]);
        if($inventory_item_list['code'] == false){
            $this->error_json($this->_BadRequest,$inventory_item_list['message']);
        }else{
            $inventory_item_list = $inventory_item_list['data'];
        }


        // 过滤 入库明细记录 是否可以生成对账单，显示错误数据
        $check_valid_result = $this->Purchase_statement_model->check_inventory_item_able_statement($inventory_item_list);
        if(empty($check_valid_result['data_list'])){// 没有有效数据
            $this->error_json($this->_BadRequest,'所有的数据都不符合要求',null,['error_list' => $check_valid_result['error_data'], 'able_charge_against_list' => []]);
        }else{
            $inventory_item_list = $check_valid_result['data_list'];
        }


        // 按 是否一致 组合条件分组
        $check_same_result = $this->Purchase_statement_model->group_inventory_item_is_same_statement($inventory_item_list);
        $inventory_item_list = $check_same_result['data_list'];
        unset($check_same_result);


        // 对比 当前要生成对账单的数据量 和 所有满足可生成对账单的入库记录的数据量
        foreach ($inventory_item_list as $inventory_item_key => $inventory_item_values){
            $explode_keys = explode('_',$inventory_item_key);// 分组的条件
            $explode_key_need_create_sum = count($inventory_item_values);// 分组的条件下需要创建对张单的数据条数

            // 查询数据库中 满足 $explode_keys 条件的数总个数
            $is_drawback        = $explode_keys[0];
            $supplier_code      = $explode_keys[1];
            $purchase_name      = $explode_keys[2];
            $pay_type           = $explode_keys[3];
            $account_type       = $explode_keys[4];
            $is_purchasing      = $explode_keys[5];
            $instock_month      = $explode_keys[6];
            $purchase_type_id   = $explode_keys[7];

            $check_able_sum_total = $this->Purchase_statement_model->get_able_create_statement_inventory_item($supplier_code,$purchase_type_id,$instock_month,$is_drawback,$account_type,$is_purchasing,$purchase_name,$pay_type);

            if($check_able_sum_total <= 0){// 没有数据，说明有异常，需排查
                $this->error_json($this->_BadRequest,'当前数据少于可对账的总数据，请核实可对账的总数[选择/总数='.$explode_key_need_create_sum.'/'.$check_able_sum_total.']');
            }

            if($explode_key_need_create_sum < $check_able_sum_total){
                $this->error_json($this->_BadRequest,'当前数据少于可对账的总数据，请核实可对账的总数[选择/总数='.$explode_key_need_create_sum.'/'.$check_able_sum_total.']');
            }
        }


        // 最终要生成对账单的目标数据（排除不符合要求）
        // 是否直接生成
        if(count($inventory_item_list) == 1 and count(current($inventory_item_list))  <= 1500){// 只有一个对账单，进行预览
            // 验证通过的入库批次号
            $instock_batchs = array_column(current($inventory_item_list),'instock_batch');
            $result = $this->Purchase_statement_model->get_statement_format_data($instock_batchs);
            if($result['code']){
                // Start:数据通过验证  缓存验证结果（入库批次 创建对账单（第二步） 调用）
                $instock_batchs = array_values($instock_batchs);
                sort($instock_batchs);
                $this->rediss->setData(md5(implode('_',$instock_batchs)),1,6000);
                // End:数据通过验证  缓存验证结果

                $this->success_json($this->_OK,$result['data']);
            }else{
                $this->error_json($this->_BadRequest,$result['message']);
            }
        }else{
            $return_data = [];
            // 多个对账单 进行拆分，自动生成，不预览
            foreach ($inventory_item_list as $key => $lists_value){
                if(count($lists_value) > 1500){
                    $lists_value_ll = array_chunk($lists_value,1500);
                }else{
                    $lists_value_ll = [$lists_value];
                }

                foreach($lists_value_ll as $ll_value){
                    // 数据验证通过，不存在可以冲销的入库单 -->>> 生成对账单
                    $result = $this->Purchase_statement_model->create_statement(array_column($ll_value,'instock_batch'),false);
                    if($result['code']){
                        $return_data['statement_number_list'][] = is_array($result['data'])?current($result['data']):$result['data'];
                    }else{
                        $return_data['error_list'][] = $result['message'];
                    }
                }
            }

            $this->success_json($this->_OK,$return_data);
        }

    }


    /**
     * 入库批次 创建对账单（第二步）
     */
    public function create_statement(){
        $instock_batchs = $this->_requestData['instock_batchs'];
        if(empty($instock_batchs)){
            // 读取缓存的查询SQL
            $create_statement_order_querySql = $this->rediss->getData(md5($this->_requestData['supplier_code'].'-create_statement_order'));
            if(empty($create_statement_order_querySql)) $this->error_json($this->_BadRequest,"缓存数据读取异常，请您先点击查询按钮刷新页面后，再操作，谢谢！");
            $create_statement_order_querySql = base64_decode($create_statement_order_querySql);
            $create_statement_order_querySql = str_replace("SELECT *","SELECT a.instock_batch",$create_statement_order_querySql);

            $instock_batchs  = $this->Purchase_statement_model->purchase_db->query($create_statement_order_querySql)->result_array();
            $instock_batchs  = array_column($instock_batchs,'instock_batch');
        }

        if(count($instock_batchs) > 10000) $this->error_json($this->_BadRequest,'您操作的数据过多，极耗系统资源，为保证系统稳定，请不要超过1万条数据');
        if(empty($instock_batchs)) $this->error_json($this->_BadRequest,'没有获取到待【生成对账单】的数据，请确认操作');


        // 如果生成对账单成功 则只返回 生成成功的对账单号
        // 如果一个对账单都没有生成成功  则 返回错误提示信息
        $inventory_item_list = $this->Purchase_inventory_items_model->get_inventory_items_for_statement(['instock_batch' => $instock_batchs]);
        if($inventory_item_list['code'] == false){
            $this->error_json($this->_BadRequest,$inventory_item_list['message']);
        }else{
            $inventory_item_list = $inventory_item_list['data'];
        }


        // 过滤 入库明细记录 是否可以生成对账单，显示错误数据
        $check_valid_result = $this->Purchase_statement_model->check_inventory_item_able_statement($inventory_item_list);
        if(empty($check_valid_result['data_list'])){// 没有有效数据
            $this->error_json($this->_BadRequest,'所有的数据都不符合要求',null,['error_list' => $check_valid_result['error_data'], 'able_charge_against_list' => []]);
        }else{
            $inventory_item_list = $check_valid_result['data_list'];
        }

        $instock_batchs = array_column($inventory_item_list,'instock_batch');

        // 数据通过验证  缓存验证结果
        $instock_batchs = array_values($instock_batchs);
        sort($instock_batchs);
        $exists = $this->rediss->getData(md5(implode('_',$instock_batchs)));

        if($exists){
            // 数据验证通过，不存在可以冲销的入库单 -->>> 生成对账单
            $result = $this->Purchase_statement_model->create_statement($instock_batchs);
            if($result['code']){
                $this->success_json($this->_OK,$result['data']);
            }else{
                $this->error_json($this->_BadRequest,$result['message']);
            }
        }else{
            $this->error_json($this->_BadRequest,'数据未通过验证，请重新创建对账单');
        }

    }

    /**
     * 添加备注
     * /others/Supplier_gateways/gateway_add_remark
     */
    public function gateway_add_remark(){
        if (!isset($this->_requestData['id']) or empty($this->_requestData['id']) or !is_array($this->_requestData['id'])) {
            $this->error_json($this->_BadRequest,'请求数据id缺失');
        }
        if (!isset($this->_requestData['remark']) or empty($this->_requestData['remark'])) {
            $this->error_json($this->_BadRequest,'备注信息不能为空');
        }
        $this->load->model('statement/Purchase_statement_note_model', 'note_model');
        $data = $this->note_model->add_remark($this->_requestData['id'], $this->_link_type, $this->_requestData['remark'],'添加备注');
        if ($data['flag']) {
            $this->success_json($this->_OK);
        } else {
            $this->error_json($this->_BadRequest,$data['msg']);
        }
    }

    /**
     * 上传附属文件
     */
    public function upload_attachment_pdf(){
        if(!SetAndNotEmpty($this->_requestData,'statement_number')){
            $this->error_json($this->_BadRequest,'必要参数缺失');
        }
        if(!SetAndNotEmpty($this->_requestData,'attachment_pdf_path')){
            $this->error_json($this->_BadRequest,'必要参数缺失');
        }

        $result = $this->Purchase_statement_model->upload_attachment_pdf($this->_requestData['statement_number'],$this->_requestData['attachment_pdf_path']);

        if($result['code'] == true){
            $this->success_json($this->_OK);
        }else{
            $this->error_json($this->_BadRequest,$result['message']);
        }
    }


    /**
     * 下载对账单运费明细 EXCEL
     * @author Jolon
     */
    public function download_freight_details()
    {
        if(!SetAndNotEmpty($this->_requestData,'statement_number')){
            $this->error_json($this->_BadRequest,'必要参数缺失');
        }
        $statement_number = $this->_requestData['statement_number'];

        $this->load->helper('status_order');
        $this->load->helper('status_finance');
        $this->load->model('supplier/supplier_model');

        try {
            $statement = $this->Purchase_statement_model->get_statement($statement_number);
            if(empty($statement)) $this->error_json($this->_BadRequest,'单据不存在');

            $supplierInfo = $this->supplier_model->get_supplier_info($statement['supplier_code'],false);

            $history_statement_list = [];
            // 不包邮时判断是否重复多次生产对账单，不显示运费 明细
            if($supplierInfo and $supplierInfo['is_postage'] == 2){// 2.不包邮
                $history_statement_list = $this->Purchase_statement_model->getHistoryStatementNumber(array_column($statement['summary_list'],'purchase_number'),$statement['create_time']);
            }

            $filePath = $this->Purchase_statement_model->download_freight_details_html($statement,$history_statement_list);

            if ($filePath) {
                $this->success_json($this->_OK,$filePath);
            } else {
                $this->error_json($this->_BadRequest,'模板数据生成失败');
            }
        } catch (Exception $e) {
            $this->error_json($this->_BadRequest,$e->getMessage());
        }

    }
}