<?php
/**
 * Created by PhpStorm.
 * 合同单 明细表信息
 * User: Jolon
 * Date: 2019/01/208 0027 11:23
 */

class Compact_items_model extends Purchase_model {

    protected $table_name = 'purchase_compact_items';

    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取 合同明细
     * @author Jolon
     * @param string $compact_number  合同编号
     * @param bool   $purchase_number 采购单编号
     * @param bool   $is_only         是否只查询一条
     * @return mixed
     */
    public function get_compact_item($compact_number,$purchase_number = false,$is_only = false){
        $query_builder      = $this->purchase_db;
        $query_builder->where('compact_number',$compact_number);

        if($is_only and $purchase_number !== false){// 只查询一条记录
            $query_builder->where('purchase_number',$purchase_number);
            $results        = $query_builder->get($this->table_name)->row_array();
        }else{
            $results       = $query_builder->get($this->table_name)->result_array();
        }
        return $results;
    }




}