<?php
/**
 * Created by PhpStorm.
 * 缺货数量，同步ERP的数据
 * User: Jaden
 * Date: 2018/12/29 0029 11:50
 */
class Sku_outofstock_statisitics_model extends Purchase_model {
    protected $table_name   = 'sku_outofstock_statisitics';// 库存表

    public function __construct(){
        parent::__construct();
    }


    public function tableName() {
        return $this->table_name;
    }
    /**
     * 获取 sku 的总缺货数量
     * @author Jaden
     * @param array  $skus
     * @return array
     * 2019-05-24
     */
    public function get_outofstock_total_quantity($skus){
        if(empty($skus)) return false;
        $sku_data = array();
        $this->purchase_db->select('sku,sum(lack_quantity) as lack_quantity');
        $this->purchase_db->where_in('sku',$skus);
        $results = $this->purchase_db->get($this->table_name)
            ->result_array();
        foreach ($results as $key => $value) {
            $sku_data[$value['sku']]['lack_quantity'] = $value['lack_quantity']; 
         }    

        return $sku_data;
    }

  










}