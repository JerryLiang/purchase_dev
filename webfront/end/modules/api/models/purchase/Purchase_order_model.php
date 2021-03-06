<?php

/**
 * [function desc]
 * @author Jackson
 * @param
 * @DateTime 2019/1/21
 */
class Purchase_order_model extends Api_base_model
{

    /**
     * modules/api/conf/caigou_sys_purchase_order.php
     */
    protected $_rejectOrderApi;
    protected $_auditOrderApi;
    protected $keys_table = ['供应商', '采购单号', '历史采购单价', '采购数量', '入库数量', '采购日期', '采购员'];
    protected $key_value = [
       // 'id' => 'ID号',
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
    //    'is_include_tax' => '是否含税',
        'product_base_price' => '含税单价',
        'purchase_unit_price' => '未税单价',
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
//        'create_time' => '计划日期',
        'audit_time' => '审核日期',
        'plan_arrive_time' => '预计到货日期',
        'es_shipment_time' => '预计发货时间',
        'first_plan_arrive_time' => '首次预计到货时间',
        'source' => '采购来源',
        'freight' => '运费',
        'is_freight' => '运费支付',
        'discount' => '优惠额',
        'freight_formula_mode' => '运费计算方式',
        'purchase_acccount' => '网拍账号',
        'pai_number' => '拍单号',
        'arrival_date' => '仓库到货日期',
        'arrival_qty' => '仓库到货数量',
        'instock_date' => '仓库入库日期',
        'instock_qty' => '仓库入库数量',
        'instock_qty_more' => '多货数量',
        'logistics_type' => '物流类型',
        'amount_storage' => '入库金额',
        'amount_paid' => "已付金额",
        'overdue_days' => '逾期天数（权均）',
        'is_overdue' => '是否逾期(权均)',
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
        'is_inspection' => '是否商检',
        'lack_quantity_status' => '是否欠货',
        'purchase_packaging' => '包装类型',
        'check_status' => '验货状态',
        'push_gateway_success' => '是否推送成功',
        'is_customized' => '是否定制',
        'devliery_days' => '逾期天数（固定）',
        'devliery_status' => '是否逾期(固定)',
        'quantity' => '门户系统回货数',
        'overdue_day' => '是否逾期（预计）',
        'overdue_day_one' => '是否逾期（预计）',
        'overdue_day_day' => '逾期天数（预计）',
        'is_oversea_boutique' => '是否海外精品',
        'demand_type'           => '需求类型',
        'is_merge'              => '合单状态',
        'temp_container'        => '虚拟柜号',
        'free_shipping'         => '是否包邮',
        'is_distribution'       => '是否分销',
        'logistics_type'        => '物流类型',
        'price_reduction'       => 'SKU降价中',
    ];
    protected $search_row = [
        'purchase_order_status' => '采购状态',
        'suggest_order_status'  => '备货单状态',
        'demand_number'         => '备货单号',
        'purchase_number'       => '采购单号',
        'sku'                   => 'SKU',
        'create_time'           => '创建时间',
        'buyer_id'              => '采购员',
        'supplier_code'         => '供应商',
        'is_drawback'           => '是否退税',
        'product_name'          => '产品名称',
        'is_cross_border'       => '跨境宝供应商',
        'pay_status'            => '付款状态',
        'pay_notice'            => '付款提醒状态',
        'source'                => '采购来源',
        'is_destroy'            => '是否核销',
        'product_is_new'        => '是否新品',
        'purchase_type_id'      => '业务线',
        'compact_number'        => '合同号',
        'loss_status'           => '报损状态',
        'audit_status'          => '取消未到货状态',
        'is_ali_order'          => '是否1688下单',
        'express_no'            => '物流单号',
        'product_status'        => '商品状态',
        'is_ali_abnormal'       => '是否1688异常',
        'warehouse_code'        => '采购仓库',
        'pertain_wms'           => '公共仓',
        'pai_number'            => '拍单号',
        'account_type'          => '结算方式',
        'is_inspection'         => '是否商检',
        'is_overdue'            => '是否逾期(权均)',
        'supplier_source'       => '供应商来源',
        'statement_number'      => '对账单号',
        'need_pay_time'         => '应付款时间',
        'audit_time'            => '审核时间',
        'state_type'            => '开发类型',
        'is_expedited'          => '是否加急',
        'is_scree'              => '是否屏蔽申请中',
        'entities_lock_status'  => '是否锁单中',
        'lack_quantity_status'  => '是否欠货',
        'is_invaild'            => '链接是否失效',
        'is_forbidden'          => '供应商是否禁用',
        'is_ali_price_abnormal' => '金额异常',
        'level'                 => '审核级别',
        'is_relate_ali'         => '是否关联1688',
        'first_product_line'    => '一级产品线',
        'is_generate'           => '是否生成合同单',
        'is_purchasing'         => '是否代采',
        'is_arrive_time_audit'  => '交期确认状态',
        'order_num'             => '下单次数',
        'barcode_pdf'           => '是否有产品条码',
        'label_pdf'             => '是否有物流标签',
        'is_equal_sup_id'       => '供应商ID一致',
        'is_equal_sup_name'     => '供应商名称一致',
        'is_overseas_first_order' => '是否海外首单',
        'is_gateway'            =>'是否对接门户',
        'check_status'          =>'验货状态',
        'push_gateway_success'  => '是否推送成功',
        'gateway_status'        => '门户订单状态',
        'transformation'        => '国内转海外',
        'pay_finish_status'     => '付款完结状态',
        'ca_amount_search'      => '抵扣金额',
        'demand_purchase_type_id' => '备货单业务线',
        'is_customized'         => '是否定制',
        'devliery_days'         => '逾期天数(固定)',
        'devliery_status'       => '是否逾期(固定)',
        'quantity'              => '门户系统回货数',
        'shipment_type'         => '发运类型',
        'pay_type'              => '支付方式',
        'unfinished_overdue'    => '未完结天数',
        'pur_manager_audit_reject' => '采购经理驳回',
        'overdue_delivery_type' => '逾期天数类型',
        'ali_order_status'      => '1688订单状态',
        'ali_refund_amount'     => '1688退款金额',
        'instock_qty_gt_zero'   => '入库数量',
        'pay_time'              => '付款时间',
        'is_completion_order'   => '订单是否完结',
        'plan_arrive_time'      => '预计到货时间',
        'instock_date'          => '入库时间',
        'groupname'             => '采购组别',
        'new_is_freight'        => '运费结算方式',
        'tap_date_str'          => '线上账期日期',
        'mude_code'             => '目的仓',
        'supply_status'         => '货源状态',
        'overdue_days_one'      => '逾期天数(权均)',
        'overdue_day_one'       => '是否逾期（预计）',
        'overdue_day_day'       => '逾期天数（预计）',
        'use_wechat_official'   => '公众号启用状态',
        'track_status'          => '轨迹状态',
        'exp_no_is_empty'       => '快递单号是否为空',
        'is_long_delivery'      => '是否超长交期',
        'delivery'              => '交期',
        'is_fumigation'         => '是否熏蒸',
        'is_oversea_boutique'   => '是否海外精品',
        'demand_type'           => '需求类型',
        'is_merge'              => '合单状态',
        'temp_container'        => '虚拟柜号',
        'free_shipping'         => '是否包邮',
        'is_distribution'       => '是否分销',
        'logistics_type'        => '物流类型',
        'price_reduction'       => 'SKU降价中',

    ];

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
        $this->load->helper('export_csv');
        $this->load->helper('export_excel');
        $this->load->helper('status_supplier');
        $this->load->helper('status_order');
        $this->load->helper('status_finance');
    }

    /**
     * 采购单列表搜索部分下拉框
     * @author harvin
     *
     */
    public function get_status_lists($params = array(),$product_line_data = array())
    {
        // $url = $this->_baseUrl . $this->_searchUrl;
        // $result = $this->httrequest($params, $url);
        //  $data = $result['data_list'];
        $data_list = [];
        //采购单状态;
        $data_list['drop_down_box']['order_status'] = getPurchaseStatus();
        $data_list['drop_down_box']['suggest_order_status'] = getPurchaseStatus();

/*        if( !empty( $data_list['drop_down_box']['order_status']) ) {

            foreach(  $data_list['drop_down_box']['order_status']  as $key=>$value ) {

                if( $key == 14 ) {

                    unset( $data_list['drop_down_box']['order_status'][$key]);
                }
            }
        }*/
        //采购员
        $data_list['drop_down_box']['buyer_name'] = $this->get_buyer_name($params);//第三方接口
        //是否退税
        $data_list['drop_down_box']['is_drawback'] = getIsDrawback();
        //跨境宝供应商
        $data_list['drop_down_box']['is_cross_border'] = getCrossBorder();
        //付款状态;
        $data_list['drop_down_box']['pay_status'] = getPayStatus();
        //采购来源;
        $data_list['drop_down_box']['purchase_source'] = getPurchaseSource();
        //是否核销
        $data_list['drop_down_box']['is_destroy'] = getDestroy();
        //采购类型;
        $data_list['drop_down_box']['purchase_type'] = getPurchaseType();
        //备货单业务线-采购类型
        $data_list['drop_down_box']['demand_purchase_type_id'] = getPurchaseType();
        //是否新品;
        $data_list['drop_down_box']['is_new'] = getProductIsNew();
        //支付方式
        $data_list['drop_down_box']['pay_type'] = getPayType();
        //结算比例
        $data_list['drop_down_box']['settlement_ratio'] = getSettlementRatio();
        //供应商运输
        $data_list['drop_down_box']['shipping_method_id'] = getShippingMethod();
        //运费支付
        $data_list['drop_down_box']['is_freight'] = getFreightPayment();
        //运费计算方式
        $data_list['drop_down_box']['freight_formula_mode'] = freight_formula_mode();
        //账号
        $data_list['drop_down_box']['purchase_acccount'] = $this->get_purchase_acccount($params);
        // 采购单-驳回原因
        $data_list['drop_down_box']['purchase_reject_reason'] = getPurchaseOrderRejectReason();
        // 加急采购单
        $data_list['drop_down_box']['is_expedited'] = getIsExpedited();
        // 是否需要中转
        $data_list['drop_down_box']['is_transfer_warehouse'] = getIsTransferWarehouse();
        //报损审核状态
        $data_list['drop_down_box']['loss_status'] = getReportlossApprovalStatus();
        //取消未到货状态
        $data_list['drop_down_box']['audit_status'] =get_cancel_status();
        //付款提醒状态
        $data_list['drop_down_box']['pay_notice_status'] = getPayNotice_Status();
        //是否1688下单
        $data_list['drop_down_box']['is_ali_order'] = getIsAliOrder();
        //是否1688订单金额异常
        $data_list['drop_down_box']['is_ali_price_abnormal'] = getIsAliPriceAbnormal();
        //仓库下拉框
        $data_list['drop_down_box']['warehouse_code'] = getWarehouse();
        $data_list['drop_down_box']['mude_code'] = getWarehouse();
        //产品状态
        $data_list['drop_down_box']['product_status'] = getProdcutStatus();
        //1688异常状态
        $data_list['drop_down_box']['is_ali_abnormal'] = getIsAliAbnormalStatus();
        // 审核级别
        $data_list['drop_down_box']['level'] = get_Audit_interval();
        // 开票点是否为空
        $data_list['drop_down_box']['ticketed_point'] = getTicketedPoint();
        //结算方式
        $data_list['drop_down_box']['account_type'] = $this->get_settlement_list($params);
        $data_list['drop_down_box']['is_inspection'] =  [['is_inspection'=>1,'message'=>'不商检'],['is_inspection'=>2,'message'=>'商检']];
        //是否逾期
        $data_list['drop_down_box']['is_overdue'] = getIsOverdue();

        $data_list['drop_down_box']['supplier_source'] = getOptionSupplierSource();
        //2: "停产", 3: "断货", 4: "缺货", 99: "需要起订量"
        $data_list['drop_down_box']['scree_sku'] = getSkuScreeMessage();
        //$data_list['drop_down_box']['is_scree']= [['status'=>1,'message'=>'是'],['status'=>2,'message'=>'否']];
        $data_list['drop_down_box']['is_scree']= getOptionMessage();

        //发运类型
        $data_list['drop_down_box']['shipment_type'] = getShipmentType();
        $data_list['drop_down_box']['state_type'] = getProductStateType();
        $data_list['drop_down_box']['is_invaild'] =  getOptionMessage();


        $data_list['drop_down_box']['entities_lock_status'] = getEntitiesLockStatus();
        $data_list['drop_down_box']['lack_quantity_status'] = getIsLeftStock();
        $data_list['drop_down_box']['is_forbidden'] = getOption();

        $data_list['drop_down_box']['is_relate_ali'] = getOption();
        $data_list['drop_down_box']['is_generate']         = [1 => '否', 2 => '是'];
        $data_list['drop_down_box']['is_storage_abnormal'] = [1 => '是', 2 => '否'];
        $data_list['drop_down_box']['is_purchasing'] = [1 => '否', 2 => '是'];
        $data_list['drop_down_box']['barcode_pdf'] = [ 2=> '否', 1 => '是'];
        $data_list['drop_down_box']['label_pdf'] = [2 => '否', 1 => '是'];
        $data_list['drop_down_box']['is_overseas_first_order'] = getIsOverdue();
        $data_list['drop_down_box']['is_equal_sup_id'] = getEqualSupId();
        $data_list['drop_down_box']['is_equal_sup_name'] = getEqualSupName();
        $data_list['drop_down_box']['pay_status'] = getPayStatus();
        $data_list['drop_down_box']['transformation'] = [1=>'否',6=>'是'];
        $data_list['drop_down_box']['pay_finish_status'] = getPayFinishStatus();
        $data_list['drop_down_box']['ca_amount_search'] = ['=0' => '=0','≠0' => '≠0'];
        $data_list['drop_down_box']['devliery_days'] = ['1' => '=0','2' => '≠0'];
        $data_list['drop_down_box']['apply_status'] = ['1'=>'正常','2'=>'停产','3'=>'断货','10'=>'停产找货中'];

        if( !empty($product_line_data)) {
            $data_list['drop_down_box']['product_line'] = $product_line_data;
        }
        $data_list['drop_down_box']['seven_day_sales'] = [['status'=>1,'message'=>'大于0'],['status'=>2,'message'=>'等于0']];;
        //轨迹状态
        $data_list['drop_down_box']['track_status'] = array_merge(['0'=>'=空'],getTrackStatus());
        //异常类型
        $data_list['drop_down_box']['abnormal_type'] = getWarehouseAbnormalType();
        //是否在途异常
        $data_list['drop_down_box']['on_way_abnormal'] = getOnWayAbnormalStatus();

        //一级产品线
        $data_list['drop_down_box']['first_product_line'] = $this->get_first_product_line($params);
        $data_list['drop_down_box']['pertain_wms'] = $this->get_pertain_wms_list($params);

        //快递单号是否为空
        $data_list['drop_down_box']['exp_no_is_empty'] = getEmptyStatus();
        $data_list['drop_down_box']['is_gateway'] = [2 => '否', 1 => '是'];

        //交期确认状态
        $data_list['drop_down_box']['is_arrive_time_audit'] = getIsPredictStatus();
        //验货状态
       // $data_list['drop_down_box']['check_status'] = getCheckStatus();
        // 是否推送门户系统
        $data_list['drop_down_box']['push_gateway_success'] = [1=>'成功',2=>'失败'];
        // 采购单门户系统状态
        $data_list['drop_down_box']['gateway_status'] = getGateWaysStatus();
        $data_list['drop_down_box']['is_customized'] =[2 => '否', 1 => '是'];
        $data_list['drop_down_box']['devliery_status'] =[1 => '否', 2 => '是'];

        //逾期未完结天数（1-超20天未完结，2-超30天未完结，3-超40天未完结，4-45天以上总未完结，5-45-60天未完结，6-60天以上未完结）
        $data_list['drop_down_box']['unfinished_overdue'] = [1 => '超20天未完结', 2 => '超30天未完结', 3 => '超40天未完结', 4 => '45天以上总未完结', 5 => '45-60天未完结', 6 => '60天以上未完结'];
        //是否采购经理审核驳回
        $data_list['drop_down_box']['pur_manager_audit_reject'] = [1 => '是'];
        //逾期交货天数
        $data_list['drop_down_box']['overdue_delivery_type'] = [1 => '逾期7天以上',2 => '逾期10天以上',3 => '逾期15天以上',4 => '逾期20天以上',5 => '逾期30天以上',];
        //1688订单状态
        $data_list['drop_down_box']['ali_order_status'] = alibaba_pay_status();
        //1688退款金额≠0
        $data_list['drop_down_box']['ali_refund_amount'] = [1 => '≠0'];
        //入库数量≠0
        $data_list['drop_down_box']['instock_qty_gt_zero'] = [1 => '>0'];
        //订单是否完结
        $data_list['drop_down_box']['is_completion_order'] = [1 => '是', 2 => '否'];
        // SKU 货源状态
        $data_list['drop_down_box']['supply_status'] = [1=>'正常',2=>'停产',3=>'断货',10=>'停产找货中'];

        $url =  $this->_baseUrl .$this->_getGrupData;
        $result = $this->httrequest($params, $url);
        $data_list['drop_down_box']['groupname'] = (isset($result['data_list']['alias']))?$result['data_list']['alias']:'';

        return array(
            'data_list' => $data_list,
        );
    }

    /**
     * 获取一级产品线下拉
     * @author Manson
     * @return array
     */
    public function get_first_product_line($params=[])
    {
        $url =  $this->_baseUrl .$this->_getFirstProductLineUrl;
        $result = $this->httrequest($params, $url);
        return array_column(!empty($result['data_list'])?$result['data_list']:[], 'linelist_cn_name','product_line_id');
    }

    /**
     * 获取作废原因下拉框
     * @author jeff
     *
     */
    public function get_cancel_reasons($params = array())
    {
        //作废原因;
        $data_list = getCancelReasons();

        return array(
            'data_list' => $data_list,
        );
    }

    public function import_progress($params) {

        set_time_limit(0);
        ini_set('memory_limit','1024M');

        $file_path = $params['file_path'];
        $fileExp   = explode('.', $file_path);
        $fileExp   = strtolower($fileExp[count($fileExp) - 1]);//文件后缀

        include APPPATH.'third_party/PHPExcel/IOFactory.php';
        if ($fileExp == 'csv') $PHPReader = new \PHPExcel_Reader_CSV();
        if(!isset($PHPReader)){
            $return['code']    = false;
            $return['message'] = "只能导入 csv 文件 ";
            $return['data']    = '';
            return $return;
        }
        $PHPReader = PHPExcel_IOFactory::createReader('CSV')
            ->setDelimiter(',')
            ->setInputEncoding('GBK') //不设置将导致中文列内容返回boolean(false)或乱码
            ->setEnclosure('"')
            ->setSheetIndex(0);

        $PHPReader      = $PHPReader->load($file_path);

        $currentSheet   = $PHPReader->getSheet();
        $sheetData      = $currentSheet->toArray(null,true,true,true);
        $out = array ();
        $n = 0;
        foreach($sheetData as $data){

            $num = count($data);
            $i =0;
            foreach($data as $data_key=>$data_value){
                $out[$n][$i] = $data_value;
                ++$i;
            }
            $n++;
        }
        $params['import_arr'] = json_encode($out);
        $params['uid'] = $params['uid'];
        $url =$this->_baseUrl. $this->_import_progress;
        $result = $this->httpRequest($url, $params);
        if( isset($result['status']) && $result['status'] == 0 && $result['errorMess'])
        {
            $result = array(

                'status' => 0,
                'errorMess' => isset($result['errorMess']['error_message'])?$result['errorMess']['error_message']:'',
                'data_list' => isset($result['errorMess']['error_list'])?$result['errorMess']['error_list']:''
            );
        }
        return $result;


//        $url = $this->_baseUrl. $this->_import_progress;
//        $result = $this->_curlWriteHandleApi($url, $params, "POST");
//        return $result;

    }

    /**
      * 变更采购单号
     **/
    public function update_purchase_data( $params ) {


        $url = $this->_baseUrl . $this->_purchase_buyer;
        $result = $this->_curlWriteHandleApi($url, $params, "POST");
        return $result;

    }

    public function get_purchase_oa($params) {

        $url = $this->_baseUrl . $this->_get_purchase_oa;
        $result = $this->httrequest($params, $url);
        return $result;
    }

    public function get_progress_export($params) {

//        set_time_limit(0);
//        ini_set('memory_limit', '256M');
//        ini_set('pcre.backtrack_limit', -1);

        $url = $this->_baseUrl . $this->_progressExport;
        $result = $this->httrequest($params,$url);
        return $result;

//        $result = getCurlData($url, $params, 'POST', '', false);
//        $result = json_decode($result, true);
//
//        if( !empty($result) && isset($result['data_list']) ) {
//            header('location:' . $result['data_list']);
//        }else if( !empty($result) && !isset($result['status'])) {
//            throw new Exception("导出错误");
//        }
    }
    /**
     * 获取采购员旗下账号
     * **/
    public function get_purchase_acccount($params = array()){
         $url = $this->_baseUrl . $this->_searchUrl;
         $result = $this->httrequest($params, $url);
         $data = $result['data_list'];
         return $data;
    }
    /**
     * 获取采购员下拉
     * **/
    public function get_buyer_name($params = array()){
         $url =  $this->_baseUrl .$this->_buyernameUrl;
         $result = $this->httrequest($params, $url);
         return array_column(!empty($result['data_list'])?$result['data_list']:[], 'name','id');
	 }

	 public function get_purchase_progress_total($params = array()){

         $url =  $this->_baseUrl .$this->_get_purchase_progress_total;
         $result = $this->httrequest($params, $url);
         return $result;

     }

    public function get_purchase_progress( $params = array() ) {
        $data_list = [];
        $url =  $this->_baseUrl .$this->_get_purchase_progress;

        $result = $this->httrequest($params, $url);

        $product_line_drop_down = [];
        if( $result['status'] == 1 && isset($result['data_list']['product_line_drop_down']))
        {
            $product_line_drop_down = $result['data_list']['product_line_drop_down'];
            unset($result['data_list']['product_line_drop_down']);
        }
        $data_list = $this->get_status_lists($params,$product_line_drop_down);

        if( isset($result['data_list']['demand_type'])){

            $data_list['data_list']['drop_down_box']['demand_type'] = $result['data_list']['demand_type'];
            unset($result['data_list']['demand_type']);
        }

        if( isset($result['data_list']['is_merge'])){

            $data_list['data_list']['drop_down_box']['is_merge'] = $result['data_list']['is_merge'];
            unset($result['data_list']['is_merge']);
        }

        if( isset($result['data_list']['purchase_order_cancel'])){

            $data_list['data_list']['drop_down_box']['purchase_order_cancel'] = $result['data_list']['purchase_order_cancel'];
            unset($result['data_list']['purchase_order_cancel']);
        }

        if( isset($data_list['data_list']['drop_down_box']['suggest_order_status']))
        {
           foreach( $data_list['data_list']['drop_down_box']['suggest_order_status'] as $key=>$value){
              if( in_array($value,['等待采购询价','待采购审核','待生成进货单']))
              {
                  unset($data_list['data_list']['drop_down_box']['suggest_order_status'][$key]);
              }
           }
        }

        if( isset($data_list['data_list']['drop_down_box']['is_new'])){
            $data_list['data_list']['drop_down_box']['is_new'] = [['message'=>'是','status'=>1],['message'=>'否','status'=>0]];

        }
        $data_list['list'] = $result;
        return $data_list;

    }
    /**
     * 获取结算方式下拉
     * **/
    public function get_settlement_list($params = array()){
        $url =  $this->_baseUrl .$this->_settlementListUrl;
        $result = $this->httrequest($params, $url);
        return array_column(!empty($result['data_list']['list'])?$result['data_list']['list']:[], 'settlement_name','settlement_code');
    }
    /**
     * 获取公共仓
     * **/
    public function get_pertain_wms_list($params = array()){
        $url =  $this->_baseUrl .$this->_pertainWmsListUrl;
        $result = $this->httrequest($params, $url);
        return !empty($result['data_list'])?$result['data_list']:[];
    }
    /**
     * 获取 采购单列表
     * @author harvin
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-8
     */
    public function get_list($params)
    {
        $url = $this->_baseUrl . $this->_listUrl;

        $result = $this->httrequest($params, $url,'POST');

        if(!empty($result) && $result['status'] == 1){
            $data = $result['data_list']['value']; //返回数据
            $keys = $result['data_list']['key']; //动态的表头
        }else{
            return $result;
        }
        $result['data_list']['total'] = isset( $result['data_list']['total'])? $result['data_list']['total']:0;
        return array(
            'data_list' => array(
                'key' => $keys,
                'value' => $data,
                'drop_down_box' => null,
                'aggregate_data' => isset($result['data_list']['aggregate_data'])?$result['data_list']['aggregate_data']:NULL,
            ),
            'page_data' => array(
                'offset' => $result['data_list']['offset'],
                'limit' => intval($result['data_list']['limit']),
                'total' => $result['data_list']['total'],             
                'pages' => ceil($result['data_list']['total'] / $result['data_list']['limit'])
            ),
           
            
        );
    }

    public function get_list_sum($params) {

        $url = $this->_baseUrl . $this->_sum;
        $result = $this->httrequest($params, $url,'POST');
        return $result;
    }

    /**
     * 导出
     * @author harvin
     * **/
    public function get_list_export($params)
    {
        $url = $this->_baseUrl . $this->_exportUrl;

        $result = $this->httrequest($params, $url,'POST');
        return $result;

//        if (!empty($result) && isset($result['data_list'])) {
//            header('location:' . $result['data_list']);
//        } else if (!empty($result) && !isset($result['data_list'])) {
//            print_r($result);
//        }

    }

    /**
     * 导出excel
     * @author luxu 2019-8-1
     * **/
    public function get_list_progress_export_excel($params)
    {
        set_time_limit(0);
        ini_set('memory_limit','256M');
        $url = $this->_baseUrl . $this->_import_progess_excel;
        //$result = $this->httrequest($params, $url);
        //$result = getCurlData($url,$params,'POST','',false,array('time_out'=>3000));
        // $result = json_decode($result,true);
        $result = $this->_curlWriteHandleApi($url, $params, "POST");
//        pr($result);exit;
        $keys = ['序号','是否新品','发货省份','图片','备货单号','在途异常','备货单状态','备货单业务线','订单状态','业务线','供应商','采购单号','SKU','产品名称','采购数量',
            '入库数量','内部采购在途','仓库采购在途','未到货数量','跟进进度','采购员', '跟单员','审核时间','预计到货日期',
            '到货日期','入库日期','入库时效（h）','逾期天数','拍单号','采购仓库','跟进日期','备注说明',
            '请款时间','付款时间','付款时效(h)','采购来源','产品线',
            '产品状态','缺货数量','1688异常','近7天销量','物流公司','快递单号','轨迹状态','发货批次号','异常类型'];
        $data = $result['data_list']['value']; //返回数
        $result_array = [];
        $i=0;
        foreach( $data as $key=>$v_value ){
            $v_value_tmp = [];
            $v_value_tmp['id'] = ++$i;
            $v_value_tmp['is_new'] = $v_value['is_new_ch'];
            $v_value_tmp['provinces'] = $v_value['provinces'];

            $v_value_tmp['product_img'] = $v_value['product_img'];
            $v_value_tmp['demand_number'] = $v_value['demand_number'];
            $v_value_tmp['on_way_abnormal_cn'] = $v_value['on_way_abnormal_cn'];
            $v_value_tmp['suggest_status_ch'] = $v_value['suggest_status_ch'];
            $v_value_tmp['demand_purchase_type_id'] = $v_value['demand_purchase_type_id'];
            $v_value_tmp['purchase_status_ch'] = $v_value['purchase_status_ch'];
            $v_value_tmp['purchase_type_id'] = $v_value['purchase_type_id'];
            $v_value_tmp['supplier_name'] =  $v_value['supplier_name'];
            $v_value_tmp['purchase_number'] = $v_value['purchase_number'];
            $v_value_tmp['sku'] = $v_value['sku'];
            $v_value_tmp['product_name'] =  $v_value['product_name'];
            $v_value_tmp['purchase_num'] = $v_value['purchase_num'];
            $v_value_tmp['instock_number'] = $v_value['instock_qty'] ;
            $v_value_tmp['purchase_on_way_num'] = $v_value['purchase_on_way_num'] ;
            $v_value_tmp['warehouse_on_way_num'] = $v_value['warehouse_on_way_num'] ;
            $v_value_tmp['no_instock_date'] = $v_value['no_instock_date'];
            $v_value_tmp['progres'] =  $v_value['progres'];
            $v_value_tmp['buyer_name'] =$v_value['buyer_name'];
            $v_value_tmp['documentary'] =  $v_value['documentary_name'];
            $v_value_tmp['create_time'] = $v_value['create_time'];
            $v_value_tmp['estimate_time'] = $v_value['estimate_time'];
            $v_value_tmp['arrival_date'] = $v_value['arrival_date'];
            $v_value_tmp['instock_date'] = $v_value['instock_date'];
            $v_value_tmp['storage'] = $v_value['storage'];
            $v_value_tmp['storageday'] = $v_value['storageday'];
            $v_value_tmp['pai_number'] = $v_value['pai_number'];
            $v_value_tmp['warehourse'] = $v_value['warehourse'];
            $v_value_tmp['documentary_time'] = $v_value['documentary_time'];
            $v_value_tmp['remark'] = $v_value['remark'];
            $v_value_tmp['application_time'] = $v_value['application_time'];
            $v_value_tmp['payer_time'] = $v_value['payer_time'];
            $v_value_tmp['payer_h'] = $v_value['payer_h'];
            $v_value_tmp['source_ch'] = $v_value['source_ch'];
            $v_value_tmp['product_line_ch'] = $v_value['product_line_ch'];
            $v_value_tmp['product_status_ch'] = $v_value['product_status_ch'];
            $v_value_tmp['stock_owes'] = $v_value['stock_owes'];
            $v_value_tmp['ali_order_status_ch'] = $v_value['ali_order_status_ch'];
            $v_value_tmp['sevensale'] = $v_value['sevensale'];
            //快递公司 快递单号 轨迹状态
            if (isset($v_value['logistics_info']) && is_array($v_value['logistics_info']) && !empty($v_value['logistics_info'])){
                $cargo_company_id = array_column($v_value['logistics_info'],'cargo_company_id');
                $express_no = array_column($v_value['logistics_info'],'express_no');
                $logistics_status = array_column($v_value['logistics_info'],'logistics_status_cn');
                $batch_no = array_column($v_value['logistics_info'],'batch_no');
                $cargo_company_id = implode(' ',$cargo_company_id);
                $express_no = implode(' ',$express_no);
                $logistics_status = empty($logistics_status) ? '' : implode(' ',$logistics_status);
                $batch_no = empty($batch_no) ? '' : implode(' ',$batch_no);
                $v_value_tmp['logistics_company'] = $cargo_company_id;
                $v_value_tmp['courier_number'] =$express_no."\t";
                $v_value_tmp['logistics_status'] = $logistics_status;
                $v_value_tmp['batch_no'] = $batch_no."\t";
            }else{
                $v_value_tmp['logistics_company'] = '';
                $v_value_tmp['courier_number'] ='';
                $v_value_tmp['logistics_status'] ='';
                $v_value_tmp['batch_no'] = '';
            }
            //异常类型
            $abnormal_type_arr = explode(',',$v_value['abnormal_type_cn']);
            $abnormal_type_tmp = array();
            foreach ($abnormal_type_arr as $item) {
                $abnormal_type_tmp[]=$item;
            }
            $v_value_tmp['abnormal_type'] = implode(',',$abnormal_type_tmp);

            $result_array[] = $v_value_tmp;
        }
        $filename = '订单追踪.xls';
        export_excel($keys, $result_array, $filename,array('图片'),array('product_img'));
    }




    /**
     * 导出excel
     * @author sinder 2019-5-24
     * **/
    public function get_list_export_excel($params)
    {

        set_time_limit(0);
        ini_set('memory_limit','256M');
        $url = $this->_baseUrl . $this->_exportUrl;
        $result = $this->_curlWriteHandleApi($url, $params, "POST");
        $keys = array(); //动态的表头
        // $keys = array_column($keys,NULL,'key');
        foreach( $result['data_list']['key'] as $key=>$value ) {

            $keys[$value['key']] = $value['name'];
        }

        $data = $result['data_list']['value']; //返回数
        $keyss= array_keys($keys);
        $datalist=$data_values=$data_key=$heads=$data_list=[];
        foreach ($data as $row) {
            $row['purchase_order_status']= $row['purchase_order_status_name'];
            $row['suggest_order_status']= isset($row['suggest_order_status'])?getPurchaseStatus($row['suggest_order_status']):'';
            $row['source']= getPurchaseSource($row['source']);
            $row['pay_status']= $row['pay_status_name'];
            $row['product_img_url']= !empty($row['product_img_url'])?$row['product_img_url']:$row['product_img_url_thumbnails'];
            $row['is_equal_sup_id'] = getEqualSupId($row['is_equal_sup_id']);
            $row['is_equal_sup_name'] = getEqualSupName($row['is_equal_sup_name']);
            $row['plan_arrive_time'] = isset($row['plan_product_arrive_time']) ? $row['plan_product_arrive_time'] : '';

            foreach ($row as $key=>$val) {
                if(in_array($key, $keyss)){
                    $data_key[$key]=$row[$key];
                }
            }
            $datalist[]=$data_key;
            unset($data_key);
        }
        foreach ($datalist as $key =>$vv) {
            foreach ($vv as $k => $vvv) {
                if($key==0){
                    $heads[]=$keys[$k];
                }
                $data_list[]=$vvv;
            }
            $data_values[]=$data_list;
            unset($data_list);
        }
        $filename = '采购单-' . date('YmdHis') . '.xls';
        if( empty($heads)){
            $heads = array_values($keys);
        }

        export_excel($heads, $data_values, $filename,array('图片'),array('product_img'));
    }

    public function get_progess_history( $params ) {

        $url = $this->_baseUrl . $this->_get_progess_history;
        $result = $this->httrequest($params, $url);
        return $result;
    }

    /**
     * 获取采购单历史数据
     * @param string $sku
     * @author harvin 21019-1-24
     * @return array data;
     **/
    public function order_history($params)
    {
        $url = $this->_baseUrl . $this->_historytUrl;
        $result = $this->httrequest($params, $url);
        if($result['status'] == 1){
            $data = $result['data_list']['data']; //返回数据
            return array(
                'data_list' => array(
                    'key' => $this->keys_table,
                    'value' => $data,
                    'drop_down_box' => null
                ),
                'page_data' => array(
                    'offset' => $result['data_list']['offset'],
                    'limit' => intval($result['data_list']['limit']),
                    'total' => $result['data_list']['total'],
                    'pages' => ceil($result['data_list']['total'] / $result['data_list']['limit'])
                )
            );
        }else{
            return $result;
        }
    }

    /**
     * 批量编辑采购单
     */
    public function get_batch_edit_order($params)
    {
        $url = $this->_baseUrl . $this->_getBatchEditOrderUrl;
//        print_r($url);exit;
        $result = $this->httrequest($params, $url);
        return $result;
    }

    /**
     * 批量编辑采购单-保存
     */
    public function save_batch_edit_order($params)
    {
        $header = ['Content-Type: application/json'];
        $url    = $this->_baseUrl . $this->_saveBatchEditOrderUrl.'?uid='.$params['uid'];
        $result = getCurlData($url,json_encode($params),'post',$header);

        $result = json_decode($result,true);
        return $result;

        $url = $this->_baseUrl . $this->_saveBatchEditOrderUrl;
        $result = $this->httrequest($params, $url);
        return $result;
    }
     /**
     * 应付款单列表页
     * @author harvin
     * @date 2019-5-13
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_batch_audit_order_list($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_batch_audit_orderApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
        
    }
     /**
     * 财务审核操作
     * @author harvin
     * @date 2019-5-13
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
    public function batch_audit_order_save($post)
    {
        $url = $this->_baseUrl . $this->_batch_audit_order_saveApi;
        $result = $this->_curlWriteHandleApi($url, $post, 'POST');
        $result['status'] = 1;
        $result['errorMess'] = $this->_errorMsg;
        return $result;
    }


    /**
     * 无需付款操作
     */
    public function no_payment_save($post)
    {
        $url = $this->_baseUrl . $this->_no_payment_saveApi;
        $result = $this->_curlWriteHandleApi($url, $post, 'POST');
        $result['status'] = 1;
        $result['errorMess'] = $this->_errorMsg;
        return $result;
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
    public function save_purchase($params)
    {
        $url = $this->_baseUrl . $this->_informationdeUrl;
        $result = $this->httrequest($params, $url);
        return $result;
    }

    /**
     * 更新采购单的状态
     * @param  array $ids
     * @author harvin 2019-1-10
     *
     * */
    public function order_status_save($params)
    {
        $url = $this->_baseUrl . $this->_revokeUrl;
        $result = $this->httrequest($params, $url);   
        return $result;
    }

    /**
     * 采购单列表列名称
     * @author harvin
     * @return array
     */
    public function  table_columns()
    {  
        $data = [
            'data_list' => $this->key_value,
        ];
        return $data;
    }

    /**
     * 保存采购列表字段判断用户显示
     * @param json $data json数据
     * @author harvin
     * ** */
    public function table_save($params)
    {
        $url = $this->_baseUrl . $this->_tablesaveUrl;
        $result = $this->httrequest($params, $url);
        return $result;
    }

    /**
     * 采购单列表搜索框配置-查看
     */
    public function get_table_search_header($params)
    {
        $res = [
            "status"        => 0,
            "errorMess"     => '',
            "data_list"     => []
        ];
        if(!isset($params['uid']) || empty($params['uid']) || !isset($params['list_type']) || empty($params['list_type'])){
            $res['errorMess'] = '用户ID已失效，或者页签数据不能为空。';
            return $res;
        }

        $def_data = [
            "purchase_order_status"=>[
                "index"=>0,
                "status"=>1,
                "name"=>"采购状态"
            ],
            "suggest_order_status"=>[
                "index"=>1,
                "status"=>1,
                "name"=>"备货单状态"
            ],
            "purchase_number"=>[
                "index"=>2,
                "status"=>1,
                "name"=>"采购单号"
            ],
            "create_time"=>[
                "index"=>3,
                "status"=>1,
                "name"=>"创建时间"
            ]
        ];
        /*
        $redis=new Rediss();
        $hash = "HEADER_SEARCH_DATA_LIST";
        $hash_field = "HEADER_SEARCH_DATA_".$params['uid']."_".$params['list_type'];
        if($redis->hexists($hash, $hash_field)){
            $data =$redis->hmget($hash, $hash_field); // 用户配置
            try{
                $data = json_decode($data[0]);
                $data =  json_decode(json_encode($data),true);
            }catch (Exception $e){}
        }*/

        $url =  $this->_baseUrl .$this->_getTableSearchHeader;
        $get_back = $this->httrequest($params, $url);
        $data = false;
        if($get_back && isset($get_back['status']) && $get_back['status']== 1){
            try{
                if(gettype($get_back['data_list']) == 'string')$get_back['data_list'] = json_decode($get_back['data_list'],true);
                $data = count($get_back['data_list']) >0? $get_back['data_list']: false;
            }catch (Exception $e){}
        }
        $base_data = $this->search_row;
        $base = $this->handle_search_base_data($base_data, $params['list_type']);
        $res['errorMess'] = '获取成功';
        $res['status'] = 1;
        $data_type = gettype($data);
        $res_list = [];
        if($data && in_array($data_type, ['array', 'object']) && count($data) > 0){
            $res_list = $this->search_data_select($base, $data);
        }else{
            foreach($base as $key=>$val){
                foreach($def_data as $d_k=>$d_v){
                    if($d_k == $key){
                        $val['index'] = $d_v['index'];
                        $val['status'] = $d_v['status'];
                    }
                }
                $res_list[$key] = $val;
            }
        }
        $res['data_list'] = $res_list;

        return $res;
    }

    /**
     * 需要请求的
     */
    protected $need_request_list = [
        "account_type",
        "ali_order_status",
        "audit_status",
        "buyer_id",
        "first_product_line",
        "gateway_status",
        "is_arrive_time_audit",
        "loss_status",
        "pay_status",
        "pertain_wms",
        "product_status",
        "suggest_order_status",
        "supplier_code",
        "warehouse_code",
        "groupname",
        "mude_code",
        "logistics_type",
    ];

    /**
     * 组装基本的搜索数据
     */
    private function handle_search_base_data($data_list, $list_type=2)
    {
        $result = [];
        $set = [
            'select_more'   => ['supply_status','track_status','mude_code','ali_order_status','warehouse_code','account_type','pay_notice','loss_status',
                'audit_status','first_product_line','level','product_status','buyer_id','purchase_order_status','suggest_order_status','pertain_wms','purchase_type_id',
                'check_status','pay_status','gateway_status','pay_finish_status','pay_notice','groupname','tap_date_str','demand_purchase_type_id', 'demand_type',
                'logistics_type','supplier_code'
            ],
            'select_one'    => ['delivery','is_long_delivery','exp_no_is_empty','overdue_day_day','overdue_day_one','overdue_days_one','new_is_freight',
                'suggest_order_status','level','buyer_name','is_drawback','is_cross_border','pay_status',
                'purchase_source','is_destroy','is_new','purchase_type_id','is_ali_order','product_status','is_ali_abnormal',
                'is_inspection','is_overdue','supplier_source','state_type','is_expedited','is_scree','source','is_oversea_boutique',
                'entities_lock_status','lack_quantity_status','is_invaild','is_forbidden','is_ali_price_abnormal','ticketed_point','is_relate_ali','is_storage_abnormal','is_purchasing',
                'is_arrive_time_audit','barcode_pdf','devliery_days','is_generate','is_overseas_first_order','devliery_status',
                'label_pdf','is_equal_sup_id','is_equal_sup_name','pertain_wms','is_gateway', 'is_fumigation',
                'check_status','push_gateway_success','gateway_status','transformation','pay_finish_status','ca_amount_search','is_customized',
                'shipment_type', 'pay_type','unfinished_overdue','pur_manager_audit_reject','overdue_delivery_type','ali_refund_amount',
                'instock_qty_gt_zero','is_completion_order', 'use_wechat_official', 'is_merge', "free_shipping", "free_shipping", 'is_distribution','price_reduction'
            ],
            "date"          => ['create_time','need_pay_time','audit_time','pay_time', 'plan_arrive_time', 'instock_date'],
            "keys"          => ['demand_number','purchase_number','sku','pai_number','statement_number','compact_number', 'temp_container'],
            "must"          => ['purchase_order_status',"suggest_order_status",'purchase_number','create_time'],
            "not_date_type" => ['need_pay_time','audit_time','pay_time'], // 无没有格式日期
            "range_num"     => ['order_num']
        ];
        // 无需缓存的数据
        $select_data_list = [
            "demand_type"               => getDemandType(),
            "is_merge"                  => ["1"=> "已合单", "0"=> "正常"],
            "free_shipping"             => getIsPostage(),
            "ali_refund_amount"         => [1 => "≠0"],
            "barcode_pdf"               => [1=> "是", 2=> "否"],
            "price_reduction"           => [1=> "是", 2=> "否"],
            "ca_amount_search"          => ["=0"=> "=0", "≠0" => "≠0"],
            "demand_purchase_type_id"   => getPurchaseType(),
            "devliery_days"             => ["1"=> "=0", "2" => "≠0"],
            "devliery_status"           => [1=> "否", 2=> "是"],
            "is_distribution"           => ["1"=> "是", "2"=> "否", "0" => "历史数据"],
            "entities_lock_status"      => [1=> "否", 2=> "是"],
            "instock_qty_gt_zero"       => [1=>">0"],
            "is_ali_abnormal"           => ["1"=> "是", "0"=> "否"],
            "is_ali_order"              => [1=> "是", "0"=> "否"],
            "is_ali_price_abnormal"     => ["1"=> "是", "0"=> "否"],
            "is_completion_order"       => [1=> "是", 2=> "否"],
            "is_cross_border"           => ["1"=> "是", "0"=> "否"],
            "is_oversea_boutique"       => ["1"=> "是", "0"=> "否"],
            "is_customized"             => [1=> "是", 2=> "否"],
            "source"                    => [1=> "合同", 2=> "网采"],
            "is_destroy"                => ["1"=> "是", "0"=> "否"],
            "is_drawback"               => ["1"=> "是", "0"=> "否"],
            "is_equal_sup_id"           => ["1"=> "是", "2"=> "否", "0"=> "空"],
            "is_equal_sup_name"         => ["1"=> "是", "2"=> "否", "0"=> "空"],
            "is_expedited"              => [1=> "否", 2=> "是"],
            "is_forbidden"              => [1=> "是", 2=> "否"],
            "is_gateway"                => [1=> "是", 2=> "否"],
            "is_generate"               => [1=> "否", 2=> "是"],
            "is_inspection"             => [1=> "不商检", 2=> "商检"],
            "is_invaild"                => [1=> "是", 2=> "否"],
            "is_new"                    => ["1"=> "是", "0"=> "否"],
            "is_overdue"                => [1=> "是", "0"=> "否"],
            "is_overseas_first_order"   => ["1"=> "是", "0"=> "否"],
            "is_purchasing"             => [1=> "否", 2=> "是"],
            "is_relate_ali"             => [1=> "是", 2=> "否"],
            "is_scree"                  => [1=> "是", 2=> "否"],
            "use_wechat_official"       => [1=> "否", 2=> "是"],
            "label_pdf"                 => [1=> "是", 2=> "否"],
            "lack_quantity_status"      => [1=> "是", 2=> "否"],
	        "new_is_freight"            => [1=>"甲方支付",2=>"乙方支付"],
            "supply_status"             => [1=>"正常",2=>"停产",3=>"断货",10=>"停产找货中"],
            "track_status"              => [1=>"=空", 2=>"已揽件", 3=>"已发货", 4=>"已到提货点", 5=>"派件中", 6=>"已签收", 7=>"问题件"],
            "exp_no_is_empty"                =>[1=>"≠空", 2=>"=空"],
            "is_long_delivery"          => [2=> "是", 1=> "否"],
	    
            "unfinished_overdue"        => [
                1 => '超20天未完结',
                2 => '超30天未完结',
                3 => '超40天未完结',
                4 => '45天以上总未完结',
                5 => '45-60天未完结',
                6 => '60天以上未完结'
            ],
            "level"                     => [
                "deputy_manager_start"  => "副经理审核",
                "director"              => "主管审核",
                "headman"               => "组长审核",
                "majordomo"             => "总监审核",
                "manager"               => "经理审核",
            ],
            "overdue_delivery_type"     => [
                1                       => "逾期7天以上",
                2                       => "逾期3天以上",
                3                       => "逾期15天以上",
                4                       => "逾期20天以上",
                5                       => "逾期30天以上",
            ],
            "pay_finish_status"         => [
                1                       => "未付",
                2                       => "无需付",
                3                       => "部分已付",
                4                       => "已付",
            ],
            "pay_notice"                => [
                1                       => "已超期",
                2                       => "即将到期",
                3                       => "可继续等待",
                4                       => "额度已满，需紧急支付",
            ],
            "pay_type"                  => [
                1                       => "线上支付宝",
                2                       => "线下境内",
                3                       => "线下境外",
                4                       => "paypal",
                5                       => "银行公对公",
                6                       => "p卡",
            ],
            "pur_manager_audit_reject"  => [1 => "是"],
            "purchase_order_status"     => [
                "4"                     => [1, 3, 6],
                "6"                     => [2, 7, 8, 10, 12, 13, 15],
                "8"                     => [2, 9, 11, 14, 15],
                "10"                    => [2, 6, 7, 10, 12, 13, 15],
                "12"                    => [7, 10],
            ],
            "purchase_type_id"          => getPurchaseType(),
            "tap_date_str"              => [
                "一个月一结，1号"         => "一个月一结，1号",
                "一个月一结，2号"         => "一个月一结，2号",
                "一个月一结，3号"         => "一个月一结，3号",
                "一个月一结，5号"         => "一个月一结，5号",
                "一个月一结，6号"         => "一个月一结，6号",
                "一个月一结，7号"         => "一个月一结，7号",
                "一个月一结，8号"         => "一个月一结，8号",
                "一个月一结，10号"        =>  "一个月一结，10号",
                "一个月一结，13号"        => "一个月一结，13号",
                "一个月一结，14号"        => "一个月一结，14号",
                "一个月一结，15号"        => "一个月一结，15号",
                "一个月一结，16号"        => "一个月一结，16号",
                "一个月一结，18号"        => "一个月一结，18号",
                "一个月一结，20号"        => "一个月一结，20号",
                "一个月一结，25号"        => "一个月一结，25号",
                "一个月一结，26号"        => "一个月一结，26号",
                "一个月一结，28号"        => "一个月一结，28号",
                "两个月一结，1号"         => "两个月一结，1号",
                "两个月一结，15号"        => "两个月一结，15号",
                "两个月一结，20号"        => "两个月一结，20号",
                "两个月一结，28号"        => "两个月一结，28号",
                "7天"                   => "7天",
                "10天"                  => "10天",
                "20天"                  => "20天",
                "30天"                  => "30天",
                "45天"                  => "45天",
                "60天"                  => "60天",
            ],

            "overdue_days_one"          => ["1"=>"≠0","2"=>"=0"],
            "overdue_day_one"           => ["1"=>"是","2"=>"否"],
            "overdue_day_day"           => ["1"=>"≠0","2"=>"=0"],
            "delivery"                  => ["1"=>"≠0","2"=>"=0"],
            "push_gateway_success"      => [1=>'成功',2=>'失败'],
            "shipment_type"             => getShipmentType(),
            "supplier_source"           => [1 => '常规', 2 =>'海外',3 =>'临时'],
            "transformation"            => [1 =>'否',6=>'是'],
            "state_type"                => getProductStateType(),
            "is_fumigation"             => ["1"=>"是","2"=>"否"],
        ];

        foreach($data_list as $key => $value){
            if($key == "product_is_new"){
                $key = "is_new";
            }

            $disabled       = 0;
            $cass_type      = 'input';
            $select         = '';
            $specific_time  = 0;
            $first_data     = '';
            $url_address    = '';
            $placeholder    = '';
            $need_request = 0;

            if(in_array($key, $set['must'])){   // 查询条件限制  必选
                $disabled        = 1;
            }
            if(in_array($key, $set['date'])){   // 日期
                $cass_type = 'date';
                $specific_time  = 1;
                //if(in_array($key, $set['not_date_type']))$specific_time  = 1;
            }else if(in_array($key, $set['keys'])){    // 空格符分开
                $placeholder = '多个用空格分开';
            }else if(in_array($key, $set['select_more'])){    // 多选
                $cass_type = 'select';
                $select      = '2';
                $placeholder = '请选择';
            }else if(in_array($key, $set['select_one'])){    // 单选
                $cass_type = 'select';
                $select      = '1';
                $placeholder = '请选择';
            }else if(in_array($key, $set['range_num'])){
                $cass_type   = 'InputRangeNumber';
            }

            $url_address = "/api/purchase/purchase_order/get_search_select_data";

            $select_data = in_array($key, array_keys($select_data_list))? $select_data_list[$key] : [];
            if(in_array($key, $this->need_request_list)){
                $need_request = 1;
            }
            if(in_array($key, ["create_time"]) && !in_array($list_type, [6, 10])){
                $first_data = date('Y-m-d', strtotime('-6 days')).','.date('Y-m-d', strtotime('+1 days'));
            }

            if($key == 'purchase_order_status'){
                $first_data  = '1';// 默认待采购询价
                $select_data = [];
                $order_status = getPurchaseStatus();
                $base_status = $select_data_list['purchase_order_status'];
                if($order_status && count($order_status) > 0){
                    if($list_type == 2)$select_data = $order_status;
                    if(in_array($list_type, array_keys($base_status))){
                        foreach ($order_status as $k_l=>$v_l){
                            if(in_array((int)$k_l, $base_status[$list_type]))$select_data[(string)$k_l] = $v_l;
                        }
                    }
                }
            }
            if($key == 'supplier_code'){
                $need_request = 2;
                $placeholder = '请输入关键字';
            }

            $result[$key] = [
                'index'          => -1,
                'status'         => 0,
                'attribute_name' => $value,
                'cass_type'      => $cass_type,// 筛选框类型：select,input,date,caserder
                'first_data'     => $first_data,// 首次打开默认值
                'html_name'      => $key,
                'select'         => $select,// 1.单选，2.多选
                'select_data'    => $select_data, //$select_data,// 下拉框数据
                'url_address'    => $url_address,// 动态搜索API
                'disabled'       => $disabled,// 是否禁用 1.禁用，0.启用
                'specific_time'  => $specific_time,// 有无时间格式 1:有时间，0：无时间（cass_type=date有效）
                'placeholder'    => $placeholder,// 提示语
                'is_url'         => $need_request,// 是否需要请求后端接口
            ];
        }

        return $result;
    }

    /**
     * 头部搜索选中
     */
    public function search_data_select($base, $data)
    {
        $res = [];
        foreach ($base as $k=>$v){
            foreach ($data as $key=>$val){
                if($k == $key){
                    $v['index'] = $val['index'];
                    $v['status'] = $val['status'];
                    break;
                }
            }
            $res[$k] = $v;
        }
        return $res;
    }

    public function getGroupName($params){

        $url =  $this->_baseUrl .$this->_getGroupName;
        $result = $this->httrequest($params, $url);

        return isset($result['data_list']['alias'])?$result['data_list']['alias']:'';

    }

    public function get_order_payment_pay($params){
        $url =  $this->_baseUrl .$this->_getOrderPaymentPay;
        return $this->httrequest($params, $url);

    }

    /**
       * 获取目的仓库数据
     **/
    public function getMudeCode($params){

        $url =  $this->_baseUrl .$this->_getMudeCode;
        $data = $this->httrequest($params, $url);
        return isset($data['data_list'])?$data['data_list']:'';
    }

    /**
     * 获取一个选项的配置
     */
    public function get_search_data_by_one($params)
    {
        $action = $params['action'];
        $res = [
            "errorMess"     => '暂无数据',
            "status"        => 0,
            "data_list"     => []
        ];
        if(!in_array($action, $this->need_request_list))return $res;
        $data = [];
        switch ($action){
            case 'account_type':
                $data = $this->get_settlement_list($params);
                break;
            case 'ali_order_status':
                $data = alibaba_pay_status();
                break;
            case 'audit_status':
                $data = get_cancel_status();
                break;
            case 'buyer_id':
                $data = $this->get_buyer_name($params);
                break;
            case 'first_product_line':
                $data = $this->get_first_product_line($params);
                break;
            case 'gateway_status':
                $data = getGateWaysStatus();
                break;
            case 'is_arrive_time_audit':
                $data = getIsPredictStatus();
                break;
            case 'loss_status':
                $data = getReportlossApprovalStatus();
                break;
            case 'pay_status':
                $data = getPayStatus();
                break;
            case 'pertain_wms':
                $data = $this->get_pertain_wms_list($params);
                break;
            case 'product_status':
                $data = getProdcutStatus();
                break;
            case 'suggest_order_status':
                $data = getPurchaseStatus();
                break;
            case 'groupname':
                $data = $this->getGroupName($params);
                break;

            case 'supplier_code':
                $fields = isset($params['fields']) ? $params['fields']: '';
                $supplier_param = [
                    "supplier_name" => $fields,
                    "uid"           => $params['uid']
                ];
                $this->load->model('supplier/Supplier_model');
                $req_data = $this->Supplier_model->supplier_list($supplier_param);
                $data = isset($req_data['status']) && $req_data['status'] == 1? $req_data['data_list']: [];
                break;
            case 'warehouse_code':
                $data = getWarehouse();
                break;

            case 'mude_code':
                $data = $this->getMudeCode($params);
                break;
            case 'logistics_type':
                $data = $this->getLogisticsType($params);
                break;

        }
        if(count($data) == 0)return $res;

        $res['errorMess'] = '获取成功';
        $res['status'] = 1;
        $res['data_list'] = $data;
        /*[
            "key"   => $action,
            "value" => $data
        ];*/
        return $res;
    }

    /**
     * 获取 物流类型列表
     */
    public function getLogisticsType($params)
    {
        $url = $this->_baseUrl . $this->_getLogisticsType;
        $result = $this->httrequest($params, $url);
        if(isset($result['data_list']) && isset($result['status']) && $result['status'] == 1){
            $res = [];
            foreach ($result['data_list'] as $val){
                $res[$val['key']] = $val['value'];
            }
            return $res;
        };
        return [];
    }

    /**
     * 采购单列表搜索框配置-保存
     * @param json $data json数据
     * @author jolon
     * ** */
    public function save_table_search_header($params)
    {
        $url = $this->_baseUrl . $this->_saveTableSearchHeader;
        $result = $this->httrequest($params, $url);
        return $result;
    }

    /**
     * 查找采购单明细表 采购单号及对应的sku
     * @param array $ids id
     * * */
    public function get_order_binding_logistics($params)
    {
        $url = $this->_baseUrl . $this->_logisticlistsUrl;
        $result = $this->httrequest($params, $url);
        if($result['status'] == 1){
            return array(
                'data_list' => array(
                    'value' => $result['data_list'],
                    'drop_down_box' => $result['drop_down_box'],
                )
            );
        }else{
            return $result;
        }
    }


    /**
     * 保存物流单号相关信息
     * @param array $$purchase_number 采购单号
     * @param string $express_no 快递单号
     * @param string $cargo_company_id 快递公司
     * @author harvin 2019-1-8
     * * */
    public function get_order_binding_logistics_save($params)
    {
        $url = $this->_baseUrl . $this->_logisticsUrl;
        $result = $this->httrequest($params, $url);
        return $result;
    }

    /**
     * 查询采购单数据
     * @param array $ids 数组id
     * @return array $data 返回的数组
     * @author harvin 2019-1-10
     * * */
    public function get_printing_purchase_order($params)
    {
        $url = $this->_baseUrl . $this->_printingUrl;
        $result = $this->httrequest($params, $url);
        return $result;
    }
    /**
    * 返回打印采购单数据
    * @author harvin
    * **/
    public function  get_print_menu($post){
        $url = $this->_baseUrl . $this->_print_menuUrl;
        return $this->_curlReadHandleApi($url, $post, 'POST');
    }
    /**
     * 根据指定的 采购单编号 预览采购单合同数据
     *      生成进货单第一步 - 合同采购确认
     * @author Jolon
     */
    public function compact_confirm_purchase($params = array())
    {

        //1.调用接口
        $result = $this->_curlRequestInterface($this->_confirmPurchaseUrl, $params, 'POST');

        //2.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End
        return $result;

    }


    /**
     * 根据指定的 采购单编号 创建对账单
     * @author Jaden
     */
    public function web_create_statement_confirm($params = array())
    {
        //1.调用接口
        $result = $this->_curlRequestInterface($this->_conStatementConfirmUrl, $params, 'POST');
        //2.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End
        return $result;
    }






    /**
     * 根据指定的 采购单编号 生成合同模板
     *      生成进货单第二步 - 合同模板确认（在 compact_confirm_purchase 之后）
     * @author Jolon
     */
    public function compact_confirm_template($params = array())
    {
        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_confirmTemplateUrl, $params, 'POST');

        //3.判断返回结果
        if (is_null($result)) {
            return null;
        }
        return $result;

    }

    /**
     * 根据指定的 采购单编号 生成采购合同
     *      生成进货单第三步 - 合同创建（在 compact_confirm_template 之后）
     * @author Jolon
     */
    public function compact_create($params = array())
    {
        //1.调用接口
        $result = $this->_curlRequestInterface($this->_compactCreatUrl, $params, 'POST');

        //2.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End
        return $result;

    }

    /**
     * 增加KEY头
     * @author Jolon
     */
    public function add_table_header($result = array(), $_tableHeader = '')
    {
        $responsed = array();
        //添加key
        if (!empty($result['data_list']) && $_data = $result['data_list']) {

            $responsed['status'] = $result['status'];
            $responsed['data_list'] = array();

            foreach ($_data as $key => $item) {
                if (is_array($_data[$key])) {
                    $responsed['data_list'][$key]['key'] = $_tableHeader;//添加tableHeader
                    $responsed['data_list'][$key]['value'] = $_data[$key];//值
                }
            }

        }
        return $responsed;

    }

    /**
     * 调用api服务层写操作
     * @author liwuxue
     * @date 2019/1/30 10:55
     * @param $url
     * @param $post
     * @return mixed
     * @throws Exception
     */
    private function _postWriteApi($url, $post)
    {
        //调用服务层api
        $result = parent::httpRequest($url, $post, 'POST');
        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:" . json_encode($result, 256),
                -1
            );
        }
    }

    /**
     * 采购单驳回
     * @author liwuxue
     * @date 2019/1/30 10:47
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function reject_order($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_rejectOrderApi;
        return $this->_postWriteApi($url, $post);
    }

    /**
     * 采购单作废
     * @author liwuxue
     * @date 2019/1/30 10:47
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function cancel_order($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_cancelOrderApi;
        return $this->_postWriteApi($url, $post);
    }

    /**
     * 采购单审核
     * @author liwuxue
     * @date 2019/1/30 10:49
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function audit_order($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_auditOrderApi;
        return $this->_postWriteApi($url, $post);
    }

    /**
     * 采购单审核(蓝凌审核)
     * @author harvin
     * @date 2019/1/30 10:49
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function audit_orders($params)
    {
        $url = $this->_baseUrl . $this->_auditsOrderApi;
        $result = $this->httpRequest($url, $params, 'POST');

        if ($result['status'] == 1) {
            return $result['data_list'];
        } else {
            return NULL;
        }
    }

    public function get_purchase_operator_log($params)
    {
        $url = $this->_baseUrl . $this->_getoperator_logUrl;
        return $this->request_http($params, $url);
    }

    /**
     * 获取报损界面sku数据
     * 2019-02-01
     * @author Jaxton
     * @parame $purchase_number
     * @return array()
     */
    public function get_reportloss_sku_data($params)
    {
        $url = $this->_baseUrl . $this->_get_reportloss_skuUrl;
        return $this->request_http($params, $url);
    }

    /**
     * 获取报损界面sku数据
     * 2019-02-01
     * @author Jaxton
     * @parame $purchase_number
     * @return array()
     */
    public function reportloss_submit($params)
    {
        $header = ['Content-Type: application/json'];
        $url    = $this->_baseUrl . $this->_reportloss_submitUrl.'?uid='.$params['uid'];
        $result = getCurlData($url,json_encode($params),'post',$header);
        $result = json_decode($result,true);
        return $result;

        $url = $this->_baseUrl . $this->_reportloss_submitUrl;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 入库日志
     * 2019-02-19
     * @author Jaxton
     * @parame $params
     * @return array()
     */
    public function get_storage_record($params)
    {
        $url = $this->_baseUrl . '/get_storage_record';
        return $this->request_http($params, $url, 'GET', false);
    }


    /**
     * 获取交易订单的物流跟踪信息
     * @author Jolon
     * @parame $params
     */
    public function get_logistics_trace_info($params)
    {
        $url = $this->_baseUrl . $this->_getLogisticsTraceInfo;
        return $this->request_http($params, $url, 'GET', false);
    }

    //获取自定义列表表头
    public function get_set_table_header_info($params)
    {
        $url = $this->_baseUrl . $this->_getSetTableHeaderApi;
        $result = $this->httpRequest($url, $params, 'POST');

        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:" . json_encode($result, 256),
                -1
            );
        }
    }

    //保存自定义列表表头
    public function save_table_list_info($params)
    {
        $url = $this->_baseUrl . $this->_saveTableListApi;
        $result = $this->httpRequest($url, $params, 'POST');

        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:" . json_encode($result, 256),
                -1
            );
        }
    }

    //获取采购单列表页各种备注
    public function get_note_list_info($params)
    {
        $url = $this->_baseUrl . $this->_getNoteListApi;
        $result = $this->httpRequest($url, $params, 'POST');

        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:" . json_encode($result, 256),
                -1
            );
        }
    }

    //手动刷新1688订单是否异常
    public function fresh_ali_order_abnormal($params)
    {
        $url = $this->_baseUrl . $this->_freshAliOrderAbnormalApi;
        $result = $this->httpRequest($url, $params, 'POST');

        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:" . json_encode($result, 256),
                -1
            );
        }
    }

    /**
     * 批量审核信息修改待审核状态改采购单--显示
     * @author harvin
     * @date 2019-5-13
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function batch_audit_data_change_order($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_batchAuditDataChangeOrderUrl . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');

    }
    /**
     * 批量审核信息修改待审核状态改采购单--保存
     * @author harvin
     * @date 2019-5-13
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
    public function batch_audit_data_change_save($post)
    {
        $url = $this->_baseUrl . $this->_batchAuditDataChangeSaveUrl;
        $result = $this->_curlWriteHandleApi($url, $post, 'POST');
        $result['status'] = 1;
        $result['errorMess'] = $this->_errorMsg;
        return $result;
    }

    /**
     * 采购单信息修改
     * @author yefanli
     * @throws Exception
     * @return mixed
     */
    public function get_change_order_preview($param){
        //调用服务层api
        $url = $this->_baseUrl . $this->_getChangeOrderPreview. "?" . http_build_query($param);
        return $this->_curlReadHandleApi($url, "", 'GET');

    }

    /**
     * 保存采购单信息修改
     * @author yefanli
     * @param array $param
     * @return mixed
     * @throws Exception
     */
    public function save_change_order_preview($param){
        //调用服务层api
        $url = $this->_baseUrl . $this->_saveChangeOrderPreview;
        $result = $this->_curlReadHandleApi($url, $param, 'POST');
        $result['status'] = 1;
        $result['errorMess'] = $this->_errorMsg;
        return $result;

    }

    /**
     * 批量审核信息修改待审核状态改采购单--显示
     * @author harvin
     * @date 2019-5-13
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_change_order_data_preview($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_changeOrderDataPreviewUrl . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');

    }
    /**
     * 批量审核信息修改待审核状态改采购单--保存
     * @author harvin
     * @date 2019-5-13
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
    public function change_order_data_save($post)
    {
        $url = $this->_baseUrl . $this->_changeOrderDataSaveUrl;
        $result = $this->_curlWriteHandleApi($url, $post, 'POST');
        $result['status'] = 1;
        $result['errorMess'] = $this->_errorMsg;
        return $result;
    }

    /**
     * 采购单作废
     * @author liwuxue
     * @date 2019/1/30 10:47
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function cancel_order_by_demand_number($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_cancelOrderByDemandNumberApi;
        return $this->_postWriteApi($url, $post);
    }

    /**
     *1688下单订单信息修改-预览
     * @param $get
     * @return array|mixed
     * @throws Exception
     */
    public function get_edit_order_data_preview($purchase){
        $url = $this->_baseUrl . $this->_editAliOrderDataPreviewUrl . "?" . http_build_query($purchase);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 1688下单订单信息修改-保存
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function get_edit_ali_order_data_save($post){
        $url = $this->_baseUrl . $this->_editAliOrderDataSaveUrl;
        $result = $this->_curlWriteHandleApi($url, $post, 'POST');
        $result['status'] = 1;
        $result['errorMess'] = $this->_errorMsg;
        return $result;
    }

    /**
     * 下载送货单
     * @author 叶凡立  20200730
     * @params  $params
     * @return  mixed
     */
    public function download_purchase_delivery_note($params)
    {
        $url = $this->_baseUrl.$this->_downloadPurchaseDeliveryNote;
        return $this->request_http($params,$url,'GET',false);
    }

    /**
     * 下载采购单PDF
     * @param $params
     * @return array
     * @author Jaxton 2019/02/26
     */
    public function download_purchase_order($params){

        $url=$this->_baseUrl . $this->_downloadPurchaseOrderUrl;
        return $this->request_http($params,$url,'GET',false);
        //return $this->_curlReadHandleApi($url, $params, 'POST');



    }

    /**
     * 获取SKU 信息
     * @param:  $params      string    查询的SKU
     * @return  array
     * @author: luxu
     */
    public function get_sku_message( $params )
    {
//        $url = $this->_baseUrl . $this->_get_sku_message;
        $url = constant('CG_API_HOST_' . static::MODULE_NAME)."/stock/get_sku_message";
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }

    /**
     * 添加备注
     * @author Manson
     */
    public function batch_add_remark($post)
    {
        $url = $this->_baseUrl . $this->_batch_add_remark;
        $result = $this->_curlWriteHandleApi($url, $post, 'POST');
        $result['status'] = 1;
        $result['errorMess'] = $this->_errorMsg;
        return $result;
    }


    /**
     * 计算采购单的参考运费
     * @author Jolon
     * @param array  $params
     * @return  array
     * @throws Exception
     */
    public function get_calculate_order_freight( $params )
    {
        $url = $this->_baseUrl . $this->_getCalculateOrderFreight;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }

    public function audit_arrive_time_status( $params )
    {
        $url = $this->_baseUrl . $this->_getAuditArriveStatus;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        $result['errorMess'] = $this->_errorMsg;
        return $result;
    }

    public function audit_arrive_time_log( $params )
    {
        $url = $this->_baseUrl . $this->_getAuditArriveLog;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        $result['errorMess'] = $this->_errorMsg;
        return $result;
    }

    public function audit_arrive_info($params){
        $url = $this->_baseUrl . $this->_getAuditInfo;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        $result['errorMess'] = $this->_errorMsg;
        return $result;
    }
    /**
     * 采购单预览功能接口
     * @author:luxu
     * @time:2020/年6月3号
     **/
    public function getViewData($params){

        $url=$this->_baseUrl . $this->_getViewData;
        return $this->request_http($params,$url,'GET',false);
    }

    /**
     * 采购单预览功能接口(下载)
     * @author:luxu
     * @time:2020/年6月3号
     **/

    public function downViewData($params){

        $url=$this->_baseUrl . $this->_getViewData;
        $result =  $this->request_http($params,$url,'GET',false);
        $keys = ['采购单号','图片','sku','供应商名称','联系方式','产品名称','采购仓库','采购数量','是否退税','含税单价','未税单价'
        ,'采购金额','包装类型','要求降价','是否包邮','最新账期','最新单价','预计交货时间','开票品名','开票单位','开票点',
            '运费','优惠金额'];
        $data = $result['data_list']; //返回数
        $result_array = [];
        $i=0;
        if(!empty($data)) {
            foreach ($data as $key => $v_value) {

                foreach ($v_value['items'] as $items_key => $items_value) {
                    $v_value_tmp = [];
                    $v_value_tmp['purchase_number'] = isset($items_value['purchase_number'])?$items_value['purchase_number']:'';
                    $v_value_tmp['product_img'] = $items_value['image_url'];
                    $v_value_tmp['sku'] = $items_value['sku'];
                    $v_value_tmp['supplier_name'] = $v_value['supplier_name'];
                    $v_value_tmp['contact_number'] = $v_value['contact_number'];
                    $v_value_tmp['product_name'] = $items_value['product_name'];
                    $v_value_tmp['warehouse_name'] = $items_value['warehouse_name'];
                    $v_value_tmp['confirm_amount'] = $items_value['confirm_amount'];
                    $v_value_tmp['is_drawback_ch'] = $items_value['is_drawback_ch'];
                    $v_value_tmp['unit_price'] = $items_value['unit_price'];
                    $v_value_tmp['base_price'] = $items_value['base_price'];
                    $v_value_tmp['total_price'] = $items_value['total_price'];
                    $v_value_tmp['sample_packaging_type'] = $items_value['sample_packaging_type'];
                    $v_value_tmp['is_down_ch'] = $items_value['is_down_ch'];
                    $v_value_tmp['is_postage_ch'] = $items_value['is_postage_ch'];
                    $v_value_tmp['accounting'] = $items_value['accounting'];
                    $v_value_tmp['newprice'] = $items_value['newprice'];
                    $v_value_tmp['es_shipment_time'] = $items_value['es_shipment_time'];
                    $v_value_tmp['export_cname'] = $items_value['export_cname'];
                    $v_value_tmp['declare_unit'] = $items_value['declare_unit'];
                    $v_value_tmp['ticketed_point'] = $items_value['ticketed_point'];
                    $v_value_tmp['is_freight'] = $items_value['is_freight'];
                    $v_value_tmp['discount'] = $items_value['discount'];

                    $result_array[] = $v_value_tmp;
                }
            }
        }
        $filename = '采购单预览数据.xls';
        export_excel($keys, $result_array, $filename,array('图片'),array('product_img'));

    }

    /**
     * 获取PO 回货日志数据
     * 数据中心接口文档：http://dp.yibai-it.com:33344/web/#/118?page_id=15758
     * @author:luxu
     * @time:2020/7/3
     **/
    public function getShipmentsQty($params){

        $url=$this->_baseUrl . $this->_getShipmentsQty;
        return $this->request_http($params,$url,'GET',false);
    }

    /**
     * 应付款单列表页
     * @author yefanli
     * @date 20200807
     * @param array $get
     * @return mixed|array
     */
    public function ali_order_refresh_purchase_SDP($get){
        $url = $this->_baseUrl.$this->_aliOrderRefreshPurchaseSDP;
        return $this->request_http($get,$url,'GET',false);
    }

    /**
     * 获取多货调拨
     * @author 叶凡立
     * @time  20201014
     */
    public function get_order_sku_allocation_info($get)
    {
        $url = $this->_baseUrl.$this->_getOrderSkuAllocationInfo;
        return $this->request_http($get,$url,'GET',false);
    }

    /**
     * 保存多货调拨数据
     * @author 叶凡立
     * @time  20201014
     */
    public function save_order_sku_allocation($get)
    {
        $url = $this->_baseUrl.$this->_getSaveOrderSkuAllocation;
        return $this->request_http($get,$url,'GET',false);
    }

    /**
     * 采购单催发货
     * @author 叶凡立
     * @time  20201123
     */
    public function urge_send_order($get)
    {
        $url = $this->_baseUrl.$this->_getUrgeSendOrder;
        return $this->request_http($get,$url,'GET',false);
    }

    /**
     * 采购单催改价
     * @author 叶凡立
     * @time  20201123
     */
    public function urge_change_order_price($get)
    {
        $url = $this->_baseUrl.$this->_getUrgeChangeOrderPrice;
        return $this->request_http($get,$url,'GET',false);
    }

    /**
     * 获取虚拟入库
     * @author 叶凡立
     * @time  20201123
     */
    public function get_imitate_purchase_instock($get)
    {
        $url = $this->_baseUrl.$this->_getImitatePurchaseInstock;
        return $this->request_http($get,$url,'GET',false);
    }

    /**
     * 保存虚拟入库
     * @author 叶凡立
     * @time  20201123
     */
    public function save_imitate_purchase_instock($get)
    {
        $url = $this->_baseUrl.$this->_saveImitatePurchaseInstock;
        return $this->request_http($get,$url,'GET',false);
    }
}
