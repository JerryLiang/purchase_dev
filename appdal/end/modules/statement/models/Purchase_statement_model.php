<?php
/**
 * 对账单数据主表操作模型
 * User: Jolon
 * Date: 2020/04/14 10:00
 */

class Purchase_statement_model extends Purchase_model {

    protected $table_name       = 'purchase_statement';
    protected $table_name_items = 'purchase_statement_items';
    protected $table_name_summary = 'purchase_statement_summary';
    protected $table_statement_note = 'statement_note';//日志表

    /**
     * Purchase_statement_model constructor.
     */
    public function __construct(){
        parent::__construct();
        $this->load->helper('status_finance');
        $this->load->helper('status_supplier');
        $this->load->model('statement/Charge_against_surplus_model');
        $this->load->model('statement/Charge_against_records_model');
        $this->load->model('statement/Purchase_statement_note_model');

    }

    /**
     * 获取对账单信息
     * @param      $statement_number
     * @param bool $append
     * @return array
     */
    public function get_statement($statement_number,$append = true){

        $statement = $this->purchase_db->where('statement_number',$statement_number)
            ->get($this->table_name)
            ->row_array();

        if($statement and $append){
            // 对账单入库明细
            $statement['items_list'] = $this->purchase_db->where('statement_number',$statement_number)
                ->order_by('id asc')
                ->get($this->table_name_items)
                ->result_array();

            $this->load->model('purchase/Purchase_order_determine_model');
            $purchase_number_list = array_unique(array_column($statement['items_list'],'purchase_number'));
            $sku_list             = array_unique(array_column($statement['items_list'],'sku'));
            $order_cancel_list    = $this->Purchase_order_determine_model->get_order_cancel_list($purchase_number_list,$sku_list);//获取取消数量集合

            if($statement['items_list']){
                $pay_list = $this->purchase_db->from("purchase_order_pay_detail as a")
                    ->join("pur_purchase_order_pay as b", "a.requisition_number=b.requisition_number", "inner")
                    ->select("b.payer_time,a.purchase_number,a.sku")
                    ->where_in("a.purchase_number", $purchase_number_list)
                    ->where_in("a.sku", $sku_list)
                    ->get()
                    ->result_array();
                foreach($statement['items_list'] as &$item_value){
                    $combine_key = $item_value['purchase_number'].'-'.$item_value['sku'];
                    $item_value['supplier_code'] = $statement['supplier_code'];
                    $item_value['supplier_name'] = $statement['supplier_name'];
                    $item_value['product_img_url'] = erp_sku_img_sku($item_value['product_img_url']);

                    $item_value['payer_time'] = '';
                    foreach($pay_list as $van_pay){
                        if($item_value['purchase_number'] == $van_pay['purchase_number'] && $item_value['sku'] == $van_pay['sku']){
                            $item_value['payer_time'] = $van_pay['payer_time'];
                        }
                    }

                    $order_cancel_qty = isset($order_cancel_list[$combine_key])?$order_cancel_list[$combine_key]:0;
                    $item_value['real_confirm_amount'] = $item_value['confirm_amount'] - $order_cancel_qty;
                }
            }

            // 对账单对账汇总
            $s_list = $this->purchase_db->where('statement_number',$statement_number)
                ->get($this->table_name_summary)
                ->result_array();
            $summary_list = [];
            if($s_list && count($s_list) > 0){
                $sku_weight = $sku_instock_weight = 0;
                foreach ($s_list as $s_val){
                    // 116616
                    $p_n_info = $this->purchase_db->from('purchase_order_items as oi')
                        ->select("o.account_type,oi.purchase_number,oi.sku,p.product_weight,oi.upselft_amount,oi.confirm_amount")
                        ->join('purchase_order as o', "o.purchase_number=oi.purchase_number", "left")
                        ->join('product as p', 'oi.sku=p.sku', 'left')
                        ->where(["oi.purchase_number" => $s_val['purchase_number']])
                        ->get()
                        ->result_array();

                    // 对账单中PO的入库重量
                    $po_instock_weight = $this->purchase_db->select("SUM(p.product_weight * swr.instock_qty) as instock_weight")
                        ->from('statement_warehouse_results as swr')
                        ->join('product as p', 'swr.sku=p.sku', 'left')
                        ->where('swr.purchase_number',$s_val['purchase_number'])
                        ->where('swr.statement_number',$statement_number)
                        ->get()
                        ->row_array();

                    if($p_n_info && count($p_n_info) > 0){
                        foreach ($p_n_info as $pv){
                            if(!empty($pv['account_type']) && in_array($pv['account_type'], [33,10,11,12,13,14,15,16,17,18,19,21,22,23,24,25,26,27,28,29,30,31,32,39,40])){
                                $sku_weight += $pv['confirm_amount'] * $pv['product_weight'];
                            }else{
                                $sku_weight += $pv['upselft_amount'] * $pv['product_weight'];
                            }

                            //$sku_instock_weight += $pv['upselft_amount'] * $pv['product_weight'];// 入库数量总重量
                        }
                    }
                    $sku_weight = $sku_weight / 1000;
                    $s_val['weight'] = format_price($sku_weight);
                    if($po_instock_weight){
                        $s_val['instock_weight'] = format_two_point_price($po_instock_weight['instock_weight'] / 1000);
                    }else{
                        $s_val['instock_weight'] = 0;
                    }
                    $summary_list[] =  $s_val;
                }
            }

            $statement['summary_list'] = $summary_list;
        }
        if($statement){
            // 数据转换成中文
            $this->load->model('supplier/supplier_settlement_model');
            $supplierSettlementInfo            = $this->supplier_settlement_model->get_settlement_one($statement['settlement_method']);
            $statement['settlement_method_cn'] = isset($supplierSettlementInfo['settlement_name']) ? $supplierSettlementInfo['settlement_name'] : '';
            $statement['pay_type_cn']          = getPayType($statement['pay_type']);
            $company_info                      = compactCompanyInfo($statement['purchase_name']);
            $statement['purchase_name_cn']     = $company_info['name'];
            $statement['purchase_name_address']= $company_info['address'];
            $statement['is_drawback_cn']       = getIsDrawback($statement['is_drawback']);
        }

        return $statement;
    }



    /**
     * 获取对账单&e签宝签署信息
     * @param      $flowId
     * @return array
     */
    public function get_statement_by_flowId($flowId){

        $statement = $this->purchase_db
            ->select('PS.statement_number,PS.firstSignParty,PS.status,PS.purchase_name,PS.flowId,PS.fileId,PS.attachmentFileId,EF.aUsername,EF.aMobile,EF.bUsername,EF.bMobile')
            ->from($this->table_name.' AS PS')
            ->join('pur_esign_flows AS EF','PS.flowId=EF.flowId','LEFT')
            ->where('PS.flowId',$flowId)
            ->get()
            ->row_array();

        return $statement;
    }


    /**
     * 获取对账单 - 采购单相关的信息 -采购单明细&冲销汇总
     * @param $purchase_numbers
     * @return array
     */
    public function get_statement_append_info($purchase_numbers){
        $this->load->model('compact/Compact_model');

        $appendInfo = [];
        $purOrderChargeAgainstDetails = [];
        $purPayDataDetails            = [];

        $total_real_price = $total_instock_price = $total_paid_real_price = $total_cancel_real_price = $total_cancel_real_price = 0;
        $total_loss_real_price = $total_ip_after_ca = $total_rp_after_ca = 0;

        $purOrderCADetailList = $this->get_summary_data_row($purchase_numbers);// 批量查询所有采购单
        $purPayDataList = $this->get_compact_pay_data_details($purchase_numbers);// 批量查询所有采购单

        foreach($purchase_numbers as $purchase_number){
            $purOrderCADetail = isset($purOrderCADetailList[$purchase_number])?$purOrderCADetailList[$purchase_number]:[];
            $purPayData = isset($purPayDataList[$purchase_number])?$purPayDataList[$purchase_number]:[];
            $purOrderChargeAgainstDetails[] = $purOrderCADetail;

            $total_real_price        += isset($purOrderCADetail['real_price']) ? $purOrderCADetail['real_price'] : 0;
            $total_instock_price     += isset($purOrderCADetail['total_instock_price']) ? $purOrderCADetail['total_instock_price'] : 0;
            $total_paid_real_price   += isset($purOrderCADetail['paid_real_price']) ? $purOrderCADetail['paid_real_price'] : 0;
            $total_cancel_real_price += isset($purOrderCADetail['cancel_real_price']) ? $purOrderCADetail['cancel_real_price'] : 0;
            $total_loss_real_price   += isset($purOrderCADetail['loss_real_price']) ? $purOrderCADetail['loss_real_price'] : 0;
            $total_ip_after_ca       += isset($purOrderCADetail['instock_price_after_charge_against']) ? $purOrderCADetail['instock_price_after_charge_against'] : 0;
            $total_rp_after_ca       += isset($purOrderCADetail['real_price_after_charge_against']) ? $purOrderCADetail['real_price_after_charge_against'] : 0;

            //$purPayData = $this->Compact_model->get_compact_pay_data_details($purchase_number);
            $purPayDataDetails += $purPayData;
        }

        $appendInfo['purOrder_details']                = $purPayDataDetails;// 采购单明细
        $appendInfo['purOrder_charge_against_details'] = $purOrderChargeAgainstDetails;// 采购单冲销汇总

        $appendInfo['purOrder_charge_against_total'] = [

            'total_real_price'        => format_price($total_real_price),
            'total_instock_price'     => format_price($total_instock_price),
            'total_paid_real_price'   => format_price($total_paid_real_price),
            'total_cancel_real_price' => format_price($total_cancel_real_price),
            'total_loss_real_price'   => format_price($total_loss_real_price),
            'total_ip_after_ca'       => format_price($total_ip_after_ca),
            'total_rp_after_ca'       => format_price($total_rp_after_ca),
        ];

        return $appendInfo;
    }

