<?php

/**
 * Created by PhpStorm.
 * 省份、城市、区域
 * User: jackson
 * Date: 2019/01/09 0029 11:50
 */
class Supplier_address_model extends Purchase_model
{
    protected $table_name = 'pur_region';// 数据表名称

    public function __construct(){
        parent::__construct();
    }

    /**
     * @desc 获取城市省份区县
     * @author Jackon
     * @param int $level 级别类型(region_type,1:省、2:城市、3:区县)
     * @param array $parames 条件(pid:父ID)
     * @return array
     */
    public function get_address_by_Level($level = REGION_TYPE_PROVINCE, $parames = array())
    {
        //搜索条件
        $condition = [];
        $condition['region_type'] = $level;
        if (!empty($parames) && isset($parames['pid'])) {
            $condition['pid'] = $parames['pid'];
        }

        //查询字段
        $fields = '*';

        return $this->getDataByCondition($condition, $fields);

    }

    /**
     * 根据ID 获取 对应省、市、区 名称
     * @author Jolon
     * @param $region_code
     * @return string
     */
    public function get_address_name_by_id($region_code){
        if(empty($region_code)) return '';
        $region_name = $this->purchase_db->where(['region_code' => $region_code])
            ->select('region_name')
            ->get($this->table_name)
            ->row_array();
        return isset($region_name['region_name'])?$region_name['region_name']:'';
    }


    /**
     * 根据ID 获取 对应省、市、区 名称
     * @param int $level
     * @param int $pid
     * @return mixed
     */
    public function get_address_list ($level = REGION_TYPE_PROVINCE,$pid =1){
        $sql = "select pid,region_name,region_type,region_code from ".$this->table_name." where region_type=".$level." and pid=".$pid;
        return  $this->purchase_db->query($sql)->result_array();
    }



}