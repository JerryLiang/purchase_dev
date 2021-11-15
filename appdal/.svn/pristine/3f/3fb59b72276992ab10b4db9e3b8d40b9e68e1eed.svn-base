<?php

/**
 * Created by PhpStorm.
 * 供应商相关操作
 * User: Jolon
 * Date: 2018/12/29 0029 11:50
 */
class Supplier_purchase_amount extends Purchase_model
{

    protected $table_name = 'supplier_purchase_amount';

    /**
     * 返回表名
     * MY_Model 中的 filterNotExistFields() 方法需要
     * @return string
     */
    public function table_nameName()
    {
        return $this->table_name;
    }

    /**
     * Supplier_model constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('purchase/Purchase_order_model');

    }

    /**
     * 获取 指定供应商 指定月份的采购总金额
     * @param $date
     * @param $supplier_code
     * @return array
     */
    public function get_calculate_amount($date,$supplier_code){
        $have_count = $this->purchase_db->where('date',$date)
            ->where('supplier_code',$supplier_code)
            ->get($this->table_name)
            ->row_array();

        return $have_count?$have_count:[];
    }

    /**
     * 生成 当前月份需要计算采购金额的 供应商
     * @param $date
     * @return bool
     */
    public function create_need_run_data($date){
        $this->purchase_db->where('date',$date)->delete($this->table_name);

        $sql = "INSERT INTO pur_supplier_purchase_amount(`date`,`supplier_code`)
                SELECT '{$date}',supplier_code FROM pur_supplier";

        $this->purchase_db->query($sql);

        return true;
    }


    /**
     * 计算所有 供应商 当月的采购金额
     * @param string $date
     * @return bool
     */
    public function calculate_purchase_amount($date = ''){
        $date       = $date?$date:date('Y-m-').'01';

        $date       = date("Y-m-01", strtotime('-1 month',strtotime($date)));// 获取上个月的一号 的日期
        $begin_time = date('Y-m-01 00:00:00',strtotime($date));// 获取上个月的月初
        $end_time   = date('Y-m-t',strtotime($date)).' 23:59:59';// 获取上个月的月末

        if($date >= date('Y-m-').'01'){
            return false;
        }

        // 当月 供应商金额汇总数据 初始化
        $have_count = $this->purchase_db->select('count(1) as total')->where('date',$date)->get($this->table_name)->row_array();
        if(!isset($have_count['total']) or $have_count['total'] < 10000){
            $this->create_need_run_data($date);
        }

        $flag = true;
        $page_count = 0;// 页码计数：每次运行十页数据 五千条数据
        do{
            $need_list = $this->purchase_db->select('id,supplier_code')->where('is_run',0)->where('date',$date)->get($this->table_name,500)->result_array();
            if($need_list){
                foreach($need_list as $value){
                    $id = $value['id'];
                    $supplier_code = $value['supplier_code'];

                    if (empty($supplier_code)) continue;

                    $purchase_price_arr = $this->Purchase_order_model->get_purchase_amount_by_supplier_code($supplier_code,$begin_time,$end_time);

                    $update = [
                        'is_run' => 1,
                    ];
                    if($purchase_price_arr){
                        $update['purchase_amount']   = $purchase_price_arr['purchase_total_price'];
                        $update['cancel_amount']     = $purchase_price_arr['cancel_total_price'];
                        $update['reportloss_amount'] = $purchase_price_arr['baosun_price'];
                        $update['actual_price']      = $purchase_price_arr['actual_price'];
                    }

                    $this->purchase_db->where('id',$id)->update($this->table_name,$update);
                }

                $page_count ++;
                if($page_count >= 10) break;
            }else{
                $flag = false;
                break;
            }

        }while($flag);

        return true;
    }


}