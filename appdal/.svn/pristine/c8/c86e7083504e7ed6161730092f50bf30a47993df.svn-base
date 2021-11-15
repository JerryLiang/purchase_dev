<?php

/**
 * 验货
 */
class Supplier_check_model extends Purchase_model
{
    protected $table_name = 'product_statistics';

    /**
     * Supplier_check_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * sku退款率列表
     */
    public function get_list($params=[], $sum=false, $export=false)
    {
        $base = $this->purchase_db->from($this->table_name." as ps")
            ->join("pur_product as p", "ps.sku=p.sku", "inner")
            ->join("pur_supplier as s", "s.supplier_code=ps.supplier_code", "left")
            ->where("statistics_type=", 1);
        if(SetAndNotEmpty($params, 'id')){
            $id = explode(",", $params['id']);
            $id = array_unique($id);
            if(count($id) > 0){
                $base->where_in("ps.id", $id);
            }
        }
        if(SetAndNotEmpty($params, 'supplier_code'))    $base->where("ps.supplier_code=", $params['supplier_code']);
        if(SetAndNotEmpty($params, 'supplier_code'))    $base->where("ps.supplier_code=", $params['supplier_code']);
        if(SetAndNotEmpty($params, 'sku'))              $base->where("ps.sku=", $params['sku']);
        if(SetAndNotEmpty($params, 'title'))            $base->where('p.product_name like "%'.$params['title'].'%"');
        if(SetAndNotEmpty($params, 'min_refund_rate'))  $base->where("ps.refund_rate >=", $params['min_refund_rate']);
        if(SetAndNotEmpty($params, 'max_refund_rate'))  $base->where("ps.refund_rate <=", $params['max_refund_rate']);

        $sum_query = clone $base;
        $sum_data = $sum_query->select('count(1) as count_all')->get()->row_array();
        $count_all = isset($sum_data['count_all']) && !empty($sum_data['count_all']) ? $sum_data['count_all'] : 0;
        if($sum){
//            return $count_all;
        }
        $base->select("ps.*,p.product_name,p.product_thumb_url,s.supplier_name");
        $page = SetAndNotEmpty($params, 'page') ? (int)$params['page'] : 1;
        $limit = SetAndNotEmpty($params, 'limit') ? (int)$params['limit'] : 20;
        $offset = ($page - 1) * $limit;
        $data = $base->order_by("p.id desc")->limit($limit, $offset)->get()->result_array();

        $data_temp = [];
        if(!$data || count($data) == 0)return $data_temp;
        foreach ($data as $val){
            // 针对数据进行处理
            $data_temp[] = $val;
        }
        return ["value" => $data_temp, "page_data" => [
            "count"=> (int)$count_all,
            "page" => $page,
            "limit" => $limit]
        ];
    }

    /**
     * 导入SKU退款率
     */
    public function refund_rate_import($data=[])
    {
        $res = ["code" => 0, "msg" => "更新失败！"];

        $len = 1000; // 单次处理条数
        if(count($data) > $len){
            $data = array_chunk($data, $len);
        }else{
            $data = [$data];
        }
        $uid = getActiveUserName();
        $err = [];

        foreach ($data as $value){
            $sku = array_column($value, 'sku');
            $has_temp = $this->purchase_db->from($this->table_name)
                ->select("sku")
                ->where_in("sku", $sku)
                ->where(["statistics_type" => 1])
                ->get()->result_array();
            $product_list = $this->purchase_db->from("product")
                ->select("sku,supplier_code")
                ->where_in("sku", $sku)
                ->get()->result_array();
            $sku_temp = [];
            foreach ($product_list as $th_val){
                $sku_temp[$th_val['sku']] = $th_val['supplier_code'];
            }
            $has_list = array_column($has_temp, 'sku');
            $time = date("Y-m-d H:i:s");
            foreach ($value as $val){
                $row = [];
                $row['sku']             = $val['sku'];
                $row['refund_rate']     = $val['rate'];
                $row['supplier_code']   = isset($sku_temp[$val['sku']])?$sku_temp[$val['sku']]:'';
                $row['last_change_info']= $uid." ".$time;
                if(in_array($val['sku'], $has_list)){
                    if($this->purchase_db->where(["statistics_type" => 1, "sku" => $row['sku']])->update($this->table_name, $row)) continue;
                }else{
                    $row['statistics_type']     = 1;
                    if($this->purchase_db->insert($this->table_name, $row)) continue;
                }
                $err[] = $val['sku'];
            }
        }

        if(count($err) == 0){
            $res['code'] = 1;
            $res['msg'] = "更新成功！";
        }else{
            $res['msg'] = "部分更新成功！";
        }

        return $res;
    }

    /**
     * 退款率编辑
     */
    public function refund_rate_edit($sku=[])
    {
        $res = ["code" => 0, "msg" => "更新失败！"];
        try{
            $this->purchase_db->trans_begin();
            $change = getActiveUserName()." ".date("Y-m-d H:i:s");

            foreach ($sku as $key=>$val){
                $this->purchase_db->where(["sku" => $key,"statistics_type" => 1])->update($this->table_name, ["refund_rate" => $val, "last_change_info" => $change]);
            }
            if($this->purchase_db->trans_status()){
                $this->purchase_db->trans_commit();
            }else{
                throw new Exception('事务提交出错');
            }
            $res['code'] = 1;
        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $res['message'] = $e->getMessage();
        }
        return $res;
    }