    /**
     * 根据采购单号获取记录
     * @param $purchase_numbers
     * @return array
     */
    public function get_summary_data_row($purchase_numbers)
    {
        $resultList = $this->purchase_db->select('a.*,b.purchase_order_status,b.pay_status,b.supplier_name,b.supplier_code,b.buyer_name,b.buyer_id,b.waiting_time,c.real_price,
                c.product_money,c.freight,c.process_cost,c.discount,c.commission')
            ->from("purchase_order_charge_against_surplus a")
            ->join("purchase_order b", 'b.purchase_number=a.purchase_number')
            ->join("purchase_order_pay_type c", 'c.purchase_number=a.purchase_number')
            ->where_in('a.purchase_number', $purchase_numbers)
            ->get()
            ->result_array();
        $resultList = arrayKeyToColumn($resultList,'purchase_number');
        if (empty($resultList)) {
            return [];
        }

        //<editor-fold desc="数据转换">

        //获取取消未到货状态
        $cancel_info_list = $this->Charge_against_surplus_model->get_unfinish_cancel_info($purchase_numbers);
        //获取报损状态
        $report_loss_info_list = $this->Charge_against_surplus_model->_get_report_loss_status($purchase_numbers);

        $resultListTmp = [];
        foreach ($purchase_numbers as $value){
            $result = isset($resultList[$value])?$resultList[$value]:[];
            $cancel_info = isset($cancel_info_list[$value])?$cancel_info_list[$value]:[];
            $report_loss_info = isset($report_loss_info_list[$value])?$report_loss_info_list[$value]:[];

            //业务线
            $result['purchase_type_cn'] = !is_null($result['purchase_type_id']) ? getPurchaseType($result['purchase_type_id']) : '';
            //采购单状态
            $result['purchase_order_status_cn'] = !is_null($result['purchase_order_status']) ? getPurchaseStatus($result['purchase_order_status']) : '';
            //取消未到货状态
            $result['cancel_status'] = isset($cancel_info[$result['purchase_number']]['audit_status']) ? $cancel_info[$result['purchase_number']]['audit_status'] : '';
            $result['cancel_status_cn'] = '' != $result['cancel_status'] ? get_cancel_status($result['cancel_status']) : '未申请取消';
            //报损状态
            $result['report_loss_status'] = isset($report_loss_info[$result['purchase_number']]) ? $report_loss_info[$result['purchase_number']] : '';
            $result['report_loss_status_cn'] = '' != $result['report_loss_status'] ? getReportlossApprovalStatus($result['report_loss_status']) : '未申请报损';
            //采购单付款状态
            $result['pay_status_cn'] = !is_null($result['pay_status']) ? getPayStatus($result['pay_status']) : '';
            //是否完结状态
            $result['finished_cn'] = !is_null($result['finished']) ? getChargeAgainstFinishStatus($result['finished']) : '';
            //采购来源
            $result['source_cn'] = (1 == $result['source']) ? '合同单' : '网采单';

            $resultListTmp[$value] = $result;
        }

        return $resultListTmp;
    }

    /**
     * 获取合同里面采购单的相关数据
     * @param $purchase_numbers
     * @return array
     */
    public function get_compact_pay_data_details($purchase_numbers){
        if(!isset($this->Purchase_order_determine_model)){
            $this->load->model('purchase/Purchase_order_determine_model');
        }
        if(!isset($this->purchase_order_model)){
            $this->load->model('purchase/purchase_order_model');
        }
        if(!isset($this->Purchase_suggest_map_model)){
            $this->load->model('purchase_suggest/Purchase_suggest_map_model');
        }
        if(!isset($this->purchase_order_pay_model)){
            $this->load->model('finance/purchase_order_pay_model');
        }

        // 采购单主表信息
        $purchase_order_info_list = $this->purchase_db
            ->where_in('purchase_number',$purchase_numbers)
            ->get('purchase_order')
            ->result_array();
        $purchase_order_info_list = arrayKeyToColumn($purchase_order_info_list,'purchase_number');

        // 采购单明细表信息
        $purchase_order_items_info_list = $this->purchase_db
            ->where_in('purchase_number',$purchase_numbers)
            ->get('purchase_order_items')
            ->result_array();
        $sku_list = array_column($purchase_order_items_info_list,'sku');
        $purchase_order_items_info_list = arrayKeyToColumnMulti($purchase_order_items_info_list,'purchase_number','sku');

        // 批量查询相关数据
        $demand_list_info_list = $this->purchase_db->select('purchase_number,sku,demand_number')
            ->where_in('purchase_number',$purchase_numbers)
            ->get('purchase_suggest_map')
            ->result_array();
        $demand_list_info_list = arrayKeyToColumnMulti($demand_list_info_list,'purchase_number','sku');

        // SKU 已付款总金额(pay_total 采购单 SKU 已付款金额)
        $paidInfoList = $this->purchase_db
            ->select('poy.requisition_number,poyd.purchase_number,sku,
                sum(poyd.product_money) as product_money,
                sum(poyd.pay_total) as pay_total'
            )
            ->from('purchase_order_pay as poy')
            ->join('purchase_order_pay_detail as poyd', 'poy.requisition_number=poyd.requisition_number', 'inner')
            ->where('poy.pay_status', PAY_PAID)// 付款状态 51.已付款
            ->where_in('poyd.purchase_number', $purchase_numbers)
            ->group_by('poyd.purchase_number,poyd.sku')
            ->get()->result_array();
        $paidInfoList = arrayKeyToColumnMulti($paidInfoList,'purchase_number','sku');

        // 取消数量（取消记录表中读取汇总数据）
        $cancel_info_list = $this->purchase_db->select('purchase_number,sku,sum(pocd.cancel_ctq) as cancel_ctq')
            ->from('purchase_order_cancel as poc')
            ->join('purchase_order_cancel_detail as pocd', 'poc.id=pocd.cancel_id', 'inner')
            ->where_in('pocd.purchase_number', $purchase_numbers)
            ->where_in('poc.audit_status', [CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC])
            ->group_by('pocd.purchase_number,pocd.sku')
            ->get()
            ->result_array();
        $cancel_info_list = arrayKeyToColumnMulti($cancel_info_list,'purchase_number','sku');

        //报损数量
        $loss_info_list = $this->purchase_db
            ->select('pur_number,sku,loss_amount')
            ->from('purchase_order_reportloss')
            ->where_in('pur_number',$purchase_numbers)
            ->where('status', REPORT_LOSS_STATUS_FINANCE_PASS)
            ->group_by('pur_number,sku')
            ->get()
            ->result_array();
        $loss_info_list = arrayKeyToColumnMulti($loss_info_list,'purchase_number','sku');

        // 产品重量
        $prod_weight_list = $this->purchase_db->query("SELECT sku,product_weight FROM pur_product where sku in('".implode("','",$sku_list)."')")->result_array();
        $prod_weight_list = array_column($prod_weight_list,'product_weight','sku');


        $compact_item_tmp   = [];
        foreach ($purchase_numbers as $purchase_number){
            $items_list = isset($purchase_order_items_info_list[$purchase_number])?$purchase_order_items_info_list[$purchase_number]:[];

            foreach($items_list as $pur_item){
                $sku = $pur_item['sku'];
                $purchase_order = isset($purchase_order_info_list[$purchase_number])?$purchase_order_info_list[$purchase_number]:[];
                $sku_qty_list = isset($cancel_info_list[$purchase_number][$sku])?$cancel_info_list[$purchase_number][$sku]:[];
                $loss_info = isset($loss_info_list[$purchase_number][$sku])?$loss_info_list[$purchase_number][$sku]:[];
                $demand_info = isset($demand_list_info_list[$purchase_number][$sku])?$demand_list_info_list[$purchase_number][$sku]:'';
                $paid_info = isset($paidInfoList[$purchase_number][$sku])?$paidInfoList[$purchase_number][$sku]:[];

                $item_tmp                        = [];
                $item_tmp['purchase_number']     = $purchase_number;
                $item_tmp['is_drawback']         = $purchase_order['is_drawback'];
                $item_tmp['is_drawback_cn']      = getIsDrawback($purchase_order['is_drawback']);
                $item_tmp['demand_number']       = isset($demand_info['demand_number'])?$demand_info['demand_number']:'';
                $item_tmp['sku']                 = $pur_item['sku'];
                $item_tmp['product_name']        = $pur_item['product_name'];
                $item_tmp['product_img_url']     = $pur_item['product_img_url'];
                $item_tmp['purchase_unit_price'] = $pur_item['purchase_unit_price'];
                $item_tmp['freight']             = $pur_item['freight'];
                $item_tmp['discount']            = $pur_item['discount'];
                $item_tmp['process_cost']        = $pur_item['process_cost'];
                $item_tmp['commission']          = $pur_item['commission'];
                $item_tmp['ca_product_money']    = $pur_item['ca_product_money'];

                $item_tmp['item_paid_total']     = isset($paid_info['pay_total'])?$paid_info['pay_total']:0;// 采购单-SKU 已请款金额
                $item_tmp['item_paid_product_money'] = isset($paid_info['product_money'])?$paid_info['product_money']:0;// 采购单-SKU 已请款商品金额

                // 数量
                $item_tmp['suggest_amount'] = $pur_item['purchase_amount'];
                $item_tmp['order_amount']   = $pur_item['confirm_amount'];
                $item_tmp['cancel_amount']  = isset($sku_qty_list['cancel_ctq']) ? $sku_qty_list['cancel_ctq'] : 0;
                $item_tmp['receive_amount'] = $pur_item['receive_amount'];
                $item_tmp['loss_amount']    = isset($loss_info['loss_amount']) ? $loss_info['loss_amount'] : 0;
                $item_tmp['upselft_amount'] = $pur_item['upselft_amount'];
                $item_tmp['cancel_ctq']     = $item_tmp['cancel_amount'];

                // 剩余可申请商品金额（用于 请款金额的分摊）
                $item_tmp['item_available_product_money'] = format_two_point_price($item_tmp['purchase_unit_price'] * ($item_tmp['order_amount'] - $item_tmp['cancel_amount']) - $item_tmp['item_paid_product_money'] - $item_tmp['ca_product_money']);
                if($item_tmp['item_available_product_money'] < 0) $item_tmp['item_available_product_money'] = 0;// 可申请金额必须大于等于0

                // 116616
                $sku_weight = 0;
                $prod_weight = isset($prod_weight_list[$sku])?$prod_weight_list[$sku]:0;
                if(!empty($purchase_order['account_type']) && in_array($purchase_order['account_type'], [33,10,11,12,13,14,15,16,17,18,19,21,22,23,24,25,26,27,28,29,30,31,32,39,40])){
                    $sku_weight += $item_tmp['order_amount'] * $prod_weight;
                }else{
                    $sku_weight += $item_tmp['upselft_amount'] * $prod_weight;
                }
                $item_tmp['weight'] = $sku_weight;

                $compact_item_tmp[$purchase_number][] = $item_tmp;

            }
        }

        return $compact_item_tmp;
    }


    /**
     * 获取 对账单 所包含的采购单的总入库金额
     * @param $statement_number
     * @return int
     */
    public function get_total_instock_price($statement_number){

        $total_instock_price = "SELECT SUM(total_instock_price) AS total_instock_price
        FROM pur_purchase_order_charge_against_surplus 
        WHERE purchase_number IN(SELECT purchase_number FROM pur_purchase_statement_items WHERE statement_number='{$statement_number}')";

        $total_instock_price = $this->purchase_db->query($total_instock_price)->row_array();

        return isset($total_instock_price['total_instock_price'])?$total_instock_price['total_instock_price']:0;

    }

    /**
     * 生成最新的 对账单号
     * @author Jolon
     * @param int $type 对账单类型
     * @param bool $virtual 虚拟单号（返回如 PO-DZxxxxxx）
     * @param array $purchase_type_ids 业务线列表（多个业务线会组合生成合同号）
     * @return mixed
     */
    public function getStatementNumber($type,$virtual = false,$purchase_type_ids = []){
        if(count(array_unique($purchase_type_ids)) >= 2 ){
            $type = PURCHASE_TYPE_PFB;// PFB
        }

        switch($type){
            case PURCHASE_TYPE_INLAND:// 国内
                $type = 'PO-DZ';
                break;
            case PURCHASE_TYPE_OVERSEA:// 海外
                $type = 'ABD-DZ';
                break;
            case PURCHASE_TYPE_FBA:// FBA
                $type = 'FBA-DZ';
                break;
            case PURCHASE_TYPE_PFB:// PFB
                $type = 'PFB-DZ';
                break;
            case PURCHASE_TYPE_PFH:// PFH
                $type = 'PO-DZ';
                break;
            default:// 默认国内
                $type = 'PO-DZ';
                break;
        }

        if($virtual){
            return $type.'xxxxxx';
        }

        $this->load->model('prefix_number_model');// 数据表前缀
        $new_purchase_number = $this->prefix_number_model->get_prefix_new_number($type);
        return $new_purchase_number;
    }

    /**
     * 验证 入库记录是否可以创建对账单
     *      返回验证通过的数据集合
     * @param array $inventory_item_list 入库明细记录（ Purchase_inventory_items_model::get_inventory_items_for_statement() 的结果集）
     * @return array
     */
    public function check_inventory_item_able_statement($inventory_item_list){
        $return = ['code' => false, 'message' => '', 'error_data' => []];

        $this->load->model('purchase/purchase_order_model');

        $allow_settlement_codes = $this->purchase_db->select('settlement_code')
            ->where_in('parent_id',[35,36])
            ->get('pur_supplier_settlement')
            ->result_array();
        $allow_settlement_codes = array_column($allow_settlement_codes,'settlement_code','settlement_code');

        $error_list = [];
        foreach($inventory_item_list as $key => $inventory_item){
            $instock_batch = $inventory_item['instock_batch'];

            if(intval($inventory_item['instock_qty']) <= 0 or $inventory_item['instock_price'] <= 0){// 入库数量≠0
                $error_list[] = '入库批次号 '.$instock_batch.' 入库数量＝0或入库金额=0，不允许生成对账单';
                unset($inventory_item_list[$key]);
                continue;
            }
            if($inventory_item['pay_status'] == PAY_NONEED_STATUS or intval($inventory_item['charge_against_status']) == 0){// 入库批次号 无需冲销
                $error_list[] = '入库批次号 '.$instock_batch.' 无需冲销，不允许生成对账单';
                unset($inventory_item_list[$key]);
                continue;
            }
            if($inventory_item['created_statement'] == 2){// 合同关联的对账单是否作废/未关联对账单
                $error_list[] = '入库批次号 '.$instock_batch.' 已创建对账单，不允许重复创建，需创建请作废原对账单';
                unset($inventory_item_list[$key]);
                continue;
            }
            if(defined('OAUTH_ACCESS') and $inventory_item['enable_statement'] == 2){// 是否可对账（1.可对账，2不可对账）
                $error_list[] = '入库批次号 '.$instock_batch.' 暂不可对账';
                unset($inventory_item_list[$key]);
                continue;
            }
            if(!empty($inventory_item['statement_number'])){// 合同关联的对账单是否作废/未关联对账单
                $error_list[] = '入库批次号 '.$instock_batch.' 已创建对账单，不允许重复创建，需创建请作废原对账单';
                unset($inventory_item_list[$key]);
                continue;
            }

            if($inventory_item['surplus_charge_against_amount'] <= 0){// 入库批次好***存在剩余可冲销金额
                $error_list[] = '入库批次号 '.$instock_batch.' 剩余可冲销金额为0，无需生成对账单';
                unset($inventory_item_list[$key]);
                continue;
            }

            if($inventory_item['is_isolation'] == 1){// 属于历史隔离数据
                $error_list[] = '入库批次号 '.$instock_batch.' 属于历史隔离数据，只能从采购单的【申请请款】进行请款';
                unset($inventory_item_list[$key]);
                continue;
            }

            if(!in_array($inventory_item['account_type'],$allow_settlement_codes)){
                $error_list[] = '入库批次号 '.$instock_batch.' 结算方式≠线下账期/货到付款不允许进行申请';
                unset($inventory_item_list[$key]);
                continue;
            }

            $purchaseOrderInfo = $this->purchase_order_model->get_one($inventory_item['purchase_number'],false);

            if(($check_res2 = $this->purchase_order_model->check_pay_status_able_change($purchaseOrderInfo['pay_status'])) !== true){
                $error_list[] = '入库批次号 '.$instock_batch.' 请款中，不能创建对账单';
                unset($inventory_item_list[$key]);
                continue;
            }

            if(!in_array(intval($inventory_item['is_abnormal']),[1,3])){// 正常和入库时间异常可以创建
                $error_list[] = '请检查筛选“入库是否异常”都为“正常”的数据进行操作！';
                unset($inventory_item_list[$key]);
                continue;
            }

        }

        $return['code']    = true;
        $return['message'] = '验证通过';
        $return['data_list'] = $inventory_item_list;
        $return['error_data'] = $error_list;

        return $return;
    }


    /**
     * 生成对账单数据分组（参数是大量不同类型的数据，需要按照条件生成不同的对账单）
     * @param $inventory_item_list
     * @return array
     */
    public function group_inventory_item_is_same_statement($inventory_item_list){
        $return = ['code' => false, 'message' => '', 'error_data' => []];

        $list = [];
        foreach($inventory_item_list as $key => $item){
            if(in_array($item['purchase_type_id'],[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH])){
                $purchase_type_id = PURCHASE_TYPE_INLAND;
            }else{
                $purchase_type_id = PURCHASE_TYPE_OVERSEA;
            }

            // 按组合条件分组
            $key = implode('_',[
                $item['is_drawback'],
                $item['supplier_code'],
                $item['purchase_name'],
                $item['pay_type'],
                $item['account_type'],
                $item['is_purchasing'],
                $item['instock_month'],
                $purchase_type_id
            ]);
            $list[$key][] = $item;
        }

        unset($inventory_item_list);

        // 验证成功
        $return['code'] = true;
        $return['data_list'] = $list;
        return $return;
    }


    /**
     * 验证采购单 是否可以生成同一个对账单
     * @param array $inventory_item_list 入库明细记录（ Purchase_inventory_items_model::get_inventory_items_for_statement() 的结果集）
     * @return array
     */
    public function check_inventory_item_is_same_statement($inventory_item_list){
        $return = ['code' => false, 'message' => '', 'error_data' => []];

        // 验证 是否退税、供应商、采购主体、结算方式、支付方式 是否一致
        $check_is_drawback   = array_unique(array_column($inventory_item_list, 'is_drawback')); // 是否退税
        $check_supplier_code = array_unique(array_column($inventory_item_list, 'supplier_code')); // 供应商
        $check_purchase_name = array_unique(array_column($inventory_item_list, 'purchase_name')); // 采购主体
        $check_pay_type      = array_unique(array_column($inventory_item_list, 'pay_type')); // 支付方式
        $check_account_type  = array_unique(array_column($inventory_item_list, 'account_type')); // 结算方式
        $is_purchasing       = array_unique(array_column($inventory_item_list, 'is_purchasing')); // 是否代采
        $instock_month       = array_unique(array_column($inventory_item_list, 'instock_month')); // 入库月份
        $purchase_type_ids   = array_unique(array_column($inventory_item_list, 'purchase_type_id')); // 业务线

        $error_message = [];
        if(count($check_is_drawback) != 1)
            $error_message[] = '是否退税';
        if(count($check_supplier_code) != 1)
            $error_message[] = '供应商';
        if(count($check_purchase_name) != 1)
            $error_message[] = '采购主体';
        if(count($check_pay_type) != 1)
            $error_message[] = '支付方式';
        if(count($check_account_type) != 1)
            $error_message[] = '结算方式';
        if(count($is_purchasing) != 1)
            $error_message[] = '是否代采不一致';
        if(count($instock_month) != 1)
            $error_message[] = '入库月份不一致';

        if(array_diff($purchase_type_ids,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH])
            and array_diff($purchase_type_ids,[PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])){
            $error_message[] = '业务线[国内/FBA/PFB/平台头程]和[海外/FBA大货]不能生成到同一个对账单';
        }

        if(!empty($error_message)){// 存在错误
            $return['error_data'][] = implode('、', $error_message)." 必须一致";
            $return['message']    = '验证不通过';
            return $return;
        }else{
            // 验证成功
            $return['code'] = true;
            $return['message'] = '验证通过';
            return $return;
        }
    }

    /**
     * 验证入库明细记录里面是否存在可以 冲销的采购单号
     * @param array $inventory_item_list 入库明细记录（ Purchase_inventory_items_model::get_inventory_items_for_statement() 的结果集）
     * @return array
     */
    public function check_inventory_item_able_charge_against($inventory_item_list){
        function remove_not_surplus($val_surplus){
            return $val_surplus > 0 ? true : false;
        }

        $purchase_numbers   = array_column($inventory_item_list, 'purchase_number');
        $order_surplus_list = $this->Charge_against_surplus_model->get_surplus($purchase_numbers);
//        print_r($order_surplus_list);exit;
        $order_surplus_list = array_column($order_surplus_list, 'surplus_able_charge_against_money', 'purchase_number');
        $order_surplus_list = array_filter($order_surplus_list, "remove_not_surplus");

//        print_r($order_surplus_list);exit;
        $inventory_item_surplus_list = [];
        foreach($inventory_item_list as $inventory_item){
            $purchase_number = $inventory_item['purchase_number'];

            if($inventory_item['surplus_charge_against_amount'] <= 0)
                continue;// 入库明细记录无剩余可冲销金额
            if(!isset($order_surplus_list[$purchase_number]) or $order_surplus_list[$purchase_number] <= 0)
                continue;// 采购单无剩余可冲销金额

            // 入库单剩余可冲销金额总计
            if(isset($inventory_item_surplus_charge_against_amount[$inventory_item['purchase_number']])){
                $inventory_item_surplus_list[$inventory_item['purchase_number']]['surplus_amount'] += $inventory_item['surplus_charge_against_amount'];
            }else{
                $inventory_item_surplus_list[$inventory_item['purchase_number']]['surplus_amount'] = $inventory_item['surplus_charge_against_amount'];
            }

            $inventory_item_surplus_list[$inventory_item['purchase_number']]['surplus_amount'] = format_price($inventory_item_surplus_list[$inventory_item['purchase_number']]['surplus_amount']);

            // 可冲销入库批次号 => 剩余可冲销金额
            $inventory_item_surplus_list[$inventory_item['purchase_number']]['instock_batch'][$inventory_item['instock_batch']] = $inventory_item['surplus_charge_against_amount'];
            // 采购单剩余可冲销金额
            $inventory_item_surplus_list[$inventory_item['purchase_number']]['surplus_amount'] = $order_surplus_list[$purchase_number];
        }
//        print_r($inventory_item_surplus_list);exit;

        return $inventory_item_surplus_list;
    }


    /**
     * 验证 满足指定条件的同时，又满足可以生成对账单条件的数据 的总数
     *      用来比对业务是否选择所有满足条件的入库记录生成对账单
     * @author Jolon
     * @param string    $supplier_code      供应商
     * @param int       $purchase_type_id   业务线
     * @param string    $instock_month      入库月份
     * @param int       $is_drawback        是否退税
     * @param int       $account_type       结算方式
     * @param int       $is_purchasing      是否代采
     * @param string    $purchase_name      采购主体
     * @param int       $pay_type           支付方式
     * @return int 满足条件的数据总数
     */
    public function get_able_create_statement_inventory_item($supplier_code,$purchase_type_id,$instock_month,$is_drawback,$account_type,$is_purchasing,$purchase_name,$pay_type){

        // 转换业务线
        // 转换规则同 self::group_inventory_item_is_same_statement() 方法
        if($purchase_type_id == PURCHASE_TYPE_INLAND){
            $purchase_type_id_arr = [PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH];
        }else{
            $purchase_type_id_arr = [PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG];
        }
        $purchase_type_id_str = implode(',',$purchase_type_id_arr);

        // 原生SQL查询
        $querySql = "SELECT COUNT(1) AS sumTotal
            FROM pur_statement_warehouse_results AS WR
            LEFT JOIN pur_purchase_order AS PO ON PO.purchase_number=WR.purchase_number
            LEFT JOIN pur_purchase_order_items AS POI ON POI.purchase_number=WR.purchase_number AND POI.sku=WR.sku
            LEFT JOIN pur_purchase_suggest AS PS ON PS.demand_number=POI.demand_number
            LEFT JOIN pur_purchase_order_charge_against_surplus AS SUR ON SUR.purchase_number=PO.purchase_number
            WHERE 1=1";

        // 筛选条件：self::group_inventory_item_is_same_statement() 方法分组的参数
        $querySql .= " AND PO.supplier_code='{$supplier_code}'";
        $querySql .= " AND PO.purchase_type_id IN({$purchase_type_id_str})";
        $querySql .= " AND LEFT(WR.instock_date, 7)='{$instock_month}'";
        $querySql .= " AND PO.is_drawback=".intval($is_drawback);
        $querySql .= " AND PO.account_type=".intval($account_type);
        $querySql .= " AND WR.is_purchasing=".intval($is_purchasing);
        $querySql .= " AND PO.purchase_name='{$purchase_name}'";
        $querySql .= " AND PO.pay_type=".intval($pay_type);


        // 筛选条件：self::check_inventory_item_able_statement() 方法验证 入库记录是否可以创建对账单
        $querySql .= " AND WR.instock_qty>0 AND WR.instock_price>0";// 入库数量和金额
        $querySql .= " AND PO.pay_status <> ".PAY_NONEED_STATUS." AND WR.charge_against_status>0";// 付款状态+冲销状态
        $querySql .= " AND WR.created_statement=1";// 是否已创建对账单=否

        if(defined('OAUTH_ACCESS')){// 是否可对账（1.可对账，2不可对账）
            $querySql .= " AND WR.enable_statement=1";// 是否可对账（1.可对账，2不可对账）=可对账
        }
        $querySql .= " AND WR.statement_number=''";// 是否有对账单号=否
        $querySql .= " AND WR.surplus_charge_against_amount>0";// 剩余可冲销金额不为0
        $querySql .= " AND WR.is_isolation=2";// 是否属于历史隔离数据（1.是,2.否）=否
        $querySql .= " AND WR.is_abnormal IN(1,3)";// 1.正常 和 3.入库时间异常可以创建

        // Purchase_order_model::check_pay_status_able_change() 方法限定的付款状态
        $pay_status_str = implode(',',[
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
        ]);
        $querySql .= " AND PO.pay_status IN({$pay_status_str})";


        $querySqlRes = $this->purchase_db->query($querySql)->row_array();

        return $querySqlRes?$querySqlRes['sumTotal']:0;
    }

