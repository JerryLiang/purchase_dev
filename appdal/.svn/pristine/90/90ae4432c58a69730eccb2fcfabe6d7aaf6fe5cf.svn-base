<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/11/26
 * Time: 15:24
 */
class Purchase_order_tax_list_model extends Purchase_model {

    protected $table_name = 'purchase_order';
    protected $table_customs = 'declare_customs';
    protected $table_product = 'product';
    protected $table_invoice_detail = 'purchase_items_invoice_info';
    protected $show_status =  [
        PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
        PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
        PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION,
        PURCHASE_ORDER_STATUS_ALL_ARRIVED,
        PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
        PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
        PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,
        PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND,
        PURCHASE_ORDER_STATUS_CANCELED,
        PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT,
    ];

    public function __construct() {
        parent::__construct();
        $this->load->library('Upload_image');
    }


    /**
     * 订单退税列表页标题
     * @author Manson
     * @return array
     */
    public function list_title(){
        $this->lang->load('common');
        $key_value = $this->lang->myline('purchase_tax_order_tacking_list');
        return $key_value;
    }


    /**
     * 订单退税列表页- 查询
     * @author Manson
     * @param $params
     * @param $offset
     * @param $limit
     * @param string $field
     * @param string $group
     * @param bool $export
     * @return array
     */
    public function get_tax_order_tacking_list($params, $offset='', $limit='',$field='ppoi.*',$group='',$export=false) {

        $field ='ppo.pay_finish_status,ppo.pay_time AS completion_time,comp.compact_number,ppo.supplier_code,ppoi.id,ppoi.sku,ppoi.purchase_number,
        ppoi.confirm_amount,ppoi.product_name,ppoi.invoice_is_abnormal, ppoi.invoiced_qty, ppoi.uninvoiced_qty,
        ppoi.pur_ticketed_point,ppoi.purchase_unit_price,ppoi.invoice_status,ppoi.contract_invoicing_status,
        ppoi.purchase_amount,ppoi.upselft_amount,ppoi.is_end,ppoi.customs_status,
        ppoi.export_cname, ppoi.declare_unit, ppoi.coupon_rate,
        ppo.create_time as order_create_time,
        ppo.buyer_name,ppo.is_drawback,ppo.warehouse_code,ppo.purchase_order_status,ppo.pay_status,
        ppo.supplier_name,ppo.currency_code,
        statement_items.statement_number,
        ppoi.ca_product_money,
        ppoi.ca_process_cost,
        ppoi.demand_number as pdemand_number,
        sug.demand_number,ppo.is_gateway,IFNULL(sum(loss.loss_amount),0) AS loss_amount,IFNULL(SUM(b.cancel_ctq),0) AS cnacel_ctq';
        $group='ppoi.id';
        $order_info = 'ppo.create_time desc';

        if(!empty($group)){
            $this->purchase_db->group_by($group);
        }
        $joinString ='a.cancel_id = b.id  and b.audit_status IN ('.implode(",",[CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC]).')';

        $this->purchase_db->select($field);
        $this->purchase_db->from('purchase_order_items as ppoi');
        $this->purchase_db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $this->purchase_db->join('purchase_suggest_map as sug', 'ppoi.purchase_number=sug.purchase_number AND ppoi.sku=sug.sku', 'left');
        $this->purchase_db->join('purchase_compact_items as comp', 'ppoi.purchase_number=comp.purchase_number', 'left');
        $this->purchase_db->join('pur_purchase_order_reportloss as loss', 'ppoi.purchase_number=loss.pur_number AND ppoi.sku = loss.sku AND loss.status = '.REPORT_LOSS_STATUS_FINANCE_PASS, 'left');
        $this->purchase_db->join('purchase_order_cancel_detail AS  a','ppoi.id=a.items_id','LEFT');
        $this->purchase_db->join( 'purchase_order_cancel  AS b',$joinString, 'LEFT');
        $this->purchase_db->join('purchase_statement_items as statement_items','ppoi.purchase_number=statement_items.purchase_number AND
        statement_items.demand_number=ppoi.demand_number AND statement_items.sku=ppoi.sku','LEFT');
        if (isset($params['ids']) && !empty($params['ids'])){//勾选导出
            $this->purchase_db->where_in('ppoi.id',explode(",",$params['ids']));
           /* $this->purchase_db->select($field)->order_by($order_info);
            $return_data['quick_sql'] =  $this->purchase_db->get_compiled_select();
            $clone_db = clone($this->purchase_db);
            $return_data['total_count'] = $clone_db->count_all_results();//符合当前查询条件的总记录数
            return $return_data;*/
        }

        if( isset($params['demand_number']) && !empty($params['demand_number'])){

            $this->purchase_db->where_in("sug.demand_number",$params['demand_number']);
        }

        if( isset($params['completion_time_start']) && !empty($params['completion_time_start'])){

            $this->purchase_db->where("ppo.pay_time>=",$params['completion_time_start']);
        }
        if( isset($params['statement_number']) && !empty($params['statement_number'])){
            $this->purchase_db->where_in("statement_items.statement_number",$params['statement_number']);
        }

        if( isset($params['completion_time_end']) && !empty($params['completion_time_end'])){

            $this->purchase_db->where("ppo.pay_time<=",$params['completion_time_end']);
        }

        if( isset($params['is_gateway']) && !empty($params['is_gateway'])){

            if( $params['is_gateway'] == 1){

                $this->purchase_db->where("ppo.is_gateway",1);
            }else{
                $this->purchase_db->where("ppo.is_gateway",0);
            }
        }
        if(!empty($params['purchase_number']) && isset($params['purchase_number'])){
            $this->purchase_db->where_in('ppoi.purchase_number', query_string_to_array($params['purchase_number']));
        }
        if(!empty($params['buyer_id']) && isset($params['buyer_id'])){
            $this->purchase_db->where_in('ppo.buyer_id', query_string_to_array($params['buyer_id']));
        }

        if (isset($params['sku']) and $params['sku']) {// SKU
            $this->purchase_db->where_in('ppoi.sku', query_string_to_array($params['sku']));
        }
        if((isset($params['is_check_goods']) && !empty($params['is_check_goods'])) ||
            (isset($params['customs_code']) && !empty($params['customs_code']))){
            $this->purchase_db->join('product pro','ppoi.sku = pro.sku','left');
            //是否商检
            if (isset($params['is_check_goods']) && !empty($params['is_check_goods'])){
                $this->purchase_db->where('pro.is_inspection', $params['is_check_goods']);
            }
            //出口海关编码 product表
            if(isset($params['customs_code']) && !empty($params['customs_code'])){
                $customs_code = query_string_to_array($params['customs_code']);
                $this->purchase_db->where_in('pro.customs_code', $customs_code);
            }
        }


        if(!empty($params['supplier_code']) && isset($params['supplier_code'])){
            $this->purchase_db->where('ppo.supplier_code', $params['supplier_code']);
        }
        //业务线搜索
        if(isset($params['purchase_type_id']) && !empty($params['purchase_type_id'])){
            $this->purchase_db->where('ppo.purchase_type_id',$params['purchase_type_id']);
        }
        if(!empty($params['order_create_time_start']) && isset($params['order_create_time_start']) ){
            $this->purchase_db->where('ppo.create_time>=', $params['order_create_time_start']);
        }
        if(!empty($params['order_create_time_end']) && isset($params['order_create_time_end'])){
            $this->purchase_db->where('ppo.create_time<=', $params['order_create_time_end']);
        }

        if(!empty($params['invoice_is_abnormal']) && isset($params['invoice_is_abnormal'])){
            $this->purchase_db->where('ppoi.invoice_is_abnormal', $params['invoice_is_abnormal']);
        }
        if(isset($params['is_end']) and $params['is_end']!==''){
            $this->purchase_db->where('ppoi.is_end', $params['is_end']);
        }
        if(!empty($params['customs_status']) && isset($params['customs_status'])){
            $this->purchase_db->where('ppoi.customs_status', $params['customs_status']);
        }

        if(!empty($params['purchase_order_status']) && isset($params['purchase_order_status'])){
            $this->purchase_db->where('ppo.purchase_order_status', $params['purchase_order_status']);
        }

        //未开票数量
        if(!empty($params['min_no_invoice']) && isset($params['min_no_invoice'])){
            $this->purchase_db->where('ppoi.uninvoiced_qty >=', $params['min_no_invoice']);
        }
        //未开票数量
        if(!empty($params['max_no_invoice']) && isset($params['max_no_invoice'])){
            $this->purchase_db->where('ppoi.uninvoiced_qty <=', $params['max_no_invoice']);
        }

        //发票清单号
        if(!empty($params['invoice_number']) && isset($params['invoice_number'])){
            $invoice_number = query_string_to_array($params['invoice_number']);
            $this->purchase_db->join('purchase_invoice_item pii','pii.purchase_number = ppoi.purchase_number AND pii.sku = ppoi.sku','left');
            $this->purchase_db->where_in('pii.invoice_number', $invoice_number);
        }

        if(!empty($params['pay_status']) && isset($params['pay_status'])){

            if( is_array($params['pay_status'])) {
                $this->purchase_db->where_in('ppo.pay_status', $params['pay_status']);
            }else{
                $this->purchase_db->where('ppo.pay_status', $params['pay_status']);
            }
        }
        //发票状态
        if(!empty($params['invoice_status']) && isset($params['invoice_status'])){

            $params['invoice_status'] = explode(',',$params['invoice_status']);

            if(is_array($params['invoice_status'])){


//                $havingString = "( 1=1 ";
//                $this->purchase_db->having($havingString);

                $flag = True;
                if( in_array(1,$params['invoice_status'])){
                    //$this->purchase_db->where_in('b.audit_status ',[CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC]);
                    $this->purchase_db->having('(ppoi.invoice_status!=12 AND ppoi.invoice_status!=5  AND ppoi.invoice_status!=2 AND (ppoi.confirm_amount-cnacel_ctq)>0 AND ppoi.invoiced_qty=0)');
                    $flag = False;
                }

                if( in_array(6,$params['invoice_status'])){

                    if( False == $flag) {
                        $this->purchase_db->or_having(' ((  ppoi.invoice_status!=12 AND ppoi.invoice_status!=5 AND ppoi.invoice_status!=2 AND (ppoi.confirm_amount-cnacel_ctq)=0) OR (ppoi.invoice_status!=2 AND ppoi.invoice_status!=5  AND  (ppoi.confirm_amount-cnacel_ctq)<=0 AND ppoi.invoiced_qty=0 AND ppoi.uninvoiced_qty=0))');
                    }else{
                        $this->purchase_db->having('((  ppoi.invoice_status!=12 AND ppoi.invoice_status!=5 AND ppoi.invoice_status!=2 AND (ppoi.confirm_amount-cnacel_ctq)=0) OR (ppoi.invoice_status!=2  AND ppoi.invoice_status!=5 AND  (ppoi.confirm_amount-cnacel_ctq)<=0 AND ppoi.invoiced_qty=0 AND ppoi.uninvoiced_qty=0))');
                    }
                    $flag = False;
                }

                if( in_array(3,$params['invoice_status'])){
                    //$this->purchase_db->where_in('b.audit_status ',[CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC]);
                    if( False == $flag) {
                        $this->purchase_db->or_having('( ppoi.invoice_status!=12 AND  ppoi.invoice_status!=5  AND ppoi.invoice_status!=2 AND ppoi.invoiced_qty>0 AND (ppoi.confirm_amount-cnacel_ctq)>invoiced_qty)');
                    }else{
                        $this->purchase_db->having('(ppoi.invoice_status!=12 AND ppoi.invoice_status!=5  AND ppoi.invoice_status!=2 AND ppoi.invoiced_qty>0 AND (ppoi.confirm_amount-cnacel_ctq)>invoiced_qty)');
                    }
                    $flag = False;
                }

                if(in_array(2,$params['invoice_status'])){

                    if( False == $flag) {
                        $this->purchase_db->or_having('ppoi.invoice_status!=12 AND ppoi.invoice_status=2');
                    }else{
                        $this->purchase_db->having('ppoi.invoice_status!=12 AND ppoi.invoice_status=2');
                    }
                    $flag = False;
                }

                if(in_array(4,$params['invoice_status'])){
                    //$this->purchase_db->where_in('b.audit_status ',[CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC]);
                    if( False == $flag) {
                        $this->purchase_db->or_having('((ppoi.invoice_status!=5  AND ppoi.invoice_status!=2 AND ppoi.invoiced_qty>0 AND (ppoi.confirm_amount-cnacel_ctq)=invoiced_qty) OR ppoi.invoice_status=12)');
                    }else{
                        $this->purchase_db->having('((ppoi.invoice_status!=5  AND ppoi.invoice_status!=2 AND ppoi.invoiced_qty>0 AND (ppoi.confirm_amount-cnacel_ctq)=invoiced_qty) OR ppoi.invoice_status=12)');
                    }
                    $flag = False;
                }

                if(in_array(5,$params['invoice_status'])){

                    if( False == $flag) {
                        $this->purchase_db->or_having(' ppoi.invoice_status!=12 AND ppoi.invoice_status=5');
                    }else{
                        $this->purchase_db->having(' ppoi.invoice_status!=12 AND ppoi.invoice_status=5');
                    }
                    $flag = False;
                }


//                $this->purchase_db->having(" 1=1 )");
            }
        }
        //合同发票状态
        if(!empty($params['contract_invoicing_status']) && isset($params['contract_invoicing_status'])){


            //$this->purchase_db->where('(ppoi.contract_invoicing_status', $params['contract_invoicing_status'])->or_where('ppoi.invoice_status=12)');

            $sql = "SELECT compact_number FROM (SELECT comp.compact_number, ppo.supplier_code, ppoi.id, ppoi.sku, ppoi.purchase_number,ppoi.confirm_amount, ppoi.product_name, ppoi.invoice_is_abnormal, ppoi.invoiced_qty,ppoi.uninvoiced_qty, ppoi.pur_ticketed_point, ppoi.purchase_unit_price, ppoi.invoice_status,ppoi.contract_invoicing_status, ppoi.purchase_amount, ppoi.upselft_amount, ppoi.is_end,ppoi.customs_status, ppoi.export_cname, ppoi.declare_unit, ppoi.coupon_rate, ppo.create_time asorder_create_time, ppo.buyer_name, ppo.is_drawback, ppo.warehouse_code, ppo.purchase_order_status,ppo.pay_status, ppo.supplier_name, ppo.currency_code, sug.demand_number, ppo.is_gateway,IFNULL(sum(loss.loss_amount), 0) AS loss_amount, IFNULL(SUM(a.cancel_ctq), 0) AS cnacel_ctq FROM pur_purchase_order_items as ppoi LEFT JOIN pur_purchase_order as ppo ON ppoi.purchase_number=ppo.purchase_number LEFT JOIN pur_purchase_suggest_map as sug ON ppoi.purchase_number=sug.purchase_number AND ppoi.sku=sug.sku LEFT JOIN pur_purchase_compact_items as comp ON ppoi.purchase_number=comp.purchase_number LEFT JOIN pur_purchase_order_cancel_detail as cancel ON ppoi.purchase_number=cancel.purchase_number AND ppoi.sku=cancel.sku LEFT JOIN pur_purchase_order_reportloss as loss ON ppoi.purchase_number=loss.pur_number AND ppoi.sku = loss.sku AND loss.status = 4 LEFT JOIN pur_purchase_order_cancel_detail AS a ON ppoi.id=a.items_id LEFT JOIN pur_purchase_order_cancel AS b ON a.cancel_id = b.id and b.audit_status IN (60,70,90) WHERE 1=1 AND ppo.is_drawback = 1 AND ppo.purchase_order_status IN(2, 7, 8, 9, 10, 11, 12, 13, 14, 15) AND source = 1 GROUP BY ppoi.id HAVING ( ppoi.invoice_status != 12 AND ppoi.invoice_status != 5 AND ppoi.invoice_status != 2 AND (ppoi.confirm_amount-cnacel_ctq) >0 AND ppoi.invoiced_qty =0) OR ( ppoi.invoice_status != 12 AND ppoi.invoice_status != 5 AND ppoi.invoice_status != 2 AND ppoi.invoiced_qty >0 AND (ppoi.confirm_amount-cnacel_ctq) > invoiced_qty) OR ppoi.invoice_status = 2 ORDER BY ppo.create_time DESC) as tp";
            $noData = $this->purchase_db->query($sql)->result_array();
            $noDatas = array_column($noData,"compact_number");
            if($params['contract_invoicing_status'] == 1){



                    if(count($noDatas)>2000){

                        $noDatasChunk = array_chunk($noDatas,10);
                        $this->purchase_db->where("(1=1")->where_in('comp.compact_number',$noDatasChunk[0]);

                        foreach($noDatasChunk as $chunkdata){

                            $this->purchase_db->or_where_in('comp.compact_number',$chunkdata);
                        }
                        $this->purchase_db->where(" 1=1)");
                    }else{

                        $this->purchase_db->where_in('comp.compact_number',$noDatas);
                    }

                   // $this->purchase_db->having("(ppoi.confirm_amount - ppoi.invoiced_qty)>0");

            }else{

                if(count($noDatas)>2000){

                    $noDatasChunk = array_chunk($noDatas,10);
                    $this->purchase_db->where("(1=1")->where_not_in('comp.compact_number',$noDatasChunk[0]);

                    foreach($noDatasChunk as $chunkdata){

                        $this->purchase_db->where_not_in('comp.compact_number',$chunkdata);
                    }
                    $this->purchase_db->where(" 1=1)");
                }else{

                    $this->purchase_db->where_not_in('comp.compact_number',$noDatas);
                }
                //$this->purchase_db->having("(ppoi.confirm_amount - ppoi.invoiced_qty)=0");
            }

        }
        //合同发票状态
        if(!empty($params['compact_number']) && isset($params['compact_number'])){
            $this->purchase_db->where('comp.compact_number', $params['compact_number']);
        }

        if(!empty($params['ids'])){
            $this->purchase_db->where_in('ppoi.id', explode(',', $params['ids']));
        }

        //入库时间
        if((!empty($params['instock_time_start']) && isset($params['instock_time_start'])) || (!empty($params['instock_time_end']) && isset($params['instock_time_end']))){
            $this->purchase_db->join('warehouse_results_main wrm','ppoi.id = wrm.items_id','left');

            if(!empty($params['instock_time_start']) && isset($params['instock_time_start']) ){
                $this->purchase_db->where('wrm.instock_date>=', $params['instock_time_start']);
            }
            if(!empty($params['instock_time_end']) && isset($params['instock_time_end']) ){
                $this->purchase_db->where('wrm.instock_date<=', $params['instock_time_end']);
            }
        }

        //发票代码
        if (isset($params['invoice_code_left']) || isset($params['invoice_code_right'])){
            $this->purchase_db->join($this->table_invoice_detail.' invoice_detail','ppoi.purchase_number=invoice_detail.purchase_number AND ppoi.sku=invoice_detail.sku','left');
            if (isset($params['invoice_code_left']) && !empty($params['invoice_code_left'])){
                $this->purchase_db->where('invoice_detail.invoice_code_left',$params['invoice_code_left']);
            }
            if (isset($params['invoice_code_right']) && !empty($params['invoice_code_right'])){
                $this->purchase_db->where('invoice_detail.invoice_code_right',$params['invoice_code_right']);
            }
        }

        //退税的,合同单,变成等待到货之后都放在含税订单页面
        $this->purchase_db->where('ppo.is_drawback',PURCHASE_IS_DRAWBACK_Y);
        $this->purchase_db->where_in('ppo.purchase_order_status',$this->show_status);
        $this->purchase_db->where('source',SOURCE_COMPACT_ORDER);


        if(isset($params['export_save'])){//导出不需要分页查询
            $clone_db = clone( $this->purchase_db);
            $total_count=$clone_db->count_all_results();//符合当前查询条件的总记录数


            $query_export = clone  $this->purchase_db;
            $query_export->select($field)->order_by($order_info);
            $this->load->service('basic/SearchExportCacheService');
            $total = str_pad((string)$total_count, 10, '0', STR_PAD_LEFT);
            $this->searchexportcacheservice->setScene($this->searchexportcacheservice::PURCHASE_TAX_ORDER_TACKING_LIST_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));

        }


        //统计总数
        $clone_db = clone($this->purchase_db);
        $total_count=$clone_db->count_all_results();//符合当前查询条件的总记录数
        if($export){//导出不需要分页查询
            $results = $this->purchase_db->get()->result_array();
        }else{
            $results = $this->purchase_db->order_by('ppo.create_time','desc')->limit($limit, $offset)->get()->result_array();
        }
        if(!empty($results)){
            $supplierCods = array_unique(array_column($results,"supplier_code"));
            $supplierDatas = $this->purchase_db->from("supplier")->where_in("supplier_code",$supplierCods)->select("supplier_settlement,supplier_code")
                ->get()->result_array();
            $supplierD = array_column($supplierDatas,NULL,"supplier_code");
            $settlementDatas = array_column($supplierDatas,"supplier_settlement");
            if(!empty($settlementDatas)) {
                $settlements = $this->purchase_db->from("supplier_settlement")->where_in("settlement_code", $settlementDatas)->get()->result_array();
            }
            if(!empty($settlements)){
                $settlements = array_column($settlements,NULL,"settlement_code");
            }

            $purchaseNumbers = array_unique(array_column($results,"purchase_number"));
            $amount_paid_list = $this->get_list_by_purchase_numbers($purchaseNumbers);
            $demandNumbers = array_column($results,"pdemand_number");
            $demandDatas = $this->purchase_db->from("purchase_suggest")->where_in("demand_number",$demandNumbers)->select("demand_number,suggest_status")
                ->get()->result_array();
            if(!empty($demandDatas)){
                $demandDatas = array_column($demandDatas,NULL,"demand_number");
            }
            foreach( $results as $key=>$value){

                if( $value['is_gateway'] == 1){

                    $results[$key]['is_gateway_ch']= "是";
                }else{
                    $results[$key]['is_gateway_ch'] = "否";
                }
                $supplier_sttlements = isset($supplierD[$value['supplier_code']])?$supplierD[$value['supplier_code']]['supplier_settlement']:0;
                $results[$key]['settlement_ch'] = isset($settlements[$supplier_sttlements])?$settlements[$supplier_sttlements]['settlement_name']:'';
                $results[$key]['pay_finish_status_ch'] = !empty($value['pay_finish_status'])?getPayFinishStatus($value['pay_finish_status']):'';
                $amount_paid_key = $value['purchase_number'] . "_" . $value['sku'];
                $results[$key]['amount_paid'] = isset($amount_paid_list[$amount_paid_key])?sprintf("%.3f",$amount_paid_list[$amount_paid_key]):0;
                $results[$key]['amount_paid'] += $value['ca_product_money'] + $value['ca_process_cost'];
                $results[$key]['suggest_status_ch'] = isset($demandDatas[$value['pdemand_number']])?getSuggestStatus($demandDatas[$value['pdemand_number']]['suggest_status']):'';
            }
        }
        //$return_data['quick_sql'] =  $this->purchase_db->get_compiled_select();
        //            $clone_db = clone($this->purchase_db);
        //            $return_data['total_count'] = $clone_db->count_all_results();//符合当前查询条件的总记录数
        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'limit'     => $limit,
            ],
            'key'   => $this->list_title(),

        ];
        return $return_data;
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
     * 订单退税列表页标题
     * @author Manson
     * @return array
     */
   /* public function list_title(){
        $this->lang->load('common');
        $key_value = $this->lang->myline('purchase_tax_order_tacking_list');
        return $key_value;
    }*/

    /**
     * 查询出生成发票清单的数据 用于验证
     * @author Manson
     * @return array
     */
    public function get_create_invoice_listing($params,$group,$flag = True)
    {
        //如果是one_key 要查询所勾选的备货单的合同号下面的所有备货单
        if (isset($params['one_key']) && !empty($params['one_key']) && isset($params['ids']) && !empty($params['ids'])){
            $this->purchase_db->select('comp.compact_number');
            $this->purchase_db->from('purchase_order_items as ppoi');
            $this->purchase_db->join('purchase_compact_items as comp', 'ppoi.purchase_number=comp.purchase_number', 'left');
            $this->purchase_db->where_in('ppoi.id',$params['ids']);
            $compact_numbers_list = $this->purchase_db->get()->result_array();
            if (!empty($compact_numbers_list)){
                $compact_numbers_list = array_column($compact_numbers_list,'compact_number');
            }
        }

        $field = 'ppoi.invoice_status, ppoi.contract_invoicing_status, ppoi.id, ppoi.confirm_amount as purchase_qty,
        ppoi.sku, ppoi.customs_status, ppoi.is_end, ppoi.upselft_amount, ppoi.purchase_unit_price, pro.coupon_rate,
        ppoi.export_cname,
        ppo.currency_code, ppo.purchase_order_status, ppo.buyer_id, ppo.buyer_name,
        ppo.purchase_number, ppo.purchase_type_id, ppo.purchase_name,
        sum(loss.loss_amount) as loss_qty,
        sug.demand_number, 
        sum(invoice.invoiced_qty) as invoiced_qty,
        comp.compact_number,
        ppo.supplier_code,
        ppo.supplier_name,
        pro.export_cname as new_export_cname,
        ppo.is_gateway,
        pro.product_name,
        pro.customs_code,
        ppoi.confirm_amount,
      
        ppoi.pur_ticketed_point,
        invoice.is_tovoid,
        ppoi.uninvoiced_qty';

        $this->purchase_db->select($field);
        $this->purchase_db->from('purchase_order_items as ppoi');
        $this->purchase_db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $this->purchase_db->join('purchase_suggest_map as sug', 'ppoi.purchase_number=sug.purchase_number AND ppoi.sku = sug.sku', 'left');
        $this->purchase_db->join('purchase_compact_items as comp', 'ppoi.purchase_number=comp.purchase_number', 'left');
        $this->purchase_db->join('pur_purchase_items_invoice_info as invoice', 'ppoi.purchase_number=invoice.purchase_number AND ppoi.sku = invoice.sku AND invoice.is_tovoid<>2 AND invoice.audit_status = '.INVOICE_STATES_AUDIT, 'left');
        $this->purchase_db->join('pur_purchase_order_reportloss as loss', 'ppoi.purchase_number=loss.pur_number AND ppoi.sku = loss.sku AND loss.status = '.REPORT_LOSS_STATUS_FINANCE_PASS, 'left');
        $this->purchase_db->join('product as pro', ' ppoi.sku = pro.sku', 'left');

        if (isset($compact_numbers_list) && !empty(array_filter($compact_numbers_list))){
            $this->purchase_db->where_in('comp.compact_number', array_unique(array_filter($compact_numbers_list)));
        }else{
            if(!empty($params['ids'])){
                $this->purchase_db->where_in('ppoi.id',array_unique(array_filter($params['ids'])));
            }
        }

        if(!empty($group)){
            $this->purchase_db->group_by($group);
        }
        $this->purchase_db->order_by('comp.compact_number');
        //统计总数
        $clone_db = clone($this->purchase_db);
        $total_count=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数

        $this->purchase_db=$clone_db;
        $results = $this->purchase_db->get()->result_array();
        $return_data = [
            'total' => $total_count,
            'data_list' => $results,
        ];

        return $return_data;
    }

    /**
     * @params  purchaseNumber   string    采购单号
     *          sku     string   产品SKU
     * @author:luxu
     * @time:2020/5/18
     **/
    public function getDeclareCustoms($purchaseNumber,$sku){

        $where = array(
            'purchase_number' => $purchaseNumber,
            'sku'             => $sku
        );
        return $this->purchase_db->from($this->table_customs)->where($where)->get()->row_array();
    }

    public function push_gateways($data){
        if(!empty($data)){

            $pushGateWaysDataPurchase = $pushGateWaysSku = []; // 推送门户系统数据
            foreach($data as $key=>$value){

                if( !isset($pushGateWaysDataPurchase[$value['purchase_number']])){

                    $pushGateWaysDataPurchase[$value['purchase_number']] = [];

                }

                if( !isset($pushGateWaysSku[$value['sku']])){

                    $pushGateWaysSku[$value['sku']] = [];
                }

                $compactFiles = $this->purchase_db->from("purchase_compact AS compact")->join("purchase_compact_file AS file","file.pc_id=compact.id","LFET")
                    ->where("compact.compact_number",$value['compact_number'])->select("file.file_path")->get()->row_array();

                $pushGateWaysDataPurchase[$value['purchase_number']] = [

                    'purchaseNumber' => $value['purchase_number'], // 采购单号
                    'compactNumber'   => $value['compact_number'], // 合同号
                    'compactUrl'      => !empty($compactFiles['file_path'])?$compactFiles['file_path']:'', //合同下载路径
                    'supplierCode'    => $value['supplier_code'], // 供应商 CODE
                    'supplierName'    => $value['supplier_name'], // 供应商名称
                    'currencyCode'    => $value['currency_code'], // 币种
                    'purchaseUserId'  => $value['buyer_id'], // 采购员ID
                    'purchaseUserName' => $value['buyer_name'], // 采购员姓名
                    'createUser'      => getActiveUserName(), // 创建人姓名
                    'invoiceNumber'   => $value['invoiceNumber']
                ];


                $pushGateWaysSku[$value['sku']] = [

                    'sku'  => $value['sku'],
                    'productName'            => $value['product_name'], // 产品名称
                    'exportCname' => $value['export_cname'], // 开票品名 export_cname
                    'customsNumber' => !empty($value['customs_number'])?$value['customs_number']:'', // 报关单号
                    'customsCode'             =>$value['customs_code'], //出口海关编码
                    'customsName'  => !empty($value['customsName'])?$value['customsName']:"",//报关品名 customsNumber
                    'customsQuantity'  => !empty($value['customsQuantity'])?$value['customsQuantity']:0,//报关数量
                    'invoiceCouponRate' => $value['coupon_rate'], // 票面税率
                    'uncustomsQuantity' => 10, //未报关数量
                    'instockQty' => $value['confirm_amount'], //实际入库数量
                    'unitPrice'=> $value['purchase_unit_price'], //含税单价
                    'purTicketedPoint' => $value['pur_ticketed_point'], //开票点
                    'purchase_number'  => $value['purchase_number'],
                    'customsType' => !empty($value['customsType'])?$value['customsType']:"", //报关型号
                    'customsUnit' => !empty($value['customsUnit'])?$value['customsUnit']:"", //报关单位
                    'uncustomsQuantity' =>10,
                    'appInvoiceQty' => $value['uninvoiced_qty'] //uninvoiced_qty
                ];

            }

            if(!empty($pushGateWaysSku)){

                foreach( $pushGateWaysSku as $key=>$value){

                    if( isset($pushGateWaysDataPurchase[$value['purchase_number']])){

                        $pushGateWaysDataPurchase[$value['purchase_number']]['item'][] = $value;
                    }

                }
            }

            if(!empty($pushGateWaysDataPurchase)){

                $isGateWayPurchaseNumbers = array_keys($pushGateWaysDataPurchase);
                $isGateWayPurchaseNumbers = $this->purchase_db->from("purchase_order")->where_in("purchase_number",$isGateWayPurchaseNumbers)
                    ->select("purchase_number")->get()->result_array();

                if(!empty($isGateWayPurchaseNumbers)) {
                    $isGateWayPurchaseNumbers = array_column($isGateWayPurchaseNumbers,"purchase_number");
                    $url           = getConfigItemByName('api_config','invoice','list');
                    $header = array('Content-Type: application/json');
                    $access_taken = getOASystemAccessToken();
                    $url = $url . "?access_token=" . $access_taken;
                    foreach ($pushGateWaysDataPurchase as $key => $value) {
                        if( in_array($key,$isGateWayPurchaseNumbers)) {
                            $result = getCurlData($url, json_encode($value, JSON_UNESCAPED_UNICODE), 'post', $header);
                        }
                    }
                }
            }
        }
    }

    /**
     * 获取待采购审核数据
     * @params  $invoice_number   array   发票清单号
     * @author:luxu
     * @time:2020/5/18
     * @return array
     **/
    public function get_purchase_review($invoice_number){

        try{
            if(empty($invoice_number)){

                throw new Exception("请传入发票清单号");
            }

            $result = $this->purchase_db->from("purchase_items_invoice_info AS info")->where_in("children_invoice_number",$invoice_number)
                ->select("invoice_image as image,sku,children_invoice_number,purchase_number,demand_number,invoiced_qty")->get()->result_array();

            if(!empty($result)){

                foreach($result as $key=>$value){

                    $uninvoiced_qty = $this->purchase_db->from("purchase_order_items")->where("purchase_number",$value['purchase_number'])
                        ->where("sku",$value['sku'])->select("uninvoiced_qty")->get()->row_array();
                    $result[$key]['uninvoiced_qty'] = $uninvoiced_qty['uninvoiced_qty'];
                    $result[$key]['image_number'] =1;
                }
                return $result;
            }
            return NULL;

        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }


    /**
     * 待采购审核
     * @param  $subject   array   审核主体数据
     *         $examine   int     1表示通过 2表示驳回
     *         $remark    string  备注信息
     * @author:luxu
     * @time:2020/5/19
     **/
    public function reject($subject,$examine,$remark){

        try{
            // 审核通过
            if( $examine == 1){

                $error = [];
                foreach($subject as $subject_key=>$subject_value){

                    $uninvoiced_qty = $this->purchase_db->from("purchase_order_items")
                        ->where("purchase_number",$subject_value['purchase_number'])
                        ->where("sku",$subject_value['sku'])->select("uninvoiced_qty")->get()->row_array();
                    if($uninvoiced_qty['uninvoiced_qty']<$subject_value['invoiced_qty']){

                        throw new Exception($subject_value['children_invoice_number']);
                    }

                    $update = array(

                        "audit_status" => 3,
                        "invoiced_qty" => $subject_value['invoiced_qty'],
                        "invoice_image" =>$subject_value['invoice_image'],
                        "remark" => $remark,
                        "invoice_code_left" => $subject_value['invoice_code_left'],
                        "invoice_code_right" => $subject_value["invoice_code_right"]
//                        "audit_user" => getActiveUserName(),
//                        "audit_time" => date("Y-m-d H:i:s",time())
                    );

                    $result = $this->purchase_db->where("children_invoice_number",$subject_value['children_invoice_number'])->update("purchase_items_invoice_info",$update);
                    if(!$result){

                        $error[] = $subject_value['children_invoice_number'];
                    }
                }

            }

            if( $examine == 2){

                foreach($subject as $subject_key=>$subject_value){

                    $update = array(

                        "audit_status" => 7,
                        "audit_user" => getActiveUserName(),
                        "audit_time" => date("Y-m-d H:i:s",time()),
                        "remark" => $remark,
                    );
                    $result = $this->purchase_db->where("children_invoice_number",$subject_value['children_invoice_number'])->update("purchase_items_invoice_info",$update);
                    

                    if(!$result){

                        $error[] = $subject_value['children_invoice_number'];
                    }
                }
            }



            // 推送门户系统
            if(!empty($error)){
                throw new Exception("子开票清单:".implode(",",$error).".审核失败");
            }

            if( $examine == 2) {
                // 推送门户系统
                $url = getConfigItemByName('api_config', 'invoice', 'reject');
                $header = array('Content-Type: application/json');
                $access_taken = getOASystemAccessToken();
                $url = $url . "?access_token=" . $access_taken;
                foreach ($subject as $key => $value) {
                    $infos = $this->purchase_db->from("purchase_items_invoice_info")->where_in("children_invoice_number", $value['children_invoice_number'])
                        ->get()->row_array();

                    $isGateWaysFlag = $this->purchase_db->from("purchase_order")->where("purchase_number",$value['purchase_number'])
                        ->select("is_gateway,id")->get()->row_array();

                    if( $isGateWaysFlag['is_gateway'] == 0){
                        continue;
                    }
                    if (!empty($infos)) {
                        $pushData = [

                            'invoiceNumber' => $infos['invoice_number'],
                            'purchaseNumber' => $infos['purchase_number'],
                            'sku' => $infos['sku'],
                            'invoiceCodeLeft' => $infos['invoice_code_left'],
                            'invoiceCodeRight' => $infos['invoice_code_right'],
                            'auditType' => $examine,
                            'auditUser' => getActiveUserName(),
                            'invoiceNumberSub' => $value['children_invoice_number'],
                            'rejectRemark' => $remark
                        ];
                        $result = getCurlData($url, json_encode($pushData, JSON_UNESCAPED_UNICODE), 'post', $header);
                        $insertData =[
                            'pushdata' => json_encode($pushData, JSON_UNESCAPED_UNICODE),
                            'returndata' => $result,
                            'type' => 'reject'
                        ];
                        $this->purchase_db->insert('invoice_data_log',$insertData);

                    }
                }
            }
            if($examine == 1){
                $pushMdatas =[];
                foreach($subject as $subject_push=>$subject_push_value){
                    $invoiceN = $this->purchase_db->from("purchase_items_invoice_info")->where("children_invoice_number",$subject_push_value['children_invoice_number'])
                        ->select("invoice_number")->get()->row_array();
                    $pushMdatas[] = [
                        'invoiceNumberSub' => $subject_push_value['children_invoice_number'],
                        'invoiceCodeLeft' => $subject_push_value['invoice_code_left'],
                        'invoiceCodeRight' => $subject_push_value['invoice_code_right'],
                        'invoiceNumber' => $invoiceN['invoice_number']
                    ];
                }
                if(!empty($pushMdatas)){
                    $url = getConfigItemByName('api_config', 'invoice', 'adoptreject');
                    $header = array('Content-Type: application/json');
                    $access_taken = getOASystemAccessToken();
                    $url = $url . "?access_token=" . $access_taken;
                    $result = getCurlData($url, json_encode(['item'=>$pushMdatas], JSON_UNESCAPED_UNICODE), 'post', $header);
                }
            }

            //财务审核成功,记录日志

            $childrenLogs = array_column($subject,"children_invoice_number");
            $logsIds = $this->purchase_db->from("purchase_items_invoice_info")->where_in("children_invoice_number", $childrenLogs)
                ->select("id")->get()->result_array();
            $logsData = [];
            foreach($logsIds as $ids) {

                $examineMess = '';

                if($examine == 1){
                    $examineMess = "采购审核通过";
                }
                if( $examine == 2 ){
                    $examineMess ="采购审核驳回";
                }
                $logsData[] = [
                    'examine_user' => getActiveUserName(),
                    'examine_time' => date('Y-m-d H:i:s'),
                    'remark' => $remark,
                    'examine' =>$examineMess,
                    'ids' => $ids['id']
                ];
            }

            $this->m_financial_audit->insertLogsData($logsData);

            return True;
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }

    }

    public function upload_file($filepath)
    {
        $java_url_list = '';
        $return = [
            'code' => 0,
            'filepath' => '',
            'msg' => '',
        ];
        $java_result = $this->upload_image->doUploadFastDfs('image',$filepath);
        if ($java_result['code'] == 200) {
            $return['code'] = $java_result['code'];
            $return['filepath'] = $java_result['data'];
        } else {
            $return['code'] = 500;
            $return['msg'] = '文件上传数据库失败';
        }
        return $return;
    }

    /**
     * 更新文件
     * @params   $files    array  文件名称
     *           $dirname  string 文件路径名称
     * @author:luxu
     * @time:2020/5/19
     **/

    public function uplodeImage($files,$dirname){

        try{
            $filesNames = array_map(function($files){

                list($name,$ext) = explode(".",$files);
                return $name;
            },$files);
            // 判断子发票清单状态，只有带采购确认，财务驳回，待财务审核 时候采购可以上传图片
            $childrenNumbers = $this->purchase_db->from("purchase_items_invoice_info")->select("invoice_number,children_invoice_number")->where_in("children_invoice_number",$filesNames)
                ->where_in("audit_status",[3,6,5])->get()->result_array();

            $childrenNumbers = array_column($childrenNumbers,"children_invoice_number");
            $errors = [];
            foreach($files as $key=>$file) {
                $fileDirs = $dirname . "/" . $file;
               // $fileDirs = dirname(dirname(APPPATH)).'/webfront/download_csv/zipData/'.$fileDirs;
                //$fileDirs = str_replace(dirname(dirname(APPPATH)) . "/webfront/", CG_SYSTEM_WEB_FRONT_IP, $fileDirs);
               // $fileDirs = "C:/phpStudy/PHPTutorial/WWW/webfront/download_csv/zipData/FP202004010007/FP202004010007-1.png";
                $pushResult = $this->upload_file($fileDirs);
                if ($pushResult['code'] == 200) {

                    $update = [

                        'invoice_image' => $pushResult['filepath']
                    ];
                    list($children_invoice_number, $ext) = explode(".", $file);
                    $childrenData = $this->purchase_db->from("purchase_items_invoice_info")->where("children_invoice_number", $children_invoice_number)->select("id")->get()->row_array();
                    if(empty($childrenData)){

                        throw new Exception("图片名称请以子开票清单号命名");
                    }
                    if (!in_array($children_invoice_number, $childrenNumbers)) {

                        throw new  Exception($children_invoice_number . "只有待采购确认，财务驳回，待财务审核时候采购可以上传图片");
                    }
                    $result = $this->purchase_db->where("children_invoice_number", $children_invoice_number)->update("purchase_items_invoice_info", $update);
                    if (!$result) {
                        $errors[] = $file;
                    } else {
                        $pushGateWaysData = [];

                        $pushData = $this->purchase_db->from("purchase_items_invoice_info")
                            ->where("children_invoice_number", $children_invoice_number)->get()->row_array();
                        if (!empty($pushData)) {
                            $pushGateWaysData[] = [

                                'invoiceNumber' => $pushData['invoice_number'],
                                'purchaseNumber' => $pushData['purchase_number'],
                                'sku' => $pushData['sku'],
                                'invoiceCodeLeft' => $pushData['invoice_code_left'],
                                'invoice_code_right' => $pushData['invoice_code_right'],
                                'invoiceImage' => isset($pushResult['filepath'])?$pushResult['filepath']:''
                            ];

                            $header        = array('Content-Type: application/json');
                            $access_taken  = getOASystemAccessToken();
                            $url           = getConfigItemByName('api_config','invoice','pushInvoiceImage');
                            $url           = $url."?access_token=".$access_taken;
                            $result        = getCurlData($url,json_encode(['item'=>$pushGateWaysData],JSON_UNESCAPED_UNICODE),'post',$header);
                            $insertData =[
                                'pushdata' => json_encode($pushData, JSON_UNESCAPED_UNICODE),
                                'returndata' => $result,
                                'type' => 'pushGateWaysImage'
                            ];
                            $this->db->insert('invoice_data_log',$insertData);
                        }


                    }
                }else{
                    throw new Exception("上传图片服务器失败");
                }
            }

            if(!empty($errors)){

                throw new Exception("发票:".implode(".",$errors)."上传失败");
            }
            return true;
        }catch ( Exception $exp ){

                throw new Exception($exp->getMessage());
        }
    }

    /**
     * 下载压缩包文件到本地服务器
     * @params  $files    string  压缩包地址
     * @author:luxu
     * @time:2020/5/19
     **/
    public function downFiles($files,$zipDirs){

        try{

            $ch = curl_init($files);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            $rawdata = curl_exec($ch);
            curl_close($ch);
            $pdf_name = basename($files);
            $pdf_url = $zipDirs.$pdf_name;
            $fp = fopen($pdf_url, 'w');
            fwrite($fp, $rawdata);
            fclose($fp);
            return $pdf_url;
        }catch ( Exception $exp ){

            throw new Exception("ZIP包解压失败");
        }
    }

    /**
     * 获取发票图片地址
     * @METHODS GET
     * @author:luxu
     * @time:2020/5/19
     **/

    public function getImage($children_invoice_number){

        return $this->purchase_db->from("purchase_items_invoice_info")->where("children_invoice_number",$children_invoice_number)
            ->select("invoice_image")->get()->row_array();
    }

    /**
     * 通过发票清单号获取数据信息
     * @param $invoceNumber   string     发票清单号
     * @author:luxu
     * @time:2020/5/23
     **/

    public function getInvoiceNumber($invoceNumber){

        $result = $this->purchase_db->from("purchase_items_invoice_info")->where("invoice_number",$invoceNumber)
            ->select("id")->get()->row_array();
        return $result;
    }

    /**
     * 获取日志信息
     * @param $ids   array  id
     * @author:luxu
     * @time:2020/6/29
     **/
    public function getLogs($ids){

        return $this->purchase_db->from("invoice_examine_log")->where_in("ids",$ids)->get()->result_array();
    }

    /**
     *  获取采购单信息
     *  @params: $ids  array id 采购单明细ID
     *  @author:luxu
     *  @time：2020/8/24
     **/
    public function getPurchaseOrders($ids){

        $result = $this->purchase_db->from("purchase_order_items")->where_in("id",$ids)->select("id,confirm_amount,purchase_number")->get()->result_array();
        $result = array_column($result,NULL,"id");
        return $result;
    }

    public function getCompactDemandNumber($compact){

        $this->purchase_db->from('purchase_order_items as ppoi');
        $this->purchase_db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $this->purchase_db->join('purchase_suggest_map as sug', 'ppoi.purchase_number=sug.purchase_number AND ppoi.sku=sug.sku', 'left');
        $this->purchase_db->join('purchase_compact_items as comp', 'ppoi.purchase_number=comp.purchase_number', 'left');

        $result = $this->purchase_db->select("sug.demand_number,comp.compact_number")->where_in('comp.compact_number',$compact)->get()->result_array();
        return $result;
    }


}
