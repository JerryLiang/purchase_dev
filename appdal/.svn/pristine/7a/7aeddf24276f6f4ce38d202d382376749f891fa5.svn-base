<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/11/27
 * Time: 11:52
 */

class Purchase_invoice_list_model extends Purchase_model
{

    protected $table_name = 'purchase_invoice_list';// 数据表名称
    protected $declare_customs_table = 'declare_customs';
    protected $table_invoice_detail = 'purchase_items_invoice_info';
    protected $table_invoice_item = 'purchase_invoice_item';
    protected $table_purchase_order = 'purchase_order';
    protected $table_purchase_order_items = 'purchase_order_items';
    protected $table_product = 'product';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_order');
    }


    /**
     * 订单退税列表页标题
     * @author Manson
     * @return array
     */
    public function list_title(){
        $title = ['ID','发票清单号','发票总金额','币种','供应商名称','发票代码','发票号码','发票清单时间','采购员','状态','创建人/创建时间','提交人/提交时间',
            '审核人/审核时间'];
        return $title;
    }


    /**
     * 发票清单列表
     * @author Manson
     * @param $params
     * @param $offset
     * @param $limit
     * @param int $page
     * @return array
     */
    public function get_invoice_listing_list($params, $offset, $limit,$page=1)
    {

        $select_cols = "a.*";
        $order_info = 'a.create_time desc';
        $group_by_info = 'a.id';

        $this->purchase_db->select($select_cols);

        $this->purchase_db->from($this->table_name.' a');

        if(isset($params['invoice_number']) && !empty($params['invoice_number'])){
            $invoice_number = query_string_to_array($params['invoice_number']);
            $this->purchase_db->where_in('a.invoice_number',$invoice_number);
        }
        if(isset($params['purchase_number']) && !empty($params['purchase_number'])){
            $this->purchase_db->join($this->table_invoice_item. ' c','a.invoice_number = c.invoice_number','left');
            $this->purchase_db->where_in('c.purchase_number',query_string_to_array($params['purchase_number']));
        }
        if(isset($params['purchase_user_id']) && !empty($params['purchase_user_id'])){

            if( is_array($params['purchase_user_id'])) {
                $this->purchase_db->where_in('a.purchase_user_id', $params['purchase_user_id']);
            }else{
                $this->purchase_db->where('a.purchase_user_id', $params['purchase_user_id']);
            }
        }
        if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
            $this->purchase_db->where('a.supplier_code',$params['supplier_code']);
        }
        if(isset($params['audit_status']) && !empty($params['audit_status'])){
            $this->purchase_db->where('a.audit_status',$params['audit_status']);
        }
        if(isset($params['create_time_start']) && !empty($params['create_time_start'])){
            $this->purchase_db->where('a.create_time>=',$params['create_time_start']);
        }
        if(isset($params['create_time_end']) && !empty($params['create_time_end'])){
            $this->purchase_db->where('a.create_time<=',$params['create_time_end']);
        }

        if( isset($params['is_gateway']) && !empty($params['is_gateway'])){

            if( $params['is_gateway'] == 1){

                $this->purchase_db->where("a.is_gateway",1);
            }else{
                $this->purchase_db->where("a.is_gateway",0);
            }
        }

        if (isset($params['invoice_code_left']) || isset($params['invoice_code_right'])){
            $this->purchase_db->join($this->table_invoice_detail.' b','a.invoice_number=b.invoice_number','left');
            if (!empty($params['invoice_code_left'])){
                $this->purchase_db->where('b.invoice_code_left',$params['invoice_code_left']);
            }
            if (!empty($params['invoice_code_right'])){
                $this->purchase_db->where('b.invoice_code_right',$params['invoice_code_right']);
            }
        }

        if(isset($params['compact_number']) && !empty($params['compact_number'])){
            $this->purchase_db->where('a.compact_number',$params['compact_number']);
        }

        if(!empty($params["purchase_type"])){
            $this->purchase_db->join('pur_purchase_compact_items c','b.purchase_number = c.purchase_number','left');
            $this->purchase_db->join('pur_purchase_compact d','c.compact_number = d.compact_number','left');
            $this->purchase_db->where("d.source", $params["purchase_type"]);
        }

        //发票清单列表 只显示待提交和待采购开票
        $this->purchase_db->where_in('a.audit_status',[INVOICE_TO_BE_CONFIRMED,INVOICE_TO_BE_PURCHASE_INVOICE]);

        $this->purchase_db->group_by($group_by_info);
        $this->purchase_db->order_by($order_info);
        //统计总数
        $clone_db = clone($this->purchase_db);
        $total_count=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数
        $this->purchase_db=$clone_db;
        $results = $this->purchase_db->limit($limit, $offset)->get()->result_array();

        if(!empty($results)){

            foreach( $results as $key=>$value){

                if( $value['is_gateway'] == 1){

                    $results[$key]['is_gateway_ch'] = "是";
                }else{
                    $results[$key]['is_gateway_ch'] = "否";
                }
            }
        }
