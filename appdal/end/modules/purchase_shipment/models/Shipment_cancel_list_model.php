<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2020/4/14
 * Time: 16:22
 * 计划部取消
 */

class Shipment_cancel_list_model extends Purchase_model
{
    protected $table_name = 'shipment_cancel_list';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_order');
    }

    public function check_audit_status($ids)
    {
        $result = $this->purchase_db->select('id,audit_status,new_demand_number,
        purchase_number,sku')
            ->from($this->table_name)
            ->where_in('id',$ids)
            ->get()
            ->result_array();
        return empty($result)?[]:array_column($result,NULL,'id');

    }

    /**
     * 根据申请id查询出order_items表的id
     * @author Manson
     * @param $ids
     * @return array
     */
    public function get_order_items_ids($ids)
    {
        $result = $this->purchase_db->select('b.id')
            ->from($this->table_name.' a')
            ->join('purchase_order_items b','a.purchase_number = b.purchase_number AND a.sku = b.sku','left')
            ->where_in('a.id',$ids)
            ->get()->result_array();
        return $result;
    }

    /**
     * 获取要同步更新取消的数据
     * @author Manson
     * @param $ids
     * @return array
     */
    public function get_shared_data($ids)
    {
        $data = [];
        $result = $this->purchase_db->select('id, purchase_number, sku, audit_status, apply_cancel_qty, new_demand_number')
            ->from($this->table_name)
            ->where_in('id',$ids)
            ->get()->result_array();
        if (empty($result)){
            return [];
        }
        foreach ($result as $key => $item){
            $tag = sprintf('%s%s',$item['purchase_number'],$item['sku']);
            $data[$tag][] = $item;
        }
        return $data;
    }

    /**
     * 该备货单关联的申请ID的计划申请数量之和
     * @author Manson
     * @param $demand_number_list
     * @return array
     */
    public function get_sum_apply_cancel_qty($demand_number_list,$plan_cancel_ids)
    {
        $result = $this->purchase_db->select('demand_number,sum(apply_cancel_qty) as sum_apply_cancel_qty')
            ->from($this->table_name)
            ->where_in('id',$plan_cancel_ids)
            ->where_in('demand_number',$demand_number_list)
            ->group_by('demand_number')
            ->get()->result_array();

        return empty($result)?[]:array_column($result,'sum_apply_cancel_qty','demand_number');
    }

}