    /**
     * 入库批次 创建对账单预览数据（第一步）
     * @param $instock_batchs
     * @return array
     */
    public function get_statement_format_data($instock_batchs){
        $return = ['code' => false, 'message' => '获取失败', 'data' => []];


        $this->load->helper('status_finance');
        $this->load->model('supplier/supplier_model');
        $this->load->model('supplier/supplier_buyer_model');
        $this->load->model('supplier/supplier_settlement_model');
        $this->load->model('supplier/Supplier_payment_info_model');
        $this->load->model('user/purchase_user_model');
        $this->load->model('purchase/Purchase_order_determine_model');
        $this->load->model('finance/Purchase_order_pay_model');
        $this->load->model('abnormal/Report_loss_model');

        try{
            $statement_data = [// 数据格式化  指定三项式数据
                'main'    => [],
                'items'   => [],
                'summary' => []
            ];

            $inventory_item_list = $this->Purchase_inventory_items_model->get_inventory_items_for_statement(['instock_batch' => $instock_batchs]);
            if($inventory_item_list['code'] == false){
                throw new Exception($inventory_item_list['message']);
            }else{
                $inventory_item_list = $inventory_item_list['data'];
            }

            $purchase_type_id_list = array_unique(array_column($inventory_item_list,'purchase_type_id'));
            if(empty($inventory_item_list)){
                throw new Exception('入库明细记录获取失败');
            }

            // 确定 对账单业务线
            $first_inventory_item = $inventory_item_list[0];
            $supplier_code        = $first_inventory_item['supplier_code'];

            if(count($purchase_type_id_list) > 1){
                $purchase_type_id = PURCHASE_TYPE_PFB;
            }else{
                $purchase_type_id = current($purchase_type_id_list);
            }
            $is_drawback = $first_inventory_item['is_drawback'];

            // 获取供应商基本信息
            $supplierInfo = $this->supplier_model->get_supplier_info($supplier_code,false);
            if(empty($supplierInfo)){
                throw new Exception("对不起，供应商不存在");
            }
            $supplier_payment_info = $this->Supplier_payment_info_model->check_payment_info($supplier_code,$is_drawback,$purchase_type_id);
            if (empty($supplier_payment_info)){
                throw new Exception("对不起，供应商未找到对应的财务结算信息，请先维护供应商资料");
            }

            // 创建对账单 头部信息
            $statement_number                              = $this->getStatementNumber($purchase_type_id,true);
            $statement_data['main']['statement_number']    = $statement_number;
            $company_info                                  = compactCompanyInfo($first_inventory_item['purchase_name']);
            $statement_data['main']['purchase_name']       = $first_inventory_item['purchase_name'];
            $statement_data['main']['purchase_name_cn']    = $company_info['name'];
            $statement_data['main']['purchase_type_id']    = $purchase_type_id;
            $statement_data['main']['is_drawback']         = $is_drawback;
            $statement_data['main']['supplier_code']       = $first_inventory_item['supplier_code'];
            $statement_data['main']['supplier_name']       = $supplierInfo['supplier_name'];
            $statement_data['main']['supplier_address']    = $supplierInfo['register_address'];
            $statement_data['main']['supplier_is_gateway'] = $supplierInfo['is_gateway'];
            $statement_data['main']['settlement_method']   = $first_inventory_item['account_type'];
            $statement_data['main']['sup_account_name']    = $supplier_payment_info['account_name'];
            $statement_data['main']['sup_phone_number']    = $supplier_payment_info['phone_number'];
            $statement_data['main']['sup_bank']            = $supplier_payment_info['payment_platform_bank'];
            $statement_data['main']['sup_account']         = $supplier_payment_info['account'];

            $supplierSettlementInfo = $this->supplier_settlement_model->get_settlement_one($first_inventory_item['account_type']);

            $statement_data['main']['settlement_method_cn'] = isset($supplierSettlementInfo['settlement_name']) ? $supplierSettlementInfo['settlement_name'] : '';
            $statement_data['main']['pay_type']             = $first_inventory_item['pay_type'];
            $statement_data['main']['is_purchasing']        = $first_inventory_item['is_purchasing'];
            $statement_data['main']['pay_type_cn']          = getPayType($first_inventory_item['pay_type']);

            // 创建人
            if(defined('OAUTH_ACCESS')){// 供应商门户系统Oauth访问
                $su_buyer_list = $this->supplier_buyer_model->get_buyer_list($supplier_code);// 采购员
                if(empty($su_buyer_list) or !isset($su_buyer_list[10])){
                    throw new Exception("供应商未绑定对账员，请先修改供应商资料");
                }

                $statement_data['main']['create_user_id']       = 0;
                $statement_data['main']['create_user_name']     = $supplierInfo['reconciliation_agent'];
                $statement_data['main']['create_user_phone']    = $supplierInfo['agent_mobile'];
                $statement_data['main']['statement_user_id']    = $su_buyer_list[10]['buyer_id'];
                $statement_data['main']['statement_user_name']  = $su_buyer_list[10]['buyer_name'];;
                $statement_data['main']['source_party']         = 2;// 对账来源：门户系统
            }else{
                $statement_data['main']['create_user_id']       = getActiveUserId();
                
                $getActiveUserName  = getActiveUserName();
                $getActiveUserName  = preg_replace('/[0-9A-Za-z]/', '', $getActiveUserName);//去除字母和数字（工号）
                $purchase_user_info = $this->purchase_user_model->get_user_info_by_user_id($statement_data['main']['create_user_id']);// 根据采购员id获取联系电话


                // 创建人和对账人都＝点击生成对账单的用户名
                $statement_data['main']['create_user_name']     = $getActiveUserName;
                $statement_data['main']['create_user_phone']    = $purchase_user_info['phone_number'];
                $statement_data['main']['statement_user_id']    = getActiveUserId();
                $statement_data['main']['statement_user_name']  = $getActiveUserName;
                $statement_data['main']['source_party']         = 1;// 对账来源：采购系统

            }
            $statement_data['main']['create_time']        = date('Y-m-d');
            $statement_data['main']['accout_period_time'] = max(array_column($inventory_item_list,'need_pay_time'));
            $statement_data['main']['instock_month']      = max(array_column($inventory_item_list,'instock_month'));

            // 创建对账单 入库明细
            $po_real_confirm_amount_list = [];// 下单数量
            $instock_price_statement_list = [];// 对账单内的入库金额
            $instock_price_after_charge_against_list = [];// 入库明细剩余可冲销金额 汇总（根据PO维度）
            $total_instock_qty = 0;
            $total_instock_price = 0;
            foreach($inventory_item_list as $item_value){
                $item_tmp                        = [];
                $item_tmp['statement_number']    = $statement_number;
                $item_tmp['compact_number']      = $item_value['compact_number'];
                $item_tmp['purchase_number']     = $item_value['purchase_number'];
                $item_tmp['sku']                 = $item_value['sku'];
                $item_tmp['product_name']        = $item_value['product_name'];
                $item_tmp['product_img_url']     = $item_value['product_img_url'];
                $item_tmp['demand_number']       = $item_value['demand_number'];
                $item_tmp['s_w_r_id']            = $item_value['id'];
                $item_tmp['instock_batch']       = $item_value['instock_batch'];

                // 下单数量 = 采购数量 - 取消数量
                $order_cancel_list = $this->Purchase_order_determine_model->get_order_cancel_list($item_value['purchase_number'],$item_value['sku']);//获取取消数量集合
                $order_cancel_qty = isset($order_cancel_list[$item_value['purchase_number'].'-'.$item_value['sku']])?$order_cancel_list[$item_value['purchase_number'].'-'.$item_value['sku']]:0;
                $item_tmp['real_confirm_amount'] = $item_value['confirm_amount'] - $order_cancel_qty;

                $item_tmp['confirm_amount']      = $item_value['confirm_amount'];
                $item_tmp['instock_qty']         = $item_value['instock_qty'];
                $item_tmp['instock_date']        = $item_value['instock_date'];
                $item_tmp['instock_price']       = $item_value['instock_price'];
                $item_tmp['buyer_id']            = $item_value['buyer_id'];
                $item_tmp['buyer_name']          = $item_value['buyer_name'];
                $item_tmp['warehouse_code']      = $item_value['warehouse_code'];
                $item_tmp['warehouse_name']      = $item_value['warehouse_name'];
                $item_tmp['purchase_unit_price'] = $item_value['purchase_unit_price'];
                $item_tmp['surplus_charge_against_amount'] = $item_value['surplus_charge_against_amount'];
                $item_tmp['currency']            = 'RMB';

                $statement_data['items'][] = $item_tmp;

                if(!isset($po_real_confirm_amount_list[$item_tmp['purchase_number'].'_'.$item_tmp['sku']])){// 分批入库的只计算一次汇总
                    $po_real_confirm_amount_list[$item_tmp['purchase_number'].'_'.$item_tmp['sku']] = $item_tmp['real_confirm_amount'];
                }
                $total_instock_qty += $item_tmp['instock_qty'];
                $total_instock_price += $item_tmp['instock_price'];

                if(isset($instock_price_after_charge_against_list[$item_value['purchase_number']])){
                    $instock_price_after_charge_against_list[$item_value['purchase_number']] += $item_value['surplus_charge_against_amount'];
                }else{
                    $instock_price_after_charge_against_list[$item_value['purchase_number']] = $item_value['surplus_charge_against_amount'];
                }

                if(isset($instock_price_statement_list[$item_value['purchase_number']])){
                    $instock_price_statement_list[$item_value['purchase_number']] += $item_value['instock_price'];
                }else{
                    $instock_price_statement_list[$item_value['purchase_number']] = $item_value['instock_price'];
                }
                $instock_price_after_charge_against_list[$item_value['purchase_number']] = format_price($instock_price_after_charge_against_list[$item_value['purchase_number']]);
                $instock_price_statement_list[$item_value['purchase_number']] = format_price($instock_price_statement_list[$item_value['purchase_number']]);
            }

            $statement_data['items_total_list'] = [
                'total_real_confirm_amount' => array_sum($po_real_confirm_amount_list),
                'total_instock_qty' => format_price($total_instock_qty),
                'total_instock_price' => format_price($total_instock_price),
            ];

            // 采购单冲销结余 记录
            $purchase_numbers   = array_unique(array_column($inventory_item_list,'purchase_number'));
            $po_surplus_list    = $this->Charge_against_surplus_model->get_surplus($purchase_numbers);
            $po_surplus_list    = arrayKeyToColumn($po_surplus_list,'purchase_number');

            $surplus_loss_product_money_list = $this->get_surplus_available_loss_product_money($purchase_numbers);// 计算 PO下 每次 对账单请款的 报损金额明细

            // 采购单 信息
            $po_pay_type_list = $this->purchase_db
                ->select('purchase_number,product_money,freight,discount,process_cost,real_price')
                ->where_in('purchase_number',$purchase_numbers)
                ->group_by('purchase_number')
                ->get('purchase_order_pay_type')
                ->result_array();
            $po_pay_type_list    = arrayKeyToColumn($po_pay_type_list,'purchase_number');

            // 冲销金额列表 - 退款冲销的
            $caPriceList = $this->Charge_against_records_model->get_charge_against_records_gather(['purchase_number' => $purchase_numbers,'charge_against_status' => CHARGE_AGAINST_STATUE_WAITING_PASS],2);
            $caPriceList = arrayKeyToColumn($caPriceList,'purchase_number');

            // 创建对账单 对账汇总
            foreach($purchase_numbers as $purchase_number){
                $summary_tmp                                       = [];
                $summary_tmp['statement_number']                   = $statement_number;
                $summary_tmp['purchase_number']                    = $purchase_number;

                $po_surplus = isset($po_surplus_list[$purchase_number])?$po_surplus_list[$purchase_number]:[];
                $po_pay_type = isset($po_pay_type_list[$purchase_number])?$po_pay_type_list[$purchase_number]:[];
                $surplus_loss_product_money = isset($surplus_loss_product_money_list[$purchase_number])?$surplus_loss_product_money_list[$purchase_number]:[];
                if(empty($po_surplus) or empty($po_pay_type)){
                    throw new Exception('采购单请款信息或冲销剩余信息缺失');
                }

                $summary_tmp['real_confirm_amount_price']          = format_price($po_surplus['product_money'] - $po_surplus['cancel_product_money']);// PO下单金额
                $summary_tmp['total_instock_price']                = format_price($po_surplus['total_instock_price']);
                $summary_tmp['total_instock_weight']               = format_price($po_surplus['total_instock_weight']);
                $summary_tmp['paid_product_money']                 = format_price($po_surplus['paid_product_money']);
                $summary_tmp['real_price_after_charge_against']    = format_price($po_surplus['real_price_after_charge_against']);
                $summary_tmp['order_inner_instock_price']          = format_price(isset($instock_price_statement_list[$purchase_number]) ? $instock_price_statement_list[$purchase_number] : 0);
                $summary_tmp['instock_price_after_charge_against'] = format_price(isset($instock_price_after_charge_against_list[$purchase_number]) ? $instock_price_after_charge_against_list[$purchase_number] : 0);
                $summary_tmp['ca_process_cost']                    = format_price(isset($caPriceList[$purchase_number]) ? $caPriceList[$purchase_number]['charge_against_process_cost'] : 0);// 退款抵冲的加工费
                $summary_tmp['ca_amount']                          = format_price(isset($caPriceList[$purchase_number]) ? $caPriceList[$purchase_number]['charge_against_product'] : 0);// 退款抵冲的商品金额

                // 对账单应付信息
                $summary_tmp['order_freight']                      = format_price($po_pay_type['freight'] - $po_surplus['cancel_freight']);// 采购单运费 - 已取消运费
                $summary_tmp['order_discount']                     = format_price($po_pay_type['discount'] - $po_surplus['cancel_discount']);// 采购单优惠额 - 已取消优惠额
                $summary_tmp['order_process_cost']                 = format_price($po_pay_type['process_cost'] - $po_surplus['cancel_process_cost'] - $summary_tmp['ca_process_cost']);// 采购单加工费 - 已取消加工费 - 已抵扣加工费
                $summary_tmp['paid_freight']                       = format_price($po_surplus['paid_freight']);// 采购单已付款运费
                $summary_tmp['paid_discount']                      = format_price($po_surplus['paid_discount']);// 采购单已付款优惠额
                $summary_tmp['paid_process_cost']                  = format_price($po_surplus['paid_process_cost']);// 采购单已付款加工费

                $summary_tmp['loss_product_money']                 = format_price(array_sum(array_column($surplus_loss_product_money,'loss_product_money')));// 采购单总报损商品额 - 已付款报损商品额
                $summary_tmp['loss_product_money_sku_detail']      = json_encode($surplus_loss_product_money);// 缓存每次 对账单请款的 报损金额明细


                $statement_data['summary'][]                       = $summary_tmp;
            }

            $return['code']    = true;
            $return['data']    = $statement_data;
            $return['message'] = '获取成功';

            return $return;
        }catch(Exception $exception){

            $return['message'] = $exception->getMessage();
            return $return;
        }
    }


