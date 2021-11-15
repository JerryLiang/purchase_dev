<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/11/20
 * Time: 9:51
 */
class Purchase_order_remark_model extends Purchase_model
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'purchase_order_remark';
    }

    /**
     * 批量添加备注
     * @author Manson
     * @param $purchase_number
     * @param $remark
     * @return bool
     * @throws Exception
     */
    public function batch_add($purchase_number,$remark,$id=[], $image=null)
    {
        $data = [];
        if(is_array($id) && count($id) > 0){
            $data = $this->purchase_db->from("purchase_order_items")->select("id,purchase_number,sku")->where_in("id", $id)->get()->result_array();
        }else{
            $purchase_number = explode(',',$purchase_number);
            $purchase_number = array_filter($purchase_number);
            if (empty($purchase_number)){
                throw new Exception("参数错误");
            }
            foreach ($purchase_number as $val){
                $data[] = [
                    "purchase_number" => $val
                ];
            }
        }
        if(empty($data))return false;
        foreach ($data as $val){
            $row = [
                'purchase_number' => $val['purchase_number'],
                'remark' => $remark,
                'create_time' => date('Y-m-d H:i:s'),
                'user_name' => getActiveUserName(),
                'user_id' => getActiveUserId(),
            ];
            if(isset($val['id']))$row['items_id'] = $val['id'];
            if(isset($val['sku']))$row['sku'] = $val['sku'];
            if(!empty($image))$row['images'] = json_encode($image);
            $batch_params[] =  $row;
        }
        $result = $this->purchase_db->insert_batch($this->table_name,$batch_params);
        if(empty($result)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 根据采购单号查询采购单备注
     * @author Manson
     * @param $purchase_number
     * @return array|bool
     */
    public function get_remark_list($purchase_number)
    {
         $result = $this->purchase_db->select('*')
            ->where('purchase_number',$purchase_number)
            ->get($this->table_name)
            ->result_array();
         return empty($result)?false:$result;
    }

}