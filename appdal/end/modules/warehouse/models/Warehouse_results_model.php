<?php
/**
 * Created by PhpStorm.
 * 仓库操作模型
 * User: Jaden
 * Date: 2018/12/29 0029 11:50
 */
class Warehouse_results_model extends Purchase_model {

    protected $table_name   = 'warehouse_results';// 数据表名称
    protected $table_warehouse_result_main   = 'warehouse_results_main';//仓库入库主表

    //根据采购单号和SKU查询入库记录
    public function get_warehousing_list($purchase_number,$sku,$offset, $limit,$field='*'){
        $this->purchase_db->select($field);
        $this->purchase_db->from($this->table_name);
        $this->purchase_db->where('purchase_number',$purchase_number);
        $this->purchase_db->where('sku',$sku);
        $clone_db = clone($this->purchase_db);
        $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        $this->purchase_db=$clone_db;
        $results=$this->purchase_db->limit($limit, $offset)->order_by('instock_date','DESC')->get()->result_array();
        //echo $this->purchase_db->last_query();exit;
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
     * 根据 items_id 集合获取数据集合
     * @author liwuxue
     * @date 2019/2/14 17:56
     * @param array $items_ids
     * @param string $field
     * @return array
     */
    public function get_list_by_items_ids(array $items_ids, $field = "*")
    {
        $data = [];
        $items_ids = array_unique(array_filter($items_ids));
        if (!empty($items_ids)) {
            $rows = $this->purchase_db
                ->select($field)
                ->where_in("items_id", $items_ids)
                ->get($this->table_name)
                ->result_array();
            !empty($rows) && $data = $rows;
        }
        return $data;
    }


    /**
     * 根据 items_id获取入库记录主表数据
     * @author Manson
     * @param array $items_ids
     * @param string $field
     * @return array
     */
    public function get_instock_list_by_items_ids(array $items_ids, $field = "*")
    {
        $data = [];
        $items_ids = array_unique(array_filter($items_ids));
        if (!empty($items_ids)) {
            $rows = $this->purchase_db
                ->select($field)
                ->where_in("items_id", $items_ids)
                ->get($this->table_warehouse_result_main)
                ->result_array();
            !empty($rows) && $data = $rows;
        }
        return empty($data)?[]:array_column($data,null,'items_id');
    }

}