    /**
     * 入库批次 创建对账单（第二步）
     * @param $instock_batchs
     * @param $check_enable
     * @return array
     */
    public function create_statement($instock_batchs,$check_enable = true){
        $return = ['code' => false, 'message' => '获取失败', 'data' => []];

        try{

            // 数据通过验证  缓存验证结果
            $instock_batchs = array_values($instock_batchs);
            if($check_enable){
                sort($instock_batchs);
                $cache_key = md5(implode('_', $instock_batchs));
                $exists    = $this->rediss->getData($cache_key);
                if(empty($exists)){
                    throw new Exception('数据未通过验证，请重新创建对账单');
                }
            }


            // 获取数据
            $statement_data = $this->get_statement_format_data($instock_batchs);
            if(empty($statement_data['code'])){
                throw new Exception($statement_data['message']);
            }else{
                $statement_data = $statement_data['data'];
            }

            // 验证数据
            if(empty($statement_data['main']) or empty($statement_data['items']) or empty($statement_data['summary'])){
                throw new Exception('获取对账单数据异常');
            }
            if(empty($statement_data['main']['create_user_phone'])){
                if(defined('OAUTH_ACCESS')){
                    throw new Exception('请先前往我的资料维护对账经办人！');
                }else{
                    throw new Exception('对账联系人联系方式缺失，请在数据权限中配置');
                }
            }

            $statement_number             = $this->getStatementNumber($statement_data['main']['purchase_type_id']);

            $total_instock_price = $total_pay_price = $total_freight = $total_discount = $total_process_cost = $total_loss_product_money = 0;


            // 1、保存对账单 主表信息
            $data_main = [
                'statement_number'        => $statement_number,
                'purchase_name'           => $statement_data['main']['purchase_name'],
                'purchase_type_id'        => $statement_data['main']['purchase_type_id'],
                'is_drawback'             => $statement_data['main']['is_drawback'],
                'supplier_code'           => $statement_data['main']['supplier_code'],
                'supplier_name'           => $statement_data['main']['supplier_name'],
                'supplier_address'        => $statement_data['main']['supplier_address'],
                'sup_account_name'        => $statement_data['main']['sup_account_name'],
                'sup_phone_number'        => $statement_data['main']['sup_phone_number'],
                'sup_bank'                => $statement_data['main']['sup_bank'],
                'sup_account'             => $statement_data['main']['sup_account'],
                'supplier_is_gateway'     => $statement_data['main']['supplier_is_gateway'],
                'settlement_method'       => $statement_data['main']['settlement_method'],
                'pay_type'                => $statement_data['main']['pay_type'],
                'is_purchasing'           => $statement_data['main']['is_purchasing'],
                'create_time'             => date('Y-m-d H:i:s'),
                'statement_pay_status'    => PAY_UNPAID_STATUS,// 付款状态(10.未申请付款)
                'status_valid'            => 1,// 状态(1.正常,2.作废)
                'status'                  => STATEMENT_STATUS_IS_CREATE,// 对账单状态(1.已生成对账单)
                'create_user_id'          => $statement_data['main']['create_user_id'],
                'create_user_name'        => $statement_data['main']['create_user_name'],
                'create_user_phone'       => $statement_data['main']['create_user_phone'],
                'accout_period_time'      => $statement_data['main']['accout_period_time'],
                'source_party'            => $statement_data['main']['source_party'],
                'instock_month'           => date('Y-m-01',strtotime($statement_data['main']['instock_month'])),
                'statement_user_id'       => $statement_data['main']['statement_user_id'],
                'statement_user_name'     => $statement_data['main']['statement_user_name'],
                'total_instock_price'     => 0,
                'total_pay_price'         => 0,
                'total_freight'           => 0,
                'total_discount'          => 0,
                'total_process_cost'      => 0,
                'total_loss_product_money'=> 0,
            ];


            // 2、保存对账单 明细表信息
            $data_items_list = [];
            foreach($statement_data['items'] as $item_value){
                // 防止多人操作
                if($this->rediss->getData('cr_'.md5($item_value['instock_batch']))){
                    throw new Exception("入库批次号[".$item_value['instock_batch']."] 在对账中");
                }else{
                    $this->rediss->setData('cr_'.md5($item_value['instock_batch']),1,30);
                }

                $data_item = [
                    'statement_number'    => $statement_number,
                    'compact_number'      => $item_value['compact_number'],
                    'purchase_number'     => $item_value['purchase_number'],
                    'sku'                 => $item_value['sku'],
                    'product_name'        => $item_value['product_name'],
                    'purchase_unit_price' => $item_value['purchase_unit_price'],
                    'buyer_id'            => $item_value['buyer_id'],
                    'buyer_name'          => $item_value['buyer_name'],
                    'currency'            => 'RMB',
                    'product_img_url'     => $item_value['product_img_url'],
                    'demand_number'       => $item_value['demand_number'],
                    'warehouse_code'      => $item_value['warehouse_code'],
                    'warehouse_name'      => $item_value['warehouse_name'],
                    's_w_r_id'            => $item_value['s_w_r_id'],
                    'instock_batch'       => $item_value['instock_batch'],
                    'confirm_amount'      => $item_value['confirm_amount'],
                    'instock_qty'         => $item_value['instock_qty'],
                    'instock_date'        => $item_value['instock_date'],
                    'instock_price'       => $item_value['instock_price'],
                    'pay_product_money'   => $item_value['surplus_charge_against_amount'],// 应付商品额
                ];

                $total_instock_price    += $item_value['instock_price'];// 总入库商品额

                $data_items_list[] = $data_item;
            }


            // 3、创建对账单 对账汇总
            $data_summary_list = [];
            foreach($statement_data['summary'] as $summary_value){
                $summary_tmp     = [
                    'statement_number'                   => $statement_number,
                    'purchase_number'                    => $summary_value['purchase_number'],
                    'real_confirm_amount_price'          => $summary_value['real_confirm_amount_price'],
                    'total_instock_price'                => $summary_value['total_instock_price'],
                    'total_instock_weight'               => $summary_value['total_instock_weight'],
                    'paid_product_money'                 => $summary_value['paid_product_money'],
                    'real_price_after_charge_against'    => $summary_value['real_price_after_charge_against'],
                    'instock_price_after_charge_against' => $summary_value['instock_price_after_charge_against'],
                    'ca_process_cost'                    => $summary_value['ca_process_cost'],
                    'ca_amount'                          => $summary_value['ca_amount'],
                    'order_discount'                     => $summary_value['paid_discount'] == 0?$summary_value['order_discount']:0,
                    'order_freight'                      => $summary_value['paid_freight'] == 0?$summary_value['order_freight']:0,
                    'order_process_cost'                 => $summary_value['paid_process_cost'] == 0?$summary_value['order_process_cost']:0,
                    'loss_product_money'                 => $summary_value['loss_product_money']?$summary_value['loss_product_money']:0,
                    'loss_product_money_sku_detail'      => $summary_value['loss_product_money_sku_detail'],
                    'pay_product_money'                  => $summary_value['instock_price_after_charge_against'],
                ];
                $total_pay_price            += $summary_value['instock_price_after_charge_against'];// 总应付商品额
                $total_freight              += $summary_tmp['order_freight'];
                $total_discount             += $summary_tmp['order_discount'];
                $total_process_cost         += $summary_tmp['order_process_cost'];
                $total_loss_product_money   += $summary_value['loss_product_money'];// 总应付商品额

                $data_summary_list[] = $summary_tmp;
            }

            $data_main['total_instock_price']      = format_price($total_instock_price);
            $data_main['total_pay_price']          = format_price($total_pay_price);
            $data_main['total_freight']            = format_price($total_freight);
            $data_main['total_discount']           = format_price($total_discount);
            $data_main['total_process_cost']       = format_price($total_process_cost);
            $data_main['total_loss_product_money'] = format_price($total_loss_product_money);


            try{
                $this->purchase_db->trans_strict(true);
                $this->purchase_db->trans_begin();

                // 保存数据到数据库
                $result_main = $this->purchase_db->insert($this->table_name,$data_main);

                if(empty($result_main)){
                    throw new Exception('创建对账单主表提交出错');
                }else{
                    $result_items = $this->purchase_db->insert_batch($this->table_name_items,$data_items_list);
                    if(empty($result_items)){
                        throw new Exception('创建对账单明细表提交出错');
                    }
                    $result_summary = $this->purchase_db->insert_batch($this->table_name_summary,$data_summary_list);
                    if(empty($result_summary)){
                        throw new Exception('创建对账单对账汇总表提交出错');
                    }
                    $result_inv = $this->purchase_db->where_in('instock_batch',$instock_batchs)
                        ->update('statement_warehouse_results',['statement_number' => $statement_number,'created_statement' => 2]);
                    if(empty($result_inv)){
                        throw new Exception('创建对账单更新入库明细提交出错');
                    }
                    $this->Purchase_statement_note_model->add_remark([$statement_number], 1, '对账单成功', '创建对账单');
                }

                if ($this->purchase_db->trans_status() === false) {
                    throw new Exception('创建对账单事务提交出错');
                } else {
                    $this->purchase_db->trans_commit();
                    $this->rediss->deleteData($cache_key);
                }

                // 触发SWOOLE事务生成对账单PDF
                if(CG_ENV == 'prod'){
                    $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
                    if ($client->connect('172.16.50.42', 9508, 0.5)) {// web3服务器IP，这里暂且写死
                        $client->send(json_encode(['id' => $statement_number]));
                        $client->recv();
                        $client->close();
                    }
                }else{
                    @file_get_contents(CG_SYSTEM_APP_DAL_IP.'/export_server_handle/compact_statement_download_execute/'.$statement_number);
                }
            }catch(Exception $excep){
                $this->purchase_db->trans_rollback();
                throw new Exception($excep->getMessage());
            }

            // //对接门户系统的供应商，对账单推送门户系统
            // if ($data_main['supplier_is_gateway']) {
            //     try{
            //         //生成对账pdf
            //         $file_data = $this->_create_statement_pdf($statement_number);
            //         if ($file_data['code']) {
            //             $data_main['file_url'] = $file_data['file_path'];
            //             $data_main['file_name'] = $file_data['file_name'];
            //             $this->_push_statement_to_gateway($data_main, $data_items_list);
            //         }
            //     }catch (Exception $e){
            //         return ['code' => true, 'message' => '对账单创建成功，但' . $e->getMessage(), 'data' => [$statement_number]];
            //     }
            // }

            $return['code'] = true;
            $return['data'] = [$statement_number];
            $return['message'] = '对账单创建成功';
        }catch(Exception $exception){
            $return['message'] = $exception->getMessage();
        }
        return $return;
    }

    /**
     * 获取对账单中已申请的报损商品金额（占用的报损商品金额）
     * @param $purchase_numbers
     * @return array|bool
     * @throws
     */
    public function get_statement_loss_product_money($purchase_numbers){

        // 采购单冲销结余 记录
        $po_pay_statement_list    = $this->purchase_db->select('B.purchase_number,SUM(B.loss_product_money) AS loss_product_money,B.loss_product_money_sku_detail')
            ->from($this->table_name .' AS A')
            ->join($this->table_name_summary .' AS B','A.statement_number=B.statement_number','LEFT')
            ->where('A.status_valid',1)
            ->where('B.loss_product_money >',0)
            ->where_in('B.purchase_number',$purchase_numbers)
            ->group_by('B.purchase_number')
            ->get()
            ->result_array();
        return $po_pay_statement_list;
    }

    /**
     * 计算 PO下 每次 对账单请款的 报损金额明细
     *      剩余可申请报损商品金额 = PO+SKU已审核通过的报损商品金额 - 请款单报损请款的请款商品金额 - 有效的对账单中占用的报损商品金额
     * @param array $purchase_numbers  采购单号
     * @return array
     */
    public function get_surplus_available_loss_product_money($purchase_numbers){
        // 采购单-已付报损金额
        $po_paid_loss_list    = $this->purchase_db
            ->select(
                'poyd.purchase_number,poyd.sku,'
                .'sum(poyd.product_money) as paid_product_money'
            )
            ->from('purchase_order_pay as poy')
            ->join('purchase_order_pay_detail as poyd', 'poy.requisition_number=poyd.requisition_number', 'INNER')
            ->where('poy.pay_status', PAY_PAID)// 付款状态 51.已付款
            ->where_in('poyd.purchase_number', $purchase_numbers)
            ->where('poy.pay_category',PURCHASE_REQUISITION_METHOD_REPORTLOSS)
            ->group_by('poyd.purchase_number,poyd.sku')
            ->get()
            ->result_array();
        $po_paid_loss_list = arrayKeyToColumnMulti($po_paid_loss_list,'purchase_number','sku');

        // 采购单-对账单中的请款报损金额
        $po_statement_loss_list     = $this->get_statement_loss_product_money($purchase_numbers);
        $po_statement_loss_list     = arrayKeyToColumn($po_statement_loss_list,'purchase_number');
        $po_statement_loss_list_tmp = [];
        foreach($po_statement_loss_list as $key_number => $value_number){
            $loss_product_money_sku_detail = json_decode($value_number['loss_product_money_sku_detail'],true);
            if($loss_product_money_sku_detail){
                foreach($loss_product_money_sku_detail as $detail_value){
                    $po_statement_loss_list_tmp[$key_number][$detail_value['sku']]['loss_product_money'] = $detail_value['loss_product_money'];
                }
            }
        }

        // 采购单-所有SKU的报损商品金额
        $loss_price_list = $this->Report_loss_model->get_loss_money_by_purchase_number_sku($purchase_numbers);
        $loss_price_list = arrayKeyToColumnMulti($loss_price_list,'pur_number','sku');


        // 计算剩余可申请报损商品金额
        $surplus_loss_product_money_list = [];
        foreach($loss_price_list as $loss_key_po => $loss_value_po){
            foreach($loss_value_po as $loss_key_sku => $loss_value_sku){

                $loss_product_money             = $loss_value_sku['loss_product_money'];// PO+SKU总报损商品额
                $paid_loss_product_money        = isset($po_paid_loss_list[$loss_key_po][$loss_key_sku])?$po_paid_loss_list[$loss_key_po][$loss_key_sku]['paid_product_money']:0;
                $po_statement_loss_product_money = isset($po_statement_loss_list_tmp[$loss_key_po][$loss_key_sku])?$po_statement_loss_list_tmp[$loss_key_po][$loss_key_sku]['loss_product_money']:0;

                $surplus_loss_product_money = format_price($loss_product_money - $paid_loss_product_money - $po_statement_loss_product_money);
                $surplus_loss_product_money = $surplus_loss_product_money > 0 ? $surplus_loss_product_money : 0;

                $surplus_loss_product_money_list[$loss_key_po][] = ['sku' => $loss_key_sku,'loss_product_money' => $surplus_loss_product_money];
            }
        }

        return $surplus_loss_product_money_list;
    }

    /**
     * 创建对账单pdf，并上传文件服务器
     * @param $statement_number
     * @return array|bool
     * @throws
     */
    private function _create_statement_pdf($statement_number)
    {
        //保存pdf服务器路径
        $file_path = get_export_path(date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . date('d'));
        if (!file_exists($file_path)) @mkdir($file_path, 0777, true);
        //pdf路径+文件名称
        $file_name = $file_path . $statement_number . '.pdf';
        //生成pdf
        $download_statement_pdf_url = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'download_statement_pdf');
        $params = ['statement_number' => $statement_number, 'file_path' => $file_path, 'type' => 'F', 'appdal_cron' => 1];
        $download_statement_pdf_url .= '?' . http_build_query($params);
        $res = getCurlData($download_statement_pdf_url, '', 'get', ['Content-Type: application/json']);
        if (!file_exists($file_name)) {
            //写入操作日志表
            $this->_insert_mongodb_log($statement_number, '生成PDF失败', ['push_data' => $params, 'result_data' => $res], 'PUR_STATEMENT_PUSH_GATEWAY_CREATE_PDF');
            throw new Exception('生成PDF失败');
        }

