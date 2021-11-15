<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/4
 * Time: 10:50
 */
class Return_after_storage_part_model extends Purchase_model
{
    protected $table_name = 'return_after_storage_part';
    protected $table_main = 'return_after_storage_main';


    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_order');
    }

    /**
     * 查询申请子id的退货数量等信息
     * @author Manson
     * @param $params
     * @return array
     */
    public function get_part_info($params)
    {
        if (empty($params)){
            return [];
        }

        $map = [];
        $key = '';
        $this->purchase_db->select('part.*, main.main_number, main.return_qty, main.wms_return_qty, main.pur_return_qty')
            ->from($this->table_name.' part')
            ->join($this->table_main.' main','main.main_number = part.main_number','left');
        if (isset($params['id']) && !empty($params['id'])){
            $this->purchase_db->where_in('part.id',$params['id']);
            $key = 'id';
        }
        if (isset($params['part_number']) && !empty($params['part_number'])){
            $this->purchase_db->where_in('part.part_number',$params['part_number']);
            $key = 'part_number';
        }
        if (isset($params['main_number']) && !empty($params['main_number'])){
            $this->purchase_db->where_in('part.main_number',$params['main_number']);
        }

        $result = $this->purchase_db->get()->result_array();
        if (!empty($key)){
            $map = array_column($result,NULL,$key);
        }
        return $map;
    }

    /**
     * 详情页
     * @author Manson
     * @param $ids
     * @return array
     */
    public function get_purchase_confirm_detail($ids)
    {
        if (empty($ids)){
            return [];
        }

        $result = $this->purchase_db->select('a.*,b.sku, b.product_name, b.sample_packing_weight, b.return_qty, b.return_reason, b.return_warehouse_code, b.unit_price_without_tax')
            ->from($this->table_name. ' a')
            ->join($this->table_main. ' b','a.main_number = b.main_number', 'left')
            ->where_in('a.id',$ids)
            ->get()->result_array();
        return $result;
    }

    /**
     * 详情页
     * @author Manson
     * @param $ids
     * @return array
     */
    public function get_return_status($params)
    {
        if (empty($params)){
            return [];
        }

        $map = [];
        $key = '';
        $this->purchase_db->select('id,part_number,return_status')
            ->from($this->table_name);
        if (isset($params['id']) && !empty($params['id'])){
            $this->purchase_db->where_in('id',$params['id']);
            $key = 'id';
        }
        if (isset($params['part_number']) && !empty($params['part_number'])){
            $this->purchase_db->where_in('part_number',$params['part_number']);
            $key = 'part_number';
        }
        $result = $this->purchase_db->get()->result_array();

        if (!empty($key)){
            $map = array_column($result,NULL,$key);
        }
        return $map;
    }

    /**
     * 获取配库数据
     */
    public function get_push_data($ids)
    {
        return $this->purchase_db->select('a.*,
         b.sku, b.product_name, b.sample_packing_weight, b.return_qty, b.return_reason, b.return_warehouse_code, 
         b.unit_price_without_tax, b.buyer_name')
            ->from($this->table_name. ' a')
            ->join($this->table_main. ' b','a.main_number = b.main_number', 'left')
            ->where_in('a.id',$ids)
            ->get()->result_array();
    }
    /**
     * 获取推送的详情数据
     * @author Manson
     * @param $ids
     * @return array
     */
    public function get_detail_push_to_wms($ids)
    {
        if (empty($ids)){
            return [];
        }
        $push_data = [];

        $result = $this->get_push_data($ids);
        foreach ($result as $key => $value)
        {
            if (!empty($value['restricted_supplier'])){
                $value['restricted_supplier'] = array_keys(json_decode($value['restricted_supplier'],true));
            }

            $push_data['push_data'][] = [
                'demand_order_id' => $value['part_number'],
//                'warehouse_code' => 'TH_'.$value['return_warehouse_code'], // 20200806 10:22  因推送到wms，加了TH_前缀，业务拒绝加虚拟仓导致失败，现去掉TH_前缀   叶凡立
                'warehouse_code' => $value['return_warehouse_code'],
                'sku' => $value['sku'],
                'num' => $value['pur_return_qty'],
                'th_price' => $value['return_unit_price'],
                'purchaser' => $value['buyer_name'],
                'freight_pay_type' => $value['freight_payment_type'],
                'provider_code' => $value['supplier_code'],
                'provider_contact' => $value['contact_person'],
                'provider_phone' => $value['contact_number'],
                'provider_province' => $this->get_province_mane($value['contact_province']),
                'provider_address' => $value['contact_addr'],
                'allow_provider_codes' => $value['restricted_supplier']
            ];
            $push_data['part_number_list'][] = $value['part_number'];

        }
        if (!empty($push_data)){
            $push_data['push_data'] = json_encode($push_data['push_data']);
        }
        return $push_data;

    }

    /**
     * 获取云仓配库数据
     */
    public function get_detail_push_to_new_wms($ids)
    {
        $res = [];
        if (empty($ids))return $res;

        $result = $this->get_push_data($ids);
        if(!$result || empty($result))return $res;
        foreach ($result as $val){
            $row = [];
            if(!isset($row['cargoOwnerId']))$row['cargoOwnerId'] = 8; // 固定写8  王时敏
            if(!isset($row['isPart']))$row['isPart'] = 0;
            if(!isset($row['orderNo']))$row['orderNo'] = $val['main_number'];
            if(!isset($row['operateType']))$row['operateType'] = 46; // 46：滞销单－配库成功  王时敏
            if(!isset($row['orderType']))$row['orderType'] = 12;  // 12"滞销退货单"  王时敏
            if(!isset($row['warehouseCode']))$row['warehouseCode'] = $val['return_warehouse_code'];
            $row['skus'] = [];
            $row['skus'][] = [
                "idempotent"            => $val['id']."_".$val['main_number'],
                "sku"                   => $val['sku'],
                "quantity"              => $val['pur_return_qty'],
                "soldQuantity"          => $val['return_qty'],
            ];
            $res[] = $row;
        }


        return $res;
    }

    /**
     * 获取省份名称
     * @param $province
     * @return mixed
     */
    public function get_province_mane($province){
        $name ='';
        $result = $this->purchase_db->select('region_name name')
            ->from('pur_region')
            ->where('region_code',$province)
            ->where('pid',1)
            ->get()->row_array();
        if(!empty($result['name'])){
            $name =  $result['name'];
        }
        return $name;
    }
}