<?php
/**
 * Created by PhpStorm.
 * 采购单信息确认-请款金额相关信息
 * User: Jolon
 * Date: 2019/01/10 0027 11:23
 */
class Purchase_order_pay_type_model extends Purchase_model {

    protected $table_name = 'purchase_order_pay_type';

    public function __construct(){
        parent::__construct();

    }

    /**
     * 获取 采购单请款确认信息
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @return mixed
     */
    public function get_one($purchase_number){

        $row = $this->purchase_db->where('purchase_number',$purchase_number)->get($this->table_name)->row_array();

        return empty($row)?false:$row;
    }

    /**
     * 获取 采购单请款确认信息（1688订单与采购单关联关系）
     * @author Jolon
     * @param string $pai_number 1688拍单号
     * @return mixed
     */
    public function get_one_by_pai_number($pai_number){

        $row = $this->purchase_db->where('pai_number',$pai_number)->get($this->table_name)->row_array();

        return empty($row)?false:$row;
    }

    public function update_one($id,$update){
        $result = $this->purchase_db->update($this->table_name,$update,['id' => $id]);
        return $result;
    }


    public function insert_one($update){
        $result = $this->purchase_db->insert($this->table_name,$update);
        return $result;
    }


    public  function delete_one($id){
        $this->purchase_db->where('id',$id);
        return $this->purchase_db->delete($this->table_name);
    }

    /**
     * @desc 根据采购单号获取拍单号
     * @author Jeff
     * @Date 2019/9/24 14:10
     * @param $purchase_numbers
     * @return array
     * @return
     */
    public function get_pai_number($purchase_numbers)
    {
        $pai_numbers = $this->purchase_db->select('pai_number')->where_in('purchase_number',$purchase_numbers)
            ->get($this->table_name)->result_array();

        if(empty($pai_numbers)){
            return [];
        }

        return array_column($pai_numbers, 'pai_number');
    }

    /**
     * @desc 根据拍单号获取退款金额
     * @author Jeff
     * @Date 2019/9/25 13:35
     * @return
     */
    public function get_apply_amount($pai_numbers)
    {
        $apply_amounts = $this->purchase_db->select('apply_amount')->where_in('pai_number',$pai_numbers)
            ->group_by('pai_number')->get($this->table_name)->result_array();

        if(empty($apply_amounts)){
            return [];
        }

        return array_column($apply_amounts, 'apply_amount');
    }

    /**
     * @desc 根据拍单号获取退款金额
     * @author Jeff
     * @Date 2019/9/25 13:35
     * @return
     */
    public function get_refund_time_by_pai($pai_numbers)
    {
        $refund_times = $this->purchase_db->select('completed_time')->where_in('pai_number',$pai_numbers)
            ->get($this->table_name)->result_array();

        if(empty($refund_times)){
            return '0000-00-00 00:00:00';
        }

        $refund_time='0000-00-00 00:00:00';
        foreach ($refund_times as $value){
            if ( strtotime($value['completed_time']) > strtotime($refund_time) ){
                $refund_time = $value['completed_time'];
            }
        }

        return $refund_time;
    }

    /**
     * @desc 根据拍单号获取退款金额
     * @author Jeff
     * @Date 2019/9/25 13:35
     * @return
     */
    public function get_refund_time_by_purchase_number($purchase_number)
    {
        $refund_times = $this->purchase_db->select('completed_time')->where('purchase_number',$purchase_number)
            ->get($this->table_name)->row_array();

        if(empty($refund_times)){
            return '0000-00-00 00:00:00';
        }

        $refund_time='0000-00-00 00:00:00';

        if ( strtotime($refund_times['completed_time']) > strtotime($refund_time) ){
            $refund_time = $refund_times['completed_time'];
        }

        return $refund_time;
    }


    /**
     * 查询出po下的sku
     * @author Manson
     * @param $purchase_number
     * @return array|bool
     */
    public function get_sku_by_po($purchase_number){
        if (empty($purchase_number)){
            return false;
        }
        $result = $this->purchase_db->select('b.sku')
            ->distinct()
            ->from('purchase_order_pay_type a')
            ->join('purchase_order_items b','a.purchase_number = b.purchase_number', 'LEFT')
            ->where('a.purchase_number',$purchase_number)
            ->get()
            ->result_array();
        if (!empty($result)){
            return array_column($result,'sku');
        }else{
            return false;
        }
    }

    /**
     * 更细采购单金额
     * @param $purchase_number
     * @return bool
     */
    public function refresh_order_price($purchase_number){

        // 采购单的总商品额
        $total_money = $this->purchase_db
            ->select('sum(poi.purchase_unit_price * poi.confirm_amount) as product_money,t.freight,t.discount,t.process_cost,t.commission')
            ->from('purchase_order as po')
            ->join($this->table_name." as t", "t.purchase_number=po.purchase_number", "left")
            ->join('purchase_order_items as poi', 'po.purchase_number=poi.purchase_number')
            ->where('poi.purchase_number', $purchase_number)
            ->get()
            ->row_array();

        $freight = $total_money['freight']??0;
        $discount = $total_money['discount']??0;
        $process_cost = $total_money['process_cost']??0;
        $commission = $total_money['commission']??0;
        $real_price = $total_money['product_money'] + $freight + $discount + $process_cost + $commission;
        $update_data = [
            'product_money' => $total_money['product_money'],
            'real_price'    => format_price($real_price),
        ];

        $this->purchase_db->where('purchase_number', $purchase_number)
            ->update($this->table_name, $update_data, null, 1);

        return true;
    }

    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd-H:i:s")."-".($b[0] * 1000);
    }

    /**
     * 更新 网采单的应付款时间
     * @param string $purchase_number 采购单号
     * @param string $pai_number 拍单号
     * @param string $datetime 日期时间
     * @return bool
     */
    public function update_ali_accout_period_time($purchase_number = null,$pai_number = null,$datetime){
        if(empty($purchase_number) and empty($pai_number)) return false;
        if(empty($datetime)) return false;

        if(empty($purchase_number)){
            $orderPayType = $this->get_one_by_pai_number($pai_number);
            if(empty($orderPayType)) return false;
            $purchase_number = $orderPayType['purchase_number'];
        }

        $this->purchase_db->where('purchase_number',$purchase_number)
            ->update('purchase_order_items',['need_pay_time' => substr($datetime,0,10)]);

        $this->purchase_db->where('purchase_number',$purchase_number)
            ->update($this->table_name,['accout_period_time' => $datetime]);

        $this->purchase_db->where('pur_number',$purchase_number)
            ->update('purchase_order_pay',['need_pay_time' => substr($datetime,0,10)]);

        return true;

    }
}