        //pdf上传文件服务器
        $print_statement_url = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'upload_file');
        $file_data = ['file_path' => $file_name];
        $_result = getCurlData($print_statement_url, $file_data, 'post');
        $_result = json_decode($_result, true);

        if (empty($_result['status']) or empty($_result['url_img'])) {
            //写入操作日志表
            $this->_insert_mongodb_log($statement_number, 'PDF上传文件服务器失败', ['push_data' => $file_data, 'result_data' => $_result], 'PUR_STATEMENT_PUSH_GATEWAY_CREATE_PDF');
            throw new Exception('PDF上传文件服务器失败');
        }
        return ['file_name' => basename($file_name), 'file_path' => $_result['url_img'], 'code' => true];
    }

    /**
     * 对账单推送门户系统
     * @param $data_main
     * @param $data_items_list
     * @throws Exception
     */
    private function _push_statement_to_gateway($data_main, $data_items_list)
    {
        // 数据转换成中文
        $this->load->model('supplier/supplier_settlement_model');
        $supplierSettlementInfo = $this->supplier_settlement_model->get_settlement_one($data_main['settlement_method']);
        //主表数据
        $post_data = [
            'statementNumber' => $data_main['statement_number'],//对账单号
            'purchaseName' => $data_main['purchase_name'],//采购主体
            'supplierCode' => $data_main['supplier_code'],//供应商code
            'supplierName' => $data_main['supplier_name'],//供应商名称
            'settlementMethod' => isset($supplierSettlementInfo['settlement_name']) ? $supplierSettlementInfo['settlement_name'] : 0,//结算方式
            'payType' => getPayType($data_main['pay_type']),//支付方式
            'settlementProportion' => '100%',//结算比例
            'isDrawback' => $data_main['is_drawback'],//是否开票（0:不开票 1:开票）
            'freight' => 0,//总运费
            'discount' => 0,//总优惠额
            'payPrice' => $data_main['total_pay_price'],//应付款金额
            'paidPrice' => 0,//已付款金额
            'fileName' => $data_main['file_name'],//对账单附件名称
            'fileUrl' => $data_main['file_url'],//对账单附件路径
            'payStatus' => 1,//付款状态(1:未付款 2:部分付款 3:已付款)
            'createUser' => $data_main['create_user_name'],//创建人
        ];

        //获取采购单明细数据
        $purchase_number = array_filter(array_column($data_items_list, 'purchase_number'));
        $sku = array_filter(array_column($data_items_list, 'sku'));
        $pur_items_data_tmp = $this->purchase_db->select('purchase_number,sku,pur_ticketed_point,freight,discount')
            ->from('purchase_order_items')
            ->where_in('purchase_number', $purchase_number)
            ->where_in('sku', $sku)
            ->get()->result_array();
        foreach ($pur_items_data_tmp as $item) {
            //税率
            $tax_rate_data[$item['purchase_number'] . '_' . $item['sku']] = $item['pur_ticketed_point'];
            //汇总明细数据采购单维度的运费和优惠额
            $post_data['freight'] += (float)$item['freight'];
            $post_data['discount'] += (float)$item['discount'];
        }
        unset($pur_items_data_tmp);

        $this->load->model('finance/purchase_order_pay_model');
        //明细数据
        foreach ($data_items_list as $item) {
            //获取已付款金额
            $price_data = $this->purchase_order_pay_model->get_pay_total_by_sku($item['purchase_number'], $item['sku']);
            $post_data['item'][] = [
                'statementNumber' => $item['statement_number'],//对账单号
                'purchaseNumber' => $item['purchase_number'],//采购单号
                'compactNumber' => $item['compact_number'],//合同单号
                'warehouseCode' => $item['warehouse_code'],//采购仓库编码
                'warehouseName' => $item['warehouse_name'],//仓库名称
                'purchaseUserId' => $item['buyer_id'],//采购员ID
                'purchaseUserName' => $item['buyer_name'],//采购员姓名
                'sku' => $item['sku'],//sku
                'productName' => $item['product_name'],//产品名称
                'instockQty' => (int)$item['instock_qty'],//入库数量
                'unitPrice' => (float)$item['purchase_unit_price'],//采购单价
                'currencyCode' => $item['currency'],//币种
                'taxRate' => isset($tax_rate_data[$item['purchase_number'] . '_' . $item['sku']]) ? (float)$tax_rate_data[$item['purchase_number'] . '_' . $item['sku']] : 0,//税率
                'taxTotal' => (float)bcmul($item['instock_qty'], $item['purchase_unit_price'], 3),//计税合计
                'paidPrice' => (float)$price_data['pay_total'],//已付金额
            ];
        }
        try {
            $_result = $this->_post_gateway_api($post_data, 'push_statement_items');
            if (isset($_result['code']) && $_result['code'] == 200) {
                //推送成功后，更新推送状态
                if (isset($_result['data']) && $_result['data']) {
                    $update_res = $this->purchase_db->where('statement_number', $data_main['statement_number'])
                        ->update('purchase_statement', ['is_push_gateway' => 1]);
                    if (empty($update_res)) {
                        throw new Exception('更新是否推送门户系统状态失败');
                    }
                } else {
                    //推送失败的处理
                    throw new Exception('对账单推送门户系统失败[1]');
                }
            } elseif (isset($_result['code']) && !$_result['code']) {
                //推送失败的处理
                throw new Exception('对账单推送门户系统失败[2]');
            } else {
                $msg = isset($_result['msg']) ? $_result['msg'] : '请求推送接口异常';
                throw new Exception($msg);
            }
            //写入操作日志表
            $this->_insert_mongodb_log($data_main['statement_number'], '对账单推送门户系统成功', ['push_data' => $post_data, 'result_data' => $_result]);
        } catch (Exception $e) {
            //写入操作日志表
            $this->_insert_mongodb_log($data_main['statement_number'], $e->getMessage(), ['push_data' => $post_data, 'result_data' => isset($_result) ? $_result : '']);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 请求门户系统接口
     * @param array $post_data 请求数据
     * @param string $api_second_name api配置接口第二名称
     * @return mixed
     */
    private function _post_gateway_api($post_data, $api_second_name)
    {
        //请求URL
        $request_url = getConfigItemByName('api_config', 'charge_against', $api_second_name);
        if (empty($request_url)) exit('请求URL不存在');
        $access_token = getOASystemAccessToken();
        if (empty($access_token)) exit('获取access_token值失败');
        $request_url = $request_url . '?access_token=' . $access_token;
        $header = ['Content-Type: application/json'];
        $res = getCurlData($request_url, json_encode($post_data), 'POST', $header);
        return json_decode($res, true);

    }

    /**
     * 写入MongoDB日志
     * @param string $record_number 对账单号
     * @param string $msg 日志消息
     * @param array $data 日志数据
     * @param string $type 类型
     * @return mixed
     */
    private function _insert_mongodb_log($record_number, $msg, $data, $type = 'PUR_STATEMENT_PUSH_GATEWAY')
    {
        $this->load->library('mongo_db');
        $insert_data = array(
            'record_number' => $record_number,
            'record_type' => $type,
            'content' => $msg,
            'content_detail' => json_encode($data, 320),
            'create_time' => date('Y-m-d H:i:s')
        );
        return $this->mongo_db->insert('push_statement_log', $insert_data);
    }


    /**
     * 对账单管理列表
     * @param array $params
     * @param int   $offset
     * @param int   $limit
     * @param int   $page
     * @param bool  $export
     * @param bool  $is_gateway
     * @return array
     */
    public function get_statement_list($params = array(), $offset = 1, $limit = 20, $page = 1, $export = false,$action = null, $is_gateway=false)
    {
        if(defined('OAUTH_ACCESS')){
            $gateWayPayStatus = [
                PAY_UNPAID_STATUS           => '未申请付款',
                PURCHASE_NUMBER_ZFSTATUS    => '等待支付',
                PART_PAID                   => '已部分付款',
                PAY_PAID                    => '已付款'
            ];
        }else{
            $gateWayPayStatus = getStatementPayStatus();
        }

        $query = $this->purchase_db;
        $query->from($this->table_name. ' AS ps')
            ->join("pur_supplier as sup", "ps.supplier_code=sup.supplier_code", "left")
            ->join("pur_purchase_order_pay as pa", "ps.statement_number=pa.pur_number", "left");
        $select_field = 'ps.*,sup.is_postage as free_shipping';
        $select_field .= ',pa.pur_tran_num,pa.trans_orderid,pa.voucher_address,pa.pay_status,pa.pay_price';

        // 已完结
        if(SetAndNotEmpty($params, 'pay_finished_type')){
            if($params['pay_finished_type'] == 2){
                $query->where('ps.statement_pay_status',PAY_PAID);
                $query->where('pa.pay_status',PAY_PAID);
            }else{
                $query->where('(ps.statement_pay_status <> '.PAY_PAID .' OR ps.statement_pay_status IS NULL)');
            }
        }

        if(SetAndNotEmpty($params, 'order_number') || SetAndNotEmpty($params, 'purchase_number') ||
            SetAndNotEmpty($params, 'purchase_type') || SetAndNotEmpty($params, 'buyer_id') || SetAndNotEmpty($params, 'demand_number')){
            $query->join($this->table_name_items.' AS psi','ps.statement_number=psi.statement_number','LEFT');

            if(SetAndNotEmpty($params, 'order_number')){
                if(stripos($params['order_number'],' ') !== false){
                    $order_number_list = query_string_to_array($params['order_number']);
                    $query->group_start()
                        ->where_in('psi.statement_number',$order_number_list)
                        ->or_where_in('psi.compact_number',$order_number_list)
                        ->group_end();
                }else{
                    $query->group_start()
                        ->where('psi.statement_number',$params['order_number'])
                        ->or_where('psi.compact_number',$params['order_number'])
                        ->group_end();
                }
            }

            if(SetAndNotEmpty($params, 'purchase_number')){
                if(stripos($params['purchase_number'],' ') !== false){
                    $purchase_number_list = explode(' ',$params['purchase_number']);
                    $query->where_in('psi.purchase_number',$purchase_number_list);
                }else{
                    $query->where('psi.purchase_number',$params['purchase_number']);
                }
            }

            if(SetAndNotEmpty($params, 'purchase_type')) {
                if(is_array($params["purchase_type"])) {
                    $query->where_in('ps.purchase_type_id', $params["purchase_type"]);
                } else {
                    $query->where('ps.purchase_type_id', $params["purchase_type"]);
                }
            }

            if(SetAndNotEmpty($params, 'buyer_id')){
                if(is_array($params['buyer_id'])){
                    $query->where_in('psi.buyer_id',$params['buyer_id']);
                }else{
                    $query->where('psi.buyer_id',$params['buyer_id']);
                }
            }

            if(SetAndNotEmpty($params, 'demand_number')){
                if(stripos($params['demand_number'],' ') !== false){
                    $demand_number_list = explode(' ',$params['demand_number']);
                    $query->where('psi.demand_number',$demand_number_list);
                }else{
                    $query->where('psi.demand_number',$params['demand_number']);
                }
            }
        }

        if(SetAndNotEmpty($params, 'ids')){
            if(stripos($params['ids'],',') !== false){
                $ids_list = explode(',',$params['ids']);
                $query->where_in('ps.id',$ids_list);
            }else{
                $query->where('ps.id',$params['ids']);
            }
        }

        if(SetAndNotEmpty($params, 'group_ids')){
            if(count($params['groupdatas'])>2000){
                $this->purchase_db->where("( 1=1");
                $groupdatas = array_chunk($params['groupdatas'],10);
                foreach($groupdatas as $groupdata){
                    $this->purchase_db->or_where_in("ps.create_user_id", $groupdata);
                }
                $this->purchase_db->where(" 1=1 )");
            }else {
                $this->purchase_db->where_in("ps.create_user_id", $params['groupdatas']);
            }
        }

        if(SetAndNotEmpty($params, 'create_user_id')){
            if(is_array($params['create_user_id'])){
                $query->where_in('ps.create_user_id',$params['create_user_id']);
            }else{
                $query->where('ps.create_user_id',$params['create_user_id']);
            }
        }
        if(SetAndNotEmpty($params, 'statement_user_id')){
            if(is_array($params['statement_user_id'])){
                $query->where_in('ps.statement_user_id',$params['statement_user_id']);
            }else{
                $query->where('ps.statement_user_id',$params['statement_user_id']);
            }
        }

        if(SetAndNotEmpty($params, 'statement_pdf_status')){
            if(is_array($params['statement_pdf_status'])){
                $query->where_in('ps.statement_pdf_status',$params['statement_pdf_status']);
            }else{
                $query->where('ps.statement_pdf_status',$params['statement_pdf_status']);
            }
        }

        if(SetAndNotEmpty($params, 'status')){
            if(is_array($params['status'])){
                $query->where_in('ps.status',$params['status']);
            }else{
                $query->where('ps.status',$params['status']);
            }
        }

        if(SetAndNotEmpty($params, 'is_esign')){
            if($params['is_esign'] == 1){
                $query->where('ps.flowId <> ""');
            }else{
                $query->where('ps.flowId =""');
            }
        }

        if(SetAndNotEmpty($params, 'supplier_code')) $query->where('ps.supplier_code',$params['supplier_code']);
        if(SetAndNotEmpty($params, 'is_purchasing')) $query->where('ps.is_purchasing',$params['is_purchasing']);
        if(SetAndNotEmpty($params, 'supplier_is_gateway')) $query->where('ps.supplier_is_gateway',$params['supplier_is_gateway']);
        if(SetAndNotEmpty($params, 'is_drawback')) $query->where('ps.is_drawback',$params['is_drawback']);
        if(SetAndNotEmpty($params, 'purchase_name')) $query->where('ps.purchase_name',$params['purchase_name']);
        if(SetAndNotEmpty($params, 'status_valid')) $query->where('ps.status_valid',$params['status_valid']);
        if(SetAndNotEmpty($params, 'create_time_start')) $query->where('ps.create_time >=',$params['create_time_start']);
        if(SetAndNotEmpty($params, 'create_time_end')) $query->where('ps.create_time <=',$params['create_time_end']);
        if(SetAndNotEmpty($params, 'free_shipping')) $query->where('sup.is_postage =',$params['free_shipping']);
        if(SetAndNotEmpty($params, 'create_user_name')) $query->like('ps.create_user_name',$params['create_user_name'],'both',false);// 创建人模糊查找
        if(SetAndNotEmpty($params, 'statement_user_name')) $query->like('ps.statement_user_name',$params['statement_user_name'],'both',false);// 创建人模糊查找
        if(SetAndNotEmpty($params, 'source_party')) $query->where('ps.source_party',$params['source_party']);
        if(SetAndNotEmpty($params, 'instock_month')) $query->like('ps.instock_month',$params['instock_month'],'before',false);
        if(SetAndNotEmpty($params, 'accout_period_time_start')) $query->where('ps.accout_period_time >=',$params['accout_period_time_start'].' 00:00:00');
        if(SetAndNotEmpty($params, 'accout_period_time_end')) $query->where('ps.accout_period_time <=',$params['accout_period_time_end'].' 23:59:59');

        if(SetAndNotEmpty($params, 'trans_orderid')) $query->where('pa.trans_orderid',$params['trans_orderid']);
        if(SetAndNotEmpty($params, 'payer_time_start')) $query->where('pa.payer_time >=',substr($params['payer_time_start'],0,10).' 00:00:00');
        if(SetAndNotEmpty($params, 'payer_time_end')) $query->where('pa.payer_time <=',substr($params['payer_time_end'],0,10).' 23:59:59');

        //按付款状态查询
        if(SetAndNotEmpty($params, 'statement_pay_status')){
            if(defined('OAUTH_ACCESS')){// 门户系统付款状态查询需要转换
                $statement_pay_status = [];
                if(is_string($params['statement_pay_status'])){
                    $statement_pay_status[] = $params['statement_pay_status'];// 转成数组统一处理
                }else{
                    $statement_pay_status = $params['statement_pay_status'];// 转成数组统一处理
                }

                if (is_array($statement_pay_status) and count($statement_pay_status) == 4) {// 勾选全部查询枚举值

                }elseif(!in_array(PURCHASE_NUMBER_ZFSTATUS,$statement_pay_status)){// 不包含 等待支付
                    $query->where_in('ps.statement_pay_status', array_filter($statement_pay_status));
                }elseif(in_array(PURCHASE_NUMBER_ZFSTATUS,$statement_pay_status)){
                    // 包含 等待支付 改为状态枚举值较少的 NOT IN 查询
                    $not_pay_status = [];
                    if(!in_array(PAY_UNPAID_STATUS,$statement_pay_status)) $not_pay_status[] = PAY_UNPAID_STATUS;
                    if(!in_array(PART_PAID,$statement_pay_status)) $not_pay_status[] = PART_PAID;
                    if(!in_array(PAY_PAID,$statement_pay_status)) $not_pay_status[] = PAY_PAID;
                    $query->where_not_in('ps.statement_pay_status', $not_pay_status);
                }
            }else{
                if(is_array($params['statement_pay_status'])){
                    $query->where_in('ps.statement_pay_status',$params['statement_pay_status']);
                }else{
                    $query->where('ps.statement_pay_status',$params['statement_pay_status']);
                }
            }
        }

        if(SetAndNotEmpty($params, 'settlement_method')){
            if(is_array($params['settlement_method'])){
                $query->where_in('ps.settlement_method',$params['settlement_method']);
            }else{
                $query->where('ps.settlement_method',$params['settlement_method']);
            }
        }

        $query->group_by('ps.statement_number');

        $count_qb = clone $query;
        $cache_qb = clone $query;
        $gateway_statistics_qb = clone $query;
        $count_sql = $count_qb->select('ps.id,count(ps.id) as num')->get_compiled_select();
        $count_row = $count_qb->query("SELECT count(cc.id) as num FROM ($count_sql) AS cc")->row_array();
        $total = isset($count_row['num']) ? (int) $count_row['num'] : 0;
        if($action == 'sum'){
            return $total;
        }
        $this->rediss->setData(md5("statement_get_list_".getActiveUserId()),base64_encode($cache_qb->get_compiled_select()),3600);// 缓存查询SQL，便于做批量操作

        $result = $query->select($select_field)
            ->order_by('ps.id', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->result_array();

        $key_table = ['对账单号', '付款信息', '请款信息', '结算方式', '支付方式', '是否退税', '供应商',
            '是否作废', '操作人', '采购员', '付款状态', '是否上传扫描件', '创建时间', "是否包邮"];// 表头信息

        $this->load->model('supplier/supplier_settlement_model');
        $account_type = $this->supplier_settlement_model->get_code_by_name_list();

        $return_data = [
            'data_list' => [
                'value'         => $result,
                'key'           => $key_table,
                'drop_down_box' => [
                    'create_user_list'      => [],
                    'buyer_list'            => getBuyerDropdown(),
                    'getStatementPayStatus' => $gateWayPayStatus,
                    'getStatementPdfStatus' => getStatementPdfStatus(),
                    'is_drawback'           => getIsDrawback(),
                    'get_purchase_agent'    => get_purchase_agent(),
                    'is_file_uploaded'      => getCompactIsFileUploaded(),
                    'is_status_valid'       => ['1' => '正常', '2' => '作废'],
                    'is_esign_list'         => ['1' => '是', '2' => '否'],// 是否电子盖章
                    'is_purchasing'         => ['1' => '否','2' => '是'],//是否代采
                    'source_party_list'     => ['1' => '采购系统','2' => '门户系统'],//对账单来源
                    'status_list'           => getPurchaseStatementStatus(),
                    'free_shipping'         => getIsPostage(),// 是否包邮
                    'purchase_type'         => getPurchaseType(), //[1 => '国内仓', 2 => '海外', 3 => 'FBA', 4 => 'PFB', 5 => '平台头程']
                    'account_type'          => $account_type,
                ]
            ],
            'page_data' => [
                'total'  => $total,
                'offset' => $page,
                'limit'  => $limit,
                'pages'  => ceil($total / $limit)
            ]
        ];


        if($is_gateway){// 门户页面汇总数据

            // 统计页签头部显示的总数
            $page_index_count = $this->purchase_db->query("
                SELECT COUNT(1) AS num,
                CASE statement_pay_status
                WHEN ".PAY_PAID." THEN 'finished'
                ELSE 'unfinished' 
                END AS pay_finished_type 
                FROM pur_purchase_statement 
                WHERE status_valid=1 
                AND supplier_code='".$params['supplier_code']."'"
                ."GROUP BY pay_finished_type"
            )->result_array();
            $page_index_count = array_column($page_index_count,'num','pay_finished_type');


            $c_page_total_in_price = $c_page_total_pay_price = 0;
            foreach($result as $ss_vv){
                $c_page_total_in_price += $ss_vv['total_instock_price'];
                if($ss_vv['pay_status'] == PAY_PAID){
                    $c_page_total_pay_price += $ss_vv['pay_price'];
                }

            }
            $statistics_select = 'ps.statement_number,'
                .'ps.total_instock_price,'
                .'CASE pa.pay_status WHEN 51 THEN pa.pay_price ELSE 0  END AS pay_price';
            $statistics_sql = $gateway_statistics_qb->get_compiled_select();
            $statistics_sql = str_replace('SELECT *','SELECT '.$statistics_select,$statistics_sql);
            $statistics_res = $gateway_statistics_qb->query("SELECT count(1) as num,SUM(total_instock_price) AS total_in_price,SUM(pay_price) AS total_pay_price FROM ($statistics_sql) AS st_tmp")->row_array();

            $return_data['statistics_data'] = [
                'c_page_count' => count($result),
                'c_page_total_in_price' => (string)format_price($c_page_total_in_price),
                'c_page_total_pay_price' => (string)format_price($c_page_total_pay_price),
                'a_page_count' => $total,
                'a_page_total_in_price' => (string)format_price(isset($statistics_res['total_in_price'])?$statistics_res['total_in_price']:0),
                'a_page_total_pay_price' => (string)format_price(isset($statistics_res['total_pay_price'])?$statistics_res['total_pay_price']:0),
            ];

            $return_data['statistics_data'] = format_price_multi_floatval($return_data['statistics_data'],false,true);

            $return_data['tab_index_count'] = [
                'finished' => isset($page_index_count['finished'])?$page_index_count['finished']:0,
                'unfinished' => isset($page_index_count['unfinished'])?$page_index_count['unfinished']:0,
            ];
            unset($return_data['data_list']['drop_down_box']['create_user_list']);
            unset($return_data['data_list']['drop_down_box']['buyer_list']);
        }


        // 对账人 从历史数据和配置数据中取值
        $statement_user_list = $this->rediss->getData('statement_user_list');
        if(empty($statement_user_list)){
            $statement_user_list = $this->purchase_db->query("SELECT buyer_id,buyer_name
            FROM (
            SELECT buyer_id,buyer_name FROM pur_supplier_buyer WHERE `status`=1 AND buyer_type=10 GROUP BY buyer_id
            UNION 
            SELECT statement_user_id AS buyer_id,statement_user_name AS buyer_name FROM `pur_purchase_statement` GROUP BY statement_user_id
            ) AS utmp GROUP BY buyer_id")->result_array();
            $this->rediss->setData('statement_user_list',json_encode($statement_user_list),600);// 10分钟
        }else{
            $statement_user_list = json_decode($statement_user_list,true);
        }
        $return_data['data_list']['drop_down_box']['create_user_list'] = array_column($statement_user_list,'buyer_name','buyer_id');

        return $return_data;
    }


    /**
     * 数据格式化
     * @param $data_list
     * @return array
     * @author Jaxton 2019/01/18
     */
    public function format_compact_list($data_list){
        $this->load->helper('status_order');
        $this->load->model('supplier/supplier_settlement_model');
        $this->load->model('finance/Purchase_order_pay_model');

//        print_r($data_list);exit;
        if(!empty($data_list)){
            foreach($data_list as $key => $val){
                $statement_number = $val['statement_number'];

                // 展示最新一次申请请款信息
                $pay_record_latest = $this->purchase_db->select('id,requisition_number,pay_status,product_money,freight,discount,process_cost,commission,pay_price,payer_time')
                    ->where('pur_number', $statement_number)
                    ->order_by('id DESC')
                    ->limit(1)
                    ->get('purchase_order_pay')
                    ->row_array();

                if($pay_record_latest){
                    $data_list[$key]['in_pay_id'] = $pay_record_latest['id'];
                    $data_list[$key]['in_pay_requisition_number'] = $pay_record_latest['requisition_number'];
                    $data_list[$key]['in_pay_pay_status'] = getPayStatus($pay_record_latest['pay_status']);
                    $data_list[$key]['in_pay_product_money'] = $pay_record_latest['product_money'];
                    $data_list[$key]['in_pay_freight'] = $pay_record_latest['freight'];
                    $data_list[$key]['in_pay_discount'] = $pay_record_latest['discount'];
                    $data_list[$key]['in_pay_process_cost'] = $pay_record_latest['process_cost'];
                    $data_list[$key]['in_pay_commission'] = $pay_record_latest['commission'];
                    $data_list[$key]['in_pay_pay_price'] = $pay_record_latest['pay_price'];
                    $data_list[$key]['in_pay_payer_time'] = $pay_record_latest['payer_time'];
                }else{
                    $data_list[$key]['in_pay_id'] = null;
                    $data_list[$key]['in_pay_requisition_number'] = null;
                    $data_list[$key]['in_pay_pay_status'] = null;
                    $data_list[$key]['in_pay_product_money'] = null;
                    $data_list[$key]['in_pay_freight'] = null;
                    $data_list[$key]['in_pay_discount'] = null;
                    $data_list[$key]['in_pay_process_cost'] = null;
                    $data_list[$key]['in_pay_commission'] = null;
                    $data_list[$key]['in_pay_pay_price'] = null;
                    $data_list[$key]['in_pay_payer_time'] = null;
                }

                $settlement_model = $this->supplier_settlement_model->get_settlement_one($val['settlement_method']);// 结算费方式
                $data_list[$key]['settlement_method']     = isset($settlement_model['settlement_name']) ? $settlement_model['settlement_name'] : '';
                $data_list[$key]['pay_type'] = getPayType($val['pay_type']);
                $data_list[$key]['is_drawback'] = getIsDrawback($val['is_drawback']);
                $data_list[$key]['purchase_name'] = get_purchase_agent($val['purchase_name']);
                $data_list[$key]['status_valid'] = $val['status_valid'] == 1 ? '正常':'作废';
                $data_list[$key]['is_purchasing'] = $val['is_purchasing'] == 2?'是':'否';
                $data_list[$key]['status_valid'] = $val['status_valid'] == 1 ? '正常':'作废';
                $data_list[$key]['source_party_cn'] = $val['source_party'] == 1 ? '采购系统':'门户系统';
                $data_list[$key]['instock_month'] = substr($val['instock_month'],0,7);
                $data_list[$key]['free_shipping_cn'] = getIsPostage($val['free_shipping']);

                $data_list[$key]['pur_tran_num'] = isset($val['pur_tran_num']) ? $val['pur_tran_num'] :'';
                $data_list[$key]['trans_orderid'] = isset($val['trans_orderid']) ? $val['trans_orderid'] :'';
                $data_list[$key]['voucher_address'] = isset($val['voucher_address']) ? $val['voucher_address'] :'';

                // 获取对账单的所有采购员
                $statement_buyer_list = $this->purchase_db->select('buyer_name')
                    ->where('statement_number',$statement_number)
                    ->group_by('buyer_name')
                    ->get($this->table_name_items)
                    ->result_array();
                $data_list[$key]['buyer_name'] = array_column($statement_buyer_list,'buyer_name');

                // 是否允许上传扫描件
                $check_allow_upload = $this->check_allow_upload_statement_pdf($val['statement_pay_status'],$val['status'],false);
                if($check_allow_upload['code']){
                    $data_list[$key]['allow_upload_pdf'] = '1';// 允许上传
                }else{
                    $data_list[$key]['allow_upload_pdf'] = '0';// 不允许上传
                }

                if(defined('OAUTH_ACCESS')){
                    //门户系统状态
                    $gateWayPayStatus = [
                        PAY_UNPAID_STATUS           => '未申请付款',
                        PURCHASE_NUMBER_ZFSTATUS    => '等待支付',
                        PART_PAID                   => '已部分付款',
                        PAY_PAID                    => '已付款'
                    ];
                    $data_list[$key]['statement_pay_status'] = in_array($val['statement_pay_status'],array_keys($gateWayPayStatus))?$gateWayPayStatus[$val['statement_pay_status']]:'等待支付';
                }else{
                    $data_list[$key]['statement_pay_status'] = getStatementPayStatus($val['statement_pay_status']);
                }

                $data_list[$key]['statement_pdf_status_int'] = $val['statement_pdf_status'];
                $data_list[$key]['statement_pdf_status']     = getStatementPdfStatus($val['statement_pdf_status']);
                $data_list[$key]['status_cn']                = getPurchaseStatementStatus($val['status']);

                // 总已付商品金额
                $paid_price_list                     = $this->Purchase_order_pay_model->get_pay_total_by_compact_number($statement_number);
                $data_list[$key]['total_paid_price'] = isset($paid_price_list['product_money']) ? $paid_price_list['product_money'] : '';

                $data_list[$key]['total_real_pay_price'] = format_price($val['total_pay_price'] + $val['total_freight'] + $val['total_process_cost'] + $val['total_loss_product_money'] - $val['total_discount']);

                //是否展示查看上传操作pdf日志链接(“40.供应商已上传”时，可点击日志链接)
                $data_list[$key]['enable_log_link'] = isset($val['statement_pdf_status']) && $val['statement_pdf_status'] == 40 ? 1 : 0;

                if($val['statement_pay_status'] == PAY_PAID){// 已付金额
                    $data_list[$key]['real_paid_price'] = $data_list[$key]['in_pay_pay_price'];
                }else{
                    $data_list[$key]['real_paid_price'] = 0;
                }

                if(empty($val['attachmentPathEsign'])){// 没有签署文件时显示上传的附属文件
                    $data_list[$key]['attachmentPathEsign'] = $val['attachmentPath'];
                }

                // 是否电子盖章
                if(!empty($val['flowId'])){
                    $data_list[$key]['is_esign'] = '是';
                }else{
                    $data_list[$key]['is_esign'] = '否';
                }
            }
        }
        return $data_list;
    }

    /**
     * 设置 对账单 是否有效
     * @param $statement_number
     * @param $status_valid
     * @return array
     */
    public function set_status_valid($statement_number, $status_valid = 2)
    {
        $return = ['code' => false, 'message' => '获取失败', 'data' => []];

        $this->purchase_db->trans_begin();
        try {
            $this->load->model('finance/purchase_order_pay_model');
            $result = $this->purchase_order_pay_model->verify_order_status_enable_pay($statement_number);
            if ($result !== true) {
                $return['message'] = '对账单号：' . $statement_number . ' 对账单存在未完结的请款单[' . $result . ']';
                return $return;
            }

            $have_paid_data = $this->purchase_order_pay_model->get_pay_total_by_compact_number($statement_number);
            if ($have_paid_data and $have_paid_data['pay_price'] > 0) {
                $return['message'] = '对账单号：' . $statement_number . ' 已经付过款了';
                return $return;
            }

            $statementInfo = $this->get_statement($statement_number);
            if ($statementInfo['status_valid'] == 2) {
                $return['message'] = '对账单号：' . $statement_number . ' 已经是作废状态了';
                return $return;
            }
            if ($statementInfo['statement_pay_status'] == PAY_PAID or $statementInfo['statement_pay_status'] == PART_PAID) {
                $return['message'] = '对账单号：' . $statement_number . ' 已经付过款了';
                return $return;
            }

            $instock_batchs = array_column($statementInfo['items_list'], 'instock_batch');

            // 直接删除对账单
            $this->purchase_db->where('statement_number', $statement_number)->delete($this->table_name);
            $this->purchase_db->where('statement_number', $statement_number)->delete($this->table_name_items);
            $this->purchase_db->where('statement_number', $statement_number)->delete($this->table_name_summary);
            $this->purchase_db->where('link_number', $statement_number)->delete($this->table_statement_note);
            //更新入库明细创建对账单状态和对账单号
            $this->purchase_db->where_in('instock_batch', $instock_batchs)
                ->update('statement_warehouse_results', ['created_statement' => 1, 'statement_number' => '']);

            // //对接门户系统的对账单，需推送状态给门户系统
            // if($statementInfo['supplier_is_gateway'] && $statementInfo['is_push_gateway']){
            //     $post_data = [
            //         'statementNumber' => $statement_number,
            //         'supplierFile' => '',
            //         'auditUser' => getActiveUserName(),
            //         'result' => 3,//作废
            //     ];
            //     $_result = $this->_post_gateway_api($post_data, 'push_statement_result');
            //     if (!isset($_result['code']) or !$_result['code'] == 200 or empty($_result['data'])) {
            //         //写入操作日志表
            //         $this->_insert_mongodb_log($statement_number, '作废对账单推送门户系统失败', ['push_data' => $post_data, 'result_data' => $_result],'PUR_STATEMENT_PUSH_GATEWAY_RESULT');
            //         //推送失败的处理
            //         throw new Exception('作废对账单推送门户系统失败');
            //     } else {
            //         //写入操作日志表
            //         $this->_insert_mongodb_log($statement_number, '作废对账单推送门户系统成功', ['push_data' => $post_data, 'result_data' => $_result],'PUR_STATEMENT_PUSH_GATEWAY_RESULT');
            //     }
            // }

            $this->purchase_db->trans_commit();

            $return['code'] = true;
            $return['message'] = '恭喜，对账单作废成功';
        } catch (Exception $e) {
            $this->purchase_db->trans_rollback();
            $return = ['code' => false, 'message' => $e->getMessage(), 'data' => []];
        }

        return $return;
    }

    /**
     * 判断是否允许上传扫描件
     * @param $statement_pay_status
     * @param $status
     * @param bool $sign_type
     * @return array
     */
    public function check_allow_upload_statement_pdf($statement_pay_status,$status,$sign_type = false){
        $allow_pay_status = [// 允许上传的付款状态（请款终止）
            // 5,
            PAY_UNPAID_STATUS,
            PAY_SOA_REJECT,
            PAY_MANAGER_REJECT,
            PAY_WAITING_MANAGER_REJECT,
            PAY_FINANCE_REJECT,
            PAY_REJECT_SUPERVISOR,
            PAY_REJECT_MANAGER,
            PAY_REJECT_SUPPLY,
            PAY_GENERAL_MANAGER_REJECT
        ];

        $allow_not_status = [
            STATEMENT_STATUS_WAIT_PURCHASE_AUDIT,
            STATEMENT_STATUS_WAIT_PARTY_B_AUDIT
        ];

        // 线上签署中
        if($sign_type === false and in_array($status,$allow_not_status)){
            return $this->res_data(false,'对账单已在签署中，不允许上传');
        }

        // 付款中
        if (!in_array($statement_pay_status,$allow_pay_status)) {// 在未请款或请款驳回状态下才能上传扫描件
            return $this->res_data(false,'对账单在请款中，已上传扫描件，不允许上传');
        }

        return $this->res_data(true);
    }

    /**
     * 上传扫描件\同步到供应商门户系统
     * @param string $statement_number 对账单号
     * @param string $pdf_file_name PDF文件名，带后缀
     * @param string $pdf_url PDF文件路径（JAVA DFS 路径）
     * @param bool   $sign_type 签署方式（true.为e签宝线上签署，false.为线下签署）
     * @return array
     */
    public function upload_statement_pdf($statement_number, $pdf_file_name, $pdf_url,$sign_type = false)
    {
        $return = ['code' => false, 'message' => '获取失败', 'data' => []];
        $this->purchase_db->trans_begin();
        try {
            //验证是否可以上传合同扫描件
            $statementInfo = $this->get_statement($statement_number, false);

            // 是否允许上传扫描件
            $check_res = $this->check_allow_upload_statement_pdf($statementInfo['statement_pay_status'],$statementInfo['status'],$sign_type);
            if($check_res['code'] === false){
                $return['message'] = $check_res['message'];
                return $return;
            }

            if(stripos($pdf_file_name,$statement_number) !== 0){
                $return['message'] = '扫描文件必须以对账单号命名且是图片或PDF格式';
                return $return;
            }

            $update_data = [
                'statement_pdf_file_name' => $pdf_file_name,
                'statement_pdf_url' => $pdf_url,
                'statement_pdf_status' => 30,
                'statement_pdf_time' => date('Y-m-d H:i:s'),
            ];
            $result = $this->purchase_db->where('statement_number', $statement_number)->update($this->table_name, $update_data);
            if(empty($result)){
                throw new Exception('对账单上传扫描件状态更新失败');
            }
            if($sign_type){// 线上签署完成
                $result = $this->change_statement_status($statement_number,STATEMENT_STATUS_SIGN_OFFLINE);
            }else{// 线下签署完成
                $result = $this->change_statement_status($statement_number,STATEMENT_STATUS_SIGN_ONLINE);
            }
            if(empty($result['code'])){
                throw new Exception($result['message']);
            }

            // 添加日志
            $this->Purchase_statement_note_model->add_remark([$statement_number], 1,$pdf_url, '上传扫描件');
            if ($this->purchase_db->trans_status() === FALSE) {
                throw new Exception('上传失败');
            }

            // //对接门户系统的对账单，需推送状态给门户系统
            // if ($statementInfo['supplier_is_gateway'] && $statementInfo['is_push_gateway']) {
            //     //推送状态给门户系统
            //     $post_data = [
            //         'statementNumber' => $statement_number,
            //         'supplierFile' => $pdf_url,
            //         'auditUser' => getActiveUserName(),
            //         'result' => 1,
            //     ];
            //     $_result = $this->_post_gateway_api($post_data, 'push_statement_result');
            //     if (!isset($_result['code']) or !$_result['code'] == 200 or empty($_result['data'])) {
            //         //写入操作日志表
            //         $this->_insert_mongodb_log($statement_number, '上传扫描件推送门户系统失败', ['push_data' => $post_data, 'result_data' => $_result], 'PUR_STATEMENT_PUSH_GATEWAY_RESULT');
            //         //推送失败的处理
            //         throw new Exception('上传扫描件推送门户系统失败');
            //     } else {
            //         //写入操作日志表
            //         $this->_insert_mongodb_log($statement_number, '上传扫描件推送门户系统成功', ['push_data' => $post_data, 'result_data' => $_result], 'PUR_STATEMENT_PUSH_GATEWAY_RESULT');
            //     }
            // }

            $return = ['code' => true, 'message' => '上传成功', 'data' => []];

            $this->purchase_db->trans_commit();
        } catch (Exception $e) {
            $this->purchase_db->trans_rollback();
            $return = ['code' => false, 'message' => $e->getMessage(), 'data' => []];
        }
        return $return;
    }

    /**
     * e签宝附属文件（JAVA地址）
     * @param string $statement_number 对账单号
     * @param string $pdf_url PDF文件路径（JAVA DFS 路径）
     * @return array
     */
    public function upload_attachmentFile_pdf($statement_number, $pdf_url)
    {
        $return = ['code' => false, 'message' => '获取失败', 'data' => []];
        try {

            $update_data = [
                'attachmentPathEsign' => $pdf_url,
            ];
            $result = $this->purchase_db->where('statement_number', $statement_number)->update($this->table_name, $update_data);
            if(empty($result)){
                throw new Exception('对账单上传扫描件状态更新失败');
            }

            $return = ['code' => true, 'message' => '上传成功', 'data' => []];

        } catch (Exception $e) {
            $return = ['code' => false, 'message' => $e->getMessage(), 'data' => []];
        }
        return $return;
    }


    /**
     * 更新对账单-支付状态（对账单支付状态同步至采购单支付状态，两者保持一致，自动更新）
     * @author Jolon
     * @param string $statement_number  对账单号
     * @param int $new_status 目的状态
     * @param string $payer_time 付款时间
     * @return bool
     * @throws Exception
     */
    public function change_statement_pay_status($statement_number,$new_status,$payer_time = null){
        if(empty($new_status)){
            throw new Exception('对账单支付状态更新-状态错误');
        }

        $statement = $this->get_statement($statement_number);
        if(empty($statement) or empty($statement['items_list'])){
            throw new Exception('对账单或对账单明细不存在');
        }

        // 该逻辑有多步操作
        // 1、更新对账单付款状态
        $result = $this->purchase_db->where('statement_number',$statement_number)
            ->update($this->table_name,['statement_pay_status' => $new_status]);

        if($result){
            // //对接门户系统，并且已经推送门户系统，的部分付款和已付款的对账单，存入redis,待推送门户系统
            // if (in_array($new_status, [PART_PAID, PAY_PAID]) && $statement['supplier_is_gateway'] && $statement['is_push_gateway']) {
            //     $this->rediss->lpushData('PUSH_STATEMENT_PAY_STATUS_TO_GATEWAY', ['statement_number' => $statement_number, 'pay_status' => PART_PAID == $new_status ? 2 : 3]);
            // }

            // 记录操作日志
            $old_status_text = getStatementPayStatus($statement['statement_pay_status']);
            $new_status_text = getStatementPayStatus($new_status);
            $detail          = "修改支付状态，从[{$old_status_text}]改为[{$new_status_text}]";
            operatorLogInsert(['id' => $statement_number,'type' => $this->table_name,'content' => '对账单支付状态','detail' => $detail]);

            // 2、采购单支付状态
            $items_list           = $statement['items_list'];
            $purchase_number_list = array_unique(array_column($items_list, 'purchase_number'));

            if($new_status == PAY_PAID or $new_status == PART_PAID){// 根据已付商品额 计算采购单实际付款状态
                $this->load->model('finance/purchase_order_pay_model');
                $this->load->model('compact/compact_model');
                $this->load->library('Rabbitmq');
                foreach($purchase_number_list as $purchase_number){// 根据 采购单：采购金额、已付金额、取消金 计算付款状态
                    try{
                        $this->purchase_order_pay_model->calculate_purchase_order_pay_status($purchase_number,$payer_time);
                    }catch(Exception $exception){
                        throw new Exception('采购单支付状态变更失败');
                    }
                }

                $this->purchase_order_pay_model->update_order_items_paid_price_detail($purchase_number_list,'commission');// 采购单请款金额明细更细到采购单明细表上（申请中、已付款状态下）

                // 更新合同金额明细
                $compact_number_list = $this->purchase_db->select('compact_number')
                    ->where_in('purchase_number',$purchase_number_list)
                    ->group_by('compact_number')
                    ->get('purchase_compact_items')
                    ->result_array();
                foreach($compact_number_list as $compact_value){
                    $this->compact_model->refresh_compact_data($compact_value['compact_number']);
                }
                
                // 3、对账单的入库批次全部抵冲、入库批次的剩余可冲销金额=0
                foreach($items_list as $item_value){
                    $purchase_number        = $item_value['purchase_number'];
                    $instock_batch          = $item_value['instock_batch'];
                    $charge_against_product = $item_value['pay_product_money'];
                    $charge_against_amount  = $item_value['pay_product_money'];
                    $s_w_r_id               = $item_value['s_w_r_id'];

                    // 3.1、对账单的入库批次全部抵冲
                    $result = $this->Charge_against_records_model->createInventoryItemChargeAgainstRecords($purchase_number,$instock_batch,$charge_against_product,$charge_against_amount);
                    if(empty($result)){
                        throw new Exception('对账单的入库单生成抵冲记录失败');
                    }

                    // 3.2、入库批次的剩余可冲销金额=0
                    $update_data = ['charge_against_status' => 3, 'surplus_charge_against_amount' => 0];
                    $this->purchase_db->where('id', $s_w_r_id)->update('statement_warehouse_results', $update_data, null, 1);
                }

                // 4、重新计算 采购单冲销结余，更新冲销状态
                // 采购单状态变更需要刷新冲销汇总
                $mq = new Rabbitmq();//创建消息队列对象
                $mq->setQueueName('STATEMENT_CHANGE_PAY_STATUS_REFRESH');//设置参数
                $mq->setExchangeName('STATEMENT_CHANGE_PAY_STATUS');//构造存入数据
                $mq->setRouteKey('SO_REFRESH_FOR_003');
                $mq->setType(AMQP_EX_TYPE_DIRECT);
                $mq->sendMessage(['purchase_number' => $purchase_number_list,'add_time' => time()]);// 保持格式一致

            }else{
                $data_pay_status = ['pay_status' => $new_status];
                foreach($purchase_number_list as $purchase_number){
                    $result1 = $this->purchase_db->where('purchase_number',$purchase_number)
                        ->update('purchase_order',$data_pay_status);
                    if(!$result1){
                        throw new Exception('采购单支付状态变更失败');
                    }else{
                        operatorLogInsert(['id' => $purchase_number,'type' => 'purchase_order','content' => '采购单支付状态','detail' => $detail]);
                    }
                }
            }

        }else{
            throw new Exception('对账单支付状态变更失败');
        }

        // 付款状态 推送至计划系统
        if (!empty($items_list)){
            $po_list = array_unique(array_column($items_list,'purchase_number'));
            foreach ($po_list as $purchase_number){
                $push_pay_status_to_plan_key = 'push_pay_status_to_plan';
                $this->rediss->set_sadd($push_pay_status_to_plan_key,$purchase_number);
                $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$push_pay_status_to_plan_key);
            }
        }

        return true;
    }


    /**
     * 更新对账单状态 并变更记录日志
     * @param string $statement_number 对账单号
     * @param string $new_status 对账单目标状态
     * @param string $flowStatus e签宝签署流程状态（e签宝平台状态值）
     * @param array  $operatorUser e签宝签署流程操作用户
     * @return array
     */
    public function change_statement_status($statement_number,$new_status,$flowStatus = null,$operatorUser = []){

        $statementInfo = $this->get_statement($statement_number,false);
        $operatorMsgList = [
            1 => '发起盖章',
            2 => '催办电子对账单',
            3 => '撤回电子对账单',
            4 => '乙方盖章',
            5 => '对账人审核',
            6 => '批量盖章',
            7 => '对账单申请付款',
            8 => '对账单自动申请付款'
        ];

        switch ($new_status){
            case STATEMENT_STATUS_WAIT_PARTY_B_AUDIT:
                $operatorMsg = $operatorMsgList[1];
                break;

            case STATEMENT_STATUS_RECALL:
                if($statementInfo['firstSignParty'] == 1){// 已撤销，发起方是甲方
                    $operatorMsg = $operatorMsgList[3];
                }else{// 已撤销，发起方是乙方
                    $operatorMsg = $operatorMsgList[3];
                }
                break;

            case STATEMENT_STATUS_WAIT_PURCHASE_AUDIT:// 乙方盖章变成待采购审核
                $operatorMsg = $operatorMsgList[4];
                break;

            case STATEMENT_STATUS_PURCHASE_REJECT:// 采购审核
                $operatorMsg = $operatorMsgList[5];
                break;

            case STATEMENT_STATUS_SIGN_OFFLINE:
                if($statementInfo['firstSignParty'] == 1){// 签署完成，发起方是甲方
                    $operatorMsg = $operatorMsgList[4];// 最后签署的是乙方
                }else{
                    $operatorMsg = $operatorMsgList[6];// 最后签署的是甲方
                }
                break;

            default:
                $operatorMsg = '';
        }

        $update_arr = ['status' => $new_status];
        if(!is_null($flowStatus))  $update_arr['flowStatus'] = $flowStatus;

        $res = $this->purchase_db->where('statement_number',$statement_number)
            ->update($this->table_name,$update_arr);

        if($res){
            if($operatorMsg){
                $user_id = isset($operatorUser['user_id'])?$operatorUser['user_id']:null;
                $user_name = isset($operatorUser['user_name'])?$operatorUser['user_name']:null;
                $create_time = isset($operatorUser['create_time'])?$operatorUser['create_time']:null;
                $this->Purchase_statement_note_model->add_remark($statement_number, 1, $operatorMsg, $operatorMsg,$user_id,$user_name,$create_time);
            }

            return $this->res_data(true);

        }else{
            return $this->res_data(false,'对账单状态变更失败');
        }

    }

    /**
     * 采购确认扫描件，并推送到门户系统
     * @param string $statement_number 对账单号
     * @param string $audit_status 审核状态（agree-审核通过，disagree-驳回）
     * @param string $audit_remark 备注
     * @return array
     */
    public function confirm_statement_pdf($statement_number, $audit_status, $audit_remark)
    {
        $return = ['code' => false, 'message' => '获取对账单数据失败', 'data' => []];
        $this->purchase_db->trans_begin();
        try {
            //验证是否可以上传合同扫描件
            $statementInfo = $this->get_statement($statement_number, false);
            if (empty($statementInfo)) {
                return $return;
            } elseif ($statementInfo['statement_pdf_status'] != 20) {
                $return['message'] = '“待采购确认”状态下才允许操作';
                return $return;
            } elseif (!$statementInfo['supplier_is_gateway']) {
                $return['message'] = '对接门户系统的对账单才允许操作';
                return $return;
            }

            if ('agree' == $audit_status) {
                $audit_status = 40;
                $audit_remark = empty($audit_remark) ? '审核通过' : '审核通过:' . $audit_remark;
                $gateway_result = 1;//同意
            } else {
                $audit_status = 25;
                $audit_remark = '驳回:' . $audit_remark;
                $gateway_result = 2;//驳回
            }

            //更新扫描件状态
            $update_data = [
                'statement_pdf_status' => $audit_status
            ];
            $result = $this->purchase_db->where('statement_number', $statement_number)->update($this->table_name, $update_data);
            if(empty($result)){
                throw new Exception('对账单上传扫描件状态更新失败');
            }
            if($update_data['statement_pdf_status'] == 40){
                $result = $this->change_statement_status($statement_number,STATEMENT_STATUS_SIGN_ONLINE);
                if(empty($result['code'])){
                    throw new Exception($result['message']);
                }
            }

            if ($this->purchase_db->trans_status() === FALSE) {
                throw new Exception('确认失败');
            }

            // 添加日志
            $this->Purchase_statement_note_model->add_remark($statement_number, 1, $audit_remark, '采购确认扫描件');


            // //推送状态给门户系统
            // $post_data = [
            //     'statementNumber' => $statement_number,
            //     'supplierFile' => '',
            //     'auditUser' => getActiveUserName(),
            //     'result' => $gateway_result,
            // ];
            // $_result = $this->_post_gateway_api($post_data, 'push_statement_result');
            // if (!isset($_result['code']) or !$_result['code'] == 200 or empty($_result['data'])) {
            //     //写入操作日志表
            //     $this->_insert_mongodb_log($statement_number, '采购确认扫描件推送门户系统失败', ['push_data' => $post_data, 'result_data' => $_result], 'PUR_STATEMENT_PUSH_GATEWAY_RESULT');
            //     //推送失败的处理
            //     throw new Exception('推送门户系统失败');
            // } else {
            //     //写入操作日志表
            //     $this->_insert_mongodb_log($statement_number, '采购确认扫描件推送门户系统成功', ['push_data' => $post_data, 'result_data' => $_result], 'PUR_STATEMENT_PUSH_GATEWAY_RESULT');
            // }

            $return = ['code' => true, 'message' => '确认成功', 'data' => []];

            $this->purchase_db->trans_commit();
        } catch (Exception $e) {
            $this->purchase_db->trans_rollback();
            $return = ['code' => false, 'message' => $e->getMessage(), 'data' => []];
        }
        return $return;
    }

    /**
     * 推送对账单数据到门户系统（定时任务）
     * @return array
     */
    public function push_statement_to_gateway_cron()
    {
        try {
            $this->purchase_db->select('a.statement_number,a.purchase_name,a.supplier_code,a.supplier_name,a.settlement_method,a.pay_type,a.is_drawback,a.create_user_name');
            $this->purchase_db->select('b.purchase_number,b.compact_number,b.warehouse_code,b.warehouse_name,b.buyer_id,b.buyer_name,b.sku,b.product_name,b.instock_qty,b.purchase_unit_price,b.currency');
            $this->purchase_db->from("{$this->table_name} a");
            $this->purchase_db->join("{$this->table_name_items} b", 'a.statement_number = b.statement_number');
            $this->purchase_db->where(['supplier_is_gateway' => 1, 'is_push_gateway' => 0]);
            $statement_data = $this->purchase_db->get()->result_array();
            if (empty($statement_data)) {
                return ['code' => false, 'msg' => '没有可推送的对账单数据', 'data' => []];
            }

            $data_main = [];//主表数据
            $data_items_list = [];//明细数据
            foreach ($statement_data as $item) {
                if (empty($data_main[$item['statement_number']])) {
                    $data_main[$item['statement_number']] = [
                        'statement_number' => $item['statement_number'],
                        'purchase_name' => $item['purchase_name'],
                        'supplier_code' => $item['supplier_code'],
                        'supplier_name' => $item['supplier_name'],
                        'settlement_method' => $item['settlement_method'],
                        'pay_type' => $item['pay_type'],
                        'is_drawback' => $item['is_drawback'],
                        'create_user_name' => $item['create_user_name'],
                        'file_name' => '',
                        'file_url' => ''
                    ];
                }

                $data_items_list[$data_main[$item['statement_number']]['statement_number']][] = [
                    'statement_number' => $item['statement_number'],
                    'purchase_number' => $item['purchase_number'],
                    'compact_number' => $item['compact_number'],
                    'warehouse_code' => $item['warehouse_code'],
                    'warehouse_name' => $item['warehouse_name'],
                    'buyer_id' => $item['buyer_id'],
                    'buyer_name' => $item['buyer_name'],
                    'sku' => $item['sku'],
                    'product_name' => $item['product_name'],
                    'instock_qty' => $item['instock_qty'],
                    'purchase_unit_price' => $item['purchase_unit_price'],
                    'currency' => $item['currency']
                ];
            }

            // //生成对账单pdf，并推送门户系统
            // foreach ($data_main as &$item) {
            //     //生成对账pdf
            //     $file_data = $this->_create_statement_pdf($item['statement_number']);
            //     if ($file_data['code']) {
            //         $item['file_url'] = $file_data['file_path'];//pdf路径
            //         $item['file_name'] = $file_data['file_name'];//pdf名称
            //         //推送门户系统
            //         $this->_push_statement_to_gateway($item, $data_items_list[$item['statement_number']]);
            //     }
            // }

            $return = ['code' => true, 'msg' => '推送成功', 'data' => array_column($data_main, 'statement_number')];

        } catch (Exception $e) {
            $return = ['code' => false, 'msg' => $e->getMessage(), 'data' => []];
        }
        return $return;
    }

    /**
     * 推送对账单付款状态到门户系统（定时任务）
     * @return array
     */
    public function push_pay_status_to_gateway_cron()
    {
        try {
            $len = $this->rediss->llenData('PUSH_STATEMENT_PAY_STATUS_TO_GATEWAY');
            if (!$len) {
                return ['code' => false, 'msg' => '没有可推送的对账单数据', 'data' => []];
            }
            $statement_number = [];
            //循环处理Redis数据
            while (1) {
                $statement_data = $this->rediss->rpopData('PUSH_STATEMENT_PAY_STATUS_TO_GATEWAY');
                if (!empty($statement_data) && is_array($statement_data)) {
                    $post_data = [
                        'statementNumber' => $statement_data['statement_number'],
                        'payStatus' => $statement_data['pay_status']
                    ];
                    $_result = $this->_post_gateway_api($post_data, 'push_statement_pay_status');
                    if (!isset($_result['code']) or !$_result['code'] == 200 or empty($_result['data'])) {
                        //写入操作日志表
                        $this->_insert_mongodb_log($statement_data['statement_number'], '对账单付款状态推送门户系统失败', ['push_data' => $post_data, 'result_data' => $_result], 'PUR_STATEMENT_PUSH_GATEWAY_PAY_STATUS');
                        //再次推入redis的数据
                        $push_again_data[] = ['statement_number' => $statement_data['statement_number'], 'pay_status' => $statement_data['pay_status']];
                        //推送失败的处理
                        throw new Exception('对账单付款状态推送门户系统失败');
                    } else {
                        //写入操作日志表
                        $this->_insert_mongodb_log($statement_data['statement_number'], '对账单付款状态推送门户系统成功', ['push_data' => $post_data, 'result_data' => $_result], 'PUR_STATEMENT_PUSH_GATEWAY_PAY_STATUS');
                        //推送成功的对账单号
                        $statement_number[] = $statement_data['statement_number'];
                    }
                }
                //判断数据是否处理完成
                $len = $this->rediss->llenData('SUPPLIER_CROSS_BORDER');
                if (!$len) {
                    break;
                }
                //延迟0.1秒
                usleep(100000);
            }
            $return = ['code' => true, 'msg' => '推送成功', 'data' => $statement_number];
        } catch (Exception $e) {
            $return = ['code' => false, 'msg' => $e->getMessage(), 'data' => []];
        }
        if (!empty($push_again_data)) {
            foreach ($push_again_data as $item) {
                //存入redis,待推送门户系统
                $this->rediss->lpushData('PUSH_STATEMENT_PAY_STATUS_TO_GATEWAY', ['statement_number' => $item['statement_number'], 'pay_status' => $item['pay_status']]);
            }
        }
        return $return;
    }

    /**
     * 查找采购单 是否在指定时间之前有对账单
     * @param $purchase_numbers
     * @param $create_time
     * @return array|bool
     */
    public function getHistoryStatementNumber($purchase_numbers,$create_time){
        $result = $this->purchase_db->select('SUM.purchase_number,PS.statement_number')
            ->from($this->table_name .' AS PS')
            ->join($this->table_name_summary .' AS SUM','PS.statement_number=SUM.statement_number','INNER')
            ->where('PS.create_time <',$create_time)
            ->where_in('SUM.purchase_number',$purchase_numbers)
            ->where('PS.status_valid',1)
            ->get()
            ->result_array();

        if(empty($result)) return [];

        return arrayKeyToColumn($result,'purchase_number');
    }

    /**
     * 下载付款申请书
     * @return array|bool
     */
    public function get_statement_pay_requisition($ids){
        if($ids) $ids = explode(',',$ids);
        if($ids){
            $query_sql_res = $this->purchase_db->select("statement_number")->where_in('id',$ids)->get($this->table_name)->result_array();
            if(empty($query_sql_res)) return '数据获取失败，请核对数据后操作';
            $statement_number_list = array_column($query_sql_res,'statement_number');
        }else{
            $query_sql = $this->rediss->getData(md5("statement_get_list_".getActiveUserId()));
            if(empty($query_sql)) return '数据获取失败，请核对数据后操作';
            $query_sql = base64_decode($query_sql);
            $query_sql_res = $this->purchase_db->query($query_sql.' LIMIT 100')->result_array();
            if(empty($query_sql_res)) return '数据获取失败，请核对数据后操作';
            $statement_number_list = array_column($query_sql_res,'statement_number');
        }
        if(count($statement_number_list) > 100) return '下载数据过多，请勿超过100个对账单';

        // 展示最新一次申请请款信息
        $pay_record_latest = $this->purchase_db->select('max(id) as id')
            ->where_in('pur_number', $statement_number_list)
            ->order_by('id DESC')
            ->group_by('pur_number')
            ->get('purchase_order_pay')
            ->result_array();
        if(empty($pay_record_latest)) return false;
        $pay_record_latest_ids = array_column($pay_record_latest,'id');
        $pay_record_latest = $this->purchase_db->select('id,requisition_number,pur_number')
            ->where_in('id', $pay_record_latest_ids)
            ->get('purchase_order_pay')
            ->result_array();
        
        $success_list = $error_list = [];
        $this->load->model('compact/Compact_list_model','compact_model');
        foreach($pay_record_latest as $value){
            try{
                $result = $this->compact_model->get_pay_requisition($value['pur_number'],$value['requisition_number']);

                if($result['success'] && $result['data']){
                    $data= urlencode(json_encode($result['data'],JSON_UNESCAPED_UNICODE));
                    if(is_array($data)){
                        throw new Exception('付款申请书数据解析失败');
                    }
                    $print_data = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_payment_apply');
                    if(is_array($print_data)){
                        throw new Exception('付款申请书模板地址错误');
                    }
                    $url  = $print_data."?data=".$data;
                    $html = file_get_contents($url);

                    $success_list[$value['pur_number']] = $html;
                }else{
                    $error_list[] = $value['pur_number'].'：未获取到数据';
                }
            }catch(Exception $e){
                $error_list[] = $value['pur_number'].'：'.$e->getMessage();
            }
        }

        return ['success_list' => $success_list,'error_list' => $error_list];
    }



    //region e签宝电子合同签署流程
    /**
     * 发起签署流程（发起盖章）
     * @param $statement_number
     * @return array
     */
    public function initiator_start_flow($statement_number){
        $this->load->library('esign/ESignFlows');
        $this->load->model('supplier/supplier_model');

        $statementInfo = $this->get_statement($statement_number,false);

        // 已生成对账单、采购驳回、乙方驳回、已撤回，且是否上传扫描件的状态=未上传、采购驳回
        if( !in_array($statementInfo['status'],[STATEMENT_STATUS_IS_CREATE, STATEMENT_STATUS_PURCHASE_REJECT, STATEMENT_STATUS_PARTY_B_REJECT, STATEMENT_STATUS_RECALL])){
            return $this->res_data(false,'状态为已生成对账单|采购驳回|乙方驳回|已撤回才允许操作');
        }
        if(!in_array($statementInfo['statement_pdf_status'],[10,25])){
            return $this->res_data(false,'是否上传扫描件的状态=未上传|采购驳回才允许操作');
        }
        if(in_array($statementInfo['flowStatus'],[1,2])){
            return $this->res_data(false,'请等待乙方签署盖章，或点击催办！');
        }

        if(empty($statementInfo['filePath'])){
            return $this->res_data(false,'对账单文件正在生成中，请稍后再发起盖章。若2分钟还没有变化，请重新点击查询。');
        }

        // 附属文件
        $supplierInfo = $this->supplier_model->get_supplier_info($statementInfo['supplier_code'],false);
        if(empty($statementInfo['attachmentPath']) and $supplierInfo){
            if($supplierInfo['is_postage'] == 2 or $supplierInfo['is_postage'] == 3){
                return $this->res_data(false,'是否包邮＝不包邮/部分包邮时需要先上传附属文件');
            }
        }

        // 验证甲方联系人
        $AAAUserData = $this->_A_user_info($statementInfo['statement_user_id']);
        if($AAAUserData['code'] == false){
            return $this->res_data(false,$AAAUserData['message']);
        }
        // 验证乙方联系人
        $BBBUserData = $this->_B_user_info($statementInfo['supplier_code']);
        if($BBBUserData['code'] == false){
            return $this->res_data(false,$BBBUserData['message']);
        }

        $result = $this->esignflows->createFlowOneStep($statement_number,$statementInfo['supplier_code'],$statementInfo['instock_month'],$statementInfo['filePath'],$statementInfo['purchase_name'],$statementInfo['attachmentPath']);
        if($result['code'] == true){

            $result_data = $result['data'];
            $updateArr = [
                'firstSignParty' => '1',// 甲方发起
                'fileId' => isset($result_data['fileId'])?$result_data['fileId']:'',
                'flowId' => isset($result_data['flowId'])?$result_data['flowId']:'',
                'signShortUrl' => isset($result_data['signShortUrl'])?$result_data['signShortUrl']:'',
                'otherSignShortUrl' => isset($result_data['otherSignShortUrl'])?$result_data['otherSignShortUrl']:'',
                'attachmentFileId' => isset($result_data['subsidiaryFileId'])?$result_data['subsidiaryFileId']:'',
            ];
            if(!empty($updateArr['signShortUrl'])){// 更新签署数据
                $this->purchase_db->where('statement_number',$statement_number)
                    ->update($this->table_name,$updateArr);

                $this->change_statement_status($statement_number,STATEMENT_STATUS_WAIT_PARTY_B_AUDIT,1);

                return $this->res_data(true,'发起盖章成功',$updateArr['signShortUrl']);
            }else{
                return $this->res_data(false,'发起盖章失败[签署链接 data.signShortUrl 缺失]');
            }
        }else{
            return $this->res_data(false,'发起盖章失败：'.$result['errorMsg']);
        }

    }


    /**
     *
     * 签署合同（盖章）
     * @param $statement_number
     * @return array
     */
    public function signfields_flow($statement_number){
        $this->load->library('esign/ESignFlows');

        $statementInfo = $this->get_statement($statement_number,false);
        $this->Purchase_statement_note_model->add_remark($statement_number, 1, '批量盖章', '批量盖章');

        if(!empty($statementInfo['signShortUrl'])){
            return $this->res_data(true,'盖章成功',$statementInfo['signShortUrl']);
        }else{
            return $this->res_data(false,'盖章失败[签署链接 data.signShortUrl 缺失]');
        }
    }


    /**
     * 催办（合同签署流程）
     * @param $statement_number
     * @return array
     */
    public function signflows_rushsign($statement_number){
        $this->load->library('esign/ESignFlows');

        $statementInfo = $this->get_statement($statement_number,false);


        // 催办次数
        $records = $this->Purchase_statement_note_model->get_remark_list($statement_number, 5);
        if($records){
            $records_day_count = 0;
            foreach($records as $record_val){
                if(strtotime($record_val['create_time']) > strtotime(date('Y-m-d'))){
                    $records_day_count ++;
                }
                if($records_day_count >= 2){
                    return $this->res_data(false,'请耐心等待对方处理，或明天再试试！');
                }
            }
        }

        if( $statementInfo['status'] != STATEMENT_STATUS_WAIT_PARTY_B_AUDIT){
            return $this->res_data(false,'对账单号'.$statement_number.'，状态错误，无法催办！');
        }

        $result = $this->esignflows->signFlowsRushSign($statementInfo['flowId'],$statementInfo['purchase_name']);
        if($result['code'] == true){
            // 执行更新操作

            $this->Purchase_statement_note_model->add_remark($statement_number, 1, '催办电子对账单', '催办电子对账单');
            $this->Purchase_statement_note_model->add_remark($statement_number, 5, '记录催办次数', '催办电子对账单');

            return $this->res_data(true,'催签成功');
        }else{
            return $this->res_data(false,$result['errorMsg']);
        }

    }

    /**
     * 撤销（合同签署流程）
     * @param $statement_number
     * @return array
     */
    public function signflows_revoke($statement_number){
        $this->load->library('esign/ESignFlows');

        $statementInfo = $this->get_statement($statement_number,false);

        // 已生成对账单、采购驳回、乙方驳回、已撤回，且是否上传扫描件的状态=未上传、采购驳回
        if( !in_array($statementInfo['status'],[STATEMENT_STATUS_WAIT_PURCHASE_AUDIT, STATEMENT_STATUS_WAIT_PARTY_B_AUDIT])){
            return $this->res_data(false,'对账单号'.$statement_number.'，状态错误，无法撤回！');
        }


        $result = $this->esignflows->signFlowsRevoke($statementInfo['flowId'],$statementInfo['purchase_name']);
        if($result['code'] == true){
            // 执行更新操作

            $this->change_statement_status($statement_number,STATEMENT_STATUS_RECALL);

            return $this->res_data(true);
        }else{
            return $this->res_data(false,$result['errorMsg']);
        }

    }


    /**
     * 上传附属文件
     * @param $statement_number
     * @param $attachment_pdf_path
     * @return array
     */
    public function upload_attachment_pdf($statement_number,$attachment_pdf_path){
        $this->load->model('supplier/supplier_model');

        $statementInfo = $this->get_statement($statement_number,false);
        $supplierInfo = $this->supplier_model->get_supplier_info($statementInfo['supplier_code'],false);

        if($supplierInfo['is_postage'] != 2 and $supplierInfo['is_postage'] != 3){
            return $this->res_data(false,'是否包邮＝不包邮/部分包邮时才需要上传附属文件');
        }

        if(pathinfo($attachment_pdf_path,PATHINFO_EXTENSION) != 'pdf'){
            return $this->res_data(false,'附属文件只允许PDF格式');
        }

        // 重新上传时 置空e签宝的附属文件
        $rows = $this->purchase_db->where('statement_number',$statement_number)
            ->update($this->table_name,['attachmentPath' => $attachment_pdf_path,'attachmentPathEsign' => $attachment_pdf_path]);

        if($rows){
            return $this->res_data(true);
        }else{
            return $this->res_data(false,'更新失败');
        }

    }

    /**
     * 获取用户的联系方式（此为甲方联系人信息）
     * @param string $statement_user_id 对账员ID
     * @return array
     */
    private function _A_user_info($statement_user_id){
        $this->load->model('user/purchase_user_model');

        $userInfo = $this->purchase_user_model->get_user_info_by_user_id($statement_user_id);
        if(empty($userInfo)){
            $this->res_data(false,'用户信息不存在[UserId:'.$statement_user_id.']');
        }

        $getActiveUserName  = preg_replace('/[0-9A-Za-z]/', '', $userInfo['user_name']);//去除字母和数字（工号）
        if(empty($userInfo['phone_number'])){
            $this->res_data(false,'用户手机号码缺失[UserId:'.$statement_user_id.']');
        }

        $userData = [
            'username' => $getActiveUserName,
            'mobile' => $userInfo['phone_number']
        ];

        return $this->res_data(true,'',$userData);

    }


    /**
     * 获取用户的联系方式（此为乙方联系人信息）
     * @param string $supplier_code 供应商code
     * @return array
     */
    private function _B_user_info($supplier_code){

        $this->load->model('supplier/supplier_model');

        // 验证对账人信息
        $supplierInfo = $this->supplier_model->get_supplier_info($supplier_code,false);
        if(empty($supplierInfo['reconciliation_agent']) or empty($supplierInfo['agent_mobile'])){
            return $this->res_data(false,'请先联系供应商完成电子章认证，且填写经办人！');
        }

        $userData = [
            'username' => $supplierInfo['reconciliation_agent'],
            'mobile' => $supplierInfo['agent_mobile']
        ];

        return $this->res_data(true,'',$userData);

    }


    /**
     * 解析 E签宝回调接口推送的消息
     * @param $data
     * @return array
     */
    public function analyze_esign_message($data){
        $this->load->library('esign/ESignFlows');
        $this->load->library('Upload_image');

        $flowStatus = null;
        $first  = 1;// 甲方
        $second = 2;// 乙方
        $flowId = $data['flowId'];
        $statementInfo = $this->get_statement_by_flowId($flowId);

        // action = PARTICIPANT_MARKREAD 已读通知

        $new_status = null;// 目标状态
        if($statementInfo){
            $statement_number = $statementInfo['statement_number'];

            if($data['action'] == 'SIGN_FLOW_UPDATE'){// 签署人签署完成回调通知
                $signResult = $data['signResult'];// 签署结果 2:签署完成 3:失败 4:拒签
                $flowStatus = $signResult;
                $order      = $data['order'];// 签署人的签署顺序

                // 判断状态
                if($statementInfo['firstSignParty'] == $first){// 甲方发起
                    if($order == $first){// 甲方签署
                        if($signResult == 2){
                            // 待乙方审核：甲方发起，甲方签署后 变成待乙方签署
                            $new_status = STATEMENT_STATUS_WAIT_PARTY_B_AUDIT;
                        }elseif($signResult == 4){
                            // 采购驳回：甲方拒签
                            $new_status = STATEMENT_STATUS_PURCHASE_REJECT;
                        }
                    }else{// 乙方签署
                        if($signResult == 2){
                            // 线上签署完成：甲方发起，乙方最后签署，乙方签署后 变成线上签署完成
                            $new_status = STATEMENT_STATUS_SIGN_OFFLINE;
                        }elseif($signResult == 4){
                            // 乙方驳回：乙方拒签
                            $new_status = STATEMENT_STATUS_PARTY_B_REJECT;
                        }
                    }
                }else{// 乙方发起
                    if($order == $second){// 甲方签署
                        if($signResult == 2){
                            // 线上签署完成：乙方发起，甲方最后签署，甲方签署后 变成线上签署完成
                            $new_status = STATEMENT_STATUS_SIGN_OFFLINE;
                        }elseif($signResult == 4){
                            // 采购驳回：甲方拒签
                            $new_status = STATEMENT_STATUS_PURCHASE_REJECT;
                        }

                    }else{// 乙方签署
                        if($signResult == 2){
                            // 待采购审核：乙方发起，乙方签署后 变成待甲方签署
                            $new_status = STATEMENT_STATUS_WAIT_PURCHASE_AUDIT;
                        }elseif($signResult == 4){
                            // 乙方驳回：乙方拒签
                            $new_status = STATEMENT_STATUS_PARTY_B_REJECT;
                        }
                    }
                }

            }
            elseif($data['action'] == 'SIGN_FLOW_FINISH'){// 流程结束回调通知
                /*
                 * flowStatus 任务状态：2-已完成: 所有签署人完成签署；
                 * 3-已撤销: 发起方撤销签署任务；
                 * 5-已过期: 签署截止日到期后触发；
                 * 7-已拒签
                 */
                $flowStatus = $data['flowStatus'];

                if($flowStatus == 2){
                    $new_status = STATEMENT_STATUS_SIGN_OFFLINE;
                }elseif($flowStatus == 3){
                    $new_status = STATEMENT_STATUS_RECALL;
                }elseif($flowStatus == 5){
                    $new_status = STATEMENT_STATUS_EXPIRE;
                }elseif($flowStatus == 7){
                    if($statementInfo['status'] == STATEMENT_STATUS_WAIT_PURCHASE_AUDIT){
                        $new_status = STATEMENT_STATUS_PURCHASE_REJECT;// 甲方拒签
                    }elseif($statementInfo['status'] == STATEMENT_STATUS_WAIT_PARTY_B_AUDIT){
                        $new_status = STATEMENT_STATUS_PARTY_B_REJECT;// 乙方拒签
                    }else{
                        $new_status = STATEMENT_STATUS_PARTY_B_REJECT;// 乙方拒签
                    }
                }

            }elseif($data['action'] == 'SIGN_DOC_EXPIRE'){
                $new_status = STATEMENT_STATUS_EXPIRE;// 已过期
            }

            // 执行更新
            if($new_status){
                try{

                    // 操作用户判断
                    $operatorUser = [
                        'user_id' => 0,
                        'create_time' => $data['opTime']
                    ];
                    if(in_array($new_status,[STATEMENT_STATUS_PURCHASE_REJECT,STATEMENT_STATUS_WAIT_PARTY_B_AUDIT])){
                        // 当前为甲方操作
                        $operatorUser['user_name'] = $statementInfo['aUsername'];
                    }
                    elseif(in_array($new_status,[STATEMENT_STATUS_WAIT_PURCHASE_AUDIT,STATEMENT_STATUS_PARTY_B_REJECT])){
                        // 当前为乙方操作
                        $operatorUser['user_name'] = $statementInfo['bUsername'];
                    }elseif(in_array($new_status,[STATEMENT_STATUS_RECALL])){
                        // 撤销操作只有发起人才能操作
                        if($statementInfo['firstSignParty'] == $first){
                            $operatorUser['user_name'] = $statementInfo['aUsername'];
                        }else{
                            $operatorUser['user_name'] = $statementInfo['bUsername'];
                        }
                    }elseif(in_array($new_status,[STATEMENT_STATUS_EXPIRE])){
                        // 过期为e签宝系统自动过期
                        $operatorUser['user_name'] = 'admin';
                    }elseif(in_array($new_status,[STATEMENT_STATUS_SIGN_OFFLINE])){
                        // 签署完成，最后签署人
                        if($statementInfo['firstSignParty'] == $first){// 甲方发起，则操作为乙方
                            $operatorUser['user_name'] = $statementInfo['bUsername'];
                        }else{
                            $operatorUser['user_name'] = $statementInfo['aUsername'];
                        }
                    }
                    $this->change_statement_status($statement_number,$new_status,$flowStatus,$operatorUser);

                    if($new_status == STATEMENT_STATUS_SIGN_OFFLINE){// 下载归档文件
                        $result = $this->esignflows->getSignFlowsDocument($data['flowId'],$statementInfo['purchase_name']);
                        if($result['code'] == true){
                            $docs = isset($result['data']['docs'])?$result['data']['docs']:[];
                            if(empty($docs)){
                                throw new Exception('文档路径参数[data.docs]为空');
                            }

                            foreach($docs as $key => $doc){
                                $java_file_path = null;
                                $fileUrl        = $doc['fileUrl'];
                                $fileName       = $statement_number . '-esign.pdf';

                                if($fileUrl){

                                    $template_dir = 'statement_pdf_esign_file/';
                                    $file_save_path = get_export_path($template_dir.substr(md5($statement_number),0,3));
                                    $fileNamePdf = $file_save_path . $statement_number.'-'.$key. '.pdf';

                                    file_put_contents($fileNamePdf,file_get_contents($fileUrl));
                                    if(file_exists($fileNamePdf)){
                                        $java_result = $this->upload_image->doUploadFastDfs('image', $fileNamePdf,false);
                                        if(isset($java_result['code']) and $java_result['code'] == 200){
                                            $java_file_path = $java_result['data'];
                                        }else{
                                            throw new Exception('文件生成成功，但并上传JAVA服务器失败');
                                        }
                                    }else{
                                        throw new Exception('文件下载失败:'.$fileUrl);
                                    }

                                }else{
                                    throw new Exception($result['errorMsg']);
                                }

                                if($java_file_path){
                                    if($statementInfo['fileId'] == $doc['fileId']){// 扫描件
                                        $resUpload = $this->upload_statement_pdf($statement_number,$fileName,$java_file_path,true);
                                        if( !$resUpload['code']){
                                            throw new Exception('更新扫描件地址失败');
                                        }
                                    }elseif($statementInfo['attachmentFileId'] == $doc['fileId']){// 附件
                                        $resUpload = $this->upload_attachmentFile_pdf($statement_number,$java_file_path);
                                        if( !$resUpload['code']){
                                            throw new Exception('更新扫描件地址失败');
                                        }
                                    }
                                }
                            }

                            return $this->res_data(true,'更新扫描件地址成功');


                        }else{
                            throw new Exception($result['errorMsg']);
                        }
                    }

                    return $this->res_data(true,'更新成功');
                }catch (Exception $e){
                    return $this->res_data(false,$e->getMessage());
                }
            }else{

                return $this->res_data(false,'无需更新');
            }
        }else{

            return $this->res_data(false,$flowId . '-匹配不到对账单号');
        }

    }

    //endregion


    /**
     * 下载对账单运费明细 EXCEL
     * @param array $statement 当前对账单
     * @param array $history_statement_list 历史对账单号
     * @example $statement = $this->Purchase_statement_model->get_statement($statement_number);
     * @author Jolon
     * @return bool|string
     */
    public function download_freight_details_html($statement,$history_statement_list){
        require_once APPPATH . 'third_party/PHPExcel/PHPExcel.php';
        set_time_limit(3600);
        ini_set('memory_limit', '1024M');
        header("Content-Type:text/html;Charset=utf-8");
        $objPHPExcel = new PHPExcel();
        $objPHPExcelActive = $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcelActive->mergeCells('A1:H1')->setCellValue('A1',$statement['supplier_name'].'运费明细');
        $objPHPExcelActive->getStyle('A1')->getFont()->setSize(22);
        $objPHPExcelActive->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcelActive->getStyle('A1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcelActive->getRowDimension(1)->setRowHeight(35);


        $objPHPExcelActive
            ->setCellValue('A2', '序号')
            ->setCellValue('B2', '对账单号')
            ->setCellValue('C2', 'PO 号')
            ->setCellValue('D2', '运 费')
            ->setCellValue('E2', '重 量')
            ->setCellValue('F2', "抛货体积（非\n必填）")
            ->setCellValue('G2', '运费计算逻辑')
            ->setCellValue('H2', '备注');
        $objPHPExcelActive->getRowDimension(2)->setRowHeight(35);

        $objPHPExcelActive->getStyle('F2')->getAlignment()->setWrapText(true);

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(11);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(16);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(16);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(13);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(11);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(18);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(14);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(14);

//        print_r($statement);exit;

        $start = 3;
        $begin_i = $i = 2;
        if(!empty($statement['summary_list'])){
            $data_list = $statement['summary_list'];
            foreach($data_list as $key => $val){
                // 已经存在历史对账单
                if(isset($history_statement_list[$val['purchase_number']])){
                    continue;
                }

                $i++;

                $objPHPExcelActive
                    ->setCellValue('A'.$i, $key + 1)
                    ->setCellValue('B'.$i, $val['statement_number'])
                    ->setCellValue('C'.$i, $val['purchase_number'])
                    ->setCellValue('E'.$i, $val['instock_weight']);

                $objPHPExcelActive->getRowDimension($i)->setRowHeight(19);
            }
            if($begin_i != $i){
                $objPHPExcelActive->mergeCells('B'.$start.':'.'B'.($i));
            }
        }

        // 合计
        $objPHPExcelActive->setCellValue('C'.($i+1),'合计');
        $objPHPExcelActive->getStyle('C'.($i+1))->getFont()->setBold(true);
        $objPHPExcelActive->getRowDimension($i+1)->setRowHeight(38);
        // 汇款信息
        $objPHPExcelActive->setCellValue('A'.($i+2),'汇款信息');
        $objPHPExcelActive->mergeCells('B'.($i+2).':'.'H'.($i+2));
        $objPHPExcelActive->setCellValue('B'.($i+2),'运费随货款一起转到指定的银行账号中');
        $objPHPExcelActive->getStyle('A'.($i+2))->getFont()->setBold(true);
        $objPHPExcelActive->getStyle('B'.($i+2))->getFont()->setBold(true);
        $objPHPExcelActive->getRowDimension($i+2)->setRowHeight(40);
        // 盖章
        $objPHPExcelActive->mergeCells('A'.($i+3).':'.'D'.($i+3));
        $objPHPExcelActive->setCellValue('A'.($i+3),'甲方盖章：'.$statement['purchase_name_cn']."\n");
        $objPHPExcelActive->mergeCells('E'.($i+3).':'.'H'.($i+3));
        $objPHPExcelActive->setCellValue('E'.($i+3),'乙方盖章：'.$statement['supplier_name']."\n");
        $objPHPExcelActive->getRowDimension($i+3)->setRowHeight(150);
        $objPHPExcelActive->getStyle('A'.($i+3))->getAlignment()->setWrapText(true);
        $objPHPExcelActive->getStyle('E'.($i+3))->getAlignment()->setWrapText(true);

        // 统一设置格式
        for($m = 1;$m < $i + 3;$m ++){
            for ($n = 65;$n < 65 + 8;$n ++){
                if($m != $i + 3){
                    $objPHPExcelActive->getStyle(chr($n).$m)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcelActive->getStyle(chr($n).$m)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                }
            }
        }

        // 设置边框线
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,//粗的是thick
                )
            )
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.'H'.($i + 3))->applyFromArray($styleThinBlackBorderOutline);

        // 设置第一个sheet为工作的sheet
        $objPHPExcelActive->setTitle('运费明细');

        //生成xlsx文件
        $filename = $statement['supplier_code'].'-运费明细'.date('YmdHis');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');

        $filePath = get_export_path('statement_excel/'.date('Ymd')).$statement['statement_number'].'.xlsx';
        // 保存Excel 2007格式文件
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);
        if(file_exists($filePath)){
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url = get_export_path_replace_host($filePath,$down_host);
            return $down_file_url;
        }else{
            return false;
        }
    }

}