<?php
/**
 * Created by PhpStorm.
 * 国家模型
 * User: Jeff
 * Date: 2019/05/28 0029 11:50
 */
class Country_model extends Purchase_model {

    protected $table_name = 'country';

    /**
     * 获取 国家列表
     * @author Jeff
     * @return array
     */
    public function get_country_list(){
        $country_list = $this->purchase_db->select('*')
            ->get($this->table_name)
            ->result_array();

        return $country_list;
    }


}