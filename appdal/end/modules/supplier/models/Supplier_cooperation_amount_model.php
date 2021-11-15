<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/12
 * Time: 9:40
 */
class Supplier_cooperation_amount_model extends Purchase_model{
    protected $table_name = 'pur_supplier_cooperation_amount';
    protected $warehouse_main_table = 'pur_warehouse_results';

    /**
     * 返回表名
     * MY_Model 中的 filterNotExistFields() 方法需要
     * @return string
     */
    public function table_nameName(){
        return $this->table_name;
    }


    /** 生成供应商合作金额记录
     * @param int $type 类型  1 按照天生成数据（新系统）  2 按照天生成数据（老系统）
     * @param string $result 待处理的数据
     * @param string $default_date 结算日期（入库数据查询的日期）
     * @return array
     */
    public function make_cooperation_record($type = 1,$result = '',$default_date = ''){

        try{
            if(!in_array($type,[1,2])){
                throw new Exception('记录类型不存在');
            }

            $start = $end = '';
            if($default_date){
                $start = date('Y-m-d',strtotime($default_date));
                $end   = $start .' 23:59:59';
            }
            $result = $type == 2 ? $result : $this->calculate_cooperation_amount($start,$end);

            if(empty($result)){
                throw new Exception('暂无数据');
            }

            foreach ($result as $re){
                $record = $this->get_cooperation_record($re['supplier_code'],$default_date);
                $data = [
                    'supplier_code'     => $re['supplier_code'],
                    'calculate_date'    => $default_date,// 入库数据查询的日期
                    'create_time'       => date('Y-m-d H:i:s'),
                ];
                if(isset($re['cooperation_amount'])){
                    $data['new_purchase'] = $re['cooperation_amount'];
                }

                if(isset($re['old_purchase'])){
                    $data['old_purchase'] = $re['old_purchase'];
                }

                if ($type == 1) {
                    $data['payment_days_online_amount']   = $re['payment_days_online_amount'];
                    $data['payment_days_offline_amount']  = $re['payment_days_offline_amount'];
                    $data['no_payment_days_amount']       = $re['no_payment_days_amount'];


                }

                if($record){
                    $new_purchase = isset($data['new_purchase']) ? $data['new_purchase'] : $record['new_purchase'];
                    $old_purchase = isset($data['old_purchase']) ? $data['old_purchase'] : $record['old_purchase'];
                    $data['cooperation_amount'] = $new_purchase + $old_purchase;
                    $res = $this->purchase_db->where('id',$record['id'])->update($this->table_name,$data);
                }else{
                    $new_purchase = isset($data['new_purchase']) ? $data['new_purchase'] : 0;
                    $old_purchase = isset($data['old_purchase']) ? $data['old_purchase'] : 0;
                    $data['cooperation_amount'] = $new_purchase + $old_purchase;
                    $res = $this->purchase_db->insert($this->table_name,$data);

                }

                if(!$res){
                    throw new Exception('数据处理失败'.json_encode($data));
                }

                apiRequestLogInsert(
                    [
                        'record_number'     => $re['supplier_code'],
                        'record_type'       => $this->table_name,
                        'post_content'      => '供应商合作金额统计',
                        'response_content'  => $result,
                    ]);
            }
            $return = ['code' => 1,'msg'=>'成功'];
        }catch (Exception $e){
            $return = ['code' => 0,'msg'=>$e->getMessage()];
        }
        return $return;
    }


    /** 根据供应商编码查询记录是否存在
     * @param string $supplier_code 供应商编码
     * @param string $calculate_date 结算日期  2019-08-12
     * @return array
     */
    public function get_cooperation_record($supplier_code,$calculate_date){
        return $this->purchase_db->where('supplier_code',$supplier_code)->where('calculate_date',$calculate_date)->get($this->table_name)->row_array();
    }


    /** 统计供应商合作金额（采购单价 * 入库数量） 默认按天统计
     * @param string $start_time 查询开始时间 2019-08-12 00:00:00
     * @param string $end_time 查询结束时间 2019-08-12 00:00:00
     * @param string $supplier_code 供应商编码 array || string  可以根据供应编码查询
     * @return array
     */
    public function calculate_cooperation_amount($start_time = '',$end_time = '',$supplier_code = ''){
        //将账期非账期所有结算方式分类
        $payment_days_offline_settlement = [];//线下账期支付方式
        $no_payment_days_settlement = [];//非账期支付方式
        $allSettlementList = $this->purchase_db->select('*')->from('supplier_settlement')->get()->result_array();
        foreach ($allSettlementList as $settle) {
            if ($settle['parent_id'] == 35) {
                $payment_days_offline_settlement[] = $settle['settlement_code'];

            }

            if (in_array($settle['parent_id'],[33,36])) {
                $no_payment_days_settlement[] = $settle['settlement_code'];

            }



        }


        if(empty($start_time)){
            $start_time = date('Y-m-d',strtotime("-1 day"));
        }
        if(empty($end_time))   $end_time   = $start_time.' 23:59:59';

        $builder = $this->purchase_db->select('ppo.supplier_code,sum(items.purchase_unit_price * main.instock_qty) as cooperation_amount,ppo.account_type');
        $builder->from($this->warehouse_main_table.' as main');
        $builder->join('pur_purchase_order_items as items','main.purchase_number = items.purchase_number and main.sku = items.sku');
        $builder->join('pur_purchase_order as ppo','items.purchase_number = ppo.purchase_number');

        if(!empty($supplier_code)){
            if(is_array($supplier_code)){
                $builder->where_in('ppo.supplier_code',$supplier_code);
            }else{
                $builder->where('ppo.supplier_code',$supplier_code);
            }
        }

        $builder->where('main.instock_date >=',$start_time);
        $builder->where('main.instock_date <',$end_time);
        $builder->group_by('ppo.supplier_code,ppo.account_type');
        $result = $builder->get()->result_array();

        $cal_result = [];

        if (!empty($result)) {

            $supplier_code_arr = array_unique(array_column($result,'supplier_code'));

            foreach ($supplier_code_arr as $supplier_code) {
                $total_amount = 0;
                $payment_days_online_amount = 0 ;//线上账期金额
                $payment_days_offline_amount= 0 ;//线下账期金额
                $no_payment_days_amount= 0 ;//非账期金额

                //筛查所有数据
                foreach ($result as $day_result) {
                    if ($day_result['supplier_code'] == $supplier_code ) {
                        $total_amount += $day_result['cooperation_amount'];//总金额
                        if ($day_result['account_type'] == 20) {
                            $payment_days_online_amount += $day_result['cooperation_amount'];

                        }

                        if (in_array($day_result['account_type'],$payment_days_offline_settlement )) {
                            $payment_days_offline_amount += $day_result['cooperation_amount'];

                        }


                        if (in_array($day_result['account_type'],$no_payment_days_settlement )) {
                            $no_payment_days_amount += $day_result['cooperation_amount'];

                        }




                    }


                }


                $cal_result[] = ['supplier_code'=>$supplier_code,'cooperation_amount'=>$total_amount,'payment_days_online_amount'=>$payment_days_online_amount,'payment_days_offline_amount'=>$payment_days_offline_amount,'no_payment_days_amount'=>$no_payment_days_amount];



            }

        }

        return $cal_result;
    }
}