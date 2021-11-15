<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/4
 * Time: 10:50
 */
class Return_after_storage_main_model extends Purchase_model
{

    protected $table_name = 'return_after_storage_main';
    protected $table_part = 'return_after_storage_part';


    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_order');
    }


    public function batch_update($update_data,$index)
    {
        return $this->purchase_db->update_batch($this->table_name,$update_data,$index);
    }

        /**
     * 导入时查询sku对应的数据
     * @author Manson
     */
    public function get_import_sku_info($sku_list)
    {
        if (empty($sku_list)){
            return [];
        }
        $map = [];
        $result = $this->purchase_db->select('pro.sku, pro.purchase_price, pro.product_weight, pro.product_name, order.buyer_id, order.buyer_name')
            ->from('product pro')
            ->join('purchase_order_items items','pro.sku = items.sku','left')
            ->join('purchase_order order','order.purchase_number = items.purchase_number','left')
            ->where_in('pro.sku',$sku_list)
            ->order_by('order.create_time desc')//降序排 采购时间
            ->get()->result_array();

        foreach ($result as $item){
            if (!isset($map[$item['sku']])){
                $map[$item['sku']] = [
                    'sample_packing_weight' => $item['product_weight']??'',//样品包装重量(克)
                    'product_name' => $item['product_name']??'',//产品名称
                    'unit_price_without_tax' => $item['purchase_price']??0,//未税单价
                    'buyer_id' => $item['buyer_id']??0,
                    'buyer_name' => $item['buyer_name']??'',
                ];
            }
        }
        return $map;
    }


    /**
     * 采购确认详情
     * @author Manson
     */
    public function get_purchase_confirm_detail($ids)
    {
        if (empty($ids)){
            return [];
        }
        $result = $this->purchase_db->select('
            b.id,
            b.main_number,
            b.sku, 
            b.product_name,
            b.sample_packing_weight, 
            b.return_qty, b.return_reason, 
            b.return_warehouse_code, 
            b.unit_price_without_tax,
            b.supplier_code,
            b.supplier_name,
            b.return_unit_price
        ')
            ->from($this->table_name. ' b')
            ->where_in('b.id',$ids)
            ->get()->result_array();
        return $result;
    }

    public function get_main_info($params)
    {

        if (empty($params)){
            return [];
        }
        $map = [];
        $this->purchase_db->select('*')
            ->from($this->table_name);
        if (isset($params['id']) && !empty($params['id'])){
            $this->purchase_db->where_in('id',$params['id']);
            $key = 'id';
        }
        if (isset($params['main_number']) && !empty($params['main_number'])){
            $this->purchase_db->where_in('main_number',$params['main_number']);
            $key = 'main_number';
        }
       $result = $this->purchase_db->get()->result_array();

        if (isset($key)){
            $map = array_column($result,NULL,$key);
        }

        return $map;
    }



}