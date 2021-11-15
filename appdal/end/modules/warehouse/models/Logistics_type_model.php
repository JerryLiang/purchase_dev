<?php
/**
 * Created by PhpStorm.
 * 物流类型模型
 * User: Jolon
 * Date: 2018/12/29 0029 11:50
 */
class Logistics_type_model extends Purchase_model {

    protected $table_name = 'logistics_logistics_type';

    /**
     * 获取 物流类型列表
     * @author Jolon
     * @return array
     */
    public function get_logistics_type_list(){
        $logistics_type_list = $this->purchase_db->select('*')
            ->get($this->table_name)
            ->result_array();

        return $logistics_type_list;
    }

    /**
     * 仓库code=>name
     * @author Manson
     */
    public function logistics_code_to_name()
    {
        $result = $this->purchase_db->select('type_code, type_name')
            ->from($this->table_name)->get()->result_array();
        return empty($result)?[]:array_column($result,'type_name','type_code');
    }

}