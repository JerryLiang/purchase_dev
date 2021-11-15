<?php

/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2018/12/27 0027 11:23
 */
class Purchase_order_tax_model extends Purchase_model {

    protected $table_name = 'purchase_order';
    protected $table_customs = 'declare_customs';
    protected $table_product= 'product';
    /**
     * Purchase_order_model constructor.
     */
    public function __construct() {
        parent::__construct();

        //$this->load->model('supplier_model', '', false, 'warehouse_results'); // 仓库入库记录
        //$this->load->model('purchase_order_items_model', '', false, 'purchase'); // 采购单子表
    }


    public function list_title(){
        $key_value = ['备货单号', 'sku', '采购单号', '合同号', '采购单状态', '产品名称', '采购下单日期', '是否退税',
            '采购仓库', '采购员', '税点', '开票点%', '含税单价', '是否商检', '供货商名称',
            '采购数量', '实际入库数量/金额', '已报关数量', '已生成发票清单', '已开票数量',
            '未开票数量', '报关状态', '报关单号', '付款状态', '出口海关编码', '开票品名', '开票单位',
            '报关品名', '报关单位', '发票清单', '是否完结', '开票状态', '合同开票状态'];
        return $key_value;

    }

    public function get_tax_order_tacking_list()
    {
//        $this->purchase_db->select()
//            ->from('purchase_order_items as a')
//            ->join('')
    }


    /**
     * 采购单列表列名称
     * @author Jolon
     * @return array
     */
    public function table_columns() {
        $key_value = [
            // 采购单信息
            'purchase_number' => '采购单号',
            'purchase_type_id' => '业务线',
            'purchase_order_status' => '采购单状态',
            'source' => '合同来源',
            'warehouse_code' => '仓库编码',
            'supplier_id' => '供应商ID',
            'supplier_code' => '供应商编码',
            'supplier_name' => '供应商名字',
            'is_drawback' => '是否退税',
            'is_expedited' => '加急采购单',
            'create_type_id' => '创建类型',
            'buyer_id' => '采购员ID',
            'buyer_name' => '采购人名称',
            'pay_type' => '支付方式',
            'account_type' => '结算方式',
            'merchandiser_id' => '跟单员ID',
            'merchandiser_name' => '跟单员名称',
            'currency_code_id' => '币种',
            'shipping_method_id' => '供应商运输',
            'pay_status' => '付款状态',
            'is_freight' => '运费支付',
            'freight_calculate_mode' => '运费计算方式',
            'freight' => '运费',
            'total_price' => '订单总额',
            'refund_status' => '退款状态',
            'first_plan_product_arrive_time' => '首次预计到货时间',
            'plan_product_arrive_time' => '预计到货时间',
            'audit_time' => '审核时间',
            'audit_name' => '审核人',
            'audit_note' => '审核备注',
            'confirm_note' => '确认备注',
            'complete_type' => '完成类型',
            'is_check_goods' => '是否验货',
            'transport_code' => '物流单号',
            'create_user_name' => '添加人',
            'create_time' => '添加时间',
            'modify_time' => '更新时间',
            'modify_user_name' => '最后一次更新操作人',
            // 产品信息
            'items_id' => '采购单明细ID',
            'sku' => '产品SKU',
            'product_name' => '产品名称',
            'purchase_amount' => '购买数量',
            'purchase_unit_price' => '采购单价',
            'confirm_amount' => '确认数量',
            'receive_amount' => '收货数量',
            'upselft_amount' => '上架数量',
            'sales_status' => '销售状态',
            'product_img_url' => 'SKU图片',
            'is_exemption' => '是否免检',
            'pur_ticketed_point' => '采购开票点',
            'product_base_price' => '产品初始单价',
            'is_cancel' => 'SKU是否作废',
        ];

        return $key_value;
    }


