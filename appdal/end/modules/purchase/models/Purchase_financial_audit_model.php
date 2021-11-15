<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/4
 * Time: 10:50
 */
class Purchase_financial_audit_model extends Purchase_model
{

    protected $table_name = 'purchase_invoice_list';// 数据表名称
    protected $declare_customs_table = 'declare_customs';
    protected $table_invoice_detail = 'purchase_items_invoice_info';
    protected $table_invoice_item = 'purchase_invoice_item';
    protected $table_purchase_order = 'purchase_order';
    protected $table_purchase_order_items = 'purchase_order_items';
    protected $table_purchase_order_reportloss = 'purchase_order_reportloss';
    protected $table_product = 'product';


    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_order');
        $this->load->model('Purchase_order_cancel_model', 'm_cancel', false, 'purchase');
    }

    public function list_title()
    {
        $this->lang->load('common');
        $key_value = $this->lang->myline('purchase_financial_audit_list');
        return $key_value;
    }

    /**
     * 记录推送门户系统的数据日志
     *  $pushData   string 推送门户系统数据
     *  $result     string 门户系统返回数据
     *  $type       string 标识
     **/
    public function insertData($pushData,$result,$type){

        $insertData =[
            'pushdata' => $pushData,
            'returndata' => $result,
            'type' => $type
        ];
        $this->purchase_db->insert('invoice_data_log',$insertData);
    }

    /**
     * 财务审核列表页
     * @author Manson
     */
    public function get_financial_audit_list($params)
    {
        $select_cols = " b.tovoid_remark,b.void_audit_time,b.void_audit_user,b.tovoid_user,b.tovoid_time,b.is_tovoid,a.invoice_number, a.purchase_number, a.sku, a.demand_number, a.unit_price,
        c.supplier_code, c.supplier_name, c.submit_user, c.submit_time, c.purchase_user_id, c.purchase_user_name,
        d.pur_ticketed_point, d.product_name, d.upselft_amount,
        e.compact_number,b.invoiced_amount,
        b.id, b.invoice_code_left, b.invoice_code_right, 
        b.invoice_coupon_rate, b.invoiced_qty, b.invoice_value, b.taxes, b.audit_user, b.audit_time, b.audit_status, 
        b.remark,c.is_gateway,b.invoice_image,d.uninvoiced_qty,b.children_invoice_number,d.declare_unit";

        $order_info = 'a.create_time desc';
        $group_by_info = 'b.id';
        $this->purchase_db->select($select_cols);
        $this->purchase_db->from($this->table_invoice_detail. ' b')
            ->join($this->table_invoice_item.' a','a.invoice_number=b.invoice_number AND a.purchase_number = b.purchase_number AND a.sku = b.sku','left')
            ->join($this->table_name. ' c', 'b.invoice_number=c.invoice_number', 'left')
            ->join($this->table_purchase_order_items. ' d','b.purchase_number = d.purchase_number and b.sku = d.sku','left')
            ->join($this->table_purchase_order." as od", "b.purchase_number = od.purchase_number")
            ->join('purchase_compact_items e','b.purchase_number = e.purchase_number','left');

        if (isset($params['ids']) && !empty($params['ids'])){//勾选导出
            $this->purchase_db->where_in('b.id',query_string_to_array($params['ids']));
            $return_data['quick_sql'] =  $this->purchase_db->get_compiled_select();
            $clone_db = clone($this->purchase_db);
            $return_data['total_count'] = $clone_db->count_all_results();//符合当前查询条件的总记录数
            return $return_data;
        }

        if( isset($params['is_gateway']) && !empty($params['is_gateway'])){

            if( $params['is_gateway'] == 1){

                $this->purchase_db->where("c.is_gateway",1);
            }else{
                $this->purchase_db->where("c.is_gateway",0);
            }
        }

        if(isset($params["purchase_type"]) && is_numeric($params['purchase_type'])){
            $this->purchase_db->where("od.purchase_type_id", $params["purchase_type"]);
        }

        if( isset($params['is_invoice_image']) && !empty($params['is_invoice_image'])){

            if( $params['is_invoice_image'] == 1){

                $this->purchase_db->where("b.invoice_image",'');
            }else{
                $this->purchase_db->where("b.invoice_image!=",'');
            }
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
//pr($params);exit;
        if(isset($params['auditTime'][0]) && !empty($params['auditTime'][0])){
            $this->purchase_db->where('b.audit_time>=',$params['auditTime'][0]);
        }
        if(isset($params['auditTime'][1]) && !empty($params['auditTime'][1])){
            $this->purchase_db->where('b.audit_time<=',$params['auditTime'][1]);
        }

        if (isset($params['invoice_code_left']) || isset($params['invoice_code_right']) || isset($params['audit_status'])){
            if (!empty($params['invoice_code_left'])){
                $this->purchase_db->where_in('b.invoice_code_left',query_string_to_array($params['invoice_code_left']));
            }
            if (!empty($params['invoice_code_right'])){
                $this->purchase_db->where_in('b.invoice_code_right',query_string_to_array($params['invoice_code_right']));
            }
            if( !empty($params['audit_status'])){
                $this->purchase_db->where('b.audit_status',$params['audit_status']);
            }
        }
        if(isset($params['purchase_number']) && !empty($params['purchase_number'])){
            $this->purchase_db->where_in('a.purchase_number',query_string_to_array($params['purchase_number']));
        }
        if(isset($params['compact_number']) && !empty($params['compact_number'])){
            $this->purchase_db->where_in('e.compact_number',query_string_to_array($params['compact_number']));
        }

        if(isset($params['sku']) && !empty($params['sku'])){
            $this->purchase_db->where_in('a.sku',query_string_to_array($params['sku']));
        }

        if(isset($params['demand_number']) && !empty($params['demand_number'])){
            $this->purchase_db->where_in('a.demand_number',query_string_to_array($params['demand_number']));
        }

        if(isset($params['tovoid_status']) && !empty($params['tovoid_status'])){

            $this->purchase_db->where_in('b.is_tovoid',$params['tovoid_status']);
        }




        $this->purchase_db->where_in('c.audit_status',[INVOICE_STATES_WAITING_FINANCE_AUDIT,6]);
        $this->purchase_db->where('e.bind',1);

        //统计总数
        if(isset($params['export_save'])){//导出不需要分页查询

            $query_export = clone $this->purchase_db;
            $clone_db = clone($this->purchase_db);

            $total_count=$clone_db->count_all_results();//符合当前查询条件的总记录数

            $this->load->service('basic/SearchExportCacheService');
            $total = str_pad((string)$total_count, 10, '0', STR_PAD_LEFT);
            $this->searchexportcacheservice->setScene($this->searchexportcacheservice::PURCHASE_FINANCIAL_AUDIT_LIST_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));

        }
        $this->purchase_db->group_by($group_by_info);


        $clone_db = clone($this->purchase_db);
        $total_count=$clone_db->count_all_results();//符合当前查询条件的总记录数

        $results = $this->purchase_db->order_by($order_info)->limit($params['limit'], $params['offset'])->get()->result_array();

        if( !empty($results)){
            foreach( $results as $key=>$value){
                if( $value['is_gateway'] == 1){
                    $results[$key]['is_gateway_ch'] = "是";
                }else{
                    $results[$key]['is_gateway_ch'] = "否";
                }

                if( $value['is_tovoid'] == 1){

                    $results[$key]['is_tovoid_ch'] = '未作废';
                }else if($value['is_tovoid'] == 2){

                    $results[$key]['is_tovoid_ch'] = '已作废';
                }else if($value['is_tovoid'] == 3){

                    $results[$key]['is_tovoid_ch'] = '作废驳回';
                }else if($value['is_tovoid'] == 4){
                    $results[$key]['is_tovoid_ch'] = '作废待审核';
                }
            }
        }
        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'limit'     => $params['limit']??'',
                'offset'    => $params['offset']??'',
                'page'    => $params['page']??'',
            ],
            'key'   => $this->list_title(),
        ];
        return $return_data;

    }

    /**
     * 根据invoice_info表的id获取开票信息
     * @author Manson
     * @param $invoice_detail_id
     * @return mixed
     */
    public function get_audit_invoice_detail($invoice_detail_id)
    {
        $result['value'] =  $this->purchase_db->select('a.*,b.unit_price, c.supplier_name, c.supplier_code')
            ->from($this->table_invoice_detail. ' a')
            ->join($this->table_invoice_item. ' b','a.purchase_number = b.purchase_number AND a.sku = b.sku','left')
            ->join($this->table_name.' c','a.invoice_number = c.invoice_number','left')
            ->where_in('a.id',$invoice_detail_id)
            ->where('a.audit_status',INVOICE_STATES_WAITING_FINANCE_AUDIT)//待财务审核的数据
            ->group_by('a.id')
            ->get()
            ->result_array();
        return $result;
    }

    /**
     * 根据invoice_item表的id获取开票信息
     * @author Manson
     */
    public function get_batch_audit_invoice_detail($invoice_item_id)
    {
        $result['value'] =  $this->purchase_db->select('a.*,b.unit_price, c.supplier_name, c.supplier_code')
            ->from($this->table_invoice_detail.' a')
            ->join($this->table_invoice_item.' b','a.purchase_number = b.purchase_number AND a.sku = b.sku','left')
            ->join($this->table_name.' c','a.invoice_number = c.invoice_number','left')
            ->where_in('b.id',$invoice_item_id)
            ->where('a.audit_status',INVOICE_STATES_WAITING_FINANCE_AUDIT)//待财务审核的数据
            ->group_by('a.id')
            ->get()
            ->result_array();
//        echo $this->purchase_db->last_query();exit;
        return $result;
    }


    /**
     * 查询审核状态
     * @author Manson
     * @param $ids
     * @return array
     */
    public function get_audit_status($ids)
    {
        $result = $this->purchase_db->select('a.id,a.audit_status, b.id as items_id,c.compact_number, a.invoiced_qty')
            ->from($this->table_invoice_detail.' a')
            ->join($this->table_purchase_order_items. ' b','a.purchase_number = b.purchase_number AND a.sku = b.sku','left')
            ->join('purchase_compact_items c','b.purchase_number = c.purchase_number','left')
            ->where_in('a.id',$ids)
            ->get()
            ->result_array();
//        echo $this->purchase_db->last_query();exit;
        return $result;
        return empty($result)?[]:array_column($result,'audit_status','id');
    }

    /**
     * 审核操作
     * @author Manson
     * @param $params
     * @return bool
     */
    public function batch_audit($params)
    {

        return $this->purchase_db->where_in('id',$params['ids'])
            ->set('audit_status',$params['audit_status'])
            ->set('remark',$params['remark'])
            ->set('audit_user',$params['audit_user'])
            ->set('audit_time',$params['audit_time'])
            ->update($this->table_invoice_detail);

    }

    /**
     * 更新开票状态
     * @author Manson
     */
    public function get_po_sku($ids)
    {
        $result = $this->purchase_db->select('purchase_number,sku')
            ->from($this->table_invoice_item)
            ->where_in('id',$ids)
            ->group_by('purchase_number,sku')
            ->get()
            ->result_array();
        return $result;
    }


    /**
     * 获取采购数量
     * @author Manson
     * @param $ids
     * @return array
     */
    public function get_qty_info($ids)
    {
        $result = $this->purchase_db->select('a.id, a.confirm_amount as purchase_qty, b.loss_amount as loss_qty, a.invoiced_qty')
            ->from($this->table_purchase_order_items. ' a')
            ->join($this->table_purchase_order_reportloss. ' b','a.purchase_number = b.pur_number AND a.sku = b.sku AND b.status = '.REPORT_LOSS_STATUS_FINANCE_PASS,'left')
            ->where_in('a.id',$ids)
            ->get()
            ->result_array();
//        echo $this->purchase_db->last_query();exit;
        return empty($result)?[]:array_column($result,NULL,'id');
    }

    public function getCompactData($compact_number){

        $sql = "
              SELECT `comp`.`compact_number`, `ppo`.`supplier_code`, `ppoi`.`id`, `ppoi`.`sku`, `ppoi`.`purchase_number`,
                `ppoi`.`confirm_amount`, `ppoi`.`product_name`, `ppoi`.`invoice_is_abnormal`, `ppoi`.`invoiced_qty`,
                `ppoi`.`uninvoiced_qty`, `ppoi`.`pur_ticketed_point`, `ppoi`.`purchase_unit_price`, `ppoi`.`invoice_status`,
                `ppoi`.`contract_invoicing_status`, `ppoi`.`purchase_amount`, `ppoi`.`upselft_amount`, `ppoi`.`is_end`,
                `ppoi`.`customs_status`, `ppoi`.`export_cname`, `ppoi`.`declare_unit`, `ppoi`.`coupon_rate`, `ppo`.`create_time` as
                `order_create_time`, `ppo`.`buyer_name`, `ppo`.`is_drawback`, `ppo`.`warehouse_code`, `ppo`.`purchase_order_status`,
                `ppo`.`pay_status`, `ppo`.`supplier_name`, `ppo`.`currency_code`, `sug`.`demand_number`, `ppo`.`is_gateway`,
                IFNULL(sum(loss.loss_amount), 0) AS loss_amount, IFNULL(SUM(a.cancel_ctq), 0) AS cnacel_ctq
                FROM `pur_purchase_order_items` as `ppoi`
                LEFT JOIN `pur_purchase_order` as `ppo` ON `ppoi`.`purchase_number`=`ppo`.`purchase_number`
                LEFT JOIN `pur_purchase_suggest_map` as `sug` ON `ppoi`.`purchase_number`=`sug`.`purchase_number` AND
                `ppoi`.`sku`=`sug`.`sku`
                LEFT JOIN `pur_purchase_compact_items` as `comp` ON `ppoi`.`purchase_number`=`comp`.`purchase_number`
                LEFT JOIN `pur_purchase_order_cancel_detail` as `cancel` ON `ppoi`.`purchase_number`=`cancel`.`purchase_number` AND
                `ppoi`.`sku`=`cancel`.`sku`
                LEFT JOIN `pur_purchase_order_reportloss` as `loss` ON `ppoi`.`purchase_number`=`loss`.`pur_number` AND `ppoi`.`sku` =
                `loss`.`sku` AND `loss`.`status` = 4
                LEFT JOIN `pur_purchase_order_cancel_detail` AS `a` ON `ppoi`.`id`=`a`.`items_id`
                LEFT JOIN `pur_purchase_order_cancel` AS `b` ON `a`.`cancel_id` = `b`.`id` and `b`.`audit_status` IN (60,70,90)
                WHERE `comp`.`compact_number` = '".$compact_number."'
                AND `ppo`.`is_drawback` = 1
                AND `ppo`.`purchase_order_status` IN(2, 7, 8, 9, 10, 11, 12, 13, 14, 15)
                AND `source` = 1
                GROUP BY `ppoi`.`id`
                HAVING ( `ppoi`.`invoice_status` != 12 AND `ppoi`.`invoice_status` != 5 AND `ppoi`.`invoice_status` != 2 AND (ppoi.confirm_amount-cnacel_ctq) >0 AND
                `ppoi`.`invoiced_qty` =0)
                OR ( `ppoi`.`invoice_status` != 12 AND `ppoi`.`invoice_status` != 5 AND `ppoi`.`invoice_status` != 2 AND `ppoi`.`invoiced_qty` >0 AND
                (ppoi.confirm_amount-cnacel_ctq) > `invoiced_qty`)
                OR `ppoi`.`invoice_status` = 2
                ORDER BY `ppo`.`create_time` DESC
                ";

        $result = $this->purchase_db->query($sql)->result_array();
        return $result;

    }

    /**
     * 合同号下的所有备货单的开票状态都是已开票 -合同状态已完结
     */
    public function update_contract_invoicing_status($compact_number)
    {
        $update_end = $update_no_end = [];
        foreach($compact_number as $items){
            $result = $this->getCompactData($items);
            $resultData = $this->purchase_db->select('a.compact_number,d.suggest_order_status, b.invoice_status,b.id,a.compact_number')
                ->from('purchase_compact_items a')
                ->join('purchase_order_items b','a.purchase_number = b.purchase_number','left')
                ->join('purchase_suggest_map c','a.purchase_number = c.purchase_number','left')
                ->join('purchase_suggest d','d.demand_number = c.demand_number','left')
                ->where_in('a.compact_number',$compact_number)

                ->get()
                ->result_array();
            if(!empty($result)){

                foreach($resultData as $key=>$value){

                    $update_end[] = [
                        'id' => $value['id'],
                        'contract_invoicing_status' =>   CONTRACT_INVOICING_STATUS_NOT,
                    ];
                }
            }else{

                foreach($resultData as $key=>$value){

                    $update_no_end[] = [
                        'id' => $value['id'],
                        'contract_invoicing_status' =>  CONTRACT_INVOICING_STATUS_END,
                    ];
                }
            }
        }

        if (!empty($update_no_end)){
            $this->purchase_db->update_batch('purchase_order_items',$update_no_end,'id');
        }
        if (!empty($update_end)){
            $this->purchase_db->update_batch('purchase_order_items',$update_end,'id');
        }
//
//
//
//
//        $result = $this->purchase_db->select('a.compact_number,d.suggest_order_status, b.invoice_status,b.id,a.compact_number')
//            ->from('purchase_compact_items a')
//            ->join('purchase_order_items b','a.purchase_number = b.purchase_number','left')
//            ->join('purchase_suggest_map c','a.purchase_number = c.purchase_number','left')
//            ->join('purchase_suggest d','d.demand_number = c.demand_number','left')
//            ->where_in('a.compact_number',$compact_number)
//
//            ->get()
//            ->result_array();
//        $end = [];
//        $no_end = [];
//        $update_end =[];
//        $update_no_end = [];
//        foreach ($result as $key => $item){
//            /*
//             * 已完结：合同下的每个备货单开票状态=已开票,或者订单状态=已作废
//             */
//            if ($item['invoice_status'] == INVOICE_STATUS_END || $item['invoice_status'] == INVOICE_STATUS_PROHBIT ||
//                $item['suggest_order_status'] == PURCHASE_ORDER_STATUS_CANCELED){
//                $end[$item['compact_number']][] = $item['id'];
//            }else{
//                unset($end[$item['compact_number']]);
//                $no_end[$item['compact_number']][] = $item['id'];
//            }
//        }
//        foreach ($end as $key => $item){
//            foreach ($item as $k => $val){
//                $update_end[] = [
//                    'id' => $val,
//                    'contract_invoicing_status' => CONTRACT_INVOICING_STATUS_END,
//                ];
//            }
//        }
//        foreach ($no_end as $key => $item){
//            foreach ($item as $k => $val){
//                $update_no_end[] = [
//                    'id' => $val,
//                    'contract_invoicing_status' => CONTRACT_INVOICING_STATUS_NOT,
//                ];
//            }
//        }
//
//        if (!empty($update_no_end)){
//            $this->purchase_db->update_batch('purchase_order_items',$update_no_end,'id');
//        }
//        if (!empty($update_end)){
//            $this->purchase_db->update_batch('purchase_order_items',$update_end,'id');
//        }
    }


    /**
     * 更新审核状态
     * @author Manson
     * @param $ids_str
     * @param $remark
     * @param $update_data
     * @throws Exception
     */
    public function update_audit_status($ids_str,$remark,$update_data)
    {
        if (!$this->batch_audit($update_data)){
            throw new  Exception('更新审核失败');
        }
        $log_data =  [
            'id' => $ids_str,
            'content' => '报关开票-财务审核列表-审核通过/审核驳回',
            'detail' => sprintf('审核备注: %s;审核状态由 %s 改为 %s;修改成功的记录:%s',$remark,INVOICE_STATES_WAITING_FINANCE_AUDIT,$update_data['audit_status'],$ids_str)
        ];
        if (!operatorLogInsert($log_data)){
            throw new  Exception('记录日志失败');
        }
    }

    /**
     * 更新po+sku维度items表开票状态
     * @author Manson
     * @param $items_id
     * @return array
     */
    public function update_invoice_status($items_id_list,$invoiced_qty_map = [])
    {

        $result = $this->purchase_db->select('a.id as items_id,b.audit_status,b.is_tovoid')
            ->from($this->table_purchase_order_items. ' a')
            ->join($this->table_invoice_detail. ' b', 'a.purchase_number = b.purchase_number AND a.sku = b.sku','left')
            ->where_in('a.id',$items_id_list)
            ->get()->result_array();
        foreach ($result as $item){
            //if (!in_array($item['audit_status'],[INVOICE_AUDITED,INVOICE_FINANCIAL_REJECTION]) || $item['is_tovoid'] == 2)
            if (!in_array($item['audit_status'],[INVOICE_AUDITED,INVOICE_FINANCIAL_REJECTION,INVOICE_FINANCIAL_TOVOID,7])){//不是完结状态(审核通过 审核驳回)
                $part_invoice_map[$item['items_id']] = 1;//开票中 待财务审核
            }
        }


        //判断开票状态
        $qty_map = $this->m_financial_audit->get_qty_info($items_id_list);
//                pr($qty_map);exit;
        $cancel_map = $this->m_cancel->get_cancel_qty_by_item_id($items_id_list);
        foreach ($items_id_list as $id){
            $purchase_qty = $qty_map[$id]['purchase_qty']??0;//采购数量
            $loss_qty = $qty_map[$id]['loss_qty']??0;//报损数量
            $cancel_qty = $cancel_map[$id]??0;//取消数量
            $act_purchase_qty = $purchase_qty - $cancel_qty;//实际采购数量
            $invoiced_qty_success = $qty_map[$id]['invoiced_qty']??0;//已开票数量(审核通过的)
            $invoiced_qty = $invoiced_qty_map[$id]??0;//已开票数量(这次审核勾选的)
            $total_invoiced_qty = $invoiced_qty_success + $invoiced_qty;
            $uninvoiced_qty = $act_purchase_qty - $total_invoiced_qty;//未开票数量 = [未开票数量 = 实际采购数量-已开票数量]

            if ($total_invoiced_qty <= 0){//未开票
                $invoice_status = INVOICE_STATUS_NOT;
            } elseif (isset($part_invoice_map[$id])){//开票中
                $invoice_status = INVOICE_STATUS_ING;
            } elseif ($total_invoiced_qty > 0 && $total_invoiced_qty<$act_purchase_qty && !isset($part_invoice_map[$id])){//部分已开票
                $invoice_status = INVOICE_STATUS_PART;
            }elseif ($total_invoiced_qty >= $act_purchase_qty){//已开票
                $invoice_status = INVOICE_STATUS_END;
            }

            $update_invoice_status[] = [
                'id' => $id,
                'invoice_status' => $invoice_status,
                'invoiced_qty' => $total_invoiced_qty,
                'uninvoiced_qty' => $uninvoiced_qty,
            ];
        }

        //更新开票状态
        $result = $this->purchase_db->update_batch('purchase_order_items',$update_invoice_status,'id');
        return $result;
    }

    /**
     * 获取开票明细信息
     * @params $ids   array   明细ID
     * @author:luxu
     * @time:2020/5/20
     **/
    public function get_invoice_info($ids){

        $result = $this->purchase_db->from($this->table_invoice_detail." AS detail")
            ->join($this->table_name." AS list","list.invoice_number=detail.invoice_number","LEFT")->where_in("detail.id",$ids)
        ->select("detail.*")->get()->result_array();
        return $result;
    }

    /**
     *记录审核日志
     * @param $logsData   array   记录日志信息
     * @author：luxu
     * @time:2020/6/29
     **/
    public function insertLogsData($logsData){

        $this->purchase_db->insert_batch('invoice_examine_log',$logsData);
    }

    /**
     * @function:获取子发票清单号信息
     * @param:  $childrenNumbers    array|string   子发票清单号
     * @author:luxu
     * @time:2020/8/4
     **/

    public function getChildrenNumberStatus($childrenNumbers){

        try{

            $query = $this->purchase_db->from("purchase_items_invoice_info");
            if( is_array($childrenNumbers)){

                $query->where_in("children_invoice_number",$childrenNumbers);
            }

            $result = $query->get()->result_array();
            return $result;
        }catch ( Exception $exception ){
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @function: 任务编号：09631 【报关开票】流程调整:增加订单作废功能。增加已作废/已推送退税系统的状态
     * 申请作废接口
     * @author:luxu
     * @time:2020/8/4
     * @param  $childrenNumber  array  子发票清单号
     *         $remark          string 备注
     **/

    public function toVoid($childrenNumber,$remark)
    {
        try{

            if( empty($childrenNumber)){

                throw new Exception("请传入子发票清单号");
            }

            $childrenNumberData = $this->getChildrenNumberStatus($childrenNumber);
            if(empty($childrenNumberData)){

                throw new Exception("子发票清单号不存在");
            }

            $noAuditData = $apply = $applyData =  [];
            foreach( $childrenNumberData as $key=>$value){

                if($value['audit_status'] !=4){

                   // throw new Exception("子发票清单号:".$value['children_invoice_number'].",未审核");
                    $noAuditData[] = $value['children_invoice_number'];
                }

                if( $value['is_tovoid'] == 2){
                   // throw new Exception("子发票清单号:".$value['children_invoice_number'].",已经作废,请勿重复申请");
                    $apply[] = $value['children_invoice_number'];
                }

                if( $value['is_tovoid'] == 4){
                    // throw new Exception("子发票清单号:".$value['children_invoice_number'].",已经作废,请勿重复申请");
                    $applyData[] = $value['children_invoice_number'];
                }
            }

            if(!empty($noAuditData)){

                throw new Exception("子发票清单号:".implode(",",$noAuditData).",未审核");
            }

            if(!empty($apply)){

                throw new Exception("子发票清单号:".implode(",",$apply).",已经作废,请勿重复申请");
            }

            if(!empty($applyData)){

                throw new Exception("子发票清单号:".implode(",",$applyData).",已经申请作废,请勿重复申请");
            }

            $childrenNumberDatas = array_column($childrenNumberData,"children_invoice_number");
            $updateData = [

                'is_tovoid' => 4, // 作废申请待审核状态
                'remark' => $remark,
                'tovoid_user' =>getActiveUserName(),
                'tovoid_time' => date("Y-m-d H:i:s")
            ];
            $result = $this->purchase_db->where_in("children_invoice_number",$childrenNumberDatas)->update('purchase_items_invoice_info',$updateData);
            return $result;

        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }

    }

    /**
     * @function:获取备货单下发票明细状态
     * @params : $demandNumber  array |string   备货单号
     *           $dataStatus    array  状态
     * @author:luxu
     * @time:2020/09/01
     **/
    public function getDemandTaxNumber($demandNumber,$dataStatus){

        $result = $this->purchase_db->from("purchase_items_invoice_info")->where($demandNumber)
            ->where_in("audit_status",$dataStatus)->get()->result_array();
        return $result;
    }

    /**
     * @ function : 获取合同号下所有备货单
     * @ params : $demandNumbers  array  备货单号
     * @ author:luxu
     **/
    public  function getCompactDemandDatas($invocieNumbers){

        $sql = " SELECT invoice_number,audit_status FROM pur_purchase_invoice_list WHERE 
               compact_number IN ( SELECT compact_number FROM pur_purchase_invoice_list WHERE  invoice_number ='{$invocieNumbers}')";
        $result = $this->purchase_db->query($sql)->result_array();
        if(!empty($result)){

            $results = array_unique(array_column( $result,"audit_status"));
            return $results;
        }
        return NULL;
    }

    /**
     * @function: 任务编号：09631 【报关开票】流程调整:增加订单作废功能。增加已作废/已推送退税系统的状态
     * 申请作废申请接口
     * @author:luxu
     * @time:2020/8/4
     * @param  $childrenNumber  array  子发票清单号
     *         $remark          string 备注
     *         $auditStatus     int    审核结果 2表示审核通过，3表示审核驳回
     **/

    public function ToVoidAudit($childrenNumbers,$auditStatus,$remark = NULL){

        try{

            if( empty($childrenNumbers) ){

                throw new Exception("传入子发票清单号");
            }
            // 获取字发票清单号信息
            $childrenNumberData = $this->getChildrenNumberStatus($childrenNumbers);
            if(empty($childrenNumberData)){

                throw new Exception("子发票清单号不存在");
            }
            // 验证子发票清单号是否申请作废
            $noApply =[];
            foreach($childrenNumberData as $key=>$value){
                if( $value['is_tovoid'] !=4){

                    //throw new Exception("子发票清单号:".$value['children_invoice_number'].",并未申请作废");
                    $noApply[] = $value['children_invoice_number'];
                }
            }

            if(!empty($noApply)){

                throw new Exception("子发票清单号:".implode(",",$noApply).",并未申请作废");
            }
            // 修改子开票清单号的是否作废，审核状态
            $childrenStatus = [
                'is_tovoid' => $auditStatus, // 是否作废状态修改为作废
                'void_audit_user' => getActiveUserName(), // 审核人
                'void_audit_time' => date("Y-m-d H:i:s", time()), // 审核时间
                'tovoid_remark' =>$remark // 审核备注
            ];
            // 根据审核结果对应相关数据操作
            $this->purchase_db->trans_begin();
            if( $auditStatus == 2){
//                $childrenStatus['audit_status'] = 9;
                // 审核通过, 开启事务
                // 修改子开票清单号的状态
                $childrenNumberResult = $this->purchase_db->where_in("children_invoice_number",$childrenNumbers)
                    ->update('purchase_items_invoice_info',$childrenStatus);
                if($childrenNumberResult){

                    // 修改 含税跟踪列表，减去相应的已开票数量
                    foreach($childrenNumberData as $childrenKey=>$childrenValue){

                        // 1： 获取采购单 已开票数量
                        $purchaseInvoicedQty =  $this->purchase_db->from("purchase_order_items")->where("purchase_number",$childrenValue['purchase_number'])
                            ->where("sku",$childrenValue['sku'])->select("id,invoiced_qty,uninvoiced_qty,confirm_amount,invoice_status")->get()->row_array();
                        $purchaseInvoicedQtyData = [

                            'invoiced_qty' => (int)$purchaseInvoicedQty['invoiced_qty'] - (int)$childrenValue['invoiced_qty'],
                            'invoice_status' =>INVOICE_STATUS_NOT,
                            'uninvoiced_qty' =>$purchaseInvoicedQty['uninvoiced_qty'] + (int)$childrenValue['invoiced_qty'],
                            'contract_invoicing_status' => CONTRACT_INVOICING_STATUS_NOT
                        ];

                        if($purchaseInvoicedQtyData['invoiced_qty'] == 0){

                            $purchaseInvoicedQtyData['invoice_status'] = INVOICE_STATUS_NOT;
                        }
                        $contractStatus = $this->getCompactDemandDatas($childrenValue['invoice_number']);
                        if( $contractStatus != NULL){

                            //invoice_status 1未开票 2开票中 3部分已开票
                            $flagStatus = [INVOICE_STATUS_NOT,INVOICE_STATUS_ING,INVOICE_STATUS_PART];
                            $inserectData = array_intersect($flagStatus,$contractStatus);
                            if( !empty($inserectData)){

                                $purchaseInvoicedQtyData['contract_invoicing_status'] = CONTRACT_INVOICING_STATUS_NOT;
                            }else{
                                $purchaseInvoicedQtyData['contract_invoicing_status'] = CONTRACT_INVOICING_STATUS_END;
                            }
                        }


                        // 判断备货单是否存在 “财务驳回”,"采购驳回","已经确认" 的发票明细
                        $demandTaxNumbers = $this->getDemandTaxNumber(['demand_number'=>$childrenValue['demand_number']],[INVOICE_STATES_AUDIT,INVOICE_STATES_FINANCE_REJECTED

                        ,7]);

                        $cancel_qty_map = $this->m_cancel->get_cancel_qty_by_item_id([$childrenValue['id']]);

                        if(isset($cancel_qty_map[$childrenValue['id']])){

                            $purchaseInvoicedQty['confirm_amount'] = $purchaseInvoicedQty['confirm_amount'] - $cancel_qty_map[$childrenValue['id']];
                        }
                        if(empty($demandTaxNumbers))
                        {
                            $purchaseInvoicedQtyData['invoice_status'] = INVOICE_STATUS_ING;
                        }else{
                            if($purchaseInvoicedQtyData['invoiced_qty'] >0 ){

                                if( $purchaseInvoicedQtyData['invoiced_qty'] >=$purchaseInvoicedQty['confirm_amount'] ){

                                    $purchaseInvoicedQtyData['invoice_status'] = INVOICE_STATUS_END;
                                }else{

                                    $purchaseInvoicedQtyData['invoice_status'] = INVOICE_STATUS_PART;
                                }
                            }else{

                                // 已开票数量小于0 或者等于0
                                if( $purchaseInvoicedQtyData['uninvoiced_qty'] >0 ){

                                    $purchaseInvoicedQtyData['invoice_status'] = INVOICE_STATUS_NOT;
                                }else{

                                    $purchaseInvoicedQtyData['invoice_status'] = INVOICE_STATUS_PROHBIT;
                                }
                            }
                        }

                        $this->purchase_db->where("purchase_number",$childrenValue['purchase_number'])
                            ->where("sku",$childrenValue['sku'])->update('purchase_order_items',$purchaseInvoicedQtyData);
                    }
                }

            }else{

                if( empty($childrenStatus['tovoid_remark']) || $childrenStatus['tovoid_remark'] == ''){

                    throw new Exception("驳回，请填写备注",400);
                }
//                $childrenStatus['audit_status'] =10;

                $this->purchase_db->where_in("children_invoice_number",$childrenNumbers)->update('purchase_items_invoice_info',$childrenStatus);

            }
            $this->purchase_db->trans_commit();
            return True;
        }catch ( Exception $exception ){

            if($exception->getCode() != 400) {
                $this->purchase_db->trans_rollback();
            }
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * 开票状态=未开票的，用户可以点击【无法开票】，否则报错：备货单号**只有开票状态=未开票才可点击
     * @author:luxu
     * @time:2020/8/11
     **/

    public function unableToInvoice($data){

        try{

            if(empty($data)){

                throw new Exception("请传入参数");
            }

            foreach($data as $key=>$value){

                $result = $this->purchase_db->from("purchase_order_items")->where("purchase_number",$value['purchase_number'])
                    ->where("sku",$value['sku'])->get()->row_array();
                if(!empty($result) && $result['invoice_status'] != 1){

                    throw new Exception("备货单:".$value['demand_number']."，只有开票状态等于=未开票时，才可以点击");
                }

                $updateData['invoice_status'] = INVOICE_STATUS_PROHBIT; // 无法开票
                $flag =  $this->purchase_db->where("purchase_number",$value['purchase_number'])
                    ->where("sku",$value['sku'])->update('purchase_order_items',$updateData);
            }
            return True;
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

}