//        echo $this->purchase_db->last_query();exit;
//        $this->load->model('purchase_suggest/forecast_plan_model');
        $this->load->model('user/Purchase_user_model');
        $user_list=$this->Purchase_user_model->get_user_all_list();
        $return_data = [

            'data_list' => [
                'value' => $results,
                'key'   => $this->list_title(),
                'drop_down_box' => [
                    'user_list' => array_column($user_list, 'name','id'),
//                    'supplier_list' => $this->forecast_plan_model->get_supplier_down_box(),
                    //
                    //'status_list' => invoice_number_status(),
                    'status_list' => [1=>'待提交',2=>'待采购开票'],
                    'is_gateway' => [1=>'是',2=>'否'],
                    'purchase_type'=>getPurchaseType(),
                ],
            ],
            'paging_data' => [
                'total'     => $total_count,
                'offset'    => $page,
                'limit'     => $limit,
            ]
        ];
        return $return_data;
    }
    /**
     * 根据采购单号和sku维度获取发票清单详情
     * @author Manson
     * @param $params
     * @param $offset
     * @param $limit
     * @param string $field
     * @return array
     */
    public function get_invoice_detail($params = [],$field='*',$offset = '', $limit = '')
    {
        $this->purchase_db->select($field)
            ->from($this->table_invoice_detail.' a')
            ->join($this->table_name.' b','a.invoice_number = b.invoice_number','left');
        if (isset($params['purchase_number']) && !empty($params['purchase_number'])){
            $this->purchase_db->where('a.purchase_number',$params['purchase_number']);
        }
        if (isset($params['sku']) && !empty($params['purchase_number'])){
            $this->purchase_db->where('a.sku',$params['sku']);
        }
        if (isset($params['invoice_number']) && !empty($params['invoice_number'])){
            $this->purchase_db->where('a.invoice_number',$params['invoice_number']);
        }
        if (isset($params['invoice_number_list']) && !empty($params['invoice_number_list'])){
            $this->purchase_db->where_in('a.invoice_number',$params['invoice_number_list']);
        }

        if (!empty($limit) || !empty($offset)){
            $clone_db = clone($this->purchase_db);
            $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数
            $this->purchase_db=$clone_db;
            $result=$this->purchase_db->limit($limit, $offset)->order_by('a.id','DESC')->get()->result_array();
            $return_data = [
                'value'   => $result,
                'page_data' => [
                    'total'     => $total,
                    'limit'     => $limit,
                ]
            ];
            return $return_data;
        }else{
            $result = $this->purchase_db->get()->result_array();
            return $result;
        }
    }

    /**
     * 根据查询条件获取多条发票清单详情
     * @author Manson
     * @param $params
     * @param $offset
     * @param $limit
     * @param string $field
     * @return array
     */
    public function get_batch_invoice_detail($params,$offset, $limit,$field='*')
    {
        $this->purchase_db->select($field)
            ->from($this->table_invoice_item.' a')
            ->join($this->table_name.' b','a.invoice_number = b.invoice_number','left')
            ->join('pur_product c',' a.sku = c.sku','left');
        if (isset($params['ids']) && !empty($params['ids'])){
            $this->purchase_db->where_in('b.id',$params['ids']);
        }
        $clone_db = clone($this->purchase_db);
        $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数
        $this->purchase_db=$clone_db;
        $results=$this->purchase_db->limit($limit, $offset)->order_by('a.id','DESC')->get()->result_array();
        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total,
                'limit'     => $limit,
            ]
        ];
        return $return_data;
    }



    /**
     * 根据备货单号和发票清单号查看开票信息
     * @author Manson
     * @param $invoice_number
     * @param $demand_number
     * @return array
     */
    public function get_more_invoice_info($invoice_number,$demand_number)
    {
        $result = $this->purchase_db->select('*')
            ->where('invoice_number',$invoice_number)
            ->where('demand_number',$demand_number)
            ->get($this->table_invoice_detail)
            ->result_array();
        return $result;

    }

    /**
     * 获取发票清单号 总的已开票数量
     * @author Manson
     * @param $purchase_number
     * @param $sku
     * @return array
     */
