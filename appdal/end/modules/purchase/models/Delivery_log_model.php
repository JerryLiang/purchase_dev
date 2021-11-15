<?php
/**
 * Created by PhpStorm.
 * 权均交期日志模型
 * User: Jaden
 * Date: 2019/01/16 
 */
class Delivery_log_model extends Purchase_model {

    protected $table_name   = 'sku_avg_delivery_time_log';
    protected $warehourse_result  = 'pur_warehouse_results';

    /**
     * 返回表名
     * @author Jaden 2019-1-16
     */
    public function tableName() {
        return $this->table_name;
    }

    

    /**
     * 批量插入数据
     * @author Jaden
     * @param $insert_data
     * @return bool
     * 2019-1-16
     */
    public function insert_delivery_time_log_all($insert_data){
        if(empty($insert_data)) {
            return false;
        }    
        $result = $this->purchase_db->insert_batch($this->table_name,$insert_data);
        return $result;
    }

    /**
     * 根据 采购单号和SKU查数据
     * @author Jaden
     * @param $purchase_number
     * @param $sku
     * @return array
     * 2019-03-15
     */
    public function get_delivery_log_info($purchase_number,$sku){
        if(empty($purchase_number) || empty($sku)){
            return false;
        }
        $this->purchase_db->where('purchase_number',$purchase_number);
        $this->purchase_db->where('sku',$sku);
        $this->purchase_db->where('is_calculate',1);
        $delivery_log_info = $this->purchase_db->get($this->table_name)->row_array();
        return $delivery_log_info;
    }


    /**
     * 根据 where获取数据
     * @author Jaden
     * @param $where
     * @return array
     * 2019-03-15
     */

    public function get_delivery_log_list_by_where($where,$group='',$field='*',$limit=''){
        if(empty($where)){
            return false;
        }
        $this->purchase_db->select($field);
        $this->purchase_db->where($where);
        if(!empty($group)){
            $this->purchase_db->group_by($group);
        }
        $this->purchase_db->order_by('create_time', 'DESC');
        if(!empty($limit)){
            $arrival_record_arr = $this->purchase_db->limit($limit)->get($this->table_name)->result_array();    
        }else{
            $arrival_record_arr = $this->purchase_db->get($this->table_name)->result_array();
        }
        return $arrival_record_arr;

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
        $this->purchase_db->from($this->warehourse_result.' as a');
        $this->purchase_db->join('purchase_order as p', 'a.purchase_number=p.purchase_number', 'left');
        $this->purchase_db->join('purchase_compact_items as c', 'a.purchase_number=c.purchase_number', 'left');
        $this->purchase_db->where($where);
        $arrival_record_arr = $this->purchase_db->limit($limit, $offset)->get()->result_array();

        //echo $this->purchase_db->last_query();exit;
        return $arrival_record_arr;

    }






}