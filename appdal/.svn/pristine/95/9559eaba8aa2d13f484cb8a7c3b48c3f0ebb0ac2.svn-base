<?php
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2018/12/27 0027 11:23
 */

class Purchase_order_items_model extends Purchase_model {

    protected $table_name = 'purchase_order_items';

    public function __construct(){
        parent::__construct();
    }

    /**
     * 采购单明细列表 列名称
     * @author Jolon
     * @return array
     */
    public function table_columns(){
        $key_values = [
            'purchase_number'     => '采购单号',
            'sku'                 => '产品SKU',
            'product_name'        => '产品名称',
            'purchase_amount'     => '购买数量',
            'purchase_unit_price' => '采购单价(实际单价)',
            'confirm_amount'      => '确认数量',
            'receive_amount'      => '收货数量',
            'upselft_amount'      => '上架数量',
            'sales_status'        => '销售状态(1可售2作为这个sku不可售用作采购确认逻辑删除)',
            'product_img_url'     => 'sku图片',
            'order_id'            => '订单ID',
            'is_exemption'        => '是否免检 0不免检,1免检',
            'is_drawback'         => '是否退税(1不退2退)',
            'pur_ticketed_point'  => '采购开票点（税点）实际税点*1000',
            'product_base_price'  => '产品初始单价',
            'is_cancel'           => '是否作废：1作废，2未作废',
            'create_user_name'    => '添加人',
            'create_time'         => '添加时间',
            'modify_time'         => '更新时间',
            'modify_user_name'    => '最后一次更新操作人',
        ];

        return $key_values;
    }

    /**
     * 获取 采购单的 SKU 明细
     * @author Jolon
     * @param string $purchase_number 采购单编号
     * @param bool   $sku             SKU
     * @param bool   $is_only         是否只查询一条
     * @return mixed
     */
    public function get_item($purchase_number,$sku = false,$is_only = false){
        $query_builder      = $this->purchase_db;
        $query_builder->where('purchase_number',$purchase_number);

        if($is_only and $sku !== false){// 只查询一条记录
            $query_builder->where('sku',$sku);
            $results        = $query_builder->get($this->table_name)->row_array();
        }else{
            $results       = $query_builder->get($this->table_name)->result_array();
        }
        return $results;
    }

    /**
     * 获取 采购单的 SKU 明细 带上需求单号
     * @author Jolon
     * @param string $purchase_number 采购单编号
     * @param bool   $sku             SKU
     * @param bool   $is_only         是否只查询一条
     * @return mixed
     */
    public function get_item_with_demand_number($purchase_number,$sku = false,$is_only = false){
        $query_builder      = $this->purchase_db;
        $query_builder->select('oi.*,m.demand_number,s.suggest_status,s.suggest_order_status');
        $query_builder->from($this->table_name.' oi');
        $query_builder->join('purchase_suggest_map m','m.purchase_number=oi.purchase_number and m.sku=oi.sku');
        $query_builder->join('purchase_suggest s','s.demand_number=m.demand_number');
        $query_builder->where('oi.purchase_number',$purchase_number);

        if($is_only and $sku !== false){// 只查询一条记录
            $query_builder->where('oi.sku',$sku);
            $results        = $query_builder->get()->row_array();
        }else{
            $results       = $query_builder->get()->result_array();
        }
        return $results;
    }


    /**
     * 更新采购单明细的信息
     * @author Jolon
     * @param int   $item_id     明细ID
     * @param array $update_data 更新数组
     * @return bool
     */
    public function update_item($item_id,$update_data){
        if(empty($item_id) and (empty($update_data['purchase_number']) or empty($update_data['sku']))){
            return false;
        }

        if(empty($item_id)){
            $item_id = $this->get_item($update_data['purchase_number'],$update_data['sku'],true);
            $item_id = $item_id['id'];
            if(empty($item_id)) return false;
        }

        $update = array_intersect_key($update_data,$this->table_columns());// 过滤非法字段
        if(empty($update)) return false;

        if(empty($update['modify_time'])) $update['modify_time'] = date('Y-m-d H:i:s');
        if(empty($update['modify_user_name'])) $update['modify_user_name'] = getActiveUserName();

        $this->purchase_db->set($update);
        $this->purchase_db->where('id', $item_id);
        $result = $this->purchase_db->update($this->table_name);

        return $result;
    }

