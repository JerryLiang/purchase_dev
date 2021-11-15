<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购到货记录模型
 */

class Arrival_record_model extends Purchase_model {

    protected $table_name = 'arrival_record';

    public function __construct(){
        parent::__construct();
        $this->load->model('Reject_note_model');
    }

    /**
     * 保存采购单到货记录表
     * @param array $data
     * @author harvin 2019-2-19
     * @return array|bool
     */
    public function arrival_record_save($data){
        if(!is_array($data)){
            return false;
        }
        $query = $this->purchase_db;
        try{
            //获取产品名称及到货数量
            $order_items = $query->select('product_name,receive_amount,id')
                ->where('purchase_number', $data['purchase_order_no'])
                ->where('sku', $data['sku'])
                ->get('purchase_order_items')
                ->row_array();
            if(empty($order_items)){
                throw new Exception("采购单不存在");
            }else{
                $data['name'] = $order_items['product_name'];
            }
            //查询入库记录表
            $warehouse_results = $query->select('arrival_qty,arrival_date')
                ->where('purchase_number', $data['purchase_order_no'])
                ->where('sku', $data['sku'])
                ->where('receipt_id', $data['qc_id'])
                ->get('warehouse_results')
                ->row_array();
            //开启事物
            $query->trans_begin();
            //更新采购单主表状态
            if(empty($warehouse_results)){
                $this->load->model('Redis_model');
                $this->Redis_model->received_order_status_queue($data['purchase_order_no']);
                //更新到货数量
                $receive_amount = $order_items['receive_amount'] + $data['delivery_qty'];
                $query->where('purchase_number', $data['purchase_order_no'])->where('sku', $data['sku'])->update('purchase_order_items', ['receive_amount' => $receive_amount]);
            }
            $query->insert('arrival_record', $data);

            if($query->trans_status() === false){
                $query->trans_rollback();

                return ['msg' => '记录失败', 'bool' => false];
            }else{
                $query->trans_commit();

                return ['msg' => '记录成功', 'bool' => true];
            }
        }catch(Exception $exc){
            $query->trans_rollback();
            $exc->getMessage();

            return ['msg' => $exc->getMessage(), 'bool' => false];
        }
    }

    /**
     * 获取待推送仓库的数据
     * @author harvin 2019-3-12
     * @return array
     */
    public function get_cancel_warehouse($add_ids = []){
        $data_list        = [];
        $temp             = [];
        $cancel_warehouse = $this->purchase_db
            ->select('a.*,s.source_from,s.purchase_type_id')
            ->from('purchase_cancel_warehouse as a')
            ->join('purchase_suggest as s', 's.demand_number=a.demand_number', 'left')
            ->where('a.is_push', 0)
            ->where_in('a.id', $add_ids)
            ->get()
            ->result_array();
        if(empty($cancel_warehouse)){
            throw new Exception('没有相关数据推送给仓库');
        }
        foreach($cancel_warehouse as $row){
            $temp['id']         = $row['id'];
            $temp['cancel_id']  = $row['cancel_id'];
            $temp['pur_number'] = $row['purchase_number'];
//            $temp['warehouse_code'] = $row['warehouse_code'];
            $temp['transit_warehouse'] = $row['transit_warehouse'];
            $temp['sku']               = $row['sku'];
            $temp['ctq']               = $row['ctq'];
            $temp['cancel_operator']   = $row['cancel_operator'];
            $temp['type']              = $row['type'];
            $temp['check_operator']    = $row['check_operator'];
            $temp['status']            = $row['status'];
            $temp['demand_number']     = !empty($row['demand_number']) ? $row['demand_number'] : null;
            $temp['purchase_type']     = $row['purchase_type'];
            $temp['source_from']       = $row['source_from'];

            if($row['purchase_type_id'] == PURCHASE_TYPE_OVERSEA){//海外仓
                $temp['warehouse_code'] = $row['warehouse_code'];
            }else{
                $temp['warehouse_code'] = $row['warehouse_code'];
            }

            $data_list[] = $temp;
        }

        return $data_list;
    }

    /**
     * 更新推送状态
     * @param array $data
     * @author harvin 2019-3-12
     */
    public function update_cancel_warehouse(array $data, $type = true){
        if(empty($data)){
            throw new Exception('仓库返回信息，错误');
        }
        foreach($data as $val){
            if($type){
                $update = $this->purchase_db
                    ->where('cancel_id', $val['cancel_id'])
                    ->where('purchase_number', $val['pur_number'])
                    ->where('sku', $val['sku'])
                    ->update('purchase_cancel_warehouse', ['is_push' => 1, 'update_time' => date('Y-m-d H:i:s')]);
            }else{

                $update = $this->purchase_db
                    ->where('cancel_id', $val['cancel_id'])
                    ->where('purchase_number', $val['pur_number'])
                    ->where('sku', $val['sku'])
                    ->update('purchase_cancel_warehouse', ['is_push' => 3, 'update_time' => date('Y-m-d H:i:s')]);
            }

            if(empty($update)){
                throw new Exception('取消未到货--待推送仓库表,更新失败');
            }
            $update_detail = $this->purchase_db
                ->where('cancel_id', $val['cancel_id'])
                ->where('purchase_number', $val['pur_number'])
                ->where('sku', $val['sku'])
                ->update('purchase_order_cancel_detail', ['is_push' => 1]);
            if(empty($update_detail)){
                throw new Exception('采购单-取消未到货数量-子表,更新失败');
            }
        }

    }

    /**
     * 同步采购记录以及PO单审核时间到权均交期日志表
     * @param string $where
     * @author Jaden 2019-3-18
     */
    public function get_arrival_record_list_by_where($where, $limit, $offset, $field = '*'){
        $arrival_record_arr = array();
        if(empty($where)){
            return false;
        }
        $this->purchase_db->select($field);
        $this->purchase_db->from($this->table_name.' as a');
        $this->purchase_db->join('purchase_order as p', 'a.purchase_order_no=p.purchase_number', 'left');
        $this->purchase_db->join('purchase_compact_items as c', 'a.purchase_order_no=c.purchase_number', 'left');
        $this->purchase_db->where($where);
        $arrival_record_arr = $this->purchase_db->limit($limit, $offset)->get()->result_array();

        //echo $this->purchase_db->last_query();exit;
        return $arrival_record_arr;

    }


}