    /**
     * 获取 含税订单
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-8
     */
    public function get_purchase_tax_list($params, $offset, $limit,$field='ppoi.*',$group='',$export=false) {
        $query_builder_list      = clone($this->purchase_db);
        $query_builder_list->select($field);
        $query_builder_list->from('purchase_order_items as ppoi');
        $query_builder_list->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $query_builder_list->join('purchase_suggest_map as sug', 'ppoi.purchase_number=sug.purchase_number AND ppoi.sku=sug.sku', 'left');
        $query_builder_list->join('declare_customs as cu', 'ppoi.purchase_number=cu.purchase_number AND ppoi.sku=cu.sku', 'left');
        $query_builder_list->join('purchase_compact_items as comp', 'ppoi.purchase_number=comp.purchase_number', 'left');

         //未开票数量
        if(!empty($params['min_no_invoice']) && isset($params['min_no_invoice'])){ 
            $purchase_number_arr = $this->no_customs_quantity_by_purchase_number($params['min_no_invoice'],'min');
            if(!empty($purchase_number_arr)){
                $query_builder_list->where_in('ppoi.purchase_number', $purchase_number_arr);    
            }
            
        }
        if(!empty($params['max_no_invoice']) && isset($params['max_no_invoice'])){
            $purchase_number_arr = $this->no_customs_quantity_by_purchase_number($params['min_no_invoice'],'max');
            if(!empty($purchase_number_arr)){
               $query_builder_list->where_in('ppoi.purchase_number', $purchase_number_arr); 
            }
            
        }
        if(!empty($params['purchase_number']) && isset($params['purchase_number'])){
            $query_builder_list->where('ppoi.purchase_number', $params['purchase_number']);
        }
        if(!empty($params['buyer_id']) && isset($params['buyer_id'])){
            $query_builder_list->where('ppo.buyer_id', $params['buyer_id']);
        }
        if(!empty($params['customs_number']) && isset($params['customs_number'])){
            $query_builder_list->where('cu.customs_number', $params['customs_number']);
        }
        if (isset($params['sku']) and $params['sku']) {// SKU
            $sku = query_string_to_array($params['sku']);
            $query_builder_list->where_in('ppoi.sku', $sku);
        }
        if(!empty($params['is_check_goods']) && isset($params['is_check_goods'])){
            $query_builder_list->where('ppo.is_check_goods', $params['is_check_goods']);
        }
        if(!empty($params['customs_name']) && isset($params['customs_name'])){
            $query_builder_list->where('cu.customs_name', $params['customs_name']);
        }
        if(!empty($params['invoice_code_left']) && isset($params['invoice_code_left'])){
            $query_builder_list->where('cu.invoice_code_left', $params['invoice_code_left']);
        }
        if(!empty($params['invoice_code_right']) && isset($params['invoice_code_right'])){
            $query_builder_list->where('cu.invoice_code_right', $params['invoice_code_right']);
        }
        if(!empty($params['supplier_code']) && isset($params['supplier_code'])){
            $query_builder_list->where('ppo.supplier_code', $params['supplier_code']);
        }
        if(!empty($params['order_create_time_start']) && isset($params['order_create_time_start']) ){
            $query_builder_list->where('ppo.create_time>=', $params['order_create_time_start']);
        }
        if(!empty($params['order_create_time_end']) && isset($params['order_create_time_end'])){
            $query_builder_list->where('ppo.create_time<=', $params['order_create_time_end']);
        }
        if(!empty($params['customs_time_start']) && isset($params['customs_time_start'])){
            $query_builder_list->where('cu.customs_time>=', $params['customs_time_start']);
        }
        if(!empty($params['customs_time_end']) && isset($params['customs_time_end'])){
            $query_builder_list->where('cu.customs_time<=', $params['customs_time_end']);
        }
       if(!empty($params['is_abnormal']) && isset($params['is_abnormal'])){
           $query_builder_list->where('ppoi.is_abnormal', $params['is_abnormal']);
       }
        if(isset($params['is_end']) and $params['is_end']!==''){
            $query_builder_list->where('ppoi.is_end', $params['is_end']);
        }
        if(!empty($params['customs_status']) && isset($params['customs_status'])){
            $query_builder_list->where('ppoi.customs_status', $params['customs_status']);
        }

        if(!empty($params['purchase_order_status']) && isset($params['purchase_order_status'])){
             $query_builder_list->where('ppo.purchase_order_status', $params['purchase_order_status']);
        }
        if(!empty($params['pay_status']) && isset($params['pay_status'])){
             $query_builder_list->where('ppo.pay_status', $params['pay_status']);
        }

        if(!empty($params['ids'])){
            $query_builder_list->where_in('ppoi.id', explode(',', $params['ids']));
        }

        //业务线搜索
        if(isset($params['purchase_type_id']) && !empty($params['purchase_type_id'])){
            if(is_array($params['purchase_type_id'])){
                $query_builder_list->where_in('ppo.purchase_type_id',$params['purchase_type_id']);
            }else{
                $query_builder_list->where('ppo.purchase_type_id',$params['purchase_type_id']);
            }

        }
        //退税的都放在含税订单页面
        $query_builder_list->where('is_drawback="'.PURCHASE_IS_DRAWBACK_Y.'" AND (purchase_order_status>="'.PURCHASE_ORDER_STATUS_WAITING_ARRIVAL.'" OR purchase_order_status="'.PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT.'")');

        //FBA采购订单仓库为退税仓,海外仓采购订单“是否退税”为“是”  采购单状态是等待到货后的状态
        /*
        if(!empty($params['purchase_type_id'])){
            if($params['purchase_type_id']==PURCHASE_TYPE_FBA){
                $query_builder_list->where('ppo.purchase_type_id="'.PURCHASE_TYPE_FBA.'" AND warehouse_code="TS" AND purchase_order_status>="'.PURCHASE_ORDER_STATUS_WAITING_ARRIVAL.'"');
            }elseif($params['purchase_type_id']==PURCHASE_TYPE_OVERSEA){
                $query_builder_list->where('ppo.purchase_type_id="'.PURCHASE_TYPE_OVERSEA.'" AND is_drawback="'.PURCHASE_IS_DRAWBACK_Y.'" AND purchase_order_status>="'.PURCHASE_ORDER_STATUS_WAITING_ARRIVAL.'"');
            }
        }else{
            $query_builder_list->where('((ppo.purchase_type_id="'.PURCHASE_TYPE_FBA.'" AND warehouse_code="TS") OR (ppo.purchase_type_id="'.PURCHASE_TYPE_OVERSEA.'" AND is_drawback="'.PURCHASE_IS_DRAWBACK_Y.'")) AND purchase_order_status>="'.PURCHASE_ORDER_STATUS_WAITING_ARRIVAL.'"');
        }
        */

        if(!empty($group)){
            $query_builder_list->group_by($group);     
        }
        //统计总数
        $clone_db = clone($query_builder_list);
        $total_count=$clone_db->count_all_results();//符合当前查询条件的总记录数
        if($export){//导出不需要分页查询
            $results = $query_builder_list->get()->result_array();
        }else{
            $results = $query_builder_list->order_by('ppo.create_time','desc')->limit($limit, $offset)->get()->result_array();
        }

        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'limit'     => $limit,
            ]
        ];
        return $return_data;
    }
    //生成发票清单
    public function add_invoice($params,$group=''){
        $this->purchase_db->select('ppoi.id,ppo.purchase_number,ppo.purchase_type_id,ppo.purchase_name,ppo.supplier_name,ppo.supplier_code,ppo.buyer_id,ppo.buyer_name,ppoi.purchase_amount,ppoi.upselft_amount,ppoi.purchase_unit_price,ppoi.sku,ppoi.customs_status,ppoi.is_end,sug.demand_number');
        $this->purchase_db->from('purchase_order_items as ppoi');
        $this->purchase_db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $this->purchase_db->join('purchase_suggest_map as sug', 'ppo.purchase_number=sug.purchase_number', 'left');
        $this->purchase_db->join('declare_customs as cu', 'ppoi.purchase_number=cu.purchase_number', 'left');
        if(!empty($params['purchase_number']) && isset($params['purchase_number'])){
            $this->purchase_db->where('ppo.purchase_number', $params['purchase_number']);
        }
        if(!empty($params['buyer_id']) && isset($params['buyer_id'])){
            $this->purchase_db->where('ppo.buyer_id', $params['buyer_id']);
        }
        if(!empty($params['customs_number']) && isset($params['customs_number'])){
            $this->purchase_db->where('cu.customs_number', $params['customs_number']);
        }
        if (isset($params['sku']) and $params['sku']) {// SKU
            $sku = query_string_to_array($params['sku']);
            $this->purchase_db->where_in('ppoi.sku', $sku);
        }
        if(!empty($params['is_check_goods']) && isset($params['is_check_goods'])){
            $this->purchase_db->where('ppo.is_check_goods', $params['is_check_goods']);
        }
        if(!empty($params['customs_name']) && isset($params['customs_name'])){
            $this->purchase_db->where('cu.customs_name', $params['customs_name']);
        }
        if(!empty($params['invoice_code_left']) && isset($params['invoice_code_left'])){
            $this->purchase_db->where('cu.invoice_code_left', $params['invoice_code_left']);
        }
        if(!empty($params['invoice_code_right']) && isset($params['invoice_code_right'])){
            $this->purchase_db->where('cu.invoice_code_right', $params['invoice_code_right']);
        }
        if(!empty($params['supplier_code']) && isset($params['supplier_code'])){
            $this->purchase_db->where('ppo.supplier_code', $params['supplier_code']);
        }

        if(!empty($params['order_create_time_start']) && isset($params['order_create_time_start']) ){
            $this->purchase_db->where('ppo.create_time>=', $params['order_create_time_start']);
        }
        if(!empty($params['order_create_time_end']) && isset($params['order_create_time_end'])){
            $this->purchase_db->where('ppo.create_time<=', $params['order_create_time_end']);
        }
        if(!empty($params['customs_time_start']) && isset($params['customs_time_start'])){
            $this->purchase_db->where('cu.customs_time>=', $params['customs_time_start']);
        }
        if(!empty($params['customs_time_end']) && isset($params['customs_time_end'])){
            $this->purchase_db->where('cu.customs_time<=', $params['customs_time_end']);
        }
//        if(!empty($params['is_abnormal']) && isset($params['is_abnormal'])){
//            $this->purchase_db->where('ppo.is_abnormal', $params['is_abnormal']);
//        }
        if(isset($params['is_end']) && $params['is_end']!=''){
            $this->purchase_db->where('ppoi.is_end', $params['is_end']);
        }
        if(!empty($params['customs_status']) && isset($params['customs_status'])){
            $this->purchase_db->where('ppoi.customs_status', $params['customs_status']);
        }
        if(!empty($params['id'])){
            $this->purchase_db->where_in('ppoi.id',$params['id']);
        }
        //FBA采购订单仓库为退税仓,海外仓采购订单“是否退税”为“是” 
        if(!empty($params['purchase_type_id'])){
            if($params['purchase_type_id']==PURCHASE_TYPE_FBA){
                $this->purchase_db->where('ppo.purchase_type_id="'.PURCHASE_TYPE_FBA.'" AND warehouse_code="TS" AND purchase_order_status>="'.PURCHASE_ORDER_STATUS_WAITING_ARRIVAL.'"');
            }elseif($params['purchase_type_id']==PURCHASE_TYPE_OVERSEA){
                $this->purchase_db->where('ppo.purchase_type_id="'.PURCHASE_TYPE_OVERSEA.'" AND is_drawback="'.PURCHASE_IS_DRAWBACK_Y.'" AND purchase_order_status>="'.PURCHASE_ORDER_STATUS_WAITING_ARRIVAL.'"');
            }elseif($params['purchase_type_id']==PURCHASE_TYPE_FBA_BIG){
                $this->purchase_db->where('ppo.purchase_type_id="'.PURCHASE_TYPE_FBA_BIG.'" AND is_drawback="'.PURCHASE_IS_DRAWBACK_Y.'" AND purchase_order_status>="'.PURCHASE_ORDER_STATUS_WAITING_ARRIVAL.'"');
            }
        }else{
            $this->purchase_db->where('((ppo.purchase_type_id="'.PURCHASE_TYPE_FBA.'" AND warehouse_code="TS") OR (ppo.purchase_type_id="'.PURCHASE_TYPE_OVERSEA.'" AND is_drawback="'.PURCHASE_IS_DRAWBACK_Y.'"))  OR (ppo.purchase_type_id="'.PURCHASE_TYPE_FBA_BIG.'" AND is_drawback="'.PURCHASE_IS_DRAWBACK_Y.'")) AND purchase_order_status>="'.PURCHASE_ORDER_STATUS_WAITING_ARRIVAL.'"');
        }
        if(!empty($group)){
           $this->purchase_db->group_by($group); 
        }
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

    //查未开票数据的采购单号查询
    public function no_customs_quantity_by_purchase_number($no_customs_quantity,$compare='min'){
        if(empty($no_customs_quantity) || empty($compare)){
            return [];
        }
        $query_builder      = clone($this->purchase_db);
        $query_builder->select('dec.purchase_number,dec.sku,SUM(dec.customs_quantity) as no_customs_quantity');
        $query_builder->from('declare_customs as dec');
        $query_builder->join('purchase_invoice_list as in', 'dec.invoice_number=in.invoice_number AND dec.invoice_number=""', 'left');
        $query_builder->group_by('dec.purchase_number,dec.sku');
        if($compare=='min'){
            $query_builder->having('SUM(dec.customs_quantity) > "'.$no_customs_quantity.'"');    
        }else{
            $query_builder->having('SUM(dec.customs_quantity) < "'.$no_customs_quantity.'"');
        }
        $results = $query_builder->get()->result_array();
        if(!empty($results)){
            $purchase_number_arr = array_column($results, 'purchase_number');
        }else{
           $purchase_number_arr = array(); 
        }
        return $purchase_number_arr;
        
    }
    /**
     * 获取推送财务数据
     */
    public function get_push_finance(){     
        $customs_order = $this->purchase_db->select('*')
                ->where('is_clear', 2)
                ->where('is_finance', 0)
                ->where('is_invoice', 1)
                ->limit(100)
                ->get($this->table_customs)
                ->result_array();
        if (empty($customs_order)) {
            return [];
        }
        //判断是否已开票
        $invoice_number_list = array_column($customs_order, 'invoice_number');
        $invoice_number_list = array_filter($invoice_number_list);
        $invoice_number_list = array_unique($invoice_number_list);

        $invoice = $this->purchase_db->select('invoice_number,audit_status')
                        ->where_in('invoice_number', $invoice_number_list)
                        ->get('purchase_invoice_list')->result_array();

        if (empty($invoice)) {
            return [];
        }
        foreach ($invoice as $val) {
            if ($val['audit_status'] == 4) {
                $invoice_list[] = $val['invoice_number'];
            }
        }
        if (empty($invoice_list)) {
            return [];
        }
        $customs = $this->purchase_db->select('*')
                ->where_in('invoice_number', $invoice_list)
                ->limit(100)
                ->get($this->table_customs)
                ->result_array();

        $pur_number = array_column($customs, 'purchase_number');
        $sku_list = array_column($customs, 'sku');
        $purchase_number_list = array_unique($pur_number);
        $sku_list = array_unique($sku_list);
        $order = $this->purchase_db
                        ->select('supplier_code,supplier_name,purchase_number')
                        ->where_in('purchase_number', $purchase_number_list)
                        ->get($this->table_name)->result_array();
        if (empty($order)) {
            return [];
        }
        //采购数量

        $product = $this->purchase_db
                ->select('product_name,sku')
                ->where_in('sku', $sku_list)
                ->get($this->table_product)
                ->result_array();
        if (empty($product)) {
            return [];
        }
        $order_supplier_name = array_column($order, 'supplier_name', 'purchase_number');
        $order_supplier_code = array_column($order, 'supplier_code', 'purchase_number');
        $product_product_name = array_column($product, 'product_name', 'sku');
        $inventory_numbers = array_column( $customs,'invoice_number');
//        $childrenNumbers = $this->purchase_db->select("id,children_invoice_number,invoice_number")->where_in("invoice_number",$inventory_numbers)->get('purchase_items_invoice_info')->result_array();
//        $childrenNumbers = array_column($childrenNumbers,'children_invoice_number','invoice_number');
        //获取采购单供应商
        foreach ($customs as $key => $val) {
            $customs[$key]['supplier_code'] = isset($order_supplier_code[$val['purchase_number']]) ? $order_supplier_code[$val['purchase_number']] : '';
            $customs[$key]['supplier_name'] = isset($order_supplier_name[$val['purchase_number']]) ? $order_supplier_name[$val['purchase_number']] : '';
            $customs[$key]['product_name'] = isset($product_product_name[$val['sku']]) ? $product_product_name[$val['sku']] : '';
            //$customs[$key]['inventory_number'] = isset($childrenNumbers[$val['invoice_number']]) ? $childrenNumbers[$val['invoice_number']] : '';
        }

        return $customs;
    }
    /**
     * 更新报关详情是否推送
     * @author harvin
     * @param type $purchase_number
     * @param type $sku
     * @return boolean
     */
    public function get_push_finance_status($purchase_number,$sku){
       $res= $this->purchase_db->where('purchase_number',$purchase_number)
        ->where('sku',$sku)->update($this->table_customs,['is_finance'=>1]);
       if(empty($res)) {
           return false;
       }else{
           return TRUE;
       }       
    }

    /**
     * 开票单位中含有2个或者以上单位时，生成的开票合同中的开票单位需要删除千克
     * @author Jaden
     * @param string $declare_unit
     * @return string
     */
    public function get_new_declare_unit($declare_unit){
         $new_declare_unit ='';
        if(empty($declare_unit)){
            return '';
        }
        $declare_arr = explode('/', $declare_unit);
        if(count($declare_arr)>=2 and in_array('千克', $declare_arr)){
          $declare_arr  =array_flip($declare_arr);
          unset($declare_arr['千克']);
          $declare_arr  =array_flip($declare_arr);
          $new_declare_unit = implode('/', $declare_arr);
        }else{
          $new_declare_unit = $declare_unit;  
        }
        return $new_declare_unit;
        

    }


    // -------------------------- new -----------------------
    public function batch_create_invoice_listing($success_list = [],$update_invoice_status = [],$invoice_data = [])
    {
        if (empty($success_list) || empty($update_invoice_status) || empty($invoice_data)){
            throw new Exception('数据异常');
        }
        $this->purchase_db->trans_start();
        //写入invoice_item表
        $this->purchase_db->insert_batch('purchase_invoice_item',$success_list);
        //pur_purchase_order_items 表对应的po+sku的开票状态改为开票中
        $this->purchase_db->update_batch('purchase_order_items',$update_invoice_status,'id');
        //开票列表新增一条开票单号
        $this->purchase_db->insert_batch('purchase_invoice_list',$invoice_data);
        $this->purchase_db->trans_complete();
        if ($this->purchase_db->trans_status() === false) {
            return false;
        }else{
            return true;
        }
    }
//    public function batch_create_invoice_listing($success_list = [],$update_invoice_status = [],$invoice_data = [])
//    {
//        if (empty($success_list) || empty($update_invoice_status) || empty($invoice_data)){
//            throw new Exception('数据异常');
//        }
//        $this->db_local = $this->load->database('local',true);
////pr($success_list);
////pr($update_invoice_status);
////pr($invoice_data);
////        exit;
//        $this->db_local->trans_start();
//        //写入invoice_item表
//        $this->db_local->insert_batch('purchase_invoice_item',$success_list);
//        //pur_purchase_order_items 表对应的po+sku的开票状态改为开票中
//        $this->db_local->update_batch('purchase_order_items',$update_invoice_status,'id');
//        //开票列表新增一条开票单号
//        $this->db_local->insert_batch('purchase_invoice_list',$invoice_data);
//        $this->db_local->trans_complete();
//        if ($this->db_local->trans_status() === false) {
//            return false;
//        }else{
//            return true;
//        }
//    }

}