    /**
     * 根据备货单获取相应的验货信息
     */
    public function get_order_by_suggest($param=[])
    {
        $res = ["code" => 0, "msg" => '没有数据'];
        $data = $this->purchase_db->from("pur_purchase_order_items as it")
            ->join("pur_purchase_order as o", "o.purchase_number=it.purchase_number", "inner")
            ->select("it.purchase_number,it.sku,it.demand_number,it.confirm_amount,o.supplier_code,o.supplier_name")
            ->where_in("it.demand_number", $param)
            ->get()
            ->result_array();
        if(!$data && empty($data))return $res;
        $res['data'] = $data;
        $res['code'] = 1;
        return $res;
    }

    /**
     * 验证备货单状态
     */
    public function check_suggest_status($param=[])
    {
        $status = [PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE];
        $data = $this->purchase_db->from("pur_purchase_suggest")
            ->select("demand_number")
            ->where_in("demand_number", $param)
            ->where_not_in('suggest_order_status', $status)
            ->get()
            ->result_array();
        return $data && count($data) > 0 ? array_column($data, 'demand_number'): [];
    }

    /**
     * 获取批量确认数据
     */
    public function get_check_confirm($param=[], $ids=[])
    {
        $res = ["code" => 0, "msg" => '没有数据'];
        $query = $this->purchase_db->from("supplier_check_sku as sc")
            ->select("sc.*,o.check_code,o.purchase_number,o.supplier_code,o.supplier_name,o.supplier_check_times,
            o.contact_province,o.contact_city,o.contact_area,o.contact_address,o.complete_address,o.apply_user_id,
            o.apply_user_name,o.apply_remark,o.status,o.warehouse_code,o.purchase_type_id,o.order_type,
            o.check_suggest,o.is_push,o.push_time,sc.is_urgent,o.check_times,o.is_special,o.is_abnormal,sc.brand,
            cr.contact_person as contact,cr.phone_number as phone,cr.check_expect_time as expect,o.apply_remark as remark")
            ->join("pur_supplier_check as o", "o.id=sc.check_id", "inner")
            ->join("pur_supplier_check_record as cr", "cr.check_id=sc.check_id", "left");

        if(count($param) > 0)$query->where_in("sc.demand_number", $param);
        if(count($ids) > 0)$query->where_in("sc.id", $ids);

        $data = $query->get()->result_array();
        if(!$data || empty($data))return $res;
        $data_temp = [];
        foreach ($data as $val){
            $val['expect'] = $val['expect'] == '0000-00-00' || empty($val['expect']) ? '': $val['expect'];
            $data_temp[] = $val;
        }
        $res['data'] = $data_temp;
        $res['code'] = 1;
        return $res;
    }

    /**
     * 新增验货申请
     */
    public function create_check_save($param=[])
    {
        $this->load->model('supplier/check/Check_product_model', 'check_product');
        return $this->check_product->create_check_save($param);
    }

    /**
     * 新增/保存批量确认数据
     */
    public function save_check_confirm($param=[])
    {
        $this->load->model('supplier/check/Check_product_model', 'check_product');
        return $this->check_product->save_check_confirm($param);
    }

    /**
     * 兼容验货旧数据
     */
    public function handle_old_data_push_product($id=0, $time=0, $status=0, $push=0, $type=false)
    {
        $res = ["code" => 0, "msg"=>"默认失败"];
        if($id == 0 || $time == 0){
            $res['msg'] = 'id或者推送次数为空！';
            return $res;
        }

        if($type && $type ==2){
            $sql = "update pur_supplier_check_sku set check_id=0 where id={$id}";
            $res['msg'] = '修改失败！';
            if($this->purchase_db->query($sql)){
                $res['msg'] = '修改成功！';
            }
            return $res;
        }

        if($type && $type ==3){
            $sql = "update pur_supplier_check_record set check_id=0 where id={$id}";
            $res['msg'] = '修改失败！';
            if($this->purchase_db->query($sql)){
                $res['msg'] = '修改成功！';
            }
            return $res;
        }

        $sql = "update pur_supplier_check as sc
            left join pur_supplier_check_sku as scs on sc.id=scs.check_id 
            left join pur_purchase_order_items as it on scs.demand_number=it.demand_number 
            left join pur_supplier_check_record as scr on sc.id=scr.check_id 
            set sc.check_times={$time},sc.is_push={$push},sc.status={$status},scs.batch_no={$time},scr.batch_no={$time},it.check_status={$status} 
            where sc.id={$id}";
        $res['msg'] = '修改失败！';
        if($this->purchase_db->query($sql)){
            $res['msg'] = '修改成功！';
        }
        return $res;
    }

    /**
     * 修改产品图片
     */
    public function change_product_image($sku, $images=false, $images_big=false)
    {
        $res = ["code" => 0, "msg"=>"默认失败"];
        $query = [];
        if($images)$query['product_thumb_url'] = $images;
        if($images_big)$query['product_img_url'] = $images_big;
        if(empty($query)){
            $res['msg'] = '更新图片不能为空！';
            return $res;
        }
        $res['msg'] = '更新失败！';
        if($this->purchase_db->where(['sku' => $sku])->update("product", $query)){
            $res['code'] = 1;
            $res['msg'] = '更新成功！';
        };
        return $res;
    }
}