    /**
     * 根据SKU查询列表数据
     * @author Jaden
     * @param string   $sku   
     * @param $params
     * @param $offset
     * @param $limit 
     * @return array
     */
    public function getByskulist($sku,$offset, $limit,$field='*'){
        $this->purchase_db->select($field);
        $this->purchase_db->from($this->table_name.' as a');
        $this->purchase_db->join('purchase_order as b', 'a.purchase_number=b.purchase_number', 'left');
        $this->purchase_db->where('a.sku', $sku);
        $this->purchase_db->where_in('b.purchase_order_status', [6,7,8,9,10,11,12,13,14]);
        //统计总数
        $clone_db = clone($this->purchase_db);
        $total_count=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        //echo $this->purchase_db->last_query();exit;
        $this->purchase_db=$clone_db;
        $results = $this->purchase_db->limit($limit, $offset)->get()->result_array(); 
        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'limit'     => $limit,
            ]
        ];

        return $return_data;

    }

    /**
     * 根据SKU和时间段统计审核通过的采购数量
     * @author Jaden
     * @date 2019/3/14 17:21
     * @param string $sku
     * @param string $start_time
     * @param string $end_time
     * @return array
     */
    public function get_confirm_amount_by_sku($sku,$start_time,$end_time,$change_price){
        if(empty($sku) || empty($start_time) || empty($end_time) || empty($change_price)){
            return [];
        }
        $this->purchase_db->select('sum(ppoi.confirm_amount) as confirm_amount,ppoi.purchase_number,ppo.audit_time');
        $this->purchase_db->from('purchase_order_items as ppoi');
        $this->purchase_db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $this->purchase_db->where('ppoi.sku', $sku);
        $this->purchase_db->where('ppoi.product_base_price', $change_price);
        $this->purchase_db->where_in('ppo.purchase_order_status', [7,8,9,10,11]);
        $this->purchase_db->where('ppo.audit_time >=', $start_time);
        $this->purchase_db->where('ppo.audit_time <=', $end_time);
        $results = $this->purchase_db->order_by('ppo.audit_time asc')->get()->result_array();
        return $results;
      
    }

    /**
     * 根据id集合获取列表数据
     * @author liwuxue
     * @date 2019/2/14 17:21
     * @param array $ids
     * @param $field
     * @return array
     */
    public function get_list_by_ids(array $ids, $field = "*")
    {
        $data = [];
        $ids = array_unique(array_filter($ids));
        if (!empty($ids)) {
            $rows = $this->purchase_db->select($field)
                ->where_in("id", $ids)
                ->get($this->table_name)
                ->result_array();
            $data = is_array($rows) ? $rows : [];
        }
        return $data;
    }


    /**
     * 更新报关状态
     * @author Jaden
     * @param string $purchase_number     采购单号
     * @param string $sku
     * @param int $customs_status 报关状态
     * @return bool
     */
    public function update_item_customs_status($purchase_number,$sku,$customs_status){
        if( empty($purchase_number) or empty($sku) or empty($customs_status) ){
            return false;
        }
        $this->purchase_db->where('purchase_number', $purchase_number);
        $this->purchase_db->where('sku', $sku);
        $data['customs_status'] = $customs_status;
        $result = $this->purchase_db->update($this->table_name, $data);
        return $result;
    }

    /**
     * 一个po是否退税、供应商代码、结算方式、支付方式、结算比例不一致时，采购经理审核时,不允许通过   新版本
     * @author yefanli
     * @time
     */
    public function new_check_purchase_disabled($purchase_number)
    {
        $res = [];
        $field = 'map.purchase_number,
                pa.sku,
                pb.is_drawback,
                pb.supplier_code,
                pc.account_type,
                pc.pay_type,
                pc.supplier_code as pc_supplier_code,
                su.is_drawback as su_is_drawback,
                su.supplier_code';
        $data = $this->purchase_db->from('purchase_suggest_map as map')
            ->select($field)
            ->join('purchase_suggest as su', 'map.demand_number=su.demand_number', 'left')
            ->join('product as pb', 'map.sku=pb.sku', 'inner')
            ->join('purchase_order as pc', 'map.purchase_number=pc.purchase_number', 'inner')
            ->join('purchase_order_items as pa', 'map.purchase_number=pa.purchase_number and map.sku=pa.sku', 'left')
            ->where_in('map.purchase_number', $purchase_number)
            ->get()
            ->result_array();
        if($data && count($data) > 0){
            $list = [];
            foreach ($data as $val){
                $pur = $val['purchase_number'];
                if(!in_array($pur, array_keys($list)))$list[$pur] = [];
                $list[$pur][] = $val;
            }
            foreach ($list as $key=>$val){
                $res[$key] = false;
                $supplier =  $supplier1 =  $drawback = [];
                foreach ($val as $v){
                    // 供应商不一致
                    if(!in_array($v['supplier_code'], $supplier)){
                        $supplier[] = $v['supplier_code'];
                        $supplier1[] = $v['supplier_code'];
                    }
                    if(!in_array($v['pc_supplier_code'], $supplier))$supplier1[] = $v['pc_supplier_code'];
                    if(!in_array($v['su_is_drawback'], $drawback))$drawback[] = (string)$v['su_is_drawback'];
                }
                $err_str = '采购单号'.$key;
                $err = [];
                if(count($supplier) > 1)$err[] =  '存在SKU供应商不一致情况';
                if(count($supplier1) > 1)$err[] =  '采购单与备货单供应商不一致';
                if(count($drawback) > 1)$err[] =  '存在SKU是否退税不一致情况';
                if(count($err) > 0)$res[$key] = $err_str.implode(',', $err);
            }
        }
        return $res;
    }

     /**
     * 一个po是否退税、供应商代码、结算方式、支付方式、结算比例不一致时，采购经理审核时,不允许通过
     * @author Jaden
     * @param string $purchase_number
     * @return array
     */
    public function check_purchase_number_is_disabled($purchase_number){
        $result_msg = array('code' => 200,'msg' => '');
        if(empty($purchase_number)){
            $result_msg['code'] = 500;
            $result_msg['msg'] = '采购单号为空-检测';
        }
        /* 查询如果同一采购单号，有退税的也有不退税的 2019-06-21 */
        $order_list = $this->db->select('pa.purchase_number,pa.sku,pb.is_drawback,pb.supplier_code,pc.account_type,pc.pay_type,pc.supplier_code as pc_supplier_code')
                    ->from('purchase_order_items as pa')
                    ->join('product as pb', 'pa.sku=pb.sku', 'inner')
                    ->join('purchase_order as pc', 'pa.purchase_number=pc.purchase_number', 'inner')
                    ->where('pa.purchase_number="'.$purchase_number.'"')
                    ->order_by('pa.create_time DESC')
                    ->get()
                    ->result_array();
        $suggset_list = $this->db->select('su.is_drawback,map.purchase_number,su.sku')
                    ->from('purchase_suggest as su')
                    ->join('purchase_suggest_map as map', 'map.demand_number=su.demand_number', 'inner')
                    ->where('map.purchase_number="'.$purchase_number.'"')
                    ->order_by('su.create_time DESC')
                    ->get()
                    ->result_array();            

        $is_drawback_arr = array_unique( array_column($suggset_list, 'is_drawback') );
        if(count($is_drawback_arr) >1){
            $result_msg['code'] = 500;
            $result_msg['msg'] = '采购单号'.$purchase_number.'存在SKU是否退税不一致情况';  
            return $result_msg; 
        }
        //供应商是否一致
        $pc_supplier_code_arr = array_unique( array_column($order_list, 'pc_supplier_code') );
        $supplier_code_arr = array_unique( array_column($order_list, 'supplier_code') );
        if(count($supplier_code_arr) >1){
            $result_msg['code'] = 500;
            $result_msg['msg'] = '采购单号'.$purchase_number.'存在SKU供应商不一致情况'; 
            return $result_msg;
        }
        if(count(array_unique(array_merge($pc_supplier_code_arr,$supplier_code_arr))) >1){
            $result_msg['code'] = 500;
            $result_msg['msg'] = '采购单号'.$purchase_number.' 采购单与备货单供应商不一致';
            return $result_msg;
        }
        //结算方式
        /*
        $supplier_code = $order_list[0]['supplier_code'];
        $supplier_info = $this->db->select('supplier_settlement')->from('supplier')->where('supplier_code="'.$supplier_code.'"')->get()->row_array();
        if(empty($supplier_info)){
            $result_msg['code'] = 500;
            $result_msg['msg'] = '采购单号'.$purchase_number.'供应商不存在'; 
            return $result_msg;  
        }
        if($supplier_info['supplier_settlement']!=$order_list[0]['account_type']){
           $result_msg['code'] = 500;
           $result_msg['msg'] = '采购单号'.$purchase_number.'和供应商结算方式不一致'; 
           return $result_msg;
        }
        //支付方式
        $supplier_payment_account_info = $this->db->select('payment_method')->from('supplier_payment_account')->where('supplier_code="'.$supplier_code.'" and is_del=0')->get()->row_array();
        if(empty($supplier_payment_account_info)){
            $result_msg['code'] = 500;
            $result_msg['msg'] = '采购单号'.$purchase_number.'与绑定的供应商支付方式不存在'; 
            return $result_msg; 
        }
        if($supplier_payment_account_info['payment_method']!=$order_list[0]['pay_type']){
           $result_msg['code'] = 500;
           $result_msg['msg'] = '采购单号'.$purchase_number.'和供应商支付方式不一致'; 
           return $result_msg;
        }
        */

        if(empty($result_msg['msg'])){
            $result_msg['code'] = 200;
            $result_msg['msg'] = ''; 
        }

        return $result_msg;
        
    }




    /**
     * 数据中心推送SKU信息过来，修改价格(该方法已经作废)
     * @author Jaden
     * @param string $sku     SKU
     * @param array $price
     * @return bool
     */
    public function chang_price($sku,$change_data)
    {
        //根据sku查询未完结,未生成采购单的,未过期的需求单
        $query_builder = $this->purchase_db->where('sku', $sku);
        $query_builder = $query_builder->where('is_create_order', SUGGEST_ORDER_STATUS_N);
        $query_builder = $query_builder->where('expiration_time >', date("Y-m-d H:i:s"));
        $query_builder = $query_builder->from('purchase_suggest as ps');
        $query_builder = $query_builder->select('ps.id,ps.purchase_amount,ps.purchase_unit_price,ps.supplier_code,ps.supplier_name,ps.is_drawback');
        $suggest_list_infos  = $query_builder->get()->result_array();

        //根据sku 查询还未审核的采购单及其关联的需求单号
        $query = $this->purchase_db->from('purchase_suggest_map as smp');
        $query = $query->join('purchase_order as od','od.purchase_number=smp.purchase_number','left');
        $query = $query->join('purchase_order_items as oi','oi.purchase_number=smp.purchase_number','left');
        $query = $query->join('product as pd','pd.sku=smp.sku','left');
        $query = $query->where('oi.sku', $sku);
        $query = $query->where('smp.sku', $sku);
        $query = $query->where_in('purchase_order_status', [PURCHASE_ORDER_STATUS_WAITING_QUOTE,
            PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT,PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER]);
        $query = $query->select('smp.demand_number,oi.id,oi.purchase_unit_price,pd.is_drawback,pd.ticketed_point,od.purchase_number');
        $purchase_order_infos  = $query->get()->result_array();

        if (empty($suggest_list_infos) && empty($purchase_order_infos)){
            return;
        }
        $change_price = $change_data['new_supplier_price'];
        if(isset($change_data['is_drawback'])){
           $is_drawback = $change_data['is_drawback']; 
        }
        
        //开始事物
        try {
            $this->purchase_db->trans_begin();

            if (!empty($suggest_list_infos)){
                
                $update_suggest = [];
                foreach ($suggest_list_infos as $unit_info){

                    if(isset($is_drawback)){
                        $update_suggest[] = [
                            'id' => $unit_info['id'],
                            'purchase_unit_price' => $change_price,//修改后的单价
                            'is_drawback' => $change_data['is_drawback'],//是否退税
                            'purchase_total_price' => $change_price * $unit_info['purchase_amount']//修改后的总价
                        ];
                        $insert_res = operatorLogInsert(
                            [
                                'id' => $unit_info['id'],
                                'type' => 'pur_purchase_suggest_service_center_pust',
                                'content' => '修改需求单单价',
                                'detail' => '修改单价，从【' . $unit_info['purchase_unit_price'] . '】改到【' . $change_price . '】;修改是否退税，从【' . $unit_info['is_drawback'] . '】改到【' . $change_data['is_drawback'] . '】',
                            ]
                        );   
                    }else{
                        $update_suggest[] = [
                            'id' => $unit_info['id'],
                            'purchase_unit_price' => $change_price,//修改后的单价
                            'purchase_total_price' => $change_price * $unit_info['purchase_amount']//修改后的总价
                        ];
                        $insert_res = operatorLogInsert(
                            [
                                'id' => $unit_info['id'],
                                'type' => 'pur_purchase_suggest_service_center_pust',
                                'content' => '修改需求单单价',
                                'detail' => '修改单价，从【' . $unit_info['purchase_unit_price'] . '】改到【' . $change_price . '】',
                            ]
                        );
                    }
                    

                    if(empty($insert_res)) throw new Exception($unit_info['id'].":需求单操作记录添加失败");

                }

                //更新需求表
                if(!empty($update_sugges)){
                    $update_res = $this->purchase_db->update_batch('purchase_suggest', $update_suggest,'id');   
                }
                

                if(empty($update_res) and isset($update_res)) throw new Exception("需求单更新采购价格失败");
            }

            if (!empty($purchase_order_infos)){
                if(isset($is_drawback)){
                    $purchase_number_arr = array_unique(array_column($purchase_order_infos,'purchase_number'));
                    $order_change['is_drawback'] = $change_data['is_drawback'];
                    $this->db->where_in('purchase_number', $purchase_number_arr)->update('purchase_order',$order_change);    
                }
                
                //备货单号
                $demand_numbers = array_column($purchase_order_infos,'demand_number');
                //根据sku查询未完结,未生成采购单的,未过期的需求单
                $query2 = $this->purchase_db->where('sku', $sku);
                $query2 = $query2->where_in('demand_number', $demand_numbers);
                $query2 = $query2->from('purchase_suggest as ps');
                $query2 = $query2->select('ps.id,ps.demand_number,ps.purchase_amount,ps.purchase_unit_price,ps.supplier_code,ps.supplier_name,ps.is_drawback');
                $suggest_list_infos_ordered = $query2->get()->result_array();
                $suggest_update = [];
                foreach ($suggest_list_infos_ordered as $unit_info){

                    if(isset($is_drawback)){
                        $suggest_update[] = [
                            'id' => $unit_info['id'],
                            'purchase_unit_price' => $change_price,//修改后的单价
                            'is_drawback' => $change_data['is_drawback'],//是否退税
                            'purchase_total_price' => $change_price * $unit_info['purchase_amount']//修改后的总价
                        ];

                        $insert_res = operatorLogInsert(
                            [
                                'id' => $unit_info['id'],
                                'type' => 'pur_purchase_suggest_service_center_pust',
                                'content' => '修改需求单单价',
                                'detail' => '修改单价，从【' . $unit_info['purchase_unit_price'] . '】改到【' . $change_price . '】;修改是否退税，从【' . $unit_info['is_drawback'] . '】改到【' . $change_data['is_drawback'] . '】',
                            ]
                        );
                    }else{
                        $suggest_update[] = [
                            'id' => $unit_info['id'],
                            'purchase_unit_price' => $change_price,//修改后的单价
                            'purchase_total_price' => $change_price * $unit_info['purchase_amount']//修改后的总价
                        ];
                        $insert_res = operatorLogInsert(
                            [
                                'id' => $unit_info['id'],
                                'type' => 'pur_purchase_suggest_service_center_pust',
                                'content' => '修改需求单单价',
                                'detail' => '修改单价，从【' . $unit_info['purchase_unit_price'] . '】改到【' . $change_price . '】',
                            ]
                        );    
                    }
                    
                    if(empty($insert_res)) throw new Exception($unit_info['id'].":需求单操作记录添加失败");
                }

                //更新需求表
                if(!empty($suggest_update)){
                    $update_res = $this->purchase_db->update_batch('purchase_suggest', $suggest_update,'id'); 
                }
                
                if(empty($update_res) && isset($update_res)) throw new Exception("已生成采购单的需求单更新采购价格失败");

                //采购单明细id
                $item_ids = array_unique(array_column($purchase_order_infos,'id'));
                $item_info = [];
                foreach ($item_ids as $key => $id){
                    
                    $item_info[]=[
                        'id' => $id,
                        'pur_ticketed_point' =>  $change_data['new_ticketed_point'],
                        'purchase_unit_price' => $change_price
                    ];
                    
                    $insert_res = operatorLogInsert(
                        [
                            'id' => $id,
                            'type' => 'purchase_order_items_service_center_pust',
                            'content' => '修改采购明细表单价',
                            'detail' => '修改单价，从【' . $purchase_order_infos[$key]['purchase_unit_price'] . '】改到【' . $change_price . '】;修改票点，从【' . $purchase_order_infos[$key]['ticketed_point'] . '】改到【' . $change_data['new_ticketed_point'] . '】',
                        ]
                    );

                    if(empty($insert_res)) throw new Exception($purchase_order_infos[$key]['id'].":需求单操作记录添加失败");
                }

                //更新采购明细表
                if(!empty($item_info)){
                   $update_res = $this->purchase_db->update_batch('purchase_order_items', $item_info,'id'); 
                }
                

                if(empty($update_res) && isset($update_res)) throw new Exception("采购明细表更新采购价格失败");

            }

            $this->purchase_db->trans_commit();
        }catch (Exception $e) {
            $this->purchase_db->trans_rollback();
            return ['msg' => $e->getMessage(), 'bool' => FALSE];
        }
        return ['msg' => '成功', 'bool' => TRUE];
    }


    /**
     * 修改开票是否异常
     * @author Manson
     */
    public function change_error_status($purchase_number)
    {
        $this->load->model('Purchase_order_cancel_model','m_order_cancel',false,'purchase');
        $this->load->model('Declare_customs_model','m_declare_customs',false,'purchase');
        //如果实际采购数量=实际入库数量=报关数量=已开票数量 则为正常,反之为异常
        //根据采购单号 查询出采购数量,已开票数量,报损数量
        $qty_info = $this->purchase_db->select("CONCAT(a.purchase_number,'_',a.sku) as tag, a.id, a.purchase_number, a.sku, a.upselft_amount, a.confirm_amount as purchase_qty, a.invoiced_qty, b.loss_amount as loss_qty")
            ->from($this->table_name.' a')
            ->join('purchase_order_reportloss b','a.purchase_number = b.pur_number AND a.sku = b.sku','left')
            ->where('b.status',REPORT_LOSS_STATUS_FINANCE_PASS)//报损审核通过
            ->where('purchase_number',$purchase_number)
            ->get()
            ->result_array();

        if (!empty($qty_info)){
            $qty_info = array_column($qty_info,NULL,'tag');
            $items_id_list = array_column($qty_info,'id');
            $cancel_map = $this->m_order_cancel->get_cancel_qty_by_item_id($items_id_list);
            $declare_customs_map = $this->m_declare_customs->getInvoiceByWherelist($purchase_number);
            $update_data = [];
            foreach ($qty_info as $item){
                $purchase_qty = $item['purchase_qty']??0;//采购数量
                $loss_qty = $item['loss_qty']??0;//报损数量
                $cancel_qty = $cancel_map[$item['id']]??0;//取消数量
                $act_purchase_qty = $purchase_qty - $loss_qty - $cancel_qty;//实际采购数量
                $invoiced_qty = $item['invoiced_qty'];//已开票数量
                $declare_customs_qty = $declare_customs_map[$item['tag']];//报关数量
                $instock_qty = $declare_customs_map[$item['upselft_amount']];//入库数量

                if (($act_purchase_qty == $instock_qty && $act_purchase_qty == $declare_customs_qty && $act_purchase_qty==$invoiced_qty) == 0){//正常
                    $update_data[] = [
                        'id' => $item['id'],
                        'invoice_is_abnormal' =>INVOICE_IS_ABNORMAL_FALSE,
                    ];
                }else{//异常
                    $update_data[] = [
                        'id' => $item['id'],
                        'invoice_is_abnormal' =>INVOICE_IS_ABNORMAL_TRUE,
                    ];
                }
            }
            if (!empty($update_data)){
                $this->purchase_db->update_batch($this->table_name,$update_data,'id');
            }
        }
    }

    /**
     * 查询 采购数量,报损数量,已开票数量
     * @author Manson
     * @param $purchase_number
     * @param string $sku
     * @return array|null
     */
    public function get_qty_info($purchase_number,$sku=''){
        if (empty($purchase_number)){
            return null;
        }
        //根据采购单号 查询出采购数量,已开票数量,报损数量
        $this->purchase_db->select("a.invoice_is_abnormal , a.id, a.purchase_number, a.sku, a.upselft_amount, a.confirm_amount as purchase_qty, a.invoiced_qty, a.uninvoiced_qty, b.loss_amount as loss_qty, b.status as loss_status")
            ->from($this->table_name.' a')
            ->join('purchase_order_reportloss b','a.purchase_number = b.pur_number AND a.sku = b.sku','left');

        $this->purchase_db->where('a.purchase_number',$purchase_number);
        if (!empty($sku)){
            $result = $this->purchase_db->where('a.sku',$sku)->get()->row_array();
        }else{
            $result = $this->purchase_db->get()->result_array();
        }
        return $result;
    }

    /**
     * 查询采购单下的sku
     * @author Manson
     * @param $purchase_number
     * @return array
     */
    public function get_po_sku_info($purchase_number)
    {
        if (empty($purchase_number)){
            return [];
        }
        $result = $this->purchase_db->select('purchase_number,sku')
            ->from($this->table_name)
            ->where('purchase_number',$purchase_number)
            ->get()->result_array();
        return $result;
    }

    /**
     * 变成等待到货状态,保存当时的开票单位,开票品名
     */
    public function save_invoice_info($purchase_number)
    {
        $result = $this->purchase_db->select('a.id,b.declare_unit,b.export_cname')
            ->from($this->table_name. ' a')
            ->join('product'. ' b','a.sku = b.sku','left')
            ->where('a.purchase_number',$purchase_number)
            ->get()->result_array();
        if (!empty($result)){
            $this->purchase_db->update_batch($this->table_name,$result,'id');
        }

    }

    /**
     * 批量上次采购价格
     * @author yefanli
     */
    public function getSkuLastPurchasePrice($sku)
    {
        $res = [];
        try{
            $status = [
                PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE
            ];
            $data = $this->purchase_db->from('purchase_order_items as b')
                ->select('b.purchase_unit_price,b.product_base_price,a.is_drawback,b.sku,a.audit_time')
                ->join('pur_purchase_order AS a', 'a.purchase_number = b.purchase_number', 'inner')
                ->where_in('b.sku', $sku)
                ->where_in('a.purchase_order_status', $status)
                ->order_by("audit_time desc")
//                ->group_by('b.sku')
                ->get()
                ->result_array();
            if($data && count($data) > 0){
                foreach ($data as $val){
                    if(in_array($val['sku'], array_keys($res)))continue;
                    if(!empty($val['sku']))$res[$val['sku']] = $val['is_drawback'] == 0?$val['product_base_price']:$val['purchase_unit_price'];
                }
            }
        }catch (Exception $e){}
        return $res;
    }
}