<?php
/**
 * Created by PhpStorm.
 * 缺货列表(ERP同步过来的数据表）
 * User: Jolon
 * Date: 2018/12/29 0029 11:50
 */
class Sku_outofstock_statisitics_model extends Purchase_model {
    protected $table_name   = 'sku_outofstock_statisitics';// 缺货列表(ERP同步过来的数据表）

    public function __construct(){
        parent::__construct();
    }


    public function tableName() {
        return $this->table_name;
    }

    //定时任务查找ERP同步表缺货数据
    public function get_outofstock_list($offset, $limit,$field='*'){
        $this->purchase_db->select($field);
        $this->purchase_db->from($this->table_name);
        $results = $this->purchase_db->limit($limit, $offset)->group_by('sku')->order_by('earlest_outofstock_date','DESC')->get()->result_array();
        return $results;
    }

    //根据SKU获取物流单号和PO号
    public function get_order_list($skus){
        if(empty($skus)){
            return false;
        }
        $this->purchase_db->select('ppoi.sku,ppoi.purchase_number,ppy.express_no');
        $this->purchase_db->from('purchase_order_items as ppoi');
        $this->purchase_db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $this->purchase_db->join('purchase_order_pay_type as ppy', 'ppy.purchase_number=ppo.purchase_number', 'left');
        $this->purchase_db->where_in('ppoi.sku',$skus);
        $this->purchase_db->where_in('ppo.purchase_order_status','7,10');
        $order_list = $this->purchase_db->get()->result_array();
        return $order_list;

    }


    public function get_platform_code_by_sku($sku){
        if(empty($sku)){
            return false;
        }
        $this->purchase_db->select('platform_code,sum(lack_quantity) as lack_quantity');
        $this->purchase_db->from($this->table_name);
        $results = $this->purchase_db->where('sku',$sku)->group_by('platform_code')->get()->result_array();
        return $results;
    }




}