/*    public function get_invoice_number($purchase_number,$sku)
    {
        $result = $this->purchase_db->select('invoice_number,invoiced_qty,audit_status')
            ->where('purchase_number',$purchase_number)
            ->where('sku',$sku)
            ->get($this->table_invoice_detail)
            ->result_array();
        //总的已开票数量
        $total_invoiced_qty = 0;
        if (!empty($result)){
            foreach ($result as $key => $item){
                $data['invoice_number_list'][] = $item['invoice_number'];
                if ($item['audit_status'] == INVOICE_STATES_AUDIT){//只统计审核通过的开票数量
                    $total_invoiced_qty+=$item['invoiced_qty'];
                }
            }
            //已开票+开票中(生成开票清单的开票数量)
            $result = $this->purchase_db->select('SUM(`b`.`app_invoice_qty`) as total_qty')
                ->from($this->table_invoice_item.' a')
                ->join($this->table_name.' b','a.invoice_number = b.invoice_number','left')
                ->where('a.purchase_number',$purchase_number)
                ->where('a.sku',$sku)
                ->where('b.audit_status !=',3)
                ->get()
                ->row_array();
            $data['total_invoiced_qty'] = $total_invoiced_qty;

            $data['all_invoiced_qty'] = isset($result['total_qty'])??0 + $total_invoiced_qty;
            return $data;
        }else{
            return [];
        }
    }*/


    /**
     * 提交详情页
     * @author Manson
     * @param $invoice_number
     * @return array
     */
    public function get_submit_detail($invoice_number)
    {
        $result = $this->purchase_db->select('a.*,
         c.product_name, c.export_cname, d.supplier_name,
         d.buyer_name, d.create_time as order_time,
         e.upselft_amount, e.id as items_id, e.invoiced_qty')
            ->from($this->table_invoice_item.' a')
            ->join($this->table_product.' c','a.sku = c.sku','left')
            ->join($this->table_purchase_order.' d','a.purchase_number = d.purchase_number','left')
            ->join($this->table_purchase_order_items. ' e','a.purchase_number = e.purchase_number AND a.sku = e.sku','left')
            ->where('a.invoice_number',$invoice_number)
            ->get()
            ->result_array();
//        echo $this->purchase_db->last_query();exit;
//        pr($result);exit;
        return $result;
    }


    /**
     * 根据发票清单号,查询单条信息
     * @author Manson
     * @param $invoice_number
     * @return array
     */
    public function get_one($invoice_number)
    {
        return $this->purchase_db->select('*')
            ->where('invoice_number',$invoice_number)
            ->get($this->table_name)
            ->row_array();
    }


    /**
     * 下载开票合同
     * @author Manson
     */
    public function invoice_contract_detail($invoice_number)
    {
        $field = 'a.sku, a.purchase_number, a.unit_price, a.app_invoice_qty as invoiced_qty , b.product_name, b.customs_code, b.declare_cname, c.declare_unit, c.export_cname,
          d.purchase_name, d.buyer_name,d.create_time as order_time';
        $result = $this->purchase_db->select($field)
            ->from($this->table_invoice_item.' a')
            ->join($this->table_product.' b','a.sku = b.sku','left')
            ->join('purchase_order_items c','a.purchase_number = c.purchase_number AND a.sku = c.sku','left')
            ->join('purchase_order d','a.purchase_number = d.purchase_number','left')
            ->where('a.invoice_number',$invoice_number)
            ->get()
            ->result_array();
        return $result;
    }


    /**
     * 发票清单数量, 发票清单号, 发票代码
     *
     */
    public function get_invoice_info($purchase_number)
    {
        if(empty($purchase_number)){
            return [];
        }
        $result = $this->purchase_db->select('a.invoice_number,a.purchase_number,a.sku, b.invoice_code_left,b.invoice_code_right,b.audit_status')
            ->from($this->table_invoice_item.' a')
            ->join($this->table_invoice_detail.' b', 'a.invoice_number = b.invoice_number AND a.purchase_number = b.purchase_number AND a.sku = b.sku','left')
            ->where_in('a.purchase_number',$purchase_number)
            ->get()
            ->result_array();
        $invoice_map = [];
        foreach ($result as $key => $item){
            $_tag = sprintf('%s_%s',$item['purchase_number'],$item['sku']);
            if ($item['audit_status'] == INVOICE_STATES_AUDIT){//只显示审核通过的
//                if (!isset($invoice_map[$_tag]['invoice_count'])){
//                    $invoice_map[$_tag]['invoice_count'] = 1;
//                }else{
//                    $invoice_map[$_tag]['invoice_count'] +=1;
//                }
                $invoice_map[$_tag]['invoice_number_list'][] =  $item['invoice_number'];
                $invoice_map[$_tag]['invoice_code_left_list'][] =  $item['invoice_code_left'];
                $invoice_map[$_tag]['invoice_code_right_list'][] =  $item['invoice_code_right'];
            }
        }
        return $invoice_map;
    }

    /**
     * 导入开票信息时查询 通过发票单号查询采购单号,可开票数量,含税单价,
     * @author Manson
     */
    public function import_invoice_get_info($invoice_number_list){
        $result = $this->purchase_db->select('a.invoice_number, a.purchase_number, a.demand_number, a.app_invoice_qty, a.unit_price, b.audit_status')
            ->from($this->table_invoice_item.' a')
            ->join($this->table_name.' b','a.invoice_number = b.invoice_number','left')
            ->where_in('a.invoice_number',$invoice_number_list)
            ->get()
            ->result_array();
        if (empty($result)){
            throw new Exception('上传的数据异常,未查询到对应的数据');
        }
        foreach ($result as $key => $item){
            $_tag = sprintf('%s%s',$item['invoice_number'],$item['demand_number']);
            $purchase_info_map[$_tag] = [
                  'purchase_number' => $item['purchase_number'],
                  'purchase_unit_price' => $item['unit_price'],
                  'app_invoice_qty' => $item['app_invoice_qty'],
                  'audit_status' => $item['audit_status'],
            ];

        }
        $map['purchase_info_map'] =$purchase_info_map;
        return $map;
    }

    /**
     * 合同开票状态
     * 合同下的所有采购单的合同开票状态为完结 返回ture 反之返回false
     */
    public function check_compact_invoice_status($compact_number)
    {
        $result= $this->purchase_db->from('pur_purchase_compact_items a')
            ->join($this->table_purchase_order_items.' b','a.purchase_number = b.purchase_number','left')
            ->where('a.bind',1)
            ->where('a.compact_number',$compact_number)
            ->where('b.contract_invoicing_status',CONTRACT_INVOICING_STATUS_NOT)
            ->get()
            ->row_array();
        return empty($result)?true:false;

    }

    /**
     * 查询发票清单下的po
     */
    public function get_invoice_po($invoice_number_list)
    {
        if (empty($invoice_number_list)){
            return [];
        }
        $map = [];
        $result = $this->purchase_db->select('invoice_number,purchase_number')
            ->from($this->table_invoice_item)
            ->where_in('invoice_number',$invoice_number_list)
            ->get()->result_array();
        foreach ($result as $k => $item){
            $map[$item['invoice_number']][] = $item['purchase_number'];
        }

        return $map;

    }

    /**
     * 导出
     */
    public function export_list($params)
    {
        $select_cols = "a.id, a.invoice_number, a.purchase_number, a.sku, a.demand_number, a.unit_price, a.invoice_coupon_rate, a.app_invoice_qty,
        c.supplier_code, c.supplier_name, c.submit_user, c.submit_time, c.purchase_user_id, c.purchase_user_name,
        d.pur_ticketed_point, d.product_name, d.upselft_amount, d.export_cname,
        e.compact_number,c.is_gateway";

        $order_info = 'a.create_time desc';
        $group_by_info = 'a.id';

        $this->purchase_db->from($this->table_invoice_item. ' a')
            ->join($this->table_name. ' c', 'a.invoice_number=c.invoice_number', 'left')
            ->join($this->table_purchase_order_items. ' d','a.purchase_number = d.purchase_number and a.sku = d.sku','left')
            ->join('purchase_compact_items e','a.purchase_number = e.purchase_number','left');

        if (isset($params['ids']) && !empty($params['ids'])){
            $this->purchase_db->where_in('c.id',query_string_to_array($params['ids']));
        }

        if(isset($params['invoice_number']) && !empty($params['invoice_number'])){
            $invoice_number = query_string_to_array($params['invoice_number']);
            $this->purchase_db->where_in('a.invoice_number',$invoice_number);
        }

        if(isset($params['purchase_user_id']) && !empty($params['purchase_user_id'])){
            $this->purchase_db->where_in('c.purchase_user_id',query_string_to_array($params['purchase_user_id']));
        }
        if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
            $this->purchase_db->where('c.supplier_code',$params['supplier_code']);
        }

        if(isset($params['create_time_start']) && !empty($params['create_time_start'])){
            $this->purchase_db->where('a.create_time>=',$params['create_time_start']);
        }
        if(isset($params['create_time_end']) && !empty($params['create_time_end'])){
            $this->purchase_db->where('a.create_time<=',$params['create_time_end']);
        }

        if(isset($params['audit_status']) && !empty($params['audit_status'])){
            $this->purchase_db->where('c.audit_status',$params['audit_status']);
        }
        //发票清单列表 只显示待提交和待采购开票
        $this->purchase_db->where_in('c.audit_status',[INVOICE_TO_BE_CONFIRMED,INVOICE_TO_BE_PURCHASE_INVOICE]);
        $this->purchase_db->where('e.bind',1);

        $this->purchase_db->group_by($group_by_info);
        $this->purchase_db->select($select_cols);
        $clone_db = clone($this->purchase_db);
        $total_count=$clone_db->count_all_results();//符合当前查询条件的总记录数

        $quick_sql = $this->purchase_db->order_by($order_info)->get_compiled_select();


        $return_data = [
            'quick_sql'   => $quick_sql,
            'total_count' => $total_count
        ];
        return $return_data;

    }


    /**
     * 根据开票单号查询可开票数量(申请时的)
     * @author Manson
     * @param $invoice_number_list
     * @return array
     */
    public function get_app_invoice_qty($invoice_number_list)
    {
        $map = [];
        if (empty($invoice_number_list)){
            return [];
        }
        $result = $this->purchase_db->select('a.invoice_number,b.demand_number,b.app_invoice_qty,a.audit_status')
            ->from($this->table_name. ' a')
            ->join($this->table_invoice_item. ' b','a.invoice_number = b.invoice_number','left')
            ->where_in('a.invoice_number',$invoice_number_list)
            ->get()
            ->result_array();
        if (!empty($result)){
            foreach ($result as $key => $item){
                $tag = sprintf('%s%s',$item['invoice_number'],$item['demand_number']);
                $map[$tag] = [
                    'app_invoice_qty' => $item['app_invoice_qty'],
                    'audit_status' => $item['audit_status'],
                ];
            }
        }
//        echo $this->purchase_db->last_query();exit;
        return $map;
    }

    /**
     * 根据id查询审核状态
     * @author Manson
     * @param $ids
     * @return array
     */
    public function get_audit_status_by_ids($ids)
    {
        if (empty($ids)){
            return [];
        }
        $result = $this->purchase_db->select('id,audit_status,invoice_number')
            ->from($this->table_name)
            ->where_in('id',$ids)
            ->get()->result_array();
        return empty($result)?[]:array_column($result,NULL,'id');
    }

    public function setInvoiceGateWays($ids){

        $this->purchase_db->where_in("id",$ids)->update('purchase_invoice_list',['is_gateway'=>1]);
    }

    /**
     * 获取推送门户系统的发票号
     * @params  $ids    array   发票ID
     * @author:luxu
     * @time:2020/5/20
     **/

    public function getInvoiceMessage($ids = NULL,$invoice_number = NULL){

        try{

            if( !is_array($ids) && NULL !=$ids){

                $ids = [$ids];
            }

            $result = $this->purchase_db->from("purchase_invoice_list");
            if($ids != NULL && !empty($ids)) {
                $result->where_in("id", $ids);
            }

            if($invoice_number != NULL){

                $result->where("invoice_number",$invoice_number);
            }
            $data = $result->get()->result_array();
            return $data;
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }


    /**
     * 获取推送门户系统的发票号
     * @params  $ids    array   发票ID
     * @author:luxu
     * @time:2020/5/20
     **/

    public function getInvoiceGateWays($ids = NULL,$invoice_number = NULL){

        try{

            if( !is_array($ids) && NULL !=$ids){

                $ids = [$ids];
            }

            $result = $this->purchase_db->from("purchase_invoice_list");
            if($ids != NULL && !empty($ids)) {
                $result->where_in("id", $ids);
            }

            if($invoice_number != NULL){

                $result->where("invoice_number",$invoice_number);
            }
            $data = $result->where("is_gateway",1)->get()->result_array();
            return $data;
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 获取 采购单号对于的SKU 报关信息
     * @param $purchaseNumber    array   采购单号
     * @author :luxu
     * @time:2020/5/27
     **/
    public function getPurchaseSkuDeclare($purchaseNumber){

        $this->load->model('declare_customs_model','declare_customs');
        $result = $this->declare_customs->get_customs_clearance_details($purchaseNumber);
        return $result;
    }

    /**
     * 发票清单推送到门户系统
     * @params $ids   array   发票清单ID
     **/
    public function pushGateWays($ids){

        try{

            // 通过ID 获取发票清单信息
            $invoiceDatas = $this->purchase_db->from("purchase_invoice_list AS list")
                ->join("purchase_invoice_item AS item","list.invoice_number=item.invoice_number","LEFT")
                ->join("purchase_order_items AS items","item.purchase_number=items.purchase_number AND item.sku=items.sku","LEFT")
                ->join("purchase_compact AS compact","compact.compact_number=list.compact_number","LEFT")
                ->join("purchase_compact_file AS file","file.pc_id=compact.id","LEFT")
                ->join("declare_customs AS cu","cu.invoice_number=item.invoice_number AND cu.purchase_number=item.purchase_number AND item.sku=cu.sku","LEFT")
                ->where_in("list.id",$ids)->select("items.upselft_amount,cu.customs_number,item.unit_price,item.app_invoice_qty,list.*,item.*,file.file_path,
                items.uninvoiced_qty,items.upselft_amount,items.purchase_unit_price,items.uninvoiced_qty,items.confirm_amount")->get()->result_array();

            if(!empty($invoiceDatas)){
                $pushData = $pushGateWaysSku=[];
                foreach($invoiceDatas as $key=>$value){

                    if( isset($pushData[$value['invoice_number']])){

                        $pushData[$value['invoice_number']] = [];
                    }

                    if( !isset($pushGateWaysSku[$value['sku']])){

                        $pushGateWaysSku[$value['sku']] = [];
                    }

                    $pushData[$value['invoice_number']] = [

                        'invoiceNumber' => $value['invoice_number'], // 发票清单号
                        'purchaseNumber' => $value['purchase_number'], // 采购单号
                        'compactNumber' => $value['compact_number'], // 合同号
                        //'compactUrl' => $value['file_path'], // 合同下载地址
                        'supplierCode' => $value['supplier_code'], // 供应商编码
                        'supplierName' => $value['supplier_name'], // 供应商名称
                        'invoiceAmount' => $value['invoice_amount'], // 发票金额
                        'currencyCode' => $value['currency_code'], // 币种
                        'purchaseUserId' => $value['purchase_user_id'], // 采购员ID
                        'purchaseUserName' =>$value['purchase_user_name'], // 采购员名称
                        'createUser' => $value['create_user'], // 创建人
                    ];
                    $pushGateWaysSku[$value['sku']] = [

                        'uncustomsQuantity' => $value['uninvoiced_qty'], //未报关数量
                        'uncustomsQuantity' => $value['upselft_amount'], // 入库数量
                        'unitPrice' => $value['purchase_unit_price'], //含税单价

                        'appInvoiceQty' => $value['app_invoice_qty'], //可以开票数量
                        'invoiceNumber' => $value['invoice_number'],
                        'purchaseNumber' =>$value['purchase_number'],
                        'sku' => $value['sku'],
                        'instockQty' =>$value['upselft_amount'],
                        'app_invoice_qty' => $value['app_invoice_qty'],
                        'unit_price' =>$value['unit_price'],
                        'customsNumber' => $value['customs_number']
                    ];
                }
                if(!empty($pushGateWaysSku)){

                    $skus = array_keys($pushGateWaysSku);
                    $productDatas = $this->purchase_db->from("product")->where_in("sku",$skus)->get()->result_array();
                    $productDatas = array_column($productDatas,NULL,"sku");
                    $purchaseNumbersData = array_column($pushData,"purchaseNumber");
                    $declaresData = $this->getPurchaseSkuDeclare($purchaseNumbersData);

                    foreach($pushGateWaysSku as $sku=>$sku_value){

                        if( isset($productDatas[$sku]) && !empty($productDatas[$sku])){
                            $declareKeys = $sku_value['purchaseNumber']."_".$sku_value['sku'];
                            $pushGateWaysSku[$sku]['sku'] = $sku;
                            $pushGateWaysSku[$sku]['productName'] = $productDatas[$sku]['product_name'];
                            $pushGateWaysSku[$sku]['exportCname'] = $productDatas[$sku]['export_cname'];
                            $pushGateWaysSku[$sku]['customsNumber'] = $sku_value['customsNumber'];

                            $pushGateWaysSku[$sku]['customsCode'] = $productDatas[$sku]['customs_code'];
                            $pushGateWaysSku[$sku]['exportCname'] = $productDatas[$sku]['export_cname'];

                            $pushGateWaysSku[$sku]['exportCname'] = $productDatas[$sku]['export_cname'];

                            $pushGateWaysSku[$sku]['invoiceCouponRate'] = $productDatas[$sku]['coupon_rate'];

                            $pushGateWaysSku[$sku]['purTicketedPoint'] = $productDatas[$sku]['ticketed_point'];

                           // if(isset($declaresData[$declareKeys])){
                            $pushGateWaysSku[$sku]['customsType'] = isset($declaresData[$declareKeys]['customs_type'])?$declaresData[$declareKeys]['customs_type']:''; // 报关单位
                            $pushGateWaysSku[$sku]['customsQuantity'] = isset($declaresData[$declareKeys]['customs_quantity'])?$declaresData[$declareKeys]['customs_quantity']:0; // 报关数量
                            $pushGateWaysSku[$sku]['customsName'] =  isset($declaresData[$declareKeys]['customs_name'])?$declaresData[$declareKeys]['customs_name']:''; // 报关单号
                            $pushGateWaysSku[$sku]['customsUnit'] = isset($declaresData[$declareKeys]['customs_unit'])?$declaresData[$declareKeys]['customs_unit']:''; //
                            $pushGateWaysSku[$sku]['sumPrice']  = (isset($sku_value['unit_price']) && isset($sku_value['app_invoice_qty']))?bcmul($sku_value['unit_price'],$sku_value['app_invoice_qty'],2):0;//sku总金额
                           // } bcmul($item['unit_price'],$item['customs_quantity'],2);
                        }
                    }
                    if(!empty($pushGateWaysSku)){

                        foreach( $pushGateWaysSku as $key=>$value){

                            if( isset($pushData[$value['invoiceNumber']])){

                                $pushData[$value['invoiceNumber']]['item'][] = $value;
                            }

                        }
                        if(!empty($pushData)){
                            $url           = getConfigItemByName('api_config','invoice','list');
                            $header = array('Content-Type: application/json');
                            $access_taken = getOASystemAccessToken();
                            $url = $url . "?access_token=" . $access_taken;
                            foreach ($pushData as $key => $value) {
                                $result = getCurlData($url, json_encode($value, JSON_UNESCAPED_UNICODE), 'post', $header);
                                $insertData =[
                                    'pushdata' => json_encode($value, JSON_UNESCAPED_UNICODE),
                                    'returndata' => $result,
                                    'type' => 'batch_submit'
                                ];
                                $this->purchase_db->insert('invoice_data_log',$insertData);
                            }
                        }
                    }

                }
            }

        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    public function pushIsGateWays($ids){

        $this->purchase_db->where_in("id",$ids)->update($this->table_name,['is_push_gateway'=>1]);
    }
}